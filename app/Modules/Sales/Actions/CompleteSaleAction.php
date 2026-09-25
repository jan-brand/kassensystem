<?php

namespace App\Modules\Sales\Actions;

use App\Modules\Audit\Actions\WriteAuditEventAction;
use App\Modules\CashRegister\Enums\CashSessionStatus;
use App\Modules\CashRegister\Models\CashSession;
use App\Modules\Identity\Models\User;
use App\Modules\Sales\Enums\PaymentMethod;
use App\Modules\Sales\Enums\SaleStatus;
use App\Modules\Sales\Models\Payment;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Services\SaleNumberGenerator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

final class CompleteSaleAction
{
    public function __construct(
        private readonly SaleNumberGenerator $numberGenerator,
        private readonly WriteAuditEventAction $audit,
    ) {}

    /**
     * @param  list<array{
     *     method: PaymentMethod|string,
     *     amount_cents: int,
     *     received_cents?: int,
     *     change_cents?: int,
     *     confirmed_by_user_id?: int|null,
     *     provider_reference?: string|null
     * }>  $paymentEntries
     */
    public function execute(Sale $sale, array $paymentEntries): Sale
    {
        return DB::transaction(function () use ($sale, $paymentEntries): Sale {
            $locked = Sale::query()
                ->with('cashier')
                ->lockForUpdate()
                ->findOrFail($sale->id);

            if ($locked->status === SaleStatus::Completed) {
                return $locked->load(['items', 'payments.confirmedBy', 'payment']);
            }

            $session = CashSession::query()
                ->lockForUpdate()
                ->findOrFail($locked->cash_session_id);

            if ($session->status !== CashSessionStatus::Open) {
                throw new LogicException('Cash session is not open.');
            }

            if ($locked->payments()->exists()) {
                throw new LogicException('Open sale already contains payment ledger entries.');
            }

            $total = (int) $locked->items()->sum('total_cents');
            $entries = $this->normalizeEntries($paymentEntries, $locked);
            $paymentTotal = array_sum(array_column($entries, 'amount_cents'));

            if ($total === 0 && $entries !== []) {
                throw new InvalidArgumentException('A zero price sale must not create a payment.');
            }

            if ($total > 0 && $paymentTotal !== $total) {
                throw new InvalidArgumentException('Payment total must exactly match the sale total.');
            }

            if ($total > 0 && $entries === []) {
                throw new InvalidArgumentException('A paid sale requires at least one payment.');
            }

            $number = $this->numberGenerator->next();
            $payments = [];
            $cashAmountCents = 0;

            foreach ($entries as $entry) {
                $payment = Payment::query()->create([
                    'sale_id' => $locked->id,
                    'method' => $entry['method'],
                    'amount_cents' => $entry['amount_cents'],
                    'received_cents' => $entry['received_cents'],
                    'change_cents' => $entry['change_cents'],
                    'confirmed_by_user_id' => $entry['confirmed_by_user_id'],
                    'provider_reference' => $entry['provider_reference'],
                    'completed_at' => now(),
                ]);

                $payments[] = $payment;

                if ($entry['method'] === PaymentMethod::Cash) {
                    $cashAmountCents += $entry['amount_cents'];
                }

                if ($entry['method'] === PaymentMethod::Paypal) {
                    $confirmer = User::query()->findOrFail($entry['confirmed_by_user_id']);

                    $this->audit->execute(
                        eventKey: 'payment.paypal.confirmed',
                        actorUserId: $confirmer->id,
                        actorUsername: $confirmer->username,
                        actorDisplayName: $confirmer->auditDisplayName(),
                        subjectType: Payment::class,
                        subjectId: $payment->id,
                        after: [
                            'sale_id' => $locked->id,
                            'amount_cents' => $entry['amount_cents'],
                            'provider_reference' => $entry['provider_reference'],
                        ],
                    );
                }
            }

            $locked->update([
                'number' => $number,
                'status' => SaleStatus::Completed,
                'subtotal_cents' => $total,
                'total_cents' => $total,
                'completed_at' => now(),
            ]);

            if ($cashAmountCents > 0) {
                $session->increment('cash_sales_cents', $cashAmountCents);
            }

            $this->audit->execute(
                eventKey: 'sale.completed',
                actorUserId: $locked->cashier_id,
                actorUsername: $locked->cashier->username,
                actorDisplayName: $locked->cashier->auditDisplayName(),
                subjectType: Sale::class,
                subjectId: $locked->id,
                after: [
                    'number' => $number,
                    'total_cents' => $total,
                    'payment_method' => count($payments) === 1 ? $payments[0]->method->value : null,
                    'received_cents' => count($payments) === 1 ? $payments[0]->received_cents : null,
                    'change_cents' => count($payments) === 1 ? $payments[0]->change_cents : null,
                    'cash_amount_cents' => $cashAmountCents,
                    'payments' => array_map(
                        static fn (Payment $payment): array => [
                            'method' => $payment->method->value,
                            'amount_cents' => $payment->amount_cents,
                        ],
                        $payments,
                    ),
                ],
            );

            return $locked->refresh()->load(['items', 'payments.confirmedBy', 'payment']);
        });
    }

    /**
     * @param  list<array{
     *     method: PaymentMethod|string,
     *     amount_cents: int,
     *     received_cents?: int,
     *     change_cents?: int,
     *     confirmed_by_user_id?: int|null,
     *     provider_reference?: string|null
     * }>  $entries
     * @return list<array{
     *     method: PaymentMethod,
     *     amount_cents: int,
     *     received_cents: int,
     *     change_cents: int,
     *     confirmed_by_user_id: int,
     *     provider_reference: string|null
     * }>
     */
    private function normalizeEntries(array $entries, Sale $sale): array
    {
        $normalized = [];

        foreach ($entries as $entry) {
            $method = $entry['method'];
            $method = $method instanceof PaymentMethod
                ? $method
                : PaymentMethod::tryFrom((string) $method);

            if (! $method instanceof PaymentMethod) {
                throw new InvalidArgumentException('Unknown payment method.');
            }

            $amount = (int) $entry['amount_cents'];

            if ($amount <= 0) {
                throw new InvalidArgumentException('Payment amount must be positive.');
            }

            $received = (int) ($entry['received_cents'] ?? $amount);
            $change = (int) ($entry['change_cents'] ?? ($received - $amount));
            $confirmedByUserId = (int) ($entry['confirmed_by_user_id'] ?? $sale->cashier_id);
            $providerReference = isset($entry['provider_reference'])
                ? trim((string) $entry['provider_reference'])
                : null;
            $providerReference = $providerReference === '' ? null : $providerReference;

            if ($confirmedByUserId < 1 || ! User::query()->whereKey($confirmedByUserId)->where('active', true)->exists()) {
                throw new InvalidArgumentException('Payment confirmation requires an active user.');
            }

            if ($method === PaymentMethod::Cash) {
                if ($received < $amount || $change !== $received - $amount) {
                    throw new InvalidArgumentException('Invalid cash payment amounts.');
                }

                $providerReference = null;
            }

            if ($method === PaymentMethod::Paypal) {
                if ($received !== $amount || $change !== 0) {
                    throw new InvalidArgumentException('PayPal payments must be confirmed for the exact amount.');
                }

                if ($providerReference === null || mb_strlen($providerReference) > 255) {
                    throw new InvalidArgumentException('PayPal payment requires a provider reference.');
                }
            }

            $normalized[] = [
                'method' => $method,
                'amount_cents' => $amount,
                'received_cents' => $received,
                'change_cents' => $change,
                'confirmed_by_user_id' => $confirmedByUserId,
                'provider_reference' => $providerReference,
            ];
        }

        return $normalized;
    }
}
