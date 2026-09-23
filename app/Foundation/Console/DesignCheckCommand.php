<?php

namespace App\Foundation\Console;

use App\Foundation\Design\DesignInspector;
use Illuminate\Console\Command;

final class DesignCheckCommand extends Command
{
    protected $signature = 'design:check';

    protected $description = 'Validate design hierarchy and dependencies.';

    public function handle(DesignInspector $i): int
    {
        $p = $i->problems();
        if ($p) {
            foreach ($p as $x) {
                $this->error($x);
            }

            return self::FAILURE;
        }$this->info('Design system is valid.');

        return self::SUCCESS;
    }
}
