<?php

/**
 * EVF-Ranking Category Model
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

 class Category extends Base {

    public function __construct($id=null) {
        $this->table = "TD_Category";
        $this->pk="category_id";
        $this->fields=array("category_id","category_abbr","category_name","category_type", "category_value");
        parent::__construct($id);
    }

    public function export($result=null) {
        if(empty($result)) {
            $result=$this;
        }
        return array(
            "id" => $result->category_id,
            "name" => $result->category_name,
            "abbr" => $result->category_abbr,
            "type" => $result->category_type,
            "value" => $result->category_value
        );
    }

    public function selectAll($offset=0,$pagesize=0,$filter=null,$sort=null,$special=null) {
        return $this->select('*')->orderBy('category_name')->get();
    }

    public function count($filter=null,$special=null) {
        $result = $this->select("count(*) as cnt")->get();
        if(empty($result) || !is_array($result)) return 0;
        return intval($result[0]->cnt);
    }

    public function categoryFromYear($year, $wrt) {
        $year=intval($year);
        $wrtM=intval(strftime('%m',strtotime($wrt)));
        $wrtY=intval(strftime('%Y', strtotime($wrt)));

        $diff = $wrtY - $year;
        if($wrtM > 7) {
            $diff += 1; // people start fencing in the older category as of august
        }
        if ($diff >= 80 ) return 5;
        if ($diff >= 70 ) return 4;
        if ($diff >= 60 ) return 3;
        if ($diff >= 50 ) return 2;
        if ($diff >= 40 ) return 1;
        return -1;
    }
 }
 