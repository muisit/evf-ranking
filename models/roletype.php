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


 namespace EVFRanking;

 class RoleType extends Base {
    public $table = "TD_Role_Type";
    public $pk="role_type_id";
    public $fields=array("role_type_id","role_type_name","org_declaration");
    public $fieldToExport=array(
        "role_type_id" => "id",
        "role_type_name" => "name",
        "org_declaration"=>"org_declaration"
    );
    public $rules = array(
        "role_type_id"=>"skip",
        "role_type_name" => "trim|required",
        "org_declaration"=> "trim|enum=Country,Org,EVF,FIE"
    );


    public function __construct($id=null) {
        parent::__construct($id);
    }

    private function sortToOrder($sort) {
        if(empty($sort)) $sort="i";
        $orderBy=array();
        for($i=0;$i<strlen($sort);$i++) {
            $c=$sort[$i];
            switch($c) {
            default:
            case 'i': $orderBy[]="role_type_id asc"; break;
            case 'I': $orderBy[]="role_type_id desc"; break;
            case 'n': $orderBy[]="role_type_name asc"; break;
            case 'N': $orderBy[]="role_type_name desc"; break;
            }
        }
        return $orderBy;
    }

    private function addFilter($qb, $filter,$special) {
        if(!empty(trim($filter))) {
            $filter=str_replace("%","%%",$filter);
            $qb->where("role_type_name","like","%$filter%");
        }
    }

    public function selectAll($offset,$pagesize,$filter,$sort, $special=null) {
        $qb = $this->select('*')->offset($offset)->limit($pagesize)->orderBy($this->sortToOrder($sort));
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

    public function delete($id=null) {
        if($id === null) $id = $this->{$this->pk};

        // check that role_type is not used in Role
        $nr1 = $this->numrows()->from("TD_Role")->where("role_type",$id)->count();
        $this->errors=array();
        if($nr1>0) {
            $this->errors[]="Cannot delete Role Type that is still used for Roles";
        }
        if(sizeof($this->errors)==0) {
            return parent::delete($id);
        }
        return false;
    }
 }
 