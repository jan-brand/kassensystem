<?php

namespace App\Foundation\Console;

use App\Foundation\Registry\ProjectRegistry;
use App\Foundation\Support\JsonFile;
use Illuminate\Console\Command;

final class AppInfoCommand extends Command
{
    protected $signature = 'app:info';

    protected $description = 'Show foundation and project information.';

    public function handle(ProjectRegistry $registry): int
    {
        $f = JsonFile::read(base_path('foundation.json'));
        $this->table(['Key', 'Value'], [['Project', $f['project']['name'] ?? config('app.name')], ['PHP', PHP_VERSION], ['Laravel', app()->version()], ['Modules', count($registry->modules())], ['Surfaces', count($registry->surfaces())], ['Pages', count($registry->pages())], ['Design items', count($registry->design())]]);

        return self::SUCCESS;
    }
}
