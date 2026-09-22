<?php

use App\Foundation\Production\FinalReleaseCheckService;

it('declares the current repository state as v1 rc1', function () {
    $record = json_decode(
        (string) file_get_contents(base_path('release/v1-acceptance.json')),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );

    expect(config('release.version'))->toBe('1.0.0-rc.1')
        ->and(config('release.target_version'))->toBe('1.0.0')
        ->and(config('release.stage'))->toBe('rc1')
        ->and(config('foundation.default_surface'))->toBe('pos')
        ->and($record['release'])->toBe('1.0.0-rc.1')
        ->and($record['target'])->toBe('1.0.0')
        ->and($record['status'])->toBe('pending')
        ->and($record['checks'])->toBe([
            'manual_checklist_complete' => false,
            'desktop_browser' => false,
            'smartphone_or_tablet' => false,
            'backup_restore' => false,
            'production_check_reviewed' => false,
        ]);
});

it('keeps the final v1 release blocked until manual acceptance is complete', function () {
    $results = collect(
        app(FinalReleaseCheckService::class)->run(),
    )->keyBy('name');

    expect(app(FinalReleaseCheckService::class)->isReady())->toBeFalse()
        ->and($results['Final release metadata']['status'])->toBe('fail')
        ->and($results['Manual acceptance']['status'])->toBe('fail')
        ->and($results['Acceptance issue']['status'])->toBe('fail')
        ->and($results['v1 epic']['status'])->toBe('fail')
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

it('documents the current kassensystem product scope and rc1 status', function () {
    $readme = (string) file_get_contents(base_path('README.md'));
    $scope = (string) file_get_contents(base_path('docs/KASSENSYSTEM_V1.md'));
    $changelog = (string) file_get_contents(base_path('CHANGELOG.md'));

    expect($readme)
        ->toContain('# Kassensystem')
        ->toContain('1.0.0-rc.1')
        ->not->toContain('Fachmodule werden erst im jeweiligen Projekt erzeugt.')
        ->and($scope)
        ->toContain('Administration')
        ->toContain('Reporting und CSV')
        ->not->toContain('- POS- und Verwaltungsoberfläche.')
        ->and($changelog)
        ->toContain('## 1.0.0-rc.1 - 2026-09-22');
});
