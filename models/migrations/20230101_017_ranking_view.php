<?php

/**
 * EVF-Ranking database migration
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

class Migration0017 extends MigrationObject
{
    public function __construct($id = null, $forceload = false)
    {
        parent::__construct($id, $forceload);
        $this->name = "017: Ranking view update";
    }

    public function up()
    {
        $this->rawQuery("DROP VIEW IF EXISTS `VW_Ranking`;");
        $this->rawQuery("CREATE VIEW `VW_Ranking`  AS SELECT " .
            " e.event_id, e.event_name, e.event_open, e.event_location, cnt.country_name, " .
            " c.competition_id, " .
            " cat.category_id, cat.category_name, cat.category_abbr, " .
            " w.weapon_id, w.weapon_name, w.weapon_abbr, w.weapon_gender, " .
            " f.fencer_id, f.fencer_firstname, f.fencer_surname, f.fencer_dob, f.fencer_gender, " .
            " fcnt.country_abbr as fencer_country_abbr, fcnt.country_name as fencer_country_name, fcnt.country_registered as fencer_country_registered, " .
            " r.result_id, r.result_place, r.result_points, r.result_entry, " .
            " r.result_de_points, r.result_podium_points, r.result_total_points, e.event_factor, r.result_in_ranking " .
            " FROM TD_Result r " .
            " inner join TD_Competition c on c.competition_id=r.result_competition " .
            " inner join TD_Event e on e.event_id = c.competition_event " .
            " inner join TD_Fencer f on f.fencer_id=r.result_fencer " .
            " inner join TD_Country cnt on cnt.country_id=e.event_country " .
            " inner join TD_Country fcnt on fcnt.country_id=f.fencer_country " .
            " inner join TD_Category cat on cat.category_id=c.competition_category " .
            " inner join TD_Weapon w on w.weapon_id=c.competition_weapon " .
            " WHERE e.event_in_ranking='Y'");
    }

    public function down()
    {
    }
}
