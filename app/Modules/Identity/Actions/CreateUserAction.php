<?php

namespace App\Modules\Identity\Actions;

use App\Modules\Audit\Actions\WriteAuditEventAction;
use App\Modules\Identity\Enums\UserRole;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

final class CreateUserAction
{
    public function __construct(
        private readonly WriteAuditEventAction $audit,
    ) {}

    public function execute(
        string $username,
        string $pin,
        string $firstName,
        string $lastName,
        UserRole $role = UserRole::Cashier,
        ?string $displayName = null,
        ?string $email = null,
        ?string $phone = null,
        ?User $actor = null,
    ): User {
        $username = strtolower(trim($username));
        $firstName = trim($firstName);
        $lastName = trim($lastName);

        if ($username === '' || $firstName === '' || $lastName === '') {
            throw new InvalidArgumentException('Username, first name and last name are required.');
        }

        $this->assertValidPin($pin);

        if (User::query()->where('username', $username)->exists()) {
            throw new InvalidArgumentException('Username is already in use.');
        }

        $user = User::query()->create([
            'username' => $username,
            'pin_hash' => Hash::make($pin),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'display_name' => $displayName !== null ? trim($displayName) : null,
            'email' => $email !== null ? trim($email) : null,
            'phone' => $phone !== null ? trim($phone) : null,
            'role' => $role,
            'active' => true,
            'pin_changed_at' => now(),
        ]);

        $this->audit->execute(
            eventKey: 'user.created',
            actorUserId: $actor?->id,
            actorUsername: $actor?->username,
            actorDisplayName: $actor?->auditDisplayName(),
            subjectType: User::class,
            subjectId: $user->id,
            after: [
                'username' => $user->username,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'display_name' => $user->display_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role->value,
                'active' => $user->active,
            ],
        );

        return $user;
    }

    private function assertValidPin(string $pin): void
    {
        $length = (int) config('kassensystem.pin_length', 6);

        if (! preg_match('/^\d{'.$length.'}$/D', $pin)) {
            throw new InvalidArgumentException("PIN must contain exactly {$length} digits.");
        }
    }
}
