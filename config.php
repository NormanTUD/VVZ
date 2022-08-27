<?php
	if(file_exists('/etc/hardcore_debugging')) {
		error_reporting(E_ALL);
		set_error_handler(function ($severity, $message, $file, $line) {
			throw new \ErrorException($message, $severity, $severity, $file, $line);
		});

		ini_set('display_errors', 1);
	}

	if(!function_exists("might_be_query")) {
		function might_be_query ($data) {
			if(isset($data)) {
				if(!is_array($data)) {
					# ist vorhanden und ein string
					if(is_string($data)) {
						if(preg_match('/^SELECT\s+.*FROM\s+.*/i', $data)) {
							return 1;
						} else if(preg_match('/^UPDATE\s+.*SET\s+/i', $data)) {
							return 1;
						} else if(preg_match('/^DELETE\s+FROM\s+/i', $data)) {
							return 1;
						}
					}
				}
			}

			return 0;
		}
	}

	if(!function_exists("dier")) {
		function dier ($data, $sql = 0, $show_error = 1) {
			if(might_be_query($data)) {
				$sql = 1;
			}
			if($sql) {
				include_once('scripts/SqlFormatter.php');
			}
			
			if(!isset($GLOBALS['logged_in_user_id'])) {
				$from = $GLOBALS['from_email'];

				$subject = "FEHLER im Vorlesungsverzeichnis";

				$datum = date("d.m.Y");
				$uhrzeit = date("H:i");

				$message = "Name: VVZ-Fehler\n";
				$message .= "Zeit: $datum $uhrzeit\n";
				if(get_post('referer')) {
					$message .= "Referer: ".htmlentities(get_post('referer'))."\n";
				}
				if(isset($_SERVER['HTTP_USER_AGENT'])) {
					$message .= "User-Agent: ".htmlentities($_SERVER['HTTP_USER_AGENT'])."\n";
				}
				$actual_link = "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
				$message .= "URL: ".htmlentities($actual_link)."\n";
				$message .= "POST: ".htmlentities(print_r($_POST, true))."\n";
				$message .= "DBname: ".print_r($GLOBALS['dbname'], true)."\n";
				$message .= "DBH: ".print_r($GLOBALS['dbh'], true)."\n";
				$message .= "\n";
				$message .= "Nachricht ===============================\n";
				$message .= htmlentities($data ?? "")."\n";
				$message .= "========================== Nachricht Ende\n";

				$headers = '';
				$headers .= "From:" . $from."\r\n";

				try {
					$fp = fsockopen("localhost", 25, $errno, $errstr, 5);
					if($fp && mail($GLOBALS['admin_email'], $subject, $message, $headers)) {
						$GLOBALS['messageerror'] = 'Die Administration ist informiert worden.';
					}
				} catch (\Throwable $e) {
					//stderrw("No mail server");
				}

				include("error.php");
			} else {
				if(!file_exists("/etc/vvz_debug_query")) {
					print "FEHLER!";
				}
				$source_data = debug_backtrace()[0];
				@$source = 'Aufgerufen von <b>'.debug_backtrace()[1]['file'].'</b>::<i>'.debug_backtrace()[1]['function'].'</i>, line '.htmlentities($source_data['line'])."<br />\n";
				if($GLOBALS['logged_in_user_id'] || $show_error) {
					print $source;
				}
				print "<pre>\n";
				$buffer = '';
				if($sql) {
					$buffer = SqlFormatter::highlight($data);
				} else {
					ob_start();
					print_r($data);
					$buffer = ob_get_clean();
				}

				if($sql) {
					print $buffer;
				} else {
					print htmlentities($buffer);
				}
				print "</pre>\n";
				if($GLOBALS['logged_in_user_id']) {
					print "Backtrace:\n";
					print "<pre>\n";
					foreach (debug_backtrace() as $trace) {
							print htmlentities(sprintf("\n%s:%s %s", $trace['file'], $trace['line'], $trace['function']));
					}
					print "</pre>\n";
				}
				exit();
			}
		}
	}

	include_once("kundenkram.php");

/*
	Global stuff
 */
	$GLOBALS["selftest_already_done"] = 0;
	$GLOBALS['university_name'] = "TU Dresden";
	$GLOBALS['impressum_university_page'] = "https://tu-dresden.de/impressum";
	$GLOBALS['institut'] = "Institut für Philosophie";
	$GLOBALS['faculty'] = "Fakultät für Philosophie";
	$GLOBALS['vvz_base_url'] = "vvz.phil.tu-dresden.de";
	$GLOBALS['university_page_url'] = "https://tu-dresden.de/";
	$GLOBALS["calname"] = "Philosophie";
	$GLOBALS['timezone_name'] = "Europe/Berlin";

/*
	Navigator

	For campus navigator. This makes the assumption that the navigator's base url, when appended the abbreviation of a building,
	leads to the pages' site about that building.
 */

	$GLOBALS['navigator_base_url'] = "https://navigator.tu-dresden.de/karten/dresden/geb/";

/*
	DB Config

	Set the password in the file '/etc/vvzdbpw'
 */
	$GLOBALS['dbname'] = null;
	$GLOBALS["db_username"] = 'root';

/*
	Email Settings
 */
	$GLOBALS['from_email'] = "vvz.phil@tu-dresden.de";
	$GLOBALS['admin_email'] = "norman.koch@tu-dresden.de";
	$GLOBALS['admin_name'] = "Norman Koch";

	$GLOBALS['name_non_technical'] = "Holm Bräuer";
	$GLOBALS['to_non_technical'] = "holm.braeuer@tu-dresden.de";
	$GLOBALS['cc_non_technical'] = array('nengemaier@gmail.com', $GLOBALS['admin_email']);
?>
