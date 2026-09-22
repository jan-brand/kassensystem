<?php

namespace App\Foundation\Production;

use JsonException;

final class ReleaseArtifactCheckService
{
    /**
     * @return list<array{name: string, status: 'pass'|'warn'|'fail', message: string}>
     */
    public function run(): array
    {
        return [
            ...$this->runtimeChecks(),
            ...$this->repositoryChecks(),
        ];
    }

    /**
     * @return list<array{name: string, status: 'pass'|'warn'|'fail', message: string}>
     */
    public function runtimeChecks(): array
    {
        return [
            $this->frontendManifestCheck(),
            $this->bootstrapCacheCheck(),
            $this->backupDirectoryCheck(),
            $this->publicEnvironmentCheck(),
        ];
    }

    /**
     * @return list<array{name: string, status: 'pass'|'warn'|'fail', message: string}>
     */
    public function repositoryChecks(): array
    {
        return [
            $this->lockFilesCheck(),
            $this->releaseDocumentationCheck(),
            $this->releaseScriptsCheck(),
        ];
    }

    /** @return array{name: string, status: 'pass'|'warn'|'fail', message: string} */
    private function frontendManifestCheck(): array
    {
        $manifest = public_path('build/manifest.json');

        if (! is_file($manifest) || ! is_readable($manifest)) {
            return $this->result(
                'Frontend build',
                'fail',
                'public/build/manifest.json is missing. Run npm run build.',
            );
        }

        try {
            $decoded = json_decode(
                (string) file_get_contents($manifest),
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
        } catch (JsonException) {
            return $this->result(
                'Frontend build',
                'fail',
                'Vite manifest is not valid JSON.',
            );
        }

        if (! is_array($decoded) || $decoded === []) {
            return $this->result(
                'Frontend build',
                'fail',
                'Vite manifest is empty.',
            );
        }

        foreach (['resources/css/app.css', 'resources/js/app.js'] as $entry) {
            if (! array_key_exists($entry, $decoded)) {
                return $this->result(
                    'Frontend build',
                    'fail',
                    "Vite manifest is missing the entry {$entry}.",
                );
            }
        }

        return $this->result(
            'Frontend build',
            'pass',
            'Compiled Vite assets and both application entrypoints are available.',
        );
    }

    /** @return array{name: string, status: 'pass'|'warn'|'fail', message: string} */
    private function bootstrapCacheCheck(): array
    {
        $directory = base_path('bootstrap/cache');
        $ready = is_dir($directory) && is_writable($directory);

        return $this->result(
            'Bootstrap cache',
            $ready ? 'pass' : 'fail',
            $ready
                ? 'bootstrap/cache is writable for optimize and route caching.'
                : 'bootstrap/cache is missing or not writable.',
        );
    }

    /** @return array{name: string, status: 'pass'|'warn'|'fail', message: string} */
    private function backupDirectoryCheck(): array
    {
        $configured = trim((string) config('kassensystem.backup_directory'));
        $directory = $configured !== ''
            ? $this->absolutePath($configured)
            : storage_path('app/backups/database');

        $public = rtrim($this->normalisePath(public_path()), '/').'/';
        $normalised = $this->normalisePath($directory);

        if (
            $normalised === rtrim($public, '/')
            || str_starts_with($normalised, $public)
        ) {
            return $this->result(
                'Backup directory',
                'fail',
                'The configured backup directory must stay outside public/.',
            );
        }

        $probe = $directory;

        while (! is_dir($probe)) {
            $parent = dirname($probe);

            if ($parent === $probe) {
                return $this->result(
                    'Backup directory',
                    'fail',
                    'No writable parent directory exists for database backups.',
                );
            }

            $probe = $parent;
        }

        return $this->result(
            'Backup directory',
            is_writable($probe) ? 'pass' : 'fail',
            is_writable($probe)
                ? 'The backup directory or its nearest existing parent is writable.'
                : 'The backup directory cannot be created because its parent is not writable.',
        );
    }

    /** @return array{name: string, status: 'pass'|'warn'|'fail', message: string} */
    private function publicEnvironmentCheck(): array
    {
        $files = array_values(array_filter(
            glob(public_path('.env*')) ?: [],
            static fn (string $path): bool => is_file($path),
        ));

        return $this->result(
            'Public environment files',
            $files === [] ? 'pass' : 'fail',
            $files === []
                ? 'No environment file is exposed below public/.'
                : 'Environment files must never be present below public/.',
        );
    }

    /** @return array{name: string, status: 'pass'|'warn'|'fail', message: string} */
    private function lockFilesCheck(): array
    {
        $missing = array_values(array_filter(
            ['composer.lock', 'package-lock.json'],
            static fn (string $file): bool => ! is_file(base_path($file)),
        ));

        return $this->result(
            'Dependency lock files',
            $missing === [] ? 'pass' : 'fail',
            $missing === []
                ? 'Composer and npm dependency locks are available.'
                : 'Missing dependency lock file(s): '.implode(', ', $missing),
        );
    }

    /** @return array{name: string, status: 'pass'|'warn'|'fail', message: string} */
    private function releaseDocumentationCheck(): array
    {
        $required = [
            'CHANGELOG.md',
            'config/release.php',
            'docs/ACCEPTANCE_V1.md',
            'docs/PRODUCTION.md',
            'docs/RELEASE_V1.md',
            'release/v1-acceptance.json',
        ];

        $missing = array_values(array_filter(
            $required,
            static fn (string $file): bool => ! is_file(base_path($file)),
        ));

        return $this->result(
            'Release documentation',
            $missing === [] ? 'pass' : 'fail',
            $missing === []
                ? 'Release documentation and metadata are present.'
                : 'Missing release documentation or metadata: '.implode(', ', $missing),
        );
    }

    /** @return array{name: string, status: 'pass'|'warn'|'fail', message: string} */
    private function releaseScriptsCheck(): array
    {
        $required = [
            'scripts/production/final-release-check.cmd',
            'scripts/production/final-release-check.php',
            'scripts/production/mariadb-smoke.cmd',
            'scripts/production/mariadb-smoke.php',
            'scripts/production/rc1-check.cmd',
            'scripts/production/release-artifacts.php',
        ];

        $missing = array_values(array_filter(
            $required,
            static fn (string $file): bool => ! is_file(base_path($file)),
        ));

        return $this->result(
            'Release scripts',
            $missing === [] ? 'pass' : 'fail',
            $missing === []
                ? 'RC1, final-release and MariaDB smoke-test scripts are present.'
                : 'Missing release script(s): '.implode(', ', $missing),
        );
    }

    private function absolutePath(string $path): string
    {
        if (
            str_starts_with($path, '/')
            || str_starts_with($path, '\\')
            || preg_match('~^[A-Za-z]:[\\\\/]~', $path) === 1
        ) {
            return $path;
        }

        return base_path($path);
    }

    private function normalisePath(string $path): string
    {
        return strtolower(str_replace('\\', '/', $path));
    }

    /**
     * @param  'pass'|'warn'|'fail'  $status
     * @return array{name: string, status: 'pass'|'warn'|'fail', message: string}
     */
    private function result(
        string $name,
        string $status,
        string $message,
    ): array {
        return [
            'name' => $name,
            'status' => $status,
            'message' => $message,
        ];
    }
}
