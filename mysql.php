<?php
	include("config.php");

	if(!function_exists("get_url_uni_name")) {
		function get_url_uni_name () {
			if(isset($_SERVER["REDIRECT_URL"]) && preg_match("/^\/v\/(.*?)(?:$|\/.*)/", $_SERVER["REDIRECT_URL"], $matches)) {
				return $matches[1];
			}
			return "";
		}
	}

	$GLOBALS["db_freshly_created"] = 0;
	$GLOBALS["db_password"] = "";

	$dbfile = '/etc/vvzdbpw';

	if(file_exists($dbfile)) {
		$vvzdbpw = explode("\n", file_get_contents($dbfile))[0];

		if($vvzdbpw) {
			$GLOBALS["db_password"] = $vvzdbpw;

			$GLOBALS['dbh'] = mysqli_connect('localhost', $GLOBALS['db_username'], $GLOBALS["db_password"]);

			#$sql = "SELECT ".$GLOBALS["dbname"];
			#if (!$GLOBALS["dbh"]->query($sql) === TRUE) {

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
					try {
						if($GLOBALS["dbh"]->query("use ".$GLOBALS["dbname"])) {
							$GLOBALS["db_freshly_created"] = 1;
							include_once("selftest.php");

							print "Die neue Uni wurde erstellt. Sie werden weitergeleitet...";
							flush();
							$kn = get_kunden_db_name();
							print '<meta http-equiv="refresh" content="0; url=./" />';
							flush();

							exit(0);
						} else {
							die("Could not use DB");
						}
					} catch (\Throwable $e) {
						die("Could not select DB: $e");
					}
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
