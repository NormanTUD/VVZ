<?php
	$GLOBALS['setup_mode'] = 0;
	include_once("functions.php");

	if(get_get('veranstaltung')) {
		$filedate = date('Y-m-d_H-m-s', time());
		header('Content-type: text/calendar, charset=utf-8');
		header('Content-Disposition: attachment; filename="calendar-'.$filedate.'.ics"');
		print create_event_file(get_get('veranstaltung'));
	} else {
		die("Keine Veranstaltungen angegeben!");
	}

?>
