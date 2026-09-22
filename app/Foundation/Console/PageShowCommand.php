<?php

namespace App\Foundation\Console;

use App\Foundation\Registry\ProjectRegistry;
use Illuminate\Console\Command;

final class PageShowCommand extends Command
{
    protected $signature = 'page:show {name}';

    protected $description = 'Show one page manifest.';

    public function handle(ProjectRegistry $r): int
    {
        foreach ($r->pages() as $p) {
            if ($p['name'] === $this->argument('name')) {
                $this->line(json_encode(array_diff_key($p, ['_file' => 1]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

                return self::SUCCESS;
            }
        }$this->error('Page not found.');

        return self::FAILURE;
    }
}
