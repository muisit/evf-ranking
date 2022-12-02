<?php

namespace {

    function evftest_autoloader($name)
    {
        if (!strncmp($name, 'EVFRanking\\', 11)) {
            $elements = explode('\\', strtolower($name));
            // require at least EVFRanking\<sub>\<name>, so 3 elements
            if (sizeof($elements) > 2 && $elements[0] == "evfranking") {
                $fname = $elements[sizeof($elements) - 1] . ".php";
                $dir = implode("/", array_splice($elements, 1, -1)); // remove the evfranking part
                if (file_exists(__DIR__ . "/../" . $dir . "/" . $fname)) {
                    include(__DIR__ . "/../" . $dir . "/" . $fname);
                }
            }
        }
        if (!strncmp($name, 'Fixtures\\', 9)) {
            $elements = explode('\\', strtolower($name));
            if (sizeof($elements) > 1 && $elements[0] == "fixtures") {
                $fname = $elements[sizeof($elements) - 1] . ".php";
                $dir = '/fixtures/';
                if (file_exists(__DIR__ . $dir . $fname)) {
                    include(__DIR__ . $dir . $fname);
                }
            }
        }
    }
    
    spl_autoload_register('evftest_autoloader');

    // load the PDF implementation upfront, so we can subclass its main class
    require_once('../ext-libraries/libraries/tcpdf/tcpdf.php');

    class TestLogger
    {
        public function log($txt)
        {
            fwrite(STDERR, $txt . "\r\n");
            //echo $txt . "\r\n";
        }
    }
    global $evflogger;
    $evflogger = new TestLogger();

    global $wp_current_user;
    $wp_current_user = 1;

    require_once('preload/mockwp.php');

    require_once('preload/mockdb.php');
    global $wpdb;
    $wpdb = new \EVFTest\MockDBClass();
    
    require_once('preload/testdb.php');
    global $DB;
    $DB = new \EVFTest\TestDatabase();

    require_once('preload/BaseTestCase.php');
}
