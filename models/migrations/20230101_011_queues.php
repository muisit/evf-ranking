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

class Migration0011 extends MigrationObject
{
    public function __construct($id = null, $forceload = false)
    {
        parent::__construct($id, $forceload);
        $this->name = "011: Queues";
    }

    public function up()
    {
        $this->rawQuery("CREATE TABLE `TD_Queue` (`id` int(11) NOT NULL AUTO_INCREMENT,`state` varchar(20) NOT NULL,`payload` text NOT NULL,`attempts` int(11) NOT NULL,`started_at` datetime NULL,`finished_at` datetime NULL,`created_at` datetime NOT NULL,`available_at` datetime  NULL,`queue` varchar(20) NOT NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
        $this->rawQuery("CREATE TABLE `TD_Accreditation` (`id` int(11) NOT NULL AUTO_INCREMENT ,`fencer_id` int(11) NOT NULL,`event_id` int(11) NOT NULL,`data` text COLLATE utf8_bin NOT NULL,`hash` varchar(512) COLLATE utf8_bin DEFAULT NULL,`file_hash` varchar(512) COLLATE utf8_bin DEFAULT NULL,`template_id` int(11) NOT NULL,`file_id` varchar(255) COLLATE utf8_bin NULL,`generated` datetime NULL, `is_dirty` DATETIME NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin");
        $this->rawQuery("CREATE TABLE `TD_Accreditation_Template` (`id` int(11) NOT NULL AUTO_INCREMENT, `name` varchar(200) COLLATE utf8_bin NOT NULL,`content` text COLLATE utf8_bin NOT NULL,`event_id` int(11) NULL,PRIMARY KEY(`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin");
        $this->rawQuery("alter table TD_Registration add column registration_mainevent int null");
        $this->rawQuery("ALTER TABLE `TD_Registration` CHANGE `registration_event` `registration_event` INT(11) NULL; ");
        $this->rawQuery("ALTER TABLE `TD_Registration` ADD `registration_payment` CHAR(1) NULL AFTER `registration_mainevent`; ");
        $this->rawQuery("ALTER TABLE `TD_Registration` DROP `registration_individual`;");
    }

    public function down()
    {
    }
}
