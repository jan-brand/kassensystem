<?php

namespace App\Foundation\Console;

use App\Foundation\Environment\EnvSynchronizer;
use Illuminate\Console\Command;

final class EnvDiffCommand extends Command
{
    protected $signature = 'env:diff {target=.env}';

    protected $description = 'Show structural differences between .env.example and a target environment file.';

    public function handle(EnvSynchronizer $s): int
    {
        $t = base_path(config('foundation.environment.template'));
        $p = base_path($this->argument('target'));
        if (! is_file($t) || ! is_file($p)) {
            $this->error('Template or target missing.');

            return self::FAILURE;
        }$d = $s->diff((string) file_get_contents($t), (string) file_get_contents($p));
        $this->line('Missing: '.($d['missing'] ? implode(', ', $d['missing']) : 'none'));
        $this->line('Extra: '.($d['extra'] ? implode(', ', $d['extra']) : 'none'));

        return $d['missing'] ? self::FAILURE : self::SUCCESS;
    }
}
