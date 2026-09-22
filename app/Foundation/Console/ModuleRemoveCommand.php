<?php

namespace App\Foundation\Console;

use App\Foundation\Architecture\ArchitectureInspector;
use App\Foundation\Generation\FilePlan;
use App\Foundation\Support\Names;

final class ModuleRemoveCommand extends FoundationCommand
{
    protected $signature = 'module:remove {name} {--dry-run} {--force}';

    protected $description = 'Remove a module manifest after dependency checks.';

    public function handle(FilePlan $p, ArchitectureInspector $i): int
    {
        $n = Names::studly($this->argument('name'));
        foreach ($i->graph() as $m => $deps) {
            if (in_array($n, $deps, true) && ! $this->option('force')) {
                $this->error("{$m} depends on {$n}; use --force only after resolving the dependency.");

                return self::FAILURE;
            }
        }$p->delete("app/Modules/{$n}/module.json");
        $this->warn('Source files are intentionally not recursively deleted.');

        return $this->runPlan($p, "module:remove {$n}");
    }
}
