<?php

namespace EVFRanking\Lib;

class RegisterPage extends VirtualPage
{
    public function create($eventid)
    {
        $policy = new Policy();

        if ($this->redirectToLogin()) {
            exit;
        }

        $event = $policy->feEventToBeEvent($eventid);
        if ($event == null || !$event->exists()) {
            error_log("no such event " . $eventid);
            wp_safe_redirect("/calendar");
            exit;
        }

        // logged in, so we can show the React front end
        // this creates the post content and adds the relevant scripts
        $post = $this->virtualPage($event, $policy);
        return $post;
    }

    public function virtualPage($model, $policy)
    {
        $options = $this->createFakePost();

        $options["post_name"] = "Registration";
        $options["post_title"] = "Registrations for " . $model->event_name . " at " .
            $model->event_location . " on " . strftime("%e %B %Y", strtotime($model->event_open));
        $options["post_content"] = "<div id='evfregistration-frontend-root'></div>";

        Display::$jsparams["eventid"] = intval($model->getKey());
        Display::$jsparams["eventcap"] = $policy->eventCaps($model);
        Display::$jsparams["country"] = $policy->hodCountry();
        $script = plugins_url('/dist/registrationsfe.js', $this->get_plugin_base());
        $this->enqueue_code($script);

        return $this->createPost($options);
    }
}
