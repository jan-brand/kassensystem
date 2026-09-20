<?php

namespace App\Modules\CashRegister\Actions;

use App\Modules\Audit\Actions\WriteAuditEventAction;
use App\Modules\CashRegister\Enums\CashSessionStatus;
use App\Modules\CashRegister\Models\CashSession;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use LogicException;

final class StartCashSessionClosingAction
{
    public function __construct(private readonly WriteAuditEventAction $audit) {}

    public function execute(CashSession $session, User $user): CashSession
    {
        return DB::transaction(function () use ($session, $user): CashSession {
            $locked = CashSession::query()->lockForUpdate()->findOrFail($session->id);

            if ($locked->status !== CashSessionStatus::Open) {
                throw new LogicException('Only an open cash session can start closing.');
            }

            $locked->update([
                'status' => CashSessionStatus::Closing,
                'closing_started_at' => now(),
            ]);

            $this->audit->execute(
                eventKey: 'cash_session.closing_started',
                actorUserId: $user->id,
                actorUsername: $user->username,
                actorDisplayName: $user->auditDisplayName(),
                subjectType: CashSession::class,
                subjectId: $locked->id,
            );

            return $locked->refresh();
        });
    }
}
