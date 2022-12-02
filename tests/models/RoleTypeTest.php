<?php

namespace tests\models;

use \EVFRanking\Models\RoleType;
use \Fixtures\RoleFixture;
use \Fixtures\RoleTypeFixture;

class RoleTypeTest extends \EVFTest\BaseTestCase
{
    public function testListAll(): void
    {
        $roles = RoleType::ListAll();
        $this->assertEquals(
            ["SELECT * FROM TD_Role_Type ORDER BY role_type_name asc"],
            $this->dbLog()
        );
    }

    public function testFindByType(): void
    {
        $roles = RoleType::FindByType('declstring');
        $this->assertEquals(
            ["SELECT * FROM TD_Role_Type WHERE org_declaration = declstring"],
            $this->dbLog()
        );
    }

    public function testSelectAll(): void
    {
        $model = new RoleType();
        $model->selectAll(null, null, null, null);
        $this->assertEquals(
            ["SELECT * FROM TD_Role_Type ORDER BY role_type_id asc"],
            $this->dbLog()
        );

        $model->selectAll(200, 20, null, null);
        $this->assertEquals(
            ["SELECT * FROM TD_Role_Type ORDER BY role_type_id asc LIMIT 20 OFFSET 200"],
            $this->dbLog()
        );

        $model->selectAll(null, null, "filter", null);
        $this->assertEquals(
            ["SELECT * FROM TD_Role_Type WHERE role_type_name like %filter% ORDER BY role_type_id asc"],
            $this->dbLog()
        );

        $model->selectAll(null, null, "fil%ter", null);
        $this->assertEquals(
            ["SELECT * FROM TD_Role_Type WHERE role_type_name like %fil%%ter% ORDER BY role_type_id asc"],
            $this->dbLog()
        );

        // no support for other filters than string

        $model->selectAll(null, null, null, "iN");
        $this->assertEquals(
            ["SELECT * FROM TD_Role_Type ORDER BY role_type_id asc,role_type_name desc"],
            $this->dbLog()
        );

        $model->selectAll(null, null, null, "In");
        $this->assertEquals(
            ["SELECT * FROM TD_Role_Type ORDER BY role_type_id desc,role_type_name asc"],
            $this->dbLog()
        );

        $model->selectAll(null, null, null, "ni");
        $this->assertEquals(
            ["SELECT * FROM TD_Role_Type ORDER BY role_type_name asc,role_type_id asc"],
            $this->dbLog()
        );

        $model->selectAll(null, null, null, "x");
        $this->assertEquals(
            ["SELECT * FROM TD_Role_Type ORDER BY role_type_id asc"],
            $this->dbLog()
        );

        $model->selectAll(null, null, null, null, "special");
        $this->assertEquals(
            ["SELECT * FROM TD_Role_Type ORDER BY role_type_id asc"],
            $this->dbLog()
        );
    }

    // $role->count
    public function testCount(): void
    {
        $model = new RoleType();
        $model->count(null, null);
        $this->assertEquals(
            ["SELECT count(*) as cnt FROM TD_Role_Type"],
            $this->dbLog()
        );

        $model->count("filter", null);
        $this->assertEquals(
            ["SELECT count(*) as cnt FROM TD_Role_Type WHERE role_type_name like %filter%"],
            $this->dbLog()
        );

        $model->count("fil%ter", null);
        $this->assertEquals(
            ["SELECT count(*) as cnt FROM TD_Role_Type WHERE role_type_name like %fil%%ter%"],
            $this->dbLog()
        );

        $model->count(null, "special");
        $this->assertEquals(
            ["SELECT count(*) as cnt FROM TD_Role_Type"],
            $this->dbLog()
        );
    }
    
    // $roletype->delete
    public function testDelete(): void
    {
        RoleFixture::init();
        $model = new RoleType();
        $model->setKey(RoleTypeFixture::ROLETYPE_ID_1);
        $result = $model->delete();
        $queries = $this->dbLog();
        $this->assertEquals(1, count($queries));
        $this->assertEquals($queries[0], "SELECT count(*) as cnt FROM TD_Role WHERE role_type = " . RoleTypeFixture::ROLETYPE_ID_1);
        $this->assertEquals(false, $result);

        $result = $model->delete(RoleTypeFixture::ROLETYPE_ID_2);
        $queries = $this->dbLog();
        $this->assertEquals(2, count($queries));
        $this->assertEquals($queries[0], "SELECT count(*) as cnt FROM TD_Role WHERE role_type = " . RoleTypeFixture::ROLETYPE_ID_2);
        $this->assertEquals($queries[1], ["delete", "TD_Role_Type", ["role_type_id" => RoleTypeFixture::ROLETYPE_ID_2]]);
        $this->assertEquals(true, $result);
    }
}
