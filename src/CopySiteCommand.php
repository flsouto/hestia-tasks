<?php
namespace FGTasks;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Process\Process;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class CopySiteCommand extends CommandBase{

    use WordPressTrait;

    public function getName(){
        return 'copy-site';
    }

    protected function configure(){
        $this->addArgument('from',InputArgument::REQUIRED,'Which domain you wish to clone');
        $this->addArgument('to',InputArgument::REQUIRED,'Destination domain to clone site to');
    }


    protected function exec(){

        $args = $this->in->getArguments();
        $base_dir_from = "/home/fgmed_wm/web/$args[from]/public_html/";
        $base_dir_to = "/home/fgmed_wm/web/$args[to]/public_html/";

        $config_from = $this->extractConfig($base_dir_from);
        $config_to = $this->extractConfig($base_dir_to);
        $cmd = 'mysqldump -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME"';
        $p = Process::fromShellCommandLine($cmd);
        $p->run(null,(array)$config_from);
        $dump = str_replace("fgmed.org","stdev.fgmed.org",$p->getOutput());
        file_put_contents($dumpf="/dev/shm/dump.sql", $dump);
        $cmd = 'mysql -u "$DB_USER" -f -p"$DB_PASSWORD" "$DB_NAME" < '.$dumpf;
        $p = Process::fromShellCommandLine($cmd);
        $p->run(null,(array)$config_to);

        Process::fromShellCommandLine('rm "$path_to" -Rf; cp "$path_from" "$path_to" -R')
        ->run(null,[
            'path_to' => $base_dir_to."wp-content/",
            'path_from' => $base_dir_from."wp-content/"
        ]);
        return 0;
    }

}
