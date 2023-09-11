<?php

namespace FGTasks;

trait WordPressTrait{

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

}
