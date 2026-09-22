<?php

namespace App\Foundation\Generation;

use App\Foundation\Support\JsonFile;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * @phpstan-type HistoryOperation array{
 *     path: string,
 *     before: string|null,
 *     after: string|null
 * }
 * @phpstan-type HistoryEntry array{
 *     id: string,
 *     created_at: string,
 *     label: string,
 *     operations: list<HistoryOperation>
 * }
 */
final class HistoryRepository
{
    public function directory(): string
    {
        return (string) config('foundation.paths.history');
    }

    /**
     * @param list<HistoryOperation> $operations
     */
    public function record(string $label, array $operations): string
    {
        $directory = $this->directory();

        if (
            ! is_dir($directory)
            && ! mkdir($directory, 0775, true)
            && ! is_dir($directory)
        ) {
            throw new RuntimeException("Unable to create history directory {$directory}");
        }

        $id = now()->format('Ymd-His').'-'.Str::lower(Str::random(6));
        $path = $directory.'/'.$id.'.json';

        $written = file_put_contents($path, JsonFile::encode([
            'id' => $id,
            'created_at' => now()->toIso8601String(),
            'label' => $label,
            'operations' => $operations,
        ]));

        if ($written === false) {
            throw new RuntimeException("Unable to write history entry {$path}");
        }

        return $id;
    }

    /** @return list<HistoryEntry> */
    public function all(): array
    {
        $files = glob($this->directory().'/*.json') ?: [];
        rsort($files);

        $entries = [];

        foreach ($files as $file) {
            $entries[] = $this->entry(JsonFile::read($file), $file);
        }

        return $entries;
    }

    /** @return HistoryEntry|null */
    public function latest(): ?array
    {
        return $this->all()[0] ?? null;
    }

    public function remove(string $id): void
    {
        $path = $this->directory().'/'.$id.'.json';

        if (is_file($path) && ! unlink($path)) {
            throw new RuntimeException("Unable to remove history entry {$path}");
        }
    }

    /**
     * @param array<array-key, mixed> $data
     * @return HistoryEntry
     */
    private function entry(array $data, string $path): array
    {
        $id = $data['id'] ?? null;
        $createdAt = $data['created_at'] ?? null;
        $label = $data['label'] ?? null;
        $operations = $data['operations'] ?? null;

        if (
            ! is_string($id)
            || ! is_string($createdAt)
            || ! is_string($label)
            || ! is_array($operations)
        ) {
            throw new RuntimeException("Invalid history entry: {$path}");
        }

        $normalizedOperations = [];

        foreach ($operations as $operation) {
            if (! is_array($operation)) {
                throw new RuntimeException("Invalid history operation: {$path}");
            }

            $operationPath = $operation['path'] ?? null;
            $before = $operation['before'] ?? null;
            $after = $operation['after'] ?? null;

            if (
                ! is_string($operationPath)
                || (! is_string($before) && $before !== null)
                || (! is_string($after) && $after !== null)
            ) {
                throw new RuntimeException("Invalid history operation: {$path}");
            }

            $normalizedOperations[] = [
                'path' => $operationPath,
                'before' => $before,
                'after' => $after,
            ];
        }

        return [
            'id' => $id,
            'created_at' => $createdAt,
            'label' => $label,
            'operations' => $normalizedOperations,
        ];
    }
}
