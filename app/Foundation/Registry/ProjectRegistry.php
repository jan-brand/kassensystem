<?php

namespace App\Foundation\Registry;

use App\Foundation\Support\JsonFile;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * @phpstan-type Manifest array<array-key, mixed>
 * @phpstan-type NavigationItem array<array-key, mixed>
 */
final class ProjectRegistry
{
    /** @return list<Manifest> */
    public function modules(): array
    {
        return $this->manifests(
            (string) config('foundation.paths.modules'),
            'module.json',
        );
    }

    /** @return list<Manifest> */
    public function pages(): array
    {
        return $this->manifests(
            (string) config('foundation.paths.pages'),
            'page.json',
        );
    }

    /** @return list<Manifest> */
    public function surfaces(): array
    {
        return $this->manifests(
            (string) config('foundation.paths.surfaces'),
            'surface.json',
        );
    }

    /** @return list<Manifest> */
    public function design(?string $type = null): array
    {
        $types = $type !== null
            ? [$type]
            : ['components', 'patterns', 'templates'];

        $base = (string) config('foundation.paths.design');
        $all = [];

        foreach ($types as $directory) {
            $all = array_merge(
                $all,
                $this->manifests(
                    $base.'/'.$directory,
                    rtrim($directory, 's').'.json',
                ),
            );
        }

        return $all;
    }

    /** @return list<string> */
    public function permissions(): array
    {
        $data = JsonFile::read(
            (string) config('foundation.paths.permissions'),
            ['permissions' => []],
        );

        $permissions = $data['permissions'] ?? [];

        if (! is_array($permissions)) {
            return [];
        }

        return array_values(array_filter(
            $permissions,
            static fn (mixed $permission): bool => is_string($permission),
        ));
    }

    /** @return array<string, list<NavigationItem>> */
    public function navigation(): array
    {
        $directory = (string) config('foundation.paths.navigation');
        $navigation = [];

        foreach (glob($directory.'/*.json') ?: [] as $file) {
            $data = JsonFile::read($file, ['items' => []]);
            $items = $data['items'] ?? [];

            if (! is_array($items)) {
                $items = [];
            }

            $navigation[basename($file, '.json')] = array_values(array_filter(
                $items,
                static fn (mixed $item): bool => is_array($item),
            ));
        }

        return $navigation;
    }

    /** @return list<Manifest> */
    private function manifests(string $base, string $filename): array
    {
        if (! is_dir($base)) {
            return [];
        }

        $manifests = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $base,
                RecursiveDirectoryIterator::SKIP_DOTS,
            ),
        );

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getFilename() !== $filename) {
                continue;
            }

            $data = JsonFile::read($file->getPathname());
            $data['_file'] = $file->getPathname();
            $manifests[] = $data;
        }

        usort(
            $manifests,
            static fn (array $left, array $right): int => strcmp(
                (string) ($left['name'] ?? ''),
                (string) ($right['name'] ?? ''),
            ),
        );

        return $manifests;
    }
}
