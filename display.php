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
            $script = plugins_url('/dist/app.js', __FILE__);
            $this->enqueue_code($script);
        }
        if(in_array($page,array("toplevel_page_evfregistration"))) {
            $script = plugins_url('/dist/registrations.js', __FILE__);
            $this->enqueue_code($script);
        }
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
        wp_localize_script(
            'evfranking',
            'evfranking',
            array(
                'url' => admin_url( 'admin-ajax.php?action=evfranking' ),
                'nonce'    => $nonce,
            )
        );
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
}