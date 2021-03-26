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


namespace EVFRanking\Lib;

class Activator extends BaseLib {
     const DBVERSION="1.0.0";

    public function deactivate() {
        $ts = wp_next_scheduled( 'evfranking_cron_hook' );
        if ( $ts ) {
            wp_unschedule_event( $ts, 'evfranking_cron_hook' );
        }
        $ts = wp_next_scheduled( 'evfranking_cron_hook_10m' );
        if ( $ts ) {
            wp_unschedule_event( $ts, 'evfranking_cron_hook_10m' );
        }
    }

    public function activate() {
        if ( ! wp_next_scheduled( 'evfranking_cron_hook' ) ) {
            $date = strftime('%Y-%m-%d',time());
            $ts = strtotime($date) + (24+4) * 60 * 60; // schedule for 4 in the morning, starting 24 hours after the start of this day
            wp_schedule_event( $ts, 'daily', 'evfranking_cron_hook' );
        }
        if ( ! wp_next_scheduled( 'evfranking_cron_hook_10m' ) ) {
            wp_schedule_event( time(), '1_second', 'evfranking_cron_hook_10m' );
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

    // daily call
    public function cron() {
        $model = new \EVFRanking\Models\Ranking();

        // remove the old tournaments from the ranking automatically
        $model->unselectOldTournaments();

        // then rebuild the rankings
        $model->calculateRankings();
    }

    // every 10 minutes
    public function cron_10() {
        $model = new \EVFRanking\Models\Accreditation();
        $model->checkDirtyAccreditations();

        // run the Queue as long as we have a time limit and it doesn't take longer than, say, 9 minutes
        $start=time();
        $delta=10*60; // total time we spend
        $lastjob=0; // time for the last job
        $queue = new \EVFRanking\Models\Queue();
        while(time() < ($start + $delta - $lastjob)) {
            $qstart=time();
            // pass the estimation of the time we have left
            if(!$queue->tick(($start+$delta) - time())) {
                // end of the queue reached
                break;
            }
            $qend=time();
            // make sure we take looooong running jobs into account
            // in our estimation of the worst-case delta time for
            // our next job
            if(($qend - $qstart) > $lastjob) {
                $lastjob=$qend-$qstart;
            }
        }
    }
}