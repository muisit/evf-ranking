<?php

/**
 * EVF-Ranking
 *
 * @package             evf-ranking
 * @author              Michiel Uitdehaag
 * @copyright           2020 Michiel Uitdehaag for muis IT
 * @licenses            GPL-3.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:         evf-ranking
 * Plugin URI:          https://github.com/muisit/evf-ranking
 * Description:         Result entry and Ranking calculations for EVF
 * Version:             1.0.11
 * Requires at least:   5.4
 * Requires PHP:        7.2
 * Author:              Michiel Uitdehaag
 * Author URI:          https://www.muisit.nl
 * License:             GNU GPLv3
 * License URI:         https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:         evf-ranking
 * Domain Path:         /languages
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


function evfranking_activate() {
    require_once(__DIR__.'/activate.php');
    $activator = new \EVFRanking\Activator();
    $activator->activate();
}

function evfranking_deactivate() {
    require_once(__DIR__.'/activate.php');
    $activator = new \EVFRanking\Activator();
    $activator->deactivate();
}

function evfranking_plugins_loaded()
{
    require_once(__DIR__ . '/activate.php');
    $activator = new \EVFRanking\Activator();
    $activator->upgrade();
}

function evfranking_display_admin_page() {
    error_log('displaying admin page');
    require_once(__DIR__ . '/display.php');
    $dat = new \EVFRanking\Display();
    $dat->index();
}

function evfranking_enqueue_scripts($page) {
    error_log('adding script');
    require_once(__DIR__ . '/display.php');
    $dat = new \EVFRanking\Display();
    $dat->scripts($page);
    $dat->styles($page);
}

function evfranking_ajax_handler($page) {
    error_log('evfranking_ajax_handler');
    require_once(__DIR__ . '/api.php');
    $dat = new \EVFRanking\API();
    $dat->resolve();
}

function evfranking_admin_menu() {
    error_log('adding admin menu option');
	add_menu_page(
		__( 'Rankings' ),
		__( 'Rankings' ),
		'manage_ranking',
		'evfrankings',
        'evfranking_display_admin_page',
        'dashicons-media-spreadsheet',
        100
	);
}

function evfranking_cron_exec() {
    require_once(__DIR__ . '/activate.php');
    $activator = new \EVFRanking\Activator();
    $activator->cron();
}

function evfranking_ranking_shortcode($atts) {
    error_log('evfranking shortcode');
    require_once(__DIR__ . '/display.php');
    $actor = new \EVFRanking\Display();
    return $actor->rankingShortCode($atts);
}
function evfranking_results_shortcode($atts) {
    require_once(__DIR__ . '/display.php');
    $actor = new \EVFRanking\Display();
    return $actor->resultsShortCode($atts);
}


if (defined('ABSPATH')) {
    register_activation_hook( __FILE__, 'evfranking_deactivate' );
    register_deactivation_hook( __FILE__, 'evfranking_deactivate' );
    add_action('plugins_loaded', 'evfranking_plugins_loaded');

    add_action( 'admin_enqueue_scripts', 'evfranking_enqueue_scripts' );
    add_action( 'admin_menu', 'evfranking_admin_menu' );
    add_action( 'wp_ajax_evfranking', 'evfranking_ajax_handler' );
    add_action( 'wp_ajax_nopriv_evfranking', 'evfranking_ajax_handler' );
    add_action( 'evfranking_cron_hook', 'evfranking_cron_exec' );
    add_shortcode( 'evf-ranking', 'evfranking_ranking_shortcode' );
    add_shortcode( 'evf-results', 'evfranking_results_shortcode' );

}
