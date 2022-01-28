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
    public function createNonceText() {
        $user = wp_get_current_user();        
        if(!empty($user)) {
            return "evfranking".$user->ID;
        }
        return "evfranking";
    }

    public function resolve() {
        $json = file_get_contents('php://input');
        $data = json_decode($json,true);

        if (!empty($_FILES)) {
            $retval= $this->doFile($_POST["nonce"]);
        }
        else if(empty($data) || !isset($data['nonce']) || !isset($data['path'])) {
            if(empty($data)) {
                // see if we have the proper GET requests for a download
                if(!empty($this->fromGet("action")) && !empty($this->fromGet("nonce"))) {
                    $retval=$this->doGet($this->fromGet("action"),$this->fromGet("nonce"));
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

    private function checkNonce($nonce) {
        $result = wp_verify_nonce($nonce, $this->createNonceText());
        if (!($result === 1 || $result === 2)) {
            error_log('die because nonce does not match');
            die(403);
        }
    }

    private function fromGet($var, $def=null) {
        if(isset($_GET[$var])) return $_GET[$var];
        return $def;
    }
    private function fromPost($var, $def=null) {
        if(isset($_POST[$var])) return $_POST[$var];
        return $def;
    }

    private function doGet($action, $nonce) {
        $this->checkNonce($nonce);
        if($action == "evfranking") {
            $filetype = $this->fromGet("download");
            $sid = $this->fromGet("event");
            $eid = $this->fromGet("mainevent");
            $picture = $this->fromGet("picture");
            $tid = $this->fromGet("template");
            //error_log("filetype $filetype, sid $sid, eid $eid, picture $picture, tid $tid");

            if ((!empty($sid) || !empty($eid)) && in_array($filetype, array("participants"))) {
                $sideevent = new \EVFRanking\Models\SideEvent($sid);
                $event = new \EVFRanking\Models\Event($eid);
                if(empty($eid) && $sideevent->exists()) {
                    $event=new \EVFRanking\Models\Event($sideevent->event_id);
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

                    $em = new ExportManager();
                    $em->export($filetype,$sideevent,$event);
                }
            }
            else if(!empty($eid) && in_array($filetype,array("summary"))) {
                $event = new \EVFRanking\Models\Event($eid);                
                if ($event->exists()) {
                    // check the policy to see if the user can retrieve a listing
                    $this->checkPolicy("accreditation", "view", array(
                        "model" => array(
                            "event" => $event->getKey()
                        )
                    ));

                    $em = new ExportManager();
                    $em->exportSummary($event,$this->fromGet("type"),$this->fromGet("typeid"));
                }
            }
            else if(!empty($eid) && in_array($filetype,array("accreditation"))) {
                $event = new \EVFRanking\Models\Event($eid);                
                if ($event->exists()) {
                    // check the policy to see if the user can retrieve a listing
                    $this->checkPolicy("accreditation", "view", array(
                        "model" => array(
                            "event" => $event->getKey()
                        )
                    ));

                    $em = new ExportManager();
                    $em->exportAccreditation($event,$this->fromGet("id"));
                }
            }
            else if (!empty($sid) && in_array($filetype, array("cashier"))) {
                $event = new \EVFRanking\Models\Event($sid);
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
                    $em->export($filetype, null, $event);
                }
            }
            else if (!empty($tid) && !empty($picture)) {
                $event = new \EVFRanking\Models\Event($sid);
                $template = new \EVFRanking\Models\AccreditationTemplate($tid);

                if ($event->exists() && $template->exists() && $template->event_id == $event->getKey()) {
                    $this->checkPolicy("templates", "save", array(
                        "model" => array(
                            "event" => $event->getKey()
                        )
                    ));
                    
                    $pm = new PictureManager();
                    $pm->template($template,$picture);
                }
            }
            else if (!empty($sid) && is_numeric($picture)) {
                $event = new \EVFRanking\Models\Event($sid);
                $fencer = new \EVFRanking\Models\Fencer($picture);

                if ($event->exists() && $fencer->exists()) {
                    $sides = $event->sides();
                    if (!empty($sides)) {
                        $sideevent = new \EVFRanking\Models\SideEvent($sides[0]); // pick any sideevent

                        // check the policy to see if the user can retrieve a listing
                        $this->checkPolicy("registration", "list", array(
                            "model" => array(
                                "sideevent" => $sideevent->getKey(),
                                "event" => $sideevent->event_id,
                            ),
                            "filter" => array(
                                "event" => $sideevent->event_id,
                                "country" => $fencer->fencer_country
                            )
                        ));

                        $pm = new PictureManager();
                        $pm->display($fencer);
                    }
                }
            }
        }
        die(403);
    }

    private function doFile($nonce) {
        $this->checkNonce($nonce);

        $upload = $this->fromPost("upload");
        $fencer = $this->fromPost("fencer");
        $event = $this->fromPost("event");
        $template = $this->fromPost("template");
        $retval=array("error"=>true);

        if (!empty($event) && $upload == "true")  {
            $event = new \EVFRanking\Models\Event($event, true);
            $fencer = new \EVFRanking\Models\Fencer($fencer, true);
            $template = new \EVFRanking\Models\AccreditationTemplate($template,true);

            if ($event->exists() && $fencer->exists()) {
                $sides = $event->sides();
                if(!empty($sides)) {
                    $sideevent = new \EVFRanking\Models\SideEvent($sides[0]); // pick any sideevent
                    // check the policy to see if the user can save a registration
                    $this->checkPolicy("registration", "save", array(
                        "model" => array(
                            "sideevent" => $sideevent->getKey(),
                            "event" => $sideevent->event_id,
                            "fencer" => $fencer
                        )
                    ));

                    $pm = new PictureManager();
                    $retval=$pm->import($fencer);
                    if (!isset($retval["error"])) {
                        $retval["success"] = true;
                        $retval["model"]=$fencer->export();
                    }
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
        }
        return $retval;
    }

    private function doPost($data) {
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
                    case 'fencers': $model = new \EVFRanking\Models\Fencer();break;
                    case 'countries': $model = new \EVFRanking\Models\Country(); break;
                    case 'results': $model = new \EVFRanking\Models\Result(); break;
                    case 'events': $model = new \EVFRanking\Models\Event(); break;
                    case 'roletypes': $model = new \EVFRanking\Models\RoleType(); break;
                    case 'roles': $model = new \EVFRanking\Models\Role(); break;
                    case 'registrars': $model = new \EVFRanking\Models\Registrar(); break;
                    case 'templates': $model = new \EVFRanking\Models\AccreditationTemplate(); break;
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
                    $item = $model->get($modeldata['id']);
                    $retval=array_merge($retval, array("item"=> ($item === null) ? $item : $item->export() ));
                }
                else if($path[0] == 'events' && isset($path[1]) && $path[1] == "competitions") {
                    // list all competitions of events (event special action)
                    $this->checkPolicy("competitions","list", array("filter" => $filter, "model" => $modeldata));
                    $retval=array_merge($retval, $this->listResults($model, $model->competitions($modeldata['id']), null, TRUE));
                }
                else if($path[0] == 'events' && isset($path[1]) && $path[1] == "sides") {
                    // list all side events of events (event special action)
                    $this->checkPolicy("sides","list", array("filter" => $filter, "model" => $modeldata));
                    $retval=array_merge($retval, $this->listResults($model, $model->sides($modeldata['id']), null, TRUE));
                }
                else if($path[0] == 'events' && isset($path[1]) && $path[1] == "roles") {
                    // list all side events of events (event special action)
                    $this->checkPolicy("eventroles","list", array("filter" => $filter, "model" => $modeldata));
                    $retval=array_merge($retval, $this->listResults($model, $model->roles($modeldata['id']), null, TRUE));
                }
                else if($path[0] == 'results' && isset($path[1]) && $path[1] == "importcheck") {
                    $this->checkPolicy("results","misc", array("filter" => $filter, "model" => $modeldata));
                    $retval=array_merge($retval, $model->doImportCheck($modeldata['ranking'], $modeldata["competition"]));
                }
                else if($path[0] == 'results' && isset($path[1]) && $path[1] == "import") {
                    $this->checkPolicy("results","misc", array("filter" => $filter, "model" => $modeldata));
                    $retval=array_merge($retval, $model->doImport($modeldata['import']));
                }
                else if($path[0] == 'results' && isset($path[1]) && $path[1] == "recalculate") {
                    $this->checkPolicy("results","misc", array("filter" => $filter, "model" => $modeldata));
                    $retval=array_merge($retval, $model->recalculate($modeldata['competition_id']));
                }
                else if($path[0] == 'results' && isset($path[1]) && $path[1] == "clear") {
                    $this->checkPolicy("results","misc", array("filter" => $filter, "model" => $modeldata));
                    $retval=array_merge($retval, $model->clear($modeldata['competition_id']));
                }
                else if($path[0] == 'templates' && isset($path[1]) && $path[1] == "delpic") {
                    $this->checkPolicy("templates","delete", array("filter" => $filter, "model" => $modeldata));
                    $model->deletePicture($modeldata['file_id'], $modeldata["template_id"]);
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
                    case 'migrations': $model = new \EVFRanking\Models\Migration(); break;
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
                    case 'weapons': $model = new \EVFRanking\Models\Weapon(); break;
                    case 'categories': $model = new \EVFRanking\Models\Category(); break;
                    case 'types': $model = new \EVFRanking\Models\EventType(); break; 
                    case 'users': $model = new \EVFRanking\Models\User(); break;
                    case 'posts': $model = new \EVFRanking\Models\Posts(); break;
                    //case 'audit': $model = new \EVFRanking\Models\Audit(); break;
                }
                $this->checkPolicy($path[0],"list", array("filter" => $filter, "model" => $modeldata));
                $retval=array_merge($retval, $this->listAll($model,0,null,'','i',$special));
                break;
            // special models
            case 'ranking':
                if(isset($path[1]) && $path[1] == "reset") {
                    $this->checkPolicy($path[0],"misc", array("filter" => $filter, "model" => $modeldata));
                    $model = new \EVFRanking\Models\Ranking();
                    $total = $model->calculateRankings();
                    $retval=array(
                        "success" => TRUE,
                        "total" => $total
                    );
                }
                else if(isset($path[1]) && $path[1] == "list") {
                    $this->checkPolicy($path[0],"list", array("filter" => $filter, "model" => $modeldata));
                    $model = new \EVFRanking\Models\Ranking();
                    $cid = intval(isset($modeldata['category_id']) ? $modeldata['category_id'] : "-1");
                    $catmodel = new \EVFRanking\Models\Category($cid);
                    $catmodel->load();
                    $wid = intval(isset($modeldata['weapon_id']) ? $modeldata['weapon_id'] : "-1");
                    if($cid > 0 && $wid > 0) {
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
                    $model = new \EVFRanking\Models\Ranking();
                    $cid = intval(isset($modeldata['category_id']) ? $modeldata['category_id'] : "-1");
                    $wid = intval(isset($modeldata['weapon_id']) ? $modeldata['weapon_id'] : "-1");
                    $fid = intval(isset($modeldata['id']) ? $modeldata['id'] : "-1");
                    if($cid > 0 && $wid > 0 && $fid>0) {
                        error_log("listing detail for $fid");
                        $retval = $model->listDetail($wid,$cid,$fid);
                    }
                    else {
                        $retval=array("error"=>"No category or weapon selected");
                    }
                }
                break;
            case "registration":
                $model = new \EVFRanking\Models\Registration();
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
                    $retval = array_merge($retval, $model->overview($modeldata["event"]));
                }
                else {
                    // we can list if we can administer the event belonging to the passed side-event
                    $this->checkPolicy($path[0], "list", array("filter"=>$filter, "model"=>$modeldata));
                    $retval = array_merge($retval, $this->listAll($model, $offset, $pagesize, $filter, $sort, $special));
                }                   
                break;
            case 'accreditation':
                $model = new \EVFRanking\Models\Accreditation();
                $this->checkPolicy($path[0], "view", array("filter" => $filter, "model" => $modeldata));
                switch($path[1]) {
                case "overview":
                    // overview displayed for accreditors
                    $retval = array_merge($retval, $model->overview($modeldata["event"]));
                    break;
                case "regenerate":
                    // regenerate all accreditations, from the accreditors tab
                    $retval = array_merge($retval, $model->regenerate($modeldata["event"]));
                    break;
                case "check":
                    // check validity of summary documents, from the accreditors tab
                    $retval = array_merge($retval, $model->checkSummaryDocuments($modeldata["event"]));
                    break;
                case "fencer":
                    // get all accreditations for this fencer, from fencerselect and the accreditation page
                    $retval = array_merge($retval, $model->findAccreditations($modeldata["event"], $modeldata["fencer"]));
                    break;
                case "generate":
                    // generate a summary document, from the accreditors tab
                    $retval = array_merge($retval, $model->generate($modeldata["event"],$modeldata["type"],$modeldata["type_id"]));
                    break;
                case "generateone":
                    // generate accreditations for a specific fencer, from the fencerselect dialog
                    $retval = array_merge($retval, $model->generateForFencer($modeldata["event"],$modeldata["fencer"]));
                    break;
                default:
                    $retval=array("error"=>"invalid action");
                }
                break;

        }
        return $retval;
    }

    private function save($model, $data) {
        $retval=array();
        $event=null;
        if(isset($data["event"])) {
            $event = new \EVFRanking\Models\Event($data["event"], true);
        }
        if(isset($data["sideevent"])) {
            $se=new \EVFRanking\Models\SideEvent($data["sideevent"], true);
            if($se->exists()) {
                $event = new \EVFRanking\Models\Event($se->event_id,true);
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
        error_log("filtering data");
        $data = $model->filterData($data, $caps);
        error_log("filtered data is ".json_encode($data));

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
            error_log('save failed');
            $retval["error"]=true;
            $retval["messages"]=$model->errors;
        }
        else {
            $retval["id"] = $model->getKey();
            $retval = array_merge($retval,array("model"=>$model->export()));
        }
        return $retval;
    }

    private function delete($model, $data) {
        $retval=array();
        $model=$model->get($data['id']);
        if(!empty($model) && $model->exists()) {
            if(!$model->delete($data['id'])) {
                error_log('delete failed');
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

    private function listAll($model,$offset,$pagesize,$filter,$sort,$special) {
        return $this->listResults($model, $model->selectAll($offset,$pagesize,$filter,$sort,$special), $model->count($filter,$special));
    }

    private function listResults($model, $lst,$total=null, $noexport=FALSE) {
        if($total === null) {
            $total = sizeof($lst);
        }

        $retval=array();
        $retval["list"]=array();

        if(!empty($lst) && is_array($lst)) {
            array_walk($lst,function($v,$k) use (&$retval,$model,$noexport) {
                $retval["list"][]=$noexport ? $v : $model->export($v);
            });
            $retval["total"] = $total;
        }
        else {
            error_log('empty result, checking errors');
            global $wpdb;
            $str = mysqli_error( $wpdb->dbh );
            error_log('DB ERROR:' .$str);
            $retval['list']=array();
            $retval['total']=0;
        }
        return $retval;
    }

    private function checkPolicy($model,$action,$obj=null) {
        $policy = new \EVFRanking\Lib\Policy();
        if(!$policy->check($model,$action,$obj)) {
            die(403);
        }
    }
}