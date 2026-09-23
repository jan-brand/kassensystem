<?php

namespace App\Foundation\Console;

use App\Foundation\Generation\FilePlan;
use App\Foundation\Modules\ModuleArtifactGenerator;

final class ModuleMakeRequestCommand extends FoundationCommand
{
    protected $signature = 'module:make:request {module} {name} {--dry-run} {--force}';

    protected $description = 'Create a request inside a module.';

    public function handle(FilePlan $p, ModuleArtifactGenerator $g): int
    {
        try {
            $g->plan($p, $this->argument('module'), 'request', $this->argument('name'));
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return $this->runPlan($p, 'module:make:request');
    }
}
