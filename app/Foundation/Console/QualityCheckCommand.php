<?php

namespace App\Foundation\Console;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

final class QualityCheckCommand extends Command
{
    protected $signature = 'quality:check {--quick}';

    protected $description = 'Run the project quality gate locally.';

    public function handle(): int
    {
        /** @var list<list<string>> $commands */
        $commands = [
            [PHP_BINARY, 'artisan', 'app:check'],
            [PHP_BINARY, 'artisan', 'test'],
        ];

        if (! (bool) $this->option('quick')) {
            $commands[] = [PHP_BINARY, 'vendor/bin/pint', '--test', '--parallel'];
            $commands[] = [
                PHP_BINARY,
                'vendor/bin/phpstan',
                'analyse',
                '--memory-limit=1G',
                '--no-progress',
            ];
        }

        foreach ($commands as $command) {
            $this->line('$ '.implode(' ', $command));

            $process = new Process($command, base_path());
            $process->setTimeout(300);
            $process->run(
                function (string $type, string $buffer): void {
                    $this->output->write($buffer);
                },
            );

            if (! $process->isSuccessful()) {
                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}
