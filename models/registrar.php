<?php

/**
 * EVF-Ranking Registrar Model
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

 class Registrar extends Base {
    public $table = "TD_Registrar";
    public $pk="id";
    public $fields=array("id","user_id","country_id");
    public $fieldToExport=array(
        "id" => "id",
        "user_id" => "user",
        "country_id"=>"country",
        "country_name"=>"country_name",
        "user_nicename"=>"name"
    );
    public $rules = array(
        "id"=>"skip",
        "user_id" => array("rules"=>"model=User|required","label"=>"Please select a valid Wordpress user"),
        "country_id"=> "model=Country",
        "country_name" => "skip",
        "user_nicename" => "skip"
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
            case 'i': $orderBy[]="id asc"; break;
            case 'I': $orderBy[]="id desc"; break;
            case 'n': $orderBy[]="user_nicename asc"; break;
            case 'N': $orderBy[]="user_nicename desc"; break;
            case 'c': $orderBy[]="country_name asc"; break;
            case 'C': $orderBy[]="country_name desc"; break;
            }
        }
        return $orderBy;
    }

    private function addFilter($qb, $filter,$special) {
        if(!empty(trim($filter))) {
            $filter=str_replace("%","%%",$filter);
            $qb->where("role_name","like","%$filter%");
        }
    }

    public function selectAll($offset,$pagesize,$filter,$sort, $special=null) {
        $qb = $this->select('TD_Registrar.*, IFNULL(c.country_name,\'General Administration\') as country_name, u.user_nicename')
          ->join("TD_Country","c","TD_Registrar.country_id=c.country_id")
          ->join("wp_users", "u", "TD_Registrar.user_id=u.ID")
          ->offset($offset)->limit($pagesize)->orderBy($this->sortToOrder($sort));
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

    public function findByUser($userid) {
        $vals = $this->select('*')->where('user_id',$userid)->first();
        if(!empty($vals)) {
            return new Registrar($vals);
        }
        return null;
    }
 }
 