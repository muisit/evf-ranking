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
            "firstname" => $fencer->fencer_firstname,
            "lastname" => $fencer->fencer_surname,
            "organisation" => $country->country_name,
            "country" => $country->country_abbr,
            "roles"=> array("Athlete WS4", "Team Armourer", "Head of Delegation", "Referee"),
            "dates" => array("SAT 12","SUN 21"),
            "accid" => "783-242",
            "created" => 1000,
            "modified" => 2000));

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
                "test" => __DIR__."/../support/fish.jpg",
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
        $this->checkHash("0b6716341e0ab3409654a21b265e13ec", $fname);
    }

    public function test_case_20220216() {
        $content = array(
        "elements" => array(
            array(
                "type"=>"photo",
                "style"=>array(
                    "left"=>291,
                    "top"=>159,
                    "width"=>101.11111111111111,
                    "height"=>130,
                    "zIndex"=>1),
                "ratio"=>0.7777777777777778,
                "hasRatio"=>true,
                "index"=>226878,
                "test" => __DIR__."/../support/fish.jpg"
            ),
            array(
                "type"=>"name",
                "text"=>"NOSUCHNAME, nosuchperson",
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
                "name"=>"last",
                "color2"=>"#003b76"),
            array(
                "type"=>"name",
                "text"=>"NOSUCHNAME, nosuchperson",
                "style"=>array(
                    "width"=>270,
                    "height"=>27,
                    "left"=>19,
                    "top"=>187,
                    "fontSize"=>14,
                    "fontStyle"=>"bold",
                    "fontFamily"=>"Sans",
                    "zIndex"=>6,
                    "color"=>"#003b76"),
                "hasFontSize"=>true,
                "hasColour"=>true,
                "resizeable"=>true,
                "index"=>112980,
                "name"=>"first",
                "color2"=>"#003b76"),
            array(
                "type"=>"country",
                "text"=>"EUR",
                "style"=>array(
                    "width"=>270,
                    "height"=>44,
                    "left"=>18,
                    "top"=>244,
                    "fontSize"=>30,
                    "fontStyle"=>"bold",
                    "fontFamily"=>"Sans",
                    "zIndex"=>6,
                    "color"=>"#003b76"),
                "hasFontSize"=>true,
                "hasColour"=>true,
                "resizeable"=>true,
                "index"=>793172,
                "color2"=>"#003b76"),
            array(
                "type"=>"img",
                "style"=>array(
                    "left"=>20,
                    "top"=>391,
                    "width"=>101.8018018018018,
                    "height"=>100,
                    "zIndex"=>3),
                "hasRatio"=>true,
                "ratio"=>1.018018018018018,
                "file_id"=>"logo",
                "index"=>567581),
            array(
                "type"=>"box",
                "style"=>array(
                    "left"=>19,
                    "top"=>305,
                    "width"=>270,
                    "height"=>60,
                    "backgroundColor"=>"#003b76",
                    "zIndex"=>1),
                "resizeable"=>true,
                "hasBackgroundColour"=>true,
                "index"=>239660,
                "backgroundColor2"=>"#003b76"),
            array(
                "type"=>"roles",
                "style"=>array(
                    "width"=>260,
                    "height"=>58,
                    "left"=>22,
                    "top"=>306,
                    "fontSize"=>18,
                    "fontStyle"=>"bold",
                    "fontFamily"=>"Sans",
                    "zIndex"=>1,
                    "color"=>"#ffffff"),
                "hasFontSize"=>true,
                "hasColour"=>true,
                "resizeable"=>true,
                "index"=>34780,
                "color2"=>"#ffffff"),
// freshly generated qr code causes hash mismatch
//            array(
//                "type"=>"qr",
//                "style"=>array(
//                    "left"=>165,
//                    "top"=>391,
//                    "width"=>100,
//                    "height"=>100,
//                    "zIndex"=>2),
//                "resizeable"=>true,
//                "hasRatio"=>true,
//                "ratio"=>1,
//                "index"=>887448,
//                "link"=>"https:\/\/event.com"),
            array(
                "type"=>"box",
                "style"=>array(
                    "left"=>297,
                    "top"=>389,
                    "width"=>100,
                    "height"=>100,
                    "backgroundColor"=>"#003b76",
                    "zIndex"=>1),
                "resizeable"=>true,
                "hasBackgroundColour"=>true,
                "index"=>257294,
                "backgroundColor2"=>"#003b76"),
            array(
                "type"=>"dates",
                "style"=>array(
                    "width"=>100,
                    "height"=>63,
                    "left"=>297,
                    "top"=>413,
                    "fontSize"=>19,
                    "fontStyle"=>"bold",
                    "fontFamily"=>"Sans",
                    "zIndex"=>1,
                    "color"=>"#ffffff"),
                "hasFontSize"=>true,
                "hasColour"=>true,
                "resizeable"=>true,
                "index"=>609550,
                "color2"=>"#ffffff"),
            array(
                "type"=>"cntflag",
                "style"=>array(
                    "left"=>302,
                    "top"=>305,
                    "width"=>80,
                    "height"=>60,
                    "zIndex"=>2),
                "hasRatio"=>true,
                "ratio"=>1.3333333333333333,
                "index"=>282564),
            array(
                "type"=>"text",
                "text"=>"Test Event",
                "style"=>array(
                    "left"=>89,
                    "top"=>43,
                    "fontSize"=>40,
                    "zIndex"=>1,
                    "color"=>"#000000"),
                "hasFontSize"=>true,
                "hasColour"=>true,
                "resizeable"=>true,
                "index"=>262897)),
        "roles"=>array("0"),
        "pictures"=>array(array(
            "width"=>339,
            "height"=>333,
            "file_ext"=>"png",
            "path" => __DIR__."/../support/logo.png",
            "file_id"=>"logo",
            "file_name"=>"accrbadge_evflogo.png",
            "type"=>"img"))
        );        

        list($fencer, $event, $country, $accreditation, $template, $pdf, $fname) = $this->createModels();
        $template->content = json_encode($content);
        $pdf->create($fencer, $event, $template, $country, $accreditation, $fname);
        $this->checkHash("6cd59003869772babbfb8273a047863f", $fname);
    }

    public function test_image4() {
        // this is a test case to see if the actual production logo prints on badges. This appeared
        // to be a problem 2022-02-16
        list($fencer, $event, $country, $accreditation, $template, $pdf, $fname) = $this->createModels();
        $content = array(
            "pictures" => array(
                array(
                    "file_id" => "logo",
                    "file_ext" => "png",
                    "path" => __DIR__."/../support/logo.png"
                )
            ),
            "elements" => array()
        );

        $content["elements"][]=array(
                "type" => "img",
                "file_id" => "logo",
                "hasRatio"=>true,
                "ratio"=>1.018018018018018,
                "style" => array("left"=>20,"top"=>391,"width"=>101.8018018018018,"height"=>100,"zIndex"=>2 )
            );

        $template->content = json_encode($content);
        $pdf->create($fencer, $event, $template, $country, $accreditation, $fname);
        $this->checkHash("755981e7de0c028ba8a70ab611815001", $fname);
    }


    public function test_image3() {
        list($fencer, $event, $country, $accreditation, $template, $pdf, $fname) = $this->createModels();
        $content = array(
            "pictures" => array(
                array(
                    "file_id" => "fish",
                    "file_ext" => "png",
                    "path" => __DIR__."/../support/fish.png"
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
        $this->checkHash("7e607f8f1d71dc88c4250952157ac7d4", $fname);
    }


    public function test_image2() {
        list($fencer, $event, $country, $accreditation, $template, $pdf, $fname) = $this->createModels();
        $content = array(
            "pictures" => array(
                array(
                    "file_id" => "fish",
                    "file_ext" => "jpg",
                    "path" => __DIR__."/../support/fish.jpg"
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
        $this->checkHash("3f2232c316f4256bbcabbd826e922e63", $fname);
    }


    public function test_image() {
        list($fencer, $event, $country, $accreditation, $template, $pdf, $fname) = $this->createModels();
        $template->content = json_encode(array(
            "pictures" => array(
                array(
                    "file_id"=> "fish",
                    "file_ext"=> "jpg",
                    "path" => __DIR__ . "/../support/fish.jpg"
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
        $this->checkHash("b69430705f92d56242ae54c09ef631bd", $fname);
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
        $this->checkHash("2a9be03d9f70dbe8ad4f8bf0532e204a", $fname);
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
        $this->checkHash("cd4cfd4839b2e0d3cf1a945602932999", $fname);
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
        $this->checkHash("f5234b1140d3b42b1095134a873f1437", $fname);
    }

    public function test_fonts1() {
        list($fencer, $event, $country, $accreditation, $template, $pdf, $fname) = $this->createModels();
        $template->content = array(
            "elements" => array()
        );
        $offset=20;
        foreach(array(
            "Courier", "Courier Bold", "Courier Italic","Courier Bold Italic",
            "Helvetica","Helvetica Bold","Helvetica Italic","Helvetica Bold Italic",
            "Times","Times Bold","Times Italic","Times Bold Italic",
            "DejaVuSans","DejaVuSans Bold","DejaVuSans Italic","DejaVuSans Bold Italic",
        ) as $fontname) {
            $element =  array(
                "type" => "text",
                "text" => "This is a test in $fontname",
                "style" => array(
                    "left" => 20,
                    "top" => $offset,
                    "width" => 400,
                    "height" => 100,
                    "color" => "#1234ab",
                    "fontSize"=>"10",
                    "fontFamily" => $fontname
                )
            );
            $template->content["elements"][]=$element;
            $offset+=32;    
        }
        $template->content = json_encode($template->content);
        $pdf->create($fencer, $event, $template, $country, $accreditation, $fname);
        $this->checkHash("0c4995622c76036e3f82d444647a9b1f", $fname);
    }

    public function test_fonts2() {
        list($fencer, $event, $country, $accreditation, $template, $pdf, $fname) = $this->createModels();
        $template->content = array(
            "elements" => array()
        );
        $offset=20;
        foreach(array(
            "DejaVuSans Condensed","DejaVuSans Condensed Bold","DejaVuSans Condensed Italic","DejaVuSans Condensed Bold Italic",
            "DejaVuSans Mono","DejaVuSans Mono Bold","DejaVuSans Mono Italic","DejaVuSans Mono Bold Italic",
            "FreeSans","FreeSans Bold","FreeSans Italic","FreeSans Bold Italic",
            "FreeMono","FreeMono Bold","FreeMono Italic","FreeMono Bold Italic",
        ) as $fontname) {
            $element =  array(
                "type" => "text",
                "text" => "This is a test in $fontname",
                "style" => array(
                    "left" => 20,
                    "top" => $offset,
                    "width" => 400,
                    "height" => 100,
                    "color" => "#1234ab",
                    "fontSize"=>"10",
                    "fontFamily" => $fontname
                )
            );
            $template->content["elements"][]=$element;
            $offset+=32;    
        }
        $template->content = json_encode($template->content);
        $pdf->create($fencer, $event, $template, $country, $accreditation, $fname);
        $this->checkHash("b89cf0ff9fb88e6be93f593c7488588d", $fname);
    }

    public function test_fonts3() {
        list($fencer, $event, $country, $accreditation, $template, $pdf, $fname) = $this->createModels();
        $template->content = array(
            "elements" => array()
        );
        $offset=20;
        foreach(array(
            "FreeSerif","FreeSerif Bold","FreeSerif Italic","FreeSerif Bold Italic",
            "Eurofurence","Eurofurence Bold","Eurofurence Italic","Eurofurence Bold Italic",
//            "Eurofurence Light","Eurofurence Light Italic",
        ) as $fontname) {
            $element =  array(
                "type" => "text",
                "text" => "This is a test in $fontname",
                "style" => array(
                    "left" => 20,
                    "top" => $offset,
                    "width" => 400,
                    "height" => 100,
                    "color" => "#1234ab",
                    "fontSize"=>"10",
                    "fontFamily" => $fontname
                )
            );
            $template->content["elements"][]=$element;
            $offset+=32;    
        }
        $template->content = json_encode($template->content);
        $pdf->create($fencer, $event, $template, $country, $accreditation, $fname);
        $this->checkHash("fddeb41513803f742b8abea19ce7526c", $fname);
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
        $this->checkHash("77da89e769fd1052eb85008b24632c8e", $fname);
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
        $this->checkHash("e70cce114b412d99e29698536367bbdd",$fname);
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