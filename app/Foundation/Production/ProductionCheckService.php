<?php

namespace App\Foundation\Production;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

final class ProductionCheckService
{
    public function __construct(
        private readonly ReleaseArtifactCheckService $releaseArtifacts,
    ) {}

    /**
     * @return list<array{name: string, status: 'pass'|'warn'|'fail', message: string}>
     */
    public function run(): array
    {
        $results = [];

        $results[] = $this->result(
            'Environment',
            app()->environment('production') ? 'pass' : 'fail',
            'APP_ENV must be production for the production deployment.',
        );
        $results[] = $this->result(
            'Debug mode',
            config('app.debug') === false ? 'pass' : 'fail',
            config('app.debug') === false ? 'APP_DEBUG is disabled.' : 'APP_DEBUG must be false.',
        );
        $results[] = $this->result(
            'Application key',
            trim((string) config('app.key')) !== '' ? 'pass' : 'fail',
            trim((string) config('app.key')) !== '' ? 'APP_KEY is configured.' : 'APP_KEY is missing.',
        );

        $url = (string) config('app.url');
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $localHost = $host === '' || in_array($host, ['localhost', '127.0.0.1', '::1'], true);
        $results[] = $this->result(
            'Application URL',
            $localHost ? 'fail' : (str_starts_with(strtolower($url), 'https://') ? 'pass' : 'warn'),
            $localHost ? 'APP_URL still points to a local address.' : (str_starts_with(strtolower($url), 'https://') ? 'APP_URL uses HTTPS.' : 'APP_URL is non-local but does not use HTTPS.'),
        );

        $results = array_merge($results, $this->databaseChecks());

        foreach ([
            'Storage logs' => storage_path('logs'),
            'Storage framework' => storage_path('framework'),
            'Private storage' => storage_path('app/private'),
        ] as $name => $path) {
            $results[] = $this->result(
                $name,
                is_dir($path) && is_writable($path) ? 'pass' : 'fail',
                is_dir($path) && is_writable($path) ? 'Directory is writable.' : 'Directory is missing or not writable: '.$path,
            );
        }

        $storageLink = public_path('storage');
        $results[] = $this->result(
            'Public storage link',
            is_dir($storageLink) || is_link($storageLink) ? 'pass' : 'fail',
            is_dir($storageLink) || is_link($storageLink) ? 'public/storage is available.' : 'Run php artisan storage:link.',
        );

        $results[] = $this->result(
            'Currency',
            config('kassensystem.currency') === 'EUR' ? 'pass' : 'fail',
            'Kassensystem currency must be EUR in v1.',
        );
        $results[] = $this->result(
            'Timezone',
            config('kassensystem.timezone') === 'Europe/Berlin' ? 'pass' : 'fail',
            'Kassensystem timezone must be Europe/Berlin in v1.',
        );

        $gitignore = File::exists(base_path('.gitignore')) ? File::get(base_path('.gitignore')) : '';
        $results[] = $this->result(
            'Secret safeguards',
            str_contains($gitignore, '/.env') && str_contains($gitignore, '/storage/app/backups/') ? 'pass' : 'fail',
            'The repository must ignore environment files and database backups.',
        );

        $results = array_merge(
            $results,
            $this->releaseArtifacts->runtimeChecks(),
        );

        return $results;
    }

    /** @return list<array{name: string, status: 'pass'|'warn'|'fail', message: string}> */
    private function databaseChecks(): array
    {
        try {
            $connectionName = (string) config('database.default');
            $connection = DB::connection($connectionName);
            $connection->select('SELECT 1');
            $driver = $connection->getDriverName();
            $results = [
                $this->result('Database connectivity', 'pass', 'Database connection is available.'),
                $this->result(
                    'Production database driver',
                    $driver === 'mysql' ? 'pass' : 'fail',
                    $driver === 'mysql' ? 'MySQL/MariaDB driver is active.' : 'Production requires the mysql driver; active driver: '.$driver,
                ),
            ];

            if ($driver === 'mysql') {
                $version = $connection->selectOne('SELECT VERSION() AS version');
                $results[] = $this->result(
                    'Database server',
                    'pass',
                    'Server version: '.(string) ($version->version ?? 'unknown'),
                );
            }

            $results[] = $this->migrationCheck($connectionName);
            $results[] = $this->demoAccountsCheck($connectionName);

            return $results;
        } catch (Throwable) {
            return [
                $this->result('Database connectivity', 'fail', 'Database connection failed. Check the production DB settings.'),
            ];
        }
    }

    /** @return array{name: string, status: 'pass'|'warn'|'fail', message: string} */
    private function demoAccountsCheck(string $connectionName): array
    {
        try {
            $connection = DB::connection($connectionName);

            if (! $connection->getSchemaBuilder()->hasTable('users')) {
                return $this->result(
                    'Demo accounts',
                    'fail',
                    'The users table does not exist.',
                );
            }

            $count = $connection
                ->table('users')
                ->whereIn('username', [
                    'demo-admin',
                    'demo-manager',
                    'demo-kasse',
                ])
                ->count();

            return $this->result(
                'Demo accounts',
                $count === 0 ? 'pass' : 'fail',
                $count === 0
                    ? 'No built-in demo accounts are present.'
                    : $count.' demo account(s) must be removed before production use.',
            );
        } catch (Throwable) {
            return $this->result(
                'Demo accounts',
                'fail',
                'Demo-account status could not be checked.',
            );
        }
    }

    /** @return array{name: string, status: 'pass'|'warn'|'fail', message: string} */
    private function migrationCheck(string $connectionName): array
    {
        try {
            $connection = DB::connection($connectionName);

            if (! $connection->getSchemaBuilder()->hasTable('migrations')) {
                return $this->result('Migrations', 'fail', 'The migrations table does not exist. Run php artisan migrate --force.');
            }

            $applied = $connection->table('migrations')->pluck('migration')->all();
            $expected = [];

            foreach (glob(database_path('migrations/*.php')) ?: [] as $file) {
                $expected[] = pathinfo($file, PATHINFO_FILENAME);
            }
            foreach (glob(app_path('Modules/*/database/migrations/*.php')) ?: [] as $file) {
                $expected[] = pathinfo($file, PATHINFO_FILENAME);
            }

            $missing = array_values(array_diff(array_unique($expected), $applied));

            if ($missing !== []) {
                return $this->result('Migrations', 'fail', count($missing).' migration(s) are not applied.');
            }

            return $this->result('Migrations', 'pass', 'All discovered migrations are applied.');
        } catch (Throwable) {
            return $this->result('Migrations', 'fail', 'Migration status could not be checked.');
        }
    }

    /** @return array{name: string, status: 'pass'|'warn'|'fail', message: string} */
    private function result(string $name, string $status, string $message): array
    {
        return [
            'name' => $name,
            'status' => $status,
            'message' => $message,
        ];
    }
}
