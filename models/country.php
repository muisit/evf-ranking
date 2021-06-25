<?php

/**
 * EVF-Ranking Country Model
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

 class Country extends Base {
    public $table = "TD_Country";
    public $pk="country_id";
    public $fields=array("country_id","country_abbr","country_name","country_registered");
    public $fieldToExport=array(
        "country_id" => "id",
        "country_abbr" => "abbr",
        "country_name" => "name",
        "country_registered" => "registered",
    );
    public $rules = array(
        "country_id"=>"skip",
        "country_abbr" => "trim|upper|eq=3|required",
        "country_name" => "trim|gte=3|required",
        "country_registered" => "bool|required"
    );

    private function sortToOrder($sort) {
        if(empty($sort)) $sort="i";
        $orderBy=array();
        for($i=0;$i<strlen($sort);$i++) {
            $c=$sort[$i];
            switch($c) {
            default:
            case 'i': $orderBy[]="country_id asc"; break;
            case 'I': $orderBy[]="country_id desc"; break;
            case 'n': $orderBy[]="country_name asc"; break;
            case 'N': $orderBy[]="country_name desc"; break;
            }
        }
        return $orderBy;
    }

    private function addFilter($qb, $filter,$special) {
        if(!empty(trim($filter))) {
            $filter=str_replace("%","%%",$filter);
            $qb->where("country_name","like","%$filter%");
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

        // check that country is not used in Fencer or Event
        $nr1 = $this->numrows()->from("TD_Fencer")->where("fencer_country",$id)->count();
        $nr2 = $this->numrows()->from("TD_Event")->where("event_country",$id)->count();
        $this->errors=array();
        if($nr1>0) {
            $this->errors[]="Cannot delete country that is still used for fencers";
        }
        if($nr2>0) {
            $this->errors[]="Cannot delete country that is still used for events";
        }
        if(sizeof($this->errors)==0) {
            return parent::delete($id);
        }
        return false;
    }

    public function selectAccreditations($event) {
        // only select accreditations with an athlete or federative role template
        //$ses = SideEvent::SelectCompetitions($event);
        //$sids = array();
        //foreach ($ses as $sid) $sids[] = $sid->id;

        $templateIdByType = AccreditationTemplate::TemplateIdsByRoleType($event);
        $rtype = RoleType::FindByType("Country");
        $athleteTemplates = isset($templateIdByType["r0"]) ? $templateIdByType["r0"] : array();
        $federativeTemplates = isset($templateIdByType["r".$rtype->getKey()]) ? $templateIdByType["r".$rtype->getKey()] : array();
        $acceptableTemplates=array_merge($athleteTemplates, $federativeTemplates);

        $accr=new Accreditation();
        $res = $accr->select('*')
            ->join("TD_Fencer","f","f.fencer_id=TD_Accreditation.fencer_id","inner")
            ->where('f.fencer_country', $this->getKey())
            ->where_in("TD_Accreditation.template_id",$acceptableTemplates)
            ->where("event_id",$event->getKey())
            ->get();
        $retval = array();
        foreach ($res as $r) $retval[] = new Accreditation($r);
        return $retval;
    }
 }
 