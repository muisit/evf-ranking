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

    private $fencer;
    private $country;
    private $event;
    private $sidesById;
    private $rolesById;
    private $roletypes;
    private $templates;

    // first argument is a fencer ID
    public function create() {
        $args= func_get_args();
        $fencer_id = sizeof($args) > 0 ? $args[0] : -1;
        $event_id = sizeof($args) > 1 ? $args[1] : -1;
        $this->queue->setData("fencer_id",$fencer_id);
        $this->queue->setData("event_id", $event_id);
        parent::create();
    }

    public function run() {
        parent::run();

        $this->event = new \EVFRanking\Models\Event($this->queue->getData("event_id"));
        $this->fencer = new \EVFRanking\Models\Fencer($this->queue->getData("fencer_id"));
        $this->accreditation = new \EVFRanking\Models\Accreditation();

        if($this->event->exists() && $this->fencer->exists()) {
            $this->country=new \EVFRanking\Models\Country($this->fencer->fencer_country);
            $this->country->load();


            $existing_accreditations = $this->accreditation->selectRecordsByFencer($this->fencer->getKey(), $this->event->getKey());

            $registration = new \EVFRanking\Models\Registration();
            $existing_registrations = $registration->selectAllOfFencer($this->event, $this->fencer);
            $new_accreditations = $this->createAccreditations($existing_registrations);
            $this->matchAccreditations($existing_accreditations, $new_accreditations);
        }
        else {
            // no such event, perhaps the accreditation is in error. We can safely delete all accreditations for 
            // this event and fencer
            $this->accreditation->cleanForFencer($this->fencer->getKey(),$this->event->getKey());
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
                if($a2->similar($a1["data"])) {
                    $found[]=$a2->getKey();
                    $foundThis=true;
                    break; // only match the first accreditation if we happen to have duplicates
                }
            }
            if(!$foundThis) {
                $addthese[]=$a1;
            }
        }

        // find out which accreditations we need to dump and which we can keep
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
        foreach($missing as $a) {
            $a->delete();
        }

        // the found accreditations need to update their dirty value
        foreach($actualfound as $a) {
            // check that the file actually exists and that we have an accreditation ID set
            $path=$a->getPath();
            if(!file_exists($path) || empty($a->fe_id)) {
                $job = new \EVFRanking\Jobs\AccreditationCreate();
                if(empty($a->fe_id)) {
                    $a->createID();
                    $a->save();
                }
                $job->create($a);
            }
            else {
                $a->unsetDirty();                
            }
        }

        // the addthese accreditations need to be (re)generated, so we create
        // new versions of them and queue them up
        foreach($addthese as $a) {
            $accr = new \EVFRanking\Models\Accreditation();
            $accr->import($a["data"]);
            $accr->fencer_id = $this->fencer->getKey();
            $accr->event_id = $this->event->getKey();
            $accr->template_id = $a["template"]->getKey();
            $accr->createID();
            $accr->save();

            $job = new \EVFRanking\Jobs\AccreditationCreate();
            $job->queue->queue = $this->queue->queue; // make sure we stay in the same queue
            $job->create($accr);
        }
    }

    private function createAccreditations($registrations) {
        $accreditations=array();
        $this->setupData();
        $dates = $this->checkRolesAndDates($registrations);

        // for each template, extract the dates and roles and create the template
        foreach($this->templates as $t) {
            $content = json_decode($t->content, true);
            $roleids = isset($content["roles"]) ? $content["roles"]: array();
            $assignedRoles = $this->findAssignedRoles($dates, $roleids);

            // if any of the roles for this template was assigned, create an accreditation
            if(sizeof($assignedRoles) > 0) {
                // see if any of these roles appears in the ALL list. In that case, we
                // assign all the roles managed by this accreditation for ALL dates
                $foundall=false;
                foreach($dates["all"]["roles"] as $role) {
                    if(in_array(strval($role->getKey()),$roleids)) {
                        if(sizeof($assignedRoles) > 0) {
                            $accreditations[]= array("template" => $t, "data" => $this->createTemplate($t, $assignedRoles, array("ALL")));
                            $foundall=true;
                            break;
                        }
                    }
                }

                if(!$foundall) {
                    // loop over all dates, find all assigned roles for each date
                    $founddates=array();
                    foreach($dates as $dt=>$spec) {
                        if($dt != "all") {
                            foreach($spec["roles"] as $rl) {
                                if(in_array(strval($rl->getKey()),$roleids)) {
                                    $founddates[]=$dt;
                                    break;
                                }
                            }
                        }
                    }

                    // if we find a role in each of the dates, assign roles for all dates
                    // anyway
                    // (compare with sizeof()-1 because the 'all' entry is included in $dates)
                    if(sizeof($founddates) >= (sizeof(array_keys($dates)) -1)) {
                        $accreditations[] = array("template"=>$t, "data"=>$this->createTemplate($t, $assignedRoles, array("ALL")));
                    }
                    else {
                        $accreditations[] = array("template" => $t, "data" => $this->createTemplate($t, $assignedRoles, $founddates));
                    }
                }
            }
        }
        return $accreditations;
    }

    private function findAssignedRoles($dates, $roleids) {
        $roles=array();
        $alreadyfound=array();
        foreach($dates as $dt=>$datespec) {
            foreach($datespec["roles"] as $role) {
                // add roles that are in the list of roles of this template
                // if role=0 is in the template, add all the roles (which are
                // individual competition events)
                if(   in_array(strval($role->getKey()), $roleids) 
                   && (!in_array($role->getKey(),$alreadyfound) || $role->getKey() == 0)) {
                    $roles[]=$role;
                    $alreadyfound[]=$role->getKey(); // make sure there are no duplicates
                }
            }
        }
        return $roles;
    }

    private function checkRolesAndDates($registrations) {
        // create a list of dates and the roles this fencer has on each date.
        // the dates are the days of each side event
        $dates = array("all" => array("sideevents" => array(), "roles" => array()));
        foreach ($this->sidesById as $k=>$s) {
            $date = strftime('%F', strtotime($s->starts));
            if (!isset($dates[$date])) {
                $dates[$date] = array("sideevents" => array(), "roles" => array());
            }
            $dates[$date]["sideevents"][] = $s;
        }

        // add roles to each sideevent
        foreach ($registrations as $r) {
            $sid = $r->registration_event;
            $rid = $r->registration_role;
            $sideevent = isset($this->sidesById["s" . $sid]) ? $this->sidesById["s" . $sid] : null;
            $role = isset($this->rolesById["r" . $rid]) ? $this->rolesById["r" . $rid] : null;

            if (!empty($sideevent)) {
                $date = strftime('%F', strtotime($s->starts));
                if (empty($role)) {
                    // just a mere participant
                    // if the side event has a competition, accredit the person. Do not
                    // accredit for other side events
                    if ($sideevent->competition->exists()) {
                        $role= new \EVFRanking\Models\Role();
                        $role->role_id=0;
                        $role->role_name = "Athlete " . $sideevent->competition->abbreviation();
                        $dates[$date]["roles"][]=$role;
                    }
                } else {
                    $dates[$date]["roles"][] = $role;
                }
            } else if (!empty($role)) {
                // event-wide role
                $dates["all"]["roles"][] = $role;
            }
        }

        // check to see if some roles are given for all dates anyway
        foreach ($this->rolesById as $k=>$r) {
            $foralldates = true;
            foreach ($dates as $k => $v) {
                if ($k != "all") {
                    $found = false;
                    foreach ($v["roles"] as $r2) {
                        if ($r2->getKey() == $r->getKey() && $r2->getKey()!=0) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $foralldates = false;
                        break;
                    }
                }
            }

            if ($foralldates) {
                $dates["all"]["roles"][] = $r;

                // filter out the role from the date fields
                foreach ($dates as $k => $v) {
                    if ($k != "all") {
                        $dates[$k]["roles"] = array_filter($dates[$k]["roles"], function ($item) use ($r) {
                            return $item->getKey() != $r->getKey();
                        });
                    }
                }
            }
        }
        return $dates;
    }

    private function setupData() {
        // sides returns objects
        $sides = $this->event->sides(null, true);
        $this->sidesById = array();
        foreach ($sides as $s) {
            $s->competition = new \EVFRanking\Models\Competition($s->competition_id, true);
            $this->sidesById["s" . $s->getKey()] = $s;
        }

        // role->selectAll returns db rows as object
        $rmodel = new \EVFRanking\Models\Role();
        $roles = $rmodel->selectAll(0, 10000, "", "");
        $this->rolesById = array();
        foreach ($roles as $r) {
            $r = new \EVFRanking\Models\Role($r);
            $r->type = new \EVFRanking\Models\RoleType($r->role_type, true);
            $this->rolesById["r" . $r->role_id] = $r;
        }

        $tmodel = new \EVFRanking\Models\AccreditationTemplate();
        $templates = $tmodel->selectAll(0, 10000, array("event" => $this->event->getKey()), "i");
        $this->templates = array();
        foreach ($templates as $t) {
            $this->templates[] = new \EVFRanking\Models\AccreditationTemplate($t, true);
        }

        $rtype = new \EVFRanking\Models\RoleType();
        $this->roletypes = array();
        $types=$rtype->selectAll(0,10000,null,"i");
        foreach($types as $rt) {
            $roletype=new \EVFRanking\Models\RoleType($rt);
            $this->roletypes["t".$roletype->getKey()]=$roletype;
        }
    }

    private function createTemplate($template, $assignedRoles, $dates)
    {
        $accr = array(
            "country" => $this->country->country_abbr,
            "country_flag" => $this->country->country_flag_path,
            "organisation" => "",
            "roles" => array(),
            "dates" => array(),
            "lastname" => strtoupper($this->fencer->fencer_surname),
            "firstname" => $this->fencer->fencer_firstname
        );

        // make sure we change the accreditation when the fencer photo ID changes
        $path = $this->fencer->getPath();
        if(file_exists($path)) {
            $accr["photo_hash"] = hash_file("sha256",$path);
        }
        else {
            // add a value to indicate the file is non-existant
            $accr["photo_hash"]="---";
        }

        // make sure we change the accreditation if the template configuration changes
        // we hash the template content JSON string, assuming if it changes, it's content
        // will have changed as well
        $accr["template_hash"] = hash('sha256',$template->content,false);

        // convert dates to a 'SAT 1' kind of display
        foreach($dates as $k) {
            if($k != "ALL") {
                $time = strtotime($k);
                $entry = str_replace('  ', ' ', strtoupper(strftime('%a %e', $time)));
                $accr["dates"][]=$entry;
            }
            else {
                $accr["dates"][]=$k;
            }
        }

        // convert all roles to their role name
        foreach($assignedRoles as $role) {
            $accr["roles"][]=$role->role_name;

            // depending on the role types, set the organisation
            $rtid=$role->role_type_id;
            if(isset($this->roletypes["t".$rtid])) {
                $orgdecl=$this->roletypes["t".$rtid]->org_declaration;

                if($orgdecl == "Country" && strlen($accr["organisation"])==0) {
                    $accr["organisation"]=$accr["country"];
                }
                else if($orgdecl=="Org" && $accr["organisation"] != "EVF") {
                    $accr["organisation"]="ORG";
                }
                else if($orgdecl == "EVF") {
                    $accr["organisation"]="EVF";
                }
            }
        }

        return $accr;
    }
}
