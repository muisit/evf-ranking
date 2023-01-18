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

class Migration0009b extends MigrationObject
{
    public function __construct($id = null, $forceload = false)
    {
        parent::__construct($id, $forceload);
        $this->name = "009: Registrations 3";
    }

    public function up()
    {
        $this->rawQuery("alter table TD_Registration add column registration_paid_hod enum('Y','N') null");
        $this->rawQuery("alter table TD_Event add column event_payments varchar(20) null");
        $this->rawQuery("alter table TD_Fencer add column fencer_picture enum('Y','N','A','R') null");
    }

    public function down()
    {
    }
}
