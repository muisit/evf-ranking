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

class Migration0008 extends MigrationObject
{
    public function __construct($id = null, $forceload = false)
    {
        parent::__construct($id, $forceload);
        $this->name = "008: Registrations";
    }

    public function up()
    {
        $this->rawQuery("DROP TABLE `TD_Event_Side`;");
        $this->rawQuery("CREATE TABLE `TD_Event_Side` (`id` int(11) NOT NULL AUTO_INCREMENT, `event_id` int(11) NOT NULL, `title` varchar(255) COLLATE utf8_bin NOT NULL, `description` text COLLATE utf8_bin NOT NULL, `costs` float NOT NULL, `competition_id` int(11) DEFAULT NULL, PRIMARY KEY (`id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;");
        $this->rawQuery("ALTER TABLE `TD_Event` DROP `event_registration_cost`, DROP `event_entry_cost`, DROP `event_dinner_cost`, DROP `event_dinner_note`;");
        $this->rawQuery("alter table TD_Event add column event_registration_open date null");
        $this->rawQuery("alter table TD_Event add column event_registration_close date null");
        $this->rawQuery("alter table TD_Event_Side add column starts date null");
    }

    public function down()
    {
    }
}
