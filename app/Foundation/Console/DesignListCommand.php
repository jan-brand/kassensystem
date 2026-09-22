<?php

namespace App\Foundation\Console;

use App\Foundation\Registry\ProjectRegistry;

final class DesignListCommand extends FoundationCommand
{
    protected $signature = 'design:list {--type=}';

    protected $description = 'List design-system components, patterns and templates.';

    public function handle(ProjectRegistry $r): int
    {
        $type = $this->option('type');
        $items = $type ? $r->design($type.'s') : $r->design();
        $this->tableOrEmpty(['Name', 'Type', 'Status'], array_map(fn ($i) => [$i['name'], $i['type'] ?? '', $i['status'] ?? ''], $items));

        return self::SUCCESS;
    }
}
