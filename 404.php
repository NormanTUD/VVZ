<?php
	require_once(__DIR__.'/error_page.php');
	render_vvz_error_page(
		404,
		'Diese Seite ist uns entwischt.',
		'Die angeforderte Adresse existiert nicht, wurde verschoben oder hat sich vertippt. Kein Grund zur Sorge – das Vorlesungsverzeichnis ist nur einen Klick entfernt.',
		'Tipp: Nutzen Sie die Startseite oder die Suche, um wieder auf den richtigen Pfad zu kommen.',
		true
	);
?>
