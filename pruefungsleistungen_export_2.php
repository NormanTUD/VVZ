<?php
	$setup_mode = 0;
	include("functions.php");
	include_once('./Classes/PHPExcel.php');

	if(check_page_rights(get_page_id_by_filename('export_dozent_pruefungsnummern.php'))) { // Wichtig, damit Niemand ohne Anmeldung etwas ändern kann
		if(get_get('semester')) {
			$chosen_semester = get_get('semester') ? get_get('semester') : get_this_semester()[0];
			$chosen_pruefungsamt = (get_get('pruefungsamt') ? get_get('pruefungsamt') : null);

			$semester_data = get_semester($chosen_semester);

			$objPHPExcel = export_crazy_ethik_export_format_2($chosen_pruefungsamt, $chosen_semester, get_get("einzelne_pns"), 0, get_get("last_changed_date"));
			if($objPHPExcel) {
				header('Content-type: application/vnd.ms-excel');
				header('Content-Disposition: attachment; filename="Pruefungsleistungen-'.$semester_data[2].'-'.$semester_data[1].'.xlsx"');

				$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
				$objWriter->save('php://output');
			} else {
				die("Nothing found...");
			}
		} else {
			die("Der Parameter `semester` muss mit einem validen Semester definiert worden sein.");
		}
	} else {
		die("Sie dürfen auf diese Seite nicht zugreifen.");
	}
?>
