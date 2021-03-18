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


 namespace EVFRanking;

 class Display {
    public static $policy=null;
    public static $instance=null;
    public static $jsparams=array();

    public function __construct() {
        Display::$instance=$this;
    }

    public static function Instance() {
        if(Display::$instance === null) {
            $display=new Display();
        }
        return Display::$instance;
    }

    private function get_plugin_base() {
        return __DIR__;
    }

    public function index() {
        echo <<<HEREDOC
        <div id="evfranking-root"></div>
HEREDOC;
    }

    public function registration() {
        echo <<<HEREDOC
        <div id="evfregistration-root"></div>
HEREDOC;
    }

    public function scripts($page) {
        if(in_array($page,array("toplevel_page_evfrankings"))) {
            $script = plugins_url('/dist/app.js', $this->get_plugin_base());
            $this->enqueue_code($script);
        }
        if(in_array($page,array("toplevel_page_evfregistration"))) {
            $script = plugins_url('/dist/registrations.js', $this->get_plugin_base());
            $this->enqueue_code($script);
        }
    }

    public function styles($page) {
        if(in_array($page,array("toplevel_page_evfrankings","toplevel_page_evfregistration"))) {
            wp_enqueue_style( 'evfranking', plugins_url('/dist/app.css', $this->get_plugin_base()), array(), '1.0.0' );
//            wp_enqueue_style( 'blueprint', plugins_url('/node_modules/@blueprintjs/core/lib/css/blueprint.css', __FILE__), array(), '1.0.0' );
//            wp_enqueue_style( 'blueprint-icons', plugins_url('/node_modules/@blueprintjs/icons/lib/css/blueprint-icons.css', __FILE__), array(), '1.0.0' );
        }
    }

    private function enqueue_code($script) {
        // insert a small piece of html to load the ranking react script
        wp_enqueue_script( 'evfranking', $script, array('jquery','wp-element'), '1.0.0' );
        require_once(__DIR__ . '/api.php');
        $dat = new \EVFRanking\API();
        $nonce = wp_create_nonce( $dat->createNonceText() );
        $params= array_merge(Display::$jsparams, array(
            'url' => admin_url('admin-ajax.php?action=evfranking'),
            'nonce'    => $nonce
        ));
        wp_localize_script('evfranking', 'evfranking', $params);
    }

    public function rankingShortCode($attributes) {
        $script = plugins_url('/dist/ranking.js', $this->get_plugin_base());
        $this->enqueue_code($script);
        wp_enqueue_style( 'evfranking', plugins_url('/dist/app.css', $this->get_plugin_base()), array(), '1.0.0' );
        $output="<div id='evfranking-ranking'></div>";
        return $output;
    }    

    public function resultsShortCode($attributes) {
        // insert a small piece of html to load the ranking react script
        $script = plugins_url('/dist/results.js', $this->get_plugin_base());
        $this->enqueue_code($script);
        wp_enqueue_style( 'evfranking', plugins_url('/dist/app.css', $this->get_plugin_base()), array(), '1.0.0' );
        $output="<div id='evfranking-results'></div>";
        return $output;
    }    

    // action called when we move from the Event registration button to the Event registration page
    // Check here if we are already logged in. If not, redirect. If so, display the Event registration
    // frontend page.
    public function registerRedirect($eventid) {
        error_log("registerRedirect $eventid");
        if (Display::$policy === null) {
            require_once(__DIR__ . '/policy.php');
            Display::$policy = new Policy();
        }

        $event = Display::$policy->feEventToBeEvent($eventid);
        if($event == null) {
            error_log("no such event");
            return null;
        }

        // if we are not logged in yet, redirect to the login page
        if(!is_user_logged_in()) {
            error_log("redirecting to login");
            global $wp;
            $registrationpage = home_url($wp->request);
            $location = wp_login_url($registrationpage);
            wp_safe_redirect($location);
            exit;
        }
        else {
            error_log("creating Post");
            // logged in, so we can show the React front end
            // this creates the post content and adds the relevant scripts
            $post = $this->virtualPage($event);
            return $post;
        }
        return null;
    }

    // action called from the Event template to generate a button inside the listed event
    public function eventButton($event) {
        $id = is_object($event) ? $event->ID : intval($event);
        if(Display::$policy === null) {
            require_once(__DIR__.'/policy.php');
            Display::$policy=new Policy();                
        }
        $event = Display::$policy->feEventToBeEvent($id);
        if($event != null) {
            $caps = Display::$policy->eventCaps($event);

            $location = home_url("/register/$id");
            error_log("caps is $caps");
            if(in_array($caps, array("organiser","cashier","accreditation"))) {
                echo "<a href='$location'><div class='evfranking-manage'></div><a/>";
            }
            else if (in_array($caps, array("open","registrar","hod"))) {
                echo "<a href='$location'><div class='evfranking-register'></div></a>";
            }
        }
    }

    public function virtualPage($event)
    {
        error_log(json_encode($event));
        $id = intval($event->getKey());
        global $wp;
        // create a fake post instance
        $post = new \WP_Post((object)array(
            "ID"=>$id,
            "post_type" => "page",
            "filter" => "raw",
            "post_name" => "Registration",
            "comment_status" => "closed",
            "post_title" => "Registrations for ".$event->event_name." at ".$event->event_location." on ".strftime("%e %B %Y",strtotime($event->event_open)),
            "post_content" => "<div id='evfregistration-frontend-root'></div>",
            "post_date" => strftime("%Y-%m-%d %H:%M:%S")
        ));

        Display::$jsparams["eventid"] = intval($id);
        Display::$jsparams["eventcap"] = Display::$policy->eventCaps($event);
        Display::$jsparams["country"] = Display::$policy->hodCountry();

        $script = plugins_url('/dist/registrationsfe.js', $this->get_plugin_base());
        $this->enqueue_code($script);
        wp_enqueue_style('evfranking', plugins_url('/dist/app.css', $this->get_plugin_base()), array(), '1.0.0');
        
        // reset wp_query properties to simulate a found page
        global $wp_query;
        $wp_query->is_page = TRUE;
        $wp_query->is_singular = TRUE;
        $wp_query->is_home = FALSE;
        $wp_query->is_archive = FALSE;
        $wp_query->is_category = FALSE;
        unset($wp_query->query['error']);
        $wp_query->query_vars['error'] = '';
        $wp_query->is_404 = FALSE;

        return $post;
    }
}