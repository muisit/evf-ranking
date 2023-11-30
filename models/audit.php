<?php

/**
 * EVF-Ranking Audit Model
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

 class Audit extends Base {
    public $table = "TD_Audit";
    public $pk="id";
    public $fields=array("id","creator","created","model","modelid","log","data");
    public $fieldToExport=array(
        "id" => "id",
        "creator" => "creator",
        "created"=>"created",
        "model"=>"model",
        "modelid"=>"modelid",
        "log"=>"log"
    );
    public $rules = array(
        "id"=>"skip",
        "creator" => "skip",
        "created" => "skip",
        "model"=> "enum=Registration,Accreditation",
        "modelid" => "int",
        "log" => "trim",
        "data" => "json"
    );

    public function save() {
        if($this->isNew()) {
            $this->created=date('Y-m-d H:i:s');
            $this->creator = -1;

            $user = wp_get_current_user();
            if (!empty($user)) {
                $this->creator=$user->ID;
            }
            return parent::save();
        }
        return false; // not allowed to update
    }

    private function sortToOrder($sort) {
        if(empty($sort)) $sort="i";
        $orderBy=array();
        for($i=0;$i<strlen($sort);$i++) {
            $c=$sort[$i];
            switch($c) {
            default:
            case 'i': $orderBy[]="id asc"; break;
            case 'I': $orderBy[]="id desc"; break;
            case 'c': $orderBy[]="created asc, id asc"; break;
            case 'C': $orderBy[]="created desc, id desc"; break;
            }
        }
        return $orderBy;
    }

    private function addFilter($qb, $filter,$special) {
        if(is_object($filter)) $filter=(array)$filter;
        if(isset($filter["model"]) && !empty(trim($filter["model"]))) {
            $qb->where("model",$filter["model"]);
        }
        if (isset($filter["modelid"]) && !empty(trim($filter["modelid"]))) {
            $qb->where("modelid", intval($filter["modelid"]));
        }
    }

    public function selectAll($offset,$pagesize,$filter,$sort, $special=null) {
        $qb = $this->select('*')
          ->offset($offset)->limit($pagesize)->orderBy($this->sortToOrder($sort));
        $this->addFilter($qb,$filter,$special);
        return $qb->get();
    }

    public function count($filter,$special=null) {
        $qb = $this->numrows();
        $this->addFilter($qb,$filter,$special);
        return $qb->count();
    }

    public static function SelectAllOfModel($model,$id) {
        $model=new Audit();
        return $model->selectAll(null,null,array("model"=>$model,"modelid"=>intval($id)),"C");
    }

    private function getClassFromModel($model) {
        $cls=explode('\\',get_class($model));
        $clsname=end($cls);
        switch($clsname) {
        case 'Accreditation':
        case 'Registration': return $clsname; $amodel->model=$clsname; break;
        default: return null;
        }
    }

    public static function Create($model,$log,$data=null) {
        $amodel=new Audit();
        $amodel->modelid=$model->getKey();
        $amodel->model=$amodel->getClassFromModel($model);
        if(empty($amodel->model)) return;

        $amodel->log=$log;
        if(!empty($data)) {
            if(!is_string($data)) $data=json_encode($data);
            $amodel->data=$data;
        }
        $amodel->save();
    }

    public static function Clear($model) {
        $amodel=new Audit();
        $clsname=$amodel->getClassFromModel($model);
        if(!empty($clsname)) {
            $amodel->query()->where("model", $clsname)->where("modelid",$model->getKey())->delete();
        }
    }
}
 