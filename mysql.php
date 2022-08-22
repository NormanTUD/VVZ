<?php
	include_once("config.php");

	$GLOBALS["db_freshly_created"] = 0;

	$dbfile = '/etc/vvzdbpw';

	if(file_exists($dbfile)) {
		$vvzdbpw = explode("\n", file_get_contents($dbfile))[0];

		if($vvzdbpw) {
			$password = $vvzdbpw;

			$GLOBALS['dbh'] = mysqli_connect('localhost', $GLOBALS['db_username'], $password);
			// Check connection
			if ($GLOBALS["dbh"]->connect_error) {
				die("Connection failed: ".$GLOBALS["dbh"]->connect_error);
			}


			try {
				mysqli_select_db($GLOBALS["dbh"], $GLOBALS["dbname"]);
			} catch (\Throwable $e) {
				error_log($e);
				error_log("Trying to create database...");
				$sql = "CREATE DATABASE ".$GLOBALS["dbname"];
				if (!$GLOBALS["dbh"]->query($sql) === TRUE) {
					die("Error creating database: ".$GLOBALS["dbh"]->error);
				} else {
					$GLOBALS["db_freshly_created"] = 1;
				}
			}
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
