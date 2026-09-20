<?php

namespace App\Modules\Identity\Actions;

use App\Modules\Audit\Actions\WriteAuditEventAction;
use App\Modules\Identity\Enums\UserRole;
use App\Modules\Identity\Models\User;
use LogicException;

final class SetUserActiveAction
{
    public function __construct(
        private readonly WriteAuditEventAction $audit,
    ) {}

    public function execute(User $user, bool $active, ?User $actor = null): User
    {
        if (
            ! $active
            && $user->active
            && $user->role === UserRole::Administrator
            && User::query()
                ->where('active', true)
                ->where('role', UserRole::Administrator->value)
                ->count() <= 1
        ) {
            throw new LogicException('The last active administrator cannot be deactivated.');
        }

        $before = ['active' => $user->active];

        $user->update(['active' => $active]);

        $this->audit->execute(
            eventKey: $active ? 'user.activated' : 'user.deactivated',
            actorUserId: $actor?->id,
            actorUsername: $actor?->username,
            actorDisplayName: $actor?->auditDisplayName(),
            subjectType: User::class,
            subjectId: $user->id,
            before: $before,
            after: ['active' => $user->active],
        );

        return $user->refresh();
    }
}
