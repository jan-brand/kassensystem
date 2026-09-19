<?php

namespace App\Foundation\Generation;

final class UndoService
{
    public function __construct(private readonly HistoryRepository $history) {}

    public function undoLatest(): ?array
    {
        $entry = $this->history->latest();
        if (! $entry) { return null; }

        foreach (array_reverse($entry['operations'] ?? []) as $op) {
            $absolute = base_path($op['path']);
            $before = $op['before'] ?? null;
            if ($before === null) {
                if (is_file($absolute)) { unlink($absolute); }
                continue;
            }
            $dir = dirname($absolute);
            if (! is_dir($dir)) { mkdir($dir, 0775, true); }
            file_put_contents($absolute, base64_decode($before, true) ?: '');
        }
        $this->history->remove($entry['id']);
        return $entry;
    }
}
