<?php

namespace App\Foundation\Registry;

use App\Foundation\Support\JsonFile;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class ProjectRegistry
{
    public function modules(): array { return $this->manifests(config('foundation.paths.modules'), 'module.json'); }
    public function pages(): array { return $this->manifests(config('foundation.paths.pages'), 'page.json'); }
    public function surfaces(): array { return $this->manifests(config('foundation.paths.surfaces'), 'surface.json'); }

    public function design(?string $type = null): array
    {
        $types = $type ? [$type] : ['components', 'patterns', 'templates'];
        $all = [];
        foreach ($types as $dir) {
            $all = array_merge($all, $this->manifests(config('foundation.paths.design').'/'.$dir, rtrim($dir, 's').'.json'));
        }
        return $all;
    }

    public function permissions(): array
    {
        return JsonFile::read(config('foundation.paths.permissions'), ['permissions' => []])['permissions'] ?? [];
    }

    public function navigation(): array
    {
        $dir = config('foundation.paths.navigation');
        $out = [];
        foreach (glob($dir.'/*.json') ?: [] as $file) {
            $out[basename($file, '.json')] = JsonFile::read($file, ['items' => []])['items'] ?? [];
        }
        return $out;
    }

    private function manifests(string $base, string $filename): array
    {
        if (! is_dir($base)) { return []; }
        $out = [];
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if ($file->isFile() && $file->getFilename() === $filename) {
                $data = JsonFile::read($file->getPathname());
                $data['_file'] = $file->getPathname();
                $out[] = $data;
            }
        }
        usort($out, fn ($a, $b) => strcmp((string)($a['name'] ?? ''), (string)($b['name'] ?? '')));
        return $out;
    }
}
