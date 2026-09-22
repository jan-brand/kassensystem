<?php

declare(strict_types=1);

const VALID_STATUSES = ['open', 'in-progress', 'closed'];
const VALID_PRIORITIES = ['P0', 'P1', 'P2', 'P3'];
const VALID_TYPES = ['bug', 'feature', 'tech', 'docs', 'qa', 'epic'];

$root = realpath(__DIR__.'/../..');
if ($root === false) {
    fwrite(STDERR, "ERROR: Repository root could not be resolved.\n");
    exit(1);
}

$storePath = $root.'/issues/issues.json';
$command = strtolower($argv[1] ?? 'help');
$args = array_slice($argv, 2);

try {
    $store = loadStore($storePath);

    switch ($command) {
        case 'list':
            commandList($store, $args);
            break;
        case 'next':
            commandNext($store, $root);
            break;
        case 'show':
            commandShow($store, $root, requireId($args));
            break;
        case 'start':
            updateStatus($store, $storePath, requireId($args), 'in-progress');
            break;
        case 'close':
            updateStatus($store, $storePath, requireId($args), 'closed');
            break;
        case 'reopen':
            updateStatus($store, $storePath, requireId($args), 'open');
            break;
        case 'new':
            commandNew($store, $storePath, $root);
            break;
        case 'stats':
            commandStats($store);
            break;
        case 'doctor':
            commandDoctor($store, $root);
            break;
        case 'help':
        case '--help':
        case '-h':
            printHelp();
            break;
        default:
            throw new RuntimeException("Unknown command: {$command}. Run 'scripts\\issues\\issues.cmd help'.");
    }
} catch (Throwable $exception) {
    fwrite(STDERR, 'ERROR: '.$exception->getMessage().PHP_EOL);
    exit(1);
}

/** @return array{version:int,issues:list<array<string,mixed>>} */
function loadStore(string $path): array
{
    if (! is_file($path)) {
        throw new RuntimeException("Issue store not found: {$path}");
    }

    $json = file_get_contents($path);
    if ($json === false) {
        throw new RuntimeException("Issue store could not be read: {$path}");
    }

    $data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
    if (! is_array($data) || ! isset($data['issues']) || ! is_array($data['issues'])) {
        throw new RuntimeException('Issue store has an invalid format.');
    }

    return $data;
}

/** @param array{version:int,issues:list<array<string,mixed>>} $store */
function saveStore(array $store, string $path): void
{
    $json = json_encode($store, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    if (file_put_contents($path, $json.PHP_EOL, LOCK_EX) === false) {
        throw new RuntimeException("Issue store could not be written: {$path}");
    }
}

/** @param array<string,mixed> $issue */
function printRow(array $issue): void
{
    $areas = implode(',', array_map('strval', $issue['areas'] ?? []));
    printf(
        "%-8s %-12s %-3s %-8s %-22s %s\n",
        (string) ($issue['id'] ?? ''),
        (string) ($issue['status'] ?? ''),
        (string) ($issue['priority'] ?? ''),
        (string) ($issue['type'] ?? ''),
        $areas,
        (string) ($issue['title'] ?? '')
    );
}

/** @param array{version:int,issues:list<array<string,mixed>>} $store */
function commandList(array $store, array $args): void
{
    $options = parseOptions($args);
    $all = array_key_exists('all', $options);
    $issues = $store['issues'];

    $issues = array_values(array_filter($issues, static function (array $issue) use ($options, $all): bool {
        if (! $all && ! isset($options['status']) && ($issue['status'] ?? null) === 'closed') {
            return false;
        }

        foreach (['status', 'priority', 'type', 'milestone'] as $field) {
            if (isset($options[$field]) && strcasecmp((string) ($issue[$field] ?? ''), (string) $options[$field]) !== 0) {
                return false;
            }
        }

        if (isset($options['area'])) {
            $areas = array_map('strtolower', array_map('strval', $issue['areas'] ?? []));
            if (! in_array(strtolower((string) $options['area']), $areas, true)) {
                return false;
            }
        }

        return true;
    }));

    sortIssues($issues);

    echo "ID       STATUS       PRI TYPE     AREAS                  TITLE\n";
    echo str_repeat('-', 118)."\n";
    foreach ($issues as $issue) {
        printRow($issue);
    }
    echo "\n".count($issues)." issue(s).\n";
}

/** @param array{version:int,issues:list<array<string,mixed>>} $store */
function commandNext(array $store, string $root): void
{
    $issues = array_values(array_filter(
        $store['issues'],
        static fn (array $issue): bool => ($issue['status'] ?? null) !== 'closed' && ($issue['type'] ?? null) !== 'epic'
    ));

    if ($issues === []) {
        echo "No open work items.\n";

        return;
    }

    usort($issues, static function (array $a, array $b): int {
        $statusRank = ['in-progress' => 0, 'open' => 1, 'closed' => 2];
        $priorityRank = ['P0' => 0, 'P1' => 1, 'P2' => 2, 'P3' => 3];

        return ($statusRank[$a['status'] ?? 'open'] ?? 9) <=> ($statusRank[$b['status'] ?? 'open'] ?? 9)
            ?: ($priorityRank[$a['priority'] ?? 'P3'] ?? 9) <=> ($priorityRank[$b['priority'] ?? 'P3'] ?? 9)
            ?: strcmp((string) ($a['id'] ?? ''), (string) ($b['id'] ?? ''));
    });

    $issue = $issues[0];
    printRow($issue);
    echo "\nUse: scripts\\issues\\issues.cmd show {$issue['id']}\n";
    if (($issue['status'] ?? '') === 'open') {
        echo "Then: scripts\\issues\\issues.cmd start {$issue['id']}\n";
    }
}

/** @param array{version:int,issues:list<array<string,mixed>>} $store */
function commandShow(array $store, string $root, string $id): void
{
    $issue = findIssue($store, $id);

    echo "ID:        {$issue['id']}\n";
    echo "Title:     {$issue['title']}\n";
    echo "Status:    {$issue['status']}\n";
    echo "Priority:  {$issue['priority']}\n";
    echo "Type:      {$issue['type']}\n";
    echo 'Areas:     '.implode(', ', $issue['areas'] ?? [])."\n";
    echo "Milestone: {$issue['milestone']}\n";
    echo 'GitHub:    '.(($issue['github_number'] ?? null) === null ? 'not synced' : '#'.$issue['github_number'])."\n";
    echo "Body:      {$issue['body_file']}\n\n";

    $bodyPath = pathFromRoot($root, (string) $issue['body_file']);
    if (! is_file($bodyPath)) {
        echo "[Body file missing]\n";

        return;
    }

    $body = file_get_contents($bodyPath);
    echo $body === false ? "[Body file could not be read]\n" : rtrim($body)."\n";
}

/** @param array{version:int,issues:list<array<string,mixed>>} $store */
function updateStatus(array $store, string $storePath, string $id, string $status): void
{
    if (! in_array($status, VALID_STATUSES, true)) {
        throw new RuntimeException("Invalid status: {$status}");
    }

    $index = findIssueIndex($store, $id);
    $current = (string) $store['issues'][$index]['status'];

    if ($current === $status) {
        echo "{$id} is already {$status}.\n";

        return;
    }

    if ($status === 'in-progress' && $current === 'closed') {
        throw new RuntimeException("{$id} is closed. Reopen it first.");
    }

    $store['issues'][$index]['status'] = $status;
    $store['issues'][$index]['closed_at'] = $status === 'closed' ? date('Y-m-d') : null;
    saveStore($store, $storePath);

    echo "{$id}: {$current} -> {$status}\n";
}

/** @param array{version:int,issues:list<array<string,mixed>>} $store */
function commandNew(array $store, string $storePath, string $root): void
{
    echo "Create local issue (offline)\n";
    echo str_repeat('-', 28)."\n";

    $title = promptRequired('Title');
    $type = promptChoice('Type', VALID_TYPES, 'feature');
    $priority = strtoupper(promptChoice('Priority', VALID_PRIORITIES, 'P2'));
    $milestone = strtolower(promptDefault('Milestone', 'v1'));
    $areasRaw = promptDefault('Areas (comma separated)', 'foundation');
    $areas = array_values(array_filter(array_map(
        static fn (string $area): string => strtolower(trim($area)),
        explode(',', $areasRaw)
    )));

    $id = nextIssueId($store['issues'], $milestone);
    $bodyRelative = 'issues/backlog/'.$id.'.md';
    $bodyPath = pathFromRoot($root, $bodyRelative);

    $dir = dirname($bodyPath);
    if (! is_dir($dir) && ! mkdir($dir, 0777, true) && ! is_dir($dir)) {
        throw new RuntimeException("Could not create issue body directory: {$dir}");
    }

    $body = "## Ziel\n\nBeschreibe hier das Ziel.\n\n## Umfang\n\n- [ ] Aufgabe ergaenzen\n\n## Akzeptanzkriterien\n\n- [ ] Kriterium ergaenzen\n";
    if (file_put_contents($bodyPath, $body, LOCK_EX) === false) {
        throw new RuntimeException("Could not create issue body: {$bodyPath}");
    }

    $store['issues'][] = [
        'id' => $id,
        'title' => $title,
        'status' => 'open',
        'priority' => $priority,
        'type' => $type,
        'areas' => $areas,
        'milestone' => $milestone,
        'body_file' => $bodyRelative,
        'github_number' => null,
        'created_at' => date('Y-m-d'),
        'closed_at' => null,
    ];

    saveStore($store, $storePath);

    echo "\nCreated {$id}.\n";
    echo "Body: {$bodyRelative}\n";
    echo "Edit that file to complete goal, scope and acceptance criteria.\n";
}

/** @param array{version:int,issues:list<array<string,mixed>>} $store */
function commandStats(array $store): void
{
    $statusCounts = array_fill_keys(VALID_STATUSES, 0);
    $priorityCounts = array_fill_keys(VALID_PRIORITIES, 0);

    foreach ($store['issues'] as $issue) {
        $status = (string) ($issue['status'] ?? '');
        $priority = (string) ($issue['priority'] ?? '');
        if (array_key_exists($status, $statusCounts)) {
            $statusCounts[$status]++;
        }
        if (array_key_exists($priority, $priorityCounts)) {
            $priorityCounts[$priority]++;
        }
    }

    echo "Status\n";
    foreach ($statusCounts as $status => $count) {
        echo "  {$status}: {$count}\n";
    }
    echo "\nPriority\n";
    foreach ($priorityCounts as $priority => $count) {
        echo "  {$priority}: {$count}\n";
    }
}

/** @param array{version:int,issues:list<array<string,mixed>>} $store */
function commandDoctor(array $store, string $root): void
{
    $errors = 0;
    $seenIds = [];

    foreach ($store['issues'] as $issue) {
        $id = (string) ($issue['id'] ?? '');
        if ($id === '') {
            echo "ERROR: Issue without ID.\n";
            $errors++;

            continue;
        }

        if (isset($seenIds[strtolower($id)])) {
            echo "ERROR: Duplicate ID {$id}.\n";
            $errors++;
        }
        $seenIds[strtolower($id)] = true;

        if (! in_array($issue['status'] ?? null, VALID_STATUSES, true)) {
            echo "ERROR: {$id} has invalid status.\n";
            $errors++;
        }
        if (! in_array($issue['priority'] ?? null, VALID_PRIORITIES, true)) {
            echo "ERROR: {$id} has invalid priority.\n";
            $errors++;
        }
        if (! in_array($issue['type'] ?? null, VALID_TYPES, true)) {
            echo "ERROR: {$id} has invalid type.\n";
            $errors++;
        }

        $bodyFile = (string) ($issue['body_file'] ?? '');
        if ($bodyFile === '' || ! is_file(pathFromRoot($root, $bodyFile))) {
            echo "ERROR: {$id} body file is missing: {$bodyFile}\n";
            $errors++;
        }
    }

    if ($errors > 0) {
        throw new RuntimeException("Issue doctor found {$errors} problem(s).");
    }

    echo 'Issue store valid: '.count($store['issues'])." issues, all body files present.\n";
}

/** @param list<array<string,mixed>> $issues */
function sortIssues(array &$issues): void
{
    usort($issues, static function (array $a, array $b): int {
        $statusRank = ['in-progress' => 0, 'open' => 1, 'closed' => 2];
        $priorityRank = ['P0' => 0, 'P1' => 1, 'P2' => 2, 'P3' => 3];

        return ($statusRank[$a['status'] ?? 'open'] ?? 9) <=> ($statusRank[$b['status'] ?? 'open'] ?? 9)
            ?: ($priorityRank[$a['priority'] ?? 'P3'] ?? 9) <=> ($priorityRank[$b['priority'] ?? 'P3'] ?? 9)
            ?: strcmp((string) ($a['id'] ?? ''), (string) ($b['id'] ?? ''));
    });
}

/** @return array<string,string|bool> */
function parseOptions(array $args): array
{
    $options = [];
    foreach ($args as $arg) {
        if ($arg === '--all') {
            $options['all'] = true;

            continue;
        }
        if (str_starts_with($arg, '--') && str_contains($arg, '=')) {
            [$key, $value] = explode('=', substr($arg, 2), 2);
            $options[strtolower($key)] = $value;
        }
    }

    return $options;
}

function requireId(array $args): string
{
    $id = strtoupper(trim((string) ($args[0] ?? '')));
    if ($id === '') {
        throw new RuntimeException('Issue ID is required.');
    }

    return $id;
}

/** @param array{version:int,issues:list<array<string,mixed>>} $store @return array<string,mixed> */
function findIssue(array $store, string $id): array
{
    return $store['issues'][findIssueIndex($store, $id)];
}

/** @param array{version:int,issues:list<array<string,mixed>>} $store */
function findIssueIndex(array $store, string $id): int
{
    foreach ($store['issues'] as $index => $issue) {
        if (strcasecmp((string) ($issue['id'] ?? ''), $id) === 0) {
            return $index;
        }
    }

    throw new RuntimeException("Issue not found: {$id}");
}

/** @param list<array<string,mixed>> $issues */
function nextIssueId(array $issues, string $milestone): string
{
    $prefix = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', $milestone) ?: 'LOCAL');
    $max = 0;

    foreach ($issues as $issue) {
        $id = (string) ($issue['id'] ?? '');
        if (preg_match('/^'.preg_quote($prefix, '/').'-(\d+)$/i', $id, $matches) === 1) {
            $max = max($max, (int) $matches[1]);
        }
    }

    return sprintf('%s-%03d', $prefix, $max + 1);
}

function promptRequired(string $label): string
{
    do {
        $value = trim(prompt($label.': '));
    } while ($value === '');

    return $value;
}

function promptDefault(string $label, string $default): string
{
    $value = trim(prompt("{$label} [{$default}]: "));

    return $value === '' ? $default : $value;
}

/** @param list<string> $choices */
function promptChoice(string $label, array $choices, string $default): string
{
    while (true) {
        $value = promptDefault($label.' ('.implode('/', $choices).')', $default);
        foreach ($choices as $choice) {
            if (strcasecmp($value, $choice) === 0) {
                return $choice;
            }
        }
        echo "Invalid value.\n";
    }
}

function prompt(string $text): string
{
    echo $text;
    $line = fgets(STDIN);
    if ($line === false) {
        throw new RuntimeException('Input could not be read.');
    }

    return $line;
}

function pathFromRoot(string $root, string $relative): string
{
    return $root.'/'.str_replace('\\', '/', $relative);
}

function printHelp(): void
{
    echo <<<'TXT'
Local offline issue system

Usage from CMD:
  scripts\issues\issues.cmd list [--all] [--status=open] [--priority=P1] [--type=feature] [--area=pos]
  scripts\issues\issues.cmd next
  scripts\issues\issues.cmd show <ID>
  scripts\issues\issues.cmd start <ID>
  scripts\issues\issues.cmd close <ID>
  scripts\issues\issues.cmd reopen <ID>
  scripts\issues\issues.cmd new
  scripts\issues\issues.cmd stats
  scripts\issues\issues.cmd doctor

No network connection and no GitHub CLI are required.
TXT;
    echo PHP_EOL;
}
