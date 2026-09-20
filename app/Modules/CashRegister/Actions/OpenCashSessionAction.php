<?php

namespace App\Modules\CashRegister\Actions;

use App\Modules\Audit\Actions\WriteAuditEventAction;
use App\Modules\CashRegister\Enums\CashSessionStatus;
use App\Modules\CashRegister\Models\CashSession;
use App\Modules\CashRegister\Models\Register;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use LogicException;

final class OpenCashSessionAction
{
    public function __construct(private readonly WriteAuditEventAction $audit) {}

    public function execute(Register $register, User $user, int $openingCashCents): CashSession
    {
        if ($openingCashCents < 0) {
            throw new LogicException('Opening cash must not be negative.');
        }

        return DB::transaction(function () use ($register, $user, $openingCashCents): CashSession {
            $lockedRegister = Register::query()->lockForUpdate()->findOrFail($register->id);

            if (! $lockedRegister->active) {
                throw new LogicException('Register is inactive.');
            }

            $hasActiveSession = CashSession::query()
                ->where('register_id', $lockedRegister->id)
                ->whereIn('status', [
                    CashSessionStatus::Open->value,
                    CashSessionStatus::Closing->value,
                ])
                ->exists();

            if ($hasActiveSession) {
                throw new LogicException('Register already has an active cash session.');
            }

            $session = CashSession::query()->create([
                'register_id' => $lockedRegister->id,
                'opened_by_user_id' => $user->id,
                'status' => CashSessionStatus::Open,
                'opening_cash_cents' => $openingCashCents,
                'cash_sales_cents' => 0,
                'opened_at' => now(),
            ]);

            $this->audit->execute(
                eventKey: 'cash_session.opened',
                actorUserId: $user->id,
                actorUsername: $user->username,
                actorDisplayName: $user->auditDisplayName(),
                subjectType: CashSession::class,
                subjectId: $session->id,
                after: [
                    'register_id' => $lockedRegister->id,
                    'opening_cash_cents' => $openingCashCents,
                    'status' => CashSessionStatus::Open->value,
                ],
            );

            return $session;
        });
    }
}
