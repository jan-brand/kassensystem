<?php

namespace App\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

final class GeneratedRebuildCommand extends Command
{
    protected $signature = 'generated:rebuild';

    protected $description = 'Rebuild generated registry and documentation.';

    public function handle(): int
    {
        foreach (['manifest:build --force', 'docs:build --force', 'design:token:sync --force'] as $cmd) {
            $this->line('> '.$cmd);
            $code = Artisan::call($cmd);
            $this->output->write(Artisan::output());
            if ($code !== 0) {
                return self::FAILURE;
            }
        }

return self::SUCCESS;
    }
}
