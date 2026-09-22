<?php

namespace App\Foundation\Console;

use App\Foundation\Generation\FilePlan;
use App\Foundation\Registry\ProjectRegistry;
use App\Foundation\Support\JsonFile;
use App\Foundation\Support\Names;

final class PageRenameCommand extends FoundationCommand
{
    protected $signature = 'page:rename {from} {to} {--dry-run} {--force}';

    protected $description = 'Rename a page manifest while preserving its route/view until explicitly refactored.';

    public function handle(FilePlan $p, ProjectRegistry $r): int
    {
        $f = Names::dot($this->argument('from'));
        $t = Names::dot($this->argument('to'));
        foreach ($r->pages() as $page) {
            if ($page['name'] === $f) {
                $page['name'] = $t;
                unset($page['_file']);
                $surface = $page['surface'];
                $p->write("resources/pages/{$surface}/".str_replace('.', '/', $t).'/page.json', JsonFile::encode($page));
                $p->delete("resources/pages/{$surface}/".str_replace('.', '/', $f).'/page.json');
                $this->warn('Route name and Blade path are preserved intentionally; refactor them explicitly if desired.');

                return $this->runPlan($p, "page:rename {$f} {$t}");
            }
        }$this->error('Page not found.');

        return self::FAILURE;
    }
}
