<?php

namespace App\Foundation\Generation;

use RuntimeException;
use Throwable;

final class FilePlan
{
    private array $operations = [];

    public function __construct(private readonly HistoryRepository $history) {}

    public function write(string $relativePath, string $content, bool $allowOverwrite = false): self
    {
        $this->operations[] = ['type' => 'write', 'path' => $this->safe($relativePath), 'content' => $content, 'allow_overwrite' => $allowOverwrite];
        return $this;
    }

    public function delete(string $relativePath): self
    {
        $this->operations[] = ['type' => 'delete', 'path' => $this->safe($relativePath)];
        return $this;
    }

    public function operations(): array { return $this->operations; }

    public function descriptions(): array
    {
        return array_map(fn (array $op) => strtoupper($op['type']).' '.$op['path'], $this->operations);
    }

    public function apply(string $label, bool $force = false, bool $recordHistory = true): ?string
    {
        $this->preflight($force);

        $historyOps = [];
        foreach ($this->operations as $op) {
            $absolute = base_path($op['path']);
            $exists = is_file($absolute);
            $historyOps[] = [
                'path' => $op['path'],
                'before' => $exists ? base64_encode((string) file_get_contents($absolute)) : null,
                'after' => $op['type'] === 'write' ? base64_encode($op['content']) : null,
            ];
        }

        $applied = [];
        try {
            foreach ($this->operations as $index => $op) {
                $absolute = base_path($op['path']);
                if ($op['type'] === 'write') {
                    $dir = dirname($absolute);
                    if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
                        throw new RuntimeException("Unable to create directory {$dir}");
                    }
                    if (file_put_contents($absolute, $op['content']) === false) {
                        throw new RuntimeException("Unable to write {$op['path']}");
                    }
                } else {
                    if (is_file($absolute) && ! unlink($absolute)) {
                        throw new RuntimeException("Unable to delete {$op['path']}");
                    }
                }
                $applied[] = $index;
            }
        } catch (Throwable $exception) {
            foreach (array_reverse($applied) as $index) {
                $state = $historyOps[$index];
                $absolute = base_path($state['path']);
                if ($state['before'] === null) {
                    if (is_file($absolute)) { @unlink($absolute); }
                } else {
                    $dir = dirname($absolute);
                    if (! is_dir($dir)) { @mkdir($dir, 0775, true); }
                    @file_put_contents($absolute, base64_decode($state['before'], true) ?: '');
                }
            }
            throw $exception;
        }

        return $recordHistory && $historyOps !== [] ? $this->history->record($label, $historyOps) : null;
    }

    private function preflight(bool $force): void
    {
        foreach ($this->operations as $op) {
            $absolute = base_path($op['path']);
            if ($op['type'] === 'write' && is_file($absolute) && ! $force && ! ($op['allow_overwrite'] ?? false)) {
                throw new RuntimeException("Refusing to overwrite {$op['path']}; use --force.");
            }
        }
    }

    private function safe(string $path): string
    {
        $path = str_replace('\\', '/', ltrim($path, '/'));
        if ($path === '' || str_contains($path, '../') || $path === '..') {
            throw new RuntimeException('Unsafe project-relative path.');
        }
        return $path;
    }
}
