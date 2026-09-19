<?php

namespace App\Foundation\Console;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

final class AppDoctorCommand extends Command
{
    protected $signature = 'app:doctor';

    protected $description =
        'Check the local development environment and show actionable diagnostics.';

    public function handle(): int
    {
        [$nodeAvailable, $nodeVersion] = $this->commandVersion('node');
        [$npmAvailable, $npmVersion] = $this->commandVersion('npm');

        $checks = [
            [
                'PHP >= 8.4',
                version_compare(PHP_VERSION, '8.4.0', '>='),
                PHP_VERSION,
            ],
            [
                'ext-json',
                extension_loaded('json'),
                extension_loaded('json') ? 'loaded' : 'missing',
            ],
            [
                'ext-pdo',
                extension_loaded('pdo'),
                extension_loaded('pdo') ? 'loaded' : 'missing',
            ],
            [
                'ext-mbstring',
                extension_loaded('mbstring'),
                extension_loaded('mbstring') ? 'loaded' : 'missing',
            ],
            [
                '.env',
                is_file(base_path('.env')),
                is_file(base_path('.env'))
                    ? 'present'
                    : 'missing',
            ],
            [
                'storage writable',
                is_writable(storage_path()),
                is_writable(storage_path())
                    ? 'yes'
                    : 'no',
            ],
            [
                'bootstrap/cache writable',
                is_writable(base_path('bootstrap/cache')),
                is_writable(base_path('bootstrap/cache'))
                    ? 'yes'
                    : 'no',
            ],
            [
                'Node.js',
                $nodeAvailable,
                $nodeVersion,
            ],
            [
                'npm',
                $npmAvailable,
                $npmVersion,
            ],
        ];

        $this->table(
            ['Check', 'Status', 'Details'],
            array_map(
                fn (array $check): array => [
                    $check[0],
                    $check[1] ? 'OK' : 'FAIL',
                    $check[2],
                ],
                $checks,
            ),
        );

        $failed = array_filter(
            $checks,
            fn (array $check): bool => ! $check[1],
        );

        if ($failed !== []) {
            $this->error(count($failed).' check(s) failed.');

            return self::FAILURE;
        }

        $this->info('Development environment looks good.');

        return self::SUCCESS;
    }

    /**
     * @return array{bool, string}
     */
    private function commandVersion(string $command): array
    {
        try {
            $process = new Process([$command, '--version']);

            $process->setTimeout(5);
            $process->run();

            if (! $process->isSuccessful()) {
                return [false, 'missing'];
            }

            $version = trim($process->getOutput());

            return [
                $version !== '',
                $version !== '' ? $version : 'missing',
            ];
        } catch (\Throwable) {
            return [false, 'missing'];
        }
    }
}