<?php

/**
 * EVF-Ranking AccreditationDirty job clas
 *
 * @package             evf-ranking
 * @author              Michiel Uitdehaag
 * @copyright           2020-2021 Michiel Uitdehaag for muis IT
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

namespace EVFRanking\Jobs;

class AccreditationDirty extends BaseJob {

    private $accreditation;
    private $fencer;
    private $country;
    private $event;
    private $sidesById;
    private $rolesById;

    // first argument is a fencer ID
    public function create() {
        $args= func_get_args();
        $fencer_id = sizeof($args) > 0 ? $args[0] : -1;
        $event_id = sizeof($args) > 1 ? $args[1] : -1;
        $accr_id = sizeof($args) > 2 ? $args[2] : -1;
        $this->queue->setData("fencer_id",$fencer_id);
        $this->queue->setData("event_id", $event_id);
        $this->queue->setData("accreditation_id", $accr_id);
        parent::create();
    }

    public function run() {
        parent::run();

        $this->accreditation = new \EVFRanking\Models\Accreditation($this->queue->getData("accreditation_id"));
        $this->event = new \EVFRanking\Models\Event($this->queue->getData("event_id"));
        $this->fencer = new \EVFRanking\Models\Fencer($this->queue->getData("fencer_id"));

        if($this->event->exists() && $this->fencer->exists()) {
            $this->country=new \EVFRanking\Models\Country($this->fencer->fencer_country);
            $this->country->load();

            $this->accreditation->unsetDirty($this->fencer->getKey(),$this->event->getKey());

            $existing_accreditations = $accred->selectRecordsByFencer($this->fencer->getKey(), $this->event->getKey());

            $registration=  new \EVFRanking\Models\Registration();
            $existing_registrations = $registration->selectAllOfFencer($this->event, $this->fencer);
            $new_accreditations = $this->createAccreditations($existing_registrations);
            $this->matchAccreditations($existing_accreditations, $new_accreditations);
        }
        else {
            // no such event, perhaps the accreditation is in error. We can safely delete it
            $this->accreditation->delete();
        }
    }

    private function matchAccreditations($existing, $newones) {
        $lst=array();
        foreach($existing as $a) $lst[] = new \EVFRanking\Models\Accreditation($a);
        $existing=$lst;

        $found=array();
        $addthese=array();
        $missing=array();
        foreach($newones as $a1) {
            $foundThis=false;
            foreach($existing as $a2) {
                if($a2->similar($a1)) {
                    $found[]=$a2->getKey();
                    $foundThis=true;
                }
            }
            if(!$foundThis) {
                $addthese[]=$a1;
            }
        }
        $actualfound=array();
        foreach($existing as $a1) {
            if(!in_array($a1->getKey(),$found)) {
                $missing[]=$a1;
            }
            else {
                $actualfound[]=$a1;
            }
        }

        // the missing accreditations can be removed.
        // This will remove the file as well
        foreach($missing as $a) $a->delete();

        // the found accreditations need to update their dirty value
        foreach($actualfound as $a) {
            $a->unsetDirty();
        }

        // the addthese accreditations need to be (re)generated, so we create
        // new versions of them and queue them up
        foreach($addthese as $a) {
            $accr = new \EVFRanking\Models\Accreditation();
            $accr->import($a);
            $accr->save();

            $job = new \EVFRanking\Jobs\AccreditationCreate();
            error_log("creating queue job");
            $job->create($accr);
        }
    }

    private function createAccreditations($registrations) {
        // sides returns objects
        $sides = $this->event->sides(null,true);
        $this->sidesById=array();
        foreach($sides as $s) {
            $s->competition = new \EVFRanking\Models\Competition($s->competition_id,true);
            $this->sidesById["s".$s->getKey()] = $s;
        }

        // role->selectAll returns db rows as object
        $rmodel = new \EVFRanking\Models\Role();
        $roles = $rmodel->selectAll(0,10000,"","");
        $this->rolesById=array();
        foreach($roles as $r) {
            $r=new \EVFRanking\Models\Role($r);
            $r->type=new \EVFRanking\Models\RoleType($r->role_type,true);
            $this->rolesById["r".$r->role_id]=$r;
        }

        // create a list of dates and the roles this fencer has on each date.
        // the dates are the days of each side event
        $dates=array("all"=>array("sideevents"=>array(),"roles"=>array()));
        foreach($sides as $s) {
            $date = strftime('%F',strtotime($s->starts));
            if(!isset($dates[$date])) {
                $dates[$date]=array("sideevents"=>array(), "roles"=>array());
            }
            $dates[$date]["sideevents"][]=$s;
        }

        // add roles to each sideevent
        $count=0;
        $acount=0;
        foreach($registrations as $r) {
            $sid = $r->registration_event;
            $rid = $r->registration_role;
            $sideevent = isset($sidesById["s".$sid]) ? $sidesById["s".$sid] : null;
            $role = isset($rolesById["r".$rid]) ? $rolesById["r".$rid] : null;

            if(!empty($sideevent)) {
                $date = strftime('%F',strtotime($s["starts"]));
                if(empty($role)) {
                    // just a mere participant
                    // if the side event has a competition, accredit the person. Do not
                    // accredit for other side events
                    if($s->competition->exists()) {
                        $dates[$date]["roles"][]="Athlete ".$sideevent->competition->abbreviation();
                        $count++;
                        $acount++;
                    }
                }
                else {
                    $dates[$date]["roles"][]=$role;
                    $count++;
                }
            }
            else if(!empty($role)) {
                // event-wide role
                $dates["all"]["roles"][]=$role;
                $count++;
            }
        }

        // check to see if some roles are given for all dates anyway
        foreach($roles as $r) {
            $foralldates=true;
            foreach($dates as $k=>$v) {
                if($k != "all") {
                    $found=false;
                    foreach($v["roles"] as $r2) {
                        if(is_object($r2) && $r2->getKey() == $r->getKey()) {
                            $found=true;
                            break;
                        }
                    }
                    if(!$found) {
                        $foralldates=false;
                        break;
                    }
                }
            }

            if($foralldates) {
                $dates["all"]["roles"][]=$r;

                // filter out the role from the date fields
                foreach ($dates as $k => $v) {
                    if ($k != "all") {
                        $count--;
                        $dates[$k]["roles"]=array_filter($dates[$k]["roles"],function($item) use($r) {
                            return $item->getKey() != $r->getKey();
                        });
                    }
                }
                $count++;
            }
        }

        // no roles, no accreditations
        if($count==0) return array();

        if($count == $acount) {
            // only competition related roles. Give a single accreditation with the specific dates
            return $this->athleteAccreditation($dates);
        }

        // else only event-wide roles, or event-wide roles combined with athlete roles
        // In either case, we give a single accreditation showing the various roles, for all dates
        return $this->supportAccreditation($dates); 
    }

    private function athleteAccreditation($dates) {
        $accr = array(
            "surname" => $this->fencer->fencer_surname,
            "firstname" => $this->fencer->fencer_firstname,
            "country" => $this->country->country_name,
            "country_abbr" => $this->country->country_abbr,
            "organisation" => $this->country->country_name,
            "organisation_abbr" => $this->country->country_abbr,
            "roles" => array(),
            "dates" => array()
        );

        foreach($dates as $k=>$v) {
            $time = strtotime($k);
            $entry = str_replace('  ', ' ', strtoupper(strftime('%a %e', $time)));
            $accr["dates"][]=$entry;

            foreach($v["roles"] as $r) {
                if(is_string($r)) {
                    $accr["roles"][]=$r;
                }
                else {
                    $accr["roles"][]=$r->role_name;
                }
            }
        }
        return array($accr);
    }

    private function supportAccreditation($dates) {
        $accr_fed = array(
            "surname" => $this->fencer->fencer_surname,
            "firstname" => $this->fencer->fencer_firstname,
            "country" => $this->country->country_name,
            "country_abbr" => $this->country->country_abbr,
            "organisation" => $this->country->country_name,
            "organisation_abbr" => $this->country->country_abbr,
            "roles" => array(),
            "dates" => "ALL"
        );

        $accr_org = array(
            "surname" => $this->fencer->fencer_surname,
            "firstname" => $this->fencer->fencer_firstname,
            "country" => $this->country->country_name,
            "country_abbr" => $this->country->country_abbr,
            "organisation" => "Organisation ".$this->event->event_title,
            "organisation_abbr" => "ORG",
            "roles" => array(),
            "dates" => "ALL"
        );

        $accr_evf = array(
            "surname" => $this->fencer->fencer_surname,
            "firstname" => $this->fencer->fencer_firstname,
            "country" => $this->country->country_name,
            "country_abbr" => $this->country->country_abbr,
            "organisation" => "European Veterans Fencing",
            "organisation_abbr" => "EVF",
            "roles" => array(),
            "dates" => "ALL"
        );

        foreach ($dates as $k => $v) {
            foreach ($v["roles"] as $r) {                
                if (is_string($r)) {
                    // a string text would be an athlete role, which is a federative accreditation
                    $accr_fed["roles"][]=$r;
                } else {
                    switch($r->type->org_declaration) {
                    default:
                    case "Country": 
                        $accr_fed["roles"][] = $r->role_name;
                        $accr_org["roles"][] = $r->role_name." (".$this->country->country_abbr.")";
                        $accr_evf["roles"][] = $r->role_name . " (" . $this->country->country_abbr . ")";
                        break;
                    case "Org": 
                        $accr_org["roles"][] = $r->role_name; 
                        $accr_evf["roles"][] = $r->role_name; 
                        break;
                    case "EVF": 
                        $accr_evf["roles"][] = $r->role_name; 
                        break;
                    }
                }
            }
        }

        // now see which accreditation has the most roles, but one more
        // than the next lower level (ie: it contains at least an additional
        // role of the higher level)
        $retval = array();
        if (sizeof($accr_evf["roles"]) > sizeof($accr_org["roles"])) {
            // EVF assigned role, combined with personal roles
            $retval[]=$accr_evf;
        }
        else if (sizeof($accr_org["roles"]) > sizeof($accr_fed["roles"])) {
            // someone of the organisation, who also participates
            $retval[]=$accr_org;
        }
        else {
            // athlete+coach, coach+team armourer, HoD+athlete
            $retval[]=$accr_fed;
        }
        return $retval;
    }

}
