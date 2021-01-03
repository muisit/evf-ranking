<?php

/**
 * EVF-Ranking Weapon Model
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

 class Weapon extends Base {

    public function __construct($id=null) {
        $this->table = "TD_Weapon";
        $this->pk="weapon_id";
        $this->fields=array("weapon_id","weapon_abbr","weapon_name","weapon_gender");
        parent::__construct($id);
    }

    public function export($result=null) {
        if(empty($result)) {
            $result=$this;
        }
        return array(
            "id" => $result->weapon_id,
            "name" => $result->weapon_name,
            "abbr" => $result->weapon_abbr,
            "gender" => $result->weapon_gender,
        );
    }

    public function selectAll($offset=0,$pagesize=0,$filter=null,$sort=null,$special=null) {
        return $this->select('*')->orderBy('weapon_id')->get();
    }

    public function count($filter=null,$special=null) {
        $result = $this->select("count(*) as cnt")->get();
        if(empty($result) || !is_array($result)) return 0;
        return intval($result[0]->cnt);
    }
 }
 