<?php

namespace App\Foundation\Generation;

use App\Foundation\Support\JsonFile;
use Illuminate\Support\Str;

final class HistoryRepository
{
    public function directory(): string { return config('foundation.paths.history'); }

    public function record(string $label, array $operations): string
    {
        $dir = $this->directory();
        if (! is_dir($dir)) { mkdir($dir, 0775, true); }
        $id = now()->format('Ymd-His').'-'.Str::lower(Str::random(6));
        $path = $dir.'/'.$id.'.json';
        file_put_contents($path, JsonFile::encode([
            'id' => $id,
            'created_at' => now()->toIso8601String(),
            'label' => $label,
            'operations' => $operations,
        ]));
        return $id;
    }

    public function all(): array
    {
        $files = glob($this->directory().'/*.json') ?: [];
        rsort($files);
        return array_map(fn (string $file) => JsonFile::read($file), $files);
    }

    public function latest(): ?array { return $this->all()[0] ?? null; }

    public function remove(string $id): void
    {
        $path = $this->directory().'/'.$id.'.json';
        if (is_file($path)) { unlink($path); }
    }
}
