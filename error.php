<?php
	if(!empty($GLOBALS['error_page_shown'])) {
		exit();
	}
	$GLOBALS['error_page_shown'] = 1;

	require_once(__DIR__.'/error_page.php');
	render_vvz_error_page(
		500,
		'Da ist unser Server kurz ausgerutscht.',
		'Bei der Verarbeitung ist ein interner Fehler aufgetreten. Bitte versuchen Sie es gleich noch einmal.',
		'Bleibt der Fehler bestehen, informieren Sie bitte die Administration des Vorlesungsverzeichnisses.',
		false
	);
	exit();
?>
