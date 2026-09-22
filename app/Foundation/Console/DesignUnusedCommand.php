<?php

namespace App\Foundation\Console;

use App\Foundation\Design\DesignInspector;
use App\Foundation\Registry\ProjectRegistry;
use Illuminate\Console\Command;

final class DesignUnusedCommand extends Command
{
    protected $signature = 'design:unused';

    protected $description = 'List design items not referenced by another design manifest.';

    public function handle(ProjectRegistry $r, DesignInspector $i): int
    {
        $pageTemplates = array_values(array_filter(array_map(fn ($p) => $p['template'] ?? null, $r->pages())));
        $unused = [];
        foreach ($r->design() as $x) {
            if (! $i->usage($x['name']) && ! in_array($x['name'], $pageTemplates, true)) {
                $unused[] = $x['name'];
            }
        }foreach ($unused as $x) {
            $this->line($x);
        }if (! $unused) {
            $this->comment('No unused design items.');
        }

return self::SUCCESS;
    }
}
