<?php

namespace App\Foundation\Console;

use App\Foundation\Generation\FilePlan;
use App\Foundation\Support\JsonFile;

final class DesignTokenMakeCommand extends FoundationCommand
{
    protected $signature = 'design:token:make {name} {value} {--dry-run} {--force}';

    protected $description = 'Add or update a design token.';

    public function handle(FilePlan $p): int
    {
        $rel = 'resources/design/tokens/tokens.json';
        $d = JsonFile::read(base_path($rel), ['tokens' => []]);
        $d['tokens'][$this->argument('name')] = $this->argument('value');
        ksort($d['tokens']);
        $p->write($rel, JsonFile::encode($d), true);

        return $this->runPlan($p, 'design:token:make');
    }
}
