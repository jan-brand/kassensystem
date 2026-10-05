<?php

use App\Modules\Identity\Actions\CreateUserAction;
use App\Modules\Identity\Enums\UserRole;
use App\Surfaces\Administration\Livewire\ReportingScreen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the v2 reporting dimensions and accounting csv disclaimer', function () {
    $manager = app(CreateUserAction::class)->execute(
        'v2-reporting-ui-manager',
        '123456',
        'Mara',
        'Manager',
        UserRole::Manager,
    );

    $this->actingAs($manager);

    Livewire::test(ReportingScreen::class)
        ->assertSee('Bruttoverkauf')
        ->assertSee('Storno')
        ->assertSee('Netto-Umsatz')
        ->assertSee('Barzahlungen')
        ->assertSee('PayPal.me')
        ->assertSee('Rabatte')
        ->assertSee('Gastro-Vorgänge')
        ->assertSee('Tickets und Einlösungen')
        ->assertSee('Zubereitungsstationen')
        ->assertSee('Audit-Ereignisse')
        ->assertSee('kein DATEV-/Steuerexport');
});
