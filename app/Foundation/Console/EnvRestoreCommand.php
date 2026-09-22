<?php

namespace App\Foundation\Console;

use Illuminate\Console\Command;

final class EnvRestoreCommand extends Command
{
    protected $signature = 'env:restore {backup?} {--target=.env} {--yes}';

    protected $description = 'Restore an environment backup created by env:backup or env:sync --force.';

    public function handle(): int
    {
        $dir = base_path('.foundation/env-backups');
        $backup = $this->argument('backup');
        if (! $backup) {
            $files = glob($dir.'/*.bak') ?: [];
            rsort($files);
            $backup = $files[0] ?? null;
        } else {
            $backup = str_starts_with($backup, '/') ? $backup : $dir.'/'.$backup;
        }if (! $backup || ! is_file($backup)) {
            $this->error('Backup not found.');

            return self::FAILURE;
        }if (! $this->option('yes') && $this->input->isInteractive() && ! $this->confirm('Restore '.$backup.' to '.$this->option('target').'?', false)) {
            return self::SUCCESS;
        }copy($backup, base_path($this->option('target')));
        $this->info('Restored.');

        return self::SUCCESS;
    }
}
