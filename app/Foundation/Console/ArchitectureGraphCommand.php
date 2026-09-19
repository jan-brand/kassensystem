<?php
namespace App\Foundation\Console;use App\Foundation\Architecture\ArchitectureInspector;use Illuminate\Console\Command;
final class ArchitectureGraphCommand extends Command{protected $signature='architecture:graph {--json}';protected $description='Show the application module dependency graph.';public function handle(ArchitectureInspector $i):int{$g=$i->graph();if($this->option('json')){$this->line(json_encode($g,JSON_PRETTY_PRINT));return self::SUCCESS;}foreach($g as $n=>$d)$this->line($n.' -> '.($d?implode(', ',$d):'(none)'));return self::SUCCESS;}}
