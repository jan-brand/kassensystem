<?php

use App\Foundation\Production\ReleaseArtifactCheckService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

$basePath = dirname(__DIR__, 2);

require $basePath.'/vendor/autoload.php';

/** @var Application $app */
$app = require $basePath.'/bootstrap/app.php';

/** @var Kernel $kernel */
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

/** @var ReleaseArtifactCheckService $checks */
$checks = $app->make(ReleaseArtifactCheckService::class);
$results = $checks->run();

foreach ($results as $result) {
    printf(
        '%-6s %-28s %s%s',
        strtoupper($result['status']),
        $result['name'],
        $result['message'],
        PHP_EOL,
    );
}

$failures = array_filter(
    $results,
    static fn (array $result): bool => $result['status'] === 'fail',
);

if ($failures !== []) {
    fwrite(
        STDERR,
        count($failures).' release artifact check(s) failed.'.PHP_EOL,
    );

    exit(1);
}

fwrite(STDOUT, 'Release artifact checks passed.'.PHP_EOL);

exit(0);
