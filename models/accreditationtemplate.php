<?php

/**
 * EVF-Ranking RoleType Model
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

 class AccreditationTemplate extends Base {
    public $table = "TD_Accreditation_Template";
    public $pk="id";
    public $fields=array("id","name","content");
    public $fieldToExport=array(
        "id" => "id",
        "name" => "name",
        "content"=>"content"
    );
    public $rules = array(
        "id"=>"skip",
        "name" => "trim|required|lte=200",
        "content"=> "trim"
    );

    private function sortToOrder($sort) {
        if(empty($sort)) $sort="i";
        $orderBy=array();
        for($i=0;$i<strlen($sort);$i++) {
            $c=$sort[$i];
            switch($c) {
            default:
            case 'i': $orderBy[]="id asc"; break;
            case 'I': $orderBy[]="id desc"; break;
            case 'n': $orderBy[]="name asc"; break;
            case 'N': $orderBy[]="name desc"; break;
            }
        }
        return $orderBy;
    }

    private function addFilter($qb, $filter,$special) {
        if(!empty(trim($filter))) {
            $filter=str_replace("%","%%",$filter);
            $qb->where("name","like","%$filter%");
        }
    }

    public function selectAll($offset,$pagesize,$filter,$sort, $special=null) {
        $qb = $this->select('*')->offset($offset)->limit($pagesize)->orderBy($this->sortToOrder($sort));
        $this->addFilter($qb,$filter,$special);
        return $qb->get();
    }

    public function count($filter,$special=null) {
        $qb = $this->numrows();
        $this->addFilter($qb,$filter,$special);
        return $qb->count();
    }

    public function delete($id=null) {
        if($id === null) $id = $this->getKey();

        // check that template is not used in accreditation
        $nr1 = $this->numrows()->from("TD_Accreditation")->where("template_id",$id)->count();
        $this->errors=array();
        if($nr1>0) {
            $this->errors[]="Cannot delete Accreditation Template that is still used for Accreditations";
        }
        if(sizeof($this->errors)==0) {
            return parent::delete($id);
        }
        return false;
    }
 }
 