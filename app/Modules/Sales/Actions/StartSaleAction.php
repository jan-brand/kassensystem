<?php

namespace App\Modules\Sales\Actions;

use App\Modules\Audit\Actions\WriteAuditEventAction;
use App\Modules\CashRegister\Enums\CashSessionStatus;
use App\Modules\CashRegister\Models\CashSession;
use App\Modules\CashRegister\Models\Register;
use App\Modules\Identity\Models\User;
use App\Modules\Sales\Enums\SaleStatus;
use App\Modules\Sales\Models\Sale;
use Illuminate\Support\Facades\DB;
use LogicException;

final class StartSaleAction
{
    public function __construct(private readonly WriteAuditEventAction $audit) {}

    public function execute(Register $register, CashSession $cashSession, User $cashier): Sale
    {
        return DB::transaction(function () use ($register, $cashSession, $cashier): Sale {
            Register::query()->lockForUpdate()->findOrFail($register->id);
            $lockedSession = CashSession::query()->lockForUpdate()->findOrFail($cashSession->id);

            if (
                $lockedSession->register_id !== $register->id
                || $lockedSession->status !== CashSessionStatus::Open
            ) {
                throw new LogicException('An open cash session for this register is required.');
            }

            if (! $cashier->active) {
                throw new LogicException('Cashier is inactive.');
            }

            $existing = Sale::query()
                ->where('register_id', $register->id)
                ->where('status', SaleStatus::Open->value)
                ->first();

            if ($existing !== null) {
                if ($existing->cash_session_id !== $lockedSession->id) {
                    throw new LogicException('Register has an open sale from another cash session.');
                }

                if ($existing->cashier_id !== $cashier->id) {
                    throw new LogicException('Open sale belongs to another cashier and must be completed or discarded first.');
                }

                return $existing;
            }

            $sale = Sale::query()->create([
                'register_id' => $register->id,
                'cash_session_id' => $lockedSession->id,
                'cashier_id' => $cashier->id,
                'status' => SaleStatus::Open,
                'subtotal_cents' => 0,
                'total_cents' => 0,
                'started_at' => now(),
            ]);

            $this->audit->execute(
                eventKey: 'sale.started',
                actorUserId: $cashier->id,
                actorUsername: $cashier->username,
                actorDisplayName: $cashier->auditDisplayName(),
                subjectType: Sale::class,
                subjectId: $sale->id,
                metadata: [
                    'register_id' => $register->id,
                    'cash_session_id' => $lockedSession->id,
                ],
            );

            return $sale;
        });
    }
}
