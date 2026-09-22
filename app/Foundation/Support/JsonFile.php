<?php

namespace App\Foundation\Support;

use JsonException;
use RuntimeException;

final class JsonFile
{
    /**
     * @param  array<array-key, mixed>  $default
     * @return array<array-key, mixed>
     */
    public static function read(string $path, array $default = []): array
    {
        if (! is_file($path)) {
            return $default;
        }

        try {
            $decoded = json_decode(
                (string) file_get_contents($path),
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
        } catch (JsonException $exception) {
            throw new RuntimeException(
                "Invalid JSON: {$path}",
                previous: $exception,
            );
        }

        if (! is_array($decoded)) {
            throw new RuntimeException("Invalid JSON object: {$path}");
        }

        /** @var array<array-key, mixed> $decoded */
        return $decoded;
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function encode(array $data): string
    {
        try {
            return json_encode(
                $data,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
            ).PHP_EOL;
        } catch (JsonException $exception) {
            throw new RuntimeException(
                'Unable to encode JSON data.',
                previous: $exception,
            );
        }
    }
}
