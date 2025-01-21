<?php

/**
 * EVF-Ranking Display Interface
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

class Display
{
    public static $policy = null;
    public static $instance = null;
    public static $jsparams = array();

    public function __construct()
    {
        Display::$instance = $this;
    }

    public static function Instance()
    {
        if (Display::$instance === null) {
            $display = new Display();
        }
        return Display::$instance;
    }

    private function get_plugin_base()
    {
        return __DIR__;
    }

    public function index()
    {
        echo <<<HEREDOC
        <div id="evfranking-root"></div>
HEREDOC;
    }

    public function scripts($page)
    {
        if (in_array($page, array("toplevel_page_evfrankings"))) {
            $script = plugins_url('/dist/app.js', $this->get_plugin_base());
            $this->enqueue_code($script);
        }
    }

    public function styles($page)
    {
        if (in_array($page, array("toplevel_page_evfrankings"))) {
            wp_enqueue_style('evfranking', plugins_url('/dist/app.css', $this->get_plugin_base()), array(), EVFRANKING_VERSION);
        }
    }

    private function enqueue_code($script)
    {
        // insert a small piece of html to load the ranking react script
        wp_enqueue_script( 'evfranking', $script, array('jquery','wp-element'), EVFRANKING_VERSION );
        $dat = new API();
        $nonce = wp_create_nonce($dat->createNonceText());
        $params = array_merge(Display::$jsparams, array(
            'url' => admin_url('admin-ajax.php?action=evfranking'),
            'api' => API_URL,
            'nonce' => $nonce,
            'capabilities' => (new Policy())->getCapabilities()
        ));
        wp_localize_script('evfranking', 'evfranking', $params);
    }

    public function rankingShortCode($attributes)
    {
        $script = plugins_url('/dist/ranking.js', $this->get_plugin_base());
        $this->enqueue_code($script);
        wp_enqueue_style('evfranking', plugins_url('/dist/app.css', $this->get_plugin_base()), array(), EVFRANKING_VERSION);
        $output = "<div id='evfranking-ranking'></div>";
        return $output;
    }

    public function resultsShortCode($attributes)
    {
        // insert a small piece of html to load the ranking react script
        $script = plugins_url('/dist/results.js', $this->get_plugin_base());
        $this->enqueue_code($script);
        wp_enqueue_style('evfranking', plugins_url('/dist/app.css', $this->get_plugin_base()), array(), EVFRANKING_VERSION);
        $output = "<div id='evfranking-results'></div>";
        return $output;
    }

    public function feedShortCode($attributes)
    {
        $attributes = shortcode_atts(array(
            "id" => -1,
            "name" => ""
        ), $attributes);

        // check to see if there is currently any event open
        $model = new \EVFRanking\Models\Event();
        $events = $model->findOpenEvents();

        $found = null;
        foreach ($events as $e) {
            // if we have an id, make sure it matches
            if (isset($attributes["id"]) && intval($attributes["id"]) > 0) {
                if (intval($attributes["id"]) == intval($e->getKey())) {
                    if (!empty($e->event_feed)) {
                        $found = $e->event_feed;
                        break;
                    }
                }
            }
            // if we have part of a title, make sure it matches
            else if (isset($attributes["name"]) && strlen($attributes["name"])) {
                if (strpos(strtolower($e->event_name), strtolower($attributes["name"])) !== false) {
                    if (!empty($e->event_feed)) {
                        $found = $e->event_feed;
                        break;
                    }
                }
            }
            else if (!empty($e->event_feed)) {
                // take the first event with a live feed url
                $found = $e->event_feed;
                break;
            }
        }
        
        if (empty($found)) {
            // else find all tribe events
            $args = array(
                'post_type' => 'tribe_events',
                'post_status' => 'publish',
                'orderby' => 'meta_value',
                'meta_type' => 'DATETIME',
                'meta_key' => '_EventStartDate',
                'meta_query' => array(
                    array(
                        'key' => '_EventEndDate',
                        'value' => (new \DateTimeImmutable())->sub((new \DateInterval("P4D")))->format('Y-m-d'),
                        'compare' => '>=',
                        'type' => 'DATETIME'
                    ),
                    array(
                        'key' => '_EventStartDate',
                        'value' => (new \DateTimeImmutable())->add((new \DateInterval("P2D")))->format('Y-m-d'),
                        'compare' => '<=',
                        'type' => 'DATETIME'
                    )
                )
            );
            $query = new \WP_Query($args);
            while ($query->have_posts()) {
                $found = $this->getLiveFeedFromWPPost($query->post);
                break;
            }
        }

        if (!empty($found)) {
            wp_enqueue_style('evfranking', plugins_url('/dist/app.css', $this->get_plugin_base()), array(), EVFRANKING_VERSION);
            return "<a href='" . addslashes($found) . "' target='_blank'><div class='live-feed'></div></a>";
        }
        return "";
    }

    public function overviewRedirect($eventid)
    {
        $page = new OverviewPage();
        return $page->create($eventid);
    }

    // action called from the Event template to generate a button inside the listed event
    public function eventButton($wpEvent)
    {
        $id = is_object($wpEvent) ? $wpEvent->ID : intval($wpEvent);
        if (Display::$policy === null) {
            Display::$policy = new Policy();
        }
        $model = new \EVFRanking\Models\Event();
        $event = $model->get($this->getBOIdFromWPPost($id));
        if ($event != null) {
            $caps = $event->eventCaps();

            if (in_array($caps, array("system","organiser","cashier","accreditation", "open","registrar","hod","hod-view"))) {
                $location = "https://register.veteransfencing.eu?event=" . $event->getKey();
                echo "<a href='$location'><div class='evfranking-register'></div></a>";

                $location = home_url("/entries/$id");
                echo "<a href='$location'><div class='evfranking-entries'></div></a>";
            }
        }

        $url = '';
        if (is_object($wpEvent)) {
            $url = $this->getLiveFeedFromWPPost($wpEvent);
        }
        elseif ($event != null && strlen($event->event_feed ?? '')) {
            $url = $event->event_feed;
        }
        if (!empty($url)) {
            // add the style sheet so we can style the front end button
            wp_enqueue_style('evfranking', plugins_url('/dist/app.css', $this->get_plugin_base()), array(), EVFRANKING_VERSION);
            echo "<a href='" . addslashes($url) . "' target='_blank'><div class='evfranking-livefeed'></div></a>";
        }
    }

    private function getLiveFeedFromWPPost($post)
    {
        $retval = get_post_meta($post->ID, 'live_results', true);
        return $retval;
    }

    private function getBOIdFromWPPost($postid)
    {
        $retval = get_post_meta($postid, 'backofficeid', true);
        return $retval;
    }
}
