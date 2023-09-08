<?php

namespace FGTasks;
use Symfony\Component\Console\Command\Command;

class CommandError extends \Exception{}

class CommandBase extends Command{
    public $in;
    public $out;
    final public function execute($in, $out){
        $this->in = $in;
        $this->out = $out;
        try{
            return $this->exec();
        } catch(CommandError $e){
            $this->out->writeln("<error>".$e->getMessage()."</error>");
            return self::FAILURE;
        }
    }
    public function error($msg){
        throw new CommandError($msg);
    }
    public function info($msg){
        $this->out->writeln("<info>$msg</error>");
    }
}
