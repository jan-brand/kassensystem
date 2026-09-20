<?php

namespace App\Modules\CashRegister\Actions;

use App\Modules\Audit\Actions\WriteAuditEventAction;
use App\Modules\CashRegister\Enums\CashSessionStatus;
use App\Modules\CashRegister\Models\CashSession;
use App\Modules\Identity\Models\User;
use LogicException;

final class CancelCashSessionClosingAction
{
    public function __construct(private readonly WriteAuditEventAction $audit) {}

    public function execute(CashSession $session, User $user): CashSession
    {
        if ($session->status !== CashSessionStatus::Closing) {
            throw new LogicException('Cash session is not in closing state.');
        }

        $session->update([
            'status' => CashSessionStatus::Open,
            'closing_started_at' => null,
        ]);

        $this->audit->execute(
            eventKey: 'cash_session.closing_cancelled',
            actorUserId: $user->id,
            actorUsername: $user->username,
            actorDisplayName: $user->auditDisplayName(),
            subjectType: CashSession::class,
            subjectId: $session->id,
        );

        return $session->refresh();
    }
}
