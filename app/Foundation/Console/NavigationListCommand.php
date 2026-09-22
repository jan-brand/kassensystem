<?php

namespace App\Foundation\Console;

use App\Foundation\Registry\ProjectRegistry;

final class NavigationListCommand extends FoundationCommand
{
    protected $signature = 'navigation:list {surface?}';

    protected $description = 'List navigation items.';

    public function handle(ProjectRegistry $r): int
    {
        $all = $r->navigation();
        foreach ($all as $s => $items) {
            if ($this->argument('surface') && $s !== $this->argument('surface')) {
                continue;
            }$this->newLine();
            $this->info($s);
            $this->tableOrEmpty(['Label', 'Route', 'Permission'], array_map(fn ($i) => [$i['label'] ?? '', $i['route'] ?? '', $i['permission'] ?? ''], $items));
        }

return self::SUCCESS;
    }
}
