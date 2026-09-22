<?php

namespace App\Foundation\Console;

use App\Foundation\Support\JsonFile;
use Illuminate\Console\Command;

final class DesignTokenListCommand extends Command
{
    protected $signature = 'design:token:list';

    protected $description = 'List design tokens.';

    public function handle(): int
    {
        $d = JsonFile::read(resource_path('design/tokens/tokens.json'), ['tokens' => []]);
        foreach ($d['tokens'] as $k => $v) {
            $this->line("{$k} = {$v}");
        }

return self::SUCCESS;
    }
}
