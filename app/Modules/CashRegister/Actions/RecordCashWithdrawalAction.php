<?php

namespace App\Modules\CashRegister\Actions;

use App\Modules\Audit\Actions\WriteAuditEventAction;
use App\Modules\CashRegister\Enums\CashMovementType;
use App\Modules\CashRegister\Enums\CashSessionStatus;
use App\Modules\CashRegister\Models\CashMovement;
use App\Modules\CashRegister\Models\CashSession;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

final class RecordCashWithdrawalAction
{
    public function __construct(private readonly WriteAuditEventAction $audit) {}

    public function execute(CashSession $session, User $user, int $amountCents, string $reason): CashMovement
    {
        $reason = trim($reason);

        if ($amountCents <= 0 || $reason === '') {
            throw new InvalidArgumentException('Withdrawal amount must be positive and a reason is required.');
        }

        return DB::transaction(function () use ($session, $user, $amountCents, $reason): CashMovement {
            $locked = CashSession::query()->lockForUpdate()->findOrFail($session->id);

            if ($locked->status !== CashSessionStatus::Open) {
                throw new LogicException('Cash movements require an open cash session.');
            }

            if ($amountCents > $locked->expectedCashCents()) {
                throw new LogicException('Withdrawal would make the expected cash balance negative.');
            }

            $movement = CashMovement::query()->create([
                'cash_session_id' => $locked->id,
                'user_id' => $user->id,
                'type' => CashMovementType::Withdrawal,
                'amount_cents' => $amountCents,
                'reason' => $reason,
            ]);

            $this->audit->execute(
                eventKey: 'cash_movement.withdrawal',
                actorUserId: $user->id,
                actorUsername: $user->username,
                actorDisplayName: $user->auditDisplayName(),
                subjectType: CashMovement::class,
                subjectId: $movement->id,
                after: [
                    'cash_session_id' => $locked->id,
                    'amount_cents' => $amountCents,
                    'reason' => $reason,
                ],
            );

            return $movement;
        });
    }
}
