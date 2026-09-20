<?php

namespace App\Modules\Identity\Services;

use App\Modules\Audit\Actions\WriteAuditEventAction;
use App\Modules\Identity\Actions\CreateUserAction;
use App\Modules\Identity\Actions\ResetUserPinAction;
use App\Modules\Identity\Actions\SetUserActiveAction;
use App\Modules\Identity\Enums\UserRole;
use App\Modules\Identity\Models\User;
use InvalidArgumentException;
use LogicException;

final class CashierManagementService
{
    public function __construct(
        private readonly CreateUserAction $createUser,
        private readonly ResetUserPinAction $resetUserPin,
        private readonly SetUserActiveAction $setUserActive,
        private readonly WriteAuditEventAction $audit,
    ) {}

    public function create(
        User $actor,
        string $username,
        string $pin,
        string $firstName,
        string $lastName,
        ?string $displayName = null,
        ?string $email = null,
        ?string $phone = null,
    ): User {
        $this->assertActor($actor);

        return $this->createUser->execute(
            username: $username,
            pin: $pin,
            firstName: $firstName,
            lastName: $lastName,
            role: UserRole::Cashier,
            displayName: $displayName,
            email: $email,
            phone: $phone,
            actor: $actor,
        );
    }

    public function update(
        User $actor,
        User $cashier,
        string $username,
        string $firstName,
        string $lastName,
        ?string $displayName = null,
        ?string $email = null,
        ?string $phone = null,
    ): User {
        $this->assertActor($actor);
        $this->assertCashier($cashier);

        $username = strtolower(trim($username));
        $firstName = trim($firstName);
        $lastName = trim($lastName);
        $displayName = $this->nullableTrim($displayName);
        $email = $this->nullableTrim($email);
        $phone = $this->nullableTrim($phone);

        if ($username === '' || $firstName === '' || $lastName === '') {
            throw new InvalidArgumentException('Username, first name and last name are required.');
        }

        if (User::query()->where('username', $username)->where('id', '!=', $cashier->id)->exists()) {
            throw new InvalidArgumentException('Username is already in use.');
        }

        if (
            $email !== null
            && User::query()->where('email', $email)->where('id', '!=', $cashier->id)->exists()
        ) {
            throw new InvalidArgumentException('Email address is already in use.');
        }

        $before = $this->auditFields($cashier);

        $cashier->update([
            'username' => $username,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'display_name' => $displayName,
            'email' => $email,
            'phone' => $phone,
        ]);

        $fresh = $cashier->refresh();

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

    public function setActive(User $actor, User $cashier, bool $active): User
    {
        $this->assertActor($actor);
        $this->assertCashier($cashier);

        return $this->setUserActive->execute($cashier, $active, $actor);
    }

    public function resetPin(User $actor, User $cashier, string $pin): User
    {
        $this->assertActor($actor);
        $this->assertCashier($cashier);

        return $this->resetUserPin->execute($cashier, $pin, $actor);
    }

    private function assertActor(User $actor): void
    {
        if (
            ! $actor->active
            || ! in_array($actor->role, [UserRole::Manager, UserRole::Administrator], true)
        ) {
            throw new LogicException('Only active managers and administrators may manage cashiers.');
        }
    }

    private function assertCashier(User $user): void
    {
        if ($user->role !== UserRole::Cashier) {
            throw new LogicException('This area may only manage cashier accounts.');
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
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role->value,
            'active' => $user->active,
        ];
    }
}
