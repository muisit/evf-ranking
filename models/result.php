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

 class Result extends Base {
    public $table = "TD_Result";
    public $pk="result_id";
    public $fields=array(
        "result_id","result_competition","result_fencer", 
        "result_place","result_points", "result_entry",
        "result_de_points","result_podium_points","result_total_points",
        "fencer_firstname", "fencer_surname", "country_abbr", "country_id"
    );

    public $fieldToExport = array(
        "result_id" => "id",
        "result_competition" => "competition_id",
        "result_fencer" => "fencer_id",
        "result_place" => "place",
        "result_points" => "points",
        "result_entry" => "entry",
        "result_de_points" => "de_points",
        "result_podium_points" => "podium_points",
        "result_total_points" => "total_points",
        "fencer_firstname" => "fencer_firstname",
        "fencer_surname" => "fencer_surname",
        "country_abbr" => "country",
        "country_id" => "country_id"
    );
    public $rules=array(
        "result_id" => "skip",
        "result_competition" => "skip",
        "fencer_firstname" => "skip",
        "fencer_surname" => "skip",
        "country_abbr" => "skip",
        "country_id" => "skip",
        "result_fencer" => array("rules" => "model=Fencer|required","message"=>"Please select a valid fencer"),
        "result_place" => array("rules" => "int|required"),
        "result_points" => array("rules"=> "float"),
        "result_entry" => array("rules" => "int"),
        "result_de_points"  => array("rules" => "float"),
        "result_podium_points" => array("rules" => "float"),
        "result_total_points" => array("rules" => "float")
    );

    private function sortToOrder($sort) {
        if(empty($sort)) $sort="i";
        $orderBy=array();
        for($i=0;$i<strlen($sort);$i++) {
            $c=$sort[$i];
            switch($c) {
            default:
            case 'p': $orderBy[]="result_place asc"; break;
            case 'P': $orderBy[]="result_place desc"; break;
            case 't': $orderBy[]="result_total_points asc"; break;
            case 'T': $orderBy[]="result_total_points desc"; break;
            case 's': $orderBy[]="result_points asc"; break;
            case 'S': $orderBy[]="result_points desc"; break;
            case 'i': $orderBy[]="result_id asc"; break;
            case 'I': $orderBy[]="result_id desc"; break;
            case 'n': $orderBy[]="fencer_surname asc"; break;
            case 'N': $orderBy[]="fencer_surname desc"; break;
            case 'f': $orderBy[]="fencer_firstname asc"; break;
            case 'F': $orderBy[]="fencer_firstname desc"; break;
            case 'c': $orderBy[]="country_name asc"; break;
            case 'C': $orderBy[]="country_name desc"; break;
            }
        }
        return $orderBy;
    }

    private function addFilter($qb, $filter,$special) {
        if(!empty(trim($filter))) {
            global $wpdb;
            $filter=$wpdb->esc_like($filter);
            //$filter=str_replace("%","%%",$filter);
            $qb->where( function($qb2) use ($filter) {
                $qb2->where("fencer_surname like '%$filter%' or fencer_firstname like '%$filter%' or country_name like '%$filter%'");
            });
        }
        if($special) {
            $doc = json_decode($special);
            if(is_object($doc)) {
                if(isset($doc->event_id)) {
                    $qb->where("cm.competition_event",$doc->event_id);
                }
                if (isset($doc->category_id)) {
                    $qb->where("cm.competition_category", $doc->category_id);
                }
                if (isset($doc->weapon_id)) {
                    $qb->where("cm.competition_weapon", $doc->weapon_id);
                }
                if (isset($doc->competition_id)) {
                    $qb->where("cm.competition_id", $doc->competition_id);
                }
            }
        }
    }

    public function selectAll($offset,$pagesize,$filter,$sort,$special=null) {
        $qb = $this->select('TD_Result.*, f.fencer_surname, f.fencer_firstname, c.country_abbr, c.country_id')
            ->join("TD_Fencer","f","TD_Result.result_fencer=f.fencer_id")
            ->join("TD_Country","c","f.fencer_country=c.country_id")
            ->join("TD_Competition", "cm", "TD_Result.result_competition=cm.competition_id")
            ->offset($offset)->limit($pagesize)->orderBy($this->sortToOrder($sort));

        $this->addFilter($qb,$filter,$special);
        return $qb->get();
    }

    public function count($filter,$special=null) {
        $qb = $this->numrows()
            ->join("TD_Competition", "cm", "TD_Result.result_competition=cm.competition_id");
        $this->addFilter($qb,$filter,$special);
        return $qb->count();
    }

    public function recalculate($competition_id) {
        $competition=null;
        if(!is_object($competition)) {
            $competition = new Competition(intval($competition_id));
            $competition->load();
        }

        $results = $this->select('*')->where('result_competition',$competition->competition_id)->orderBy("result_place")->get();
        if($results && sizeof($results)) {
            $event = new Event($competition->competition_event);
            $event->load();

            $factor=floatval($event->event_factor);
            // error situation, but we'll correct and ignore
            if($factor<=0.0000001) {
                $factor=1.0;
            }

            foreach($results as $r) {
                $res = new Result($r);
                $res->result_entry = sizeof($results);
                $this->recalculateResult($res,$factor);
            }
        }
        return array();
    }

    public function doImport($obj) {
        $fencer = new Fencer();
        $competition = new Competition();
        $event = new Event();

        $obj=(array)$obj;
        if(isset($obj["competition_id"])) {
            $competition = $competition->get($obj["competition_id"]);
        }

        $errors=array();
        if(!$competition->isNew()) {
            $event = $event->get($competition->competition_event);
            $factor=floatval($event->event_factor);
            // error situation, but we'll correct and ignore
            if($factor<=0.0000001) {
                $factor=1.0;
            }
    
            if(isset($obj["ranking"]) && is_array($obj["ranking"])) {
                $start=1;
                foreach($obj["ranking"] as $entry) {
                    $pos = intval($entry["pos"]);
                    $fencerid = intval($entry["fencer_id"]);
                    $fmodel = $fencer->get($fencerid);
                    if(!$fmodel || $fmodel->isNew()) {
                        $errors[]="Unknown fencer at position $pos called ".$entry["lastname"].", ".$entry["firstname"]."\r\n";
                    }

                    if($pos < $start) {
                        $errors[]="Invalid position $pos after $start for fencer ".$entry["lastname"].", ".$entry["firstname"]."\r\n";
                    }
                }

                if(sizeof($errors) == 0) {
                    $totalparticipants=sizeof($obj["ranking"]);
                    $this->clear($competition->competition_id);
                    foreach($obj["ranking"] as $entry) {
                        $pos = intval($entry["pos"]);
                        $fencerid = intval($entry["fencer_id"]);
                        $res = $this->createResult($competition, $fencerid, $pos, $totalparticipants);
                        $this->recalculateResult($res,$factor);
                    }
                }
            }
            else {
                $errors[]="Invalid data object\r\n";
            }

        }
        else {
            $errors[]="No competition found\r\n";
        }
        if(sizeof($errors)) {
            return array("error"=>$errors);
        }
        return array();
    }

    public function clear($cid) {
        $this->query()->where("result_competition",intval($cid))->delete();
        return array();
    }

    private function createResult($competition, $fid, $pos, $total) {
        $res = new Result();
        $res->result_competition = $competition->competition_id;
        $res->result_fencer = $fid;
        $res->result_place = $pos;
        $res->result_entry = $total;
        return $res;
    }

    private function recalculateResult($res, $factor) {
        $pos=$res->result_place;
        $total=$res->result_entry;

        $max_points = 50;        
        if($pos > 0)
        {
            // Place factor: 1st place gets Max_points, last place (= size of entry) gets one point)
            // Intermediate places are log curve = MP - (MP-1) * log(x)/log(N) 
            if( ($total <= 1) && ($pos == 1) ) {
                $res->result_points = $max_points;
            }
            else {
                $res->result_points = $max_points - ($max_points -1) * log($pos) /log($total);
            }
        } 

        $round_bonus = 10;          // Points for surviving each round of DE 
        $res->result_de_points = 0;
        if($pos > 0 && $total>1) {
           $res->result_de_points = intval(log($total, 2)) - ceil(log($pos,2)) + 1 ;
        }        
        $res->result_de_points *= $round_bonus;
       
        $podium_bonus = 3 * (pow($total,1/3));          // Points for reaching podium 
        $res->result_podium_points = 0;       
        if($pos == 1) {
            $res->result_podium_points = 3 * $podium_bonus;
        } 
        elseif($pos == 2) {
            $res->result_podium_points = 2 * $podium_bonus;
        } 
        elseif( ($pos == 3) || ($pos == 4) ) {
            $res->result_podium_points = 1 * $podium_bonus;
        }

        $res->result_total_points =  $factor * ($res->result_points + $res->result_de_points + $res->result_podium_points);
        $res->save();
    }

    public function doImportCheck($ranking) {
        // ranking consists of a list of pos,lastname,firstname,country values
        // Check for each entry that the combination of lastname, firstname, country exists
        //
        $model = new Fencer();
                
        $retval=array("ranking"=>array());
        foreach($ranking as $entry) {
            // we leave 'position' as it is: an integer front-end check can be done there without problem
            $lastname = $entry["lastname"];
            $firstname = $entry["firstname"];
            $country = $entry["country"];

            $fencerid=-1;
            $suggestions=null;
            $ltext='';
            $lcheck='und';
            $ftext='';
            $fcheck='und';
            $ctext='';
            $ccheck='und';
            $atext='';
            $acheck='und';

            $allbyname = $model->allByName($lastname,$firstname);
            // see if anyone of these results matches the country abbreviation
            foreach($allbyname as $fencer) {
                $values = (array)$fencer;
                if($values["country_abbr"] === $country) {
                    $fencerid = $values["fencer_id"];
                    $lcheck='ok';
                    $fcheck='ok';
                    $ccheck='ok';
                    $acheck='ok';
                    $suggestions=array($model->export($values));
                    break;
                }
            }
            if(sizeof($allbyname) == 1 && $fencerid < 0) {
                $values=(array)$allbyname[0];
                $fencerid = $values["fencer_id"];
                $lcheck='ok';
                $fcheck='ok';
                $ccheck='nok';
                $ctext='Incorrect country';
                $acheck='nok';
                $suggestions=array($model->export($values));
            }

            if($fencerid<0) {
                $suggestions=array();
                $allbylastname = $model->allByLastName($lastname);
                $allbyfirstname = $model->allByFirstName($firstname);
                $allbycountry = $model->allByCountry($country);

                $ln=array();
                foreach($allbylastname as $f) {
                    $v = (array)$f;
                    $ln["f_".$v["fencer_id"]] = $v;
                }
                $fn=array();
                foreach($allbyfirstname as $f) {
                    $v = (array)$f;
                    $fn["f_".$v["fencer_id"]] = $v;
                }
                $cn=array();
                foreach($allbycountry as $f) {
                    $v = (array)$f;
                    $cn["f_".$v["fencer_id"]] = $v;
                }

                $m1 = array_intersect(array_keys($ln),array_keys($fn));
                $m2 = array_intersect(array_keys($ln),array_keys($cn));
                $m3 = array_intersect(array_keys($fn),array_keys($cn));
                $keys = array_unique(array_merge($m1,$m2,$m3));
                $values = array_merge($ln,$fn,$cn);
                foreach($keys as $k) {
                    if(isset($values[$k])) {
                        $vs = $model->export($values[$k]);
                        $suggestions[]=$vs;
                    }
                }

                if(sizeof($suggestions) == 1) {
                    $key = $keys[0];
                    $fencer = $values[$key];
                    $fencerid=$fencer["fencer_id"];
                    if(!isset($ln[$key])) {
                        $lcheck='nok';
                        $ltext='No match on last name';
                    }
                    if(!isset($fn[$key])) {
                        $fcheck='nok';
                        $ftext='No match on first name';
                    }
                    if(!isset($cn[$key])) {
                        $ccheck='nok';
                        $ctext='No match on country';
                    }
                    $acheck='nok';
                    $atext='at least one field did not match properly';
                }
                else {
                    $lcheck='nok';
                    $ltext=sizeof($suggestions)>0 ? 'Please pick a suggestion' : 'not found';
                    $fcheck='nok';
                    $ftext=sizeof($suggestions)>0 ? 'Please pick a suggestion' : 'not found';
                    $ccheck='nok';
                    $ctext=sizeof($suggestions)>0 ? 'Please pick a suggestion' : 'not found';
                    $acheck='nok';
                    $atext=sizeof($suggestions)>0 ? 'Please pick a suggestion' : 'not found';
                }

                if(sizeof($cn) == 0) {
                    $ccheck='nok';
                    $ctext='No such country';
                }
                if(sizeof($fn) == 0) {
                    $fcheck='nok';
                    $ftext='No such first name found';
                }
                if(sizeof($ln) == 0) {
                    $lcheck='nok';
                    $ltext='No such last name found';
                }
            }
            
            $values = array(
                "index" => $entry["index"],
                "fencer_id" => $fencerid,
                "suggestions" => $suggestions,
                "lastname_text" => $ltext,
                "firstname_text" => $ftext,
                "country_text" => $ctext,
                "lastname_check" => $lcheck,
                "firstname_check" => $fcheck,
                "country_check" => $ccheck,
                "all_text" => $atext,
                "all_check" => $acheck
            );
            $retval["ranking"][]=$values;
        }
        return $retval;
    }
 }
 