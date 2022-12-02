<?php

namespace EVFTest;

use PHPUnit\Framework\TestCase;

class BaseTestCase extends TestCase
{
    protected function log($txt)
    {
        global $evflogger;
        $evflogger->log($txt);
    }

    protected function dump($obj)
    {
        $this->log(print_r($obj, true));
    }

    protected function dbLog()
    {
        global $DB;
        $output = $DB->queryLog;
        $DB->queryLog = array();
        return $output;
    }

    protected function onDb()
    {
        global $DB;
        return $DB;
    }

    public function tearDown(): void
    {
        $this->dbLog(); // clear the log
    }
}
