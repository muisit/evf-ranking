<?php

/**
 * EVF-Ranking Event Model
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

 class Event extends Base {
    public $table = "TD_Event";
    public $pk="event_id";
    public $fields=array("event_id","event_name","event_open","event_registration_open","event_registration_close","event_year", 
        "event_duration","event_email", "event_web", "event_location", "event_country","event_type",
        "event_currency_symbol","event_currency_name","event_base_fee", "event_competition_fee",
        "event_bank","event_account_name","event_organisers_address","event_iban","event_swift","event_reference",
        "event_in_ranking", "event_factor", "event_frontend", "event_payments","event_feed","event_config",
        "type_name","country_name",
    );
    public $fieldToExport=array(
        "event_id" => "id",
        "event_name" => "name",
        "event_open" => "opens",
        "event_registration_open" => "reg_open",
        "event_registration_close" => "reg_close",
        "event_year" => "year",
        "event_duration" => "duration",
        "event_email" => "email",
        "event_web" => "web",
        "event_location" => "location",
        "event_country" => "country",
        "event_type" => "type",
        "event_currency_symbol" => "symbol",
        "event_currency_name" => "currency",
        "event_base_fee"=>"base_fee",
        "event_competition_fee" => "competition_fee",
        "event_bank" => "bank",
        "event_account_name" => "account",
        "event_organisers_address" => "address",
        "event_iban" => "iban",
        "event_swift" => "swift",
        "event_reference" => "reference",
        "event_in_ranking" => "in_ranking",
        "event_factor" => "factor",
        "event_frontend" => "frontend",
        "event_type_name" => "type_name",
        "event_payments" => "payments",
        "event_feed" => "feed",
        "event_config" => "config",
        "country_name" => "country_name",
    );
    public $rules=array(
        "event_id" => "skip",
        "event_name" => array("label"=>"Name", "rules"=>"trim|lte=100|required","message"=>"Name is required"),
        "event_open" => array("label"=>"Opens", "rules"=>"date|gt=2000-01-01|lt=2100-01-01|required","message"=>"Opening date is required"),
        "event_registration_open" => array("label"=>"Registration Start", "rules"=>"date|gt=2000-01-01|lt=2100-01-01","message"=>"Registration start date must be a valid date"),
        "event_registration_close" => array("label"=>"Registration Close", "rules"=>"date|gt=2000-01-01|lt=2100-01-01","message"=>"Registration close date must be a valid date"),
        "event_year" => array("label"=>"Year", "rules"=>"int|gte=2000|lt=2100|required","message"=>"Year of the event is required"),
        "event_duration" => array("label"=>"Duration", "rules"=>"int"),
        "event_email" => array("label"=>"E-mail", "rules"=>"email", "message"=>"E-mail address is incorrect"),
        "event_web" => array("label"=>"Website", "rules"=>"url","message"=>"Website address is incorrect"),
        "event_location" => array("label"=>"Location", "rules"=>"trim|lte=45"),
        "event_country" => array("label"=>"Country", "rules"=>"model=Country","message"=>"Please select a valid country"),
        "event_type" => array("label"=>"Type", "rules"=>"model=EventType", "message"=>"Please select a valid type"),
        "event_currency_symbol" => array("label"=>"Currency symbol", "rules"=>"trim|lte=10"),
        "event_currency_name" => array("label"=>"Currency name", "rules"=>"trim|lte=30"),
        "event_base_fee"=> array("label"=>"Base fee","rules"=>"float"),
        "event_competition_fee"=> array("label"=>"Competition fee","rules"=>"float"),
        "event_bank" => array("label"=>"Bank name", "rules"=>"trim|lte=100"),
        "event_account_name" => array("label"=>"Bank account", "rules"=>"trim|lte=100"),
        "event_organisers_address" => array("label"=>"Account address", "rules"=>"trim"),
        "event_iban" => array("label"=>"IBAN number", "rules"=>"trim|lte=40"),
        "event_swift" => array("label"=>"SWIFT code", "rules"=>"trim|lte=20"),
        "event_reference" => array("label"=>"Account reference", "rules"=>"trim|lte=255"),
        "event_in_ranking" => array("label"=>"In-Ranking", "rules"=>"bool"),
        "event_factor" => array("label" => "Factor","rules"=>"float"),
        "event_frontend" => array("label"=>"Select a valid, published front-end event", "rules"=>"model=Posts"),
        "event_payments" => array("label"=>"Select a valid payment method", "rules"=>"enum=all,group,individual"),
        "event_feed" => array("label"=>"Live feed","rules"=>"trim"),
        "event_config" => array("label"=>"Configuration","rules"=>"trim"),
        "event_type_name" => "skip",
        "country_name" => "skip",
        "competitions" => "contains=Competition,competition_list",
        "sides" => "contains=SideEvent,sides_list",
        "roles" => "contains=EventRole,roles_list"
    );

    public function export($result=null) {
        $retval=parent::export($result);
        if(isset($retval["config"])) $retval["config"]=json_decode($retval["config"],true);
        return $retval;
    }

    private function sortToOrder($sort) {
        if(empty($sort)) $sort="i";
        $orderBy=array();
        for($i=0;$i<strlen($sort);$i++) {
            $c=$sort[$i];
            switch($c) {
            default:
            case 'i': $orderBy[]="event_id asc"; break;
            case 'I': $orderBy[]="event_id desc"; break;
            case 'n': $orderBy[]="event_name asc"; break;
            case 'N': $orderBy[]="event_name desc"; break;
            case 'd': $orderBy[]="event_open asc"; break;
            case 'D': $orderBy[]="event_open desc"; break;
            case 'y': $orderBy[]="event_year asc"; break;
            case 'Y': $orderBy[]="event_year desc"; break;
            case 't': $orderBy[]="event_type_name asc"; break;
            case 'T': $orderBy[]="event_type_name desc"; break;
            case 'r': $orderBy[]="event_in_ranking asc"; break;
            case 'R': $orderBy[]="event_in_ranking desc"; break;
            case 'l': $orderBy[]="event_location asc"; break;
            case 'L': $orderBy[]="event_location desc"; break;
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
//                $qb2->where("fencer_surname","like","%$filter%")
//                    ->or_where("fencer_firstname","like","%$filter%");
                  $qb2->where("event_name like '%$filter%' or event_location like '%$filter%'");
            });
        }
        if ($special == "with_competitions") {
            $qb->where("exists(select * from TD_Competition c where c.competition_event=TD_Event.event_id)");
        }
        if ($special == "with_results") {
            $qb->where("exists(select * from TD_Competition c, TD_Result r where c.competition_event=TD_Event.event_id and r.result_competition=c.competition_id)");
        }
    }

    public function selectAll($offset,$pagesize,$filter,$sort,$special=null) {
        $qb = $this->select('TD_Event.*, c.country_name, et.event_type_name')
            ->join("TD_Country","c","TD_Event.event_country=c.country_id")
            ->join("TD_Event_Type", "et", "TD_Event.event_type=et.event_type_id")
            ->offset($offset)->limit($pagesize)->orderBy($this->sortToOrder($sort));
        $this->addFilter($qb,$filter,$special);
        return $qb->get();
    }

    public function count($filter,$special=null) {
        $qb = $this->select("count(*) as cnt");
        $this->addFilter($qb,$filter,$special);
        $result = $qb->get();
 
        if(empty($result) || !is_array($result)) return 0;
        return intval($result[0]->cnt);
    }

    public function competitions($id=null, $asObject=false) {
        if($id === null) $id = $this->getKey();
        // find the competitions belonging to this event
        $model = new Competition();
        $dt = $model->listByEvent($id);

        $retval = array();
        if (!empty($dt) && is_array($dt)) {
            foreach ($dt as $c) {
                if($asObject) {
                    $retval[] = new Competition($c);
                }
                else {
                    $retval[] = $model->export($c);
                }
            }
        }
        return $retval;
    }

    public function sides($id=null, $asObject=false) {
        if($id === null) $id = $this->getKey();
        // find the side events belonging to this event
        $model = new SideEvent();
        $dt = $model->listByEvent($id);

        $retval = array();
        if (!empty($dt) && is_array($dt)) {
            foreach ($dt as $c) {
                if($asObject) {
                    $retval[] = new SideEvent($c);
                }
                else {
                    $retval[] = $model->export($c);
                }
            }
        }
        return $retval;
    }

    public function roles($id = null, $asObject = false) {
        if ($id === null) $id = $this->{$this->pk};
        // find the roles belonging to this event
        $model = new EventRole();
        $dt = $model->listByEvent($id);

        $retval = array();
        if (!empty($dt) && is_array($dt)) {
            foreach ($dt as $c) {
                if ($asObject) {
                    $retval[] = new EventRole($c);
                } else {
                    $retval[] = $model->export($c);
                }
            }
        }
        return $retval;
    }
    public function roleOfUser($userid) {
        $model = new EventRole();
        return $model->roleOfUser($this->{$this->pk},$userid);
    }

    public function postSave($wassaved) {
        if(isset($this->competition_list)) {
            $oldcomps = $this->competitions(null,true);

            $lst = $this->sides(null,true);
            $sides=array();
            foreach($lst as $se) { 
                if(isset($se->competition_id) && intval($se->competition_id)>0) {
                    $sides["c_".$se->competition_id] = $se;
                }
            }
            $wmodel=new Weapon();
            $cmodel=new Category();

            foreach($this->competition_list as $c) {
                $c->competition_event = $this->getKey();
                $c->save();

                // make sure there is a SideEvent linked to this as well
                $se=null;
                if(!isset($sides["c_".$c->getKey()])) {
                    $se = new SideEvent();
                    $se->event_id=$this->getKey();
                    $se->competition_id=$c->getKey();
                }
                else {
                    $se = $sides["c_".$c->getKey()];
                }

                // overwrite the SideEvent details if they were changed on the competition
                $weapon=$wmodel->get($c->competition_weapon);
                $category=$cmodel->get($c->competition_category);
                if($weapon!=null && $category != null) {
                    $se->title=$weapon->weapon_name ." ".$category->category_name;
                }
                $se->starts = $c->competition_opens;
                $se->save();

                for($i=0;$i<sizeof($oldcomps);$i++) {
                    if($oldcomps[$i]->identical($c)) {
                        unset($oldcomps[$i]);
                        $oldcomps = array_values($oldcomps);
                    }
                }
            }
            foreach($oldcomps as $c) {
                if(isset($sides["c_".$c->{$c->pk}])) {
                    $sides["c_".$c->{$c->pk}]->delete();
                }
                $c->delete();
            }
        }

        if(isset($this->sides_list)) {
            $old = $this->sides(null,true); // this includes any new competitions added above
            foreach($this->sides_list as $c) {
                $c->event_id = $this->getKey();
                $c->save();

                for($i=0;$i<sizeof($old);$i++) {
                    if($old[$i]->identical($c)) {
                        unset($old[$i]);
                        $old = array_values($old);
                    }
                }
            }
            foreach($old as $c) {
                // do not remove side events linked to a competition. These are removed with the competition above
                if(!isset($c->competition_id) || intval($c->competition_id)<0) {
                    $c->delete();
                }
            }
        }

        if (isset($this->roles_list)) {
            $old = $this->roles(null, true); // this includes any new competitions added above
            foreach ($this->roles_list as $c) {
                $c->event_id = $this->getKey();
                $c->save();

                for ($i = 0; $i < sizeof($old); $i++) {
                    if ($old[$i]->identical($c)) {
                        unset($old[$i]);
                        $old = array_values($old);
                    }
                }
            }
            foreach ($old as $c) {
                $c->delete();
            }
        }        
        return true;
    }

    public function findByFeId($id) {
        $data=$this->select('*')->where('event_frontend', intval($id))->first();
        if($data !== null) {
            return new Event($data);
        }
        return null;
    }

    public function isOpen() {
        $now = time();
        $opens = strtotime($this->event_registration_open);
        $closes = strtotime($this->event_registration_close);
        return $now >= $opens && $now < $closes;
    }

    public function findOpenEvents() {
        // allow events one day ahead and 2 days behind
        $opens=strftime('%F', time()+24*60*60);
        $wayold=strftime('%F',time()-21*24*60*60);
        $retval=array();

        $res = $this->select('*')->where("event_open","<",$opens)->where("event_open",">",$wayold)->get();
        if(!empty($res) && sizeof($res)) {
            foreach($res as $e) {
                // check the duration to see if it is still open
                $base = strtotime($e->event_open);
                $addthis = 24*60*60*intval($e->event_duration);
                $closes = strtotime(strftime('%F',$base + $addthis));
                if($closes > (time() - 48*60*60)) {
                    $retval[]=new Event($e);
                }
            }
        }
        return $retval;
    }

    public function eventCaps() {
        $user = wp_get_current_user();
        $id=-1;
        if (!empty($user)) {
            $id = $user->ID;
        }

        // see if there is an event with this front-end id
        $retval="closed";

        // if the user has manage_registration rights, return it as system role
        if(current_user_can('manage_registration')) {
            return "system";
        }

        // if the user has management rights to this specific event, we always show it
        // as manageable, even when it is closed still (or again)
        if (intval($id) > 0) {
            // if the current user has special rights on the event, we return those rights
            $role = $this->roleOfUser($id);
            if ($role !== null) {
                $retval = $role->role_type;
            }
        }

        if($retval === "closed" && $this->isOpen()) {
            // open for registration, so at least return "open" to allow HoD registration
            $retval="open";

            // see if the current user is by accident a generic registrar
            $model=new Registrar();
            $registrar = $model->findByUser($id);
            if($registrar != null) {
                $retval="hod";
            }
        }
        return $retval;
    }

    // this is similar to SideEvents::registrations except it selects on the overall event
    public function registrations() {
        $qb = SideEvent::BaseRegistrationSelection($this);
        return $qb->where("TD_Registration.registration_mainevent", $this->getKey())->get();
    }

 }
 