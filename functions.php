<?php
if(file_exists('/etc/hardcore_debugging')) {
	error_reporting(E_ALL);
	set_error_handler(function ($severity, $message, $file, $line) {
		throw new \ErrorException($message, $severity, $severity, $file, $line);
	});

	ini_set('display_errors', 1);
}


/*
register_tick_function(function() {
    $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
    $last = reset($bt);
    $info = sprintf("%s +%d\n", $last['file'], $last['line']);
    file_put_contents('/tmp/segfault.txt', $info, FILE_APPEND);
    // or
    // file_put_contents('php://output', $info, FILE_APPEND);
});
declare(ticks=1);
 */

/*
	JUMP POINTS:

	FUNCTION_CHECK

	PARAMETERCHECK	
	ID_EXISTS
	DELETE_DATA
	UPDATE_DATA
	NO_ID
	
	
	
	
	REGEX ZUM DEBUGGEN VON FUNKTIONEN:
	(^\s*function\s*)([a-zA-Z0-9_]+)(\s*\(.*\)\s*\{\s*)
	\1\2\3\n\t\tfunction_debug_counter("\2");
 */

	include_once("config.php");

	header_remove("X-Powered-By"); // Serverinfos entfernen
	header("X-Frame-Options: ALLOW-FROM ".$GLOBALS['university_page_url']); // Gegen Clickjacking

	$GLOBALS['nonce'] = generate_random_string(10);

	$GLOBALS['csp_string'] =  "default-src 'self' 'nonce-".nonce()."' 'unsafe-inline'; ";
	$GLOBALS['csp_string'] .= "script-src 'self' 'nonce-".nonce()."' blob: 'unsafe-inline' https://www.gstatic.com/charts; ";
	$GLOBALS['csp_string'] .= "img-src 'self' 'nonce-".nonce()."' data: 'unsafe-inline'; ";
	$GLOBALS['csp_string'] .= "style-src 'self' 'unsafe-inline'; ";
	header("Content-Security-Policy: ".$GLOBALS['csp_string']);
	header("X-Content-Security-Policy: ".$GLOBALS['csp_string']);
	header("WebKit-CSP: \"default-src 'self'\"");

	// Definition globaler Variablen
	$GLOBALS['backtraces'] = array();
	$GLOBALS['function_debugger'] = array();
	$GLOBALS['debug'] = array();
	$GLOBALS['toc'] = array();
	$GLOBALS['error'] = array();
	$GLOBALS['message'] = array();
	$GLOBALS['warning'] = array();
	$GLOBALS['success'] = array();
	$GLOBALS['easter_egg'] = array();
	$GLOBALS['right_issue'] = array();

	$GLOBALS['shown_help_ids'] = array("google_maps_icon" => 0, "calendar" => 0);

	$GLOBALS['compare_db'] = '';

	$GLOBALS['pruefungen_already_done'] = array();
	if(get_cookie('absolviertepruefungsleistungen')) {
		$this_cookie = get_cookie('absolviertepruefungsleistungen');
		$json_data = json_decode($this_cookie);
		if($json_data) {
			$GLOBALS['pruefungen_already_done'] = $json_data;
		}
	}

	if(get_get('stundenplan_addieren')) {
		$additiver_stundenplan = array();
		$bisherige_veranstaltungen = explode(",", get_cookie('additiver_stundenplan'));
		foreach ($bisherige_veranstaltungen as $this_bisherige_veranstaltung) {
			$additiver_stundenplan[] = $this_bisherige_veranstaltung;
		}

		foreach (get_get('veranstaltung') as $this_bisherige_veranstaltung) {
			$additiver_stundenplan[] = $this_bisherige_veranstaltung;
		}

		$additiver_stundenplan = array_unique($additiver_stundenplan);
		setcookie('additiver_stundenplan', join(',', $additiver_stundenplan), time() + (86400 * 365));
	}

	$GLOBALS['pruefungen_already_chosen'] = array();
	if(get_cookie('geplante_pruefungsleistungen')) {
		$this_cookie = get_cookie('geplante_pruefungsleistungen');
		$json_data = json_decode($this_cookie);
		if($json_data) {
			$GLOBALS['pruefungen_already_chosen'] = $json_data;
		}
	}

	$GLOBALS['already_deleted_old_session_ids'] = 0;

	$GLOBALS['submenu_id'] = null;

	$GLOBALS['end_html'] = 1;

	$GLOBALS['slurped_sql_file'] = 0;

	$GLOBALS['deletion_page'] = 0;

	$GLOBALS['rquery_print'] = 0;

	$GLOBALS['queries'] = array();
	$GLOBALS['function_usage'] = array();

	$GLOBALS['dbh'] = '';
	$GLOBALS['reload_page'] = 0;

	$GLOBALS['get_modul_name_cache'] = array();
	$GLOBALS['user_role_cache'] = array();
	$GLOBALS['get_role_id_cache'] = array();
	$GLOBALS['get_rolle_beschreibung'] = array();
	$GLOBALS['raum_name_cache'] = array();
	$GLOBALS['get_veranstaltung_semester_cache'] = array();
	$GLOBALS['get_gebaeude_abkuerzung_cache'] = array();
	$GLOBALS['get_page_name_by_id_cache'] = array();
	$GLOBALS['create_language_array_cache'] = array();
	$GLOBALS['get_gebaeude_geo_coords_by_id_cache'] = array();
	$GLOBALS['get_language_name_cache'] = array();

	$GLOBALS['memoize'] = array();

	$GLOBALS['show_snow'] = 0;
	$GLOBALS['show_comic_sans'] = 0;

	$GLOBALS['datadir'] = './data/';

	if((date('d') == 1 && date('m') == 4 && 0) || get_get('show_comic_sans') || get_get('comic_sans') || file_exists('/etc/vvz_comic_sans')) {
		$GLOBALS['show_comic_sans'] = 1;
	}

	if((date('d') == 24 || date('d') == 25 || date('d') == 26) && date('m') == 12 || get_get('snow') || file_exists('/etc/vvz_snow')) {
		$GLOBALS['show_snow'] = 1;
	}

	include('mysql.php');

	if(!isset($GLOBALS['setup_mode'])) {
		$GLOBALS['setup_mode'] = 0;
	}

	if(file_exists('new_setup')) {
		$GLOBALS['setup_mode'] = 1;
	}

	if(!$GLOBALS['setup_mode']) {
		rquery('USE `'.$GLOBALS['dbname'].'`');
		rquery('SELECT @@FOREIGN_KEY_CHECKS');
		rquery('SET FOREIGN_KEY_CHECKS=1');
	}

	rquery("SET NAMES utf8");

	/* Login-Kram */
	$GLOBALS['logged_in_was_tried'] = 0;
	$GLOBALS['logged_in'] = 0;
	$GLOBALS['logged_in_user_id'] = NULL;
	$GLOBALS['logged_in_data'] = NULL;
	$GLOBALS['accepted_public_data'] = NULL;

	$GLOBALS['pages'] = NULL;

	if(!function_exists('set_login_data')) {
		function set_login_data ($row) {
			function_debug_counter("set_login_data");
			$GLOBALS['logged_in'] = 1;
			$GLOBALS['logged_in_data'] = $row;
			$GLOBALS['logged_in_user_id'] = $row[0];
			$GLOBALS['user_dozent_id'] = $row[2];
			$GLOBALS['user_institut_id'] = $row[3];
			$GLOBALS['user_role_id'] = get_role_id_by_user($row[0]);
			$GLOBALS['this_semester_id'] = get_and_create_this_semester();
			$GLOBALS['accepted_public_data'] = $row[4];
		}
	}

	function set_session_id ($user_id) {
		function_debug_counter("set_session_id");
		delete_old_session_ids($GLOBALS['logged_in_user_id']);
		$session_id = generate_random_string(1024);
		$query = 'INSERT IGNORE INTO `session_ids` (`session_id`, `user_id`) VALUES ('.esc($session_id).', '.esc($user_id).')';
		rquery($query);

		setcookie('session_id', $session_id, time() + 86400, "/");
	}

	if(!$GLOBALS['setup_mode']) {
		if(get_post('try_login')) {
			$GLOBALS['logged_in_was_tried'] = 1;
		}

		if(get_cookie('session_id')) {
			delete_old_session_ids();
			$query = 'SELECT `user_id`, `username`, `dozent_id`, `institut_id`, `accepted_public_data` FROM `view_user_session_id` WHERE `session_id` = '.esc($_COOKIE['session_id']).' AND `enabled` = "1"';
			$result = rquery($query);
			while ($row = mysqli_fetch_row($result)) {
				set_login_data($row);
			}
		}

		if (!$GLOBALS['logged_in'] && get_post('username') && get_post('password')) {
			delete_old_session_ids();
			$GLOBALS['logged_in_was_tried'] = 1;
			$user = $_POST['username'];
			$possible_user_id = get_user_id($user);
			$salt = get_salt($possible_user_id);
			$pass = hash('sha256', $_POST['password'].$salt);

			$query = 'SELECT `id`, `username`, `dozent_id`, `institut_id`, `accepted_public_data` FROM `users` WHERE `username` = '.esc($user).' AND `password_sha256` = '.esc($pass).' AND `enabled` = "1"';
			$result = rquery($query);
			while ($row = mysqli_fetch_row($result)) {
				set_login_data($row);
				set_session_id($row[0]);
			}
		}

		if($GLOBALS['logged_in_user_id'] && basename($_SERVER['SCRIPT_NAME']) == 'admin.php') {
			$query = 'SELECT `name`, `file`, `page_id`, `show_in_navigation`, `parent` FROM `view_account_to_role_pages` WHERE `user_id` = '.esc($GLOBALS['logged_in_user_id']).' ORDER BY `parent`, `name`';
			$result = rquery($query);

			while ($row = mysqli_fetch_row($result)) {
				$GLOBALS['pages'][$row[2]] = $row;
			}

			if(get_get('sdsg_einverstanden')) {
				$query = 'UPDATE `users` SET `accepted_public_data` = "1" WHERE `id` = '.esc($GLOBALS['logged_in_user_id']);
				rquery($query);

				$GLOBALS['accepted_public_data'] = 1;
			}
		}

		if(array_key_exists('REQUEST_URI', $_SERVER) && preg_match('/\/pages\//', $_SERVER['REQUEST_URI'])) {
			$script_name = basename($_SERVER['REQUEST_URI']);
			$page_id = get_page_id_by_filename($script_name);
			if($page_id) {
				$header = 'Location: ../admin.php?page='.$page_id;
				header($header);
			} else {
				die("Die internen Seiten dürfen nicht direkt aufgerufen werden. Die gesuchte Seite konnte nicht gefunden werden. Nehmen Sie &mdash; statt der direkten Datei-URL &mdash; den Weg über das Administrationsmenü.");
			}
		}
	}

	update_user_agent_counter();

	/* Parameter verarbeiten */

	#FUNCTION_CHECK
	if($GLOBALS['logged_in']) { // Wichtig, damit Niemand ohne Anmeldung etwas ändern kann
		# PARAMETERCHECK
		// Falls eine ID gegeben ist, dann sind bereits Daten vorhanden, die editiert oder gelöscht werden sollen.
		if(!is_null(get_post('id')) || !is_null(get_get('id'))) {
			# ID_EXISTS
			$this_id = get_post('id');
			if(!$this_id) {
				$this_id = get_get('id');
			}

			if(get_post('delete') && !get_post('delete_for_sure')) {
				# DELETE_DATA
				/*
					Festlegung der Tabellen, aus denen etwas gelöscht werden soll.
				 */
				$GLOBALS['deletion_page'] = 1;

				$GLOBALS['deletion_where'] = array('id' => $this_id);

				fill_deletion_global("pruefungstyp_name", "pruefungstyp");
				fill_deletion_global("update_pruefungsamt", "pruefungsamt");
				fill_deletion_global("update_pruefung_zeitraum", "pruefung_zeitraum");
				fill_deletion_global("update_language", "language");
				fill_deletion_global("funktion_name", "function_right");
				fill_deletion_global("update_titel", "titel");
				fill_deletion_global("delete_semester", "semester");
				fill_deletion_global("faculty_name", "institut");
				fill_deletion_global("update_veranstaltung", "veranstaltung");
				fill_deletion_global(array("studiengang", "institut_id"), "studiengang");
				fill_deletion_global(array("gebaeude_id", "raum_name"), "raum");
				fill_deletion_global(array("gebaeude_name", "abkuerzung"), "gebaeude");
				fill_deletion_global(array("updatepage", "id"), "page");
				fill_deletion_global(array("dozent_first_name", "dozent_last_name"), "dozent");
				fill_deletion_global(array("studiengang_id", "module"), "modul");
				fill_deletion_global(array("veranstaltungstyp_name", "veranstaltungstyp_abkuerzung"), "veranstaltungstyp");
				fill_deletion_global(array("pruefungstyp_id", "veranstaltung_id", "pruefungsnummer"), "pruefung");
				fill_deletion_global(array("neue_rolle", "page"), "role");
				fill_deletion_global(array("faq_update", "id"), "faq");
				fill_deletion_global(array("datum", "raum", "stunde", "pruefung", "update_nachpruefung"), "nachpruefung");
				fill_deletion_global(array("update_pruefungsnummer", "pruefungstyp"), "pruefungsnummer");
				fill_deletion_global(array("name", "id", "password", "role"), "users");
				fill_deletion_global(array("update_bereich", "id"), "bereich");
				fill_deletion_global(array("update_fach", "id"), "pruefungsnummer_fach");
				fill_deletion_global(array("update_veranstaltungstyp", "id"), "veranstaltungstyp");
			} else {
				## UPDATE_DATA

				if(get_post('update_language')) {
					$id = get_post('id');
					if(get_post('delete')) {
						delete_language($id);
					} else {
						$name = get_post('name');
						$abkuerzung = get_post('abkuerzung');
						update_language($id, $name, $abkuerzung);
					}
				}

				if(get_post('update_semester')) {
					$id = get_post('id');
					$erster_termin = get_post('erster_termin');
					if($id) {
						update_semester($id, $erster_termin);
					}
				}
				// hinweis und beschreibung nur dann eintragen, wenn strlen > 0

				if(get_post('update_dozent_titel')) {
					$id = get_post('id');
					$titel_id = get_post('titel_id');
					update_dozent_titel($id, $titel_id);
				}

				if(get_post('update_titel')) {
					$id = get_post('id');
					if(!get_post('delete')) {
						$name = get_post('name');
						$abkuerzung = get_post('titel_abkuerzung');
						update_titel($id, $name, $abkuerzung);
					} else {
						delete_titel($id);
					}
				}

				if(get_post('newpage')) {
					$titel = get_post('titel');
					$datei = get_post('datei');
					$show_in_navigation = get_post('show_in_navigation') ? 1 : 0;
					$eltern = get_post('eltern') ? get_post('eltern') : '';
					$role_to_page = get_post('role_to_page');
					$beschreibung = get_post('beschreibung') ? get_post('beschreibung') : '';
					$hinweis = get_post('hinweis') ? get_post('hinweis') : '';

					if(isset($titel) && isset($datei) && isset($show_in_navigation) && isset($eltern) && isset($role_to_page) && isset($beschreibung) && isset($hinweis)) {

						create_new_page($titel, $datei, $show_in_navigation, $eltern, $role_to_page, $beschreibung, $hinweis);
					} else {
						error('Missing parameters!');
					}
				}

				if(get_post('updatepage')) {
					$id = get_post('id');
					if(get_post('delete')) {
						if(isset($id)) {
							delete_page($id);
						}
					} else {
						$titel = get_post('titel');
						$datei = get_post('datei');
						$show_in_navigation = get_post('show_in_navigation') ? 1 : 0;
						$eltern = get_post('eltern') ? get_post('eltern') : '';
						$role_to_page = get_post('role_to_page');
						$beschreibung = get_post('beschreibung') ? get_post('beschreibung') : '';
						$hinweis = get_post('hinweis') ? get_post('hinweis') : '';

						if(isset($id) && isset($titel) && isset($show_in_navigation) && isset($role_to_page) && isset($beschreibung)) {

							update_page_full($id, $titel, $datei, $show_in_navigation, $eltern, $role_to_page, $beschreibung, $hinweis);
						} else {
							error('Missing parameters!');
						}
					}
				}

				if(get_post('frage') && get_post('antwort') && get_post('faq_update')) {
					if(get_post('delete')) {
						delete_faq($this_id);
					} else {
						update_faq($this_id, get_post('frage'), get_post('antwort'), get_post('wie_oft_gestellt'));
					}
				}

				if(get_post('update_pruefung_zeitraum')) {
					if(get_post('delete')) {
						delete_pruefung_zeitraum($this_id);
					} else {
						$name = get_post('name');
						update_pruefung_zeitraum($this_id, $name);
					}
				}

				if(get_post('update_pruefungsamt')) {
					if(get_post('delete')) {
						delete_pruefungsamt($this_id);
					} else {
						$name = get_post('name');
						update_pruefungsamt($this_id, $name);
					}
				}

				if(get_post('update_bereich')) {
					if(get_post('delete')) {
						delete_bereich($this_id);
					} else {
						$name = get_post('name');
						update_bereich($this_id, $name);
					}
				}

				// TODO LISTE
				if(get_post('update_pruefungsnummer_fach')) {
					if(get_post('delete')) {
						delete_fach($this_id);
					} else {
						$name = get_post('name');
						update_fach($this_id, $name);
					}			
				}

				if(get_post('delete_semester')) {
					delete_semester($this_id);
				}

				if(get_post('pruefungen_kopieren') && get_post('kopieren_von')) {
					kopiere_pruefungen_von_nach(get_post('kopieren_von'), $this_id, get_post('delete_old_data'));
				}

				if(get_post('raumplanung_bearbeiten')) {
					$gebaeude = get_post('gebaeude');
					$raum = get_post('raum');
					$meldungsdatum = get_post('meldungsdatum');

					update_raumplanung($this_id, $gebaeude, $raum, $meldungsdatum);
				}

				if(get_post('pruefungstyp_name')) {
					if(get_post('delete')) {
						delete_pruefungstyp($this_id);
					} else {
						update_pruefungstyp($this_id, get_post('pruefungstyp_name'));
					}
				}

				// TODO LISTE
				if(get_post('funktion_name')) {
					if(get_post('delete')) {
						delete_funktion_rights($this_id);
					} else {
						update_funktion_rights($this_id, get_post('funktion_name'));
					}
				}

				if(get_post('institut_id') && get_post('studiengang')) {
					if(get_post('delete')) {
						delete_studiengang($this_id);
					} else {
						update_studiengang($this_id, get_post('studiengang'), get_post('institut_id'), get_post('studienordnung'));
					}
				}

				// TODO LISTE
				if(get_post('gebaeude_id') && get_post('raum_name')) {
					if(get_post('delete')) {
						delete_raum($this_id);
					} else {
						update_raum($this_id, get_post('raum_name'), get_post('gebaeude_id'));
					}
				}

				if(get_post('dozent_first_name') && get_post('dozent_last_name')) {
					if(get_post('delete')) {
						delete_dozent($this_id);
					} else {
						update_dozent($this_id, get_post('dozent_first_name'), get_post('dozent_last_name'), get_post("ausgeschieden"));
					}
				}

				if(get_post('gebaeude_name') && get_post('abkuerzung')) {
					if(get_post('delete')) {
						delete_gebaeude($this_id);
					} else {
						update_gebaeude($this_id, get_post('gebaeude_name'), get_post('abkuerzung'));
					}
				}

				if(get_post('faculty_name')) {
					if(get_post('delete')) {
						delete_institut($this_id);
					} else {
						update_institut($this_id, get_post('faculty_name'), get_post('start_nr'));
					}
				}

				if(get_post('studiengang_id') && get_post('module')) {
					if(get_post('delete')) {
						delete_modul($this_id);
					} else {
						update_modul($this_id, get_post('module'), get_post('studiengang_id'), get_post('beschreibung'), get_post("abkuerzung"));
					}
				}

				if(get_post('veranstaltungstyp_name') && get_post('veranstaltungstyp_abkuerzung')) {
					if(get_post('delete')) {
						delete_veranstaltungstyp($this_id);
					} else {
						update_veranstaltungstyp($this_id, get_post('veranstaltungstyp_name'), get_post('veranstaltungstyp_abkuerzung'));
					}
				}

				if(get_post('update_veranstaltung')) {
					if(get_post('delete')) {
						delete_veranstaltung($this_id);
					} else {
						if(get_post('name') && get_post('dozent') && get_post('veranstaltungstyp') && get_post('institut') && get_post('semester')) {
							update_veranstaltung(get_post('id'), get_post('name'), get_post('dozent'), get_post('veranstaltungstyp'), get_post('institut'), get_post('semester'), get_get('master_niveau'));
							if(get_post('speichern_metainfos')) {
								if(preg_match('/^\d+$/', $this_id)) {
									header('Location: admin.php?page='.get_page_id_by_filename('veranstaltung.php').'&id='.$this_id);
								}
							}
						} else if (get_post('update_veranstaltung')) {
							message('Für eine Veranstaltung muss Titel, Dozent, Veranstaltungstyp, Semester und das Institut eingegeben werden.');
						}
					}
				}

				// TODO LISTE
				if(get_post('pruefungstyp_id') && get_post('veranstaltung_id') && get_post('pruefungsnummer')) {
					if(get_post('delete')) {
						delete_pruefung($this_id);
					} else {
						update_pruefung($this_id, get_post('pruefungstyp_id'), get_post('veranstaltung_id'), get_post('pruefungsnummer'), get_post('pruefungsname'), get_post('datum'), get_post('stunde'), get_post('raum'));
					}
				}

				// TODO LISTE
				if(get_post('datum') && get_post('raum') && get_post('stunde') && get_post('pruefung') && get_post('update_nachpruefung')) {
					if(get_post('delete')) {
						delete_nachpruefung($this_id);
					} else {
						update_nachpruefung($this_id, get_post('pruefung'), get_post('datum'), get_post('raum'), get_post('stunde'));
					}
				} else if (get_post('update_nachpruefung')) {
					message('Die Nachprüfung muss ein Datum, einen Raum, eine Stunde und eine Prüfungsnummer haben.');
				}

				if(get_post('update_page_info')) {
					update_page_info(get_post('id'), get_post('info'));
				}

				if(get_post('neue_rolle') && get_post('page')) {
					if(get_post('delete')) {
						delete_role($this_id);
					} else {
						update_role($this_id, get_post('neue_rolle'), get_post("beschreibung"));
						$query = 'DELETE FROM `role_to_page` WHERE `role_id` = '.esc(get_role_id(get_post('neue_rolle')));;
						rquery($query);
						foreach (get_post('page') as $key => $this_page_id) {
							if(preg_match('/^\d+$/', $this_page_id)) {
								assign_page_to_role(get_role_id(get_post('neue_rolle')), $this_page_id);
							}
						}
					}
				}

				if(get_post('update_pruefungsnummer') && get_post('pruefungstyp')) {
					if(get_post('delete')) {
						delete_pruefungsnummer($this_id);
					} else {
						update_pruefungsnummer(get_post('id'), get_post('modul_id'), get_post('pruefungsnummer'), get_post('pruefungstyp'), get_post('bereich'), get_post('modulbezeichnung'), get_post('zeitraum'), get_post('pndisabled'));
					}

				}

				if(get_post('name') && get_post('id') && get_post('role')) {
					if(get_post('delete')) {
						delete_user($this_id);
					} else {
						$enabled = get_account_enabled_by_id($this_id);
						if(get_post('disable_account')) {
							$enabled = 0;
						}

						if(get_post('enable_account')) {
							$enabled = 1;
						}

						$barrierefrei = 0;
						if(get_post('barrierefrei')) {
							$barrierefrei = 1;
						}

						$accpubdata = 0;
						if(get_post('accepted_public_data')) {
							$accpubdata = 1;
						}

						update_user(get_post('name'), get_post('id'), get_post('password'), get_post('role'), get_post('dozent'), get_post('institut'), $enabled, $barrierefrei, $accpubdata);
					}
				}

				if(get_post_multiple_check(array('update_einzelne_veranstaltung', 'tag', 'stunde', 'woche'))) {
					$tag = get_post('tag');
					$stunde = get_post('stunde');
					$woche = get_post('woche');
					$erster_termin = get_post('erster_termin');
					$anzahl_hoerer = get_post('anzahl_hoerer');
					$wunsch = get_post('wunsch');
					$hinweis = get_post('hinweis');
					$opal_link = get_post('opal_link');
					$abgabe_pruefungsleistungen = get_post('abgabe_pruefungsleistungen');
					$gebaeudewunsch = get_post('gebaeudewunsch');
					$raumwunsch = null;
					$master_niveau = get_post('master_niveau');
					if($master_niveau) {
						$master_niveau = 1;
					} else {
						$master_niveau = 0;
					}
					$language = get_post('language');
					if($gebaeudewunsch && get_post('raumwunsch')) {
						$raumwunsch = get_and_create_raum_id($gebaeudewunsch, get_post('raumwunsch'));
					}

					$related_veranstaltung = get_post('related_veranstaltung');

					$einzelne_termine = get_einzelne_termine_from_post();


								#	1	2	3	4	5		6		7	8		9	10				11		12		13				14
					update_veranstaltung_metadata($this_id, $tag, $stunde, $woche, $erster_termin, $anzahl_hoerer, $wunsch, $hinweis, $opal_link, $abgabe_pruefungsleistungen, $raumwunsch, $gebaeudewunsch, get_post('pruefungsnummer'), $master_niveau, $language, $related_veranstaltung, $einzelne_termine);
				}

			// ist keine Id gegeben, sind es neue Daten. Aufgrund der Parameternamen wird dann entschieden, was wo einzutragen ist.
			}

			if(get_post_multiple_check(array('neue_veranstaltung', 'dozent', 'veranstaltungstyp', 'institut', 'name'))) {
				$name = get_post('name');
				$dozent = get_post('dozent');
				$veranstaltungstyp = get_post('veranstaltungstyp');
				$institut = get_post('institut');
				$semester = get_post('semester');
				$language = get_post('language');
				$related_veranstaltung = get_post('related_veranstaltung');

				create_veranstaltung($name, $dozent, $veranstaltungstyp, $institut, $semester, $language, $related_veranstaltung);
			} else if (get_post('neue_veranstaltung')) {
				error('Für eine Veranstaltung muss ein Name, ein Dozent, der Typ der Institut und ein Veranstaltungstyp definiert sein. Sofern Sie kein Administrator sind, muss Ihrem Account zum Erstellen von Veranstaltungen ein Dozent zugewiesen sein. Bitte kontaktieren Sie die <a href="kontakt.php">Administratoren</a>, damit Ihr Account diese Zuordnung bekommt.');
			}
		} else {
			# NO_ID
			if(get_post('update_right_to_user_role')) {
				$role_rights = array();
				foreach ($_POST as $key => $value) {
					if(preg_match('/checkbox_(\d+)_(\d+)/', $key, $founds)) {
						$right_id = $founds[1];
						$role_id = $founds[2];
						@$role_rights[$right_id][$role_id] = 1;
					}
				}

				update_right_to_user_role($role_rights);
			}

			if(get_post('update_right_to_page')) {
				$page_rights = array();
				foreach ($_POST as $key => $value) {
					if(preg_match('/checkbox_(\d+)_(\d+)/', $key, $founds)) {
						$right_id = $founds[1];
						$page_id = $founds[2];
						@$page_rights[$right_id][$page_id] = 1;
					}
				}

				update_right_to_page($page_rights);
			}

			if(get_post('editable_users') && get_post('dozent_id')) {
				$editable_users = get_post('editable_users');
				$dozent_id = get_post('dozent_id');

				update_superdozent($dozent_id, $editable_users);
			}

			if(get_post('update_modul_semester_data')) {
				$semester = get_post('semester');
				$studiengang = get_post('studiengang');
				$modul = get_post('modul');
				$credit_points = get_post('credit_points');
				$pruefungsleistung_anzahl = get_post('pruefungsleistung_anzahl');

				$veranstaltungstypen_anzahl = array();

				foreach ($_POST as $this_post => $this_post_data) {
					$founds = array();
					if(preg_match("/^veranstaltungstyp_(\d+)$/", $this_post, $founds)) {
						$this_veranstaltungstyp = $founds[1];
						if(preg_match("/^\d+$/", $this_veranstaltungstyp)) {
							$veranstaltungstypen_anzahl[$this_veranstaltungstyp] = $this_post_data;
						} else {
							error('Der Veranstaltungstyp muss als ID gegeben werden!');
						}
					}
				}

				update_modul_semester_data($semester, $studiengang, $credit_points, $pruefungsleistung_anzahl, $veranstaltungstypen_anzahl, $modul);
			}

			if(get_post('pruefungsamt_nach_studiengang_zuordnung')) {
				$pruefungsamt_id = get_post('pruefungsamt_id');
				if($pruefungsamt_id) {
					update_pruefungsamt_studiengang($pruefungsamt_id, get_post('checked_studiengaenge'));
				} else {
					error('Es muss eine Prüfungsamt-ID angegeben werden!');
				}
			}

			if(get_post('create_pruefungsamt')) {
				create_pruefungsamt(get_post('new_name'));
			}

			if(get_post('create_titel')) {
				create_title(get_post('new_name'), get_post('new_titel_abkuerzung'));
			}

			if(get_post('create_pruefung_zeitraum')) {
				create_pruefung_zeitraum(get_post('new_name'));
			}

			if(get_post('setze_semester')) {
				setze_semester(get_post('setze_semester'));
			}

			if(get_post('merge_data')) {
				if(get_get('table') && get_post('merge_from') && get_post('merge_to')) {
					merge_data(get_get('table'), get_post('merge_from'), get_post('merge_to'));
				} else {
					error(' Sowohl eine bzw. mehrere Quelle als auch ein Zielort müssen angegeben werden.');
				}
			}

			if(get_post('new_function_right')) {
				$funktion_name = get_post('funktion_name');
				if($funktion_name) {
					create_function_right($role_id, $funktion_name);
				} else {
					error('Die Funktion konnte nicht angelegt werden, da sie keinen validen Namen zugeordnet bekommen hat.');
				}
			}

			if(get_post('import_datenbank')) {
				if(array_key_exists('sql_file', $_FILES) && array_key_exists('tmp_name', $_FILES['sql_file'])) {
					SplitSQL($_FILES['sql_file']['tmp_name']);
				}
			}

			if(get_post('datenbankvergleich')) {
				if(array_key_exists('sql_file', $_FILES) && array_key_exists('tmp_name', $_FILES['sql_file'])) {
					$GLOBALS['compare_db'] = compare_db($_FILES['sql_file']['tmp_name']);
				}
			}

			if(get_post('create_fach')) {
				create_fach(get_post('new_name'));
			}

			if(get_post('create_bereich')) {
				create_bereich(get_post('new_name'));
			}

			if(get_post('modul_nach_semester') && get_post('modul')) {
				update_modul_semester(get_post('modul'), get_post('semester'));
			}

			if(get_post('update_barrierefrei')) {
				$barrierefrei = 0;
				if(get_post('barrierefrei')) {
					$barrierefrei = 1;
				}
				update_barrierefrei($barrierefrei);
			}

			if(get_post('change_own_data')) {
				$new_password = get_post('password');
				$new_password_repeat = get_post('password_repeat');
				if($new_password && strlen($new_password) >= 5) {
					if($new_password == $new_password_repeat) {
						$barrierefrei = 0;
						if(get_post('barrierefrei')) {
							$barrierefrei = 1;
						}
						update_own_data($new_password, $barrierefrei);
					} else {
						error('Beide Passworteingaben müssen identisch sein.');
					}
				} else {
					error('Das Passwort muss mindestens 5 Zeichen haben.');
				}
			}

			if(get_post('api_change')) {
				$auth_code = get_post('auth_code');
				if(get_post('delete')) {
					delete_api($auth_code);
				} else {
					$ansprechpartner = get_post('ansprechpartner');
					$email = get_post('email');
					$grund = get_post('grund');

					update_api($auth_code, $email, $ansprechpartner, $grund);
				}
			}

			if(get_post('api_new')) {
				$ansprechpartner = get_post('ansprechpartner');
				$email = get_post('email');
				$grund = get_post('grund');

				create_api($email, $ansprechpartner, $grund);			
			}

			if(get_post('startseitentext')) {
				$startseitentext = get_post('startseitentext');
				update_startseitentext($startseitentext);
			}

			if(get_post('neue_pruefungsnummer')) {
				if(get_post('modul') && get_post('pruefungstyp')) {
					create_pruefungsnummer(get_post('modul'), get_post('pruefungsnummer'), get_post('pruefungstyp'), get_post('bereich'), get_post('modulbezeichnung'), get_post('zeitraum'));
				}
			}

			if(get_post('update_text') && get_post('page_id')) {
				update_text(get_post('page_id'), get_post('text'));
			}

			if(get_post('update_hinweis') && get_post('page_id')) {
				update_hinweis(get_post('page_id'), get_post('hinweis'));
			}

			if(get_post_multiple_check(array('new_user', 'name', 'password', 'role'))) {
				$barrierefrei = 0;
				if(get_post('barrierefrei')) {
					$barrierefrei = 1;
				}
				create_user(get_post('name'), get_post('password'), get_post('role'), get_post('dozent'), get_post('institut'), $barrierefrei);
			} else if (get_post('new_user')) {
				warning('Benutzer müssen einen Namen, ein Passwort und eine Rolle haben.');
			}

			if(get_post('neue_rolle') && get_post('page')) {
				create_role(get_post('neue_rolle'), get_post("beschreibung"));
				// Alle alten Rollendaten löschen
				$query = 'DELETE FROM `role_to_page` WHERE `role_id` = '.esc(get_role_id(get_post('neue_rolle')));
				rquery($query);
				foreach (get_post('page') as $key => $this_page_id) {
					if(preg_match('/^\d+$/', $this_page_id)) {
						assign_page_to_role(get_role_id(get_post('neue_rolle')), $this_page_id);
					}
				}
			}
			
			$new_faculty_name = get_post('new_faculty_name');
			if(strlen($new_faculty_name)) {
				create_institut($new_faculty_name, get_post('new_start_nr'));
			} else if (isset($new_faculty_name)) {
				error('Jede Institut muss einen Namen haben.');
			}

			if(get_post('create_gebaeude')) {
				if(get_post('new_gebaeude_name') && get_post('new_abkuerzung')) {
					create_gebaeude(get_post('new_gebaeude_name'), get_post('new_abkuerzung'));
				} else if(get_post('new_gebaeude_name') || get_post('new_abkuerzung')) {
					message('Gebäude müssen Namen <b>und</b> Abkürzung haben.');
				}
			}

			if(get_post('create_language')) {
				$name = get_post('new_name');
				$abkuerzung = get_post('new_abkuerzung');

				create_language($name, $abkuerzung);
			}

			if(get_post('new_dozent_first_name') && get_post('new_dozent_last_name')) {
				create_dozent(get_post('new_dozent_first_name'), get_post('new_dozent_last_name'));
			} else if (get_post('new_dozent_first_name') || get_post('new_dozent_last_name')) {
				message("Dozenten müssen Vor- und Nachnamen haben.");
			}

			if(get_post('new_pruefungstyp_name')) {
				create_pruefungstyp(get_post('new_pruefungstyp_name'));
			}

			if(get_post('institut_id') && get_post('new_studiengang')) {
				create_studiengang(get_post('new_studiengang'), get_post('institut_id'), get_post('studienordnung'), get_post('bereich'));
			} else if(get_post('institut_id') || get_post('new_studiengang')) {
				message('Jeder Studiengang muss einer Institut zugeordnet werden.');
			}

			if(get_post('studiengang_id') && get_post('new_module')) {
				create_modul(get_post('new_module'), get_post('studiengang_id'), get_post("beschreibung"), get_post("abkuerzung"));
			} else if(get_post('studiengang_id') || get_post('new_module')) {
				message('Ein Modul muss einem Studiengang zugeordnet sein.');
			}

			if(get_post('new_veranstaltungstyp_name') && get_post('new_veranstaltungstyp_abkuerzung')) {
				create_veranstaltungstyp(get_post('new_veranstaltungstyp_name'), get_post('new_veranstaltungstyp_abkuerzung'));
			} else if(get_post('new_veranstaltungstyp_name') && get_post('new_veranstaltungstyp_abkuerzung')) {
				message('Es müssen Veranstaltungstyp-Name und Abkürzung eingetragen werden.');
			}

			if(get_post('pruefungstyp_id') && get_post('veranstaltung_id') && get_post('pruefungsnummer')) {
				create_pruefung (get_post('pruefungstyp_id'), get_post('veranstaltung_id'), get_post('pruefungsname'), get_post('pruefungsnummer'), get_post('datum'), get_post('stunde'), get_post('raum'));
			} else if (get_post('neue_pruefung')) {
				message('Für eine Prüfung muss der Prüfungstyp, die Veranstaltung und die Prüfungsnummer gegeben sein.');
			}

			if(get_post('create_faq')) {
				$frage = get_post('frage');
				$antwort = get_post('antwort');
				$wie_oft_gestellt = get_post('wie_oft_gestellt');

				if($frage && $antwort) {
					create_faq($frage, $antwort, $wie_oft_gestellt);
				}
			}
		}
	}

	if($GLOBALS['setup_mode']) {
		if(get_post('import_datenbank')) {
			rquery('USE `'.$GLOBALS['dbname'].'`');
			if(array_key_exists('sql_file', $_FILES) && array_key_exists('tmp_name', $_FILES['sql_file'])) {
				SplitSQL($_FILES['sql_file']['tmp_name']);
			}
		}
	}

	function htmle ($str, $shy = 0) {
		function_debug_counter("htmle");
		if($shy) {
			if($str) {
				$str = htmlentities($str);
				$str = preg_replace('/Philosophie/', 'Phi&shy;lo&shy;so&shy;phie', $str);
				$str = preg_replace('/Wissenschaft/', 'Wis&shy;sen&shy;schaft', $str);
				$str = preg_replace('/Erkenntnis/', 'Er&shy;kennt&shy;nis', $str);
				$str = preg_replace('/Theorie/', 'Theo&shy;rie', $str);
				$str = preg_replace('/Sprachphilosophie/', 'Sprach&shy;phi&shy;lo&shy;so&shy;phie', $str);
				$str = preg_replace('/Religion/', 'Re&shy;li&shy;gion', $str);
				$str = preg_replace('/Anthropologie/', 'An&shy;thro&shy;po&shy;lo&shy;gie', $str);
				$str = preg_replace('/Moralphilosophie/', 'Mo&shy;ral&shy;phi&shy;lo&shy;so&shy;phie', $str);
				$str = preg_replace('/Philosophische/', 'Phi&shy;lo&shy;so&shy;phi&shy;sche', $str);
				$str = preg_replace('/philosophie/', 'phi&shy;lo&shy;so&shy;phie', $str);
				$str = preg_replace('/Seminararbeit/', 'Se&shy;mi&shy;nar&shy;ar&shy;beit', $str);
				return $str;
			} else {
				return '&mdash;';
			}
		} else {
			if($str) {
				return htmlentities($str);
			} else {
				return '&mdash;';
			}
		}
	}

	function update_right_to_user_role ($role_rights) {
		function_debug_counter("update_right_to_user_role");
		if(!check_function_rights(__FUNCTION__)) { return; }

		$error = 0;

		start_transaction();

		foreach ($role_rights as $right_id => $roles) {
			$result = rquery("delete from function_right_to_user_role where function_right_id = ".esc($right_id));
			if($result) {
				foreach ($roles as $this_role_key => $this_role_value) {
					$query = "insert into function_right_to_user_role (function_right_id, role_id) values (".esc($right_id).", ".esc($this_role_key).")";
					$result = rquery($query);
					if(!$result) {
						$error = 1;
						rollback();
						error('Konnte die neuen Rollendaten nicht einfügen!');
					}
				}
			} else {
				$error = 1;
				rollback();
				error('Konnte die alten Rollendaten nicht löschen!');
			}
		}

		if($error) {
			error('Es trat ein Fehler auf. Alle Änderungen wurden rückgängig gemacht.');
		} else {
			success('Die Daten wurden erfolgreich geändert.');
		}
	}

	function update_right_to_page ($page_rights) {
		function_debug_counter("update_right_to_page");
		if(!check_function_rights(__FUNCTION__)) { return; }

		$error = 0;

		start_transaction();

		foreach ($page_rights as $right_id => $pages) {
			$result = rquery("delete from function_right_to_page where function_right_id = ".esc($right_id));
			if($result) {
				foreach ($pages as $this_page_key => $this_page_value) {
					$result = rquery("insert into function_right_to_page (function_right_id, page_id) values (".esc($right_id).", ".esc($this_page_key).")");
					if(!$result) {
						$error = 1;
						rollback();
						error('Konnte die neuen Seitendaten nicht einfügen!');
					}
				}
			} else {
				$error = 1;
				rollback();
				error('Konnte die alten Seitendaten nicht löschen!');
			}
		}

		if($error) {
			error('Es trat ein Fehler auf. Alle Änderungen wurden rückgängig gemacht.');
		} else {
			success('Die Daten wurden erfolgreich geändert.');
		}
	}

	function get_bereich_name_by_id ($id) {
		function_debug_counter("get_bereich_name_by_id");
		if(!strlen($id)) {
			return null;
		}
		$key = "get_bereich_name_by_id($id)";
		if(array_key_exists($key, $GLOBALS['memoize'])) {
			return $GLOBALS['memoize'][$key];
		}
		$query = 'SELECT `name` FROM `bereich` WHERE `id` = '.esc($id);
		$result = rquery($query);

		$name = 0;

		while ($row = mysqli_fetch_row($result)) {
			$name = $row[0];
		}

		$GLOBALS['memoize'][$key] = $name;

		return $name;
	}

	function user_braucht_barrierefreien_zugang ($dozent) {
		function_debug_counter("user_braucht_barrierefreien_zugang");
		$key = '';
		if(is_array($dozent)) {
			$key = "user_braucht_barrierefreien_zugang(".join(', ', $dozent).")";
		} else {
			$key = "user_braucht_barrierefreien_zugang($dozent)";
		}
		if(array_key_exists($key, $GLOBALS['memoize'])) {
			return $GLOBALS['memoize'][$key];
		}
		$query = 'SELECT `dozent_id`, `barrierefrei` FROM `view_user_to_role` WHERE `barrierefrei` = "1"';
		if(is_array($dozent) && count($dozent)) {
			$query .= ' AND `dozent_id` IN ('.join(', ', array_map('esc', $dozent)).')';
		} else if ($dozent) {
			$query .= ' AND `dozent_id` = '.esc($dozent);
		}
		$result = rquery($query);

		$barrierefrei = array();

		while ($row = mysqli_fetch_row($result)) {
			if(is_array($dozent)) {
				$barrierefrei[$row[0]] = 1;
			} else {
				$barrierefrei = 1;
			}
		}

		$GLOBALS['memoize'][$key] = $barrierefrei;

		return $barrierefrei;
	}

	function get_user_by_dozent ($dozent_id) {
		function_debug_counter("get_user_by_dozent");
		$query = 'SELECT `id` FROM `users` WHERE `dozent_id` = '.esc($dozent_id);
		return get_single_row_from_query($query);
	}

	// http://stackoverflow.com/questions/13646690/how-to-get-real-ip-from-visitor

	function compare_db ($file, $session_ids = 0) {
		function_debug_counter("compare_db");
		if(!check_function_rights(__FUNCTION__)) { return; }
		if(file_exists($file)) {
			$skip = array();
			if(!$session_ids) {
				$skip = array('session_ids');
			}
			$now = backup_tables('*', $skip);
			$then = file_get_contents($file);

			if(strlen($then)) {
				require_once dirname(__FILE__).'/Classes/Diff.php';

				$a = explode("\n", $then);
				$b = explode("\n", $now);

				$options = array();

				$diff = new Diff($a, $b, $options);
				require_once dirname(__FILE__).'/Classes/Diff/Renderer/Html/SideBySide.php';
				$renderer = new Diff_Renderer_Html_SideBySide;
				$tdiff = $diff->Render($renderer);
				if($tdiff) {
					return $tdiff;
				} else {
					error('Das Diff konnte nicht erzeugt werden oder war leer.');
				}
			} else if (!$now) {
				error('Das Image der aktuellen Datenbank konnte nicht erstellt werden.');
			} else {
				error('Die Vergleichsdatei darf nicht leer sein.');
			}
		} else {
			error('Die Datei konnte nach dem Hochladen nicht gefunden werden. Bitte die Apache-Konfiguration überprüfen!');
		}
	}

	// https://stackoverflow.com/questions/1883079/best-practice-import-mysql-file-in-php-split-queries
	function SplitSQL($file, $delimiter = ';') {
		function_debug_counter("SplitSQL");
		if(!$GLOBALS['setup_mode']) {
			if(!check_function_rights(__FUNCTION__)) { return; }
		}

		$GLOBALS['slurped_sql_file'] = 1;
		set_time_limit(0);

		if (is_file($file) === true) {
			$file = fopen($file, 'r');
			$GLOBALS['install_counter'] = 1;

			if (is_resource($file) === true) {
				$query = array();

				while (feof($file) === false) {
					$query[] = fgets($file);

					if(preg_match('~' . preg_quote($delimiter, '~') . '\s*$~iS', end($query)) === 1) {
						$query = trim(implode('', $query));

						stderrw(">>> ".($GLOBALS['install_counter']++).": $query\n");

						if (rquery($query) === false) {
							print '<h3>ERROR: '.htmlentities($query).'</h3>'."\n";
						}

						while (ob_get_level() > 0) {
							ob_end_flush();
						}

						flush();
					}

					if (is_string($query) === true) {
						$query = array();
					}
				}

				return fclose($file);
			}
		}

		return false;
	}

	function table_exists ($db, $table) {
		function_debug_counter("table_exists");
		$query = "SELECT table_name FROM information_schema.tables WHERE table_schema = ".esc($db)." AND table_name = ".esc($table);
		$result = mysqli_query($GLOBALS['dbh'], $query);
		$table_exists = 0;
		while ($row = mysqli_fetch_row($result)) {
			$table_exists = 1;
		}
		return $table_exists;
	}

	function show_create_table ($dbname, $table, $noviews = 0) {
		function_debug_counter("show_create_table");
		$data = mysqli_fetch_row(rquery('SHOW CREATE TABLE '.$dbname.'.'.$table));
		$data = preg_replace('/CHARSET=latin1/', 'CHARSET=utf8', $data);
		if($noviews) {
			if(preg_match('/^CREATE TABLE/i', $row2[1])) {
				return $data;
			} else {
				return null;
			}
		} else {
			return $data;
		}
	}
	/* https://davidwalsh.name/backup-mysql-database-php */
	function backup_tables ($tables = '*', $skip = null, $data = 1) {
		function_debug_counter("backup_tables");
		if(!$GLOBALS['setup_mode']) {
			if(!check_function_rights(__FUNCTION__)) { return; }
		}

		rquery('USE `'.$GLOBALS['dbname'].'`');
		//get all of the tables
		if($tables == '*') {
			$tables = array();
			$tmp_tables = get_all_tables();

			foreach ($tmp_tables as $row) {
				if(!((is_array($skip) && array_search($row, $skip)) || (!is_array($skip) && $row == $skip))) {
					$tables[] = $row;
				}
			}
		} else {
			$tables = is_array($tables) ? $tables : explode(',', $tables);
		}
		
		$return = "SET FOREIGN_KEY_CHECKS=0;\n";
		$return .= "DROP DATABASE `".$GLOBALS['dbname']."`;\n";
		$return .= "CREATE DATABASE `".$GLOBALS['dbname']."`;\n";
		$return .= "USE `".$GLOBALS['dbname']."`;\n";

		foreach(sort_tables($tables) as $table) {
			$result = rquery('SELECT * FROM '.$table);
			$num_fields = mysqli_field_count($GLOBALS['dbh']);

			$this_return = '';
			
			$row2 = show_create_table($GLOBALS['dbname'], $table);
			if(preg_match('/^CREATE TABLE/i', $row2[1])) {
				$this_return .= 'DROP TABLE IF EXISTS '.$table.';';
			} else {
				$this_return .= 'DROP VIEW IF EXISTS '.$table.';';
			}

			$this_return.= "\n\n".$row2[1].";\n\n";

			if(preg_match('/^CREATE TABLE/i', $row2[1])) {
				for ($i = 0; $i < $num_fields; $i++) {
					if($data) {
						while($row = mysqli_fetch_row($result)) {
							$this_return.= 'INSERT INTO `'.$table.'` VALUES(';
							for($j = 0; $j < $num_fields; $j++) {
								$row[$j] = esc($row[$j]);
								if (isset($row[$j])) {
									$this_return .= $row[$j];
								} else {
									$this_return .= 'NULL';
								}
								if ($j < ($num_fields - 1)) {
									$this_return .= ', ';
								}
							}
							$this_return .= ");\n";
						}
					}
				}
			}

			$return .= "$this_return\n";
		}
		
		$return .= "\n\n\nSET FOREIGN_KEY_CHECKS=1;\n";
		return $return;
	}

	function sort_tables ($tables) {
		function_debug_counter("sort_tables");
		$create_views = array();
		$create_tables = array();

		foreach ($tables as $table) {
			if(preg_match('/^view_|^ua_overview$/', $table)) {
				$create_views[] = $table;
			} else {
				$create_tables[] = $table;
			}
		}

		$tables_sorted_tmp = array();

		foreach ($create_tables as $table) {
			$foreign_keys = get_foreign_key_tables($GLOBALS['dbname'], $table);
			$foreign_keys_counter = 0;
			if(array_key_exists(0, $foreign_keys)) {
				$foreign_keys_counter = count($foreign_keys[0]);
			}
			$tables_sorted_tmp[] = array('name' => $table, 'foreign_keys_counter' => $foreign_keys_counter);
		}

		usort($tables_sorted_tmp, 'foreignKeyAscSort');

		foreach ($tables_sorted_tmp as $table) {
			$tables_sorted[] = $table['name'];
		}

		foreach ($create_views as $view) {
			$tables_sorted[] = $view;
		}

		return $tables_sorted;
	}

	function foreignKeyAscSort($item1, $item2) {
		function_debug_counter("foreignKeyAscSort");
		if ($item1['foreign_keys_counter'] == $item2['foreign_keys_counter']) {
			return 0;
		} else {
		        return ($item1['foreign_keys_counter'] < $item2['foreign_keys_counter']) ? -1 : 1;
		}
	}

	function get_user_ip () {
		function_debug_counter("get_user_ip");
		$client = $_SERVER['REMOTE_ADDR'];

		if(filter_var($client, FILTER_VALIDATE_IP)) {
			$ip = $client;
		}

		return $ip;
	}

	function get_all_tables ($db) {
		function_debug_counter("get_all_tables");
		$tables = array();
		$result = rquery('SHOW TABLES');
		while($row = mysqli_fetch_row($result)) {
			$tables[] = $row[0];
		}
		return $tables;
	}

	function make_all_foreign_keys_on_delete_cascade () {
		function_debug_counter("make_all_foreign_keys_on_delete_cascade");
		warning("<span class='red_text text_30px'>Lasse make_all_foreign_keys_on_delete_cascade() laufen. ICH HOFFE DU HAST EIN BACKUP DER DATENBANK!</span>\n");
		$tables = get_all_tables();
		$todo_tables = array();
		foreach ($tables as $this_table) {
			$references = array("start" => $this_table, "data" => get_referencing_foreign_keys($GLOBALS['dbname'], $this_table, 0));
			if(count($references["data"])) {
				foreach ($references['data'] as $this_data) {
					if($this_data['on_delete'] != 'CASCADE' && $this_data['on_delete'] != 'SET NULL') {
						$todo_tables[] = $this_data;
					}
				}
			}
		}

		foreach ($todo_tables as $this_todo_table) {
			# alter table footable drop foreign key fooconstraint
			start_transaction();
			$query = 'ALTER TABLE '.$this_todo_table['database'].'.'.$this_todo_table['table'].' drop foreign key '.$this_todo_table['constraint_name'];
			message("$query\n");
			$res = rquery($query);
			if(!$res) {
				rollback();
				error("ERROR!");
			} else {
				$query = 'ALTER TABLE '.$this_todo_table['database'].'.'.$this_todo_table['table'].' ADD CONSTRAINT '.$this_todo_table['constraint_name'].' FOREIGN KEY ('.$this_todo_table['column'].') REFERENCES '.$this_todo_table['reference_table'].'('.$this_todo_table['reference_column'].') ON DELETE CASCADE;';
				message("$query\n");
				$res = rquery($query);
				if(!$res) {
					rollback();
					error("ERROR!");
				} else {
                    commit();
                    success("OK!");
				}

			}
		}
	}

	function get_referencing_foreign_keys ($database, $table, $old = 1) {
		function_debug_counter("get_referencing_foreign_keys");
		$query = '';
		if($old) {
		$query = 'SELECT TABLE_SCHEMA, TABLE_NAME, COLUMN_NAME, REFERENCED_COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_SCHEMA = "'.$database.'" AND REFERENCED_TABLE_NAME = '.esc($table);
		} else {
			$query = "SELECT k.TABLE_SCHEMA, k.TABLE_NAME, k.COLUMN_NAME, k.REFERENCED_COLUMN_NAME, r.DELETE_RULE, k.REFERENCED_TABLE_NAME, k.CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE k join information_schema.REFERENTIAL_CONSTRAINTS r on k.CONSTRAINT_NAME = r.CONSTRAINT_NAME WHERE k.REFERENCED_TABLE_SCHEMA = ".esc($database)." AND k.TABLE_NAME = ".esc($table);
		}
		$result = rquery($query);
		$foreign_keys = array();
		while ($row = mysqli_fetch_row($result)) {
			$database = array_value_or_null($row, 0);
			$table = array_value_or_null($row, 1);
			$column = array_value_or_null($row, 2);
			$on_delete = array_value_or_null($row, 4);
			$reference_column = array_value_or_null($row, 3);
			$reference_table = array_value_or_null($row, 5);
			$constraint_name = array_value_or_null($row, 6);

			$foreign_keys[] = array(
				'database' => $database,
				'table' => $table,
				'column' => $column,
				'reference_column' => $reference_column,
				'reference_table' => $reference_table,
				'on_delete' => $on_delete,
				'constraint_name' => $constraint_name
			);
		}

		return $foreign_keys;
	}

	function array_value_or_null ($array, $id) {
		function_debug_counter("array_value_or_null");
		if(array_key_exists($id, $array)) {
			return $array[$id];
		} else {
			return NULL;
		}
	}

	function get_foreign_key_deleted_data_html ($database, $table, $where) {
		function_debug_counter("get_foreign_key_deleted_data_html");
		$data = get_foreign_key_deleted_data($database, $table, $where);

		$html = '';
		$j = 0;
		foreach ($data as $key => $this_data) {
			$html .= "<h2>$key</h2>\n";

			$html .= "<table>\n";
			$i = 0;
			foreach ($this_data as $value) {
				if($i == 0) {
					$html .= "\t<tr>\n";
					foreach ($value as $column => $column_value) {
						$html .= "\t\t<th>".htmlentities($column)."</th>\n";
					}
					$html .= "\t</tr>\n";
				}
				$html .= "\t<tr>\n";
				foreach ($value as $column => $column_value) {
					if(preg_match('/password|session_id|salt/', $column)) {
						$html .= "\t\t<td><i>Aus Sicherheitsgründen wird diese Spalte nicht angezeigt.</i></td>\n";
					} else {
						if($column_value) {
							$html .= "\t\t<td>".htmlentities($column_value)."</td>\n";
						} else {
							$html .= "\t\t<td><i class='orange'>NULL</i></td>\n";
						}
					}
				}
				$html .= "\t</tr>\n";
				$i++;
			}
			$html .= "</table>\n";

			if($i == 1) {
				$html .= "<h3>$i Zeile</h3><br />\n";
			} else {
				$html .= "<h3>$i Zeilen</h3><br />\n";
			}
			$j += $i;
		}

		$html .= "<h4>Insgesamt $j Datensätze</h4>\n";;

		return $html;
	}

	function get_primary_keys ($database, $table) {
		function_debug_counter("get_primary_keys");
		$query = "SELECT k.column_name FROM information_schema.table_constraints t JOIN information_schema.key_column_usage k USING(constraint_name,table_schema,table_name) WHERE t.constraint_type='PRIMARY KEY' AND t.table_schema = ".esc($GLOBALS['dbname'])."   AND t.table_name = ".esc($table);
		$result = rquery($query);

		$data = fill_data_from_mysql_result($result);

		return $data;
	}

	function get_foreign_key_tables ($database, $table) {
		function_debug_counter("get_foreign_key_tables");
		$query = "SELECT TABLE_NAME, COLUMN_NAME, ' -> ', REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE REFERENCED_COLUMN_NAME IS NOT NULL AND CONSTRAINT_SCHEMA = ".esc($database)." AND TABLE_NAME = ".esc($table);
		$result = rquery($query);

		$data = fill_data_from_mysql_result($result);

		return $data;
	}

	function get_foreign_key_deleted_data ($database, $table, $where) {
		function_debug_counter("get_foreign_key_deleted_data");
		$GLOBALS['get_data_that_would_be_deleted'] = array();
		$data = get_data_that_would_be_deleted($database, $table, $where);
		$GLOBALS['get_data_that_would_be_deleted'] = array();
		return $data;
	}

	function get_data_that_would_be_deleted ($database, $table, $where, $recursion = 100) {
		function_debug_counter("get_data_that_would_be_deleted");
		if($recursion <= 0) {
			error("get_data_that_would_be_deleted: Tiefenrekursionsfehler.");
			return;
		}

		if($recursion == 100) {
			$GLOBALS['get_data_that_would_be_deleted'] = array();
		}

		if($table) {
			if(preg_match('/^[a-z0-9A-Z_]+$/', $table)) {
				if(is_array($where)) {
					$foreign_keys = get_referencing_foreign_keys($database, $table);
					$data = array();

					$query = 'SELECT * FROM `'.$table.'`';
					if(count($where)) {
						$query .= ' WHERE 1';
						foreach ($where as $name => $value) {
							$query .= " AND `$name` IN (".esc($value).')';
						}
					}
					$result = rquery($query);

					$to_check = array();

					while ($row = mysqli_fetch_row($result)) {
						$new_row = array();
						$i = 0;
						foreach ($row as $this_row) {
							$field_info = mysqli_fetch_field_direct($result, $i);
							$new_row[$field_info->name] = $this_row;
							foreach ($foreign_keys as $this_foreign_key) {
								if($this_foreign_key['reference_column'] == $field_info->name) {
									$to_check[] = array(
										'value' => $this_row,
										'foreign_key' => array(
											'table' => $this_foreign_key['table'],
											'column' => $this_foreign_key['column'], 
											'database' => $this_foreign_key['database']
										)
									);
								}
							}
							$i++;
						}
						$GLOBALS['get_data_that_would_be_deleted'][$table][] = $new_row;
					}
					foreach ($to_check as $this_to_check) {
						if(isset($this_to_check['value']) && !is_null($this_to_check['value'])) {
							$db = $this_to_check['foreign_key']['database'];
							$table = $this_to_check['foreign_key']['table'];
							$column = $this_to_check['foreign_key']['column'];
							$value = $this_to_check['value'];

							$values = array($column => $value);

							get_data_that_would_be_deleted($db, $table, $values, $recursion - 1);
						}
					}

					$data = $GLOBALS['get_data_that_would_be_deleted'];

					return $data;
				} else {
					die("\$where needs to be an array with column_name => value pairs");
				}
			} else {
				die('`'.htmlentities($table).'` is not a valid table name');
			}
		} else {
			die("\$table was not defined!");
		}
	}

	// http://php.net/manual/de/function.is-writable.php#118667
	function is_writable_r($dir) {
		function_debug_counter("is_writable_r");
		if (is_dir($dir)) {
			if(is_writable($dir)){
				$objects = scandir($dir);
				foreach ($objects as $object) {
					if ($object != "." && $object != "..") {
						if (!is_writable_r($dir."/".$object)) return false;
						else continue;
					}
				}   
				return true;   
			} else {
				return false;
			}

		} else if (file_exists($dir)){
			return (is_writable($dir));

		}
	}

	function last_api_access_long_ago ($auth_code) {
		function_debug_counter("last_api_access_long_ago");
		return 1;
		$query = 'SELECT time_to_sec(timediff(now(), `last_access`)) AS `timediff` FROM `api_auth_codes`';
		$result = rquery($query);

		$last_access = null;
		while ($row = mysqli_fetch_row($result)) {
			$last_access = $row[0];
		}

		if(is_null($last_access)) { // Falls noch nie aufgerufen wurde
			$query = 'update api_auth_codes set last_access = now() where auth_code = '.esc($auth_code);
			rquery($query);
			return 1;
		} else {
			if($last_access >= 1) {
				$query = 'update api_auth_codes set last_access = now() where auth_code = '.esc($auth_code);
				rquery($query);
			}

			if($last_access >= 2) {
				return 1;
			} else {
				return 0;
			}
		}
	}

	function cellColor($objPHPExcel, $cells, $color){
		function_debug_counter("cellColor");
		$objPHPExcel->getActiveSheet()->getStyle($cells)->getFill()->applyFromArray(array(
			'type' => PHPExcel_Style_Fill::FILL_SOLID,
			'startcolor' => array(
				'rgb' => $color
			)
		));
	}

	function is_valid_auth_code ($auth_code) {
		function_debug_counter("is_valid_auth_code");
		$query = 'SELECT `auth_code` FROM `api_auth_codes` WHERE `auth_code` = '.esc($auth_code);
		$result = rquery($query);

		$is_valid = 0;
		while ($row = mysqli_fetch_row($result)) {
			$is_valid = 1;
		}

		return $is_valid;
	}

	function check_page_rights ($page, $log = 1) {
		function_debug_counter("check_page_rights");
		$log = 0;
		if((array_key_exists('user_role_id', $GLOBALS) && isset($GLOBALS['user_role_id'])) ) {
			$role_id = $GLOBALS['user_role_id'];
			return check_page_rights_role_id($page, $role_id, $log);
		} else {
			return 0;
		}
	}

	function mask_module ($module) {
		function_debug_counter("mask_module");
		return "<i>$module</i>";
	}

	function get_language_by_veranstaltung ($v_id) {
		function_debug_counter("get_language_by_veranstaltung");
		$array = array();
		$query = 'select language_id from veranstaltung_to_language where veranstaltung_id = '.esc($v_id);
		$result = rquery($query);

		while ($row = mysqli_fetch_row($result)) {
			$array[] = $row[0];
		}

		return $array;
	}

	function get_language_name ($language_id) {
		function_debug_counter("get_language_name");
		if(array_key_exists($language_id, $GLOBALS['get_language_name_cache'])) {
			return $GLOBALS['get_language_name_cache'][$language_id];
		} else {
			$query = 'select name from language where id = '.esc($language_id);
			$res = get_single_row_from_query($query);
			$res = get_language_flag($res).$res;
			$GLOBALS['get_language_name_cache'][$language_id] = $res;
			return $res;

		}
	}

	function get_language_flag ($language) {
		$flag = '';
		switch ($language) {
			case 'deutsch':
				$flag = '&#x1F1E9;&#x1F1EA; ';
				break;
			case 'englisch':
				$flag = '&#127468;&#127463; ';
				break;
			case 'französisch':
				$flag = '&#127468;&#127463; ';
				break;
			case 'klingonisch':
				$flag = '&#xf8de;&#xf8d7;&#xf8df;&#xf8d0;&#xf8d3;';
				break;
		}

		return $flag;
	}

	function get_oberkategorie_id_by_page_id ($page_id) {
		function_debug_counter("get_oberkategorie_id_by_page_id");
		$query = 'select parent from page where id = '.esc($page_id);
		return get_single_row_from_query($query);
	}

	function check_page_rights_role_id ($page_id, $role_id, $log = 1) {
		function_debug_counter("check_page_rights_role_id");
		if( (isset($role_id) || is_null($role_id) ) && (array_key_exists('user_role_id', $GLOBALS) && isset($GLOBALS['user_role_id'])) ) {
			$role_id = $GLOBALS['user_role_id'];
		}

		if(!$role_id) {
			return 0;
		}

		if(is_array($page_id)) {
			$query = 'SELECT `page_id` FROM `role_to_page` WHERE `page_id` IN ('.multiple_esc_join($page_id).') AND `role_id` = '.esc($role_id);
			$result = rquery($query);
			
			$rights_id = array();
			while ($row = mysqli_fetch_row($result)) {
				$rights_id[] = $row[0];
			}

			return $rights_id;
		} else {
			if(!preg_match('/^\d+$/', $page_id)) {
				$page_id = get_page_id_by_filename($page_id);
			}
			$return = 0;
			$key = "$page_id----$role_id";
			if(array_key_exists($key, $GLOBALS['user_role_cache'])) {
				$return = $GLOBALS['user_role_cache'][$key];
			} else {
				if(isset($GLOBALS['logged_in_user_id'])) {
					$query = 'SELECT `page_id` FROM `role_to_page` WHERE `page_id` = '.esc($page_id).' AND `role_id` = '.esc($role_id);
					$result = rquery($query);
					
					$rights_id = null;
					while ($row = mysqli_fetch_row($result)) {
						$rights_id = $row[0];
					}

					if(!is_null($rights_id)) {
						$return = 1;
					}
				}

				if(!$return) {
					// is_oberkategorie?
					// has_children?
					$query = 'select parent from page where parent IS NULL and file IS NULL';
					$result = rquery($query);

					$parent_of_this_page = get_oberkategorie_id_by_page_id($page_id);
					
					while ($row = mysqli_fetch_row($result)) {
						if($row[0] == $parent_of_this_page) {
							$return = 1;
						}
					}
				}
			}

			$GLOBALS['user_role_cache'][$key] = $return;

			if($log) {
				right_issue("Die Seite mit der ID `$page_id` darf mit den aktuellen Rechten nicht ausgeführt werden.");
				if(!$return) {
					$query = 'INSERT IGNORE INTO `right_issues_pages` (`user_id`, `page_id`, `date`) VALUES ('.esc($GLOBALS['logged_in_user_id']).', '.esc($page_id).', now())';
					rquery($query);
					right_issue("Der Vorfall wird gespeichert und der Administrator informiert.");
				}
			}

			return $return;
		}
	}

	function discordian_date ($str) {
		function_debug_counter("discordian_date");
		if(!isset($str) || !$str) {
			return null;
		}

		$data = split('-', $str);
		$year = $data[0];
		$month = $data[1];
		$day = $data[2];

		if($month && $day && $year) {
			include_once('scripts/ddatelibrary.php');
			$ddate = new PHPDiscordianDate();
			$ret = $ddate->MakeDay($month, $day, $year);
			return $ret;
		} else {
			die("Day, Month and Year needs to be set!");
		}

	}

	function check_function_rights ($function, $log = 1) {
		function_debug_counter("check_function_rights");
		$role_id = $GLOBALS['user_role_id'];
		return check_function_rights_role_id($function, $role_id, $log);
	}

	function check_function_rights_role_id ($function, $role_id, $log = 1) {
		function_debug_counter("check_function_rights_role_id");
		$log = 0;
		if(!$role_id || is_null($role_id)) {
			$role_id = $GLOBALS['user_role_id'];
		}

		$return = 0;
		if(isset($GLOBALS['logged_in_user_id'])) {
			$query = 'select id from function_right fr join function_right_to_user_role frur on fr.id = frur.function_right_id where role_id = '.esc($role_id).' and function_name = '.esc($function);
			$result = rquery($query);
			
			$rights_id = null;
			while ($row = mysqli_fetch_row($result)) {
				$rights_id = $row[0];
			}

			if(!is_null($rights_id)) {
				$return = 1;
			} else {
				$query = 'select 1 from function_right_to_user_role r join function_right fr on r.function_right_id = fr.id where r.role_id = '.esc($role_id).' and fr.function_name = '.esc($function);
				$result = rquery($query);
				$rights_id = null;
				
				while ($row = mysqli_fetch_row($result)) {
					$rights_id = $row[0];
					if(!is_null($rights_id)) {
						$return = 1;
					}
				}

				// Wenn der User die Seite aufrufen darf, dann darf er auch die Rechte der Funktionen nutzen
				if(!$return) {
					$query = 'select 1 from function_right_to_page rp join function_right fr on rp.function_right_id = fr.id where rp.page_id = '.esc(get_get('page')).' and fr.function_name = '.esc($function);
					$result = rquery($query);
					$rights_id = null;
					
					while ($row = mysqli_fetch_row($result)) {
						$rights_id = $row[0];
						if(!is_null($rights_id)) {
							$return = 1;
						}
					}
				}
			}
		}

		if(!$return) {
			right_issue("Die Funktion $function darf mit den aktuellen Rechten nicht ausgeführt werden.");
		}

		if($log) {
			if(!$return) {
				$query = 'INSERT IGNORE INTO `right_issues` (`user_id`, `function`, `date`) VALUES ('.esc($GLOBALS['logged_in_user_id']).', '.esc($function).', now())';
				rquery($query);
				right_issue("Der Vorfall wird gespeichert und der Administrator informiert.");
			}
		}

		return $return;
	}

	function convert_date ($date) {
		function_debug_counter("convert_date");
		$converted_date = '';
		if(preg_match('/^(\d+)\.(\d+)\.(\d\d\d\d)$/', $date, $founds)) {
			$converted_date = $founds[2].'-'.add_leading_zero($founds[1]).'-'.add_leading_zero($founds[0]);
		}

		if($converted_date) {
			return $converted_date;
		} else {
			return $date;
		}
	}

	function get_previous_letter($string){
		function_debug_counter("get_previous_letter");
		$last = substr($string, -1);
		$part = substr($string, 0, -1);
		if(strtoupper($last)=='A'){
			$l = substr($part, -1);
			if($l == 'A'){
				return substr($part, 0, -1)."Z";
			}
			return $part.chr(ord($l)-1);
		}else{
			return $part.chr(ord($last)-1);
		}
	}

	function FormatBacktrace() { 
		function_debug_counter("FormatBacktrace");# http://stackoverflow.com/questions/4282120/is-there-a-pretty-print-stack-dump
		$result = '<h4>Backtrace</h4>';

		foreach (debug_backtrace() as $trace)
		{
			if ($trace['function'] ==__FUNCTION__)
				continue;

			$parameters = '';
			foreach ($trace['args'] as $parameter)
				$parameters .= $parameter . ', ';

			if (substr($parameters, -2) == ', ')
				$parameters = substr($parameters, 0, -2);

			if (array_key_exists('class', $trace))
				$result .= sprintf("%s:%s %s::%s(%s)<br>", $trace['file'], $trace['line'],  $trace['class'], $trace['function'],  $parameters);
			else
				$result .= sprintf("%s:%s %s(%s)<br>", $trace['file'], $trace['line'], $trace['function'], $parameters);
		}

		return $result;
	}

	// http://stackoverflow.com/questions/10038236/php-htmlentities-allow-b-and-i-only
	function strip_tags_attributes( $str, 
		$allowedTags = array('<a>','<b>','<blockquote>','<br>','<cite>','<code>','<del>','<div>','<em>','<ul>','<ol>','<li>','<dl>','<dt>','<dd>','<img>','<ins>','<u>','<q>','<h3>','<h4>','<h5>','<h6>','<samp>','<strong>','<sub>','<sup>','<p>','<table>','<tr>','<td>','<th>','<pre>','<span>'), 
		$disabledEvents = array('onclick','ondblclick','onkeydown','onkeypress','onkeyup','onload','onmousedown','onmousemove','onmouseout','onmouseover','onmouseup','onunload') )
	{       
		if( empty($disabledEvents) ) {
			return strip_tags($str, implode('', $allowedTags));
		}
		return preg_replace('/<(.*?)>/ies', "'<' . preg_replace(array('/javascript:[^\"\']*/i', '/(" . implode('|', $disabledEvents) . ")=[\"\'][^\"\']*[\"\']/i', '/\s+/'), array('', '', ' '), stripslashes('\\1')) . '>'", strip_tags($str, implode('', $allowedTags)));
	}

	// Idee: über diese Wrapperfunktion kann man einfach Queries mitloggen etc., falls notwendig.
	function rquery ($internalquery, $die = 1) {
		function_debug_counter("rquery");
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
					dier("Ung&uuml;ltige Anfrage: <p><pre>".htmlentities($internalquery)."</pre></p>".htmlentities(mysqli_error($GLOBALS['dbh'])), 0, 1);
				} else {
					dier("Ung&uuml;ltige Anfrage: <p><pre>".htmlentities($internalquery)."</pre></p><p>DBH undefined!</p>", 0, 0);
				}
			}
		}

		if($GLOBALS['rquery_print']) {
			print "<p>".htmlentities($internalquery)."</p>\n";
		}

		return $result;
	}

	function esc ($parameter) { 
		function_debug_counter("esc");// escape
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

	function my_mysqli_real_escape_string ($arg) {
		function_debug_counter("my_mysqli_real_escape_string");
		return mysqli_real_escape_string($GLOBALS['dbh'], $arg);
	}

	function might_be_query ($data) {
		function_debug_counter("might_be_query");
		if(isset($data)) {
			if(is_array($data)) {
				return 0;
			} else {
				# ist vorhanden und ein string

				if(preg_match('/^SELECT\s+.*FROM\s+.*/i', $data)) {
					return 1;
				} else if(preg_match('/^UPDATE\s+.*SET\s+/i', $data)) {
					return 1;
				} else if(preg_match('/^DELETE\s+FROM\s+/i', $data)) {
					return 1;
				} else {
					return 0;
				}
			}
		} else {
			return 0;
		}
	}

	function dier ($data, $sql = 0, $show_error = 1) {
		function_debug_counter("dier");
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
			$message .= htmlentities($data)."\n";
			$message .= "========================== Nachricht Ende\n";

			$headers = '';
			$headers .= "From:" . $from."\r\n";

			$fp = fsockopen("localhost", 25, $errno, $errstr, 5);
			if($fp && mail($GLOBALS['admin_email'], $subject, $message, $headers)) {
				$GLOBALS['messageerror'] = 'Die Administration ist informiert worden.';
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

	function multiple_esc_join ($data) {
		function_debug_counter("multiple_esc_join");
		if(is_array($data)) {
			$data = array_map('esc', $data);
			$string = join(", ", $data);
			return $string;
		} else {
			return esc($data);
		}
	}

	function get_post_multiple_check ($names) {
		function_debug_counter("get_post_multiple_check");
		if(is_array($names)) {
			$return = 1;
			foreach ($names as $name) {
				if(!get_post($name)) {
					$return = 0;
					break;
				}
			}
			return $return;
		} else {
			return get_post($name);
		}
	}

	function get_cookie ($name, $default = NULL) {
		function_debug_counter("get_cookie");
		if(array_key_exists($name, $_COOKIE)) {
			return $_COOKIE[$name];
		} else {
			return $default;
		}
	}

	function get_get_or_cookie ($name) {
		function_debug_counter("get_get_or_cookie");
		if(array_key_exists($name, $_COOKIE)) {
			return $_COOKIE[$name];
		} else if(array_key_exists($name, $_GET)) {
			return $_GET[$name];
		} else {
			return NULL;
		}
	}

	// Die get_-Funktionen sollen häßliche Konstrukte mit array_key_exists($bla, $_POST) vermeiden.
	function get_get ($name) {
		function_debug_counter("get_get");
		if(array_key_exists($name, $_GET)) {
			return $_GET[$name];
		} else {
			return NULL;
		}
	}

	function get_post ($name) {
		function_debug_counter("get_post");
		if(array_key_exists($name, $_POST)) {
			return $_POST[$name];
		} else {
			return NULL;
		}
	}

	function generate_random_string ($length = 50) {
		function_debug_counter("generate_random_string");
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[mt_rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}

	function delete_old_session_ids ($user_id = null) {
		function_debug_counter("delete_old_session_ids");
		if($GLOBALS['already_deleted_old_session_ids']) {
			return;
		}

		$query = 'DELETE FROM `session_ids` WHERE `creation_time` <= now() - INTERVAL 1 DAY';
		rquery($query);
		if($user_id) {
			$query = 'DELETE FROM `session_ids` WHERE `user_id` = '.esc($user_id);
			rquery($query);
		}
		$GLOBALS['already_deleted_old_session_ids'] = 1;
	}

	function print_subnavigation ($parent) {
		function_debug_counter("print_subnavigation");
		$query = 'SELECT `name`, `file`, `page_id`, `show_in_navigation`, `parent` FROM `view_account_to_role_pages` WHERE `user_id` = '.esc($GLOBALS['logged_in_user_id']).' AND `parent` = '.esc($parent).' AND `show_in_navigation` = "1" ORDER BY `name`';
		$result = rquery($query);

		$str = '';
		$subnav_selected = 0;

		if(mysqli_num_rows($result)) {
			$str .= "\t<ul>\n";
			while ($row = mysqli_fetch_row($result)) {
				if($row[2] == get_get('page')) {
					$str .= "\t\t<li class='font_weight_bold'><a href='admin.php?page=".$row[2]."'>&rarr; $row[0]</a></li>\n";
					$subnav_selected = 1;
				} else {
					$str .= "\t\t<li><a href='admin.php?page=".$row[2]."'>$row[0]</a></li>\n";
				}
			}
			$str .= "\t</ul>\n";
		}

		return array($subnav_selected, $str);
	}

	/* MySQL-get-Funktionen */

	/*
		Ich habe hier "auf Vorrat" gearbeitet. Fast alle dieser Funktionen sind irgendwie
		sinnvoll einsetzbar. Sobald das der Fall ist, will ich sie einfach benutzen können.
		Der Overhead ist vergleichsweise klein und wiegt den Aufwand im späteren Programmieren
		bei Weitem auf.
	 */

	function setze_semester ($id) {
		function_debug_counter("setze_semester");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'select id from semester where id = '.esc($id);
		$result = rquery($query);

		if(mysqli_num_rows($result)) {
			$query = 'UPDATE `semester` SET `DEFAULT` = "0"';
			$result = rquery($query);

			$query = 'UPDATE `semester` SET `DEFAULT` = "1" WHERE `id` = '.esc($id);
			$result = rquery($query);

			success('Das ausgewählte Semester wurde als Standardsemester ausgewählt.');
		} else {
			error('Das ausgewählte Semester konnte nicht gefunden werden.');
		}
	}

	function merge_data ($table, $from, $to) {
		function_debug_counter("merge_data");
		if(!check_function_rights(__FUNCTION__)) { return; }
		if(preg_match('/^[a-z0-9A-Z_]+$/', $table)) {
			foreach ($from as $this_from) {
				$where = array('id' => $from);
				$data = get_foreign_key_deleted_data($GLOBALS['dbname'], $table, $where);

				foreach ($data as $this_table => $this_table_val) {
					if($this_table != $table) {
						$where = '';
						$refkey = '';

						$this_where = array();

						$foreign_keys = get_foreign_key_tables($GLOBALS['dbname'], $this_table);
						foreach ($foreign_keys as $this_foreign_key) {
							if($this_foreign_key[3] == $table) {
								$refkey = $this_foreign_key[1];
							}
						}

						if($refkey) {
							$primary_keys = get_primary_keys($GLOBALS['dbname'], $this_table);
							$i = 0;
							foreach ($this_table_val as $this_table_val_2) {
								$this_where_str = '';
								foreach ($primary_keys as $this_primary_key) {
									$this_where_str .= ' (';
									$this_where_str .= "`$this_primary_key[0]` = ".esc($this_table_val_2[$this_primary_key[0]]);
									$this_where_str .= ') OR ';

									$i++;
								}
								$this_where[] = $this_where_str;
							}
							$where = join(' ', $this_where);
							$where = preg_replace('/\s+OR\s*$/', '', $where);

							if($where) {
								if(preg_match('/=/', $where)) {
									$query = "UPDATE `$this_table` SET `$refkey` = ".esc($to)." WHERE $where";
									stderrw($query);
									$result = rquery($query);
								} else {
									die("Es konnte kein valides `$where entwickelt werden`: $where.");
								}
							} else {
								die("Es konnte kein `$where entwickelt werden`.");
							}
						}
					}
				}
			}

			$wherea = array();
			foreach ($from as $this_from) {
				if($this_from != $to) {
					$wherea[] = $this_from;
				}
			}
			$where = '`id` IN ('.join(', ', array_map('esc', $wherea)).')';
			$query = "DELETE FROM `$table` WHERE $where";
			$result = rquery($query);

			if($result) {
				success('Die Keys wurden erfolgreich gelöscht.');
			} else {
				error('Die Daten wurden nicht erfolgreich gemergt.');
			}
		} else {
			error('Die Tabelle `'.htmlentities($table).'` konnte ist nicht valide.');
		}
	}

	function faq_has_entry () {
		function_debug_counter("faq_has_entry");
		$query = 'SELECT COUNT(*) FROM `'.$GLOBALS["dbname"].'`.`faq`';
		$result = rquery($query);

		$antwort = 0;
		while ($row = mysqli_fetch_row($result)) {
			$antwort = $row[0];
		}

		return $antwort;
	}

	function studiengang_has_semester_modul_data ($studiengang_id) {
		function_debug_counter("studiengang_has_semester_modul_data");
		if($studiengang_id == 'alle') {
			return null;
		}
		$query = 'select * from view_modul_semester where semester is not null and studiengang_id = '.esc($studiengang_id);
		$result = rquery($query);

		$ret = 0;
		while ($row = mysqli_fetch_row($result)) {
			$ret = 1;
		}

		return $ret;
	}

	function get_page_file_by_id ($id) {
		function_debug_counter("get_page_file_by_id");
		$key = "get_page_file_by_id($id)";
		if(array_key_exists($key, $GLOBALS['memoize'])) {
			return $GLOBALS['memoize'][$key];
		}

		$query = 'SELECT `file` FROM `page` WHERE `id` = '.esc($id);

		$id = get_single_row_from_query($query);

		$GLOBALS['memoize'][$key] = $id;

		return $id;
	}

	function get_page_info_by_id ($id) {
		function_debug_counter("get_page_info_by_id");
		$query = 'SELECT `page_id`, `info` FROM `page_info` WHERE `page_id` ';
		if(is_array($id)) {
			$query .= 'IN ('.join(', ', array_map('esc', $id)).')';
		} else {
			$query .= ' = '.esc($id);
		}
		$result = rquery($query);

		$data = array();

		while ($row = mysqli_fetch_row($result)) {
			if(is_array($id)) {
				$data[$row[0]] = $row[1];
			} else {
				$data = $row[1];
			}
		}

		return $data;
	}

	function get_page_parent_by_page_id ($id) {
		function_debug_counter("get_page_parent_by_page_id");
		$query = 'SELECT `parent` FROM `page` WHERE `id` = '.esc($id);
		return get_single_row_from_query($query);
	}

	function get_page_name_by_id ($id) {
		function_debug_counter("get_page_name_by_id");
		$data = '';
		if(array_key_exists($id, $GLOBALS['get_page_name_by_id_cache'])) {
			$data = $GLOBALS['get_page_name_by_id_cache'][$id];
		} else {
			$query = 'SELECT `name` FROM `page` WHERE `id` = '.esc($id);
			$result = rquery($query);

			$data = NULL;

			while ($row = mysqli_fetch_row($result)) {
				$data = $row[0];
			}

			$GLOBALS['get_page_name_by_id_cache'][$id] = $data;
		}

		return $data;
	}

	function create_page_id_by_name_array () {
		function_debug_counter("create_page_id_by_name_array");
		$query = 'SELECT `name`, `id` FROM `page`';
		$result = rquery($query);

		$id = array();

		while ($row = mysqli_fetch_row($result)) {
			$id[$row[1]] = $row[0];
		}

		return $id;
	}

	function is_future_semester ($semester_param) {
		function_debug_counter("is_future_semester");
		$ts = get_and_create_this_semester();
		$this_semester_year = preg_replace('/\/.*$/', '', $ts[1]);
		$param_semester_year = preg_replace('/\/.*$/', '', $semester_param[1]);

		if($param_semester_year > $this_semester_year) {
			return 1;
		} else if($param_semester_year < $this_semester_year) {
			return 0;
		} else {
			$this_semester_type = $ts[2];
			$param_semester_type = $semester_param[2];

			if($this_semester_type == 'Sommersemester' && $param_semester_type == "Wintersemester") {
				return 1;
			} else {
				return 0;
			}
		} 
	}

	function get_page_id ($name) {
		function_debug_counter("get_page_id");
		$query = 'SELECT `id` FROM `page` WHERE `name` = '.esc($name).' limit 1';
		return get_single_row_from_query($query);
	}

	function get_dozent_id_by_user_id ($id) {
		function_debug_counter("get_dozent_id_by_user_id");
		$query = 'select dozent_id from users where id = '.esc($id);
		return get_single_row_from_query($query);
	}

	function get_role_id_by_user ($name) {
		function_debug_counter("get_role_id_by_user");
		$key = "get_role_id_by_user($name)";
		if(array_key_exists($key, $GLOBALS['memoize'])) {
			$return = $GLOBALS['memoize'][$key];
		} else {
			$query = 'SELECT `role_id` FROM `role_to_user` `ru` LEFT JOIN `users` `u` ON `ru`.`user_id` = `u`.`id` WHERE `u`.`id` = '.esc($name);
			$result = rquery($query);

			$return = NULL;

			while ($row = mysqli_fetch_row($result)) {
				$return = $row[0];
			}
			$GLOBALS['memoize'][$key] = $return;
		}

		return $return;
	}

	function get_account_enabled_by_id ($id) {
		function_debug_counter("get_account_enabled_by_id");
		$query = 'select enabled from users where id = '.esc($id);
		return get_single_row_from_query($query);
	}

	function get_role_name ($id) {
		function_debug_counter("get_role_name");
		if(array_key_exists($id, $GLOBALS['get_role_name_cache'])) {
			return $GLOBALS['get_role_name_cache'][$id];
		} else {
			$query = 'SELECT `name` FROM `role` WHERE `id` = '.esc($id).' limit 1';
			$ret = get_single_row_from_query($query);

			$GLOBALS['get_role_name_cache'][$id] = $ret;
			return $ret;
		}
	}

	function get_role_id ($name) {
		function_debug_counter("get_role_id");
		if(array_key_exists($name, $GLOBALS['get_role_id_cache'])) {
			return $GLOBALS['get_role_id_cache'][$name];
		} else {
			$query = 'SELECT `id` FROM `role` WHERE `name` = '.esc($name).' limit 1';
			$ret = get_single_row_from_query($query);
			$GLOBALS['get_role_id_cache'][$name] = $ret;
			return $ret;
		}
	}

	function get_user_id ($name) {
		function_debug_counter("get_user_id");
		$query = 'SELECT `id` FROM `users` WHERE `username` = '.esc($name);
		return get_single_row_from_query($query);
	}

	function get_user_name ($id) {
		function_debug_counter("get_user_name");
		$query = 'SELECT `username` FROM `users` WHERE `id` = '.esc($id);
		return get_single_row_from_query($query);
	}

	function get_studiengang_name ($id) {
		function_debug_counter("get_studiengang_name");
		if($id == 'alle') {
			return 'Alle Studiengänge';
		}

		$query = 'SELECT `name` FROM `studiengang` WHERE `id` ';

		if($id) {
			$query .= '= '.esc($id);
		} else {
			$query .= 'IS NULL';
		}

		return get_single_row_from_query($query);
	}

	function get_studiengang_id ($name, $institut_id) {
		function_debug_counter("get_studiengang_id");
		$query = 'SELECT `id` FROM `studiengang` WHERE `name` = '.esc($name).' AND `institut_id` = '.esc($institut_id);
		return get_single_row_from_query($query);
	}

	function get_pruefungsnummer_id_by_pruefungsnummer ($pn) {
		function_debug_counter("get_pruefungsnummer_id_by_pruefungsnummer");
		$query = 'select id from pruefungsnummer where pruefungsnummer = '.esc($pn).' limit 1';
		return get_single_row_from_query($query);
	}

	function get_pruefungstyp_by_pruefungsnummer ($pn) {
		function_debug_counter("get_pruefungstyp_by_pruefungsnummer");
		$query = 'select name from pruefungstyp pruefungstyp where id in (select pruefungstyp_id from pruefungsnummer where pruefungsnummer = '.esc($pn).')';
		return get_single_row_from_query($query);
	}

	function get_modul_by_pruefungsnummer ($pn) {
		function_debug_counter("get_modul_by_pruefungsnummer");
		$query = 'select name from modul where id in (select modul_id from pruefungsnummer where pruefungsnummer = '.esc($pn).')';
		return get_single_row_from_query($query);
	}

	function get_modul_id ($name, $studiengang_id) {
		function_debug_counter("get_modul_id");
		$query = 'SELECT `id` FROM `modul` WHERE `name` = '.esc($name).' AND `studiengang_id` = '.esc($studiengang_id);
		return get_single_row_from_query($query);
	}

	function get_pruefungstyp_name ($id) {
		function_debug_counter("get_pruefungstyp_name");
		if(is_null($id) || !$id) {
			return null;
		}
		$query = 'SELECT `name` FROM `pruefungstyp` WHERE `id` = '.esc($id);
		return get_single_row_from_query($query);
	}

	function get_modul_name ($id) {
		function_debug_counter("get_modul_name");
		if(is_null($id) || !$id) {
			return null;
		}
		$ret = null;
		if(array_key_exists($id, $GLOBALS['get_modul_name_cache'])) {
			$ret = $GLOBALS['get_modul_name_cache'][$id];
		} else {
			$query = 'SELECT `name` FROM `modul` WHERE `id` = '.esc($id);
			$ret = get_single_row_from_query($query);
			$GLOBALS['get_modul_name_cache'][$id] = $ret;
		}
		return $ret;
	}

	function get_gebaeude_abkuerzung_name_by_raum_id ($id) {
		function_debug_counter("get_gebaeude_abkuerzung_name_by_raum_id");
		if(is_null($id) || !$id) {
			return null;
		}
		$key = "get_gebaeude_abkuerzung_name_by_raum_id($id)";
		if(array_key_exists($key, $GLOBALS['memoize'])) {
			return $GLOBALS['memoize'][$key];
		}
		$query = 'SELECT `g`.`abkuerzung`, `g`.`name` as `gb` FROM `raum` `r` JOIN `gebaeude` `g` ON `g`.`id` = `r`.`gebaeude_id` WHERE `r`.`id` = '.esc($id);
		$result = rquery($query);

		$name = array();

		while ($row = mysqli_fetch_row($result)) {
			$name = array($row[0], $row[1]);
		}
		$GLOBALS['memoize'][$key] = $name;

		return $name;
	}

	function get_raum_gebaeude_by_id ($id) {
		function_debug_counter("get_raum_gebaeude_by_id");
		if(is_null($id) || !$id) {
			return null;
		}

		$key = "get_raum_gebaeude_by_id($id)";
		if(array_key_exists($key, $GLOBALS['memoize'])) {
			return $GLOBALS['memoize'][$key];
		}

		$query = 'SELECT `r`.`raumnummer`, `g`.`abkuerzung` as `gb` FROM `raum` `r` JOIN `gebaeude` `g` ON `g`.`id` = `r`.`gebaeude_id` WHERE `r`.`id` = '.esc($id);
		$result = rquery($query);

		$name = NULL;

		while ($row = mysqli_fetch_row($result)) {
			$name = "<a href='".$GLOBALS['navigator_base_url'].strtolower($row[1])."/'>".htmlentities($row[1])."</a> ".htmlentities($row[0]);
		}
		$GLOBALS['memoize'][$key] = $name;

		return $name;
	}

	function get_raum_gebaeude_array () {
		function_debug_counter("get_raum_gebaeude_array");
		$query = 'SELECT `r`.`id`, `abkuerzung`, `raumnummer` as `gb` FROM `raum` `r` JOIN `gebaeude` `g` ON `g`.`id` = `r`.`gebaeude_id`';
		$result = rquery($query);

		$raum_gebaeude = array();

		while ($row = mysqli_fetch_row($result)) {
			$raum_gebaeude["$row[0]"] = "<a href='".$GLOBALS['navigator_base_url'].strtolower(htmlentities($row[1]))."/'>".htmlentities($row[1])."</a> ".htmlentities($row[2]);
		}

		return $raum_gebaeude;
	}

	function get_raum_gebaeude ($raumnummer) {
		function_debug_counter("get_raum_gebaeude");
		if(is_null($raumnummer) || !$raumnummer) {
			return null;
		}

		$key = "get_raum_gebaeude($raumnummer)";
		if(array_key_exists($key, $GLOBALS['memoize'])) {
			return $GLOBALS['memoize'][$key];
		}

		$query = 'SELECT `abkuerzung`, `raumnummer` as `gb` FROM `raum` `r` JOIN `gebaeude` `g` ON `g`.`id` = `r`.`gebaeude_id` WHERE `r`.`id` = '.esc($raumnummer);
		$result = rquery($query);

		$name = NULL;

		while ($row = mysqli_fetch_row($result)) {
			$name = "<a href='".$GLOBALS['navigator_base_url'].strtolower($row[0])."/'>$row[0]</a> $row[1]";
		}

		$GLOBALS['memoize'][$key] = $name;

		return $name;
	}

	function get_and_create_next_n_semester_years ($n) {
		function_debug_counter("get_and_create_next_n_semester_years");
		foreach (range(date('Y'), date('Y') + $n) as $this_year) {
			get_and_create_semester_id_by_jahr_monat_tag($this_year, 10, 10);	# Wintersemester
			get_and_create_semester_id_by_jahr_monat_tag($this_year, 8, 10);	# Sommersemester
		}
	}

	function get_and_create_this_semester ($swap = 0) {
		function_debug_counter("get_and_create_this_semester");
		return get_and_create_semester_id_by_jahr_monat_tag(date('Y'), date('m'), date('d'), $swap);
	}

	function get_this_semester () {
		function_debug_counter("get_this_semester");
		$query = 'select id from semester where `default` = "1" order by id asc';
		$result = rquery($query);

		if(mysqli_num_rows($result)) {
			$id = get_single_row_from_result($result);
			return get_semester($id);
		} else {
			return get_and_create_this_semester();
		}
	}

	function get_semester ($id, $join_together = 0) {
		function_debug_counter("get_semester");
		$data = array();
		$query = 'SELECT `id`, `jahr`, `typ` FROM `semester` WHERE `id` = '.esc($id);
		$result = rquery($query);

		while ($row = mysqli_fetch_row($result)) {
			if($row[2] == 'Wintersemester') {
				$next_year = $row[1] + 1;
				$row[1] = "$row[1]/$next_year";
			}
			if($join_together) {
				$data = array($row[0], "$row[2] $row[1]");
			} else {
				$data = $row;
			}
		}

		return $data;
	}

	function get_and_create_semester_id_by_jahr_monat_tag ($jahr, $monat, $tag, $swap = 0) {
		function_debug_counter("get_and_create_semester_id_by_jahr_monat_tag");
		$type = '';

		if(in_array($monat, array(10, 11, 12, 1, 2, 3))) {
			$type = 'Wintersemester';
		} else {
			$type = 'Sommersemester';
		}

		$key = "get_and_create_semester_id_by_jahr_monat_tag($jahr, $monat, $tag)";
		if(array_key_exists($key, $GLOBALS['memoize'])) {
			return $GLOBALS['memoize'][$key];
		}

		$data = array();
		$query = 'SELECT `id`, `jahr`, `typ` FROM `semester` WHERE `typ` = '.esc($type).' AND `jahr` = '.esc($jahr).';';
		$result = rquery($query);

		while ($row = mysqli_fetch_row($result)) {
			if($swap) {
				$data = array($row[0], $row[2], $row[1]);
			} else {
				$data = $row;
			}
		}

		if(count($data)) {
			$GLOBALS['memoize'][$key] = $data;
			return $data;
		} else {
			$query = 'INSERT IGNORE INTO `semester` (`jahr`, `typ`) VALUES ('.esc($jahr).', '.esc($type).')';
			$result = rquery($query);

			if($result) {
				return get_and_create_semester_id_by_jahr_monat_tag($jahr, $monat, $tag, $swap);
			} else {
				error('Kann das Semester nicht erstellen.');
			}
		}
	}

	function get_and_create_pruefungstyp ($pruefungstyp_name) {
		function_debug_counter("get_and_create_pruefungstyp");
		$result = get_pruefungstyp_id($pruefungstyp_name);

		if($result) {
			return $result;
		} else {
			$query = 'INSERT IGNORE INTO `pruefungstyp` (`name`) VALUES ('.esc($pruefungstyp_name).')';
			$results = rquery($query);

			if($results) {
				$result = get_pruefungstyp_id($pruefungstyp_name);
				if($result) {
					success('Prüfungstyp eingefügt.');
					return $result;
				} else {
					error('Der Prüfungstyp konnte nicht eingefügt werden.');
					return null;
				}
			}
		}
	}

	/*
		+-----------------+------------------+------+-----+---------+----------------+
		| Field           | Type             | Null | Key | Default | Extra          |
		+-----------------+------------------+------+-----+---------+----------------+
		| id              | int(10) unsigned | NO   | PRI | NULL    | auto_increment |
		| pruefungsnummer | varchar(100)     | YES  | UNI | NULL    |                |
		| modul_id        | int(10) unsigned | YES  | MUL | NULL    |                |
		| pruefungstyp_id | int(10) unsigned | YES  | MUL | NULL    |                |
		+-----------------+------------------+------+-----+---------+----------------+
	 */
	function insert_pruefungsnummern ($modul, $pruefungsnummer, $pruefungstyp) {
		function_debug_counter("insert_pruefungsnummern");
		$query = 'INSERT IGNORE INTO `pruefungsnummer` (`pruefungsnummer`, `modul_id`, `pruefungstyp_id`) VALUES ('.esc($pruefungsnummer).', '.esc($modul).', '.esc($pruefungstyp).')';
		rquery($query);
	}

	function get_and_create_modul ($name, $studiengang) {
		function_debug_counter("get_and_create_modul");
		$result = get_modul_id($name, $studiengang);

		if($result) {
			return $result;
		} else {
			$query = 'INSERT IGNORE INTO `modul` (`name`, `studiengang_id`) VALUES ('.esc($name).', '.esc($studiengang).')';
			$results = rquery($query);

			if($results) {
				$id = get_modul_id($name, $studiengang);
				if($id) {
					message('Modul eingefügt.');
					return $id;
				} else {
					message('Das Modul konnte nicht eingefügt werden.');
					return null;
				}
			} else {
				die(mysqli_error());
			}
		}
	}

	function get_and_create_raum_id ($gebaeude_id, $name, $raumplanung = 0) {
		function_debug_counter("get_and_create_raum_id");
		if(!preg_match('/^\d+$/', $gebaeude_id)) {
			$tmessage = 'Gebäude-ID wurde nicht definiert. Der Raum wird nicht angezeigt bzw. angelegt. ';
			if($raumplanung) {
				warning($tmessage);
			} else {
				error($tmessage);
			}
		} else {
			if($name) {
				$result = get_raum_id($gebaeude_id, $name);

				if($result) {
					return $result;
				} else {
					$query = 'INSERT IGNORE INTO `raum` (`gebaeude_id`, `raumnummer`) VALUES ('.esc($gebaeude_id).', '.esc($name).')';
					$results = rquery($query);

					if($results) {
						$id = get_raum_id($gebaeude_id, $name);
						if($id) {
							success('Raum eingefügt.');
							return $id;
						} else {
							error('Der Raum konnte nicht eingefügt werden.');
							return null;
						}
					}
				}
			} else {
				error('Raumname wurde nicht definiert. Der Raum wird nicht angezeigt bzw. angelegt.');
			}
		}
	}

	function get_pruefungsnummer_bereich_by_pruefungsnummer_id ($name) {
		function_debug_counter("get_pruefungsnummer_bereich_by_pruefungsnummer_id");
		$query = 'select bereich_id from pruefungsnummer where pruefungsnummer = '.esc($name);
		return get_single_row_from_query($query);
	}

	function get_pruefungsnummer_modul_by_pruefungsnummer_id ($name) {
		function_debug_counter("get_pruefungsnummer_modul_by_pruefungsnummer_id");
		$query = 'select modul_id from pruefungsnummer where pruefungsnummer = '.esc($name);
		return get_single_row_from_query($query);
	}

	function get_pruefungstyp_id_from_pruefungsnummer ($name) {
		function_debug_counter("get_pruefungstyp_id_from_pruefungsnummer");
		$query = 'select pruefungstyp_id from pruefungsnummer where pruefungsnummer = '.esc($name);
		return get_single_row_from_query($query);
	}

	function get_pruefungstyp_id ($name) {
		function_debug_counter("get_pruefungstyp_id");
		$query = 'SELECT `id` FROM `pruefungstyp` WHERE `name` = '.esc($name);
		return get_single_row_from_query($query);
	}

	function get_raum_id ($gebaeude_id, $raumnummer) {
		function_debug_counter("get_raum_id");
		$query = 'SELECT `id` FROM `raum` WHERE `gebaeude_id` = '.esc($gebaeude_id).' AND `raumnummer` = '.esc($raumnummer);
		return get_single_row_from_query($query);
	}

	function get_auth_code_by_id ($id) {
		function_debug_counter("get_auth_code_by_id");
		if(is_null($id) || !$id) {
			return null;
		}

		$query = 'SELECT `auth_code` FROM `api_auth_codes` WHERE `id` = '.esc($id);
		return get_single_row_from_query($query);
	}

	function get_auth_code_id ($id) {
		function_debug_counter("get_auth_code_id");
		if(is_null($id) || !$id) {
			return null;
		}

		$query = 'SELECT `id` FROM `api_auth_codes` WHERE `auth_code` = '.esc($id);
		return get_single_row_from_query($query);
	}

	function create_pruefungen_by_studiengang_array ($studiengang, $bereich = '') {
		function_debug_counter("create_pruefungen_by_studiengang_array");
		#			0			1		2		3	4		5	6		7			8,	9
		$query = "
SELECT 
	`p`.`veranstaltung_id`,
	`pruefungsnummer`, 
	`m`.`name`, 
	`pt`.`name` AS `pruefungstyp_name`,
	`b`.`name` AS `bereich_name`,
	`f`.`name` AS `pruefungsnummer_fach_name`, 
	`modul_id`, 
	`v`.`veranstaltungstyp_id`, 
	`pn`.`id`, 
	`s`.`name` as studiengang_name
FROM 
	`pruefung` `p`
JOIN 
	`pruefungsnummer` `pn`
		ON 
	`pn`.`id` = `p`.`pruefungsnummer_id` 
JOIN 
	`modul` `m`
		ON 
	`m`.`id` = `pn`.`modul_id` 
JOIN 
	`pruefungstyp` `pt`
		ON 
	`pt`.`id` = `pn`.`pruefungstyp_id`
LEFT JOIN 
	`bereich` `b`
		ON
	`b`.`id` = `pn`.`bereich_id`
LEFT JOIN 
	`pruefungsnummer_fach` `f`
		ON
	`f`.`id` = `pn`.`pruefungsnummer_fach_id`
JOIN
	`veranstaltung` `v`
		ON
	`v`.`id` = `p`.`veranstaltung_id`
JOIN 
	`studiengang` `s`
		ON
	`m`.`studiengang_id` = `s`.`id`
WHERE 1
";
		if(preg_match('/^\d+$/', $studiengang)) {
			$query .= ' AND `studiengang_id` = '.esc($studiengang);
		} else {
			debug("Studiengang ist keine Zahl, sondern `".htmlentities($studiengang)."`. Daher keine Suche nach studiengang_id");
		}

		if(preg_match('/^\d+$/', $bereich)) {
			$query .= ' AND `bereich_id` = '.esc($bereich);
		} else {
			debug("Bereich ist keine Zahl, sondern `".htmlentities($bereich)."`, daher keine Suche nach bereich_id");
		}

		$query .= ' GROUP BY s.name, `p`.`veranstaltung_id`, `pruefungsnummer`, `m`.`name`, `pt`.`name`, `b`.`name` ';
		$query .= ' ORDER BY studiengang_id ASC, `m`.`name` ASC, `pt`.`name` ASC, `pruefungsnummer` ASC';
		$result = rquery($query);

		$pruefungen = array();

		while ($row = mysqli_fetch_row($result)) {
			if(!array_key_exists($row[0], $pruefungen)) {
				$pruefungen[$row[0]] = array();
			}
			#				0	1	   2		3	4	5	6	7	8	9
			$pruefungen[$row[0]][] = array($row[1], $row[2], $row[3], $row[4], $row[5], $row[0], $row[6], $row[7], $row[8], $row[9]);
		}

		return $pruefungen;
	}

	function create_gebaeude_abkuerzung_id_array () {
		function_debug_counter("create_gebaeude_abkuerzung_id_array");
		$query = 'SELECT `id`, `abkuerzung` FROM `gebaeude`';

		$name = fill_first_element_from_mysql_query($query);

		return $name;
	}

	function get_gebaeude_abkuerzung ($id, $navigator = 0) {
		function_debug_counter("get_gebaeude_abkuerzung");
		if(is_null($id) || !$id) {
			return null;
		}
		$return = '';
		$key = "get_gebaeude_abkuerzung($id, $navigator)";
		if(array_key_exists($key, $GLOBALS['get_gebaeude_abkuerzung_cache'])) {
			$return = $GLOBALS['get_gebaeude_abkuerzung_cache'][$key];
		} else {
			$query = 'SELECT `abkuerzung` FROM `gebaeude` WHERE `id` = '.esc($id);
			$result = rquery($query);

			while ($row = mysqli_fetch_row($result)) {
				if($navigator) {
					$return = "<a href='".$GLOBALS['navigator_base_url'].strtolower($row[0])."/'>".htmlentities($row[0])."</a>";
				} else {
					$return = $row[0];
				}
			}
		}
		$GLOBALS['get_gebaeude_abkuerzung_cache'][$key] = $return;

		return $return;
	}

	function get_gebaeude_name_abkuerzung ($id) {
		function_debug_counter("get_gebaeude_name_abkuerzung");
		if(is_null($id) || !$id) {
			return null;
		}
		$query = 'SELECT `name`, `abkuerzung` FROM `gebaeude` WHERE `id` = '.esc($id);
		$result = rquery($query);

		$name = NULL;

		while ($row = mysqli_fetch_row($result)) {
			$name = array($row[1], $row[0]);
		}

		return $name;
	}

	function get_gebaeude_name ($id) {
		function_debug_counter("get_gebaeude_name");
		if(array_key_exists($id, $GLOBALS['get_gebaeude_name_cache'])) {
			return $GLOBALS['get_gebaeude_name_cache'][$id];
		} else {
			if(is_null($id) || !$id) {
				return null;
			}
			$query = 'SELECT `name` FROM `gebaeude` WHERE `id` = '.esc($id);

			$ret = get_single_row_from_query($query);
			$GLOBALS['get_gebaeude_name_cache'][$id] = $ret;
			return $ret;
		}
	}

	function get_gebaeude_id_by_abkuerzung ($abkuerzung) {
		function_debug_counter("get_gebaeude_id_by_abkuerzung");
		$query = 'SELECT `id` FROM `gebaeude` WHERE `abkuerzung` = '.esc($abkuerzung).' limit 1';
		return get_single_row_from_query($query);
	}

	function get_gebaeude_geo_coords_by_id ($id) {
		function_debug_counter("get_gebaeude_geo_coords_by_id");
		if(array_key_exists($id, $GLOBALS['get_gebaeude_geo_coords_by_id_cache'])) {
			return $GLOBALS['get_gebaeude_geo_coords_by_id_cache'][$id];
		} else {
			$query = 'SELECT `latitude`, `longitude` FROM `gebaeude` WHERE `id` = '.esc($id).' limit 1';
			$result = rquery($query);

			$array = array();

			while ($data = mysqli_fetch_row($result)) {
				$array = array($data[0], $data[1]);
			}

			$GLOBALS['get_gebaeude_geo_coords_by_id_cache'][$id] = $array;

			return $array;
		}
	}


	function get_gebaeude_id ($name) {
		function_debug_counter("get_gebaeude_id");
		$query = 'SELECT `id` FROM `gebaeude` WHERE `name` = '.esc($name).' limit 1';
		return get_single_row_from_query($query);
	}

	function get_veranstaltungstyp_name ($id) {
		function_debug_counter("get_veranstaltungstyp_name");
		$query = 'SELECT `name` FROM `veranstaltungstyp` WHERE `id` = '.esc($id);
		return get_single_row_from_query($query);
	}

	function get_veranstaltung_name ($id) {
		function_debug_counter("get_veranstaltung_name");
		$query = 'SELECT `name` FROM `veranstaltung` WHERE `id` = '.esc($id);
		return get_single_row_from_query($query);
	}

	function get_veranstaltung_semester ($veranstaltung) {
		function_debug_counter("get_veranstaltung_semester");
		if(is_null($veranstaltung) || !$veranstaltung) {
			return null;
		}

		$key = $veranstaltung;
		if(array_key_exists($key, $GLOBALS['get_veranstaltung_semester_cache'])) {
			$return = $GLOBALS['get_veranstaltung_semester_cache'][$key];
		} else {
			$query = 'SELECT `semester_id` FROM `veranstaltung` WHERE `id` = '.esc($veranstaltung);
			$result = rquery($query);

			$semester = NULL;

			while ($row = mysqli_fetch_row($result)) {
				$return = $row[0];
			}
			$GLOBALS['get_veranstaltung_semester_cache'][$key] = $return;
		}

		return $return;
	}

	function get_veranstaltungstyp_id ($veranstaltungstyp) {
		function_debug_counter("get_veranstaltungstyp_id");
		if(is_null($veranstaltungstyp) || !$veranstaltungstyp) {
			return null;
		}
		$query = 'SELECT `id` FROM `veranstaltungstyp` WHERE `name` = '.esc($veranstaltungstyp).' limit 1';
		$result = rquery($query);

		$veranstaltungstyp_id = NULL;

		while ($row = mysqli_fetch_row($result)) {
			$veranstaltungstyp_id = $row[0];
		}

		return $veranstaltungstyp_id;
	}

	function get_user_array () {
		function_debug_counter("get_user_array");
		$query = 'SELECT id, username from users';

		$name = fill_first_element_from_mysql_query($query);

		return $name;
	}

	function get_dozent_array () {
		function_debug_counter("get_dozent_array");
		$query = 'SELECT `d`.`id`, CONCAT(IF(`t`.`abkuerzung` IS NOT NULL, CONCAT(`t`.`abkuerzung`, " "), ""), `d`.`first_name`, " ", `d`.`last_name`) FROM `dozent` `d` LEFT JOIN `titel` `t` ON `t`.`id` = `d`.`titel_id` ORDER BY `d`.`last_name` asc, `d`.`first_name`';

		$name = fill_first_element_from_mysql_query($query);

		return $name;
	}

	function get_dozent_id_by_veranstaltung_id  ($id) {
		function_debug_counter("get_dozent_id_by_veranstaltung_id");
		if(is_null($id) || !$id) {
			return null;
		}

		$key = "get_dozent_id_by_veranstaltung_id($id)";
		if(array_key_exists($key, $GLOBALS['memoize'])) {
			return $GLOBALS['memoize'][$key];
		}

		$query = 'SELECT dozent_id FROM `veranstaltung` WHERE `id` = '.esc($id);

		$name = get_single_row_from_query($query);

		$GLOBALS['memoize'][$key] = $name;

		return $name;
	}

	function get_pruefungsamt_name ($id) {
		function_debug_counter("get_pruefungsamt_name");
		if(is_null($id) || !$id) {
			return null;
		}

		$key = "get_pruefungsamt_name($id)";
		if(array_key_exists($key, $GLOBALS['memoize'])) {
			return $GLOBALS['memoize'][$key];
		}

		$query = 'SELECT `name` FROM `pruefungsamt` WHERE `id` = '.esc($id);

		$name = get_single_row_from_query($query);

		$GLOBALS['memoize'][$key] = $name;

		return $name;
	}

	function get_dozent_name ($id) {
		function_debug_counter("get_dozent_name");
		if(is_null($id) || !$id) {
			return null;
		}

		$key = "get_dozent_name($id)";
		if(array_key_exists($key, $GLOBALS['memoize'])) {
			return $GLOBALS['memoize'][$key];
		}

		$query = 'SELECT concat(`first_name`, " ", `last_name`) FROM `dozent` WHERE `id` = '.esc($id);

		$name = get_single_row_from_query($query);

		$GLOBALS['memoize'][$key] = $name;

		return $name;
	}

	function get_dozent_id ($first_name, $last_name) {
		function_debug_counter("get_dozent_id");
		$query = 'SELECT `id` FROM `dozent` WHERE concat(`first_name`, " ", `last_name`) = '.esc("$first_name $last_name").' limit 1';
		return get_single_row_from_query($query);
	}

	function get_studiengang_name_by_modul_id ($id) {
		function_debug_counter("get_studiengang_name_by_modul_id");
		$query = 'SELECT `s`.`name` FROM `modul` `m` LEFT JOIN `studiengang` `s` ON `s`.`id` = `m`.`studiengang_id` WHERE `m`.`id` = '.esc($id);
		return get_single_row_from_query($query);
	}

	function get_sws ($stunde, $rhythmus) {
		function_debug_counter("get_sws");
		if($rhythmus == 'keine Angabe') {
			return null;
		}

		if(preg_match("/^\d+$/", $stunde)) {
			return array(0, 2);
		} else if (preg_match("/^(\d+)-(\d+)$/", $stunde, $this_founds)) {
			$start = $this_founds[1];
			$end = $this_founds[2];
			return array(0, (($end - $start + 1) * 2));
		} else {
			$zeiten = get_zeiten($stunde);

			$warn = 0;
			if(preg_match('/^(\d+:\d+)\s*&mdash;\s*(\d+:\d+)$/', $zeiten, $founds)) {
				$from = zeit_nach_sekunde_am_tag($founds[1]);
				$to = zeit_nach_sekunde_am_tag($founds[2]);

				$swsu = (($to - $from) / 60 / 45);
				$sws = round(($to - $from) / 60 / 45);
				if($sws != $swsu) {
					$warn = 1;
				}

				if($rhythmus != 'jede Woche') {
					$sws = $sws / 2;
				}

				return array($warn, $sws);
			} else {
				return null;
			}
		}
	}

	function zeit_nach_sekunde_am_tag ($zeit) {
		function_debug_counter("zeit_nach_sekunde_am_tag");
		if(preg_match('/^(\d+):(\d+)$/', $zeit, $founds)) {
			return ($founds[1] * 60 * 60) + ($founds[2] * 60);
		} else {
			return null;
		}
	}

	function get_zeiten ($stunde, $array = 0) {
		function_debug_counter("get_zeiten");
		if(preg_match('/^(\d+)-(\d+)$/', $stunde, $founds)) {
			return create_hour_from_to($founds[1], $founds[2], $array);
		} else if(preg_match('/^\d$/', $stunde)) {
			return create_hour_from_to($stunde, $stunde, $array);
		} else {
			switch($stunde) {
				case '*':
					return '<i>Siehe Hinweise</i>';
				case 'Ganztägig':
					return 'Ganztägig';
				default:
					return 'ERROR';
			}
		}

	}

	function get_veranstaltungsabkuerzung_by_id ($id) {
		function_debug_counter("get_veranstaltungsabkuerzung_by_id");
		$key = "get_veranstaltungsabkuerzung_by_id($id)";
		if(array_key_exists($key, $GLOBALS['memoize'])) {
			return $GLOBALS['memoize'][$key];
		}
		$query = 'SELECT `abkuerzung` FROM `veranstaltungstyp` WHERE `id` = '.esc($id);

		$name = get_single_row_from_query($query);

		$GLOBALS['memoize'][$key] = $name;

		return $name;	
	}

	function get_veranstaltungsabkuerzung_array () {
		function_debug_counter("get_veranstaltungsabkuerzung_array");
		$query = 'SELECT `id`, `abkuerzung` FROM `veranstaltungstyp`';

		$name = fill_first_element_from_mysql_query($query);

		return $name;	
	}

	function get_veranstaltungsname_by_id ($id) {
		function_debug_counter("get_veranstaltungsname_by_id");
		$query = 'SELECT `name` FROM `veranstaltung` WHERE `id` = '.esc($id);
		return get_single_row_from_query($query);
	}

	function create_raum_name_id_array () {
		function_debug_counter("create_raum_name_id_array");
		$query = 'SELECT `id`, `raumnummer` FROM `raum`';
		$result = rquery($query);

		$return = array();
		while ($row = mysqli_fetch_row($result)) {
			$return[$row[0]] = $row[1];
		}
		return $return;
	}

	function create_titel_abk_array () {
		function_debug_counter("create_titel_abk_array");
		$query = 'SELECT `id`, `abkuerzung` FROM `titel`';
		$result = rquery($query);

		$return = array();
		while ($row = mysqli_fetch_row($result)) {
			$return[] = $row;
		}
		return $return;
	}

	function create_titel_array () {
		function_debug_counter("create_titel_array");
		$query = 'SELECT `id`, `name` FROM `titel`';
		$result = rquery($query);

		$return = array();
		while ($row = mysqli_fetch_row($result)) {
			$return[$row[0]] = $row[1];
		}
		return $return;
	}

	function get_raum_name_by_id ($id) {
		function_debug_counter("get_raum_name_by_id");
		if(is_null($id) || !$id) {
			return null;
		}

		$return = null;

		if(array_key_exists($id, $GLOBALS['raum_name_cache'])) {
			$return = $GLOBALS['raum_name_cache'][$id];
		} else {
			$query = 'SELECT `raumnummer` FROM `raum` WHERE `id` = '.esc($id);
			$result = rquery($query);

			while ($row = mysqli_fetch_row($result)) {
				$return = $row[0];
			}
			$GLOBALS['raum_name_cache'][$id] = $return;
		}

		return $return;
	}

	function get_and_create_studiengang ($name, $institut, $bereich) {
		function_debug_counter("get_and_create_studiengang");
		$id = get_studiengang_id_by_name($name);
		
		if($id) {
			return $id;
		} else {
			create_studiengang($name, $institut, $bereich);

			return get_studiengang_id_by_name($name);
		}
	}

	function get_studiengang_id_by_name ($name) {
		function_debug_counter("get_studiengang_id_by_name");
		$query = 'SELECT `id` FROM `studiengang` WHERE `name` = '.esc($name).' limit 1';
		return get_single_row_from_query($query);
	}

	function get_rolle_beschreibung ($id) {
		function_debug_counter("get_rolle_beschreibung");
		$query = 'SELECT `beschreibung` FROM `role` WHERE `id` = '.esc($id);
		$result = rquery($query);

		$beschreibung = NULL;

		while ($row = mysqli_fetch_row($result)) {
			if($row[0]) {
				$beschreibung = $row[0];
			}
		}

		return $beschreibung;
	}

	function get_seitentext ($allow_html = 0) {
		function_debug_counter("get_seitentext");
		$tpnr = '';
		if(array_key_exists('this_page_number', $GLOBALS) && !is_null($GLOBALS['this_page_number'])) {
			$tpnr = $GLOBALS['this_page_number'];
		} else {
			$tpnr = get_page_id_by_filename('welcome.php');
		}

		$query = 'SELECT `text` FROM `seitentext` WHERE `page_id` = '.esc($tpnr);
		$result = rquery($query);

		$id = NULL;

		while ($row = mysqli_fetch_row($result)) {
			if($row[0]) {
				$id = $row[0];
			}
		}

		if($allow_html) {
			return $id;
		} else {
			return htmlentities($id);
		}
	}

	function get_single_row_from_result ($result, $default = NULL) {
		function_debug_counter("get_single_row_from_result");
		$id = $default;
		while ($row = mysqli_fetch_row($result)) {
			$id = $row[0];
		}
		return $id;
	}


	function get_single_row_from_query ($query, $default = NULL) {
		function_debug_counter("get_single_row_from_query");
		$result = rquery($query);
		return get_single_row_from_result($result, $default);
	}

	function get_institut_name ($id) {
		function_debug_counter("get_institut_name");
		$query = 'SELECT `name` FROM `institut` WHERE `id` = '.esc($id);
		return get_single_row_from_query($query);
	}

	function get_studienordnung_url ($studiengang) {
		function_debug_counter("get_studienordnung_url");
		if($studiengang == 'alle') {
			return null;
		}
		$query = 'SELECT `studienordnung` FROM `studiengang` WHERE `id` = '.esc($studiengang);
		return get_single_row_from_query($query);
	}

	function get_institut_id ($name) {
		function_debug_counter("get_institut_id");
		$query = 'SELECT `id` FROM `institut` WHERE `name` = '.esc($name).' limit 1';
		return get_single_row_from_query($query);
	}

	/* MySQL-create-Funktionen */

	/*
		Trägt die verschiedenen Datensatztypen ein.
	 */

	function modul_zu_veranstaltung_hinzufuegen ($id, $modul_id) {
		function_debug_counter("modul_zu_veranstaltung_hinzufuegen");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'INSERT IGNORE INTO `veranstaltung_nach_modul` (`veranstaltung_id`, `modul_id`) values ('.esc($id).', '.esc($modul_id).')';
		$result = rquery($query);
		if($result) {
			success("Das Modul `".get_modul_name($modul_id)."` wurde erfolgreich zur Veranstaltung eingetragen.");
		} else {
			error("Das Modul `".get_modul_name($modul_id)."` konnte nicht erfolgreich zur Veranstaltung eingetragen werden.");
		}
	}

	function studiengang_zu_veranstaltung_hinzufuegen ($id, $studiengang) {
		function_debug_counter("studiengang_zu_veranstaltung_hinzufuegen");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'INSERT IGNORE INTO `veranstaltung_nach_studiengang` (`veranstaltung_id`, `studiengang_id`) values ('.esc($id).', '.esc($studiengang).')';
		$result = rquery($query);
		if($result) {
			success('Der Studiengang wurde erfolgreich zur Veranstaltung eingetragen.');
		} else {
			error('Der Studiengang konnte nicht erfolgreich zur Veranstaltung eingetragen werden.');
		}
	}

	function create_nachpruefung ($pruefung_id, $datum, $raum_id, $stunde) {
		function_debug_counter("create_nachpruefung");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'INSERT INTO `nachpruefung` (`pruefungs_id`, `raum_id`, `datum`, `stunde`) VALUES ('.esc($pruefung_id).', '.esc($raum_id).', '.esc($datum).', '.esc($stunde).')';
		$result = rquery($query);
		if($result) {
			$inserted_id = mysqli_insert_id($GLOBALS['dbh']);
			if(raum_ist_belegt($raum_id, $datum, $stunde, null, $inserted_id)) {
				warning('Der Raum ist bereits belegt!');
			}
			success('Die Nachprüfung wurde erfolgreich zur Prüfung eingetragen.');
		} else {
			error('Die Nachprüfung konnte nicht erfolgreich zur Prüfung eingetragen werden.');
		}
	}

	function create_role ($role, $beschreibung) {
		function_debug_counter("create_role");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'INSERT IGNORE INTO `role` (`name`, `beschreibung`) VALUES ('.esc($role).', '.esc($beschreibung).')';
		$result = rquery($query);
		if($result) {
			success('Die Rolle wurde erfolgreich eingetragen.');
		} else {
			error('Die Rolle konnte nicht eingetragen werden.');
		}
	}

	function delete_language ($id) {
		function_debug_counter("delete_language");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'DELETE FROM `language` WHERE `id` = '.esc($id);
		$result = rquery($query);
		if($result) {
			success('Die Sprache wurde erfolgreich gelöscht.');
		} else {
			error('Die Sprache konnte nicht gelöscht werden.');
		}

	}

	function create_language ($name, $abkuerzung) {
		function_debug_counter("create_language");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'INSERT IGNORE INTO `language` (`name`, `abkuerzung`) VALUES ('.esc($name).', '.esc($abkuerzung).')';
		$result = rquery($query);
		if($result) {
			success('Die Sprache wurde erfolgreich eingetragen.');
		} else {
			error('Die Sprache konnte nicht eingetragen werden.');
		}
	}

	function create_api ($email, $ansprechpartner, $grund) {
		function_debug_counter("create_api");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$auth_code = generate_random_string(30);
		$query = 'INSERT IGNORE INTO `api_auth_codes` (`auth_code`, `email`, `ansprechpartner`, `grund`, `user_id`) VALUES ('.multiple_esc_join(array($auth_code, $email, $ansprechpartner, $grund, $GLOBALS['logged_in_user_id'])).')';
		$result = rquery($query);
		if($result) {
			success('Der API-Zugang wurde erfolgreich eingetragen.');
		} else {
			error('Die API-Zugang konnte nicht eingetragen werden.');
		}
	}

	function create_pruefungsnummer($modul, $pruefungsnummer, $pruefungstyp, $bereich, $modulbezeichnung, $zeitraum_id) {
		function_debug_counter("create_pruefungsnummer");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'INSERT IGNORE INTO `pruefungsnummer` (`pruefungsnummer`, `modul_id`, `pruefungstyp_id`, `bereich_id`, `modulbezeichnung`, `zeitraum_id`) VALUES ('.esc($pruefungsnummer).', '.esc($modul).', '.esc($pruefungstyp).', '.esc($bereich).', '.esc($modulbezeichnung).', '.esc($zeitraum_id).')';
		$result = rquery($query);
		if($result) {
			if(mysqli_affected_rows($GLOBALS['dbh'])) {
				success('Die Prüfungsnummer wurde erfolgreich eingetragen.');
			} else {
				error('Der Prüfungsnummer konnte nicht eingetragen werden.');
			}
		} else {
			error('Der Prüfungsnummer konnte nicht eingetragen werden.');
		}	
	}

	function create_user ($name, $password, $role, $dozent, $institut, $barrierefrei) {
		function_debug_counter("create_user");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'SELECT `id` FROM `users` WHERE `dozent_id` = '.esc($dozent);
		$result = rquery($query);

		if(mysqli_num_rows($result)) {
			warning('Der Dozent hatte bereits einen Account. Der Account wird ohne konkrete Dozentenzuordnung trotzdem erstellt.');
			$dozent = null;
		}

		$salt = generate_random_string(100);
		$query = 'INSERT IGNORE INTO `users` (`username`, `password_sha256`, `dozent_id`, `institut_id`, `salt`, `barrierefrei`) VALUES ('.esc($name).', '.esc(hash('sha256', $password.$salt)).', '.esc($dozent).', '.esc($institut).', '.esc($salt).', '.esc($barrierefrei).')';
		$result = rquery($query);
		if($result) {
			$id = get_user_id($name);
			$query = 'INSERT IGNORE INTO `role_to_user` (`role_id`, `user_id`) VALUES ('.esc($role).', '.esc($id).')';
			$result = rquery($query);

			if($result) {
				success('Der User wurde mit seiner Rolle erfolgreich eingetragen.');
			} else {
				error('Der User konnte eingefügt, aber nicht seiner Rolle zugeordnet werden.');
			}
		} else {
			error('Der User konnte nicht eingetragen werden.');
		}
	}

	function create_faq ($frage, $antwort, $wie_oft_gestellt) {
		function_debug_counter("create_faq");
		if(!check_function_rights(__FUNCTION__)) { return; }

		if(!preg_match("/^\d+$/", $wie_oft_gestellt)) {
			$wie_oft_gestellt = 0;
		}

		$query = 'INSERT INTO `faq` (`frage`, `antwort`, `wie_oft_gestellt`) VALUES ('.esc($frage).', '.esc($antwort).', '.esc($wie_oft_gestellt).')';
		$result = rquery($query);

		if($result) {
			success('Der FAQ-Eintrag wurde erfolgreich eingefügt.');
		} else {
			error('Der FAQ-Eintrag konnte nicht eingefügt werden.');
		}
	}

	function create_veranstaltung($name, $dozent, $veranstaltungstyp, $institut, $semester, $language, $related_veranstaltung) {
		function_debug_counter("create_veranstaltung");
		if(!check_function_rights(__FUNCTION__)) { return; }

		if(((get_role_id_by_user($GLOBALS['logged_in_user_id']) != 1 && $dozent != $GLOBALS['user_dozent_id'])) && !user_can_edit_other_users_veranstaltungen($GLOBALS['logged_in_user_id'], $dozent)) {
			// Wenn der User nicht der Gruppe der Admins zugehörig ist, dann kann er nur seine eigenen Sachen ändern.
			error('Sie haben nicht die notwendigen Rechte um Veranstaltungen Anderer zu erstellen.');
			return;
		}

		$query = 'INSERT IGNORE INTO `veranstaltung` (`veranstaltungstyp_id`, `name`, `dozent_id`, `institut_id`, `semester_id`) VALUES ('.
			multiple_esc_join(array($veranstaltungstyp, $name, $dozent, $institut, $semester)).')';

		easter_egg($name);

		$result = rquery($query);
		if($result) {
			$inserted_id = mysqli_insert_id($GLOBALS['dbh']);
			$woche = null;
			if(get_veranstaltungstyp_name($veranstaltungstyp) == 'Blockseminar') {
				$woche = 'keine Angabe';
			}

			$einzelne_termine = get_einzelne_termine_from_post();

			update_veranstaltung_metadata($inserted_id, null, null, $woche, null, null, null, null, null, null, null, null, null, $master_niveau, $language, $related_veranstaltung, $einzelne_termine);
			success('Die Veranstaltung wurde erfolgreich eingetragen.');
		} else {
			error('Die Veranstaltung konnte nicht eingetragen werden.');
		}
	}

	function create_dozent ($first_name, $last_name) {
		function_debug_counter("create_dozent");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'INSERT IGNORE INTO `dozent` (`first_name`, `last_name`) VALUES ('.esc($first_name).', '.esc($last_name).')';
		$result = rquery($query);
		if($result) {
			success('Der Dozent wurde erfolgreich eingetragen.');
		} else {
			error('Der Dozent konnte nicht eingetragen werden.');
		}

	}

	function create_veranstaltungstyp ($name, $abkuerzung) {
		function_debug_counter("create_veranstaltungstyp");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'INSERT IGNORE INTO `veranstaltungstyp` (`abkuerzung`, `name`) VALUES ('.esc($abkuerzung).', '.esc($name).')';
		$result = rquery($query);
		if($result) {
			success('Der Veranstaltungstyp wurde erfolgreich eingetragen.');
		} else {
			error('Der Veranstaltungstyp konnte nicht eingetragen werden.');
		}
	}

	function create_gebaeude ($name, $abkuerzung) {
		function_debug_counter("create_gebaeude");
		if(!check_function_rights(__FUNCTION__)) { return; }
		if($abkuerzung && $name) {
			$query = 'INSERT IGNORE INTO `gebaeude` (`abkuerzung`, `name`) VALUES ('.esc($abkuerzung).', '.esc($name).')';
			$result = rquery($query);
			if($result) {
				success('Das Gebäude wurde erfolgreich eingetragen.');
			} else {
				error('Das Gebäude konnte nicht eingetragen werden.');
			}
		} else {
			message("Für Gebäude muss sowohl ein Name als auch eine Abkürzung eingetragen werden.");
		}

	}

	function create_raum ($gebaeude_id, $raumnummer) {
		function_debug_counter("create_raum");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'INSERT IGNORE INTO `raum` (`gebaeude_id`, `raumnummer`) VALUES ('.esc($gebaeude_id).', '.esc($raumnummer).')';
		$result = rquery($query);
		if($result) {
			success('Der Raum wurde erfolgreich eingetragen.');
		} else {
			error('Der Raum konnte nicht eingetragen werden.');
		}
	}

	function create_studiengang ($name, $institut_id, $studienordnung) {
		function_debug_counter("create_studiengang");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'INSERT IGNORE INTO `studiengang` (`name`, `institut_id`, `studienordnung`) VALUES ('.esc($name).', '.esc($institut_id).', '.esc($studienordnung).')';
		$result = rquery($query);
		if($result) {
			success('Der Studiengang wurde erfolgreich eingetragen.');
		} else {
			error('Der Studiengang konnte nicht eingetragen werden.');
		}
	}

	function create_modul ($modulname, $studiengang_id, $beschreibung, $abkuerzung) {
		function_debug_counter("create_modul");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'INSERT IGNORE INTO `modul` (`name`, `studiengang_id`, `abkuerzung`, `beschreibung`) VALUES ('.esc($modulname).', '.esc($studiengang_id).', '.esc($abkuerzung).', '.esc($beschreibung).')';
		$result = rquery($query);
		if($result) {
			success('Das Modul wurde erfolgreich eingetragen.');
		} else {
			error('Das Modul konnte nicht eingetragen werden.');
		}
	}

	function create_institut ($name, $start_nr) {
		function_debug_counter("create_institut");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'INSERT IGNORE INTO `institut` (`name`, `start_nr`) VALUES ('.esc($name).', '.esc($start_nr).')';
		$result = rquery($query);
		if($result) {
			success('Die Institut wurde erfolgreich eingetragen.');
		} else {
			error('Die Institut konnte nicht eingetragen werden.');
		}
	}

	function create_fach ($name) {
		function_debug_counter("create_fach");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'INSERT IGNORE INTO `pruefungsnummer_fach` (`name`) VALUES ('.esc($name).')';
		$result = rquery($query);
		if($result) {
			success('Das Fach wurde erfolgreich eingetragen.');
		} else {
			error('Das Fach konnte nicht eingetragen werden.');
		}
	}

	function delete_titel ($id) {
		function_debug_counter("delete_titel");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'DELETE FROM `titel` WHERE `id` = '.esc($id);
		$result = rquery($query);
		if($result) {
			success('Der Titel wurde erfolgreich gelöscht.');
		} else {
			error('Der Titel konnte nicht gelöscht werden.');
		}
	}

	function create_title ($name, $abk) {
		function_debug_counter("create_title");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'INSERT IGNORE INTO `titel` (`name`, `abkuerzung`) VALUES ('.esc(array($name, $abk)).')';
		$result = rquery($query);
		if($result) {
			success('Der Titel wurde erfolgreich eingetragen.');
		} else {
			error('Der Titel konnte nicht eingetragen werden.');
		}
	}

	function create_pruefungsamt ($name) {
		function_debug_counter("create_pruefungsamt");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'INSERT IGNORE INTO `pruefungsamt` (`name`) VALUES ('.esc($name).')';
		$result = rquery($query);
		if($result) {
			success('Das Prüfungsamt wurde erfolgreich eingetragen.');
		} else {
			error('Das Prüfungsamt konnte nicht eingetragen werden.');
		}
	}

	function create_pruefung_zeitraum ($name) {
		function_debug_counter("create_pruefung_zeitraum");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'INSERT IGNORE INTO `pruefung_zeitraum` (`name`) VALUES ('.esc($name).')';
		$result = rquery($query);
		if($result) {
			success('Der Zeitraum wurde erfolgreich eingetragen.');
		} else {
			error('Der Zeitraum konnte nicht eingetragen werden.');
		}
	}

	function create_function_right ($role_id, $name) {
		function_debug_counter("create_function_right");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'INSERT IGNORE INTO `function_right` (`function_name`) VALUES ('.esc($name).')';
		$result = rquery($query);
		if($result) {
			success('Das Funktionsrecht wurde erfolgreich eingetragen.');
		} else {
			error('Das Funktionsrecht konnte nicht eingetragen werden.');
		}
	}

	function create_bereich ($name) {
		function_debug_counter("create_bereich");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'INSERT IGNORE INTO `bereich` (`name`) VALUES ('.esc($name).')';
		$result = rquery($query);
		if($result) {
			success('Der Bereich wurde erfolgreich eingetragen.');
		} else {
			error('Der Bereich konnte nicht eingetragen werden.');
		}
	}

	function create_pruefungstyp ($name) {
		function_debug_counter("create_pruefungstyp");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'INSERT IGNORE INTO `pruefungstyp` (`name`) VALUES ('.esc($name).')';
		$result = rquery($query);
		if($result) {
			success('Der Prüfungstyp wurde erfolgreich eingetragen.');
		} else {
			error('Der Prüfungstyp konnte nicht eingetragen werden.');
		}
	}

	function create_pruefung ($pruefungstyp_id, $veranstaltung_id, $name, $pruefungsnummer, $datum, $stunde, $raum) {
		function_debug_counter("create_pruefung");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'INSERT IGNORE INTO `pruefung` (`pruefungstyp_id`, `veranstaltung_id`, `name`, `pruefungsnummer`, `datum`, `stunde`, `raum_id`) VALUES ('.esc($pruefungstyp_id).', '.esc($veranstaltung_id).', '.esc($name).', '.esc($pruefungsnummer).', '.esc($datum).', '.esc($stunde).', '.esc($raum).')';
		$result = rquery($query);
		if($result) {
			$inserted_id = mysqli_insert_id($GLOBALS['dbh']);
			if($inserted_id) {
				if(raum_ist_belegt($raum, $datum, $stunde, $inserted_id, null)) {
					warning('Der Raum ist bereits belegt!');
				}
			} else {
				message('Konnte ID der neu-eingetragenen Prüfung nicht ermitteln.');
			}
			success('Die Prüfung wurde erfolgreich eingetragen.');
		} else {
			error('Die Prüfung konnte nicht eingetragen werden.');
		}
	}

	/* MySQL-delete-Funktionen */

	function delete_pruefungsnummer ($id) {
		function_debug_counter("delete_pruefungsnummer");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'DELETE FROM `pruefungsnummer` WHERE `id` = '.esc($id);
		$result = rquery($query);
		if($result) {
			success('Die Prüfungsnummer wurde erfolgreich gelöscht.');
		} else {
			error('Die Prüfungsnummer konnte nicht gelöscht werden.');
		}
	}

	function delete_role ($id) {
		function_debug_counter("delete_role");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'DELETE FROM `role` WHERE `id` = '.esc($id);
		$result = rquery($query);
		if($result) {
			success('Die Rolle wurde erfolgreich gelöscht.');
		} else {
			error('Die Rolle konnte nicht gelöscht werden.');
		}
	}

	function delete_nachpruefung ($id) {
		function_debug_counter("delete_nachpruefung");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'DELETE FROM `nachpruefung` WHERE `id` = '.esc($id);
		$result = rquery($query);
		if($result) {
			success('Der Prüfungstyp wurde erfolgreich gelöscht.');
		} else {
			error('Der Prüfungstyp konnte nicht gelöscht werden.');
		}
	}

	function delete_page ($id) {
		function_debug_counter("delete_page");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'DELETE FROM `page` WHERE `id` = '.esc($id);
		$result = rquery($query);
		if($result) {
			success('Die Seite wurde erfolgreich gelöscht.');
		} else {
			error('Die Seite konnte nicht gelöscht werden.');
		}
	}

	function delete_pruefung_zeitraum ($id) {
		function_debug_counter("delete_pruefung_zeitraum");
		if(!check_function_rights(__FUNCTION__)) {
			return;
		}

		$query = 'DELETE FROM `pruefung_zeitraum` WHERE `id` = '.esc($id);
		$result = rquery($query);

		if($result) {
			success('Der Zeitraum wurde erfolgreich gelöscht.');
		} else {
			error('Der Zeitraum konnte nicht gelöscht werden.');
		}
	}

	function delete_pruefungsamt ($id) {
		function_debug_counter("delete_pruefungsamt");
		if(!check_function_rights(__FUNCTION__)) {
			return;
		}

		$query = 'DELETE FROM `pruefungsamt` WHERE `id` = '.esc($id);
		$result = rquery($query);

		if($result) {
			success('Das Prüfungsamt wurde erfolgreich gelöscht.');
		} else {
			error('Das Prüfungsamt konnte nicht gelöscht werden.');
		}
	}

	function delete_bereich ($id) {
		function_debug_counter("delete_bereich");
		if(!check_function_rights(__FUNCTION__)) {
			return;
		}
		$query = 'DELETE FROM `bereich` WHERE `id` = '.esc($id);
		$result = rquery($query);
		if($result) {
			success('Der Bereich wurde erfolgreich gelöscht.');
		} else {
			error('Der Bereich konnte nicht gelöscht werden.');
		}
	}

	function delete_semester ($id) {
		function_debug_counter("delete_semester");
		if(!check_function_rights(__FUNCTION__)) {
			return;
		}
		$query = 'DELETE FROM `semester` WHERE `id` = '.esc($id);
		$result = rquery($query);
		if($result) {
			success('Das Semester wurde erfolgreich gelöscht.');
		} else {
			error('Das Semester konnte nicht gelöscht werden.');
		}		
	}

	function delete_fach ($id) {
		function_debug_counter("delete_fach");
		if(!check_function_rights(__FUNCTION__)) {
			return;
		}
		$query = 'DELETE FROM `pruefungsnummer_fach` WHERE `id` = '.esc($id);
		$result = rquery($query);
		if($result) {
			success('Das Fach wurde erfolgreich gelöscht.');
		} else {
			error('Das Fach konnte nicht gelöscht werden.');
		}
	}

	function delete_pruefungstyp ($id) {
		function_debug_counter("delete_pruefungstyp");
		if(!check_function_rights(__FUNCTION__)) {
			return;
		}
		$query = 'DELETE FROM `pruefungstyp` WHERE `id` = '.esc($id);
		$result = rquery($query);
		if($result) {
			success('Der Prüfungstyp wurde erfolgreich gelöscht.');
		} else {
			error('Der Prüfungstyp konnte nicht gelöscht werden.');
		}
	}

	function delete_modul ($id) {
		function_debug_counter("delete_modul");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'DELETE FROM `modul` WHERE `id` = '.esc($id);
		$result = rquery($query);
		if($result) {
			success('Das Modul wurde erfolgreich gelöscht.');
		} else {
			error('Das Modul konnte nicht gelöscht werden.');
		}
	}

	function delete_api ($auth_code) {
		function_debug_counter("delete_api");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'DELETE FROM `api_auth_codes` WHERE `auth_code` = '.esc($auth_code);
		$result = rquery($query);
		if($result) {
			success('Der API-Zugang wurde erfolgreich gelöscht.');
		} else {
			error('Der API-Zugang konnte nicht gelöscht werden.');
		}
	}

	function delete_user ($id) {
		function_debug_counter("delete_user");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'DELETE FROM `users` WHERE `id` = '.esc($id);
		$result = rquery($query);
		if($result) {
			success('Der Benutzer wurde erfolgreich gelöscht.');
		} else {
			error('Der Benutzer konnte nicht gelöscht werden.');
		}
	}

	function delete_funktion_rights ($id) {
		function_debug_counter("delete_funktion_rights");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'DELETE FROM `function_right` WHERE `id` = '.esc($id);
		$result = rquery($query);
		if($result) {
			success('Das Funktionsrecht wurde erfolgreich gelöscht.');
		} else {
			error('Das Funktionsrecht konnte nicht gelöscht werden.');
		}
	}

	function delete_studiengang ($id) {
		function_debug_counter("delete_studiengang");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'DELETE FROM `studiengang` WHERE `id` = '.esc($id);
		$result = rquery($query);
		if($result) {
			success('Der Studiengang wurde erfolgreich gelöscht.');
		} else {
			error('Der Studiengang konnte nicht gelöscht werden.');
		}
	}

	function delete_raum ($id) {
		function_debug_counter("delete_raum");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'DELETE FROM `raum` WHERE `id` = '.esc($id);
		$result = rquery($query);
		if($result) {
			success('Der Raum wurde erfolgreich gelöscht.');
		} else {
			error('Der Raum konnte nicht gelöscht werden.');
		}
	}

	function delete_pruefung ($id) {
		function_debug_counter("delete_pruefung");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'DELETE FROM `pruefung` WHERE `id` = '.esc($id);
		$result = rquery($query);
		if($result) {
			success('Die Prüfung wurde erfolgreich gelöscht.');
		} else {
			error('Die Prüfung konnte nicht gelöscht werden.');
		}
	}

	function delete_gebaeude ($id) {
		function_debug_counter("delete_gebaeude");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'DELETE FROM `gebaeude` WHERE `id` = '.esc($id);
		$result = rquery($query);
		if($result) {
			success('Das Gebäude wurde erfolgreich gelöscht.');
		} else {
			error('Das Gebäude konnte nicht gelöscht werden.');
		}
	}

	function delete_veranstaltung_modul ($veranstaltung_id, $modul_id) {
		function_debug_counter("delete_veranstaltung_modul");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'DELETE FROM `veranstaltung_nach_modul` WHERE `veranstaltung_id` = '.esc($veranstaltung_id).' AND `modul_id` = '.esc($modul_id);
		$result = rquery($query);
		if($result) {
			success('Die Verbindung zwischen Modul und Veranstaltung wurde erfolgreich gelöscht.');
		} else {
			error('Die Verbindung zwischen Modul und Veranstaltung konnte nicht gelöscht werden.');
		}
	}

	function delete_veranstaltung_studiengang ($veranstaltung_id, $studiengang_id) {
		function_debug_counter("delete_veranstaltung_studiengang");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'DELETE FROM `veranstaltung_nach_studiengang` WHERE `veranstaltung_id` = '.esc($veranstaltung_id).' AND `studiengang_id` = '.esc($studiengang_id);
		$result = rquery($query);
		if($result) {
			success('Die Verbindung zwischen Veranstaltung und Studiengang wurde erfolgreich gelöscht.');
		} else {
			error('Die Verbindung zwischen Veranstaltung und Studiengang konnte nicht gelöscht werden.');
		}
	}

	function delete_veranstaltungstyp ($id) {
		function_debug_counter("delete_veranstaltungstyp");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'DELETE FROM `veranstaltungstyp` WHERE `id` = '.esc($id);
		$result = rquery($query);
		if($result) {
			success('Der Veranstaltungstyp wurde erfolgreich gelöscht.');
		} else {
			error('Der Veranstaltungstyp konnte nicht gelöscht werden.');
		}
	}

	function delete_veranstaltung ($id) {
		function_debug_counter("delete_veranstaltung");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'DELETE FROM `veranstaltung` WHERE `id` = '.esc($id);
		$result = rquery($query);
		if($result) {
			success('Die Veranstaltung wurde erfolgreich gelöscht.');
		} else {
			error('Die Veranstaltung konnte nicht gelöscht werden.');
		}
	}

	function delete_dozent ($id) {
		function_debug_counter("delete_dozent");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'DELETE FROM `dozent` WHERE `id` = '.esc($id);
		$result = rquery($query);
		if($result) {
			success('Der Dozent wurde erfolgreich gelöscht.');
		} else {
			error('Der Dozent konnte nicht gelöscht werden.');
		}
	}

	function delete_institut ($id) {
		function_debug_counter("delete_institut");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'DELETE FROM `institut` WHERE `id` = '.esc($id);
		$result = rquery($query);
		if($result) {
			success('Das Institut wurde erfolgreich gelöscht.');
		} else {
			error('Das Institut konnte nicht gelöscht werden.');
		}
	}
	
	/* MySQL-update-Funktionen */

	function update_pruefungsnummer($id, $modul_id, $pruefungsnummer, $pruefungstyp, $bereich, $modulbezeichnung, $zeitraum_id, $pndisabled) {
		function_debug_counter("update_pruefungsnummer");
		if(!check_function_rights(__FUNCTION__)) { return; }
		if($pndisabled == "Ja") {
			$pndisabled = "1";
		} else {
			$pndisabled = "0";
		}
		$query = 'UPDATE `pruefungsnummer` SET `pruefungsnummer` = '.esc($pruefungsnummer).', `modul_id` = '.esc($modul_id).', `pruefungstyp_id` = '.esc($pruefungstyp).', `bereich_id` = '.esc($bereich).', `modulbezeichnung` = '.esc($modulbezeichnung).', `zeitraum_id` = '.esc($zeitraum_id).', `disabled` = '.esc($pndisabled).' WHERE `id` = '.esc($id);
		$result = rquery($query);
		if($result) {
			success('Die Prüfungsnummer wurde erfolgreich geändert.');
		} else {
			message('Die Prüfungsnummer konnte nicht geändert werden oder es waren keine Änderungen notwendig.');
		}

	}

	function get_salt ($id) {
		function_debug_counter("get_salt");
		$query = 'SELECT `salt` FROM `users` WHERE `id` = '.esc($id);
		return get_single_row_from_query($query);
	}

	function get_and_create_salt ($id) {
		function_debug_counter("get_and_create_salt");
		if(!check_function_rights(__FUNCTION__)) { return; }

		$result = get_salt($id);

		if($result) {
			return $result;
		} else {
			$salt = generate_random_string(100);
			$query = 'UPDATE `users` SET `salt` = '.esc($salt).' WHERE `id` = '.esc($id);
			$results = rquery($query);

			if($results) {
				$id = get_salt($name, $studiengang);
				if($id) {
					message('Salt eingefügt.');
					return $id;
				} else {
					message('Salt konnte nicht eingefügt werden.');
					return null;
				}
			} else {
				die(mysqli_error());
			}
		}
	}

	function update_barrierefrei ($barrierefrei) {
		function_debug_counter("update_barrierefrei");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'UPDATE `users` SET `barrierefrei` = '.esc($barrierefrei).' WHERE `id` = '.esc($GLOBALS['logged_in_user_id']);
		$result = rquery($query);
		if($result) {
			success('Die Barrierefreitsoption wurde erfolgreich geändert.');
		} else {
			error('Die Barrierefreitsoption wurde nicht geändert.');
		}
	}

	function update_own_data ($password) {
		function_debug_counter("update_own_data");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$salt = get_and_create_salt($GLOBALS['logged_in_user_id']);
		$query = 'UPDATE `users` SET `password_sha256` = '.esc(hash('sha256', $password.$salt)).' WHERE `id` = '.esc($GLOBALS['logged_in_user_id']);
		$result = rquery($query);
		if($result) {
			success('Ihr Passwort wurde erfolgreich geändert.');
		} else {
			message('Die Benutzerdaten konnten nicht geändert werden oder es waren keine Änderungen notwendig.');
		}
	}

	function update_user ($name, $id, $password, $role, $dozent, $institut, $enable, $barrierefrei, $accpubdata) {
		function_debug_counter("update_user");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$salt = get_and_create_salt($id);
		$enabled = 1;
		if(!$enable) {
			$enabled = 0;
		}
		$query = '';
		if($password) {
			$query = 'UPDATE `users` SET `username` = '.esc($name).', `password_sha256` = '.esc(hash('sha256', $password.$salt)).', `dozent_id` = '.esc($dozent).', `institut_id` = '.esc($institut).', `enabled` = '.esc($enabled).', `barrierefrei` = '.esc($barrierefrei).', `accepted_public_data` = '.esc($accpubdata).' WHERE `id` = '.esc($id);
		} else {
			$query = 'UPDATE `users` SET `username` = '.esc($name).', `dozent_id` = '.esc($dozent).', `institut_id` = '.esc($institut).', `enabled` = '.esc($enabled).', `barrierefrei` = '.esc($barrierefrei).', `accepted_public_data` = '.esc($accpubdata).' WHERE `id` = '.esc($id);
		}
		$result = rquery($query);
		if($result) {
			$query = 'INSERT INTO `role_to_user` (`role_id`, `user_id`) VALUES ('.esc($role).', '.esc($id).') ON DUPLICATE KEY UPDATE `role_id` = '.esc($role);
			$result = rquery($query);
			if($result) {
				success('Die Benutzerdaten und Rollenzuordnungen wurden erfolgreich geändert.');
			} else {
				success('Die Benutzerdaten wurden erfolgreich geändert, aber die Rollenänderung hat nicht geklappt.');
			}
		} else {
			message('Die Benutzerdaten konnten nicht geändert werden oder es waren keine Änderungen notwendig.');
		}
		$GLOBALS['reload_page'] = 1;
	}

	function update_dozent ($id, $first_name, $last_name, $ausgeschieden = 0) {
		function_debug_counter("update_dozent");
		if(!check_function_rights(__FUNCTION__)) { return; }
		if($ausgeschieden == 'ja') {
			$ausgeschieden = "1";
		} else {
			$ausgeschieden = "0";
		}
		$query = 'UPDATE `dozent` SET `first_name` = '.esc($first_name).', `last_name` = '.esc($last_name).', `ausgeschieden` = '.esc($ausgeschieden).' WHERE `id` = '.esc($id);
		$result = rquery($query);
		if($result) {
			success('Die Daten des Dozenten wurden erfolgreich geändert.');
		} else {
			message('Die Daten des Dozenten konnten nicht geändert werden oder es waren keine Änderungen notwendig.');
		}
	}

/*
	+----------------------+------------------+------+-----+---------+----------------+
	| Field                | Type             | Null | Key | Default | Extra          |
	+----------------------+------------------+------+-----+---------+----------------+
	| id                   | int(10) unsigned | NO   | PRI | NULL    | auto_increment |
	| veranstaltungstyp_id | int(10) unsigned | YES  | MUL | NULL    |                |
	| name                 | varchar(100)     | YES  |     | NULL    |                |
	| dozent_id            | int(10) unsigned | YES  | MUL | NULL    |                |
	| gebaeudewunsch_id    | int(10) unsigned | YES  | MUL | NULL    |                |
	| gebaeude_id          | int(10) unsigned | YES  | MUL | NULL    |                |
	| raummeldung          | date             | YES  |     | NULL    |                |
	| raumwunsch_id        | int(10) unsigned | YES  | MUL | NULL    |                |
	| raum_id              | int(10) unsigned | YES  | MUL | NULL    |                |
	| opal_link            | varchar(1000)    | YES  |     | NULL    |                |
	+----------------------+------------------+------+-----+---------+----------------+

 */

	function update_veranstaltung($id, $name, $dozent, $veranstaltungstyp, $institut, $semester, $master_niveau) {
		function_debug_counter("update_veranstaltung");
		if(!check_function_rights(__FUNCTION__)) {
			return;
		}


		if(!user_can_edit_other_users_veranstaltungen($GLOBALS['logged_in_user_id'], $dozent)) {
			if(get_role_id_by_user($GLOBALS['logged_in_user_id']) != 1 && $dozent != $GLOBALS['user_dozent_id']) {
				// Wenn der User nicht der Gruppe der Admins zugehörig ist, dann kann er nur seine eigenen Sachen ändern.
				error('Sie haben nicht die notwendigen Rechte um Veränderungen an den Daten Anderer vorzunehmen.');
				return;
			}
		}

		if($master_niveau) {
			$master_niveau = '1';
		} else {
			$master_niveau = "0";
		}

		easter_egg($name);

		$alte_daten = get_raumplanung_relevante_daten($id);

		$query = 'UPDATE `veranstaltung` SET `veranstaltungstyp_id` = '.esc($veranstaltungstyp).', `name` = '.esc($name).', `dozent_id` = '.esc($dozent).', `institut_id` = '.esc($institut).', `semester_id` = '.esc($semester).', `master_niveau` = '.esc($master_niveau).' WHERE `id` = '.esc($id);

		$result = rquery($query);

		if($result) {
			success('Die Veranstaltung wurde erfolgreich geändert.');
		} else {
			message('Die Veranstaltung konnte nicht geändert werden oder es waren keine Änderungen notwendig.');
		}
		updated_raumplanung_relevante_daten($id, $alte_daten);
	}

	function update_veranstaltungstyp ($id, $name, $abkuerzung) {
		function_debug_counter("update_veranstaltungstyp");
		if(!check_function_rights(__FUNCTION__)) {
			return;
		}
		$query = 'UPDATE `veranstaltungstyp` SET `name` = '.esc($name).', `abkuerzung` = '.esc($abkuerzung).' WHERE `id` = '.esc($id);
		$result = rquery($query);
		if($result) {
			success('Der Veranstaltungstyp wurde erfolgreich geändert.');
		} else {
			message('Der Veranstaltungstyp konnte nicht geändert werden oder es waren keine Änderungen notwendig.');
		}
	}

	/*
		+------------------+----------------------------------------------------+------+-----+------------+-------+
		| Field            | Type                                               | Null | Key | Default    | Extra |
		+------------------+----------------------------------------------------+------+-----+------------+-------+
		| veranstaltung_id | int(10) unsigned                                   | YES  | UNI | NULL       |       |
		| wunsch           | varchar(500)                                       | YES  |     | NULL       |       |
		| hinweis          | varchar(500)                                       | YES  |     | NULL       |       |
		| opal_link        | varchar(500)                                       | YES  |     | NULL       |       |
		| anzahl_hoerer    | int(10) unsigned                                   | YES  |     | NULL       |       |
		| erster_termin    | date                                               | YES  |     | NULL       |       |
		| wochentag        | enum('Mo','Di','Mi','Do','Fr','Sa','So')           | NO   |     | Mo         |       |
		| stunde           | enum('1','2','3','4','5','6','7','8')              | NO   |     | 1          |       |
		| woche            | enum('gerade woche','ungerade woche','jede woche') | NO   |     | jede woche |       |
		+------------------+----------------------------------------------------+------+-----+------------+-------+

	 */
	function update_api($auth_code, $email, $ansprechpartner, $grund) {
		function_debug_counter("update_api");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'UPDATE `api_auth_codes` SET `email` = '.esc($email).', `ansprechpartner` = '.esc($ansprechpartner).', `grund` = '.esc($grund).' WHERE `auth_code` = '.esc($auth_code);
		$result = rquery($query);

		if($result) {
			success('API-Zugang erfolgreich editiert.');
		} else {
			success('API-Zugang konnte nicht editiert werden.');
		}
	}

	function update_startseitentext ($startseitentext) {
		function_debug_counter("update_startseitentext");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = '';
		if(get_startseitentext()) {
			$query = 'UPDATE `startseite` SET `text` = '.esc($startseitentext);;
		} else {
			$query = 'INSERT INTO `startseite` (`text`) VALUES ('.esc($startseitentext).');';
		}
		$result = rquery($query);

		if($result) {
			success('Startseitentext erfolgreich editiert.');
		} else {
			success('Startseitentext konnte nicht editiert werden.');
		}
	}

	function kopiere_pruefungen_von_nach ($von, $nach, $delete_old_data = 0) {
		function_debug_counter("kopiere_pruefungen_von_nach");
		if(!check_function_rights(__FUNCTION__)) { return; }

		start_transaction();

		$result = '';
		if($delete_old_data) {
			$query = 'DELETE FROM `pruefung` WHERE `veranstaltung_id` = '.esc($nach);
			$result = rquery($query);
		}
		if($result || !$delete_old_data) {
			$query = 'select pruefungsnummer_id, date, raum_id from pruefung where veranstaltung_id = '.esc($von);
			$result = rquery($query);

			$fail = 0;

			while ($row = mysqli_fetch_row($result)) {
				$this_insert_query = 'INSERT INTO `pruefung` (`pruefungsnummer_id`, `date`, `raum_id`, `veranstaltung_id`) VALUES ('.esc($row[0]).', '.esc($row[1]).', '.esc($row[2]).', '.esc($nach).')';
				$result1 = rquery($this_insert_query);
				if(!$result1) {
					$fail = 1;
					break;
				}
			}

			if($fail) {
				rollback();
				error("Kopieren fehlgeschlagen. Alter Zustand wurde wiederhergestellt.");
			} else {
				commit();
				success("Die Prüfungen wurden erfolgreich kopiert.");
			}
		} else {
			rollback();
			error("Kopieren fehlgeschlagen. Alter Zustand wurde wiederhergestellt.");
		}
	}

	function update_raumplanung($id, $gebaeude, $raum, $meldungsdatum) {
		function_debug_counter("update_raumplanung");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = '
UPDATE `veranstaltung` SET
	`gebaeude_id` = '.esc($gebaeude).',
	`raum_id` = '.esc(get_and_create_raum_id($gebaeude, $raum, 1)).',
	`raummeldung` = '.esc(convert_date($meldungsdatum)).'
WHERE `id` = '.esc($id);

		$result = rquery($query);

		if($result) {
			raumplanung_update($id);
			success('Die Raumplanungsinformationen wurden erfolgreich geändert.');
		} else {
			message('Die Raumplanungsinformationen konnten nicht geändert werden oder es waren keine Änderungen notwendig.');
		}
	}

	function add_missing_seconds_to_datetime ($dt) {
		function_debug_counter("add_missing_seconds_to_datetime");
		# 2018-09-07 00:00
		if(preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $dt)) {
			return $dt;
		} else if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $dt)) {
			return "$dt:00";
		} else {
			error("Ungültiges Datetime-Format. Muss YYYY-MM-DD HH:MM oder YYYY-MM-DD HH:MM:SS sein!");
			return null;
		}
	}

	function nice_einzelne_veranstaltung_by_id ($id) {
		$data = get_einzelne_termine_by_veranstaltung_id($id);
		if(is_array($data)) {
			$betterdata = array();

			foreach ($data as $id => $this_data) {
				$gebaeude_und_raum = $this_data['gebaeude_abkuerzung'].' '.$this_data['raumnummer'];

				$start_datum = add_leading_zero($this_data['start_day']).'.'.add_leading_zero($this_data['start_month']).'.'.add_leading_zero($this_data['start_year']);
				$end_datum = add_leading_zero($this_data['end_day']).'.'.add_leading_zero($this_data['end_month']).'.'.add_leading_zero($this_data['end_year']);

				$start_time = add_leading_zero($this_data['start_hour']).':'.add_leading_zero($this_data['start_minute']);
				$end_time = add_leading_zero($this_data['end_hour']).':'.add_leading_zero($this_data['end_minute']);

				$start_unixtime = strtotime($this_data['start_month']."/".$this_data['start_day']."/".$this_data["start_year"].' '.$start_time);

				$from_to_string = $start_datum;
				if($start_datum == $end_datum) {
					$from_to_string .= " -- $start_time - $end_time";
				} else {
					$from_to_string .= ' $start_time -- '.$end_datum .' '.$end_time;
				}

				$bd = array(
					"start_unixtime" => $start_unixtime,
					"gebaeude_und_raum" => $gebaeude_und_raum,
					"start_datum" => $start_datum,
					"end_datum" => $end_datum,
					"start_time" => $start_time,
					"end_time" => $end_time,
					"from_to_string" => $from_to_string
				);
				$betterdata[] = $bd;
			}
			return $betterdata;
		} else {
			return "";
		}
	}

	function get_einzelne_termine_by_veranstaltung_id ($id) {
		function_debug_counter("get_einzelne_termine_by_veranstaltung_id");
		$query = 'select year(e.start) as start_year, month(e.start) as start_month, day(e.start) as start_day, hour(e.start) as start_hour, minute(e.start) as start_minute, year(e.end) as end_year, month(e.end) as end_month, day(e.end) as end_day, hour(e.end) as end_hour, minute(e.end) as end_minute, g.id as gebaeude_id, g.name as gebaeude_name, r.raumnummer, r.id as raum_id, g.abkuerzung as gebaeude_abkuerzung, dayname(e.start) as day_start, dayname(e.end) as day_end from einzelne_termine e left join raum r on e.raum_id = r.id left join gebaeude g on g.id = r.gebaeude_id  where veranstaltung_id = '.esc($id);
		$result = rquery($query);

		$data = array();

		while ($row = mysqli_fetch_assoc($result)) {
			$data[] = $row;
		}

		return $data;
	}

	# 					1	2	   3	   4		5		6	    7		8	9		10			11
# 12		    13
	function update_veranstaltung_metadata ($id, $wochentag, $stunde, $woche, $erster_termin, $anzahl_hoerer, $wunsch, $hinweis, $opal_link, $abgabe_pruefungsleistungen, $raumwunsch, $gebaeudewunsch, $pruefungsnummern, $master_niveau, $language, $related_veranstaltung, $einzelne_termine) {
		function_debug_counter("update_veranstaltung_metadata");
		if(!check_function_rights(__FUNCTION__)) { return; }

		$alte_daten = get_raumplanung_relevante_daten($id);

		$query = '
INSERT INTO 
	`veranstaltung_metadaten` (
		`veranstaltung_id`,
		`wunsch`,
		`hinweis`,
		`opal_link`,
		`anzahl_hoerer`,
		`erster_termin`,
		`wochentag`,
		`stunde`,
		`woche`,
		`abgabe_pruefungsleistungen`,
		`related_veranstaltung`
	) VALUES (
		'.esc($id).',
		'.esc($wunsch).',
		'.esc($hinweis).',
		'.esc($opal_link).',
		'.esc($anzahl_hoerer).',
		'.esc($erster_termin).',
		'.esc($wochentag).',
		'.esc($stunde).',
		'.esc($woche).',
		'.esc($abgabe_pruefungsleistungen).',
		'.esc($related_veranstaltung).'
	) ON DUPLICATE KEY UPDATE
		`wunsch` = '.esc($wunsch).',
		`hinweis` = '.esc($hinweis).',
		`opal_link` = '.esc($opal_link).',
		`anzahl_hoerer` = '.esc($anzahl_hoerer).',
		`erster_termin` = '.esc($erster_termin).',
		`wochentag` = '.esc($wochentag).',
		`stunde` = '.esc($stunde).',
		`abgabe_pruefungsleistungen` = '.esc($abgabe_pruefungsleistungen).',
		`woche` = '.esc($woche).',
		`related_veranstaltung` = '.esc($related_veranstaltung);

		$result = rquery($query);

		if($result) {
			$query = '';
			if($raumwunsch && $gebaeudewunsch) {
				$query = 'UPDATE `veranstaltung` SET `raumwunsch_id` = '.esc($raumwunsch).', `gebaeudewunsch_id` = '.esc($gebaeudewunsch).' WHERE `id` = '.esc($id);
			} elseif ($gebaeudewunsch) {
				$query = 'UPDATE `veranstaltung` SET `gebaeudewunsch_id` = '.esc($gebaeudewunsch).' WHERE `id` = '.esc($id);
			}

			if($query) {
				if(rquery($query)) {
					success('Die Details zur Veranstaltung wurden erfolgreich geändert.');

				} else {
					message('Die Details zur Veranstaltung wurden erfolgreich geändert. Aber der Raumwunsch konnte nicht gespeichert werden.');
				}
			} else {
				success('Die Details zur Veranstaltung wurden erfolgreich geändert.');
			}

			if(is_array($einzelne_termine) && count($einzelne_termine)) {
				start_transaction();
				$delete_query = 'delete from einzelne_termine where veranstaltung_id = '.esc($id);
				$res = rquery($delete_query);
				if($res) {
					$error = 0;
					foreach ($einzelne_termine as $einzelner_termin) {
						if(!$error) {
							$start = add_missing_seconds_to_datetime($einzelner_termin['einzelner_termin_start']);
							$ende = add_missing_seconds_to_datetime($einzelner_termin['einzelner_termin_ende']);
							$gebaeude_id = $einzelner_termin['einzelner_termin_gebaeude'];
							$raum = $einzelner_termin['einzelner_termin_raum'];
							$raum_id = get_and_create_raum_id($gebaeude_id, $raum);

							if($start && $ende) {
								$query = 'insert ignore into einzelne_termine (veranstaltung_id, start, end, raum_id) values ('.esc($id).', '.esc($start).', '.esc($ende).', '.esc($raum_id).')';
								$res = rquery($query);
								if(!$res) {
									$error = 1;
								}
							}
						}

					}

					if($error) {
						rollback();
						error("Die einzelnen Termine konnten nicht hinzugefügt werden.");
					} else {
						commit();
						success("Die einzelnen Termine wurden erfolgreich hinzugefügt.");
					}
				} else {
					error("Konnte die alten Termine nicht löschen und die Neuen hinzufügen.");
					rollback();
				}
			}

			if(is_array($language) && count($language)) {
				start_transaction();
				$failed = 0;
				$delete = 'delete from veranstaltung_to_language where veranstaltung_id = '.esc($id);
				if(!rquery($delete)) {
					$failed = 1;
				}
				if(!$failed) {
					foreach ($language as $this_language) {
						if(!$failed) {
							$query = 'insert into veranstaltung_to_language (veranstaltung_id, language_id) values ('.esc($id).', '.esc($this_language).')';
							if(!rquery($query)) {
								$failed = 1;
							}
						}
					}
				}

				if($failed) {
					rollback();
					error("Die gewählten Sprachen konnten nicht hinzugefügt werden.");
				} else {
					commit();
					success("Die gewählten Sprachen wurden erfolgreich hinzugefügt.");
				}
			}

			$query = 'UPDATE `veranstaltung` SET `master_niveau` = '.esc($master_niveau).' WHERE `id` = '.esc($id);
			if(rquery($query)) {
				success('Das Niveau wurde angepasst.');
			} else {
				warning("Die Studiengangsniveaueinstellung konnte nicht gespeichert werden");
			}
		} else {
			message('Die Metadaten zur Veranstaltung konnten nicht geändert werden oder es waren keine Änderungen notwendig.');
		}

		assign_pruefungsnummer_to_veranstaltung($pruefungsnummern, $id);
		updated_raumplanung_relevante_daten($id, $alte_daten);
	}

	function assign_pruefungsnummer_to_veranstaltung ($pruefungsnummer, $id) {
		function_debug_counter("assign_pruefungsnummer_to_veranstaltung");
		if(!check_function_rights(__FUNCTION__)) { return; }

		if(is_array($pruefungsnummer)) {
			start_transaction();
			rquery('DELETE FROM pruefung where veranstaltung_id = '.esc($id));
			$commit = 1;
			foreach ($pruefungsnummer as $this_pn) {
				if($commit) {
					$query = 'INSERT IGNORE INTO `pruefung` (veranstaltung_id, pruefungsnummer_id) values ('.esc($id).', '.esc($this_pn).')';
					$result = rquery($query);
					if(!$result) {
						$commit = 0;
					}
				}
			}

			if($commit) {
				commit();
				success('Die Prüfungsnummern wurden erfolgreich zur Veranstaltung hinzugefügt bzw. entfernt.');
			} else {
				rollback();
				error('Bei einer der Prüfungsnummern trat ein Fehler auf. Daher wird alles zurückgesetzt.');
			}
		} else {
			assign_pruefungsnummer_to_veranstaltung(array($pruefungsnummer), $id);
		}
	}

	function update_gebaeude ($id, $name, $abkuerzung) {
		function_debug_counter("update_gebaeude");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'UPDATE `gebaeude` SET `name` = '.esc($name).', `abkuerzung` = '.esc($abkuerzung).' WHERE `id` = '.esc($id);
		$result = rquery($query);
		if($result) {
			success('Das Gebäude wurde erfolgreich geändert.');
		} else {
			message('Das Gebäude konnte nicht geändert werden oder es waren keine Änderungen notwendig.');
		}
	}

	function update_raum ($id, $raum_name, $gebaeude_id) {
		function_debug_counter("update_raum");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'UPDATE `raum` SET `raumnummer` = '.esc($raum_name).', `gebaeude_id` = '.esc($gebaeude_id).' WHERE `id` = '.esc($id);
		$result = rquery($query);
		if($result) {
			success('Der Raum wurde erfolgreich geändert.');
		} else {
			message('Der Raum konnte nicht geändert werden oder es waren keine Änderungen notwendig.');
		}
	}

	function update_funktion_rights ($id, $name) {
		function_debug_counter("update_funktion_rights");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'UPDATE `function_right` SET `function_name` = '.esc($name).' WHERE `id` = '.esc($id);
		$result = rquery($query);
		if($result) {
			success('Das Funktionsrecht wurde erfolgreich geändert.');
		} else {
			message('Das Funktionsrecht konnte nicht geändert werden oder es waren keine Änderungen notwendig.');
		}
	}

	function update_studiengang ($id, $name, $institut_id, $studienordnung) {
		function_debug_counter("update_studiengang");
		if(!check_function_rights(__FUNCTION__)) { return; }
		if ($studienordnung && filter_var($studienordnung, FILTER_VALIDATE_URL) === FALSE) {
			error('`'.htmlentities($studienordnung).'` ist keine valide URL für die Studienordnung.');
			$studienordnung = '';
		}
		$query = 'UPDATE `studiengang` SET `name` = '.esc($name).', `institut_id` = '.esc($institut_id).', `studienordnung` = '.esc($studienordnung).' WHERE `id` = '.esc($id);
		$result = rquery($query);
		if($result) {
			success('Der Studiengang wurde erfolgreich geändert.');
		} else {
			message('Der Studiengang konnte nicht geändert werden oder es waren keine Änderungen notwendig.');
		}
	}

	function update_modul ($id, $name, $studiengang_id, $beschreibung, $abkuerzung) {
		function_debug_counter("update_modul");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'UPDATE `modul` SET `studiengang_id` = '.esc($studiengang_id).', `name` = '.esc($name).', `beschreibung` = '.esc($beschreibung).', `abkuerzung` = '.esc($abkuerzung).' WHERE `id` = '.esc($id);
		$result = rquery($query);
		if($result) {
			success('Das Modul wurde erfolgreich geändert.');
		} else {
			message('Das Modul konnte nicht geändert werden oder es waren keine Änderungen notwendig.');
		}
	}

	function update_institut ($id, $name, $start_nr) {
		function_debug_counter("update_institut");
		if(!check_function_rights(__FUNCTION__)) { return; }
		if(preg_match('/^\d+$', $start_nr)) {
			$query = 'UPDATE `institut` SET `name` = '.esc($name).', `start_nr` = '.esc($start_nr).' WHERE `id` = '.esc($id);
			$result = rquery($query);
			if($result) {
				success('Das Institut wurde erfolgreich geändert.');
			} else {
				message('Das Institut konnte nicht geändert werden oder es waren keine Änderungen notwendig.');
			}
		} else {
			error("Die Startnummer muss eine natürliche Zahl sein");
		}
	}

	function update_page ($id, $name, $file) {
		function_debug_counter("update_page");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'UPDATE `page` SET `name` = '.esc($name).', `file` = '.esc($file).' WHERE `id` = '.esc($id);
		$result = rquery($query);
		if($result) {
			success('Die Seite wurde erfolgreich geändert.');
		} else {
			message('Die Seite konnte nicht geändert werden oder es waren keine Änderungen notwendig.');
		}
	}

	/*
		id ist die page-id
		role_to_page muss ein array sein mit ids von rollen, die der seite
		zugeordnet werden sollen
	 */
	function update_or_create_role_to_page ($id, $role_to_page) {
		function_debug_counter("update_or_create_role_to_page");
		if(!check_function_rights(__FUNCTION__)) { return; }

		if(isset($role_to_page) && !is_array($role_to_page)) {
			$temp = array();
			$temp[] = $role_to_page;
		}

		if(is_array($role_to_page) && count($role_to_page)) {
			$at_least_one_role_set = 0;
			foreach ($role_to_page as $trole) {
				$rname = get_role_name($trole);
				if($rname) {
					$at_least_one_role_set = 1;
				}
			}

			$roles_cleared = 0;
			if($at_least_one_role_set) {
				$query = 'DELETE FROM `'.$GLOBALS['dbname'].'`.`role_to_page` WHERE `page_id` = '.esc($id);
				$result = rquery($query);
				if($result) {
					success("Die Rollen wurden erfolgreich geklärt.");
					$roles_cleared = 1;
				} else {
					error("Die Rollen wurden NICHT erfolgreich geklärt.");
				}
			}

			if($roles_cleared) {
				foreach ($role_to_page as $trole) {
					$rname = get_role_name($trole);
					if($rname) {
						$query = 'INSERT IGNORE INTO `'.$GLOBALS['dbname'].'`.`role_to_page` (`role_id`, `page_id`) VALUES ('.esc($trole).', '.esc($id).')';
						$result = rquery($query);
						if($result) {
							success("Die Rolle $rname wurde erfolgreich hinzugefügt.");
						} else {
							error("Die Rolle $rname konnte nicht eingefügt werden.");
						}
					} else {
						error("Die Rolle mit der ID $trole existiert nicht.");
					}
				}
			}
		}
	}

	function create_new_page ($name, $file, $show_in_navigation, $parent, $role_to_page, $beschreibung, $hinweis) {
		function_debug_counter("create_new_page");
		if(!check_function_rights(__FUNCTION__)) { return; }
		if($parent == "") {
			$parent = null;
		}
		$query = 'INSERT IGNORE INTO `'.$GLOBALS['dbname'].'`.`page` (`name`, `file`, `show_in_navigation`, `parent` ) VALUES ('.esc(array($name, $file, $show_in_navigation, $parent)).')';
		$result = rquery($query);
		if($result) {
			$idquery = 'SELECT LAST_INSERT_ID()';

			$id = get_single_row_from_query($idquery);

			if($id) {
				update_role_to_page_page_info_hinweis ($id, $role_to_page, $beschreibung, $hinweis);

				success('Die Seite wurde erfolgreich hinzugefügt.');
			} else {
				error('Die letzte insert-id konnte nicht ermittelt werden, aber die Seite wurde erstellt.');
			}
		} else {
			message('Die Seite konnte nicht erfolgreich hinzugefügt werden.');
		}
	}

	function update_role_to_page_page_info_hinweis ($id, $role_to_page, $beschreibung, $hinweis) {
		function_debug_counter("update_role_to_page_page_info_hinweis");
		if(isset($role_to_page)) {
			update_or_create_role_to_page($id, $role_to_page);
		}

		if(isset($beschreibung)) {
			update_page_info($id, $beschreibung);
		}

		if(isset($hinweis)) {
			update_hinweis($id, $hinweis);
		}
	}

	function update_page_full($id, $name, $file, $show_in_navigation, $parent, $role_to_page, $beschreibung, $hinweis) {
		function_debug_counter("update_page_full");
		if(!check_function_rights(__FUNCTION__)) { return; }
		if($parent == "") {
			$parent = null;
		}
		$query = 'UPDATE `page` SET `name` = '.esc($name).', `file` = '.esc($file).', `show_in_navigation` = '.esc($show_in_navigation).', `parent` = '.esc($parent).' WHERE `id` = '.esc($id);
		$result = rquery($query);
		if($result) {
			update_role_to_page_page_info_hinweis ($id, $role_to_page, $beschreibung, $hinweis);

			update_function_to_page($id, $role_to_page);
			success('Die Seite wurde erfolgreich geändert.');
		} else {
			message('Die Seite konnte nicht geändert werden oder es waren keine Änderungen notwendig.');
		}
	}

	function update_function_to_page ($id, $role_to_page) {
		function_debug_counter("update_function_to_page");
		 #$query = 'select function_right_id, page_id from function_right_to_page where page_id = '.;
	}

	function update_pruefungstyp ($id, $name) {
		function_debug_counter("update_pruefungstyp");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'UPDATE `pruefungstyp` SET `name` = '.esc($name).' WHERE `id` = '.esc($id);
		$result = rquery($query);
		if($result) {
			success('Der Prüfungstyp wurde erfolgreich geändert.');
		} else {
			message('Der Prüfungstyp konnte nicht geändert werden oder es waren keine Änderungen notwendig.');
		}
	}

	function update_fach ($id, $name) {
		function_debug_counter("update_fach");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'UPDATE `pruefungsnummer_fach` SET `name` = '.esc($name).' WHERE `id` = '.esc($id);
		$result = rquery($query);
		if($result) {
			success('Das Fach wurde erfolgreich geändert.');
		} else {
			message('Das Fach konnte nicht geändert werden oder es waren keine Änderungen notwendig.');
		}
	}

	function update_pruefungsamt ($id, $name) {
		function_debug_counter("update_pruefungsamt");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'UPDATE `pruefungsamt` SET `name` = '.esc($name).' WHERE `id` = '.esc($id);
		$result = rquery($query);
		if($result) {
			success('Das Prüfungsamt wurde erfolgreich geändert.');
		} else {
			message('Das Prüfungsamt konnte nicht geändert werden oder es waren keine Änderungen notwendig.');
		}
	}

	function update_pruefung_zeitraum ($id, $name) {
		function_debug_counter("update_pruefung_zeitraum");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'UPDATE `pruefung_zeitraum` SET `name` = '.esc($name).' WHERE `id` = '.esc($id);
		$result = rquery($query);
		if($result) {
			success('Der Zeitraum wurde erfolgreich geändert.');
		} else {
			message('Der Zeitraum konnte nicht geändert werden oder es waren keine Änderungen notwendig.');
		}
	}

	function update_bereich ($id, $name) {
		function_debug_counter("update_bereich");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'UPDATE `bereich` SET `name` = '.esc($name).' WHERE `id` = '.esc($id);
		$result = rquery($query);
		if($result) {
			success('Der Bereich wurde erfolgreich geändert.');
		} else {
			message('Der Bereich konnte nicht geändert werden oder es waren keine Änderungen notwendig.');
		}
	}

	function update_role ($id, $name, $beschreibung) {
		function_debug_counter("update_role");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'UPDATE `role` SET `name` = '.esc($name).', `beschreibung` = '.esc($beschreibung).' WHERE `id` = '.esc($id);
		$result = rquery($query);
		if($result) {
			success('Die Rolle wurde erfolgreich geändert.');
		} else {
			message('Die Rolle konnte nicht geändert werden oder es waren keine Änderungen notwendig.');
		}
	}

	function update_pruefung ($id, $pruefungstyp_id, $veranstaltung_id, $pruefungsnummer, $pruefungsname, $datum, $stunde, $raum) {
		function_debug_counter("update_pruefung");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'UPDATE `pruefung` SET `pruefungsnummer` = '.esc($pruefungsnummer).', `veranstaltung_id` = '.esc($veranstaltung_id).', `pruefungstyp_id` = '.esc($pruefungstyp_id).', `name` = '.esc($pruefungsname).', `datum` = '.esc($datum).', `stunde` = '.esc($stunde).', `raum_id` = '.esc($raum).' WHERE `id` = '.esc($id);
		$result = rquery($query);
		if($result) {
			if(raum_ist_belegt($raum, $datum, $stunde, $id, null)) {
				warning('Der Raum ist bereits belegt!');
			}
			success('Die Prüfung wurde erfolgreich geändert.');
		} else {
			message('Die Prüfung konnte nicht geändert werden oder es waren keine Änderungen notwendig.');
		}
	}

	function update_text ($page_id, $text) {
		function_debug_counter("update_text");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'INSERT INTO `seitentext` (`page_id`, `text`) VALUES ('.esc($page_id).', '.esc($text).') ON DUPLICATE KEY UPDATE `text` = '.esc($text);
		$result = rquery($query);
		if($result) {
			success('Der Seitentext wurde erfolgreich geändert.');
		} else {
			message('Der Seitentext konnte nicht geändert werden oder es waren keine Änderungen notwendig.');
		}
	}

	function delete_faq ($id) {
		function_debug_counter("delete_faq");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'DELETE FROM `faq` WHERE `id` = '.esc($id);
		$result = rquery($query);
		if($result) {
			success('Die Frage wurde erfolgreich gelöscht.');
		} else {
			message('Die Frage konnte nicht gelöscht werden.');
		}
	}

	function update_faq ($id, $frage, $antwort, $wie_oft_gestellt) {
		function_debug_counter("update_faq");
		if(!check_function_rights(__FUNCTION__)) { return; }
		if(!preg_match('/^\d+$/', $wie_oft_gestellt)) {
			error("Wie oft gestellt muss eine natürliche Zahl sein. Sie wird auf 1 gesetzt statt auf ".htmle($wie_oft_gestellt));
			$wie_oft_gestellt = 1;
		}
		$query = 'UPDATE `faq` SET `frage` = '.esc($frage).', `antwort` = '.esc($antwort).', `wie_oft_gestellt` = '.esc($wie_oft_gestellt).' WHERE `id` = '.esc($id);
		$result = rquery($query);
		if($result) {
			success('Die Frage wurde erfolgreich geändert.');
		} else {
			message('Die Frage konnte nicht geändert werden oder es waren keine Änderungen notwendig.');
		}
	}

	function get_credit_points_and_anzahl_pruefungsleistungen_by_modul_id_and_semester ($modul, $semester) {
		function_debug_counter("get_credit_points_and_anzahl_pruefungsleistungen_by_modul_id_and_semester");
		$query = 'SELECT `credit_points`, `anzahl_pruefungsleistungen` FROM `modul_nach_semester_metadata` WHERE `modul_id` = '.esc($modul).' AND `semester` = '.esc($semester);
		$result = rquery($query);
		
		$data = fill_data_from_mysql_result($result);

		return $data;
	}

	function get_veranstaltungstypen_modul_semester ($modul, $semester) {
		function_debug_counter("get_veranstaltungstypen_modul_semester");
		$data = array();
		$query = 'SELECT `v`.`name` as `veranstaltungstyp_name`, `ms`.`anzahl`, `v`.`id` AS `veranstaltungstyp_id` FROM `modul_nach_semester_veranstaltungstypen_anzahl` AS `ms` JOIN `veranstaltungstyp` `v` ON `v`.`id` = `ms`.`veranstaltungstyp_id` WHERE `ms`.`modul_id` = '.esc($modul).' AND `ms`.`semester` = '.esc($semester).' AND `ms`.`anzahl` ORDER BY `ms`.`anzahl` DESC';
		$result = rquery($query);

		while ($row = mysqli_fetch_row($result)) {
			$data[] = array($row[0], $row[1], $row[2]);
		}

		return $data;
	}

	function create_array_veranstaltungstyp_anzahl_by_modul_id_semester($modul, $semester) {
		function_debug_counter("create_array_veranstaltungstyp_anzahl_by_modul_id_semester");
		$array = array();
		foreach (create_veranstaltungstyp_array() as $this_veranstaltungstyp) {
			$array[$this_veranstaltungstyp[0]] = 0;
			$query = 'SELECT `veranstaltungstyp_id`, `anzahl` FROM `modul_nach_semester_veranstaltungstypen_anzahl` WHERE `modul_id` = '.esc($modul).' AND `semester` = '.esc($semester);
			$result = rquery($query);

			while ($row = mysqli_fetch_row($result)) {
				$array[$row[0]] = $row[1];
				
			}
		}

		return $array;
	}

	function update_modul_semester_data($semester, $studiengang, $credit_points, $pruefungsleistung_anzahl, $veranstaltungstypen_anzahl, $modul_id) {
		function_debug_counter("update_modul_semester_data");
		if(!check_function_rights(__FUNCTION__)) { return; }

		$error = 0;
		if(!preg_match('/^\d+$/', $credit_points)) {
			error("Die Anzahl der Credit-Points muss eine natürliche Zahl sein");
			$error++;
		}

		if(!preg_match('/^\d+$/', $pruefungsleistung_anzahl)) {
			error("Die Anzahl der Prüfungsleistungen muss eine natürliche Zahl sein");
			$error++;
		}

		if($error) {
			return;
		}

		$query = 'INSERT INTO `modul_nach_semester_metadata` (`modul_id`, `semester`, `credit_points`, `anzahl_pruefungsleistungen`) VALUES ('.multiple_esc_join(array($modul_id, $semester, $credit_points, $pruefungsleistung_anzahl)).') ON DUPLICATE KEY UPDATE `credit_points` = '.esc($credit_points).', `anzahl_pruefungsleistungen` = '.esc($pruefungsleistung_anzahl);
		$result = rquery($query);

		if($result) {
			success('Das Eintragen der Metadaten zum Semester hat funktioniert.');

			start_transaction();

			$failure = 0;
			$query = 'DELETE FROM `modul_nach_semester_veranstaltungstypen_anzahl` WHERE `modul_id` = '.esc($modul_id).' AND `semester` = '.esc($semester);
			$result = rquery($query);
			if(!$result) {
				$failure = 1;
			}

			if(!$failure) {
				if(count($veranstaltungstypen_anzahl)) {
					foreach ($veranstaltungstypen_anzahl as $veranstaltungstyp_id => $veranstaltungstyp_anzahl) {
						if(!$failure) {
							if(preg_match('/^\d+$/', $veranstaltungstyp_anzahl)) {
								$query = 'INSERT INTO `modul_nach_semester_veranstaltungstypen_anzahl` (`modul_id`, `semester`, `veranstaltungstyp_id`, `anzahl`) VALUES ('.multiple_esc_join(array($modul_id, $semester, $veranstaltungstyp_id, $veranstaltungstyp_anzahl)).')';
								$result = rquery($query);

								if(!$result) {
									$failure = 1;
								}
							} else {
								error("Die Anzahl der Veranstaltungen muss eine natürliche Zahl sein.");
							}
						}
					}
				}

				if($failure) {
					rollback();
					error('Die Veranstaltungstypen konnten nicht erfolgreich diesem Studiengang/Semester zugewiesen werden. Die Änderungen werden rückgängig gemacht.');
				} else {
					commit();
					success('Die Veranstaltungstypen wurden erfolgreich dem Studiengang/Semester zugeordnet. Die Änderungen wurden gespeichert.');
				}
			} else {
				rollback();

				error('Die Veranstaltungstypen konnten nicht erfolgreich diesem Studiengang/Semester zugewiesen werden.');
			}
		} else {
			error('Irgendetwas ging schief beim Eintragen...');
		}
	}

	function update_titel ($id, $name, $abk) {
		function_debug_counter("update_titel");
		if(!check_function_rights(__FUNCTION__)) { return; }
		if(isset($id)) {
			if(isset($name)) {
				if(isset($abk)){
					$query = 'UPDATE `titel` SET `name` = '.esc($name).', `abkuerzung` = '.esc($abk).' WHERE `id` = '.esc($id);
					$result = rquery($query);
					if($result) {
						success('Der Titel wurde erfolgreich geändert.');
					} else {
						message('Der Titel konnte nicht geändert werden oder es waren keine Änderungen notwendig.');
					}
				} else {
					error("Leere Abkürzung.");
				}
			} else {
				error("Leerer Name.");
			}
		} else {
			error("Falsche Titel-ID.");
		}
	}

	function update_hinweis ($page_id, $hinweis) {
		function_debug_counter("update_hinweis");
		if(!check_function_rights(__FUNCTION__)) { return; }
		if(get_page_name_by_id($page_id)) {
			if($hinweis) {
				$query = 'INSERT INTO `hinweise` (`page_id`, `hinweis`) VALUES ('.esc($page_id).', '.esc($hinweis).') ON DUPLICATE KEY UPDATE `hinweis` = '.esc($hinweis);
				$result = rquery($query);
				if($result) {
					success('Der neue Hinweis wurde erfolgreich geändert.');
				} else {
					message('Der Hinweis konnte nicht geändert werden oder es waren keine Änderungen notwendig.');
				}
			} else {
				message("Leerer Hinweis.");
			}
		} else {
			error("Falsche Page-ID.");
		}
	}

	function update_superdozent ($user, $dozenten) {
		function_debug_counter("update_superdozent");
		if(!check_function_rights(__FUNCTION__)) { return; }

		start_transaction();

		$error_sum = 0;

		$query = 'DELETE FROM `superdozent` WHERE `user_id` = '.esc($user);
		$result = rquery($query);

		if(!$result) {
			$error_sum++;
		}

		foreach ($dozenten as $this_dozent) {
			$query = 'INSERT INTO `superdozent` (`user_id`, `dozent_id`) VALUES ('.esc($user).', '.$this_dozent.')';
			$result = rquery($query);

			if(!$result) {
				$error_sum++;
			}
		}

		if($error_sum) {
			error('Konnte die neuen Berechtigungen nicht eintragen.');
			rollback();
		} else {
			success('Der Superdozent wurde erfolgreich mit <span class="font40px rainbow">Superkräften</span> ausgestattet!');
			commit();
		}
	}

	function user_is_logged_in () {
		function_debug_counter("user_is_logged_in");
		if(preg_match('/^\d+$/', $GLOBALS["logged_in_user_id"])) {
			return 1;
		} else {
			return 0;
		}
	}

	function user_has_role ($user, $rolename) {
		function_debug_counter("user_has_role");
		$admin_role_id = get_role_id($rolename);

		$this_user_role_id = get_role_id_by_user($user);

		if($this_user_role_id == $admin_role_id) {
			return 1;
		} else {
			return 0;
		}
	}

	function user_is_admin ($user) {
		function_debug_counter("user_is_admin");
		// CACHEN!!!
		return user_has_role($user, 'Administrator');
	}

	function user_is_verwalter ($user) {
		function_debug_counter("user_is_verwalter");
		// CACHEN!!!
		return user_has_role($user, "Verwalter");
	}

	function user_is_superdozent ($user) {
		function_debug_counter("user_is_superdozent");
		$superdozent_role_id = get_role_id('Superdozent');
		$verwalter_role_id = get_role_id('Verwalter');

		$this_user_role_id = get_role_id_by_user($user);

		if($this_user_role_id == $superdozent_role_id || $this_user_role_id == $verwalter_role_id) {
			return 1;
		} else {
			return 0;
		}
	}

	function get_user_per_superdozent ($user) {
		function_debug_counter("get_user_per_superdozent");
		$dozenten_liste = array();

		$query = 'SELECT `dozent_id` FROM `superdozent` WHERE `user_id` = '.esc($user);
		$result = rquery($query);

		while ($row = mysqli_fetch_row($result)) {
			$dozenten_liste[] = $row;
		}

		return $dozenten_liste;
	}

	function user_can_edit_other_users_veranstaltungen ($user, $dozent) {
		function_debug_counter("user_can_edit_other_users_veranstaltungen");
		$can = 0;

		$query = 'SELECT COUNT(*) FROM `superdozent` WHERE `user_id` = '.esc($user).' AND `dozent_id` = '.esc($dozent);
		$result = rquery($query);

		while ($row = mysqli_fetch_row($result)) {
			$can = $row[0];
		}

		if(!$can) {
			if(user_is_verwalter($user)) {
				$can = 1;
			}
		}

		return $can;
	}

	function update_pruefungsamt_studiengang ($id, $studiengang) {
		function_debug_counter("update_pruefungsamt_studiengang");
		if(!check_function_rights(__FUNCTION__)) { return; }

		start_transaction();

		$error_sum = 0;

		$query = 'DELETE FROM `pruefungsamt_nach_studiengang` WHERE `pruefungsamt_id` = '.esc($id);
		$result = rquery($query);
		if(!$result) {
			$error_sum++;
		}

		if(!$error_sum) {
			foreach ($studiengang as $this_studiengang) {
				$query = 'INSERT INTO `pruefungsamt_nach_studiengang` (`pruefungsamt_id`, `studiengang_id`) VALUES ('.esc($id).', '.esc($this_studiengang).')';
				$result = rquery($query);
				if(!$result) {
					$error_sum++;
				}
			}
		}

		if(!$error_sum) {
			commit();
			success('Der Studiengang wurde erfolgreich zum Prüfungsamt zugeordnet.');
		} else {
			rollback();
			message('Der Studiengang konnte nicht erfolgreich zum Prüfungsamt zugeordnet werden.');
		}
	}

	function update_page_info ($id, $info) {
		function_debug_counter("update_page_info");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'INSERT INTO `page_info` (`page_id`, `info`) VALUES ('.esc($id).', '.esc($info).') ON DUPLICATE KEY UPDATE `info` = '.esc($info);
		$result = rquery($query);
		if($result) {
			success('Die Seiteninfo wurde erfolgreich geändert.');
		} else {
			message('Die Seiteninfo konnte nicht geändert werden oder es waren keine Änderungen notwendig.');
		}
	}

	function update_modul_semester ($modul, $semester) {
		function_debug_counter("update_modul_semester");
		if(!check_function_rights(__FUNCTION__)) { return; }

		$success_counter = 0;
		$failure = 0;

		start_transaction();

		rquery('DELETE FROM `modul_nach_semester` WHERE `modul_id` = '.esc($modul));

		foreach (preg_split('/\s*,\s*/', $semester) as $this_semester) {
			if(preg_match('/^\d+$/', $this_semester)) {
				$query = 'INSERT IGNORE INTO `modul_nach_semester` (`modul_id`, `semester`) VALUES ('.esc($modul).', '.esc($this_semester).')';
				$result = rquery($query);
				if($result) {
					$success_counter++;
				} else {
					$failure++;
				}
			}
		}

		if($success_counter && !$failure) {
			success('Alle Semester konnten dem Modul erfolgreich hinzugefügt werden.');
			commit();
		} else if ($success_counter && $failure) {
			error('Von '.($success_counter + $failure).' Einträgen konnten '.$failure.' nicht dem Modul hinzugefügt werden. Daher wird alles rückgängig gemacht.');
			rollback();
		} else if(!$success_counter && $failure) {
			error('Es konnte keines der Semester dem Modul hinzugefügt werden. Daher wird alles rückgängig gemacht.');
			rollback();
		} else {
			success('Die Modul-Semester-Zuordnungen wurden entfernt.');
			commit();
		}
	}

	function update_nachpruefung ($id, $pruefung, $datum, $raum, $stunde) {
		function_debug_counter("update_nachpruefung");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'UPDATE `nachpruefung` SET `pruefungs_id` = '.esc($pruefung).', `raum_id` = '.esc($raum).', `datum` = '.esc($datum).', `stunde` = '.esc($stunde).' WHERE `id` = '.esc($id);
		$result = rquery($query);
		if($result) {
			if(raum_ist_belegt($raum, $datum, $stunde, null, $id)) {
				warning('Der Raum ist bereits belegt!');
			}
			success('Die Prüfung wurde erfolgreich geändert.');
		} else {
			message('Die Prüfung konnte nicht geändert werden oder es waren keine Änderungen notwendig.');
		}
	}

	function create_semester_type_array () {
		function_debug_counter("create_semester_type_array");
		return array('Wintersemester', 'Sommersemester');
	}

	/* Darstellungsfunktionen */

	#			1		2	3	4	5		6		7		8			9
	# 10
	function simple_edit ($columnnames, $table, $columns, $page, $datanames, $block_user_id, $htmlentities = 1, $special_input = array(), $order_by = null, $disable_new = 0, $disable_delete = 0, $width = null) {
		function_debug_counter("simple_edit");
		$query = 'SELECT `id`, `'.join('`, `', $columnnames).'` FROM `'.$table.'`';
		if($order_by) {
			$query .= ' ORDER BY `'.join('`, `', $order_by).'`';
		}
		$result = rquery($query);

		$j = 0;
?>
			<table>
				<tr>
<?php
				foreach ($columns as $c) {
?>
					<th<?php if(is_array($width) && array_key_exists($j, $width) && !is_null($width)) {
						print " class='width".$width[$j]."px'";
					} ?>><?php print $c ?></th>
<?php
					$j++;
				}
?>
				<tr>
<?php
		while($row = mysqli_fetch_row($result)) {
?>
			<tr>
				<form class="form" method="post" action="admin.php?page=<?php print htmlentities($GLOBALS['this_page_number']); ?>">
					<input type="hidden" name="update_<?php print $table; ?>" value="1" />
<?php
					$i = 0;
					foreach ($datanames as $c) {
						if(!is_null($special_input) && is_array($special_input) && array_key_exists($i, $special_input)) {
							print $special_input[$i];
						} else {
							if($i == 0) {
?>
								<input type="hidden" value="<?php print htmlentities($row[0]); ?>" name="<?php print htmlentities($datanames[0]); ?>" />
<?php
							} else {
								if($columnnames[$i - 1] == "ausgeschieden") {
									$background_class = 'green';
									$ausgeschieden = 'nein';
									if($row[$i] == 1) {
										$ausgeschieden = 'ja';
										$background_class = 'red';
									}

									$background_class .= "_background";
?>
									<td class="<?php print $background_class; ?>"><?php create_select(array('nein', 'ja'), $ausgeschieden, 'ausgeschieden'); ?></td>
<?php
								} else if($columnnames[$i - 1] == "erste_veranstaltung_default") {
?>
	<td><input type="text" placeholder="erster_termin" name="erster_termin" class="datepicker" value="<?php print ($htmlentities ? htmlentities($row[$i]) : $row[$i]); ?>" /></td>
<?php
								} else if($columnnames[$i - 1] == "typ" && $table == 'semester') {
									//<td><?php create_select(create_semester_type_array(), $row[$i], 'typ'); ? ></td>
?>
									<td><?php print htmlentities($row[$i]); ?></td>
<?php
								} else if($columnnames[$i - 1] == "jahr" && $table == 'semester') {
									//<td><?php create_select(create_semester_type_array(), $row[$i], 'typ'); ? ></td>
?>
									<td><?php print htmlentities($row[$i]); ?></td>
<?php
								} else {
?>
									<td><input class="width500px" type="<?php print $c == 'password' ? 'password' : 'text'; ?>" name="<?php print $c; ?>" placeholder="<?php print $c; ?>" value="<?php print $c == 'password' ? '' : ($htmlentities ? htmlentities($row[$i]) : $row[$i]); ?>" /></td>
<?php
								}
							}
						}
						$i++;
					}
?>
					<td><input type="submit"  value="Speichern" /></td>
<?php
					if($block_user_id && $GLOBALS['logged_in_data'][0] == $row[0]) {
?>
						<td><button name="delete" value="1" disabled>Löschen</button></td>
<?php
					} else {
						if(!$disable_delete) {
?>
							<td><input type="submit" name="delete" value="Löschen" /></td>
<?php
						}
					}
?>
				</form>
			</tr>
<?php
		}

		if(!$disable_new) {
?>
			<tr>
				<form class="form" method="post" action="admin.php?page=<?php print htmlentities($GLOBALS['this_page_number']); ?>">
					<input type="hidden" name="create_<?php print $table; ?>" value="1" />
<?php
					$i = 0;
					foreach ($datanames as $c) {
						if(array_key_exists($i - 1, $columnnames) && $columnnames[$i - 1] == "ausgeschieden") {
?>
							<td>Nein</td>
<?php
						} else {
							if($i != 0) {
	?>
								<td><input type="<?php print $c == 'password' ? 'password' : 'text'; ?>" name="new_<?php print $c; ?>" placeholder="<?php print $c; ?>" /></td>
	<?php
							}
						}
						$i++;
					}
?>
					<td><input type="submit" class="submit" value="Speichern" /></td>
					<td>&mdash;</td>
				</form>
			</tr>
<?php
		}
?>
		</table>
<?php
	}

	function create_select ($data, $chosen, $name, $allow_empty = 0, $noautosubmit = 0, $aria_labelledby = null) {
		function_debug_counter("create_select");
		if(!is_null($aria_labelledby)) {
			$aria_labelledby = 'aria-labelledby="'.htmle($aria_labelledby).'"';
		} else {
			$aria_labelledby = '';
		}
?>
		<select <?php print $aria_labelledby; ?> name="<?php print htmlentities($name); ?>"<?php print ($noautosubmit == 1 ? ' noautosubmit="1"' : ''); ?>>
<?php
			if($allow_empty) {
?>
				<option value="">&mdash;</option>
<?php
			}
			foreach ($data as $datum) {
				if(is_array($datum)) {
?>
					<option value="<?php print $datum[0]; ?>" <?php print ($chosen && $datum[0] == $chosen) ? 'selected' : ''; ?>><?php print htmlentities($datum[1]); ?></option>
<?php
				} else {
?>
					<option value="<?php print $datum; ?>" <?php print ($chosen && $datum == $chosen) ? 'selected' : ''; ?>><?php print htmlentities($datum); ?></option>
<?php
				}
			}
?>
		</select>
<?php
	}

	function create_table_one_dependency ($data, $columnnames, $headlines, $table, $page, $select_name, $dataname, $where = null) {
		function_debug_counter("create_table_one_dependency");
?>
		<table>
			<tr>
<?php
			foreach ($headlines as $datum) {
?>
				<th><?php print $datum; ?></th>
<?php
			}
?>
			</tr>
<?php
		$query = 'SELECT `id`, `'.join('`, `', $columnnames).'` FROM `'.$table.'` ';
		if(isset($where) && $where) {
			$query .= $where;
		}
		$query .= 'ORDER BY `'.join('`, `', $columnnames).'` ';

		$result = rquery($query);
		$bereiche = array();
		if(in_array('bereich_id', $columnnames)) {
			$rquery = 'select id, name from bereich';
			$rresult = rquery($rquery);

			while ($rrow = mysqli_fetch_row($rresult)) {
				$bereiche[] = array($rrow[0], $rrow[1]);
			}
		}
		while ($row = mysqli_fetch_row($result)) {
?>
			<tr>
				<form class="form" method="post" action="admin.php?page=<?php print htmlentities($page) ?>">
					<input type="hidden" name="id" value="<?php print $row[0]; ?>" />
					<td>
						<?php create_select($data, $row[1], $select_name); ?>
					</td>
					<td><input type="text" class="width500px" name="<?php print $dataname; ?>" value="<?php print htmlentities($row[2]); ?>" /></td>
<?php
					if(in_array('studienordnung', $columnnames)) {
?>
						<td><input type="text" class="width500px" name="studienordnung" value="<?php print htmlentities($row[3]); ?>" /></td>
<?php
					}
?>
<?php
					if(in_array('bereich_id', $columnnames)) {
?>
						<td><?php create_select($bereiche, $row[4], 'bereich', 1); ?></td>
<?php
					}

					if(in_array('beschreibung', $columnnames)) {
?>
						<td><input type="text" class="width500px" name="beschreibung" value="<?php print htmlentities($row[3]); ?>" /></td>
<?php
					}

					if(in_array('abkuerzung', $columnnames)) {
?>
						<td><input type="text" class="width500px" name="abkuerzung" value="<?php print htmlentities($row[4]); ?>" /></td>
<?php
					}
?>
					<td><input type="submit" class="submit" value="Speichern" /></td>
					<td><input type="submit" name="delete" value="Löschen" /></td>
				</form>
			</tr>
<?php
		}
?>
			<tr>
				<form class="form" method="post" action="admin.php?page=<?php print htmlentities($page) ?>">
					<td>
<?php create_select($data, NULL, $select_name); ?>
					</td>
					<td><input type="text" placeholder="<?php print $dataname; ?>" name="new_<?php print $dataname; ?>" /></td>
<?php
					if(in_array('studienordnung', $columnnames)) {
?>
						<td><input type="text" class="width500px" name="studienordnung" value="<?php print htmlentities($row[3]); ?>" /></td>
<?php
					}
					if(in_array('bereich_id', $columnnames)) {
?>
						<td><?php create_select($bereiche, null, 'bereich', 1); ?></td>
<?php
					}

					if(in_array('beschreibung', $columnnames)) {
?>
						<td><input type="text" class="width500px" name="beschreibung" value="" /></td>
<?php
					}

					if(in_array('abkuerzung', $columnnames)) {
?>
						<td><input type="text" class="width500px" name="abkuerzung" value="" /></td>
<?php
					}
?>
					<td><input type="submit" class="submit" value="Speichern" /></td>
					<td>&mdash;</td>
				</form>
			</tr>
		</table>
<?php
	}

	function create_modul_html ($veranstaltung_id, $modul, $chosen) {
		function_debug_counter("create_modul_html");
		$query = 'SELECT `modul_id` FROM `veranstaltung_nach_modul` WHERE `veranstaltung_id` = '.esc($veranstaltung_id);
		$result = rquery($query);
?>
		<div class="input_fields_wrap_modul_<?php print $veranstaltung_id; ?>">
			<button class="add_field_button_modul" onclick="veranstaltung_id=<?php print $veranstaltung_id; ?>;"><img src="plus.png" /></button>
<?php
			while ($row = mysqli_fetch_row($result)) {
?>
				<?php create_select($modul, $row[0], 'modul[0]'); ?>
<?php
			}
?>
		</div>
<?php
	}

	function create_studiengang_html ($veranstaltung_id, $studiengaenge) {
		function_debug_counter("create_studiengang_html");
?>
		<div class="input_fields_wrap_studiengang_<?php print $veranstaltung_id; ?>">
			<button class="add_field_button_studiengang"><img src="plus.png" /></button>
			<?php create_select($studiengaenge, $chosen, 'studiengang[0]'); ?>
		</div>
<?php
	}

	function create_modul_html_vvz ($id) {
		function_debug_counter("create_modul_html_vvz");
		if(is_null($id) || !$id) {
			return null;
		}
		$query = 'SELECT `modul_id` FROM `veranstaltung_nach_modul` WHERE `veranstaltung_id` = '.esc($id);
		$result = rquery($query);

		print_ul_li_from_result($result);
	}

	function create_pruefungsmoeglichkeiten_html ($id) {
		function_debug_counter("create_pruefungsmoeglichkeiten_html");
		$query = 'SELECT `pt`.`name`, `p`.`name`, `p`.`pruefungsnummer` FROM `pruefung` `p` LEFT JOIN `pruefungstyp` `pt` ON `pt`.`id` = `p`.`pruefungstyp_id` WHERE `p`.`veranstaltung_id` = '.esc($id);
		$result = rquery($query);

		$i = 0;
		while ($row = mysqli_fetch_row($result)) {
			if($i == 0) {
				print "<ul>";
			}
			print "<li>".$row[0].": ".($row[1] ? "$row[1] ($row[2])" : "$row[2]")."</li>";
			$i++;
		}
		if($i) {
			print "</ul>";
		}
	}

	function create_studiengang_html_vvz ($id) {
		function_debug_counter("create_studiengang_html_vvz");
		$query = 'SELECT `studiengang_id` FROM `veranstaltung_nach_studiengang` WHERE `veranstaltung_id` = '.esc($id);
		$result = rquery($query);

		print_ul_li_from_result($result);
	}

	function print_ul_li_from_result ($result) {
		function_debug_counter("print_ul_li_from_result");
		$i = 0;
		while ($row = mysqli_fetch_row($result)) {
			$name = get_studiengang_name($row[0]);
			if($name) {
				if($i == 0) {
					print "<ul>";
				}
				print "<li>".$name."</li>";
				$i++;
			}
		}
		if($i) {
			print "</ul>";
		}
	}

	function create_nachpruefung_liste ($pruefung_id) {
		function_debug_counter("create_nachpruefung_liste");
		$query = 'SELECT `g`.`abkuerzung`, `r`.`raumnummer`, date_format(`datum`, "%d.%m.%Y") `datum`, `stunde` FROM `nachpruefung` `np` LEFT JOIN `raum` `r` ON `r`.`id` = `np`.`raum_id` LEFT JOIN `gebaeude` `g` ON `g`.`id` = `r`.`gebaeude_id` WHERE `pruefungs_id` = '.esc($pruefung_id);
		$result = rquery($query);

		$i = 0;
		while ($row = mysqli_fetch_row($result)) {
			if($i == 0) {
				print "<ul>";
			}
			if($row[0] && $row[1] || $row[2] && $row[3]) {
				print "<li>";
				if($row[0] && $row[1]) {
					print "$row[0], $row[1]: ";
				}
				if($row[2] && $row[3]) {
					print "$row[2], $row[3]. Stunde";
				}
				print "</li>";
			}
			$i++;
		}

		if($i) {
			print "</ul>";
		}
	}

	function create_pruefungsplan ($veranstaltungen) {
		function_debug_counter("create_pruefungsplan");
		$query = 'SELECT `pt`.`name`,  `p`.`name`, `p`.`pruefungsnummer`, date_format(`p`.`datum`, "%d.%m.%Y"), `p`.`stunde`, `g`.`abkuerzung`, `r`.`raumnummer`, `v`.`titel`, `p`.`id` FROM `pruefung` `p` LEFT JOIN `raum` `r` ON `p`.`raum_id` = `r`.`id` LEFT JOIN `gebaeude` `g` ON `r`.`gebaeude_id` = `g`.`id` LEFT JOIN `pruefungstyp` `pt` ON `p`.`pruefungstyp_id` = `pt`.`id` LEFT JOIN `veranstaltung` `v` ON `v`.`id` = `p`.`veranstaltung_id` WHERE `p`.`veranstaltung_id` IN ('.join(", ", array_map('esc', $veranstaltungen)).')';
		$result = rquery($query);
		if(mysqli_num_rows($result)) {
?>
			<table>
				<tr>
					<th>Datum</th>
					<th>Veranstaltung</th>
					<th>Prüfungstyp</th>
					<th>Name</th>
					<th>Prüfungsnummer</th>
					<th>Gebäude, Raum</th>
					<th>Nachprüfungen</th>
				</tr>
<?php
			while ($row = mysqli_fetch_row($result)) {
?>
				<tr>
					<td><?php print $row[3]; ?></td>
					<td><?php print $row[7]; ?></td>
					<td><?php print $row[0]; ?></td>
					<td><?php print $row[1] ? $row[1] : '&mdash;'; ?></td>
					<td><?php print $row[2]; ?></td>
					<td><?php print $row[5] ? "$row[5], $row[6]" : '&mdash;'; ?></td>
					<td><?php create_nachpruefung_liste($row[8]); ?></td>
				</tr>
<?php
			}
?>
			</table>
<?php
		} else {
?>
			<i class="red_text">Für die gewählten Veranstaltungen konnten keine Prüfungen gefunden werden.</i><br>
<?php
		}
	}

	function my_strip_tags ($str) {
		function_debug_counter("my_strip_tags");
		$str = preg_replace('/<br\s*\/*>/', "\n", $str);
		return strip_tags($str);
	}

	function create_stundenplan ($veranstaltungen, $show_pruefungsleistungen = 1, $show_gebaeudeliste = 1, $bereich = null, $excel = 0, $studiengang_id = null, $dozent = null, $semester = null) {
		function_debug_counter("create_stundenplan");
		$stundenplan = array(
			'Mo' => array('', '', '', '', '', '', '', '', ''),
			'Di' => array('', '', '', '', '', '', '', '', ''),
			'Mi' => array('', '', '', '', '', '', '', '', ''),
			'Do' => array('', '', '', '', '', '', '', '', ''),
			'Fr' => array('', '', '', '', '', '', '', '', '')
			#'Sa' => array('', '', '', '', '', '', '', '', ''),
			#'So' => array('', '', '', '', '', '', '', '', '')
		);

		if(get_get('generate_cookie_stundenplan')) {
			foreach (explode(',', get_cookie('additiver_stundenplan')) as $this_veranstaltung_cookie) {
				if(preg_match('/^\d+$/', $this_veranstaltung_cookie)) {
					$veranstaltungen[] = $this_veranstaltung_cookie;
				}
			}
			$veranstaltungen = array_unique($veranstaltungen);
		}
		$veranstaltungen_liste = $stundenplan;

					#0		1		2			3						4		5		6		7		8		9
		$query = 'SELECT `vm`.`wochentag`, `vm`.`stunde`, `v`.`name`, concat(if(`t`.`abkuerzung` is not null, concat(`t`.`abkuerzung`, " "), ""), `d`.`last_name`, ", ", `d`.`first_name`) as `dozent`, `r`.`id` as `ort`, `vt`.`name`, `vm`.`woche`, `v`.`gebaeude_id`, DATE_FORMAT(`vm`.`erster_termin`, "%d.%m.%Y"), DATE_FORMAT(`vm`.`abgabe_pruefungsleistungen`, "%d.%m.%Y"), `vm`.`veranstaltung_id` FROM `veranstaltung` `v` LEFT JOIN `dozent` `d` ON `d`.`id` = `v`.`dozent_id` LEFT JOIN `raum` `r` ON `v`.`raum_id` = `r`.`id` LEFT JOIN `gebaeude` `g` ON `g`.`id` = `r`.`gebaeude_id` LEFT JOIN `veranstaltungstyp` `vt` ON `v`.`veranstaltungstyp_id` = `vt`.`id` LEFT JOIN `veranstaltung_metadaten` `vm` ON `vm`.`veranstaltung_id` = `v`.`id` LEFT JOIN `titel` `t` ON `t`.`id` = `d`.`titel_id` WHERE 1';
		
		if (is_array($dozent)) {
			$query .= ' AND `d`.`id` = '.esc($dozent);
		} else if($veranstaltungen) {
			$query .= ' AND `v`.`id` IN (';
			$query .= join(", ", array_map('esc', $veranstaltungen));
			if(get_cookie('additiver_stundenplan')) {
				$deserialized = unserialize(get_cookie('additiver_stundenplan'));
				if(count($deserialized)) {
					$query .= ', '.join(", ", array_map('esc', $veranstaltungen));
				}
			}
			$query .= ')';
		}

		$got_veranstaltungen = array();

		if(preg_match('/^\d+$/',$semester)) {
			$query .= ' AND `semester_id` = '.esc($semester);
		}

		$result = rquery($query);

		$number_of_veranstaltungen = mysqli_num_rows($result);

		$str = '';

		$benutzte_raeume = array();
		$benutzte_gebaeude = array();

		$gebaeudeliste_gezeigt = 0;
		$pruefungsliste_gezeigt = 0;

		if(mysqli_num_rows($result)) {
			$collisions_reported = 0;
			while ($row = mysqli_fetch_row($result)) {
				$got_veranstaltungen[] = $row[10];
				$wochentag = $row[0];
				$stunde = $row[1];
				$name = htmlentities($row[2]);
				$dozent = $row[3];
				$ort = get_raum_gebaeude_by_id($row[4]);

				if(!$ort && $row[7]) {
					$ort = get_gebaeude_abkuerzung($row[7], 1);
				}

				if($row[4]) {
					$benutzte_raeume[] = $row[4];
				} else if ($row[7]) {
					$benutzte_gebaeude[] = $row[7];
				}

				$veranstaltungstyp = $row[5];
				$woche = $row[6];

				$eintrag = '';
				if($woche == 'keine Angabe') {
					$eintrag = '<i>Blockveranstaltung</i><br />';
				} else if($woche != 'jede Woche') {
					$eintrag = ucwords($woche).":<br />\n";
				}

				$eintrag .= "<b>$name</b><br /><i>".htmlentities($row[3])."</i><br />$ort<br /><i>".htmlentities($veranstaltungstyp)."</i>";
				$has_collisions = 0;
				if(array_key_exists($wochentag, $stundenplan) && array_key_exists($stunde, $stundenplan[$wochentag]) && strlen($stundenplan[$wochentag][$stunde])) {
					$andere_veranstaltung_gerade_ungerade = $veranstaltungen_liste[$wochentag][$stunde][1];
					$kollision = '';
					if($andere_veranstaltung_gerade_ungerade == $woche || $woche == 'jede Woche' || $andere_veranstaltung_gerade_ungerade == 'jede Woche') {
						$kollision = "<span class='collision_text'>!!! kollidiert mit !!!</span><br />";
						$has_collisions = 1;
					}
					$eintrag .= "<br />".$kollision.$stundenplan[$wochentag][$stunde];
				}

				if(isset($row[8])) {
					$eintrag .= '<br /><span class="tiny_text">Erster&nbsp;Termin:&nbsp;'.htmle($row[8])."</span>";
				}

				if(isset($row[9]) && !preg_match('/0000$/', $row[9])) {
					$eintrag .= '<br /><span class="tiny_text">Abgabe Prüfungsleistungen:&nbsp;'.htmle($row[9])."</span>";
				}

				if($has_collisions && !$collisions_reported) {
					$eintrag .= "<br /><br /><i class='class_red'>Bitte gehen Sie eine Seite zurück<br /> und wählen Sie aus dieser<br /> Veranstaltungsliste die gewünschten<br /> Veranstaltungen aus.</i>";

					$collisions_reported = 1;
				}

				if(preg_match('/^(\d+)-(\d+)$/', $stunde, $founds)) {
					foreach (range($founds[1], $founds[2]) as $this_hour) {
						$stundenplan[$wochentag][$this_hour] = $eintrag;
					}
				} else {
					$stundenplan[$wochentag][$stunde] = $eintrag;
				}
				$veranstaltungen_liste[$wochentag][$stunde] = array($name, $row[6]);
			}

			$stunden = array(
				1 => "1. DS (07:30 &mdash; 09:00)",
				2 => "2. DS (09:20 &mdash; 10:50)",
				3 => "3. DS (11:10 &mdash; 12:40)",
				4 => "4. DS (13:00 &mdash; 14:30)",
				5 => "5. DS (14:50 &mdash; 16:20)",
				6 => "6. DS (16:40 &mdash; 18:10)",
				7 => "7. DS (18:30 &mdash; 20:00)",
				8 => "8. DS (20:20 &mdash; 21:50)"
			);

			$str .= '<div class="autocenter_large">'."\n";

			$query = $_GET;
			$query_result = http_build_query($query);

			$str .= '<form method="get" enctype="multipart/form-data">'."\n";
			foreach ($_GET as $key => $value) {
				if(is_array($value)) {
					foreach ($value as $this_value) {
						$str .= "<input type='hidden' name='".htmlentities($key)."[]' value='".htmlentities($this_value)."'>\n";
					}
				} else {
					$str .= "<input type='hidden' name='".htmlentities($key)."' value='".htmlentities($value)."'>\n";
				}
			}
			$str .= "<table class='stundenplan'>\n";
			$str .= "<thead>\n";
			$str .= "<tr>\n";
			$str .= "<th class='text_align_center background_black'>&mdash;</th>\n";
			foreach ($stundenplan as $tag => $this_stunden) {
				$str .= "<th class='text_align_center'>".$tag."</th>\n";
			}
			$str .= "</tr>\n";
			$str .= "</thead>\n";
			$str .= "<tbody>\n";
			foreach ($stunden as $stunde_key => $stunde_value) {
				$str .= "<tr>\n";
				$str .= '<td class="background_color_00305e stunde_und_zeit">'.$stunde_value."</td>\n";
				foreach ($stundenplan as $tag => $this_stunden) {
					$id = urlencode("$tag-$stunde_value");
					$id = preg_replace("/mdash;/", '', $id);
					$id = preg_replace("/[^A-Za-z0-9 ]/", '', $id);
					$veranstaltung_text = $stundenplan[$tag][$stunde_key];
					$alt_text = get_get('alternative_text_veranstaltung_'.$id);
					if($alt_text && strlen($alt_text) >= 2) {
						$alt_text = htmlentities($alt_text);
						$alt_text = preg_replace("/\n/", "<br />", $alt_text);
						$veranstaltung_text = $alt_text;
					}
					$str .= '<td class="stundenplan_td"><div class="display_none" id="edit_veranstaltung_'.$id.'_div"><textarea class="autoExpand display_none" id="edit_veranstaltung_'.$id.'">'.my_strip_tags($veranstaltung_text).'</textarea></div><span id="original_veranstaltung_text_'.$id.'">'.$veranstaltung_text."</span><a id='click_to_edit_veranstaltung_$id'>&#x270D;</a></td>\n";
				}
				$str .= "</tr>\n";
			}
			$str .= "<tbody>\n";
			$str .= '<input type="hidden" value="'.htmlentities(get_get('studiengang')).'" name="studiengang">';
			$str .= "</table><input id='submit_button_aenderungen' class='display_none' type='submit' value='&Auml;nderungen speichern'></form>\n";

			$datum = date("d.m.Y");
			$uhrzeit = date("H:i");
			$str .= "<p><i>Stand: $datum ($uhrzeit)</i></p>";

			if($number_of_veranstaltungen) {
				$str .= '<a href="event_file.php?veranstaltung[]='.join('&veranstaltung[]=', $got_veranstaltungen).'">'.html_calendar().'In meinen Kalendar eintragen</a>'."\n";
			}
			#$str .= '<a id="create_tinyurl">TinyURL-Link dieses Stundenplanes generieren</a><br><div id="created_tinyurl"></div>';

			if($show_gebaeudeliste && (count($benutzte_raeume) || count($benutzte_gebaeude))) {
				$benutzte_raeume = array_unique($benutzte_raeume);
				$str .= "<h2>Gebäude</h2>\n";
				$str .= "<table class='width_100_whitespace_nowrap'>\n";
				$str .= "<thead>\n";
				$str .= "<tr>\n";
				$str .= "\t<th>Gebäudeabkürzung</th>\n";
				$str .= "\t<th>Gebäudename</th>\n";
				$str .= "</tr>\n";
				$str .= "</thead>\n";
				$str .= "<tbody>\n";

				$shown_buildings = array();
				$gebaeudeliste_gezeigt = 1;

				foreach ($benutzte_raeume as $this_benutzter_raum) {
					$ar = get_gebaeude_abkuerzung_name_by_raum_id($this_benutzter_raum);
					if(!in_array($ar[0], $shown_buildings)) {
						$str .= "\t<tr>\n";
						$str .= "\t\t".'<td class="stundenplan_td">'.htmle($ar[0])."</td>\n";
						$str .= "\t\t".'<td class="stundenplan_td">'.htmle($ar[1])."</td>\n";
						$str .= "\t</tr>\n";
						$shown_buildings[] = $ar[0];
					}
				}
				foreach ($benutzte_gebaeude as $this_benutzter_raum) {
					$ar = get_gebaeude_name_abkuerzung($this_benutzter_raum);
					if(!in_array($ar[0], $shown_buildings)) {
						$str .= "\t<tr>\n";
						$str .= "\t\t".'<td class="stundenplan_td">'.htmle($ar[0])."</td>\n";
						$str .= "\t\t".'<td class="stundenplan_td">'.htmle($ar[1])."</td>\n";
						$str .= "\t</tr>\n";
						$shown_buildings[] = $ar[0];
					}
				}
				$str .= "<tbody>\n";
				$str .= "</table>\n";
			}

			if($show_pruefungsleistungen && count($veranstaltungen)) {
				$pruefungsliste_gezeigt = 1;
				$veranstaltungen = array_unique($veranstaltungen);
				$query = 'select pruefungsnummer, name, modul_name, veranstaltung_id, DATE_FORMAT(abgabe_pruefungsleistungen, "%d.%m.%Y"), bereich, dozent_first_name, dozent_last_name from view_pruefungsdaten where veranstaltung_id in (';
				$query .= join(", ", array_map('esc', $veranstaltungen));
				$query .= ')';
				if($bereich) {
					#$query .= ' AND `bereich_id` = '.esc($bereich);
				}
				if(get_get('modul')) {
					$query .= ' AND modul_id IN ('.esc(get_get('modul')).')';
				}
				if($studiengang_id) {
					$query .= ' AND `studiengang_id` IN ('.esc($studiengang_id).')';
				}
				$query .= ' order by veranstaltung_id asc';
				if(get_get('modul')) {
					$query .= ', modul_name asc';
				}
				$query .= ', dozent_first_name asc, dozent_last_name asc, name asc, pruefungsnummer asc';
				$result = rquery($query);

				if(mysqli_num_rows($result)) {
					$str .= "<h2>Mögliche Prüfungsleistungen</h2>\n";
					$str .= "<table class='width_100_whitespace_nowrap'>\n";
					$str .= "<thead>\n";
					$str .= "<tr>\n";
					$str .= "\t<th>Prüfungsnummer</th>\n";
					$str .= "\t<th>Prüfungstyp</th>\n";
					$str .= "\t<th>Bereich</th>\n";
					$str .= "</tr>\n";
					$str .= "</thead>\n";
					$str .= "<tbody>\n";
				}
				$modul_name = '';
				$veranstaltung_name = '';
				$c = 0;
				$shown_pruefungen = array();
				while ($row = mysqli_fetch_row($result)) {
					$shown_pruefungen[] = $row[0];
					if($row[4] == '00.00.0000') {
						$row[4] = null;
					}

					if($veranstaltung_name != $row[3]) {
						$v_name = get_veranstaltungsname_by_id($row[3]);
						$str .= "\t<tr>\n";
						$str .= "\t\t<th class='width_100_break_word background_color_003377_color_white' colspan='3'>Veranstaltung: &raquo;<i>".htmlentities($v_name)."</i>&laquo;".(strlen($row[4]) ? ' (Abgabe der Prüfungsleistungen bis zum bzw. am '.$row[4].')' : '').(strlen($row[6].$row[7]) ? htmlentities(" [Dozent: $row[6] $row[7]]") : '').":</th>\n";
						$str .= "\t</tr>\n";

						$veranstaltung_name = $row[3];
						$modul_name = '';
					}

					if($modul_name != $row[2]) {
						$str .= "\t<tr>\n";
						$str .= "\t\t<th class='background_color_225599_color_white' colspan='3'>Modul: <i>".htmlentities($row[2])."</i></th>\n";
						$str .= "\t</tr>\n";

						$modul_name = $row[2];
					}

					$str .= "\t<tr>\n";
					$str .= "\t\t".'<td class="stundenplan_td">'.htmlentities($row[0]).pruefung_symbole($row[0])."</td>\n";
					$str .= "\t\t".'<td class="stundenplan_td colorhashme">'.htmlentities($row[1])."</td>\n";
					$str .= "\t\t".'<td class="stundenplan_td">'.htmlentities($row[5])."</td>\n";
					$str .= "\t</tr>\n";
					$c++;
				}
				$str .= "<tbody>\n";
				$str .= "</table>\n";
				$str .= "</div>\n";
			}

			//$geplante_pruefungen_ohne_veranstaltungen = array_diff($shown_pruefungen, $GLOBALS['pruefungen_already_chosen']);
			$geplante_pruefungen_ohne_veranstaltungen = array_diff($GLOBALS['pruefungen_already_chosen'], $shown_pruefungen);
			if(count($geplante_pruefungen_ohne_veranstaltungen) != 0) {
				$str .= "<table>\n";
				$str .= "<tr>\n";
				$str .= "<th colspan='4'>Eingeplante Prüfungsnummern ohne dazugehörige Veranstaltung</th>\n";
				$str .= "</tr>\n";
				$str .= "<tr>\n";
				$str .= "<th>Prüfungsnummer</th>\n";
				$str .= "<th>Prüfungstyp</th>\n";
				$str .= "<th>Modul</th>\n";
				$str .= "<th>Bereich</th>\n";
				$str .= "</tr>\n";
				foreach ($geplante_pruefungen_ohne_veranstaltungen as $this_pruefung_ohne_veranstaltung) {
					$str .= "<tr>\n";
					$str .= "<td>".htmlentities($this_pruefung_ohne_veranstaltung).pruefung_symbole($this_pruefung_ohne_veranstaltung)."</td>\n";
					$str .= "<td>".get_pruefungstyp_name(get_pruefungstyp_id_from_pruefungsnummer($this_pruefung_ohne_veranstaltung))."</td>\n";
					$str .= "<td>".get_modul_name(get_pruefungsnummer_modul_by_pruefungsnummer_id($this_pruefung_ohne_veranstaltung))."</td>\n";
					$str .= "<td>".get_bereich_name_by_id(get_pruefungsnummer_bereich_by_pruefungsnummer_id($this_pruefung_ohne_veranstaltung))."</td>\n";
					$str .= "</tr>\n";
				}
				$str .= "</table>\n";
			}
		} else {
			$str .= '<i class="red_text">Es ist ein Fehler aufgetreten: die gewählten Veranstaltungen scheinen nicht zu existieren.</i><br>'."\n";
		}

		return array($str, $gebaeudeliste_gezeigt, $pruefungsliste_gezeigt);
	}

	function comma_list_to_array ($str) {
		function_debug_counter("comma_list_to_array");
		$array = array();

		$str = preg_replace('/^,+/', '', $str);
		$str = preg_replace('/,+$/', '', $str);
		$str = preg_replace('/\s+,\s+$/', ',', $str);
		$array = explode(",", $str);

		return $array;
	}

	/* Datenfunktionen */

	function create_page_info_parent ($parent, $user_role_id_data = null) {
		function_debug_counter("create_page_info_parent");
		$page_infos = array();
		$query = 'SELECT `p`.`id`, `p`.`name`, `p`.`file`, `pi`.`info`, `p`.`parent` FROM `page` `p` LEFT JOIN `page_info` `pi` ON `pi`.`page_id` = `p`.`id` WHERE `p`.`show_in_navigation` = "1" AND `parent` = '.esc($parent);
		if(isset($user_role_id_data)) {
			$query .= ' AND `p`.`id` IN (SELECT `page_id` FROM `role_to_page` WHERE `role_id` = '.esc($user_role_id_data).')';
		}
		$query .= ' ORDER BY p.name';
		$result = rquery($query);
		while ($row = mysqli_fetch_row($result)) {
			$page_infos[$row[0]] = array($row[0], $row[1], $row[2], $row[3], $row[4]);
		}
		return $page_infos;
	}

	function table_has_mergeable_structure ($table) {
		function_debug_counter("table_has_mergeable_structure");
		if(preg_match('/^view_/', $table)) {
			return 0;
		}
		$query1 = 'SHOW COLUMNS FROM '.$table;
		$result1 = rquery($query1);

		$has_mergeable_structure = 1;
		while ($row1 = mysqli_fetch_row($result1)) {
			if($row1[0] == 'id' || $row1[0] == 'name' || $row1[0] == 'abkuerzung' || $row1[0] == 'studiengang_id') {
				# OK
			} else {
				$has_mergeable_structure = 0;
			}
		}

		return $has_mergeable_structure;
	}

	function get_father_page ($id) {
		function_debug_counter("get_father_page");
		$query = 'SELECT `parent` FROM `page` WHERE `id` = '.esc($id);
		$result = rquery($query);

		if(mysqli_num_rows($result)) {
			$father = null;
			while ($row = mysqli_fetch_row($result)) {
				$father = $row[0];
			}
			return $father;
		} else {
			return null;
		}
	}

	function create_page_info () {
		function_debug_counter("create_page_info");
		$page_infos = array();
		$query = 'select p.id, p.name, p.file, pi.info, p.parent from page p left join page_info pi on pi.page_id = p.id where p.show_in_navigation = "1" ORDER BY p.name';
		$result = rquery($query);
		while ($row = mysqli_fetch_row($result)) {
			$page_infos[$row[0]] = array($row[0], $row[1], $row[2], $row[3], $row[4]);
		}
		return $page_infos;
	}

	function create_studiengaenge_mit_veranstaltungen_array ($semester = null, $institut = null) {
		function_debug_counter("create_studiengaenge_mit_veranstaltungen_array");
		$studiengaenge = array();
		$query = 'SELECT `s`.`id`, `s`.`name` FROM `studiengang` `s` JOIN `view_veranstaltung_nach_studiengang` `vs` ON `s`.`id` = `vs`.`studiengang_id` JOIN `veranstaltung` `v` ON `v`.`id` = `vs`.`veranstaltung_id` WHERE 1 ';
		if(isset($semester) && !is_array($semester)) {
			$query .= ' AND `v`.`semester_id` = '.esc($semester);
		}

		if(isset($institut) && !is_array($institut)) {
			$query .= ' AND `v`.`institut_id` = '.esc($institut);
		}

		$query .= ' GROUP BY `id` ORDER BY `s`.`name` ASC';

		$result = rquery($query);
		while ($row = mysqli_fetch_row($result)) {
			$studiengaenge[$row[0]] = array($row[0], $row[1]);
		}
		return $studiengaenge;
	}

	function create_zeitraum_array () {
		function_debug_counter("create_zeitraum_array");
		$studiengaenge = array();
		$query = 'SELECT `id`, `name` FROM `pruefung_zeitraum`';
		$result = rquery($query);
		while ($row = mysqli_fetch_row($result)) {
			$studiengaenge[$row[0]] = array($row[0], "$row[1]");
		}
		return $studiengaenge;
	}

	function create_studiengaenge_array ($institut_id = null) {
		function_debug_counter("create_studiengaenge_array");
		$studiengaenge = array();
		$query = 'SELECT `id`, `name` FROM `studiengang`';
		if($institut_id) {
			$query .= ' WHERE `institut_id` = '.esc($institut_id);
		}
		$result = rquery($query);
		while ($row = mysqli_fetch_row($result)) {
			$studiengaenge[$row[0]] = array($row[0], "$row[1]");
		}
		return $studiengaenge;
	}

	function create_semester_array_short () {
		function_debug_counter("create_semester_array_short");
		$semester = array();
		$query = 'SELECT `id`, concat(`typ`, " ", `jahr`) FROM `semester` ORDER BY `jahr`, `typ`';
		$result = rquery($query);
		while ($row = mysqli_fetch_row($result)) {
			$data = $row[1];
			$data = preg_replace('/Wintersemester/', 'WS', $data);
			$data = preg_replace('/Sommersemester/', 'SS', $data);
			$data = preg_replace('/\s+\d\d/', '', $data);
			$semester[$row[0]] = array($row[0], $data);
		}
		return $semester;
	}

	function create_semester_array ($mit_veranstaltungen = 0, $split_typen = 0, $id_in = null) {
		function_debug_counter("create_semester_array");
		$semester = array();
		$query = 'SELECT `id`, `typ`, `jahr` FROM `semester` WHERE 1 AND ';

		$added_to_query = 0;

		if(is_array($id_in)) {
			$id_in = array_filter($id_in);
		}

		if(is_array($id_in) && count($id_in) || $mit_veranstaltungen) {
			$query .= '(0';
			$added_to_query = 1;
		}

		if($mit_veranstaltungen) {
			$query .= ' OR (`id` IN (SELECT `semester_id` FROM `veranstaltung` GROUP BY `semester_id`))';
			$added_to_query = 1;
		}

		if(is_array($id_in) && count($id_in)) {
			$query .= ' OR (`id` IN ('.multiple_esc_join($id_in).'))';
			$added_to_query = 1;
		}

		if(is_array($id_in) && count($id_in) || $mit_veranstaltungen) {
			$query .= ')';
			$added_to_query = 1;
		}

		if(!$added_to_query) {
			$query .= '1';
		}

		$query .= ' ORDER BY `jahr`, `typ`';
		$result = rquery($query);
		while ($row = mysqli_fetch_row($result)) {
			if($row[1] == 'Wintersemester') {
				$this_year = $row[2];
				$next_year = $this_year + 1;
				$row[2] = "$this_year/$next_year";
			}
			if($split_typen) {
				$semester[$row[0]] = array($row[0], $row[1], $row[2]);
			} else {
				$semester[$row[0]] = array($row[0], "$row[1] $row[2]");
			}
		}
		return $semester;
	}

	function create_pruefungsamt_array() {
		function_debug_counter("create_pruefungsamt_array");
		$pruefungsamt = array();
		$query = 'SELECT `id`, `name` FROM `pruefungsamt`';
		$result = rquery($query);
		while ($row = mysqli_fetch_row($result)) {
			$pruefungsamt[$row[0]] = array($row[0], "$row[1]");
		}
		return $pruefungsamt;
	}

	function create_institute_array () {
		function_debug_counter("create_institute_array");
		$institute = array();
		$query = 'SELECT `id`, `name` FROM `institut`';
		$result = rquery($query);
		while ($row = mysqli_fetch_row($result)) {
			$institute[$row[0]] = array($row[0], "$row[1]");
		}
		return $institute;
	}

	function create_raum_array () {
		function_debug_counter("create_raum_array");
		$raum = array();

		$gebaeude = create_gebaeude_abkuerzungen_array();

		$query = 'SELECT `id`, `gebaeude_id`, `raumnummer` FROM `raum`';
		$result = rquery($query);
		while ($row = mysqli_fetch_row($result)) {
			$raum[] = array($row[0], $gebaeude[$row[1]][1]." ".$row[2]);
		}
		return $raum;
	}

	function create_dozenten_first_last_name_array () {
		function_debug_counter("create_dozenten_first_last_name_array");
		$dozenten = array();
		$query = 'SELECT `last_name`, `first_name` FROM `dozent`';
		$result = rquery($query);
		while ($row = mysqli_fetch_row($result)) {
			$dozenten[] = array($row[0], $row[1]);
		}
		return $dozenten;
	}

	function create_dozenten_by_ids_array ($ids) {
		function_debug_counter("create_dozenten_by_ids_array");
		$dozenten = array();
		$query = 'SELECT `id`, concat(`last_name`, ", ", `first_name`) FROM `dozent` WHERE `id` IN ('.multiple_esc_join($ids).')';
		$result = rquery($query);
		while ($row = mysqli_fetch_row($result)) {
			$dozenten[$row[0]] = array($row[0], $row[1]);
		}
		return $dozenten;
	}

	function create_dozenten_array ($show_ausgeschieden = 0) {
		function_debug_counter("create_dozenten_array");
		$dozenten = array();
		$query = 'SELECT `id`, concat(`last_name`, ", ", `first_name`) FROM `dozent` `d` WHERE `d`.`ausgeschieden` = ';
		if($show_ausgeschieden) {
			$query .= '"0" or `d`.`ausgeschieden` = "1"';
		} else {
			$query .= '"0"';
		}
		$query .= ' ORDER BY `d`.`last_name` asc, `d`.`first_name` asc';
		$result = rquery($query);
		while ($row = mysqli_fetch_row($result)) {
			$dozenten[$row[0]] = array($row[0], $row[1]);
		}
		return $dozenten;
	}

	function create_veranstaltungstyp_abkuerzung_array () {
		function_debug_counter("create_veranstaltungstyp_abkuerzung_array");
		$veranstaltungstyp = array();
		$query = 'SELECT `id`, `abkuerzung` FROM `veranstaltungstyp`';
		$result = rquery($query);
		while ($row = mysqli_fetch_row($result)) {
			$veranstaltungstyp[$row[0]] = array($row[0], "$row[1]");
		}
		return $veranstaltungstyp;
	}

	function create_veranstaltungstyp_abkuerzung_namen_array () {
		function_debug_counter("create_veranstaltungstyp_abkuerzung_namen_array");
		$veranstaltungstyp = array();
		$query = 'SELECT `name`, `abkuerzung` FROM `veranstaltungstyp`';
		$result = rquery($query);
		while ($row = mysqli_fetch_row($result)) {
			$veranstaltungstyp[] = array($row[0], $row[1]);
		}
		return $veranstaltungstyp;
	}

	function create_veranstaltungstyp_name_array () {
		function_debug_counter("create_veranstaltungstyp_name_array");
		$veranstaltungstyp = array();
		$query = 'SELECT `id`, `name` FROM `veranstaltungstyp`';
		$result = rquery($query);
		while ($row = mysqli_fetch_row($result)) {
			$veranstaltungstyp[$row[0]] = array($row[0], $row[1]);
		}
		return $veranstaltungstyp;
	}

	function create_veranstaltungstyp_array () {
		function_debug_counter("create_veranstaltungstyp_array");
		$veranstaltungstyp = array();
		$query = 'SELECT `id`, `name`, `abkuerzung` FROM `veranstaltungstyp`';
		$result = rquery($query);
		while ($row = mysqli_fetch_row($result)) {
			$veranstaltungstyp[$row[0]] = array($row[0], "$row[2] ($row[1])");
		}
		return $veranstaltungstyp;
	}

	function create_stunden_array () {
		function_debug_counter("create_stunden_array");
		$data = enum_to_array($GLOBALS['dbname'], 'veranstaltung_metadaten', 'stunde');
		return $data;
	}

	function get_vvz_start_message ($institut_id) {
		function_debug_counter("get_vvz_start_message");
		if(is_null($institut_id)) {
			$institut_id = 0;
		}
		$query = 'SELECT `message` FROM `'.$GLOBALS['dbname'].'`.`start_message` WHERE `institut_id` = '.esc($institut_id);
		$result = rquery($query);

		$rr = null;
		while ($row = mysqli_fetch_row($result)) {
			if(isset($row[0])) {
				$rr = $row[0];
			}
		}

		return $rr;
	}

	function enum_to_array($database, $table, $field) {    
		function_debug_counter("enum_to_array");
		$query = "SHOW FIELDS FROM `{$database}`.`{$table}` LIKE '{$field}'";
		$result = rquery($query);
		$enum = NULL;
		while ($row = mysqli_fetch_row($result)) {
			preg_match('#^enum\((.*?)\)$#ism', $row[1], $matches);
			$enum = str_getcsv($matches[1], ",", "'");
		}
		return $enum;
	}

	function create_wann_array () {
		function_debug_counter("create_wann_array");
		return array("jede Woche", "gerade Woche", "ungerade Woche", 'keine Angabe');
	}

	function create_wochentag_array ($all = 0) {
		function_debug_counter("create_wochentag_array");
		if($all) {
			return array('Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So', 'BS');
		} else {
			return array('Mo', 'Di', 'Mi', 'Do', 'Fr', 'BS');
		}
	}

	function create_wochentag_abk_nach_name_array () {
		function_debug_counter("create_wochentag_abk_nach_name_array");
		return array(
			'Mo' => 'Montag',
			'Di' => 'Dienstag',
			'Mi' => 'Mittwoch',
			'Do' => 'Donnerstag',
			'Fr' => 'Freitag',
			'Sa' => 'Samstag',
			'So' => 'Sonntag',
			'BS' => 'Blockseminar'
		);
	}

	function create_gebaeude_abkuerzungen_array() {
		function_debug_counter("create_gebaeude_abkuerzungen_array");
		$gebaeude = array();
		$query = 'SELECT `id`, `abkuerzung` FROM `gebaeude`';
		$result = rquery($query);
		while ($row = mysqli_fetch_row($result)) {
			$gebaeude[$row[0]] = array($row[0], $row[1]);
		}
		return $gebaeude;
	}

	function veranstaltung_has_language ($v_id, $l_id) {
		function_debug_counter("veranstaltung_has_language");
		$query = 'select count(*) from veranstaltung_to_language where veranstaltung_id = '.esc($v_id).' and language_id = '.esc($l_id);;
		$res = get_single_row_from_query($query);
		return !!$res;
	}

	function create_language_array () {
		function_debug_counter("create_language_array");
		$query = 'SELECT `id`, `name` FROM `language`';
		$result = rquery($query);
		$languages = array();
		while ($row = mysqli_fetch_row($result)) {
			$languages[$row[0]] = array($row[0], $row[1]);
		}
		$GLOBALS['create_language_array_cache'] = $languages;
		return $languages;	
	}

	function create_studiengang_array_by_institut_id_str ($institut_id = null, $studiengaenge = array()) {
		function_debug_counter("create_studiengang_array_by_institut_id_str");
		$query = 'SELECT `id`, `name` FROM `studiengang`';
		if(!is_null($institut_id) && $institut_id) {
			$query .= ' WHERE `institut_id` = '.esc($institut_id);
		}
		$result = rquery($query);
		while ($row = mysqli_fetch_row($result)) {
			$studiengaenge[$row[0]] = array($row[0], $row[1]);
		}
		return $studiengaenge;	
	}

	function create_studiengang_array_by_institut_id ($institut_id = null) {
		function_debug_counter("create_studiengang_array_by_institut_id");
		return create_studiengang_array_by_institut_id_str($institut_id, array("alle" => "Alle Studiengänge"));
	}

	function create_pruefungsnummern_array () {
		function_debug_counter("create_pruefungsnummern_array");
		$pruefungsnummern = array();
		$query = 'SELECT `p`.`id`, `p`.`pruefungsnummer`, `p`.`pruefungstyp_id`, `pt`.`name` AS `pruefungstyp_name` FROM `pruefungsnummer` `p` LEFT JOIN `pruefungstyp` `pt` ON `pt`.`id` = `p`.`pruefungstyp_id`';
		if(strlen($modul_id)) {
			$query .= ' WHERE `modul_id` = '.esc($modul_id);
		}
		$result = rquery($query);
		while ($row = mysqli_fetch_row($result)) {
			$pruefungsnummern[$row[0]] = array($row[0], "$row[1] ($row[3])", $row[2]);
		}
		return $pruefungsnummern;	
	}

	function html_calendar ($first = 0) {
		function_debug_counter("html_calendar");
		$first = $GLOBALS['shown_help_ids']['calendar'];
		if($first) {
			$GLOBALS['shown_help_ids']['calendar'] = 1;
			return '<span class="calendarlarge" id="ical_item" title="iCal-Kalendardatei">&#128467;</span>';
		} else {
			return '<span class="calendarlarge" title="iCal-Kalendardatei">&#128467;</span>';
		}
	}

	function html_map ($lat, $lon, $geb, $first = 0, $die = 0) {
		function_debug_counter("html_map");
		$first = $GLOBALS['shown_help_ids']['google_maps_icon'];
		if($geb) {
			$array = get_gebaeude_geo_coords_by_id($geb);
			$lat = $array[0];
			$lon = $array[1];
		}
		if($lat && $lon) {
			$url = "https://www.openstreetmap.org/?mlat=$lat&mlon=$lon&zoom=18'";
			if($first) {
				$GLOBALS['shown_help_ids']['google_maps_icon'] = 1;
				#return "<a class='calendarlarge' id='google_maps_icon' href='http://maps.google.com/maps?q=$lat,$lon'>&#128506;</a>";
				return "<a class='calendarlarge' id='google_maps_icon' href='$url>&#128506;</a>";
			} else {
				return "<a class='calendarlarge' href='$url'>&#128506;</a>";
			}
		} else {
			return '';
		}
	}

	function html_checked () {
		function_debug_counter("html_checked");
		return '<span title="Erledigte Prüfung" class="green_large">&#10003;</span>';
	}

	function html_chosen () {
		function_debug_counter("html_chosen");
		return '<span title="Geplante Prüfung" class="blue_large">&#x1f58a;</span>';
	}

	function create_pruefungsnummern_array_by_modul_id ($modul_id = '', $show_disabled = 0) {
		function_debug_counter("create_pruefungsnummern_array_by_modul_id");
		$pruefungsnummern = array();
		$query = '
SELECT 
	`p`.`id`, 
	`p`.`pruefungsnummer`, 
	`p`.`pruefungstyp_id`, 
	`pt`.`name`, 
	`modul_id` AS `pruefungstyp_name`, 
	`b`.`name` AS `bereich_name`, 
	`f`.`name` as `fach_name`, 
	modulbezeichnung 
FROM 
	`pruefungsnummer` `p` 
LEFT JOIN 
	`pruefungstyp` `pt`
ON 
	`pt`.`id` = `p`.`pruefungstyp_id` 
LEFT JOIN 
	`bereich` `b` ON `b`.`id` = `p`.`bereich_id` 
LEFT JOIN 
	`pruefungsnummer_fach` `f` ON `f`.`id` = `p`.`pruefungsnummer_fach_id`
WHERE 1
';
		if(!$show_disabled) {
			$query .= ' AND `p`.`disabled` = "0" ';
		}
		if(is_array($modul_id) && count($modul_id)) {
			$query .= ' AND `modul_id` IN ('.join(', ', array_map('esc', $modul_id)).') ';
		} else if(!is_array($modul_id) && strlen($modul_id)) {
			$query .= ' AND `modul_id` = '.esc($modul_id).' ';
		}

		$query .= 'ORDER BY `fach_name` ASC, `bereich_name` ASC, `pruefungstyp_name` ASC, `p`.`id` ASC';

		$result = rquery($query);
		while ($row = mysqli_fetch_row($result)) {
			$name = $row[3];
			if(isset($row[1]) && strlen($row[1])) {
				$name .= " ($row[1])";
			}
			$this_array = array($row[0], $name, $row[2], $row[5], $row[6], $row[7], $row[1]);
			if(is_array($modul_id)) {
				$pruefungsnummern[$row[4]][$row[0]] = $this_array;
			} else {
				$pruefungsnummern[$row[0]] = $this_array;
			}
		}
		return $pruefungsnummern;
	}

	function create_module_array_by_studiengang_and_semester ($studiengang_id, $semester = NULL) {
		function_debug_counter("create_module_array_by_studiengang_and_semester");
		$module = array();
		$query = 'SELECT `id`, `name`, `studiengang_id` FROM `modul` WHERE 1';
		if(!is_null($semester)) {
			$query .= ' AND `id` IN (SELECT `modul_id` FROM `modul_nach_semester` WHERE `semester` = '.esc($semester).')';
		}

		if(is_array($studiengang_id)) {
			$query .= ' AND `studiengang_id` IN ('.join(', ', array_map('esc', $studiengang_id)).')';
		} else {
			$query .= ' AND `studiengang_id` = '.esc($studiengang_id);
		}
		$result = rquery($query);
		while ($row = mysqli_fetch_row($result)) {
			if(is_array($studiengang_id)) {
				$module[$row[2]][$row[0]] = array($row[0], $row[1]);
			} else {
				$module[$row[0]] = array($row[0], $row[1]);
			}
		}
		return $module;	
	}

	function create_module_array_by_studiengang ($studiengang_id) {
		function_debug_counter("create_module_array_by_studiengang");
		return create_module_array_by_studiengang_and_semester($studiengang_id, null);
	}

	function create_modul_studiengang_array ($shorten = 70, $institut_id = null) {
		function_debug_counter("create_modul_studiengang_array");
		$modul = array();
		$query = 'SELECT `m`.`id`, concat(`s`.`name`, " | ", `m`.`name`) as `name` FROM `modul` `m` LEFT JOIN `studiengang` `s` ON `s`.`id` = `m`.`studiengang_id`';
		if($institut_id) {
			$query .= ' WHERE `s`.`institut_id` = '.esc($institut_id);
		}
		$result = rquery($query);
		while ($row = mysqli_fetch_row($result)) {
			if($shorten) {
				if(($shorten + 3) <= strlen($row[1])) {
					$row[1] = substr($row[1], 0, $shorten).'...';
				}
			}
			$modul[$row[0]] = array($row[0], $row[1]);
		}
		return $modul;
	}

	function create_bereiche_array () {
		function_debug_counter("create_bereiche_array");
		$modul = array();
		$query = 'SELECT `id`, `name` FROM `bereich`';
		$result = rquery($query);
		while ($row = mysqli_fetch_row($result)) {
			$modul[$row[0]] = array($row[0], $row[1]);
		}
		return $modul;
	}

	function create_pruefungsnummer_fach_array () {
		function_debug_counter("create_pruefungsnummer_fach_array");
		$modul = array();
		$query = 'SELECT `id`, `name` FROM `pruefungsnummer_fach`';
		$result = rquery($query);
		while ($row = mysqli_fetch_row($result)) {
			$modul[$row[0]] = array($row[0], $row[1]);
		}
		return $modul;
	}

	function create_modul_array () {
		function_debug_counter("create_modul_array");
		$modul = array();
		$query = 'SELECT `id`, `name` FROM `modul`';
		$result = rquery($query);
		while ($row = mysqli_fetch_row($result)) {
			$modul[$row[0]] = array($row[0], $row[1]);
		}
		return $modul;
	}

	function create_veranstaltungen_array ($dozent = null, $not_id = null, $shorten = 0, $semester = null, $institut = null) {
		function_debug_counter("create_veranstaltungen_array");
		$veranstaltungen = array();
		$query = 'SELECT `v`.`veranstaltung_id`, `v`.`veranstaltung_name`, concat(`v`.`first_name`, " ", `v`.`last_name`) AS `dozent`, `v`.`veranstaltung_typ`, `v`.`wochentag`, `v`.`stunde` FROM `view_veranstaltung_komplett` `v` ';
		
		if(isset($institut)) {
			$query .= ' LEFT JOIN `veranstaltung` `ve` ON `ve`.`id` = `v`.`veranstaltung_id`';
		}

		$query .= 'WHERE 1';

		if(isset($dozent) && !is_null($dozent)) {
			if(is_array($dozent) && count($dozent)) {
				$query .= ' AND `v`.`dozent_id` IN ('.multiple_esc_join($dozent).')';
			} else if (strlen($dozent)) {
				$query .= ' AND `v`.`dozent_id` = '.esc($dozent);
			}
		}

		if(isset($not_id) && !is_null($not_id)) {
			$query .= ' AND `v`.`veranstaltung_id` != '.esc($not_id);
		}

		if(isset($semester) && !is_null($semester)) {
			$query .= ' AND `v`.`semester_id` = '.esc($semester);
		}

		if(isset($institut) && !is_null($institut)) {
			$query .= ' AND `ve`.`institut_id` = '.esc($institut);
		}

		$result = rquery($query);
		while ($row = mysqli_fetch_row($result)) {
			if($shorten) {
				if(strlen($row[1]) >= $shorten) {
					$row[1] = substr($row[1], 0, $shorten).'...';
				}
			}

			$str = '';
			if($row[1]) {
				$str .= $row[1];
			}

			if($row[3]) {
				if($str) {
					$str .= ', ';
				}
				$str .= $row[3];
			}

			if($row[4]) {
				if($str) {
					$str .= ', ';
				}
				$str .= 'Dozent: '.$row[4];
			}

			if($row[5]) {
				if($str) {
					$str .= ', ';
				}
				$str .= 'Dozent: '.$row[2];
			}

			$veranstaltungen [$row[0]] = array($row[0], $str);
		}
		return $veranstaltungen;
	}

	function create_pruefungstypen_array () {
		function_debug_counter("create_pruefungstypen_array");
		$pruefungstypen = array();
		$query = 'SELECT `id`, `name` FROM `pruefungstyp`';
		$result = rquery($query);
		while ($row = mysqli_fetch_row($result)) {
			$pruefungstypen[$row[0]] = array($row[0], $row[1]);
		}
		return $pruefungstypen;
	}

	function create_seiten_array () {
		function_debug_counter("create_seiten_array");
		$seiten = array();
		$query = 'SELECT `id`, `name`, `file` FROM `page`';
		$result = rquery($query);
		while ($row = mysqli_fetch_row($result)) {
			$seiten[$row[0]] = array($row[0], $row[1], $row[2]);
		}
		return $seiten;
	}

	function create_gebaeude_array($show_long_name = 1) {
		function_debug_counter("create_gebaeude_array");
		$gebaeude = array();
		$query = 'SELECT `id`, `name`, `abkuerzung` FROM `gebaeude`';
		$result = rquery($query);
		while ($row = mysqli_fetch_row($result)) {
			$gebaeude[$row[0]] = array($row[0], ($row[2].($show_long_name ? " ($row[1])" : '')));
		}
		return $gebaeude;
	}

	function create_pruefungen_veranstaltungen_array () {
		function_debug_counter("create_pruefungen_veranstaltungen_array");
		$pruefungen = array();
		$query = 'SELECT `v`.`id`, count(*) AS `anzahl_pruefungen` FROM `pruefung` `p` LEFT JOIN `veranstaltung` `v` ON `v`.`id` = `p`.`veranstaltung_id` GROUP BY `v`.`id`';
		$result = rquery($query);
		while ($row = mysqli_fetch_row($result)) {
			$pruefungen[$row[0]] = $row[1];
		}
		return $pruefungen;
	}

	function create_studiengang_array_with_semester_data () {
		function_debug_counter("create_studiengang_array_with_semester_data");
		$query = 'select s.id, s.name, b.name from studiengang s left join view_modul_semester vms on vms.studiengang_id = s.id where modul_id is not null and semester is not null group by id';
		$result = rquery($query);

		$st = array();

		while ($row = mysqli_fetch_row($result)) {
			if($row[2]) {
				$st[] = array($row[0], "$row[1] ($row[2])");
			} else {
				$st[] = array($row[0], $row[1]);
			}
		}
		return $st;
	}

	function print_hinweis_for_page ($chosen_page){
		function_debug_counter("print_hinweis_for_page");
		$hinweis = get_hinweis_for_page($chosen_page);
		if($hinweis) {
			print "<span class='blue_text'><i>Hinweis: </i> ".htmlentities($hinweis)."<br /><br /></span>";
		}
	}

	function get_hinweis_for_page ($chosen_page) {
		function_debug_counter("get_hinweis_for_page");
		$query = 'SELECT `hinweis` FROM `hinweise` WHERE `page_id` = '.esc($chosen_page);
		$result = rquery($query);
		$hinweis = '';
		while ($row = mysqli_fetch_row($result)) {
			if(strlen($row[0]) && !preg_match('/^\s*$/', $row[0])) {
				$hinweis = $row[0];
			}
		}
		return $hinweis;
	}

	function get_roles_for_page ($pageid) {
		function_debug_counter("get_roles_for_page");
		$rollen = array();
		$query = 'SELECT `role_id` FROM `role_to_page` WHERE `page_id` = '.esc($pageid);
		$result = rquery($query);
		while ($row = mysqli_fetch_row($result)) {
			$rollen[] = $row[0];
		}
		return $rollen;
	}

	function create_page_parent_array () {
		function_debug_counter("create_page_parent_array");
		$rollen = array();
		$query = 'SELECT `id`, `name` FROM `page` WHERE `parent` IS NULL AND `file` IS NULL';
		$result = rquery($query);
		while ($row = mysqli_fetch_row($result)) {
			$rollen[$row[0]] = array($row[0], $row[1]);
		}
		return $rollen;
	}

	function create_rollen_array () {
		function_debug_counter("create_rollen_array");
		$rollen = array();
		$query = 'SELECT `id`, `name` FROM `role`';
		$result = rquery($query);
		while ($row = mysqli_fetch_row($result)) {
			$rollen[$row[0]] = array($row[0], $row[1]);
		}
		return $rollen;
	}

	function create_user_array ($role = 0, $specific_role = null) {
		function_debug_counter("create_user_array");
		$user = array();
		if($role) {
			$query = 'SELECT `u`.`id`, `u`.`username`, `r`.`role_id` FROM `users` `u` JOIN `role_to_user` `r` ON `r`.`user_id` = `u`.`id`';
			if(isset($specific_role)) {
				$query .= ' WHERE `role_id` = '.esc($specific_role);
			}
			$result = rquery($query);
			while ($row = mysqli_fetch_row($result)) {
				$user[$row[0]] = array($row[0], $row[1], $row[2]);
			}
			return $user;
		} else {
			$query = 'SELECT `id`, `username` FROM `users`';
			$result = rquery($query);
			while ($row = mysqli_fetch_row($result)) {
				$user[$row[0]] = array($row[0], $row[1]);
			}
			return $user;
		}
	}
	
	/* Hilfsfunktionen */

	function global_exists ($name) {
		function_debug_counter("global_exists");
		if(array_key_exists($name, $GLOBALS) && count($GLOBALS[$name])) {
			return 1;
		} else {
			return 0;
		}
	}

	function get_output_class ($name) {
		function_debug_counter("get_output_class");
		if($name == 'error') {
			return "red_background";
		} else if ($name == 'right_issue') {
			return "red_background";
		} else if ($name == 'warning') {
			return "orange_background";
		} else if ($name == 'message') {
			return "blue_background";
		} else if ($name == 'success') {
			return "green_background";
		} else if ($name == 'easter_egg') {
			return "hotpink_background";
		} else {
			die("Unknown $name");
		}
	}

	function show_output ($name, $color) {
		function_debug_counter("show_output");
		if(global_exists($name)) {
			#print "<div class='square ".get_output_class($name)."'>\n";
			print "<div class='square'>\n";
			print "<div class='one'>\n";
			if(file_exists("./i/$name.svg")) {
				print "<img height='60' src='./i/$name.svg' />\n";
			}
			print "</div>\n";
			print "<div class='two'>\n";
			$this_output = $GLOBALS[$name];
			$this_output = array_unique($this_output);
			if($color) {
				if(count($this_output) > 1) {
					print "<ul>\n";
				}
				foreach ($this_output as $this_output_item) {
					#print "<span class='class_$color'><h2>$name: ".$this_output_item."</h2></span>\n";
					if(count($this_output) > 1) {
						print "<li>\n";
					}
					print "<span class='message_text'>".$this_output_item."</span>\n";
					if(count($this_output) > 1) {
						print "</li>\n";
					}
				}
				if(count($this_output) > 1) {
					print "</ul>\n";
				}
			}
			print "</div>\n";
			print "</div>\n";
			print "<div class='clear_both' /><br />\n";
		}
	}

	function raum_ist_belegt ($raum_id, $datum, $stunde, $pruefung_id, $nachpruefung_id) {
		function_debug_counter("raum_ist_belegt");
		$raum_ist_belegt = 0;

		if($raum_id && $datum && $stunde) {
			$query = 'SELECT `id` FROM `pruefung` WHERE `raum_id` = '.esc($raum_id).' AND `datum` = '.esc($datum).' AND `stunde` = '.esc($stunde);
			if($pruefung_id) {
				$query .= ' AND `id` != '.esc($pruefung_id);
			}
			$query .= " LIMIT 1";
			$result = rquery($query);
			if(mysqli_num_rows($result)) {
				$raum_ist_belegt = 1;
			}

			if(!$raum_ist_belegt) {
				$query = 'SELECT `id` FROM `nachpruefung` WHERE `raum_id` = '.esc($raum_id).' AND `datum` = '.esc($datum).' AND `stunde` = '.esc($stunde);
				if($nachpruefung_id) {
					$query .= ' AND `id` != '.esc($nachpruefung_id);
				}
				$query .= " LIMIT 1";
				$result = rquery($query);
				if(mysqli_num_rows($result)) {
					$raum_ist_belegt = 1;
				}
			}
		}

		return $raum_ist_belegt;
	}

	function export_pruefungsnummern_dozent ($chosen_semester, $chosen_dozent, $chosen_institut, $chosen_studiengang, $chosen_pruefungsamt, $studiengang_group_by, $html = 1) {
		function_debug_counter("export_pruefungsnummern_dozent");
		#			0														1
		#	2			3		4
		$query = 'SELECT 
	concat(if(`t`.`abkuerzung` is not null, `t`.`abkuerzung`, ""), " ", `d`.`first_name`, " ",`d`.`last_name`) AS `dozent_name`, 
	IF(`pn`.`pruefungsnummer`, `pn`.`pruefungsnummer`, "Keine Prüfungsnummer eingetragen") AS `pruefungsdaten`,
	`s`.`name` AS `studiengang`, 
	`pz`.`name`, 
	`pn`.`id`,
	`vm`.`abgabe_pruefungsleistungen`
FROM 
	`veranstaltung` `v` 
JOIN 
	`pruefung` `p` 
ON 
	`p`.`veranstaltung_id` = `v`.`id` 
JOIN 
	`pruefungsnummer` `pn` 
ON 
	`pn`.`id` = `p`.`pruefungsnummer_id` 
JOIN
	`veranstaltung_metadaten` `vm`
ON
	`v`.`id` = `vm`.`veranstaltung_id` 
JOIN 
	`dozent` `d` 
ON 
	`v`.`dozent_id` = `d`.`id` 
JOIN 
	`modul` `m` 
ON
	`m`.`id` = `pn`.`modul_id` 
JOIN 
	`studiengang` `s` 
ON 
	`s`.`id` = `m`.`studiengang_id` 
JOIN 
	`pruefung_zeitraum` `pz` 
ON 
	`pz`.`id` = `pn`.`zeitraum_id` 
LEFT JOIN 
	`titel` `t` 
ON 
	`d`.`titel_id` = `t`.`id` 
WHERE 
	`v`.`semester_id` = '.esc($chosen_semester);


		$query .= create_add_where_export_dozent_pruefungsnummern($chosen_institut, $chosen_dozent, $chosen_studiengang, $chosen_pruefungsamt);

		$query .= ' ORDER BY `studiengang`, `pruefungsdaten`, `dozent_name`';

		$result = rquery($query);

		$data = array();
		$zeitraeume = array();

		while ($row = mysqli_fetch_row($result)) {
			$dozent_name = $row[0];
			$pn = $row[1];
			$studiengang = $row[2];
			$zeitraum = $row[3];
			$id = $row[4];
			$abgabe_pruefungsleistungen = $row[5];

			if(!array_key_exists($studiengang, $data)) {
				$data[$studiengang] = array();
				$data[$studiengang][$pn] = array();
			}
			$data[$studiengang][$pn]['abgabe_pruefungsleistungen'] = $abgabe_pruefungsleistungen;
			$data[$studiengang][$pn]['zeitraum'] = $zeitraum;
			if(array_key_exists('dozenten', $data[$studiengang][$pn]) && is_null($data[$studiengang][$pn]['dozenten'])) {
				@$data[$studiengang][$pn]['dozenten'] = array();
			}
			if(!in_array($dozent_name, $data[$studiengang][$pn]['dozenten'])) {
				$data[$studiengang][$pn]['dozenten'][] = $dozent_name;
			}
			$zeitraeume[$id] = $zeitraum;
		}

		if($html) {
			$ret_string = '';
			if(count($data)) {
				$ret_string = "<table>
					<tr>
					<th>Prüfungsnummer</th>
					<th>Dozenten</th>
					<th>Zeitraum</th>
					<th>Abgabe Prüfungsleistungen</th>
					</tr>";
$ic = 0;

foreach ($data as $studiengang => $local_data) {
	$ret_string .= "<tr><td colspan='4' class='bg_add8e6'>".htmle($studiengang)."</td></tr>\n";
	foreach ($local_data as $pnname => $pruefungsnummer_array) {
		$ret_string .= "<tr><td>".htmle($pnname)."</td><td>\n";
		$ret_string .= join("<br />\n", array_unique($pruefungsnummer_array['dozenten']));
		$ret_string .= "<td>".$pruefungsnummer_array['zeitraum']."</td>\n";
		$ret_string .= "<td>".htmle($pruefungsnummer_array['abgabe_pruefungsleistungen'])."</td></tr>\n";
		$ic++;
	}
}
$ret_string .= '</table>';
			} else {
				$ret_string .= '<i>Mit den gewählten Optionen sind keine Daten verfügbar.</i>';
			}

			return $ret_string;
		} else {
			include_once './Classes/PHPExcel.php';
			$objPHPExcel = new PHPExcel();

			$objPHPExcel->getProperties()->setCreator(htmlentities($GLOBALS['logged_in_data'][1]));
			$objPHPExcel->getProperties()->setTitle("Export Prüfungsnummer -> Dozent");
			$objPHPExcel->setActiveSheetIndex(0);
			$number = 1;
			$letter = 'A';

			$title = "Export der Dozenten und der angebotenen Prüfungsnummern";
			$title_plus = create_title_plus($chosen_semester, $chosen_institut, $chosen_studiengang, $chosen_dozent, $chosen_pruefungsamt);

			$title = "$title$title_plus";
			$objPHPExcel->getActiveSheet()->SetCellValue($letter.$number, $title);
			$objPHPExcel->getActiveSheet()->mergeCells("A".$number.":"."D".$number);

			$number++;
			$ci = 0;
			foreach ($data as $studiengang => $local_data) {
				$objPHPExcel->getActiveSheet()->SetCellValue($letter.$number, $studiengang);
				cellColor($objPHPExcel, $letter.$number, '99acbe');
				$objPHPExcel->getActiveSheet()->mergeCells("A".$number.":"."D".$number);
				$number++;

				foreach ($local_data as $pnname => $pruefungsnummer_array) {
					$zeitraum = $pruefungsnummer_array['zeitraum'];
					$abgabe_pruefungsleistungen = $pruefungsnummer_array['abgabe_pruefungsleistungen'];
					$objPHPExcel->getActiveSheet()->SetCellValue("A".$number, $pnname);
					$str = '';
					if(count($pruefungsnummer_array['dozenten']) == 1) {
						$str = strip_tags($pruefungsnummer_array['dozenten'][0])." ";
					} else {
						$str = join(", ", array_unique(array_map('strip_tags', $pruefungsnummer_array['dozenten'])));
					}
					$objPHPExcel->getActiveSheet()->SetCellValue('B'.$number, $str);
					$objPHPExcel->getActiveSheet()->SetCellValue('C'.$number, $zeitraum);
					$objPHPExcel->getActiveSheet()->SetCellValue('D'.$number, $abgabe_pruefungsleistungen ? $abgabe_pruefungsleistungen : '-');
					$number++;
					$ci++;
				}
			}

			return auto_size_phpexcel($objPHPExcel);
		}
	}

	function create_add_where_export_dozent_pruefungsnummern ($chosen_institut, $chosen_dozent, $chosen_studiengang, $chosen_pruefungsamt) {
		function_debug_counter("create_add_where_export_dozent_pruefungsnummern");
		$query = '';
		if(!is_null($chosen_institut)) {
			$query .= ' AND `v`.`institut_id` = '.esc($chosen_institut);
		}
		if(!is_null($chosen_dozent)) {
			$query .= ' AND `v`.`dozent_id` = '.esc($chosen_dozent);
		}
		if(!is_null($chosen_studiengang)) {
			$query .= ' AND `m`.`studiengang_id` = '.esc($chosen_studiengang);
		}
		if(!is_null($chosen_pruefungsamt)) {
			$query .= ' AND `m`.`studiengang_id` IN (SELECT `studiengang_id` FROM `pruefungsamt_nach_studiengang` WHERE `pruefungsamt_id` = '.esc($chosen_pruefungsamt).')';
		}
		return $query;
	}

	function export_dozent_pruefungsnummern ($chosen_semester, $chosen_dozent, $chosen_institut, $chosen_studiengang, $chosen_pruefungsamt, $html = 1) {
		function_debug_counter("export_dozent_pruefungsnummern");
		$query = 'SELECT concat(`d`.`last_name`, ", ",`d`.`first_name`) AS `dozent_name`, IF(`pn`.`pruefungsnummer`, `pn`.`pruefungsnummer`, "NODATA") `pruefungsdaten`, `s`.`name` AS `studiengang`, `pz`.`name` FROM `veranstaltung` `v` JOIN `pruefung` `p` ON `p`.`veranstaltung_id` = `v`.`id` JOIN `pruefungsnummer` `pn` ON `pn`.`id` = `p`.`pruefungsnummer_id` JOIN `dozent` `d` ON `v`.`dozent_id` = `d`.`id` JOIN `modul` `m` ON `m`.`id` = `pn`.`modul_id` JOIN `studiengang` `s` ON `s`.`id` = `m`.`studiengang_id` JOIN `pruefung_zeitraum` `pz` ON `pz`.`id` = `pn`.`zeitraum_id` WHERE `v`.`semester_id` = '.esc($chosen_semester);

		$query .= create_add_where_export_dozent_pruefungsnummern ($chosen_institut, $chosen_dozent, $chosen_studiengang, $chosen_pruefungsamt);

		$query .= ' ORDER BY `studiengang`, `dozent_name`, `pruefungsdaten`';

		$result = rquery($query);

		$data = array();

		while ($row = mysqli_fetch_row($result)) {
			if(!array_key_exists($row[2], $data)) {
				$data[$row[2]] = array();
				$data[$row[2]][$row[0]] = array();
			}
			$data[$row[2]][$row[0]][] = $row[1];
		}

		$data2 = array();

		foreach ($data as $studiengang => $local_data) {
			foreach ($local_data as $dozent_name => $pruefungsnummer_array) {
				$count_no_data = 0;
				foreach ($pruefungsnummer_array as $this_pruefungsnummer) {
					if($this_pruefungsnummer == 'NODATA') {
						$count_no_data++;
					} else {
						$data2[$studiengang][$dozent_name][] = $this_pruefungsnummer;
					}
				}
				if($count_no_data) {
					$data2[$studiengang][$dozent_name][] = "<i>Keine Prüfungsnummer vorhanden für $count_no_data vorhandene Prüfungsmöglichkeit(en)</i>";
				}
			}
		}

		if($html) {
			$ret_string = '';
			if(count($data2)) {
				$ret_string = "<table>
					<tr>
					<th>Dozent</th>
					<th>Prüfungsnummer</th>
					</tr>";
foreach ($data2 as $studiengang => $local_data) {
	$ret_string .= "<tr><td colspan='2' class='bg_add8e6'>".htmle($studiengang)."</td></tr>\n";

	foreach ($local_data as $dozent_name => $pruefungsnummer_array) {
		$ret_string .= "<tr><td>".htmle($dozent_name)."</td><td>\n";
		$ret_string .= join(', ', $pruefungsnummer_array);
		$ret_string .= "</td></tr>\n";
	}
}
$ret_string .= '</table>';
			} else {
				$ret_string .= '<i>Mit den gewählten Optionen sind keine Daten verfügbar.</i>';
			}

			return $ret_string;
		} else {
			$objPHPExcel = '';
			include_once 'Classes/PHPExcel.php';
			$objPHPExcel = new PHPExcel();

			$objPHPExcel->getProperties()->setCreator(htmlentities($GLOBALS['logged_in_data'][1]));
			$objPHPExcel->getProperties()->setTitle("Raumbelegung");
			$objPHPExcel->setActiveSheetIndex(0);
			$number = 1;
			$letter = 'A';

			$title = "Export Dozenten/Prüfungsnummern";
			$title_plus = create_title_plus($chosen_semester, $chosen_institut, $chosen_studiengang, $chosen_dozent, $chosen_pruefungsamt);

			$title = "$title$title_plus";
			$objPHPExcel->getActiveSheet()->SetCellValue($letter.$number, $title);
			$objPHPExcel->getActiveSheet()->mergeCells("A".$number.":"."B".$number);

			$number++;
			foreach ($data2 as $studiengang => $local_data) {
				$objPHPExcel->getActiveSheet()->SetCellValue($letter.$number, $studiengang);
				cellColor($objPHPExcel, $letter.$number, '99acbe');
				$objPHPExcel->getActiveSheet()->mergeCells("A".$number.":"."B".$number);
				$number++;

				foreach ($local_data as $dozent_name => $pruefungsnummer_array) {
					$objPHPExcel->getActiveSheet()->SetCellValue("A".$number, $dozent_name);
					$str = '';
					if(count($pruefungsnummer_array) == 1) {
						$str = strip_tags($pruefungsnummer_array[0])." ";
					} else {
						$str = join(", ", array_map('strip_tags', $pruefungsnummer_array));
					}
					$objPHPExcel->getActiveSheet()->SetCellValue('B'.$number, $str);
					$number++;
				}
			}

			return auto_size_phpexcel ($objPHPExcel);

		}
	}

	function auto_size_phpexcel ($objPHPExcel) {
		function_debug_counter("auto_size_phpexcel");
		$sheet = $objPHPExcel->getActiveSheet();
		$cellIterator = $sheet->getRowIterator()->current()->getCellIterator();
		$cellIterator->setIterateOnlyExistingCells(true);

		foreach ($cellIterator as $cell) {
			$sheet->getColumnDimension($cell->getColumn())->setAutoSize(true);
		}

		foreach (range('A', $objPHPExcel->getActiveSheet()->getHighestDataColumn()) as $col) {
			$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
		}

		return $objPHPExcel;	
	}

	function check_this_user_role ($roles = array()) {
		function_debug_counter("check_this_user_role");
		$valid = 0;
		if(is_array($roles)) {
			if(count($roles)) {
				foreach ($roles as $trole) {
					if(is_array($trole)) {
						die("ERROR: CANNOT BE ARRAY IN `check_this_user_role`");
					} else {
						if($GLOBALS['user_role_id'] == $trole) {
							$valid = 1;
						}
					}
				}
			}
		} else if (is_string($roles)) {
			if($GLOBALS['user_role_id'] == $roles) {
				$valid = 1;
			}		
		}

		return $valid;
	}

	function get_raumplanung_relevante_daten ($id) {
		function_debug_counter("get_raumplanung_relevante_daten");
		$relevante_daten = array(
			'veranstaltung_metadaten' => array(
				"id_name" => "veranstaltung_id",
				"fields" => array(
					"wunsch",
					"anzahl_hoerer",
					"wochentag",
					"stunde",
					"woche"
				)
			),
			"veranstaltung" => array(
				"id_name" => "id",
				"fields" => array(
					"gebaeudewunsch_id",
					"raumwunsch_id"
				)
			)
		);
		$daten = array();

		foreach ($relevante_daten as $this_tabellen_name => $this_relevante_daten) {
			$query = 'SELECT `'.join('`, `', $this_relevante_daten['fields']).'` FROM `'.$this_tabellen_name.'` WHERE `'.$this_relevante_daten['id_name'].'` = '.esc($id);
			$result = rquery($query);

			while ($row = mysqli_fetch_assoc($result)) {
				$daten[$this_tabellen_name] = $row;
			}
		}

		return $daten;
	}

	function raumplanung_update ($id) {
		function_debug_counter("raumplanung_update");
		$query = 'INSERT INTO raumplanung_relevante_daten_geaendert (veranstaltung_id, raumplanung_aenderung) VALUES ('.esc($id).', now()) ON DUPLICATE KEY UPDATE raumplanung_aenderung = values(raumplanung_aenderung)';
		rquery($query);
	}

	function updated_raumplanung_relevante_daten ($id, $alte_daten) {
		function_debug_counter("updated_raumplanung_relevante_daten");
		$neue_daten = get_raumplanung_relevante_daten($id);

		if(serialize($alte_daten) != serialize($neue_daten)) {
			$query = 'INSERT INTO raumplanung_relevante_daten_geaendert (veranstaltung_id, veranstaltung_aenderung) VALUES ('.esc($id).', now()) ON DUPLICATE KEY UPDATE veranstaltung_aenderung = values(veranstaltung_aenderung)';
			rquery($query);
		}
	}

	function veranstaltung_raumplanungsrelevante_daten_geupdatet ($id) {
		function_debug_counter("veranstaltung_raumplanungsrelevante_daten_geupdatet");
/*
	TODO!!!!! JAHR 2036 BUG

		veranstaltung_aenderung | raumplanung_aenderung
	Neu erstellt // 1
		1				NULL
	Update ohne Raumplanung // 1
		2				NULL
	Raumplanung gemacht nach letztem Update // 0
		2				3
	Letztes Update ohne Raumplanung // 1
		3				NULL
	Letztes Update mit Raumplanung // 1
		4				3
	Letzte Raumplanung ohne Update // 0
		3				4


	Bedingung:
		WAHR, wenn:
			- raumplanung_aenderung is null
			- veranstaltung_aenderung > raumplanung_aenderung
			- gar keine zeilen zurückkommen
		SONST FALSCH
 */


		$query = 'select unix_timestamp(veranstaltung_aenderung), unix_timestamp(raumplanung_aenderung) from raumplanung_relevante_daten_geaendert where veranstaltung_id = '.esc($id);
		$result = rquery($query);

		$i = 0;
		while ($row = mysqli_fetch_row($result)) {
			$veranstaltung_aenderung = $row[0];
			$raumplanung_aenderung = $row[1];
			if(!$raumplanung_aenderung) {
				return 1;
			} else if(!$veranstaltung_aenderung) {
				return 0;
			} else if ($veranstaltung_aenderung && $raumplanung_aenderung) {
				if(($raumplanung_aenderung - $veranstaltung_aenderung) < 0) {
					return 1;
				}

			} else {
				return 0;
			}
			$i++;
		}

		if($i == 0) {
			return 1;
		} else {
			return 0;
		}
	}

	function veranstaltung_is_in_schueler_uni ($id) {
		$query = 'select count(*) from pruefung p left join pruefungsnummer pn on pn.id = p.pruefungsnummer_id left join modul m on m.id = pn.modul_id where m.studiengang_id in (select id from studiengang where name like "%sch%leruni%") and veranstaltung_id = '.esc($id);;
		$result = get_single_row_from_query($query);
		if($result[0]) {
			return 'x';
		} else {
			return'';
		}
	}


	function veranstaltung_is_in_stex ($id) {
		$query = 'select count(*) from pruefung p left join pruefungsnummer pn on pn.id = p.pruefungsnummer_id left join modul m on m.id = pn.modul_id where m.studiengang_id in (select id from studiengang where name like "%staatsexamen%") and veranstaltung_id = '.esc($id);;
		$result = get_single_row_from_query($query);
		if($result[0]) {
			return 'x';
		} else {
			return'';
		}
	}

	function veranstaltung_is_in_aqua ($id) {
		$query = 'select count(*) from pruefung p left join pruefungsnummer pn on pn.id = p.pruefungsnummer_id left join modul m on m.id = pn.modul_id where m.studiengang_id in (select id from studiengang where name like "%aqua%") and veranstaltung_id = '.esc($id);;
		$result = get_single_row_from_query($query);
		if($result[0]) {
			return 'x';
		} else {
			return'';
		}
	}

	function veranstaltung_is_in_studium_generale_or_buerger_universitaet ($id) {
		$query = 'select count(*) from pruefung p left join pruefungsnummer pn on pn.id = p.pruefungsnummer_id left join modul m on m.id = pn.modul_id where m.studiengang_id in (select id from studiengang where name like "%rger%" or name like "%generale%") and veranstaltung_id = '.esc($id);;
		$result = get_single_row_from_query($query);
		if($result[0]) {
			return 'x';
		} else {
			return'';
		}
	}

	function raumplanung_crazy ($institut = null, $semester, $show_html) {
		function_debug_counter("raumplanung");
		if(is_null($institut)) {
			$institute = create_institute_array();

			$this_institut = null;

			if(preg_match('/^\d+$/', get_get('institut'))) {
				$this_institut = get_get('institut');
			} else {
				if($_SERVER['HTTP_HOST'] == $GLOBALS['vvz_base_url']) {
					$this_institut = $institute[1][0];
				}
				
				if(!$this_institut) {
					if(count($institute)) {
						if(array_key_exists(0, $institute) && array_key_exists(0, $institute[0])) {
							$this_institut = $institute[0][0];
						}
						if(!$this_institut) {
							$this_institut = 1;
						}
					} else {
						die("Es konnten keine Institute gefunden werden. Ohne eingetragene Institute kann die Software nicht benutzt werden. Bitte kontaktieren Sie die Administratoren über die Kontaktseite.");
					}
				}
			}

			$institut = $this_institut;
		}
		if(is_null($semester) || !$semester) {
			$semester = get_and_create_this_semester();
		}
		if(is_array($semester)) {
			$semester = $semester[0];
		}
		$gebaeude = create_gebaeude_array();

		$query = 'select 
    vt.name as veranstaltungstyp_name, 
    pt.name as pruefungstyp, 
    d.last_name as lehrend, 
    pn.pruefungsnummer as pruefungsnummer, 
    m.abkuerzung as modulname,
    ifnull(v.gebaeude_id, v.gebaeudewunsch_id) as gebaeude_id,
    ifnull(v.raum_id, v.raumwunsch_id) as raum_id,
    concat(vm.wochentag, "(", vm.stunde, ")") as zeitvorschlag,
    v.name as veranstaltungname,
    vm.anzahl_hoerer as tn,
    vm.wunsch as raumausstattungsvorschlag,
    if(u.barrierefrei = "1", "Barrierefrei", "---") as bemerkung,
    v.institut_id as institut_id,
    v.semester_id as semester_id,
    v.id as veranstaltung_id
from 
    pruefung p 
    left join pruefungsnummer pn on pn.id = p.pruefungsnummer_id 
    left join modul m on m.id = pn.modul_id 
    left join veranstaltung v on p.veranstaltung_id = v.id
    left join dozent d on d.id = v.dozent_id 
    left join pruefungstyp pt on pt.id = pn.pruefungstyp_id 
    left join veranstaltungstyp vt on vt.id = v.veranstaltungstyp_id 
    left join veranstaltung_metadaten vm on vm.veranstaltung_id = v.id
    left join users u on u.dozent_id = d.id
    left join studiengang s on m.studiengang_id = s.id
    left join institut i on u.dozent_id = d.id
where 1
';

		if($institut) {
			$query .= ' AND `v`.`institut_id` = '.esc($institut);
		}


		if($semester) {
			$query .= ' AND `v`.`semester_id` = '.esc($semester);
		}

		$query .= "\n";

		$query .= ' ORDER BY `i`.`name` ASC, `vm`.`wochentag` ASC, `vm`.`stunde` ASC, `vm`.`woche` ASC, `v`.`name` ASC, d.last_name ASC';

		$result = rquery($query);

		$raum_name = create_raum_name_id_array();
		$gebaeude_abkuerzung_id = create_gebaeude_abkuerzung_id_array();

		$start_nr = 0;
		$reihen = array();
		$institut_id = null;
		$number_of_cols = 0;
		$ids_array = array();
		$minicache = array('gebaeude_abkuerzung' => array(), 'raumnummer' => array());

		$has_printed_rows = 0;

		$data = array();

		while ($row = mysqli_fetch_assoc($result)) {
			$data[] = $row;
		}

		$header = array(	
			'LV-Nummer',
			'Modulbezeichnung lt. Modulbeschreibung',
			'Modulbezeichnung in CampusNet',
			'Titel der LV',	
			'Kurztitel der LV',
			'Lehrend',
			'Voraussichtlich prüfend',
			'Prüfungsleistung',
			'Prüfungsnummer',
			'LV-Art',
			'SWS',
			'TN-Zahl',
			'Zeitvorschlag',
			'Alternativer Zeitvorschlag',
			'Gebäude/Raumvorschlag',
			'UR/HS',
			'Raumausstattungsvorschlag',
			'Bemerkungen/Sperrzeiten',
			array(
				'Zusätzlich angeboten für' => array(
					'stud. gen., Bürger-uni',
					'AQua',
					'Schüleruni (nur Do-LV)',
					'Ergänzungsbereich: StEx',
					'Ergänzungsbereich: MA',
					'Freigegeben für Schüleruniversität',
					'Sonstiges (bitte nennen)'
				)
			)
		);

		$gebaeude = create_gebaeude_abkuerzungen_array();
		include_once 'Classes/PHPExcel.php';
		$objPHPExcel = new PHPExcel();

		$objPHPExcel->getProperties()->setCreator(htmlentities($GLOBALS['logged_in_data'][1]));
		$objPHPExcel->getProperties()->setTitle("Raumplanung");
		$objPHPExcel->setActiveSheetIndex(0);

		$semester_data = get_semester($semester);
		$semester_string = $semester_data[2].' '.$semester_data[1];

		$objPHPExcel->getActiveSheet()->SetCellValue('B1', "Planung der Veranstaltungen für das ".$semester_string);
		$objPHPExcel->getActiveSheet()->mergeCells('B1:F1');
		cellColor($objPHPExcel, 'B1:F1', 'FFFF00');

		$objPHPExcel->getActiveSheet()->SetCellValue('B3', "Institut: ".get_institut_name($institut));
		$objPHPExcel->getActiveSheet()->mergeCells('B3:F3');
		cellColor($objPHPExcel, 'B3:F3', 'E7E6E6');

		$objPHPExcel->getActiveSheet()->SetCellValue('B4', "Professur: ");
		$objPHPExcel->getActiveSheet()->mergeCells('B4:F4');
		cellColor($objPHPExcel, 'B4:F4', 'E7E6E6');

		$objPHPExcel->getActiveSheet()->SetCellValue('B5', "Bearbeitet von: ".$GLOBALS['logged_in_data'][1]);
		$objPHPExcel->getActiveSheet()->mergeCells('B5:F5');
		cellColor($objPHPExcel, 'B5:F5', 'E7E6E6');

		$number = 7;
		$letter = 'A';

		foreach ($header as $this_head) {
			if(is_array($this_head)) {
				#dier($this_head);
				$start_letter = $letter;
				foreach ($this_head as $top => $bottom) {
					if($letter == $start_letter) {
						$last_letter = $start_letter;
						$number_of_items = count($this_head[$top]) - 1;
						while ($number_of_items) {
							$number_of_items--;
							$last_letter++;
						}
						$objPHPExcel->getActiveSheet()->SetCellValue($letter.'7', $top);
						$objPHPExcel->getActiveSheet()->mergeCells($start_letter.'7:'.$last_letter.'7');
						cellColor($objPHPExcel, $start_letter.'7:'.$last_letter.'7', 'E7E6E6');
					}
					foreach ($bottom as $bottom_headline) {
						$objPHPExcel->getActiveSheet()->SetCellValue($letter.'8', $bottom_headline);
						cellColor($objPHPExcel, $letter.'8', 'E7E6E6');
						$letter++;
					}
				}
			} else {
				$objPHPExcel->getActiveSheet()->SetCellValue($letter.'7', $this_head);
				$objPHPExcel->getActiveSheet()->mergeCells($letter.'7:'.$letter.'8');
				cellColor($objPHPExcel, $letter.'7:'.$letter.'8', 'E7E6E6');
				$letter++;
			}
		}
		$number++;

#dier($data);

/*

        (
            [veranstaltungstyp_name] => Blockseminar
            [pruefungstyp] => Bericht
            [lehrend] => Pagel
            [pruefungsnummer] => 48711
            [modulname] => PHF-SEGY-ETH-SPÜ
            [gebaeude_id] => 
            [raum_id] => 
            [zeitvorschlag] => Do(1)
            [veranstaltungname] => Schulpraktische Übungen (SPÜ) Ethik Dr. Seele
            [tn] => 
            [raumausstattungsvorschlag] => Beamer und Tafel
SE 1/2 oder BZW
            [bemerkung] => 
            [institut_id] => 1
            [semester_id] => 7
        )
*/

		$lv_nr = null;
		$last_institut_id = null;
		$last_veranstaltung_id = null;
		$zeile = 9;
		$last_letter = null;
		foreach($data as $row) {
			if(is_null($last_veranstaltung_id)) {
				$last_veranstaltung_id = $row['veranstaltung_id'];
			} else {
				if($row["veranstaltung_id"] != $last_veranstaltung_id) {
					$last_veranstaltung_id = $row['veranstaltung_id'];
					$zeile++;
					$lv_nr++;
				}
			}
			$letter = "A";
			$modulbezeichnung = $row["modulname"];
			$veranstaltungname = $row["veranstaltungname"];
			$lehrend = $row["lehrend"];
			$pruefungsleistung = $row["pruefungstyp"];
			$pruefungsnummer = $row["pruefungsnummer"];
			$veranstaltungstyp = $row["veranstaltungstyp_name"];
			$tn_zahl = $row["tn"];
			$zeitvorschlag = $row["zeitvorschlag"];
			$raum_gebaeude_vorschlag = get_gebaeude_abkuerzung($row["gebaeude_id"]).' '.get_raum_name_by_id($row["raum_id"]);
			$raumausstattungsvorschlag = $row["raumausstattungsvorschlag"];
			$bemerkung = $row["bemerkung"];
			$institut_id = $row["institut_id"];

			if(is_null($lv_nr) || is_null($last_institut_id) || $last_institut_id != $institut_id) {
				$lv_nr = get_startnr_by_institut($institut_id);
				$last_institut_id = $institut_id;
			}

			$objPHPExcel->getActiveSheet()->SetCellValue($letter.$zeile, $lv_nr);
			$letter++;

			$objPHPExcel->getActiveSheet()->SetCellValue($letter.$zeile, $modulbezeichnung);
			$letter++;

			// Modulbezeichnung im CampusNet (leer)
			$letter++;

			$veranstaltungname = preg_replace("/[\n\r]/", " ", $veranstaltungname);
			$objPHPExcel->getActiveSheet()->SetCellValue($letter.$zeile, $veranstaltungname);
			$letter++;

			// Kurztitel (leer)
			$letter++;

			$objPHPExcel->getActiveSheet()->SetCellValue($letter.$zeile, $lehrend);
			$letter++;

			$objPHPExcel->getActiveSheet()->SetCellValue($letter.$zeile, $lehrend);
			$letter++;

			$objPHPExcel->getActiveSheet()->SetCellValue($letter.$zeile, $pruefungsleistung);
			$letter++;

			$objPHPExcel->getActiveSheet()->SetCellValue($letter.$zeile, $pruefungsnummer);
			$letter++;

			$objPHPExcel->getActiveSheet()->SetCellValue($letter.$zeile, $veranstaltungstyp);
			$letter++;

			// SWS (leer)
			$letter++;

			$objPHPExcel->getActiveSheet()->SetCellValue($letter.$zeile, $tn_zahl);
			$letter++;


			$objPHPExcel->getActiveSheet()->SetCellValue($letter.$zeile, $zeitvorschlag);
			$letter++;

			// Alternativer Zeitvorschlag
			$letter++;

			$objPHPExcel->getActiveSheet()->SetCellValue($letter.$zeile, $raum_gebaeude_vorschlag);
			$letter++;

			// UR/HS (leer)
			$letter++;

			$raumausstattungsvorschlag = preg_replace("/[\n\r]/", " ", $raumausstattungsvorschlag);
			$objPHPExcel->getActiveSheet()->SetCellValue($letter.$zeile, $raumausstattungsvorschlag);
			$letter++;


			$objPHPExcel->getActiveSheet()->SetCellValue($letter.$zeile, $bemerkung);
			$letter++;

			$objPHPExcel->getActiveSheet()->SetCellValue($letter.$zeile, veranstaltung_is_in_studium_generale_or_buerger_universitaet($row["veranstaltung_id"]));
			$letter++;

			$objPHPExcel->getActiveSheet()->SetCellValue($letter.$zeile, veranstaltung_is_in_aqua($row["veranstaltung_id"]));
			$letter++;

			// Schüleruni (nur Do-LV)
			$letter++;


			$objPHPExcel->getActiveSheet()->SetCellValue($letter.$zeile, veranstaltung_is_in_stex($row["veranstaltung_id"]));
			$letter++;


			$objPHPExcel->getActiveSheet()->SetCellValue($letter.$zeile, veranstaltung_is_in_schueler_uni($row["veranstaltung_id"]));
			$letter++;
			$last_letter = $letter;

			$zeile++;
		}

		// Automatisch die Größe jeder Zelle anpassen
		foreach (range('A', $objPHPExcel->getActiveSheet()->getHighestDataColumn()) as $col) {
			$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
		}


		foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {
			$objPHPExcel->setActiveSheetIndex($objPHPExcel->getIndex($worksheet));

			$sheet = $objPHPExcel->getActiveSheet();
			$cellIterator = $sheet->getRowIterator()->current()->getCellIterator();
			$cellIterator->setIterateOnlyExistingCells(true);
			/** @var PHPExcel_Cell $cell */
			foreach ($cellIterator as $cell) {
				$sheet->getColumnDimension($cell->getColumn())->setAutoSize(true);
			}
		}

		foreach($objPHPExcel->getActiveSheet()->getRowDimensions() as $rd) {
			$rd->setRowHeight(-1);
		}

		return $objPHPExcel;
	}


	function raumplanung ($institut = null, $semester, $show_html) {
		function_debug_counter("raumplanung");
		if(is_null($semester) || !$semester) {
			$semester = get_and_create_this_semester();
		}
		if(is_array($semester)) {
			$semester = $semester[0];
		}
		$gebaeude = create_gebaeude_array();

		$query = 'SELECT '.
			# 0	1	2		3		4		5					6	7	    8		9
			'`id`, `name`, `dozent_name`, `wunsch`, `anzahl_hoerer`, date_format(`erster_termin`, "%d.%m.%Y"), `wochentag`, `stunde`, `woche`, `abgabe_pruefungsleistungen`, '.
			# 10			11			12	13		14			15				# 16
			'`gebaeudewunsch_id`, `raumwunsch_id`, `gebaeude_id`, `raum_id`, `veranstaltungstyp_name`, `veranstaltungstyp_abkuerzung`, `institut_id`, '.
			#		17				18	19		20
			'`raummeldung`, `wochentag` + 0 as `wochentag_name`, `semester_id`, `dozent_id` FROM `view_veranstaltung_raumplanung` WHERE `semester_id` = '.esc($semester);

		if($institut) {
			$query .= ' AND `institut_id` = '.esc($institut);
		}

		$query .= ' ORDER BY `institut_name` ASC, `wochentag_name` ASC, `stunde` ASC, `woche` ASC, `name` ASC, `dozent_name` ASC';
		$result = rquery($query);

		$raum_name = create_raum_name_id_array();
		$gebaeude_abkuerzung_id = create_gebaeude_abkuerzung_id_array();

		$start_nr = 0;
		$reihen = array();
		$institut_id = null;
		$number_of_cols = 0;
		$ids_array = array();
		$minicache = array('gebaeude_abkuerzung' => array(), 'raumnummer' => array());

		$has_printed_rows = 0;

		$data = array();

		$dozenten_barrierefrei_ids = array();

		while ($row = mysqli_fetch_row($result)) {
			$data[] = $row;
			$dozenten_barrierefrei_ids[] = $row[20];
		}

		$dozenten_barrierefrei = user_braucht_barrierefreien_zugang($dozenten_barrierefrei_ids);

		$typen_array = array();

		foreach ($data as $row) {
			if(!$institut_id || $institut_id != $row[16]) {
				$institut_id = $row[16];
				$start_nr = get_startnr_by_institut($institut_id);
				$reihen[] = 'Institut: '.htmlentities(get_institut_name($institut_id));
			}
			$gebaeude_abkuerzung = '';
			if(array_key_exists($row[10], $minicache['gebaeude_abkuerzung'])) {
				$gebaeude_abkuerzung = $minicache['gebaeude_abkuerzung'][$row[10]];
			} else {
				if(array_key_exists($row[10], $gebaeude_abkuerzung_id)) {
					$gebaeude_abkuerzung = $gebaeude_abkuerzung_id[$row[10]];
					$minicache['gebaeude_abkuerzung'][$row[10]] = $gebaeude_abkuerzung;
				}
			}

			$raumnummer = '';
			if(array_key_exists($row[11], $minicache['raumnummer'])) {
				$raumnummer = $minicache['raumnummer'][$row[11]];
			} else {
				if(array_key_exists($row[11], $raum_name)) {
					$raumnummer = $raum_name[$row[11]];
					$minicache['raumnummer'][$row[11]] = $raumnummer;
				}
			}
			if(!preg_match('/barrierefrei/i', $row[3])) {
				if(array_key_exists($row[20], $dozenten_barrierefrei) && $dozenten_barrierefrei[$row[20]]) {
					if($row[3]) {
						$row[3] = "Barrierefrei, $row[3]";
					} else {
						$row[3] = "Barrierefrei";
					}
				}
			}
			$reihe_array = array(
				$start_nr,
				$row[1],	# name
				$row[2],	# dozent_name
				$row[6],	# wochentag
				$row[7],	# stunde
				$row[5],	# erster_termin
				$row[8],	# woche
				$row[4],	# anzahl_hoerer
				$gebaeude_abkuerzung,
				$raumnummer,
				$row[3],	# spezielle wünsche
				$row[12],	# gebäude-id
				$row[13],	# raum-id
				$row[17]	# raummeldung
			);

			$typen_array[] = $row[15];

			$ids_array[] = $row[0];
			if(!$number_of_cols) {
				$number_of_cols = count($reihe_array);
			}
			$reihen[] = $reihe_array;
			$start_nr++;
		}

		$header = array(	
			'LV-Nr.',			# 0
			'LV-Typ',			# 1
			'Dozent',			# 2
			'Wochentag',			# 3
			'Stunde',			# 4
			'Erster Termin',		# 5
			'Woche',			# 6
			'Geschätzte Anzahl Hörer',	# 7
			'Gebäudewunsch',		# 8
			'Raumwunsch',			# 9
			'Hinweis für Raumplanung',	# 10
			'Bestätigtes Gebäude',		# 11
			'Bestätigter Raum',		# 12
			'Raummeldung'#,			# 13
			#'Speichern'			# 14
		);

		$gebaeude = create_gebaeude_abkuerzungen_array();

		if($show_html) {
			$number_of_cols++; // Wegen "Speichern"-Menü
			$number_of_cols++; // Wegen LV-Typ
			$header[3] = 'Wo&shy;chen&shy;tag';
			$header[4] = 'Stun&shy;de';
			$header[7] = 'Ge&shy;schä&shy;tzte An&shy;zahl Hörer';
			$header[8] = 'Ge&shy;bäu&shy;de&shy;wunsch';
			$header[9] = 'Raum&shy;wunsch';

			if($number_of_cols && count($reihen)) {
				$has_printed_rows = 1;
?>
				<table class='raumplanungtable'>
					<tr>
<?php
				$k = 0;
				foreach ($header as $this_head) {
					print "<th>".$this_head."</th>\n";
					if($k == 1) {
						print "<th>Name</th>\n";
					}
					$k++;
				}
?>
					</tr>
<?php
				$user_can_edit = check_this_user_role(array(1, 3));
				$nr = $start_nr;
				$i = 0;
				foreach ($reihen as $this_reihe) {
					if(is_array($this_reihe)) {
?>
						<form method="post" enctype="multipart/form-data" action="admin.php?page=<?php print $GLOBALS['this_page_number']; ?>&institut=<?php print htmlentities(get_get('institut')); ?>&semester=<?php print htmlentities($semester); ?>">
						<tr>
<?php
						$j = 0;
						foreach ($this_reihe as $this_cell) {
							if($j == 0) { // ID
								print "<input type='hidden' value='raumplanung_bearbeiten' name='raumplanung_bearbeiten' />\n";
								print "<input type='hidden' value='".htmlentities($ids_array[$i])."' name='id' />\n";
							}

							$updated = '';
							if($j == 1) {
								if(veranstaltung_raumplanungsrelevante_daten_geupdatet($ids_array[$i])) {
									$updated = '<span class="largelightning">&#9889;</span>';
								}
							}


							if($j == 1) {
								print "<td>".htmle($typen_array[$i])."</td>"; # TODO
							}

							if($j == 13 ) { // Raummeldung
								if($user_can_edit == 1) {
									print "<td><input placeholder='raummeldung' class='datepicker' type='text' value='".htmlentities($this_cell)."' name='meldungsdatum' /></td>";
									#print "<td><input type='submit' value='Speichern' /></td>";
								} else {
									print "<td>".htmle($this_cell)."</td>\n"."<td>&mdash;</td>\n";
								}
							} else if($j == 12) { // Bestätigter Raum
								if($user_can_edit == 1) {
									print "<td><input type='text' placeholder='raum' name='raum' value='".(isset($this_cell) ? htmlentities($raum_name[$this_cell]) : '')."' /></td>\n";
								} else {
									print "<td>".(isset($this_cell) ? htmlentities($raum_name[$this_cell]) : '')."</td>\n";
								}
							} else if($j == 11) { // Bestätigtes Gebäude
								if($user_can_edit == 1) {
									print "<td>";
									create_select($gebaeude, $this_cell, 'gebaeude', 1);
									print "</td>\n";
								} else {
									print "<td>".get_gebaeude_abkuerzung($this_cell)."</td>\n";
								}
							} else {
								print "<td>$updated".((isset($this_cell) && !preg_match('/^\s+$/', $this_cell)) ? htmlentities($this_cell) : '&mdash;')."</td>\n";
							}
							$j++;
						}
?>
								</tr>
							</form>
<?php
					} else {
?>
								<tr>
<?php
						print "<td class='c5e3ed_background' colspan='$number_of_cols'>$this_reihe</td>\n";
?>
								</tr>
<?php
						$i--;
					}
					$nr++;
					$i++;
				}
?>
				</table>
<?php
				if(!$user_can_edit) {
					print "<br /><i>Einige Spalten sind ausgeblendet worden, weil Sie nicht über die nötige Berechtigung besitzen, diese zu ändern.</i>";
				}
			} else {
				print "<i>Für dieses Semester sind noch keine Daten vorhanden.</i>";
			}
		} else {
			include_once 'Classes/PHPExcel.php';
			$objPHPExcel = new PHPExcel();

			$objPHPExcel->getProperties()->setCreator(htmlentities($GLOBALS['logged_in_data'][1]));
			$objPHPExcel->getProperties()->setTitle("Raumbelegung");
			$objPHPExcel->setActiveSheetIndex(0);
			$number = 1;
			$letter = 'A';

			$objPHPExcel->getActiveSheet()->SetCellValue($letter.$number, "Raumplanung");

			$number++;

			foreach ($header as $this_head) {
				if($this_head != "Speichern") {
					$objPHPExcel->getActiveSheet()->SetCellValue($letter.$number, $this_head);
					$letter++;
				}
			}
			$number++;

			$r_i = 0;

			$to_merge = array(1);
			foreach ($reihen as $this_reihe) {
				$letter = 'A';
				if(is_array($this_reihe)) {
					// Zeilen, die mehr beinhalten als nur die Institutsnamen
					foreach ($this_reihe as $this_cell) {
						$has_printed_rows = 1;
						$cell_id = $letter.$number;
						if($letter == 'L') { // Gebäude
							$objPHPExcel->getActiveSheet()->SetCellValue($cell_id, (($this_cell && !preg_match('/^\s+$/', $this_cell)) ? $gebaeude_abkuerzung_id[$this_cell] : '—'));
						} else if($letter == 'M') { // Raum
							$objPHPExcel->getActiveSheet()->SetCellValue($cell_id, (($this_cell && !preg_match('/^\s+$/', $this_cell)) ? get_raum_name_by_id($this_cell) : '—'));
						} else if($letter == 'B') { // Name: Typ hinzufügen, daher dieses if
							if(array_key_exists($r_i, $typen_array) && defined($typen_array[$r_i])) {
								$this_cell = "$typen_array[$r_i]: $this_cell";
							}
							$objPHPExcel->getActiveSheet()->SetCellValue($cell_id, (($this_cell && !preg_match('/^\s+$/', $this_cell)) ? $this_cell : '—'));
						} else if($letter == 'N') { // Raummeldung
							if(preg_match('/^([2-9])\d\d\d-\d\d-\d\d$/', $this_cell)) {
								$this_cell = date_format(date_create($this_cell), 'd.m.Y');
							} else {
								$this_cell = '—';
							}
							$objPHPExcel->getActiveSheet()->SetCellValue($cell_id, $this_cell);
						} else {
							$objPHPExcel->getActiveSheet()->SetCellValue($cell_id, (($this_cell && !preg_match('/^\s+$/', $this_cell)) ? $this_cell : '—'));
						}

						if($number % 2) {
							cellColor($objPHPExcel, $cell_id, 'ededed');
						} else {
							cellColor($objPHPExcel, $cell_id, 'F5F5F5');
						}

						$objPHPExcel->getActiveSheet()->getStyle($cell_id)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
						$letter++;
					}
					$r_i++;
				} else {
					// Institute-Zeilen
					$cell_id = $letter.$number;
					$to_merge[] = $number;
					$objPHPExcel->getActiveSheet()->SetCellValue($cell_id, $this_reihe);
					$r_i++;
				}
				$number++;
			}

			$styleArray = array(
				'borders' => array(
					'allborders' => array(
						'style' => PHPExcel_Style_Border::BORDER_THIN
					)
				)
			);

			foreach (range(1, $number - 1) as $tnumber) {
				$objPHPExcel->getActiveSheet()->getStyle('A1:'.get_previous_letter($letter).$tnumber)->applyFromArray($styleArray);
			}

			// Einfärben

			$letter = get_previous_letter($letter);
			foreach ($to_merge as $line) {
				$objPHPExcel->getActiveSheet()->mergeCells('A'.$line.':'.$letter.$line);
				cellColor($objPHPExcel, 'A'.$line, 'd6ebf2');
			}
			cellColor($objPHPExcel, 'A1', '99acbe');
			cellColor($objPHPExcel, 'A2:'.$letter.'2', 'c9dae9');

			// Automatisch die Größe jeder Zelle anpassen
			foreach (range('A', $objPHPExcel->getActiveSheet()->getHighestDataColumn()) as $col) {
				$objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
			} 

			return $objPHPExcel;
		}

		return $has_printed_rows;
	}

	function pruefung_symbole ($pn) {
		function_debug_counter("pruefung_symbole");
		$str = '';
		$str .= checked_if_pruefung_already_done($pn);
		$str .= chosen_if_pruefung_chosen($pn);
		return $str;
	}

	function pruefung_already_done ($pn) {
		function_debug_counter("pruefung_already_done");
		if($pn && array_search($pn, $GLOBALS['pruefungen_already_done']) !== false) {
			return 1;
		} else {
			return 0;
		}
	}

	function pruefung_already_chosen ($pn) {
		function_debug_counter("pruefung_already_chosen");
		if($pn && array_search($pn, $GLOBALS['pruefungen_already_chosen']) !== false) {
			return 1;
		} else {
			return 0;
		}
	}

	function chosen_if_pruefung_chosen ($pn) {
		function_debug_counter("chosen_if_pruefung_chosen");
		if(pruefung_already_chosen($pn)) {
			return html_chosen();
		}
	}

	function checked_if_pruefung_already_done ($pn) {
		function_debug_counter("checked_if_pruefung_already_done");
		if(pruefung_already_done($pn)) {
			return html_checked();
		}
	}

	/* Füllhilfsfunktionen */

	function get_cached ($url, $return_filename = 0) {
		function_debug_counter("get_cached");
		$md5 = hash('md5', $url);
		$cache_dir = '/tmp/php_get_cache';
		$cache_file = $cache_dir.'/'.$md5;
		if(!file_exists($cache_dir) || !is_dir($cache_dir)) {
			print "`$cache_dir` existiert nicht. Erstelle es neu...";
			if (!mkdir($cache_dir)) {
				$error_message = error_get_last();
				print htmlentities($error_message['message']);
			} 
		}

		$return = '';

		if(file_exists($cache_file)) {
			print "Konnte get_cached(`$url`) aus dem Cache beantworten: `$cache_file`\n";;
			$return = file_get_contents($url);
		} else {
			$return = file_get_contents($url);
			file_put_contents($cache_file, $return);
		}

		if($return_filename) {
			return $cache_file;
		} else {
			return $return;
		}
	}

	function add_leading_zero ($v) {
		function_debug_counter("add_leading_zero");
		if(strlen($v) < 2) {
			return "0$v";
		} else {
			return $v;
		}
	}

	function add_next_year_to_wintersemester ($semestertype, $year) {
		function_debug_counter("add_next_year_to_wintersemester");
		if(preg_match('/^\d+(\/\d+)?$/', $semestertype)) {
			$tmp = $year;
			$year = $semestertype;
			$semestertype = $tmp;
		}

		// Ziemlich schmutziger Hack; irgendwo wird das nächste Jahr bereits eingetragen, aber ich finde es nicht...
		$year = preg_replace('/\/.*$/', '', $year);

		if($semestertype == 'Wintersemester') {
			$next_year = $year + 1;
			return "$semestertype $year/$next_year";
		} else {
			return "$semestertype $year";
		}	
	}

	function get_institut_id_by_veranstaltung_id ($veranstaltung_id) {
		function_debug_counter("get_institut_id_by_veranstaltung_id");
		$query = 'SELECT `v`.`institut_id` FROM `veranstaltung` `v` LEFT JOIN `institut` `i` ON `i`.`id` = `v`.`institut_id` WHERE `v`.`id` = '.esc($veranstaltung_id);
		return get_single_row_from_query($query, '');
	}

	function get_checked_pruefungsnummern ($veranstaltung_id) {
		function_debug_counter("get_checked_pruefungsnummern");
		$query = 'SELECT `p`.`pruefungsnummer_id`, `pn`.`modul_id` FROM `pruefung` `p` LEFT JOIN `pruefungsnummer` `pn` ON `p`.`pruefungsnummer_id` = `pn`.`id` WHERE `p`.`veranstaltung_id` = '.esc($veranstaltung_id);
		$result = rquery($query);

		$r = array();

		while ($row = mysqli_fetch_row($result)) {
			if(!array_key_exists($row[0], $r)) {
				$r[$row[0]] = array();
			}
			$r[$row[0]][$row[1]] = 1;
		}

		return $r;
	}

	function pruefungsnummer_is_checked ($pruefungsnummer, $modul, $veranstaltung_id) {
		function_debug_counter("pruefungsnummer_is_checked");
		$query = 'SELECT count(*) FROM `pruefung` `p` LEFT JOIN `pruefungsnummer` `pn` ON `p`.`pruefungsnummer_id` = `pn`.`id` WHERE `pn`.`modul_id` = '.esc($modul).' and `p`.`pruefungsnummer_id` = '.esc($pruefungsnummer).' and `p`.`veranstaltung_id` = '.esc($veranstaltung_id);

		return get_single_row_from_query($query, 0);
	}

	function get_page_id_by_filename ($file) {
		function_debug_counter("get_page_id_by_filename");
		if(is_null($file) || !$file) {
			return null;
		}

		$key = "get_page_id_by_filename($file)";
		if(array_key_exists($key, $GLOBALS['memoize'])) {
			return $GLOBALS['memoize'][$key];
		}

		$return = null;

		// Falls $file = aktuelle Seite, dann einfach &page=... zurückgeben
		if(get_get('page') && get_page_file_by_id(get_get('page')) == $file) {
			$return = get_get('page');
		} else {
			$query = 'SELECT `id` FROM `page` WHERE `file` = '.esc($file);
			$result = rquery($query);

			$return = '';

			while ($row = mysqli_fetch_row($result)) {
				$return = $row[0];
			}
		}

		$GLOBALS['memoize'][$key] = $return;

		return $return;
	}

	function get_startnr_by_institut ($id) {
		function_debug_counter("get_startnr_by_institut");
		$query = 'SELECT `start_nr` FROM `institut` WHERE `id` = '.esc($id);
		return get_single_row_from_query($query, '');
	}

	/* Rechteverwaltung */

	function easter_egg ($name) {
		function_debug_counter("easter_egg");
		$found = array();
		if(preg_match('/(sex|fuck|porn|cunt|ass|arsch|anal|shit)/i', $name, $found)) {
			show_easter_egg('<a href="https://de.wikipedia.org/wiki/Infantilismus">Haha, im Titel der Veranstaltung kommt das Wort &raquo;'.htmlentities(ucwords(strtolower($found[0]))).'&laquo; vor!</a>');
		}
	}

	function update_user_agent_counter () {
		/*
		function_debug_counter("update_user_agent_counter");
		if(isset($GLOBALS['logged_in_user_id'])) {
			return;
		}
		include_once('ua/PHP-UA-parser.php');
		if(isset($_SERVER['REMOTE_ADDR'])) {
			$browser_name = $GLOBALS['parsed_UA']['browser_name'];
			$browser_vers = $GLOBALS['parsed_UA']['browser_vers'];
			$platfrm_name = $GLOBALS['parsed_UA']['platfrm_name'];
			$platfrm_vers = $GLOBALS['parsed_UA']['platfrm_vers'];

			$browser_id = get_and_create_ua_browser($browser_name, $browser_vers);
			$os_id = get_and_create_ua_os($platfrm_name, $platfrm_vers);

			update_ua_call($os_id, $browser_id);
		}
		 */
	}

	function update_ua_call($os_id, $browser_id) {
		function_debug_counter("update_ua_call");
		$year = date("Y");
		$month = date("m");
		$day = date("d");

		$query = 'INSERT INTO `ua_call` (`specific_os_id`, `specific_browser_id`, `c`, `month`, `year`, `day`) VALUES ('.esc($os_id).', '.esc($browser_id).', 1, '.esc($month).', '.esc($year).', '.esc($day).') ON DUPLICATE KEY UPDATE `c` = `c` + 1';
		rquery($query);
	}

	function get_and_create_ua_os ($os_name, $os_vers) {
		function_debug_counter("get_and_create_ua_os");
		$os_id = null;
		$spec_os_id = null;

		if(!$os_vers || $os_vers == "Unknown") {
			$os_vers = "n/a";
		}

		if(!$os_name || $os_name == "Unknown") {
			$os_name = "n/a";
		}

		if(strlen($os_vers) > 100) {
			$os_vers = substr($os_vers, 0, 100);
		}

		if(strlen($os_name) > 100) {
			$os_name = substr($os_name, 0, 100);
		}

		$query = 'SELECT `id` FROM `ua_os` WHERE `name` = '.esc($os_name);
		$result = rquery($query);
		if(mysqli_num_rows($result)) {
			while ($row = mysqli_fetch_row($result)) {
				$os_id = $row[0];
			}

			$query = 'SELECT `id` FROM `ua_specific_os` WHERE `name_id` = '.esc($os_id).' AND `version` = '.esc($os_vers);
			$result = rquery($query);

			if(mysqli_num_rows($result)) {
				while ($row = mysqli_fetch_row($result)) {
					$spec_os_id = $row[0];
				}
			} else {
				$query = 'INSERT INTO `ua_specific_os` (`name_id`, `version`) VALUES ('.esc($os_id).', '.esc($os_vers).')';
				rquery($query);
				return get_and_create_ua_os($os_name, $os_vers);			
			}
		} else {
			$query = 'INSERT INTO `ua_os` (`name`) VALUES ('.esc($os_name).')';
			rquery($query);
			return get_and_create_ua_os($os_name, $os_vers);
		}

		return $spec_os_id;
	}

	function get_and_create_ua_browser ($browser_name, $browser_vers) {
		function_debug_counter("get_and_create_ua_browser");
		$browser_id = null;
		$spec_browser_id = null;

		if(!$browser_vers || $browser_vers == "Unknown") {
			$browser_vers = "n/a";
		}

		if(!$browser_name || $browser_name == "Unknown") {
			$browser_name = "n/a";
		}

		if(strlen($browser_name) > 100) {
			$browser_name = substr($browser_name, 0, 100);
		}

		if(strlen($browser_vers) > 100) {
			$browser_vers = substr($browser_vers, 0, 100);
		}

		$query = 'SELECT `id` FROM `ua_browser` WHERE `name` = '.esc($browser_name);
		$result = rquery($query);
		if(mysqli_num_rows($result)) {
			while ($row = mysqli_fetch_row($result)) {
				$browser_id = $row[0];
			}

			$query = 'SELECT `id` FROM `ua_specific_browser` WHERE `name_id` = '.esc($browser_id).' AND `version` = '.esc($browser_vers);
			$result = rquery($query);

			if(mysqli_num_rows($result)) {
				while ($row = mysqli_fetch_row($result)) {
					$spec_browser_id = $row[0];
				}
			} else {
				$query = 'INSERT INTO `ua_specific_browser` (`name_id`, `version`) VALUES ('.esc($browser_id).', '.esc($browser_vers).')';
				rquery($query);
				return get_and_create_ua_browser($browser_name, $browser_vers);			
			}
		} else {
			$query = 'INSERT INTO `ua_browser` (`name`) VALUES ('.esc($browser_name).')';
			rquery($query);
			return get_and_create_ua_browser($browser_name, $browser_vers);
		}

		return $spec_browser_id;
	}

	/* Zuordnungsfunktionen */

	function assign_page_to_role ($role_id, $page_id) {
		function_debug_counter("assign_page_to_role");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'INSERT IGNORE INTO `role_to_page` (`role_id`, `page_id`) VALUES ('.esc($role_id).', '.esc($page_id).')';
		$result = rquery($query);
		if($result) {
			success("Die Seite wurde erfolgreich zur Rolle hinzugefügt.");
			if($GLOBALS['user_role_id'] == $role_id) {
				$GLOBALS['reload_page'] = 1;
			}
		} else {
			error("Die Seite konnte nicht zur Rolle hinzugefügt werden.");
		}
	}

	function update_language ($id, $name, $abkuerzung) {
		function_debug_counter("update_language");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'update `'.$GLOBALS['dbname'].'`.`language` SET `name` = '.esc($name).', `abkuerzung` = '.esc($abkuerzung).' WHERE `id` = '.esc($id);
		$result = rquery($query);
		if($result) {
			success("Die Sprache wurde erfolgreich geupdated.");
		} else {
			error("Die Sprache konnte nicht editiert werden.");
		}
	}

	function update_semester($id, $erster_termin) {
		function_debug_counter("update_semester");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'update `'.$GLOBALS['dbname'].'`.`semester` SET `erste_veranstaltung_default` = '.esc($erster_termin).' WHERE `id` = '.esc($id);
		$result = rquery($query);
		if($result) {
			success("Das Erste-Termin-Datum wurde erfolgreich zum Semester hinzugefügt.");
		} else {
			error("Das Erste-Termin-Datum konnte nicht zum Semester hinzugefügt werden.");
		}
	}

	function update_dozent_titel ($dozent_id, $titel_id) {
		function_debug_counter("update_dozent_titel");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'update `'.$GLOBALS['dbname'].'`.`dozent` SET `titel_id` = '.esc($titel_id).' WHERE `id` = '.esc($dozent_id);
		$result = rquery($query);
		if($result) {
			success("Der Titel wurde erfolgreich zum Dozenten hinzugefügt.");
		} else {
			error("Die Titel konnte nicht zum Dozenten hinzugefügt werden.");
		}
	}

	function update_user_role ($user_id, $role_id) {
		function_debug_counter("update_user_role");
		if(!check_function_rights(__FUNCTION__)) { return; }
		$query = 'INSERT IGNORE INTO `role_to_user` (`role_id`, `user_id`) VALUES ('.esc($role_id).', '.esc($user_id).') ON DUPLICATE KEY UPDATE `role_id` = VALUES(`role_id`)';
		$result = rquery($query);
		if($result) {
			success("Die Rolle wurde erfolgreich zum User hinzugefügt.");
		} else {
			error("Die Rolle konnte nicht zum User hinzugefügt werden.");
		}
	}

	function create_hour_from_to ($from, $to, $array = 0) {
		function_debug_counter("create_hour_from_to");
		$re = '/^\d+$/';
		if(preg_match($re, $from) && preg_match($re, $to)) {
			$times = array(
				0 => array("from" => "05:40", "to" => "07:10"),
				1 => array("from" => "07:30", "to" => "09:00"),
				2 => array("from" => "09:20", "to" => "10:50"),
				3 => array("from" => "11:10", "to" => "12:40"),
				4 => array("from" => "13:00", "to" => "14:30"),
				5 => array("from" => "14:50", "to" => "16:20"),
				6 => array("from" => "16:40", "to" => "18:10"),
				7 => array("from" => "18:30", "to" => "20:00"),
				8 => array("from" => "20:20", "to" => "21:50"),
				9 => array("from" => "22:10", "to" => "23:40")
			);

			if(array_key_exists($from, $times) && array_key_exists($to, $times)) {
				$from_time = $times[$from]['from'];
				$to_time = $times[$to]['to'];

				if($array) {
					return array($from_time, $to_time);
				} else {
					return "$from_time &mdash; $to_time";
				}
			} else {
				return null;
			}
		} else {
			return null;
		}
	}

	/* Systemfunktionen */

	function stderrw ($str) {
		function_debug_counter("stderrw");
		trigger_error($str, E_USER_WARNING);
	}

	function green_text ($str) {
		function_debug_counter("green_text");
		return "\033[32m".$str."\033[0m";
	}

	function red_text ($str) {
		function_debug_counter("red_text");
		return "\033[31m".$str."\033[0m";
	}

	function print_debug ($str) {
		function_debug_counter("print_debug");
		print green_text($str);
	}

	function insert_values ($database, $columns, $data, $print = 0) {
		function_debug_counter("insert_values");
		if($database) {
			if(is_array($columns)) {
				if(is_array($data)) {
					stderrw("Befülle `$database`\n");
					$query = 'DELETE FROM `'.$database.'`'."\n";
					if($print) {
						print green_text($query);
					}
					rquery($query);
					$base_query = 'INSERT IGNORE INTO `'.$database.'` ('.join(', ', $columns).') VALUES (';
					foreach ($data as $this_data) {
						$query = $base_query;

						if(is_array($this_data)) {
							foreach ($this_data as $this_data_key => $this_data_data) {
								$query .= esc($this_data_key).', '.esc($this_data_data);
							}

							$query = preg_replace('/,\s*$/', '', $query);
						} else {
							$query .= esc($this_data);
						}
						$query .= ')';
						$result = rquery($query);
						if($print) {
							print green_text($query)."\n";
							if($result) {
								print green_text("Ok\n");
							} else {
								print red_text("Warning\n");
							}
						}
					}
				} else {
					die("\$data muss ein Array sein! ($database)");
				}
			} else {
				die("\$columns muss ein Array sein! ($database)");
			}
		} else {
			die("Datenbank muss definiert werden!");
		}
	}

	function run_install_query ($database, $queries_data, $print = 0) {
		function_debug_counter("run_install_query");
		if($database) {
			if(is_array($queries_data) && count($queries_data)) {
				stderrw("Befülle `$database`");
				$query = 'DELETE FROM `'.$database.'`'."\n";
				if($print) {
					print green_text($query);
				}
				rquery($query);
				foreach ($queries_data as $query) {
					if($print) {
						print green_text($query)."\n";
					}
					$result = rquery($query);
					if($print) {
						if($result) {
							print green_text("Ok\n");
						} else {
							print red_text("Warning\n");
						}
					}
				}
			} else {
				die("\$queries_data muss ein Array sein");
			}
		} else {
			die("\$database muss definiert sein");
		}
	}

	function referrer_from_same_domain () {
		function_debug_counter("referrer_from_same_domain");
		if(isset($_SERVER['HTTP_REFERER'])) {
			$referer = $_SERVER['HTTP_REFERER'];
			$referer_host = parse_url($referer, PHP_URL_HOST);
			$referer_path = parse_url($referer, PHP_URL_PATH);

			$referer_url = $referer_host.$referer_path;

			$this_host = $_SERVER['HTTP_HOST'];
			$this_path = $_SERVER['SCRIPT_NAME'];

			$this_url = $this_host.$this_path;
			if($this_url == $referer_url) {
				return 1;
			} else {
				return 0;
			}
		} else {
			return 0;
		}
	}

	function sanitize_data ($data, $recursion = 0) {
		function_debug_counter("sanitize_data");
		if($recursion == 300) {
			die("ERROR: Deep-Recursion! Bitte melden Sie dies dem Administrator.");
		}

		if(is_array($data)) {
			foreach ($data as $te => $val) {
				$data[$te] = sanitize_data($val, $recursion + 1);
			}

			return $data;
		} else {
			return htmlentities($data);
		}
	}

	function get_sum_credit_points_anzahl_pruefungsleistungen_for_studiengang ($studiengang_id) {
		function_debug_counter("get_sum_credit_points_anzahl_pruefungsleistungen_for_studiengang");
		$query = "select sum(credit_points) as sum_credit_points, sum(anzahl_pruefungsleistungen) as sum_anzahl_pruefungsleistungen from modul_nach_semester_metadata where modul_id in (select id from modul where studiengang_id = ".esc($studiengang_id).")";

		$result = rquery($query);
		$data = array();
		while ($row = mysqli_fetch_row($result)) {
			$data = $row;
		}
		return $data;
	}

	function create_event_file ($veranstaltungen) {
		function_debug_counter("create_event_file");
		$str = "BEGIN:VCALENDAR\n";
		$str .= "PRODID:-".$GLOBALS['university_name']."//Vorlesungsverzeichnis//DE\n";
		$str .= "VERSION:2.0\n";
		$str .= "CALSCALE:GREGORIAN\n";
		$str .= "METHOD:PUBLISH\n";
		$str .= "CHARSET:utf8\n";
		$str .= "X-WR-CALNAME: ".$GLOBALS["calname"]."\n";
		$str .= "TZID:".$GLOBALS['timezone_name']."\n";
		$str .= "X-LIC-LOCATION:".$GLOBALS['timezone_name']."\n";

		foreach ($veranstaltungen as $this_veranstaltung) {
			$veranstaltung_name = get_veranstaltungsname_by_id($this_veranstaltung);
			if($veranstaltung_name) {
				$veranstaltung_typ = get_veranstaltungsabkuerzung_by_id(get_typid_by_veranstaltung_id($this_veranstaltung));
				$dozent = get_dozent_name(get_dozent_id_by_veranstaltung_id($this_veranstaltung));

				$location = get_veranstaltung_location($this_veranstaltung);
				$location_string = '';
				if(isset($location['gebaeude_abkuerzung'])) {
					$location_string = $location['gebaeude_abkuerzung'];
					if(isset($location['raum_name'])) {
						$location_string .= ', '.$location['raum_name'];
					}
				}

				$veranstaltung_summary = "$veranstaltung_typ, $veranstaltung_name";

				$v_semester = get_semester_from_veranstaltung_id($this_veranstaltung);
				$semester_data = get_semester_begin_and_end($v_semester["year"], $v_semester["type"]);

				$erste_veranstaltung = get_erste_veranstaltung($this_veranstaltung, $v_semester);

				if(is_null($erste_veranstaltung)) {
					$erste_veranstaltung = $semester_data['start'];
				}
				$letzte_veranstaltung = $semester_data['end'];

				$veranstaltung_stunde = get_veranstaltung_stunde($this_veranstaltung);

				$stunde_zeiten = get_zeiten($veranstaltung_stunde, 1);

				$tag_from_veranstaltung = get_tag_from_veranstaltung_id($this_veranstaltung);
				$day_short = wochentag_to_weekday($tag_from_veranstaltung)[0];
				$day = wochentag_to_weekday($tag_from_veranstaltung)[1];

				$erster_termin_datum_object = strtotime($erste_veranstaltung);
				$string = "first $day ".date('Y', $erster_termin_datum_object)."-".date('m', $erster_termin_datum_object);
				$erster_termin = date("Y-m-d", strtotime($string));

				$erster_termin = preg_replace('/-/', '', $erster_termin);
				$stunde_zeiten_start = preg_replace('/:/', '', $stunde_zeiten[0]);
				$stunde_zeiten_end = preg_replace('/:/', '', $stunde_zeiten[1]);

				$dtstart = $erster_termin.'T'.$stunde_zeiten_start.'00';

				$letzter_termin_object = strtotime($letzte_veranstaltung);
				$string2 = "last $day ".date('Y', $letzter_termin_object)."-".date('m', $letzter_termin_object);
				$letzter_termin_object = date("Y-m-d", strtotime($string2));
				$letzte_veranstaltung = preg_replace('/-/', '', $letzter_termin_object);
				$dtend = $erster_termin.'T'.$stunde_zeiten_end.'00';
				$real_dtend = $letzte_veranstaltung.'T'.$stunde_zeiten_end.'00';

				$interval = 1;
				$woche = get_woche_from_veranstaltung_id($this_veranstaltung);

				$geocoords = get_gebaeude_geo_coords_by_id($location['gebaeude_id']);
				if(isset($geocoords[0])) {
					$geostring = $geocoords[0].';'.$geocoords[1];
				}

				$str .= "BEGIN:VEVENT\n";
				$str .= "SUMMARY:".$veranstaltung_summary."\n";
				$str .= "DESCRIPTION:$dozent, $woche\n";
				if($geostring) {
					$str .= "GEO:$geostring\n";
				}
				$str .= "RRULE:FREQ=WEEKLY;";
				if($day_short != 'BS') {
					$str .= "BYDAY=".strtoupper($day_short).';';
				}
				$str .= "INTERVAL=".$interval.";UNTIL=$real_dtend\n";
				$str .= "DTSTAMP:".date('Ymd')."T".date('Hi')."00\n";
				$str .= "UID:$this_veranstaltung\n";
				$str .= "STATUS:CONFIRMED\n";
				$str .= "DTSTART:$dtstart\n";
				$str .= "DTEND:$dtend\n";

				if($location) {
					$str .= "LOCATION:$location_string\n";
				}

				$str .= "TRANSP:OPAQUE\n";
				$str .= "END:VEVENT\n";
			}
		}

		$str .= "END:VCALENDAR\n";
		return $str;
	}

	function get_veranstaltung_location ($id) {
		function_debug_counter("get_veranstaltung_location");
		$query = 'select v.gebaeude_id, v.raum_id, g.name, r.raumnummer, g.abkuerzung from veranstaltung v join gebaeude g on g.id = v.gebaeude_id join raum r on r.id = v.raum_id where v.id = '.esc($id);
		$result = rquery($query);

		$location = array();

		while ($row = mysqli_fetch_row($result)) {
			$location['gebaeude_id'] = $row[0];
			$location['raum_id'] = $row[1];
			$location['gebaeude_name'] = $row[2];
			$location['raum_name'] = $row[3];
			$location['gebaeude_abkuerzung'] = $row[4];
		}

		return $location;
	}

	function get_erste_veranstaltung ($id, $semester_data) {
		function_debug_counter("get_erste_veranstaltung");
		$query = 'select erster_termin from veranstaltung_metadaten where veranstaltung_id = '.esc($id);

		$res = get_single_row_from_query($query);
		if($res) {
			return $res;
		} else {
			$query = 'select erste_veranstaltung_default from semester where id = '.esc($semester_data['id']);
			$res = get_single_row_from_query($query);
			return $res;
		}
	}

	function get_typid_by_veranstaltung_id ($id) {
		function_debug_counter("get_typid_by_veranstaltung_id");
		$query = 'select veranstaltungstyp_id from veranstaltung where id = '.esc($id);
		$result = rquery($query);

		$id = '';
		while ($row = mysqli_fetch_row($result)) {
			$id = $row[0];
		}

		return $id;
	}

	function get_semester_begin_and_end ($year, $type) {
		function_debug_counter("get_semester_begin_and_end");
		# Wise: 01.10.2018 -- 31.03.2019
		# Sose: 01.04.2019 -- 30.09.2019

		if($type == 'Wintersemester') {
			return array('start' => "$year-10-01", 'end' => ($year + 1)."-03-31");
		} else if ($type == 'Sommersemester')  {
			return array('start' => "$year-04-01", 'end' => "$year-09-30");
		} else {
			die("Wrong type `".htmlentities($type)."`!");
		}
	}

	function get_semester_from_veranstaltung_id ($id) {
		function_debug_counter("get_semester_from_veranstaltung_id");
		$query = 'select v.semester_id, s.jahr, s.typ from veranstaltung v join semester s on s.id = v.semester_id where v.id = '.esc($id);
		$result = rquery($query);
		$semester = array();

		while ($row = mysqli_fetch_row($result)) {
			$semester["id"] = $row[0];
			$semester["year"] = $row[1];
			$semester["type"] = $row[2];
		}
		return $semester;
	}

	function get_veranstaltung_stunde ($id) {
		function_debug_counter("get_veranstaltung_stunde");
		$query = 'select stunde from veranstaltung_metadaten where veranstaltung_id = '.esc($id);
		$result = rquery($query);
		$stunde = '';

		while ($row = mysqli_fetch_row($result)) {
			$stunde = $row[0];
		}

		return $stunde;
	}

	function get_tag_from_veranstaltung_id ($id) {
		function_debug_counter("get_tag_from_veranstaltung_id");
		$query = 'select wochentag from veranstaltung_metadaten where veranstaltung_id = '.esc($id);
		$result = rquery($query);
		$tag = '';

		while ($row = mysqli_fetch_row($result)) {
			$tag = $row[0];
		}

		return $tag;
	}

	function weekday_to_wochentag ($weekday) {
		function_debug_counter("weekday_to_wochentag");
		$selected = array();
		switch ($weekday) {
			case 'Monday':
				$selected = array("Mo", "Montag");
				break;
			case 'Tuesday':
				$selected = array("Di", "Dienstag");
				break;
			case 'Wednesday':
				$selected = array("Mi", "Mittwoch");
				break;
			case 'Thursday':
				$selected = array("Do", "Donnerstag");
				break;
			case 'Friday':
				$selected = array("Fr", "Freitag");
				break;
			case 'Saturday':
				$selected = array("Sa", "Samstag");
				break;
			case 'Sunday':
				$selected = array("So", "Sonntag");
				break;
			default:
				debug("ERROR: Could not convert `$weekday` to wochentag!");
				$selected = array("ERROR", "Fehler beim Bestimmen des Tages");
		}
		return $selected;
	}

	function wochentag_to_weekday ($wochentag) {
		function_debug_counter("wochentag_to_weekday");
		$selected = array();
		switch ($wochentag) {
			case 'Mo':
				$selected = array('Mo', 'Monday');
				break;
			case 'Di':
				$selected = array('Tu', 'Tuesday');
				break;
			case 'Mi':
				$selected = array('We', 'Wednesday');
				break;
			case 'Do':
				$selected = array('Th', 'Thursday');
				break;
			case 'Fr':
				$selected = array('Fr', 'Friday');
				break;
			case 'Sa':
				$selected = array('Sa', 'Saturday');
				break;
			case 'So':
				$selected = array('Su', 'Sunday');
				break;
			case 'BS':
				$selected = array('BS', 'Blockseminar');
				break;
			default:
				die("Falscher Wochentag: $wochentag!");
		}
		return $selected;
	}

	function get_woche_from_veranstaltung_id ($id) {
		function_debug_counter("get_woche_from_veranstaltung_id");
		$query = 'select woche from veranstaltung_metadaten where veranstaltung_id = '.esc($id);
		$result = rquery($query);
		$woche = '';

		while ($row = mysqli_fetch_row($result)) {
			$woche = $row[0];
		}

		return $woche;
	}

	function css ($name) {
		function_debug_counter("css");
		if(is_array($name)) {
			foreach ($name as $this_name) {
				single_css($this_name);
			}
		} else {
			single_css($name);
		}
	}

	function single_css ($name) {
		function_debug_counter("single_css");
		$file = $GLOBALS['datadir'].$name;

		if(file_exists($file)) {
			if(nonce() !== null) {
?>
				<link nonce="<?php print nonce(); ?>" rel="stylesheet" href="<?php print $file; ?>" />
<?php
			} else {
?>
				<link rel="stylesheet" href="<?php print $file; ?>" />
<?php
			}
		}
	}

	function js ($name) {
		function_debug_counter("js");
		if(is_array($name)) {
			foreach ($name as $this_name) {
				single_js($this_name);
			}
		} else {
			single_js($name);
		}	
	}

	function single_js ($name) {
		function_debug_counter("single_js");
		$file = $GLOBALS['datadir'].$name;

		$path = $file;
		if(preg_match('/^https?:\/\//', $name)) {
			$path = $name;
		}

		if(file_exists($file) || preg_match('/^https?:\/\//', $name)) {
			if(nonce() !== null) {

?>
				<script nonce="<?php print nonce(); ?>" src="<?php print $path; ?>"></script>
<?php
			} else {
?>
				<script src="<?php print $path; ?>"></script>
<?php
			}
		}
	}

	function create_title_plus ($chosen_semester, $chosen_institut, $chosen_studiengang, $chosen_dozent, $chosen_pruefungsamt) {
		function_debug_counter("create_title_plus");
		$title_plus = '';
		if($chosen_semester) {
			if(!$title_plus) {
				$title_plus .= ' (';
			}
			$chosen_semester_data = get_semester($chosen_semester);
			$title_plus .= htmle($chosen_semester_data[2])." ".htmle($chosen_semester_data[1]);
		}

		if(!is_null($chosen_institut)) {
			if(!$title_plus) {
				$title_plus .= ' (';
			} else {
				$title_plus .= ', ';
			}
			$title_plus .= htmle(get_institut_name($chosen_institut));
		}

		if(!is_null($chosen_studiengang)) {
			if(!$title_plus) {
				$title_plus .= ' (';
			} else {
				$title_plus .= ', ';
			}
			$title_plus .= htmle(get_studiengang_name($chosen_studiengang));
		}

		if(!is_null($chosen_dozent)) {
			if(!$title_plus) {
				$title_plus .= ' (';
			} else {
				$title_plus .= ', ';
			}
			$title_plus .= htmle(get_dozent_name($chosen_dozent));
		}

		if(!is_null($chosen_pruefungsamt)) {
			if(!$title_plus) {
				$title_plus .= ' (';
			} else {
				$title_plus .= ', ';
			}
			$title_plus .= "Prüfungsamt: ".htmle(get_pruefungsamt_name($chosen_pruefungsamt));
		}

		if($title_plus) {
			$title_plus .= ')';
		}
		return $title_plus;
	}

	function add_to_output ($name, $msg) {
		function_debug_counter("add_to_output");
		if($name) {
			if($msg) {
				$GLOBALS[$name][] = $msg;
			}
		} else {
			die(htmlentities($name)." existiert nicht!");
		}
	}

	function error ($message) {
		function_debug_counter("error");
		add_to_output("error", $message);
	}

	function success ($message) {
		function_debug_counter("success");
		add_to_output("success", $message);
	}

	function debug ($message) {
		function_debug_counter("debug");
		$bt = debug_backtrace();
		$caller = array_shift($bt);
		$data = array("msg" => $message, "caller" => $caller);
		add_to_output("debug", $data);
	}

	function warning ($message) {
		function_debug_counter("warning");
		add_to_output("warning", $message);
	}

	function right_issue ($message) {
		function_debug_counter("right_issue");
		add_to_output("right_issue", $message);
	}

	function message ($message) {
		function_debug_counter("message");
		add_to_output("message", $message);
	}

	function show_easter_egg ($message) {
		function_debug_counter("show_easter_egg");
		add_to_output("easter_egg", $message);
	}

	function nonce () {
		function_debug_counter("nonce");
		if($GLOBALS['nonce']) {
			return $GLOBALS['nonce'];
		} else {
			$GLOBALS['nonce'] = generate_random_string(10);
			return $GLOBALS['nonce'];
		}
	}

	function rollback () {
		function_debug_counter("rollback");
		$result = rquery("rollback");
		if(!$result) {
			error("Rollback ist fehlgeschlagen.");
		}
		set_autocommit(1);
	}

	function commit () {
		function_debug_counter("commit");
		$result = rquery("commit");
		if(!$result) {
			error("Commit ist fehlgeschlagen.");
		}
		set_autocommit(1);
	}

	function set_autocommit ($true) {
		function_debug_counter("set_autocommit");
		if($true) {
			$true = 1;
		} else {
			$true = 0;
		}
		$result = rquery('set autocommit='.$true);
		if(!$result) {
			error("`SET AUTOCOMMIT = $true` fehlgeschlagen.");
		}
	}

	function start_transaction () {
		function_debug_counter("start_transaction");
		set_autocommit(0);
		$result = rquery('start transaction');
		if(!$result) {
			error("`start transaction` fehlgeschlagen.");
		}
	}

/*
	╔════════════════╤══════════════════╤══════╤═════╤═══════════════════╤═══════╗
	║ Field          │ Type             │ Null │ Key │ Default           │ Extra ║
	╟────────────────┼──────────────────┼──────┼─────┼───────────────────┼───────╢
	║ id             │ int(10) unsigned │ NO   │ PRI │ NULL              │       ║
	║ predecessor_id │ int(10) unsigned │ YES  │ MUL │ NULL              │       ║
	║ name           │ varchar(100)     │ YES  │     │ NULL              │       ║
	║ email          │ varchar(500)     │ YES  │     │ NULL              │       ║
	║ time           │ datetime         │ YES  │     │ CURRENT_TIMESTAMP │       ║
	║ useragent      │ varchar(500)     │ YES  │     │ NULL              │       ║
	║ subject        │ varchar(500)     │ YES  │     │ NULL              │       ║
	║ message        │ varchar(4096)    │ YES  │     │ NULL              │       ║
	║ sent           │ datetime         │ YES  │     │ NULL              │       ║
	╚════════════════╧══════════════════╧══════╧═════╧═══════════════════╧═══════╝
 */

	function write_mail_to_db ($name, $email, $useragent, $subject, $message, $sent, $predecessor_id = null) {
		function_debug_counter("write_mail_to_db");
		if(!isset($useragent)) {
			$useragent = $_SERVER['HTTP_USER_AGENT'];
		}
		if(isset($sent)) {
			$sent = "now()";
		} else {
			$sent = "null";
		}
		$query = 'insert into emails (';
		$query .= 'predecessor_id, name, email, useragent, subject, message, time, sent) VALUES (';
		$query .= multiple_esc_join(array($predecessor_id, $name, $email, $useragent, $subject, $message, $send)).", now(), $sent)";
		$query .= ')';

		dier($query);
	}

	function escapeJsonString($value) { 
		function_debug_counter("escapeJsonString");# list from www.json.org: (\b backspace, \f formfeed)
		$escapers = array("\\", "/", "\"", "\n", "\r", "\t", "\x08", "\x0c");
		$replacements = array("\\\\", "\\/", "\\\"", "\\n", "\\r", "\\t", "\\f", "\\b");
		$result = str_replace($escapers, $replacements, $value);
		return $result;
	}

	function fill_deletion_global ($post_ids, $dbname, $debugvalues = array()) {
		function_debug_counter("fill_deletion_global");
		if(is_array($post_ids)) {
			$true = 1;
			foreach ($post_ids as $this_post_id) {
				if(!(get_post($this_post_id) || array_key_exists($this_post_id, $debugvalues))) {
					$true = 0;
					break;
				}
			}

			if($true) {
				$GLOBALS['deletion_db'] = $dbname;
				return $dbname;
			}
		} else {
			if(get_post($post_ids) || array_key_exists($this_post_id, $debugvalues)) {
				$GLOBALS['deletion_db'] = $dbname;
				return $dbname;
			}
		}
	}

	function get_einzelne_termine_from_post () {
		function_debug_counter("get_einzelne_termine_from_post");
		$einzelne_termine = array();

		if(array_key_exists('einzelner_termin_start', $_POST)) {
			foreach (get_post('einzelner_termin_start') as $einzelner_termin_array_id => $einzelner_termin_array_value) {
				$einzelner_termin_start = $_POST['einzelner_termin_start'][$einzelner_termin_array_id];
				$einzelner_termin_ende = $_POST['einzelner_termin_ende'][$einzelner_termin_array_id];
				$einzelner_termin_gebaeude = $_POST['einzelner_termin_gebaeude'][$einzelner_termin_array_id];
				$einzelner_termin_raum = $_POST['einzelner_termin_raum'][$einzelner_termin_array_id];

				$einzelne_termine[] = array(
					"einzelner_termin_start"	=> $einzelner_termin_start,
					"einzelner_termin_ende"		=> $einzelner_termin_ende,
					"einzelner_termin_gebaeude"	=> $einzelner_termin_gebaeude,
					"einzelner_termin_raum"		=> $einzelner_termin_raum
				);
			}
		}

		return $einzelne_termine;
	}


	function fill_data_from_mysql_result ($result) {
		function_debug_counter("fill_data_from_mysql_result");
		$data = array();
		while ($row = mysqli_fetch_row($result)) {
			$data[] = $row;
		}
		return $data;
	}

	function fill_first_element_from_mysql_query ($query) {
		function_debug_counter("fill_first_element_from_mysql_query");
		$result = rquery($query);
		$data = array();
		while ($row = mysqli_fetch_row($result)) {
			$data[$row[0]] = $row[1];
		}
		return $data;
	}

	function set_debug ($value) {
		function_debug_counter("set_debug");
		$value = !!$value;
		$query = 'delete from debug';
		$result = rquery($query);
		if($result) {
			#create table debug (debug tinyint default "0" primary key);
			$query = 'insert into debug (debug) values ('.esc($value).')';
			$result = rquery($query);
			if($result) {
				success("Debug-Status erfolgreich eingetragen.");
			} else {
				error("Konnte den neuen Debug-Status nicht eintragen.");
			}
		} else {
			error("Konnte den Debug-Status nicht setzen löschen.");
		}

	}

	function is_debug () {
		function_debug_counter("is_debug");
		$query = 'SELECT `debug` FROM `debug`';
		$result = rquery($query);
		$status = 0;
		while ($row = mysqli_fetch_row($result)) {
			$status = $row[0];
		}
		return $status;
	}

	/* DO NOT REPLACE */	function debug_backtrace_string() {
		$stack = array();
		$trace = debug_backtrace();
		unset($trace[0]);
		unset($trace[1]);
		foreach(array_reverse($trace) as $node) {
			if(!preg_match('/^include(_once)?$/', $node["function"])) {
				$stack[] = '"'.addslashes($node['function']).'"';
			}
		}
		return join(' -> ', $stack).";\n";
	} 


	/* DO NOT REPLACE */	function function_debug_counter ($funcname) {
		return 1;
		$this_backtrace_string = debug_backtrace_string();

		if(!in_array($this_backtrace_string, $GLOBALS['backtraces'])) {
			@$GLOBALS["backtraces"][] = $this_backtrace_string;
		}

		if(array_key_exists($funcname, $GLOBALS["function_debugger"])) {
			@$GLOBALS["function_debugger"][$funcname]++;
		} else {
			@$GLOBALS["function_debugger"][$funcname] = 1;
		}
	}

	/* DO NOT REPLACE */	function plot_all_backtraces () {
		$string = "strict digraph a {\n   graph [ splines = false ]\n";
		foreach ($GLOBALS["backtraces"] as $thistrace) {
			$string .= "$thistrace\n";
		}
		$string .= "}\n";
		return plot_graphviz($string);
	}

	/* DO NOT REPLACE */	function plot_graphviz ($code) {
		$tmpfile = tempnam("/tmp/", "backtrace_");;
		file_put_contents($tmpfile, $code);
		$outputfile = tempnam("/tmp/", "backtrace_");;
		$format = "png";
		$command = "circo -T$format $tmpfile > $outputfile";
		system($command);
		$file = file_get_contents($outputfile);
		$base64 = '<img src="'.'data:image/' . $format. ';base64,' . base64_encode($file).'">';
		return $base64;
	}

	function create_veranstaltung_pruefung_tabelle ($pruefungen) {
		function_debug_counter("create_veranstaltung_pruefung_tabelle");

?>
		<table class="font_size_10px">
<?php
		$i = 0;
		$last_modul_name = '';
		foreach ($pruefungen as $this_pruefung) {

			$this_modul_id = $this_pruefung['6'];
			if($i == 0) {
?>
				<tr>
					<th>Prü&shy;fungs&shy;num&shy;mer</th> <!-- Prüfungsnummer -->
					<th>Prü&shy;fungs&shy;typ</th> <!-- Prüfungstyp -->
					<th>Bereich</th>	<!-- Bereich/Studiengang -->
				</tr>
<?php
			}

			$studiengang = $this_pruefung[9];
			if($this_modul_id != $last_modul_name) {
				$bgc = "background_color_add8e6";
				if(
					(get_get('modul') == $this_modul_id) || 
					(
						isset($relevante_module) &&
						is_array($relevante_module) &&
						in_array($this_metadata_id, $relevante_module)
					)
				) {
					$bgc = "background_color_ffa500";
				}
#dier($this_pruefung);
?>
				<tr>
					<td class="<?php print $bgc; ?>" colspan="4"><?php print "Studiengang: <i>".htmlentities($studiengang)."</i><br />\nModul: <i>".htmlentities(get_modul_name($this_pruefung[6]))."</i>"; ?>:</td>
				</tr>
<?php
				$last_modul_name = $this_modul_id;
			}
?>
			<tr>
				<td><?php print htmle($this_pruefung[0]); print pruefung_symbole($this_pruefung[0]); ?></td>
				<td><?php print htmle($this_pruefung[2]); ?></td>
				<td><?php print htmle($this_pruefung[3]); ?></td>
			</tr>
<?php
			$i++;
		}

?>
		</table>
<?php
	}

	function replace_hinweis_with_graphics ($text, $show_base_url = 0) {
		$base_url = '';
		if($show_base_url) {
			$base_url = $GLOBALS['vvz_base_url'];
		}
		$text = preg_replace('/LaTeX/', '<img width="45px" alt="LaTeX" src="'.$base_url.'i/LaTeX.svg">', $text);
		$text = preg_replace('/\\\\git/', '<img width="45px" alt="git" src="'.$base_url.'i/git.svg">', $text);
		$text = preg_replace('/(warnung|achtung|vorsicht)/i', '&#x26a0; \1', $text);
		return $text;
	}

	function query_to_table ($query, $cols) {
		$string = '';
		if($query) {
			if(is_array($cols)) {
				$string = "<table>\n";
				
				$string .= "<tr>\n";
				foreach ($cols as $thiscol) {
					$string .= "<th>$thiscol</th>\n";
				}
				$string .= "</tr>\n";

				$result = rquery($query);
				while ($row = mysqli_fetch_row($result)) {
					$string .= "<tr>\n";
					foreach ($row as $thisrow) {
						$string .= "\t<td>$thisrow</th>\n";
					}
					$string .= "</tr>\n";
				}
				$string .= "</table>\n<br>\n";
			} else {
				$string = '<i>Falsche Cols!</i>"';
			}
		} else {
			$string  = "<i>No Query!</i>";
		}
		return $string;
	}

	function print_h ($string, $level = 1, $toc = array()) {
		$output = '';
		$id = NULL;
		if(is_integer($level) && $level >= 0) {
			$id = generate_random_string(60);
			$output = "<h$level id='$id'>$string</h$level>\n";
		} else {
			$GLOBALS['debug'][] = "Irgendwas stimmt hier nicht, print_h \$level = $level";
		}
		if(!is_null($id)) {
			$GLOBALS['toc'][] = array("string" => $string, "level" => $level, "id" => $id);
		}
		return $output;
	}

	function print_h2 ($string) {
		return print_h($string, 2);
	}

	function print_h3 ($string) {
		return print_h($string, 3);
	}

	function get_toc () {
		$levels = array();
		print "<ul>\n";
		foreach ($GLOBALS['toc'] as $entry) {
			/*
			if(!array_key_exists($entry["level"], $levels)) {
				$level[$entry["level"]] = 0;
			} else {
				$level[$entry["level"]]++;
				print $level
			}
			*/
			$id = $entry["id"];
			$level = $entry["level"];
			$string = $entry["string"];

			print "<li><a href='#$id'>$string</a></li>\n";
		}
		print "</ul>\n";
	}
?>
