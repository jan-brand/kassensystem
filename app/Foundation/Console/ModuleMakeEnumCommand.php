<?php
namespace App\Foundation\Console;
use App\Foundation\Generation\FilePlan;
use App\Foundation\Modules\ModuleArtifactGenerator;
final class ModuleMakeEnumCommand extends FoundationCommand
{protected $signature='module:make:enum {module} {name} {--dry-run} {--force}';protected $description='Create a enum inside a module.';public function handle(FilePlan $p,ModuleArtifactGenerator $g):int{try{$g->plan($p,$this->argument('module'),'enum',$this->argument('name'));}catch(\Throwable $e){$this->error($e->getMessage());return self::FAILURE;}return $this->runPlan($p,'module:make:enum');}}
