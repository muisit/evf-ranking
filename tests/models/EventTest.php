<?php

namespace tests\models;

use EVFRanking\Models\Role;
use EVFRanking\Models\Event;
use Fixtures\RegistrationFixture;
use Fixtures\RoleFixture;
use Fixtures\AccreditationTemplateFixture;
use Fixtures\AccreditationFixture;
use Fixtures\EventFixture;
use EVFTest\BaseTestCase;

class EventTest extends BaseTestCase
{
    public function testSelectAll(): void
    {
        $event = new Event();
        $event->selectAll(null, null, null, null);
        $this->assertEquals(
            ["SELECT TD_Event.*, c.country_name, et.event_type_name FROM TD_Event left JOIN TD_Country c ON TD_Event.event_country=c.country_id left JOIN TD_Event_Type et ON TD_Event.event_type=et.event_type_id ORDER BY event_id asc"],
            $this->dbLog()
        );

        $event->selectAll(200, 20, null, null);
        $this->assertEquals(
            ["SELECT TD_Event.*, c.country_name, et.event_type_name FROM TD_Event left JOIN TD_Country c ON TD_Event.event_country=c.country_id left JOIN TD_Event_Type et ON TD_Event.event_type=et.event_type_id ORDER BY event_id asc LIMIT 20 OFFSET 200"],
            $this->dbLog()
        );

        $event->selectAll(null, null, "filter", null);
        $this->assertEquals(
            ["SELECT TD_Event.*, c.country_name, et.event_type_name FROM TD_Event left JOIN TD_Country c ON TD_Event.event_country=c.country_id left JOIN TD_Event_Type et ON TD_Event.event_type=et.event_type_id WHERE (event_name like '%filter%' or event_location like '%filter%') ORDER BY event_id asc"],
            $this->dbLog()
        );

        $event->selectAll(null, null, (object)array("name" => "filter2"), null);
        $this->assertEquals(
            ["SELECT TD_Event.*, c.country_name, et.event_type_name FROM TD_Event left JOIN TD_Country c ON TD_Event.event_country=c.country_id left JOIN TD_Event_Type et ON TD_Event.event_type=et.event_type_id ORDER BY event_id asc"],
            $this->dbLog()
        );

        $event->selectAll(null, null, array("name" => "filter3"), null);
        $this->assertEquals(
            ["SELECT TD_Event.*, c.country_name, et.event_type_name FROM TD_Event left JOIN TD_Country c ON TD_Event.event_country=c.country_id left JOIN TD_Event_Type et ON TD_Event.event_type=et.event_type_id ORDER BY event_id asc"],
            $this->dbLog()
        );

        $event->selectAll(null, null, array("name" => ''), null);
        $this->assertEquals(
            ["SELECT TD_Event.*, c.country_name, et.event_type_name FROM TD_Event left JOIN TD_Country c ON TD_Event.event_country=c.country_id left JOIN TD_Event_Type et ON TD_Event.event_type=et.event_type_id ORDER BY event_id asc"],
            $this->dbLog()
        );

        $event->selectAll(null, null, null, "iN");
        $this->assertEquals(
            ["SELECT TD_Event.*, c.country_name, et.event_type_name FROM TD_Event left JOIN TD_Country c ON TD_Event.event_country=c.country_id left JOIN TD_Event_Type et ON TD_Event.event_type=et.event_type_id ORDER BY event_id asc,event_name desc"],
            $this->dbLog()
        );

        $event->selectAll(null, null, null, "In");
        $this->assertEquals(
            ["SELECT TD_Event.*, c.country_name, et.event_type_name FROM TD_Event left JOIN TD_Country c ON TD_Event.event_country=c.country_id left JOIN TD_Event_Type et ON TD_Event.event_type=et.event_type_id ORDER BY event_id desc,event_name asc"],
            $this->dbLog()
        );

        $event->selectAll(null, null, null, "ni");
        $this->assertEquals(
            ["SELECT TD_Event.*, c.country_name, et.event_type_name FROM TD_Event left JOIN TD_Country c ON TD_Event.event_country=c.country_id left JOIN TD_Event_Type et ON TD_Event.event_type=et.event_type_id ORDER BY event_name asc,event_id asc"],
            $this->dbLog()
        );

        $event->selectAll(null, null, null, "x");
        $this->assertEquals(
            ["SELECT TD_Event.*, c.country_name, et.event_type_name FROM TD_Event left JOIN TD_Country c ON TD_Event.event_country=c.country_id left JOIN TD_Event_Type et ON TD_Event.event_type=et.event_type_id ORDER BY event_id asc"],
            $this->dbLog()
        );

        $event->selectAll(null, null, null, null, "with_competitions");
        $this->assertEquals(
            ["SELECT TD_Event.*, c.country_name, et.event_type_name FROM TD_Event left JOIN TD_Country c ON TD_Event.event_country=c.country_id left JOIN TD_Event_Type et ON TD_Event.event_type=et.event_type_id WHERE exists(select * from TD_Competition c where c.competition_event=TD_Event.event_id) ORDER BY event_id asc"],
            $this->dbLog()
        );

        $event->selectAll(null, null, null, null, "with_results");
        $this->assertEquals(
            ["SELECT TD_Event.*, c.country_name, et.event_type_name FROM TD_Event left JOIN TD_Country c ON TD_Event.event_country=c.country_id left JOIN TD_Event_Type et ON TD_Event.event_type=et.event_type_id WHERE exists(select * from TD_Competition c, TD_Result r where c.competition_event=TD_Event.event_id and r.result_competition=c.competition_id) ORDER BY event_id asc"],
            $this->dbLog()
        );

        $event->selectAll(null, null, null, null, "other_special");
        $this->assertEquals(
            ["SELECT TD_Event.*, c.country_name, et.event_type_name FROM TD_Event left JOIN TD_Country c ON TD_Event.event_country=c.country_id left JOIN TD_Event_Type et ON TD_Event.event_type=et.event_type_id ORDER BY event_id asc"],
            $this->dbLog()
        );
    }

    // $role->count
    public function testCount(): void
    {
        $event = new Event();
        $event->count(null, null);
        $this->assertEquals(
            ["SELECT count(*) as cnt FROM TD_Event"],
            $this->dbLog()
        );

        $event->count("filter", null);
        $this->assertEquals(
            ["SELECT count(*) as cnt FROM TD_Event WHERE (event_name like '%filter%' or event_location like '%filter%')"],
            $this->dbLog()
        );

        $event->count((object)array("name" => "filter2"), null);
        $this->assertEquals(
            ["SELECT count(*) as cnt FROM TD_Event"],
            $this->dbLog()
        );

        $event->count(array("name" => "filter3"), null);
        $this->assertEquals(
            ["SELECT count(*) as cnt FROM TD_Event"],
            $this->dbLog()
        );

        $event->count(array("name" => ''), null);
        $this->assertEquals(
            ["SELECT count(*) as cnt FROM TD_Event"],
            $this->dbLog()
        );

        $event->count(null, "with_competitions");
        $this->assertEquals(
            ["SELECT count(*) as cnt FROM TD_Event WHERE exists(select * from TD_Competition c where c.competition_event=TD_Event.event_id)"],
            $this->dbLog()
        );

        $event->count(null, "with_results");
        $this->assertEquals(
            ["SELECT count(*) as cnt FROM TD_Event WHERE exists(select * from TD_Competition c, TD_Result r where c.competition_event=TD_Event.event_id and r.result_competition=c.competition_id)"],
            $this->dbLog()
        );

        $event->count(null, "other_special");
        $this->assertEquals(
            ["SELECT count(*) as cnt FROM TD_Event"],
            $this->dbLog()
        );
    }
    
    public function testDelete(): void
    {
        RegistrationFixture::init();
        $event = new Event();
        $event->setKey(EventFixture::EVENT_ID);
        $event->delete();
        $queries = $this->dbLog();
        $this->assertEquals(1, count($queries));
        //$this->assertEquals(json_encode($queries[0]), "SELECT COUNT(*) as cnt FROM TD_Registration WHERE registration_role = " . RoleFixture::ROLE_ID_1);
        $this->assertEquals($queries[0], ["delete", "TD_Event", ["event_id" => EventFixture::EVENT_ID]]);
    }

    public function testSave(): void
    {
        $event = new Event();
        $event->setKey(EventFixture::EVENT_ID);
        $event->event_name = 'TestMe';
        $event->event_open = '2000-01-01';
        $event->event_year = 2004;
        $event->event_type = 2;
        $event->event_country = 3;
        $event->event_location = 'Somewhere';
        $event->event_in_ranking = 'Y';
        $event->event_factor = 1.2;
        $event->event_frontend = 12;
        $event->event_feed = '';
        $event->event_config = json_encode([
            'allow_registration_lower_age' => false,
            'allow_more_teams' => false,
            'no_accreditations' => false,
            'no_accreditations' => false,
            'use_accreditation' => false,
            'use_registration' => false,
            'require_cards' => false,
            'require_documents' => false,
            'allow_incomplete_checkin' => false,
            'allow_hod_checkout' => false,
            'mark_process_start' => false,
            'combine_checkin_checkout' => false
        ]);
        $event->save();
        $queries = $this->dbLog();
        $this->assertEquals(1, count($queries));
        //$this->assertEquals(json_encode($queries[0]), "SELECT COUNT(*) as cnt FROM TD_Registration WHERE registration_role = " . RoleFixture::ROLE_ID_1);
        $this->assertEquals($queries[0], '["set","TD_Event",800,{"event_name":"TestMe","event_open":"2000-01-01","event_year":2004,"event_type":2,"event_country":3,"event_location":"Somewhere","event_in_ranking":"Y","event_factor":1.2,"event_frontend":12,"event_feed":"","event_config":"{\"use_registration\":true,\"allow_registration_lower_age\":true,\"allow_more_teams\":true,\"no_accreditations\":true,\"use_accreditation\":true,\"require_cards\":true,\"require_documents\":true,\"allow_incomplete_checkin\":true,\"allow_hod_checkout\":true,\"mark_process_start\":true,\"combine_checkin_checkout\":true}"}]');

        $event = new Event();
        $event->event_name = 'TestMe';
        $event->event_open = '2000-01-01';
        $event->event_year = 2004;
        $event->event_type = 2;
        $event->event_country = 3;
        $event->event_location = 'Somewhere';
        $event->event_in_ranking = 'Y';
        $event->event_factor = 1.2;
        $event->event_frontend = 12;
        $event->event_feed = '';
        $event->event_config = json_encode([
            'allow_registration_lower_age' => '12',
            'invalid_key' => false
        ]);
        $event->save();
        $queries = $this->dbLog();
        $this->assertEquals(2, count($queries));
        //$this->assertEquals(json_encode($queries[0]), "SELECT COUNT(*) as cnt FROM TD_Registration WHERE registration_role = " . RoleFixture::ROLE_ID_1);
        $this->assertEquals($queries[0], '["save","TD_Event",{"event_name":"TestMe","event_open":"2000-01-01","event_year":2004,"event_type":2,"event_country":3,"event_location":"Somewhere","event_in_ranking":"Y","event_factor":1.2,"event_frontend":12,"event_feed":"","event_config":"{\"allow_registration_lower_age\":true}"}]');
        $this->assertEquals($queries[1], '["set","TD_Event",2,{"event_name":"TestMe","event_open":"2000-01-01","event_year":2004,"event_type":2,"event_country":3,"event_location":"Somewhere","event_in_ranking":"Y","event_factor":1.2,"event_frontend":12,"event_feed":"","event_config":"{\"allow_registration_lower_age\":true}","event_id":2}]');


    }

}
