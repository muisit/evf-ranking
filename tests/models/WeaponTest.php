<?php

namespace tests\models;

use \EVFRanking\Models\Weapon;
use \Fixtures\WeaponFixture;

class WeaponTest extends \EVFTest\BaseTestCase
{
    public function testSelectAll(): void
    {
        $model = new Weapon();
        $model->selectAll(null, null, null, null);
        $this->assertEquals(
            ["SELECT * FROM TD_Weapon ORDER BY weapon_id"],
            $this->dbLog()
        );

        // no use of limit/offset
        $model->selectAll(200, 20, null, null);
        $this->assertEquals(
            ["SELECT * FROM TD_Weapon ORDER BY weapon_id"],
            $this->dbLog()
        );

        // no use of filter
        $model->selectAll(null, null, "filter", null);
        $this->assertEquals(
            ["SELECT * FROM TD_Weapon ORDER BY weapon_id"],
            $this->dbLog()
        );

        // no use of sort
        $model->selectAll(null, null, null, "iN");
        $this->assertEquals(
            ["SELECT * FROM TD_Weapon ORDER BY weapon_id"],
            $this->dbLog()
        );

        // no use of special
        $model->selectAll(null, null, null, null, "special");
        $this->assertEquals(
            ["SELECT * FROM TD_Weapon ORDER BY weapon_id"],
            $this->dbLog()
        );
    }

    // $weapon->count
    public function testCount(): void
    {
        $model = new Weapon();
        $model->count(null, null);
        $this->assertEquals(
            ["SELECT count(*) as cnt FROM TD_Weapon"],
            $this->dbLog()
        );

        // no use of filter
        $model->count("filter", null);
        $this->assertEquals(
            ["SELECT count(*) as cnt FROM TD_Weapon"],
            $this->dbLog()
        );

        // no use of special
        $model->count(null, "special");
        $this->assertEquals(
            ["SELECT count(*) as cnt FROM TD_Weapon"],
            $this->dbLog()
        );
    }
    
    // $weapon->delete
    public function testDelete(): void
    {
        $model = new Weapon();
        $model->setKey(WeaponFixture::WEAPON_ID_1);
        $model->delete();
        $queries = $this->dbLog();
        $this->assertEquals(1, count($queries));
        $this->assertEquals($queries[0], ["delete", "TD_Weapon", ["weapon_id" => WeaponFixture::WEAPON_ID_1]]);

        $model->delete(WeaponFixture::WEAPON_ID_2);
        $queries = $this->dbLog();
        $this->assertEquals(1, count($queries));
        $this->assertEquals($queries[0], ["delete", "TD_Weapon", ["weapon_id" => WeaponFixture::WEAPON_ID_2]]);
    }
}
