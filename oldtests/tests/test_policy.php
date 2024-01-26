<?php

namespace EVFTest;

class Test_Policy extends BaseTest
{
    public $disabled = false;

    public function init()
    {
        parent::init();
        $this->init_admin();
    }

    public function test_policy_fencers()
    {
        $this->checkPolicy("fencers",array(
            // list view save delete misc nosuchcapa
            TRUE, TRUE, TRUE, TRUE, FALSE, FALSE,  // admin
            TRUE, TRUE, TRUE, TRUE, FALSE, FALSE,  // ranking
            TRUE, TRUE, FALSE, FALSE, FALSE, FALSE, // unpriv
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, // anonymous
        ));
    }

    public function test_policy_events()
    {
        $this->checkPolicy("events", array(
            TRUE, TRUE, TRUE, TRUE, FALSE, FALSE,
            TRUE, TRUE, TRUE, TRUE, FALSE, FALSE,
            TRUE, TRUE, FALSE, FALSE, FALSE, FALSE,
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE,
        ));
    }

    public function test_policy_results()
    {
        $this->checkPolicy("results", array(
            TRUE, TRUE, TRUE, TRUE, TRUE, FALSE,
            TRUE, TRUE, TRUE, TRUE, TRUE, FALSE,
            TRUE, TRUE, FALSE, FALSE, FALSE, FALSE,
            TRUE, TRUE, FALSE, FALSE, FALSE, FALSE,
        ));
    }

    public function test_policy_ranking()
    {
        $this->checkPolicy("ranking", array(
            TRUE, TRUE, TRUE, TRUE, TRUE, FALSE,
            TRUE, TRUE, TRUE, TRUE, TRUE, FALSE,
            TRUE, TRUE, FALSE, FALSE, FALSE, FALSE,
            TRUE, TRUE, FALSE, FALSE, FALSE, FALSE,
        ));
    }

    public function test_policy_competitions()
    {
        $this->checkPolicy("competitions", array(
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
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE,
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE,
        ));
    }

    public function test_policy_registrars()
    {
        $this->checkPolicy("registrars", array(
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
        ));
    }

    public function test_policy_categories()
    {
        $this->checkPolicy("categories", array(
            TRUE, TRUE, TRUE, TRUE, FALSE, FALSE,
            TRUE, TRUE, TRUE, TRUE, FALSE, FALSE,
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
        ));
    }

    public function test_policy_types()
    {
        $this->checkPolicy("types", array(
            TRUE, TRUE, TRUE, TRUE, FALSE, FALSE,
            TRUE, TRUE, TRUE, TRUE, FALSE, FALSE,
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
        ));
    }

    public function test_policy_roletypes()
    {
        $this->checkPolicy("roletypes", array(
            TRUE, TRUE, TRUE, TRUE, FALSE, FALSE,
            TRUE, TRUE, TRUE, TRUE, FALSE, FALSE,
            TRUE, FALSE, FALSE, FALSE, FALSE, FALSE,
            TRUE, FALSE, FALSE, FALSE, FALSE, FALSE,
        ));
    }

    public function test_policy_users()
    {
        $this->checkPolicy("users", array(
            TRUE, TRUE, FALSE, FALSE, FALSE, FALSE,
            TRUE, TRUE, FALSE, FALSE, FALSE, FALSE,
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE,
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE,
        ));
    }

    public function test_policy_posts()
    {
        $this->checkPolicy("posts", array(
            TRUE, TRUE, FALSE, FALSE, FALSE, FALSE,
            TRUE, TRUE, FALSE, FALSE, FALSE, FALSE,
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE,
            FALSE, FALSE, FALSE, FALSE, FALSE, FALSE,
        ));
    }

    public function test_unknown_policies()
    {
        $policynames = array(
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
        foreach ($policynames as $p) {
            if ($verbose) echo "Testing policy $p\r\n";
            $this->checkPolicy($p, array(
                FALSE, FALSE, FALSE, FALSE, FALSE, FALSE,
                FALSE, FALSE, FALSE, FALSE, FALSE, FALSE,
                FALSE, FALSE, FALSE, FALSE, FALSE, FALSE,
                FALSE, FALSE, FALSE, FALSE, FALSE, FALSE,
            ));
        }
    }


    private function checkPolicyForUser($policy, $for, $outcomes, $data, $cmt)
    {
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

    private function checkPolicy($for, $outcomes, $data = null)
    {
        $policy = $this->loadPolicy();
        $this->init_admin();
        $this->checkPolicyForUser($policy, $for, array_slice($outcomes, 0, 6), $data, "admin");

        $this->init_ranking();
        $this->checkPolicyForUser($policy, $for, array_slice($outcomes, 6, 6), $data, "rank");

        $this->init_unpriv();
        $this->checkPolicyForUser($policy, $for, array_slice($outcomes, 12, 6), $data, "unpriv");

        $this->init_anonymous();
        $this->checkPolicyForUser($policy, $for, array_slice($outcomes, 18, 6), $data, "anon");
    }
}

