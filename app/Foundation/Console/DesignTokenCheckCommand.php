<?php

namespace App\Foundation\Console;

use App\Foundation\Support\JsonFile;
use Illuminate\Console\Command;

final class DesignTokenCheckCommand extends Command
{
    protected $signature = 'design:token:check';

    protected $description = 'Validate token names and values.';

    public function handle(): int
    {
        $t = JsonFile::read(resource_path('design/tokens/tokens.json'), ['tokens' => []])['tokens'];
        $bad = false;
        foreach ($t as $n => $v) {
            if (! preg_match('/^[a-z][a-z0-9.-]*$/', $n)) {
                $this->error("Invalid token name: {$n}");
                $bad = true;
            }if (! is_scalar($v)) {
                $this->error("Token {$n} must have a scalar value.");
                $bad = true;
            }
        }if (! $bad) {
            $this->info('Design tokens valid.');
        }

        return $bad ? self::FAILURE : self::SUCCESS;
    }
}
