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

use \DateTimeImmutable;

class Fencer extends Base {
    public $table = "TD_Fencer";
    public $pk = "fencer_id";
    public $fields = array("fencer_id","fencer_firstname","fencer_surname","fencer_country","fencer_dob",
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
    public $rules = array(
        "fencer_id" => "skip",
        "fencer_firstname" =>array("rules"=>"trim|ucfirst|required","message"=>"Please enter the first name of the fencer"),
        "fencer_surname" => array("rules"=>"trim|upper|required","message"=>"Surname is a required field"),
        "fencer_country" => array("rules"=>"model=Country|required","message"=>"Please select a valid country"),
        "country_name" => "skip",
        "fencer_dob" => array("rules"=>"date","message"=>"Please set a date of birth at least 20 years in the past"),
        "fencer_gender" => array("rules"=>"enum=M,F","message"=>"Please pick a valid gender"),
        "fencer_picture" => array("rules" => "enum=Y,N,A,R")
    );

    public $fencer_id = null;
    public $fencer_firstname = null;
    public $fencer_surname = null;
    public $fencer_country = null;
    public $fencer_dob = null;
    public $fencer_gender = null;
    public $fencer_picture = null;

    // Submodels
    public $country_name = null;

    public function __construct($id=null,$forceload=false) {
        parent::__construct($id,$forceload);
        $this->rules["fencer_dob"]["rule"]="date|lt=".date('Y-m-d',strtotime(time() - 20*365*24*60*60));
    }

    public function export($result = null)
    {
        $retval = parent::export($result);
        if (isset($this->basic)) {
            $retval["basic"] = $this->basic;
        }
        return $retval;
    }

    public function getFullName()
    {
        return strtoupper($this->fencer_surname) . ", " . $this->fencer_firstname;
    }

    public function delete($id = null) {
        if ($id === null) {
            $id = $this->getKey();
        }

        $cnt = $this->query()->from("TD_Result")->where("result_fencer", $id)->count();

        if (intval($cnt) == 0) {
            $this->query()->from("TD_Accreditation")->where("fencer_id", $id)->delete();
            $this->query()->from("TD_Registration")->where("registration_fencer", $id)->delete();
            //$this->query()->from("TD_Result")->where("result_fencer",$id)->delete();
            return parent::delete($id);
        }
        return false;
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

    public function postProcessing($data)
    {
        if (!empty($data) && isset($data["merge"]) && $data["merge"]) {
            $this->basic = array(
                "rankings" => $this->getRankingPositions(),
                "registrations" => $this->getRegistrations()
            );
        }
    }

    public function getCurrentCategory()
    {
        $date = DateTimeImmutable::createFromFormat('Y-m-d', $this->fencer_dob);
        if ($date === false) {
            return null;
        }
        $catnum = \EVFRanking\Models\Category::CategoryFromYear($date->format('Y'), date('Y-m-d'));
        if ($catnum < 1) {
            return null;
        }
        $model = new \EVFRanking\Models\Category();
        return new \EVFRanking\Models\Category($model->select('*')->where("category_value", $catnum)->first());
    }

    public function getRegistrations()
    {
        $regmodel = new \EVFRanking\Models\Registration();
        $regs = $regmodel->select('TD_Registration.*, e.event_name, e.event_open')->where('registration_fencer', $this->getKey())
            ->join("TD_Event", "e", "TD_Registration.registration_mainevent=e.event_id")
            ->where("e.event_open", '>', date('Y-m-d'))
            ->get();
        $retval = array();
        if (!empty($regs)) {
            foreach ($regs as $reg) {
                $date = DateTimeImmutable::createFromFormat('Y-m-d', $reg->event_open);
                $retval[$reg->registration_mainevent] = array(
                    $reg->event_name,
                    $date->format('Y')
                );
            }
        }
        return $retval;
    }

    public function getRankingPositions()
    {
        $retval = array();
        foreach (array("E","F","S") as $wpn) {
            $weapon = $this->gender == 'F' ? 'W' . $wpn : 'M' . $wpn;
            $ranking = $this->getRankingForWeapon($weapon);
            if (!empty($ranking)) {
                $retval[$weapon] = $ranking;
            }
        }
        return $retval;
    }

    public function getRankingForWeapon($weapon)
    {
        if (!is_object($weapon)) {
            $model = new \EVFRanking\Models\Weapon();
            $weapon = $model->select('*')->where("weapon_abbr", $weapon)->first();
            if (empty($weapon)) {
                return null;
            }
            $weapon = new \EVFRanking\Models\Weapon($weapon);
        }
        if (empty($weapon) || !$weapon->exists()) {
            return null;
        }
        $category = $this->getCurrentCategory();
        if (empty($category) || !$category->exists()) {
            return null;
        }
        $rankingmodel = new \EVFRanking\Models\Ranking();
        $rankings = $rankingmodel->listResults($weapon->getKey(), $category);
        foreach ($rankings as $ranking) {
            if ($ranking["id"] == $this->getKey()) {
                return $ranking;
            }
        }
        return null;
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

    public function getPath()
    {
        $dirname = "/home/veterans/api_storage/app/fencers";
        $filename = $dirname . "/fencer_" . $this->getKey() . ".dat";
        return $filename;
    }

    public function save()
    {
        $this->fencer_name = Fencer::Sanitize($this->fencer_surname);
        $this->fencer_firstname = Fencer::Sanitize($this->fencer_firstname);
        return parent::save();
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
                ->where("fencer_dob",date("Y-m-d",strtotime($modeldata['birthday'])))
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

        // this is the one place we retain the link to Accreditation and Registration
        $this->query()->from("TD_Accreditation")->set("fencer_id",$model1->getKey())->set('is_dirty',date('Y-m-d H:i:s'))->where("fencer_id",$model2->getKey())->update();
        $this->query()->from("TD_Registration")->set("registration_fencer",$model1->getKey())->where("registration_fencer",$model2->getKey())->update();
        $this->query()->from("TD_Result")->set("result_fencer",$model1->getKey())->where("result_fencer",$model2->getKey())->update();

        if(file_exists($model1->getPath())) {
            if(file_exists($model2->getPath())) {
                if(intval($model1->getKey()) < intval($model2->getKey())) {
                    // keep the one linked to the newest entry
                    @rename($model2->getPath(),$model1->getPath());
                    $model1->fencer_picture = $model2->fencer_picture;
                    $model1->save();
                }
            }
            // else don't do anything, the file for model1 is kept
        }
        else if(file_exists($model2->getPath())) {
            @rename($model2->getPath(),$model1->getPath());
            $model1->fencer_picture = $model2->fencer_picture;
            $model1->save();
        }

        $this->query()->from("TD_Fencer")->where("fencer_id",$model2->getKey())->delete();
        return array("messages"=>array("Fencers merged successfully"));
    }

    public static function Sanitize($value) {
        // trim whitespace in front and after
        $value = preg_replace("/(^\s+)|(\s+$)/u", "", $value);
        // replace any non-numeric, non-lexical, non-space characters
        $value = preg_replace("/[^-. '\p{L}\p{N}]/u", " ", $value);
        return $value;
    }

    public function doImportCheck($fencers, $cid)
    {
        global $evflogger;
        $evflogger->log("Fencer::doImportCheck for $cid and list of " . count($fencers) . " fencers");
        $retval = array("result" => array());
        $country = new Country($cid);

        foreach ($fencers as $entry) {
            $name = Fencer::Sanitize($entry["name"]);
            $firstname = Fencer::Sanitize($entry["firstname"]);
            $gender = trim(Fencer::Sanitize($entry['gender']));
            $countryAbbreviation = trim(Fencer::Sanitize($entry['country']));

            $birthdayText = isset($entry['birthday']) ? $entry['birthday'] : null;
            $birthday = $this->parseBirthdate($birthdayText);

            $retval["result"][] = $this->checkSingleFencer($name, $firstname, $gender, $birthday, $countryAbbreviation, $country, $entry["index"]);
        }
        return $retval;
    }

    private function checkSingleFencer($name, $firstname, $gender, $birthday, $countryAbbreviation, $country, $index)
    {
        global $evflogger;
        $retval = [
            "id" => -1,
            "index" => $index,
            "lastNameCheck" => 'nok',
            "firstNameCheck" => 'nok',
            "birthdayCheck" => 'nok',
            "countryCheck" => 'nok',
            "suggestions" => [],
            'error' => '',
            'firstname' => ucfirst(trim($firstname)),
            'name' => strtoupper(trim($name)),
            'birthday' => $birthday->format('Y-m-d'),
            'gender' => $gender
        ];

        if (strlen($retval['firstname']) == 0) {
            $retval['error'] .= 'Please enter a valid first name. ';
            $retval['firstNameCheck'] = 'err';
        }
        if (strlen($retval['name']) < 2) {
            $retval['error'] .= 'Please enter a valid last name. ';
            $retval['lastNameCheck'] = 'err';
        }
        $birthdayTime = strtotime($retval['birthday']);
        if ($birthdayTime > (time() - 12 * 365 * 24 * 60 * 60)) { // age of 12 minimum
            $retval['error'] .= 'Please enter a valid date of birth. ';
            $retval['birthdayCheck'] = 'err';
        }
        if ($birthdayTime < (time() - 110 * 365 * 24 * 60 * 60)) { // age of 110 maximum
            $retval['error'] .= 'Please enter a valid date of birth. ';
            $retval['birthdayCheck'] = 'err';
        }
        $birthyear = $birthday->format('Y');
        if ($birthyear == (new DateTimeImmutable('now'))->format('Y')) {
            $birthyear = null;
        }

        if (!in_array($gender, array('F','W','M'))) {
            $retval['error'] .= 'Gender ' . htmlspecialchars($gender) . ' is not allowed. Please enter a valid gender. ';
            $retval['lastNameCheck'] = 'err';
        }
        else if ($gender == 'W') {
            $retval['gender'] = 'F';
        }

        if (empty($countryAbbreviation) && !$country->exists()) {
            $retval['error'] .= 'Please select a correct country. ';
            $retval['countryCheck'] = 'err';
        }
        else {
            if (!empty($countryAbbreviation)) {
                $evflogger->log("selecting country based on $countryAbbreviation");
                $fencerCountry = $country->select('*')->where('country_abbr', $countryAbbreviation)->first();
                if (!empty($fencerCountry)) {
                    // override the country for picking the most probable selection
                    $country = new Country($fencerCountry);
                    $retval['country'] = $country->getKey();
                }
                else {
                    $retval['error'] .= 'Invalid country abbreviation ' . htmlspecialchars($countryAbbreviation) .'. ';
                    $retval['countryCheck'] = 'err';
                }
            }
            else {
                if (!$country->exists()) {
                    $retval['error'] .= 'Please enter a valid country for each fencer. ';
                    $retval['countryCheck'] = 'err';
                }
                else {
                    $retval['country'] = $country->getKey();
                }
            }
        }

        $allbyname = $this->allByName($name, $firstname, $gender);
        foreach ($allbyname as $fencer) {
            $values = (array)$fencer;
            if (!$country->exists() || $values["country_abbr"] == $country->country_abbr) {
                if ($birthyear != null) {
                    $birthyear2 = DateTimeImmutable::createFromFormat('Y-m-d', $values['fencer_dob'])->format('Y');
                }
                if ($birthyear === null || $birthyear2 == $birthyear) {
                    $retval["id"] = $values["fencer_id"];
                    $retval["lastNameCheck"] = 'ok';
                    $retval["firstNameCheck"] = 'ok';
                    $retval["countryCheck"] = 'ok';
                    $retval["birthdayCheck"] = $values["fencer_dob"] == $retval['birthday'] ? 'ok' : 'nok';
                    $retval["suggestions"] = array($this->export($values));
                    break;
                }
            }
        }
            
        // no match, but if we found exactly one fencer, it is only a country change
        if (sizeof($allbyname) == 1 && $retval["id"] < 0) {
            if ($birthyear != null) {
                $birthyear2 = DateTimeImmutable::createFromFormat('Y-m-d', $values['fencer_dob'])->format('Y');
            }
            if ($birthyear === null || $birthyear2 == $birthyear) {
                $values = (array)$allbyname[0];
                $retval["id"] = $values["fencer_id"];
                $retval["lastNameCheck"] = 'ok';
                $retval["firstNameCheck"] = 'ok';
                $retval["countryCheck"] = 'nok';
                $retval["birthdayCheck"] = $values["fencer_dob"] == $retval['birthday'] ? 'ok' : 'nok';
                $retval["suggestions"] = array($this->export($values));
            }
        }

        if ($retval['id'] < 0) {
            $suggestions = $this->findSuggestions($firstname, $name, $country->exists() ? $country->country_abbr : null, $gender);
            $retval["suggestions"] = $suggestions;
        }
        return $retval;
    }

    public function findSuggestions($firstname, $lastname, $country, $gender, $minDate = null, $maxDate = null)
    {
        $debug = false;
        $retval = array();
        $allbylastname = $this->allByLastNameSound($lastname, $gender);
        $allbyfirstname = $this->allByFirstNameSound($firstname, $gender);
        $allbycountry = empty($country) ? [] : $this->allByCountry($country, $gender);
        $tooyoungLN = [];
        $tooyoungFN = [];
        $tooyoungCN = [];
        if ($debug) error_log("allbylastname: " . count($allbylastname) . ", allbyfirstname: " . count($allbyfirstname) . ", allbycountry: " . count($allbycountry));

        $ln = array();
        foreach ($allbylastname as $f) {
            $v = (array)$f;
            $tm = DateTimeImmutable::createFromFormat('Y-m-d', $v['fencer_dob']);
            error_log("lastname checking " . $v['fencer_id'] .': ' . (is_bool($tm) ? "none" : $tm->format('Y-m-d')) . ' vs ' . $minDate?->format('Y-m-d') . ' / ' . $maxDate?->format('Y-m-d'));
            if ($tm !== false && (($minDate === null || $tm >= $minDate) && ($maxDate === null || $tm < $maxDate))) {
                if ($debug) error_log("lastname adding " . $v['fencer_id'] . '-' . $v['fencer_surname']);
                $ln["f_" . $v["fencer_id"]] = $v;
            }
            else if ($tm !== false && $maxDate !== null && $tm >= $maxDate) {
                if ($debug) error_log("lastname too young: adding " . $v['fencer_id'] . '-' . $v['fencer_surname']);
                $tooyoungLN["f_" . $v["fencer_id"]] = $v;
            }
        }
        $fn = array();
        foreach ($allbyfirstname as $f) {
            $v = (array)$f;
            $tm = DateTimeImmutable::createFromFormat('Y-m-d', $v['fencer_dob']);
            error_log("firstname checking ". $v['fencer_id'] .': '  . (is_bool($tm) ? "none" : $tm->format('Y-m-d')) . ' vs ' . $minDate?->format('Y-m-d') . ' / ' . $maxDate?->format('Y-m-d'));
            if ($tm !== false && (($minDate === null || $tm >= $minDate) && ($maxDate === null || $tm < $maxDate))) {
                if ($debug) error_log("firstname adding " . $v['fencer_id'] . '-' . $v['fencer_surname']);
                $fn["f_" . $v["fencer_id"]] = $v;
            }
            else if ($tm !== false && $maxDate !== null && $tm >= $maxDate) {
                if ($debug) error_log("firstname too young: adding " . $v['fencer_id'] . '-' . $v['fencer_surname']);
                $tooyoungFN["f_" . $v["fencer_id"]] = $v;
            }
        }
        $cn = array();
        foreach ($allbycountry as $f) {
            $v = (array)$f;
            $tm = DateTimeImmutable::createFromFormat('Y-m-d', $v['fencer_dob']);
            error_log("country checking ". $v['fencer_id'] .': '  . (is_bool($tm) ? "none" : $tm->format('Y-m-d')) . ' vs ' . $minDate?->format('Y-m-d') . ' / ' . $maxDate?->format('Y-m-d'));
            if ($tm !== false && (($minDate === null || $tm >= $minDate) && ($maxDate === null || $tm < $maxDate))) {
                if ($debug) error_log("country adding " . $v['fencer_id'] . '-' . $v['fencer_surname']);
                $cn["f_" . $v["fencer_id"]] = $v;
            }
            else if ($tm !== false && $maxDate !== null && $tm >= $maxDate) {
                if ($debug) error_log("country too young: adding " . $v['fencer_id'] . '-' . $v['fencer_surname']);
                $tooyoungCN["f_" . $v["fencer_id"]] = $v;
            }
        }
        if ($debug) error_log("allbylastname: " . count($ln) . ", allbyfirstname: " . count($fn) . ", allbycountry: " . count($cn));
        if ($debug) error_log("too young lastname: " . count($tooyoungLN) . ", firstname: " . count($tooyoungFN) . ", country: " . count($tooyoungCN));

        // find out the records that match 2 out of 3 fields
        $m1 = array_intersect(array_keys($ln), array_keys($fn));
        $m2 = array_intersect(array_keys($ln), array_keys($cn));
        $m3 = array_intersect(array_keys($fn), array_keys($cn));
        if ($debug) error_log("last+first: " . count($m1) . ", last+country: " . count($m2) . ", first+country: " . count($m3));
        $keys = array_unique(array_merge($m1, $m2, $m3));

        // do the same for the too-young fencers
        $tooyoungm1 = array_intersect(array_keys($tooyoungLN), array_keys($tooyoungFN));
        $tooyoungm2 = array_intersect(array_keys($tooyoungLN), array_keys($tooyoungCN));
        $tooyoungm3 = array_intersect(array_keys($tooyoungFN), array_keys($tooyoungCN));
        if ($debug) error_log("last+first: " . count($tooyoungm1) . ", last+country: " . count($tooyoungm2) . ", first+country: " . count($tooyoungm3));
        $tooyoungKeys = array_unique(array_merge($tooyoungm1, $tooyoungm2, $tooyoungm3));
        if ($debug) error_log(json_encode($tooyoungKeys));

        // if any list is very small and we have less than 10 values, add that list
        // first add fencers that were considered too young
        if (sizeof($keys) < 10 && sizeof($tooyoungKeys) < 10) {
            if ($debug) error_log("adding too-young fencers");
            $keys = array_unique(array_merge($keys, $tooyoungKeys));
            if ($debug) error_log(json_encode($keys));
        }

        // then add all matching lastnames (which are relatively country-specific)
        if (sizeof($keys) < 10 && sizeof($ln) < 10) {
            if ($debug) error_log("adding lastname matches");
            $keys = array_unique(array_merge($keys, array_keys($ln)));
            if ($debug) error_log(json_encode($keys));
        }
        // then add all matching firstnames (which are more international)
        if (sizeof($keys) < 10 && sizeof($fn) < 10) {
            if ($debug) error_log("adding firstname matches");
            $keys = array_unique(array_merge($keys, array_keys($fn)));
            if ($debug) error_log(json_encode($keys));
        }
        // then add all fencers from the same country
        if (sizeof($keys) < 10 && sizeof($cn) < 10) {
            if ($debug) error_log("adding country matches");
            $keys = array_unique(array_merge($keys, array_keys($cn)));
            if ($debug) error_log(json_encode($keys));
        }

        $ln = array_merge($ln, $tooyoungLN);
        $fn = array_merge($fn, $tooyoungFN);
        $cn = array_merge($cn, $tooyoungCN);
        $values = array_merge($ln, $fn, $cn);
        foreach ($keys as $k) {
            if (isset($values[$k])) {
                $vs = $this->export($values[$k]);
                $vs["inLn"] = isset($ln[$k]) ? 'ok' : 'nok';
                $vs["inFn"] = isset($fn[$k]) ? 'ok' : 'nok';
                $vs["inCn"] = isset($cn[$k]) ? 'ok' : 'nok';
                $vs["inAge"] = in_array($k, $tooyoungKeys) ? 'nok' : 'ok';
                $retval[] = $vs;
            }
        }
        return $retval;
    }

    private function parseBirthdate($date)
    {
        if (empty($date)) return time();

        $format = 'Y#m#d';
        $sep = '-';
        $pos = strpos($date, $sep);
        if ($pos === false) {
            $sep = '/';
            $pos = strpos($date, $sep);
        }
        if ($pos === false) {
            $format = 'Y#m#d';
        }
        else if ($pos > 2) {
            // first digit is too wide, expect YYYY/M/D
            $format = 'Y#m#d';
        }
        else if(strlen($date) < 9) {
            if ($sep == '/') {
                // expect M/D/YY
                $format = 'm#d#y';
            }
            else {
                $format = 'd#m#y';
            }
        }
        else if ($sep == '/') {
            // Windows/American date, expect M/D/YYYY
            $format = 'm#d#Y';
        }
        else {
            $format = 'Y#m#d';
        }

        $date = DateTimeImmutable::createFromFormat($format, $date);
        $time = strtotime($date->format('Y-m-d'));
        if ($time > time() && strpos($format, $y) !== false) {
            // in case we used 'y', it is interpreted as between 1970 and 2069, but we need to shift that
            // to the past. So we reformat the date into the 19-hundreds
            $date = DateTimeImmutable::createFromFormat('Y-m-d', '19' . $date->format('y-m-d'));
        }
        return $date;
    }
}
