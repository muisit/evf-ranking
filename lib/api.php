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
            $picture = $this->fromGet("picture");
            $download = $this->fromGet("download");

            if (!empty($picture) && is_numeric($picture)) {
                $fencer = new \EVFRanking\Models\Fencer($picture, true);

                if ($fencer->exists()) {
                    // check the policy to see if the user can retrieve a listing
                    $this->checkPolicy("picture", "view", array(
                        "model" => array(
                            "fencer" => $fencer->getKey(),
                        )
                    ));
                    $pm = new PictureManager();
                    $pm->display($fencer);
                }
            }

            if (!empty($download) && $download == 'ranking') {
                (new ExportManager())->download('ranking');
            }
        }
        die(403);
    }

    private function doFile($nonce)
    {
        $this->checkNonce($nonce);
        $fencer = $this->fromPost("fencer");
        $retval = array("error" => true);

        if (!empty($event) && $upload == "true") {
            $fencer = $this->loadModel("Fencer", $fencer);

            if ($fencer->exists()) {
                // check the policy to see if the user can save a picture
                $this->checkPolicy("picture", "save", array(
                    "model" => array(
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
        }
        return $retval;
    }

    protected function doPost($data)
    {
        $this->checkNonce($data['nonce']);

        $modeldata = isset($data['model']) ? $data['model'] : array();
        $offset = isset($modeldata['offset']) ? intval($modeldata['offset']) : 0;
        $pagesize = isset($modeldata['pagesize']) ? intval($modeldata['pagesize']) : 20;
        $filter = isset($modeldata['filter']) ? $modeldata['filter'] : "";
        $sort = isset($modeldata['sort']) ? $modeldata['sort'] : "";
        $special = isset($modeldata['special']) ? $modeldata['special'] : "";

        $path = $data['path'];
        if (empty($path)) {
            $path = "index";
        }
        $path = explode('/', trim($path, '/'));
        if (!is_array($path) || sizeof($path) == 0) {
            $path = array("index");
        }

        $retval = array();
        switch ($path[0]) {
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
                    case 'roletypes': $model = $this->loadModel("RoleType"); break;
                    case 'roles': $model = $this->loadModel("Role"); break;
                    case 'registrars': $model = $this->loadModel("Registrar"); break;
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
                else if($path[0] == 'events' && isset($path[1]) && $path[1] == "roles") {
                    // list all side events of events (event special action)
                    $this->checkPolicy("eventroles","list", array("filter" => $filter, "model" => $modeldata));
                    $id=isset($modeldata["id"]) ? intval($modeldata["id"]) : -1;
                    $retval=array_merge($retval, $this->listResults($model, $model->roles($id), null, TRUE));
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
                else if(isset($path[1]) && $path[1] == "cutoff") {
                    $this->checkPolicy($path[0],"misc", array("filter" => $filter, "model" => $modeldata));
                    $model = $this->loadModel("Ranking");
                    $retval = $model->setCutoff($modeldata);
                }
                else {
                    $retval=array("error"=>"invalid action");
                }
                break;
        }
        return $retval;
    }

    protected function save($model, $data) {
        $retval=array();

        $caps="none"; // no capabilities by default, unless we have an event to base it on
        if (current_user_can( 'manage_ranking' ) || current_user_can( 'manage_registration' )) {
            $caps="system";
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
        $retval = array();
        $model = $model->get($data['id']);
        if (!empty($model) && $model->exists()) {
            if (!$model->delete()) {
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