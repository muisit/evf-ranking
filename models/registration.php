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
        "registration_paid","registration_payment", "registration_paid_hod", "registration_mainevent", "registration_state","registration_team",
        "registration_country"
    );
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
        "registration_team" => "team",
        "registration_country" => "country"

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
        "registration_team" => "trim|lt=100",
        "registration_country" => "model=Country,null"
    );

    public $registration_id = null;
    public $registration_fencer = null;
    public $registration_role = null;
    public $registration_mainevent = null;
    public $registration_event = null;
    public $registration_costs = null;
    public $registration_date = null;
    public $registration_paid = null;
    public $registration_paid_hod = null;
    public $registration_payment = null;
    public $registration_state = null;
    public $registration_team = null;
    public $registration_country = null;

    public function export($result=null) {
        if (empty($result)) {
            $result = $this;
        }
        $retval = parent::export($result);
        $fencer = new Fencer($result);
        $retval["fencer_data"] = $fencer->export();
        return $retval;
    }

    private function sortToOrder($sort) {
        return array("registration_id asc");
    }

    private function addFilter($qb, $filter, $special) {
        if (is_string($filter)) {
            $filter = json_decode($filter, true);
        }
        if (is_string($special)) {
            $special = json_decode($special, true);
        }
        if (!empty($filter)) {
            if (isset($filter["country"]) && (empty($special) || !isset($special["photoid"]))) {
                if (intval($filter["country"]) == -1) {
                    // empty selection, select only entries that have at least one org-level role for this fencer
                    //$qb->where_exists(function ($qb2) {
                    //    $qb2->select('*')->from('TD_Registration r2')
                    //        ->join("TD_Role", "r", "r.role_id=r2.registration_role")
                    //        ->join("TD_Role_Type", "rt", "r.role_type=rt.role_type_id")
                    //        ->where("rt.org_declaration", "<>", "Country")
                    //        ->where("f.fencer_id=r2.registration_fencer");
                    //});
                    $qb->where("registration_country", null);
                }
                else {
                    $qb->where("registration_country", intval($filter["country"]));
                }
            }
            if (isset($filter["event"])) {
                $qb->where("TD_Registration.registration_mainevent", intval($filter["event"]));
            }
            if (isset($filter["org_roles"])) {
                $qb->where("rt.org_declaration", "<>", "country");
            }
        }
        if (!empty($special)) {
            if (isset($special["photoid"])) {
                $qb->where_in("f.fencer_picture", array('Y','R'));
            }
        }
    }

    public function selectAll($offset, $pagesize, $filter, $sort, $special = null)
    {
        $qb = $this->select('TD_Registration.*, c.country_name, f.*')
          ->join("TD_Fencer", "f", "TD_Registration.registration_fencer=f.fencer_id")
          ->join("TD_Country", "c", "f.fencer_country=c.country_id")
          ->join("TD_Role", "r", "r.role_id=TD_Registration.registration_role")
          ->join("TD_Role_Type", "rt", "r.role_type=rt.role_type_id")
          ->offset($offset)->limit($pagesize)->orderBy($this->sortToOrder($sort));
        $this->addFilter($qb, $filter, $special);
        return $qb->get();
    }

    public function selectAllOfFencer($event, $fencer)
    {
        $qb = $this->select('TD_Registration.*')
            ->where("TD_Registration.registration_mainevent", $event->getKey())->where("registration_fencer", $fencer->getKey());
        return $qb->get();
    }

    public function count($filter, $special = null)
    {
        $qb = $this->numrows()
          ->join("TD_Fencer", "f", "TD_Registration.registration_fencer=f.fencer_id")
          ->join("TD_Country", "c", "f.fencer_country=c.country_id")
          ->join("TD_Role", "r", "r.role_id=TD_Registration.registration_role")
          ->join("TD_Role_Type", "rt", "r.role_type=rt.role_type_id");
        $this->addFilter($qb, $filter, $special);
        return $qb->count();
    }

    // cannot save registrations through this interface
    public function save()
    {
        return false;
    }

    public function delete($id = null)
    {
        // cannot delete registrations through this interface
        return false;
    }
}
