<?php

/**
 * EVF-Ranking Policy Settings
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


namespace EVFRanking\Lib;

class Policy extends BaseLib {
    public function feEventToBeEvent($id) {
        $model=new \EVFRanking\Models\Event();
        return $model->findByFeId($id);
    }

    public function findEvent($id) {
        $model=new \EVFRanking\Models\Event();
        return $model->get($id);
    }

    public function eventCaps($event) {
        if(!empty($event)) {
            return $event->eventCaps();
        }
        return "";
    }

    public function hodCountry() {
        $user = wp_get_current_user();
        if (!empty($user)) {
            $model=new \EVFRanking\Models\Registrar();
            $registrar = $model->findByUser($user->ID);
            if($registrar != null) {
                return $registrar->country_id;
            }
        }
        return -1;
    }

    public function findUser() {
        $userdata=array("id"=>null,"rankings"=>false,"registration"=>false);
        $user = wp_get_current_user();        
        if(!empty($user)) {
            $userdata["id"]=$user->ID;

            if(current_user_can( 'manage_ranking' )) {
                $userdata["rankings"]=true;
            }
            if(current_user_can( 'manage_registration' )) {
                $userdata["registration"]=true;
            }
        }
        return $userdata;
    }

    private function loadModelsForPolicy($data,$modelname) {
        $event = null;
        $sideevent = null;
        $basemodel = null;
        $fencer=null;
        global $evflogger;

        if(isset($data["model"])) {
            $sid = isset($data["model"]["sideevent"]) ? $data["model"]["sideevent"] : null;
            if (empty($sid) && isset($data["filter"])) {
                $sid = isset($data["filter"]["sideevent"]) ? $data["filter"]["sideevent"] : null;
            }
            $evflogger->log("sideevent is $sid");

            $eid = isset($data["model"]["event"]) ? $data["model"]["event"] : null;
            if (empty($eid) && isset($data["filter"])) {
                $eid = isset($data["filter"]["event"]) ? $data["filter"]["event"] : null;
            }
            $evflogger->log("event is $eid");

            $sideevent = new \EVFRanking\Models\SideEvent($sid, true);
            if (!$sideevent->exists()) {
                $evflogger->log("no such sideevent");
                $sideevent = null;
            }

            $event = new \EVFRanking\Models\Event($eid, true);
            if(!$event->exists()) {
                $event = null;
                $evflogger->log("no such event");
            }

            $rid = isset($data["model"]["id"]) ? $data["model"]["id"] : null;
            $modelname="\\EVFRanking\\Models\\$modelname";
            $evflogger->log("loading model $modelname($rid)");
            $basemodel = new $modelname($rid, true);
            if(!$basemodel->exists()) {
                $evflogger->log("model not specified in modeldata, or -$rid- does not exist");
                $basemodel = null;
            }
            
            $fid = isset($data["model"]["fencer"]) ? $data["model"]["fencer"] : null;
            $evflogger->log("fencer is ".json_encode($fid));
            $fencer = new \EVFRanking\Models\Fencer($fid);
            if (!$fencer->exists()) {
                $fencer = null;
                $evflogger->log("no such fencer");
            }            
        }
        return array($sideevent,$event, $basemodel,$fencer);
    }


    public function check($model, $action, $data) {
        global $evflogger;

        $policies = array(        // List     View        Update/Create      Delete      Misc
            # Common tables
            "fencers" => array(      "any",   "any",      "fsave",           "rank",     "noone"    ),
            "events" => array(       "any",   "any",      "reg",             "rank",     "noone"    ),
            "audit" => array(        "eaccr", "eaccr",    "noone",           "noone",    "noone"    ),

            # Results and Rankings
            "results" => array(      "any",   "any",      "rank",            "rank",     "rank"     ),
            "ranking"=>array(        "any",   "any",      "rank",            "rank",     "rank"    ),
            "competitions" => array( "any",   "any",      "rank",            "rank",     "noone"    ),

            # Registration and Accreditation
            "sides" => array(        "any",   "any",      "reg",             "reg",      "noone"    ),
            "eventroles" => array(   "reg",   "reg",      "reg",             "reg",      "noone"    ),
            "registrars" => array(   "reg",   "reg",      "reg",             "reg",      "noone"    ),
            "templates" => array(    "eaccr", "eaccr",    "eaccr",           "eaccr",    "reg"      ),
            "registration" => array( "rlist", "rlist",    "rsave",           "rdel",     "noone"    ),
            "accreditation" => array("vaccr", "vaccr",    "noone",           "noone",    "noone"    ),

            # base tables
            "weapons"=>array(        "any",   "rank",     "rank",            "rank",     "noone"    ),
            "categories" => array(   "any",   "rank",     "rank",            "rank",     "noone"    ),
            "countries"=> array(     "any",   "rank",     "rank",            "rank",     "noone"    ),
            "types" => array(        "any",   "rank",     "rank",            "rank",     "noone"    ),
            "roles" => array(        "any",   "rank",     "rank",            "rank",     "noone"    ),
            "roletypes" => array(    "any",   "rank",     "rank",            "rank",     "noone"    ),

            # Purely sysadmin-type
            # registration can list them, rank can see them. Update, delete and misc are all forbidden
            "users" => array(        "reg",   "rank",     "noone",           "noone",    "noone"    ),
            "posts" => array(        "reg",   "rank",     "noone",           "noone",    "noone"    ),
            "migrations" => array(   "rank",  "rank",     "rank",            "noone",    "noone"    ),
        );

        $idx=-1;
        if($action === "list") $idx=0;
        if($action === "view") $idx=1;
        if($action === "save") $idx=2;
        if($action === "delete") $idx=3;
        if($action === "misc") $idx=4;
        if($idx==-1) {
            $evflogger->log("invalid action $action");
            return false;
        }

        $base="noone"; // most restrictive
        if(isset($policies[$model]) && isset($policies[$model][$idx])) {
            $base = $policies[$model][$idx];
        }

        $evflogger->log("base capa to test is $base");
        if ($base == "any") return true;
        if ($base == "noone") return false;

        return $this->hasCapa($base,$data);
    }

    private function hasCapa($capa,$data) {        
        $userdata=$this->findUser();
        switch($capa) {
        // has manage_rankings capability, a super-user power
        case "rank": return $userdata["rankings"] === true;
        // has manage_registration capability, a super-user power
        case "reg": return ($userdata["rankings"] === true || $userdata["registration"] === true);
        // is allowed to edit accreditation templates
        case "eaccr": return $this->hasCapaEaccr($data); 
        // is allowed to view and generate accreditations
        case 'vaccr': return $this->hasCapaVaccr($data);
        //
        // the registration capa's also check on additional supplied data
        // and restrict the fields that can be changed/deleted/updated
        //
        // can see a listing of registrations (HoD, registrar)
        case 'rlist': return $this->hasCapaListRegs($data);
        // can view an individual registration (HoD, registrar)
        case 'rview': return $this->hasCapaViewRegs($data);
        // can update registrations (HoD, registrar)
        case 'rsave': return $this->hasCapaSaveRegs($data);
        // can remove registrations (HoD, registrar)
        case 'rdel': return $this->hasCapaDelRegs($data);
        // special capa for saving fencers, which is restricted to registrars 
        case 'fsave': return $this->hasCapaSaveFencer($userdata, $data);
        default: break;
        }

        return false;
    }

    private function isValidHod($cid) {
        global $evflogger;
        $hodcountry = $this->hodCountry();

        // generic HoDs are always allowed to act on event registration while it is open
        if (empty($hodcountry)) {
            return true;
        }
        // check on correct filter for this HoD
        $evflogger->log("testing $cid vs $hodcountry");
        if (intval($cid) === intval($hodcountry)) {
            return true;
        }
        $evflogger->log("invalid HoD");
        return false;

    }

    private function hasCapaSaveFencer($userdata, $data) {
        global $evflogger;
        // if the user has registration capabilities, always allow
        if ($userdata["rankings"] === true || $userdata["registration"] === true) {
            $evflogger->log("user has rankings or registration rights, fsave allowed");
            return true;
        }

        // else we allow saving new fencers for registrars
        // and update the accreditation photo for accreditors
        list($sideevent, $event, $fencer, $fencer2) = $this->loadModelsForPolicy($data, "Fencer");

        // the sideevent and fencer2 settings are bogus, event should be valid
        if(empty($event) || !$event->exists())  {
            $evflogger->log("invalid event, but event was expected");
            return false;
        }

        // check on country only if we have additional data as well. If we are only saving 
        // the accreditation photo id, country and name do no exist
        if (   isset($data["model"]["name"])
            || isset($data["model"]["firstname"])
            || isset($data["model"]["gender"])
            || isset($data["model"]["birthday"])
        ) {
            $cid=isset($data["model"]["country"]) ? $data["model"]["country"] : -1;
            $country=new \EVFRanking\Models\Country($cid,true);
            if(!$country->exists()) {
                $evflogger->log("invalid country, but fencer should be linked to a valid country");
                return false;
            }
        }

        $caps = $event->eventCaps();
        $isorganiser = in_array($caps, array("system", "organiser", "registrar", "accreditation"));

        if ($isorganiser) {
            $evflogger->log("organiser/registrar/accreditor is allowed");
            return true;
        }

        if($this->isValidHod($country->getKey())) {
            $evflogger->log("HoD for this country is allowed");
            return true;
        }
        $evflogger->log("not a HoD, not an organiser, no registration-capa: not allowed to save fencer");
        return false;
    }

    private function hasCapaEaccr($data)
    {
        global $evflogger;
        list($sideevent, $event, $template, $fencer) = $this->loadModelsForPolicy($data,"AccreditationTemplate");

        if(!empty($template) && $template->exists()) {
            if(!empty($event) && $event->getKey() != $template->event_id) {
                $evflogger->log("invalid event specified (1)");
                return false;
            }
            else {
                $event = new \EVFRanking\Models\Event($template->event_id,true);
                if(!$event->exists()) {
                    $evflogger->log("invalid event for template (2)");
                    return false;
                }
            }
        }

        // if we have no template, we need an event
        if(empty($template) || !$template->exists()) {
            if (empty($event) || !$event->exists()) {
                $evflogger->log("no event specified (3)");
                return false;
            }
        }

        $caps = $event->eventCaps();
        $isorganiser = in_array($caps, array("system", "organiser", "accreditation"));

        if (!$isorganiser) {
            $evflogger->log("no organiser ($caps) (4)");
            return false;
        }
        return true;
    }

    private function hasCapaVaccr($data) {
        global $evflogger;
        list($sideevent, $event, $accreditation, $fencer) = $this->loadModelsForPolicy($data, "Accreditation");

        if (!empty($accreditation) && $accreditation->exists()) {
            if (!empty($event) && $event->getKey() != $accreditation->event_id) {
                $evflogger->log("invalid event specified (1)");
                return false;
            } else {
                $event = new \EVFRanking\Models\Event($accreditation->event_id, true);
                if (!$event->exists()) {
                    $evflogger->log("invalid event for accreditation (2)");
                    return false;
                }
            }
        }

        // if we have no template, we need an event
        if (empty($accreditation) || !$accreditation->exists()) {
            if (empty($event) || !$event->exists()) {
                $evflogger->log("no event specified (3)");
                return false;
            }
        }

        $caps = $event->eventCaps();
        $isorganiser = in_array($caps, array("system", "organiser", "accreditation"));

        if (!$isorganiser) {
            $evflogger->log("no organiser ($caps) (4)");
            return false;
        }
        $evflogger->log("has capa vaccr");
        return true;
    }

    private function hasCapaListRegs($data) {
        global $evflogger;
        list($sideevent, $event, $registration, $fencer) = $this->loadModelsForPolicy($data,"Registration");

        if(empty($event) || !$event->exists()) {
            $evflogger->log("no event specified (1)");
            return false;
        }
        if(!empty($sideevent)) $evflogger->log("testing ".$sideevent->event_id." vs ".$event->getKey());
        if (!empty($sideevent) && $sideevent->event_id != $event->getKey()) {
            $evflogger->log("sideevent does not match event (2)");
            return false;
        }

        $caps = $event->eventCaps();
        $isorganiser = in_array($caps, array("system", "organiser", "accreditation", "cashier", "registrar"));
        $ishod = $caps == "hod";

        if (!$isorganiser && !$ishod) {
            $evflogger->log("no organiser and no hod ($caps) (3)");
            return false;
        }

        if (!isset($data["filter"]["event"])) {
            $evflogger->log("no event in filter (4)");
            return false; // invalid filter setting
        }
        if ($data["filter"]["event"] != $event->getKey()) {
            $evflogger->log("filter event does not match model event (5)");
            return false; // filter does not match
        }

        if ($isorganiser) {
            $evflogger->log("is organiser, listing allowed (6)");
            return true;
        }
        $cid = isset($data["filter"]) && isset($data["filter"]["country"]) ? $data["filter"]["country"] : -1;
        return $this->isValidHod($cid);
    }

    private function hasCapaViewRegs($data)
    {
        // same as ListRegs, except no requirement on filter
        global $evflogger;
        list($sideevent, $event, $registration, $fencer) = $this->loadModelsForPolicy($data, "Registration");

        if (empty($event) || !$event->exists()) {
            $evflogger->log("no event specified (1)");
            return false;
        }
        if (!empty($sideevent) && $sideevent->event_id != $event->getKey()) {
            $evflogger->log("sideevent does not match event (2)");
            return false;
        }

        $caps = $event->eventCaps();
        $isorganiser = in_array($caps, array("system", "organiser", "accreditation", "cashier", "registrar"));
        $ishod = $caps == "hod";

        if (!$isorganiser && !$ishod) {
            $evflogger->log("no organiser and no hod ($caps) (3)");
            return false;
        }

        if ($isorganiser) {
            $evflogger->log("is organiser, listing allowed (6)");
            return true;
        }
        $cid = isset($data["filter"]) && isset($data["filter"]["country"]) ? $data["filter"]["country"] : -1;
        return $this->isValidHod($cid);
    }

    private function hasCapaSaveRegs($data)
    {
        global $evflogger;
        list($sideevent, $event, $registration, $fencer) = $this->loadModelsForPolicy($data, "Registration");

        if (empty($event) || !$event->exists()) {
            $evflogger->log("no event specified (1)");
            return false;
        }
        if (empty($sideevent) || $sideevent->event_id != $event->getKey()) {
            $sideevent = null;
        }

        $caps = $event->eventCaps();
        $isorganiser = in_array($caps, array("system", "organiser", "cashier", "registrar","accreditation"));
        $ishod = $caps == "hod";

        if (!$isorganiser && !$ishod) {
            $evflogger->log("no organiser and no hod ($caps) (5)");
            return false;
        }

        // for save actions, make sure the country_id setting is correct
        // we need to check this on the fencer that is going to be saved
        if (empty($fencer)) {
            $evflogger->log("invalid fencer for save (6)");
            return false; // no such fencer
        }

        if ($isorganiser) {
            $evflogger->log("is organiser ($caps), save allowed (7)");
            return true;
        }

        // check on correct filter for this HoD
        if (  isset($data['filter']) 
           && isset($data['filter']['country']) 
           && intval($data["filter"]["country"]) === intval($fencer->country)) {
            $evflogger->log("invalid country for fencer (8)");
            return false;
        }
        return $this->isValidHod($fencer->fencer_country);
    }

    private function hasCapaDelRegs($data) {
        global $evflogger;
        list($sideevent, $event, $registration, $fencer) = $this->loadModelsForPolicy($data, "Registration");

        if (empty($registration)) {
            $evflogger->log("no such registration (1)");
            // it is allowed to delete a non-existing item
            return true;
        }
        if (empty($fencer)) {
            $evflogger->log("fencer does not exist (2)");
            return false;
        }

        if($registration->registration_fencer != $fencer->getKey()) {
            $evflogger->log("fencer does not match registration");
            return false;
        }

        $event = new \EVFRanking\Models\Event($registration->registration_mainevent, true);
        if (!$event->exists()) {
            $evflogger->log("no event specified (3)");
            return false;
        }

        $caps = $event->eventCaps();
        $isorganiser = in_array($caps, array("system", "organiser", "registrar"));
        $ishod = $caps == "hod";

        if (!$isorganiser && !$ishod) {
            $evflogger->log("no organiser and no hod ($caps) (4)");
            return false;
        }

        if ($isorganiser) {
            $evflogger->log("is organiser, delete allowed (5)");
            return true;
        }

        // generic HoDs are always allowed to act on event registration while it is open
        if (empty($hodcountry)) {
            $evflogger->log("is hod for all countries (28)");
            return true;
        }
        return $this->isValidHod($fencer->fencer_country);
    }
}

