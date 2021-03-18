<?php

/**
 * EVF-Ranking activation routines
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


namespace EVFRanking;
require_once(__DIR__ . '/baselib.php');

class Activator extends BaseLib {
     const DBVERSION="1.0.0";

    public function deactivate() {
        $ts = wp_next_scheduled( 'evfranking_cron_hook' );
        if ( $ts ) {
            wp_unschedule_event( $ts, 'evfranking_cron_hook' );
        }
    }

    public function activate() {
        if ( ! wp_next_scheduled( 'evfranking_cron_hook' ) ) {
            $date = strftime('%Y-%m-%d',time());
            $ts = strtotime($date) + (24+4) * 60 * 60; // schedule for 4 in the morning, starting 24 hours after the start of this day
            wp_schedule_event( time(), 'daily', 'evfranking_cron_hook' );
        }
    }

    public function upgrade() {
        $installed_ver = get_option("evfranking_db_version");

        if (empty($installed_ver) || version_compare($installed_ver, "1.0.0") < 0) {
            // no version, but we do not create new tables...
        }

        if(empty($installed_ver)) {
            add_option("evfranking_db_version", Activator::DBVERSION);
        }
        else {
            update_option("evfranking_db_version", Activator::DBVERSION);
        }
    }

    public function cron() {
        $model = $this->loadModel("Ranking");

        // remove the old tournaments from the ranking automatically
        $model->unselectOldTournaments();

        // then rebuild the rankings
        $model->calculateRankings();
    }
 }