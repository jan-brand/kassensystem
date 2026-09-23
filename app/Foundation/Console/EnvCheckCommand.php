<?php

namespace App\Foundation\Console;

use App\Foundation\Environment\EnvSynchronizer;
use Illuminate\Console\Command;

final class EnvCheckCommand extends Command
{
    protected $signature = 'env:check {--target=*}';

    protected $description = 'Check configured environment files for missing and extra keys.';

    public function handle(EnvSynchronizer $s): int
    {
        $tpl = base_path(config('foundation.environment.template'));
        if (! is_file($tpl)) {
            $this->error('Template missing.');

            return self::FAILURE;
        }$tc = (string) file_get_contents($tpl);
        $targets = $this->option('target') ?: config('foundation.environment.targets');
        $bad = false;
        foreach ($targets as $t) {
            $p = base_path($t);
            if (! is_file($p)) {
                $this->error("{$t}: missing");
                $bad = true;

                continue;
            }$d = $s->diff($tc, (string) file_get_contents($p));
            $this->line("{$t}: missing [".implode(', ', $d['missing']).'] extra ['.implode(', ', $d['extra']).']');
            if ($d['missing']) {
                $bad = true;
            }
        }

        return $bad ? self::FAILURE : self::SUCCESS;
    }
}
