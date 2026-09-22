<?php

namespace App\Foundation\Console;

use App\Foundation\Architecture\ArchitectureInspector;
use Illuminate\Console\Command;

final class ModuleCheckCommand extends Command
{
    protected $signature = 'module:check';

    protected $description = 'Validate module manifests and dependencies.';

    public function handle(ArchitectureInspector $i): int
    {
        $p = $i->problems();
        if ($p) {
            foreach ($p as $x) {
                $this->error($x);
            }

return self::FAILURE;
        }$this->info('Module graph is valid.');

        return self::SUCCESS;
    }
}
