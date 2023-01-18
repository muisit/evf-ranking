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

class Migration0003 extends MigrationObject
{
    public function __construct($id = null, $forceload = false)
    {
        parent::__construct($id, $forceload);
        $this->name = "003: Adjust TD_Result table";
    }

    public function up()
    {
        $this->rawQuery("ALTER TABLE `TD_Result` ADD `result_competition` INT NULL AFTER `result_event`;");
        $this->rawQuery("update TD_Result r " .
            " inner join TD_Competition c on c.competition_event=r.result_event and c.competition_category=r.result_category and c.competition_weapon=r.result_weapon " .
            " set r.result_competition=c.competition_id");
        $this->rawQuery("ALTER TABLE `TD_Result` DROP `result_event`;");
    }

    public function down()
    {
    }
}
