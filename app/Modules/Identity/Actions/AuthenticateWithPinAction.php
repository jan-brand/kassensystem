<?php

namespace App\Modules\Identity\Actions;

use App\Modules\Audit\Actions\WriteAuditEventAction;
use App\Modules\Identity\Exceptions\InvalidPin;
use App\Modules\Identity\Exceptions\PinRateLimited;
use App\Modules\Identity\Exceptions\UserInactive;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

final class AuthenticateWithPinAction
{
    public function __construct(
        private readonly WriteAuditEventAction $audit,
    ) {}

    public function execute(string $username, string $pin, ?string $ipAddress = null, ?string $userAgent = null): User
    {
        $username = strtolower(trim($username));
        $key = 'pin-login:'.$username.'|'.($ipAddress ?: 'unknown');
        $maxAttempts = (int) config('kassensystem.pin_max_attempts', 5);
        $lockSeconds = (int) config('kassensystem.pin_lock_seconds', 60);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            throw new PinRateLimited('Too many PIN attempts. Please try again later.');
        }

        $user = User::query()->where('username', $username)->first();

        if ($user === null || ! Hash::check($pin, $user->pin_hash)) {
            RateLimiter::hit($key, $lockSeconds);

            $this->audit->execute(
                eventKey: 'auth.login_failed',
                actorUsername: $username ?: null,
                metadata: ['reason' => 'invalid_credentials'],
                ipAddress: $ipAddress,
                userAgent: $userAgent,
            );

            throw new InvalidPin('Invalid username or PIN.');
        }

        if (! $user->active) {
            $this->audit->execute(
                eventKey: 'auth.login_failed',
                actorUserId: $user->id,
                actorUsername: $user->username,
                actorDisplayName: $user->auditDisplayName(),
                metadata: ['reason' => 'inactive_user'],
                ipAddress: $ipAddress,
                userAgent: $userAgent,
            );

            throw new UserInactive('User account is inactive.');
        }

        RateLimiter::clear($key);
        Auth::login($user);

        $user->forceFill(['last_login_at' => now()])->save();

        $this->audit->execute(
            eventKey: 'auth.login_succeeded',
            actorUserId: $user->id,
            actorUsername: $user->username,
            actorDisplayName: $user->auditDisplayName(),
            ipAddress: $ipAddress,
            userAgent: $userAgent,
        );

        return $user->refresh();
    }
}
