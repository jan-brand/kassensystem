<?php

namespace App\Modules\Identity\Actions;

use App\Modules\Audit\Actions\WriteAuditEventAction;
use App\Modules\Identity\Enums\UserRole;
use App\Modules\Identity\Models\User;
use LogicException;

final class ChangeUserRoleAction
{
    public function __construct(
        private readonly WriteAuditEventAction $audit,
    ) {}

    public function execute(User $user, UserRole $role, ?User $actor = null): User
    {
        if (
            $user->active
            && $user->role === UserRole::Administrator
            && $role !== UserRole::Administrator
            && User::query()
                ->where('active', true)
                ->where('role', UserRole::Administrator->value)
                ->count() <= 1
        ) {
            throw new LogicException('The last active administrator cannot lose the administrator role.');
        }

        $before = ['role' => $user->role->value];

        $user->update(['role' => $role]);

        $this->audit->execute(
            eventKey: 'user.role_changed',
            actorUserId: $actor?->id,
            actorUsername: $actor?->username,
            actorDisplayName: $actor?->auditDisplayName(),
            subjectType: User::class,
            subjectId: $user->id,
            before: $before,
            after: ['role' => $user->role->value],
        );

        return $user->refresh();
    }
}
