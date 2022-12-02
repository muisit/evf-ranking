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

 class Role extends Base {
    public $table = "TD_Role";
    public $pk="role_id";
    public $fields=array("role_id","role_name","role_type");
    public $fieldToExport=array(
        "role_id" => "id",
        "role_name" => "name",
        "role_type"=>"type",
        "role_type_name"=>"type_name",
        "org_declaration" => "org"
    );
    public $rules = array(
        "role_id"=>"skip",
        "role_name" => "trim|required",
        "role_type"=> "model=RoleType",
        "role_type_name" => "skip",
        "org_declaration" => "skip"
    );


    private function sortToOrder($sort) {
        if(empty($sort)) $sort="i";
        $orderBy=array();
        for($i=0;$i<strlen($sort);$i++) {
            $c=$sort[$i];
            switch($c) {
            default:
            case 'i': $orderBy[]="role_id asc"; break;
            case 'I': $orderBy[]="role_id desc"; break;
            case 'n': $orderBy[]="role_name asc"; break;
            case 'N': $orderBy[]="role_name desc"; break;
            }
        }
        return $orderBy;
    }

    private function addFilter($qb, $filter, $special)
    {
        if (is_string($filter)) $filter = array("name" => $filter);
        if (is_object($filter)) $filter = (array)$filter;
        if (isset($filter["name"]) && !empty(trim($filter["name"]))) {
            $filter = str_replace("%", "%%", trim($filter["name"]));
            $qb->where("role_name", "like", "%$filter%");
        }
    }

    public static function ListAll()
    {
        $model=new Role();
        $roles=$model->selectAll(0,100000,array(),"");
        return $roles;
    }

    public function selectAll($offset,$pagesize,$filter,$sort, $special=null) {
        $qb = $this->select('TD_Role.*, rt.role_type_name, rt.org_declaration')->join("TD_Role_Type","rt","TD_Role.role_type=rt.role_type_id")
          ->offset($offset)->limit($pagesize)->orderBy($this->sortToOrder($sort));
        $this->addFilter($qb,$filter,$special);
        return $qb->get();
    }

    public function count($filter, $special = null)
    {
        $qb = $this->numrows();
        $this->addFilter($qb, $filter, $special);
        return $qb->count();
    }

    public function delete($id=null) {
        if($id === null) $id = $this->getKey();
        $id=intval($id);

        // check that role is not used in 
        $nr1 = $this->numrows()->from("TD_Registration")->where("registration_role",$id)->count();
        $this->errors=array();
        if($nr1>0) {
            $this->errors[]="Cannot delete Role that is still used for Registrations";
        }
        if(sizeof($this->errors)==0) {
            return parent::delete($id);
        }
        return false;
    }

    public function selectAccreditations($event) {
        $templateIdByType = AccreditationTemplate::TemplateIdsByRole($event);
        $key="r".$this->getKey();
        $acceptableTemplates = isset($templateIdByType[$key]) ? $templateIdByType[$key] : array();

        $id = intval($this->getKey());
        if($id < 0) $id = 0;
        $accr = new Accreditation();

        $res = $accr->select('*')
            ->where_exists(function($qb) use ($id) {
                $qb->select("*")->from("TD_Registration")
                  ->where("registration_fencer=TD_Accreditation.fencer_id")
                  ->where("registration_mainevent=TD_Accreditation.event_id")
                  ->where("registration_role",$id);
            })
            ->where_in("template_id",$acceptableTemplates)
            ->where("event_id",$event->getKey())
            ->get();
        $retval = array();
        if (!empty($res)) {
            foreach ($res as $r) $retval[] = new Accreditation($r);
        }
        return $retval;
    }
}
 