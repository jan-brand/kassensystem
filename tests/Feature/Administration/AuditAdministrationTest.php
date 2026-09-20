<?php

use App\Modules\Audit\Actions\WriteAuditEventAction;
use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Catalog\Models\Product;
use App\Modules\Identity\Actions\CreateUserAction;
use App\Modules\Identity\Enums\UserRole;
use App\Modules\Identity\Models\User;
use App\Surfaces\Administration\Livewire\AuditScreen;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('allows only administrators to open the audit browser', function () {
    expect(Route::has('administration.audit'))->toBeTrue();

    $this->get(route('administration.audit'))->assertRedirect(route('login'));

    $manager = app(CreateUserAction::class)->execute(
        'manager-audit',
        '123456',
        'Mara',
        'Manager',
        UserRole::Manager,
    );
    $this->actingAs($manager)->get(route('administration.audit'))->assertForbidden();

    $administrator = app(CreateUserAction::class)->execute(
        'administrator-audit',
        '123456',
        'Ada',
        'Admin',
        UserRole::Administrator,
    );
    $this->actingAs($administrator)->get(route('administration.audit'))->assertOk();
});

it('filters audit events by event actor subject and date range', function () {
    $administrator = app(CreateUserAction::class)->execute(
        'administrator-audit-filter',
        '123456',
        'Ada',
        'Admin',
        UserRole::Administrator,
    );

    AuditEvent::query()->create([
        'event_key' => 'product.updated',
        'actor_user_id' => $administrator->id,
        'actor_username' => $administrator->username,
        'actor_display_name' => 'Ada Admin',
        'subject_type' => Product::class,
        'subject_id' => 42,
        'after' => ['name' => 'Apfelschorle'],
        'created_at' => CarbonImmutable::parse('2026-09-20 10:30:00', 'Europe/Berlin'),
    ]);

    AuditEvent::query()->create([
        'event_key' => 'sale.completed',
        'actor_username' => 'andere-kasse',
        'actor_display_name' => 'Andere Kasse',
        'subject_type' => 'App\\Modules\\Sales\\Models\\Sale',
        'subject_id' => 99,
        'created_at' => CarbonImmutable::parse('2026-09-19 10:30:00', 'Europe/Berlin'),
    ]);

    $this->actingAs($administrator);

    Livewire::test(AuditScreen::class)
        ->set('eventKey', 'product')
        ->set('actor', 'Ada')
        ->set('subjectType', 'Product')
        ->set('subjectId', '42')
        ->set('dateFrom', '2026-09-20')
        ->set('dateTo', '2026-09-20')
        ->call('applyFilters')
        ->assertHasNoErrors()
        ->assertSee('product.updated')
        ->assertSee('1 Treffer')
        ->assertDontSee('Andere Kasse');
});

it('shows redacted audit payloads in a read only detail view', function () {
    $administrator = app(CreateUserAction::class)->execute(
        'administrator-audit-detail',
        '123456',
        'Ada',
        'Admin',
        UserRole::Administrator,
    );

    $event = app(WriteAuditEventAction::class)->execute(
        eventKey: 'user.pin_reset',
        actorUserId: $administrator->id,
        actorUsername: $administrator->username,
        actorDisplayName: $administrator->auditDisplayName(),
        subjectType: User::class,
        subjectId: $administrator->id,
        before: ['pin' => '123456'],
        after: ['pin_hash' => 'should-never-be-visible'],
        metadata: ['reason' => 'Test'],
        ipAddress: '127.0.0.1',
        userAgent: 'Pest',
    );

    $this->actingAs($administrator);

    Livewire::test(AuditScreen::class)
        ->call('showEvent', $event->id)
        ->assertSee('user.pin_reset')
        ->assertSee('[REDACTED]')
        ->assertSee('127.0.0.1')
        ->assertSee('Test')
        ->assertDontSee('123456')
        ->assertDontSee('should-never-be-visible');

    expect(fn () => $event->delete())
        ->toThrow(LogicException::class, 'Audit events cannot be deleted.');
});

it('validates audit date ranges and subject ids', function () {
    $administrator = app(CreateUserAction::class)->execute(
        'administrator-audit-validation',
        '123456',
        'Ada',
        'Admin',
        UserRole::Administrator,
    );
    $this->actingAs($administrator);

    Livewire::test(AuditScreen::class)
        ->set('subjectId', 'abc')
        ->set('dateFrom', '2026-09-21')
        ->set('dateTo', '2026-09-20')
        ->call('applyFilters')
        ->assertHasErrors([
            'subjectId' => 'integer',
            'dateTo' => 'after_or_equal',
        ]);
});
