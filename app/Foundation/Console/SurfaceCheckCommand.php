<?php

namespace App\Foundation\Console;

use App\Foundation\Registry\ProjectRegistry;
use Illuminate\Console\Command;

final class SurfaceCheckCommand extends Command
{
    protected $signature = 'surface:check';

    protected $description = 'Validate surface manifests and layouts.';

    public function handle(ProjectRegistry $r): int
    {
        $bad = false;
        foreach ($r->surfaces() as $s) {
            if (! is_file(resource_path('views/layouts/'.($s['layout'] ?? $s['name']).'.blade.php'))) {
                $this->error($s['name'].': missing layout');
                $bad = true;
            }
        }if (! $bad) {
            $this->info('Surfaces valid.');
        }

return $bad ? self::FAILURE : self::SUCCESS;
    }
}
