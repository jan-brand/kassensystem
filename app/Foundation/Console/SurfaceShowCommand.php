<?php

namespace App\Foundation\Console;

use App\Foundation\Registry\ProjectRegistry;
use Illuminate\Console\Command;

final class SurfaceShowCommand extends Command
{
    protected $signature = 'surface:show {name}';

    protected $description = 'Show a surface manifest.';

    public function handle(ProjectRegistry $r): int
    {
        foreach ($r->surfaces() as $s) {
            if ($s['name'] === $this->argument('name')) {
                $this->line(json_encode(array_diff_key($s, ['_file' => 1]), JSON_PRETTY_PRINT));

                return self::SUCCESS;
            }
        }$this->error('Surface not found.');

        return self::FAILURE;
    }
}
