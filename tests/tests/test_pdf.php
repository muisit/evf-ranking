<?php

namespace EVFTest;

class Test_PDF extends BaseTest {
    public $disabled=false;

    public function init() {
        parent::init();
    }

    private function createModels() {
        $fencer=new \EVFRanking\Models\Fencer();
        $fencer->fencer_surname="Test";
        $fencer->fencer_firstname="I.Am";
        $country=new \EVFRanking\Models\Country();
        $country->country_name = "Testonia";
        $country->country_abbr="TST";
        $template=new \EVFRanking\Models\AccreditationTemplate();
        $event=new \EVFRanking\Models\Event();
        $event->event_name="Test Event";
        $accreditation=new \EVFRanking\Models\Accreditation();
        $accreditation->data=json_encode(array(
            "created" => 1000,
            "modified" => 2000
        ));

        $pdf = new PDFCreatorTest();
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
            $this->assert($hash === $expected, "hash fails: '$hash' vs '$expected'");
        }
        else {
            $this->assert(file_exists($fname), "output file missing");
        }

    }

    public function test_photo() {
        list($fencer, $event, $country, $accreditation, $template, $pdf, $fname) = $this->createModels();
        $content = array(
            "elements" => array()
        );

        $vals=array(
            array(10,10,100),
            array(20,120,50),
            array(150,10,150),
            array(150,200,120)
        );
        for($i=0;$i<sizeof($vals);$i++) {
            list($x,$y,$w) = $vals[$i];
            $content["elements"][]=array(
                "type" => "photo",
                "test" => __DIR__."/fish.jpg",
                "style" => array(
                    "left" => $x,
                    "top" => $y,
                    "width" => $w,
                    "height" => 100000
                )
            );
        }

        $template->content = json_encode($content);
        $pdf->create($fencer, $event, $template, $country, $accreditation, $fname);
        $this->checkHash("297c2eb5f9fcdc25a66c14d4d4ce1a51", $fname);
    }

    public function test_image2() {
        list($fencer, $event, $country, $accreditation, $template, $pdf, $fname) = $this->createModels();
        $content = array(
            "pictures" => array(
                array(
                    "file_id" => "fish",
                    "file_ext" => "jpg",
                    "path" => __DIR__."/fish.jpg"
                )
            ),
            "elements" => array()
        );

        $vals=array(
            array(10,10,100),
            array(20,120,50),
            array(150,10,150),
            array(150,200,120)
        );
        for($i=0;$i<sizeof($vals);$i++) {
            list($x,$y,$w) = $vals[$i];
            $content["elements"][]=array(
                "type" => "img",
                "file_id" => "fish",
                "ratio" => 1.5081967213114753,
                "style" => array(
                    "left" => $x,
                    "top" => $y,
                    "width" => $w,
                    "height" => 100000
                )
            );
        }

        $template->content = json_encode($content);
        $pdf->create($fencer, $event, $template, $country, $accreditation, $fname);
        $this->checkHash("d354d504998cafdc09f27ed26341ab2d", $fname);
    }


    public function test_image() {
        list($fencer, $event, $country, $accreditation, $template, $pdf, $fname) = $this->createModels();
        $template->content = json_encode(array(
            "pictures" => array(
                array(
                    "file_id"=> "fish",
                    "file_ext"=> "jpg",
                    "path" => __DIR__ . "/fish.jpg"
                )
            ),
            "elements" => array(
                array(
                    "type" => "img",
                    "file_id"=> "fish",
                    "ratio"=> 1.5081967213114753,
                    "style" => array(
                        "left" => 20,
                        "top" => 20,
                        "width" => 400,
                        "height" => 100
                    )
                )
            )
        ));

        $pdf->create($fencer, $event, $template, $country, $accreditation, $fname);
        $this->checkHash("28d99c3e995bc12bfe6fdafd630012b4", $fname);
    }

    public function test_text3() {
        list($fencer, $event, $country, $accreditation, $template, $pdf, $fname) = $this->createModels();
        $content = array("elements" => array());
        $vals=array(
            array(10,10,200,40,20),
            array(250,10,150,40,30),
            array(20,120,40,200,15),
            array(80,120,40,300,20),
            array(140,120,40,300,30),
            array(200,120,40,300,10)
        );
        $colour="#888";
        for($i=0;$i<sizeof($vals);$i++) {
            list($x,$y,$w,$h,$fs) = $vals[$i];

            $content["elements"][]=array(
                "type" => "text",
                "text" => "This is a test with a lot of lines to see if this breaks up correctly. Add some lorem ipsum! And dolor, sit, amet, with - punctuations (sometimes) and an@email.address. Yes!",
                "style" => array(
                    "left" => $x,
                    "top" => $y,
                    "width" => $w,
                    "height" => $h,
                    "color" => $colour,
                    "fontSize" => $fs
                )
            );
        }

        $template->content = json_encode($content);
        $pdf->create($fencer, $event, $template, $country, $accreditation, $fname);
        $this->checkHash("31066d6aa7dd23902820633edd6e94ff", $fname);
    }

    
    public function test_text2() {
        list($fencer, $event, $country, $accreditation, $template, $pdf, $fname) = $this->createModels();
        $content = array("elements" => array());
        $lineheight=20;
        for($i=0;$i<10;$i++) {
            $fontsize=12 + $i*4;
            $lineheight+=1.4*$fontsize;
            $colour = "#".dechex(255 - ($i*10)).dechex(128+($i*2)).dechex(128);
            $content["elements"][]=array(
                "type" => "text",
                "text" => "This is a test",
                "style" => array(
                    "left" => 20+10*$i,
                    "top" => $lineheight,
                    "width" => 400,
                    "height" => 100,
                    "color" => $colour,
                    "fontSize" => $fontsize
                )
            );
        }

        $template->content = json_encode($content);
        $pdf->create($fencer, $event, $template, $country, $accreditation, $fname);
        $this->checkHash("d9eb87c847315336f807b40ad85f7a11", $fname);
    }

    public function test_text() {
        list($fencer, $event, $country, $accreditation, $template, $pdf, $fname) = $this->createModels();
        $template->content = json_encode(array(
            "elements" => array(
                array(
                    "type" => "text",
                    "text" => "This is a test",
                    "style" => array(
                        "left" => 20,
                        "top" => 20,
                        "width" => 400,
                        "height" => 100,
                        "color" => "#1234ab",
                        "fontSize"=>"20"
                    )
                )
            )
        ));

        $pdf->create($fencer, $event, $template, $country, $accreditation, $fname);
        $this->checkHash("62139be125266dae9fc7470f9742ff1d", $fname);
    }

    public function test_box2() {
        list($fencer, $event, $country, $accreditation, $template, $pdf, $fname) = $this->createModels();
        $content = array("elements"=>array());
        for($w=5;$w<420;$w+=10) {
            for($h=5;$h<594;$h+=10) {
                $content["elements"][]=array(
                        "type" => "box",
                        "style" => array(
                            "left" => $w,
                            "top" => $h,
                            "width" => 9,
                            "height" => 9,
                            "backgroundColor" => "#".dechex(32 + ((256-32)*$w/420)). dechex(32 + ((256-32)*$h/594)). dechex(32+(256-32)*($w+$h)/(420+594))
                        )
                    );
            }
        }
        $template->content=json_encode($content);

        $pdf->create($fencer, $event, $template, $country, $accreditation, $fname);
        $this->checkHash("a7f7c2e75c13894a123b85b3fcc0885d", $fname);
    }

    public function test_box() {
        list($fencer, $event, $country, $accreditation, $template,$pdf,$fname) = $this->createModels();
        $template->content=json_encode(array(
            "elements" => array(
                array(
                    "type" => "box",
                    "style" =>array(
                        "left" => 20,
                        "top" => 20,
                        "width" => 100,
                        "height" => 100,
                        "backgroundColor" => "#1234ab"
                    )
                ),
                array(
                    "type" => "box",
                    "style" => array(
                        "left" => 200,
                        "top" => 20,
                        "width" => 220,
                        "height" => 100,
                        "backgroundColor" => "#aaffff"
                    )
                ),
                array(
                    "type" => "box",
                    "style" => array(
                        "left" => 0,
                        "top" => 394,
                        "width" => 100,
                        "height" => 200,
                        "backgroundColor" => "#ffff88"
                    )
                ),
                array(
                    "type" => "box",
                    "style" => array(
                        "left" => 210,
                        "top" => 490,
                        "width" => 200,
                        "height" => 100,
                        "backgroundColor" => "#f8f"
                    )
                )
            )
        ));

        $pdf->create($fencer, $event, $template, $country, $accreditation, $fname);
        $this->checkHash("8ba128430b7b1b770d91519fce1eff60",$fname);
    }
}

class TestTCPDF extends \TCPDF {
    public function setFileID($fid) {
        $this->file_id=$fid;
    }
}

class PDFCreatorTest extends \EVFRanking\Util\PDFCreator {
    protected function instantiatePDF() {
        $pdf = new TestTCPDF("P", "mm", "A4", true, 'UTF-8', false);
        $pdf->setFileID(md5("This is a static string to ensure all PDFs are equal"));
        return $pdf;
    }
}