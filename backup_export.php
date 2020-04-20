<?php
	$GLOBALS['setup_mode'] = 0;
	include("functions.php");
	if(check_page_rights(get_page_id_by_filename(basename(__FILE__)))) { // Wichtig, damit Niemand ohne Anmeldung etwas ändern kann
		$filedate = date('Y-m-d_H-m-s', time());
		header('Content-type: application/sql, charset=utf-8');
		header('Content-Disposition: attachment; filename="dbbackup-'.$filedate.'.sql"');
		print backup_tables();
	} else {
		die("Leider haben Sie keinen Zugriff auf diese Seite.");
	}
?>
