<?php

/**
 * EVF-Ranking API interface
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

class API extends BaseLib {
    public function createNonceText()
    {
        $user = wp_get_current_user();
        if (!empty($user)) {
            return "evfranking" . $user->ID;
        }
        return "evfranking";
    }

    public function resolve()
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (!empty($_FILES)) {
            $retval = $this->doFile($_POST["nonce"]);
        }
        else if (empty($data) || !isset($data['nonce']) || !isset($data['path'])) {
            if (empty($data)) {
                // see if we have the proper GET requests for a download
                if (!empty($this->fromGet("action")) && !empty($this->fromGet("nonce"))) {
                    $retval = $this->doGet($this->fromGet("action"), $this->fromGet("nonce"));
                }
            }

            error_log('die because no path nor nonce');
            die(403);
        }
        else {
            $retval = $this->doPost($data);
        }

        if (!isset($retval["error"])) {
            wp_send_json_success($retval);
        } else {
            wp_send_json_error($retval);
        }
        wp_die();
    }

    protected function checkNonce($nonce)
    {
        $result = wp_verify_nonce($nonce, $this->createNonceText());
        if (!($result === 1 || $result === 2)) {
            error_log('die because nonce does not match');
            die(403);
        }
    }

    private function fromGet($var, $def = null)
    {
        if (isset($_GET[$var])) {
            return $_GET[$var];
        }
        return $def;
    }
    private function fromPost($var, $def = null)
    {
        if (isset($_POST[$var])) {
            return $_POST[$var];
        }
        return $def;
    }

    private function doGet($action, $nonce)
    {
        global $evflogger;
        $this->checkNonce($nonce);
        if ($action == "evfranking") {
            $filetype = $this->fromGet("download");
            $sid = $this->fromGet("event");
            $eid = $this->fromGet("mainevent");
            $picture = $this->fromGet("picture");
            $tid = $this->fromGet("template");
            //$evflogger->log("filetype $filetype, event $eid/$sid, template $tid, picture $picture");

            if ((!empty($sid) || !empty($eid)) && in_array($filetype, array("participants","participantsxml"))) {
                $sideevent = $this->loadModel("SideEvent", $sid);
                $event = $this->loadModel("Event", $eid);
                if (empty($eid) && $sideevent->exists()) {
                    $event = $this->loadModel("Event", $sideevent->event_id);
                }
                if ($event->exists()) {
                    // check the policy to see if the user can retrieve a listing
                    $this->checkPolicy("registration", "list", array(
                        "model" => array(
                            "event" => $event->getKey()
                        ),
                        "filter" => array(
                            "event" => $event->getKey()
                        )
                    ));

                    if($filetype == "participantsxml") {
                        $em = new XMLManager();
                    }
                    else {
                        $em = new ExportManager();
                    }
                    $em->export($filetype, $sideevent, $event);
                }
            }
            else if (!empty($eid) && in_array($filetype, array("summary"))) {
                $event = new \EVFRanking\Models\Event($eid, true);
                if ($event->exists()) {
                    // check the policy to see if the user can retrieve a listing
                    $this->checkPolicy("accreditation", "view", array(
                        "model" => array(
                            "event" => $event->getKey()
                        )
                    ));
                    $em = new ExportManager();
                    $em->exportSummary($event, $this->fromGet("id"));
                }
            }
            else if (!empty($eid) && in_array($filetype, array("accreditation"))) {
                $event = new \EVFRanking\Models\Event($eid, true);
                if ($event->exists()) {
                    // check the policy to see if the user can retrieve a listing
                    $this->checkPolicy("accreditation", "view", array(
                        "model" => array(
                            "event" => $event->getKey()
                        )
                    ));

                    $em = new ExportManager();
                    $em->exportAccreditation($event, $this->fromGet("id"));
                }
            }
            else if (!empty($eid) && in_array($filetype, array("cashier", "picturestate"))) {
                $event = new \EVFRanking\Models\Event($eid, true);
                $country = new \EVFRanking\Models\Country($this->fromGet('country'), true);
                if ($event->exists()) {
                    // check the policy to see if the user can retrieve a listing
                    $this->checkPolicy("registration", "list", array(
                        "model" => array(
                            "event" => $event->getKey()
                        ),
                        "filter" => array(
                            "event" => $event->getKey()
                        )
                    ));

                    $em = new ExportManager();
                    $em->export($filetype, null, $event, $country);
                }
            }
            else if (!empty($tid) && !empty($picture)) {
                $event = $this->loadModel("Event", $sid);
                $template = $this->loadModel("AccreditationTemplate", $tid);

                if ($event->exists() && $template->exists() && $template->event_id == $event->getKey()) {
                    $this->checkPolicy("templates", "save", array(
                        "model" => array(
                            "event" => $event->getKey()
                        )
                    ));
                    
                    $pm = new PictureManager();
                    $pm->template($template, $picture);
                }
            }
            else if (is_numeric($picture)) {
                $event = new \EVFRanking\Models\Event($eid, true);
                $fencer = new \EVFRanking\Models\Fencer($picture, true);

                if ($fencer->exists()) {
                    $sideevent = new \EVFRanking\Models\SideEvent($sid, true);
                    if (!$sideevent->exists() && $event->exists()) {
                        $sides = $event->sides();
                        if (!empty($sides)) {
                            $sideevent = $this->loadModel("SideEvent", $sides[0]); // pick any sideevent
                        }
                    }

                    // check the policy to see if the user can retrieve a listing
                    $this->checkPolicy("picture", "view", array(
                        "model" => array(
                            "fencer" => $fencer->getKey(),
                            "event" => $sideevent->event_id,
                        )
                    ));
                    $evflogger->log("starting picture manager");
                    $pm = new PictureManager();
                    $pm->display($fencer);
                }
            }
        }
        die(403);
    }

    private function doFile($nonce)
    {
        $this->checkNonce($nonce);

        $upload = $this->fromPost("upload");
        $fencer = $this->fromPost("fencer");
        $event = $this->fromPost("event");
        $template = $this->fromPost("template");
        $type = $this->fromPost('type');
        $retval = array("error" => true);

        if (!empty($event) && $upload == "true") {
            $event = $this->loadModel("Event", $event);
            $fencer = $this->loadModel("Fencer", $fencer);
            $template = $this->loadModel("AccreditationTemplate", $template);

            if ($fencer->exists()) {
                // check the policy to see if the user can save a picture
                $this->checkPolicy("picture", "save", array(
                    "model" => array(
                        "event" => $event->getKey(),
                        "fencer" => $fencer
                    )
                ));

                $pm = new PictureManager();
                $retval = $pm->import($fencer);
                if (!isset($retval["error"])) {
                    $retval["success"] = true;
                    $retval["model"] = $fencer->export();
                }
            }
            else if ($event->exists() && $template->exists()) {
                // check the policy to see if the user can save a registration
                $this->checkPolicy("templates", "save", array(
                    "model" => array(
                        "event" => $event->getKey(),
                    )
                ));

                $pm = new PictureManager();
                $retval=$pm->importTemplate($template);
                if(!isset($retval["error"])) {
                    $retval["success"] = true;
                    //$retval["model"] = $template->export();
                }
            }
            else if (!empty($type) && $type == 'csv') {
                $manager = new CSVManager();
                $retval = $manager->import();
                if (!isset($retval["error"])) {
                    $retval["success"] = true;
                }
            }
        }
        return $retval;
    }

    protected function doPost($data) {
        $this->checkNonce($data['nonce']);

        $modeldata = isset($data['model']) ? $data['model'] : array();
        $offset = isset($modeldata['offset']) ? intval($modeldata['offset']) : 0;
        $pagesize = isset($modeldata['pagesize']) ? intval($modeldata['pagesize']) : 20;
        $filter = isset($modeldata['filter']) ? $modeldata['filter'] : "";
        $sort = isset($modeldata['sort']) ? $modeldata['sort'] : "";
        $special = isset($modeldata['special']) ? $modeldata['special'] : "";

        $path=$data['path'];
        if(empty($path)) {
            $path="index";
        }
        $path=explode('/',trim($path,'/'));
        if(!is_array($path) || sizeof($path) == 0) {
            $path=array("index");
        }

        $retval=array();
        switch($path[0]) {
            default:
            case "index":
                break;
            // full-fledged CRUD
            case "fencers":
            case "countries":
            case "results":
            case "events":
            case "roletypes":
            case "roles":
            case "registrars":
            case 'templates':
                    switch($path[0]) {
                    case 'fencers': $model = $this->loadModel("Fencer");break;
                    case 'countries': $model = $this->loadModel("Country"); break;
                    case 'results': $model = $this->loadModel("Result"); break;
                    case 'events': $model = $this->loadModel("Event"); break;
                    case 'roletypes': $model = $this->loadModel("RoleType"); break;
                    case 'roles': $model = $this->loadModel("Role"); break;
                    case 'registrars': $model = $this->loadModel("Registrar"); break;
                    case 'templates': $model = $this->loadModel("AccreditationTemplate"); break;
                }
                
                if(isset($path[1]) && $path[1] == "save") {
                    $this->checkPolicy($path[0],"save", array("filter" => $filter, "model" => $modeldata));
                    $retval=array_merge($retval, $this->save($model,$modeldata));
                }
                else if(isset($path[1]) && $path[1] == "delete") {
                    $this->checkPolicy($path[0],"delete", array("filter" => $filter, "model" => $modeldata));
                    $retval=array_merge($retval, $this->delete($model,$modeldata));
                }
                else if(isset($path[1]) && $path[1] == "view") {
                    // Get a specific event
                    $this->checkPolicy($path[0],"view", array("filter" => $filter, "model" => $modeldata));
                    $item = $this->createModel($model, $modeldata);
                    $retval=array_merge($retval, array("item"=> ($item === null) ? $item : $item->export() ));
                }
                else if($path[0] == 'events' && isset($path[1]) && $path[1] == "competitions") {
                    // list all competitions of events (event special action)
                    $this->checkPolicy("competitions","list", array("filter" => $filter, "model" => $modeldata));
                    $id=isset($modeldata["id"]) ? intval($modeldata["id"]) : -1;
                    $retval=array_merge($retval, $this->listResults($model, $model->competitions($id), null, TRUE));
                }
                else if($path[0] == 'events' && isset($path[1]) && $path[1] == "sides") {
                    // list all side events of events (event special action)
                    $this->checkPolicy("sides","list", array("filter" => $filter, "model" => $modeldata));
                    $id=isset($modeldata["id"]) ? intval($modeldata["id"]) : -1;
                    $retval=array_merge($retval, $this->listResults($model, $model->sides($id), null, TRUE));
                }
                else if($path[0] == 'events' && isset($path[1]) && $path[1] == "roles") {
                    // list all side events of events (event special action)
                    $this->checkPolicy("eventroles","list", array("filter" => $filter, "model" => $modeldata));
                    $id=isset($modeldata["id"]) ? intval($modeldata["id"]) : -1;
                    $retval=array_merge($retval, $this->listResults($model, $model->roles($id), null, TRUE));
                }
                else if($path[0] == 'events' && isset($path[1]) && $path[1] == "statistics") {
                    // list a bunch of statistics for this event, useful for accreditors and organisers
                    $this->checkPolicy("statistics","view", array("filter" => $filter, "model" => $modeldata));
                    $id=isset($modeldata["id"]) ? intval($modeldata["id"]) : -1;
                    $retval=array_merge($retval, $model->statistics($id));
                }
                else if($path[0] == 'events' && isset($path[1]) && $path[1] == "ranking") {
                    // list a bunch of statistics for this event, useful for accreditors and organisers
                    $this->checkPolicy("events", "save", array("filter" => $filter, "model" => $modeldata));
                    $id = isset($modeldata["id"]) ? intval($modeldata["id"]) : -1;
                    $retval = array_merge($retval, $model->setRanking($id, isset($modeldata["in_ranking"]) ? $modeldata['in_ranking'] : 'N'));
                }
                else if($path[0] == 'results' && isset($path[1]) && $path[1] == "importcheck") {
                    $this->checkPolicy("results","misc", array("filter" => $filter, "model" => $modeldata));
                    $ranks=isset($modeldata["ranking"]) ? $modeldata["ranking"] : array();
                    if(!is_array($ranks)) $ranks=array();
                    $comp = isset($modeldata["competition"]) ? intval($modeldata["competition"]) : -1;
                    $retval=array_merge($retval, $model->doImportCheck($ranks,$comp));
                }
                else if ($path[0] == 'fencers' && isset($path[1]) && $path[1] == "importcheck") {
                    $this->checkPolicy("fencers", "save", array("filter" => $filter, "model" => $modeldata));
                    $fencers = isset($modeldata["fencers"]) ? $modeldata["fencers"] : array();
                    $countryId = isset($modeldata["country"]) ? $modeldata["country"] : array();
                    if (!is_array($fencers)) $fencers = array();
                    $retval = array_merge($retval, $model->doImportCheck($fencers, $countryId));
                }
                else if($path[0] == 'results' && isset($path[1]) && $path[1] == "import") {
                    $this->checkPolicy("results","misc", array("filter" => $filter, "model" => $modeldata));
                    $imp = isset($modeldata["import"]) ? $modeldata["import"] : array();
                    $retval=array_merge($retval, $model->doImport($imp));
                }
                else if($path[0] == 'results' && isset($path[1]) && $path[1] == "recalculate") {
                    $this->checkPolicy("results","misc", array("filter" => $filter, "model" => $modeldata));
                    $comp = isset($modeldata["competition_id"]) ? intval($modeldata["competition_id"]) : -1;
                    $retval=array_merge($retval, $model->recalculate($comp));
                }
                else if($path[0] == 'results' && isset($path[1]) && $path[1] == "clear") {
                    $this->checkPolicy("results","misc", array("filter" => $filter, "model" => $modeldata));
                    $comp = isset($modeldata["competition_id"]) ? intval($modeldata["competition_id"]) : -1;
                    $retval=array_merge($retval, $model->clear($comp));
                }
                else if($path[0] == 'templates' && isset($path[1]) && $path[1] == "delpic") {
                    $this->checkPolicy("templates","delete", array("filter" => $filter, "model" => $modeldata));
                    $fid=isset($modeldata["file_id"]) ? $modeldata["file_id"] : '';
                    $tid=isset($modeldata["template_id"]) ? intval($modeldata["template_id"]) : -1;
                    $model->deletePicture($fid, $tid);
                }
                else if($path[0] == 'templates' && isset($path[1]) && $path[1] == "example") {
                    $this->checkPolicy("templates","save", array("filter" => $filter, "model" => $modeldata));
                    $model->createExample($modeldata);
                }
                else if($path[0] == 'templates' && isset($path[1]) && $path[1] == "default") {
                    $this->checkPolicy("templates","misc", array("filter" => $filter, "model" => $modeldata));
                    $model->setAsDefault($modeldata);
                }
                else if($path[0] == 'templates' && isset($path[1]) && $path[1] == "defaults") {
                    $this->checkPolicy("templates","misc", array("filter" => $filter, "model" => $modeldata));
                    $retval=$model->listDefaults();
                }
                else if($path[0] == 'templates' && isset($path[1]) && $path[1] == "loaddefaults") {
                    $this->checkPolicy("templates","save", array("filter" => $filter, "model" => $modeldata));
                    $model->addDefaults($modeldata);
                    $retval= array_merge($retval, $this->listAll($model,$offset,$pagesize,$filter,$sort,$special));
                }
                else if($path[0] == 'fencers' && isset($path[1]) && $path[1] == "presavecheck") {
                    $this->checkPolicy("fencers","save", array("filter" => $filter, "model" => $modeldata));
                    $retval= array_merge($retval, $model->preSaveCheck($modeldata));
                }
                else if($path[0] == 'fencers' && isset($path[1]) && $path[1] == "merge") {
                    $this->checkPolicy("fencers","save", array("filter" => $filter, "model" => $modeldata));
                    $retval= array_merge($retval, $model->merge($modeldata));
                }
                else {
                    $this->checkPolicy($path[0],"list", array("filter" => $filter, "model" => $modeldata));
                    $retval=array_merge($retval, $this->listAll($model,$offset,$pagesize,$filter,$sort,$special));
                }
                break;
            // LIST and UPDATE
            case "migrations":
                switch($path[0]) {
                    case 'migrations': $model = $this->loadModel("Migration"); break;
                }
                
                if(isset($path[1]) && $path[1] == "save") {
                    $this->checkPolicy($path[0],"save", array("filter" => $filter, "model" => $modeldata));
                    $retval=array_merge($retval, $this->save($model,$modeldata));
                }
                else {
                    $this->checkPolicy($path[0],"list", array("filter" => $filter, "model" => $modeldata));
                    $retval=array_merge($retval, $this->listAll($model,$offset,$pagesize,$filter,$sort,$special));
                }
                break;
            // for these we only support a listing functionality
            case "weapons":
            case "categories":
            case "types":
            case "users":
            case "posts":
            //case "audit":
                switch($path[0]) {
                    case 'weapons': $model = $this->loadModel("Weapon"); break;
                    case 'categories': $model = $this->loadModel("Category"); break;
                    case 'types': $model = $this->loadModel("EventType"); break; 
                    case 'users': $model = $this->loadModel("User"); break;
                    case 'posts': $model = $this->loadModel("Posts"); break;
                    //case 'audit': $model = $this->loadModel("Audit"); break;
                }
                $this->checkPolicy($path[0],"list", array("filter" => $filter, "model" => $modeldata));
                $retval=array_merge($retval, $this->listAll($model,0,null,'','i',$special));
                break;
            // special models
            case 'ranking':
                if(isset($path[1]) && $path[1] == "reset") {
                    $this->checkPolicy($path[0],"misc", array("filter" => $filter, "model" => $modeldata));
                    $model = $this->loadModel("Ranking");
                    $total = $model->calculateRankings();
                    $retval=array(
                        "success" => TRUE,
                        "total" => $total
                    );
                }
                else if(isset($path[1]) && $path[1] == "list") {
                    $this->checkPolicy($path[0],"list", array("filter" => $filter, "model" => $modeldata));
                    $model = $this->loadModel("Ranking");
                    $cid = intval(isset($modeldata['category_id']) ? $modeldata['category_id'] : "-1");
                    $catmodel = $this->loadModel("Category",$cid);
                    $wid = intval(isset($modeldata['weapon_id']) ? $modeldata['weapon_id'] : "-1");
                    if($catmodel->exists() && $wid > 0) {
                        $results = $model->listResults($wid,$catmodel);
                        $retval=array(
                            "success" => TRUE,
                            "results" => $results
                        );
                    }
                    else {
                        $retval=array("error"=>"No category or weapon selected");
                    }
                }
                else if(isset($path[1]) && $path[1] == "detail") {
                    $this->checkPolicy($path[0],"view", array("filter" => $filter, "model" => $modeldata));
                    $model = $this->loadModel("Ranking");
                    $cid = intval(isset($modeldata['category_id']) ? $modeldata['category_id'] : "-1");
                    $wid = intval(isset($modeldata['weapon_id']) ? $modeldata['weapon_id'] : "-1");
                    $fid = intval(isset($modeldata['id']) ? $modeldata['id'] : "-1");
                    if($cid > 0 && $wid > 0 && $fid>0) {
                        $retval = $model->listDetail($wid,$cid,$fid);
                    }
                    else {
                        $retval=array("error"=>"No category or weapon selected");
                    }
                }
                else if(isset($path[1]) && $path[1] == "events") {
                    $this->checkPolicy($path[0],"list", array("filter" => $filter, "model" => $modeldata));
                    $model = $this->loadModel("Event");
                    $retval = $this->listResults($model, $model->listRankedEvents());
                }
                else {
                    $retval=array("error"=>"invalid action");
                }
                break;
            case "registration":
                $model = $this->loadModel("Registration");
                if (isset($path[1]) && $path[1] == "save") {
                    $this->checkPolicy($path[0], "save", array("filter" => $filter, "model" => $modeldata));
                    $retval = array_merge($retval, $this->save($model, $modeldata));
                } 
                else if (isset($path[1]) && $path[1] == "delete") {
                    $this->checkPolicy($path[0], "delete", array("filter" => $filter, "model" => $modeldata));
                    $retval = array_merge($retval, $this->delete($model, $modeldata));
                }
                else if (isset($path[1]) && $path[1] == "overview") {
                    $this->checkPolicy($path[0], "list", array("filter" => $filter, "model" => $modeldata));
                    $ev=isset($modeldata["event"]) ? intval($modeldata["event"]):-1;
                    $retval = array_merge($retval, $model->overview($ev));
                }
                else {
                    // we can list if we can administer the event belonging to the passed side-event
                    $this->checkPolicy($path[0], "list", array("filter"=>$filter, "model"=>$modeldata));
                    $retval = array_merge($retval, $this->listAll($model, $offset, $pagesize, $filter, $sort, $special));
                }                   
                break;
            case 'accreditation':
                $model = $this->loadModel("Accreditation");
                $this->checkPolicy($path[0], "view", array("filter" => $filter, "model" => $modeldata));
                $subpath=sizeof($path)>1 ? $path[1]: "";
                switch($subpath) {
                case "overview":
                    // overview displayed for accreditors
                    $ev=isset($modeldata["event"]) ? intval($modeldata["event"]):-1;
                    $retval = array_merge($retval, $model->overview($ev));
                    break;
                case "regenerate":
                    // regenerate all accreditations, from the accreditors tab
                    $ev=isset($modeldata["event"]) ? intval($modeldata["event"]):-1;
                    $retval = array_merge($retval, $model->regenerate($ev));
                    break;
                case "check":
                    // check validity of summary documents, from the accreditors tab
                    $ev=isset($modeldata["event"]) ? intval($modeldata["event"]):-1;
                    $retval = array_merge($retval, $model->checkSummaryDocuments($ev));
                    break;
                case "fencer":
                    // get all accreditations for this fencer, from fencerselect and the accreditation page
                    $ev=isset($modeldata["event"]) ? intval($modeldata["event"]):-1;
                    $fid=isset($modeldata["fencer"]) ? intval($modeldata["fencer"]):-1;
                    $retval = array_merge($retval, $model->findAccreditations($ev,$fid));
                    break;
                case "generate":
                    // generate a summary document, from the accreditors tab
                    $ev=isset($modeldata["event"]) ? intval($modeldata["event"]):-1;
                    $tid=isset($modeldata["type"]) ? $modeldata["type"]:-1;
                    $tyid=isset($modeldata["type_id"]) ? intval($modeldata["type_id"]):-1;
                    $retval = array_merge($retval, $model->generate($ev,$tid,$tyid));
                    break;
                case "generateone":
                    // generate accreditations for a specific fencer, from the fencerselect dialog
                    $ev=isset($modeldata["event"]) ? intval($modeldata["event"]):-1;
                    $fid=isset($modeldata["fencer"]) ? intval($modeldata["fencer"]):-1;
                    $retval = array_merge($retval, $model->generateForFencer($ev,$fid));
                    break;
                default:
                    $retval=array("error"=>"invalid action");
                }
                break;

        }
        return $retval;
    }

    protected function save($model, $data) {
        $retval=array();
        $event=null;
        if(isset($data["event"])) {
            $event = $this->loadModel("Event",$data["event"]);
        }
        if(isset($data["sideevent"])) {
            $se=$this->loadModel("SideEvent",$data["sideevent"]);
            if($se->exists()) {
                $event = $this->loadModel("Event",$se->event_id);
            }
        }
        $caps="none"; // no capabilities by default, unless we have an event to base it on
        if(!empty($event) && $event->exists()) {
            $caps = $event->eventCaps();
        }
        else {
            if(current_user_can( 'manage_ranking' ) || current_user_can( 'manage_registration' )) {
                $caps="system";
            }
        }
        $data = $model->filterData($data, $caps);

        // make sure to retrieve the current state from the database as value for all
        // fields before validation (before we overwrite the values with whatever is posted)
        // This allows us to pass only a few values through the API, but still refer to
        // all fields in our business rules inside the model        
        if(isset($data["id"])) {
            $newmodel = $model->get($data["id"]);
            if(!empty($newmodel) && $newmodel->exists()) {
                $model=$newmodel;
            }
        }

        if(!$model->saveFromObject($data)) {
            error_log('ERROR: save failed');
            $retval["error"]=true;
            $retval["messages"]=$model->errors;
        }
        else {
            $retval["id"] = $model->getKey();
            $retval = array_merge($retval,array("model"=>$model->export()));
        }
        return $retval;
    }

    protected function delete($model, $data) {
        $retval=array();
        $model=$model->get($data['id']);
        if(!empty($model) && $model->exists()) {
            if(!$model->delete($data['id'])) {
                error_log('ERROR: delete failed');
                $retval["error"]=true;
                $retval["messages"]=array("Internal database error");
                if(isset($model->errors) && is_array($model->errors)) {
                    $retval["messages"]=$model->errors;
                }
            }
        }

        if(!isset($retval["error"])) {
            $retval["id"] = $model->getKey();
        }

        return $retval;
    }

    protected function listAll($model,$offset,$pagesize,$filter,$sort,$special) {
        return $this->listResults($model, $model->selectAll($offset,$pagesize,$filter,$sort,$special), $model->count($filter,$special));
    }

    protected function createModel($model, $data)
    {
        $viewModel = $model->get($data['id']);
        if (!empty($data)) {
            $viewModel->postProcessing($data);
        }
        return $viewModel;
    }

    protected function loadModel($modelname,$arg=null) {
        $cname="\\EVFRanking\\Models\\$modelname";
        return new $cname($arg,true);
    }

    protected function listResults($model, $lst,$total=null, $noexport=FALSE) {
        if ($total === null) {
            $total = sizeof($lst);
        }

        $retval=array();
        $retval["list"]=array();

        if (!empty($lst) && is_array($lst)) {
            array_walk($lst,function($v,$k) use (&$retval,$model,$noexport) {
                $retval["list"][]=$noexport ? $v : $model->export($v);
            });
            $retval["total"] = $total;
        }
        else {
            global $wpdb;
            $str = mysqli_error( $wpdb->dbh );
            if (strlen($str)) {
                error_log('DB ERROR:' .$str);
            }
            $retval['list']=array();
            $retval['total']=0;
        }
        return $retval;
    }

    protected function checkPolicy($model,$action,$obj=null) {
        $policy = new \EVFRanking\Lib\Policy();
        if(!$policy->check($model,$action,$obj)) {
            die(403);
        }
    }
}