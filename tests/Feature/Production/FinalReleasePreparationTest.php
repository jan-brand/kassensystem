<?php

use App\Foundation\Production\FinalReleaseCheckService;

it('declares the repository state as final v1', function () {
    $record = json_decode(
        (string) file_get_contents(base_path('release/v1-acceptance.json')),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );

    expect(config('release.version'))->toBe('1.0.0')
        ->and(config('release.target_version'))->toBe('1.0.0')
        ->and(config('release.stage'))->toBe('final')
        ->and(config('foundation.default_surface'))->toBe('pos')
        ->and($record['release'])->toBe('1.0.0')
        ->and($record['target'])->toBe('1.0.0')
        ->and($record['status'])->toBe('passed')
        ->and($record['date'])->toBe('2026-09-23')
        ->and($record['tester'])->toBe('JB')
        ->and($record['commit'])
        ->toBe('d08da7f31553f9c81b79f603243ac6419544b9d0')
        ->and($record['checks'])->toBe([
            'manual_checklist_complete' => true,
            'desktop_browser' => true,
            'smartphone_or_tablet' => true,
            'backup_restore' => true,
            'production_check_reviewed' => true,
        ]);
});

it('passes every final v1 release guard after documented acceptance', function () {
    $results = collect(
        app(FinalReleaseCheckService::class)->run(),
    )->keyBy('name');

    expect(app(FinalReleaseCheckService::class)->isReady())->toBeTrue()
        ->and($results['Final release metadata']['status'])->toBe('pass')
        ->and($results['Manual acceptance']['status'])->toBe('pass')
        ->and($results['Acceptance issue']['status'])->toBe('pass')
        ->and($results['v1 epic']['status'])->toBe('pass')
        ->and($results['Blocking issues']['status'])->toBe('pass')
        ->and($results['Changelog']['status'])->toBe('pass');
});

it('exposes explicit release status and final release guard commands', function () {
    $composer = json_decode(
        (string) file_get_contents(base_path('composer.json')),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );

    expect($composer['scripts']['release:status'] ?? null)
        ->toBe('@php scripts/production/final-release-check.php --status-only')
        ->and($composer['scripts']['release:final-check'] ?? null)
        ->toBe('@php scripts/production/final-release-check.php')
        ->and(file_exists(base_path('scripts/production/final-release-check.cmd')))
        ->toBeTrue();
});

it('documents the final kassensystem v1 release', function () {
    $readme = (string) file_get_contents(base_path('README.md'));
    $scope = (string) file_get_contents(base_path('docs/KASSENSYSTEM_V1.md'));
    $changelog = (string) file_get_contents(base_path('CHANGELOG.md'));

    expect($readme)
        ->toContain('# Kassensystem')
        ->toContain('**Release-Stand:** `1.0.0`')
        ->not->toContain('**Release-Stand:** `1.0.0-rc.1`')
        ->and($scope)
        ->toContain('Kassensystem v1 ist als `1.0.0` freigegeben.')
        ->toContain('Tagesreporting und CSV-Export')
        ->and($changelog)
        ->toContain('## 1.0.0 - 2026-09-23');
});
