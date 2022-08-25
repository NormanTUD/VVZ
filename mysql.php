<?php
	$GLOBALS["dbh"] = null;
	include("config.php");

	if(!function_exists("rquery")) {
		function rquery ($internalquery, $die = 1) {
			$debug_backtrace = debug_backtrace();
			$caller_file = $debug_backtrace[0]['file'];
			$caller_line = $debug_backtrace[0]['line'];
			$caller_function = '';
			if(array_key_exists(1, $debug_backtrace) && array_key_exists('function', $debug_backtrace[1])) {
				$caller_function = $debug_backtrace[1]['function'];
			}
			$start = microtime(true);
			$result = mysqli_query($GLOBALS['dbh'], $internalquery);
			$end = microtime(true);
			$used_time = $end - $start;
			$numrows = "&mdash;";
			if(!is_bool($result)) {
				$numrows = mysqli_num_rows($result);
			}
			$GLOBALS['queries'][] = array('query' => "/* $caller_file, $caller_line".($caller_function ? " ($caller_function)" : '').": */\n$internalquery", 'time' => $used_time, 'numrows' => $numrows);

			if($caller_function) {
				if(array_key_exists($caller_function, $GLOBALS['function_usage'])) {
					$GLOBALS['function_usage'][$caller_function]['count']++;
					$GLOBALS['function_usage'][$caller_function]['time'] += $used_time;
				} else {
					$GLOBALS['function_usage'][$caller_function]['count'] = 1;
					$GLOBALS['function_usage'][$caller_function]['time'] = $used_time;
					$GLOBALS['function_usage'][$caller_function]['name'] = $caller_function;
				}
			}

			if(!$result) {
				if($die) {
					if($GLOBALS['dbh']) {
						dier("Ung&uuml;ltige Anfrage: <p><pre>".$internalquery."</pre></p>".htmlentities(mysqli_error($GLOBALS['dbh'])), 0, 1);
					} else {
						dier("Ung&uuml;ltige Anfrage: <p><pre>".htmlentities($internalquery)."</pre></p><p>DBH undefined! This must never happen unless there is something seriously wrong with the database.</p>", 0, 0);
					}
				}
			}

			if($GLOBALS['rquery_print']) {
				print "<p>".htmlentities($internalquery)."</p>\n";
			}

			return $result;
		}
	}

	if(!function_exists("selftest_startpage")) {
		function selftest_startpage() {
			$tables = array(
				'plan' => 'CREATE TABLE plan (
					id int unsigned auto_increment primary key,
					name varchar(100) unique,
					monatliche_zahlung float(2),
					jaehrliche_zahlung float(2)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8',

				'kundendaten' => "CREATE TABLE kundendaten (
					id int unsigned auto_increment primary key,
					anrede varchar(100) DEFAULT 'Hallo Testkunde',
					universitaet varchar(100) DEFAULT 'Name der Universität',
					kundename varchar(100) default 'Test',
					kundestrasse varchar(100) default 'Benutzer',
					kundeplz varchar(100) default '12345',
					kundeort varchar(100) default 'Teststadt',
					dbname varchar(100) not null,
					urlname varchar(100) unique,
					iban varchar(100),
					`plan_id` int unsigned,
					number_of_faculties int unsigned default 1,
					email varchar(100),
					CONSTRAINT `plan_fk` FOREIGN KEY (`plan_id`) REFERENCES `vvz_global`.`plan` (`id`) ON DELETE CASCADE,
					personalized int default 0
				)",

				'rechnungen' => "create table rechnungen (
					id int unsigned auto_increment primary key,
					datum date DEFAULT NULL,
					zahlungszyklus_monate int default 1,
					eingegangen DATETIME,
					rabatt int,
					spezialpreis int
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;",
			);

			rquery("CREATE DATABASE IF NOT EXISTS vvz_global");
			rquery('use `vvz_global`');

			foreach ($tables as $this_table => $create_query) {
				if(!table_exists("vvz_global", $this_table)) {
					$missing_tables[] = $this_table;
					if(is_array($create_query)) {
						foreach ($create_query as $this_create_query) {
							rquery($this_create_query);
						}
					} else {
						rquery($create_query);
					}
					$GLOBALS['settings_cache'] = array();
				}
			}
		}

	}

	if(!function_exists("table_exists")) {
		function table_exists ($db, $table) {
			$query = "SELECT table_name FROM information_schema.tables WHERE table_schema = ".esc($db)." AND table_name = ".esc($table);
			$result = mysqli_query($GLOBALS['dbh'], $query);
			$table_exists = 0;
			while ($row = mysqli_fetch_row($result)) {
				$table_exists = 1;
			}
			return $table_exists;
		}
	}

	if(!function_exists("get_url_uni_name")) {
		function get_url_uni_name () {
			if(isset($_SERVER["REDIRECT_URL"]) && preg_match("/^\/v\/(.*?)($|\/.*$)?$/", $_SERVER["REDIRECT_URL"], $matches)) {
				return $matches[1];
			}
			return "";
		}
	}

	if(!function_exists("esc")) {
		function esc ($parameter) { 
			if(!is_array($parameter)) { // Kein array
				if(isset($parameter) && strlen($parameter)) {
					return '"'.mysqli_real_escape_string($GLOBALS['dbh'], $parameter).'"';
				} else {
					return 'NULL';
				}
			} else { // Array
				$str = join(', ', array_map('esc', array_map('my_mysqli_real_escape_string', $parameter)));
				return $str;
			}
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

			// Check connection
			if ($GLOBALS["dbh"]->connect_error) {
				die("Connection failed: ".$GLOBALS["dbh"]->connect_error);
			}

			selftest_startpage();
			$url_uni_name = get_url_uni_name();
			$query = 'select dbname from vvz_global.kundendaten where urlname = '.esc($url_uni_name);
			$result_dbname = $GLOBALS["dbh"]->query($query);

			if($result_dbname) {
				while ($row = mysqli_fetch_row($result_dbname)) {
					$GLOBALS["dbname"] = $row[0];
				}
			}

			if(!$GLOBALS["dbname"]) {
				$GLOBALS['dbname'] = get_kunden_db_name();
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
