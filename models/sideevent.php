<?php

/**
 * EVF-Ranking SideEvent Model
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

 class SideEvent extends Base {
    public $table = "TD_Event_Side";
    public $pk="id";
    public $fields=array("id","event_id","title","description","starts","costs","competition_id");
    public $fieldToExport=array(
        "id"=>"id",
        "event_id"=>"event_id",
        "title"=>"title",
        "description" => "description",
        "starts" => "starts",
        "costs"=>"costs",
        "competition_id" => "competition_id"
    );
    public $rules=array(
        "id" => "skip",
        "event_id"=> "skip",
        "title" => array("rules"=>"trim|lt=255|required","message"=>"Please provide a short descriptive title"),
        "description" => array("rules"=>"trim","message"=>"Please provide a description"),
        "starts" => "date",
        "costs" => array("rules"=>"float|gte=0","message"=>"Please set the costs for participating in this event"),
        "competition_id" => "skip"
    );

    public function selectAll($offset,$pagesize,$filter,$sort,$special=null) {
        return $this->select('*')->offset($offset)->limit($pagesize)->orderBy(array("starts", "title","costs"))->get();
    }

    public function listByEvent($event) {
        return $this->select('*')->where("event_id",intval($event))->orderBy(array("starts", "title","costs"))->get();
    }

    public static function BaseRegistrationSelection($obj) {
        $qb=new QueryBuilder($obj);
        return $qb->select(array(
                'TD_Registration.*', 'f.fencer_surname', 'f.fencer_firstname', 'f.fencer_dob', 'fencer_gender',
                'c.country_name', 'c.country_abbr',
                'es.starts', 'es.costs','es.title','es.competition_id',
                'e.event_base_fee','e.event_competition_fee','e.event_name',
                'r.role_name', 'rt.org_declaration',
                'ct.category_value', 'ct.category_type', 'wp.weapon_gender')
            )
            ->from('TD_Registration')
            ->join('TD_Fencer', 'f', 'TD_Registration.registration_fencer=f.fencer_id')
            ->join('TD_Country', 'c', 'f.fencer_country=c.country_id')
            ->join('TD_Role', 'r', 'TD_Registration.registration_role=r.role_id')
            ->join('TD_Role_Type', 'rt', 'rt.role_type_id=r.role_type')
            ->join('TD_Event_Side', 'es', 'es.id=TD_Registration.registration_event')
            ->join('TD_Event', 'e', 'e.event_id=TD_Registration.registration_mainevent')
            ->join('TD_Competition', 'cmp', 'es.competition_id=cmp.competition_id')
            ->join('TD_Category', 'ct', 'cmp.competition_category=ct.category_id')
            ->join('TD_Weapon', 'wp', 'cmp.competition_weapon=wp.weapon_id')
            ->orderBy(array("c.country_name", "r.role_name", "f.fencer_surname", "f.fencer_firstname"));
    }

    public function registrations() {
        $qb = SideEvent::BaseRegistrationSelection($this);
        return $qb->where("TD_Registration.registration_event", $this->getKey())->get();
    }
}
 