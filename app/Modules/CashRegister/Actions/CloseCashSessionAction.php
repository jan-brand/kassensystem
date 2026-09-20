<?php

namespace App\Modules\CashRegister\Actions;

use App\Modules\Audit\Actions\WriteAuditEventAction;
use App\Modules\CashRegister\Enums\CashSessionStatus;
use App\Modules\CashRegister\Models\CashSession;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

final class CloseCashSessionAction
{
    public function __construct(private readonly WriteAuditEventAction $audit) {}

    public function execute(
        CashSession $session,
        User $user,
        int $countedCashCents,
        ?string $comment = null,
    ): CashSession {
        if ($countedCashCents < 0) {
            throw new InvalidArgumentException('Counted cash must not be negative.');
        }

        return DB::transaction(function () use ($session, $user, $countedCashCents, $comment): CashSession {
            $locked = CashSession::query()->lockForUpdate()->findOrFail($session->id);

            if ($locked->status !== CashSessionStatus::Closing) {
                throw new LogicException('Cash session must be in closing state.');
            }

            $expected = $locked->expectedCashCents();
            $difference = $countedCashCents - $expected;
            $comment = $comment !== null ? trim($comment) : null;

            if ($difference !== 0 && ($comment === null || $comment === '')) {
                throw new InvalidArgumentException('A closing comment is required when cash differs from the expected amount.');
            }

            $locked->update([
                'status' => CashSessionStatus::Closed,
                'closed_by_user_id' => $user->id,
                'closing_expected_cash_cents' => $expected,
                'closing_counted_cash_cents' => $countedCashCents,
                'closing_difference_cents' => $difference,
                'closing_comment' => $comment,
                'closed_at' => now(),
            ]);

            $this->audit->execute(
                eventKey: 'cash_session.closed',
                actorUserId: $user->id,
                actorUsername: $user->username,
                actorDisplayName: $user->auditDisplayName(),
                subjectType: CashSession::class,
                subjectId: $locked->id,
                after: [
                    'expected_cash_cents' => $expected,
                    'counted_cash_cents' => $countedCashCents,
                    'difference_cents' => $difference,
                    'comment' => $comment,
                ],
            );

            return $locked->refresh();
        });
    }
}
