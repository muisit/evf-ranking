<?php

/**
 * EVF-Ranking Weapon Model
 *
 * @package             evf-ranking
 * @author              Michiel Uitdehaag
 * @copyright           2020 - 2024 Michiel Uitdehaag for muis IT
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

#[AllowDynamicProperties]
class Weapon extends Base
{
    public $table = "TD_Weapon";
    public $pk = "weapon_id";
    public $fields = array("weapon_id", "weapon_abbr", "weapon_name", "weapon_gender");
    public $fieldToExport = array(
        "weapon_id" => "id",
        "weapon_name" => "name",
        "weapon_abbr" => "abbr",
        "weapon_gender" => "gender"
    );
    public $rules = array(
        "weapon_id" => "skip",
        "weapon_name" => "trim",
        "weapon_abbr" => "trim",
        "weapon_gender" => "enum=M,F"
    );

    public $weapon_id = null;
    public $weapon_name = null;
    public $weapon_abbr = null;
    public $weapon_gender = null;

    public static function ExportAll($export = true)
    {
        $model = new Weapon();
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

    public function selectAll($offset = 0, $pagesize = 0, $filter = null,$sort = null,$special = null)
    {
        return $this->select('*')->orderBy('weapon_id')->get();
    }

    public function count($filter = null, $special = null)
    {
        return $this->numrows()->count();
    }
}
