<?php

namespace App\Foundation\Console;

use App\Foundation\Generation\FilePlan;
use App\Foundation\Support\JsonFile;
use App\Foundation\Support\Names;

final class AppConfigureCommand extends FoundationCommand
{
    protected $signature = 'app:configure {--name=} {--default-surface=} {--tests=} {--docs=} {--dashboard=} {--env-target=*} {--dry-run} {--force}';

    protected $description = 'Interactively configure project-specific Foundation defaults.';

    public function handle(FilePlan $plan): int
    {
        $data = JsonFile::read(base_path('foundation.json'));
        $name = $this->option('name') ?: ($data['project']['name'] ?? 'Webapp Foundation');
        $surface = $this->option('default-surface') ?: ($data['project']['default_surface'] ?? 'public');
        $targets = $this->option('env-target') ?: ($data['environment']['targets'] ?? ['.env', '.env.testing']);

        if ($this->input->isInteractive()) {
            $name = $this->ask('Project name', $name);
            $surface = $this->ask('Default surface', $surface);
            $tests = $this->confirm('Generate tests by default?', $data['generator']['tests'] ?? true);
            $docs = $this->confirm('Generate documentation by default?', $data['generator']['docs'] ?? true);
            $dashboard = $this->confirm('Enable the local Foundation dashboard?', $data['dashboard']['enabled'] ?? true);
            $targetInput = $this->ask('Environment targets (comma separated)', implode(',', $targets));
            $targets = array_values(array_filter(array_map('trim', explode(',', (string) $targetInput))));
        } else {
            $tests = filter_var($this->option('tests') ?? ($data['generator']['tests'] ?? true), FILTER_VALIDATE_BOOL);
            $docs = filter_var($this->option('docs') ?? ($data['generator']['docs'] ?? true), FILTER_VALIDATE_BOOL);
            $dashboard = filter_var($this->option('dashboard') ?? ($data['dashboard']['enabled'] ?? true), FILTER_VALIDATE_BOOL);
        }

        $data['project'] = [
            'name' => $name,
            'slug' => Names::kebab($name),
            'default_surface' => Names::kebab($surface),
        ];
        $data['generator'] = array_merge($data['generator'] ?? [], ['tests' => $tests, 'docs' => $docs]);
        $data['environment'] = array_merge($data['environment'] ?? [], ['targets' => $targets]);
        $data['dashboard'] = array_merge($data['dashboard'] ?? [], ['enabled' => $dashboard, 'local_only' => true]);

        $plan->write('foundation.json', JsonFile::encode($data), true);

        return $this->runPlan($plan, 'app:configure');
    }
}
