<?php

namespace App\Foundation\Support;

use RuntimeException;

final class JsonFile
{
    public static function read(string $path, array $default = []): array
    {
        if (! is_file($path)) {
            return $default;
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        if (! is_array($decoded)) {
            throw new RuntimeException("Invalid JSON: {$path}");
        }

        return $decoded;
    }

    public static function encode(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL;
    }
}
