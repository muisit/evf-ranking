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


 namespace EVFRanking;

 class Migration extends Base {
    public $table = "TD_Migration";
    public $pk="id";
    public $fields=array("id","name","status");
    public $rules=array(
        "id" => "skip",
        "name" => "skip",
        "status"=>"int"
    );

    public function __construct($id=null) {
        parent::__construct($id);

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
        error_log('saving migration');
        if(parent::save() && intval($this->status) == 1) {
            error_log('save succesful, executing');
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
        error_log("syncing migrations");
        $cnt = $this->count();
        error_log("count is $cnt");
        if($cnt < 7) {
            if($cnt < 5) {
                if($cnt < 4) {
                    if($cnt < 2) {
                        if ($cnt < 1) {
                            $migration = new Migration(array("name" => "001: Adjust Event Table", "status" => 0));
                            $migration->save();
                        }
                        $migration = new Migration(array("name" => "002: Merge Result and Event", "status" => 0));
                        $migration->save();
                        $migration = new Migration(array("name" => "003: Adjust TD_Result table", "status" => 0));
                        $migration->save();
                    }
                    $migration = new Migration(array("name" => "004: More adjustments to TD_Result", "status" => 0));
                    $migration->save();
                }
                $migration = new Migration(array("name" => "005: Move Factor to Event", "status" => 0));
                $migration->save();
                $migration = new Migration(array("name" => "006: Add Ranking View", "status" => 0));
                $migration->save();
            }
            $migration = new Migration(array("name" => '007: Dropping national points', "status" => 0));
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
        default:
            break;
        }
    }
}
 