<?php

namespace App\Foundation\Console;

use App\Foundation\Generation\FilePlan;
use App\Foundation\Support\JsonFile;

final class NavigationRemoveCommand extends FoundationCommand
{
    protected $signature = 'navigation:remove {surface} {route} {--dry-run} {--force}';

    protected $description = 'Remove navigation items matching a route name.';

    public function handle(FilePlan $p): int
    {
        $rel = 'resources/navigation/'.$this->argument('surface').'.json';
        $d = JsonFile::read(base_path($rel), ['items' => []]);
        $d['items'] = array_values(array_filter($d['items'], fn ($i) => ($i['route'] ?? '') !== $this->argument('route')));
        $p->write($rel, JsonFile::encode($d), true);

        return $this->runPlan($p, 'navigation:remove');
    }
}
