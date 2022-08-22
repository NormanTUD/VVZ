<?php
	$setup_mode = 0;
	include_once("functions.php");

	if(check_page_rights(get_page_id_by_filename('export_dozent_pruefungsnummern.php'))) { // Wichtig, damit Niemand ohne Anmeldung etwas ändern kann
		if(get_get('semester')) {
			$chosen_semester = (get_get('semester') ? get_get('semester') : get_this_semester()[0]);
			$chosen_institut = (get_get('institut') ? get_get('institut') : $institute[1][0]);
			$chosen_dozent = (get_get('dozent') ? get_get('dozent') : null);
			$chosen_studiengang = (get_get('studiengang') ? get_get('studiengang') : null);
			$chosen_pruefungsamt = (get_get('pruefungsamt') ? get_get('pruefungsamt') : null);

			$studiengang_group_by = null;

			$semester_data = get_semester($chosen_semester);

			$objPHPExcel = export_pruefungsnummern_dozent($chosen_semester, $chosen_dozent, $chosen_institut, $chosen_studiengang, $chosen_pruefungsamt, $studiengang_group_by, 0);

			header('Content-type: application/vnd.ms-excel');
			header('Content-Disposition: attachment; filename="Raumplanung-'.get_institut_name($chosen_institut).'-'.$semester_data[2].'-'.$semester_data[1].'.xlsx"');

			$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
			$objWriter->save('php://output');
		} else {
			die("Der Parameter `semester` muss mit einem validen Semester definiert worden sein.");
		}
	} else {
		die("Sie dürfen auf diese Seite nicht zugreifen.");
	}
?>
