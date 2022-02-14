<?php

$verbose=false;
$namedtests=array();
$nextisnamed=false;
foreach($argv as $arg) {
    if($nextisnamed) {
        $namedtests[]=$arg;
        $nextisnamed=false;
    }

    if ($arg == "-v" || $arg == "--verbose") $verbose = true;
    if ($arg == "-s" || $arg == "--silent") $verbose = false;
    if ($arg=="-n" || $arg == "--named") $nextisnamed=true;
}

function loadClassFile($filename) {
    $classes = get_declared_classes();
    require_once($filename);
    $diff = array_diff(get_declared_classes(), $classes);
    $class = reset($diff);
    if (!empty($class)) {
        $model = new $class();
        $base = basename($filename, ".php");
        $model->name = $base;
        return $model;
    }
    return null;
}

function evftest_autoloader( $name ) {
    if(!strncmp($name,'EVFRanking\\',11)) {
        $elements = explode('\\', strtolower($name));
        // require at least EVFRanking\<sub>\<name>, so 3 elements
        if(sizeof($elements) > 2 && $elements[0] == "evfranking") {
            $fname = $elements[sizeof($elements)-1] . ".php";
            $dir = implode("/",array_splice($elements,1,-1)); // remove the evfranking part
            if(file_exists(__DIR__."/../".$dir ."/".$fname)) {
                include(__DIR__."/../".$dir."/".$fname);
            }
        }
    }
}

spl_autoload_register('evftest_autoloader');
// load the PDF implementation upfront, so we can subclass its main class
require_once('../../ext-libraries/libraries/tcpdf/tcpdf.php');
require_once("base.php");

// run all tests in this directory
$tstdir = dirname(__FILE__) .'/tests';
$objects = scandir($tstdir);
$alltests = array();
foreach ($objects as $filename) {
    $path = $tstdir . "/" . $filename;
    $ext = $pi = pathinfo($path, PATHINFO_EXTENSION);
    if ($filename != '.' && $filename != '..' && $ext == "php" && is_file($path)) {
        $model = loadClassFile($path);
        if (!empty($model) && method_exists($model,"run") && (!isset($model->disabled) || !$model->disabled)) {
            $alltests[$model->name . uniqid()]=$model;
        }
    }
}

sort($alltests);

$numtests=0;
$success=0;
$fails=0;
foreach ($alltests as $key=>$model) {
    if(empty($namedtests) || in_array($model->name,$namedtests)) {
        echo "Running tests for ".$model->name."\r\n";
        $model->run();
        echo "Tests: ".$model->count." Success: ".$model->success." Fails: ".$model->fails."\r\n";
        $success+=$model->success;
        $fails+=$model->fails;
        $numtests += $model->count;
    }
}

echo "\r\nEnd of testing.\r\nTotal tests: $numtests\r\nSuccess: $success\r\nFails: $fails\r\n";
