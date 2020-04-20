<?php
	$setup_mode = 0;
	include_once('functions.php');
	define('_MPDF_PATH', './');
	$css = '';
	foreach (array("data/foundation.min.css", "data/font-awesome.min.css", "data/style.css", "data/jquery-ui.css") as $file) {
		$css .= file_get_contents($file);
	}

	$html = '<html><head>
	<meta http-equiv="Content-Language" content="en-GB">
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<style type="text/css">
	'.$css.'
	</style>
</head><body>';

	$show_pruefungsleistungen = 0;
	if(get_get('show_pruefungsleistungen')) {
		$show_pruefungsleistungen = 1;
	}

	$show_gebaeudeliste = 0;
	if(get_get('show_gebaeudeliste')) {
		$show_pruefungsleistungen = 1;
	}
	$html .= create_stundenplan(get_get('veranstaltung'), $show_pruefungsleistungen, $show_gebaeudeliste)[0];
	$html .= '</body></html>';

	include_once("mpdf.php");

	$mpdf = new mPDF('','A4-L','','',25,15,21,22,10,10); 

	$mpdf->StartProgressBarOutput();

	$mpdf->mirrorMargins = 1;
	$mpdf->SetDisplayMode('fullpage','two');
	$mpdf->list_number_suffix = ')';

	$mpdf->WriteHTML($html);

	$mpdf->Output();

	exit;
?>
