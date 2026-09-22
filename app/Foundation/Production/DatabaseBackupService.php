<?php

namespace App\Foundation\Production;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Process;

final class DatabaseBackupService
{
    public function create(?string $connectionName = null, ?string $targetPath = null): string
    {
        $connectionName ??= (string) config('database.default');
        $connection = DB::connection($connectionName);
        $driver = $connection->getDriverName();
        $target = $targetPath !== null
            ? $this->absolutePath($targetPath)
            : $this->defaultTargetPath($connectionName, $driver);

        $this->assertBackupTargetIsPrivate($target);
        File::ensureDirectoryExists(dirname($target));

        if (File::exists($target)) {
            throw new RuntimeException('Backup target already exists: '.$target);
        }

        match ($driver) {
            'sqlite' => $this->backupSqlite($connection, $target),
            'mysql' => $this->backupMysql($connection, $target),
            default => throw new RuntimeException("Unsupported backup driver: {$driver}"),
        };

        if (! is_file($target)) {
            throw new RuntimeException('Backup file was not created or is empty.');
        }

        $size = filesize($target);

        if ($size === false || $size === 0) {
            throw new RuntimeException('Backup file was not created or is empty.');
        }

        return $target;
    }

    public function restore(string $backupPath, ?string $connectionName = null): void
    {
        $connectionName ??= (string) config('database.default');
        $backup = $this->absolutePath($backupPath);

        if (! File::isFile($backup) || File::size($backup) === 0) {
            throw new RuntimeException('Backup file does not exist or is empty: '.$backup);
        }

        $connection = DB::connection($connectionName);

        match ($connection->getDriverName()) {
            'sqlite' => $this->restoreSqlite($connectionName, $connection, $backup),
            'mysql' => $this->restoreMysql($connectionName, $connection, $backup),
            default => throw new RuntimeException('Unsupported restore driver: '.$connection->getDriverName()),
        };
    }

    private function backupSqlite(Connection $connection, string $target): void
    {
        $source = $this->sqliteDatabasePath($connection);

        if (! File::isFile($source)) {
            throw new RuntimeException('SQLite database file does not exist: '.$source);
        }

        $targetForSqlite = str_replace('\\', '/', $target);
        $escapedTarget = str_replace("'", "''", $targetForSqlite);
        $connection->unprepared("VACUUM INTO '{$escapedTarget}'");
    }

    private function restoreSqlite(string $connectionName, Connection $connection, string $backup): void
    {
        $database = $this->sqliteDatabasePath($connection);

        if ($this->normalisePath($database) === $this->normalisePath($backup)) {
            throw new RuntimeException('Backup file and active SQLite database must be different files.');
        }

        DB::purge($connectionName);
        File::delete([$database.'-wal', $database.'-shm']);
        File::ensureDirectoryExists(dirname($database));

        if (! File::copy($backup, $database)) {
            throw new RuntimeException('Unable to restore SQLite database file.');
        }

        DB::reconnect($connectionName);
        DB::connection($connectionName)->select('SELECT 1');
    }

    private function backupMysql(Connection $connection, string $target): void
    {
        $config = $connection->getConfig();
        $database = (string) ($config['database'] ?? '');

        if ($database === '') {
            throw new RuntimeException('MySQL database name is missing.');
        }

        $command = [
            (string) config('kassensystem.mysql_dump_binary', 'mysqldump'),
            '--single-transaction',
            '--quick',
            '--triggers',
            '--default-character-set=utf8mb4',
            '--host='.(string) ($config['host'] ?? '127.0.0.1'),
            '--port='.(string) ($config['port'] ?? '3306'),
            '--user='.(string) ($config['username'] ?? ''),
            '--result-file='.$target,
            $database,
        ];

        $process = new Process(
            $command,
            base_path(),
            ['MYSQL_PWD' => (string) ($config['password'] ?? '')],
            null,
            300,
        );
        $process->run();

        if (! $process->isSuccessful()) {
            File::delete($target);
            throw new RuntimeException('mysqldump failed: '.$this->processError($process));
        }
    }

    private function restoreMysql(string $connectionName, Connection $connection, string $backup): void
    {
        $config = $connection->getConfig();
        $database = (string) ($config['database'] ?? '');

        if ($database === '') {
            throw new RuntimeException('MySQL database name is missing.');
        }

        $input = fopen($backup, 'rb');

        if ($input === false) {
            throw new RuntimeException('Unable to open MySQL backup for restore.');
        }

        try {
            $process = new Process(
                [
                    (string) config('kassensystem.mysql_client_binary', 'mysql'),
                    '--default-character-set=utf8mb4',
                    '--host='.(string) ($config['host'] ?? '127.0.0.1'),
                    '--port='.(string) ($config['port'] ?? '3306'),
                    '--user='.(string) ($config['username'] ?? ''),
                    $database,
                ],
                base_path(),
                ['MYSQL_PWD' => (string) ($config['password'] ?? '')],
                $input,
                300,
            );
            $process->run();
        } finally {
            fclose($input);
        }

        if (! $process->isSuccessful()) {
            throw new RuntimeException('mysql restore failed: '.$this->processError($process));
        }

        DB::purge($connectionName);
        DB::reconnect($connectionName);
        DB::connection($connectionName)->select('SELECT 1');
    }

    private function sqliteDatabasePath(Connection $connection): string
    {
        $database = (string) $connection->getConfig('database');

        if ($database === '' || $database === ':memory:' || str_contains($database, 'mode=memory')) {
            throw new RuntimeException('In-memory SQLite databases cannot be backed up or restored.');
        }

        return $this->absolutePath($database);
    }

    private function defaultTargetPath(string $connectionName, string $driver): string
    {
        $directory = trim((string) config('kassensystem.backup_directory'));

        if ($directory === '') {
            $directory = storage_path('app/backups/database');
        }

        $extension = $driver === 'mysql' ? 'sql' : 'sqlite';
        $filename = now()->format('Ymd_His_u').'-'.$connectionName.'.'.$extension;

        return rtrim($this->absolutePath($directory), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$filename;
    }

    private function absolutePath(string $path): string
    {
        $path = trim($path);

        if ($path === '') {
            throw new RuntimeException('A file path must not be empty.');
        }

        if ($this->isAbsolutePath($path)) {
            return $path;
        }

        return base_path($path);
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/')
            || str_starts_with($path, '\\')
            || preg_match('~^[A-Za-z]:[\\\\/]~', $path) === 1;
    }

    private function assertBackupTargetIsPrivate(string $target): void
    {
        $public = rtrim($this->normalisePath(public_path()), '/').'/';
        $normalisedTarget = $this->normalisePath($target);

        if (str_starts_with($normalisedTarget, $public)) {
            throw new RuntimeException('Database backups must not be written below the public directory.');
        }
    }

    private function normalisePath(string $path): string
    {
        return strtolower(str_replace('\\', '/', $path));
    }

    private function processError(Process $process): string
    {
        $message = trim($process->getErrorOutput());

        if ($message === '') {
            $message = trim($process->getOutput());
        }

        return $message !== '' ? $message : 'process exited with code '.(string) $process->getExitCode();
    }
}
