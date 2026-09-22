<?php

namespace App\Foundation\Console;

use Illuminate\Console\Command;

final class DocsCheckCommand extends Command
{
    protected $signature = 'docs:check';

    protected $description = 'Check that core foundation documentation files exist.';

    public function handle(): int
    {
        $files = ['README.md', 'docs/ARCHITECTURE.md', 'docs/CLI_REFERENCE.md', 'docs/DESIGN_SYSTEM.md', 'docs/ENVIRONMENT.md', 'docs/MODULES.md', 'docs/PAGES_AND_SURFACES.md', 'docs/GENERATOR_CONTRACT.md'];
        $bad = false;
        foreach ($files as $f) {
            if (! is_file(base_path($f))) {
                $this->error("Missing {$f}");
                $bad = true;
            }
        }if (! $bad) {
            $this->info('Documentation baseline present.');
        }

return $bad ? self::FAILURE : self::SUCCESS;
    }
}
