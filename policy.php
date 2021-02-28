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


 namespace EVFRanking;

 class Policy {
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

    public function check($model, $action) {
        $policies = array(       // List     View        Update/Create      Delete      Misc
            # Common tables
            "fencers" => array(     "any",   "any",      "reg",             "rank",     "rank"     ),
            "events" => array(      "any",   "any",      "reg",             "rank",     "rank"     ),

            # Results and Rankings
            "results" => array(     "any",   "any",      "rank",            "rank",     "rank"     ),
            "ranking"=>array(       "any",   "any",      "rank",            "rank",     "rank"     ),
            "competitions" => array("any",   "any",      "rank",            "rank",     "rank"     ),

            # Registration and Accreditation
            "sides" => array(       "any",   "any",      "reg",             "rank",     "rank"     ),

            # base tables
            "weapons"=>array(       "any",   "rank",     "rank",            "rank",     "rank"     ),
            "categories" => array(  "any",   "rank",     "rank",            "rank",     "rank"     ),
            "countries"=> array(    "any",   "rank",     "rank",            "rank",     "rank"     ),
            "types" => array(       "any",   "rank",     "rank",            "rank",     "rank"     ),
            "roles" => array(       "any",   "rank",     "rank",            "rank",     "rank"     ),
            "roletypes" => array(   "any",   "rank",     "rank",            "rank",     "rank"     ),

            # Purely sysadmin
            "migrations" => array(  "rank",  "rank",     "rank",            "rank",     "rank"     ),
        );

        $idx=4; // Misc action
        if($action === "list") $idx=0;
        if($action === "view") $idx=1;
        if($action === "save") $idx=2;
        if($action === "delete") $idx=3;

        $base="rank"; // most restrictive
        if(isset($policies[$model]) && isset($policies[$model][$idx])) {
            $base = $policies[$model][$idx];
        }
        if($base == "any") return true;

        $userdata=$this->findUser();
        if($base == "rank" && $userdata["rankings"]===true) return true;
        if($base == "reg" && ($userdata["rankings"]===true || $userdata["registration"]===true)) return true;
        return false;
    }
}

