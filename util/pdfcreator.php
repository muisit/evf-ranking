<?php

namespace EVFRanking\Util;

class PDFCreator {
    private $fencer;
    private $event;
    private $accreditation;
    private $template;
    private $country;
    private $accrid;
    private $pageoption;
    private $pdf;

    const APP_WIDTH=420.0; // 2x210, front-end canvas width
    const APP_HEIGHT=594.0; // 2x297, front end canvas height
    const PDF_WIDTH=105.0;// A6 portrait in mm
    const PDF_HEIGHT=148.5; // A6 portrait in mm

    //const PDF_PXTOPT=1.76; // for A4 reports
    const PDF_PXTOPT=1.01;
    const PDF_FONTS=array(
        "Courier" => "courier",
        "Courier Italic" => "courierI",
        "Courier Bold" => "courierB",
        "Courier Bold Italic" => "courierBI",
        "DejaVuSans" => "dejavusans",
        "DejaVuSans Italic" => "dejavusansI",
        "DejaVuSans Bold" => "dejavusansB",
        "DejaVuSans Bold Italic" => "dejavusansBI",
        "DejaVuSans Condensed" => "dejavusanscondensed",
        "DejaVuSans Condensed Italic" => "dejavusanscondensedI",
        "DejaVuSans Condensed Bold" => "dejavusanscondensedB",
        "DejaVuSans Condensed Bold Italic" => "dejavusanscondensedBI",
        "DejaVuSans Mono" => "dejavusansmono",
        "DejaVuSans Mono Italic" => "dejavusansmonoI",
        "DejaVuSans Mono Bold" => "dejavusansmonoB",
        "DejaVuSans Mono Bold Italic" => "dejavusansmonoBI",
        "Eurofurence" => "eurofurence",
        "Eurofurence Italic" => "eurofurenceI",
        "Eurofurence Bold" => "eurofurenceB",
        "Eurofurence Bold Italic" => "eurofurenceBI",
        // there seems to be a PDF problem with the regular Eurofurence Light, so it is disabled for now
        "Eurofurence Light" => "eurofurencelight",
        "Eurofurence Light Italic" => "eurofurencelightI",
        "FreeMono" => "freemono",
        "FreeMono Italic" => "freemonoI",
        "FreeMono Bold" => "freemonoB",
        "FreeMono Bold Italic" => "freemonoBI",
        "FreeSans" => "freesans",
        "FreeSans Italic" => "freesansI",
        "FreeSans Bold" => "freesansB",
        "FreeSans Bold Italic" => "freesansBI",
        "FreeSerif" => "freeserif",
        "FreeSerif Italic" => "freeserifI",
        "FreeSerif Bold" => "freeserifB",
        "FreeSerif Bold Italic" => "freeserifBI",
        "Helvetica" => "helvetica",
        "Helvetica Italic" => "helveticaI",
        "Helvetica Bold" => "helveticaB",
        "Helvetica Bold Italic" => "helveticaBI",
        "Times" => "times",
        "Times Italic" => "timesI",
        "Times Bold" => "timesB",
        "Times Bold Italic" => "timesBI",
    );
    /*
    const PDF_FONTS2=[
        "AlArabiya","Furat","cid0cs","cid0ct","cid0jp","cid0kr",
        "Courier-BoldOblique","Courier-Bold","Courier-Oblique","Courier","DejaVuSans-BoldOblique","DejaVuSans-Bold",
        "DejaVuSansCondensed-BoldOblique","DejaVuSansCondensed-Bold","DejaVuSansCondensed-Oblique","DejaVuSansCondensed","DejaVuSans-ExtraLight","DejaVuSans-Oblique",
        "DejaVuSansMono-BoldOblique","DejaVuSansMono-Bold","DejaVuSansMono-Oblique","DejaVuSansMono","DejaVuSans","DejaVuSerif-BoldItalic",
        "DejaVuSerif-Bold","DejaVuSerifCondensed-BoldItalic","DejaVuSerifCondensed-Bold","DejaVuSerifCondensed-Italic","DejaVuSerifCondensed","DejaVuSerif-Italic",
        "DejaVuSerif","FreeMonoBoldOblique","FreeMonoBold","FreeMonoOblique","FreeMono","FreeSansBoldOblique",
        "FreeSansBold","FreeSansOblique","FreeSans","FreeSerifBoldItalic","FreeSerifBold","FreeSerifItalic",
        "FreeSerif","Helvetica-BoldOblique","Helvetica-Bold","Helvetica-Oblique","Helvetica","HYSMyeongJoStd-Medium-Acro",
        "KozGoPro-Medium-Acro","KozMinPro-Regular-Acro","MSungStd-Light-Acro","PDFACourierBoldOblique","PDFACourierBold","PDFACourierOblique",
        "PDFACourier","PDFAHelveticaBoldOblique","PDFAHelveticaBold","PDFAHelveticaOblique","PDFAHelvetica","PDFASymbol",
        "PDFATimesBoldItalic","PDFATimesBold","PDFATimesItalic","PDFATimes","PDFAZapfdingbats","STSongStd-Light-Acro",
        "core","Times-BoldItalic","Times-Bold","Times-Italic","Times-Roman","ZapfDingbats"
    ];
    */

    public function create($fencer,$event,$template, $country, $accreditation, $filename) {
        $this->fencer=$fencer;
        $this->event=$event;
        $this->template=$template;
        $this->country=$country;
        $this->accreditation=$accreditation;
        $this->accrid=null;
        
        $this->pageoption = "a4portrait";
        if(isset($this->template)) {
            $content=json_decode($this->template->content,true);
            if(!empty($content) && isset($content["print"])) {
                $this->pageoption=$content["print"];
            }
        }
        global $evflogger;

        $evflogger->log("creating PDF from accreditation");
        do_action('extlibraries_hookup', 'tcpdf');

        $this->pdf = $this->createBasePDF();

        $this->pdf->AddPage();
        // create a template A6 format (105mm wide, 148.5mm high)
        $template_id = $this->pdf->startTemplate(PDFCreator::PDF_WIDTH, PDFCreator::PDF_HEIGHT, true);
        $evflogger->log("applying accreditation template");
        $this->pdf=$this->applyTemplate();
        $this->pdf->endTemplate();

        // additional offset for landscape printing starting at 297 - (2x105) = 87/2=43
        $landscapeoffsetX = 43;
        // and landscape height: y=210 - 148.5=61.5/2=31
        $landscapeoffsetY = 31;
        switch($this->pageoption) {
        default:
        case 'a4portrait':
            // paste the template twice at the top
            $this->pdf->printTemplate($template_id, $x = 0, $y = 0, $w = PDFCreator::PDF_WIDTH, $h = PDFCreator::PDF_HEIGHT, $align = '', $palign = '', $fitonpage = false);
            $this->pdf->printTemplate($template_id, $x = PDFCreator::PDF_WIDTH, $y = 0, $w = PDFCreator::PDF_WIDTH, $h = PDFCreator::PDF_HEIGHT, $align = '', $palign = '', $fitonpage = false);
            break;
        case 'a4landscape':
            // print the template twice over the centre of the page
            $this->pdf->printTemplate($template_id, $x = $landscapeoffsetX, $y=$landscapeoffsetY, $w=PDFCreator::PDF_WIDTH, $h=PDFCreator::PDF_HEIGHT, $align='', $palign='', $fitonpage=false);
            $this->pdf->printTemplate($template_id, $x = $landscapeoffsetX+PDFCreator::PDF_WIDTH, $y = $landscapeoffsetY, $w=PDFCreator::PDF_WIDTH, $h=PDFCreator::PDF_HEIGHT, $align = '', $palign = '', $fitonpage = false);
            break;
        case 'a4portrait2':
            // print the template once at the top
            $this->pdf->printTemplate($template_id, $x = 0, $y = 0, $w = PDFCreator::PDF_WIDTH, $h = PDFCreator::PDF_HEIGHT, $align = '', $palign = '', $fitonpage = false);
            break;
        case 'a4landscape2':
            // print the template once over the centre of the page
            $this->pdf->printTemplate($template_id, $x=$landscapeoffsetX, $y=$landscapeoffsetY, $w=PDFCreator::PDF_WIDTH, $h=PDFCreator::PDF_HEIGHT, $align='', $palign='', $fitonpage=false);
            break;
        case 'a5landscape':
            // print the template twice over the width
            $this->pdf->printTemplate($template_id, $x=0, $y=0, $w=PDFCreator::PDF_WIDTH, $h=PDFCreator::PDF_HEIGHT, $align='', $palign='', $fitonpage=false);
            $this->pdf->printTemplate($template_id, $x = PDFCreator::PDF_WIDTH, $y = 0, $w=PDFCreator::PDF_WIDTH, $h=PDFCreator::PDF_HEIGHT, $align = '', $palign = '', $fitonpage = false);
            break;
        case 'a5landscape2':
            // print the template once over the width
            $this->pdf->printTemplate($template_id, $x=0, $y=0, $w=PDFCreator::PDF_WIDTH, $h=PDFCreator::PDF_HEIGHT, $align='', $palign='', $fitonpage=false);
            break;
        case 'a6portrait':
            // print the template once at the top
            $this->pdf->printTemplate($template_id, $x = 0, $y = 0, $w = PDFCreator::PDF_WIDTH, $h = PDFCreator::PDF_HEIGHT, $align = '', $palign = '', $fitonpage = false);
            break;
        }

        // put the Accreditation ID either on both sides, only left or only right
        if(is_array($this->accrid)) {
            $evflogger->log("placing accreditation ID after general rendering");
            $options=$this->accrid["options"];
            $options['align']='C';

            $offset1 = array($options["offset"][0], $options["offset"][1]);
            $offset2 = array($options["offset"][0], $options["offset"][1]);
            switch($this->pageoption) {
            default:
            case 'a4portrait':
                $offset2[0] = $offset2[0] + PDFCreator::PDF_WIDTH;
                break;
            case 'a4landscape':
                $offset1[0] = $offset1[0] + $landscapeoffsetX;
                $offset1[1] = $offset1[1] + $landscapeoffsetY;
                $offset2[0] = $offset2[0] + $landscapeoffsetX + PDFCreator::PDF_WIDTH;
                $offset2[1] = $offset2[1] + $landscapeoffsetY;
                break;
            case 'a4portrait2':
                $offset2=null;
                break;
            case 'a4landscape2':
                $offset1[0] = $offset1[0] + $landscapeoffsetX;
                $offset1[1] = $offset1[1] + $landscapeoffsetY;
                $offset2=null;
                break;
            case 'a5landscape':
                $offset2[0] = $offset2[0] + PDFCreator::PDF_WIDTH;
                break;
            case 'a5landscape2':
                $offset2=null;
                break;
            case 'a6portrait':
                $offset2=null;
                break;
            }

            if($this->accrid["side"] == "both" || $this->accrid["side"] == "left") {
                $evflogger->log("placing accreditation ID on left page");
                $options["offset"]=$offset1;
                $this->putAccIDAt($this->accrid["text"], $options);
            }
            if (!empty($offset2) && ($this->accrid["side"] == "both" || $this->accrid["side"] == "right")) {
                $evflogger->log("placing accreditation ID on right page");
                $options["offset"] = $offset2;
                $this->putAccIDAt($this->accrid["text"], $options);
            }
        }

        $this->saveFile($filename);
    }

    protected function instantiatePDF() {
        $page="A4";
        $orientation="P";
        switch($this->pageoption) {
        default:
        case 'a4portrait':
        case 'a4portrait2':
            $orientation='P';
            $page='A4';
            break;
        case 'a4landscape':
        case 'a4landscape2':
            $orientation='L';
            $page='A4';
            break;
        case 'a5landscape':
        case 'a5landscape2':
            $orientation='L';
            $page='A5';
            break;
        case 'a6portrait':
            $orientation='P';
            $page='A6';
            break;
        }
        /* last parameter: pdfa mode 3 */
        return new \TCPDF($orientation, "mm", $page, true, 'UTF-8', false,3 ); 
    }

    protected function createBasePDF() {
        $pdf = $this->instantiatePDF();
        $pdf->SetCreator("European Veteran Fencing");
        $pdf->SetAuthor('European Veteran Fencing');
        $pdf->SetTitle('Accreditation for '. $this->event->event_title);
        $pdf->SetSubject($this->fencer->fencer_surname.", ". $this->fencer->fencer_firstname);
        $pdf->SetKeywords('EVF, Accreditation,'. $this->event->event_title);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(5,5,5);
        $pdf->SetAutoPageBreak(FALSE);
        $pdf->setImageScale(1.25);
        $pdf->setFontSubsetting(true);
        $pdf->SetDefaultMonospacedFont('courier');
        // set to helvetica, always loaded
        $pdf->SetFont('helvetica', '', 14, '', true);
        return $pdf;
    }

    private function addFont($fontname) {
        if(isset(PDFCreator::PDF_FONTS[$fontname])) {
            $fontkey = PDFCreator::PDF_FONTS[$fontname];
            global $evflogger;
            $evflogger->log("adding font $fontname with key $fontkey");
            switch($fontkey) {
            // our fonts
            case 'eurofurence': $this->pdf->AddFont("Eurofurence","",__DIR__."/fonts/eurof55.php",true); break;
            case 'eurofurenceI':$this->pdf->AddFont("Eurofurence","I",__DIR__."/fonts/eurof56.php",true);
            case 'eurofurenceB':$this->pdf->AddFont("Eurofurence","B",__DIR__."/fonts/eurof75.php",true);
            case 'eurofurenceBI':$this->pdf->AddFont("Eurofurence","BI",__DIR__."/fonts/eurof76.php",true); break;
            case 'eurofurencelight':$this->pdf->AddFont("Eurofurencelight","",__DIR__."/fonts/eurof35.php",true); break;
            case 'eurofurencelightI':$this->pdf->AddFont("Eurofurencelight","I",__DIR__."/fonts/eurof36.php",true); break;

            // core fonts
            case "courier":
            case "courierB":
            case "courierI":
            case "courierBI":
            case "helvetica":
            case "helveticaB":
            case "helveticaI":
            case "helveticaBI":
            case "times":
            case "timesB":
            case "timesI":
            case "timesBI":
            case "symbol":
            case "zapfdingbats":
            
            // other fonts also available in the TCPDF font folder
            case "dejavusans":
            case "dejavusansI":
            case "dejavusansB":
            case "dejavusansBI":
            case "dejavusanscondensed":
            case "dejavusanscondensedI":
            case "dejavusanscondensedB":
            case "dejavusanscondensedBI":
            case "dejavusansmono":
            case "dejavusansmonoI":
            case "dejavusansmonoB":
            case "dejavusansmonoBI":
            case "freemono":
            case "freemonoI":
            case "freemonoB":
            case "freemonoBI":
            case "freesans":
            case "freesansI":
            case "freesansB":
            case "freesansBI":
            case "freeserif":
            case "freeserifI":
            case "freeserifB":
            case "freeserifBI":
                $this->pdf->AddFont($fontkey,"","",true);
                break;
            default:
                global $evflogger;
                $evflogger->log("Font set, but not configured: $fontkey / $fontname");
                $this->pdf->SetFont("helvetica");
                return;
            }
            $evflogger->log("setting font to $fontkey");
            $this->pdf->SetFont($fontkey);
        }
        else {
            global $evflogger;
            $evflogger->log("No such font $fontname");
        }
    }

    private function saveFile($path) {
        $dirname = dirname($path);
        @mkdir($dirname,0755, true);

        $this->pdf->Output($path, 'F');
    }

    private function applyTemplate() {
        global $evflogger;
        $data=json_decode($this->accreditation->data, true);

        // for testing purposes, we need to be able to set the time/date
        //$evflogger->log("setting timestamps based on ".json_encode($data));
        $this->pdf->setDocCreationTimestamp(isset($data["created"]) ? $data["created"] : time());
        $this->pdf->setDocModificationTimestamp(isset($data["modified"]) ? $data["modified"] : time());
        $content = json_decode($this->template->content, true);
        $layers=array();
        //$evflogger->log("template content is ".json_encode($content));
        $evflogger->log("creating template using data ".json_encode($data));
        if(is_array($content) && isset($content["elements"])) {
            foreach($content["elements"] as $el) {
                $style = $el["style"];
                $name="l0";
                if(isset($style["zIndex"])) {
                    $name="l".$style["zIndex"];
                }
                if(!isset($layers[$name])) $layers[$name]=array();
                $layers[$name][]=$el;
            }
        }
        $pictures=array();
        if(is_array($content) && isset($content["pictures"])) {
            foreach($content["pictures"] as $pic) {
                if(isset($pic["file_id"])) {
                    $pictures[$pic["file_id"]] = $pic;
                }
            }
        }

        $keys=array_keys($layers);
        natsort($keys);
        foreach($keys as $key) {
            foreach($layers[$key] as $el) {
                $evflogger->log("layer $key, element ".$el["type"]);
                switch($el["type"]) {
                case "photo":   $this->addPhoto($el, $content,$data); break;
                case "text":    $this->addText($el,$content, $data); break;
                case "name":    $this->addName($el, $content, $data); break;
                case "accid":   $this->addID($el, $content, $data); break;
                case "country": $this->addCountry($el, $content,$data); break;
                case "cntflag": $this->addCountryFlag($el, $content,$data); break;
                case "org":     $this->addOrg($el,$content, $data); break;
                case "roles":   $this->addRoles($el,$content, $data); break;
                case "dates":   $this->addDates($el,$content, $data); break;
                case "box":     $this->addBox($el,$content, $data); break;
                case "img":     $this->addImage($el,$content, $data, $pictures); break;
                case 'qr':      $this->addQRCode($el,$content,$data); break;
                }
            }
        }
        return $this->pdf;
    }

    private function addPhoto($element,$content, $data) {
        global $evflogger;
        $offset = $this->getOffset($element);
        $size = $this->getSize($element);
        $evflogger->log("adding photo at " . json_encode($offset) . "/" . json_encode($size));

        if(isset($element["test"])) {
            $evflogger->log("test set, using static path");
            $path = $element["test"];
        }
        else {
            if($this->fencer->fencer_picture != 'N') {
                $path = $this->fencer->getPath();
            }
            else {
                $path="doesnotexist"; // no photo to print
            }
        }

        // make sure the aspect ratio is retained
        $rwidth = $size[1] * 0.77777777777777779;
        $rheight = $size[0] / 0.77777777777777779;
        if($rwidth < $size[0]) {
            $size[0]=$rwidth;
        }
        else {
            $size[1] = $rheight;
        }
        $evflogger->log("testing path $path");
        if (file_exists($path)) {
            $path = $this->createWatermark($path);
            if(!empty($path) && file_exists($path)) {
                $this->putImageAt($path, array("offset"=>$offset,"size"=>$size));
                @unlink($path);
            }
        }
    }

    private function addText($element,$content, $data) {
        $colour=$this->getColour($element);
        $offset=$this->getOffset($element);
        $size=$this->getSize($element); // text has no settable size
        $fsize=$this->getFontSize($element);
        $ffamily=$this->getFontFamily($element);
        $align=$this->getAlign($element);
        $txt = isset($element["text"]) ? $element["text"] : "";
        if(strlen(trim($txt))) {
            $options=array("offset" => $offset, "fontsize" => $fsize, "font"=>$ffamily, "colour" => $colour, "align"=>$align);
            if($size !== null) {
                $options["box"]=$size;
            }
            $this->putTextAt($txt, $options);
        }
    }

    private function addName($element,$content, $data) {
        global $evflogger;
        $colour = $this->getColour($element);
        $offset = $this->getOffset($element);
        $size = $this->getSize($element);
        $fsize = $this->getFontSize($element);
        $ffamily=$this->getFontFamily($element);
        $align=$this->getAlign($element);
        $fname = isset($data["firstname"]) ? $data["firstname"] : "";
        $lname = isset($data["lastname"]) ? $data["lastname"] : "";
        $txt=$lname.", ".$fname;
        if(isset($element["name"]) && $element["name"]=="first") {
            $txt=$fname;
        }
        if (isset($element["name"]) && $element["name"] == "last") {
            $txt = $lname;
        }
        if (strlen(trim($txt))) {
            $evflogger->log("putting text '$txt' at ".json_encode($offset)." x ".json_encode($size)." font size $fsize");
            $this->putTextAt($txt, array("offset" => $offset, "box" => $size, "fontsize" => $fsize, "font"=>$ffamily, "colour" => $colour,"align"=>$align));
        }
    }

    private function addID($element,$content, $data) {
        global $evflogger;
        $evflogger->log("putting accreditation id");
        $colour = $this->getColour($element);
        $offset = $this->getOffset($element);
        $size = $this->getSize($element);
        $fsize = $this->getFontSize($element);
        $ffamily = $this->getFontFamily($element);
        $align=$this->getAlign($element);
        $txt = isset($this->accreditation->fe_id) ? $this->accreditation->fe_id : "";
        $side = isset($element["side"]) ? $element["side"] : "both";

        $this->accrid=array("text"=>$txt, "side"=>$side,"options" => array("offset" => $offset, "box" => $size, "size"=>$size, "fontsize" => $fsize, "font"=>$ffamily, "colour" => $colour, "align"=>$align));
    }


    private function addCountry($element,$content, $data) {
        $colour = $this->getColour($element);
        $offset = $this->getOffset($element);
        $size = $this->getSize($element);
        $fsize = $this->getFontSize($element);
        $ffamily=$this->getFontFamily($element);
        $align=$this->getAlign($element);
        $txt = isset($data["country"]) ? $data["country"] : "";
        if (strlen(trim($txt))) {
            $this->putTextAt($txt, array("offset" => $offset, "box" => $size, "fontsize" => $fsize, "font"=>$ffamily, "colour" => $colour, "align"=>$align));
        }
    }

    private function addCountryFlag($element,$content, $data) {
        $offset = $this->getOffset($element);
        $size = $this->getSize($element);
        $fpath = isset($data["country_flag"]) ? $data["country_flag"] : "";
        if(empty($fpath)) return;

        $fpath = trailingslashit(ABSPATH) . $fpath;
        if(!file_exists($fpath)) return;

        // correct width/height downwards according to ratio
        if(isset($element["ratio"])) {
            $ratio=floatval($element["ratio"]);
            if($ratio > 0.0) {
                $rwidth = $size[1] * $ratio;
                $rheight = $size[0] / $ratio;

                if($rwidth < $size[0]) {
                    $size[0] = $rwidth;
                }
                else if($rheight<$size[1]) {
                    $size[1]=$rheight;
                }
            }
        }

        $this->putImageAt($fpath, array("offset"=>$offset,"size"=>$size));
    }

    private function addOrg($element,$content, $data) {
        $colour = $this->getColour($element);
        $offset = $this->getOffset($element);
        $size = $this->getSize($element);
        $fsize = $this->getFontSize($element);
        $ffamily=$this->getFontFamily($element);
        $align=$this->getAlign($element);
        $txt = isset($data["organisation"]) ? $data["organisation"] : "";
        if (strlen(trim($txt))) {
            $this->putTextAt($txt, array("offset"=>$offset, "box"=>$size, "fontsize"=>$fsize, "font"=>$ffamily, "colour"=>$colour,"align"=>$align));
        }
    }
    private function addRoles($element,$content, $data) {
        $colour = $this->getColour($element);
        $offset = $this->getOffset($element);
        $size = $this->getSize($element);
        $fsize = $this->getFontSize($element);
        $ffamily=$this->getFontFamily($element);
        $align=$this->getAlign($element);
        $txt = isset($data["roles"]) ? $data["roles"] : array();
        $txt = implode(", ",$txt);
        if (strlen(trim($txt))) {
            $this->putTextAt($txt, array("offset" => $offset, "box" => $size, "fontsize" => $fsize, "font"=>$ffamily, "colour" => $colour,"align"=>$align));
        }
    }
    private function addDates($element,$content, $data) {
        $colour = $this->getColour($element);
        $offset = $this->getOffset($element);
        $size = $this->getSize($element);
        $fsize = $this->getFontSize($element);
        $ffamily=$this->getFontFamily($element);
        $align=$this->getAlign($element);
        $txt = isset($data["dates"]) ? $data["dates"] : array();
        if(isset($element["onedateonly"]) && $element["onedateonly"]===true) {
            $txt=array($txt[0]);
        }
        $txt = implode("\n", $txt);
        // we used to make sure date/day were always on one line
        //$txt = implode("\n", str_replace(" ","~",$txt));
        if (strlen(trim($txt))) {
            $this->putTextAt($txt, array("replaceTilde"=>true, "offset"=>$offset, "box"=>$size, "fontsize"=>$fsize, "font"=>$ffamily, "colour"=>$colour,"align"=>$align));
        }
    }
    private function addBox($element,$content, $data) {
        global $evflogger;
        $evflogger->log("adding box");
        $colour = $this->getColour($element);
        $offset = $this->getOffset($element);
        $size = $this->getSize($element);
        $evflogger->log("offset ".json_encode($offset)." size ".json_encode($size)." colour ".json_encode($colour));
        $this->putBoxAt($offset,$size,$colour);
    }

    private function addQRCode($element, $content, $data) {
        global $evflogger;
        $evflogger->log("adding qr code");
        $offset = $this->getOffset($element);
        $size = $this->getSize($element);
        $link = isset($element["link"]) ? $element["link"] : "";
        if(strlen(trim($link))) {
            $evflogger->log("offset " . json_encode($offset) . " size " . json_encode($size));
            $this->putQRCodeAt($link, array("offset"=>$offset, "size"=>$size));
        }
    }

    private function addImage($element,$content, $data, $pictures) {
        $offset = $this->getOffset($element);
        $size = $this->getSize($element);
        $imageid = isset($element["file_id"]) ? $element["file_id"]:"";
        global $evflogger;
        $evflogger->log("image id is $imageid, size is ".json_encode($size));
        $evflogger->log(json_encode($pictures));
        if(isset($pictures[$imageid])) {
            $evflogger->log("image is available in pictures");
            $pic = $pictures[$imageid];
            $ext = $pic["file_ext"];
            if(isset($pic["path"])) {
                $path = $pic["path"];
            }
            else {
                $path = $this->template->getPath("picture",$imageid,$ext);
            }

            // correct width/height downwards according to ratio
            if(isset($element["ratio"])) {
                $ratio=floatval($element["ratio"]);
                $evflogger->log("ratio set: $ratio");
                if($ratio > 0.0) {
                    $rwidth = $size[1] * $ratio;
                    $rheight = $size[0] / $ratio;
                    $evflogger->log("ratio $rwidth,$rheight vs ".$size[0].",".$size[1]);

                    if($rwidth < $size[0]) {
                        $evflogger->log("adjusting width ".$size[0]." due to ratio to $rwidth");
                        $size[0] = $rwidth;
                    }
                    else if($rheight<$size[1]) {
                        $evflogger->log("adjusting height " . $size[1] . " due to ratio to $rheight");
                        $size[1]=$rheight;
                    }
                }
            }

            if(file_exists($path)) {
                $this->putImageAt($path, array("offset"=>$offset,"size"=>$size));
            }
        }
    }

    private function putAccIDAt($text, $options) {
        // the accreditation ID consists of a QR code and the AccID underneath it
        $style = array(
            'border' => 2,
            'vpadding' => 'auto',
            'hpadding' => 'auto',
            'fgcolor' => array(0, 0, 0),
            'bgcolor' => array(255,255,255),
            'module_width' => 1, // width of a single module in points
            'module_height' => 1 // height of a single module in points
        );

        $link=get_site_url(null, "/accreditation/$text","https");
        // QRCODE,H : QR-CODE Best error correction
        $this->pdf->write2DBarcode($link, 'QRCODE,H', $options["offset"][0], $options["offset"][1], $options["size"][0], $options["size"][1], $style, 'N');

        // put the text below, 2mm margin
        $options["offset"][1]=$options["offset"][1] + $options["size"][1];
        $this->putTextAt($text,$options);
    }

    private function putQRCodeAt($link, $options) {
        $style = array(
            'border' => 2,
            'vpadding' => 'auto',
            'hpadding' => 'auto',
            'fgcolor' => array(0, 0, 0),
            'bgcolor' => array(255,255,255),
            'module_width' => 1, // width of a single module in points
            'module_height' => 1 // height of a single module in points
        );

        // QRCODE,H : QR-CODE Best error correction
        $this->pdf->write2DBarcode($link, 'QRCODE,H', $options["offset"][0], $options["offset"][1], $options["size"][0], $options["size"][1], $style, 'N');
    }

    private function putTextAt($text, $options) {
        global $evflogger;
        $evflogger->log("putting text '$text' at ".json_encode($options));
        if(isset($options["colour"])) {
            $evflogger->log("setting fill colour to ".json_encode($options["colour"]));
            $this->pdf->SetTextColorArray($options["colour"]);
        }
        $this->pdf->setTextRenderingMode($stroke = 0, $fill = true, $clip = false);
        $x=0;
        $y=0;
        $width=PDFCreator::PDF_WIDTH;
        $height=PDFCreator::PDF_HEIGHT;
        if(isset($options["offset"])) {
            $x = $options["offset"][0];
            $y = $options["offset"][1];
            $width = $width - $x;
            $height = $height - $y;
        }
        if(isset($options["box"])) {
            $swidth = $options["box"][0];
            $sheight = $options["box"][1];
            $evflogger->log("width/height as set: $swidth, $sheight");
            if($swidth < $width) {
                $width = $swidth;
            }
            if($sheight < $height) {
                $height=$sheight;
            }
        }

        $font = isset($options["font"]) ? $options["font"] : "Helvetica";
        if($font!= "Helvetica") {
            $this->addFont($font);
        }
        $fontsize = $this->determineFontSize($text, isset($options["fontsize"]) ? $options["fontsize"] : 20, $font);
        $this->addFont($font);
        $this->pdf->SetFontSize($fontsize * PDFCreator::PDF_PXTOPT);
        $lineheight = $this->pdf->getCellHeight($this->pdf->GetFontSize());
        $fontwidth = $this->getTextWidth($text);
        $align=isset($options["align"]) ? $options["align"]: '';
        //$this->pdf->Rect($x, $y - 0.5, $fontwidth,$lineheight, "B",array("all"=>0.5),array(128,0,128));

        $lines = $this->breakText($text, $width);
        $evflogger->log("lines is ".json_encode($lines));
        $maxlines = intval(floor($height / $lineheight))+1; // allow the last line to overflow (a bit)

        // Print at least 1 line, even if it overflows. 
        // This allows us to set a very small height and make sure exactly one line is printed
        if($maxlines < 1) $maxlines=1; 

        if($maxlines < sizeof($lines)) {
            // cut off lines we cannot print
            $lines = array_slice($lines,0,$maxlines);
        }

        $offset = -0.5;
        foreach($lines as $line) {
            $this->pdf->SetXY($x,$y+$offset);
            $offset+=$lineheight;
            if(isset($options["replaceTilde"]) && $options["replaceTilde"]) {
                $line = str_replace("~"," ",$line); // a cheap version of non-breaking-spaces
            }
            $this->pdf->Cell($width, $lineheight, $txt=$line, $border=0, $ln=0, $align, $fill=false, $link='', $stretch=0, $ignore_min_height=false, $calign='T', $valign='T');
        }
    }

    private function determineFontSize($text, $size, $font) {
        // because fontsize is concerned with the height of the font and we want to 
        // steer on the width of the font, we need to convert the actual text
        // to a font-size that matches the expected width as configured for the
        // default Helvetica font
        if($font == "Helvetica") return $size;

        $this->pdf->SetFontSize($size);
        $this->pdf->SetFont("helvetica");
        $textwidthhelvetica = $this->getTextWidth($text);

        $newsize = $size;
        $this->addFont($font);
        global $evflogger;
        while(true) {
            $evflogger->log("determining font size using $newsize");
            $this->pdf->SetFontSize($newsize);
            $this->addFont($font);
            $fontwidth = $this->getTextWidth($text);
            $evflogger->log("text says $fontwidth vs $textwidthhelvetica");

            if(abs($fontwidth - $textwidthhelvetica) < 1) {
                $evflogger->log("returning $newsize");
                return $newsize;
            }

            $widthratio = $textwidthhelvetica/$fontwidth;            
            $newsize = $newsize * $widthratio;
            $evflogger->log("retrying using $newsize");
        }
        return $newsize;
    }

    private function getTextWidth($txt) {
        $characters = preg_split('//u', $txt, null, PREG_SPLIT_NO_EMPTY);
        $width=0.0;
        global $evflogger;
        foreach($characters as $c) {
            $w=floatval($this->pdf->GetCharWidth(ord($c)));
            $width += $w;
            $evflogger->log("character $c has width $w / $width");
        }
        return $width;
    }

    private function breakText($text, $width) {
        //global $evflogger;
        //$evflogger->log("breaking text '$text' based on width $width");
        // break the text into pieces based on whitespace, comma, dot and hyphen separation
        $tokens=$this->breakTextIntoTokens($text);
        //$evflogger->log("tokens is ".json_encode($tokens));
        $pdf=$this->pdf;
        $sizes = array_map(function($item) use ($pdf) {
            $letters=preg_split('//u', $item, null, PREG_SPLIT_NO_EMPTY);
            $size=0;
            for($i=0;$i<sizeof($letters);$i++) {
                $size+=$pdf->GetCharWidth(ord($letters[$i]));
            }
            return $size;
        }, $tokens);
        //$evflogger->log("sizes are ".json_encode($sizes));

        $lws=$this->pdf->GetCharWidth(" ");
        $lines=array();
        $current=0;
        $line="";
        for($i=0;$i<sizeof($tokens);$i++) {
            $token=$tokens[$i];
            $size = $sizes[$i];
            if($token == "\n") {
                //$evflogger->log("encountered line break");
                // line break, start a new line
                if(strlen($line)) {
                    $lines[] =$line;
                }
                $line="";
                $current=0;
            }
            else if(($current+($current>0?$lws:0)+$size) > $width) {
                //$evflogger->log("token $token exceeds width, creating new line");
                // new line
                if(strlen($line)) {
                    $lines[]=$line;
                }
                $line=$token;
                $current=$size;
            }
            else {
                //$evflogger->log("token $token put on existing line");
                if(mb_strlen($line)>0) {
                    $line.=" ";
                    $current+=$lws;
                }
                $line.=$token;
                $current+=$size;
            }
        }
        if(mb_strlen($line)>0) {
            $lines[]=$line;
        }
        return $lines;
    }

    private function breakTextIntoTokens($text) {
        //global $evflogger;
        // we could do a complicated regexp, but instead we just run over the text
        $characters = preg_split('//u', $text, null, PREG_SPLIT_NO_EMPTY);
        $totalsize = sizeof($characters);
        $retval=array();
        $current="";
        for($i=0;$i<$totalsize;$i++) {
            $c = $characters[$i];
            $n = ($i < ($totalsize-1)) ? $characters[$i+1]:"\n";
            $isspace = preg_match('/\s/u', $c);
            $ispunc = preg_match('/\p{P}/u', $c);
            $nextspace = preg_match('/\s/u', $n);
            $islinebreak = (mb_ord($c) == 10);
            //$evflogger->log("character '$c' ".mb_ord($c)." says ".json_encode(array($isspace,$ispunc,$nextspace,$islinebreak)));
            if($islinebreak) {
                //$evflogger->log("encountering line break");
                // we must keep the line breaks in order to actually break lines later on
                if (mb_strlen($current) > 0) {
                    $retval[] = $current;
                    $current = "";
                }
                $retval[]="\n";
            }
            else if($isspace) {
                if(mb_strlen($current) > 0) {
                    $retval[]=$current;
                    $current="";
                }
                // skip any whitespace, it is converted to a single space
            }
            // punctuation should always be followed by a space, or else
            // it is part of a token (@-sign, dots in addresses, etc.)
            else if($ispunc && $nextspace) {
                // punctuation belongs to the current value
                if(mb_strlen($current)>0) {
                    $current.=$c;
                }
                else {
                    $current=$c;
                }
                $retval[]=$current;
                $current="";
            }
            else {
                $current.=$c;
            }
        }
        if(mb_strlen($current)>0) {
            $retval[]=$current;
        }
        return $retval;
    }


    private function putBoxAt($offset, $size, $colour) {
        $this->pdf->Rect($offset[0], $offset[1], $size[0],$size[1], "F",array("all"=>0),$colour);
    }

    private function putImageAt($path, $options) {
        global $evflogger;
        $evflogger->log("putting image at $path");

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if(!in_array($ext,array("png","jpg","jpeg","gif"))) return;

        list($width,$height) =getimagesize($path);
        $x=0;
        $y=0;
        if (isset($options["offset"])) {
            $x= $options["offset"][0];
            $y= $options["offset"][1];
        }
        if (isset($options["size"])) {
            $swidth = $options["size"][0];
            $sheight = $options["size"][1];
            if ($swidth < $width) {
                $evflogger->log("adjusting image width $width to smaller width $swidth based on size");
                $width = $swidth;
            }
            if ($sheight < $height) {
                $evflogger->log("adjusting image height $height to smaller height $sheight based on size");
                $height = $sheight;
            }
        }
        $evflogger->log("width $width, height $height");
        $this->pdf->setJPEGQuality(90);
        $this->pdf->Image($path,$x, $y, $width, $height, $type='', $link='', $align='', $resize=true, $dpi=600, $palign='', $ismask=false, $imgmask=false, $border=0, $fitbox=false, $hidden=false, $fitonpage=false, $alt=false, $altimgs=array());
    }

    private function getColour($element) {
        //global $evflogger;
        $colour="#000000";
        if(isset($element["style"])) {
            //$evflogger->log("style set on element");
            if(isset($element["style"]["color"])) {
                $colour = $element["style"]["color"];
                //$evflogger->log("getting colour from color style: $colour");
            }
            if(isset($element["style"]["backgroundColor"])) {
                $colour = $element["style"]["backgroundColor"];
                //$evflogger->log("getting colour from backgroundcolor: $colour");
            }
        }
        if(strpos($colour,'#') === 0) {
            $colour = substr($colour,1);
        }
        //$evflogger->log("converting $colour");
        if(strlen($colour) !== 6 && strlen($colour) !== 3) {
            $colour="000000";
        }
        $r=hexdec($colour[0]);
        $g=hexdec($colour[1]);
        $b=hexdec($colour[2]);
        if(strlen($colour) == 6) {
            $r=hexdec(substr($colour,0,2));
            $g=hexdec(substr($colour,2,2));
            $b=hexdec(substr($colour,4,2));
        }
        else {
            $r = 16*$r + $r;
            $g = 18*$g + $g;
            $b=16*$b+$b;
        }
        //$evflogger->log("returning $r, $g, $b");
        return array($r,$g,$b);
    }

    private function getSize($element) {
        global $evflogger;
        $x = 0;
        $y = 0;
        if (isset($element["style"])) {
            if (isset($element["style"]["height"])) $y = floatval($element["style"]["height"]);
            if (isset($element["style"]["width"])) $x = floatval($element["style"]["width"]);
        }
        if($x === 0 && $y === 0) return null;

        if(isset($element["ratio"])) {
            $ratio = floatval($element["ratio"]);

            if($x < 1 && $y > 1) {
                $evflogger->log("adjusting size width due to ratio $x, $y, $ratio");
                $x = $y * $ratio;
            }
            if($y < 1 && $x > 1 && $ratio>0.0) {
                $evflogger->log("adjusting size height due ro ratio $x, $y, $ratio");
                $y = $x / $ratio;
            }
        }

        $x = floatval($x * PDFCreator::PDF_WIDTH / PDFCreator::APP_WIDTH);
        $y = floatval($y * PDFCreator::PDF_HEIGHT/ PDFCreator::APP_HEIGHT);
        $evflogger->log("element ".json_encode($element)." returns size ".json_encode(array($x,$y)));
        return array($x, $y);
    }

    private function getOffset($element) {
        $x=0;
        $y=0;
        if(isset($element["style"])) {
            if(isset($element["style"]["top"])) $y = intval($element["style"]["top"]);
            if(isset($element["style"]["left"])) $x = intval($element["style"]["left"]);
        }

        $x = floatval($x * PDFCreator::PDF_WIDTH  / PDFCreator::APP_WIDTH);
        $y = floatval($y * PDFCreator::PDF_HEIGHT/ PDFCreator::APP_HEIGHT);
        return array($x,$y);
    }

    private function getFontSize($element) {
        if(isset($element["style"]) && isset($element["style"]["fontSize"])) {
            return intval($element["style"]["fontSize"]);
        }
        return 20;
    }    

    private function getFontFamily($element) {
        $family="Helvetica";
        if(isset($element["style"]) && isset($element["style"]["fontFamily"])) {
            $family = $element["style"]["fontFamily"];
            if(!in_array($family, array_keys(PDFCreator::PDF_FONTS))) {
                $family="Helvetica";
            }
        }
        return $family;
    }    

    private function getAlign($element) {
        $align='';
        if(isset($element["style"]) && isset($element["style"]['textAlign'])) {
            $ta = $element["style"]['textAlign'];
            switch($ta) {
            case 'center': $align='C'; break;
            case 'right': $align='R'; break;
            case 'justify': $align='J'; break;
            default: $align=''; break;
            }
        }
        return $align;
    }

    private function getFontFile($family) {
        $ffile = __DIR__."/$family.ttf";
        if(!file_exists($ffile)) {
            $ffile = __DIR__."/arial.ttf";
        }
        return $ffile;
    }


    private function createWatermark($path) {
        global $evflogger;
        $evflogger->log("loading image from path $path to create watermark");
        $ext=pathinfo($path, PATHINFO_EXTENSION);
        if($ext == "jpg" || $ext == "jpeg") {
            $img = imagecreatefromjpeg($path);
        }
        else if($ext == "png") {
            $img = imagecreatefrompng($path);
        }
        else {
            return null;
        }
        $w=imagesx($img);
        $h=imagesy($img);
        $evflogger->log("width/height $w,$h");
        $fname= tempnam(null,"phid");
        $evflogger->log("output file is $fname");
        if(file_exists($fname)) {
            $evflogger->log("allocating colour");
            $text_color = imagecolorallocate($img, 196,196,196);
            $ffile=$this->getFontFile("arial");
            $evflogger->log("writing text ".$this->event->event_name);
            $fsize = 19; // we start with a font size decrement
            $rotation = 0;
            $wdiff=$w+1;
            $hdiff=$h+1;
            while($wdiff > $w || $hdiff > $h) {
                $fsize-=1;
                $box = imagettfbbox($fsize,$rotation,$ffile, $this->event->event_name);            
                $evflogger->log("box is ".json_encode($box));
                $maxx=max(array($box[0], $box[2], $box[4], $box[6]));
                $minx = min( array($box[0], $box[2], $box[4], $box[6]) ); 
                $maxy=max( array($box[1], $box[3], $box[5], $box[7]) ); 
                $miny=min( array($box[1], $box[3], $box[5], $box[7]) ); 
                $wdiff = $maxx - $minx;
                $hdiff = $maxy - $miny;
            }
            $x = ($w - ($maxx - $minx))/2.0;
            $y = $h - ($maxy - $miny)-2;
            $evflogger->log("position is $x,$y");
            imagettftext($img, $fsize, $rotation, $x, $y, $text_color, $ffile, $this->event->event_name);
            $evflogger->log("storing image");
            imagejpeg($img, $fname, 90);
            imagedestroy($img);

            // determine an output name, which needs to end with the JPG extension to
            // allow the putImageAt method to read it (which will not accept files
            // without extensions for security)
            // It would be silly if this file would exist, but better safe than sorry
            $outputname = $fname.".jpg";
            $outputindex=1;
            while(file_exists($outputname)) {
                $outputname = $fname."_".$outputindex.".jpg";
                $outputindex+=1;
            }
            rename($fname, $outputname);
            return $outputname;
        }
        return null;
    }
}