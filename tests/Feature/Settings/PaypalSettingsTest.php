<?php

use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Identity\Actions\CreateUserAction;
use App\Modules\Identity\Enums\UserRole;
use App\Modules\Settings\Actions\UpdateSystemSettingsAction;
use App\Modules\Settings\Queries\GetSystemSettingsQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('stores the central paypal me handle and audits changes', function () {
    $admin = app(CreateUserAction::class)->execute(
        'paypal-settings-admin',
        '123456',
        'Ada',
        'Admin',
        UserRole::Administrator,
    );

    $settings = app(UpdateSystemSettingsAction::class)->execute(
        actor: $admin,
        cafeteriaName: 'Schulcafeteria',
        paypalMeHandle: 'schoolcafe',
    );

    expect($settings->paypal_me_handle)->toBe('schoolcafe')
        ->and(app(GetSystemSettingsQuery::class)->execute()?->paypal_me_handle)->toBe('schoolcafe');

    $audit = AuditEvent::query()->where('event_key', 'settings.updated')->latest('id')->firstOrFail();

    expect($audit->after['paypal_me_handle'] ?? null)->toBe('schoolcafe');
});

it('rejects invalid paypal me handles', function () {
    $admin = app(CreateUserAction::class)->execute(
        'paypal-settings-invalid',
        '123456',
        'Ada',
        'Admin',
        UserRole::Administrator,
    );

    expect(fn () => app(UpdateSystemSettingsAction::class)->execute(
        actor: $admin,
        cafeteriaName: 'Schulcafeteria',
        paypalMeHandle: 'https://paypal.me/not-a-handle',
    ))->toThrow(InvalidArgumentException::class);
});
