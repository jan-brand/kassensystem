<?php

namespace App\Foundation\Console;

use App\Foundation\Generation\FilePlan;
use App\Foundation\Registry\ProjectRegistry;
use App\Foundation\Support\JsonFile;

final class PageMoveCommand extends FoundationCommand
{
    protected $signature = 'page:move {name} {surface} {--dry-run} {--force}';

    protected $description = 'Move a page manifest to another surface.';

    public function handle(FilePlan $p, ProjectRegistry $r): int
    {
        $target = $this->argument('surface');
        if (! array_filter($r->surfaces(), fn ($s) => $s['name'] === $target)) {
            $this->error('Target surface not found.');

            return self::FAILURE;
        }foreach ($r->pages() as $page) {
            if ($page['name'] === $this->argument('name')) {
                $old = $page['surface'];
                $page['surface'] = $target;
                $oldFile = str_replace(base_path().'/', '', $page['_file']);
                unset($page['_file']);
                $new = "resources/pages/{$target}/".str_replace('.', '/', $page['name']).'/page.json';
                $p->write($new, JsonFile::encode($page));
                $p->delete($oldFile);
                $this->warn('Blade view and route name are preserved; adjust them if surface-specific naming is desired.');

                return $this->runPlan($p, "page:move {$old} {$target}");
            }
        }$this->error('Page not found.');

        return self::FAILURE;
    }
}
