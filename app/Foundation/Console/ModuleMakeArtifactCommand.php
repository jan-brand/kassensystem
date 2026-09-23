<?php

namespace App\Foundation\Console;

use App\Foundation\Generation\FilePlan;
use App\Foundation\Modules\ModuleArtifactGenerator;

final class ModuleMakeArtifactCommand extends FoundationCommand
{
    protected $signature = 'module:make:artifact {module} {type} {name} {--dry-run} {--force}';

    protected $description = 'Create a model/action/query/service/event/etc. inside a module.';

    public function handle(FilePlan $p, ModuleArtifactGenerator $g): int
    {
        try {
            $g->plan($p, $this->argument('module'), $this->argument('type'), $this->argument('name'));
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return $this->runPlan($p, 'module:make:artifact');
    }
}
