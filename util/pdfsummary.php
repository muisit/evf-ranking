<?php

namespace EVFRanking\Util;

class PDFSummary
{
    public $event;
    public $type;
    public $model;
    public $accreditations;

    public function __construct($document, $event, $type, $model)
    {
        $this->document = $document;
        $this->event = $event;
        $this->type = $type;
        $this->model = $model;

        $this->accreditations = array();
        foreach ($this->document->configObject->accreditations as $aid) {
            $this->accreditations[] = new \EVFRanking\models\Accreditation($aid, true);
        }
    }

    public function createHash()
    {
        global $evflogger;
        // check that all files exist
        foreach ($this->accreditations as $a) {
            if ($a->isDirty()) {
                $evflogger->log("dirty accreditation prevents summary file");
                return array('',array());
            }

            $path = $a->getPath();
            if (!file_exists($path)) {
                $evflogger->log("missing PDF $path prevents summary file");
                $a->setDirty();
                $a->save();
                return array('',array());
            }
        }
        return PDFManager::MakeHash($this->accreditations);
    }

    public function create()
    {
        global $evflogger;
        // delete any existing document first
        $outputpath = $this->document->path;
        if (file_exists($outputpath)) {
            @unlink($outputpath);
        }
        if (file_exists($outputpath)) {
            $evflogger->log("PDFSummary::create unable to unlink existing file, returning");
            // error: unable to delete output, cannot create new file
            return;
        }

        $accreditations = $this->accreditations;
        if (count($accreditations) == 0) {
            $evflogger->log("PDFSummary::create no accreditations, returning empty file");
            return;
        }

        list($overallhash, $files) = $this->createHash($accreditations);
        if (count($files) == 0) {
            $evflogger->log("PDFSummary::create no files found with hashes");
            return;
        }

        $this->createPDF($files);

        if ($this->document->fileExists()) {
            $this->document->hash = $overallhash;
            $this->document->save();
        }
    }

    private function createPDF($files)
    {
        global $evflogger;
        do_action('extlibraries_hookup', 'tcpdf');
        do_action('extlibraries_hookup', 'fpdi');

        $pdf = new \setasign\Fpdi\Tcpdf\Fpdi();
        
        // cache templates
        $template = array();

        // to keep track of the next possible option, we assign 9 different positions:
        // A4 1, 2, 3 and 4
        // A4 landscape 5, 6
        // A5 landscape 7, 8
        // A6 portrait 9
        //
        // If a new template does not fit in any of these positions, we create a new page
        $currentposition = null;
        $evflogger->log("PDFSummary::create looping over contained files");
        foreach ($files as $k => $v) {
            $accreditation = $v["accreditation"];
            $templateid = $accreditation->template_id;
            if (!isset($templates["t" . $templateid])) {
                $templates["t" . $templateid] = new \EVFRanking\Models\AccreditationTemplate($templateid,true);
            }
            $template = isset($templates["t" . $templateid]) ? $templates["t" . $templateid] : null;
            $pageoption = "a4portrait";
            if (!empty($template) && $template->exists()) {
                $content = json_decode($template->content, true);
                if (isset($content["print"])) {
                    $pageoption = $content["print"];
                }
            }

            $evflogger->log('PDFSummary: importing accreditation file ' . $v['file']);
            $pdf->SetSourceFile($v["file"]);
            $templateId = $pdf->importPage(1);

            list($thisposition, $followingposition) = $this->positionPage($pageoption, $currentposition);
            if ($currentposition === null) {
                $evflogger->log("adding a new page");
                $size = $this->getPageSize($pdf, $pageoption, $templateId);
                $pdf->AddPage($size['orientation'], $size);
            }

            list($x, $y, $w, $h) = $this->placePage($thisposition);
            $evflogger->log("importing page at position $thisposition, $x, $y -> $w, $h");
            $pdf->useImportedPage($templateId, $x, $y, $w, $h, false);
            $currentposition = $followingposition;
        }

        $evflogger->log("PDFSummary: outputting document");
        $pdf->Output($this->document->getPath(), 'F');
        $evflogger->log("PDFSummary::create end of create, path at " . $this->document->path);
    }

    private function positionPage($pageoption, $currentposition)
    {
        $thisposition = null;
        $followingposition = null;
        switch ($pageoption) {
            default:
            case 'a4portrait':
                if ($currentposition === null) {
                    $thisposition = 1;
                    $followingposition = 3;
                }
                else if ($currentposition === 3) {
                    $thisposition = 3;
                    $followingposition = null;
                }
                break;
            case 'a4landscape':
                $thisposition = 5;
                $followingposition = null;
                break;
            case 'a4portrait2':
                // allow 1, 2 and 3
                if ($currentposition === null) {
                    $thisposition = 1;
                    $followingposition = 2;
                }
                else if ($currentposition === 2) {
                    $thisposition = 2;
                    $followingposition = 3;
                }
                else if ($currentposition === 3) {
                    $thisposition = 3;
                    $followingposition = 4;
                }
                else if ($currentposition === 4) {
                    $thisposition = 4;
                    $followingposition = null;
                }
                break;
            case 'a4landscape2':
                if ($currentposition === null) {
                    $thisposition = 5;
                    $followingposition = null;
                }
                else if ($currentposition === 6) {
                    $thisposition = 6;
                    $followingposition = null;
                }
                break;
            case 'a5landscape':
                $thisposition = 7;
                $followingposition = null;
                break;
            case 'a5landscape2':
                if ($currentposition === null) {
                    $thisposition = 7;
                    $followingposition = 8;
                }
                else if ($currentposition === 7) {
                    $thisposition = 8;
                    $followingposition = null;
                }
                break;
            case 'a6portrait':
                $thisposition = 9;
                $followingposition = null;
                break;
        }
        return [$thisposition, $followingposition];
    }

    private function placePage($thisposition)
    {
        $x = 0;
        $y = 0;
        $w = 210;
        $h = 297;
        switch ($thisposition) {
            case 1:
                break; // no adjustments
            case 2:
                $x = 105;
                break;
            case 3:
                $y = 148.5;
                break;
            case 4:
                $x = 105;
                $y = 148.5;
                break;
            case 5:
                $x = 43;
                $y = 31;
                $w = 297;
                $h = 210;
                break;
            case 6:
                $x = 43 + 105;
                $y = 31;
                $w = 297;
                $h = 210;
                break;
            case 7:
                $w = 210;
                $h = 148.5;
                break;
            case 8:
                $x = 105;
                $w = 210;
                $h = 148.5;
                break;
            case 9:
                $w = 105;
                $h = 148.5;
                break;
        }
        return [$x, $y, $w, $h];
    }

    private function getPageSize($pdf, $pageoption, $templateId)
    {
        $size = 0;
        switch ($pageoption) {
            default:
            case 'a4portrait':
            case 'a4portrait2':
            case 'a5landscape':
            case 'a5landscape2':
                $size = $pdf->getTemplateSize($templateId, 210);
                break;
            case 'a4landscape':
            case 'a4landscape2':
                $size = $pdf->getTemplateSize($templateId, 297);
                break;
            case 'a6portrait':
                $size = $pdf->getTemplateSize($templateId, 105);
                break;
        }
        return $size;
    }
}


