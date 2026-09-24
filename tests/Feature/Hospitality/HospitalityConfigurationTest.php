<?php

use App\Modules\Hospitality\Actions\OpenHospitalityOrderAction;
use App\Modules\Hospitality\Services\HospitalityConfigurationService;
use App\Modules\Identity\Actions\CreateUserAction;
use App\Modules\Identity\Enums\UserRole;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('protects area and table configuration and blocks unsafe deactivation', function () {
    $manager = app(CreateUserAction::class)->execute(
        'hospitality-config-manager',
        '123456',
        'Mara',
        'Manager',
        UserRole::Manager,
    );
    $waiter = app(CreateUserAction::class)->execute(
        'hospitality-config-waiter',
        '123456',
        'Wanda',
        'Waiter',
        UserRole::Waiter,
    );

    $service = app(HospitalityConfigurationService::class);

    expect(fn () => $service->createArea($waiter, 'Innenraum'))
        ->toThrow(AuthorizationException::class);

    $area = $service->createArea($manager, 'Innenraum');
    $table = $service->createTable($manager, $area, 'Tisch 1');

    $order = app(OpenHospitalityOrderAction::class)->execute($table, $waiter);

    expect(fn () => $service->setTableActive($manager, $table, false))
        ->toThrow(LogicException::class, 'offenem Vorgang');

    expect(fn () => $service->setAreaActive($manager, $area, false))
        ->toThrow(LogicException::class, 'offenem Vorgang');

    $area = $service->updateArea($manager, $area, 'Speisesaal', 20);

    $order->refresh();

    expect($area->name)->toBe('Speisesaal')
        ->and($order->area_name_snapshot)->toBe('Innenraum')
        ->and($order->table_name_snapshot)->toBe('Tisch 1');
});
