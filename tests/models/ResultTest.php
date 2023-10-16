<?php

namespace tests\models;

use \EVFRanking\Models\Result;

class ResultTest extends \EVFTest\BaseTestCase
{
    public function testCalculation()
    {
        $result = new Result();
        $this->assertEquals(60, $result->calculateDEPoints(1, 64));
        $this->assertEquals(60, $result->calculateDEPoints(1, 63));
        $this->assertEquals(70, $result->calculateDEPoints(1, 65));

        $this->assertEquals(50, $result->calculateDEPoints(2, 64));
        $this->assertEquals(50, $result->calculateDEPoints(2, 63));
        $this->assertEquals(60, $result->calculateDEPoints(2, 65));

        $this->assertEquals(10, $result->calculateDEPoints(32, 64));
        $this->assertEquals(10, $result->calculateDEPoints(32, 63));
        $this->assertEquals(20, $result->calculateDEPoints(32, 65));

        $this->assertEquals(0, $result->calculateDEPoints(64, 64));
        $this->assertEquals(0, $result->calculateDEPoints(63, 63));
        $this->assertEquals(10, $result->calculateDEPoints(64, 65));

        $this->assertEquals(70, $result->calculateDEPoints(1, 128));
        $this->assertEquals(70, $result->calculateDEPoints(1, 127));
        $this->assertEquals(80, $result->calculateDEPoints(1, 129));

        $this->assertEquals(80, $result->calculateDEPoints(1, 256));
        $this->assertEquals(80, $result->calculateDEPoints(1, 255));
        $this->assertEquals(90, $result->calculateDEPoints(1, 257));
    }
}
