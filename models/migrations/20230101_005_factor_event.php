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

class Migration0005 extends MigrationObject
{
    public function __construct($id = null, $forceload = false)
    {
        parent::__construct($id, $forceload);
        $this->name = "005: Move Factor to Event";
    }

    public function up()
    {
        $this->rawQuery("ALTER TABLE `TD_Event` ADD `event_factor` FLOAT DEFAULT(1);");
        $this->rawQuery("UPDATE TD_Event e inner join TD_Competition c on c.competition_event=e.event_id inner join TD_Result rs on rs.result_competition=c.competition_id set e.event_factor=rs.result_factor");
        $this->rawQuery("ALTER TABLE `TD_Result` DROP `result_factor`;");
    }

    public function down()
    {
    }
}
