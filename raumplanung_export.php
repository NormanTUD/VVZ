<?php
	include_once("functions.php");

	if(check_page_rights(get_page_id_by_filename('raumplanung.php'))) { // Wichtig, damit Niemand ohne Anmeldung etwas ändern kann
		if(get_get('semester')) {
			$semester_name = get_semester(get_get('semester'));
			$institut_name = get_institut_name(get_get('institut'));
			header('Content-type: application/vnd.ms-excel');
			header('Content-Disposition: attachment; filename="Raumplanung-'.$institut_name.'-'.$semester_name[2].'_'.$semester_name[1].'.xlsx"');

			$objPHPExcel = raumplanung(get_get('institut'), get_get('semester'), 0);
			$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
			$objWriter->save('php://output');
		} else {
			die("Der Parameter `semester` muss mit einem validen Semester definiert worden sein.");
		}
	} else {
		die("Sie dürfen auf diese Seite nicht zugreifen.");
	}
?>
