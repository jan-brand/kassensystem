<?php

namespace App\Foundation\Console;

use App\Foundation\Generation\FilePlan;
use App\Foundation\Registry\ProjectRegistry;
use App\Foundation\Support\JsonFile;

final class PermissionSyncCommand extends FoundationCommand
{
    protected $signature = 'permission:sync {--dry-run} {--force}';

    protected $description = 'Add permissions referenced by pages/navigation to the permission catalog.';

    public function handle(FilePlan $p, ProjectRegistry $r): int
    {
        $perms = $r->permissions();
        foreach ($r->pages() as $x) {
            if ($x['permission'] ?? null) {
                $perms[] = $x['permission'];
            }
        }foreach ($r->navigation() as $items) {
            foreach ($items as $x) {
                if ($x['permission'] ?? null) {
                    $perms[] = $x['permission'];
                }
            }
        }$perms = array_values(array_unique($perms));
        sort($perms);
        $p->write('resources/permissions/permissions.json', JsonFile::encode(['permissions' => $perms]), true);

        return $this->runPlan($p, 'permission:sync');
    }
}
