<?php
require_once('../../ext-libraries/libraries/tcpdf/tcpdf.php');

function add_elements($pdf) {

    for($x=0;$x<200;$x+=50) {
        for($y=10;$y<50;$y+=50) {
            $color=array(intval($x/4), intval($y/4),128);
            $style = array('all' => array("width"=>0));
            $pdf->Rect($x, $y, 40, 20, 'F', $style, $color);
        }
    }   
}

$pdf = new \TCPDF("P", "mm", "A4", true, 'UTF-8', false);
$pdf->SetCreator("European Veteran Fencing");
        $pdf->SetAuthor('European Veteran Fencing');
        $pdf->SetTitle('Title');
        $pdf->SetSubject("Test");
        $pdf->SetKeywords('EVF, Accreditation');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(5,5,5);
        $pdf->SetAutoPageBreak(FALSE);
        $pdf->setImageScale(1.25);
        $pdf->setFontSubsetting(true);
        $pdf->SetDefaultMonospacedFont('courier');
        $pdf->SetFont('dejavusans', '', 14, '', true);
        $pdf->AddPage();

//add_elements($pdf);

// A4: 595.276, 841.890, 595.276/72 = 8.27" * 25.44mm = 210mm  (0.3527778)
// 841.890 * 0.3527778 = 297mm
// unit is set in mm when creating the PDF above
$pdf->Rect(0,0,209, 296, "F", array("all"=>0), array(128,255,255));
$pdf->Output(__DIR__."/test.pdf", 'F');        