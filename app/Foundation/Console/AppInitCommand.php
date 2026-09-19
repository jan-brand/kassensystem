<?php
namespace App\Foundation\Console;
use App\Foundation\Generation\FilePlan;
use App\Foundation\Support\JsonFile;
use App\Foundation\Support\Names;

final class AppInitCommand extends FoundationCommand
{
    protected $signature = 'app:init {--name=} {--surface=} {--dry-run} {--force}';
    protected $description = 'Initialize or refresh the neutral webapp foundation structure.';
    public function handle(FilePlan $plan): int
    {
        $current = JsonFile::read(base_path('foundation.json'));
        $name = $this->option('name') ?: ($current['project']['name'] ?? config('app.name'));
        if ($this->input->isInteractive() && ! $this->option('name')) { $name = $this->ask('Project name', $name); }
        $surface = $this->option('surface') ?: ($current['project']['default_surface'] ?? 'public');
        $surface = Names::kebab($surface);
        $current['project'] = array_merge($current['project'] ?? [], ['name' => $name, 'slug' => Names::kebab($name), 'default_surface' => $surface]);
        $plan->write('foundation.json', JsonFile::encode($current), true);
        if (! is_file(resource_path("surfaces/{$surface}/surface.json"))) {
            $plan->write("resources/surfaces/{$surface}/surface.json", JsonFile::encode(['name'=>$surface,'description'=>'Generated surface','prefix'=>'','domain'=>null,'middleware'=>['web'],'layout'=>$surface]));
            $plan->write("resources/views/layouts/{$surface}.blade.php", "<!doctype html><html><body><main>@yield('content')</main></body></html>\n");
        }
        return $this->runPlan($plan, 'app:init');
    }
}
