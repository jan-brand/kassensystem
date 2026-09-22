<?php

namespace App\Foundation\Console;

use App\Foundation\Generation\FilePlan;
use App\Foundation\Support\JsonFile;

final class DesignTokenRenameCommand extends FoundationCommand
{
    protected $signature = 'design:token:rename {from} {to} {--dry-run} {--force}';

    protected $description = 'Rename a token in the token registry.';

    public function handle(FilePlan $p): int
    {
        $rel = 'resources/design/tokens/tokens.json';
        $d = JsonFile::read(base_path($rel), ['tokens' => []]);
        $f = $this->argument('from');
        $t = $this->argument('to');
        if (! array_key_exists($f, $d['tokens'])) {
            $this->error('Token not found.');

            return self::FAILURE;
        }$d['tokens'][$t] = $d['tokens'][$f];
        unset($d['tokens'][$f]);
        ksort($d['tokens']);
        $p->write($rel, JsonFile::encode($d), true);
        $this->warn('Update CSS/Blade references manually, then run design:token:sync.');

        return $this->runPlan($p, 'design:token:rename');
    }
}
