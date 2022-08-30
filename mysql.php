<?php
	$GLOBALS["dbh"] = null;
	include("config.php");

	if(!array_key_exists("no_selftest_force", $GLOBALS)) {
		$GLOBALS["no_selftest_force"] = 0;
	}
	if(!array_key_exists("no_selftest", $GLOBALS)) {
		$GLOBALS["no_selftest"] = 0;
	}

	if(!defined('STDERR')) define('STDERR', fopen('php://stderr', 'wb'));

	if(!function_exists("stderrw")) {
		function stderrw ($str) {
			fwrite(STDERR, $str);
		}
	}



	if(!function_exists("query_to_json")) {
		function query_to_json($query, $skip_array) {
			$result = rquery($query);

			$rows = array();
			while($row = mysqli_fetch_assoc($result)) {
				foreach ($skip_array as $skip_name) {
					unset($row[$skip_name]);
				}

				if($row) {
					$rows[] = $row;
				}
			}

			return json_encode($rows);
		}
	}

	if(!function_exists("query_to_status_hash")) {
		function query_to_status_hash ($query, $skip_array = array()) {
			return hash('md5', query_to_json($query, $skip_array));
		}
	}

	if(!function_exists("multiple_esc_join")) {
		function multiple_esc_join ($data) {
			if(is_array($data)) {
				$data = array_map('esc', $data);
				$string = join(", ", $data);
				return $string;
			} else {
				return esc($data);
			}
		}
	}



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
						throw Exception("Ung&uuml;ltige Anfrage: <p><pre>".$internalquery."</pre></p>".htmlentities(mysqli_error($GLOBALS['dbh'])), 0, 1);
					} else {
						throw Exception("Ung&uuml;ltige Anfrage: <p><pre>".htmlentities($internalquery)."</pre></p><p>DBH undefined! This must never happen unless there is something seriously wrong with the database.</p>", 0, 0);
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
			if(array_key_exists("no_selftest", $GLOBALS) && $GLOBALS["no_selftest"]) {
				return;
			}

			$tables = array(
				'plan' => 'create table if not exists plan (
					id int unsigned auto_increment primary key,
					name varchar(100) unique,
					monatliche_zahlung float(2),
					jaehrliche_zahlung float(2)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8',

				'kundendaten' => "create table if not exists kundendaten (
					id int unsigned auto_increment primary key,
					anrede varchar(100) DEFAULT 'Hallo Testkunde',
					universitaet varchar(100) DEFAULT 'Name der Universität',
					name varchar(100) default 'Test',
					strasse varchar(100) default 'Benutzer',
					plz varchar(100) default '12345',
					ort varchar(100) default 'Teststadt',
					dbname varchar(100) not null unique,
					urlname varchar(100) unique,
					external_url varchar(200),
					iban varchar(100),
					plan_id int unsigned,
					zahlungszyklus_monate int default 1,
					number_of_faculties int unsigned default 1,
					email varchar(100),
					CONSTRAINT `plan_fk` FOREIGN KEY (`plan_id`) REFERENCES `vvz_global`.`plan` (`id`) ON DELETE CASCADE,
					personalized int default 0
				)",

				'institut' => 'create table if not exists `institut` (
					`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
					`name` varchar(100) DEFAULT NULL,
					`start_nr` int(10) unsigned DEFAULT NULL,
					PRIMARY KEY (`id`),
					UNIQUE KEY `name` (`name`),
					UNIQUE KEY `start_nr` (`start_nr`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;',


				'titel' => 'create table if not exists `titel` (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`name` varchar(100) NOT NULL,
					`abkuerzung` varchar(100) NOT NULL,
					PRIMARY KEY (`id`),
					UNIQUE KEY `name` (`name`),
					UNIQUE KEY `abkuerzung` (`abkuerzung`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;', 

				'dozent' => "create table if not exists `dozent` (
					`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
					`first_name` varchar(100) NOT NULL,
					`last_name` varchar(100) NOT NULL,
					`titel_id` int(11) DEFAULT NULL,
					`ausgeschieden` enum('0','1') NOT NULL DEFAULT '0',
					PRIMARY KEY (`id`),
					UNIQUE KEY `first_last_name` (`first_name`,`last_name`),
					KEY `titel_id_fk` (`titel_id`),
					CONSTRAINT `titel_id_fk` FOREIGN KEY (`titel_id`) REFERENCES `vvz_global`.`titel` (`id`) ON DELETE CASCADE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;",


				'users' => "create table if not exists `users` (
					`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
					`username` varchar(100) DEFAULT NULL,
					`dozent_id` int(10) unsigned DEFAULT NULL,
					`institut_id` int(10) unsigned DEFAULT NULL,
					`password_sha256` varchar(256) DEFAULT NULL,
					`salt` varchar(100) NOT NULL,
					`enabled` enum('0','1') NOT NULL DEFAULT '1',
					`barrierefrei` enum('0','1') NOT NULL DEFAULT '0',
					`accepted_public_data` enum('0','1') NOT NULL DEFAULT '0',
					PRIMARY KEY (`id`),
					UNIQUE KEY `name` (`username`),
					UNIQUE KEY `dozent_id` (`dozent_id`),
					KEY `institut_id` (`institut_id`),
					CONSTRAINT `users_ibfk_1` FOREIGN KEY (`dozent_id`) REFERENCES `vvz_global`.`dozent` (`id`) ON DELETE CASCADE,
					CONSTRAINT `users_ibfk_2` FOREIGN KEY (`institut_id`) REFERENCES `vvz_global`.`institut` (`id`) ON DELETE CASCADE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;",

				'role' => 'create table if not exists `role` (
					`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
					`name` varchar(100) DEFAULT NULL,
					`beschreibung` varchar(100) DEFAULT NULL,
					PRIMARY KEY (`id`),
					UNIQUE KEY `name` (`name`)
					) ENGINE=InnoDB DEFAULT CHARSET=utf8;',

				'page' => "create table if not exists `page` (
					`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
					`name` varchar(50) NOT NULL,
					`file` varchar(50) DEFAULT NULL,
					`show_in_navigation` enum('0','1') NOT NULL DEFAULT '0',
					`parent` int(10) unsigned DEFAULT NULL,
					`disable_in_demo` int(1) unsigned not null default 0,
					`show_in_startpage` int(1) unsigned not null default 0,
					PRIMARY KEY (`id`),
					UNIQUE KEY `name` (`name`),
					UNIQUE KEY `file` (`file`),
					KEY `page` (`parent`),
					CONSTRAINT `page_ibfk_1` FOREIGN KEY (`parent`) REFERENCES `page` (`id`) ON DELETE SET NULL
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;",


				'role_to_page' => 'create table if not exists `role_to_page` (
					`role_id` int(10) unsigned NOT NULL,
					`page_id` int(10) unsigned NOT NULL,
					PRIMARY KEY (`role_id`,`page_id`),
					KEY `page_id` (`page_id`),
					CONSTRAINT `role_to_page_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `vvz_global`.`role` (`id`) ON DELETE CASCADE,
					CONSTRAINT `role_to_page_ibfk_2` FOREIGN KEY (`page_id`) REFERENCES `vvz_global`.`page` (`id`) ON DELETE CASCADE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;',

				'role_to_user' => 'create table if not exists `role_to_user` (
					`role_id` int(10) unsigned NOT NULL,
					`user_id` int(10) unsigned NOT NULL,
					PRIMARY KEY (`role_id`,`user_id`),
					UNIQUE KEY `name` (`user_id`),
					CONSTRAINT `role_to_user_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `vvz_global`.`role` (`id`) ON DELETE CASCADE,
					CONSTRAINT `role_to_user_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `vvz_global`.`users` (`id`) ON DELETE CASCADE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;',


				'logos' => "create table if not exists logos (
					id INTEGER UNSIGNED PRIMARY KEY NOT NULL AUTO_INCREMENT,
					kunde_id int unsigned unique,
					img MEDIUMBLOB NOT NULL,
					CONSTRAINT `kunde_id_fk` FOREIGN KEY (`kunde_id`) REFERENCES `vvz_global`.`kundendaten` (`id`) ON DELETE CASCADE
				)",


				'rechnungen' => "create table if not exists rechnungen (
					id int unsigned auto_increment primary key,
					kunde_id int unsigned not null,
					plan_id int unsigned not null,
					monat int unsigned not null,
					jahr int unsigned not null,
					rabatt int,
					spezialpreis int,
					UNIQUE KEY `kunde_monat_jahr` (`kunde_id`, `monat`, `jahr`),
					CONSTRAINT `plan_fk_rechnungen` FOREIGN KEY (`plan_id`) REFERENCES `vvz_global`.`plan` (`id`) ON DELETE CASCADE,
					CONSTRAINT `kunde_id_fk_rechnungen` FOREIGN KEY (`kunde_id`) REFERENCES `vvz_global`.`kundendaten` (`id`) ON DELETE CASCADE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;",
			);

			rquery("CREATE DATABASE IF NOT EXISTS vvz_global");
			rquery('use `vvz_global`');

			$new_tables = 0;
			foreach ($tables as $this_table => $create_query) {
				if(!table_exists("vvz_global", $this_table)) {
					$missing_tables[] = $this_table;
					if(is_array($create_query)) {
						foreach ($create_query as $this_create_query) {
							try {
								rquery($this_create_query);
								while (mysqli_next_result($GLOBALS["dbh"])); // Flush out the results.
								$new_tables++;
							} catch (\Throwable $e) {
								print $e;
								die($query);
							}
						}
					} else {
						try {
							rquery($create_query);
							while (mysqli_next_result($GLOBALS["dbh"])); // Flush out the results.
						} catch (\Throwable $e) {
							print("<pre>$create_query\n\n$e</pre>");
							exit(1);
						}
					}
					$GLOBALS['settings_cache'] = array();
				}
			}

			if($new_tables) {
				// Delete old Cookies
				$cookies = explode(';', $_SERVER['HTTP_COOKIE']);
				foreach($cookies as $cookie) {
					$parts = explode('=', $cookie);
					$name = trim($parts[0]);
					setcookie($name, '', time()-1000);
					setcookie($name, '', time()-1000, '/');
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


			if(array_key_exists("no_selftest", $GLOBALS) && !$GLOBALS["no_selftest"]) {
				selftest_startpage();
				$url_uni_name = get_url_uni_name();
				$query = 'select dbname from vvz_global.kundendaten where urlname = '.esc($url_uni_name);
				$result_dbname = $GLOBALS["dbh"]->query($query);

				if($result_dbname) {
					while ($row = mysqli_fetch_row($result_dbname)) {
						$GLOBALS["dbname"] = $row[0];
					}
				}
			}

			if(!$GLOBALS["dbname"]) {
				$GLOBALS['dbname'] = get_kunden_db_name();
			}

			try {
				mysqli_select_db($GLOBALS["dbh"], $GLOBALS["dbname"]);

				if(!array_key_exists("no_selftest_force", $GLOBALS) || !$GLOBALS["no_selftest"]) {
					$query = "select universitaet from vvz_global.kundendaten where id = ".esc(get_kunde_id_by_db_name($GLOBALS["dbname"]));
					$GLOBALS["university_name"] = get_single_row_from_query($query);
				}
			} catch (\Throwable $e) {
				error_log($e);
				error_log("Trying to create database...");

				if(!array_key_exists("no_selftest_force", $GLOBALS) || !$GLOBALS["no_selftest"]) {
					$sql = "CREATE DATABASE IF NOT EXISTS ".$GLOBALS["dbname"];
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

								$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
								file_get_contents($actual_link);

								exit(0);
							} else {
								die("Could not use DB");
							}
						} catch (\Throwable $e) {
							#stderrw("Could not select DB: $e");
							print "Es kann noch einen Moment dauern. Bitte warten Sie.";
							print '<meta http-equiv="refresh" content="0; url=./" />';
							flush();
							exit(0);
						}
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


	$GLOBALS['cookie_hash'] = hash("md5", $GLOBALS["dbname"]);
?>
