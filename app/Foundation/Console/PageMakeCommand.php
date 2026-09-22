<?php

namespace App\Foundation\Console;

use App\Foundation\Generation\FilePlan;
use App\Foundation\Registry\ProjectRegistry;
use App\Foundation\Support\JsonFile;
use App\Foundation\Support\Names;

final class PageMakeCommand extends FoundationCommand
{
    protected $signature = 'page:make {name} {--surface=} {--uri=} {--route-name=} {--permission=} {--template=} {--dry-run} {--force}';

    protected $description = 'Create a manifest-driven view page.';

    public function handle(FilePlan $p, ProjectRegistry $r): int
    {
        $name = Names::dot($this->argument('name'));
        $surface = Names::kebab($this->option('surface') ?: config('foundation.default_surface'));
        if (! array_filter($r->surfaces(), fn ($s) => $s['name'] === $surface)) {
            $this->error("Surface {$surface} does not exist.");

            return self::FAILURE;
        }$slug = str_replace('.', '/', $name);
        $view = 'pages.'.str_replace('/', '.', $surface.'/'.$slug);
        $uri = $this->option('uri') ?: ('/'.str_replace('.', '/', $name));
        $manifest = ['name' => $name, 'surface' => $surface, 'uri' => $uri, 'route_name' => $this->option('route-name') ?: $surface.'.'.$name, 'permission' => $this->option('permission'), 'template' => $this->option('template'), 'handler' => ['type' => 'view', 'target' => $view]];
        $p->write("resources/pages/{$surface}/{$slug}/page.json", JsonFile::encode($manifest));
        $layout = $surface;
        $p->write("resources/views/pages/{$surface}/{$slug}.blade.php", "@extends('layouts.{$layout}')\n@section('title', '".addslashes($name)."')\n@section('content')\n    <h1 class=\"text-3xl font-bold\">{$name}</h1>\n@endsection\n");

        return $this->runPlan($p, "page:make {$name}");
    }
}
