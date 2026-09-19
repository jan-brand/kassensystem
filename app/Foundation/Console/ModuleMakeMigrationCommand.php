<?php
namespace App\Foundation\Console;
use App\Foundation\Generation\FilePlan;
use App\Foundation\Modules\ModuleArtifactGenerator;
final class ModuleMakeMigrationCommand extends FoundationCommand
{protected $signature='module:make:migration {module} {name} {--dry-run} {--force}';protected $description='Create a migration inside a module.';public function handle(FilePlan $p,ModuleArtifactGenerator $g):int{try{$g->plan($p,$this->argument('module'),'migration',$this->argument('name'));}catch(\Throwable $e){$this->error($e->getMessage());return self::FAILURE;}return $this->runPlan($p,'module:make:migration');}}
