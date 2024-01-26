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


namespace EVFRanking\Models;

class Category extends Base {
    public $table = "TD_Category";
    public $pk = "category_id";
    public $fields = array("category_id", "category_abbr", "category_name", "category_type", "category_value");
    public $fieldToExport = array(
        "category_id" => "id",
        "category_name" => "name",
        "category_abbr" => "abbr",
        "category_type" => "type",
        "category_value" => "value"
    );
    public $rules = array(
        "category_id" => "skip",
        "category_name" => "trim",
        "category_abbr" => "trim",
        "category_type" => "enum=I,T",
        "category_value" => "int"
    );

    public static function ExportAll($export = true)
    {
        $model = new Category();
        $lst = $model->selectAll(0, null, null, "n", null);
        if (!$export) {
            return $lst;
        }
        $retval = [];
        foreach ($lst as $c) {
            $retval[] = $model->export($c);
        }
        return $retval;
    }

    public function selectAll($offset=0,$pagesize=0,$filter=null,$sort=null,$special=null) {
        return $this->select('*')->orderBy('category_name')->get();
    }

    public function count($filter=null,$special=null) {
        return $this->numrows()->count();
    }

    public static function CategoryFromYear($year, $wrt)
    {
        $year=intval($year);
        $wrtM=intval(date('m',strtotime($wrt)));
        $wrtY=intval(date('Y', strtotime($wrt)));

        $diff = $wrtY - $year;
        if($wrtM > 6) {
            $diff += 1; // people start fencing in the older category as of July
        }
        //if ($diff >= 80) return 5;
        if ($diff >= 70) return 4;
        if ($diff >= 60) return 3;
        if ($diff >= 50) return 2;
        if ($diff >= 40) return 1;
        return -1;
    }

    public function getMaximalDate()
    {
        $catval = intval($this->category_value);
        $year = date('Y');
        $month = intval(date('m'));
        if ($month > 6) $year = intval($year) + 1;
        switch ($catval)
        {
        default:
        case 1:
            $year -= 39; break;
        case 2:
            $year -= 49; break;
        case 3:
            $year -= 59; break;
        case 4:
            $year -= 69; break;
        case 5:
            $year -= 79; break;
        }
        return date('Y-m-d', strtotime(($year) . '-01-01'));
    }

    public function getMinimalDate()
    {
        $catval = intval($this->category_value);
        $year = date('Y');
        $month = intval(date('m'));
        if ($month > 6) $year = intval($year) + 1;
        switch ($catval)
        {
        default:
        case 1:
            $year -= 50; break;
        case 2:
            $year -= 60; break;
        case 3:
            $year -= 70; break;
        case 4:
            $year -= 199; break; // no max for cat 4 since we stopped cat 5
        case 5:
            $year -= 199; break;
        }
        return date('Y-m-d', strtotime(($year) . '-01-01'));
    }
}
