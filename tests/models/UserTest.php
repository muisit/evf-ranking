<?php

namespace tests\models;

use \EVFRanking\Models\User;
use \EVFRanking\Models\Event;
use \Fixtures\RegistrationFixture;
use \Fixtures\RoleFixture;
use \Fixtures\AccreditationTemplateFixture;
use \Fixtures\AccreditationFixture;
use \Fixtures\EventFixture;

class UserTest extends \EVFTest\BaseTestCase
{
    public function testSelectAll(): void
    {
        $model = new User();
        $model->selectAll(null, null, null, null);
        $this->assertEquals(
            ["SELECT * FROM wppref_users ORDER BY user_nicename asc,ID desc"],
            $this->dbLog()
        );

        $model->selectAll(200, 20, null, null);
        $this->assertEquals(
            ["SELECT * FROM wppref_users ORDER BY user_nicename asc,ID desc LIMIT 20 OFFSET 200"],
            $this->dbLog()
        );

        $model->selectAll(null, null, "filter", null);
        $this->assertEquals(
            ["SELECT * FROM wppref_users WHERE (user_nicename like '%filter%' or user_login like '%filter%') ORDER BY user_nicename asc,ID desc"],
            $this->dbLog()
        );

        $model->selectAll(null, null, "fil'ter", null);
        $this->assertEquals(
            ["SELECT * FROM wppref_users WHERE (user_nicename like '%fil\'ter%' or user_login like '%fil\'ter%') ORDER BY user_nicename asc,ID desc"],
            $this->dbLog()
        );

        // no support for other filters than string values

        // no use of sort
        $model->selectAll(null, null, null, "iN");
        $this->assertEquals(
            ["SELECT * FROM wppref_users ORDER BY user_nicename asc,ID desc"],
            $this->dbLog()
        );

        // no use of special
        $model->selectAll(null, null, null, null, "special");
        $this->assertEquals(
            ["SELECT * FROM wppref_users ORDER BY user_nicename asc,ID desc"],
            $this->dbLog()
        );
    }

    // $weapon->count
    public function testCount(): void
    {
        $model = new User();
        $model->count(null, null);
        $this->assertEquals(
            ["SELECT count(*) as cnt FROM wppref_users"],
            $this->dbLog()
        );

        $model->count("filter", null);
        $this->assertEquals(
            ["SELECT count(*) as cnt FROM wppref_users WHERE (user_nicename like '%filter%' or user_login like '%filter%')"],
            $this->dbLog()
        );

        $model->count("fil'ter", null);
        $this->assertEquals(
            ["SELECT count(*) as cnt FROM wppref_users WHERE (user_nicename like '%fil\'ter%' or user_login like '%fil\'ter%')"],
            $this->dbLog()
        );

        // no use of special
        $model->count(null, "special");
        $this->assertEquals(
            ["SELECT count(*) as cnt FROM wppref_users"],
            $this->dbLog()
        );
    }
    
    // $weapon->delete
    public function testDelete(): void
    {
        $model = new User();
        $model->setKey(28);
        $result = $model->delete();
        $queries = $this->dbLog();
        $this->assertEquals(0, count($queries));
        $this->assertEquals(false, $result);
    }
}
