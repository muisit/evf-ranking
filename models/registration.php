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


 namespace EVFRanking;

 class Registration extends Base {
    public $table = "TD_Registration";
    public $pk="registration_id";
    public $fields=array("registration_id","registration_fencer","registration_role","registration_event","registration_costs","registration_date",
        "registration_paid","registration_individual", "registration_paid_hod");
    public $fieldToExport=array(
        "registration_id" => "id",
        "registration_fencer" => "fencer",
        "registration_role" => "role",
        "registration_event" => "sideevent",
        "registration_costs" => "costs",
        "registration_date" => "date",
        "registration_paid" => "paid",
        "registration_paid_hod" => "paid_hod",
        "registration_individual" => "individual",
        "event_id" => "event"

    );
    public $rules = array(
        "registration_id"=>"skip",
        "registration_fencer" => "model=Fencer|required",
        "registration_role" => "model=Role",
        "registration_event" => "model=SideEvent|required",
        "registration_costs" => "float|gte=0",
        "registration_date" => "date",
        "registration_paid" => "bool|default=N",
        "registration_paid_hod" => "bool|default=N",
        "registration_individual" => "bool|default=N",
        "event_id"=>"skip"
    );

    public function export($result=null) {
        if (empty($result)) {
            $result = $this;
        }
        $retval = parent::export($result);

        $cname=$this->loadModel("Fencer");
        $fencer=new Fencer($result);
        $retval["fencer_data"] = $fencer->export();
        return $retval;
    }

    private function sortToOrder($sort) {
        return array("registration_id asc");
    }

    private function addFilter($qb, $filter,$special) {
        if (is_string($filter)) $filter = json_decode($filter, true);
        if (!empty($filter)) {
            if (isset($filter["country"])) {
                $qb->where("fencer_country", $filter["country"]);
            }
            if (isset($filter["sideevent"])) {
                $qb->where("TD_Registration.event_id", $filter["sideevent"]);
            }
            if (isset($filter["event"])) {
                $qb->where("s.event_id", $filter["event"]);
            }
        }
    }

    public function selectAll($offset,$pagesize,$filter,$sort, $special=null) {
        $qb = $this->select('TD_Registration.*, s.event_id as event_id, c.country_name, f.*')
          ->join("TD_Fencer", "f", "TD_Registration.registration_fencer=f.fencer_id")
          ->join("TD_Country","c","f.fencer_country=c.country_id")
          ->join("TD_Event_Side", "s", "TD_Registration.registration_event=s.id")
          ->offset($offset)->limit($pagesize)->orderBy($this->sortToOrder($sort));
        $this->addFilter($qb,$filter,$special);
        return $qb->get();
    }

    public function selectAllOfFencer($event,$fencer) {
        $qb = $this->select('TD_Registration.*')
          ->join("TD_Event_Side", "s", "TD_Registration.registration_event=s.id")
          ->where("s.event_id",$event->getKey())->where("registration_fencer",$fencer->getKey());
        return $qb->get();
    }

    public function count($filter,$special=null) {
        $qb = $this->select("count(*) as cnt")
          ->join("TD_Fencer", "f", "TD_Registration.registration_fencer=f.fencer_id")
          ->join("TD_Country","c","f.country_id=c.country_id")
          ->join("TD_Event_Side", "s", "TD_Registration.registartion_event=s.id");
        $this->addFilter($qb,$filter,$special);
        return $qb->count();
    }

    public function saveFromObject($obj) {
        // further restrict the saving of data depending on the specific capabilities of the user
        // The policy already checked that the current user is allowed to register fencers on the selected
        // event and sideevent. We now check that:
        // - cashier can only change the paid field
        // - registrars cannot change the paid field
        // - organiser can set anything
        error_log("saveFromObject for registration");
        $cname=$this->loadModel("SideEvent");
        $sideevent=new $cname();
        $sideevent=$sideevent->get($obj["sideevent"]);
        if(empty($sideevent)) return false;

        $cname = $this->loadModel("Event");
        $event=new $cname();
        $event = $event->get($sideevent->event_id);
        if(empty($event)) return false;

        $caps = $event->eventCaps();
        error_log("caps is $caps");
        if($caps == "cashier") {
            // make sure only the paid field is present in the object data
            $newobject=array();
            if(isset($obj["paid"])) {
                $newobject["paid"] = $obj["paid"];
            }
            return parent::saveFromObject($newobject);
        }
        else if($caps == "registrar" || $caps=="hod" ||  $caps == "organiser") {
            if(($caps == "registrar" || $caps=="hod") && isset($obj["paid"])) {
                unset($obj["paid"]);
            }
            // we're not checking if the hod is saving a registration for a fencer
            // of the HoD's country. That is checked in the policy already
            error_log("calling parent saveFromObject");
            return parent::saveFromObject($obj);
        }
        // other roles are not allowed to save any data, but this should have been checked in the general policy
        return false;
    }

    public function save() {
        if($this->isNew()) {
            $this->registration_date = strftime('%Y-%m-%d');
        }
        if(empty($this->registration_role)) {
            $this->registration_role = 0;// athlete role by default
        }
        // only ever one registration per fencer per sideevent
        // do not delete this entry in case we do an update
        $this->query()->where("registration_id","<>",$this->getKey())->where("registration_fencer",$this->registration_fencer)->where("registration_event",$this->registration_event)->delete();
        return parent::save();
    }

    public function delete($id=null) {
        error_log("delete action for Registration object");
        $model=$this;
        if($id!==null) {
            $model = $this->get($id);
        }
        if(empty($model)) {
            // deleting a non-existing registration always succeeds
            return true;
        }

        // the policy would check this, but better twice than too little
        $cname = $this->loadModel("SideEvent");
        $sideevent = new $cname();
        $sideevent = $sideevent->get($model->registration_event);
        if (empty($sideevent)) return false;

        $cname = $this->loadModel("Event");
        $event = new $cname();
        $event = $event->get($sideevent->event_id);
        if (empty($event)) return false;

        $caps = $event->eventCaps();
        error_log("caps is $caps");

        // we do not check whether the HoD is deleting a registration for a fencer
        // belonging to the same country. That is done in the policy already
        if ($caps == "registrar" || $caps == "organiser" || $caps=="hod") {
            error_log("allowing delete for caps $caps");
            return parent::delete($model->getKey());
        }
        error_log("not allowing delete");
        return false;
    }
 }
 