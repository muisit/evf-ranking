<?php

namespace EVFRanking\Lib;

class VirtualPage
{
    public function redirectToLogin()
    {
        // if we are not logged in yet, redirect to the login page
        if (!is_user_logged_in()) {
            global $wp;
            $basepage = home_url($wp->request);
            $location = wp_login_url($basepage);
            wp_safe_redirect($location);
            return true;
        }
        return false;
    }

    protected function get_plugin_base()
    {
        return __DIR__;
    }

    protected function encode($txt)
    {
        return htmlentities($txt, ENT_QUOTES, 'utf-8');
    }

    protected function enqueue_code($script)
    {
        // insert a small piece of html to load the ranking react script
        wp_enqueue_script('evfranking', $script, array('jquery','wp-element'), EVFRANKING_VERSION);
        $dat = new API();
        $nonce = wp_create_nonce($dat->createNonceText());
        $params = array_merge(Display::$jsparams, array(
            'url' => admin_url('admin-ajax.php?action=evfranking'),
            'nonce' => $nonce
        ));
        wp_localize_script('evfranking', 'evfranking', $params);
    }

    public function createFakePost()
    {
        return array(
            "ID" => 0,
            "post_type" => "page",
            "filter" => "raw",
            "comment_status" => "closed",
            "post_date" => strftime("%Y-%m-%d %H:%M:%S")
        );
    }

    public function createPost($options)
    {
        $post = new \WP_Post((object)$options);
        wp_enqueue_style('evfranking', plugins_url('/dist/app.css', $this->get_plugin_base()), array(), EVFRANKING_VERSION);
        
        // reset wp_query properties to simulate a found page
        global $wp_query;
        $wp_query->is_page = true;
        $wp_query->is_singular = true;
        $wp_query->is_home = false;
        $wp_query->is_archive = false;
        $wp_query->is_category = false;
        unset($wp_query->query['error']);
        $wp_query->query_vars['error'] = '';
        $wp_query->is_404 = false;
        remove_filter( 'the_content', 'wpautop' );
        remove_filter( 'the_excerpt', 'wpautop' );
        return $post;
    }
}
