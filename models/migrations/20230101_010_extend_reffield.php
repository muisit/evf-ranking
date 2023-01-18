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

class Migration0010 extends MigrationObject
{
    public function __construct($id = null, $forceload = false)
    {
        parent::__construct($id, $forceload);
        $this->name = "010: Extend reference field";
    }

    public function up()
    {
        $this->rawQuery("ALTER TABLE `TD_Event` CHANGE `event_reference` `event_reference` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL; ");
        $this->rawQuery("ALTER TABLE `TD_Event` CHANGE `event_organisers_address` `event_organisers_address` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL; ");
        $this->rawQuery("ALTER TABLE `TD_Event` CHANGE `event_email` `event_email` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL; ");
        $this->rawQuery("ALTER TABLE `TD_Event` CHANGE `event_web` `event_web` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL; ");
        $this->rawQuery("DROP TABLE `TD_Access`;");
        $this->rawQuery("DROP TABLE `TD_Assign`;");
        $this->rawQuery("DROP TABLE `TD_Flex`;");
        $this->rawQuery("DROP TABLE `TD_Function`;");
        $this->rawQuery("DROP TABLE `TD_Log`;");
        $this->rawQuery("DROP TABLE `TD_Note`;");
        $this->rawQuery("DROP TABLE `TD_Person`;");
        $this->rawQuery("DROP TABLE `TD_Person_Type`;");
    }

    public function down()
    {
    }
}
