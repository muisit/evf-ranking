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

    public function index() {
        echo <<<HEREDOC
        <div id="evfranking-root"></div>
HEREDOC;
    }

    public function displayRegistration() {
        echo <<<HEREDOC
        <div id="evfregistration-root"></div>
HEREDOC;
    }

    public function scripts($page) {
        if(in_array($page,array("toplevel_page_evfrankings"))) {
            $script = plugins_url('/dist/app.js', __FILE__);
            $this->enqueue_code($script);
        }
        if(in_array($page,array("toplevel_page_evfregistration"))) {
            $script = plugins_url('/dist/registrations.js', __FILE__);
            $this->enqueue_code($script);
        }
        error_log("scripts, page is $page");
    }

    public function styles($page) {
        if(in_array($page,array("toplevel_page_evfrankings","toplevel_page_evfregistration"))) {
            wp_enqueue_style( 'evfranking', plugins_url('/dist/app.css', __FILE__), array(), '1.0.0' );
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
        $script = plugins_url('/dist/ranking.js', __FILE__);
        $this->enqueue_code($script);
        wp_enqueue_style( 'evfranking', plugins_url('/dist/app.css', __FILE__), array(), '1.0.0' );
        $output="<div id='evfranking-ranking'></div>";
        return $output;
    }    

    public function resultsShortCode($attributes) {
        // insert a small piece of html to load the ranking react script
        $script = plugins_url('/dist/results.js', __FILE__);
        $this->enqueue_code($script);
        wp_enqueue_style( 'evfranking', plugins_url('/dist/app.css', __FILE__), array(), '1.0.0' );
        $output="<div id='evfranking-results'></div>";
        return $output;
    }    

    public function eventButton($event) {
        if(isset($event) && is_object($event) && isset($event->ID)) {
            if(Display::$policy === null) {
                require_once(__DIR__.'/policy.php');
                Display::$policy=new Policy();                
            }
            $caps = Display::$policy->eventCaps($event->ID);
            error_log("caps is $caps");

            if(in_array($caps, array("organiser","cashier","accreditation"))) {
                echo "<div class='evfranking-manage'></div>";
            }
            else if (in_array($caps, array("registrar"))) {
                echo "<div class='evfranking-register'></div>";
            }
        }
    }

    public function virtualPage($id)
    {
        error_log("faking page for id ".$id);
        global $wp;
        // create a fake post instance
        $post = new \WP_Post((object)array(
            "ID"=>$id,
            "post_type" => "page",
            "filter" => "raw",
            "post_name" => "Registration",
            "comment_status" => "closed",
            "post_title" => "Registration",
            "post_content" => "<div id='evfregistration-frontend-root'></div>",
            "post_date" => strftime("%Y-%m-%d %H:%M:%S")
        ));

        Display::$jsparams["eventid"] = intval($id);
        $script = plugins_url('/dist/registrationsfe.js', __FILE__);
        $this->enqueue_code($script);
        wp_enqueue_style('evfranking', plugins_url('/dist/app.css', __FILE__), array(), '1.0.0');
        
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