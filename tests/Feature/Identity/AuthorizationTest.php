<?php

use App\Modules\Identity\Actions\CreateUserAction;
use App\Modules\Identity\Enums\Permission;
use App\Modules\Identity\Enums\UserRole;
use App\Modules\Identity\Services\AuthorizationService;
use App\Modules\Identity\Services\CashierManagementService;
use App\Modules\Settings\Actions\UpdateSystemSettingsAction;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

it('maps permissions centrally by role', function () {
    $cashier = app(CreateUserAction::class)->execute('permission-cashier', '123456', 'Casey', 'Cashier');
    $manager = app(CreateUserAction::class)->execute(
        'permission-manager',
        '123456',
        'Mara',
        'Manager',
        UserRole::Manager,
    );
    $administrator = app(CreateUserAction::class)->execute(
        'permission-admin',
        '123456',
        'Ada',
        'Admin',
        UserRole::Administrator,
    );

    $authorization = app(AuthorizationService::class);

    expect($authorization->allows($cashier, Permission::PosAccess))->toBeTrue()
        ->and($authorization->allows($cashier, Permission::SalesCreate))->toBeTrue()
        ->and($authorization->allows($cashier, Permission::AdministrationAccess))->toBeFalse()
        ->and($authorization->allows($manager, Permission::CatalogManage))->toBeTrue()
        ->and($authorization->allows($manager, Permission::ReportsView))->toBeTrue()
        ->and($authorization->allows($manager, Permission::SettingsManage))->toBeFalse()
        ->and($authorization->allows($manager, Permission::AuditView))->toBeFalse();

    foreach (Permission::cases() as $permission) {
        expect($authorization->allows($administrator, $permission))
            ->toBeTrue("Administrator should have {$permission->value}");
    }

    $cashier->update(['active' => false]);

    expect($authorization->allows($cashier->refresh(), Permission::PosAccess))->toBeFalse();
});

it('registers the permission matrix as laravel gates', function () {
    $manager = app(CreateUserAction::class)->execute(
        'gate-manager',
        '123456',
        'Mara',
        'Manager',
        UserRole::Manager,
    );
    $administrator = app(CreateUserAction::class)->execute(
        'gate-admin',
        '123456',
        'Ada',
        'Admin',
        UserRole::Administrator,
    );

    expect(Gate::forUser($manager)->allows(Permission::ReportsView->value))->toBeTrue()
        ->and(Gate::forUser($manager)->allows(Permission::SettingsManage->value))->toBeFalse()
        ->and(Gate::forUser($administrator)->allows(Permission::SettingsManage->value))->toBeTrue();
});

it('enforces page permissions on routes and hides forbidden administration links', function () {
    $cashier = app(CreateUserAction::class)->execute('route-cashier', '123456', 'Casey', 'Cashier');
    $manager = app(CreateUserAction::class)->execute(
        'route-manager',
        '123456',
        'Mara',
        'Manager',
        UserRole::Manager,
    );
    $administrator = app(CreateUserAction::class)->execute(
        'route-admin',
        '123456',
        'Ada',
        'Admin',
        UserRole::Administrator,
    );

    $this->actingAs($cashier)->get(route('administration.catalog'))->assertForbidden();

    $this->actingAs($manager)
        ->get(route('administration.dashboard'))
        ->assertOk()
        ->assertSee('Katalog')
        ->assertDontSee('Einstellungen')
        ->assertDontSee('Audit');

    $this->actingAs($manager)->get(route('administration.settings'))->assertForbidden();

    $this->actingAs($administrator)
        ->get(route('administration.dashboard'))
        ->assertOk()
        ->assertSee('Einstellungen')
        ->assertSee('Audit');

    $this->actingAs($administrator)->get(route('administration.settings'))->assertOk();
});

it('protects management services independently from the ui', function () {
    $cashier = app(CreateUserAction::class)->execute('service-cashier', '123456', 'Casey', 'Cashier');
    $target = app(CreateUserAction::class)->execute('service-target', '123456', 'Taylor', 'Target');
    $manager = app(CreateUserAction::class)->execute(
        'service-manager',
        '123456',
        'Mara',
        'Manager',
        UserRole::Manager,
    );
    $administrator = app(CreateUserAction::class)->execute(
        'service-admin',
        '123456',
        'Ada',
        'Admin',
        UserRole::Administrator,
    );

    expect(fn () => app(CashierManagementService::class)->setActive($cashier, $target, false))
        ->toThrow(AuthorizationException::class);

    app(CashierManagementService::class)->setActive($manager, $target, false);

    expect($target->refresh()->active)->toBeFalse();

    expect(fn () => app(UpdateSystemSettingsAction::class)->execute(
        actor: $manager,
        cafeteriaName: 'Nicht erlaubt',
    ))->toThrow(AuthorizationException::class);

    $settings = app(UpdateSystemSettingsAction::class)->execute(
        actor: $administrator,
        cafeteriaName: 'Erlaubt',
    );

    expect($settings->cafeteria_name)->toBe('Erlaubt');
});
