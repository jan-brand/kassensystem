<?php

namespace App\Foundation\Console;

use App\Foundation\Registry\ProjectRegistry;

final class SurfaceListCommand extends FoundationCommand
{
    protected $signature = 'surface:list';

    protected $description = 'List application surfaces.';

    public function handle(ProjectRegistry $r): int
    {
        $this->tableOrEmpty(['Name', 'Prefix', 'Domain'], array_map(fn ($s) => [$s['name'], $s['prefix'] ?? '', $s['domain'] ?? ''], $r->surfaces()));

        return self::SUCCESS;
    }
}
