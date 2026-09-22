<?php

namespace App\Foundation\Console;

use App\Foundation\Registry\ProjectRegistry;
use Illuminate\Console\Command;

final class RouteUnusedCommand extends Command
{
    protected $signature = 'route:unused';

    protected $description = 'List manifest routes not referenced by navigation.';

    public function handle(ProjectRegistry $r): int
    {
        $used = [];
        foreach ($r->navigation() as $items) {
            foreach ($items as $i) {
                $used[] = $i['route'] ?? '';
            }
        }foreach ($r->pages() as $p) {
            if (! in_array($p['route_name'], $used, true)) {
                $this->line($p['route_name']);
            }
        }

return self::SUCCESS;
    }
}
