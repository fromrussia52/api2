<?php

require_once '../api2/vendor/autoload.php';

// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', true);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Юрий');
$pdf->SetTitle('Титульник');
$pdf->SetSubject('Тема');
$pdf->SetKeywords('TCPDF, PDF, example, test, guide');

// set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, 'Пример с картинкой', 'Большая картинка');

// set header and footer fonts
$pdf->setHeaderFont(['dejavusans', '', PDF_FONT_SIZE_MAIN]);
$pdf->setFooterFont(['dejavusans', '', PDF_FONT_SIZE_DATA]);

// set default monospaced font
$pdf->SetDefaultMonospacedFont('dejavusans');

// set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, 30, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// set image scale factor
//$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set some language-dependent strings (optional)
require_once('../api2/vendor/tecnickcom/tcpdf/examples/lang/rus.php');
$pdf->setLanguageArray($l);

// set default font subsetting mode
$pdf->setFontSubsetting(true);

// set font
$pdf->SetFont('dejavusans', '', 12);


// -------------------------------------------------------------------

//$pdf->setPrintHeader(false);
//$pdf->setPrintFooter(false);

// add a page
$pdf->AddPage();

// set JPEG quality
//$pdf->setJPEGQuality(75);

// Image method signature:
// Image($file, $x='', $y='', $w=0, $h=0, $type='', $link='', $align='', $resize=false, $dpi=300, $palign='', $ismask=false, $imgmask=false, $border=0, $fitbox=false, $hidden=false, $fitonpage=false)

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

// Example of Image from data stream ('PHP rules')
//$imgdata = base64_decode('iVBORw0KGgoAAAANSUhEUgAAABwAAAASCAMAAAB/2U7WAAAABlBMVEUAAAD///+l2Z/dAAAASUlEQVR4XqWQUQoAIAxC2/0vXZDrEX4IJTRkb7lobNUStXsB0jIXIAMSsQnWlsV+wULF4Avk9fLq2r8a5HSE35Q3eO2XP1A1wQkZSgETvDtKdQAAAABJRU5ErkJggg==');

// The '@' character is used to indicate that follows an image data stream and not an image file name
//$pdf->Image('@'.$imgdata);

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

// print a line using Cell()
$pdf->Cell(0, 12, 'Пример 1', 1, 1, 'C');

// output the HTML content
$html = <<<EOF
<h1>Test custom bullet image for list items</h1>
<ul style="font-size:14pt;list-style-type:img|png|4|4|images/logo_example.png">
    <li>test custom bullet image</li>
    <li>test custom bullet image</li>
    <li>test custom bullet image</li>
    <li>test custom bullet image</li>
<ul>
EOF;

$pdf->writeHTML($html, true, false, true, false, '');

// Image example with resizing
$pdf->Image('../api2/vendor/tecnickcom/tcpdf/examples/images/image_demo.jpg', '', '', 180, 150, 'JPG', 'http://www.tcpdf.org', '', true, 300, '', false, false, 0, false, false, false);


// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

// test fitbox with all alignment combinations

/*$horizontal_alignments = array('L', 'C', 'R');
$vertical_alignments = array('T', 'M', 'B');

$x = 15;
$y = 35;
$w = 30;
$h = 30;
// test all combinations of alignments
for ($i = 0; $i < 3; ++$i) {
	$fitbox = $horizontal_alignments[$i].' ';
	$x = 15;
	for ($j = 0; $j < 3; ++$j) {
		$fitbox[1] = $vertical_alignments[$j];
		$pdf->Rect($x, $y, $w, $h, 'F', array(), array(128,255,128));
		$pdf->Image('images/image_demo.jpg', $x, $y, $w, $h, 'JPG', '', '', false, 300, '', false, false, 0, $fitbox, false, false);
		$x += 32; // new column
	}
	$y += 32; // new row
}

$x = 115;
$y = 35;
$w = 25;
$h = 50;
for ($i = 0; $i < 3; ++$i) {
	$fitbox = $horizontal_alignments[$i].' ';
	$x = 115;
	for ($j = 0; $j < 3; ++$j) {
		$fitbox[1] = $vertical_alignments[$j];
		$pdf->Rect($x, $y, $w, $h, 'F', array(), array(128,255,255));
		$pdf->Image('images/image_demo.jpg', $x, $y, $w, $h, 'JPG', '', '', false, 300, '', false, false, 0, $fitbox, false, false);
		$x += 27; // new column
	}
	$y += 52; // new row
}

// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

// Stretching, position and alignment example

$pdf->SetXY(110, 200);
$pdf->Image('images/image_demo.jpg', '', '', 40, 40, '', '', 'T', false, 300, '', false, false, 1, false, false, false);
$pdf->Image('images/image_demo.jpg', '', '', 40, 40, '', '', '', false, 300, '', false, false, 1, false, false, false);
*/
// -------------------------------------------------------------------

//Close and output PDF document
$pdf->Output('example_009.pdf', 'I');

//============================================================+
// END OF FILE
//============================================================+
