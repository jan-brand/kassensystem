<?php

namespace App\Foundation\Support;

use Illuminate\Support\Str;

final class Names
{
    public static function studly(string $value): string
    {
        return Str::studly($value);
    }

    public static function kebab(string $value): string
    {
        return Str::kebab($value);
    }

    public static function snake(string $value): string
    {
        return Str::snake($value);
    }

    public static function dot(string $value): string
    {
        return str_replace(['/', '\\'], '.', Str::kebab($value));
    }
}
