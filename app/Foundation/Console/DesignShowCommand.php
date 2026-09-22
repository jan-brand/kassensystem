<?php

namespace App\Foundation\Console;

use App\Foundation\Registry\ProjectRegistry;
use Illuminate\Console\Command;

final class DesignShowCommand extends Command
{
    protected $signature = 'design:show {name}';

    protected $description = 'Show one design-system item.';

    public function handle(ProjectRegistry $r): int
    {
        foreach ($r->design() as $i) {
            if (strcasecmp($i['name'], $this->argument('name')) === 0) {
                $this->line(json_encode(array_diff_key($i, ['_file' => 1]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

                return self::SUCCESS;
            }
        }$this->error('Design item not found.');

        return self::FAILURE;
    }
}
