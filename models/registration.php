<?php

/**
 * EVF-Ranking Registrar Model
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

 class Registration extends Base {
    public $table = "TD_Registration";
    public $pk="registration_id";
    public $fields=array("registration_id","registration_fencer","registration_role","registration_event","registration_costs","registration_date",
        "registration_paid","registration_payment", "registration_paid_hod", "registration_mainevent", "registration_state","registration_team");
    public $fieldToExport=array(
        "registration_id" => "id",
        "registration_fencer" => "fencer",
        "registration_role" => "role",
        "registration_mainevent" => "event",
        "registration_event" => "sideevent",
        "registration_costs" => "costs",
        "registration_date" => "date",
        "registration_paid" => "paid",
        "registration_paid_hod" => "paid_hod",
        "registration_payment" => "payment",
        "registration_state" => "state",
        "registration_team" => "team"

    );
    public $rules = array(
        "registration_id"=>"skip",
        "registration_fencer" => "model=Fencer|required",
        "registration_role" => "model=Role,null",
        "registration_mainevent" => "model=Event|required",
        "registration_event" => "model=SideEvent",
        "registration_costs" => "float|gte=0",
        "registration_date" => "date",
        "registration_paid" => "bool|default=N",
        "registration_paid_hod" => "bool|default=N",
        "registration_payment" => "enum=I,G,O,F,E",
        "registration_state" => "enum=R,P,C",
        "registration_team" => "trim|lt=100"
    );

    public function export($result=null) {
        if (empty($result)) {
            $result = $this;
        }
        $retval = parent::export($result);
        $fencer=new Fencer($result);
        $retval["fencer_data"] = $fencer->export();
        return $retval;
    }

    private function sortToOrder($sort) {
        return array("registration_id asc");
    }

    private function addFilter($qb, $filter,$special) {
        if (is_string($filter)) $filter = json_decode($filter, true);
        if(is_string($special)) $special = json_decode($special,true);
        if (!empty($filter)) {
            if (isset($filter["country"]) && (empty($special) || !isset($special["photoid"]))) {
                if(intval($filter["country"]) == -1) {
                    // empty selection, select only entries that have at least one org-level role for this fencer
                    $qb->where_exists(function($qb2) {
                        $qb2->select('*')->from('TD_Registration r2')
                            ->join("TD_Role", "r", "r.role_id=r2.registration_role")
                            ->join("TD_Role_Type", "rt", "r.role_type=rt.role_type_id")
                            ->where("rt.org_declaration","<>","Country")
                            ->where("f.fencer_id=r2.registration_fencer");
                    });
                }
                else {
                    $qb->where("fencer_country", $filter["country"]);
                }
            }
            if (isset($filter["sideevent"])) {
                $qb->where("TD_Registration.event_id", $filter["sideevent"]);
            }
            if (isset($filter["event"])) {
                $qb->where("TD_Registration.registration_mainevent", $filter["event"]);
            }
        }
        if(!empty($special)) {
            if(isset($special["photoid"])) {
                $qb->where_in("f.fencer_picture",array('Y','R'));
            }
        }
    }

    public function selectAll($offset,$pagesize,$filter,$sort, $special=null) {
        $qb = $this->select('TD_Registration.*, c.country_name, f.*')
          ->join("TD_Fencer", "f", "TD_Registration.registration_fencer=f.fencer_id")
          ->join("TD_Country","c","f.fencer_country=c.country_id")
          ->join("TD_Role", "r", "r.role_id=TD_Registration.registration_role")
          ->join("TD_Role_Type", "rt", "r.role_type=rt.role_type_id")
          ->offset($offset)->limit($pagesize)->orderBy($this->sortToOrder($sort));
        $this->addFilter($qb,$filter,$special);
        return $qb->get();
    }

    public function selectAllOfFencer($event,$fencer) {
        $qb = $this->select('TD_Registration.*')
          ->where("TD_Registration.registration_mainevent",$event->getKey())->where("registration_fencer",$fencer->getKey());
        return $qb->get();
    }

    public function count($filter,$special=null) {
        $qb = $this->numrows()
          ->join("TD_Fencer", "f", "TD_Registration.registration_fencer=f.fencer_id")
          ->join("TD_Country","c","f.fencer_country=c.country_id")
          ->join("TD_Role", "r", "r.role_id=TD_Registration.registration_role")
          ->join("TD_Role_Type", "rt", "r.role_type=rt.role_type_id");
        $this->addFilter($qb,$filter,$special);
        return $qb->count();
    }

    public function filterData($data, $caps) {
        if ($caps == "cashier") {
            return array(
                "id" => isset($data["id"]) ? $data["id"] : -1,
                "paid" => isset($data["paid"]) ? $data["paid"] : 'N'
            );
        } 
        else if ($caps == "accreditation") {
            return array(
                "id" => isset($data["id"]) ? $data["id"] : -1,
                "state" => isset($data["state"]) ? $data["state"] : 'R'
            );
        } 
        else if ($caps == "registrar" || $caps == "hod") {
            if(isset($data["paid"])) {
                // registrars and hods cannot set the cashier-paid status
                unset($data["paid"]);
            }
        }
        return $data;
    }

    public function save() {
        $wasnew=false;
        if($this->isNew()) {
            $this->registration_date = strftime('%Y-%m-%d');
            $wasnew=true;
        }
        if(empty($this->registration_role)) {
            $this->registration_role = 0;// athlete role by default
        }

        // only ever one specific role per fencer per sideevent
        // do not delete this specific entry in case we do an update
        $qb = $this->query()->where("registration_id", "<>", $this->getKey())
            ->where("registration_fencer", $this->registration_fencer)
            ->where("registration_role",$this->registration_role);
        if(empty($this->registration_event)) {
            $qb->where("registration_event", "=",null);
        }
        else {
            $qb->where("registration_event", $this->registration_event);
        }
        $qb->delete();

        if(parent::save()) {
            // save succesful, make all accreditations for this fencer dirty
            $model=new Accreditation();
            $model->makeDirty($this->registration_fencer,$this);

//            if($wasnew) {
//                Audit::Create($this, "created");
//            }
//            else {
//                Audit::Create($this,"updated");
//            }

            return true;
        }
        return false;
    }

    public function delete($id=null) {
        $model=$this;
        if($id!==null) {
            $model = $this->get($id);
        }
        if(empty($model)) {
            // deleting a non-existing registration always succeeds
            return true;
        }
        
        $event = new Event($model->registration_mainevent);
        if (!$event->exists()) return false;

        $caps = $event->eventCaps();

        // we do not check whether the HoD is deleting a registration for a fencer
        // belonging to the same country. That is done in the policy already
        if ($caps == "registrar" || $caps == "organiser" || $caps=="hod" || $caps=="system") {
            if(parent::delete($model->getKey())) {
                // delete succesful, make all accreditations for this fencer dirty
                $amodel=new Accreditation();
                $amodel->makeDirty($model->registration_fencer,$model);
                // clear the whole audit log for this registration
                //Audit::Clear($model);
                return true;
            }
        }
        return false;
    }

    public function overview($eid) {
        $event=new Event($eid,true);
        $retval=array();
        if($event->exists()) {
            $sides = $event->sides(null,true);
            $sidesById=array();
            $teamevents=array();
            foreach($sides as $s) {
                $sidesById["s".$s->getKey()]=$s;
                $cid=$s->competition_id;
                $comp=new Competition($s->competition_id,true);
                $cat=new Category($comp->competition_category, true);
                if($cat->category_type == 'T') {
                    $teamevents["s".$s->getKey()] = true;
                }
            }

            $rtype = RoleType::ListAll();
            $roletypeById=array();
            foreach($rtype as $r) $roletypeById["r".$r->role_type_id]=new RoleType($r);

            $roles = Role::ListAll();
            $roleById = array();
            foreach ($roles as $r) $roleById["r" . $r->role_id] = new Role($r);

            // create an overview of participants per country per sideevent
            $res=$this->select('registration_event, registration_role, fencer_country, registration_team, count(*) as cnt')
                ->join("TD_Fencer","f","f.fencer_id=TD_Registration.registration_fencer")
                ->where("registration_mainevent",$event->getKey())
                ->groupBy("registration_event, registration_role, fencer_country,registration_team")
                ->get();
            foreach($res as $row) {
                $c = $row->fencer_country;
                $ckey="c".$c;
                if(!isset($retval[$ckey])) $retval[$ckey]=array();

                $se=$row->registration_event;                
                $skey=empty($se) ? "sorg" : "s".$se;
                $tot=$row->cnt;
                $rl=$row->registration_role;

                if(intval($rl) == 0 && isset($sidesById[$skey])) {
                    if(isset($teamevents[$skey])) {
                        if(!empty($row->registration_team)) {
                            $prevcount=isset($retval[$ckey][$skey]) ? $retval[$ckey][$skey] : array(0,0);
                            $prevcount[0]+=$tot; // total participants
                            $prevcount[1]+=1; // each row is a team
                            $retval[$ckey][$skey]=$prevcount;
                        }
                        // else empty team name for a team event, but not an event-wide role... error?
                    }
                    else {
                        // individual athlete or participant
                        $retval[$ckey][$skey]=(isset($retval[$ckey][$skey]) ? $retval[$ckey][$skey] : 0) + $tot;
                    }
                }
                else {
                    $skey='sorg'; // organiser role
                    $rkey="r".$rl;
                    if(isset($roleById[$rkey])) {
                        // registration with a specific role
                        $role=$roleById[$rkey];
                        $rtkey = "r".$role->role_type;
                        if(isset($roletypeById[$rtkey])) {
                            $rt=$roletypeById[$rtkey];
                            switch($rt->org_declaration) {
                            case 'Country': break;
                            case 'Org': $ckey='corg'; $skey=$rkey; break;
                            case 'EVF': $ckey='coff'; $skey=$rkey; break;
                            case 'FIE': $ckey='coff'; $skey=$rkey; break;
                            }
                        }
                        // else keep the ckey set to the country, but set the skey to sorg
                        // to mark this as an organisatory entry
                    }
                    // else: no role and no side event... this would be an error, but treat it as
                    // a generic country-organiser
                        
                    $retval[$ckey][$skey] = (isset($retval[$ckey][$skey]) ? $retval[$ckey][$skey] : 0) + $tot;
                }
            }
        }
        return $retval;
    }
 }
 