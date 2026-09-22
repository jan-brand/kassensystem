<?php

namespace App\Providers;

use App\Foundation\Console\AppCheckCommand;
use App\Foundation\Console\AppConfigureCommand;
use App\Foundation\Console\AppDoctorCommand;
use App\Foundation\Console\AppInfoCommand;
use App\Foundation\Console\AppInitCommand;
use App\Foundation\Console\AppStatusCommand;
use App\Foundation\Console\ArchitectureCheckCommand;
use App\Foundation\Console\ArchitectureCyclesCommand;
use App\Foundation\Console\ArchitectureDependenciesCommand;
use App\Foundation\Console\ArchitectureGraphCommand;
use App\Foundation\Console\DesignCheckCommand;
use App\Foundation\Console\DesignGraphCommand;
use App\Foundation\Console\DesignListCommand;
use App\Foundation\Console\DesignMakeCommand;
use App\Foundation\Console\DesignRemoveCommand;
use App\Foundation\Console\DesignRenameCommand;
use App\Foundation\Console\DesignShowCommand;
use App\Foundation\Console\DesignTokenCheckCommand;
use App\Foundation\Console\DesignTokenListCommand;
use App\Foundation\Console\DesignTokenMakeCommand;
use App\Foundation\Console\DesignTokenRemoveCommand;
use App\Foundation\Console\DesignTokenRenameCommand;
use App\Foundation\Console\DesignTokenShowCommand;
use App\Foundation\Console\DesignTokenSyncCommand;
use App\Foundation\Console\DesignUnusedCommand;
use App\Foundation\Console\DesignUsesCommand;
use App\Foundation\Console\DocsBuildCommand;
use App\Foundation\Console\DocsCheckCommand;
use App\Foundation\Console\DocsIndexCommand;
use App\Foundation\Console\DocsUnusedCommand;
use App\Foundation\Console\EnvBackupCommand;
use App\Foundation\Console\EnvCheckCommand;
use App\Foundation\Console\EnvDiffCommand;
use App\Foundation\Console\EnvRestoreCommand;
use App\Foundation\Console\EnvSyncCommand;
use App\Foundation\Console\GeneratedCheckCommand;
use App\Foundation\Console\GeneratedClearCommand;
use App\Foundation\Console\GeneratedRebuildCommand;
use App\Foundation\Console\ManifestBuildCommand;
use App\Foundation\Console\ManifestCheckCommand;
use App\Foundation\Console\ManifestDiffCommand;
use App\Foundation\Console\ModuleCheckCommand;
use App\Foundation\Console\ModuleGraphCommand;
use App\Foundation\Console\ModuleListCommand;
use App\Foundation\Console\ModuleMakeActionCommand;
use App\Foundation\Console\ModuleMakeArtifactCommand;
use App\Foundation\Console\ModuleMakeCommand;
use App\Foundation\Console\ModuleMakeCommandCommand;
use App\Foundation\Console\ModuleMakeContractCommand;
use App\Foundation\Console\ModuleMakeControllerCommand;
use App\Foundation\Console\ModuleMakeDtoCommand;
use App\Foundation\Console\ModuleMakeEnumCommand;
use App\Foundation\Console\ModuleMakeEventCommand;
use App\Foundation\Console\ModuleMakeExceptionCommand;
use App\Foundation\Console\ModuleMakeFactoryCommand;
use App\Foundation\Console\ModuleMakeJobCommand;
use App\Foundation\Console\ModuleMakeListenerCommand;
use App\Foundation\Console\ModuleMakeMigrationCommand;
use App\Foundation\Console\ModuleMakeModelCommand;
use App\Foundation\Console\ModuleMakePolicyCommand;
use App\Foundation\Console\ModuleMakeQueryCommand;
use App\Foundation\Console\ModuleMakeRequestCommand;
use App\Foundation\Console\ModuleMakeSeederCommand;
use App\Foundation\Console\ModuleMakeServiceCommand;
use App\Foundation\Console\ModuleRemoveCommand;
use App\Foundation\Console\ModuleRenameCommand;
use App\Foundation\Console\ModuleShowCommand;
use App\Foundation\Console\NavigationAddCommand;
use App\Foundation\Console\NavigationCheckCommand;
use App\Foundation\Console\NavigationListCommand;
use App\Foundation\Console\NavigationRemoveCommand;
use App\Foundation\Console\PageCheckCommand;
use App\Foundation\Console\PageListCommand;
use App\Foundation\Console\PageMakeCommand;
use App\Foundation\Console\PageMoveCommand;
use App\Foundation\Console\PageRemoveCommand;
use App\Foundation\Console\PageRenameCommand;
use App\Foundation\Console\PageShowCommand;
use App\Foundation\Console\PermissionCheckCommand;
use App\Foundation\Console\PermissionListCommand;
use App\Foundation\Console\PermissionMakeCommand;
use App\Foundation\Console\PermissionRemoveCommand;
use App\Foundation\Console\PermissionSyncCommand;
use App\Foundation\Console\QualityCheckCommand;
use App\Foundation\Console\QualityFixCommand;
use App\Foundation\Console\RouteCheckCommand;
use App\Foundation\Console\RouteManifestCommand;
use App\Foundation\Console\RouteUnusedCommand;
use App\Foundation\Console\SurfaceCheckCommand;
use App\Foundation\Console\SurfaceListCommand;
use App\Foundation\Console\SurfaceMakeCommand;
use App\Foundation\Console\SurfaceRemoveCommand;
use App\Foundation\Console\SurfaceRenameCommand;
use App\Foundation\Console\SurfaceShowCommand;
use App\Foundation\Console\ToolingHistoryCommand;
use App\Foundation\Console\ToolingUndoCommand;
use App\Foundation\Registry\ProjectRegistry;
use App\Foundation\Support\JsonFile;
use Illuminate\Console\Command;
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
        $settings = JsonFile::read(base_path('foundation.json'));
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
        if (! config('foundation.dashboard.enabled')) {
            return;
        }
        if (config('foundation.dashboard.local_only') && ! $this->app->environment('local')) {
            return;
        }

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

    /** @return list<class-string<Command>> */
    private function commandClasses(): array
    {
        return [
            AppInitCommand::class,
            AppConfigureCommand::class,
            AppDoctorCommand::class,
            AppCheckCommand::class,
            AppInfoCommand::class,
            AppStatusCommand::class,
            EnvSyncCommand::class,
            EnvCheckCommand::class,
            EnvDiffCommand::class,
            EnvBackupCommand::class,
            EnvRestoreCommand::class,
            ModuleMakeCommand::class,
            ModuleListCommand::class,
            ModuleShowCommand::class,
            ModuleCheckCommand::class,
            ModuleGraphCommand::class,
            ModuleRenameCommand::class,
            ModuleRemoveCommand::class,
            ModuleMakeArtifactCommand::class,
            ModuleMakeModelCommand::class,
            ModuleMakeActionCommand::class,
            ModuleMakeQueryCommand::class,
            ModuleMakeServiceCommand::class,
            ModuleMakeEventCommand::class,
            ModuleMakeListenerCommand::class,
            ModuleMakeJobCommand::class,
            ModuleMakePolicyCommand::class,
            ModuleMakeRequestCommand::class,
            ModuleMakeExceptionCommand::class,
            ModuleMakeEnumCommand::class,
            ModuleMakeContractCommand::class,
            ModuleMakeDtoCommand::class,
            ModuleMakeMigrationCommand::class,
            ModuleMakeFactoryCommand::class,
            ModuleMakeSeederCommand::class,
            ModuleMakeCommandCommand::class,
            ModuleMakeControllerCommand::class,
            DesignMakeCommand::class,
            DesignListCommand::class,
            DesignShowCommand::class,
            DesignCheckCommand::class,
            DesignUsesCommand::class,
            DesignUnusedCommand::class,
            DesignRenameCommand::class,
            DesignRemoveCommand::class,
            DesignTokenMakeCommand::class,
            DesignTokenListCommand::class,
            SurfaceMakeCommand::class,
            SurfaceListCommand::class,
            SurfaceShowCommand::class,
            SurfaceCheckCommand::class,
            SurfaceRenameCommand::class,
            SurfaceRemoveCommand::class,
            PageMakeCommand::class,
            PageListCommand::class,
            PageShowCommand::class,
            PageCheckCommand::class,
            PageRenameCommand::class,
            PageRemoveCommand::class,
            NavigationAddCommand::class,
            NavigationListCommand::class,
            NavigationCheckCommand::class,
            NavigationRemoveCommand::class,
            PermissionMakeCommand::class,
            PermissionListCommand::class,
            PermissionCheckCommand::class,
            PermissionRemoveCommand::class,
            ArchitectureCheckCommand::class,
            ArchitectureGraphCommand::class,
            ArchitectureCyclesCommand::class,
            DocsBuildCommand::class,
            DocsCheckCommand::class,
            QualityCheckCommand::class,
            ToolingHistoryCommand::class,
            ToolingUndoCommand::class,
            DesignGraphCommand::class,
            DesignTokenShowCommand::class,
            DesignTokenCheckCommand::class,
            DesignTokenSyncCommand::class,
            DesignTokenRenameCommand::class,
            DesignTokenRemoveCommand::class,
            PageMoveCommand::class,
            PermissionSyncCommand::class,
            RouteCheckCommand::class,
            RouteManifestCommand::class,
            RouteUnusedCommand::class,
            ArchitectureDependenciesCommand::class,
            ManifestBuildCommand::class,
            ManifestCheckCommand::class,
            ManifestDiffCommand::class,
            GeneratedClearCommand::class,
            GeneratedRebuildCommand::class,
            GeneratedCheckCommand::class,
            DocsIndexCommand::class,
            DocsUnusedCommand::class,
            QualityFixCommand::class,
        ];
    }
}
