<?php

namespace App\Foundation\Console;

use App\Foundation\Architecture\ArchitectureInspector;
use Illuminate\Console\Command;

final class ArchitectureDependenciesCommand extends Command
{
    protected $signature = 'architecture:dependencies {module?}';

    protected $description = 'Show direct dependencies and dependents.';

    public function handle(ArchitectureInspector $i): int
    {
        $g = $i->graph();
        $m = $this->argument('module');
        if (! $m) {
            foreach ($g as $n => $d) {
                $this->line($n.': '.implode(', ', $d));
            }

return self::SUCCESS;
        }if (! array_key_exists($m, $g)) {
            $this->error('Module not found.');

            return self::FAILURE;
        }$dependents = [];
        foreach ($g as $n => $d) {
            if (in_array($m, $d, true)) {
                $dependents[] = $n;
            }
        }$this->line('Depends on: '.($g[$m] ? implode(', ', $g[$m]) : 'none'));
        $this->line('Used by: '.($dependents ? implode(', ', $dependents) : 'none'));

        return self::SUCCESS;
    }
}
