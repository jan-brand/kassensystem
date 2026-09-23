<?php

it('has no open P0 or P1 blockers for the final v1 release', function () {
    $issues = json_decode(
        (string) file_get_contents(base_path('issues/issues.json')),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );

    $blockers = collect($issues['issues'] ?? [])
        ->filter(
            static fn (array $issue): bool => ($issue['status'] ?? null) === 'open'
                && in_array($issue['priority'] ?? null, ['P0', 'P1'], true),
        )
        ->pluck('id')
        ->values()
        ->all();

    expect($blockers)->toBe([]);
});

it('provides one reproducible release quality command', function () {
    $composer = json_decode(
        (string) file_get_contents(base_path('composer.json')),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );

    expect($composer['scripts']['qa:acceptance'] ?? null)
        ->toBeArray()
        ->toContain('@php artisan module:check')
        ->toContain('@php artisan architecture:check')
        ->toContain('@php artisan permission:check')
        ->toContain('@php artisan navigation:check')
        ->toContain('@php artisan page:check')
        ->toContain('@php artisan test tests/Feature/Acceptance')
        ->and($composer['scripts']['qa:release'] ?? null)
        ->toBe([
            '@qa',
            '@php scripts/production/release-artifacts.php',
            '@qa:acceptance',
        ]);
});

it('binds GitHub CI and security checks to reproducible release commands', function () {
    $ci = (string) file_get_contents(base_path('.github/workflows/ci.yml'));
    $security = (string) file_get_contents(
        base_path('.github/workflows/security.yml'),
    );

    expect($ci)
        ->toContain('composer qa:release')
        ->toContain('composer release:status')
        ->toContain('npm ci')
        ->toContain("FOUNDATION_DASHBOARD: 'false'")
        ->toContain('php artisan optimize --no-ansi')
        ->toContain('php artisan route:list --name=health --no-ansi')
        ->and($security)
        ->toContain('composer audit --locked')
        ->toContain('npm ci');
});

it('records completed manual device acceptance in the release documentation', function () {
    $acceptance = (string) file_get_contents(
        base_path('docs/ACCEPTANCE_V1.md'),
    );
    $release = (string) file_get_contents(base_path('docs/RELEASE_V1.md'));

    expect($acceptance)
        ->toContain('composer qa:release')
        ->toContain('Smartphone / Tablet / Desktop')
        ->toContain('Ergebnis: [x] PASS  [ ] FAIL')
        ->toContain('Tester: JB')
        ->not->toContain('- [ ]')
        ->and($release)
        ->toContain('composer release:final-check')
        ->toContain('d08da7f31553f9c81b79f603243ac6419544b9d0')
        ->toContain('v1.0.0');
});
