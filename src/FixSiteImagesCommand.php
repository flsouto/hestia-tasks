<?php

namespace FGTasks;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Process\Process;

class FixSiteImagesCommand extends CommandBase{

    use WordPressTrait;

    function getName(){
        return 'fix-site-images';
    }

    function configure(){
        $this->addArgument('domain',InputArgument::REQUIRED,'Which domain you wish to fix images');
    }


    function exec(){
        $domain = $this->in->getArgument('domain');
        $base_dir = "/home/fgmed_wm/web/$domain/public_html/";
        $conf = $this->extractConfig($base_dir, []);
        $conn = $conf->getDbConnection();
        $count = $conn->fetchOne("SELECT COUNT(*) FROM $conf[table_prefix]metadata;");
        dd($count);
        $finder = new Finder();
        $files = $finder->files()->in($base_dir."*/*/*/")->name("*.jpg");
        foreach($files as $f){
            $webp = $f->getPath().$f->getFilenameWithoutExtension().".webp";
            if(file_exists($webp)){
                continue;
            }
            $this->out->writeln("Converting $webp");
            (new Process($cmd = ["convert","$f","$webp"]))->run();
        }
        die();
    }

}
