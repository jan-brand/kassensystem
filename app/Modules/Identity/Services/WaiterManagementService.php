<?php

namespace App\Modules\Identity\Services;

use App\Modules\Audit\Actions\WriteAuditEventAction;
use App\Modules\Identity\Actions\CreateUserAction;
use App\Modules\Identity\Actions\ResetUserPinAction;
use App\Modules\Identity\Actions\SetUserActiveAction;
use App\Modules\Identity\Enums\Permission;
use App\Modules\Identity\Enums\UserRole;
use App\Modules\Identity\Models\User;
use InvalidArgumentException;
use LogicException;

final class WaiterManagementService
{
    public function __construct(
        private readonly CreateUserAction $createUser,
        private readonly ResetUserPinAction $resetUserPin,
        private readonly SetUserActiveAction $setUserActive,
        private readonly WriteAuditEventAction $audit,
        private readonly AuthorizationService $authorization,
    ) {}

    public function create(
        User $actor,
        string $username,
        string $pin,
        string $firstName,
        string $lastName,
        ?string $displayName = null,
    ): User {
        $this->authorization->authorize($actor, Permission::UsersWaitersManage);

        return $this->createUser->execute(
            username: $username,
            pin: $pin,
            firstName: $firstName,
            lastName: $lastName,
            role: UserRole::Waiter,
            displayName: $displayName,
            actor: $actor,
        );
    }

    public function update(
        User $actor,
        User $waiter,
        string $username,
        string $firstName,
        string $lastName,
        ?string $displayName = null,
    ): User {
        $this->authorization->authorize($actor, Permission::UsersWaitersManage);
        $this->assertWaiter($waiter);

        $username = strtolower(trim($username));
        $firstName = trim($firstName);
        $lastName = trim($lastName);
        $displayName = $this->nullableTrim($displayName);

        if ($username === '' || $firstName === '' || $lastName === '') {
            throw new InvalidArgumentException('Benutzername, Vorname und Nachname sind erforderlich.');
        }

        if (User::query()->where('username', $username)->where('id', '!=', $waiter->id)->exists()) {
            throw new InvalidArgumentException('Benutzername ist bereits vergeben.');
        }

        $before = $this->auditFields($waiter);

        $waiter->update([
            'username' => $username,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'display_name' => $displayName,
        ]);

        $fresh = $waiter->refresh();

        $this->audit->execute(
            eventKey: 'user.updated',
            actorUserId: $actor->id,
            actorUsername: $actor->username,
            actorDisplayName: $actor->auditDisplayName(),
            subjectType: User::class,
            subjectId: $fresh->id,
            before: $before,
            after: $this->auditFields($fresh),
        );

        return $fresh;
    }

    public function setActive(User $actor, User $waiter, bool $active): User
    {
        $this->authorization->authorize($actor, Permission::UsersWaitersManage);
        $this->assertWaiter($waiter);

        return $this->setUserActive->execute($waiter, $active, $actor);
    }

    public function resetPin(User $actor, User $waiter, string $pin): User
    {
        $this->authorization->authorize($actor, Permission::UsersWaitersManage);
        $this->assertWaiter($waiter);

        return $this->resetUserPin->execute($waiter, $pin, $actor);
    }

    private function assertWaiter(User $user): void
    {
        if ($user->role !== UserRole::Waiter) {
            throw new LogicException('Dieser Bereich verwaltet ausschließlich Kellnerkonten.');
        }
    }

    private function nullableTrim(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /** @return array<string, mixed> */
    private function auditFields(User $user): array
    {
        return [
            'username' => $user->username,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'display_name' => $user->display_name,
            'role' => $user->role->value,
            'active' => $user->active,
        ];
    }
}
