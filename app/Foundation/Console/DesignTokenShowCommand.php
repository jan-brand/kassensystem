<?php
namespace App\Foundation\Console;use App\Foundation\Support\JsonFile;use Illuminate\Console\Command;
final class DesignTokenShowCommand extends Command{protected $signature='design:token:show {name}';protected $description='Show one design token.';public function handle():int{$t=JsonFile::read(resource_path('design/tokens/tokens.json'),['tokens'=>[]])['tokens'];$n=$this->argument('name');if(!array_key_exists($n,$t)){$this->error('Token not found.');return self::FAILURE;}$this->line($n.' = '.$t[$n]);return self::SUCCESS;}}
