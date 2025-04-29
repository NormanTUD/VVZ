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
		function dier ($data, $sql=0, $show_error=1) {
			$stacktrace_data = debug_backtrace()[0];
			$stacktrace = "";
			if(isset($debug_backtrace[0])) {
				$stacktrace = 'Aufgerufen von <b>'.debug_backtrace()[1]['file'].'</b>::<i>'.debug_backtrace()[1]['function'].'</i>, line '.htmlentities($stacktrace_data['line'])."<br />\n";
			}

			if(is_dir("debuglogs")) {
				if(is_writeable("debuglogs")) {
					$i = 0;
					$filename = "debuglogs/$i.log";
					while (file_exists($filename)) {
						$i++;
						$filename = "debuglogs/$i.log";
					}

					$string  = "MESSAGE >>>>>>>>>>>>>>>>\n\n".print_r($data, true)."\n\n<<<<<<<<<<<<<<<<\n\nStacktrace:\n\n$stacktrace";

					file_put_contents($filename, $string);
				} else {
					stderrw("debug logs not writable");
				}
			} else {
				stderrw("debug logs is no dir");
			}

			#http_response_code(500);
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
				$message .= htmlentities(print_r($data, true) ?? "")."\n";
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
				if(array_key_exists(1, debug_backtrace())) {
					if($GLOBALS['logged_in_user_id'] || $show_error) {
						print $stacktrace;
					}
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
	$GLOBALS['admin_email'] = "kochnorman@rocketmail.com";
	$GLOBALS['admin_name'] = "Norman Koch";

	$GLOBALS['name_non_technical'] = "Norbert Engemaier";
	$GLOBALS['to_non_technical'] = "norbert.engemaier@tu-dresden.de";
	$GLOBALS['cc_non_technical'] = array('nengemaier@gmail.com', $GLOBALS['admin_email']);

/*
	Default settings
 */
	$GLOBALS["default_universitaet"] = "VVZ";
	$GLOBALS["default_name"] = "Norman Koch";
	$GLOBALS["default_ort"] = "";
	$GLOBALS["default_plz"] = "";
	$GLOBALS["default_strasse"] = "";
	$GLOBALS["default_email"] = "";
	$GLOBALS["default_zahlungszyklus_monate"] = "1";

	if(file_exists("/etc/vvztud")) {
		$GLOBALS["default_universitaet"] = "Technische Universität Dresden";
		$GLOBALS["default_name"] = "Norbert Engemaier";
		$GLOBALS["default_ort"] = "Dresden";
		$GLOBALS["default_plz"] = "01062";
		$GLOBALS["default_strasse"] = "Nöthnitzer Straße 43";
		$GLOBALS["default_email"] = "norbert.engemaier@tu-dresden.de";
		$GLOBALS['impressum_university_page'] = "https://tu-dresden.de/impressum";
	}
?>
