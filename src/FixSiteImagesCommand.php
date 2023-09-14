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
        dd($conf);
        $row  = $conn->executeQuery("
            SELECT post_content FROM $conf[table_prefix]posts
            WHERE post_content LIKE '%CHULESCO%'
        ")->fetchAll();
        dd($row);
        $urls = $conn->executeQuery("
            SELECT meta_value FROM $conf[table_prefix]postmeta
            WHERE meta_value LIKE '%webp'
        ")->fetchAll(\PDO::FETCH_COLUMN);
        dd($urls);
        $urls = $conn->executeQuery("
            SELECT meta_value FROM $conf[table_prefix]postmeta
            WHERE meta_value LIKE '%jpg' OR meta_value LIKE '%png'
        ")->fetchAll(\PDO::FETCH_COLUMN);
        $upload_dir = $base_dir."wp-content/uploads/";
        foreach($urls as $url){
            $url_prefix = preg_replace("/\.(jpg|png)$/","",$url);
            $file = $upload_dir.$url;
            if(!file_exists($file)){
                continue;
            }
            $file_prefix = $upload_dir.$url_prefix;
            foreach(glob($file_prefix.'*') as $variant){
                $ext = pathinfo($variant, PATHINFO_EXTENSION);
                $variant_prefix = str_replace('.'.$ext,'', $variant);
                $webp = $variant_prefix . '.webp';
                $this->out->writeln($webp);
                (new Process($cmd = ["convert",$variant,$webp]))->run();
                unlink($variant);
            }
            if(file_exists($file_prefix.'.webp')){
                $new_url = $url_prefix.'.webp';
                $query = "
                    UPDATE $conf[table_prefix]postmeta
                        SET meta_value='$new_url'
                        WHERE meta_value='$url'
                ";
                $this->out->writeln("Executing query: $query");
                $conn->executeQuery($query);
            }
        }
        return 0;
    }

}
