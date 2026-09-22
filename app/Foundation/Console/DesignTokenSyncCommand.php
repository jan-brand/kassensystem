<?php

namespace App\Foundation\Console;

use App\Foundation\Generation\FilePlan;
use App\Foundation\Support\JsonFile;

final class DesignTokenSyncCommand extends FoundationCommand
{
    protected $signature = 'design:token:sync {--dry-run} {--force}';

    protected $description = 'Generate CSS custom properties from design tokens.';

    public function handle(FilePlan $p): int
    {
        $t = JsonFile::read(resource_path('design/tokens/tokens.json'), ['tokens' => []])['tokens'];
        $css = ":root {\n";
        foreach ($t as $n => $v) {
            $css .= '    --'.str_replace('.', '-', $n).': '.$v.";\n";
        }$css .= "}\n";
        $p->write('resources/css/foundation-tokens.css', $css, true);

        return $this->runPlan($p, 'design:token:sync');
    }
}
