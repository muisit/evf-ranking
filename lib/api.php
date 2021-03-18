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


namespace EVFRanking;

require_once(__DIR__.'/baselib.php');
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
        error_log('resolving call: '.json_encode($data));

        if (!empty($_FILES)) {
            $this->doFile($_POST["nonce"]);
        }
        if(empty($data) || !isset($data['nonce']) || !isset($data['path'])) {
            if(empty($data)) {
                // see if we have the proper GET requests for a download
                if(!empty($this->fromGet("action")) && !empty($this->fromGet("nonce"))) {
                    return $this->doGet($this->fromGet("action"),$this->fromGet("nonce"));
                }
            }

            error_log('die because no path nor nonce');
            die(403);
        }
        return $this->doPost($data);
    }

    private function checkNonce($nonce) {
        error_log('checking nonce ' . $nonce . ' using ' . $this->createNonceText());
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
            $eid = $this->fromGet("event");
            $picture = $this->fromGet("picture");

            if (!empty($eid) && in_array($filetype, array("accreditations", "participants"))) {
                $model = $this->loadModel("SideEvent");
                $sideevent = $model->get($eid);

                if (!empty($sideevent)) {
                    error_log("checking policy");
                    // check the policy to see if the user can retrieve a listing
                    $this->checkPolicy("registration", "list", array(
                        "model" => array(
                            "sideevent" => $sideevent->getKey(),
                            "event" => $sideevent->event_id
                        ),
                        "filter" => array(
                            "event" => $sideevent->event_id
                        )
                    ));

                    require_once(__DIR__ . "/exportmanager.php");
                    $em = new ExportManager();
                    $em->export($filetype,$sideevent);
                }
            }
            else if (!empty($eid) && is_numeric($picture)) {
                error_log("displaying picture for ".$eid." and " .$picture);
                $event = $this->loadModel("Event");
                $event = $event->get($eid);
                $fencer = $this->loadModel("Fencer");
                $fencer = $fencer->get($picture);

                $this->loadModel("SideEvent");

                if (!empty($event) && !empty($fencer)) {
                    error_log("getting side events");
                    $sides = $event->sides();
                    if (!empty($sides)) {
                        $sideevent = new SideEvent($sides[0]); // pick any sideevent

                        error_log("checking policy");
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

                        require_once(__DIR__ . "/picturemanager.php");
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

        if (!empty($event) && $upload == "true")  {
            $model = $this->loadModel("Event");
            $event = $model->get($event);
            $this->loadModel("SideEvent");

            if (!empty($event)) {
                $sides = $event->sides();
                if(!empty($sides)) {
                    $sideevent = new SideEvent($sides[0]); // pick any sideevent
                    error_log("checking policy");
                    // check the policy to see if the user can save a registration
                    $this->checkPolicy("registration", "save", array(
                        "model" => array(
                            "sideevent" => $sideevent->getKey(),
                            "event" => $sideevent->event_id,
                            "fencer" => $fencer
                        )
                    ));

                    require_once(__DIR__ . "/picturemanager.php");
                    $pm = new PictureManager();
                    $pm->import($fencer);
                }
            }
        }
        return false;
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
        //error_log('path is '.json_encode($path));

        //error_log('data is '.json_encode($data));
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
                    switch($path[0]) {
                    case 'fencers': $model = $this->loadModel("Fencer"); break;
                    case 'countries': $model = $this->loadModel("Country"); break;
                    case 'results': $model = $this->loadModel("Result"); break;
                    case 'events': $model = $this->loadModel("Event"); break;
                    case 'roletypes': $model = $this->loadModel('RoleType'); break;
                    case 'roles': $model = $this->loadModel('Role'); break;
                    case 'registrars': $model = $this->loadModel('Registrar'); break;
                }
                
                if(isset($path[1]) && $path[1] == "save") {
                    $this->checkPolicy($path[0],"save");
                    $retval=array_merge($retval, $this->save($model,$modeldata));
                }
                else if(isset($path[1]) && $path[1] == "delete") {
                    $this->checkPolicy($path[0],"delete");
                    $retval=array_merge($retval, $this->delete($model,$modeldata));
                }
                else if(isset($path[1]) && $path[1] == "view") {
                    // Get a specific event
                    $this->checkPolicy($path[0],"view");
                    $item = $model->get($modeldata['id']);
                    $retval=array_merge($retval, array("item"=> ($item === null) ? $item : $item->export() ));
                }
                else if($path[0] == 'events' && isset($path[1]) && $path[1] == "competitions") {
                    // list all competitions of events (event special action)
                    $this->checkPolicy("competitions","list");
                    $retval=array_merge($retval, $this->listResults($model, $model->competitions($modeldata['id']), null, TRUE));
                }
                else if($path[0] == 'events' && isset($path[1]) && $path[1] == "sides") {
                    // list all side events of events (event special action)
                    $this->checkPolicy("sides","list");
                    $retval=array_merge($retval, $this->listResults($model, $model->sides($modeldata['id']), null, TRUE));
                }
                else if($path[0] == 'events' && isset($path[1]) && $path[1] == "roles") {
                    // list all side events of events (event special action)
                    $this->checkPolicy("eventroles","list");
                    $retval=array_merge($retval, $this->listResults($model, $model->roles($modeldata['id']), null, TRUE));
                }
                else if($path[0] == 'results' && isset($path[1]) && $path[1] == "importcheck") {
                    $this->checkPolicy("results","misc");
                    $retval=array_merge($retval, $model->doImportCheck($modeldata['ranking']));
                }
                else if($path[0] == 'results' && isset($path[1]) && $path[1] == "import") {
                    $this->checkPolicy("results","misc");
                    $retval=array_merge($retval, $model->doImport($modeldata['import']));
                }
                else if($path[0] == 'results' && isset($path[1]) && $path[1] == "recalculate") {
                    $this->checkPolicy("results","misc");
                    $retval=array_merge($retval, $model->recalculate($modeldata['competition_id']));
                }
                else if($path[0] == 'results' && isset($path[1]) && $path[1] == "clear") {
                    $this->checkPolicy("results","misc");
                    $retval=array_merge($retval, $model->clear($modeldata['competition_id']));
                }
                else {
                    $this->checkPolicy($path[0],"list");
                    $retval=array_merge($retval, $this->listAll($model,$offset,$pagesize,$filter,$sort,$special));
                }
                break;
            // LIST and UPDATE
            case "migrations":
                switch($path[0]) {
                    case 'migrations': $model = $this->loadModel("Migration"); break;
                }
                
                if(isset($path[1]) && $path[1] == "save") {
                    $this->checkPolicy($path[0],"save");
                    $retval=array_merge($retval, $this->save($model,$modeldata));
                }
                else {
                    $this->checkPolicy($path[0],"list");
                    $retval=array_merge($retval, $this->listAll($model,$offset,$pagesize,$filter,$sort,$special));
                }
                break;
            // for these we only support a listing functionality
            case "weapons":
            case "categories":
            case "types":
            case "users":
            case "posts":
                switch($path[0]) {
                    case 'weapons': $model = $this->loadModel("Weapon"); break;
                    case 'categories': $model = $this->loadModel("Category"); break;
                    case 'types': $model = $this->loadModel("EventType"); break;
                    case 'users': $model = $this->loadModel("User"); break;
                    case 'posts': $model = $this->loadModel("Posts"); break;
                }
                $this->checkPolicy($path[0],"list");
                $retval=array_merge($retval, $this->listAll($model,0,null,'','i',$special));
                break;
            // special models
            case 'ranking':
                if(isset($path[1]) && $path[1] == "reset") {
                    $this->checkPolicy($path[0],"misc");
                    $model = $this->loadModel("Ranking"); 
                    $total = $model->calculateRankings();
                    $retval=array(
                        "success" => TRUE,
                        "total" => $total
                    );
                }
                else if(isset($path[1]) && $path[1] == "list") {
                    $this->checkPolicy($path[0],"list");
                    $model = $this->loadModel("Ranking");
                    $cid = intval(isset($modeldata['category_id']) ? $modeldata['category_id'] : "-1");
                    $catmodel = $this->loadModel("Category");
                    $catmodel = $catmodel->get($cid);
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
                    $this->checkPolicy($path[0],"view");
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
                break;
            case "registration":
                if (isset($path[1]) && $path[1] == "save") {
                    $this->checkPolicy($path[0], "save", array("filter" => $filter, "model" => $modeldata));
                    $model = $this->loadModel("Registration");
                    $retval = array_merge($retval, $this->save($model, $modeldata));
                } else if (isset($path[1]) && $path[1] == "delete") {
                    $this->checkPolicy($path[0], "delete", array("filter" => $filter, "model" => $modeldata));
                    $model = $this->loadModel("Registration");
                    $retval = array_merge($retval, $this->delete($model, $modeldata));
                }
                else {
                    // we can list if we can administer the event belonging to the passed side-event
                    $this->checkPolicy($path[0], "list", array("filter"=>$filter, "model"=>$modeldata));
                    $model = $this->loadModel("Registration");
                    $retval = array_merge($retval, $this->listAll($model, $offset, $pagesize, $filter, $sort, $special));
                }                   
                break;

        }

        error_log("returning ".json_encode($retval));
        if(!isset($retval["error"])) {
            wp_send_json_success($retval);
        }
        else {
            wp_send_json_error($retval);
        }
        wp_die();
    }

    private function save($model, $data) {
        error_log('save action');
        $retval=array();
        if(!$model->saveFromObject($data)) {
            error_log('save failed');
            $retval["error"]=true;
            $retval["messages"]=$model->errors;
        }
        else {
            error_log('save successful');
            $retval["id"] = $model->{$model->pk};
            $retval = array_merge($retval,array("model"=>$model->export()));
        }
        return $retval;
    }

    private function delete($model, $data) {
        error_log('delete action');
        $retval=array();
        if(!$model->delete($data['id'])) {
            error_log('delete failed');
            $retval["error"]=true;
            $retval["messages"]=array("Internal database error");
            if(isset($model->errors) && is_array($model->errors)) {
                $retval["messages"]=$model->errors;
            }
        }
        else {
            error_log('delete successful');
            $retval["id"] = $model->{$model->pk};
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
            error_log('ERROR:' .$str);
            $retval['list']=array();
            $retval['total']=0;
        }
        return $retval;
    }

    private function checkPolicy($model,$action,$obj=null) {
        require_once(__DIR__ . "/policy.php");
        $policy = new \EVFRanking\Policy();
        if(!$policy->check($model,$action,$obj)) {
            die(403);
        }
    }
}