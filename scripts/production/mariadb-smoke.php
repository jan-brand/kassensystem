<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);
chdir($projectRoot);

$options = parseOptions(array_slice($argv, 1));

if (isset($options['help'])) {
    help();
    exit(0);
}

$host = stringOption($options, 'host', '127.0.0.1');
$port = intOption($options, 'port', 3306);
$user = stringOption($options, 'user', 'root');
$prefix = stringOption($options, 'prefix', 'kassensystem_smoke_');
$keep = isset($options['keep']);
$password = getenv('KASSENSYSTEM_SMOKE_DB_PASSWORD');

if ($password === false) {
    $password = '';
}

assertSafeIdentifierPrefix($prefix);

if (! extension_loaded('pdo_mysql')) {
    fail('Die PHP-Erweiterung pdo_mysql ist nicht geladen.');
}

$database = $prefix.date('Ymd_His').'_'.bin2hex(random_bytes(3));
assertSafeIdentifier($database);

echo 'Kassensystem MariaDB/MySQL Smoke-Test'.PHP_EOL;
echo str_repeat('=', 39).PHP_EOL;
echo "Server:    {$host}:{$port}".PHP_EOL;
echo "Benutzer:  {$user}".PHP_EOL;
echo "Datenbank: {$database}".PHP_EOL;
echo 'Passwort:  '.($password === '' ? 'leer' : 'über Umgebungsvariable gesetzt').PHP_EOL;
echo PHP_EOL;

$server = null;
$databaseCreated = false;

try {
    $server = new PDO(
        "mysql:host={$host};port={$port};charset=utf8mb4",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ],
    );

    echo '[OK] Verbindung zum Datenbankserver'.PHP_EOL;

    $server->exec(sprintf(
        'CREATE DATABASE `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
        $database,
    ));
    $databaseCreated = true;

    echo '[OK] Temporäre Datenbank erstellt'.PHP_EOL;

    $environment = array_merge(
        is_array(getenv()) ? getenv() : [],
        [
            'APP_ENV' => 'testing',
            'APP_DEBUG' => 'false',
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $host,
            'DB_PORT' => (string) $port,
            'DB_DATABASE' => $database,
            'DB_USERNAME' => $user,
            'DB_PASSWORD' => $password,
            'DB_CHARSET' => 'utf8mb4',
            'DB_COLLATION' => 'utf8mb4_unicode_ci',
            'CACHE_STORE' => 'array',
            'SESSION_DRIVER' => 'array',
            'QUEUE_CONNECTION' => 'sync',
            'FOUNDATION_DASHBOARD' => 'false',
        ],
    );

    runArtisan(
        ['migrate:fresh', '--force', '--no-interaction'],
        $environment,
        'Migrationen',
    );

    runArtisan(
        ['migrate:status', '--no-interaction'],
        $environment,
        'Migrationsstatus',
    );

    $databaseConnection = new PDO(
        "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ],
    );

    $requiredTables = [
        'users',
        'audit_events',
        'categories',
        'products',
        'registers',
        'cash_sessions',
        'cash_movements',
        'sales',
        'sale_items',
        'payments',
        'sale_number_sequences',
        'system_settings',
    ];

    foreach ($requiredTables as $table) {
        $statement = $databaseConnection->prepare(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = ?',
        );
        $statement->execute([$database, $table]);

        if ((int) $statement->fetchColumn() !== 1) {
            throw new RuntimeException("Erwartete Tabelle fehlt: {$table}");
        }
    }

    echo '[OK] Kernschema vollständig ('.count($requiredTables).' Tabellen geprüft)'.PHP_EOL;

    if ($keep) {
        echo PHP_EOL;
        echo "[HINWEIS] --keep gesetzt: Datenbank {$database} bleibt bestehen.".PHP_EOL;
        echo 'Manuell löschen, sobald sie nicht mehr benötigt wird.'.PHP_EOL;
        $databaseCreated = false;
    } else {
        $server->exec("DROP DATABASE `{$database}`");
        $databaseCreated = false;
        echo '[OK] Temporäre Datenbank wieder entfernt'.PHP_EOL;
    }

    echo PHP_EOL.'Smoke-Test erfolgreich.'.PHP_EOL;
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, PHP_EOL.'[FEHLER] '.$exception->getMessage().PHP_EOL);

    if ($databaseCreated && $server instanceof PDO) {
        try {
            $server->exec("DROP DATABASE `{$database}`");
            fwrite(STDERR, '[CLEANUP] Temporäre Datenbank wurde entfernt.'.PHP_EOL);
        } catch (Throwable $cleanupException) {
            fwrite(
                STDERR,
                '[WARNUNG] Temporäre Datenbank konnte nicht automatisch entfernt werden: '
                .$cleanupException->getMessage().PHP_EOL,
            );
        }
    }

    exit(1);
}

function runArtisan(array $arguments, array $environment, string $label): void
{
    $command = array_merge([PHP_BINARY, 'artisan'], $arguments);

    $descriptorSpec = [
        0 => STDIN,
        1 => STDOUT,
        2 => STDERR,
    ];

    $process = proc_open(
        $command,
        $descriptorSpec,
        $pipes,
        getcwd() ?: null,
        $environment,
    );

    if (! is_resource($process)) {
        throw new RuntimeException("{$label}: Prozess konnte nicht gestartet werden.");
    }

    $exitCode = proc_close($process);

    if ($exitCode !== 0) {
        throw new RuntimeException("{$label} fehlgeschlagen (Exit-Code {$exitCode}).");
    }

    echo "[OK] {$label}".PHP_EOL;
}

function parseOptions(array $arguments): array
{
    $options = [];

    foreach ($arguments as $argument) {
        if ($argument === '--help' || $argument === '-h') {
            $options['help'] = true;

            continue;
        }

        if ($argument === '--keep') {
            $options['keep'] = true;

            continue;
        }

        if (! str_starts_with($argument, '--') || ! str_contains($argument, '=')) {
            fail("Unbekanntes Argument: {$argument}");
        }

        [$key, $value] = explode('=', substr($argument, 2), 2);

        if (! in_array($key, ['host', 'port', 'user', 'prefix'], true)) {
            fail("Unbekannte Option: --{$key}");
        }

        $options[$key] = $value;
    }

    return $options;
}

function stringOption(array $options, string $key, string $default): string
{
    $value = trim((string) ($options[$key] ?? $default));

    if ($value === '') {
        fail("--{$key} darf nicht leer sein.");
    }

    return $value;
}

function intOption(array $options, string $key, int $default): int
{
    $value = (string) ($options[$key] ?? $default);

    if (! ctype_digit($value)) {
        fail("--{$key} muss eine Zahl sein.");
    }

    $number = (int) $value;

    if ($number < 1 || $number > 65535) {
        fail("--{$key} ist außerhalb des gültigen Portbereichs.");
    }

    return $number;
}

function assertSafeIdentifierPrefix(string $prefix): void
{
    if (! preg_match('/^[A-Za-z][A-Za-z0-9_]{2,40}$/D', $prefix)) {
        fail('Der Datenbankpräfix darf nur Buchstaben, Ziffern und Unterstriche enthalten.');
    }
}

function assertSafeIdentifier(string $identifier): void
{
    if (! preg_match('/^[A-Za-z][A-Za-z0-9_]{2,63}$/D', $identifier)) {
        fail('Unsicherer Datenbankname.');
    }
}

function help(): void
{
    echo <<<'TXT'
Kassensystem MariaDB/MySQL Smoke-Test

Erzeugt eine zufällige temporäre Datenbank, führt alle Laravel-Migrationen darauf
aus, prüft das Kernschema und löscht die Datenbank anschließend wieder.

Verwendung:
  scripts\production\mariadb-smoke.cmd
  scripts\production\mariadb-smoke.cmd --host=127.0.0.1 --port=3306 --user=root
  scripts\production\mariadb-smoke.cmd --keep
  scripts\production\mariadb-smoke.cmd --help

Passwort:
  Das Passwort wird absichtlich nicht als Kommandozeilenargument akzeptiert.
  Falls nötig, vorher in derselben CMD setzen:

    set KASSENSYSTEM_SMOKE_DB_PASSWORD=DEIN_PASSWORT

  Danach den Smoke-Test starten und die Variable wieder entfernen:

    set KASSENSYSTEM_SMOKE_DB_PASSWORD=

Sicherheit:
  - Es wird ausschließlich eine neu erzeugte Datenbank mit dem Präfix
    "kassensystem_smoke_" verwendet.
  - Die aktuell konfigurierte SQLite-/Produktionsdatenbank wird nicht migriert.
  - Ohne --keep wird die temporäre Datenbank nach dem Test gelöscht.
TXT;
}

function fail(string $message): never
{
    fwrite(STDERR, 'ERROR: '.$message.PHP_EOL);
    exit(1);
}
