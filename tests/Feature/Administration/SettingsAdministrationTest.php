<?php

use App\Modules\Audit\Models\AuditEvent;
use App\Modules\CashRegister\Models\Register;
use App\Modules\Identity\Actions\CreateUserAction;
use App\Modules\Identity\Enums\UserRole;
use App\Modules\Settings\Actions\UpdateSystemSettingsAction;
use App\Modules\Settings\Models\SystemSetting;
use App\Surfaces\Administration\Livewire\SettingsScreen;
use App\Surfaces\Pos\Livewire\LoginScreen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('protects the settings route and allows only administrators', function () {
    expect(Route::has('administration.settings'))->toBeTrue();

    $this->get(route('administration.settings'))->assertRedirect(route('login'));

    $cashier = app(CreateUserAction::class)->execute(
        'cashier-settings',
        '123456',
        'Casey',
        'Cashier',
    );
    $this->actingAs($cashier)->get(route('administration.settings'))->assertForbidden();

    $manager = app(CreateUserAction::class)->execute(
        'manager-settings',
        '123456',
        'Mara',
        'Manager',
        UserRole::Manager,
    );
    $this->actingAs($manager)->get(route('administration.settings'))->assertForbidden();

    $administrator = app(CreateUserAction::class)->execute(
        'administrator-settings',
        '123456',
        'Ada',
        'Admin',
        UserRole::Administrator,
    );
    $this->actingAs($administrator)->get(route('administration.settings'))->assertOk();
});

it('saves visible system and register settings with audit events', function () {
    $administrator = app(CreateUserAction::class)->execute(
        'administrator-settings-save',
        '123456',
        'Ada',
        'Admin',
        UserRole::Administrator,
    );
    $this->actingAs($administrator);

    Livewire::test(SettingsScreen::class)
        ->set('cafeteriaName', 'Schulcafeteria Nord')
        ->set('registerName', 'Hauptkasse')
        ->set('posShowShortNames', false)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('notice', 'Einstellungen wurden gespeichert.');

    $settings = SystemSetting::query()->findOrFail(1);
    $register = Register::query()->where('active', true)->firstOrFail();

    expect($settings->cafeteria_name)->toBe('Schulcafeteria Nord')
        ->and($settings->pos_show_short_names)->toBeFalse()
        ->and($register->name)->toBe('Hauptkasse')
        ->and(AuditEvent::query()->where('event_key', 'settings.updated')->count())->toBe(1)
        ->and(AuditEvent::query()->where('event_key', 'register.updated')->count())->toBe(1);

    Auth::logout();

    Livewire::test(LoginScreen::class)
        ->assertSee('Schulcafeteria Nord');
});

it('validates required names on the server', function () {
    $administrator = app(CreateUserAction::class)->execute(
        'administrator-settings-validation',
        '123456',
        'Ada',
        'Admin',
        UserRole::Administrator,
    );
    $this->actingAs($administrator);

    Livewire::test(SettingsScreen::class)
        ->set('cafeteriaName', '')
        ->set('registerName', '')
        ->call('save')
        ->assertHasErrors([
            'cafeteriaName' => 'required',
            'registerName' => 'required',
        ]);

    expect(SystemSetting::query()->count())->toBe(0);
});

it('can remove an application managed logo', function () {
    Storage::fake('public');
    Storage::disk('public')->put('settings/logos/old-logo.png', 'logo');

    $administrator = app(CreateUserAction::class)->execute(
        'administrator-settings-logo',
        '123456',
        'Ada',
        'Admin',
        UserRole::Administrator,
    );

    app(UpdateSystemSettingsAction::class)->execute(
        actor: $administrator,
        cafeteriaName: 'Schulcafeteria',
        logoPath: 'settings/logos/old-logo.png',
        posShowShortNames: true,
    );

    $this->actingAs($administrator);

    Livewire::test(SettingsScreen::class)
        ->set('removeLogo', true)
        ->call('save')
        ->assertHasNoErrors();

    expect(SystemSetting::query()->findOrFail(1)->logo_path)->toBeNull();
    Storage::disk('public')->assertMissing('settings/logos/old-logo.png');
});
