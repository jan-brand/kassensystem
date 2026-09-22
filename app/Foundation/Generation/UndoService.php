<?php

namespace App\Foundation\Generation;

use RuntimeException;

final class UndoService
{
    public function __construct(
        private readonly HistoryRepository $history,
    ) {}

    /**
     * @return array{
     *     id: string,
     *     created_at: string,
     *     label: string,
     *     operations: list<array{path: string, before: string|null, after: string|null}>
     * }|null
     */
    public function undoLatest(): ?array
    {
        $entry = $this->history->latest();

        if ($entry === null) {
            return null;
        }

        foreach (array_reverse($entry['operations']) as $operation) {
            $absolute = base_path($operation['path']);
            $before = $operation['before'];

            if ($before === null) {
                if (is_file($absolute) && ! unlink($absolute)) {
                    throw new RuntimeException("Unable to remove {$absolute}");
                }

                continue;
            }

            $directory = dirname($absolute);

            if (
                ! is_dir($directory)
                && ! mkdir($directory, 0775, true)
                && ! is_dir($directory)
            ) {
                throw new RuntimeException("Unable to create directory {$directory}");
            }

            if (
                file_put_contents(
                    $absolute,
                    base64_decode($before, true) ?: '',
                ) === false
            ) {
                throw new RuntimeException("Unable to restore {$absolute}");
            }
        }

        $this->history->remove($entry['id']);

        return $entry;
    }
}
