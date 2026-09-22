<?php

namespace App\Foundation\Console;

use App\Foundation\Registry\ProjectRegistry;
use Illuminate\Console\Command;

final class RouteCheckCommand extends Command
{
    protected $signature = 'route:check';

    protected $description = 'Check manifest route names for duplicates and missing values.';

    public function handle(ProjectRegistry $r): int
    {
        $seen = [];
        $bad = false;
        foreach ($r->pages() as $p) {
            $name = $p['route_name'] ?? '';
            if ($name === '') {
                $this->error($p['name'].': missing route_name');
                $bad = true;

                continue;
            }if (isset($seen[$name])) {
                $this->error("Duplicate route name {$name}");
                $bad = true;
            }$seen[$name] = true;
        }if (! $bad) {
            $this->info('Manifest routes valid.');
        }

return $bad ? self::FAILURE : self::SUCCESS;
    }
}
