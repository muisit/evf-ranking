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

    public function getFullName()
    {
        return strtoupper($this->fencer_surname) . ", " . $this->fencer_firstname;
    }

    public function delete($id=null) {
        if($id === null) $id = $this->getKey();

        $cnt = $this->query()->from("TD_Result")->where("result_fencer",$id)->count();

        if(intval($cnt) == 0) {
            $this->query()->from("TD_Accreditation")->where("fencer_id",$id)->delete();
            $this->query()->from("TD_Registration")->where("registration_fencer",$id)->delete();
            //$this->query()->from("TD_Result")->where("result_fencer",$id)->delete();
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

    public function save()
    {
        $this->fencer_name = Fencer::Sanitize($this->fencer_surname);
        $this->fencer_firstname = Fencer::Sanitize($this->fencer_firstname);
        if (parent::save()) {
            $accr = new Accreditation();
            $accr->makeDirty($this->getKey());

            return true;
        }
        return false;
    }

    public function filterData($data, $caps) {
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
        // trim whitespace in front and after
        $value = preg_replace("/(^\s+)|(\s+$)/u", "", $value);
        // replace any non-numeric, non-lexical, non-space characters
        $value = preg_replace("/[^- '\p{L}\p{N}]/u", " ", $value);
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

    public function findSuggestions($firstname, $lastname, $country, $gender)
    {
        $retval = array();
        $allbylastname = $this->allByLastNameSound($lastname, $gender);
        $allbyfirstname = $this->allByFirstNameSound($firstname, $gender);
        $allbycountry = empty($country) ? [] : $this->allByCountry($country, $gender);

        $ln = array();
        foreach ($allbylastname as $f) {
            $v = (array)$f;
            $ln["f_" . $v["fencer_id"]] = $v;
        }
        $fn = array();
        foreach ($allbyfirstname as $f) {
            $v = (array)$f;
            $fn["f_" . $v["fencer_id"]] = $v;
        }
        $cn = array();
        foreach ($allbycountry as $f) {
            $v = (array)$f;
            $cn["f_" . $v["fencer_id"]] = $v;
        }

        // find out the records that match 2 out of 3 fields
        $m1 = array_intersect(array_keys($ln), array_keys($fn));
        $m2 = array_intersect(array_keys($ln), array_keys($cn));
        $m3 = array_intersect(array_keys($fn), array_keys($cn));
        $keys = array_unique(array_merge($m1, $m2, $m3));

        // if any list is very small and we have less than 10 values, add that list
        // first add all matching lastnames (which are relatively country-specific)
        if (sizeof($keys) < 10 && sizeof($ln) < 10) $keys = array_unique(array_merge($keys, array_keys($ln)));
        // then add all matching firstnames (which are more international)
        if (sizeof($keys) < 10 && sizeof($fn) < 10) $keys = array_unique(array_merge($keys, array_keys($fn)));
        // then add all fencers from the same country
        if (sizeof($keys) < 10 && sizeof($cn) < 10) $keys = array_unique(array_merge($keys, array_keys($cn)));

        $values = array_merge($ln, $fn, $cn);
        foreach ($keys as $k) {
            if (isset($values[$k])) {
                $vs = $this->export($values[$k]);
                $vs["inLn"] = isset($ln[$k]) ? 'ok' : 'nok';
                $vs["inFn"] = isset($fn[$k]) ? 'ok' : 'nok';
                $vs["inCn"] = isset($cn[$k]) ? 'ok' : 'nok';
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
            return strtotime($date);
        }
        if ($pos > 2) {
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
        global $evflogger;
        $evflogger->log("parsing $date using $format ($pos, '$sep')");
        $date = DateTimeImmutable::createFromFormat($format, $date);
        $time = strtotime($date->format('Y-m-d'));
        if ($time > time() && strpos($format, $y) !== false) {
            // in case we used 'y', it is interpreted as between 1970 and 2069, but we need to shift that
            // to the past. So we reformat the date into the 19-hundreds
            $evflogger->log('reformatting date to 20th century: ' . '19' . $date->format('y-m-d'));
            $date = DateTimeImmutable::createFromFormat('Y-m-d', '19' . $date->format('y-m-d'));
        }
        return $date;
    }
}
