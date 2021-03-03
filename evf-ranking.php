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

require_once(__DIR__ . '/display.php');

function evfranking_activate() {
    error_log("activate");
    require_once(__DIR__.'/activate.php');
    $activator = new \EVFRanking\Activator();
    $activator->activate();

    // add rewrite rules just before flush, so they are added to the new cached version
    evfranking_rewrite_add_rewrites();
    flush_rewrite_rules();
    error_log('rules flushed');
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
    $dat = new \EVFRanking\Display();
    $dat->index();
}

function evfranking_display_registration_page() {
    error_log('displaying registration page');
    $dat = new \EVFRanking\Display();
    $dat->registration();
}


function evfranking_enqueue_scripts($page) {
    error_log('adding script');
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
	add_menu_page(
		__( 'Registration' ),
		__( 'Registration' ),
		'manage_registration',
		'evfregistration',
        'evfranking_display_registration_page',
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
    $actor = new \EVFRanking\Display();
    return $actor->rankingShortCode($atts);
}
function evfranking_results_shortcode($atts) {
    $actor = new \EVFRanking\Display();
    return $actor->resultsShortCode($atts);
}

function evfranking_page_template($page_template) {
    if (is_page('register')) {
        $actor = new \EVFRanking\Display();
        $page_template = $actor->displayRegistration($page_template);
    }
    return $page_template;
}

function evfranking_rewrite_add_rewrites() {
    add_rewrite_rule('register/(\d+)/?$', 'index.php?suppress_filters=1&evfranking_register=$matches[1]', 'top');
}

function simpleBT() {
    $vals=debug_backtrace();
    $retval="";
    foreach($vals as $v) {
        $retval.=basename($v["file"]).":".$v["line"]." ".$v["function"]."\r\n";
    }
    return $retval;
}

if (defined('ABSPATH')) {
    register_activation_hook( __FILE__, 'evfranking_activate' );
    register_deactivation_hook( __FILE__, 'evfranking_deactivate' );
    add_action('plugins_loaded', 'evfranking_plugins_loaded');

    add_action( 'admin_enqueue_scripts', 'evfranking_enqueue_scripts' );
    add_action( 'admin_menu', 'evfranking_admin_menu' );
    add_action( 'wp_ajax_evfranking', 'evfranking_ajax_handler' );
    add_action( 'wp_ajax_nopriv_evfranking', 'evfranking_ajax_handler' );
    add_action( 'evfranking_cron_hook', 'evfranking_cron_exec' );

    add_shortcode( 'evf-ranking', 'evfranking_ranking_shortcode' );
    add_shortcode( 'evf-results', 'evfranking_results_shortcode' );

    add_filter('page_template', 'evfranking_page_template');

    add_filter('posts_pre_query', function ($posts, $q) {
        if (empty($posts) && isset($q->query["evfranking_register"])) {
            error_log(simpleBT());
            $actor = new \EVFRanking\Display();
            $post = $actor->virtualPage($q->query["evfranking_register"]);
            $posts=array();
            $posts[]=$post;
        }
        return $posts;
    },2,99);

    // use admin_init instead of init, because we only need to add a rewrite for a possible flush,
    // which can only be done from inside the admin area
    add_action('admin_init', function () {
        error_log("\r\n\r\nevfranking init");
        // add rewrite rules in case someone decides to flush the cache
        evfranking_rewrite_add_rewrites();
    });

    add_filter('query_vars', function ($query_vars) {
        error_log("adding query vars");
        $query_vars[] = 'evfranking_register';
        return $query_vars;
    });
}
