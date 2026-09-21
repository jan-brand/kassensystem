<?php

declare(strict_types=1);

const DEFAULT_PORT = 8000;

$projectRoot = dirname(__DIR__, 2);
chdir($projectRoot);

$command = strtolower($argv[1] ?? 'help');
$port = parsePort($argv[2] ?? null);
$debug = in_array('--debug', $argv, true);

try {
    exit(match ($command) {
        'start' => startServer($projectRoot, $port, $debug),
        'info' => showInfo($projectRoot, $port),
        'doctor' => doctor($projectRoot, $port),
        'status' => status($port),
        'firewall-add' => firewallAdd($port),
        'firewall-remove' => firewallRemove($port),
        'help', '-h', '--help' => help(),
        default => unknownCommand($command),
    });
} catch (Throwable $exception) {
    fwrite(STDERR, "ERROR: {$exception->getMessage()}".PHP_EOL);

    exit(1);
}

function help(): int
{
    echo <<<'TXT'
Kassensystem Mobile LAN

Verwendung:
  scripts\mobile\mobile.cmd doctor [port]
  scripts\mobile\mobile.cmd info [port]
  scripts\mobile\mobile.cmd start [port]
  scripts\mobile\mobile.cmd start [port] --debug
  scripts\mobile\mobile.cmd status [port]
  scripts\mobile\mobile.cmd firewall-add [port]
  scripts\mobile\mobile.cmd firewall-remove [port]

Standard-Port: 8000

Empfohlener Ablauf:
  1. Administrator-CMD: scripts\mobile\mobile.cmd firewall-add
  2. Normale CMD:       scripts\mobile\mobile.cmd doctor
  3. Normale CMD:       scripts\mobile\mobile.cmd start
  4. Auf dem Handy die ausgegebene URL öffnen.
  5. Server mit Strg+C beenden.

Der normale Start setzt APP_DEBUG=false und deaktiviert das Foundation-Dashboard
nur für den gestarteten Serverprozess. Mit --debug kann Debug bewusst aktiviert
werden. Debug sollte nur in einem vertrauenswürdigen lokalen Netz verwendet werden.
TXT;

    return 0;
}

function unknownCommand(string $command): int
{
    fwrite(STDERR, "Unbekannter Befehl: {$command}".PHP_EOL.PHP_EOL);
    help();

    return 1;
}

function parsePort(?string $value): int
{
    if ($value === null || $value === '') {
        return DEFAULT_PORT;
    }

    if (! ctype_digit($value)) {
        throw new InvalidArgumentException('Port muss eine Zahl sein.');
    }

    $port = (int) $value;

    if ($port < 1024 || $port > 65535) {
        throw new InvalidArgumentException('Port muss zwischen 1024 und 65535 liegen.');
    }

    return $port;
}

function primaryIpv4(): ?string
{
    if (PHP_OS_FAMILY === 'Windows') {
        $routeOutput = shell_exec('route print -4 2>NUL') ?? '';

        if (preg_match_all(
            '/^\s*0\.0\.0\.0\s+0\.0\.0\.0\s+(\d{1,3}(?:\.\d{1,3}){3})\s+(\d{1,3}(?:\.\d{1,3}){3})\s+(\d+)\s*$/m',
            $routeOutput,
            $matches,
            PREG_SET_ORDER,
        )) {
            usort(
                $matches,
                static fn (array $a, array $b): int => ((int) $a[3]) <=> ((int) $b[3]),
            );

            foreach ($matches as $match) {
                if (isUsableIpv4($match[2])) {
                    return $match[2];
                }
            }
        }

        $ipconfig = shell_exec('ipconfig 2>NUL') ?? '';

        if (preg_match_all('/IPv4[^:]*:\s*([0-9.]+)/iu', $ipconfig, $matches)) {
            foreach ($matches[1] as $candidate) {
                if (isUsableIpv4($candidate)) {
                    return $candidate;
                }
            }
        }
    }

    $host = gethostname();

    if (is_string($host) && $host !== '') {
        $candidate = gethostbyname($host);

        if (isUsableIpv4($candidate)) {
            return $candidate;
        }
    }

    return null;
}

function isUsableIpv4(string $ip): bool
{
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
        return false;
    }

    return ! str_starts_with($ip, '127.')
        && ! str_starts_with($ip, '169.254.')
        && $ip !== '0.0.0.0';
}

function mobileUrl(int $port): ?string
{
    $ip = primaryIpv4();

    return $ip !== null ? "http://{$ip}:{$port}" : null;
}

function showInfo(string $projectRoot, int $port): int
{
    $url = mobileUrl($port);

    echo "Projekt: {$projectRoot}".PHP_EOL;
    echo "Port:    {$port}".PHP_EOL;

    if ($url === null) {
        echo "LAN-IP:  nicht automatisch ermittelbar".PHP_EOL;
        echo "Bitte mit 'ipconfig' die IPv4-Adresse des WLAN-Adapters prüfen.".PHP_EOL;

        return 1;
    }

    $ip = parse_url($url, PHP_URL_HOST);

    echo "LAN-IP:  {$ip}".PHP_EOL;
    echo PHP_EOL;
    echo "Handy-URLs:".PHP_EOL;
    echo "  App:       {$url}/".PHP_EOL;
    echo "  POS:       {$url}/pos".PHP_EOL;
    echo "  Login:     {$url}/pos/login".PHP_EOL;
    echo "  Verwaltung:{$url}/administration".PHP_EOL;
    echo "  Health:    {$url}/health".PHP_EOL;

    return 0;
}

function doctor(string $projectRoot, int $port): int
{
    echo "Kassensystem Mobile LAN - Diagnose".PHP_EOL;
    echo str_repeat('=', 38).PHP_EOL;

    $problems = 0;

    $checks = [
        ['Projektverzeichnis', is_file($projectRoot.DIRECTORY_SEPARATOR.'artisan'), $projectRoot],
        ['.env vorhanden', is_file($projectRoot.DIRECTORY_SEPARATOR.'.env'), '.env'],
        [
            'Frontend gebaut',
            is_file($projectRoot.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'build'.DIRECTORY_SEPARATOR.'manifest.json'),
            'public/build/manifest.json',
        ],
        [
            'Storage beschreibbar',
            is_writable($projectRoot.DIRECTORY_SEPARATOR.'storage'),
            'storage/',
        ],
    ];

    foreach ($checks as [$label, $ok, $detail]) {
        echo sprintf("[%s] %-24s %s", $ok ? 'OK' : '!!', $label, $detail).PHP_EOL;
        if (! $ok) {
            $problems++;
        }
    }

    echo PHP_EOL;

    $url = mobileUrl($port);
    if ($url === null) {
        echo "[!!] Keine LAN-IP automatisch gefunden.".PHP_EOL;
        echo "     'ipconfig' ausführen und die IPv4-Adresse des WLAN-Adapters verwenden.".PHP_EOL;
        $problems++;
    } else {
        echo "[OK] Handy-Adresse: {$url}/".PHP_EOL;
    }

    $listening = isPortListening($port);
    echo sprintf(
        "[%s] Port %d %s".PHP_EOL,
        $listening ? 'OK' : '--',
        $port,
        $listening ? 'ist lokal erreichbar.' : 'ist noch nicht belegt; Server ist vermutlich noch nicht gestartet.',
    );

    echo PHP_EOL;
    echo "Firewall-Regel (einmalig, Administrator-CMD):".PHP_EOL;
    echo "  scripts\\mobile\\mobile.cmd firewall-add {$port}".PHP_EOL;
    echo PHP_EOL;
    echo "Server starten:".PHP_EOL;
    echo "  scripts\\mobile\\mobile.cmd start {$port}".PHP_EOL;

    if ($problems > 0) {
        echo PHP_EOL."Diagnose: {$problems} Problem(e) vor dem Start beheben.".PHP_EOL;

        return 1;
    }

    echo PHP_EOL."Diagnose: Grundvoraussetzungen sind erfüllt.".PHP_EOL;

    return 0;
}

function status(int $port): int
{
    $url = mobileUrl($port);
    $listening = isPortListening($port);

    echo "Port {$port}: ".($listening ? 'LISTENING' : 'NICHT ERREICHBAR').PHP_EOL;

    if ($url !== null) {
        echo "Handy: {$url}/".PHP_EOL;
    }

    return $listening ? 0 : 1;
}

function isPortListening(int $port): bool
{
    $errno = 0;
    $error = '';
    $socket = @fsockopen('127.0.0.1', $port, $errno, $error, 0.25);

    if (is_resource($socket)) {
        fclose($socket);

        return true;
    }

    return false;
}

function startServer(string $projectRoot, int $port, bool $debug): int
{
    if (! is_file($projectRoot.DIRECTORY_SEPARATOR.'artisan')) {
        throw new RuntimeException('artisan wurde nicht im Projektverzeichnis gefunden.');
    }

    if (! is_file($projectRoot.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'build'.DIRECTORY_SEPARATOR.'manifest.json')) {
        throw new RuntimeException('Frontend-Build fehlt. Bitte zuerst "npm run build" ausführen.');
    }

    $url = mobileUrl($port);

    if ($url === null) {
        throw new RuntimeException('Keine LAN-IP gefunden. Bitte zuerst "scripts\\mobile\\mobile.cmd doctor" ausführen.');
    }

    if (isPortListening($port)) {
        throw new RuntimeException("Port {$port} ist bereits belegt.");
    }

    echo "Kassensystem Mobile LAN".PHP_EOL;
    echo str_repeat('=', 24).PHP_EOL;
    echo "Handy: {$url}/".PHP_EOL;
    echo "POS:   {$url}/pos".PHP_EOL;
    echo "Health:{$url}/health".PHP_EOL;
    echo "Debug: ".($debug ? 'AN' : 'AUS').PHP_EOL;
    echo PHP_EOL;
    echo "Server läuft auf 0.0.0.0:{$port}.".PHP_EOL;
    echo "Mit Strg+C beenden.".PHP_EOL.PHP_EOL;

    // Konfigurationscache entfernen, damit die Prozessvariablen sicher greifen.
    $clearCommand = quote(PHP_BINARY).' artisan config:clear';
    passthru($clearCommand, $clearExit);

    if ($clearExit !== 0) {
        throw new RuntimeException('config:clear ist fehlgeschlagen.');
    }

    putenv("APP_URL={$url}");
    putenv('APP_DEBUG='.($debug ? 'true' : 'false'));
    putenv('FOUNDATION_DASHBOARD=false');

    set_time_limit(0);

    $serveCommand = quote(PHP_BINARY)." artisan serve --host=0.0.0.0 --port={$port}";
    passthru($serveCommand, $exitCode);

    return $exitCode;
}

function firewallRuleName(int $port): string
{
    return "Kassensystem Mobile Test {$port}";
}

function firewallAdd(int $port): int
{
    assertWindows();

    $name = firewallRuleName($port);

    echo "Windows-Firewall-Regel wird eingerichtet.".PHP_EOL;
    echo "Diese Aktion benötigt eine als Administrator gestartete CMD.".PHP_EOL.PHP_EOL;

    // Gleiche benannte Regel zuerst entfernen, damit der Befehl wiederholbar bleibt.
    passthru('netsh advfirewall firewall delete rule name='.quote($name).' >NUL 2>&1');

    $command = 'netsh advfirewall firewall add rule'
        .' name='.quote($name)
        .' dir=in action=allow protocol=TCP'
        ." localport={$port}"
        .' remoteip=LocalSubnet profile=any';

    passthru($command, $exitCode);

    if ($exitCode !== 0) {
        fwrite(STDERR, PHP_EOL.'Firewall-Regel konnte nicht angelegt werden. CMD als Administrator starten.'.PHP_EOL);

        return $exitCode;
    }

    echo PHP_EOL."Regel aktiv: {$name}".PHP_EOL;
    echo "Freigegeben ist nur TCP {$port} aus dem lokalen Subnetz.".PHP_EOL;

    return 0;
}

function firewallRemove(int $port): int
{
    assertWindows();

    $name = firewallRuleName($port);
    $command = 'netsh advfirewall firewall delete rule name='.quote($name);

    passthru($command, $exitCode);

    if ($exitCode !== 0) {
        fwrite(STDERR, PHP_EOL.'Firewall-Regel konnte nicht entfernt werden. CMD als Administrator starten.'.PHP_EOL);
    }

    return $exitCode;
}

function assertWindows(): void
{
    if (PHP_OS_FAMILY !== 'Windows') {
        throw new RuntimeException('Die Firewall-Helfer sind nur für Windows vorgesehen.');
    }
}

function quote(string $value): string
{
    if (PHP_OS_FAMILY === 'Windows') {
        return '"'.str_replace('"', '""', $value).'"';
    }

    return escapeshellarg($value);
}
