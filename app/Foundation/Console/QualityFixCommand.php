<?php
namespace App\Foundation\Console;use Illuminate\Console\Command;use Symfony\Component\Process\Process;
final class QualityFixCommand extends Command{protected $signature='quality:fix';protected $description='Apply safe automatic code formatting fixes.';public function handle():int{$p=new Process(['php','vendor/bin/pint'],base_path());$p->setTimeout(300);$p->run(fn($type,$buf)=>$this->output->write($buf));return $p->isSuccessful()?self::SUCCESS:self::FAILURE;}}
