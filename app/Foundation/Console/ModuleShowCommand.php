<?php

namespace App\Foundation\Console;

use App\Foundation\Registry\ProjectRegistry;
use Illuminate\Console\Command;

final class ModuleShowCommand extends Command
{
    protected $signature = 'module:show {name}';

    protected $description = 'Show one module manifest.';

    public function handle(ProjectRegistry $r): int
    {
        foreach ($r->modules() as $m) {
            if (strcasecmp($m['name'], $this->argument('name')) === 0) {
                $this->line(json_encode(array_diff_key($m, ['_file' => 1]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

                return self::SUCCESS;
            }
        }$this->error('Module not found.');

        return self::FAILURE;
    }
}
