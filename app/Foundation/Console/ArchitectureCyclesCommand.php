<?php

namespace App\Foundation\Console;

use App\Foundation\Architecture\ArchitectureInspector;
use Illuminate\Console\Command;

final class ArchitectureCyclesCommand extends Command
{
    protected $signature = 'architecture:cycles';

    protected $description = 'Find circular module dependencies.';

    public function handle(ArchitectureInspector $i): int
    {
        $c = $i->cycles();
        if (! $c) {
            $this->info('No cycles.');

            return self::SUCCESS;
        }foreach ($c as $x) {
            $this->error(implode(' -> ', $x));
        }

return self::FAILURE;
    }
}
