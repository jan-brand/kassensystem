<?php
namespace App\Foundation\Console;
use Illuminate\Console\Command;
final class EnvBackupCommand extends Command
{protected $signature='env:backup {--target=*}';protected $description='Create timestamped backups of environment files under .foundation/env-backups.';public function handle():int{$targets=$this->option('target')?:config('foundation.environment.targets');$dir=base_path('.foundation/env-backups');if(!is_dir($dir))mkdir($dir,0775,true);foreach($targets as $t){$p=base_path($t);if(!is_file($p)){$this->warn("Skip {$t}: missing");continue;}$dest=$dir.'/'.basename($t).'.'.date('Ymd-His').'.bak';copy($p,$dest);$this->info(str_replace(base_path().'/','',$dest));}return self::SUCCESS;}}
