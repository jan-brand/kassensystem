<?php

use App\Foundation\Production\FinalReleaseCheckService;

it('keeps the final v1 release guard green while later milestones are open', function () {
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
        ->toContain(
            'V2-000',
            'V2-001',
            'V2-002',
            'V2-003',
            'V2-004',
            'V2-005',
            'V2-006',
            'V2-007',
            'V2-008',
            'V2-009',
            'V2-010',
            'V2-011',
        )
        ->and($v2->firstWhere('id', 'V2-001')['status'] ?? null)
        ->toBe('in-progress')
        ->and(
            $v2->filter(
                static fn (array $issue): bool => ($issue['status'] ?? null) !== 'closed'
                    && in_array($issue['priority'] ?? null, ['P0', 'P1'], true),
            )->count(),
        )
        ->toBeGreaterThan(0);
});

it('keeps v1 release metadata while v2 development is active', function () {
    $scope = (string) file_get_contents(base_path('docs/KASSENSYSTEM_V2.md'));

    expect(config('release.version'))->toBe('1.0.0')
        ->and(config('release.stage'))->toBe('final')
        ->and($scope)
        ->toContain('Die V2-Entwicklung ist gestartet')
        ->toContain('Bestellung ist nicht Verkauf')
        ->toContain('V2-B – Kellner, Bereiche, Tische und Tickets')
        ->toContain('V2-C – Küche und Bar')
        ->toContain('PayPal.me')
        ->toContain('QR-Belege')
        ->toContain('V2-001 Storno – noch offen');
});

it('keeps deferred product scope out of v2 and documented for later milestones', function () {
    $roadmap = (string) file_get_contents(base_path('docs/ROADMAP.md'));
    $v3 = (string) file_get_contents(base_path('docs/KASSENSYSTEM_V3.md'));
    $v4 = (string) file_get_contents(base_path('docs/KASSENSYSTEM_V4.md'));

    expect($roadmap)
        ->toContain('Nicht in V2: Lager, Guthaben, Bondrucker, Produkt-Scanning, Tischwechsel, mehrere Standorte')
        ->and($v3)
        ->toContain('Schüler-/Kundenguthaben')
        ->toContain('Lager')
        ->toContain('Bondrucker')
        ->toContain('Mehrere Standorte')
        ->toContain('Tischwechsel')
        ->and($v4)
        ->toContain('Grafischer Raumplan');
});

it('keeps every roadmap issue body available in the repository', function () {
    $issues = json_decode(
        (string) file_get_contents(base_path('issues/issues.json')),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );

    $missing = collect($issues['issues'] ?? [])
        ->filter(
            static fn (array $issue): bool => ! is_file(base_path((string) ($issue['body_file'] ?? ''))),
        )
        ->pluck('id')
        ->values()
        ->all();

    expect($missing)->toBe([]);
});
