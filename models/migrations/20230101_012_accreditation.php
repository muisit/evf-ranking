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

class Migration0012 extends MigrationObject
{
    public function __construct($id = null, $forceload = false)
    {
        parent::__construct($id, $forceload);
        $this->name = "012: Accreditation";
    }

    public function up()
    {
        $this->rawQuery("ALTER TABLE `TD_Accreditation` add column `fe_id` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL; ");
//        $this->rawQuery("CREATE TABLE `TD_Audit` (`id` int(11) NOT NULL AUTO_INCREMENT,`created` datetime NOT NULL,`creator` int(11) NOT NULL,`log` text NOT NULL,`model` varchar(100) DEFAULT NULL,`modelid` int(11) DEFAULT NULL,`data` text DEFAULT NULL, PRIMARY KEY (`id`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
        $this->rawQuery("ALTER TABLE `TD_Registration` ADD column `registration_state` CHAR(1) NULL;");
    }

    public function down()
    {
    }
}
