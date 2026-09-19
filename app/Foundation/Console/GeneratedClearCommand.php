<?php
namespace App\Foundation\Console;use Illuminate\Console\Command;
final class GeneratedClearCommand extends Command{protected $signature='generated:clear';protected $description='Clear generated foundation registries/docs without touching source manifests.';public function handle():int{$paths=[storage_path('framework/foundation/registry.json'),base_path('docs/generated/catalog.md')];foreach($paths as $p)if(is_file($p)){unlink($p);$this->line('Deleted '.str_replace(base_path().'/','',$p));}return self::SUCCESS;}}
