<?php
namespace App\Foundation\Console;use App\Foundation\Registry\ProjectRegistry;use Illuminate\Console\Command;
final class DesignGraphCommand extends Command{protected $signature='design:graph {--json}';protected $description='Show design-system dependency graph.';public function handle(ProjectRegistry $r):int{$g=[];foreach($r->design() as $i)$g[$i['name']]=$i['uses']??[];if($this->option('json')){$this->line(json_encode($g,JSON_PRETTY_PRINT));return self::SUCCESS;}foreach($g as $n=>$u)$this->line($n.' -> '.($u?implode(', ',$u):'(none)'));return self::SUCCESS;}}
