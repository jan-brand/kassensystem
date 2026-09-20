<?php

namespace App\Providers;

use App\Foundation\Registry\ProjectRegistry;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class FoundationServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(ProjectRegistry $registry): void
    {
        $this->applyProjectSettings();

        if ($this->app->runningInConsole()) {
            $this->commands($this->commandClasses());
        }

        $this->registerModuleInfrastructure();
        $this->registerManifestPages($registry);
        $this->registerDashboard($registry);
    }



    private function applyProjectSettings(): void
    {
        $settings = \App\Foundation\Support\JsonFile::read(base_path('foundation.json'));
        if (isset($settings['project']['default_surface'])) {
            config(['foundation.default_surface' => $settings['project']['default_surface']]);
        }
        if (isset($settings['environment']['targets']) && is_array($settings['environment']['targets'])) {
            config(['foundation.environment.targets' => $settings['environment']['targets']]);
        }
        if (isset($settings['dashboard']['enabled'])) {
            config(['foundation.dashboard.enabled' => (bool) $settings['dashboard']['enabled']]);
        }
        if (isset($settings['dashboard']['local_only'])) {
            config(['foundation.dashboard.local_only' => (bool) $settings['dashboard']['local_only']]);
        }
    }

    private function registerModuleInfrastructure(): void
    {
        foreach (glob(app_path('Modules/*'), GLOB_ONLYDIR) ?: [] as $modulePath) {
            $name = basename($modulePath);
            $migrations = $modulePath.'/database/migrations';
            if (is_dir($migrations)) {
                $this->loadMigrationsFrom($migrations);
            }

            $views = $modulePath.'/resources/views';
            if (is_dir($views)) {
                $this->loadViewsFrom($views, strtolower($name));
            }

            if ($this->app->runningInConsole()) {
                foreach (glob($modulePath.'/Console/Commands/*.php') ?: [] as $commandFile) {
                    $class = 'App\\Modules\\'.$name.'\\Console\\Commands\\'.pathinfo($commandFile, PATHINFO_FILENAME);
                    if (class_exists($class)) {
                        $this->commands([$class]);
                    }
                }
            }

            $routes = $modulePath.'/routes/web.php';
            if (is_file($routes)) {
                require $routes;
            }
        }
    }

    private function registerDashboard(ProjectRegistry $registry): void
    {
        if (! config('foundation.dashboard.enabled')) { return; }
        if (config('foundation.dashboard.local_only') && ! $this->app->environment('local')) { return; }

        Route::get('/'.trim(config('foundation.dashboard.path'), '/'), function () use ($registry) {
            return view('foundation.dashboard', [
                'modules' => $registry->modules(),
                'surfaces' => $registry->surfaces(),
                'pages' => $registry->pages(),
                'design' => $registry->design(),
                'permissions' => $registry->permissions(),
            ]);
        })->name('foundation.dashboard');
    }

    private function registerManifestPages(ProjectRegistry $registry): void
    {
        $surfaces = [];
        foreach ($registry->surfaces() as $surface) {
            $surfaces[$surface['name']] = $surface;
        }

        foreach ($registry->pages() as $page) {
            $surface = $surfaces[$page['surface'] ?? ''] ?? [];
            $middleware = array_values(array_unique(array_merge(
                $surface['middleware'] ?? ['web'],
                $page['middleware'] ?? [],
            )));
            $route = Route::middleware($middleware);

            if ($domain = ($surface['domain'] ?? null)) {
                $route->domain($domain);
            }
            if ($prefix = trim((string) ($surface['prefix'] ?? ''), '/')) {
                $route->prefix($prefix);
            }

            $route->group(function () use ($page): void {
                $type = $page['handler']['type'] ?? 'view';
                $target = $page['handler']['target'] ?? '';
                $uri = $page['uri'] ?? '/';
                $name = $page['route_name'] ?? $page['name'];

                if ($type === 'view') {
                    Route::view($uri, $target)->name($name);

                    return;
                }

                if ($type === 'livewire') {
                    Route::get($uri, $target)->name($name);
                }
            });
        }
    }

    private function commandClasses(): array
    {
        return [
            \App\Foundation\Console\AppInitCommand::class,
            \App\Foundation\Console\AppConfigureCommand::class,
            \App\Foundation\Console\AppDoctorCommand::class,
            \App\Foundation\Console\AppCheckCommand::class,
            \App\Foundation\Console\AppInfoCommand::class,
            \App\Foundation\Console\AppStatusCommand::class,
            \App\Foundation\Console\EnvSyncCommand::class,
            \App\Foundation\Console\EnvCheckCommand::class,
            \App\Foundation\Console\EnvDiffCommand::class,
            \App\Foundation\Console\EnvBackupCommand::class,
            \App\Foundation\Console\EnvRestoreCommand::class,
            \App\Foundation\Console\ModuleMakeCommand::class,
            \App\Foundation\Console\ModuleListCommand::class,
            \App\Foundation\Console\ModuleShowCommand::class,
            \App\Foundation\Console\ModuleCheckCommand::class,
            \App\Foundation\Console\ModuleGraphCommand::class,
            \App\Foundation\Console\ModuleRenameCommand::class,
            \App\Foundation\Console\ModuleRemoveCommand::class,
            \App\Foundation\Console\ModuleMakeArtifactCommand::class,
            \App\Foundation\Console\ModuleMakeModelCommand::class,
            \App\Foundation\Console\ModuleMakeActionCommand::class,
            \App\Foundation\Console\ModuleMakeQueryCommand::class,
            \App\Foundation\Console\ModuleMakeServiceCommand::class,
            \App\Foundation\Console\ModuleMakeEventCommand::class,
            \App\Foundation\Console\ModuleMakeListenerCommand::class,
            \App\Foundation\Console\ModuleMakeJobCommand::class,
            \App\Foundation\Console\ModuleMakePolicyCommand::class,
            \App\Foundation\Console\ModuleMakeRequestCommand::class,
            \App\Foundation\Console\ModuleMakeExceptionCommand::class,
            \App\Foundation\Console\ModuleMakeEnumCommand::class,
            \App\Foundation\Console\ModuleMakeContractCommand::class,
            \App\Foundation\Console\ModuleMakeDtoCommand::class,
            \App\Foundation\Console\ModuleMakeMigrationCommand::class,
            \App\Foundation\Console\ModuleMakeFactoryCommand::class,
            \App\Foundation\Console\ModuleMakeSeederCommand::class,
            \App\Foundation\Console\ModuleMakeCommandCommand::class,
            \App\Foundation\Console\ModuleMakeControllerCommand::class,
            \App\Foundation\Console\DesignMakeCommand::class,
            \App\Foundation\Console\DesignListCommand::class,
            \App\Foundation\Console\DesignShowCommand::class,
            \App\Foundation\Console\DesignCheckCommand::class,
            \App\Foundation\Console\DesignUsesCommand::class,
            \App\Foundation\Console\DesignUnusedCommand::class,
            \App\Foundation\Console\DesignRenameCommand::class,
            \App\Foundation\Console\DesignRemoveCommand::class,
            \App\Foundation\Console\DesignTokenMakeCommand::class,
            \App\Foundation\Console\DesignTokenListCommand::class,
            \App\Foundation\Console\SurfaceMakeCommand::class,
            \App\Foundation\Console\SurfaceListCommand::class,
            \App\Foundation\Console\SurfaceShowCommand::class,
            \App\Foundation\Console\SurfaceCheckCommand::class,
            \App\Foundation\Console\SurfaceRenameCommand::class,
            \App\Foundation\Console\SurfaceRemoveCommand::class,
            \App\Foundation\Console\PageMakeCommand::class,
            \App\Foundation\Console\PageListCommand::class,
            \App\Foundation\Console\PageShowCommand::class,
            \App\Foundation\Console\PageCheckCommand::class,
            \App\Foundation\Console\PageRenameCommand::class,
            \App\Foundation\Console\PageRemoveCommand::class,
            \App\Foundation\Console\NavigationAddCommand::class,
            \App\Foundation\Console\NavigationListCommand::class,
            \App\Foundation\Console\NavigationCheckCommand::class,
            \App\Foundation\Console\NavigationRemoveCommand::class,
            \App\Foundation\Console\PermissionMakeCommand::class,
            \App\Foundation\Console\PermissionListCommand::class,
            \App\Foundation\Console\PermissionCheckCommand::class,
            \App\Foundation\Console\PermissionRemoveCommand::class,
            \App\Foundation\Console\ArchitectureCheckCommand::class,
            \App\Foundation\Console\ArchitectureGraphCommand::class,
            \App\Foundation\Console\ArchitectureCyclesCommand::class,
            \App\Foundation\Console\DocsBuildCommand::class,
            \App\Foundation\Console\DocsCheckCommand::class,
            \App\Foundation\Console\QualityCheckCommand::class,
            \App\Foundation\Console\ToolingHistoryCommand::class,
            \App\Foundation\Console\ToolingUndoCommand::class,
            \App\Foundation\Console\DesignGraphCommand::class,
            \App\Foundation\Console\DesignTokenShowCommand::class,
            \App\Foundation\Console\DesignTokenCheckCommand::class,
            \App\Foundation\Console\DesignTokenSyncCommand::class,
            \App\Foundation\Console\DesignTokenRenameCommand::class,
            \App\Foundation\Console\DesignTokenRemoveCommand::class,
            \App\Foundation\Console\PageMoveCommand::class,
            \App\Foundation\Console\PermissionSyncCommand::class,
            \App\Foundation\Console\RouteCheckCommand::class,
            \App\Foundation\Console\RouteManifestCommand::class,
            \App\Foundation\Console\RouteUnusedCommand::class,
            \App\Foundation\Console\ArchitectureDependenciesCommand::class,
            \App\Foundation\Console\ManifestBuildCommand::class,
            \App\Foundation\Console\ManifestCheckCommand::class,
            \App\Foundation\Console\ManifestDiffCommand::class,
            \App\Foundation\Console\GeneratedClearCommand::class,
            \App\Foundation\Console\GeneratedRebuildCommand::class,
            \App\Foundation\Console\GeneratedCheckCommand::class,
            \App\Foundation\Console\DocsIndexCommand::class,
            \App\Foundation\Console\DocsUnusedCommand::class,
            \App\Foundation\Console\QualityFixCommand::class,
        ];
    }
}
