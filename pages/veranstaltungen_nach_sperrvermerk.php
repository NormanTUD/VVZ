<?php
	$included_files = get_included_files();
	$included_files = array_map('basename', $included_files);

	if(!in_array('functions.php', $included_files)) {
		include_once('../functions.php');
	}

	if(check_page_rights(get_page_id_by_filename(basename(__FILE__)))) { // Wichtig, damit Niemand ohne Anmeldung etwas ändern kann
		$semester = create_semester_array();

		$semester_mit_sperrvermerk = array();

		foreach ($semester as $s) {
			if(semester_has_sperrvermerk($s[0])) {
				$semester_mit_sperrvermerk[] = $s;
			}
		}

		if(count($semester_mit_sperrvermerk)) {
			sperrvermerk_table($semester_mit_sperrvermerk)
		} else {
			print "Keine Semester mit Sperrvermerk.";
		}
	}
?>
