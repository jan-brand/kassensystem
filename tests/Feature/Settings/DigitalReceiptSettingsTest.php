<?php

use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Identity\Actions\CreateUserAction;
use App\Modules\Identity\Enums\UserRole;
use App\Modules\Settings\Actions\UpdateSystemSettingsAction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('stores and audits the digital receipt retention period', function () {
    $admin = app(CreateUserAction::class)->execute(
        'receipt-retention-admin',
        '123456',
        'Ada',
        'Admin',
        UserRole::Administrator,
    );

    $settings = app(UpdateSystemSettingsAction::class)->execute(
        actor: $admin,
        cafeteriaName: 'Schulcafeteria',
        receiptRetentionDays: 90,
    );

    expect($settings->receipt_retention_days)->toBe(90);

    $audit = AuditEvent::query()
        ->where('event_key', 'settings.updated')
        ->latest('id')
        ->firstOrFail();

    expect($audit->after['receipt_retention_days'] ?? null)->toBe(90);
});

it('rejects invalid digital receipt retention periods', function (int $days) {
    $admin = app(CreateUserAction::class)->execute(
        'receipt-retention-invalid-'.$days,
        '123456',
        'Ada',
        'Admin',
        UserRole::Administrator,
    );

    expect(fn () => app(UpdateSystemSettingsAction::class)->execute(
        actor: $admin,
        cafeteriaName: 'Schulcafeteria',
        receiptRetentionDays: $days,
    ))->toThrow(InvalidArgumentException::class);
})->with([0, 3651]);
