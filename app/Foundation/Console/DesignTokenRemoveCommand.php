<?php
namespace App\Foundation\Console;use App\Foundation\Generation\FilePlan;use App\Foundation\Support\JsonFile;
final class DesignTokenRemoveCommand extends FoundationCommand{protected $signature='design:token:remove {name} {--dry-run} {--force}';protected $description='Remove a token from the token registry.';public function handle(FilePlan $p):int{$rel='resources/design/tokens/tokens.json';$d=JsonFile::read(base_path($rel),['tokens'=>[]]);unset($d['tokens'][$this->argument('name')]);$p->write($rel,JsonFile::encode($d),true);return $this->runPlan($p,'design:token:remove');}}
