<?php

namespace EVFTest;

class Test_Policy extends BaseTest {

    public function init() {
        parent::init();
        $this->init_admin();
    }

    public function test_policy_fencers() {
        $this->checkPolicy("fencers",array(
            TRUE, TRUE, TRUE, TRUE, FALSE, FALSE,
            TRUE, TRUE, TRUE, TRUE, FALSE, FALSE,
            TRUE, TRUE, TRUE, FALSE, FALSE, FALSE,
            TRUE, TRUE, FALSE, FALSE, FALSE, FALSE,
            TRUE, TRUE, FALSE, FALSE, FALSE, FALSE,
        ));
    }

    public function test_policy_events() {
        $this->checkPolicy("events", array(
            TRUE, TRUE, TRUE, TRUE, FALSE, FALSE,
            TRUE, TRUE, TRUE, TRUE, FALSE, FALSE,
            TRUE, TRUE, TRUE, FALSE, FALSE, FALSE,
            TRUE, TRUE, FALSE, FALSE, FALSE, FALSE,
            TRUE, TRUE, FALSE, FALSE, FALSE, FALSE,
        ));
    }

    public function test_policy_results() {
        $this->checkPolicy("results", array(
            TRUE, TRUE, TRUE, TRUE, TRUE, FALSE,
            TRUE, TRUE, TRUE, TRUE, TRUE, FALSE,
            TRUE, TRUE, FALSE, FALSE, FALSE, FALSE,
            TRUE, TRUE, FALSE, FALSE, FALSE, FALSE,
            TRUE, TRUE, FALSE, FALSE, FALSE, FALSE,
        ));
    }

    public function test_policy_ranking() {
        $this->checkPolicy("ranking", array(
            TRUE, TRUE, TRUE, TRUE, FALSE, FALSE,
            TRUE, TRUE, TRUE, TRUE, FALSE, FALSE,
            TRUE, TRUE, FALSE, FALSE, FALSE, FALSE,
            TRUE, TRUE, FALSE, FALSE, FALSE, FALSE,
            TRUE, TRUE, FALSE, FALSE, FALSE, FALSE,
        ));
    }
    public function test_policy_competitions() {
        $this->checkPolicy("competitions", array(
            TRUE, TRUE, TRUE, TRUE, FALSE, FALSE,
            TRUE, TRUE, TRUE, TRUE, FALSE, FALSE,
            TRUE, TRUE, FALSE, FALSE, FALSE, FALSE,
            TRUE, TRUE, FALSE, FALSE, FALSE, FALSE,
            TRUE, TRUE, FALSE, FALSE, FALSE, FALSE,
        ));
    }
    public function test_policy_sides()
    {
        $this->checkPolicy("sides", array(
            TRUE, TRUE, TRUE, TRUE, FALSE, FALSE,
            TRUE, TRUE, TRUE, TRUE, FALSE, FALSE,
            TRUE, TRUE, TRUE, TRUE, FALSE, FALSE,
            TRUE, TRUE, FALSE, FALSE, FALSE, FALSE,
            TRUE, TRUE, FALSE, FALSE, FALSE, FALSE,
        ));
    }
    public function test_policy_eventroles()
    {
        $this->checkPolicy("eventroles", array(
            TRUE, TRUE, TRUE, TRUE, FALSE, FALSE,
            TRUE, TRUE, TRUE, TRUE, FALSE, FALSE,
            TRUE, TRUE, TRUE, TRUE, FALSE, FALSE,
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE,
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE,
        ));
    }
    public function test_policy_registrars()
    {
        $this->checkPolicy("registrars", array(
            TRUE, TRUE, TRUE, TRUE, FALSE, FALSE,
            TRUE, TRUE, TRUE, TRUE, FALSE, FALSE,
            TRUE, TRUE, TRUE, TRUE, FALSE, FALSE,
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE,
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE,
        ));
    }
    public function test_policy_weapons()
    {
        $this->checkPolicy("weapons", array(
            TRUE, TRUE, TRUE, TRUE, FALSE, FALSE,
            TRUE, TRUE, TRUE, TRUE, FALSE, FALSE,
            TRUE, FALSE, FALSE, FALSE, FALSE, FALSE,
            TRUE, FALSE, FALSE, FALSE, FALSE, FALSE,
            TRUE, FALSE, FALSE, FALSE, FALSE, FALSE,
        ));
    }
    public function test_policy_categories()
    {
        $this->checkPolicy("categories", array(
            TRUE, TRUE, TRUE, TRUE, FALSE, FALSE,
            TRUE, TRUE, TRUE, TRUE, FALSE, FALSE,
            TRUE, FALSE, FALSE, FALSE, FALSE, FALSE,
            TRUE, FALSE, FALSE, FALSE, FALSE, FALSE,
            TRUE, FALSE, FALSE, FALSE, FALSE, FALSE,
        ));
    }
    public function test_policy_countries()
    {
        $this->checkPolicy("countries", array(
            TRUE, TRUE, TRUE, TRUE, FALSE, FALSE,
            TRUE, TRUE, TRUE, TRUE, FALSE, FALSE,
            TRUE, FALSE, FALSE, FALSE, FALSE, FALSE,
            TRUE, FALSE, FALSE, FALSE, FALSE, FALSE,
            TRUE, FALSE, FALSE, FALSE, FALSE, FALSE,
        ));
    }
    public function test_policy_types()
    {
        $this->checkPolicy("types", array(
            TRUE, TRUE, TRUE, TRUE, FALSE, FALSE,
            TRUE, TRUE, TRUE, TRUE, FALSE, FALSE,
            TRUE, FALSE, FALSE, FALSE, FALSE, FALSE,
            TRUE, FALSE, FALSE, FALSE, FALSE, FALSE,
            TRUE, FALSE, FALSE, FALSE, FALSE, FALSE,
        ));
    }
    public function test_policy_roles()
    {
        $this->checkPolicy("roles", array(
            TRUE, TRUE, TRUE, TRUE, FALSE, FALSE,
            TRUE, TRUE, TRUE, TRUE, FALSE, FALSE,
            TRUE, FALSE, FALSE, FALSE, FALSE, FALSE,
            TRUE, FALSE, FALSE, FALSE, FALSE, FALSE,
            TRUE, FALSE, FALSE, FALSE, FALSE, FALSE,
        ));
    }
    public function test_policy_roletypes()
    {
        $this->checkPolicy("roletypes", array(
            TRUE, TRUE, TRUE, TRUE, FALSE, FALSE,
            TRUE, TRUE, TRUE, TRUE, FALSE, FALSE,
            TRUE, FALSE, FALSE, FALSE, FALSE, FALSE,
            TRUE, FALSE, FALSE, FALSE, FALSE, FALSE,
            TRUE, FALSE, FALSE, FALSE, FALSE, FALSE,
        ));
    }
    public function test_policy_users()
    {
        $this->checkPolicy("users", array(
            TRUE, TRUE, FALSE, FALSE, FALSE, FALSE,
            TRUE, TRUE, FALSE, FALSE, FALSE, FALSE,
            TRUE, FALSE, FALSE, FALSE, FALSE, FALSE,
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE,
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE,
        ));
    }
    public function test_policy_posts()
    {
        $this->checkPolicy("posts", array(
            TRUE, TRUE, FALSE, FALSE, FALSE, FALSE,
            TRUE, TRUE, FALSE, FALSE, FALSE, FALSE,
            TRUE, FALSE, FALSE, FALSE, FALSE, FALSE,
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE,
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE,
        ));
    }
    public function test_policy_migrations()
    {
        $this->checkPolicy("migrations", array(
            TRUE, TRUE, TRUE, FALSE, FALSE, FALSE,
            TRUE, TRUE, TRUE, FALSE, FALSE, FALSE,
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE,
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE,
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE,
        ));
    }

    public function test_unknown_policies() 
    {
        $policynames=array(
            // singular versions of the policy names, to be sure
            "fencer","event","result","rank","competition","side","eventrole",
            "weapon","category","country","type","role","roletype","user",
            "post","migration",
            // special names wrt application development
            "table","index","auth","authenticate","service","authorize",
            "authorization","database","data","model","serve","server",
            "script","php","<?php","column","row","\$_GET"
        );
        global $verbose;
        foreach($policynames as $p) {
            if($verbose) echo "Testing policy $p\r\n";
            $this->checkPolicy($p,array(
                FALSE, FALSE, FALSE, FALSE, FALSE, FALSE,
                FALSE, FALSE, FALSE, FALSE, FALSE, FALSE,
                FALSE, FALSE, FALSE, FALSE, FALSE, FALSE,
                FALSE, FALSE, FALSE, FALSE, FALSE, FALSE,
                FALSE, FALSE, FALSE, FALSE, FALSE, FALSE,
            ));
        }
    }

    public function test_registration_policy() {
        $this->setup_registration_db();
        $policy = $this->loadPolicy();
        $users = array(
            // No-role Cashier  Accreditation   Registrar   Organiser   HoD superHoD
                1,      2,      3,              4,          5,          6,   7,     // admins
                11,     12,     13,             14,         15,         16,  17,    // ranking
                21,     22,     23,             24,         25,         26,  27,    // reg
                31,     32,     33,             34,         35,         36,  37,    // no-priv
                1024                                                                // unknown
        );

        // missing side-event
        $this->subtest_registration_policy("case 1", $policy, $users, array("list", "view", "save", "delete", "misc", "nosuch"), array(
            "model" => array(
                "event" => 1
            ),
            "filter" => array(
                "event" => 1,
            )
        ), array( // matches users above
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 1...
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 1...
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 1...
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 1...
            FALSE                                            // 1
        ));

        // missing event
        $this->subtest_registration_policy("case 2",$policy, $users, array("list", "view", "save", "delete", "misc", "nosuch"), array(
            "model" => array(
                "sideevent" => 1
            ),
            "filter" => array(
            )
        ), array( // matches users above
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 2...
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 2...
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 2...
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 2...
            FALSE                                            // 2
        ));

        // missing model
        $this->subtest_registration_policy("case 3",$policy, $users, array("list", "view", "save", "delete", "misc", "nosuch"), array(
            "model" => array(
                "event" => 100
            ),
            "filter" => array(
                "event" => 2,
                "sideevent" => 1
            )
        ), array( // matches users above
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 3...
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 3...
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 3...
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 3...
            FALSE                                            // 3
        ));

        // missing side event does not match event
        $this->subtest_registration_policy("case 4",$policy, $users, array("list", "view", "save", "delete", "misc", "nosuch"), array(
            "model" => array(
                "sideevent" => 3
            ),
            "filter" => array(
                "event" => 1
            )
        ), array( // matches users above
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 4...
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 4...
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 4...
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 4...
            FALSE                                            // 4
        ));

        // no event in filter
        $this->subtest_registration_policy("case 5",$policy, $users, array("list", "view", "save", "delete", "misc", "nosuch"), array(
            "model" => array(
                "sideevent" => 1,
                "event" => 1
            ),
            "filter" => array()
        ), array( // matches users above
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 5...
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 5...
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 5...
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 5...
            FALSE                                            // 5
        ));

        // filter incorrect
        $this->subtest_registration_policy("case 6", $policy, $users, array("list", "view", "save", "delete", "misc", "nosuch"), array(
            "model" => array(
                "event" => 1
            ),
            "filter" => array(
                "event" => 2, // event does not match up
                "sideevent" => 1
            )
        ), array( // matches users above
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 6...
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 6...
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 6...
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 6...
            FALSE                                            // 6
        ));

        // invalid action
        $this->subtest_registration_policy("case 7", $policy, $users, array("view", "misc", "nosuch"), array(
            "model" => array(
                "event" => 1
            ),
            "filter" => array(
                "event" => 1, // event does not match up
                "sideevent" => 1
            )
        ), array( // matches users above
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 7...
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 7...
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 7...
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 7...
            FALSE                                            // 7
        ));


        $this->subtest_registration_policy("case 8", $policy, $users, "list", array(
            "model" => array(
                "sideevent" => 1,
                "event" => 1
            ),
            "filter" => array(
                "event" => 1
            )
        ), array( // matches users above (no, cash, accr, reg, org, hod, superhod)
            FALSE, TRUE, TRUE, TRUE, TRUE, FALSE, TRUE, // 8 - 21 - 21 - 21 - 9 - 11 - 10
            FALSE, TRUE, TRUE, TRUE, TRUE, FALSE, TRUE, // 8 - 21 - 21 - 21 - 9 - 11 - 10
            FALSE, TRUE, TRUE, TRUE, TRUE, FALSE, TRUE, // 8 - 21 - 21 - 21 - 9 - 11 - 10
            FALSE, TRUE, TRUE, TRUE, TRUE, FALSE, TRUE, // 8 - 21 - 21 - 21 - 9 - 11 - 10
            FALSE                                       // 8
        ));

        $this->subtest_registration_policy("case 9", $policy, $users, "list", array(
            "model" => array(
                "event" => 1
            ),
            "filter" => array(
                "event" => 1,
                "sideevent" => 1,
                "country" => 1
            )
        ), array( // matches users above
            FALSE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, // 8 - 21 - 21 - 21 - 9 - 12 - 10
            FALSE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, // 8 - 21 - 21 - 21 - 9 - 12 - 10
            FALSE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, // 8 - 21 - 21 - 21 - 9 - 12 - 10
            FALSE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, // 8 - 21 - 21 - 21 - 9 - 12 - 10
            FALSE                                      // 8
        ));

        // country does not match HoD country
        $this->subtest_registration_policy("case 10", $policy, $users, "list", array(
            "model" => array(
                "event" => 1
            ),
            "filter" => array(
                "event" => 1,
                "sideevent" => 1,
                "country" => 2
            )
        ), array( // matches users above
            FALSE, TRUE, TRUE, TRUE, TRUE, FALSE, TRUE, // 8 - 21 - 21 - 21 - 21 - 20 - 10
            FALSE, TRUE, TRUE, TRUE, TRUE, FALSE, TRUE, // 8 - 21 - 21 - 21 - 21 - 20 - 10
            FALSE, TRUE, TRUE, TRUE, TRUE, FALSE, TRUE, // 8 - 21 - 21 - 21 - 21 - 20 - 10
            FALSE, TRUE, TRUE, TRUE, TRUE, FALSE, TRUE, // 8 - 21 - 21 - 21 - 21 - 20 - 10
            FALSE                                      // 8
        ));

        $this->subtest_registration_policy("case 11", $policy, $users, "save", array(
            "model" => array(
                "event" => 1
            ),
            "filter" => array(
                "event" => 1,
                "sideevent" => 1
            )
        ), array( // matches users above (no, cash, accr, reg, org, hod, superhod)
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 8 - 22 - 13 - 22 - 22 - 22 - 22
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 8 - 22 - 13 - 22 - 22 - 22 - 22
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 8 - 22 - 13 - 22 - 22 - 22 - 22
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 8 - 22 - 13 - 22 - 22 - 22 - 22
            FALSE                                            // 8
        ));

        // invalid fencer
        $this->subtest_registration_policy("case 12", $policy, $users, "save", array(
            "model" => array(
                "event" => 1,
                "fencer" => array(
                    "id" => 3
                )
            ),
            "filter" => array(
                "event" => 1,
                "sideevent" => 1
            )
        ), array( // matches users above (no, cash, accr, reg, org, hod, superhod)
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 8 - 14 - 13 - 14 - 14 - 14 - 14
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 8 - 14 - 13 - 14 - 14 - 14 - 14
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 8 - 14 - 13 - 14 - 14 - 14 - 14
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 8 - 14 - 13 - 14 - 14 - 14 - 14
            FALSE                                            // 8
        ));

        // fencer country does not match HoD country
        $this->subtest_registration_policy("case 13", $policy, $users, "save", array(
            "model" => array(
                "event" => 1,
                "fencer" => 2
            ),
            "filter" => array(
                "event" => 1,
                "sideevent" => 1
            )
        ), array( // matches users above (no, cash, accr, reg, org, hod, superhod)
            FALSE, TRUE, FALSE, TRUE, TRUE, FALSE, TRUE, // 8 - 23 - 13 - 23 - 23 - 20 - 24
            FALSE, TRUE, FALSE, TRUE, TRUE, FALSE, TRUE, // 8 - 23 - 13 - 23 - 23 - 20 - 24
            FALSE, TRUE, FALSE, TRUE, TRUE, FALSE, TRUE, // 8 - 23 - 13 - 23 - 23 - 20 - 24
            FALSE, TRUE, FALSE, TRUE, TRUE, FALSE, TRUE, // 8 - 23 - 13 - 23 - 23 - 20 - 24
            FALSE                                        // 8
        ));

        $this->subtest_registration_policy("case 14", $policy, $users, "save", array(
            "model" => array(
                "event" => 1,
                "fencer" => 1
            ),
            "filter" => array(
                "event" => 1,
                "sideevent" => 1
            )
        ), array( // matches users above (no, cash, accr, reg, org, hod, superhod)
            FALSE, TRUE, FALSE, TRUE, TRUE, TRUE, TRUE, // 8 - 23 - 13 - 23 - 23 - 15 - 24
            FALSE, TRUE, FALSE, TRUE, TRUE, TRUE, TRUE, // 8 - 23 - 13 - 23 - 23 - 15 - 24
            FALSE, TRUE, FALSE, TRUE, TRUE, TRUE, TRUE, // 8 - 23 - 13 - 23 - 23 - 15 - 24
            FALSE, TRUE, FALSE, TRUE, TRUE, TRUE, TRUE, // 8 - 23 - 13 - 23 - 23 - 15 - 24
            FALSE                                       // 8
        ));

        // no registration set
        $this->subtest_registration_policy("case 14b", $policy, $users, "delete", array(
            "model" => array(
                "event" => 1,
            ),
            "filter" => array(
                "event" => 1,
                "sideevent" => 1
            )
        ), array( // matches users above (no, cash, accr, reg, org, hod, superhod)
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 8 - 16 - 16 - 25 - 25 - 25 - 25
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 8 - 16 - 16 - 25 - 25 - 25 - 25
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 8 - 16 - 16 - 25 - 25 - 25 - 25
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 8 - 16 - 16 - 25 - 25 - 25 - 25
            FALSE                                        // 8
        ));

        // invalid registration
        $this->subtest_registration_policy("case 15", $policy, $users, "delete", array(
            "model" => array(
                "event" => 1,
                "id" => 1000
            ),
            "filter" => array(
                "event" => 1,
                "sideevent" => 1
            )
        ), array( // matches users above (no, cash, accr, reg, org, hod, superhod)
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 8 - 16 - 16 - 17 - 17 - 17 - 17
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 8 - 16 - 16 - 17 - 17 - 17 - 17
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 8 - 16 - 16 - 17 - 17 - 17 - 17
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 8 - 16 - 16 - 17 - 17 - 17 - 17
            FALSE                                        // 8
        ));

        // registration for different event
        $this->subtest_registration_policy("case 16", $policy, $users, "delete", array(
            "model" => array(
                "event" => 1,
                "id" => 3
            ),
            "filter" => array(
                "event" => 1,
                "sideevent" => 1
            )
        ), array( // matches users above (no, cash, accr, reg, org, hod, superhod)
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 8 - 16 - 16 - 26 - 26 - 26 - 26
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 8 - 16 - 16 - 26 - 26 - 26 - 26
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 8 - 16 - 16 - 26 - 26 - 26 - 26
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 8 - 16 - 16 - 26 - 26 - 26 - 26
            FALSE                                        // 8
        ));

        // registration for non-existing fencer (database corruption)
        $this->subtest_registration_policy("case 17", $policy, $users, "delete", array(
            "model" => array(
                "event" => 1,
                "id" => 2
            ),
            "filter" => array(
                "event" => 1,
                "sideevent" => 1
            )
        ), array( // matches users above (no, cash, accr, reg, org, hod, superhod)
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 8 - 16 - 16 - 18 - 18 - 18 - 18
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 8 - 16 - 16 - 18 - 18 - 18 - 18
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 8 - 16 - 16 - 18 - 18 - 18 - 18
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // 8 - 16 - 16 - 18 - 18 - 18 - 18
            FALSE                                        // 8
        ));

        // registration for fencer of different country
        global $DB;
        $this->subtest_registration_policy("case 18", $policy, $users, "delete", array(
            "model" => array(
                "event" => 1,
                "id" => 4
            ),
            "filter" => array(
                "event" => 1,
                "sideevent" => 1
            )
        ), array( // matches users above (no, cash, accr, reg, org, hod, superhod)
            FALSE, FALSE, FALSE, TRUE, TRUE, FALSE, TRUE, // 8 - 16 - 16 - 27 - 27 - 20 - 28
            FALSE, FALSE, FALSE, TRUE, TRUE, FALSE, TRUE, // 8 - 16 - 16 - 27 - 27 - 20 - 28
            FALSE, FALSE, FALSE, TRUE, TRUE, FALSE, TRUE, // 8 - 16 - 16 - 27 - 27 - 20 - 28
            FALSE, FALSE, FALSE, TRUE, TRUE, FALSE, TRUE, // 8 - 16 - 16 - 27 - 27 - 20 - 28
            FALSE                                        // 8
        ));

        // registration okay
        $this->subtest_registration_policy("case 19", $policy, $users, "delete", array(
            "model" => array(
                "event" => 1,
                "id" => 1
            ),
            "filter" => array(
                "event" => 1,
                "sideevent" => 1
            )
        ), array( // matches users above (no, cash, accr, reg, org, hod, superhod)
            FALSE, FALSE, FALSE, TRUE, TRUE, TRUE, TRUE, // 8 - 16 - 16 - 27 - 27 - 19 - 28
            FALSE, FALSE, FALSE, TRUE, TRUE, TRUE, TRUE, // 8 - 16 - 16 - 27 - 27 - 19 - 28
            FALSE, FALSE, FALSE, TRUE, TRUE, TRUE, TRUE, // 8 - 16 - 16 - 27 - 27 - 19 - 28
            FALSE, FALSE, FALSE, TRUE, TRUE, TRUE, TRUE, // 8 - 16 - 16 - 27 - 27 - 19 - 28
            FALSE                                        // 8
        ));
    }

    private function subtest_registration_policy($cs, $policy, $users, $actions, $modeldata, $outcomes) {
        global $wp_current_user;
        if(!is_array($actions)) $actions=array($actions);
        $sz=sizeof($users);
        for($i=0;$i<$sz;$i++) {
            $wp_current_user = $users[$i];
            foreach($actions as $action) {
                global $evflogger;
                $evflogger->clear();
                $name = "registration policy $cs for $action $wp_current_user";
                $evflogger->log("start test $name expected ".($outcomes[$i] ? "TRUE":"FALSE"));
                $result = $policy->check("registration", $action, $modeldata);
                $this->assert($result == $outcomes[$i], $name);
            }
        }
    }

    private function checkPolicyForUser($policy, $for,$outcomes,$data, $cmt) {
        $result = $policy->check($for, "list", $data);
        $this->assert($result === $outcomes[0], "list $cmt");

        $result = $policy->check($for, "view", $data);
        $this->assert($result === $outcomes[1], "view $cmt");

        $result = $policy->check($for, "save", $data);
        $this->assert($result === $outcomes[2], "save $cmt");

        $result = $policy->check($for, "delete", $data);
        $this->assert($result === $outcomes[3], "delete $cmt");

        $result = $policy->check($for, "misc", $data);
        $this->assert($result === $outcomes[4], "misc $cmt");

        $result = $policy->check($for, "nosuchcapa", $data);
        $this->assert($result === $outcomes[5], "unknown $cmt");
    }

    private function checkPolicy($for, $outcomes,$data=null) {
        $policy = $this->loadPolicy();
        $this->init_admin();
        $this->checkPolicyForUser($policy,$for,array_slice($outcomes,0,6),$data,"admin");

        $this->init_ranking();
        $this->checkPolicyForUser($policy, $for, array_slice($outcomes, 6, 6), $data,"rank");

        $this->init_registrar();
        $this->checkPolicyForUser($policy, $for, array_slice($outcomes, 12, 6), $data,"reg");

        $this->init_unpriv();
        $this->checkPolicyForUser($policy, $for, array_slice($outcomes, 18, 6), $data, "unpriv");

        $this->init_anonymous();
        $this->checkPolicyForUser($policy, $for, array_slice($outcomes, 24, 6), $data, "anon");
    }

    private function setup_registration_db() {
        global $DB;
        $now = strftime("%Y-%m-%d");
        $in3months=strftime("%Y-%m-%d", time() + 92*24*60*60);
        $in1weeks = strftime("%Y-%m-%d", time() + 7 * 24 * 60 * 60);
        $in2weeks = strftime("%Y-%m-%d", time() + 14*24*60*60);
        $in3weeks = strftime("%Y-%m-%d", time() + 21 * 24 * 60 * 60);
        $lastweek = strftime("%Y-%m-%d", time() - 7*24*60*60);

        $DB->set("wp_posts",1, array(
            "ID" => 1,
            "post_author" => 1,
            "post_date" => $now,
            "post_date_gmt" => $now,
            "post_content" => "not interesting",
            "post_title" => "Auto Test",
            "post_excerpt" => "",
            "post_status" => "publish",
            "comment_status" => "open",
            "ping_status" => "closed",
            "post_password" => "",
            "post_name" => "test-event",
            "post_modified" => $now,
            "post_modified_gmt" => $now,
            "post_parent" => 0,
            "post_guid" => "https://t.t/test-event&#038;1",
            "menu_order" => 0,
            "post_type" => "tribe_events",
            "comment_count" => 0
        ));

        $DB->set("TD_Event",1,array(
            "event_id" => 1,
            "event_name" => "Auto Test",
            "event_open" => $in3months,
            "event_registration_open" => $lastweek,
            "event_registration_close" => $in1weeks,
            "event_year" => "2020", 
            "event_duration" => 8,
            "event_email" => "t@t.org", 
            "event_web" => "https://t.org", 
            "event_location" => "Amsterdam", 
            "event_country" => "France",
            "event_type" => 1,
            "event_currency_symbol" => "€",
            "event_currency_name" => "EUR",
            "event_base_fee" => 50.0, 
            "event_competition_fee" => 35.0,
            "event_bank" => "Credit Agricole",
            "event_account_name" => "Auto Test",
            "event_organisers_address" => "Rue 1",
            "event_iban" => "NL82INGB0000006262",
            "event_swift" => "INGBank/SBC02",
            "event_reference" => "Auto Test",
            "event_in_ranking" => "Y", 
            "event_factor" => 1, 
            "event_frontend" => 1
        ));
        $DB->set("TD_Event", 2, array(
            "event_id" => 2,
            "event_name" => "Auto Test 2",
            "event_open" => $in3months,
            "event_registration_open" => $in2weeks,
            "event_registration_close" => $in3weeks,
            "event_year" => "2020",
            "event_duration" => 8,
            "event_email" => "t@t.org",
            "event_web" => "https://t.org",
            "event_location" => "Amsterdam",
            "event_country" => "France",
            "event_type" => 1,
            "event_currency_symbol" => "€",
            "event_currency_name" => "EUR",
            "event_base_fee" => 50.0,
            "event_competition_fee" => 35.0,
            "event_bank" => "Credit Agricole",
            "event_account_name" => "Auto Test",
            "event_organisers_address" => "Rue 1",
            "event_iban" => "NL82INGB0000006262",
            "event_swift" => "INGBank/SBC02",
            "event_reference" => "Auto Test",
            "event_in_ranking" => "Y",
            "event_factor" => 1,
            "event_frontend" => 1
        ));
        $DB->set("TD_Competition",1,array(
            "competition_id" => 1, 
            "competition_event" => 1, 
            "competition_category" => 1, 
            "competition_weapon" => 1, 
            "competition_opens" => $in3weeks, 
            "competition_weapon_check" => $in3weeks
        ));

        $DB->set("TD_Event_Side",1,array(
            "id" => 1, 
            "event_id" => 1, 
            "title" => "MF", 
            "description" => "MF", 
            "starts" => $in3weeks, 
            "costs" => 0.0, 
            "competition_id" => 1
        ));
        $DB->set("TD_Event_Side", 2, array(
            "id" => 2,
            "event_id" => 1,
            "title" => "Diner",
            "description" => "Galaddiner",
            "starts" => $in3weeks,
            "costs" => 25.0,
            "competition_id" => null
        ));
        $DB->set("TD_Event_Side", 3, array(
            "id" => 3,
            "event_id" => 2,
            "title" => "MF",
            "description" => "MF",
            "starts" => $in3weeks,
            "costs" => 0.0,
            "competition_id" => 1
        ));
        $DB->set("TD_Event_Side", 4, array(
            "id" => 4,
            "event_id" => 2,
            "title" => "Diner",
            "description" => "Galaddiner",
            "starts" => $in3weeks,
            "costs" => 25.0,
            "competition_id" => null
        ));

        // admin without roles
        $this->init_admin(1);

        // admin with only a cashier role
        $this->init_admin(2);
        $DB->set("TD_Event_Role", 2, array(
            "id" => 2,
            "event_id" => 1,
            "user_id" => 2,
            "role_type" => "cashier"
        ));

        // admin with only a registrar role
        $this->init_admin(3);
        $DB->set("TD_Event_Role", 3, array(
            "id" => 3,
            "event_id" => 1,
            "user_id" => 3,
            "role_type" => "accreditation"
        ));

        // admin with only a accreditation role
        $this->init_admin(4);
        $DB->set("TD_Event_Role", 4, array(
            "id" => 4,
            "event_id" => 1,
            "user_id" => 4,
            "role_type" => "registrar"
        ));

        // admin with only a organiser role
        $this->init_admin(5);
        $DB->set("TD_Event_Role", 5, array(
            "id" => 5,
            "event_id" => 1,
            "user_id" => 5,
            "role_type" => "organiser"
        ));
        $this->init_admin(6);
        $DB->set("TD_Registrar", 6, array(
            "id" => 6,
            "user_id" => 6,
            "country_id" => 1
        ));
        $this->init_admin(7);
        $DB->set("TD_Registrar", 7, array(
            "id" => 7,
            "user_id" => 7,
            "country_id" => null
        ));


        $this->init_ranking(11);

        // admin with only a cashier role
        $this->init_ranking(12);
        $DB->set("TD_Event_Role", 12, array(
            "id" => 12,
            "event_id" => 1,
            "user_id" => 12,
            "role_type" => "cashier"
        ));

        // admin with only a registrar role
        $this->init_ranking(13);
        $DB->set("TD_Event_Role", 13, array(
            "id" => 13,
            "event_id" => 1,
            "user_id" => 13,
            "role_type" => "accreditation"
        ));

        // admin with only a accreditation role
        $this->init_ranking(14);
        $DB->set("TD_Event_Role", 14, array(
            "id" => 14,
            "event_id" => 1,
            "user_id" => 14,
            "role_type" => "registrar"
        ));

        // admin with only a organiser role
        $this->init_ranking(15);
        $DB->set("TD_Event_Role", 15, array(
            "id" => 15,
            "event_id" => 1,
            "user_id" => 15,
            "role_type" => "organiser"
        ));
        $this->init_ranking(16);
        $DB->set("TD_Registrar", 16, array(
            "id" => 16,
            "user_id" => 16,
            "country_id" => 1
        ));
        $this->init_ranking(17);
        $DB->set("TD_Registrar", 17, array(
            "id" => 17,
            "user_id" => 17,
            "country_id" => null
        ));

        $this->init_registrar(21);

        // admin with only a cashier role
        $this->init_registrar(22);
        $DB->set("TD_Event_Role", 22, array(
            "id" => 22,
            "event_id" => 1,
            "user_id" => 22,
            "role_type" => "cashier"
        ));

        // admin with only a registrar role
        $this->init_registrar(23);
        $DB->set("TD_Event_Role", 23, array(
            "id" => 23,
            "event_id" => 1,
            "user_id" => 23,
            "role_type" => "accreditation"
        ));

        // admin with only a accreditation role
        $this->init_registrar(24);
        $DB->set("TD_Event_Role", 24, array(
            "id" => 24,
            "event_id" => 1,
            "user_id" => 24,
            "role_type" => "registrar"
        ));

        // admin with only a organiser role
        $this->init_registrar(25);
        $DB->set("TD_Event_Role", 25, array(
            "id" => 25,
            "event_id" => 1,
            "user_id" => 25,
            "role_type" => "organiser"
        ));
        $this->init_registrar(26);
        $DB->set("TD_Registrar", 26, array(
            "id" => 26,
            "user_id" => 26,
            "country_id" => 1
        ));
        $this->init_registrar(27);
        $DB->set("TD_Registrar", 27, array(
            "id" => 27,
            "user_id" => 27,
            "country_id" => null
        ));

        $this->init_unpriv(31);
        $this->init_unpriv(32);
        $DB->set("TD_Event_Role",32,array(
            "id" => 32,
            "event_id" => 1,
            "user_id" => 32,
            "role_type" => "cashier"
        ));
        $this->init_unpriv(33);
        $DB->set("TD_Event_Role", 33, array(
            "id" => 33,
            "event_id" => 1,
            "user_id" => 33,
            "role_type" => "accreditation"
        ));
        $this->init_unpriv(34);
        $DB->set("TD_Event_Role", 34, array(
            "id" => 34,
            "event_id" => 1,
            "user_id" => 34,
            "role_type" => "registrar"
        ));
        $this->init_unpriv(35);
        $DB->set("TD_Event_Role", 35, array(
            "id" => 35,
            "event_id" => 1,
            "user_id" => 35,
            "role_type" => "organiser"
        ));
        $this->init_unpriv(36);
        $DB->set("TD_Registrar", 36, array(
            "id" => 5,
            "user_id" => 36,
            "country_id" => 1
        ));
        $this->init_unpriv(37);
        $DB->set("TD_Registrar", 37, array(
            "id" => 37,
            "user_id" => 37,
            "country_id" => null
        ));

        $DB->set("TD_Fencer", 1, array(
            "fencer_id" => 1,
            "fencer_firstname" => "Test",
            "fencer_surname" => "The Tester",
            "fencer_country" => 1,
            "fencer_dob" => "2000-01-01",
            "fencer_gender" => "M"
        ));
        $DB->set("TD_Fencer", 2, array(
            "fencer_id" => 2,
            "fencer_firstname" => "Test2",
            "fencer_surname" => "The Tester",
            "fencer_country" => 2,
            "fencer_dob" => "2000-01-01",
            "fencer_gender" => "M"
        ));
        $DB->set("TD_Registration", 1, array(
            "registration_id" => 1,
            "registration_fencer" => 1,
            "registration_role" => 1,
            "registration_event" => 1,
            "registration_costs" => 50.0,
            "registration_date" => $now,
            "registration_paid" => 'N',
            "registration_individual" => 'N'
        ));
        $DB->set("TD_Registration", 2, array(
            "registration_id" => 2,
            "registration_fencer" => 1000,
            "registration_role" => 1,
            "registration_event" => 1,
            "registration_costs" => 50.0,
            "registration_date" => $now,
            "registration_paid" => 'N',
            "registration_individual" => 'N'
        ));
        $DB->set("TD_Registration", 3, array(
            "registration_id" => 3,
            "registration_fencer" => 1,
            "registration_role" => 1,
            "registration_event" => 2,
            "registration_costs" => 50.0,
            "registration_date" => $now,
            "registration_paid" => 'N',
            "registration_individual" => 'N'
        ));
        $DB->set("TD_Registration", 4, array(
            "registration_id" => 4,
            "registration_fencer" => 2,
            "registration_role" => 1,
            "registration_event" => 1,
            "registration_costs" => 50.0,
            "registration_date" => $now,
            "registration_paid" => 'N',
            "registration_individual" => 'N'
        ));


        $DB->onQuery('^SELECT \* FROM TD_Event_Role WHERE event_id=([-0-9]*) AND user_id=([-0-9]*)$', function($pattern, $qry,$matches) {
            global $evflogger;
            $evflogger->log("selecting event_role for event ".$matches[1]." and user ".$matches[2]);
            global $DB;
            $f1=$matches[1];
            $f2=$matches[2];
            return $DB->loopAll("TD_Event_Role", function($item) use ($f1, $f2) {
                return isset($item["user_id"]) && $item["user_id"] == $f2
                  && isset($item["event_id"]) && $item["event_id"]==$f1;                  
            });
        });
    }
}

