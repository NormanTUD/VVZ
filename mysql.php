<?php
	$GLOBALS['dbname'] = 'uni';
	$GLOBALS['error_page_shown'] = 0;

	$dbfile = '/etc/vvzdbpw';

	if(file_exists($dbfile)) {
		$vvzdbpw = explode("\n", file_get_contents($dbfile))[0];

		if($vvzdbpw) {
			$username = 'root';
			$password = $vvzdbpw;

			$GLOBALS['dbh'] = mysqli_connect('localhost', $username, $password, $GLOBALS['dbname']);
			if (!$GLOBALS['dbh']) {
				dier("Kann nicht zur Datenbank verbinden!");
			}
		} else {
			die("Die Passwortdatei war leer bzw. das Passwort war nicht in der ersten Zeile.");
		}
	} else {
		die("Die Verbindung zur Datenbank konnte nicht hergestellt werden (falsches oder kein Passwort)");
	}
?>
