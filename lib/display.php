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
        $dat = new API();
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

    public function feedShortCode($attributes) {
        $attributes = shortcode_atts(array(
            "id"=>-1,
            "name"=>""
        ),$attributes);

        // check to see if there is currently any event open
        $model=new \EVFRanking\Models\Event();
        $events = $model->findOpenEvents();

        $found=null;
        foreach($events as $e) {
            error_log("checking event ".json_encode($e->export()));
            // if we have an id, make sure it matches
            if(isset($attributes["id"]) && intval($attributes["id"]) > 0) {
                error_log("id set");
                if(intval($attributes["id"]) == intval($e->getKey())) {
                    $found=$e;
                    break;
                }
            }
            // if we have part of a title, make sure it matches
            else if(isset($attributes["name"]) && strlen($attributes["name"])) {
                error_log("title set to ".$attributes['name']." and checking with ".$e->event_name);
                if(strpos(strtolower($e->event_name), strtolower($attributes["name"])) !== FALSE) {
                    $found=$e;
                    break;
                }
            }
            else if(strlen($e->event_feed)) {
                error_log("feed found, taking first");
                // take the first event with a live feed url
                $found=$e;
                break;
            }
        }
        
        if(!empty($found) && strlen($found->event_feed)) {
            wp_enqueue_style( 'evfranking', plugins_url('/dist/app.css', $this->get_plugin_base()), array(), '1.0.0' );
            return "<a href='".addslashes($found->event_feed)."' target='_blank'><div class='live-feed'></div></a>";
        }
        return "";
    }

    // action called when we move from the Event registration button to the Event registration page
    // Check here if we are already logged in. If not, redirect. If so, display the Event registration
    // frontend page.
    public function registerRedirect($eventid) {
        if (Display::$policy === null) {
            Display::$policy = new Policy();
        }

        $event = Display::$policy->feEventToBeEvent($eventid);
        if($event == null) {
            error_log("no such event");
            return null;
        }

        // if we are not logged in yet, redirect to the login page
        if(!is_user_logged_in()) {
            global $wp;
            $registrationpage = home_url($wp->request);
            $location = wp_login_url($registrationpage);
            wp_safe_redirect($location);
            exit;
        }
        else {
            // logged in, so we can show the React front end
            // this creates the post content and adds the relevant scripts
            $post = $this->virtualPage("register",$event);
            return $post;
        }
        return null;
    }

    public function registerAccreditRedirect($accrid) {
        if (Display::$policy === null) {
            Display::$policy = new Policy();
        }

        // if we are not logged in yet, redirect to the login page
        if(!is_user_logged_in()) {
            global $wp;
            $registrationpage = home_url($wp->request);
            $location = wp_login_url($registrationpage);
            wp_safe_redirect($location);
            exit;
        }
        else {
            $model=new \EVFRanking\Models\Accreditation();
            $accreditation = $model->findByID($accrid);
            // logged in, so we can show the React front end
            // this creates the post content and adds the relevant scripts
            $post = $this->virtualPage("accreditation",$accreditation);
            return $post;
        }
        return null;
    }


    // action called from the Event template to generate a button inside the listed event
    public function eventButton($event) {
        $id = is_object($event) ? $event->ID : intval($event);
        if(Display::$policy === null) {
            Display::$policy=new Policy();                
        }
        $event = Display::$policy->feEventToBeEvent($id);
        if($event != null) {
            $caps = Display::$policy->eventCaps($event);

            $location = home_url("/register/$id");
            if(in_array($caps, array("system","organiser","cashier","accreditation"))) {
                echo "<a href='$location'><div class='evfranking-manage'></div><a/>";
            }
            else if (in_array($caps, array("open","registrar","hod"))) {
                echo "<a href='$location'><div class='evfranking-register'></div></a>";
            }

            // if the event has a live feed, just display it
            if(strlen($event->event_feed)) {
                wp_enqueue_style( 'evfranking', plugins_url('/dist/app.css', $this->get_plugin_base()), array(), '1.0.0' );
                echo "<a href='".addslashes($event->event_feed)."' target='_blank'><div class='live-feed'></div></a>";
            }
        }
    }

    public function virtualPage($pagetype, $model) {
        $id = !empty($model) ? intval($model->getKey()) : random_int(0,PHP_INT_MAX);

        // create a fake post instance
        $options = array(
            "ID"=>$id,
            "post_type" => "page",
            "filter" => "raw",
            "comment_status" => "closed",
            "post_date" => strftime("%Y-%m-%d %H:%M:%S")
        );
        if($pagetype == "register" && !empty($model)) {
            $options["post_name"]="Registration";
            $options["post_title"]="Registrations for ". $model->event_name." at ". $model->event_location." on ".strftime("%e %B %Y",strtotime($model->event_open));
            $options["post_content"]="<div id='evfregistration-frontend-root'></div>";

            Display::$jsparams["eventid"] = intval($id);
            Display::$jsparams["eventcap"] = Display::$policy->eventCaps($model);
            Display::$jsparams["country"] = Display::$policy->hodCountry();
            $script = plugins_url('/dist/registrationsfe.js', $this->get_plugin_base());
            $this->enqueue_code($script);
        }
        else if($pagetype == "accreditation") {
            $accreditation= $model;
            if(!empty($accreditation)) {
                $event=new \EVFRanking\Models\Event($accreditation->event_id);
                Display::$jsparams["eventcap"] = Display::$policy->eventCaps($event);
            }
            else {
                Display::$jsparams["eventcap"] = "accreditation";
            }
            $options["post_name"]="Accreditation Check";
            $options["post_title"]="Accreditation Check";
            $options["post_content"]="<div id='evfaccreditation-frontend-root'></div>";

            if(!empty($accreditation) && $accreditation->exists()) {
                $edata=$accreditation->export();
                if(isset($edata["data"])) unset($edata["data"]);
                $edata["fe_id"]=$accreditation->fe_id; // exception: export the fe-id back to the front-end
                Display::$jsparams["accreditation"] = $edata;
                $fencer=new \EVFRanking\Models\Fencer($accreditation->fencer_id,true);
                Display::$jsparams["fencer"] = $fencer->export();
            }
            else {
                error_log("accreditation does not exist ... ".json_encode($accreditation));
                Display::$jsparams["accreditation"] = array("id"=>-1);
            }
            
            $script = plugins_url('/dist/accreditationfe.js', $this->get_plugin_base());
            $this->enqueue_code($script);
        }
        $post = new \WP_Post((object)$options);
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