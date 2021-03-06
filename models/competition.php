<?php

/**
 * EVF-Ranking Competition Model
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

 class Competition extends Base {
    public $table = "TD_Competition";
    public $pk="competition_id";
    public $fields=array("competition_id","competition_event","competition_category","competition_weapon", "competition_opens","competition_weapon_check");
    public $fieldToExport=array(
        "competition_id"=>"id",
        "competition_event"=>"event_id",
        "competition_category"=>"category",
        "competition_weapon" => "weapon",
        "competition_opens"=>"opens",
        "competition_weapon_check" => "weapon_check"
    );
    public $rules=array(
        "competition_id" => "skip",
        "competition_event"=> "skip",
        "competition_category" => array("rules"=>"model=Category|required","message"=>"Please select a valid category"),
        "competition_weapon" => array("rules"=>"model=Weapon|required","message"=>"Please select a valid weapon"),
        "competition_opens" => "date",
        "competition_weapon_check" => "date"
    );

    public function selectAll($offset,$pagesize,$filter,$sort,$special=null) {
        return $this->select('*')->offset($offset)->limit($pagesize)->orderBy(array("competition_opens", "competition_weapon","competition_category"))->get();
    }

    public function listByEvent($event) {
        return $this->select('*')->where("competition_event",intval($event))->orderBy(array("competition_weapon","competition_category"))->get();
    }
}
 