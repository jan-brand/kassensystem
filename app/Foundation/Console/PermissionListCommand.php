<?php

namespace App\Foundation\Console;

use App\Foundation\Registry\ProjectRegistry;
use Illuminate\Console\Command;

final class PermissionListCommand extends Command
{
    protected $signature = 'permission:list';

    protected $description = 'List permission catalog.';

    public function handle(ProjectRegistry $r): int
    {
        foreach ($r->permissions() as $p) {
            $this->line($p);
        }

return self::SUCCESS;
    }
}
