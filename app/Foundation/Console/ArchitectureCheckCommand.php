<?php

namespace App\Foundation\Console;

use App\Foundation\Architecture\ArchitectureInspector;
use Illuminate\Console\Command;

final class ArchitectureCheckCommand extends Command
{
    protected $signature = 'architecture:check';

    protected $description = 'Run architecture dependency checks.';

    public function handle(ArchitectureInspector $i): int
    {
        $p = $i->problems();
        if ($p) {
            foreach ($p as $x) {
                $this->error($x);
            }

            return self::FAILURE;
        }$this->info('Architecture valid.');

        return self::SUCCESS;
    }
}
