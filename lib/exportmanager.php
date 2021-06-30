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


namespace EVFRanking\Lib;

class ExportManager extends BaseLib {

    public $filetype;
    public $headers;
    public $sideevent;
    public $sideevents;
    public $event;
    public $category;

    public $fencer_state;

    public function export($filetype, $sideevent,$event) {
        $this->sideevent = $sideevent;
        $this->event = $event;
        $this->filetype=$filetype;
        $this->category = new \EVFRanking\Models\Category();

        $this->sideevents = $this->event->sides();
        if(!empty($this->sideevent) && !$this->sideevent->isNew()) {
            $data = $this->sideevent->registrations();
        }
        else {
            $data = $this->event->registrations();
        }

        $this->headers = array("name", "firstname", "country", "year-of-birth", "role", "organisation", "organisation_abbr", "type", "date", "days");
        if ($this->filetype == "participants") {
            if(empty($this->sideevent) || $this->sideevent->isNew()) {
                // a list of all attendees
                $this->headers = array("name", "firstname", "country", "roles","organisation","organisation_abbr", "events");
            }
            else if (intval($this->sideevent->competition_id) > 0) {
                // list of athletes
                $this->headers = array("name", "firstname", "country", "year-of-birth", "cat", "gender");
            } 
            else {
                // side-event with no competition, only print out participant list
                $this->headers = array("name", "firstname", "country", "organisation");
            }
        }
        if($this->filetype == "cashier") {
            $this->headers=array("name","firstname","country","role","event","costs","payment","paid");
        }
        $this->fencer_state=array();
        if((empty($this->sideevent) || $this->sideevent->isNew()) && $this->filetype == "participants") {
            $this->precalc=array();
            array_map(array($this,"header_precalc"),$data);
            $data = array_values($this->precalc);
        }
        $csv = array_map(array($this,"header_map"), array_filter($data, array($this,"header_filter") ));
        usort($csv, array($this,"header_sort"));
        $csv = array_merge(array($this->headers), $csv);
        $this->createCSV($csv, $this->filetype . "_" . $this->sideevent->title . ".csv", ",");
        return true;
    }

    private function header_sort($a1, $a2) {
        for($i=0;$i<sizeof($a1);$i++) {
            $cmp = strcmp($a1[$i],$a2[$i]);
            if($cmp != 0) return $cmp;
        }
        return 0;
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
                $retval[] = strftime('%Y-%m-%d', strtotime($row['starts']));
                break;
            case 'event':
                if(empty($row['title'])) {
                    $retval[]='';
                }
                else {
                    $retval[] = $row['title'];
                }
                break;
            case 'events':
                // precalculated field
                if(isset($row['events'])) {
                    $retval[]=implode(',',$row['events']);
                }
                else {
                    $retval[]="";
                }
                break;
            case 'role':
                if (!empty($row['role_name'])) {
                    $retval[] = $row['role_name'];
                } 
                else {
                    if (intval($row["competition_id"]) > 0) {
                        $retval[] = 'Athlete';
                    } 
                    else {
                        $retval[] = 'Participant';
                    }
                }
                break;
            case 'roles':
                // precalculated field
                if(isset($row['roles'])) {
                    $retval[]=implode(',',$row['roles']);
                }
                else {
                    $retval[]="";
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
                    $se=new \EVFRanking\Models\SideEvent($se);
                    $se->realstart=strtotime($se->starts);
                    $date=strftime('%Y-%m-%d',$se->realstart);
                    $days[$date]=false;
                    $seById["s".$se->getKey()]=$se;
                }

                // find all registrations for this fencer
                $model = new \EVFRanking\Models\Registration();
                $fencer=new \EVFRanking\Models\Fencer($row['registration_fencer']);
                $fencer->load();
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
            case 'payment':
            case 'costs':
            case 'paid':
                // calculate costs for payment field in the same way
                $cost = floatval($row["costs"]); // from sideevent
                if(intval($row["competition_id"])>0) {
                    $key="f".$row["registration_fencer"];
                    $baseapplied = isset($this->fencer_state[$key]) && isset($this->fencer_state[$key]["base"]);

                    $cost = floatval($row["event_competition_fee"]);
                    if(!$baseapplied) {
                        $cost += floatval($row["event_base_fee"]);
                    }
                    if(!isset($this->fencer_state[$key])) $this->fencer_state[$key]=array();
                    // only mark 'base as applied' if we calculate the costs field
                    if($hd=="costs") $this->fencer_state[$key]["base"]=true;
                }
                if($hd == "costs") {
                    $retval[]=sprintf("%.2f",$cost);
                }
                else if($hd=="payment") {
                    if($cost > 0.0) {
                        switch($row['registration_payment']) {
                        case 'I': $retval[]="individual"; break;
                        case 'G': $retval[]="group"; break;
                        case 'O': $retval[]="organisation"; break;
                        case 'E': $retval[]="EVF"; break;
                        default:
                            $retval[]="other (".$row['registration_payment'].")";
                            break;
                        }
                    }
                    else {
                        // no costs, no cost-payment setting
                        $retval[]="";
                    }
                }
                else if($hd=="paid") {
                    if($cost > 0.0) {
                        if ($row['paid'] == 'Y') $retval[] = "yes";
                        else $retval[] = "no";
                    }
                    else {
                        // no cost, always paid
                        $retval[]="yes";
                    }
                }
                break;
            }
        }
        return $retval;
    }

    private function header_precalc($row) {
        $row = (array)$row;
        $key ="f" . $row["registration_fencer"];
        if(!isset($this->precalc[$key])) {
            $this->precalc[$key]=$row;
            $this->precalc[$key]["roles"]=array();
            $this->precalc[$key]["events"]=array();
        }
        if (!empty($row['role_name'])) {
            $this->precalc[$key]["roles"][] = $row['role_name'];
        }
        if(!empty($row['title'])) {
            $this->precalc[$key]["events"][] = $row['title'];
        }
        return $row;
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

    public function exportSummary($event, $type, $typeid) {
        $filename = \EVFRanking\Util\PDFSummary::SearchPath($event->getKey(),$type,intval($typeid));
        if(file_exists($filename)) {
            header('Content-Disposition: inline;');
            header('Content-Type: application/pdf');
            header('Expires: ' . (time() + 2 * 24 * 60 * 60));
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filename));
            readfile($filename);
            exit();
        }
        die(403);
    }
    public function exportAccreditation($event, $id) {
        $accr=new \EVFRanking\Models\Accreditation($id,true);
        if($accr->exists() && intval($accr->event_id) == intval($event->getKey())) {
            $path=$accr->getPath();
            if($accr->isDirty() || !file_exists($path)) {
                die(403);
            }
            else {
                header('Content-Disposition: inline;');
                header('Content-Type: application/pdf');
                header('Expires: ' . (time() + 2 * 24 * 60 * 60));
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($path));
                readfile($path);
                exit();
            }
        }
        die(403);
    }
}