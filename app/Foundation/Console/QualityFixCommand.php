<?php

namespace App\Foundation\Console;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

final class QualityFixCommand extends Command
{
    protected $signature = 'quality:fix';

    protected $description = 'Apply safe automatic code formatting fixes.';

    public function handle(): int
    {
        $process = new Process(
            [PHP_BINARY, 'vendor/bin/pint', '--parallel'],
            base_path(),
        );
        $process->setTimeout(300);
        $process->run(
            function (string $type, string $buffer): void {
                    $this->output->write($buffer);
                },
        );

        return $process->isSuccessful()
            ? self::SUCCESS
            : self::FAILURE;
    }
}
