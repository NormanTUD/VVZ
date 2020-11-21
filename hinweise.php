<?php
	$included_files = get_included_files();
	$included_files = array_map('basename', $included_files);

	if(!in_array('functions.php', $included_files)) {
		include_once('functions.php');
	}

	$chosen_page = null;

	if(isset($GLOBALS['submenu_id'])) {
		$chosen_page = $GLOBALS['submenu_id'];
	}

	if(!isset($chosen_page) && isset($GLOBALS['this_page_number'])) {
		$chosen_page = $GLOBALS['this_page_number'];
	}

	if(!$chosen_page) {
		$chosen_page = get_get('page');
	}

	if(!$chosen_page) {
		$filename = basename($_SERVER['SCRIPT_NAME']);
		if($filename == 'admin.php') {
			$chosen_page = get_page_id_by_filename('welcome.php');
		}
	}

	if($chosen_page) {
		if(check_page_rights($chosen_page)) { // Wichtig, damit Niemand ohne Anmeldung etwas ändern kann
			print_hinweis_for_page($chosen_page);
		} else if (preg_match('/^\d+$/', get_get('page'))) {
			if(!headers_sent()) {
				header('Location: admin.php?page='.htmlentities(get_get('page')));
			}
		} else {
			if(!headers_sent()) {
				header('Location: admin.php');
			}
		}
	} else {
		if(!headers_sent()) {
			header('Location: admin.php');
		}
	}
?>
