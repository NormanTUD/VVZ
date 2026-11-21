<?php
	require_once(__DIR__.'/error_page.php');
	render_vvz_error_page(
		500,
		'Da ist unser Server kurz ausgerutscht.',
		'Bei der Verarbeitung Ihrer Anfrage ist ein interner Fehler aufgetreten. Bitte versuchen Sie es in einem Moment noch einmal.',
		'Bleibt der Fehler bestehen, informieren Sie bitte die Administration des Vorlesungsverzeichnisses.',
		false
	);
?>
