<?php

namespace FGTasks;
use Doctrine\DBAL\DriverManager;

trait WordPressTrait{

    function extractConstants($code){
        $regex = '(?:define\(\s*(.*?)\s*,\s*(.*?)\s*\))';
        preg_match_all("/$regex/",$code,$matches);
        $arr = [];
        $unquote = function($v){
            return trim(preg_replace("/(^['\"])|(['\"]$)/", "",$v));
        };
        foreach($matches[1] as $i => $k){
            $arr[$unquote($k)] = $unquote($matches[2][$i]);
        }
        return $arr;
    }

    function extractVars($code, $vars = ['table_prefix']){
        $regex = "(".implode("|",$vars).")\s*[=]\s*'(.*?)'";
        preg_match_all("/$regex/", $code, $matches);
        $arr = [];
        $unquote = function($v){
            return trim(preg_replace("/(^['\"])|(['\"]$)/", "",$v));
        };
        foreach($matches[1] as $i => $k){
            $arr[$unquote($k)] = $unquote($matches[2][$i]);
        }
        return $arr;
    }

    function extractConfig($base_dir, $extract_keys=['DB_NAME','DB_USER','DB_PASSWORD','table_prefix']){
        if(!file_exists($f = $base_dir."/wp-config.php")){
            $this->out->writeln("<error>wp-config not found in $base_dir</error>");
            return self::FAILURE;
        }
        $code = file_get_contents($f);
        $config = $this->extractConstants($code);
        $config+= $this->extractVars($code);
        if(!$extract_keys){
            $result = $config;
        } else {
            $result = [];
            foreach($extract_keys as $k){
                if(!isset($config[$k])){
                    $this->error("Required key '$k' not found in '$f'");
                    return;
                } else {
                    $result[$k] = $config[$k];
                }
            }
        }

        return new class($result) extends \ArrayObject{
            function getDbConnection(){
                return DriverManager::getConnection([
                    'dbname' => $this['DB_NAME'],
                    'user' => $this['DB_USER'],
                    'password' => $this['DB_PASSWORD'],
                    'driver' => 'pdo_mysql',
                    'host' => 'localhost'
                ]);
            }
        };
    }

}

