<?php
namespace App\Foundation\Console;use App\Foundation\Registry\ProjectRegistry;use Illuminate\Console\Command;
final class ManifestCheckCommand extends Command{protected $signature='manifest:check';protected $description='Parse all source manifests and report invalid JSON/schema basics.';public function handle(ProjectRegistry $r):int{try{$r->modules();$r->surfaces();$r->pages();$r->design();$r->permissions();$r->navigation();}catch(\Throwable $e){$this->error($e->getMessage());return self::FAILURE;}$this->info('All manifests parse successfully.');return self::SUCCESS;}}
