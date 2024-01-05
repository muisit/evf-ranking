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

class Activator extends BaseLib
{
    const DBVERSION = "1.0.0";

    public function deactivate()
    {
        $ts = wp_next_scheduled( 'evfranking_cron_hook' );
        if ($ts) {
            wp_unschedule_event( $ts, 'evfranking_cron_hook' );
        }
        $ts = wp_next_scheduled( 'evfranking_cron_hook_10m' );
        if ($ts) {
            wp_unschedule_event( $ts, 'evfranking_cron_hook_10m' );
        }
    }

    public function uninstall()
    {
        $this->deactivate();
        delete_option("evfranking_upgrade");
        delete_option(\EVFRanking\Models\AccreditationTemplate::OPTIONNAME);
    }

    public function activate() {
        if ( ! wp_next_scheduled( 'evfranking_cron_hook' ) ) {
            $date = date('Y-m-d',time());
            $ts = strtotime($date) + (24+4) * 60 * 60; // schedule for 4 in the morning, starting 24 hours after the start of this day
            wp_schedule_event( $ts, 'daily', 'evfranking_cron_hook' );
        }
        if ( ! wp_next_scheduled( 'evfranking_cron_hook_10m' ) ) {
            wp_schedule_event( time(), EVFRANKING_CRON_WAIT_HOOK, 'evfranking_cron_hook_10m' );
        }

        if (!current_user_can('manage_ranking')) {
            $role = get_role('administrator');
            $role->add_cap('manage_ranking', true);
            $role->add_cap('manage_registration', true);
        }

        // execute the upgrade tasks as well, to allow users to run these explicitely
        // by inactivating and reactivating the plugin
        $this->upgrade();
    }

    public function upgraded($obj,$options) {
        if (isset($options["action"]) && isset($options["type"])
           && $options['action'] == 'update' 
           && $options['type'] == 'plugin' ) {
            foreach($options['plugins'] as $each_plugin) {
                if ($each_plugin==EVFRANKING_PLUGIN_PATH) {
                    add_option("evfranking_upgrade", date("Y-m-d H:i:s"));
                }
            }
        }
    }

    public function loaded() {
        $upgrade_time = get_option("evfranking_upgrade");
        if(!empty($upgrade_time)) {
            $tm = strtotime($upgrade_time);
            if((time() - $tm) < 20) {
                $this->upgrade();
            }
            delete_option("evfranking_upgrade");
        }
    }

    public function upgrade() {
        // request installation of the TCPDF library
        do_action( 'extlibraries_install', 'tcpdf','evf-ranking','6.4.1');
        do_action( 'extlibraries_install', 'fpdf','evf-ranking','2.3.6');
    }

    // daily call
    public function cron() {
        $model = new \EVFRanking\Models\Ranking();

        // remove the old tournaments from the ranking automatically
        // due to covid, unselection is being done manually for now
        //$model->unselectOldTournaments();

        // then rebuild the rankings
        $model->calculateRankings();

        // clear out Queue entries that are too old
        $model = new \EVFRanking\Models\Queue();
        $model->cleanup();

        // clear out accreditations and documents no longer needed
        $model = new \EVFRanking\Models\Event();
        $model->cleanEvents();
    }

    // every 10 minutes
    public function cron_10() {
        $model = new \EVFRanking\Models\Accreditation();
        $model->checkDirtyAccreditations();

        // run the Queue as long as we have a time limit and it doesn't take longer than, say, 9 minutes
        $start = time();
        $delta = 10 * 60; // total time we spend
        $lastjob = 0; // time for the last job
        $queue = new \EVFRanking\Models\Queue();
        $queue->queue = "default"; // run only the default queue
        while (time() < ($start + $delta - $lastjob)) {
            $qstart = time();
            // pass the estimation of the time we have left
            if (!$queue->tick(($start + $delta) - time())) {
                // end of the queue reached
                break;
            }
            $qend = time();
            // make sure we take looooong running jobs into account
            // in our estimation of the worst-case delta time for
            // our next job
            if (($qend - $qstart) > $lastjob) {
                $lastjob = $qend - $qstart;
            }
        }
    }
}
