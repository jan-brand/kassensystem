<?php

declare(strict_types=1);

function updateFile(string $path, callable $mutator): void
{
    if (! is_file($path)) {
        fwrite(STDERR, "MISSING {$path}".PHP_EOL);
        exit(1);
    }

    $original = file_get_contents($path);

    if ($original === false) {
        fwrite(STDERR, "READ ERROR {$path}".PHP_EOL);
        exit(1);
    }

    $eol = str_contains($original, "\r\n") ? "\r\n" : "\n";
    $normalised = str_replace("\r\n", "\n", $original);
    $updated = $mutator($normalised);

    if (! is_string($updated)) {
        fwrite(STDERR, "INVALID UPDATE {$path}".PHP_EOL);
        exit(1);
    }

    $updated = str_replace("\n", $eol, $updated);

    if ($updated === $original) {
        echo "UNCHANGED {$path}".PHP_EOL;
        return;
    }

    if (file_put_contents($path, $updated) === false) {
        fwrite(STDERR, "WRITE ERROR {$path}".PHP_EOL);
        exit(1);
    }

    echo "UPDATED {$path}".PHP_EOL;
}

function ensureTrailingNewline(string $value): string
{
    return rtrim($value, "\n")."\n";
}

updateFile('.env.example', static function (string $contents): string {
    $contents = ensureTrailingNewline($contents);

    $entries = [
        'KASSENSYSTEM_BACKUP_DIRECTORY=storage/app/backups/database',
        'KASSENSYSTEM_MYSQL_DUMP_BINARY=mysqldump',
        'KASSENSYSTEM_MYSQL_CLIENT_BINARY=mysql',
    ];

    foreach ($entries as $entry) {
        [$key] = explode('=', $entry, 2);
        if (preg_match('/^'.preg_quote($key, '/').'=/m', $contents) !== 1) {
            $contents .= $entry."\n";
        }
    }

    return $contents;
});

updateFile('.gitignore', static function (string $contents): string {
    $contents = ensureTrailingNewline($contents);
    $entry = '/storage/app/backups/';

    if (! in_array($entry, preg_split('/\n/', trim($contents)), true)) {
        $contents .= $entry."\n";
    }

    return $contents;
});

updateFile('routes/web.php', static function (string $contents): string {
    if (! str_contains($contents, 'use Illuminate\\Support\\Facades\\DB;')) {
        $needle = 'use Illuminate\\Support\\Facades\\Auth;';
        if (! str_contains($contents, $needle)) {
            fwrite(STDERR, "routes/web.php: Auth import not found; no automatic edit performed.".PHP_EOL);
            exit(1);
        }
        $contents = str_replace($needle, $needle."\nuse Illuminate\\Support\\Facades\\DB;", $contents, $count);
        if ($count !== 1) {
            fwrite(STDERR, "routes/web.php: unexpected Auth import count.".PHP_EOL);
            exit(1);
        }
    }

    if (! str_contains($contents, "->name('health')") && ! str_contains($contents, '->name("health")')) {
        $contents = ensureTrailingNewline($contents);
        $contents .= <<<'PHPBLOCK'

Route::get('/health', function () {
    try {
        DB::connection()->select('SELECT 1');

        return response()->json([
            'status' => 'ok',
            'database' => 'ok',
            'timestamp' => now()->utc()->toIso8601String(),
        ]);
    } catch (Throwable) {
        return response()->json([
            'status' => 'unavailable',
            'database' => 'unavailable',
            'timestamp' => now()->utc()->toIso8601String(),
        ], 503);
    }
})->name('health');
PHPBLOCK;
        $contents .= "\n";
    }

    return $contents;
});

echo "V1-006 compatibility updates complete.".PHP_EOL;
