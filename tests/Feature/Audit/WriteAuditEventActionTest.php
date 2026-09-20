<?php

use App\Modules\Audit\Actions\WriteAuditEventAction;
use App\Modules\Audit\Models\AuditEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('writes and sanitizes an audit event', function () {
    $event = app(WriteAuditEventAction::class)->execute(
        eventKey: 'user.updated',
        before: [
            'username' => 'max',
            'pin' => '123456',
            'nested' => ['password' => 'secret'],
        ],
        metadata: ['token' => 'secret-token'],
    );

    expect($event->before)->toBe([
        'username' => 'max',
        'pin' => '[REDACTED]',
        'nested' => ['password' => '[REDACTED]'],
    ])->and($event->metadata)->toBe([
        'token' => '[REDACTED]',
    ])->and($event->created_at)->not->toBeNull();
});

it('keeps audit events immutable', function () {
    $event = app(WriteAuditEventAction::class)->execute(eventKey: 'test.event');

    expect(fn () => $event->update(['event_key' => 'changed']))
        ->toThrow(LogicException::class, 'Audit events are immutable.');

    expect(fn () => $event->delete())
        ->toThrow(LogicException::class, 'Audit events cannot be deleted.');

    expect(AuditEvent::query()->findOrFail($event->id)->event_key)
        ->toBe('test.event');
});
