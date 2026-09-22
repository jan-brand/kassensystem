<?php

namespace App\Foundation\Generation;

use RuntimeException;
use Throwable;

/**
 * @phpstan-type WriteOperation array{
 *     type: 'write',
 *     path: string,
 *     content: string,
 *     allow_overwrite: bool
 * }
 * @phpstan-type DeleteOperation array{
 *     type: 'delete',
 *     path: string
 * }
 * @phpstan-type FileOperation WriteOperation|DeleteOperation
 * @phpstan-type HistoryOperation array{
 *     path: string,
 *     before: string|null,
 *     after: string|null
 * }
 */
final class FilePlan
{
    /** @var list<FileOperation> */
    private array $operations = [];

    public function __construct(
        private readonly HistoryRepository $history,
    ) {}

    public function write(
        string $relativePath,
        string $content,
        bool $allowOverwrite = false,
    ): self {
        $this->operations[] = [
            'type' => 'write',
            'path' => $this->safe($relativePath),
            'content' => $content,
            'allow_overwrite' => $allowOverwrite,
        ];

        return $this;
    }

    public function delete(string $relativePath): self
    {
        $this->operations[] = [
            'type' => 'delete',
            'path' => $this->safe($relativePath),
        ];

        return $this;
    }

    /** @return list<FileOperation> */
    public function operations(): array
    {
        return $this->operations;
    }

    /** @return list<string> */
    public function descriptions(): array
    {
        return array_map(
            static fn (array $operation): string => strtoupper($operation['type']).' '.$operation['path'],
            $this->operations,
        );
    }

    public function apply(
        string $label,
        bool $force = false,
        bool $recordHistory = true,
    ): ?string {
        $this->preflight($force);

        /** @var list<HistoryOperation> $historyOperations */
        $historyOperations = [];

        foreach ($this->operations as $operation) {
            $absolute = base_path($operation['path']);
            $exists = is_file($absolute);

            $historyOperations[] = [
                'path' => $operation['path'],
                'before' => $exists
                    ? base64_encode((string) file_get_contents($absolute))
                    : null,
                'after' => $operation['type'] === 'write'
                    ? base64_encode($operation['content'])
                    : null,
            ];
        }

        /** @var list<int> $applied */
        $applied = [];

        try {
            foreach ($this->operations as $index => $operation) {
                $absolute = base_path($operation['path']);

                if ($operation['type'] === 'write') {
                    $directory = dirname($absolute);

                    if (
                        ! is_dir($directory)
                        && ! mkdir($directory, 0775, true)
                        && ! is_dir($directory)
                    ) {
                        throw new RuntimeException("Unable to create directory {$directory}");
                    }

                    if (file_put_contents($absolute, $operation['content']) === false) {
                        throw new RuntimeException("Unable to write {$operation['path']}");
                    }
                } elseif (is_file($absolute) && ! unlink($absolute)) {
                    throw new RuntimeException("Unable to delete {$operation['path']}");
                }

                $applied[] = $index;
            }
        } catch (Throwable $exception) {
            foreach (array_reverse($applied) as $index) {
                $state = $historyOperations[$index];
                $absolute = base_path($state['path']);

                if ($state['before'] === null) {
                    if (is_file($absolute)) {
                        @unlink($absolute);
                    }

                    continue;
                }

                $directory = dirname($absolute);

                if (! is_dir($directory)) {
                    @mkdir($directory, 0775, true);
                }

                @file_put_contents(
                    $absolute,
                    base64_decode($state['before'], true) ?: '',
                );
            }

            throw $exception;
        }

        return $recordHistory && $historyOperations !== []
            ? $this->history->record($label, $historyOperations)
            : null;
    }

    private function preflight(bool $force): void
    {
        foreach ($this->operations as $operation) {
            if ($operation['type'] !== 'write') {
                continue;
            }

            $absolute = base_path($operation['path']);

            if (
                is_file($absolute)
                && ! $force
                && ! $operation['allow_overwrite']
            ) {
                throw new RuntimeException(
                    "Refusing to overwrite {$operation['path']}; use --force.",
                );
            }
        }
    }

    private function safe(string $path): string
    {
        $path = str_replace('\\', '/', ltrim($path, '/'));

        if (
            $path === ''
            || str_contains($path, '../')
            || $path === '..'
        ) {
            throw new RuntimeException('Unsafe project-relative path.');
        }

        return $path;
    }
}
