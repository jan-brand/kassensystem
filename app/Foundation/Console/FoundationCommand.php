<?php

namespace App\Foundation\Console;

use App\Foundation\Generation\FilePlan;
use Illuminate\Console\Command;

abstract class FoundationCommand extends Command
{
    protected function runPlan(
        FilePlan $plan,
        string $label,
        bool $recordHistory = true,
    ): int {
        foreach ($plan->descriptions() as $line) {
            $this->line($line);
        }

        $options = $this->input->getOptions();
        $dryRun = (bool) ($options['dry-run'] ?? false);
        $force = (bool) ($options['force'] ?? false);

        if ($dryRun) {
            $this->comment('Dry run: no files were changed.');

            return self::SUCCESS;
        }

        $id = $plan->apply(
            $label,
            $force,
            $recordHistory,
        );

        $this->info('Done.'.($id !== null ? " History: {$id}" : ''));

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $headers
     * @param  list<array<int, mixed>>  $rows
     */
    protected function tableOrEmpty(
        array $headers,
        array $rows,
        string $empty = 'No entries.',
    ): void {
        if ($rows === []) {
            $this->comment($empty);

            return;
        }

        $this->table($headers, $rows);
    }
}
