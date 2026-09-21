<?php

use App\Foundation\Production\DatabaseBackupService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

it('returns a database-aware health response', function () {
    $this->get(route('health'))
        ->assertOk()
        ->assertJson([
            'status' => 'ok',
            'database' => 'ok',
        ]);
});

it('renders the custom not found page without debug details', function () {
    Config::set('app.debug', false);

    $this->get('/definitely-not-a-kassensystem-route')
        ->assertNotFound()
        ->assertSee('Seite nicht gefunden')
        ->assertDontSee('Stack trace');
});

it('creates and restores a consistent sqlite backup', function () {
    $directory = storage_path('framework/testing/backup-'.bin2hex(random_bytes(6)));
    File::ensureDirectoryExists($directory);
    $database = $directory.DIRECTORY_SEPARATOR.'source.sqlite';
    $backup = $directory.DIRECTORY_SEPARATOR.'backup.sqlite';
    touch($database);

    Config::set('database.connections.production_backup_test', [
        'driver' => 'sqlite',
        'database' => $database,
        'prefix' => '',
        'foreign_key_constraints' => true,
    ]);

    try {
        DB::connection('production_backup_test')->statement('CREATE TABLE notes (id INTEGER PRIMARY KEY, value TEXT NOT NULL)');
        DB::connection('production_backup_test')->table('notes')->insert(['id' => 1, 'value' => 'before']);

        $service = app(DatabaseBackupService::class);
        $created = $service->create('production_backup_test', $backup);

        expect($created)->toBe($backup)
            ->and(File::exists($backup))->toBeTrue()
            ->and(File::size($backup))->toBeGreaterThan(0);

        DB::connection('production_backup_test')->table('notes')->where('id', 1)->update(['value' => 'after']);
        expect(DB::connection('production_backup_test')->table('notes')->value('value'))->toBe('after');

        $service->restore($backup, 'production_backup_test');

        expect(DB::connection('production_backup_test')->table('notes')->value('value'))->toBe('before');
    } finally {
        DB::purge('production_backup_test');
        File::deleteDirectory($directory);
    }
});

it('refuses to write database backups below the public directory', function () {
    $directory = storage_path('framework/testing/backup-private-'.bin2hex(random_bytes(6)));
    File::ensureDirectoryExists($directory);
    $database = $directory.DIRECTORY_SEPARATOR.'source.sqlite';
    touch($database);

    Config::set('database.connections.production_backup_private_test', [
        'driver' => 'sqlite',
        'database' => $database,
        'prefix' => '',
        'foreign_key_constraints' => true,
    ]);

    try {
        expect(fn () => app(DatabaseBackupService::class)->create(
            'production_backup_private_test',
            public_path('unsafe-backup.sqlite'),
        ))->toThrow(RuntimeException::class, 'must not be written below the public directory');
    } finally {
        DB::purge('production_backup_private_test');
        File::delete(public_path('unsafe-backup.sqlite'));
        File::deleteDirectory($directory);
    }
});
