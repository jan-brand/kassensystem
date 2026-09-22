<?php

namespace App\Foundation\Console;

use App\Foundation\Generation\FilePlan;
use App\Foundation\Support\JsonFile;

final class PermissionMakeCommand extends FoundationCommand
{
    protected $signature = 'permission:make {name} {--dry-run} {--force}';

    protected $description = 'Add a permission to the permission catalog.';

    public function handle(FilePlan $p): int
    {
        $rel = 'resources/permissions/permissions.json';
        $d = JsonFile::read(base_path($rel), ['permissions' => []]);
        $d['permissions'][] = $this->argument('name');
        $d['permissions'] = array_values(array_unique($d['permissions']));
        sort($d['permissions']);
        $p->write($rel, JsonFile::encode($d), true);

        return $this->runPlan($p, 'permission:make');
    }
}
