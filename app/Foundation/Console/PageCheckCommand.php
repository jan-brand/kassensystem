<?php

namespace App\Foundation\Console;

use App\Foundation\Registry\ProjectRegistry;
use Illuminate\Console\Command;
use Livewire\Component;

final class PageCheckCommand extends Command
{
    protected $signature = 'page:check';

    protected $description = 'Validate page manifests, surfaces and handler targets.';

    public function handle(ProjectRegistry $registry): int
    {
        $surfaces = array_column($registry->surfaces(), 'name');
        $templates = array_map(
            fn ($item) => $item['name'],
            array_filter($registry->design(), fn ($item) => ($item['type'] ?? '') === 'template'),
        );
        $bad = false;

        foreach ($registry->pages() as $page) {
            if (! in_array($page['surface'] ?? '', $surfaces, true)) {
                $this->error($page['name'].': missing surface');
                $bad = true;
            }

            $type = $page['handler']['type'] ?? 'view';
            $target = $page['handler']['target'] ?? '';

            if ($type === 'view') {
                $file = resource_path('views/'.str_replace('.', '/', $target).'.blade.php');
                if (! is_file($file)) {
                    $this->error($page['name'].': missing view '.$target);
                    $bad = true;
                }
            } elseif ($type === 'livewire') {
                if (! class_exists($target) || ! is_subclass_of($target, Component::class)) {
                    $this->error($page['name'].': invalid Livewire component '.$target);
                    $bad = true;
                }
            } else {
                $this->error($page['name'].': unsupported handler type '.$type);
                $bad = true;
            }

            if (($page['template'] ?? null) && ! in_array($page['template'], $templates, true)) {
                $this->error($page['name'].': missing design template '.$page['template']);
                $bad = true;
            }
        }

        if (! $bad) {
            $this->info('Pages valid.');
        }

        return $bad ? self::FAILURE : self::SUCCESS;
    }
}
