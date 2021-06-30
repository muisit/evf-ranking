<?php

namespace EVFRanking\Util;

class PDFSummary {
    public $event;
    public $type;
    public $model;
    public $_path;

    public function __construct($event, $type,$model) {
        $this->event=$event;
        $this->type=$type;
        $this->model=$model;
    }

    public static function CheckPath($eid, $path) {
        // path is summary_<type>_<tid>_<hash>.pdf
        $elements=explode('_',basename($path));
        if(sizeof($elements) == 4 && $elements[0] == "summary") {
            $type = $elements[1];
            $tid = $elements[2];

            $actualfile = PDFSummary::SearchPath($eid, $type, $tid);
            if(basename($actualfile) == $path) {
                return PDFSummary::CheckHash($actualfile,$eid,$type,$tid);
            }
        }
        return false;
    }

    public static function CheckHash($path, $eid, $type, $tid) {
        $event=new \EVFRanking\Models\Event($eid,true);
        $model=null;
        switch($type) {
        case 'Country':  $model=new \EVFRanking\Models\Country($tid,true); break;
        case 'Role':     $model=new \EVFRanking\Models\Country($tid,true); break;
        case 'Template': $model=new \EVFRanking\Models\AccreditationTemplate($tid,true); break;
        case 'Event':    $model=new \EVFRanking\Models\SideEvent($tid,true); break;
        }
        if(!empty($model) && $model->exists() && $event->exists()) {
            $accreditations=$model->selectAccreditations($event);
            if(sizeof($accreditations)==0) return false;
            list($overallhash,$files)=PDFSummary::CreateHash($accreditations);
            if(empty($overallhash)) return false;
            $outputpath = PDFSummary::GetPath($event->getKey(), $type, $tid, $overallhash);

            return $outputpath == $path;
        }
        return false;
    }

    public static function CreateHash($accreditations) {
        // check that all files exist
        $files=array();
        foreach($accreditations as $a) {
            if($a->isDirty()) {
                error_log("dirty accreditation prevents summary file");
                return array("",array());
            }

            $path=$a->getPath();
            if(!file_exists($path)) {
                error_log("missing PDF $path prevents summary file");
                $a->setDirty();
                $a->save();
                return array("", array());
            }

            $hash=$a->file_hash;
            $fencer=new \EVFRanking\Models\Fencer($a->fencer_id,true);

            $key=$fencer->fencer_surname.",".$fencer->fencer_firstname."~".$a->getKey();
            $files[$key]=array("file"=>$path,"hash"=>$hash,"fencer"=>$fencer, "accreditation" => $a);
        }

        // sort the files by fencer name
        // Sorting makes it easier for the end user to find missing accreditations
        // Also, sorting is vital to make sure the overall hash is created in the 
        // same way
        ksort($files, SORT_NATURAL);

        // accumulate all hashes to get at an overall hash
        $acchash="";
        foreach($files as $k=>$v) {
            $acchash.=$v["hash"];
        }
        $overallhash=hash('sha256',$acchash);
        return array($overallhash,$files);
    }

    public function create() {
        // delete any existing document first
        $outputpath = PDFSummary::SearchPath($this->event->getKey(), $this->type, $this->model->getKey());
        if (file_exists($outputpath)) {
            @unlink($outputpath);
        }
        if (file_exists($outputpath)) {
            // error: unable to delete output, cannot create new file
            return;
        }

        $accreditations=$this->accreditations();
        if(sizeof($accreditations) == 0) return;

        list($overallhash,$files)=PDFSummary::CreateHash($accreditations);
        if(sizeof($files) ==0) return;
        $outputpath = PDFSummary::GetPath($this->event->getKey(), $this->type, $this->model->getKey(), $overallhash);

        if (file_exists($outputpath)) {
            @unlink($outputpath);
        }
        if (file_exists($outputpath)) {
            // error: unable to delete output, cannot create new file
            return;
        }

        do_action('extlibraries_hookup', 'tcpdf');
        do_action('extlibraries_hookup', 'fpdi');

        $pdf = new \setasign\Fpdi\Tcpdf\Fpdi();
        
        $templates=array();
        // to keep track of the next possible option, we assign 9 different positions:
        // A4 1, 2, 3 and 4
        // A4 landscape 5, 6
        // A5 landscape 7, 8
        // A6 portrait 9
        // 
        // If a new template does not fit in any of these positions, we create a new page
        $currentposition=null;
        foreach($files as $k=>$v) {
            $accreditation = $v["accreditation"];
            $templateid = $accreditation->template_id;
            if(!isset($templates["t".$templateid])) {
                $templates["t".$templateid] = new \EVFRanking\Models\AccreditationTemplate($templateid,true);
            }
            $template = isset($templates["t".$templateid]) ? $templates["t".$templateid] : null;
            $pageoption = "a4portrait";
            if(!empty($template) && $template->exists()) {
                $content=json_decode($template->content,true);
                if(isset($content["print"])) $pageoption=$content["print"];
            }

            $pdf->SetSourceFile($v["file"]);
            $templateId = $pdf->importPage(1);

            switch($pageoption) {
            default:
            case 'a4portrait':
            case 'a4portrait2':
            case 'a5landscape':
            case 'a5landscape2':
                $size = $pdf->getTemplateSize($templateId, 210);
                break;
            case 'a4landscape':
            case 'a4landscape2':
                $size = $pdf->getTemplateSize($templateId,297);
                break;
            case 'a6portrait':
                $size = $pdf->getTemplateSize($templateId,105);
                break;
            }

            $thisposition=null;
            $followingposition=null;
            switch($pageoption) {
            default:
            case 'a4portrait':
                if($currentposition === null) {
                    $thisposition=1;
                    $followingposition=3;
                }
                else if($currentposition === 3) {
                    $thisposition=3;
                    $followingposition=null;
                }
                break;
            case 'a4landscape':
                $thisposition=5;
                $followingposition=null;
                break;
            case 'a4portrait2':
                // allow 1, 2 and 3
                if($currentposition === null) {
                    $thisposition=1;
                    $followingposition=2;
                }
                else if($currentposition === 2) {
                    $thisposition=2;
                    $followingposition=3;
                }
                else if($currentposition === 3) {
                    $thisposition=3;
                    $followingposition=4;
                }
                else if($currentposition === 4) {
                    $thisposition=4;
                    $followingposition=null;
                }
                break;
            case 'a4landscape2':
                if($currentposition === null) {
                    $thisposition=5;
                    $followingposition=null;
                }
                else if($currentposition === 6) {
                    $thisposition=6;
                    $followingposition=null;
                }
                break;
            case 'a5landscape':
                $thisposition=7;
                $followingposition=null;
                break;
            case 'a5landscape2':
                if($currentposition === null) {
                    $thisposition=7;
                    $followingposition=8;
                }
                else if($currentposition === 7) {
                    $thisposition=8;
                    $followingposition=null;
                }
                break;
            case 'a6portrait':
                $thisposition=9;
                $followingposition=null;
                break;
            }

            if($currentposition === null) {
                error_log("adding a new page");
                $pdf->AddPage($size['orientation'],$size);
            }

            $x=0;
            $y=0;
            $w=210;
            $h=297;
            switch($thisposition) {
            case 1: break; // no adjustments
            case 2: $x=105; break;
            case 3: $y=148.5;break;
            case 4: $x=105; $y=148.5;break;
            case 5: $x=43; $y=31; $w=297;$h=210; break;
            case 6: $x=43+105; $y=31; $w=297;$h=210; break;
            case 7: $w=210; $h=148.5; break; 
            case 8: $x=105; $w=210; $h=148.5; break;
            case 9: $w=105; $h=148.5; break;
            }

            error_log("importing page at position $thisposition, $x, $y -> $w, $h");
            $pdf->useImportedPage($templateId,$x, $y, $w,$h,false);

            $currentposition = $followingposition;
        }

        $pdf->Output($outputpath, 'F');
        $this->path=$outputpath;
    }

    public function accreditations() {
        switch($this->type) {
        case 'Country': 
        case 'Event':
        case 'Template':
        case 'Role':
            return $this->model->selectAccreditations($this->event);
            break;
        }
        return array();
    }

    public static function AllSummaries($eid) {
        $upload_dir = wp_upload_dir();
        $path = $upload_dir['basedir'] . "/pdfs/event" . $eid . "/summary_*.pdf";
        $results=glob($path);
        $retval=array();
        if(!empty($results)) {
            $dir = dirname($path);
            foreach($results as $r) {
                $fname = $dir . "/" . basename($r);
                if(file_exists($fname)) {
                    $retval[]=$fname;
                }
            }
        }
        return $retval;
    }

    public static function SearchPath($eid,$type,$tid) {
        $path = PDFSummary::GetPath($eid,$type,$tid,"*");
        $dir=dirname($path);
        $results=glob($path);
        if(!empty($results)) {
            foreach($results as $r) {
                $fname = $dir . "/" .basename($r);
                if(file_exists($fname)) {
                    return $fname;
                }
            }
        }
        return null;
    }

    public static function GetPath($eid,$type,$tid,$hash) {        
        if ($type == "Role" && in_array(intval($tid),array(0,-1),true)) {
            if (intval($tid) == -1) {
                $tid = "p";
            } else {
                $tid = "a";
            }
        }

        $upload_dir = wp_upload_dir();
        $path = $upload_dir['basedir'] . "/pdfs/event" . $eid . "/summary_" . $type ."_".$tid."_".$hash.".pdf";
        return $path;
    }

    public static function Exists($eid,$type,$tid) {
        $path = PDFSummary::SearchPath($eid, $type, $tid);
        return !($path === null);
    }
}


