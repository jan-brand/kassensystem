<?php

use App\Foundation\Production\DatabaseBackupService;
use App\Foundation\Production\ProductionCheckService;
use App\QA\DemoDataSeeder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:backup {--connection=} {--path=}', function (DatabaseBackupService $backups): int {
    try {
        $path = $backups->create(
            connectionName: $this->option('connection') ?: null,
            targetPath: $this->option('path') ?: null,
        );

        $this->info('Database backup created: '.$path);

        return SymfonyCommand::SUCCESS;
    } catch (Throwable $exception) {
        $this->error($exception->getMessage());

        return SymfonyCommand::FAILURE;
    }
})->purpose('Create a consistent SQLite or MySQL/MariaDB database backup');

Artisan::command(
    'app:restore {backup} {--connection=} {--force} {--skip-safety-backup}',
    function (DatabaseBackupService $backups): int {
        if (! $this->option('force')) {
            $this->error('Restore is destructive. Re-run with --force after verifying the backup file.');

            return SymfonyCommand::FAILURE;
        }

        try {
            $connection = $this->option('connection') ?: null;

            if (! $this->option('skip-safety-backup')) {
                $safetyBackup = $backups->create(connectionName: $connection);
                $this->warn('Safety backup created before restore: '.$safetyBackup);
            }

            $backups->restore(
                backupPath: (string) $this->argument('backup'),
                connectionName: $connection,
            );

            $this->info('Database restore completed successfully.');

            return SymfonyCommand::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return SymfonyCommand::FAILURE;
        }
    },
)->purpose('Restore a database backup; requires --force and creates a safety backup by default');

Artisan::command('app:demo:seed {--no-history}', function (DemoDataSeeder $demo): int {
    try {
        $result = $demo->seed(withHistory: ! $this->option('no-history'));

        $this->info('Demo data is ready.');
        $this->table(
            ['Role', 'Username', 'PIN'],
            DemoDataSeeder::credentialRows(),
        );

        $this->line('Categories: '.$result['categories']);
        $this->line('Products: '.$result['products']);
        $this->line('Demo users: '.$result['users']);
        $this->line('Register: '.$result['register']);

        if ($this->option('no-history')) {
            $this->comment('Demo history was skipped by --no-history.');
        } elseif ($result['history_seeded']) {
            $this->info('Demo shift and sample sales were created.');
        } else {
            $this->comment('Demo history already existed or an active cash session prevented creating it.');
        }

        return SymfonyCommand::SUCCESS;
    } catch (Throwable $exception) {
        $this->error($exception->getMessage());

        return SymfonyCommand::FAILURE;
    }
})->purpose('Seed local/testing demo accounts, catalog data and optional sample sales');

Artisan::command('app:production-check', function (ProductionCheckService $checks): int {
    $results = $checks->run();

    $this->table(
        ['Status', 'Check', 'Details'],
        array_map(
            static fn (array $result): array => [strtoupper($result['status']), $result['name'], $result['message']],
            $results,
        ),
    );

    $failures = array_filter($results, static fn (array $result): bool => $result['status'] === 'fail');

    if ($failures !== []) {
        $this->error(count($failures).' production readiness check(s) failed.');

        return SymfonyCommand::FAILURE;
    }

    $this->info('Production readiness checks passed.');

    return SymfonyCommand::SUCCESS;
})->purpose('Check production environment, database, migrations, storage and secret safeguards');
