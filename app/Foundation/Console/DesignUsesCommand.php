<?php

namespace App\Foundation\Console;

use App\Foundation\Design\DesignInspector;
use Illuminate\Console\Command;

final class DesignUsesCommand extends Command
{
    protected $signature = 'design:uses {name}';

    protected $description = 'Show which design items use the named item.';

    public function handle(DesignInspector $i): int
    {
        $u = $i->usage($this->argument('name'));
        if (! $u) {
            $this->comment('No design-system users found.');
        } else {
            foreach ($u as $x) {
                $this->line($x);
            }
        }

        return self::SUCCESS;
    }
}
