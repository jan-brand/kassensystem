<?php

use App\Modules\Identity\Actions\CreateUserAction;
use App\Modules\Identity\Enums\UserRole;
use App\Surfaces\Administration\Livewire\HospitalityConfigurationScreen;
use App\Surfaces\Administration\Livewire\WaitersScreen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('allows managers to manage hospitality configuration and waiter accounts', function () {
    $manager = app(CreateUserAction::class)->execute(
        'hospitality-admin-manager',
        '123456',
        'Mara',
        'Manager',
        UserRole::Manager,
    );
    $this->actingAs($manager);

    $this->get(route('administration.hospitality'))->assertOk();
    $this->get(route('administration.waiters'))->assertOk();

    Livewire::test(HospitalityConfigurationScreen::class)
        ->set('areaName', 'Innenraum')
        ->call('createArea')
        ->assertSee('Bereich wurde angelegt.');

    Livewire::test(WaitersScreen::class)
        ->set('username', 'service-admin-test')
        ->set('pin', '123456')
        ->set('firstName', 'Wanda')
        ->set('lastName', 'Service')
        ->call('createWaiter')
        ->assertSee('Kellner wurde angelegt.');
});
