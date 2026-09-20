<?php

use App\Modules\Identity\Enums\UserRole;
use App\Modules\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('discovers module commands and creates the initial administrator', function () {
    $this->artisan('identity:create-admin', [
        'username' => 'admin',
        '--first-name' => 'Ada',
        '--last-name' => 'Admin',
        '--pin' => '123456',
    ])->assertSuccessful();

    $admin = User::query()->where('username', 'admin')->firstOrFail();

    expect($admin->role)->toBe(UserRole::Administrator)
        ->and($admin->active)->toBeTrue()
        ->and($admin->pin_hash)->not->toBe('123456');
});
