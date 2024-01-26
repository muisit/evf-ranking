<?php

/**
 * EVF-Ranking
 *
 * @package             evf-ranking
 * @author              Michiel Uitdehaag
 * @copyright           2020 - 2024 Michiel Uitdehaag for muis IT
 * @licenses            GPL-3.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:         evf-ranking
 * Plugin URI:          https://github.com/muisit/evf-ranking
 * Description:         Result entry and Ranking calculations for EVF
 * Version:             1.11.9
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

define('EVFRANKING_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('EVFRANKING_VERSION', "1.11.9");

if (defined('WP_DEBUG')) {
    // wait time before we automatically refresh a dirty accreditation
    // Set this to something like 600 seconds in production. For DEV, this can
    // be set at 0.
    define('EVFRANKING_RENEW_DIRTY_ACCREDITATONS', 0);
    // wait time for the 10-minute-queue. On dev, we don't want to wait that long
    define('EVFRANKING_CRON_WAIT_HOOK', '1_second');
}
else {
    define('EVFRANKING_RENEW_DIRTY_ACCREDITATONS', 600);
    define('EVFRANKING_CRON_WAIT_HOOK', '2_minutes');
}

function evfranking_activate()
{
    $activator = new \EVFRanking\Lib\Activator();
    $activator->activate();

    // add rewrite rules just before flush, so they are added to the new cached version
    evfranking_rewrite_add_rewrites();
    flush_rewrite_rules();
}

function evfranking_deactivate()
{
    $activator = new \EVFRanking\Lib\Activator();
    $activator->deactivate();
}

function evfranking_uninstall()
{
    $activator = new \EVFRanking\Lib\Activator();
    $activator->uninstall();
}

function evfranking_plugins_loaded()
{
    $activator = new \EVFRanking\Lib\Activator();
    $activator->loaded();
}

function evfranking_plugins_upgraded($obj, $options)
{
    $activator = new \EVFRanking\Lib\Activator();
    $activator->upgrade($obj, $options);
}

function evfranking_display_admin_page()
{
    $actor = \EVFRanking\Lib\Display::Instance();
    $actor->index();
}

function evfranking_enqueue_scripts($page)
{
    $actor = \EVFRanking\Lib\Display::Instance();
    $actor->scripts($page);
    $actor->styles($page);
}

function evfranking_ajax_handler($page)
{
    $dat = new \EVFRanking\Lib\API();
    $dat->resolve();
}

function evfranking_admin_menu()
{
    add_menu_page(
        __('Rankings'),
        __('Rankings'),
        'manage_ranking',
        'evfrankings',
        'evfranking_display_admin_page',
        'dashicons-media-spreadsheet',
        100
    );
}

function evfranking_ranking_shortcode($atts)
{
    $actor = \EVFRanking\Lib\Display::Instance();
    return $actor->rankingShortCode($atts);
}

function evfranking_results_shortcode($atts)
{
    $actor = \EVFRanking\Lib\Display::Instance();
    return $actor->resultsShortCode($atts);
}

function evfranking_feed_shortcode($atts)
{
    $actor = \EVFRanking\Lib\Display::Instance();
    return $actor->feedShortCode($atts);
}

function evfranking_rewrite_add_rewrites()
{
    // should match the event button link in \EVFRanking\Lib\Display
    add_rewrite_rule('entries/(\d+)/?$', 'index.php?suppress_filters=1&evfranking_entries=$matches[1]', 'top');
}

function simpleBT()
{
    $vals = debug_backtrace();
    $retval = "";
    foreach ($vals as $v) {
        $retval .= basename($v["file"]) . ":" . $v["line"] . " " . $v["function"] . "\r\n";
    }
    return $retval;
}

function evfranking_cron_exec()
{
    $activator = new \EVFRanking\Lib\Activator();
    $activator->cron();
}
function evfranking_cron_exec_10m()
{
    $activator = new \EVFRanking\Lib\Activator();
    $activator->cron_10();
}

function evfranking_add_cron_interval($schedules)
{
    $schedules['1_minutes'] = array(
        'interval' => 1 * 60,
        'display'  => esc_html__('Every Minute'));
    $schedules['2_minutes'] = array(
        'interval' => 2 * 60,
        'display'  => esc_html__('Every 2 Minutes'));
    $schedules['5_minutes'] = array(
        'interval' => 5 * 60,
        'display'  => esc_html__('Every 5 Minutes'));
    $schedules['10_minutes'] = array(
        'interval' => 10 * 60,
        'display'  => esc_html__('Every 10 Minutes'));
    $schedules['1_second'] = array(
        'interval' => 1,
        'display'  => esc_html__('Every Second'));
    return $schedules;
}

if (defined('ABSPATH')) {
    register_activation_hook(__FILE__, 'evfranking_activate');
    register_deactivation_hook(__FILE__, 'evfranking_deactivate');
    register_uninstall_hook(__FILE__, 'evfranking_uninstall');
    add_action('plugins_loaded', 'evfranking_plugins_loaded');
    add_action('upgrader_process_complete', 'evfranking_plugins_upgraded', 10, 2);

    add_action('admin_enqueue_scripts', 'evfranking_enqueue_scripts');
    add_action('admin_menu', 'evfranking_admin_menu');
    add_action('wp_ajax_evfranking', 'evfranking_ajax_handler');
    add_action('wp_ajax_nopriv_evfranking', 'evfranking_ajax_handler');
    add_action('evfranking_cron_hook', 'evfranking_cron_exec');
    add_action('evfranking_cron_hook_10m', 'evfranking_cron_exec_10m');

    add_shortcode('evf-ranking', 'evfranking_ranking_shortcode');
    add_shortcode('evf-results', 'evfranking_results_shortcode');
    add_shortcode('evf-feed', 'evfranking_feed_shortcode');

    add_filter('cron_schedules', 'evfranking_add_cron_interval');

    add_filter('posts_pre_query', function ($posts, $q) {
        $post = null;
        if (empty($posts) && isset($q->query["evfranking_entries"])) {
            $actor = \EVFRanking\Lib\Display::Instance();
            $post = $actor->overviewRedirect($q->query["evfranking_entries"]);
        }
        if (!empty($post)) {
            $posts = array();
            $posts[] = $post;
        }
        return $posts;
    }, 2, 99);

    // add the rewrite rules on every init
    add_action('init', function () {
        // add rewrite rules in case someone decides to flush the cache
        evfranking_rewrite_add_rewrites();
    });

    add_filter('query_vars', function ($query_vars) {
        $query_vars[] = 'evfranking_entries';
        return $query_vars;
    });

    add_action('event_extend', function ($event) {
        $actor = \EVFRanking\Lib\Display::Instance();
        $actor->eventButton($event);
    });
}

require_once('lib/testlogger.php');
global $evflogger;
$evflogger = new \EVFRanking\Lib\TestLogger();

function evfranking_autoloader($name)
{
    if (!strncmp($name, 'EVFRanking\\', 11)) {
        $elements = explode('\\', strtolower($name));
        // require at least EVFRanking\<sub>\<name>, so 3 elements
        if (sizeof($elements) > 2 && $elements[0] == "evfranking") {
            $fname = $elements[sizeof($elements) - 1] . ".php";
            $dir = implode("/", array_splice($elements, 1, -1)); // remove the evfranking part
            if (file_exists(__DIR__ . "/" . $dir . "/" . $fname)) {
                include(__DIR__ . "/" . $dir . "/" . $fname);
            }
        }
    }
}

spl_autoload_register('evfranking_autoloader');
require_once('vendor/autoload.php');
