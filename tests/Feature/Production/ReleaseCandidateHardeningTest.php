<?php

use App\Foundation\Production\ReleaseArtifactCheckService;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

it('keeps public health routes cacheable by Laravel and hardened for probes', function () {
    expect(Route::getRoutes()->getByName('home')?->getActionName())
        ->toBe(HomeController::class)
        ->and(Route::getRoutes()->getByName('health')?->getActionName())
        ->toBe(HealthController::class);

    $this->get('/up')->assertOk();

    $response = $this->get(route('health'))
        ->assertOk()
        ->assertJson([
            'status' => 'ok',
            'database' => 'ok',
        ])
        ->assertHeader('Pragma', 'no-cache')
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    $cacheControl = (string) $response->headers->get('Cache-Control');

    expect($cacheControl)
        ->toContain('no-store')
        ->toContain('no-cache')
        ->toContain('must-revalidate')
        ->toContain('max-age=0')
        ->not->toContain('public');
});

it('passes release artifact checks with a compiled manifest and private backup target', function () {
    $manifest = public_path('build/manifest.json');
    $manifestExisted = File::exists($manifest);
    $previousManifest = $manifestExisted ? File::get($manifest) : null;
    $backupDirectory = storage_path(
        'framework/testing/release-artifacts-'.bin2hex(random_bytes(5)),
    );

    File::ensureDirectoryExists(dirname($manifest));
    File::ensureDirectoryExists($backupDirectory);

    File::put($manifest, json_encode([
        'resources/css/app.css' => [
            'file' => 'assets/app-test.css',
            'isEntry' => true,
        ],
        'resources/js/app.js' => [
            'file' => 'assets/app-test.js',
            'isEntry' => true,
        ],
    ], JSON_THROW_ON_ERROR));

    Config::set('kassensystem.backup_directory', $backupDirectory);

    try {
        $results = app(ReleaseArtifactCheckService::class)->run();
        $failures = array_values(array_filter(
            $results,
            static fn (array $result): bool => $result['status'] === 'fail',
        ));

        expect($failures)->toBe([]);
    } finally {
        if ($manifestExisted && $previousManifest !== null) {
            File::put($manifest, $previousManifest);
        } else {
            File::delete($manifest);
        }

        File::deleteDirectory($backupDirectory);
    }
});

it('rejects exposed environment files and a public backup destination', function () {
    $publicEnvironment = public_path('.env.rc1-test');
    $backupDirectory = public_path('unsafe-backups');

    File::put($publicEnvironment, 'APP_KEY=do-not-expose');
    Config::set('kassensystem.backup_directory', $backupDirectory);

    try {
        $results = collect(
            app(ReleaseArtifactCheckService::class)->runtimeChecks(),
        )->keyBy('name');

        expect($results['Public environment files']['status'])->toBe('fail')
            ->and($results['Backup directory']['status'])->toBe('fail');
    } finally {
        File::delete($publicEnvironment);
        File::deleteDirectory($backupDirectory);
    }
});

it('keeps production documentation free from control characters and exposes the rc1 workflow', function () {
    $production = (string) file_get_contents(base_path('docs/PRODUCTION.md'));
    $release = (string) file_get_contents(base_path('docs/RELEASE_V1.md'));
    $composer = json_decode(
        (string) file_get_contents(base_path('composer.json')),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );

    expect(preg_match(
        '/[\x{0000}-\x{0008}\x{000B}\x{000C}\x{000E}-\x{001F}\x{007F}-\x{009F}]/u',
        $production,
    ))
        ->toBe(0)
        ->and($production)
        ->toContain('storage\\app\\backups\\database\\vor-update.sql')
        ->toContain('storage\\app\\backups\\database\\20260921_120000_mysql.sql')
        ->and($release)
        ->toContain('scripts\\production\\rc1-check.cmd')
        ->and($composer['scripts']['qa:release'] ?? [])
        ->toContain('@php scripts/production/release-artifacts.php');
});
