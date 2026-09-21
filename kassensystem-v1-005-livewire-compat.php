<?php

declare(strict_types=1);

$root = getcwd();

if (! is_string($root) || ! is_file($root.DIRECTORY_SEPARATOR.'artisan')) {
    fwrite(STDERR, "ERROR: Bitte dieses Script im Kassensystem-Projektverzeichnis ausfuehren.\n");
    exit(1);
}

/** @return never */
function fail(string $message): never
{
    fwrite(STDERR, "ERROR: {$message}\n");
    exit(1);
}

function loadFile(string $root, string $relative): array
{
    $path = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);

    if (! is_file($path)) {
        fail("Datei fehlt: {$relative}");
    }

    $content = file_get_contents($path);

    if ($content === false) {
        fail("Datei konnte nicht gelesen werden: {$relative}");
    }

    $eol = str_contains($content, "\r\n") ? "\r\n" : "\n";

    return [$path, $content, $eol];
}

function saveFile(string $path, string $content): void
{
    if (file_put_contents($path, $content) === false) {
        fail("Datei konnte nicht geschrieben werden: {$path}");
    }
}

function ensureImport(string $content, string $import, string $anchor, string $eol): string
{
    if (str_contains($content, $import)) {
        return $content;
    }

    if (! str_contains($content, $anchor)) {
        fail("Import-Anker nicht gefunden: {$anchor}");
    }

    return str_replace($anchor, $import.$eol.$anchor, $content);
}

function removeImport(string $content, string $import, string $eol): string
{
    return str_replace($import.$eol, '', $content);
}

function insertMethodBefore(
    string $content,
    string $method,
    string $before,
    string $alreadyMarker,
    string $eol,
): string {
    if (str_contains($content, $alreadyMarker)) {
        return $content;
    }

    if (! str_contains($content, $before)) {
        fail("Methoden-Anker nicht gefunden: {$before}");
    }

    $method = str_replace("\n", $eol, $method);

    return str_replace($before, $method.$eol.$eol.$before, $content);
}

function replaceBoot(
    string $content,
    string $nextMethodName,
    string $permissionCase,
    string $eol,
): string {
    $marker = "Gate::authorize(Permission::{$permissionCase}->value);";

    if (str_contains($content, $marker)) {
        return $content;
    }

    $pattern = '~    public function boot\(\): void\s*\{.*?\r?\n    \}\r?\n\r?\n    public function '.preg_quote($nextMethodName, '~').'~s';
    $replacement = str_replace("\n", $eol,
        "    public function boot(): void\n".
        "    {\n".
        "        {$marker}\n".
        "    }\n\n".
        "    public function {$nextMethodName}"
    );

    $updated = preg_replace($pattern, $replacement, $content, 1, $count);

    if (! is_string($updated) || $count !== 1) {
        fail("Vorhandene boot()-Methode vor {$nextMethodName} konnte nicht ersetzt werden.");
    }

    return $updated;
}

$files = [
    'app/Surfaces/Administration/Livewire/CatalogScreen.php' => function (string $content, string $eol): string {
        $content = ensureImport(
            $content,
            'use App\\Modules\\Identity\\Enums\\Permission;',
            'use App\\Modules\\Identity\\Models\\User;',
            $eol,
        );
        $content = ensureImport(
            $content,
            'use Illuminate\\Support\\Facades\\Gate;',
            'use InvalidArgumentException;',
            $eol,
        );

        return insertMethodBefore(
            $content,
            "    public function boot(): void\n".
            "    {\n".
            "        Gate::authorize(Permission::CatalogManage->value);\n".
            "    }",
            '    public function createCategory(',
            'Gate::authorize(Permission::CatalogManage->value);',
            $eol,
        );
    },
    'app/Surfaces/Administration/Livewire/CashiersScreen.php' => function (string $content, string $eol): string {
        $content = ensureImport(
            $content,
            'use App\\Modules\\Identity\\Enums\\Permission;',
            'use App\\Modules\\Identity\\Enums\\UserRole;',
            $eol,
        );
        $content = ensureImport(
            $content,
            'use Illuminate\\Support\\Facades\\Gate;',
            'use InvalidArgumentException;',
            $eol,
        );

        return insertMethodBefore(
            $content,
            "    public function boot(): void\n".
            "    {\n".
            "        Gate::authorize(Permission::UsersCashiersManage->value);\n".
            "    }",
            '    public function createCashier(',
            'Gate::authorize(Permission::UsersCashiersManage->value);',
            $eol,
        );
    },
    'app/Surfaces/Administration/Livewire/SettingsScreen.php' => function (string $content, string $eol): string {
        $content = ensureImport(
            $content,
            'use App\\Modules\\Identity\\Enums\\Permission;',
            'use App\\Modules\\Identity\\Models\\User;',
            $eol,
        );
        $content = ensureImport(
            $content,
            'use Illuminate\\Support\\Facades\\Gate;',
            'use Livewire\\Attributes\\Layout;',
            $eol,
        );

        return insertMethodBefore(
            $content,
            "    public function boot(): void\n".
            "    {\n".
            "        Gate::authorize(Permission::SettingsManage->value);\n".
            "    }",
            '    public function mount(',
            'Gate::authorize(Permission::SettingsManage->value);',
            $eol,
        );
    },
    'app/Surfaces/Administration/Livewire/ReportingScreen.php' => function (string $content, string $eol): string {
        $content = removeImport($content, 'use App\\Modules\\Identity\\Enums\\UserRole;', $eol);
        $content = removeImport($content, 'use App\\Modules\\Identity\\Models\\User;', $eol);
        $content = removeImport($content, 'use Illuminate\\Support\\Facades\\Auth;', $eol);
        $content = ensureImport(
            $content,
            'use App\\Modules\\Identity\\Enums\\Permission;',
            'use App\\Modules\\Reporting\\Queries\\GetCashSessionReportQuery;',
            $eol,
        );
        $content = ensureImport(
            $content,
            'use Illuminate\\Support\\Facades\\Gate;',
            'use Livewire\\Attributes\\Layout;',
            $eol,
        );

        return replaceBoot($content, 'mount', 'ReportsView', $eol);
    },
    'app/Surfaces/Administration/Livewire/AuditScreen.php' => function (string $content, string $eol): string {
        $content = removeImport($content, 'use App\\Modules\\Identity\\Enums\\UserRole;', $eol);
        $content = removeImport($content, 'use App\\Modules\\Identity\\Models\\User;', $eol);
        $content = removeImport($content, 'use Illuminate\\Support\\Facades\\Auth;', $eol);
        $content = ensureImport(
            $content,
            'use App\\Modules\\Identity\\Enums\\Permission;',
            'use Carbon\\CarbonImmutable;',
            $eol,
        );
        $content = ensureImport(
            $content,
            'use Illuminate\\Support\\Facades\\Gate;',
            'use Livewire\\Attributes\\Layout;',
            $eol,
        );

        return replaceBoot($content, 'applyFilters', 'AuditView', $eol);
    },
    'app/Surfaces/Administration/Livewire/SalesScreen.php' => function (string $content, string $eol): string {
        $content = removeImport($content, 'use App\\Modules\\Identity\\Enums\\UserRole;', $eol);
        $content = removeImport($content, 'use App\\Modules\\Identity\\Models\\User;', $eol);
        $content = removeImport($content, 'use Illuminate\\Support\\Facades\\Auth;', $eol);
        $content = ensureImport(
            $content,
            'use App\\Modules\\Identity\\Enums\\Permission;',
            'use App\\Modules\\Sales\\Queries\\GetCompletedSaleQuery;',
            $eol,
        );
        $content = ensureImport(
            $content,
            'use Illuminate\\Support\\Facades\\Gate;',
            'use Livewire\\Attributes\\Layout;',
            $eol,
        );

        return replaceBoot($content, 'applyFilters', 'SalesView', $eol);
    },
];

foreach ($files as $relative => $transform) {
    [$path, $content, $eol] = loadFile($root, $relative);
    $updated = $transform($content, $eol);

    if ($updated !== $content) {
        saveFile($path, $updated);
        echo "UPDATED {$relative}\n";
    } else {
        echo "OK      {$relative}\n";
    }
}

echo "V1-005 Livewire compatibility updates complete.\n";
