<?php

use App\Modules\Identity\Actions\CreateUserAction;
use App\Modules\Identity\Enums\Permission;
use App\Modules\Identity\Enums\UserRole;
use App\Modules\Identity\Services\AuthorizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('gives waiters hospitality access without cashier or administration rights', function () {
    $waiter = app(CreateUserAction::class)->execute(
        'waiter-permissions',
        '123456',
        'Wanda',
        'Waiter',
        UserRole::Waiter,
    );

    $authorization = app(AuthorizationService::class);

    expect($authorization->allows($waiter, Permission::HospitalityAccess))->toBeTrue()
        ->and($authorization->allows($waiter, Permission::HospitalityOrdersManage))->toBeTrue()
        ->and($authorization->allows($waiter, Permission::TicketsRedeem))->toBeTrue()
        ->and($authorization->allows($waiter, Permission::TicketsManage))->toBeFalse()
        ->and($authorization->allows($waiter, Permission::PosAccess))->toBeFalse()
        ->and($authorization->allows($waiter, Permission::SalesCreate))->toBeFalse()
        ->and($authorization->allows($waiter, Permission::AdministrationAccess))->toBeFalse()
        ->and($authorization->allows($waiter, Permission::HospitalityConfigurationManage))->toBeFalse()
        ->and($authorization->allows($waiter, Permission::UsersWaitersManage))->toBeFalse();
});

it('lets managers use hospitality and manage waiters while keeping administrator-only permissions restricted', function () {
    $manager = app(CreateUserAction::class)->execute(
        'waiter-manager',
        '123456',
        'Mara',
        'Manager',
        UserRole::Manager,
    );

    $authorization = app(AuthorizationService::class);

    expect($authorization->allows($manager, Permission::HospitalityAccess))->toBeTrue()
        ->and($authorization->allows($manager, Permission::HospitalityOrdersManage))->toBeTrue()
        ->and($authorization->allows($manager, Permission::HospitalityConfigurationManage))->toBeTrue()
        ->and($authorization->allows($manager, Permission::TicketsRedeem))->toBeTrue()
        ->and($authorization->allows($manager, Permission::TicketsManage))->toBeTrue()
        ->and($authorization->allows($manager, Permission::UsersWaitersManage))->toBeTrue()
        ->and($authorization->allows($manager, Permission::SettingsManage))->toBeFalse()
        ->and($authorization->allows($manager, Permission::AuditView))->toBeFalse();
});
