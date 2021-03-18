<?php

$verbose=false;
foreach($argv as $arg) {
    if ($arg == "-v" || $arg == "--verbose") $verbose = true;
    if ($arg == "-s" || $arg == "--silent") $verbose = false;
}

function loadClassFile($filename)
{
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

require_once("base.php");
// run all tests in this directory
$tstdir = dirname(__FILE__) .'/tests';
$objects = scandir($tstdir);
$alltests = array();
foreach ($objects as $filename) {
    $path = $tstdir . "/" . $filename;
    if ($filename != '.' && $filename != '..' && is_file($path)) {
        $model = loadClassFile($path);
        if (!empty($model) && method_exists($model,"run")) {
            $alltests[$model->title . uniqid()]=$model;
        }
    }
}

sort($alltests);

$numtests=0;
$success=0;
$fails=0;
foreach ($alltests as $key=>$model) {
    echo "Running tests for ".$model->title."\r\n";
    $model->run();
    echo "Tests: ".$model->count." Success: ".$model->success." Fails: ".$model->fails."\r\n";
    $success+=$model->success;
    $fails+=$model->fails;
    $numtests += $model->count;
}

echo "\r\nEnd of testing.\r\nTotal tests: $numtests\r\nSuccess: $success\r\nFails: $fails\r\n";
