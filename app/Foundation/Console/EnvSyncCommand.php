<?php

namespace App\Foundation\Console;

use App\Foundation\Environment\EnvSynchronizer;
use App\Foundation\Generation\FilePlan;

final class EnvSyncCommand extends FoundationCommand
{
    protected $signature = 'env:sync {--target=*} {--force} {--prune} {--backup} {--yes} {--dry-run}';

    protected $description = 'Synchronize .env structures from .env.example while preserving real values by default.';

    public function handle(EnvSynchronizer $sync, FilePlan $plan): int
    {
        $templateRel = config('foundation.environment.template', '.env.example');
        $template = base_path($templateRel);
        if (! is_file($template)) {
            $this->error("Missing {$templateRel}");

            return self::FAILURE;
        } $templateContent = (string) file_get_contents($template);
        $targets = $this->option('target') ?: config('foundation.environment.targets', ['.env', '.env.testing']);
        $force = (bool) $this->option('force');
        if (($force || $this->option('prune')) && ! $this->option('yes')) {
            if (! $this->input->isInteractive()) {
                $this->error('Destructive env sync requires --yes in non-interactive mode.');

                return self::FAILURE;
            } if (! $this->confirm('This mode may replace or remove environment values. Continue?', false)) {
                return self::SUCCESS;
            }
        }
        foreach ($targets as $target) {
            $path = base_path($target);
            $old = is_file($path) ? (string) file_get_contents($path) : '';
            $result = $sync->synchronize($templateContent, $old, $force, (bool) $this->option('prune'));
            if (! $result['changed']) {
                $this->line("UNCHANGED {$target}");

                continue;
            } if (($force || $this->option('backup')) && is_file($path)) {
                $backup = '.foundation/env-backups/'.basename($target).'.'.date('Ymd-His').'.bak';
                $plan->write($backup, $old);
                $this->comment("Backup planned: {$backup}");
            } $plan->write($target, $result['content'], true);
            $this->line($target.': +'.count($result['missing']).' missing, '.count($result['extra']).' extra preserved');
        }

        return $this->runPlan($plan, 'env:sync', false);
    }
}
