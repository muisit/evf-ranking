<?php

namespace EVFTest;

class Test_Policy2 extends BaseTest
{
    public $disabled = false;

    public function init()
    {
    }

    public function test_policy_combinations()
    {
        $cases = array(
            "fencers" => array(false, false, "rank", "rank", false, false),
            "events" => array(false, false, "rank", "rank", false, false),
            "results" => array(true, true, "rank", "rank", "rank", false),
            "ranking" => array(true, true, "rank", "rank", "rank", false),
            "competitions" => array(true, true, "rank", "rank", false, false),
            "eventroles" => array("rank", "rank", "rank", "rank", false, false),
            "registrars" => array("rank", "rank", "rank", "rank", false, false),
            "weapons" => array(true, "rank", "rank", "rank", false, false),
            "categories" => array(true, "rank", "rank", "rank", false, false),
            "types" => array(true, "rank", "rank", "rank", false, false),
            "roles" => array(true, "rank", "rank", "rank", false, false),
            "roletypes" => array(true, "rank", "rank", "rank", false, false),
            "users" => array("rank", "rank", false, false, false, false),
            "posts" => array("rank", "rank", false, false, false, false),
            "nosuchcapa" => array(false, false, false, false, false, false)
        );

        $pol = new TestPol();
        $actions = array("list", "view", "save", "delete", "misc", "nosuchaction");
        foreach ($cases as $key => $expected) {
            for ($i = 0; $i < sizeof($actions); $i++) {
                $action = $actions[$i];
                $exp = $expected[$i];

                $pol->base = "";
                $res = $pol->check($key, $action, array('test' => 1));
                $msg = "test case $key/$action expects " . ($exp === false ? "FALSE" : ($exp === true ? "TRUE" : $exp)) . ' and received ' . ($res === false ? "FALSE" : ($pol->base === "" ? "TRUE" : $pol->base));
                if ($res === true) {
                    // check the actual base that was checked
                    if ($exp === true) {
                        $this->assert($pol->base === "", $msg);
                    }
                    else {
                        $this->assert($pol->base === $exp, $msg);
                    }
                }
                else {
                    $this->assert($res === $exp, $msg);
                }
            }
        }
    }
}

class TestPol extends \EVFRanking\Lib\Policy
{
    public $base = "";
    protected function hasCapa($base)
    {
        $this->base = $base;
        return true;
    }
}
