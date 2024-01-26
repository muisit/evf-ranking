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
        }

        // execute the upgrade tasks as well, to allow users to run these explicitely
        // by inactivating and reactivating the plugin
        $this->upgrade();
    }

    public function upgraded($obj,$options) {
        if (isset($options["action"]) && isset($options["type"])
           && $options['action'] == 'update'
           && $options['type'] == 'plugin') {
            foreach($options['plugins'] as $each_plugin) {
                if ($each_plugin==EVFRANKING_PLUGIN_PATH) {
                    add_option("evfranking_upgrade", date("Y-m-d H:i:s"));
                }
            }
        }
    }

    public function loaded()
    {
        $upgrade_time = get_option("evfranking_upgrade");
        if(!empty($upgrade_time)) {
            $tm = strtotime($upgrade_time);
            if((time() - $tm) < 20) {
                $this->upgrade();
            }
            delete_option("evfranking_upgrade");
        }
    }

    public function upgrade()
    {
    }

    // daily call
    public function cron()
    {
        $model = new \EVFRanking\Models\Ranking();

        // remove the old tournaments from the ranking automatically
        // due to covid, unselection is being done manually for now
        //$model->unselectOldTournaments();

        // then rebuild the rankings
        $model->calculateRankings();
    }

    // every 10 minutes
    public function cron_10()
    {
    }
}
