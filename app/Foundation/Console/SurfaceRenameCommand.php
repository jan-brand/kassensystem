<?php

namespace App\Foundation\Console;

use App\Foundation\Generation\FilePlan;
use App\Foundation\Registry\ProjectRegistry;
use App\Foundation\Support\JsonFile;
use App\Foundation\Support\Names;

final class SurfaceRenameCommand extends FoundationCommand
{
    protected $signature = 'surface:rename {from} {to} {--dry-run} {--force}';

    protected $description = 'Rename a surface manifest and page references.';

    public function handle(FilePlan $p, ProjectRegistry $r): int
    {
        $f = Names::kebab($this->argument('from'));
        $t = Names::kebab($this->argument('to'));
        foreach ($r->surfaces() as $s) {
            if ($s['name'] === $f) {
                $s['name'] = $t;
                $s['layout'] = $t;
                unset($s['_file']);
                $p->write("resources/surfaces/{$t}/surface.json", JsonFile::encode($s));
                $p->delete("resources/surfaces/{$f}/surface.json");
                foreach ($r->pages() as $page) {
                    if (($page['surface'] ?? '') === $f) {
                        $page['surface'] = $t;
                        $file = str_replace(base_path().'/', '', $page['_file']);
                        unset($page['_file']);
                        $p->write($file, JsonFile::encode($page), true);
                    }
                }$this->warn('Rename the layout Blade file manually if it contains custom code.');

                return $this->runPlan($p, "surface:rename {$f} {$t}");
            }
        }$this->error('Surface not found.');

        return self::FAILURE;
    }
}
