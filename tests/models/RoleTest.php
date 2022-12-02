<?php

namespace tests\models;

use \EVFRanking\Models\Role;
use \EVFRanking\Models\Event;
use \Fixtures\RegistrationFixture;
use \Fixtures\RoleFixture;
use \Fixtures\AccreditationTemplateFixture;
use \Fixtures\AccreditationFixture;
use \Fixtures\EventFixture;

class RoleTest extends \EVFTest\BaseTestCase
{
    public function testListAll(): void
    {
        $roles = Role::ListAll();
        $this->assertEquals(
            ["SELECT TD_Role.*, rt.role_type_name, rt.org_declaration FROM TD_Role left JOIN TD_Role_Type rt ON TD_Role.role_type=rt.role_type_id ORDER BY role_id asc LIMIT 100000"],
            $this->dbLog()
        );
    }

    public function testSelectAll(): void
    {
        $role = new Role();
        $role->selectAll(null, null, null, null);
        $this->assertEquals(
            ["SELECT TD_Role.*, rt.role_type_name, rt.org_declaration FROM TD_Role left JOIN TD_Role_Type rt ON TD_Role.role_type=rt.role_type_id ORDER BY role_id asc"],
            $this->dbLog()
        );

        $role->selectAll(200, 20, null, null);
        $this->assertEquals(
            ["SELECT TD_Role.*, rt.role_type_name, rt.org_declaration FROM TD_Role left JOIN TD_Role_Type rt ON TD_Role.role_type=rt.role_type_id ORDER BY role_id asc LIMIT 20 OFFSET 200"],
            $this->dbLog()
        );

        $role->selectAll(null, null, "filter", null);
        $this->assertEquals(
            ["SELECT TD_Role.*, rt.role_type_name, rt.org_declaration FROM TD_Role left JOIN TD_Role_Type rt ON TD_Role.role_type=rt.role_type_id WHERE role_name like %filter% ORDER BY role_id asc"],
            $this->dbLog()
        );

        $role->selectAll(null, null, (object)array("name" => "filter2"), null);
        $this->assertEquals(
            ["SELECT TD_Role.*, rt.role_type_name, rt.org_declaration FROM TD_Role left JOIN TD_Role_Type rt ON TD_Role.role_type=rt.role_type_id WHERE role_name like %filter2% ORDER BY role_id asc"],
            $this->dbLog()
        );

        $role->selectAll(null, null, array("name" => "filter3"), null);
        $this->assertEquals(
            ["SELECT TD_Role.*, rt.role_type_name, rt.org_declaration FROM TD_Role left JOIN TD_Role_Type rt ON TD_Role.role_type=rt.role_type_id WHERE role_name like %filter3% ORDER BY role_id asc"],
            $this->dbLog()
        );

        $role->selectAll(null, null, array("name" => ''), null);
        $this->assertEquals(
            ["SELECT TD_Role.*, rt.role_type_name, rt.org_declaration FROM TD_Role left JOIN TD_Role_Type rt ON TD_Role.role_type=rt.role_type_id ORDER BY role_id asc"],
            $this->dbLog()
        );

        $role->selectAll(null, null, null, "iN");
        $this->assertEquals(
            ["SELECT TD_Role.*, rt.role_type_name, rt.org_declaration FROM TD_Role left JOIN TD_Role_Type rt ON TD_Role.role_type=rt.role_type_id ORDER BY role_id asc,role_name desc"],
            $this->dbLog()
        );

        $role->selectAll(null, null, null, "In");
        $this->assertEquals(
            ["SELECT TD_Role.*, rt.role_type_name, rt.org_declaration FROM TD_Role left JOIN TD_Role_Type rt ON TD_Role.role_type=rt.role_type_id ORDER BY role_id desc,role_name asc"],
            $this->dbLog()
        );

        $role->selectAll(null, null, null, "ni");
        $this->assertEquals(
            ["SELECT TD_Role.*, rt.role_type_name, rt.org_declaration FROM TD_Role left JOIN TD_Role_Type rt ON TD_Role.role_type=rt.role_type_id ORDER BY role_name asc,role_id asc"],
            $this->dbLog()
        );

        $role->selectAll(null, null, null, "x");
        $this->assertEquals(
            ["SELECT TD_Role.*, rt.role_type_name, rt.org_declaration FROM TD_Role left JOIN TD_Role_Type rt ON TD_Role.role_type=rt.role_type_id ORDER BY role_id asc"],
            $this->dbLog()
        );

        $role->selectAll(null, null, null, null, "special");
        $this->assertEquals(
            ["SELECT TD_Role.*, rt.role_type_name, rt.org_declaration FROM TD_Role left JOIN TD_Role_Type rt ON TD_Role.role_type=rt.role_type_id ORDER BY role_id asc"],
            $this->dbLog()
        );
    }

    // $role->count
    public function testCount(): void
    {
        $role = new Role();
        $role->count(null, null);
        $this->assertEquals(
            ["SELECT count(*) as cnt FROM TD_Role"],
            $this->dbLog()
        );

        $role->count("filter", null);
        $this->assertEquals(
            ["SELECT count(*) as cnt FROM TD_Role WHERE role_name like %filter%"],
            $this->dbLog()
        );

        $role->count((object)array("name" => "filter2"), null);
        $this->assertEquals(
            ["SELECT count(*) as cnt FROM TD_Role WHERE role_name like %filter2%"],
            $this->dbLog()
        );

        $role->count(array("name" => "filter3"), null);
        $this->assertEquals(
            ["SELECT count(*) as cnt FROM TD_Role WHERE role_name like %filter3%"],
            $this->dbLog()
        );

        $role->count(array("name" => ''), null);
        $this->assertEquals(
            ["SELECT count(*) as cnt FROM TD_Role"],
            $this->dbLog()
        );

        $role->count(null, "special");
        $this->assertEquals(
            ["SELECT count(*) as cnt FROM TD_Role"],
            $this->dbLog()
        );
    }
    
    // $role->delete
    public function testDelete(): void
    {
        RegistrationFixture::init();
        $role = new Role();
        $role->setKey(RoleFixture::ROLE_ID_1);
        $role->delete();
        $queries = $this->dbLog();
        $this->assertEquals(2, count($queries));
        $this->assertEquals($queries[0], "SELECT count(*) as cnt FROM TD_Registration WHERE registration_role = " . RoleFixture::ROLE_ID_1);
        $this->assertEquals($queries[1], ["delete", "TD_Role", ["role_id" => RoleFixture::ROLE_ID_1]]);

        $role->delete(RoleFixture::ROLE_ID_2);
        $queries = $this->dbLog();
        $this->assertEquals(2, count($queries));
        $this->assertEquals($queries[0], "SELECT count(*) as cnt FROM TD_Registration WHERE registration_role = " . RoleFixture::ROLE_ID_2);
        $this->assertEquals($queries[1], ["delete", "TD_Role", ["role_id" => RoleFixture::ROLE_ID_2]]);
    }

    // $role->selectAccreditations
    public function testSelectAccreditations(): void
    {
        AccreditationTemplateFixture::init();
        AccreditationFixture::init();

        $event = new Event();
        $event->setKey(EventFixture::EVENT_ID);
        $role = new Role();
        $role->setKey(RoleFixture::ROLE_ID_1);
        $result = $role->selectAccreditations($event);
        $this->assertEquals(1, count($result));
        $this->assertEquals(AccreditationFixture::ACCREDITATION_ID, $result[0]->getKey());
        $this->assertEquals(EventFixture::EVENT_ID, $result[0]->event_id);
    }
}
