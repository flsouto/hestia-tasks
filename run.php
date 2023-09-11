<?php

require_once(__DIR__."/vendor/autoload.php");
use Symfony\Component\Console\Application;
use Symfony\Component\Finder\Finder;

$app = new Application();

$finder = new Finder();
$files = $finder->files()->in(__DIR__."/src")->name("*Command.php");
foreach($files as $f){
    $cmd = "\\FGTasks\\".$f->getFilenameWithoutExtension();
    $app->add(new $cmd);
}
$app->run();
