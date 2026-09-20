<?php

namespace App\Modules\Identity\Actions;

use App\Modules\Audit\Actions\WriteAuditEventAction;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

final class ResetUserPinAction
{
    public function __construct(
        private readonly WriteAuditEventAction $audit,
    ) {}

    public function execute(User $user, string $newPin, ?User $actor = null): User
    {
        $length = (int) config('kassensystem.pin_length', 6);

        if (! preg_match('/^\d{'.$length.'}$/D', $newPin)) {
            throw new InvalidArgumentException("PIN must contain exactly {$length} digits.");
        }

        $user->forceFill([
            'pin_hash' => Hash::make($newPin),
            'pin_changed_at' => now(),
        ])->save();

        $this->audit->execute(
            eventKey: 'user.pin_reset',
            actorUserId: $actor?->id,
            actorUsername: $actor?->username,
            actorDisplayName: $actor?->auditDisplayName(),
            subjectType: User::class,
            subjectId: $user->id,
            metadata: ['username' => $user->username],
        );

        return $user->refresh();
    }
}
