<?php

namespace {

    function wp_get_current_user()
    {
        global $wp_current_user;
        global $DB;
        return $DB->get("wp_users", $wp_current_user);
    }

    function current_user_can($capa)
    {
        global $DB;
        global $wp_current_user;
        $cando = $DB->get("wp_capabilities", $capa . "_" . $wp_current_user);
        return !empty($cando);
    }

    function esc_sql($query)
    {
        return addslashes($query);
    }
}
