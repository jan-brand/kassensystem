<?php

namespace App\Foundation\Console;

use App\Foundation\Architecture\ArchitectureInspector;
use Illuminate\Console\Command;

final class ModuleGraphCommand extends Command
{
    protected $signature = 'module:graph';

    protected $description = 'Print module dependency graph.';

    public function handle(ArchitectureInspector $i): int
    {
        foreach ($i->graph() as $m => $deps) {
            $this->line($m.' -> '.($deps ? implode(', ', $deps) : '(none)'));
        }

        return self::SUCCESS;
    }
}
