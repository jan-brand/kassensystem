<?php

namespace App\Foundation\Console;

use Illuminate\Console\Command;

final class GeneratedCheckCommand extends Command
{
    protected $signature = 'generated:check';

    protected $description = 'Check expected generated artifacts.';

    public function handle(): int
    {
        $files = ['storage/framework/foundation/registry.json', 'docs/generated/catalog.md'];
        $bad = false;
        foreach ($files as $f) {
            if (! is_file(base_path($f))) {
                $this->error("Missing {$f}");
                $bad = true;
            }
        }if (! $bad) {
            $this->info('Generated artifacts present.');
        }

return $bad ? self::FAILURE : self::SUCCESS;
    }
}
