<?php

/**
 * EVF-Ranking RoleType Model
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


 namespace EVFRanking\Models;

 class AccreditationTemplate extends Base {
    public $table = "TD_Accreditation_Template";
    public $pk="id";
    public $fields=array("id","name","content","event_id");
    public $fieldToExport=array(
        "id" => "id",
        "name" => "name",
        "content"=>"content",
        "event_id" => "event",
        "copy_of" => "copy_of"
    );
    public $rules = array(
        "id"=>"skip",
        "name" => "trim|required|lte=200",
        "content"=> "trim",
        "event_id" => "model=Event",
        "copy_of" => "model=AccreditationTemplate"
    );

    const OPTIONNAME="evfranking_defaulttemplate";

    private function sortToOrder($sort) {
        if(empty($sort)) $sort="i";
        $orderBy=array();
        for($i=0;$i<strlen($sort);$i++) {
            $c=$sort[$i];
            switch($c) {
            default:
            case 'i': $orderBy[]="id asc"; break;
            case 'I': $orderBy[]="id desc"; break;
            case 'n': $orderBy[]="name asc"; break;
            case 'N': $orderBy[]="name desc"; break;
            }
        }
        return $orderBy;
    }

    public function export($result=null) {
        $retval=parent::export($result);
        $retval["content"]=json_decode($retval["content"],true);
        return $retval;
    }

    private function addFilter($qb, $filter,$special) {
        if(is_string($filter)) $filter=json_decode($filter,true);
        if(!empty($filter)) {
            if(isset($filter["name"])) {
                $name=str_replace("%","%%",$filter["name"]);
                $qb->where("name","like","%$name%");
            }
            if (isset($filter["event"])) {
                $id = intval($filter["event"]);
                $qb->where("event_id", $id);
            }
        }
    }

    public function selectAll($offset,$pagesize,$filter,$sort, $special=null) {
        $qb = $this->select('*')->offset($offset)->limit($pagesize)->orderBy($this->sortToOrder($sort));
        $this->addFilter($qb,$filter,$special);
        return $qb->get();
    }

    public static function ListAll($event) {
        $model=new AccreditationTemplate();
        return $model->selectAll(0,100000,array("event",$event->getKey()),"i");
    }

    public function count($filter,$special=null) {
        $qb = $this->numrows();
        $this->addFilter($qb,$filter,$special);
        return $qb->count();
    }

    public function delete($id=null) {
        if($id === null) $id = $this->getKey();

        // remove all accreditations for this template
        $accrs = $this->select('*')->from("TD_Accreditation")->where("template_id",$id)->get();
        if(!empty($accrs) && sizeof($accrs) > 0) {
            foreach($accrs as $a) {
                $accreditation = new Accreditation($a);
                $accreditation->delete();
            }
        }

        if(sizeof($this->errors)==0) {
            if(parent::delete($id)) {
                $content = json_decode($this->content, true);
                if(isset($content["pictures"])) {
                    foreach($content["pictures"] as $pic) {
                        $path = $this->getPath("pictures",$pic["file_id"],$pic["file_ext"]);
                        if(file_exists($path)) {
                            @unlink($path);
                        }
                    }
                }
                return true;
            }
        }
        return false;
    }

    public function save() {
        if(!parent::save()) { // do this first to get an id
            return false;
        }

        if(isset($this->copy_of)) {
            $copyof=new AccreditationTemplate($this->copy_of,true);
            // we are saving a copy of another template. If there are pictures, copy the pictures
            $content=json_decode($this->content, true);
            $content2=json_decode($copyof->content, true);
            if(isset($content["pictures"])) unset($content["pictures"]);
            if(isset($content2["pictures"])) {
                $content["pictures"]=array();
                foreach($content2["pictures"] as $pic) {
                    $path2 = $copyof->getPath("pictures",$pic["file_id"],$pic["file_ext"]);
                    if(file_exists($path2)) {
                        $path = $this->getPath("pictures",$pic["file_id"],$pic["file_ext"]);
                        @copy($path2,$path);
                        if(file_exists($path)) {
                            $content["pictures"][]=$pic;
                        }
                    }
                }
            }
            $this->content=json_encode($content);
            return parent::save();
        }
        return true;
    }

    public function postSave($wassaved) {
        if($wassaved) {
            // make all accreditations of this template dirty
            $this->query()->from("TD_Accreditation")->set("is_dirty", strftime('%F %T'))->where('template_id', $this->getKey())->update();
        }
    }

    public function getDir($type) {
        $subpath = "templates";
        switch ($type) {
        default:
        case "pictures":
            $subpath = "templates";
            break;
        }
        return $subpath;
    }

    public function getPath($type, $id, $ext=null) {
        $upload_dir = wp_upload_dir();
        $subpath=$this->getDir($type);
        $fname="none.dat";
        switch($type) {
        default:
        case "pictures": 
            if($ext === null) $ext="jpg";
            $fname = "img_".$this->getKey()."_".$id.".".$ext;
            break;
        }

        $filename = $upload_dir['basedir'] . "/".$subpath."/".$fname;
        return $filename;
    }

    public function addPicture($tmpl) {
        $cnt = json_decode($this->content, true);
        if ($cnt === false) $cnt = array();
        if (!isset($cnt["pictures"])) $cnt["pictures"] = array();
        $cnt["pictures"][] = $tmpl;
        $this->content = json_encode($cnt);
    }

    public function deletePicture($fileid,$tid=null) {
        $template=$this;
        if($tid !== null)  {
            $template=new AccreditationTemplate($tid,true);
        }
        if(!$template->exists()) return;

        $cnt = json_decode($template->content, true);
        if ($cnt === false) $cnt = array();
        if (!isset($cnt["pictures"])) $cnt["pictures"] = array();
        $newlist = array();
        $found=null;
        foreach($cnt["pictures"] as $pic) {
            $fid = isset($pic["file_id"]) ? $pic["file_id"] : null;
            if($fileid === $fid) {
                $found=$pic;
            }
            else {
                $newlist[]=$pic;
            }
        }
        $cnt["pictures"] = $newlist;
        $template->content = json_encode($cnt);
        $template->save();

        if($found !== null) {
            // remove the actual file from disk
            $path = $template->getPath("pictures",$found["file_id"],$found["file_ext"]);
            if(file_exists($path)) {
                @unlink($path);
            }
        }
    }

    public function setAsDefault($modeldata) {
        $id=intval(isset($modeldata["id"]) ? $modeldata["id"]:-1);
        $name=isset($modeldata["name"]) ? $modeldata["name"] : "";
        $dounset = isset($modeldata["unset"]) ? true: false;
        $option=get_option(AccreditationTemplate::OPTIONNAME);
        if(!is_array($option)) {
            $option=array("templates"=>array());
        }
        if($dounset) {
            // filter out the id
            $option["templates"] = array_filter($option["templates"], function($v) use ($id) {
                return intval($v) != $id;
            });
        }
        else {
            $option["templates"][$name]=$id;
        }
        update_option(AccreditationTemplate::OPTIONNAME, $option);
    }

    public function listDefaults() {
        $option=get_option(AccreditationTemplate::OPTIONNAME);
        if(!is_array($option)) {
            $option=array("templates"=>array());
        }
        $lst=array();
        foreach($option["templates"] as $k=>$v) {
            $lst[]=array("id"=>$v, "name"=>$k);
        }
        return array("list"=>$lst);
    }

    public function addDefaults($modeldata) {
        $eid = intval(isset($modeldata["event"]) ? $modeldata["event"] : -1);
        $event = new Event($eid,true);
        if($event->exists()) {
            $defaults = $this->listDefaults();
            if(isset($defaults["list"])) {
                foreach($defaults["list"] as $dat) {
                    $tid = isset($dat["id"]) ? $dat["id"] : -1;
                    $template = new AccreditationTemplate($tid,true);
                    if($template->exists()) {
                        $newtemplate = new AccreditationTemplate();
                        $newtemplate->copy_of = $template->getKey();
                        $newtemplate->content = $template->content;
                        $newtemplate->name = $template->name . " (default)";
                        $newtemplate->event_id = $event->getKey();
                        $newtemplate->save();
                    }
                }
            }
        }
    }

    public function createExample($modeldata) {
        $fencer = new \EVFRanking\Models\Fencer();
        $fencer->fencer_surname = "THE TESTER";
        $fencer->fencer_firstname = "Testina";
        $country = new \EVFRanking\Models\Country();
        $country->country_name = "Testonia";
        $country->country_abbr = "TST";
        $event = new \EVFRanking\Models\Event();
        $event->event_name = "Test Event";
        $accreditation = new \EVFRanking\Models\Accreditation();
        $accreditation->data = json_encode(array(
            "firstname" => $fencer->fencer_firstname,
            "lastname" => $fencer->fencer_surname,
            "organisation" => $country->country_name,
            "country" => $country->country_abbr,
            "roles"=> array("Athlete WS4", "Team Armourer", "Head of Delegation", "Referee"),
            "dates" => array("SAT 12","SUN 21"),
            "accid" => "783-242",
            "created" => 1000,
            "modified" => 2000
        ));

        // import a few variables from the data
        $template = new \EVFRanking\Models\AccreditationTemplate();
        $template->setKey($modeldata['id']);
        $template->event_id=$modeldata["event"];

        if(isset($modeldata["content"])) {
            $content=$modeldata["content"];
            // see if there is a photoid element. In that case, add the
            // path to our generic CSS photo explicitely
            if(isset($content["elements"])) {
                $newels=array();
                foreach($content["elements"] as $el) {
                    if(isset($el["type"]) && $el["type"]=="photo") {
                        $el["test"] = __DIR__."/../dist/images/photoid.png";
                    }
                    $newels[]=$el;
                }
                $content["elements"]=$newels;
            }
            $template->content = json_encode($content);
        }

        $pdf=new \EVFRanking\Util\PDFCreator();
        $fname = tempnam(null,"");
        $pdf->create($fencer, $event, $template, $country, $accreditation, $fname);

        header('Content-Disposition: attachment; filename=template.pdf;');
        header('Content-Type: application/pdf; charset=UTF-8');

        ob_end_clean();
        $f=fopen($fname,'r');
        fpassthru($f);
        fclose($f);
        @unlink($fname);
        exit();
    }

    public function forRoles() {
        if(isset($this->content)) {
            $content=json_decode($this->content, true);
            if($content !== false && isset($content["roles"])) {
                return $content["roles"];
            }
        }
        return array();
    }

    public function selectAccreditations($event) {
        $accr=new Accreditation();
        $res = $accr->select('*')->where('template_id', $this->getKey())->where("event_id",$event->getKey())->get();
        $retval = array();
        foreach ($res as $r) $retval[] = new Accreditation($r);
        return $retval;
    }

    public static function TemplateIdsByRole($event) {
        $templs= AccreditationTemplate::ListAll($event);
        $model=new AccreditationTemplate();
        $templateByType = array("r0"=>array());
        foreach($templs as $t) {
            $model->read($t);
            $roles = $model->forRoles();

            foreach($roles as $rid) {
                $key="r".$rid;
                if(!isset($templateByType[$key])) $templateByType[$key]=array();
                $templateByType[$key][]=$model->getKey();
            }
        }
        return $templateByType;
    }


    public static function TemplateIdsByRoleType($event, $roleById=null) {
        if($roleById === null) {
            $roles = Role::ListAll();
            $roleById = array();
            foreach ($roles as $r) {
                $roleById["r" . $r->role_id] = new Role($r);
            }
        }

        $templs= AccreditationTemplate::ListAll($event);
        $model=new AccreditationTemplate();
        $templateByType = array("r0"=>array());
        foreach($templs as $t) {
            $model->read($t);
            $roles = $model->forRoles();

            foreach($roles as $rid) {
                if(intval($rid) == 0) {
                    // athlete or participant role. There can only ever be 1 template for
                    // the same role, or else we'll have an optimisation problem determining
                    // the right template to use
                    $templateByType["r0"] = array($model->getKey());
                }
                if(isset($roleById["r".$rid])) {
                    // see if this is a federative/country role
                    $key="r".$roleById["r".$rid]->role_type;
                    if(!isset($templateByType[$key])) $templateByType[$key]=array();
                    $templateByType[$key][]= $model->getKey();
                }
            }
        }
        return $templateByType;
    }
 }
 