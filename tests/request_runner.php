<?php
/*
 * Execute functions.php's request dispatcher in an isolated process against a
 * disposable clone database. The dispatcher contains a large amount of
 * top-level POST handling that cannot be reached by simply calling functions.
 */

require_once(__DIR__ . '/cli_guard.php');

$clone_db = isset($argv[1]) ? $argv[1] : '';
$variant = isset($argv[2]) ? $argv[2] : 'baseline';
if ($clone_db === '') {
	fwrite(STDERR, "Missing clone database name\n");
	exit(2);
}

$suffix = preg_replace('/^db_vvz_/', '', $clone_db);
$_SERVER['REDIRECT_URL'] = '/v/' . $suffix . '/';
$_SERVER['SCRIPT_NAME'] = '/admin.php';
$_SERVER['PHP_SELF'] = '/admin.php';
$_SERVER['REQUEST_URI'] = '/admin.php';
$_SERVER['QUERY_STRING'] = '';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['REQUEST_METHOD'] = 'POST';

$GLOBALS['no_selftest'] = 1;
$GLOBALS['no_selftest_force'] = 1;

$_POST = array();
$_GET = array();
$_COOKIE = array();
$_FILES = array();

$session_id = 'reqtest-session';
$cookie_name = md5($clone_db) . '_session_id';
$_COOKIE[$cookie_name] = $session_id;

if (strpos($variant, 'auto:') === 0 || strpos($variant, 'auto_no_id:') === 0) {
	$no_id = strpos($variant, 'auto_no_id:') === 0;
	$key = substr($variant, strlen($no_id ? 'auto_no_id:' : 'auto:'));
	if (!$no_id) {
		$_POST['id'] = 1;
	}
	$array_keys = array('language', 'praesenztyp', 'bezug', 'page', 'modules', 'checked_studiengaenge', 'editable_users', 'merge_from');
	if (in_array($key, $array_keys, true)) {
		$_POST[$key] = array(1);
	} else if (in_array($key, array('institut', 'dozent', 'role', 'titel_id', 'bereich', 'pruefungstyp', 'zeitraum', 'modul', 'studiengang_id', 'veranstaltung_id', 'pruefungstyp_id', 'gebaeude_id', 'raum', 'raumwunsch', 'gebaeudewunsch', 'semester_id', 'id'), true)) {
		$_POST[$key] = 1;
	} else if (in_array($key, array('delete', 'delete_for_sure', 'newpage', 'updatepage', 'new_user', 'dozent_wizard', 'create_gebaeude', 'create_language', 'create_faq', 'create_bereich', 'create_fach', 'create_titel', 'create_pruefung_zeitraum', 'create_pruefungsamt', 'new_function_right', 'neue_pruefungsnummer', 'update_text', 'update_hinweis', 'update_setting', 'reset_setting', 'import_bereiche_from_csv', 'import_gebaeude_from_csv', 'delete_logo', 'delete_semester_start_ids', 'setze_semester', 'merge_data', 'api_new', 'api_change'), true)) {
		$_POST[$key] = 1;
	} else {
		$_POST[$key] = 'Req';
	}
}

switch ($variant) {
	case 'login_post':
		$_COOKIE = array();
		$_POST['try_login'] = 1;
		$_POST['username'] = 'Admin';
		$_POST['password'] = 'test';
		break;
	case 'auto_login':
		$_COOKIE = array();
		$GLOBALS['auto_login_id'] = 1;
		break;
	case 'sdsg':
		$_GET['sdsg_einverstanden'] = 1;
		break;
	case 'pages_direct':
		$_SERVER['REQUEST_URI'] = '/pages/accounts.php';
		$_SERVER['SCRIPT_NAME'] = '/pages/accounts.php';
		break;
	case 'pages_die':
		$_SERVER['REQUEST_URI'] = '/pages/unknown_reqtest.php';
		$_SERVER['SCRIPT_NAME'] = '/pages/unknown_reqtest.php';
		break;
	case 'updates':
		$_POST = array(
			'id' => 1,
			'update_language' => 1, 'name' => 'ReqSprache', 'abkuerzung' => 'rs',
			'update_semester' => 1, 'erster_termin' => '2099-01-01',
			'update_dozent_titel' => 1, 'titel_id' => 1,
			'update_titel' => 1, 'titel_abkuerzung' => 'RT',
			'frage' => 'ReqFrage', 'antwort' => 'ReqAntwort', 'faq_update' => 1, 'wie_oft_gestellt' => 1,
			'update_pruefung_zeitraum' => 1,
			'update_pruefungsamt' => 1,
			'update_bereich' => 1,
			'update_pruefungsnummer_fach' => 1,
			'pruefungstyp_name' => 'ReqPT',
			'funktion_name' => 'ReqFR',
			'institut_id' => 1, 'studiengang' => 'ReqStg', 'studienordnung' => 'x', 'order_key' => 1,
			'gebaeude_id' => 1, 'raum_name' => 'ReqRaum',
			'dozent_first_name' => 'Req', 'dozent_last_name' => 'Doz', 'ausgeschieden' => 'nein',
			'gebaeude_name' => 'ReqGeb', 'abkuerzung' => 'RG',
			'faculty_name' => 'ReqInstitut', 'start_nr' => 1,
			'studiengang_id' => 1, 'module' => 'ReqModul', 'beschreibung' => 'b',
			'veranstaltungstyp_name' => 'ReqVT', 'veranstaltungstyp_abkuerzung' => 'RVT',
			'update_page_info' => 1, 'info' => 'ReqInfo',
		);
		break;
	case 'pages':
		$_POST = array(
			'id' => 1,
			'newpage' => 1, 'titel' => 'ReqPage', 'datei' => 'reqpage.php',
			'show_in_navigation' => 1, 'eltern' => '', 'role_to_page' => array(1),
			'beschreibung' => 'ReqBeschreibung', 'hinweis' => 'ReqHinweis',
			'updatepage' => 1, 'titel' => 'ReqPage2', 'datei' => 'reqpage2.php',
		);
		break;
	case 'roles':
		$_POST = array(
			'id' => 1,
			'neue_rolle' => 'ReqRolle', 'page' => array(1), 'beschreibung' => 'ReqRolleBeschreibung',
		);
		break;
	case 'users':
		$_POST = array(
			'id' => 1,
			'name' => 'Admin', 'role' => 1, 'password' => 'newpass', 'dozent' => 1,
			'institut' => 1, 'barrierefrei' => 1, 'accepted_public_data' => 1,
			'new_user' => 1,
			'update_barrierefrei' => 1,
			'change_own_data' => 1, 'password_repeat' => 'newpass',
		);
		break;
	case 'rights':
		$_POST = array(
			'update_right_to_user_role' => 1,
			'checkbox_1_1' => 1, 'checkbox_2_1' => 1,
			'update_right_to_page' => 1,
			'checkbox_1_2' => 1, 'checkbox_2_2' => 1,
			'editable_users' => array(1), 'dozent_id' => 1,
			'update_modul_semester_data' => 1, 'semester' => 1, 'studiengang' => 1,
			'credit_points' => 5, 'pruefungsleistung_anzahl' => 2, 'modul' => 1,
			'veranstaltungstyp_1' => 3,
			'pruefungsamt_nach_studiengang_zuordnung' => 1, 'pruefungsamt_id' => 1,
			'checked_studiengaenge' => array(1),
		);
		break;
	case 'creates':
		$_POST = array(
			'create_pruefungsamt' => 1, 'new_name' => 'ReqPA',
			'create_titel' => 1, 'new_titel_abkuerzung' => 'RT2',
			'create_pruefung_zeitraum' => 1,
			'setze_semester' => 1,
			'new_function_right' => 1, 'funktion_name' => 'ReqFR2',
			'create_fach' => 1,
			'create_bereich' => 1,
			'neue_pruefungsnummer' => 1, 'modul' => 1, 'pruefungsnummer' => 'ReqPN',
			'pruefungstyp' => 1, 'bereich' => 1, 'modulbezeichnung' => 'ReqM', 'zeitraum' => 1,
			'api_new' => 1, 'ansprechpartner' => 'ReqA', 'email' => 'req@example.org', 'grund' => 'ReqGrund',
			'update_text' => 1, 'page_id' => 1, 'text' => 'ReqText',
			'update_hinweis' => 1, 'hinweis' => 'ReqHinweis2',
			'neue_veranstaltung' => 1, 'dozent' => 1, 'veranstaltungstyp' => 1,
			'institut' => 1, 'name' => 'ReqVeranstaltung', 'semester' => 1,
			'language' => array(1), 'praesenztyp' => array(1),
		);
		break;
	case 'veranstaltung_update':
		$_POST = array(
			'id' => 1, 'update_veranstaltung' => 1,
			'name' => 'ReqVeranstaltungNeu', 'dozent' => 1, 'veranstaltungstyp' => 1,
			'institut' => 1, 'semester' => 1, 'master_niveau' => 1,
			'fester_bbb_raum' => 0, 'speichern_metainfos' => 1,
		);
		break;
	case 'veranstaltung_meta':
		$_POST = array(
			'id' => 1, 'update_einzelne_veranstaltung' => 1,
			'tag' => 'Mo', 'stunde' => '1', 'woche' => 'jede Woche',
			'erster_termin' => '2099-01-01', 'anzahl_hoerer' => 10,
			'wunsch' => 'w', 'hinweis' => 'h', 'opal_link' => 'https://example.org',
			'abgabe_pruefungsleistungen' => '2099-02-01',
			'gebaeudewunsch' => 1, 'raumwunsch' => 'R1', 'master_niveau' => 1,
			'language' => array(1), 'praesenztyp' => array(1),
			'pruefungsnummer' => array(1), 'fester_bbb_raum' => 0,
			'videolink' => '', 'bezug' => array(1),
		);
		break;
	case 'customize':
		$_POST = array('id' => 1, 'customize_value' => 1, 'value' => 'ReqCSS');
		break;
	case 'creates2':
		$_POST = array(
			'dozent_wizard' => 1, 'first_name' => 'Req', 'last_name' => 'Wizard',
			'password' => 'pw', 'titel_id' => 1, 'institut' => 1, 'barrierefrei' => 1,
			'reset_sperrvermerk_semester_id_1' => 1,
			'sperrvermerk_semester_id_1' => 1,
			'delete_semester_start_ids' => 1, 'semester_id' => 1,
			'new_faculty_name' => 'ReqFakultaet', 'new_start_nr' => 1,
			'create_gebaeude' => 1, 'new_gebaeude_name' => 'ReqGeb', 'new_abkuerzung' => 'RG3',
			'create_language' => 1, 'new_name' => 'ReqSprache', 'new_abkuerzung' => 'rs',
			'new_dozent_first_name' => 'Req', 'new_dozent_last_name' => 'Doz3',
			'new_pruefungstyp_name' => 'ReqPT2',
			'institut_id' => 1, 'new_studiengang' => 'ReqStudiengang2',
			'studienordnung' => 'x', 'bereich' => 1,
			'studiengang_id' => 1, 'new_module' => 'ReqModul2',
			'beschreibung' => 'b', 'abkuerzung' => 'RM2',
			'new_veranstaltungstyp_name' => 'ReqVT2', 'new_veranstaltungstyp_abkuerzung' => 'RVT2',
			'neue_pruefung' => 1,
			'create_faq' => 1, 'frage' => 'ReqFAQ', 'antwort' => 'ReqAntwort', 'wie_oft_gestellt' => 1,
			'import_bereiche_from_csv' => 1, 'csv' => "bereich_name\nReqCSV\n",
			'import_gebaeude_from_csv' => 1, 'csv' => "gebaeude_name,gebaeude_abkuerzung\nReqCSVG,RCG\n",
			'update_setting' => 1, 'name' => 'x11_debugging_mode', 'value' => '0', 'description' => 'x',
		);
		break;
	case 'delete_confirm':
		$_POST = array(
			'id' => 1, 'delete' => 1,
			'update_language' => 1, 'update_pruefungsamt' => 1,
			'update_pruefung_zeitraum' => 1, 'update_titel' => 1,
			'faculty_name' => 'x', 'update_veranstaltung' => 1,
			'studiengang' => 'x', 'institut_id' => 1,
			'gebaeude_id' => 1, 'raum_name' => 'x',
			'gebaeude_name' => 'x', 'abkuerzung' => 'x',
			'updatepage' => 1, 'dozent_first_name' => 'x', 'dozent_last_name' => 'x',
			'studiengang_id' => 1, 'module' => 'x',
			'veranstaltungstyp_name' => 'x', 'veranstaltungstyp_abkuerzung' => 'x',
			'pruefungstyp_id' => 1, 'veranstaltung_id' => 1, 'pruefungsnummer' => 'x',
			'neue_rolle' => 'x', 'page' => array(1),
			'faq_update' => 1, 'datum' => '2099-01-01', 'raum' => 1, 'stunde' => '1', 'pruefung' => 1,
			'update_nachpruefung' => 1, 'update_pruefungsnummer' => 1, 'pruefungstyp' => 1,
			'name' => 'x', 'password' => 'x', 'role' => 1,
			'update_bereich' => 1, 'update_fach' => 1,
			'update_veranstaltungstyp' => 1, 'create_institut' => 1,
		);
		break;
	case 'deletes':
		$_POST = array(
			'delete' => 1, 'delete_for_sure' => 1, 'id' => 1,
			'update_language' => 1,
			'update_titel' => 1,
			'update_pruefung_zeitraum' => 1,
			'update_pruefungsamt' => 1,
			'update_bereich' => 1,
			'update_pruefungsnummer_fach' => 1,
			'pruefungstyp_name' => 'ReqPT',
			'funktion_name' => 'ReqFR',
			'institut_id' => 1, 'studiengang' => 'ReqStg',
			'gebaeude_id' => 1, 'raum_name' => 'ReqRaum',
			'dozent_first_name' => 'Req', 'dozent_last_name' => 'Doz',
			'gebaeude_name' => 'ReqGeb', 'abkuerzung' => 'RG',
			'faculty_name' => 'ReqInstitut',
			'studiengang_id' => 1, 'module' => 'ReqModul',
			'veranstaltungstyp_name' => 'ReqVT',
			'updatepage' => 1,
			'neue_rolle' => 'ReqRolle', 'page' => array(1),
			'update_veranstaltung' => 1,
			'update_pruefungsnummer' => 1, 'pruefungstyp' => 1,
			'name' => 'Admin', 'role' => 1,
			'api_change' => 1, 'auth_code' => 'reqtestcode',
			'delete_semester' => 1,
			'update_nachpruefung' => 1, 'datum' => '2099-01-01', 'raum' => 1, 'stunde' => '1', 'pruefung' => 1,
			'pruefungstyp_id' => 1, 'veranstaltung_id' => 1, 'pruefungsnummer' => 'ReqPN',
		);
		break;
}

ob_start();
$status = 'OK';
try {
	require($root = dirname(__DIR__) . '/functions.php');
} catch (\Throwable $e) {
	$status = 'EXCEPTION: ' . get_class($e) . ': ' . $e->getMessage();
}
$output = ob_get_clean();
if ($variant === 'baseline') {
	fwrite(STDERR, "DEBUG logged_in=" . (isset($GLOBALS['logged_in']) ? $GLOBALS['logged_in'] : 'unset') . " dbname=" . (isset($GLOBALS['dbname']) ? $GLOBALS['dbname'] : 'unset') . "\n");
}
fwrite(STDOUT, "REQUEST_STATUS: " . $status . "\n");
fwrite(STDOUT, "REQUEST_OUTPUT_LENGTH: " . strlen($output) . "\n");
if ($status !== 'OK') {
	fwrite(STDERR, $status . "\n");
}
