<?php

namespace App\Modules\Sales\Actions;

use App\Modules\Audit\Actions\WriteAuditEventAction;
use App\Modules\CashRegister\Enums\CashSessionStatus;
use App\Modules\CashRegister\Models\CashSession;
use App\Modules\Sales\Enums\PaymentMethod;
use App\Modules\Sales\Enums\SaleStatus;
use App\Modules\Sales\Models\Payment;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Services\SaleNumberGenerator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

final class CompleteCashSaleAction
{
    public function __construct(
        private readonly SaleNumberGenerator $numberGenerator,
        private readonly WriteAuditEventAction $audit,
    ) {}

    public function execute(Sale $sale, int $receivedCents): Sale
    {
        if ($receivedCents < 0) {
            throw new InvalidArgumentException('Received cash must not be negative.');
        }

        return DB::transaction(function () use ($sale, $receivedCents): Sale {
            $locked = Sale::query()->lockForUpdate()->findOrFail($sale->id);

            if ($locked->status === SaleStatus::Completed) {
                return $locked->load(['items', 'payment']);
            }

            $session = CashSession::query()
                ->lockForUpdate()
                ->findOrFail($locked->cash_session_id);

            if ($session->status !== CashSessionStatus::Open) {
                throw new LogicException('Cash session is not open.');
            }

            $total = (int) $locked->items()->sum('total_cents');

            if ($receivedCents < $total) {
                throw new InvalidArgumentException('Received cash is below the sale total.');
            }

            $number = $this->numberGenerator->next();

            if ($total > 0) {
                Payment::query()->create([
                    'sale_id' => $locked->id,
                    'method' => PaymentMethod::Cash,
                    'amount_cents' => $total,
                    'received_cents' => $receivedCents,
                    'change_cents' => $receivedCents - $total,
                    'completed_at' => now(),
                ]);
            }

            $locked->update([
                'number' => $number,
                'status' => SaleStatus::Completed,
                'subtotal_cents' => $total,
                'total_cents' => $total,
                'completed_at' => now(),
            ]);

            $session->increment('cash_sales_cents', $total);

            $locked->refresh();

            $this->audit->execute(
                eventKey: 'sale.completed',
                actorUserId: $locked->cashier_id,
                actorUsername: $locked->cashier->username,
                actorDisplayName: $locked->cashier->auditDisplayName(),
                subjectType: Sale::class,
                subjectId: $locked->id,
                after: [
                    'number' => $locked->number,
                    'total_cents' => $total,
                    'payment_method' => $total > 0 ? PaymentMethod::Cash->value : null,
                    'received_cents' => $total > 0 ? $receivedCents : null,
                    'change_cents' => $total > 0 ? $receivedCents - $total : null,
                ],
            );

            return $locked->load(['items', 'payment']);
        });
    }
}
