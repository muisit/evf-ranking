<?php

/**
 * EVF-Ranking EventType Model
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

class EventType extends Base {
    public $table = "TD_Event_Type";
    public $pk = "event_type_id";
    public $fields = array("event_type_id","event_type_abbr","event_type_name","event_type_group");
    public $fieldToExport = array(
        "event_type_id" => "id",
        "event_type_name" => "name",
        "event_type_abbr" => "abbr",
        "event_type_group" => "group"
    );
    public $rules = array(
        "event_type_id" => "skip",
        "event_type_name" => "trim",
        "event_type_abbr" => "trim",
        "event_type_group" => "trim"
    );     

    public function selectAll($offset=0,$pagesize=0,$filter=null,$sort=null,$special=null) {
        return $this->select('*')->orderBy('event_type_name')->get();
    }

    public function count($filter=null) {
        return $this->numrows()->count();
    }
 }
 