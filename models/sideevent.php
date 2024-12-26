<?php

/**
 * EVF-Ranking SideEvent Model
 *
 * @package             evf-ranking
 * @author              Michiel Uitdehaag
 * @copyright           2020 - 2024 Michiel Uitdehaag for muis IT
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

class SideEvent extends Base
{
    public $table = "TD_Event_Side";
    public $pk = "id";
    public $fields = array("id", "event_id", "title", "description", "starts", "costs", "competition_id");
    public $fieldToExport = array(
        "id" => "id",
        "event_id" => "event_id",
        "title" => "title",
        "description" => "description",
        "starts" => "starts",
        "costs" => "costs",
        "competition_id" => "competition_id"
    );
    public $rules = [];

    public $id = null;
    public $event_id = null;
    public $title = null;
    public $description = null;
    public $starts = null;
    public $costs = null;
    public $competition_id = null;

    // cannot save sideevents through this interface
    public function save()
    {
        return false;
    }

    public function delete($id = null)
    {
        // cannot delete sideevents through this interface
        return false;
    }
    
    public static function BaseRegistrationSelection($obj) {
        $qb=new QueryBuilder($obj);
        return $qb->select(array(
                'TD_Registration.*', 'f.fencer_id', 'f.fencer_surname', 'f.fencer_firstname', 'f.fencer_dob', 'fencer_gender',
                'c.country_name', 'c.country_abbr', 'c.country_flag_path',
                'es.starts', 'es.costs','es.title','es.competition_id',
                'e.event_base_fee','e.event_competition_fee','e.event_name',
                'r.role_name', 'rt.org_declaration',
                'ct.category_value', 'ct.category_type', 'wp.weapon_gender')
            )
            ->from('TD_Registration')
            ->join('TD_Fencer', 'f', 'TD_Registration.registration_fencer=f.fencer_id')
            ->join('TD_Country', 'c', 'f.fencer_country=c.country_id')
            ->join('TD_Role', 'r', 'TD_Registration.registration_role=r.role_id')
            ->join('TD_Role_Type', 'rt', 'rt.role_type_id=r.role_type')
            ->join('TD_Event_Side', 'es', 'es.id=TD_Registration.registration_event')
            ->join('TD_Event', 'e', 'e.event_id=TD_Registration.registration_mainevent')
            ->join('TD_Competition', 'cmp', 'es.competition_id=cmp.competition_id')
            ->join('TD_Category', 'ct', 'cmp.competition_category=ct.category_id')
            ->join('TD_Weapon', 'wp', 'cmp.competition_weapon=wp.weapon_id')
            ->orderBy(array("c.country_name", "r.role_name", "f.fencer_surname", "f.fencer_firstname"));
    }

    public function registrations()
    {
        $qb = SideEvent::BaseRegistrationSelection($this);
        return $qb->where("TD_Registration.registration_event", $this->getKey())->get();
    }

    public function getByCompetition($comp)
    {
        $compid = is_object($comp) ? $comp->getKey() : intval($comp);
        $rows = $this->select('*')->where('competition_id', $compid)->get();
        if (!empty($rows) && count($rows) == 1) {
            return new SideEvent($rows[0]);
        }
        return null;
    }

    public function listByEvent($event)
    {
        return $this->select('*')->where("event_id", intval($event))->orderBy(array("starts", "title","costs"))->get();
    }

}
