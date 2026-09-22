<?php

namespace App\Foundation\Console;

use App\Foundation\Generation\FilePlan;
use App\Foundation\Registry\ProjectRegistry;
use App\Foundation\Support\JsonFile;

final class ManifestBuildCommand extends FoundationCommand
{
    protected $signature = 'manifest:build {--dry-run} {--force}';

    protected $description = 'Build a generated registry snapshot from source manifests.';

    public function handle(FilePlan $p, ProjectRegistry $r): int
    {
        $snapshot = ['generated_at' => date(DATE_ATOM), 'modules' => $r->modules(), 'surfaces' => $r->surfaces(), 'pages' => $r->pages(), 'design' => $r->design(), 'permissions' => $r->permissions(), 'navigation' => $r->navigation()];
        foreach (['modules', 'surfaces', 'pages', 'design'] as $k) {
            foreach ($snapshot[$k] as &$x) {
                unset($x['_file']);
            }
        }$p->write('storage/framework/foundation/registry.json', JsonFile::encode($snapshot), true);

        return $this->runPlan($p, 'manifest:build');
    }
}
