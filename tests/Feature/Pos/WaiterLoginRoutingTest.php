<?php

use App\Modules\Identity\Actions\CreateUserAction;
use App\Modules\Identity\Enums\UserRole;
use App\Surfaces\Pos\Livewire\LoginScreen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('routes waiter logins to the waiter pos instead of the cash register', function () {
    app(CreateUserAction::class)->execute(
        'login-waiter',
        '123456',
        'Wanda',
        'Waiter',
        UserRole::Waiter,
    );

    Livewire::test(LoginScreen::class)
        ->set('username', 'login-waiter')
        ->set('pin', '123456')
        ->call('login')
        ->assertRedirect(route('waiter.service'));
});
