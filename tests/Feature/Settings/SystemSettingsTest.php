<?php

use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Identity\Actions\CreateUserAction;
use App\Modules\Identity\Enums\UserRole;
use App\Modules\Settings\Actions\UpdateSystemSettingsAction;
use App\Modules\Settings\Queries\GetSystemSettingsQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates and updates the singleton system settings with audit events', function () {
    $admin = app(CreateUserAction::class)->execute(
        'admin',
        '123456',
        'Ada',
        'Admin',
        role: UserRole::Administrator,
    );

    $first = app(UpdateSystemSettingsAction::class)->execute(
        $admin,
        'Schulcafeteria',
        'logos/cafeteria.png',
        true,
    );

    $second = app(UpdateSystemSettingsAction::class)->execute(
        $admin,
        'Neue Schulcafeteria',
        null,
        false,
    );

    expect($first->id)->toBe(1)
        ->and($second->id)->toBe(1)
        ->and($second->cafeteria_name)->toBe('Neue Schulcafeteria')
        ->and($second->logo_path)->toBeNull()
        ->and($second->pos_show_short_names)->toBeFalse()
        ->and($second->updated_by_user_id)->toBe($admin->id)
        ->and(app(GetSystemSettingsQuery::class)->execute()?->id)->toBe(1)
        ->and(AuditEvent::query()->where('event_key', 'settings.updated')->count())->toBe(2);
});

it('keeps currency and timezone as central technical configuration', function () {
    expect(config('kassensystem.currency'))->toBe('EUR')
        ->and(config('kassensystem.timezone'))->toBe('Europe/Berlin');
});
