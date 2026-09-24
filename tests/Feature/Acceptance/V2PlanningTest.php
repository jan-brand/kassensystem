<?php

use App\Foundation\Production\FinalReleaseCheckService;

it('keeps the final v1 release guard green with an open v2 backlog', function () {
    $issues = json_decode(
        (string) file_get_contents(base_path('issues/issues.json')),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );

    $v2 = collect($issues['issues'] ?? [])
        ->filter(
            static fn (array $issue): bool => ($issue['milestone'] ?? null) === 'v2',
        );

    expect(app(FinalReleaseCheckService::class)->isReady())->toBeTrue()
        ->and($v2->pluck('id')->values()->all())
        ->toContain('V2-000', 'V2-001', 'V2-002', 'V2-007', 'V2-008', 'V2-009')
        ->and(
            $v2->filter(
                static fn (array $issue): bool => ($issue['status'] ?? null) === 'open'
                    && in_array($issue['priority'] ?? null, ['P0', 'P1'], true),
            )->count(),
        )
        ->toBeGreaterThan(0);
});

it('keeps v1 final while v2 is planning only', function () {
    $roadmap = (string) file_get_contents(base_path('docs/KASSENSYSTEM_V2.md'));

    expect(config('release.version'))->toBe('1.0.0')
        ->and(config('release.stage'))->toBe('final')
        ->and($roadmap)
        ->toContain('V2 ist in diesem Stand ausschließlich geplant')
        ->toContain('Ein Storno ist eine neue, referenzierte Gegenbuchung')
        ->toContain('V2-002 Payment-Architektur')
        ->toContain('V2-008 Migration und Rueckwaertskompatibilitaet')
        ->toContain('V2-009 Acceptance');
});
