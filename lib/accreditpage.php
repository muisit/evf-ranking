<?php

namespace EVFRanking\Lib;

class AccreditPage extends VirtualPage
{
    public function create($accrid)
    {
        $policy = new Policy();

        if ($this->redirectToLogin()) {
            exit;
        }

        $model = new \EVFRanking\Models\Accreditation();
        $accreditation = $model->findByID($accrid);

        // logged in, so we can show the React front end
        // this creates the post content and adds the relevant scripts
        $post = $this->virtualPage($accreditation);
        return $post;
    }

    public function virtualPage($model)
    {
        $options = $this->createFakePost();

        $event = null;
        if (!empty($model)) {
            $this->id = $model->getKey();
            $event = new \EVFRanking\Models\Event($model->event_id);
            Display::$jsparams["eventcap"] = Display::$policy->eventCaps($event);
        }
        else {
            Display::$jsparams["eventcap"] = "accreditation";
        }

        $options["post_name"] = "Accreditation Check";
        $options["post_title"] = "Accreditation Check";
        $options["post_content"] = "<div id='evfaccreditation-frontend-root'></div>";
        //$options['post_parent'] = 0;

        if (!empty($model) && $model->exists()) {
            $edata = $model->export();
            if (isset($edata["data"])) unset($edata["data"]);
            $edata["fe_id"] = $model->fe_id; // exception: export the fe-id back to the front-end
            Display::$jsparams["accreditation"] = $edata;
            $fencer = new \EVFRanking\Models\Fencer($model->fencer_id,true);
            Display::$jsparams["fencer"] = $fencer->export();
        }
        else {
            error_log("accreditation does not exist ... " . json_encode($model));
            Display::$jsparams["accreditation"] = array("id" => -1);
        }
        
        $script = plugins_url('/dist/accreditationfe.js', $this->get_plugin_base());
        $this->enqueue_code($script);

        return $this->createPost($options);
    }
}
