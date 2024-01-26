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

class Policy extends BaseLib
{
    public function feEventToBeEvent($id)
    {
        $model = new \EVFRanking\Models\Event();
        return $model->findByFeId($id);
    }

    public function findEvent($id)
    {
        $model = new \EVFRanking\Models\Event();
        return $model->get($id);
    }

    public function eventCaps($event)
    {
        if (!empty($event)) {
            return $event->eventCaps();
        }
        return "";
    }

    public function findUser()
    {
        $userdata = array("id" => null, "rankings" => false, 'downloadRankings' => false);
        $user = wp_get_current_user();
        if (!empty($user)) {
            $userdata["id"] = $user->ID;

            if (current_user_can('manage_ranking')) {
                $userdata["rankings"] = true;
            }
            if (current_user_can('download_ranking')) {
                $userdata["downloadRankings"] = true;
            }
        }
        return $userdata;
    }

    public function check($model, $action, $data)
    {
        global $evflogger;

        $policies = array(        // List     View        Update/Create      Delete      Misc
            # Common tables
            "fencers" => array(      "auth",  "auth",     "rank",            "rank",     "noone"    ),
            "events" => array(       "auth",  "auth",     "rank",            "rank",     "noone"    ),

            # Results and Rankings
            "results" => array(      "any",   "any",      "rank",            "rank",     "rank"     ),
            "ranking" => array(      "any",   "any",      "rank",            "rank",     "rank"    ),
            "competitions" => array( "any",   "any",      "rank",            "rank",     "noone"    ),

            # Registration and Accreditation
            "eventroles" => array(   "rank",  "rank",     "rank",           "rank",     "noone"    ),
            "registrars" => array(   "rank",  "rank",     "rank",           "rank",     "noone"    ),
            "picture" => array(      "rank",  "rank",     "rank",           "noone",    "noone"    ),

            # base tables
            "weapons" => array(      "any",   "rank",     "rank",            "rank",     "noone"    ),
            "categories" => array(   "any",   "rank",     "rank",            "rank",     "noone"    ),
            "countries" => array(    "any",   "rank",     "rank",            "rank",     "noone"    ),
            "types" => array(        "any",   "rank",     "rank",            "rank",     "noone"    ),
            "roles" => array(        "any",   "rank",     "rank",            "rank",     "noone"    ),
            "roletypes" => array(    "any",   "rank",     "rank",            "rank",     "noone"    ),

            # Purely sysadmin-type
            # Update, delete and misc are all forbidden
            "users" => array(        "rank",   "rank",     "noone",           "noone",    "noone"    ),
            "posts" => array(        "rank",   "rank",     "noone",           "noone",    "noone"    ),
        );

        $idx = -1;
        if ($action === "list") $idx = 0;
        if ($action === "view") $idx = 1;
        if ($action === "save") $idx = 2;
        if ($action === "delete") $idx = 3;
        if ($action === "misc") $idx = 4;
        if ($idx == -1) {
            $evflogger->log("Policy: invalid action $action");
            return false;
        }

        $base = "noone"; // most restrictive
        if (isset($policies[$model]) && isset($policies[$model][$idx])) {
            $base = $policies[$model][$idx];
        }

        if ($base == "any") return true;
        if ($base == "auth") return !empty(wp_get_current_user());
        if ($base == "noone") return false;
        return $this->hasCapa($base);
    }

    protected function hasCapa($base)
    {
        $userdata = $this->findUser();
        switch ($base) {
            // has manage_rankings capability, a super-user power
            case "rank":
                return $userdata["rankings"] === true;
            default:
                break;
        }
        return false;
    }
}
