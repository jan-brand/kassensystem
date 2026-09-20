<?php

use App\Modules\Identity\Actions\ChangeUserRoleAction;
use App\Modules\Identity\Actions\CreateUserAction;
use App\Modules\Identity\Actions\ResetUserPinAction;
use App\Modules\Identity\Actions\SetUserActiveAction;
use App\Modules\Identity\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('creates users with a hashed six digit pin', function () {
    $user = app(CreateUserAction::class)->execute(
        username: 'KASSE1',
        pin: '123456',
        firstName: 'Max',
        lastName: 'Muster',
    );

    expect($user->username)->toBe('kasse1')
        ->and($user->role)->toBe(UserRole::Cashier)
        ->and($user->active)->toBeTrue()
        ->and(Hash::check('123456', $user->pin_hash))->toBeTrue()
        ->and($user->pin_hash)->not->toBe('123456');
});

it('does not allow removing the last active administrator', function () {
    $admin = app(CreateUserAction::class)->execute(
        username: 'admin',
        pin: '123456',
        firstName: 'Ada',
        lastName: 'Admin',
        role: UserRole::Administrator,
    );

    expect(fn () => app(SetUserActiveAction::class)->execute($admin, false))
        ->toThrow(LogicException::class);

    expect(fn () => app(ChangeUserRoleAction::class)->execute($admin, UserRole::Manager))
        ->toThrow(LogicException::class);
});

it('can reset a pin without exposing it in the audit log', function () {
    $user = app(CreateUserAction::class)->execute(
        username: 'cashier',
        pin: '123456',
        firstName: 'Casey',
        lastName: 'Cashier',
    );

    app(ResetUserPinAction::class)->execute($user, '654321');

    expect(Hash::check('654321', $user->refresh()->pin_hash))->toBeTrue()
        ->and(\App\Modules\Audit\Models\AuditEvent::query()
            ->where('event_key', 'user.pin_reset')
            ->exists())->toBeTrue();
});
