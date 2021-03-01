<?php

/**
 * EVF-Ranking EventRole Model
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

 class EventRole extends Base {
    public $table = "TD_Event_Role";
    public $pk="id";
    public $fields=array("id","event_id","user_id","role_type");
    public $fieldToExport=array(
        "id"=>"id",
        "event_id"=>"event_id",
        "user_id"=>"user",
        "user_nicename" => "user_name",
        "role_type" => "role_type",
    );
    public $rules=array(
        "id" => "skip",
        "event_id"=> "skip",
        "user_id" => array("rules"=>"model=User|required","message"=>"Please select a valid user"),
        "user_name" => "skip",
        "role_type" => array("rules"=>"enum=organiser,registrar,accreditation,cashier|required","message"=>"Please set the role for the user in this event"),
    );

    public function selectAll($offset,$pagesize,$filter,$sort,$special=null) {
        return $this->select('*, u.user_nicename')
        ->join("wp_users", "u", "TD_Event_Role.user_id=u.ID")
        ->offset($offset)->limit($pagesize)->orderBy(array("TD_Event_Role.id"))->get();
    }

    public function listByEvent($event) {
        return $this->select('*, u.user_nicename')
        ->join("wp_users", "u", "TD_Event_Role.user_id=u.ID")
        ->where("event_id",intval($event))->orderBy(array("TD_Event_Role.id"))->get();
    }
}
 