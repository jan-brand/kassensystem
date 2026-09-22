<?php

namespace App\Foundation\Console;

use App\Foundation\Generation\HistoryRepository;
use Illuminate\Console\Command;

final class ToolingHistoryCommand extends Command
{
    protected $signature = 'tooling:history {--limit=20}';

    protected $description = 'Show file-changing generator history.';

    public function handle(HistoryRepository $h): int
    {
        $rows = [];
        foreach (array_slice($h->all(), 0, (int) $this->option('limit')) as $e) {
            $rows[] = [$e['id'], $e['label'], count($e['operations'])];
        }$this->table(['ID', 'Label', 'Files'], $rows);

        return self::SUCCESS;
    }
}
