<?php

namespace App\Foundation\Console;

use App\Foundation\Generation\FilePlan;
use App\Foundation\Support\JsonFile;
use App\Foundation\Support\Names;

final class ModuleMakeCommand extends FoundationCommand
{
    protected $signature = 'module:make {name} {--depends=*} {--description=} {--dry-run} {--force}';

    protected $description = 'Create a new self-describing application module.';

    public function handle(FilePlan $p): int
    {
        $n = Names::studly($this->argument('name'));
        $rel = "app/Modules/{$n}/module.json";
        $m = ['name' => $n, 'description' => $this->option('description') ?: $n.' module', 'depends_on' => array_map([Names::class, 'studly'], $this->option('depends')), 'exports' => []];
        $p->write($rel, JsonFile::encode($m));
        $p->write("app/Modules/{$n}/README.md", "# {$n}\n\nGenerated module. Add responsibilities, invariants and public exports here.\n");

        return $this->runPlan($p, "module:make {$n}");
    }
}
