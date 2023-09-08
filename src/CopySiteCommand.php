<?php
namespace FGTasks;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Process\Process;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class CopySiteCommand extends CommandBase{


    public function getName(){
        return 'copy-site';
    }

    protected function configure(){
        $this->addArgument('from',InputArgument::REQUIRED,'Which domain you wish to clone');
        $this->addArgument('to',InputArgument::REQUIRED,'Destination domain to clone site to');
    }

    function extractConstants($code){
        preg_match_all("/define\(\s*(.*?)\s*,\s*(.*?)\s*\)/",$code,$matches);
        $arr = [];
        $unquote = function($v){
            return trim(preg_replace("/(^['\"])|(['\"]$)/", "",$v));
        };
        foreach($matches[1] as $i => $k){
            $arr[$unquote($k)] = $unquote($matches[2][$i]);
        }
        return $arr;
    }

    function extractConfig($base_dir, $extract_keys=['DB_NAME','DB_USER','DB_PASSWORD']){
        if(!file_exists($f = $base_dir."/wp-config.php")){
            $this->out->writeln("<error>wp-config not found in $base_dir</error>");
            return self::FAILURE;
        }
        $config = file_get_contents($f);
        $config = $this->extractConstants($config);
        $result = [];
        foreach($extract_keys as $k){
            if(!isset($config[$k])){
                $this->error("Required key '$k' not found in '$f'");
                return;
            } else {
                $result[$k] = $config[$k];
            }
        }
        return $result;
    }

    protected function exec(){

        $args = $this->in->getArguments();
        $base_dir_from = "/home/fgmed_wm/web/$args[from]/public_html/";
        $base_dir_to = "/home/fgmed_wm/web/$args[to]/public_html/";

        $config_from = $this->extractConfig($base_dir_from);
        $config_to = $this->extractConfig($base_dir_to);
        $cmd = 'mysqldump -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME"';
        $p = Process::fromShellCommandLine($cmd);
        $p->run(null,$config_from);
        $dump = str_replace("fgmed.org","stdev.fgmed.org",$p->getOutput());
        file_put_contents($dumpf="/dev/shm/dump.sql", $dump);
        $cmd = 'mysql -u "$DB_USER" -f -p"$DB_PASSWORD" "$DB_NAME" < '.$dumpf;
        $p = Process::fromShellCommandLine($cmd);
        $p->run(null,$config_to);

        Process::fromShellCommandLine('rm "$path_to" -Rf; cp "$path_from" "$path_to" -R')
        ->run(null,[
            'path_to' => $base_dir_to."wp-content/",
            'path_from' => $base_dir_from."wp-content/"
        ]);
        return 0;
    }

}
