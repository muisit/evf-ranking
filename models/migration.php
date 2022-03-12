<?php

/**
 * EVF-Ranking Migration Model
 * 
 * @package             evf-ranking
 * @author              Michiel Uitdehaag
 * @copyright           2020 Michiel Uitdehaag for muis IT
 * @licenses            GPL-3.0-or-later
 *
 * This file is part of evf-ranking.
 *
 * evf-ranking is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * evf-ranking is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with evf-ranking.  If not, see <https://www.gnu.org/licenses/>.
 */


 namespace EVFRanking\Models;

 class Migration extends Base {
    public $table = "TD_Migration";
    public $pk="id";
    public $fields=array("id","name","status");
    public $rules=array(
        "id" => "skip",
        "name" => "skip",
        "status"=>"int"
    );

    public function __construct($id=null,$forceload=false) {
        parent::__construct($id,$forceload);

        global $wpdb;
        $sql="select count(*) as cnt from TD_Migration";
        $result = $wpdb->get_results($sql);
        $results=array();
        if(empty($result) || !is_array($result)) {
            $sql="CREATE TABLE `TD_Migration` ( 
                `id` INT NOT NULL AUTO_INCREMENT , 
                `name` VARCHAR(255) NOT NULL , 
                `status` INT NOT NULL, 
                PRIMARY KEY (`id`)) ENGINE = InnoDB; ";
            $wpdb->query($sql);
        }
    }

    public function save() {
        if(parent::save() && intval($this->status) == 1) {
            try {
                ob_start();
                $this->execute();
                ob_end_clean();
            }
            catch (Exception $e) {
                // pass
            }
        }
        return true;
    }

    public function selectAll($offset,$pagesize,$filter,$sort, $special) {
        $this->synchroniseMigrations();
        return $this->select('*')->offset($offset)->limit($pagesize)->orderBy(array("id desc"))->get();
    }

    public function count($filter=null) {
        $result = $this->select("count(*) as cnt")->get();
        if(empty($result) || !is_array($result)) return 0;
        return intval($result[0]->cnt);
    }

    public function export($result=null) {
        if(empty($result)) {
            $result=$this;
        }
        return array(
            "id" => $result->id,
            "name" => $result->name,
            "status" => $result->status
        );
    }

    private function synchroniseMigrations() {
        $cnt = $this->count();
        if ($cnt < 1) {
            $migration = new Migration(array("name" => "001: Adjust Event Table", "status" => 0));
            $migration->save();
        }
        if($cnt < 2) {
            $migration = new Migration(array("name" => "002: Merge Result and Event", "status" => 0));
            $migration->save();
            $migration = new Migration(array("name" => "003: Adjust TD_Result table", "status" => 0));
            $migration->save();
        }
        if($cnt < 4) {
            $migration = new Migration(array("name" => "004: More adjustments to TD_Result", "status" => 0));
            $migration->save();
        }
        if($cnt < 5) {
            $migration = new Migration(array("name" => "005: Move Factor to Event", "status" => 0));
            $migration->save();
            $migration = new Migration(array("name" => "006: Add Ranking View", "status" => 0));
            $migration->save();
        }
        if($cnt < 7) {
            $migration = new Migration(array("name" => '007: Dropping national points', "status" => 0));
            $migration->save();
        }
        if($cnt < 8) {
            $migration = new Migration(array("name" => '008: Registrations', "status" => 0));
            $migration->save();            
        }
        if($cnt < 9) {
            $migration = new Migration(array("name" => '009: Registrations 2', "status" => 0));
            $migration->save();            
        }
        if ($cnt < 10) {
            $migration = new Migration(array("name" => '009: Registrations 3', "status" => 0));
            $migration->save();
        }
        if($cnt < 11) {
            $migration = new Migration(array("name"=> '010: Extend reference field',"status"=>0));
            $migration->save();
        }
        if($cnt < 12) {
            $migration = new Migration(array("name"=> '011: Queues',"status"=>0));
            $migration->save();
        }
        if($cnt < 13) {
            $migration = new Migration(array("name"=>"012: Accreditation","status"=>0));
            $migration->save();
        }
        if($cnt < 14) {
            $migration = new Migration(array("name"=> "013: Country flag extension","status"=>0));
            $migration->save();
        }
        if($cnt < 15) {
            $migration = new Migration(array("name"=>"014: Team events","status"=>0));
            $migration->save();
        }
        if($cnt < 16) {
            $migration = new Migration(array("name"=>"015: Live feeds","status"=>0));
            $migration->save();
        }
        if($cnt < 17) {
            $migration = new Migration(array("name"=>'016: Event config',"status"=>0));
            $migration->save();
        }
        if($cnt < 18) {
            $migration = new Migration(array("name"=>'017: Ranking view update',"status"=>0));
            $migration->save();
        }
    }

    public function execute() {
        global $wpdb;
        switch($this->name) {
        case '001: Adjust Event Table':
            $wpdb->query("alter table TD_Event drop column event_EVF_fee");
            $wpdb->query("alter table TD_Event add column event_frontend int null");
            break;
        case '002: Merge Result and Event':
            $wpdb->query("insert into TD_Competition (competition_event, competition_weapon, competition_category, competition_opens, competition_weapon_check) ".
                " select distinct r.result_event, r.result_weapon, r.result_category, e.event_open, e.event_open from TD_Result r ".
                " inner join TD_Event e on e.event_id=r.result_event ".
                " where not exists(select * from TD_Competition c where c.competition_event = r.result_event and c.competition_category=r.result_category and c.competition_weapon=r.result_weapon)");
            break;
        case '003: Adjust TD_Result table':
            $wpdb->query("ALTER TABLE `TD_Result` ADD `result_competition` INT NULL AFTER `result_event`;");
            $wpdb->query("update TD_Result r ".
                " inner join TD_Competition c on c.competition_event=r.result_event and c.competition_category=r.result_category and c.competition_weapon=r.result_weapon ".
                " set r.result_competition=c.competition_id");
            $wpdb->query("ALTER TABLE `TD_Result` DROP `result_event`;");
            break;
        case '004: More adjustments to TD_Result':
            $wpdb->query("ALTER TABLE `TD_Result` DROP `result_weapon`;");
            $wpdb->query("ALTER TABLE `TD_Result` DROP `result_category`;");
            break;
        case '005: Move Factor to Event':
            $wpdb->query("ALTER TABLE `TD_Event` ADD `event_factor` FLOAT DEFAULT(1);");
            $wpdb->query("UPDATE TD_Event e inner join TD_Competition c on c.competition_event=e.event_id inner join TD_Result rs on rs.result_competition=c.competition_id set e.event_factor=rs.result_factor");
            $wpdb->query("ALTER TABLE `TD_Result` DROP `result_factor`;");
            break;
        case '006: Add Ranking View':
            $wpdb->query("ALTER TABLE `TD_Result` ADD COLUMN result_in_ranking enum('Y', 'N') DEFAULT('N');");
            $wpdb->query("DROP VIEW IF EXISTS `VW_Ranking`;");
            $wpdb->query("CREATE VIEW `VW_Ranking`  AS SELECT ".
                " e.event_id, e.event_name, e.event_open, e.event_location, cnt.country_name, ".
                " c.competition_id, ".
                " cat.category_id, cat.category_name, cat.category_abbr, ".
                " w.weapon_id, w.weapon_name, w.weapon_abbr, w.weapon_gender, ".
                " f.fencer_id, f.fencer_firstname, f.fencer_surname, f.fencer_dob, f.fencer_gender, fcnt.country_abbr as fencer_country_abbr, fcnt.country_name as fencer_country_name, ".
                " r.result_id, r.result_place, r.result_points, r.result_national_points, r.result_entry, ".
                " r.result_de_points, r.result_podium_points, r.result_total_points, e.event_factor, r.result_in_ranking ".
                " FROM TD_Result r ".
                " inner join TD_Competition c on c.competition_id=r.result_competition ".
                " inner join TD_Event e on e.event_id = c.competition_event ".
                " inner join TD_Fencer f on f.fencer_id=r.result_fencer ".
                " inner join TD_Country cnt on cnt.country_id=e.event_country ".
                " inner join TD_Country fcnt on fcnt.country_id=f.fencer_country ".
                " inner join TD_Category cat on cat.category_id=c.competition_category ".
                " inner join TD_Weapon w on w.weapon_id=c.competition_weapon ".
                " WHERE e.event_in_ranking='Y'");
            break;
        case '007: Dropping national points':
            $wpdb->query("ALTER TABLE `TD_Result` DROP `result_national_points`;");
            break;
        case '008: Registrations':
            $wpdb->query("DROP TABLE `TD_Event_Side`;");
            $wpdb->query("CREATE TABLE `TD_Event_Side` (`id` int(11) NOT NULL AUTO_INCREMENT, `event_id` int(11) NOT NULL, `title` varchar(255) COLLATE utf8_bin NOT NULL, `description` text COLLATE utf8_bin NOT NULL, `costs` float NOT NULL, `competition_id` int(11) DEFAULT NULL, PRIMARY KEY (`id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;");
            $wpdb->query("ALTER TABLE `TD_Event` DROP `event_registration_cost`, DROP `event_entry_cost`, DROP `event_dinner_cost`, DROP `event_dinner_note`;");
            $wpdb->query("alter table TD_Event add column event_registration_open date null");
            $wpdb->query("alter table TD_Event add column event_registration_close date null");
            $wpdb->query("alter table TD_Event_Side add column starts date null");
            break;
        case '009: Registrations 2':
            $wpdb->query("alter table TD_Event add column event_base_fee float null");
            $wpdb->query("alter table TD_Event add column event_competition_fee float null");
            $wpdb->query("ALTER TABLE `TD_Role_Type` ADD `org_declaration` ENUM('Country','EVF','Org','FIE') NULL AFTER `role_type_name`; ");
            $wpdb->query("DROP TABLE IF EXISTS `TD_Registrar`");
            $wpdb->query("CREATE TABLE `TD_Registrar` ( `id` int(11) NOT NULL AUTO_INCREMENT,`user_id` int(11) NOT NULL, `country_id` int(11) DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8");
            $wpdb->query("DROP TABLE IF EXISTS `TD_Event_Role`");
            $wpdb->query("CREATE TABLE `TD_Event_Role` ( `id` INT NOT NULL AUTO_INCREMENT , `event_id` INT NOT NULL , `user_id` INT NOT NULL , `role_type` ENUM('organiser','registrar','accreditation','cashier') NOT NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB");
            $wpdb->query("DROP VIEW IF EXISTS `VW_Ranking`;");
            $wpdb->query("CREATE VIEW `VW_Ranking`  AS SELECT ".
                " e.event_id, e.event_name, e.event_open, e.event_location, cnt.country_name, ".
                " c.competition_id, ".
                " cat.category_id, cat.category_name, cat.category_abbr, ".
                " w.weapon_id, w.weapon_name, w.weapon_abbr, w.weapon_gender, ".
                " f.fencer_id, f.fencer_firstname, f.fencer_surname, f.fencer_dob, f.fencer_gender, fcnt.country_abbr as fencer_country_abbr, fcnt.country_name as fencer_country_name, ".
                " r.result_id, r.result_place, r.result_points, r.result_entry, ".
                " r.result_de_points, r.result_podium_points, r.result_total_points, e.event_factor, r.result_in_ranking ".
                " FROM TD_Result r ".
                " inner join TD_Competition c on c.competition_id=r.result_competition ".
                " inner join TD_Event e on e.event_id = c.competition_event ".
                " inner join TD_Fencer f on f.fencer_id=r.result_fencer ".
                " inner join TD_Country cnt on cnt.country_id=e.event_country ".
                " inner join TD_Country fcnt on fcnt.country_id=f.fencer_country ".
                " inner join TD_Category cat on cat.category_id=c.competition_category ".
                " inner join TD_Weapon w on w.weapon_id=c.competition_weapon ".
                " WHERE e.event_in_ranking='Y'");
            $wpdb->query("alter table TD_Registration add column registration_paid enum('Y','N') null");
            $wpdb->query("alter table TD_Registration add column registration_individual enum('Y','N') null");
            $wpdb->query("ALTER TABLE `TD_Registration` DROP `registration_competition`;");
            break;
        case '009: Registrations 3':
            $wpdb->query("alter table TD_Registration add column registration_paid_hod enum('Y','N') null");
            $wpdb->query("alter table TD_Event add column event_payments varchar(20) null");
            $wpdb->query("alter table TD_Fencer add column fencer_picture enum('Y','N','A','R') null");
            break;
        case '010: Extend reference field':
            $wpdb->query("ALTER TABLE `TD_Event` CHANGE `event_reference` `event_reference` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL; ");
            $wpdb->query("ALTER TABLE `TD_Event` CHANGE `event_organisers_address` `event_organisers_address` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL; ");
            $wpdb->query("ALTER TABLE `TD_Event` CHANGE `event_email` `event_email` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL; ");
            $wpdb->query("ALTER TABLE `TD_Event` CHANGE `event_web` `event_web` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL; ");
            $wpdb->query("DROP TABLE `TD_Access`;");
            $wpdb->query("DROP TABLE `TD_Assign`;");
            $wpdb->query("DROP TABLE `TD_Flex`;");
            $wpdb->query("DROP TABLE `TD_Function`;");
            $wpdb->query("DROP TABLE `TD_Log`;");
            $wpdb->query("DROP TABLE `TD_Note`;");
            $wpdb->query("DROP TABLE `TD_Person`;");
            $wpdb->query("DROP TABLE `TD_Person_Type`;");
            break;
        case '011: Queues':
            $wpdb->query("CREATE TABLE `TD_Queue` (`id` int(11) NOT NULL AUTO_INCREMENT,`state` varchar(20) NOT NULL,`payload` text NOT NULL,`attempts` int(11) NOT NULL,`started_at` datetime NULL,`finished_at` datetime NULL,`created_at` datetime NOT NULL,`available_at` datetime  NULL,`queue` varchar(20) NOT NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
            $wpdb->query("CREATE TABLE `TD_Accreditation` (`id` int(11) NOT NULL AUTO_INCREMENT ,`fencer_id` int(11) NOT NULL,`event_id` int(11) NOT NULL,`data` text COLLATE utf8_bin NOT NULL,`hash` varchar(512) COLLATE utf8_bin DEFAULT NULL,`file_hash` varchar(512) COLLATE utf8_bin DEFAULT NULL,`template_id` int(11) NOT NULL,`file_id` varchar(255) COLLATE utf8_bin NULL,`generated` datetime NULL, `is_dirty` DATETIME NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin");
            $wpdb->query("CREATE TABLE `TD_Accreditation_Template` (`id` int(11) NOT NULL AUTO_INCREMENT, `name` varchar(200) COLLATE utf8_bin NOT NULL,`content` text COLLATE utf8_bin NOT NULL,`event_id` int(11) NULL,PRIMARY KEY(`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin");
            $wpdb->query("alter table TD_Registration add column registration_mainevent int null");
            $wpdb->query("ALTER TABLE `TD_Registration` CHANGE `registration_event` `registration_event` INT(11) NULL; ");
            $wpdb->query("ALTER TABLE `TD_Registration` ADD `registration_payment` CHAR(1) NULL AFTER `registration_mainevent`; ");
            $wpdb->query("ALTER TABLE `TD_Registration` DROP `registration_individual`;");
            break;
        case '012: Accreditation':
            $wpdb->query("ALTER TABLE `TD_Accreditation` add column `fe_id` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL; ");
//            $wpdb->query("CREATE TABLE `TD_Audit` (`id` int(11) NOT NULL AUTO_INCREMENT,`created` datetime NOT NULL,`creator` int(11) NOT NULL,`log` text NOT NULL,`model` varchar(100) DEFAULT NULL,`modelid` int(11) DEFAULT NULL,`data` text DEFAULT NULL, PRIMARY KEY (`id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
            $wpdb->query("ALTER TABLE `TD_Registration` ADD column `registration_state` CHAR(1) NULL;");
            break;
        case '013: Country flag extension':
            $wpdb->query("ALTER TABLE `TD_Country` CHANGE `country_flag_path` `country_flag_path` VARCHAR(1024) CHARACTER SET utf8 COLLATE utf8_general_ci NULL; ");
            break;
        case '014: Team events':
            $wpdb->query("ALTER TABLE `TD_Registration` ADD column `registration_team` VARCHAR(100) NULL;");
            break;
        case '015: Live feeds':
            $wpdb->query("ALTER TABLE `TD_Event` ADD column `event_feed` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL;");
            break;
        case '016: Event config':
            $wpdb->query("ALTER TABLE `TD_Event` ADD column `event_config` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL;");
            break;
        case '017: Ranking view update':
            $wpdb->query("DROP VIEW IF EXISTS `VW_Ranking`;");
            $wpdb->query("CREATE VIEW `VW_Ranking`  AS SELECT ".
                " e.event_id, e.event_name, e.event_open, e.event_location, cnt.country_name, ".
                " c.competition_id, ".
                " cat.category_id, cat.category_name, cat.category_abbr, ".
                " w.weapon_id, w.weapon_name, w.weapon_abbr, w.weapon_gender, ".
                " f.fencer_id, f.fencer_firstname, f.fencer_surname, f.fencer_dob, f.fencer_gender, ".
                " fcnt.country_abbr as fencer_country_abbr, fcnt.country_name as fencer_country_name, fcnt.country_registered as fencer_country_registered, ".
                " r.result_id, r.result_place, r.result_points, r.result_entry, ".
                " r.result_de_points, r.result_podium_points, r.result_total_points, e.event_factor, r.result_in_ranking ".
                " FROM TD_Result r ".
                " inner join TD_Competition c on c.competition_id=r.result_competition ".
                " inner join TD_Event e on e.event_id = c.competition_event ".
                " inner join TD_Fencer f on f.fencer_id=r.result_fencer ".
                " inner join TD_Country cnt on cnt.country_id=e.event_country ".
                " inner join TD_Country fcnt on fcnt.country_id=f.fencer_country ".
                " inner join TD_Category cat on cat.category_id=c.competition_category ".
                " inner join TD_Weapon w on w.weapon_id=c.competition_weapon ".
                " WHERE e.event_in_ranking='Y'");
        default:
            break;
        }
    }
}
 