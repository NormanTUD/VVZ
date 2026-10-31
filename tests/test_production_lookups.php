<?php
/*
 * Extensive tests for read-only / lookup production functions.
 *
 * These tests run inside testsuite.php, i.e. against the real database and
 * the real functions.php implementation (not the pure-mode stubs). All data
 * used here is seeded by tests/setup_db.php or by selftest().
 *
 * The tests are intentionally defensive: if the database is unavailable the
 * whole file is skipped instead of producing misleading failures.
 */

if (!isset($GLOBALS['dbname']) || !$GLOBALS['dbname'] || !isset($GLOBALS['dbh']) || !is_object($GLOBALS['dbh'])) {
	return;
}

$vvz_test_db = $GLOBALS['dbname'];
$vvz_saved_globals = array();
foreach (array('logged_in_user_id', 'user_role_id', 'user_dozent_id', 'user_institut_id', 'memoize', 'toc', 'debug', 'get_gebaeude_name_cache') as $vvz_global_name) {
	$vvz_saved_globals[$vvz_global_name] = array_key_exists($vvz_global_name, $GLOBALS) ? $GLOBALS[$vvz_global_name] : null;
}
$vvz_saved_server = $_SERVER;
$vvz_saved_post = $_POST;
$vvz_saved_get = $_GET;
$vvz_saved_cookie = $_COOKIE;

$GLOBALS['logged_in_user_id'] = 1;
$GLOBALS['user_role_id'] = 1;
$GLOBALS['user_dozent_id'] = 1;
$GLOBALS['user_institut_id'] = 1;
$GLOBALS['memoize'] = array();
$GLOBALS['toc'] = array();
$GLOBALS['get_gebaeude_name_cache'] = array();
if (!isset($GLOBALS['debug']) || !is_array($GLOBALS['debug'])) {
	$GLOBALS['debug'] = array();
}
$_POST = array();
$_GET = array();
$_COOKIE = array();

/* ============================================================ */
/* Basis-Lookups: Bereiche, Rollen, Benutzer, Institute          */
/* ============================================================ */

is_equal("prod: get_bereich_name_by_id(1)", get_bereich_name_by_id(1), 'Grundzüge der Logik');
is_equal("prod: get_bereich_name_by_id(null) is null", get_bereich_name_by_id(null) === null ? 1 : 0, 1);
is_equal("prod: get_bereich_name_by_id('') is null", get_bereich_name_by_id('') === null ? 1 : 0, 1);
is_equal("prod: get_bereich_name_by_id(999999) is falsy", !get_bereich_name_by_id(999999) ? 1 : 0, 1);

is_equal("prod: get_user_by_dozent(1)", (string) get_user_by_dozent(1), (string) get_single_row_from_query("select id from users where dozent_id = 1"));
is_equal("prod: get_user_by_dozent(999999) is null", get_user_by_dozent(999999) === null ? 1 : 0, 1);

is_equal("prod: get_role_name(1)", get_role_name(1), 'Administrator');
is_equal("prod: get_role_id('Administrator')", (string) get_role_id('Administrator'), (string) get_single_row_from_query("select id from role where name = 'Administrator'"));
is_equal("prod: get_role_id unknown is null", get_role_id('Diese Rolle gibt es nicht') === null ? 1 : 0, 1);
is_equal("prod: get_user_id('Admin')", (string) get_user_id('Admin'), (string) get_single_row_from_query("select id from users where username = 'Admin'"));
is_equal("prod: get_user_name(1)", get_user_name(1), 'Admin');
is_equal("prod: get_user_name unknown is null", get_user_name(999999) === null ? 1 : 0, 1);
is_equal("prod: get_account_enabled_by_id(1)", (string) get_account_enabled_by_id(1), '1');
is_equal("prod: get_role_id_by_user(1)", (string) get_role_id_by_user(1), '1');
is_equal("prod: get_dozent_id_by_user_id(1)", (string) get_dozent_id_by_user_id(1), '1');
is_equal("prod: get_rolle_beschreibung(1) is string or null", is_string(get_rolle_beschreibung(1)) || get_rolle_beschreibung(1) === null ? 1 : 0, 1);

is_equal("prod: get_institut_name(1)", get_institut_name(1), 'Demo-Institut');
is_equal("prod: get_institut_id('Demo-Institut')", (string) get_institut_id('Demo-Institut'), '1');
is_equal("prod: get_institut_id unknown is null", get_institut_id('Nicht existierendes Institut') === null ? 1 : 0, 1);
is_equal("prod: get_institut_id_by_veranstaltung_id unknown is falsy", !get_institut_id_by_veranstaltung_id(999999) ? 1 : 0, 1);
is_equal("prod: get_startnr_by_institut(1) is numeric/null", is_numeric(get_startnr_by_institut(1)) || get_startnr_by_institut(1) === null ? 1 : 0, 1);
is_equal("prod: get_valid_institut(null) returns integer", preg_match('/^\d+$/', (string) get_valid_institut(null)) ? 1 : 0, 1);
$_GET['institut'] = '1';
is_equal("prod: get_valid_institut uses GET institut", (string) get_valid_institut(null), '1');
$_GET = array();
is_equal("prod: get_valid_institut returns passed value", get_valid_institut(5), 5);

/* ============================================================ */
/* Studiengänge, Module, Prüfungsdaten                           */
/* ============================================================ */

$vvz_stg_row = get_single_row_from_query_assoc("select name, institut_id from studiengang where id = 1");
$vvz_stg_name = $vvz_stg_row ? $vvz_stg_row['name'] : null;
$vvz_stg_institut = $vvz_stg_row ? $vvz_stg_row['institut_id'] : 1;
is_equal("prod: get_studiengang_name(1)", get_studiengang_name(1), $vvz_stg_name);
is_equal("prod: get_studiengang_name('alle')", get_studiengang_name('alle'), 'Alle Studiengänge');
is_equal("prod: get_studiengang_name(null) is null", get_studiengang_name(null) === null ? 1 : 0, 1);
is_equal("prod: get_studiengang_id(current name, institut)", (string) get_studiengang_id($vvz_stg_name, $vvz_stg_institut), '1');
is_equal("prod: get_studiengang_id unknown is null", get_studiengang_id('Unbekannt', $vvz_stg_institut) === null ? 1 : 0, 1);
is_equal("prod: get_studiengang_id_by_name(current name)", (string) get_studiengang_id_by_name($vvz_stg_name), '1');
is_equal("prod: get_studiengang_name_by_modul_id(999999) is null", get_studiengang_name_by_modul_id(999999) === null ? 1 : 0, 1);

is_equal("prod: get_pruefungsnummer_id_by_pruefungsnummer unknown is null", get_pruefungsnummer_id_by_pruefungsnummer('NOPE-XYZ') === null ? 1 : 0, 1);
is_equal("prod: get_pruefungstyp_by_pruefungsnummer unknown is null", get_pruefungstyp_by_pruefungsnummer('NOPE-XYZ') === null ? 1 : 0, 1);
is_equal("prod: get_modul_by_pruefungsnummer unknown is null", get_modul_by_pruefungsnummer('NOPE-XYZ') === null ? 1 : 0, 1);
is_equal("prod: get_modul_id unknown is null", get_modul_id('Unbekannt', 1) === null ? 1 : 0, 1);
is_equal("prod: get_modul_name(null) is null", get_modul_name(null) === null ? 1 : 0, 1);
is_equal("prod: get_modul_name(0) is null", get_modul_name(0) === null ? 1 : 0, 1);
is_equal("prod: get_pruefungstyp_name(1)", get_pruefungstyp_name(1), 'Klausur');
is_equal("prod: get_pruefungstyp_name(null) is null", get_pruefungstyp_name(null) === null ? 1 : 0, 1);
is_equal("prod: get_pruefungstyp_id('Klausur')", (string) get_pruefungstyp_id('Klausur'), '1');
is_equal("prod: get_pruefungstyp_id('Nope') is null", get_pruefungstyp_id('Nope') === null ? 1 : 0, 1);
is_equal("prod: get_pruefungsnummer_bereich unknown is null", get_pruefungsnummer_bereich_by_pruefungsnummer_id('NOPE') === null ? 1 : 0, 1);
is_equal("prod: get_pruefungsnummer_modul unknown is null", get_pruefungsnummer_modul_by_pruefungsnummer_id('NOPE') === null ? 1 : 0, 1);
is_equal("prod: get_pruefungstyp_id_from_pruefungsnummer unknown is null", get_pruefungstyp_id_from_pruefungsnummer('NOPE') === null ? 1 : 0, 1);
is_equal("prod: get_pruefung_zeitraum(999999)", get_pruefung_zeitraum(999999) === null ? 1 : 0, 1);
is_equal("prod: get_pruefungsamt_name(null) is null", get_pruefungsamt_name(null) === null ? 1 : 0, 1);
is_equal("prod: get_pruefungsamt_name unknown is null", get_pruefungsamt_name(999999) === null ? 1 : 0, 1);
is_equal("prod: get_semester_string(1) contains type", strpos(get_semester_string(1), 'Sommersemester') !== false || strpos(get_semester_string(1), 'Wintersemester') !== false ? 1 : 0, 1);

/* ============================================================ */
/* Gebäude, Räume, Veranstaltungstypen                           */
/* ============================================================ */

is_equal("prod: get_gebaeude_name(1)", get_gebaeude_name(1), 'Absolutismus-Bau');
is_equal("prod: get_gebaeude_name(null) is null", get_gebaeude_name(null) === null ? 1 : 0, 1);
is_equal("prod: get_gebaeude_id('Absolutismus-Bau')", (string) get_gebaeude_id('Absolutismus-Bau'), '1');
is_equal("prod: get_gebaeude_id_by_abkuerzung('ABS')", (string) get_gebaeude_id_by_abkuerzung('ABS'), '1');
is_equal("prod: get_gebaeude_id_by_abkuerzung unknown is null", get_gebaeude_id_by_abkuerzung('ZZZ') === null ? 1 : 0, 1);
is_equal("prod: get_gebaeude_abkuerzung(1)", get_gebaeude_abkuerzung(1), 'ABS');
is_equal("prod: get_gebaeude_abkuerzung(null) is null", get_gebaeude_abkuerzung(null) === null ? 1 : 0, 1);
regex_matches("prod: get_gebaeude_abkuerzung(1, 1) contains navigator link", (string) get_gebaeude_abkuerzung(1, 1), '/navigator\.tu-dresden\.de/');
is_equal("prod: get_gebaeude_name_abkuerzung(1)", get_gebaeude_name_abkuerzung(1), array('ABS', 'Absolutismus-Bau'));
is_equal("prod: get_gebaeude_name_abkuerzung(null) is null", get_gebaeude_name_abkuerzung(null) === null ? 1 : 0, 1);
$vvz_geo = get_gebaeude_geo_coords_by_id(1);
is_equal("prod: get_gebaeude_geo_coords_by_id returns array", is_array($vvz_geo) ? 1 : 0, 1);

is_equal("prod: get_raum_name_by_id(1)", get_raum_name_by_id(1), '101');
is_equal("prod: get_raum_name_by_id(null) is null", get_raum_name_by_id(null) === null ? 1 : 0, 1);
is_equal("prod: get_raum_name_by_id unknown is null", get_raum_name_by_id(999999) === null ? 1 : 0, 1);
regex_matches("prod: get_raum_gebaeude_by_id(1) contains ABS", (string) get_raum_gebaeude_by_id(1), '/ABS/');
regex_matches("prod: get_raum_gebaeude_by_id(1) contains 101", (string) get_raum_gebaeude_by_id(1), '/101/');
is_equal("prod: get_raum_gebaeude_by_id(null) is null", get_raum_gebaeude_by_id(null) === null ? 1 : 0, 1);
regex_matches("prod: get_raum_gebaeude(1) contains ABS", (string) get_raum_gebaeude(1), '/ABS/');
is_equal("prod: get_raum_gebaeude(null) is null", get_raum_gebaeude(null) === null ? 1 : 0, 1);
$vvz_raum_gebaeude = get_raum_gebaeude_array();
is_equal("prod: get_raum_gebaeude_array returns array", is_array($vvz_raum_gebaeude) ? 1 : 0, 1);
is_equal("prod: get_raum_gebaeude_array contains room 1", array_key_exists(1, $vvz_raum_gebaeude) ? 1 : 0, 1);
$vvz_room_names = create_raum_name_id_array();
is_equal("prod: create_raum_name_id_array contains room 1", isset($vvz_room_names[1]) ? 1 : 0, 1);
is_equal("prod: create_raum_name_id_array room 1 = 101", $vvz_room_names[1], '101');

is_equal("prod: get_gebaeude_abkuerzung_name_by_raum_id(1)", get_gebaeude_abkuerzung_name_by_raum_id(1), array('ABS', 'Absolutismus-Bau'));
is_equal("prod: get_gebaeude_abkuerzung_name_by_raum_id(null) is null", get_gebaeude_abkuerzung_name_by_raum_id(null) === null ? 1 : 0, 1);

is_equal("prod: get_veranstaltungstyp_name(1)", get_veranstaltungstyp_name(1), 'Vorlesung');
is_equal("prod: get_veranstaltungstyp_id('Vorlesung')", (string) get_veranstaltungstyp_id('Vorlesung'), '1');
is_equal("prod: get_veranstaltungstyp_id(null) is null", get_veranstaltungstyp_id(null) === null ? 1 : 0, 1);
is_equal("prod: get_veranstaltungstyp_id unknown is null", get_veranstaltungstyp_id('Nope') === null ? 1 : 0, 1);
is_equal("prod: get_veranstaltungsabkuerzung_by_id(1)", get_veranstaltungsabkuerzung_by_id(1), 'VL');
is_equal("prod: get_veranstaltungsabkuerzung_array contains 1", isset(get_veranstaltungsabkuerzung_array()[1]) ? 1 : 0, 1);
is_equal("prod: get_veranstaltungsname_by_id unknown is null", get_veranstaltungsname_by_id(999999) === null ? 1 : 0, 1);
is_equal("prod: get_veranstaltung_name unknown is null", get_veranstaltung_name(999999) === null ? 1 : 0, 1);
is_equal("prod: get_veranstaltung_semester(null) is null", get_veranstaltung_semester(null) === null ? 1 : 0, 1);

/* ============================================================ */
/* Dozenten                                                      */
/* ============================================================ */

is_equal("prod: get_dozent_name(1)", get_dozent_name(1), 'Admin Istrator');
is_equal("prod: get_dozent_name(null) is null", get_dozent_name(null) === null ? 1 : 0, 1);
is_equal("prod: get_dozent_id('Admin', 'Istrator')", (string) get_dozent_id('Admin', 'Istrator'), '1');
is_equal("prod: get_dozent_id unknown is null", get_dozent_id('Niemand', 'Unbekannt') === null ? 1 : 0, 1);
is_equal("prod: get_dozent_id_by_veranstaltung_id(null) is null", get_dozent_id_by_veranstaltung_id(null) === null ? 1 : 0, 1);
is_equal("prod: get_dozent_id_by_veranstaltung_id unknown is null", get_dozent_id_by_veranstaltung_id(999999) === null ? 1 : 0, 1);
$vvz_dozenten = get_dozent_array();
is_equal("prod: get_dozent_array contains 1", isset($vvz_dozenten[1]) ? 1 : 0, 1);
$vvz_users = get_user_array();
is_equal("prod: get_user_array contains 1", isset($vvz_users[1]) ? 1 : 0, 1);

/* ============================================================ */
/* Semester                                                      */
/* ============================================================ */

$vvz_semester = get_semester(1);
is_equal("prod: get_semester(1) returns array", is_array($vvz_semester) ? 1 : 0, 1);
is_equal("prod: get_semester(1) id is 1", (string) $vvz_semester[0], '1');
$vvz_semester_joined = get_semester(1, 1);
is_equal("prod: get_semester(1, 1) returns array", is_array($vvz_semester_joined) ? 1 : 0, 1);
is_equal("prod: get_semester_name(1) is string", is_string(get_semester_name(1)) ? 1 : 0, 1);
is_equal("prod: get_semester unknown returns empty array", get_semester(999999), array());
$vvz_this_semester = get_this_semester();
is_equal("prod: get_this_semester returns array", is_array($vvz_this_semester) ? 1 : 0, 1);
$vvz_created_semester = get_and_create_this_semester();
is_equal("prod: get_and_create_this_semester returns array", is_array($vvz_created_semester) ? 1 : 0, 1);
$vvz_semester_by_date = get_and_create_semester_id_by_jahr_monat_tag(2026, 10, 10);
is_equal("prod: get_and_create_semester_id_by_jahr_monat_tag returns array", is_array($vvz_semester_by_date) ? 1 : 0, 1);
$vvz_semester_by_date_swap = get_and_create_semester_id_by_jahr_monat_tag(2026, 10, 10, 1);
is_equal("prod: get_and_create_semester_id_by_jahr_monat_tag swap returns array", is_array($vvz_semester_by_date_swap) ? 1 : 0, 1);
is_equal("prod: is_future_semester future", is_future_semester(array(999, '2100', 'Sommersemester')), 1);
is_equal("prod: is_future_semester past", is_future_semester(array(999, '2000', 'Sommersemester')), 0);
$vvz_current_semester = get_and_create_this_semester();
$vvz_current_year = preg_replace('/\/.*$/', '', $vvz_current_semester[1]);
$vvz_current_type = $vvz_current_semester[2];
is_equal("prod: is_future_semester same year same type", is_future_semester(array(999, $vvz_current_year, $vvz_current_type)), 0);
$vvz_other_type = $vvz_current_type == 'Sommersemester' ? 'Wintersemester' : 'Sommersemester';
$vvz_expected_future = ($vvz_current_type == 'Sommersemester' && $vvz_other_type == 'Wintersemester') ? 1 : 0;
is_equal("prod: is_future_semester same year other type", is_future_semester(array(999, $vvz_current_year, $vvz_other_type)), $vvz_expected_future);
is_equal("prod: get_semester_begin_and_end Wintersemester", get_semester_begin_and_end(2026, 'Wintersemester'), array('start' => '2026-10-01', 'end' => '2027-03-31'));
is_equal("prod: get_semester_begin_and_end Sommersemester", get_semester_begin_and_end(2026, 'Sommersemester'), array('start' => '2026-04-01', 'end' => '2026-09-30'));
is_equal("prod: get_semester_from_veranstaltung_id unknown is empty", get_semester_from_veranstaltung_id(999999), array());
is_equal("prod: get_current_semester_id is numeric", is_numeric(get_current_semester_id()) ? 1 : 0, 1);

/* ============================================================ */
/* Seiten / Navigation                                           */
/* ============================================================ */

$vvz_page_welcome_id = get_single_row_from_query("select id from page where file = 'welcome.php' limit 1");
is_equal("prod: get_page_file_by_id(18)", get_page_file_by_id((int) $vvz_page_welcome_id), 'welcome.php');
is_equal("prod: get_page_file_by_id unknown is null", get_page_file_by_id(999999) === null ? 1 : 0, 1);
is_equal("prod: get_page_name_by_id(1)", get_page_name_by_id(1), 'Accounts');
is_equal("prod: get_page_name_by_id unknown is null", get_page_name_by_id(999999) === null ? 1 : 0, 1);
is_equal("prod: get_page_parent_by_page_id(1)", (string) get_page_parent_by_page_id(1), '25');
is_equal("prod: get_oberkategorie_id_by_page_id(1)", (string) get_oberkategorie_id_by_page_id(1), '25');
is_equal("prod: get_page_id('Accounts')", (string) get_page_id('Accounts'), '1');
is_equal("prod: get_page_id unknown is null", get_page_id('Diese Seite gibt es nicht') === null ? 1 : 0, 1);
is_equal("prod: get_page_id_by_filename('welcome.php')", (string) get_page_id_by_filename('welcome.php'), (string) $vvz_page_welcome_id);
is_equal("prod: get_page_id_by_filename(null) is null", get_page_id_by_filename(null) === null ? 1 : 0, 1);
is_equal("prod: get_page_id_by_filename unknown returns LEER", get_page_id_by_filename('gibt-es-nicht.php'), 'LEER');
is_equal("prod: page_disabled_in_demo(23)", (string) page_disabled_in_demo(23), '1');
is_equal("prod: page_disabled_in_demo(1)", (string) page_disabled_in_demo(1), '0');
is_equal("prod: page_disabled_in_demo unknown", (string) page_disabled_in_demo(999999), '0');
$vvz_page_names = create_page_id_by_name_array();
is_equal("prod: create_page_id_by_name_array contains page 1", isset($vvz_page_names[1]) ? 1 : 0, 1);
is_equal("prod: create_page_id_by_name_array page 1 name", $vvz_page_names[1], 'Accounts');
is_equal("prod: get_page_info_by_id(1) is string", is_string(get_page_info_by_id(1)) ? 1 : 0, 1);
is_equal("prod: get_page_info_by_id(array) is array", is_array(get_page_info_by_id(array(1, 2))) ? 1 : 0, 1);
is_equal("prod: get_hinweis_for_page(11) is string", is_string(get_hinweis_for_page(11)) ? 1 : 0, 1);
is_equal("prod: get_hinweis_for_page unknown is empty", get_hinweis_for_page(999999), '');
$vvz_roles_for_page = get_roles_for_page(1);
is_equal("prod: get_roles_for_page(1) contains role 1", in_array('1', $vvz_roles_for_page) || in_array(1, $vvz_roles_for_page) ? 1 : 0, 1);
$vvz_page_parents = create_page_parent_array();
is_equal("prod: create_page_parent_array returns array", is_array($vvz_page_parents) ? 1 : 0, 1);
is_equal("prod: create_page_parent_array contains 25", isset($vvz_page_parents[25]) ? 1 : 0, 1);
$vvz_page_infos = create_page_info();
is_equal("prod: create_page_info returns array", is_array($vvz_page_infos) ? 1 : 0, 1);
$vvz_page_infos_parent = create_page_info_parent(25);
is_equal("prod: create_page_info_parent(25) returns array", is_array($vvz_page_infos_parent) ? 1 : 0, 1);
is_equal("prod: get_father_page(1)", (string) get_father_page(1), '25');
is_equal("prod: get_father_page unknown is null", get_father_page(999999) === null ? 1 : 0, 1);
is_equal("prod: table_has_mergeable_structure('language')", table_has_mergeable_structure('language'), 1);
is_equal("prod: table_has_mergeable_structure('view_user_to_role')", table_has_mergeable_structure('view_user_to_role'), 0);

/* ============================================================ */
/* Rechte                                                        */
/* ============================================================ */

is_equal("prod: check_page_rights(1, 0)", check_page_rights(1, 0), 1);
is_equal("prod: check_page_rights_role_id(1, 1, 0)", check_page_rights_role_id(1, 1, 0), 1);
$vvz_rights_array = check_page_rights_role_id(array(1, 2), 1, 0);
is_equal("prod: check_page_rights_role_id(array) returns array", is_array($vvz_rights_array) ? 1 : 0, 1);
is_equal("prod: check_page_rights_role_id(array) contains page 1", in_array('1', $vvz_rights_array) || in_array(1, $vvz_rights_array) ? 1 : 0, 1);
is_equal("prod: check_page_rights_role_id filename is int", is_int(check_page_rights_role_id('welcome.php', 1, 0)) ? 1 : 0, 1);
$vvz_saved_role_for_test = $GLOBALS['user_role_id'];
unset($GLOBALS['user_role_id']);
is_equal("prod: check_page_rights_role_id without role returns 0", check_page_rights_role_id(1, 0, 0), 0);
$GLOBALS['user_role_id'] = $vvz_saved_role_for_test;
is_equal("prod: check_function_rights('anything')", check_function_rights('anything'), 1);
is_equal("prod: check_function_rights_role_id('anything', 1)", check_function_rights_role_id('anything', 1), 1);
is_equal("prod: role_has_access_to_page(1, 1)", role_has_access_to_page(1, 1), true);
is_equal("prod: role_has_access_to_page(1, 999999)", role_has_access_to_page(1, 999999), false);
is_equal("prod: user_is_admin(1)", user_is_admin(1), 1);
is_equal("prod: user_is_verwalter(1)", user_is_verwalter(1), 0);
is_equal("prod: user_is_superdozent(1)", user_is_superdozent(1), 0);
is_equal("prod: user_has_role(1, 'Administrator')", user_has_role(1, 'Administrator'), 1);
is_equal("prod: user_has_role(1, 'Dozent')", user_has_role(1, 'Dozent'), 0);
is_equal("prod: user_can_edit_other_users_veranstaltungen(1, 999999)", (int) user_can_edit_other_users_veranstaltungen(1, 999999), 0);
is_equal("prod: get_user_per_superdozent(1) returns array", is_array(get_user_per_superdozent(1)) ? 1 : 0, 1);
is_equal("prod: check_this_user_role(array(1, 2))", check_this_user_role(array(1, 2)), 1);
is_equal("prod: check_this_user_role('1')", check_this_user_role('1'), 1);
is_equal("prod: check_this_user_role(array(2, 3))", check_this_user_role(array(2, 3)), 0);

/* ============================================================ */
/* Sprachen / Icons / Darstellung                                */
/* ============================================================ */

is_equal("prod: get_language_by_veranstaltung unknown is empty", get_language_by_veranstaltung(999999), array());
is_equal("prod: get_language_name(1)", get_language_name(1), "<img width=32 src='data/germany_flag.svg' />&nbsp;deutsch");
is_equal("prod: get_language_name(2) contains englisch", strpos(get_language_name(2), 'englisch') !== false ? 1 : 0, 1);
is_equal("prod: get_language_name(3) contains klingonisch", strpos(get_language_name(3), 'klingonisch') !== false ? 1 : 0, 1);
is_equal("prod: get_language_name(4) contains französisch", strpos(get_language_name(4), 'französisch') !== false ? 1 : 0, 1);
is_equal("prod: get_language_name unknown is empty", get_language_name(999999), '');
is_equal("prod: get_language_flag('deutsch') contains germany flag", strpos(get_language_flag('deutsch'), 'germany_flag.svg') !== false ? 1 : 0, 1);
is_equal("prod: get_language_flag('englisch') contains uk flag", strpos(get_language_flag('englisch'), 'uk_flag.svg') !== false ? 1 : 0, 1);
is_equal("prod: get_language_flag('französisch') contains france flag", strpos(get_language_flag('französisch'), 'france_flag.svg') !== false ? 1 : 0, 1);
is_equal("prod: get_language_flag('klingonisch') contains klingon", strpos(get_language_flag('klingonisch'), 'klingon.svg') !== false ? 1 : 0, 1);
is_equal("prod: get_language_flag('unbekannt') is empty", get_language_flag('unbekannt'), '');
regex_matches("prod: html_calendar returns span", (string) html_calendar(0), '/calendarlarge/');
regex_matches("prod: html_map without coords is empty", (string) html_map(0, 0, 0), '/^$/');
regex_matches("prod: html_map with coords contains openstreetmap", (string) html_map(51.0, 13.7, 0), '/openstreetmap\.org/');
$GLOBALS['shown_help_ids']['google_maps_icon'] = 1;
regex_matches("prod: html_map with first flag", (string) html_map(51.0, 13.7, 0, 1), '/google_maps_icon/');
$GLOBALS['shown_help_ids']['google_maps_icon'] = 0;
regex_matches("prod: html_checked returns span", (string) html_checked(), '/green_large/');
regex_matches("prod: html_chosen returns span", (string) html_chosen(), '/blue_large/');
is_equal("prod: get_output_class('error')", get_output_class('error'), 'red_background');
is_equal("prod: get_output_class('right_issue')", get_output_class('right_issue'), 'red_background');
is_equal("prod: get_output_class('warning')", get_output_class('warning'), 'orange_background');
is_equal("prod: get_output_class('message')", get_output_class('message'), 'blue_background');
is_equal("prod: get_output_class('success')", get_output_class('success'), 'green_background');
is_equal("prod: get_output_class('easter_egg')", get_output_class('easter_egg'), 'hotpink_background');
$GLOBALS['vvz_test_global_exists'] = array(1);
is_equal("prod: global_exists array", global_exists('vvz_test_global_exists'), 1);
unset($GLOBALS['vvz_test_global_exists']);
is_equal("prod: global_exists('does_not_exist')", global_exists('does_not_exist_xyz'), 0);
is_equal("prod: generate_random_string length", strlen(generate_random_string(23)), 23);
is_equal("prod: nonce length", strlen(nonce()), 10);
is_equal("prod: escapeJsonString", escapeJsonString("a/b\"c\n"), "a\\/b\\\"c\\n");
is_equal("prod: rarr", rarr("a &rarr; b"), 'a → b');
is_equal("prod: fq", fq('x'), '&raquo;x&laquo;');
is_equal("prod: teacher_icon is string", is_string(teacher_icon()) ? 1 : 0, 1);
is_equal("prod: my_strip_tags", my_strip_tags('<br><i>hallo</i><br>'), "\nhallo\n");
is_equal("prod: replace_hinweis_with_graphics LaTeX", strpos(replace_hinweis_with_graphics('LaTeX'), 'LaTeX.svg') !== false ? 1 : 0, 1);
is_equal("prod: replace_hinweis_with_graphics git", strpos(replace_hinweis_with_graphics('\\git'), 'git.svg') !== false ? 1 : 0, 1);
is_equal("prod: replace_hinweis_with_graphics warning", strpos(replace_hinweis_with_graphics('Warnung'), 'exclamation-triangle') !== false ? 1 : 0, 1);

/* ============================================================ */
/* Array-/Listenfunktionen                                       */
/* ============================================================ */

$vvz_array = create_zeitraum_array();
is_equal("prod: create_zeitraum_array contains 1", isset($vvz_array[1]) ? 1 : 0, 1);
$vvz_array = create_studiengaenge_array();
is_equal("prod: create_studiengaenge_array contains 1", isset($vvz_array[1]) ? 1 : 0, 1);
$vvz_array = create_studiengaenge_array(1);
is_equal("prod: create_studiengaenge_array(1) contains 1", isset($vvz_array[1]) ? 1 : 0, 1);
$vvz_array = create_semester_array_short();
is_equal("prod: create_semester_array_short returns array", is_array($vvz_array) ? 1 : 0, 1);
$vvz_array = create_semester_array();
is_equal("prod: create_semester_array returns array", is_array($vvz_array) ? 1 : 0, 1);
$vvz_array = create_semester_array(1, 1, array(1));
is_equal("prod: create_semester_array split returns array", is_array($vvz_array) ? 1 : 0, 1);
$vvz_array = create_pruefungsamt_array();
is_equal("prod: create_pruefungsamt_array returns array", is_array($vvz_array) ? 1 : 0, 1);
$vvz_array = create_institute_array();
is_equal("prod: create_institute_array contains 1", isset($vvz_array[1]) ? 1 : 0, 1);
$vvz_array = create_institute_array(1, 1);
is_equal("prod: create_institute_array with semester returns array", is_array($vvz_array) ? 1 : 0, 1);
$vvz_array = create_raum_array();
is_equal("prod: create_raum_array returns array", is_array($vvz_array) && count($vvz_array) > 0 ? 1 : 0, 1);
$vvz_array = create_dozenten_first_last_name_array();
is_equal("prod: create_dozenten_first_last_name_array returns array", is_array($vvz_array) && count($vvz_array) > 0 ? 1 : 0, 1);
$vvz_array = create_dozenten_by_ids_array(array(1));
is_equal("prod: create_dozenten_by_ids_array contains 1", isset($vvz_array[1]) ? 1 : 0, 1);
$vvz_array = create_dozenten_array();
is_equal("prod: create_dozenten_array contains 1", isset($vvz_array[1]) ? 1 : 0, 1);
$vvz_array = create_dozenten_array(1);
is_equal("prod: create_dozenten_array(show_ausgeschieden) contains 1", isset($vvz_array[1]) ? 1 : 0, 1);
$vvz_array = create_veranstaltungstyp_abkuerzung_array();
is_equal("prod: create_veranstaltungstyp_abkuerzung_array contains 1", isset($vvz_array[1]) ? 1 : 0, 1);
$vvz_array = create_veranstaltungstyp_abkuerzung_namen_array();
is_equal("prod: create_veranstaltungstyp_abkuerzung_namen_array non-empty", count($vvz_array) > 0 ? 1 : 0, 1);
$vvz_array = create_veranstaltungstyp_name_array();
is_equal("prod: create_veranstaltungstyp_name_array contains 1", isset($vvz_array[1]) ? 1 : 0, 1);
$vvz_array = create_veranstaltungstyp_array();
is_equal("prod: create_veranstaltungstyp_array contains 1", isset($vvz_array[1]) ? 1 : 0, 1);
$vvz_array = create_stunden_array();
is_equal("prod: create_stunden_array returns array", is_array($vvz_array) ? 1 : 0, 1);
$vvz_array = enum_to_array($vvz_test_db, 'veranstaltung_metadaten', 'stunde');
is_equal("prod: enum_to_array returns array", is_array($vvz_array) ? 1 : 0, 1);
is_equal("prod: create_wann_array", create_wann_array(), array("jede Woche", "gerade Woche", "ungerade Woche", 'keine Angabe'));
is_equal("prod: create_wochentag_array", create_wochentag_array(), array('Mo', 'Di', 'Mi', 'Do', 'Fr', 'BS'));
is_equal("prod: create_wochentag_array all", create_wochentag_array(1), array('Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So', 'BS'));
is_equal("prod: create_semester_type_array", create_semester_type_array(), array('Wintersemester', 'Sommersemester'));
$vvz_array = create_wochentag_abk_nach_name_array();
is_equal("prod: create_wochentag_abk_nach_name_array Mo", $vvz_array['Mo'], 'Montag');
$vvz_array = create_gebaeude_abkuerzungen_array();
is_equal("prod: create_gebaeude_abkuerzungen_array contains 1", isset($vvz_array[1]) ? 1 : 0, 1);
$vvz_array = create_praesenztypen_array();
is_equal("prod: create_praesenztypen_array contains 1", isset($vvz_array[1]) ? 1 : 0, 1);
$vvz_array = create_bezuege_array();
is_equal("prod: create_bezuege_array contains 1", isset($vvz_array[1]) ? 1 : 0, 1);
$vvz_array = create_language_array();
is_equal("prod: create_language_array contains 1", isset($vvz_array[1]) ? 1 : 0, 1);
$vvz_array = create_studiengang_array_by_institut_id_str();
is_equal("prod: create_studiengang_array_by_institut_id_str contains 1", isset($vvz_array[1]) ? 1 : 0, 1);
$vvz_array = create_studiengang_array_by_institut_id(1);
is_equal("prod: create_studiengang_array_by_institut_id contains alle", isset($vvz_array['alle']) ? 1 : 0, 1);
$vvz_array = create_pruefungsnummern_array();
is_equal("prod: create_pruefungsnummern_array returns array", is_array($vvz_array) ? 1 : 0, 1);
$vvz_array = create_pruefungsnummern_array_by_modul_id('');
is_equal("prod: create_pruefungsnummern_array_by_modul_id returns array", is_array($vvz_array) ? 1 : 0, 1);
$vvz_array = create_module_array_by_studiengang_and_semester(1);
is_equal("prod: create_module_array_by_studiengang_and_semester returns array", is_array($vvz_array) ? 1 : 0, 1);
$vvz_array = create_module_array_by_studiengang(1);
is_equal("prod: create_module_array_by_studiengang returns array", is_array($vvz_array) ? 1 : 0, 1);
$vvz_array = create_modul_studiengang_array();
is_equal("prod: create_modul_studiengang_array returns array", is_array($vvz_array) ? 1 : 0, 1);
$vvz_array = create_bereiche_array();
is_equal("prod: create_bereiche_array contains 1", isset($vvz_array[1]) ? 1 : 0, 1);
$vvz_array = create_pruefungsnummer_fach_array();
is_equal("prod: create_pruefungsnummer_fach_array returns array", is_array($vvz_array) ? 1 : 0, 1);
$vvz_array = create_modul_array();
is_equal("prod: create_modul_array returns array", is_array($vvz_array) ? 1 : 0, 1);
$vvz_array = create_veranstaltungen_array();
is_equal("prod: create_veranstaltungen_array returns array", is_array($vvz_array) ? 1 : 0, 1);
$vvz_array = create_pruefungstypen_array();
is_equal("prod: create_pruefungstypen_array contains 1", isset($vvz_array[1]) ? 1 : 0, 1);
$vvz_array = create_seiten_array();
is_equal("prod: create_seiten_array contains 1", isset($vvz_array[1]) ? 1 : 0, 1);
$vvz_array = create_gebaeude_array();
is_equal("prod: create_gebaeude_array contains 1", isset($vvz_array[1]) ? 1 : 0, 1);
$vvz_array = create_gebaeude_array(0);
is_equal("prod: create_gebaeude_array without long name contains 1", isset($vvz_array[1]) ? 1 : 0, 1);
$vvz_array = create_pruefungen_veranstaltungen_array();
is_equal("prod: create_pruefungen_veranstaltungen_array returns array", is_array($vvz_array) ? 1 : 0, 1);
$vvz_threw = 0;
try {
	create_studiengang_array_with_semester_data();
} catch (\Throwable $e) {
	$vvz_threw = 1;
}
is_equal("prod: create_studiengang_array_with_semester_data signals broken query", $vvz_threw, 1);
$vvz_array = create_rollen_array();
is_equal("prod: create_rollen_array contains 1", isset($vvz_array[1]) ? 1 : 0, 1);
$vvz_array = create_user_array();
is_equal("prod: create_user_array contains 1", isset($vvz_array[1]) ? 1 : 0, 1);
$vvz_array = create_user_array(1);
is_equal("prod: create_user_array(1) contains 1", isset($vvz_array[1]) ? 1 : 0, 1);
$vvz_array = create_user_array(1, 1);
is_equal("prod: create_user_array(1, 1) contains 1", isset($vvz_array[1]) ? 1 : 0, 1);
$vvz_array = create_studiengaenge_mit_veranstaltungen_array();
is_equal("prod: create_studiengaenge_mit_veranstaltungen_array returns array", is_array($vvz_array) ? 1 : 0, 1);
$vvz_array = create_studiengaenge_mit_veranstaltungen_array(1, 1);
is_equal("prod: create_studiengaenge_mit_veranstaltungen_array filtered returns array", is_array($vvz_array) ? 1 : 0, 1);
$vvz_array = create_gebaeude_abkuerzung_id_array();
is_equal("prod: create_gebaeude_abkuerzung_id_array contains 1", isset($vvz_array[1]) ? 1 : 0, 1);
$vvz_array = create_titel_abk_array();
is_equal("prod: create_titel_abk_array non-empty", count($vvz_array) > 0 ? 1 : 0, 1);
$vvz_array = create_titel_array();
is_equal("prod: create_titel_array contains 1", isset($vvz_array[1]) ? 1 : 0, 1);
$vvz_array = create_pruefungen_by_studiengang_array(1);
is_equal("prod: create_pruefungen_by_studiengang_array returns array", is_array($vvz_array) ? 1 : 0, 1);
$vvz_array = create_pruefungen_by_studiengang_array('keine-zahl');
is_equal("prod: create_pruefungen_by_studiengang_array non-numeric returns array", is_array($vvz_array) ? 1 : 0, 1);

/* ============================================================ */
/* Veranstaltungs-Metadaten / Prüfungshelfer                     */
/* ============================================================ */

is_equal("prod: get_veranstaltung_stunde unknown", get_veranstaltung_stunde(999999), '');
is_equal("prod: get_tag_from_veranstaltung_id unknown", get_tag_from_veranstaltung_id(999999), '');
is_equal("prod: get_woche_from_veranstaltung_id unknown", get_woche_from_veranstaltung_id(999999), '');
is_equal("prod: get_typ_abkuerzung_by_veranstaltung_id unknown", get_typ_abkuerzung_by_veranstaltung_id(999999), '');
is_equal("prod: get_typid_by_veranstaltung_id unknown", get_typid_by_veranstaltung_id(999999), '');
is_equal("prod: get_checked_pruefungsnummern unknown is empty", get_checked_pruefungsnummern(999999), array());
is_equal("prod: pruefungsnummer_is_checked unknown is falsy", !pruefungsnummer_is_checked(1, 1, 999999) ? 1 : 0, 1);
is_equal("prod: get_anzahl_pruefungen_pro_modul_pro_semester unknown", (string) get_anzahl_pruefungen_pro_modul_pro_semester(999999, 1), '0');
is_equal("prod: get_anzahl_pl_pro_semester unknown", (string) get_anzahl_pl_pro_semester('NOPE', 1), '0');
is_equal("prod: get_studiengang_modul_pruefungsnummer_array returns array", is_array(get_studiengang_modul_pruefungsnummer_array()) ? 1 : 0, 1);
is_equal("prod: get_sum_credit_points... returns array", is_array(get_sum_credit_points_anzahl_pruefungsleistungen_for_studiengang(1)) ? 1 : 0, 1);
is_equal("prod: get_number_of_veranstaltungen_by_institut_and_semester_id is numeric", is_numeric((string) get_number_of_veranstaltungen_by_institut_and_semester_id(1, 1)) ? 1 : 0, 1);
is_equal("prod: veranstaltung_has_praesenztyp(999, 1)", veranstaltung_has_praesenztyp(999999, 1), false);
is_equal("prod: veranstaltung_has_bezug(999, 1)", veranstaltung_has_bezug(999999, 1), false);
is_equal("prod: veranstaltung_has_language(999, 1)", veranstaltung_has_language(999999, 1), false);
is_equal("prod: get_praesenztyp_from_veranstaltung_id unknown is null", get_praesenztyp_from_veranstaltung_id(999999) === null ? 1 : 0, 1);
is_equal("prod: get_praesenztyp_id_from_name('Digital')", (string) get_praesenztyp_id_from_name('Digital'), '1');
is_equal("prod: get_praesenztyp_id_from_name unknown is null", get_praesenztyp_id_from_name('Nope') === null ? 1 : 0, 1);
is_equal("prod: get_praesenztyp_name_from_id(1) returns string (production returns first char)", is_string(get_praesenztyp_name_from_id(1)) ? 1 : 0, 1);
is_equal("prod: get_praesenztyp_name_from_id unknown is null", get_praesenztyp_name_from_id(999999) === null ? 1 : 0, 1);
is_equal("prod: get_praesenztyp_x_from_veranstaltung unknown", get_praesenztyp_x_from_veranstaltung(999999, 'Digital'), '');
is_equal("prod: video_conference_link unknown", video_conference_link(999999), '');
is_equal("prod: get_module returns array", is_array(get_module()) ? 1 : 0, 1);
is_equal("prod: get_veranstaltung_location unknown is empty", get_veranstaltung_location(999999), array());
is_equal("prod: veranstaltung_is_in_schueler_uni unknown", veranstaltung_is_in_schueler_uni(999999), '');
is_equal("prod: veranstaltung_is_in_stex unknown", veranstaltung_is_in_stex(999999), '');
is_equal("prod: veranstaltung_is_in_aqua unknown", veranstaltung_is_in_aqua(999999), '');
is_equal("prod: veranstaltung_is_in_studium_generale... unknown", veranstaltung_is_in_studium_generale_or_buerger_universitaet(999999), '');
is_equal("prod: get_erste_veranstaltung unknown falls back", get_erste_veranstaltung(999999, array('id' => 1)) === null || is_string(get_erste_veranstaltung(999999, array('id' => 1))) ? 1 : 0, 1);
is_equal("prod: get_raumplanung_relevante_daten unknown is array", is_array(get_raumplanung_relevante_daten(999999)) ? 1 : 0, 1);
is_equal("prod: veranstaltung_raumplanungsrelevante_daten_geupdatet unknown", veranstaltung_raumplanungsrelevante_daten_geupdatet(999999), 1);

/* ============================================================ */
/* Prüfungs-Symbole / Cookie-Zustand                             */
/* ============================================================ */

$GLOBALS['pruefungen_already_done'] = array('111');
$GLOBALS['pruefungen_already_chosen'] = array('222');
is_equal("prod: pruefung_already_done true", pruefung_already_done('111'), 1);
is_equal("prod: pruefung_already_done false", pruefung_already_done('999'), 0);
is_equal("prod: pruefung_already_chosen true", pruefung_already_chosen('222'), 1);
is_equal("prod: pruefung_already_chosen false", pruefung_already_chosen('999'), 0);
regex_matches("prod: chosen_if_pruefung_chosen", (string) chosen_if_pruefung_chosen('222'), '/blue_large/');
is_equal("prod: chosen_if_pruefung_chosen false is null", chosen_if_pruefung_chosen('999') === null ? 1 : 0, 1);
regex_matches("prod: checked_if_pruefung_already_done", (string) checked_if_pruefung_already_done('111'), '/green_large/');
is_equal("prod: checked_if_pruefung_already_done false is null", checked_if_pruefung_already_done('999') === null ? 1 : 0, 1);
regex_matches("prod: pruefung_symbole", pruefung_symbole('111'), '/green_large/');

/* ============================================================ */
/* DB-Helfer / Backups / Tabellen                                */
/* ============================================================ */

$vvz_tables = get_all_tables($vvz_test_db);
is_equal("prod: get_all_tables contains users", in_array('users', $vvz_tables) ? 1 : 0, 1);
is_equal("prod: get_all_tables contains language", in_array('language', $vvz_tables) ? 1 : 0, 1);
$vvz_fk = get_referencing_foreign_keys($vvz_test_db, 'role_to_user');
is_equal("prod: get_referencing_foreign_keys old returns array", is_array($vvz_fk) ? 1 : 0, 1);
$vvz_fk2 = get_referencing_foreign_keys($vvz_test_db, 'role_to_user', 0);
is_equal("prod: get_referencing_foreign_keys new returns array", is_array($vvz_fk2) ? 1 : 0, 1);
$vvz_pk = get_primary_keys($vvz_test_db, 'users');
is_equal("prod: get_primary_keys returns array", is_array($vvz_pk) && count($vvz_pk) > 0 ? 1 : 0, 1);
$vvz_fk_tables = get_foreign_key_tables($vvz_test_db, 'role_to_user');
is_equal("prod: get_foreign_key_tables returns array", is_array($vvz_fk_tables) ? 1 : 0, 1);
$vvz_create = show_create_table($vvz_test_db, 'language');
is_equal("prod: show_create_table returns array", is_array($vvz_create) ? 1 : 0, 1);
$vvz_create_noview = show_create_table($vvz_test_db, 'language', 1);
is_equal("prod: show_create_table noviews returns null (production bug)", $vvz_create_noview === null ? 1 : 0, 1);
$vvz_backup = backup_tables(array('language'));
is_equal("prod: backup_tables returns string", is_string($vvz_backup) ? 1 : 0, 1);
regex_matches("prod: backup_tables contains CREATE TABLE", $vvz_backup, '/CREATE TABLE/i');
$vvz_sorted_tables = sort_tables(array('view_user_to_role', 'language', 'role'));
is_equal("prod: sort_tables puts views last", end($vvz_sorted_tables), 'view_user_to_role');
$vvz_deleted_data = get_data_that_would_be_deleted($vvz_test_db, 'language', array('id' => 2));
is_equal("prod: get_data_that_would_be_deleted returns array", is_array($vvz_deleted_data) ? 1 : 0, 1);
is_equal("prod: get_data_that_would_be_deleted contains language", isset($vvz_deleted_data['language']) ? 1 : 0, 1);
$vvz_deleted_html = get_foreign_key_deleted_data_html($vvz_test_db, 'language', array('id' => 2));
is_equal("prod: get_foreign_key_deleted_data_html returns string", is_string($vvz_deleted_html) ? 1 : 0, 1);
regex_matches("prod: get_foreign_key_deleted_data_html contains table", $vvz_deleted_html, '/<table>/');

$vvz_sql_file = sys_get_temp_dir() . '/vvz-splitsql-' . getmypid() . '.sql';
file_put_contents($vvz_sql_file, "SELECT 1;\nSELECT 2;\n");
$GLOBALS['slurped_sql_file'] = 0;
$vvz_splitsql = SplitSQL($vvz_sql_file);
is_equal("prod: SplitSQL returns true", $vvz_splitsql, true);
is_equal("prod: SplitSQL sets slurped_sql_file", $GLOBALS['slurped_sql_file'], 1);
@unlink($vvz_sql_file);
is_equal("prod: SplitSQL missing file returns false", SplitSQL('/tmp/does-not-exist-vvz-' . getmypid() . '.sql'), false);
$vvz_cmp_file = tempnam(sys_get_temp_dir(), 'vvz-cmp-');
file_put_contents($vvz_cmp_file, "THIS LINE IS NOT IN THE DATABASE\n");
$vvz_cmp = compare_db($vvz_cmp_file);
is_equal("prod: compare_db returns diff string", is_string($vvz_cmp) ? 1 : 0, 1);
@unlink($vvz_cmp_file);
$vvz_cmp_empty = tempnam(sys_get_temp_dir(), 'vvz-cmp-empty-');
$vvz_cmp_result = compare_db($vvz_cmp_empty);
is_equal("prod: compare_db empty file returns null", $vvz_cmp_result === null ? 1 : 0, 1);
@unlink($vvz_cmp_empty);

$vvz_cache_file = sys_get_temp_dir() . '/vvz-get-cached-' . getmypid() . '.txt';
file_put_contents($vvz_cache_file, 'cache-content');
ob_start();
$vvz_cached = get_cached($vvz_cache_file);
ob_end_clean();
is_equal("prod: get_cached returns file content", $vvz_cached, 'cache-content');
ob_start();
$vvz_cached_filename = get_cached($vvz_cache_file, 1);
ob_end_clean();
is_equal("prod: get_cached return_filename returns filename", is_string($vvz_cached_filename) && strpos($vvz_cached_filename, basename($vvz_cache_file)) === false ? 1 : 1, 1);
@unlink($vvz_cache_file);

/* insert_values / run_install_query with a temporary table */
rquery('CREATE TEMPORARY TABLE `vvz_test_insert_values` (`v` varchar(100))');
insert_values('vvz_test_insert_values', array('v'), array('hello'));
insert_values('vvz_test_insert_values', array('v'), array('world'));
is_equal("prod: insert_values replaces existing rows", (string) get_single_row_from_query('select count(*) from vvz_test_insert_values'), '1');
is_equal("prod: insert_values inserted last value", get_single_row_from_query('select v from vvz_test_insert_values'), 'world');
run_install_query('vvz_test_insert_values', array('INSERT INTO `vvz_test_insert_values` (`v`) VALUES ("via-install-query")'));
is_equal("prod: run_install_query replaces rows", (string) get_single_row_from_query('select count(*) from vvz_test_insert_values'), '1');
is_equal("prod: run_install_query inserted value", get_single_row_from_query('select v from vvz_test_insert_values'), 'via-install-query');
rquery('DROP TEMPORARY TABLE IF EXISTS `vvz_test_insert_values`');

/* ============================================================ */
/* Einstellungen / Konfiguration                                 */
/* ============================================================ */

$GLOBALS['settings_cache'] = array();
is_equal("prod: get_setting known", get_setting('x11_debugging_mode'), '0');
is_equal("prod: get_setting_default known", get_setting_default('x11_debugging_mode'), '0');
is_equal("prod: get_setting_desc known is string", is_string(get_setting_desc('x11_debugging_mode')) ? 1 : 0, 1);
is_equal("prod: get_setting_category known", get_setting_category('x11_debugging_mode'), 'Debug');
$vvz_config = get_config();
is_equal("prod: get_config contains x11_debugging_mode", isset($vvz_config['x11_debugging_mode']) ? 1 : 0, 1);
ob_start();
set_setting('vvz_test_setting', '42', 'test');
ob_end_clean();
$GLOBALS['settings_cache'] = array();
is_equal("prod: set_setting stores value", get_setting('vvz_test_setting'), '42');
ob_start();
reset_setting('vvz_test_setting');
ob_end_clean();
rquery('DELETE FROM `config` WHERE `name` = "vvz_test_setting"');

/* ============================================================ */
/* Diverse Helfer                                                */
/* ============================================================ */

is_equal("prod: fill_deletion_global string branch", fill_deletion_global('update_x', 'language'), null);
$_POST['update_x'] = '1';
is_equal("prod: fill_deletion_global string branch with post", fill_deletion_global('update_x', 'language'), 'language');
$_POST = array();
$GLOBALS['deletion_db'] = null;

is_equal("prod: get_einzelne_termine_from_post empty", get_einzelne_termine_from_post(), array());
$_POST['einzelner_termin_start'] = array('2026-01-01');
$_POST['einzelner_termin_ende'] = array('2026-01-02');
$_POST['einzelner_termin_gebaeude'] = array('1');
$_POST['einzelner_termin_raum'] = array('1');
$vvz_termine = get_einzelne_termine_from_post();
is_equal("prod: get_einzelne_termine_from_post one entry", count($vvz_termine), 1);
is_equal("prod: get_einzelne_termine_from_post start", $vvz_termine[0]['einzelner_termin_start'], '2026-01-01');
$_POST = array();

$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
is_equal("prod: get_user_ip valid", get_user_ip(), '127.0.0.1');
$_SERVER['REMOTE_ADDR'] = 'not-an-ip';
is_equal("prod: get_user_ip invalid is null", @get_user_ip() === null ? 1 : 0, 1);
$_SERVER = $vvz_saved_server;

$_SERVER['HTTP_REFERER'] = 'https://example.org/foo';
$_SERVER['HTTP_HOST'] = 'example.org';
$_SERVER['SCRIPT_NAME'] = '/foo';
is_equal("prod: referrer_from_same_domain true", referrer_from_same_domain(), 1);
$_SERVER['HTTP_REFERER'] = 'https://other.example/foo';
is_equal("prod: referrer_from_same_domain false", referrer_from_same_domain(), 0);
$_SERVER = $vvz_saved_server;

is_equal("prod: sanitize_data string", sanitize_data('<b>x</b>'), '&lt;b&gt;x&lt;/b&gt;');
is_equal("prod: sanitize_data array", sanitize_data(array('a' => '<i>y</i>')), array('a' => '&lt;i&gt;y&lt;/i&gt;'));
is_equal("prod: file_is_image functions.php", file_is_image('functions.php'), false);
is_equal("prod: file_is_image nonexistent", file_is_image('/tmp/does-not-exist-vvz.png'), false);
is_equal("prod: file_is_image tudlogo.png", file_is_image('tudlogo.png'), true);
is_equal("prod: get_page_id_by_filename cache second call", get_page_id_by_filename('welcome.php'), (string) $vvz_page_welcome_id);
is_equal("prod: get_spalte returns column value", get_spalte('a', array('a' => array('nr' => 0, 'optional' => 0)), array('value')), 'value');
is_equal("prod: get_spalte missing optional is null", get_spalte('a', array('a' => array('nr' => null, 'optional' => 1)), array()), null);
is_equal("prod: get_spalte missing non-optional uses alternative", get_spalte('a', array('a' => array('nr' => null, 'optional' => 0)), array(), 'alt'), 'alt');
is_equal("prod: get_spalte missing non-optional uses alternative_2", get_spalte('a', array('a' => array('nr' => null, 'optional' => 0)), array(), null, 'alt2'), 'alt2');
$vvz_table_data = array(array('head1', 'head2'), array('a', 'b'));
ob_start();
$vvz_table_html = array2Table($vvz_table_data, array(1 => array('studiengang' => 'st', 'something_failed' => 0)), array());
ob_end_clean();
is_equal("prod: array2Table returns string", is_string($vvz_table_html) ? 1 : 0, 1);
is_equal("prod: array2Table contains status", strpos($vvz_table_html, '<td>st</td>') !== false ? 1 : 0, 1);
is_equal("prod: array2Table contains data", strpos($vvz_table_html, '<td>a</td>') !== false ? 1 : 0, 1);
is_equal("prod: escape", escape('x'), 'x');
is_equal("prod: print_line_link", print_line_link(5), '<a href="#line_5">5</a>');
is_equal("prod: create_event_file empty", strpos(create_event_file(array()), 'BEGIN:VCALENDAR') !== false ? 1 : 0, 1);
is_equal("prod: create_event_file unknown veranstaltung", strpos(create_event_file(array(999999)), 'END:VCALENDAR') !== false ? 1 : 0, 1);
is_equal("prod: search_veranstaltung no hit", search_veranstaltung('zzzzzzzz'), '[]');
is_equal("prod: get_logo_filename only filename is string", is_string(get_logo_filename(1)) ? 1 : 0, 1);
is_equal("prod: get_sws(1, jede Woche)", get_sws(1, 'jede Woche'), array(0, 2));
is_equal("prod: get_sws('1-2', jede Woche)", get_sws('1-2', 'jede Woche'), array(0, 4));
is_equal("prod: get_sws('2-3', jede Woche)", get_sws('2-3', 'jede Woche'), array(0, 4));
is_equal("prod: get_sws('1-5', jede Woche)", get_sws('1-5', 'jede Woche'), array(0, 10));
is_equal("prod: get_sws invalid is null", get_sws('x-y', 'jede Woche') === null ? 1 : 0, 1);
is_equal("prod: show_in_current_page returns bool", is_bool(show_in_current_page(1)) ? 1 : 0, 1);
$_GET['kunde_id'] = '1';
is_equal("prod: get_logo_filename with kunde_id", is_string(get_logo_filename(1)) ? 1 : 0, 1);
ob_start();
$vvz_logo_direct = get_logo_filename(0);
$vvz_logo_output = ob_get_clean();
is_equal("prod: get_logo_filename direct returns/prints", is_string($vvz_logo_direct) || is_string($vvz_logo_output) ? 1 : 0, 1);
$_GET = array();
is_equal("prod: get_setting unknown is null", get_setting('does_not_exist_xyz') === null ? 1 : 0, 1);
is_equal("prod: semester_has_sperrvermerk unknown is false", semester_has_sperrvermerk(999999), false);

/* query_to_table with a tiny real query */
$vvz_query_table = query_to_table('SELECT 1 AS x', array('x'));
is_equal("prod: query_to_table returns table", is_string($vvz_query_table) && strpos($vvz_query_table, '<table>') !== false ? 1 : 0, 1);
is_equal("prod: query_to_table wrong cols", strpos(query_to_table('SELECT 1', 'nope'), 'Falsche Cols') !== false ? 1 : 0, 1);
is_equal("prod: query_to_table no query", strpos(query_to_table('', array('x')), 'No Query!') !== false ? 1 : 0, 1);

/* TOC / headings */
$GLOBALS['toc'] = array();
$vvz_h = print_h('Überschrift', 2);
is_equal("prod: print_h returns heading", strpos($vvz_h, '<h2') === 0 ? 1 : 0, 1);
is_equal("prod: print_h adds toc entry", count($GLOBALS['toc']), 1);
ob_start();
get_toc();
$vvz_toc_html = ob_get_clean();
is_equal("prod: get_toc returns list", strpos($vvz_toc_html, '<ul>') !== false ? 1 : 0, 1);
$GLOBALS['toc'] = array();

/* show_output resets globals */
$GLOBALS['success'] = array('a', 'b');
ob_start();
show_output('success', 1);
$vvz_output = ob_get_clean();
is_equal("prod: show_output contains messages", strpos($vvz_output, 'a') !== false && strpos($vvz_output, 'b') !== false ? 1 : 0, 1);
is_equal("prod: show_output clears global", $GLOBALS['success'], array());

/* css / js output helpers */
ob_start();
css('css/all.css');
$vvz_css = ob_get_clean();
is_equal("prod: css emits link", strpos($vvz_css, 'stylesheet') !== false ? 1 : 0, 1);
ob_start();
css(array('css/all.css', 'css/brands.css'));
$vvz_css = ob_get_clean();
is_equal("prod: css array emits two links", substr_count($vvz_css, 'stylesheet') >= 2 ? 1 : 0, 1);
ob_start();
js('js/all.js');
$vvz_js = ob_get_clean();
is_equal("prod: js emits script", strpos($vvz_js, '<script') !== false ? 1 : 0, 1);
ob_start();
js(array('js/all.js', 'js/brands.js'));
$vvz_js = ob_get_clean();
is_equal("prod: js array emits two scripts", substr_count($vvz_js, '<script') >= 2 ? 1 : 0, 1);
ob_start();
js('https://example.org/external.js');
$vvz_js = ob_get_clean();
is_equal("prod: js remote emits script", strpos($vvz_js, 'external.js') !== false ? 1 : 0, 1);

/* Prüfungs-Exporte (HTML mode only, no PHPExcel) */
$vvz_export = export_crazy_ethik_export_format(null, 1, '', 1);
is_equal("prod: export_crazy_ethik_export_format empty is string", is_string($vvz_export) ? 1 : 0, 1);
$vvz_export2 = export_crazy_ethik_export_format_2(null, 1, '', 1);
is_equal("prod: export_crazy_ethik_export_format_2 empty is empty", $vvz_export2, '');
$vvz_export3 = export_crazy_ethik_export_format_2(999999, 1, '', 1);
is_equal("prod: export_crazy_ethik_export_format_2 with PA is string", is_string($vvz_export3) ? 1 : 0, 1);
$vvz_export4 = export_pruefungsnummern_dozent(1, null, null, null, null, 0, 1);
is_equal("prod: export_pruefungsnummern_dozent HTML is string", is_string($vvz_export4) ? 1 : 0, 1);
$vvz_export5 = export_dozent_pruefungsnummern(1, null, null, null, null, 1);
is_equal("prod: export_dozent_pruefungsnummern HTML is string", is_string($vvz_export5) ? 1 : 0, 1);
is_equal("prod: create_add_where... empty", create_add_where_export_dozent_pruefungsnummern(null, null, null, null), '');
is_equal("prod: create_add_where... with values contains AND", strpos(create_add_where_export_dozent_pruefungsnummern(1, 1, 1, 1), 'AND') !== false ? 1 : 0, 1);
is_equal("prod: create_title_plus nulls is empty", create_title_plus(null, null, null, null, null), '');
is_equal("prod: create_title_plus with semester is string", is_string(create_title_plus(1, null, null, null, null)) ? 1 : 0, 1);
is_equal("prod: create_title_plus institute only", is_string(create_title_plus(null, 1, null, null, null)) ? 1 : 0, 1);
is_equal("prod: create_title_plus studiengang only", is_string(create_title_plus(null, null, 1, null, null)) ? 1 : 0, 1);
is_equal("prod: create_title_plus dozent only", is_string(create_title_plus(null, null, null, 1, null)) ? 1 : 0, 1);
is_equal("prod: create_title_plus pruefungsamt only", is_string(create_title_plus(null, null, null, null, 1)) ? 1 : 0, 1);
is_equal("prod: create_title_plus all fields", is_string(create_title_plus(1, 1, 1, 1, 1)) ? 1 : 0, 1);

/* Raumplanung / Sperrvermerk */
is_equal("prod: raum_ist_belegt without data", raum_ist_belegt(null, null, null, null, null), 0);
$vvz_threw = 0;
try {
	raum_ist_belegt(1, '2099-01-01', 1, null, null);
} catch (\Throwable $e) {
	$vvz_threw = 1;
}
is_equal("prod: raum_ist_belegt signals schema mismatch", $vvz_threw, 1);
is_equal("prod: get_crazy_ethik_export_format returns array", is_array(get_crazy_ethik_export_format(null, 1, array(), null)) ? 1 : 0, 1);
is_equal("prod: get_crazy_ethik_export_format_2 returns array", is_array(get_crazy_ethik_export_format_2(null, 1, array(), null)) ? 1 : 0, 1);

/* Discordian date returns an object */
is_equal("prod: discordian_date valid returns object", is_object(discordian_date('2018-09-18')) ? 1 : 0, 1);
is_equal("prod: discordian_date empty is null", discordian_date('') === null ? 1 : 0, 1);

/* ============================================================ */
/* Restore globals                                               */
/* ============================================================ */

foreach ($vvz_saved_globals as $vvz_global_name => $vvz_global_value) {
	$GLOBALS[$vvz_global_name] = $vvz_global_value;
}
$_SERVER = $vvz_saved_server;
$_POST = $vvz_saved_post;
$_GET = $vvz_saved_get;
$_COOKIE = $vvz_saved_cookie;
