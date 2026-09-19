<?php
namespace App\Foundation\Console;use App\Foundation\Registry\ProjectRegistry;
final class PageListCommand extends FoundationCommand{protected $signature='page:list {--surface=}';protected $description='List manifest-driven pages.';public function handle(ProjectRegistry $r):int{$items=$r->pages();if($s=$this->option('surface'))$items=array_values(array_filter($items,fn($x)=>($x['surface']??'')===$s));$this->tableOrEmpty(['Name','Surface','URI','Route'],array_map(fn($p)=>[$p['name'],$p['surface'],$p['uri'],$p['route_name']],$items));return self::SUCCESS;}}
