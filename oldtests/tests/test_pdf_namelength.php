<?php

namespace EVFTest;

class Test_PDF_NameLength extends BaseTest {
    public $disabled=false;

    public function init()
    {
        parent::init();
    }

    private function createModels() {
        $fencer=new \EVFRanking\Models\Fencer();
        $fencer->fencer_surname="Test With A Very Long Surname";
        $fencer->fencer_firstname="I.Am Also A Very Long Firstname";
        $country=new \EVFRanking\Models\Country();
        $country->country_name = "Testonia";
        $country->country_abbr="TST";
        $template=new \EVFRanking\Models\AccreditationTemplate();
        $event=new \EVFRanking\Models\Event();
        $event->event_name="Test Event";
        $accreditation=new \EVFRanking\Models\Accreditation();
        $accreditation->data=json_encode(array(
            "firstname" => $fencer->fencer_firstname,
            "lastname" => $fencer->fencer_surname,
            "organisation" => $country->country_name,
            "country" => $country->country_abbr,
            "roles" => array("Athlete WS4", "Team Armourer", "Head of Delegation", "Referee"),
            "dates" => array("SAT 12","SUN 21"),
            "accid" => "783-242",
            "created" => 1000,
            "modified" => 2000));

        $pdf = new PDFCreatorTestName();
        $id=uniqid();
        $fname = tempnam(null,"pdftest");
        if(file_exists($fname)) {
            @unlink($fname);
        }

        return array($fencer,$event, $country, $accreditation, $template, $pdf,$fname);
    }

    private function checkHash($expected, $fname) {
        global $evflogger;
        $evflogger->log("checking hash of $fname");
        if(file_exists($fname)) {
            $hash = hash_file("md5", $fname);
            if($hash === $expected) {
                @unlink($fname);
            }
            $this->assert($hash === $expected, "hash fails: '$hash' vs '$expected' ($fname)");
        }
        else {
            $this->assert(file_exists($fname), "output file missing");
        }

    }

    public function test_name()
    {
        $content = array(
            "elements" => array(
                array(
                    "type"=>"name",
                    "name" => "first",
                    "style"=>array(
                        "width"=>270,
                        "height"=>29,
                        "left"=>19,
                        "top"=>159,
                        "fontSize"=>17,
                        "fontStyle"=>"bold",
                        "fontFamily"=>"Sans",
                        "zIndex"=>6,
                        "color"=>"#003b76"),
                    "hasFontSize"=>true,
                    "hasColour"=>true,
                    "resizeable"=>true,
                    "index"=>143656,
                    "color2"=>"#003b76"
                    ),
                array(
                    "type"=>"name",
                    "name" => "last",
                    "style"=>array(
                        "width"=>270,
                        "height"=>29,
                        "left"=>35,
                        "top"=>200,
                        "fontSize"=>17,
                        "fontStyle"=>"bold",
                        "fontFamily"=>"Sans",
                        "zIndex"=>6,
                        "color"=>"#003b76"),
                    "hasFontSize"=>true,
                    "hasColour"=>true,
                    "resizeable"=>true,
                    "index"=>143656,
                    "color2"=>"#003b76"
                )
            )
        );

        list($fencer, $event, $country, $accreditation, $template, $pdf, $fname) = $this->createModels();
        $template->content = json_encode($content);
        $pdf->create($fencer, $event, $template, $country, $accreditation, $fname);
        $this->checkHash("639ce581a097d752b2fb5c86ce62233d", $fname);
    }
}

class TestTCPDFName extends \TCPDF {
    public function setFileID($fid) {
        $this->file_id=$fid;
    }
}

class PDFCreatorTestName extends \EVFRanking\Util\PDFCreator {
    protected function instantiatePDF() {
        $pdf = new TestTCPDFName("P", "mm", "A4", true, 'UTF-8', false);
        $pdf->setFileID(md5("This is a static string to ensure all PDFs are equal"));
        return $pdf;
    }
}