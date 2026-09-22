<?php

use App\Foundation\Production\FinalReleaseCheckService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

$basePath = dirname(__DIR__, 2);

require $basePath.'/vendor/autoload.php';

/** @var Application $app */
$app = require $basePath.'/bootstrap/app.php';

/** @var Kernel $kernel */
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

/** @var FinalReleaseCheckService $checks */
$checks = $app->make(FinalReleaseCheckService::class);
$results = $checks->run();
$statusOnly = in_array('--status-only', $argv, true);

foreach ($results as $result) {
    $label = $statusOnly && $result['status'] === 'fail'
        ? 'BLOCK'
        : strtoupper($result['status']);

    printf(
        '%-6s %-24s %s%s',
        $label,
        $result['name'],
        $result['message'],
        PHP_EOL,
    );
}

$blockers = $checks->blockers();

if ($blockers === []) {
    fwrite(STDOUT, 'Final v1.0.0 release gate passed.'.PHP_EOL);

    exit(0);
}

if ($statusOnly) {
    fwrite(
        STDOUT,
        count($blockers).' final release blocker(s) remain.'.PHP_EOL,
    );

    exit(0);
}

fwrite(
    STDERR,
    count($blockers).' final release blocker(s) remain.'.PHP_EOL,
);

exit(1);
