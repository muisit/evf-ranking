<?php

/**
 * EVF-Ranking Fencer Model
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

 class Fencer extends Base {
    public $table = "TD_Fencer";
    public $pk="fencer_id";
    public $fields=array("fencer_id","fencer_firstname","fencer_surname","fencer_country","fencer_dob",
        "fencer_gender", "fencer_picture","country_name");
    public $fieldToExport = array(
        "fencer_id" => "id",
        "fencer_firstname" => "firstname",
        "fencer_surname" => "name",
        "fencer_country" => "country",
        "country_name" => "country_name",
        "fencer_dob" => "birthday",
        "fencer_gender" => "gender",
        "fencer_picture" => "picture"
    );
    public $rules=array(
        "fencer_id" => "skip",
        "fencer_firstname" =>array("rules"=>"trim|ucfirst|required","message"=>"Please enter the first name of the fencer"),
        "fencer_surname" => array("rules"=>"trim|upper|required","message"=>"Surname is a required field"),
        "fencer_country" => array("rules"=>"model=Country|required","message"=>"Please select a valid country"),
        "country_name" => "skip",
        "fencer_dob" => array("rules"=>"date","message"=>"Please set a date of birth at least 20 years in the past"),
        "fencer_gender" => array("rules"=>"enum=M,F","message"=>"Please pick a valid gender"),
        "fencer_picture" => array("rules" => "enum=Y,N,A,R")
    );

    public function __construct($id=null,$forceload=false) {
        parent::__construct($id,$forceload);
        $this->rules["fencer_dob"]["rule"]="date|lt=".strftime('%F',strtotime(time() - 20*365*24*60*60));
    }

    public function delete($id=null) {
        if($id === null) $id = $this->getKey();

        $cnt = $this->query()->from("TD_Result")->where("result_fencer",$id)->count();

        if(intval($cnt) == 0) {
            $this->query()->from("TD_Accreditation")->where("fencer_id",$id)->delete();
            $this->query()->from("TD_Registration")->where("registration_fencer",$id)->delete();
            $this->query()->from("TD_Result")->where("result_fencer",$id)->delete();
            parent::delete($id);
        }
    }

    private function sortToOrder($sort) {
        if(empty($sort)) $sort="i";
        $orderBy=array();
        for($i=0;$i<strlen($sort);$i++) {
            $c=$sort[$i];
            switch($c) {
            default:
            case 'i': $orderBy[]="fencer_id asc"; break;
            case 'I': $orderBy[]="fencer_id desc"; break;
            case 'n': $orderBy[]="fencer_surname asc"; break;
            case 'N': $orderBy[]="fencer_surname desc"; break;
            case 'f': $orderBy[]="fencer_firstname asc"; break;
            case 'F': $orderBy[]="fencer_firstname desc"; break;
            case 'c': $orderBy[]="country_name asc"; break;
            case 'C': $orderBy[]="country_name desc"; break;
            case 'g': $orderBy[]="fencer_gender asc"; break;
            case 'G': $orderBy[]="fencer_gender desc"; break;
            case 'b': $orderBy[]="fencer_dob asc"; break;
            case 'B': $orderBy[]="fencer_dob desc"; break;
            }
        }
        return $orderBy;
    }

    private function addFilter($qb, $filter,$special) {
        if(is_string($filter)) $filter=json_decode($filter,true);
        if(!empty($filter)) {
            if(isset($filter["name"])) {
                global $wpdb;
                $name = Fencer::Sanitize($filter["name"]);
                $name=esc_sql($wpdb->esc_like($name));
                //$filter=str_replace("%","%%",$filter);
                //$qb->where("(fencer_surname like '$name%' or fencer_firstname like '$name%')");
                $qb->where("(fencer_surname like '$name%')");
            }
            if(isset($filter["country"])) {
                $qb->where("fencer_country",intval($filter["country"]));
            }
        }
    }

    public function selectAll($offset,$pagesize,$filter,$sort,$special=null) {
        $qb = $this->select('TD_Fencer.*, c.country_name')->join("TD_Country","c","TD_Fencer.fencer_country=c.country_id")
            ->offset($offset)->limit($pagesize)->orderBy($this->sortToOrder($sort));
        $this->addFilter($qb,$filter,$special);
        return $qb->get();
    }

    public function count($filter,$special=null) {
        $qb = $this->numrows();
        $this->addFilter($qb,$filter,$special);
        return $qb->count();
    }

    public function allByName($lastname,$firstname,$gender) {
        $lastname=Fencer::Sanitize($lastname);
        $firstname=Fencer::Sanitize($firstname);
        return $this->select('TD_Fencer.*, c.country_abbr')->join("TD_Country","c","TD_Fencer.fencer_country=c.country_id")
            ->where("fencer_surname",$lastname)
            ->where("fencer_firstname",$firstname)
            ->where("fencer_gender",$gender)
            ->get();
    }

    public function allByLastNameSound($lastname,$gender) {
        $lastname=Fencer::Sanitize($lastname);
        return $this->select('TD_Fencer.*, c.country_abbr')->join("TD_Country","c","TD_Fencer.fencer_country=c.country_id")
            ->where("SOUNDEX(fencer_surname)=SOUNDEX('".esc_sql($lastname)."')")->where("fencer_gender",$gender)->get();
    }

    public function allByFirstNameSound($name,$gender) {
        $name=Fencer::Sanitize($name);
        return $this->select('TD_Fencer.*, c.country_abbr')->join("TD_Country","c","TD_Fencer.fencer_country=c.country_id")
            ->where("SOUNDEX(fencer_firstname)=SOUNDEX('".esc_sql($name)."')")->where("fencer_gender",$gender)->get();
    }
    public function allByCountry($name,$gender) {
        return $this->select('TD_Fencer.*, c.country_abbr')->join("TD_Country","c","TD_Fencer.fencer_country=c.country_id")
            ->where("c.country_abbr",$name)->where("fencer_gender",$gender)->get();
    }

    public function getPath() {
        $upload_dir = wp_upload_dir();
        $dirname = $upload_dir['basedir'] . '/accreditations';
        $filename = $dirname . "/fencer_" . $this->getKey() . ".jpg";
        return $filename;
    }

    public function save() {
        $this->fencer_name = Fencer::Sanitize($this->fencer_surname);
        $this->fencer_firstname = Fencer::Sanitize($this->fencer_firstname);
        if(parent::save()) {
            $accr=new Accreditation();
            $accr->makeDirty($this->getKey());

            return true;
        }
        return false;
    }

    public function filterData($data, $caps) {
        error_log("filtering data for ".json_encode($caps));
        // filter out irrelevant data depending on the capability
        $retval=array();
        if(in_array($caps, array("accreditation"))) {
            $retval=array(
                "id" => isset($data["id"]) ? intval($data["id"]) :-1,
                "picture" => isset($data["picture"]) ? $data["picture"] : 'N'
            );
        }
        // system and registrars can save all fencer data
        else if(in_array($caps, array("system", "organiser", "registrar","hod","hod-view"))) {
            $retval=$data;
        }
        return parent::filterData($retval,$caps);
    }

    public function preSaveCheck($modeldata) {
        // this check is meant to allow the front-end to check if a given fencer may have,
        // per-chance, a duplicate in the database. If so, we can signal the user that he
        // may need to request a change-of-country
        // We only do this for new entries, not for existing ones.
        if(intval($modeldata['id']) <= 0) {
            $name=Fencer::Sanitize($modeldata['name']);
            $firstname=Fencer::Sanitize($modeldata['firstname']);
            $results = $this->select('TD_Fencer.*, c.country_name')
                ->join("TD_Country","c","TD_Fencer.fencer_country=c.country_id")
                ->where('SOUNDEX(\''.addslashes($firstname).'\')=SOUNDEX(fencer_firstname)')
                ->where('SOUNDEX(\''.addslashes($name).'\')=SOUNDEX(fencer_surname)')
                ->where("fencer_dob",strftime("%F",strtotime($modeldata['birthday'])))
                ->get();
            $retval=array();
            foreach($results as $row) {
                $fencer=new Fencer($row);
                $retval[]=$fencer->export();
            }
            return array("suggestions"=>$retval);
        }
        return array(); 
    }

    public function merge($modeldata) {
        // merge two fencers in the database. All data from fencer 1 is retained, but
        // fencer 2 is removed
        if(!isset($modeldata['id1']) || !isset($modeldata['id2'])) {
            return array("error"=> true, "messages"=>array("Invalid call, missing parameters"));
        }
        $model1=new Fencer(intval($modeldata['id1']),true);
        $model2=new Fencer(intval($modeldata['id2']),true);

        if(!$model1->exists() || !$model2->exists()) {
            return array("error"=> true, "messages"=>array("Fencer does not exist"));
        }
        if($model1->getKey() == $model2->getKey()) {
            return array("error"=> true, "messages"=>array("Cannot merge fencer with him/herself"));
        }

        $this->query()->from("TD_Accreditation")->set("fencer_id",$model1->getKey())->set('is_dirty',strftime('%F %T'))->where("fencer_id",$model2->getKey())->update();
        $this->query()->from("TD_Registration")->set("registration_fencer",$model1->getKey())->where("registration_fencer",$model2->getKey())->update();
        $this->query()->from("TD_Result")->set("result_fencer",$model1->getKey())->where("result_fencer",$model2->getKey())->update();

        if(file_exists($model1->getPath())) {
            if(file_exists($model2->getPath())) {
                if(intval($model1->getKey()) < intval($model2->getKey())) {
                    // keep the one linked to the newest entry
                    @rename($model2->getPath(),$model1->getPath());
                }
            }
            // else don't do anything, the file for model1 is kept
        }
        else if(file_exists($model2->getPath())) {
            @rename($model2->getPath(),$model1->getPath());
        }

        $this->query()->from("TD_Fencer")->where("fencer_id",$model2->getKey())->delete();
        return array("messages"=>array("Fencers merged successfully"));
    }

    public static function Sanitize($value) {
        //error_log("sanitizing ".json_encode($value));
        // trim whitespace in front and after
        $value = preg_replace("/(^\s+)|(\s+$)/u", "", $value);
        // replace any non-numeric, non-lexical, non-space characters
        $value = preg_replace("/[^- '\p{L}\p{N}]/u", " ", $value);
        //error_log("returning ".json_encode($value));
        return $value;
    }

}
 