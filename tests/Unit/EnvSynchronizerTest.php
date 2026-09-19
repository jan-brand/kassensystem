<?php

use App\Foundation\Environment\EnvSynchronizer;

it('preserves existing values while adding missing keys', function (): void {
    $sync = new EnvSynchronizer;
    $result = $sync->synchronize("A=default\nB=two\n", "A=secret\n", false, false);
    expect($result['content'])->toContain("A=secret")->toContain("B=two");
});

it('uses template values in force mode', function (): void {
    $sync = new EnvSynchronizer;
    $result = $sync->synchronize("A=default\n", "A=secret\n", true, false);
    expect($result['content'])->toContain("A=default");
});
