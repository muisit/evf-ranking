<?php

/**
 * EVF-Ranking ExportManager
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

require_once(__DIR__."/baselib.php");
class ExportManager extends BaseLib {

    public $filetype;
    public $headers;
    public $sideevent;
    public $sideevents;
    public $event;
    public $category;

    public function export($filetype, $sideevent) {
        $this->sideevent = $sideevent;
        $this->filetype=$filetype;

        $this->event = $this->loadModel('Event');
        $this->event = $this->event->get($sideevent->event_id);

        $this->category = $this->loadModel('Category');

        $this->sideevents = $this->event->sides();
        $data = $this->sideevent->registrations();

        $this->headers = array("name", "firstname", "country", "year-of-birth", "role", "organisation", "organisation_abbr", "type", "date", "days");
        if ($this->filetype == "participants") {
            if (intval($this->sideevent->competition_id) > 0) {
                $this->headers = array("name", "firstname", "country", "year-of-birth", "cat", "gender");
            } else {
                // side-event with no competition, only print out participant list
                $this->headers = array("name", "firstname", "country", "role", "organisation");
            }
        }
        $csv = array_map(array($this,"header_map"), array_filter($data, array($this,"header_filter") ));
        $csv = array_merge(array($this->headers), $csv);
        $this->createCSV($csv, $this->filetype . "_" . $this->sideevent->title . ".csv", ",");
        return true;
    }

    private function header_filter($row) {
        if ($this->filetype == "participants" && intval($this->sideevent->competition_id) > 0) {
            // only return real athletes for competitions
            if (empty($row->role_name)) {
                return true;

                // the below code checks on category and gender for Athlete roles.
                // This should've been checked in the front-end. To allow for changes
                // in that policy, we skip filtering, but display a column with the 
                // category/gender match
                // determine category
                //if (empty($row['fencer_dob'])) {
                //    return false;
                //}
                //$yob = strftime('%Y', strtotime($row->fencer_dob));
                //$catnum = $cmodel->categoryFromYear($yob, $row->starts);
                //if($catnum != intval($row->category_value)) {
                //    return false;
                //}

                // check gender
                //if($row->fencer_gender != $row->weapon_gender) {
                //    return false;
                //}
            }
            return false;
        }
        return true;
    }

    private function header_map($row) {
        $row = (array)$row;
        $retval = array();
        
        foreach ($this->headers as $hd) {
            switch ($hd) {
            case 'name':
                $retval[] = $row['fencer_surname'];
                break;
            case 'firstname':
                $retval[] = $row['fencer_firstname'];
                break;
            case 'country':
                if ($this->filetype == "participants") {
                    $retval[] = $row['country_abbr'];
                } 
                else {
                    $retval[] = $row['country_name'];
                }
                break;
            case 'year-of-birth':
                if (empty($row['fencer_dob'])) {
                    $retval[] = '';
                } 
                else {
                    $yob = strftime('%Y', strtotime($row['fencer_dob']));
                    $retval[] = $yob;
                }
                break;
            case 'date':
                $retval[] = strftime('%Y-%m-%d', strtotime($this->sideevent->starts));
                break;
            case 'role':
                if (!empty($row['role_name'])) {
                    $retval[] = $row['role_name'];
                } 
                else {
                    if ($this->filetype == "participants") {
                        $retval[] = 'Participant';
                    } 
                    else {
                        $retval[] = 'Athlete';
                    }
                }
                break;
            case 'organisation':
                if (empty($row['role_name']) || empty($row['org_declaration'])) {
                    $retval[] = $row['country_name'];
                } 
                else {
                    if ($row['org_declaration'] == 'Country') {
                        $retval[] = $row['country_name'];
                    } 
                    else if ($row['org_declaration'] == 'Org') {
                        $retval[] = "Organisation " . $this->event->event_name;
                    } 
                    else if ($row['org_declaration'] == 'EVF') {
                        $retval[] = "European Veterans Fencing";
                    }
                }
                break;
            case 'organisation_abbr':
                if (empty($row['role_name']) || empty($row['org_declaration'])) {
                    $retval[] = $row['country_abbr'];
                } 
                else {
                    if ($row['org_declaration'] == 'Country') {
                        $retval[] = $row['country_abbr'];
                    } 
                    else if ($row['org_declaration'] == 'Org') {
                        $retval[] = "Org";
                    } 
                    else if ($row['org_declaration'] == 'EVF') {
                        $retval[] = "EVF";
                    }
                }
                break;
            case 'type':
                if (empty($row['role_name']) || empty($row['org_declaration'])) {
                    $retval[] = 'Athlete';
                } 
                else {
                    $retval[] = 'Official';
                }
                break;
            case 'cat':
                if (empty($row['fencer_dob'])) {
                    $retval[] = '(no category)';
                } 
                else {
                    $yob = strftime('%Y', strtotime($row['fencer_dob']));
                    $catnum = $this->category->categoryFromYear($yob, $row['starts']);
                    if ($catnum < 1) {
                        $retval[] = '(no category)';
                    } 
                    else if ($catnum < intval($row['category_value'])) {
                        $retval[] = "$catnum (wrong category)";
                    } 
                    else {
                        $retval[] = $catnum;
                    }
                }
                break;
            case 'gender':
                if ($row['fencer_gender'] != $row['weapon_gender']) {
                    $retval[] = $row['fencer_gender'] . " (wrong gender)";
                } 
                else {
                    $retval[] = $row['fencer_gender'];
                }
                break;
            case 'days':
                // return a list of the days this participant is allowed.
                // If the participant is allowed on all competition days, display 'ALL'
                $days=array();
                $seById=array();
                foreach($this->sideevents as $se) {
                    $se=new SideEvent($se);
                    $se->realstart=strtotime($se->starts);
                    $date=strftime('%Y-%m-%d',$se->realstart);
                    $days[$date]=false;
                    $seById["s".$se->getKey()]=$se;
                }

                // find all registrations for this fencer
                $model = $this->loadModel('Registration');
                $this->loadModel('Fencer');
                $fencer=new Fencer($row['registration_fencer']);
                $regs=$model->selectAllOfFencer($this->event,$fencer);
                foreach($regs as $r) {
                    $key = "s" . $r->registration_event;
                    if(isset($seById[$key])) {
                        $se=$seById[$key];
                        $date = strftime('%Y-%m-%d', $se->realstart);
                        $days[$date] = true;
                    }
                }

                $alltrue=true;
                foreach($days as $key=>$dt) {
                    $alltrue = $alltrue && $dt;
                }
                if($alltrue) {
                    $retval[]="ALL";
                }
                else {
                    $txt=array();
                    foreach($days as $key=>$dt) {
                        if($dt) {
                            $time=strtotime($key);
                            $entry = str_replace('  ',' ', strtoupper(strftime('%a %e',$time)));
                            $txt[]=$entry;
                        }
                    }
                    $retval[] = implode('/',$txt);
                }
                break;
            }
        }
        return $retval;
    }

    private function createCSV($data, $filename,$delimiter) {
        header('Content-Disposition: attachment; filename="'.$filename.'";');
        header('Content-Type: application/csv; charset=UTF-8');

        $f = fopen('php://output', 'w');
        foreach ($data as $line) {
            fputcsv($f, $line,$delimiter);
        }
        fpassthru($f);
        fclose($f);
        ob_flush();
        exit();
    }
}