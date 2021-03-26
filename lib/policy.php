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

    public function checkRegistration($model, $action, $data) {
        global $evflogger;
        $evflogger->log(json_encode($data));
        // if hodcountry === null, this is a general administrator. If it is -1, there is no record
        // this is a rather turn-around way of interpreting data...
        $hodcountry = $this->hodCountry();
        $evflogger->log("hodCountry is $hodcountry");

        $event=null;
        $sideevent=null;
        $registration=null;
        
        if($action == "save" || $action == "list") {
            // retrieve the event from the modeldata or filter
            $sid = isset($data["model"]["sideevent"]) ? $data["model"]["sideevent"] : null;
            if (empty($sid)) {
                $sid = isset($data["filter"]["sideevent"]) ? $data["filter"]["sideevent"] : null;
            }

            $eid = isset($data["model"]["event"]) ? $data["model"]["event"] : null;
            if(empty($eid)) {
                $eid = isset($data["filter"]["event"]) ? $data["filter"]["event"] : null;
            }
            if(empty($eid)) {
                $evflogger->log("empty eid (2)");
                return false;
            }

            $sideevent = new \EVFRanking\Models\SideEvent($sid);
            if(!$sideevent->exists()) {
                $sideevent=null;
            }

            $event = new \EVFRanking\Models\Event($eid);
            $event->load();

            // events don't match, bail
            if(!empty($sideevent) && $sideevent->event_id != $event->getKey()) {
                $evflogger->log("sideevent does not match event (4)");
                return false;
            }
        }
        else if($action == "delete") {
            $rid = isset($data["model"]["id"]) ? $data["model"]["id"] : null;
            if (empty($rid)) {
                $evflogger->log("empty rid (25)");
                return false;
            }
            $registration = new \EVFRanking\Models\Registration($rid);
            if(!$registration->exists()) {
                $evflogger->log("no such registration (17)");
                // it is allowed to delete a non-existing item
                return true;
            }

            $event = new \EVFRanking\Models\Event($registration->registration_mainevent);
            $event->load();
        }

        if (empty($event) || !$event->exists()) {
            $evflogger->log("empty event (3)");
            return false;
        }

        if(!in_array($action, array("list","save","delete"))) {
            $evflogger->log("invalid action (7)");
            return false;
        }

        // eventcaps will check for the state of the event (open, closed)
        $caps = $event->eventCaps();

        $isorganiser = in_array($caps, array("system","organiser", "accreditation", "cashier", "registrar"));
        $ishod = $caps == "hod" && $hodcountry != -1;

        // not privileged: no business here
        // this includes users not logged in yet. The login page should've been presented before the
        // application is opened
        if(!$isorganiser && !$ishod) {
            $evflogger->log("no organiser and no hod ($caps) (8)");
            return false;
        }        

        // if we are listing, check that the proper list filters are set
        if($action == "list") {
            // make sure event is set in the filter
            if (!isset($data["filter"]["event"])) {
                $evflogger->log("no event in filter (5)");
                return false; // invalid filter setting
            }
            if ($data["filter"]["event"] != $event->getKey()) {
                $evflogger->log("filter event does not match model event (6)");
                return false; // filter does not match
            }

            if($isorganiser) {
                $evflogger->log("is organiser, listing allowed (21)");
                return true;
            }
            // generic HoDs are always allowed to act on event registration while it is open
            if (empty($hodcountry)) {
                $evflogger->log("is hod for all countries (10)");
                return true;
            }
            if(!isset($data["filter"]["country"])) {
                $evflogger->log("no country filter set (11)");
                return false; // invalid filter setting
            }
            // check on correct filter for this HoD
            if(intval($data["filter"]["country"]) === intval($hodcountry)) {
                $evflogger->log("hod country is set in filter, allow listing registrations (12)");
                return true;
            }
            $evflogger->log("invalid settings for listing");
        }
        else if($action == "save") {
            // save is only allowed for HoD, organiser, registrar and cashier
            if (!in_array($caps, array("system","organiser", "registrar", "hod","cashier"))) {
                $evflogger->log("caps $caps are incorrect (13)");
                return false;
            }

            if(!isset($data["model"]["fencer"])) {
                $evflogger->log("no fencer data set (22)");
                return false;
            }
            // for save actions, make sure the country_id setting is correct
            // we need to check this on the fencer that is going to be saved
            $fencer = new \EVFRanking\Models\Fencer($data["model"]["fencer"]);
            if(!$fencer->exists()) {
                $evflogger->log("invalid fencer for save (14)");
                return false; // no such fencer
            }

            if ($isorganiser) {
                $evflogger->log("is organiser ($caps), save allowed (23)");
                return true;
            }
            // generic HoDs are always allowed to act on event registration while it is open
            if (empty($hodcountry)) {
                $evflogger->log("is hod for all countries (24)");
                return true;
            }

            // check on correct country for this HoD
            if (intval($fencer->fencer_country) === intval($hodcountry)) {
                $evflogger->log("fencer country matches hod country (15)");
                return true;
            }
            $evflogger->log("HoD country invalid $hodcountry vs ".$fencer->country_id);
        }
        else if($action == "delete") {
            // delete is only allowed for HoD, organiser and registrar
            if(!in_array($caps, array("system","organiser","registrar","hod"))) {
                $evflogger->log("invalid caps $caps for delete (16)");
                return false;
            }

            $fencer = new \EVFRanking\Models\Fencer($data["model"]["fencer"]);
            if(!$fencer->exists()) {
                $evflogger->log("fencer does not exist (18)");
                return false;
            }

            if ($isorganiser) {
                $evflogger->log("is organiser, delete allowed (27)");
                return true;
            }
            // generic HoDs are always allowed to act on event registration while it is open
            if (empty($hodcountry)) {
                $evflogger->log("is hod for all countries (28)");
                return true;
            }

            // check on correct country for this HoD
            if (intval($fencer->fencer_country) === intval($hodcountry)) {
                $evflogger->log("country matches hodcountry (19)");
                return true;
            }
        }

        // wrong action, closed registration, no capabilities, id's don't match
        $evflogger->log("invalid action, caps ($caps), model or id (20)");
        return false;
    }

    public function check($model, $action, $data) {
        global $evflogger;
        // complicated policy check for the registration list/save/delete
        if($model == "registration") return $this->checkRegistration($model, $action,$data);

        $policies = array(       // List     View        Update/Create      Delete      Misc
            # Common tables
            "fencers" => array(     "any",   "any",      "reg",             "rank",     "noone"    ),
            "events" => array(      "any",   "any",      "reg",             "rank",     "noone"    ),

            # Results and Rankings
            "results" => array(     "any",   "any",      "rank",            "rank",     "rank"     ),
            "ranking"=>array(       "any",   "any",      "rank",            "rank",     "noone"    ),
            "competitions" => array("any",   "any",      "rank",            "rank",     "noone"    ),

            # Registration and Accreditation
            "sides" => array(       "any",   "any",      "reg",             "reg",      "noone"    ),
            "eventroles" => array(  "reg",   "reg",      "reg",             "reg",      "noone"    ),
            "registrars" => array(  "reg",   "reg",      "reg",             "reg",      "noone"    ),

            # base tables
            "weapons"=>array(       "any",   "rank",     "rank",            "rank",     "noone"    ),
            "categories" => array(  "any",   "rank",     "rank",            "rank",     "noone"    ),
            "countries"=> array(    "any",   "rank",     "rank",            "rank",     "noone"    ),
            "types" => array(       "any",   "rank",     "rank",            "rank",     "noone"    ),
            "roles" => array(       "any",   "rank",     "rank",            "rank",     "noone"    ),
            "roletypes" => array(   "any",   "rank",     "rank",            "rank",     "noone"    ),

            # Purely sysadmin-type
            # registration can list them, rank can see them. Update, delete and misc are all forbidden
            "users" => array(       "reg",   "rank",     "noone",           "noone",    "noone"    ),
            "posts" => array(       "reg",   "rank",     "noone",           "noone",    "noone"    ),
            "migrations" => array(  "rank",  "rank",     "rank",            "noone",    "noone"    ),
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
        if($base == "any") return true;

        $userdata=$this->findUser();
        if($base == "rank" && $userdata["rankings"]===true) return true;
        if($base == "reg" && ($userdata["rankings"]===true || $userdata["registration"]===true)) return true;

        return false;
    }
}

