<?php

namespace App\Foundation\Console;

use App\Foundation\Registry\ProjectRegistry;

final class ModuleListCommand extends FoundationCommand
{
    protected $signature = 'module:list';

    protected $description = 'List application modules.';

    public function handle(ProjectRegistry $r): int
    {
        $this->tableOrEmpty(['Name', 'Depends on'], array_map(fn ($m) => [$m['name'], implode(', ', $m['depends_on'] ?? [])], $r->modules()));

        return self::SUCCESS;
    }
}
