<?php

namespace App\Foundation\Console;

use App\Foundation\Architecture\ArchitectureInspector;
use App\Foundation\Design\DesignInspector;
use App\Foundation\Registry\ProjectRegistry;
use Illuminate\Console\Command;

final class AppStatusCommand extends Command
{
    protected $signature = 'app:status';

    protected $description = 'Show a compact health summary for the project foundation.';

    public function handle(ProjectRegistry $r, ArchitectureInspector $a, DesignInspector $d): int
    {
        $rows = [['Modules', count($r->modules()), count($a->problems()) ? 'ISSUES' : 'OK'], ['Design', count($r->design()), count($d->problems()) ? 'ISSUES' : 'OK'], ['Surfaces', count($r->surfaces()), 'OK'], ['Pages', count($r->pages()), 'OK'], ['Permissions', count($r->permissions()), 'OK']];
        $this->table(['Area', 'Count', 'Status'], $rows);

        return ($a->problems() || $d->problems()) ? self::FAILURE : self::SUCCESS;
    }
}
