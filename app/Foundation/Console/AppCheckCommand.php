<?php
namespace App\Foundation\Console;

use App\Foundation\Architecture\ArchitectureInspector;
use App\Foundation\Design\DesignInspector;
use App\Foundation\Environment\EnvSynchronizer;
use App\Foundation\Registry\ProjectRegistry;
use App\Foundation\Support\JsonFile;
use Illuminate\Console\Command;

final class AppCheckCommand extends Command
{
    protected $signature = 'app:check';
    protected $description = 'Run the central Foundation consistency gate.';

    public function handle(ProjectRegistry $registry, ArchitectureInspector $architecture, DesignInspector $design, EnvSynchronizer $env): int
    {
        $problems = array_merge($architecture->problems(), $design->problems());

        $surfaceNames = array_column($registry->surfaces(), 'name');
        foreach ($registry->surfaces() as $surface) {
            $layout = $surface['layout'] ?? $surface['name'];
            if (! is_file(resource_path("views/layouts/{$layout}.blade.php"))) {
                $problems[] = "Surface {$surface['name']} is missing layout {$layout}.";
            }
        }

        $templates = array_map(
            fn (array $item) => $item['name'],
            array_filter($registry->design(), fn (array $item) => ($item['type'] ?? '') === 'template'),
        );
        $permissions = $registry->permissions();
        $routeNames = [];
        foreach ($registry->pages() as $page) {
            if (! isset($page['name'], $page['surface'], $page['handler'], $page['route_name'])) {
                $problems[] = 'Invalid page manifest: '.($page['_file'] ?? '?');
                continue;
            }
            if (! in_array($page['surface'], $surfaceNames, true)) {
                $problems[] = "Page {$page['name']} references missing surface {$page['surface']}.";
            }
            $view = $page['handler']['target'] ?? '';
            if (($page['handler']['type'] ?? 'view') === 'view' && ! is_file(resource_path('views/'.str_replace('.', '/', $view).'.blade.php'))) {
                $problems[] = "Page {$page['name']} references missing view {$view}.";
            }
            if (($page['template'] ?? null) && ! in_array($page['template'], $templates, true)) {
                $problems[] = "Page {$page['name']} references missing design template {$page['template']}.";
            }
            if (($page['permission'] ?? null) && ! in_array($page['permission'], $permissions, true)) {
                $problems[] = "Page {$page['name']} references missing permission {$page['permission']}.";
            }
            if (in_array($page['route_name'], $routeNames, true)) {
                $problems[] = "Duplicate page route name {$page['route_name']}.";
            }
            $routeNames[] = $page['route_name'];
        }

        foreach ($registry->navigation() as $surface => $items) {
            if (! in_array($surface, $surfaceNames, true)) {
                $problems[] = "Navigation exists for unknown surface {$surface}.";
            }
            foreach ($items as $item) {
                if (! in_array($item['route'] ?? '', $routeNames, true)) {
                    $problems[] = "Navigation {$surface} references missing route ".($item['route'] ?? '(empty)').'.';
                }
                if (($item['permission'] ?? null) && ! in_array($item['permission'], $permissions, true)) {
                    $problems[] = "Navigation {$surface} references missing permission {$item['permission']}.";
                }
            }
        }

        try {
            $tokens = JsonFile::read(resource_path('design/tokens/tokens.json'), ['tokens' => []]);
            if (! isset($tokens['tokens']) || ! is_array($tokens['tokens'])) {
                $problems[] = 'Design token registry must contain a tokens object.';
            }
        } catch (\Throwable $exception) {
            $problems[] = $exception->getMessage();
        }

        $templatePath = base_path(config('foundation.environment.template'));
        if (! is_file($templatePath)) {
            $problems[] = 'Missing environment template '.config('foundation.environment.template').'.';
        } else {
            $templateContent = (string) file_get_contents($templatePath);
            foreach (config('foundation.environment.targets', []) as $target) {
                $targetPath = base_path($target);
                if (! is_file($targetPath)) {
                    $problems[] = "Missing environment file: {$target}";
                    continue;
                }
                $diff = $env->diff($templateContent, (string) file_get_contents($targetPath));
                if ($diff['missing']) {
                    $problems[] = $target.' missing keys: '.implode(', ', $diff['missing']);
                }
            }
        }

        foreach (['README.md', 'docs/ARCHITECTURE.md', 'docs/CLI_REFERENCE.md', 'docs/DESIGN_SYSTEM.md', 'docs/ENVIRONMENT.md'] as $requiredDoc) {
            if (! is_file(base_path($requiredDoc))) {
                $problems[] = "Missing required documentation {$requiredDoc}.";
            }
        }

        if ($problems !== []) {
            foreach (array_values(array_unique($problems)) as $problem) {
                $this->error($problem);
            }
            $this->newLine();
            $this->error(count(array_unique($problems)).' Foundation issue(s) found.');
            return self::FAILURE;
        }

        $this->info('All Foundation checks passed.');
        return self::SUCCESS;
    }
}
