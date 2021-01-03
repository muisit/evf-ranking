<?php

/**
 * EVF-Ranking Event Model
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

 class Event extends Base {
    public $table = "TD_Event";
    public $pk="event_id";
    public $fields=array("event_id","event_name","event_open","event_year", "event_duration","event_email", "event_web", "event_location", "event_country","event_type",
        "event_registration_cost","event_entry_cost","event_dinner_cost","event_dinner_note","event_currency_symbol","event_currency_name",
        "event_bank","event_account_name","event_organisers_address","event_iban","event_swift","event_reference","event_in_ranking", "event_factor",
        "type_name","country_name",
    );
    public $fieldToExport=array(
        "event_id" => "id",
        "event_name" => "name",
        "event_open" => "opens",
        "event_year" => "year",
        "event_duration" => "duration",
        "event_email" => "email",
        "event_web" => "web",
        "event_location" => "location",
        "event_country" => "country",
        "event_type" => "type",
        "event_registration_cost" => "registration_cost",
        "event_entry_cost" => "entry_cost",
        "event_dinner_cost" => "dinner_cost",
        "event_dinner_note" => "note",
        "event_currency_symbol" => "symbol",
        "event_currency_name" => "currency",
        "event_bank" => "bank",
        "event_account_name" => "account",
        "event_organisers_address" => "address",
        "event_iban" => "iban",
        "event_swift" => "swift",
        "event_reference" => "reference",
        "event_in_ranking" => "in_ranking",
        "event_factor" => "factor",
        "event_type_name" => "type_name",
        "country_name" => "country_name",
    );
    public $rules=array(
        "event_id" => "skip",
        "event_name" => array("label"=>"Name", "rules"=>"trim|required","message"=>"Name is required"),
        "event_open" => array("label"=>"Opens", "rules"=>"date|gt=2000-01-01|lt=2100-01-01|required","message"=>"Opening date is required"),
        "event_year" => array("label"=>"Year", "rules"=>"int|gte=2000|lt=2100|required","message"=>"Year of the event is required"),
        "event_duration" => array("label"=>"Duration", "rules"=>"int"),
        "event_email" => array("label"=>"E-mail", "rules"=>"email", "message"=>"E-mail address is incorrect"),
        "event_web" => array("label"=>"Website", "rules"=>"url","message"=>"Website address is incorrect"),
        "event_location" => array("label"=>"Location", "rules"=>"trim"),
        "event_country" => array("label"=>"Country", "rules"=>"model=Country","message"=>"Please select a valid country"),
        "event_type" => array("label"=>"Type", "rules"=>"model=EventType", "message"=>"Please select a valid type"),
        "event_registration_cost" => array("label"=>"Registration costs", "rules"=>"float=.2"),
        "event_entry_cost" => array("label"=>"Entry costs", "rules"=>"float=.2"),
        "event_dinner_cost" => array("label"=>"Dinner costs", "rules"=>"float=.2"),
        "event_dinner_note" => array("label"=>"Dinner note", "rules"=>"trim"),
        "event_currency_symbol" => array("label"=>"Currency symbol", "rules"=>"trim"),
        "event_currency_name" => array("label"=>"Currency name", "rules"=>"trim"),
        "event_bank" => array("label"=>"Bank name", "rules"=>"trim"),
        "event_account_name" => array("label"=>"Bank account", "rules"=>"trim"),
        "event_organisers_address" => array("label"=>"Account address", "rules"=>"trim"),
        "event_iban" => array("label"=>"IBAN number", "rules"=>"trim"),
        "event_swift" => array("label"=>"SWIFT code", "rules"=>"trim"),
        "event_reference" => array("label"=>"Account reference", "rules"=>"trim"),
        "event_in_ranking" => array("label"=>"In-Ranking", "rules"=>"bool"),
        "event_factor" => array("label" => "Factor","rules"=>"float"),
        "event_type_name" => "skip",
        "country_name" => "skip",
        "competitions" => "contains=Competition,competition_list"
    );


    private function sortToOrder($sort) {
        if(empty($sort)) $sort="i";
        $orderBy=array();
        for($i=0;$i<strlen($sort);$i++) {
            $c=$sort[$i];
            switch($c) {
            default:
            case 'i': $orderBy[]="event_id asc"; break;
            case 'I': $orderBy[]="event_id desc"; break;
            case 'n': $orderBy[]="event_name asc"; break;
            case 'N': $orderBy[]="event_name desc"; break;
            case 'd': $orderBy[]="event_open asc"; break;
            case 'D': $orderBy[]="event_open desc"; break;
            case 'y': $orderBy[]="event_year asc"; break;
            case 'Y': $orderBy[]="event_year desc"; break;
            case 't': $orderBy[]="event_type_name asc"; break;
            case 'T': $orderBy[]="event_type_name desc"; break;
            case 'r': $orderBy[]="event_in_ranking asc"; break;
            case 'R': $orderBy[]="event_in_ranking desc"; break;
            case 'l': $orderBy[]="event_location asc"; break;
            case 'L': $orderBy[]="event_location desc"; break;
            }
        }
        return $orderBy;
    }

    private function addFilter($qb, $filter,$special) {
        if(!empty(trim($filter))) {
            global $wpdb;
            $filter=$wpdb->esc_like($filter);
            //$filter=str_replace("%","%%",$filter);
            $qb->where( function($qb2) use ($filter) {
//                $qb2->where("fencer_surname","like","%$filter%")
//                    ->or_where("fencer_firstname","like","%$filter%");
                  $qb2->where("event_name like '%$filter%' or event_location like '%$filter%'");
            });
        }
        if ($special == "with_competitions") {
            $qb->where("exists(select * from TD_Competition c where c.competition_event=TD_Event.event_id)");
        }
        if ($special == "with_results") {
            $qb->where("exists(select * from TD_Competition c, TD_Result r where c.competition_event=TD_Event.event_id and r.result_competition=c.competition_id)");
        }
    }

    public function selectAll($offset,$pagesize,$filter,$sort,$special=null) {
        $qb = $this->select('TD_Event.*, c.country_name, et.event_type_name')
            ->join("TD_Country","c","TD_Event.event_country=c.country_id")
            ->join("TD_Event_Type", "et", "TD_Event.event_type=et.event_type_id")
            ->offset($offset)->limit($pagesize)->orderBy($this->sortToOrder($sort));
        $this->addFilter($qb,$filter,$special);
        return $qb->get();
    }

    public function count($filter,$special=null) {
        $qb = $this->select("count(*) as cnt");
        $this->addFilter($qb,$filter,$special);
        $result = $qb->get();
 
        if(empty($result) || !is_array($result)) return 0;
        return intval($result[0]->cnt);
    }

    public function competitions($id=null, $asObject=false) {
        if($id === null) $id = $this->{$this->pk};
        // find the competitions belonging to this event
        require_once(__DIR__ . "/competition.php");
        $model = new Competition();
        $dt = $model->listByEvent($id);

        $retval = array();
        if (!empty($dt) && is_array($dt)) {
            foreach ($dt as $c) {
                if($asObject) {
                    error_log("creating a new competition object based on result");
                    $retval[] = new Competition($c);
                }
                else {
                    $retval[] = $model->export($c);
                }
            }
        }
        return $retval;
    }
   
    public function postSave() {
        error_log("postsave for event, testing competition_list: ".(isset($this->competition_list)?"set":"not set"));
        if(isset($this->competition_list)) {
            error_log("competition list is set for saving");
            $oldcomps = $this->competitions(null,true);
            foreach($this->competition_list as $c) {
                error_log("setting the event ID");
                $c->competition_event = $this->{$this->pk};
                $c->save();

                for($i=0;$i<sizeof($oldcomps);$i++) {
                    if($oldcomps[$i]->identical($c)) {
                        unset($oldcomps[$i]);
                        $oldcomps = array_values($oldcomps);
                    }
                }
            }
            foreach($oldcomps as $c) {
                $c->delete();
            }
        }
        return true;
    }
 }
 