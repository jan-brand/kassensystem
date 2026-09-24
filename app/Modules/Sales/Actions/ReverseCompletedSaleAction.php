<?php

namespace App\Modules\Sales\Actions;

use App\Modules\Audit\Actions\WriteAuditEventAction;
use App\Modules\CashRegister\Enums\CashSessionStatus;
use App\Modules\CashRegister\Models\CashSession;
use App\Modules\Identity\Enums\Permission;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Services\AuthorizationService;
use App\Modules\Sales\Enums\PaymentMethod;
use App\Modules\Sales\Enums\SaleStatus;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleItem;
use App\Modules\Sales\Models\SaleReversal;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

final class ReverseCompletedSaleAction
{
    public function __construct(
        private readonly AuthorizationService $authorization,
        private readonly WriteAuditEventAction $audit,
    ) {}

    public function execute(Sale $sale, User $actor, string $reason): SaleReversal
    {
        $this->authorization->authorize($actor, Permission::SalesReverse);

        $reason = trim($reason);

        if (mb_strlen($reason) < 3) {
            throw new InvalidArgumentException('Der Stornogrund muss mindestens 3 Zeichen enthalten.');
        }

        if (mb_strlen($reason) > 500) {
            throw new InvalidArgumentException('Der Stornogrund darf maximal 500 Zeichen enthalten.');
        }

        return DB::transaction(function () use ($sale, $actor, $reason): SaleReversal {
            $locked = Sale::query()
                ->with(['items', 'payment'])
                ->lockForUpdate()
                ->findOrFail($sale->id);

            if ($locked->status !== SaleStatus::Completed) {
                throw new LogicException('Nur abgeschlossene Verkäufe können storniert werden.');
            }

            $existing = SaleReversal::query()
                ->where('sale_id', $locked->id)
                ->first();

            if ($existing instanceof SaleReversal) {
                return $existing->load(['sale', 'actor', 'cashSession']);
            }

            $containsConsumables = $locked->items->contains(
                static fn (SaleItem $item): bool => $item->is_consumable,
            );

            if ($containsConsumables) {
                throw new LogicException(
                    'Verkäufe mit Lebensmitteln oder Getränken können in V2 nicht vollständig storniert werden.',
                );
            }

            $payment = $locked->payment;
            $paymentMethod = $payment?->method;
            $cashRefundCents = 0;
            $cashSession = null;

            if ($locked->total_cents > 0 && $payment === null) {
                throw new LogicException('Der abgeschlossene Verkauf hat keine erwartete Zahlung.');
            }

            if ($payment !== null && $payment->amount_cents !== $locked->total_cents) {
                throw new LogicException('Zahlungsbetrag und Verkaufssumme stimmen nicht überein.');
            }

            if ($paymentMethod === PaymentMethod::Cash && $locked->total_cents > 0) {
                $cashRefundCents = $locked->total_cents;
                $cashSession = CashSession::query()
                    ->where('register_id', $locked->register_id)
                    ->where('status', CashSessionStatus::Open->value)
                    ->lockForUpdate()
                    ->orderByDesc('id')
                    ->first();

                if (! $cashSession instanceof CashSession) {
                    throw new LogicException(
                        'Für die Barauszahlung muss an derselben Kasse eine offene Kassenschicht vorhanden sein.',
                    );
                }

                if ($cashSession->expectedCashCents() < $cashRefundCents) {
                    throw new LogicException(
                        'Der erwartete Bargeldbestand reicht für die Storno-Auszahlung nicht aus.',
                    );
                }

                $cashSession->increment('cash_refunds_cents', $cashRefundCents);
                $cashSession->refresh();
            }

            $reversal = SaleReversal::query()->create([
                'sale_id' => $locked->id,
                'actor_user_id' => $actor->id,
                'cash_session_id' => $cashSession?->id,
                'payment_method' => $paymentMethod?->value,
                'amount_cents' => $locked->total_cents,
                'cash_refund_cents' => $cashRefundCents,
                'reason' => $reason,
                'reversed_at' => now(),
            ]);

            $this->audit->execute(
                eventKey: 'sale.reversed',
                actorUserId: $actor->id,
                actorUsername: $actor->username,
                actorDisplayName: $actor->auditDisplayName(),
                subjectType: Sale::class,
                subjectId: $locked->id,
                after: [
                    'sale_reversal_id' => $reversal->id,
                    'sale_number' => $locked->number,
                    'reason' => $reason,
                    'amount_cents' => $locked->total_cents,
                    'payment_method' => $paymentMethod?->value,
                    'cash_refund_cents' => $cashRefundCents,
                    'cash_session_id' => $cashSession?->id,
                ],
            );

            return $reversal->load(['sale', 'actor', 'cashSession']);
        });
    }
}
