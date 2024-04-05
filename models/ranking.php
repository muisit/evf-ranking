<?php

/**
 * EVF-Ranking Ranking Model
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

class Ranking extends Base
{
    public $table = "VW_Ranking";
    public $pk="result_id";
    public $fields=array("event_id","competition_id","category_id","weapon_id","fencer_id","result_id");

    public function unselectOldTournaments() {
        // only take tournaments into account that are less than 2 years ago
        global $wpdb;
        $twoyearsago = evfranking_ftime('Y-m-d', time() - 2*365*24*60*60);
        $wpdb->query("update TD_Event set event_in_ranking='N' where event_open < '$twoyearsago';");
    }

    public function listDetail($wid, $cid, $fid) {
        global $wpdb;

        // category is factually not interesting
        $sql = "select fencer_id, fencer_surname, fencer_firstname, fencer_country_abbr, ".
               " event_name, event_open, event_location, country_name, ".
               " category_name, weapon_name, ".
               " result_entry, result_place, result_points, result_de_points, result_podium_points, result_total_points, event_factor, result_in_ranking".
               " from VW_Ranking where fencer_id='$fid' ";
        if(intval($wid)>0) {
            $sql.=" and weapon_id='$wid' ";
        }
        $sql.=" order by result_total_points DESC, event_open DESC, event_name";
        $results=$wpdb->get_results($sql);


        $retval=array();
        foreach($results as $r) {
            $entry = intval($r->result_entry);
            if($entry <=0) $entry="";
            $place = intval($r->result_place);
            $points = sprintf("%.2f",floatval($r->result_points));
            $depoints = sprintf("%.2f",floatval($r->result_de_points));
            $podpoints = sprintf("%.2f",floatval($r->result_podium_points));
            $totpoints = sprintf("%.2f",floatval($r->result_total_points));
            $factor = sprintf("%.2f",floatval($r->event_factor));
            $entry = array(
                "id"=>$r->fencer_id,
                "firstname" => $r->fencer_firstname,
                "surname" => $r->fencer_surname,
                "abbr" => $r->fencer_country_abbr,
                "event" => $r->event_name, // make sure no lines are broken
                "date" => $r->event_open,
                "year" => date('Y', strtotime($r->event_open)),
                "location" => $r->event_location,
                "country" => $r->country_name,
                "category" => $r->category_name,
                "weapon" => $r->weapon_name,
                "entry" => $entry,
                "place" => $place,
                "points" => $points,
                "de" => $depoints,
                "podium" => $podpoints,
                "total" => $totpoints,
                "factor" => $factor,
                "included" => $r->result_in_ranking
            );
            $retval[]=$entry;
        }
        return $retval;
    }

    function calculateCategoryAges($requested_category)
    {
        $catval = intval($requested_category->category_value);

        // determine the qualifying year
        $qualifying_year = intval(date('Y'));
        if(intval(date('m')) > 7) {
            $qualifying_year+=1;
        }

        $minyear=0;
        $maxyear=$qualifying_year;

        switch($catval) {
        default:
        case 1:
            $minyear = $qualifying_year - 50;
            $maxyear = $qualifying_year - 40;
            break;
        case 2:
            $minyear = $qualifying_year - 60;
            $maxyear = $qualifying_year - 50;
            break;
        case 3:
            $minyear = $qualifying_year - 70;
            $maxyear = $qualifying_year - 60;
            break;
        case 4:
            $minyear = 0;
            $maxyear = $qualifying_year - 70;
            break;
        // category not supported yet...
        case 5:
            $minyear = 0;
            $maxyear = $qualifying_year - 80;
            break;
        }
        return array($minyear,$maxyear);
    }

    public function listResults($wid, $category, $withDetails = false) {
        $wid = intval($wid);

        // determine the minimal and maximal year-of-birth values for the indicated category
        $ages = $this->calculateCategoryAges($category);

        $results = $this->select("fencer_id, fencer_surname, fencer_firstname, fencer_country_abbr, fencer_dob, sum(result_total_points) as total_points")
            ->from("VW_Ranking")
            ->where("(year(fencer_dob) > '" . $ages[0] . "' and year(fencer_dob) <= '" . $ages[1] . "')")
            ->where("weapon_id", $wid)
            ->where('result_in_ranking', 'Y')
            ->where('fencer_country_registered', 'Y')
            ->groupBy("fencer_id, fencer_surname, fencer_firstname, fencer_country_abbr")
            ->orderBy("total_points DESC, fencer_surname, fencer_firstname, fencer_id")->get();

        $retval = array();
        $pos = 1;
        $effectivepos = 0;
        $lastpoints = -1.0;
        foreach ($results as $r) {
            $points = sprintf("%.2f", floatval($r->total_points));
            $effectivepos += 1;
            // never true for the first entry
            if (floatval($points) < floatval($lastpoints)) {
                $pos = $effectivepos;
            }
            $lastpoints = $points;
            $entry = array(
                "id" => $r->fencer_id,
                "name" => $r->fencer_surname,
                "firstname" => $r->fencer_firstname,
                "country" => $r->fencer_country_abbr,
                "points" => $points,
                "pos" => $pos
            );
            if ($withDetails) {
                $entry['dob'] = $r->fencer_dob;
            }
            $retval[] = $entry;
        }
        return $retval;
    }

    private function getCutoff()
    {
        $data = intval(get_option("evfranking_ranking_count_included"));
        if (empty($data)) {
            $data = 5;
        }
        return $data;
    }

    private function getApiKey()
    {
        $data = intval(get_option("evf_internal_key"));
        if (empty($data)) {
            $data = '';
        }
        return $data;
    }

    private function getApiUser()
    {
        $data = intval(get_option("evf_internal_user"));
        if (empty($data)) {
            $data = wp_get_current_user()->ID;
        }
        return $data;
    }
    public function setApiData($data)
    {
        $data = (array)$data;
        if (!empty($data) && isset($data['cutoff'])) {
            $opt = get_option("evfranking_ranking_count_included");
            if (empty($opt)) {
                add_option('evfranking_ranking_count_included', intval($data['cutoff']));
            }
            else {
                update_option('evfranking_ranking_count_included', intval($data['cutoff']));
            }

            $opt = get_option("evf_internal_key");
            if (empty($opt)) {
                add_option('evf_internal_key', $data['apikey']);
            }
            else {
                update_option('evf_internal_key', $data['apikey']);
            }

            $opt = get_option("evf_internal_user");
            if (empty($opt)) {
                add_option('evf_internal_user', intval($data['apiuser']));
            }
            else {
                update_option('evf_internal_user', intval($data['apiuser']));
            }
        }
        return [
            'cutoff' => $this->getCutoff(),
            'apikey' => $this->getApiKey(),
            'apiuser' => $this->getApiUser()
        ];
    }

    public function calculateRankings()
    {
        $cutoff = $this->getCutoff();
        global $wpdb;
        // reset all results that we count in the ranking, except for the excluded results
        $wpdb->query("update TD_Result set result_in_ranking='N' where result_in_ranking in ('Y','N')");

        // sort by fencer, weapon, category
        // then by points to get the best results first, then by event_open to get the most recent best results first
        $results = $wpdb->get_results("select result_id, fencer_id, weapon_id, result_in_ranking from VW_Ranking order by fencer_id, weapon_id, result_total_points DESC, event_open DESC");
        $current_fencer = null;
        $current_weapon = null;
        $cnt = 0;
        $allresults = array();
        $totalresults = 0;
        foreach ($results as $r) {
            $fid = intval($r->fencer_id);
            $wid = intval($r->weapon_id);

            // change in fencer means a change in weapon as well
            if ($current_fencer === null || $current_fencer != $fid) {
                $current_fencer = $fid;
                $current_weapon = null;
            }
            // change in weapon means we start counting anew
            if ($current_weapon === null || $current_weapon != $wid) {
                $current_weapon = $wid;
                $cnt = 0;
            }

            // excluded 'excluded' results from being updated and included
            if ($cnt < $cutoff && $r->result_in_ranking != 'E') {
                $allresults[] = $r->result_id;
                $cnt += 1;
            }

            if (sizeof($allresults) > 100) {
                $totalresults += sizeof($allresults);
                $wpdb->query("update TD_Result set result_in_ranking='Y' where result_id in ('" . implode("','", $allresults) . "')");
                $allresults = array();
            }
        }

        if (sizeof($allresults) > 0) {
            $totalresults += sizeof($allresults);
            $wpdb->query("update TD_Result set result_in_ranking='Y' where result_id in ('" . implode("','", $allresults) . "')");
        }
        return $totalresults;
    }
}
