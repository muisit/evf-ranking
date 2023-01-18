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

class Migration0002 extends MigrationObject
{
    public function __construct($id = null, $forceload = false)
    {
        parent::__construct($id, $forceload);
        $this->name = "002: Merge Result and Event";
    }

    public function up()
    {
        $this->rawQuery("insert into TD_Competition (competition_event, competition_weapon, competition_category, competition_opens, competition_weapon_check) " .
            " select distinct r.result_event, r.result_weapon, r.result_category, e.event_open, e.event_open from TD_Result r " .
            " inner join TD_Event e on e.event_id=r.result_event " .
            " where not exists(select * from TD_Competition c where c.competition_event = r.result_event and c.competition_category=r.result_category and c.competition_weapon=r.result_weapon)");
    }

    public function down()
    {
    }
}
