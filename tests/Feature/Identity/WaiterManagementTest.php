<?php

use App\Modules\Identity\Actions\CreateUserAction;
use App\Modules\Identity\Enums\UserRole;
use App\Modules\Identity\Services\WaiterManagementService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('lets managers create and manage waiter accounts only', function () {
    $manager = app(CreateUserAction::class)->execute(
        'waiter-admin-manager',
        '123456',
        'Mara',
        'Manager',
        UserRole::Manager,
    );
    $cashier = app(CreateUserAction::class)->execute(
        'waiter-admin-cashier',
        '123456',
        'Casey',
        'Cashier',
    );

    $service = app(WaiterManagementService::class);
    $waiter = $service->create(
        $manager,
        'service-1',
        '654321',
        'Wanda',
        'Service',
        'Wanda',
    );

    expect($waiter->role)->toBe(UserRole::Waiter)
        ->and(Hash::check('654321', $waiter->pin_hash))->toBeTrue();

    $updated = $service->update(
        $manager,
        $waiter,
        'service-2',
        'Wanda',
        'Neu',
        'W. Neu',
    );

    expect($updated->username)->toBe('service-2')
        ->and($updated->auditDisplayName())->toBe('W. Neu');

    expect(fn () => $service->setActive($cashier, $updated, false))
        ->toThrow(AuthorizationException::class);
});
