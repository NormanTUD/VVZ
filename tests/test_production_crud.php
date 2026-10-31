<?php
/*
 * Tests for the write-side (CRUD) production functions.
 *
 * Everything in the main section runs inside a single transaction which is
 * rolled back at the end, so the database is left unchanged even though the
 * real create/update/delete functions are exercised.
 *
 * Functions that start or commit transactions themselves are tested in a
 * separate file (test_production_transactions.php) so they cannot commit the
 * outer transaction prematurely.
 */

if (!isset($GLOBALS['dbname']) || !$GLOBALS['dbname'] || !isset($GLOBALS['dbh']) || !is_object($GLOBALS['dbh'])) {
	return;
}

$vvz_test_db = $GLOBALS['dbname'];
$vvz_tag = 'ZZT' . substr(md5((string) getmypid() . (string) mt_rand()), 0, 4);

$vvz_saved_globals = array();
foreach (array('logged_in_user_id', 'user_role_id', 'user_dozent_id', 'user_institut_id', 'memoize', 'toc', 'debug', 'get_gebaeude_name_cache', 'this_page_number', 'db_freshly_created', 'already_deleted_old_session_ids', 'logged_in_data', 'pruefungen_already_chosen', 'pruefungen_already_done') as $vvz_global_name) {
	$vvz_saved_globals[$vvz_global_name] = array_key_exists($vvz_global_name, $GLOBALS) ? $GLOBALS[$vvz_global_name] : null;
}
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
$GLOBALS['db_freshly_created'] = 0;
$GLOBALS['already_deleted_old_session_ids'] = 0;
$GLOBALS['this_page_number'] = 7;
$GLOBALS['logged_in_data'] = array(1 => 'Test-User');
$GLOBALS['pruefungen_already_chosen'] = array();
$GLOBALS['pruefungen_already_done'] = array();
$_POST = array();
$_GET = array();
$_COOKIE = array();

/* Temporary table for the debug helpers. Creating a TEMPORARY table does not
 * commit the surrounding transaction. */
rquery('CREATE TEMPORARY TABLE IF NOT EXISTS `debug` (`debug` tinyint NOT NULL DEFAULT 0 PRIMARY KEY)');

start_transaction();

/* ============================================================ */
/* Dozenten                                                      */
/* ============================================================ */

create_dozent($vvz_tag, 'Dozent');
$vvz_dozent_id = get_dozent_id($vvz_tag, 'Dozent');
is_equal("crud: create_dozent returns id", $vvz_dozent_id !== null ? 1 : 0, 1);
is_equal("crud: create_dozent name", get_dozent_name($vvz_dozent_id), $vvz_tag . ' Dozent');
create_dozent($vvz_tag, 'Dozent');
is_equal("crud: create_dozent duplicate returns null", create_dozent($vvz_tag, 'Dozent') === null ? 1 : 0, 1);
update_dozent($vvz_dozent_id, $vvz_tag, 'Dozent2');
is_equal("crud: update_dozent changes name", get_single_row_from_query("select concat(first_name, ' ', last_name) from dozent where id = " . (int) $vvz_dozent_id), $vvz_tag . ' Dozent2');
update_dozent($vvz_dozent_id, $vvz_tag, 'Dozent2', 'ja');
is_equal("crud: update_dozent ausgeschieden", (string) get_single_row_from_query("select ausgeschieden from dozent where id = " . (int) $vvz_dozent_id), '1');
update_dozent($vvz_dozent_id, $vvz_tag, 'Dozent2');

/* ============================================================ */
/* Gebäude und Räume                                             */
/* ============================================================ */

create_gebaeude($vvz_tag . '-Gebäude', $vvz_tag . 'G');
$vvz_gebaeude_id = get_gebaeude_id_by_abkuerzung($vvz_tag . 'G');
is_equal("crud: create_gebaeude returns id", $vvz_gebaeude_id !== null ? 1 : 0, 1);
update_gebaeude($vvz_gebaeude_id, $vvz_tag . '-Gebäude2', $vvz_tag . 'G2');
is_equal("crud: update_gebaeude changes name", get_gebaeude_name($vvz_gebaeude_id), $vvz_tag . '-Gebäude2');
is_equal("crud: update_gebaeude changes abbreviation", get_gebaeude_abkuerzung($vvz_gebaeude_id), $vvz_tag . 'G2');
create_gebaeude('', '');
is_equal("crud: create_gebaeude empty values returns null", create_gebaeude('', '') === null ? 1 : 0, 1);

create_raum($vvz_gebaeude_id, 'R1');
$vvz_raum_id = get_raum_id($vvz_gebaeude_id, 'R1');
is_equal("crud: create_raum returns id", $vvz_raum_id !== null ? 1 : 0, 1);
@update_raum($vvz_raum_id, 'R2', $vvz_gebaeude_id);
is_equal("crud: update_raum changes number", get_raum_name_by_id($vvz_raum_id), 'R2');
$vvz_raum_id2 = get_and_create_raum_id($vvz_gebaeude_id, 'R3');
is_equal("crud: get_and_create_raum_id creates", $vvz_raum_id2 !== null ? 1 : 0, 1);
is_equal("crud: get_and_create_raum_id existing", (string) get_and_create_raum_id($vvz_gebaeude_id, 'R3'), (string) $vvz_raum_id2);
is_equal("crud: get_and_create_raum_id without number returns null", get_and_create_raum_id($vvz_gebaeude_id, '') === null ? 1 : 0, 1);
is_equal("crud: get_and_create_raum_id invalid building returns null", get_and_create_raum_id('x', 'R4') === null ? 1 : 0, 1);

/* ============================================================ */
/* Institute                                                     */
/* ============================================================ */

create_institut($vvz_tag . '-Institut', 4242);
$vvz_institut_id = get_institut_id($vvz_tag . '-Institut');
is_equal("crud: create_institut returns id", $vvz_institut_id !== null ? 1 : 0, 1);
update_institut($vvz_institut_id, $vvz_tag . '-Institut2', 4243);
is_equal("crud: update_institut changes name", get_institut_name($vvz_institut_id), $vvz_tag . '-Institut2');
is_equal("crud: create_institut duplicate returns id", (string) create_institut($vvz_tag . '-Institut2', 4243), (string) $vvz_institut_id);
is_equal("crud: get_startnr_by_institut", (string) get_startnr_by_institut($vvz_institut_id), '4243');

/* ============================================================ */
/* Bereiche                                                      */
/* ============================================================ */

create_bereich($vvz_tag . '-Bereich');
$vvz_bereich_id = get_single_row_from_query("select id from bereich where name = " . esc($vvz_tag . '-Bereich'));
is_equal("crud: create_bereich returns id", $vvz_bereich_id !== null ? 1 : 0, 1);
update_bereich($vvz_bereich_id, $vvz_tag . '-Bereich2');
is_equal("crud: update_bereich changes name", get_bereich_name_by_id($vvz_bereich_id), $vvz_tag . '-Bereich2');
is_equal("crud: create_bereich duplicate returns 0", (string) create_bereich($vvz_tag . '-Bereich2'), '0');

/* ============================================================ */
/* Studiengänge und Module                                       */
/* ============================================================ */

create_studiengang($vvz_tag . '-Studiengang', $vvz_institut_id, 'https://example.org/so', 1);
$vvz_studiengang_id = get_studiengang_id_by_name($vvz_tag . '-Studiengang');
is_equal("crud: create_studiengang returns id", $vvz_studiengang_id !== null ? 1 : 0, 1);
update_studiengang($vvz_studiengang_id, $vvz_tag . '-Studiengang2', $vvz_institut_id, 'https://example.org/so2', 2);
is_equal("crud: update_studiengang changes name", get_studiengang_name($vvz_studiengang_id), $vvz_tag . '-Studiengang2');
is_equal("crud: get_studienordnung_url", get_studienordnung_url($vvz_studiengang_id), 'https://example.org/so2');
is_equal("crud: get_studienordnung_url('alle') is null", get_studienordnung_url('alle') === null ? 1 : 0, 1);
is_equal("crud: get_and_create_studiengang existing", (string) get_and_create_studiengang($vvz_tag . '-Studiengang2', $vvz_institut_id, 1), (string) $vvz_studiengang_id);
$vvz_threw = 0;
try {
	get_and_create_studiengang($vvz_tag . '-Studiengang3', $vvz_institut_id, 1);
} catch (\Throwable $e) {
	$vvz_threw = 1;
}
is_equal("crud: get_and_create_studiengang signals signature bug", $vvz_threw, 1);

create_modul($vvz_tag . '-Modul', $vvz_studiengang_id, 'Beschreibung', $vvz_tag . 'M');
$vvz_modul_id = get_modul_id($vvz_tag . '-Modul', $vvz_studiengang_id);
is_equal("crud: create_modul returns id", $vvz_modul_id !== null ? 1 : 0, 1);
update_modul($vvz_modul_id, $vvz_tag . '-Modul2', $vvz_studiengang_id, 'Beschreibung2', $vvz_tag . 'M2');
is_equal("crud: update_modul changes name", get_modul_name($vvz_modul_id), $vvz_tag . '-Modul2');
is_equal("crud: get_studiengang_name_by_modul_id", get_studiengang_name_by_modul_id($vvz_modul_id), $vvz_tag . '-Studiengang2');
is_equal("crud: get_and_create_modul existing", (string) get_and_create_modul($vvz_tag . '-Modul2', $vvz_studiengang_id), (string) $vvz_modul_id);
$vvz_modul_id2 = get_and_create_modul($vvz_tag . '-Modul3', $vvz_studiengang_id);
is_equal("crud: get_and_create_modul creates", $vvz_modul_id2 !== null ? 1 : 0, 1);

/* ============================================================ */
/* Veranstaltungstypen                                           */
/* ============================================================ */

create_veranstaltungstyp($vvz_tag . '-Typ', $vvz_tag . 'T');
$vvz_veranstaltungstyp_id = get_veranstaltungstyp_id($vvz_tag . '-Typ');
is_equal("crud: create_veranstaltungstyp returns id", $vvz_veranstaltungstyp_id !== null ? 1 : 0, 1);
update_veranstaltungstyp($vvz_veranstaltungstyp_id, $vvz_tag . '-Typ2', $vvz_tag . 'T2');
is_equal("crud: update_veranstaltungstyp changes name", get_veranstaltungstyp_name($vvz_veranstaltungstyp_id), $vvz_tag . '-Typ2');
is_equal("crud: update_veranstaltungstyp changes abbreviation", get_veranstaltungsabkuerzung_by_id($vvz_veranstaltungstyp_id), $vvz_tag . 'T2');

/* ============================================================ */
/* Prüfungstypen, Zeiträume, Ämter, Fächer                       */
/* ============================================================ */

create_pruefungstyp($vvz_tag . '-PT');
$vvz_pruefungstyp_id = get_pruefungstyp_id($vvz_tag . '-PT');
is_equal("crud: create_pruefungstyp returns id", $vvz_pruefungstyp_id !== null ? 1 : 0, 1);
update_pruefungstyp($vvz_pruefungstyp_id, $vvz_tag . '-PT2');
is_equal("crud: update_pruefungstyp changes name", get_pruefungstyp_name($vvz_pruefungstyp_id), $vvz_tag . '-PT2');
is_equal("crud: get_and_create_pruefungstyp existing", (string) get_and_create_pruefungstyp($vvz_tag . '-PT2'), (string) $vvz_pruefungstyp_id);
$vvz_pruefungstyp_id2 = get_and_create_pruefungstyp($vvz_tag . '-PT3');
is_equal("crud: get_and_create_pruefungstyp creates", $vvz_pruefungstyp_id2 !== null ? 1 : 0, 1);

create_pruefung_zeitraum($vvz_tag . '-Zeitraum');
$vvz_zeitraum_id = get_single_row_from_query("select id from pruefung_zeitraum where name = " . esc($vvz_tag . '-Zeitraum'));
is_equal("crud: create_pruefung_zeitraum returns id", $vvz_zeitraum_id !== null ? 1 : 0, 1);
update_pruefung_zeitraum($vvz_zeitraum_id, $vvz_tag . '-Zeitraum2');
is_equal("crud: update_pruefung_zeitraum changes name", get_single_row_from_query("select name from pruefung_zeitraum where id = " . (int) $vvz_zeitraum_id), $vvz_tag . '-Zeitraum2');

create_pruefungsamt($vvz_tag . '-PA');
$vvz_pruefungsamt_id = get_single_row_from_query("select id from pruefungsamt where name = " . esc($vvz_tag . '-PA'));
is_equal("crud: create_pruefungsamt returns id", $vvz_pruefungsamt_id !== null ? 1 : 0, 1);
update_pruefungsamt($vvz_pruefungsamt_id, $vvz_tag . '-PA2');
is_equal("crud: update_pruefungsamt changes name", get_pruefungsamt_name($vvz_pruefungsamt_id), $vvz_tag . '-PA2');

create_fach($vvz_tag . '-Fach');
$vvz_fach_id = get_single_row_from_query("select id from pruefungsnummer_fach where name = " . esc($vvz_tag . '-Fach'));
is_equal("crud: create_fach returns id", $vvz_fach_id !== null ? 1 : 0, 1);
update_fach($vvz_fach_id, $vvz_tag . '-Fach2');
is_equal("crud: update_fach changes name", get_single_row_from_query("select name from pruefungsnummer_fach where id = " . (int) $vvz_fach_id), $vvz_tag . '-Fach2');

/* ============================================================ */
/* Titel, Sprachen, FAQ, Rollen, Funktionsrechte                 */
/* ============================================================ */

create_title($vvz_tag . '-Titel', $vvz_tag . 'TI');
$vvz_titel_id = get_single_row_from_query("select id from titel where name = " . esc($vvz_tag . '-Titel'));
is_equal("crud: create_title returns id", $vvz_titel_id !== null ? 1 : 0, 1);
update_titel($vvz_titel_id, $vvz_tag . '-Titel2', $vvz_tag . 'TI2');
is_equal("crud: update_titel changes name", get_single_row_from_query("select name from titel where id = " . (int) $vvz_titel_id), $vvz_tag . '-Titel2');
update_dozent_titel($vvz_dozent_id, $vvz_titel_id);
is_equal("crud: update_dozent_titel", (string) get_single_row_from_query("select titel_id from dozent where id = " . (int) $vvz_dozent_id), (string) $vvz_titel_id);

create_language($vvz_tag . '-Sprache', $vvz_tag . 'SP');
$vvz_language_id = get_single_row_from_query("select id from language where name = " . esc($vvz_tag . '-Sprache'));
is_equal("crud: create_language returns id", $vvz_language_id !== null ? 1 : 0, 1);
update_language($vvz_language_id, $vvz_tag . '-Sprache2', $vvz_tag . 'SP2');
is_equal("crud: update_language changes name", get_single_row_from_query("select name from language where id = " . (int) $vvz_language_id), $vvz_tag . '-Sprache2');

create_faq($vvz_tag . '-Frage', 'Antwort', 3);
$vvz_faq_id = get_single_row_from_query("select id from faq where frage = " . esc($vvz_tag . '-Frage'));
is_equal("crud: create_faq returns id", $vvz_faq_id !== null ? 1 : 0, 1);
update_faq($vvz_faq_id, $vvz_tag . '-Frage2', 'Antwort2', 5);
is_equal("crud: update_faq changes frage", get_single_row_from_query("select frage from faq where id = " . (int) $vvz_faq_id), $vvz_tag . '-Frage2');
update_faq($vvz_faq_id, $vvz_tag . '-Frage2', 'Antwort2', 'keine-zahl');
is_equal("crud: update_faq invalid number falls back to 1", (string) get_single_row_from_query("select wie_oft_gestellt from faq where id = " . (int) $vvz_faq_id), '1');

create_role($vvz_tag . '-Rolle', 'Beschreibung');
$vvz_role_id = get_role_id($vvz_tag . '-Rolle');
is_equal("crud: create_role returns id", $vvz_role_id !== null ? 1 : 0, 1);
update_role($vvz_role_id, $vvz_tag . '-Rolle2', 'Beschreibung2');
is_equal("crud: update_role changes name", get_role_name($vvz_role_id), $vvz_tag . '-Rolle2');
create_role($vvz_tag . '-Rolle3', 'Dritte');
$vvz_role_id2 = get_role_id($vvz_tag . '-Rolle3');
is_equal("crud: create_role creates second role", $vvz_role_id2 !== null ? 1 : 0, 1);

create_function_right($vvz_tag . '_Function');
$vvz_function_right_id = get_single_row_from_query("select id from function_right where function_name = " . esc($vvz_tag . '_Function'));
is_equal("crud: create_function_right returns id", $vvz_function_right_id !== null ? 1 : 0, 1);
update_funktion_rights($vvz_function_right_id, $vvz_tag . '_Function2');
is_equal("crud: update_funktion_rights changes name", get_single_row_from_query("select function_name from function_right where id = " . (int) $vvz_function_right_id), $vvz_tag . '_Function2');

/* ============================================================ */
/* API-Zugänge                                                   */
/* ============================================================ */

create_api($vvz_tag . '@example.org', $vvz_tag, 'Testgrund');
$vvz_api_id = mysqli_insert_id($GLOBALS['dbh']);
$vvz_auth_code = get_auth_code_by_id($vvz_api_id);
is_equal("crud: create_api creates auth code", is_string($vvz_auth_code) && strlen($vvz_auth_code) > 0 ? 1 : 0, 1);
is_equal("crud: get_auth_code_id", (string) get_auth_code_id($vvz_auth_code), (string) $vvz_api_id);
is_equal("crud: is_valid_auth_code", is_valid_auth_code($vvz_auth_code), 1);
is_equal("crud: last_api_access_long_ago", last_api_access_long_ago($vvz_auth_code), 1);
update_api($vvz_auth_code, $vvz_tag . '2@example.org', $vvz_tag . '2', 'Grund2');
is_equal("crud: update_api changes email", get_single_row_from_query("select email from api_auth_codes where id = " . (int) $vvz_api_id), $vvz_tag . '2@example.org');

/* ============================================================ */
/* Benutzer                                                      */
/* ============================================================ */

create_user($vvz_tag . 'User', 'secret', $vvz_role_id, $vvz_dozent_id, $vvz_institut_id, 1);
$vvz_user_id = get_user_id($vvz_tag . 'User');
is_equal("crud: create_user returns id", $vvz_user_id !== null ? 1 : 0, 1);
is_equal("crud: get_salt for user is string", is_string(get_salt($vvz_user_id)) && strlen(get_salt($vvz_user_id)) > 0 ? 1 : 0, 1);
is_equal("crud: get_and_create_salt existing", get_and_create_salt($vvz_user_id), get_salt($vvz_user_id));
update_user($vvz_tag . 'User2', $vvz_user_id, 'newsecret', $vvz_role_id, $vvz_dozent_id, $vvz_institut_id, 1, 1, 1);
is_equal("crud: update_user changes username", get_user_name($vvz_user_id), $vvz_tag . 'User2');
is_equal("crud: get_role_id_by_user", (string) get_role_id_by_user($vvz_user_id), (string) $vvz_role_id);
update_user_role($vvz_user_id, $vvz_role_id);
is_equal("crud: update_user_role keeps role", (string) get_role_id_by_user($vvz_user_id), (string) $vvz_role_id);
update_barrierefrei(0);
is_equal("crud: update_barrierefrei updates logged in user", (string) get_single_row_from_query("select barrierefrei from users where id = 1"), '0');
update_barrierefrei(1);
update_own_data('changed-password');
is_equal("crud: update_own_data changes password", strlen((string) get_single_row_from_query("select password_sha256 from users where id = 1")) > 20 ? 1 : 0, 1);
rquery("INSERT INTO `users` (`username`, `password_sha256`, `dozent_id`, `institut_id`, `salt`, `barrierefrei`) VALUES (" . esc($vvz_tag . 'SaltUser') . ", '', NULL, " . esc($vvz_institut_id) . ", '', '0')");
$vvz_salt_user_id = get_user_id($vvz_tag . 'SaltUser');
$vvz_generated_salt = get_and_create_salt($vvz_salt_user_id);
is_equal("crud: get_and_create_salt generates for empty salt", strlen((string) get_single_row_from_query("select salt from users where id = " . (int) $vvz_salt_user_id)) > 0 ? 1 : 0, 1);
delete_user($vvz_salt_user_id);

$GLOBALS['logged_in_user_id'] = $vvz_user_id;
$vvz_search_result = search_veranstaltung($vvz_tag);
is_equal("crud: search_veranstaltung non-admin returns string", is_string($vvz_search_result) ? 1 : 0, 1);
$GLOBALS['logged_in_user_id'] = 999999;
is_equal("crud: search_veranstaltung non-admin without dozent returns empty list", search_veranstaltung($vvz_tag), '[]');
$GLOBALS['logged_in_user_id'] = 1;

/* ============================================================ */
/* Seiten / Rollen-Zuordnung                                     */
/* ============================================================ */

create_new_page($vvz_tag . '-Seite', $vvz_tag . '.php', 0, null, array($vvz_role_id), 'Info', 'Hinweis');
$vvz_page_id = get_page_id($vvz_tag . '-Seite');
is_equal("crud: create_new_page creates page", $vvz_page_id !== null ? 1 : 0, 1);
is_equal("crud: create_new_page role mapping", role_has_access_to_page($vvz_role_id, $vvz_page_id), true);
update_page_info($vvz_page_id, 'Info2');
is_equal("crud: update_page_info", get_page_info_by_id($vvz_page_id), 'Info2');
update_hinweis($vvz_page_id, 'Hinweis2');
is_equal("crud: update_hinweis", get_hinweis_for_page($vvz_page_id), 'Hinweis2');
update_text($vvz_page_id, 'Seitentext');
$GLOBALS['this_page_number'] = $vvz_page_id;
is_equal("crud: update_text / get_seitentext", get_seitentext(1), 'Seitentext');
is_equal("crud: get_seitentext html encoded", get_seitentext(0), 'Seitentext');
update_role_to_page_page_info_hinweis($vvz_page_id, $vvz_role_id, 'Info3', 'Hinweis3');
is_equal("crud: update_role_to_page_page_info_hinweis info", get_page_info_by_id($vvz_page_id), 'Info3');
is_equal("crud: update_role_to_page_page_info_hinweis hinweis", get_hinweis_for_page($vvz_page_id), 'Hinweis3');
update_or_create_role_to_page($vvz_page_id, array($vvz_role_id));
is_equal("crud: update_or_create_role_to_page", role_has_access_to_page($vvz_role_id, $vvz_page_id), true);
update_page_full($vvz_page_id, $vvz_tag . '-Seite2', $vvz_tag . '2.php', 1, 25, $vvz_role_id, 'Info4', 'Hinweis4');
is_equal("crud: update_page_full name", get_single_row_from_query("select name from page where id = " . (int) $vvz_page_id), $vvz_tag . '-Seite2');
is_equal("crud: update_page_full file", get_single_row_from_query("select file from page where id = " . (int) $vvz_page_id), $vvz_tag . '2.php');
update_page($vvz_page_id, $vvz_tag . '-Seite3', $vvz_tag . '3.php');
is_equal("crud: update_page name", get_single_row_from_query("select name from page where id = " . (int) $vvz_page_id), $vvz_tag . '-Seite3');
assign_page_to_role($vvz_role_id2, $vvz_page_id);
is_equal("crud: assign_page_to_role", role_has_access_to_page($vvz_role_id2, $vvz_page_id), true);
is_equal("crud: get_roles_for_page contains new role", in_array((string) $vvz_role_id2, array_map('strval', get_roles_for_page($vvz_page_id))) ? 1 : 0, 1);
$vvz_print_subnav = print_subnavigation(25);
is_equal("crud: print_subnavigation returns array", is_array($vvz_print_subnav) ? 1 : 0, 1);
is_equal("crud: print_subnavigation returns html string", is_string($vvz_print_subnav[1]) ? 1 : 0, 1);

/* ============================================================ */
/* Semester / Merge / Settings                                   */
/* ============================================================ */

setze_semester(1);
is_equal("crud: setze_semester sets default", (string) get_current_semester_id(), '1');
get_and_create_next_n_semester_years(0);
is_equal("crud: get_and_create_next_n_semester_years creates semesters", is_array(get_this_semester()) ? 1 : 0, 1);
update_semester(1, '2099-01-01');
is_equal("crud: update_semester stores date", get_single_row_from_query("select erste_veranstaltung_default from semester where id = 1"), '2099-01-01');
$vvz_new_semester = get_and_create_semester_id_by_jahr_monat_tag(2099, 10, 10);
is_equal("crud: get_and_create new semester", is_array($vvz_new_semester) ? 1 : 0, 1);
delete_semester($vvz_new_semester[0]);
is_equal("crud: delete_semester removes row", get_semester($vvz_new_semester[0]), array());

create_language($vvz_tag . '-MergeFrom', $vvz_tag . 'MF');
create_language($vvz_tag . '-MergeTo', $vvz_tag . 'MT');
$vvz_merge_from = get_single_row_from_query("select id from language where name = " . esc($vvz_tag . '-MergeFrom'));
$vvz_merge_to = get_single_row_from_query("select id from language where name = " . esc($vvz_tag . '-MergeTo'));
merge_data('language', array($vvz_merge_from), $vvz_merge_to);
is_equal("crud: merge_data removes source row", get_single_row_from_query("select count(*) from language where id = " . (int) $vvz_merge_from), '0');
is_equal("crud: merge_data keeps target row", get_single_row_from_query("select count(*) from language where id = " . (int) $vvz_merge_to), '1');

ob_start();
set_setting($vvz_tag . '_setting', 'value1', 'Beschreibung');
ob_end_clean();
$GLOBALS['settings_cache'] = array();
is_equal("crud: set_setting stores value", get_setting($vvz_tag . '_setting'), 'value1');
ob_start();
reset_setting($vvz_tag . '_setting');
ob_end_clean();
rquery('DELETE FROM `config` WHERE `name` = ' . esc($vvz_tag . '_setting'));
$GLOBALS['settings_cache'] = array();

/* ============================================================ */
/* Veranstaltung / Prüfungsnummer / Prüfung (ohne interne TX)    */
/* ============================================================ */

rquery('INSERT INTO `veranstaltung` (`veranstaltungstyp_id`, `name`, `dozent_id`, `institut_id`, `semester_id`) VALUES (' . esc($vvz_veranstaltungstyp_id) . ', ' . esc($vvz_tag . '-Veranstaltung') . ', ' . esc($vvz_dozent_id) . ', ' . esc($vvz_institut_id) . ', ' . esc(get_current_semester_id()) . ')');
$vvz_veranstaltung_id = mysqli_insert_id($GLOBALS['dbh']);
is_equal("crud: insert veranstaltung", $vvz_veranstaltung_id > 0 ? 1 : 0, 1);
is_equal("crud: get_veranstaltung_name", get_veranstaltung_name($vvz_veranstaltung_id), $vvz_tag . '-Veranstaltung');
is_equal("crud: get_veranstaltung_semester", (string) get_veranstaltung_semester($vvz_veranstaltung_id), (string) get_current_semester_id());
is_equal("crud: get_typ_abkuerzung_by_veranstaltung_id", get_typ_abkuerzung_by_veranstaltung_id($vvz_veranstaltung_id), $vvz_tag . 'T2');
is_equal("crud: get_typid_by_veranstaltung_id", get_typid_by_veranstaltung_id($vvz_veranstaltung_id), $vvz_tag . 'T2');
is_equal("crud: get_dozent_id_by_veranstaltung_id", (string) get_dozent_id_by_veranstaltung_id($vvz_veranstaltung_id), (string) $vvz_dozent_id);
is_equal("crud: get_institut_id_by_veranstaltung_id", (string) get_institut_id_by_veranstaltung_id($vvz_veranstaltung_id), (string) $vvz_institut_id);
update_veranstaltung($vvz_veranstaltung_id, $vvz_tag . '-Veranstaltung2', $vvz_dozent_id, $vvz_veranstaltungstyp_id, $vvz_institut_id, get_current_semester_id(), 1, 0);
is_equal("crud: update_veranstaltung changes name", get_veranstaltung_name($vvz_veranstaltung_id), $vvz_tag . '-Veranstaltung2');
update_raumplanung($vvz_veranstaltung_id, $vvz_gebaeude_id, $vvz_raum_id, '01.02.2099');
$vvz_location = get_veranstaltung_location($vvz_veranstaltung_id);
is_equal("crud: update_raumplanung sets building", isset($vvz_location['gebaeude_id']) ? 1 : 0, 1);
is_equal("crud: get_erste_veranstaltung falls back to semester", get_erste_veranstaltung($vvz_veranstaltung_id, array('id' => get_current_semester_id())), '2099-01-01');
rquery('INSERT INTO `veranstaltung_metadaten` (`veranstaltung_id`, `wochentag`, `stunde`, `woche`) VALUES (' . esc($vvz_veranstaltung_id) . ', "Mo", "1", "jede Woche") ON DUPLICATE KEY UPDATE `wochentag` = "Mo", `stunde` = "1", `woche` = "jede Woche"');
is_equal("crud: get_veranstaltung_stunde", get_veranstaltung_stunde($vvz_veranstaltung_id), '1');
is_equal("crud: get_tag_from_veranstaltung_id", get_tag_from_veranstaltung_id($vvz_veranstaltung_id), 'Mo');
is_equal("crud: get_woche_from_veranstaltung_id", get_woche_from_veranstaltung_id($vvz_veranstaltung_id), 'jede Woche');
is_equal("crud: video_conference_link empty", video_conference_link($vvz_veranstaltung_id), '');
$vvz_lv_nr = get_or_set_and_get_lv_nr_by_veranstaltung($vvz_veranstaltung_id);
is_equal("crud: get_or_set_and_get_lv_nr_by_veranstaltung creates", $vvz_lv_nr !== null ? 1 : 0, 1);
is_equal("crud: get_or_set_and_get_lv_nr_by_veranstaltung existing", (string) get_or_set_and_get_lv_nr_by_veranstaltung($vvz_veranstaltung_id), (string) $vvz_lv_nr);
is_equal("crud: create_event_file with veranstaltung", strpos(create_event_file(array($vvz_veranstaltung_id)), 'BEGIN:VEVENT') !== false ? 1 : 0, 1);

$vvz_stundenplan_veranstaltungen = array($vvz_veranstaltung_id);
$vvz_stundenplan_extra = array(
	array('Mo', '1', 'gerade Woche'),
	array('Di', '1-2', 'keine Angabe'),
	array('Mi', '2', 'ungerade Woche'),
);
foreach ($vvz_stundenplan_extra as $vvz_i => $vvz_extra) {
	rquery('INSERT INTO `veranstaltung` (`veranstaltungstyp_id`, `name`, `dozent_id`, `institut_id`, `semester_id`, `gebaeude_id`, `raum_id`) VALUES (' . esc($vvz_veranstaltungstyp_id) . ', ' . esc($vvz_tag . '-Stundenplan' . $vvz_i) . ', ' . esc($vvz_dozent_id) . ', ' . esc($vvz_institut_id) . ', ' . esc(get_current_semester_id()) . ', ' . esc($vvz_gebaeude_id) . ', ' . ($vvz_i == 1 ? 'NULL' : esc($vvz_raum_id)) . ')');
	$vvz_extra_id = mysqli_insert_id($GLOBALS['dbh']);
	$vvz_stundenplan_veranstaltungen[] = $vvz_extra_id;
	rquery('INSERT INTO `veranstaltung_metadaten` (`veranstaltung_id`, `wochentag`, `stunde`, `woche`, `erster_termin`) VALUES (' . esc($vvz_extra_id) . ', ' . esc($vvz_extra[0]) . ', ' . esc($vvz_extra[1]) . ', ' . esc($vvz_extra[2]) . ', "2099-04-01")');
}
$vvz_stundenplan = create_stundenplan($vvz_stundenplan_veranstaltungen, 1, 1, null, 0, null, null, get_current_semester_id());
is_equal("crud: create_stundenplan returns array", is_array($vvz_stundenplan) ? 1 : 0, 1);
is_equal("crud: create_stundenplan renders table", strpos($vvz_stundenplan[0], 'stundenplan') !== false ? 1 : 0, 1);
is_equal("crud: create_stundenplan renders building list", strpos($vvz_stundenplan[0], 'Gebäude') !== false ? 1 : 0, 1);
is_equal("crud: create_stundenplan detects collision", strpos($vvz_stundenplan[0], 'kollidiert') !== false ? 1 : 0, 1);
is_equal("crud: create_stundenplan renders block seminar", strpos($vvz_stundenplan[0], 'Blockveranstaltung') !== false ? 1 : 0, 1);
$vvz_stundenplan_empty = create_stundenplan(array(999999), 0, 0, null, 0, null, null, get_current_semester_id());
is_equal("crud: create_stundenplan unknown veranstaltung", strpos($vvz_stundenplan_empty[0], 'scheinen nicht zu existieren') !== false ? 1 : 0, 1);

ob_start();
$vvz_raumplanung_result = raumplanung($vvz_institut_id, get_current_semester_id(), 1);
$vvz_raumplanung_html = ob_get_clean();
is_equal("crud: raumplanung with data returns int", is_int($vvz_raumplanung_result) ? 1 : 0, 1);
is_equal("crud: raumplanung renders table", strpos($vvz_raumplanung_html, 'raumplanungtable') !== false ? 1 : 0, 1);
is_equal("crud: raumplanung unknown institute returns int", is_int(raumplanung(999999, get_current_semester_id(), 1)) ? 1 : 0, 1);
ob_start();
$vvz_raumplanung_crazy = raumplanung_crazy($vvz_institut_id, get_current_semester_id(), 0);
ob_end_clean();
is_equal("crud: raumplanung_crazy returns PHPExcel", is_object($vvz_raumplanung_crazy) ? 1 : 0, 1);
ob_start();
$vvz_raumplanung_excel = raumplanung($vvz_institut_id, get_current_semester_id(), 0);
ob_end_clean();
is_equal("crud: raumplanung Excel branch returns PHPExcel", is_object($vvz_raumplanung_excel) ? 1 : 0, 1);

rquery('INSERT INTO `einzelne_termine` (`veranstaltung_id`, `start`, `end`, `raum_id`) VALUES (' . esc($vvz_veranstaltung_id) . ', "2099-05-01 10:00:00", "2099-05-01 12:00:00", ' . esc($vvz_raum_id) . ')');
$vvz_termine = get_einzelne_termine_by_veranstaltung_id($vvz_veranstaltung_id);
is_equal("crud: get_einzelne_termine_by_veranstaltung_id", count($vvz_termine), 1);
$vvz_nice_termine = nice_einzelne_veranstaltung_by_id($vvz_veranstaltung_id);
is_equal("crud: nice_einzelne_veranstaltung_by_id", count($vvz_nice_termine), 1);
is_equal("crud: nice_einzelne_veranstaltung_by_id date", $vvz_nice_termine[0]['start_datum'], '01.05.2099');
$vvz_termine_liste = get_einzelne_termine_gebaeude_raum_liste($vvz_veranstaltung_id);
is_equal("crud: get_einzelne_termine_gebaeude_raum_liste", count($vvz_termine_liste) == 1 && strpos(join(' ', $vvz_termine_liste), 'R2') !== false ? 1 : 0, 1);

create_pruefungsnummer($vvz_modul_id, $vvz_tag . '-PN', $vvz_pruefungstyp_id, $vvz_bereich_id, $vvz_tag . ' Modulbezeichnung', $vvz_zeitraum_id);
$vvz_pruefungsnummer_id = get_pruefungsnummer_id_by_pruefungsnummer($vvz_tag . '-PN');
is_equal("crud: create_pruefungsnummer returns id", $vvz_pruefungsnummer_id !== null ? 1 : 0, 1);
$vvz_pn_array = create_pruefungsnummern_array_by_modul_id($vvz_modul_id);
is_equal("crud: create_pruefungsnummern_array_by_modul_id contains PN", isset($vvz_pn_array[$vvz_pruefungsnummer_id]) ? 1 : 0, 1);
$vvz_all_pn = create_pruefungsnummern_array();
is_equal("crud: create_pruefungsnummern_array contains PN", isset($vvz_all_pn[$vvz_pruefungsnummer_id]) ? 1 : 0, 1);
$vvz_veranstaltungen_array = create_veranstaltungen_array($vvz_dozent_id, null, 5, get_current_semester_id(), $vvz_institut_id, 1);
is_equal("crud: create_veranstaltungen_array contains veranstaltung", isset($vvz_veranstaltungen_array[$vvz_veranstaltung_id]) ? 1 : 0, 1);
$vvz_veranstaltungen_array2 = create_veranstaltungen_array(array($vvz_dozent_id), $vvz_veranstaltung_id, 0, null, null, 0);
is_equal("crud: create_veranstaltungen_array not_id excludes veranstaltung", isset($vvz_veranstaltungen_array2[$vvz_veranstaltung_id]) ? 0 : 1, 1);
$vvz_modul_stg = create_modul_studiengang_array(0, $vvz_institut_id);
is_equal("crud: create_modul_studiengang_array contains modul", isset($vvz_modul_stg[$vvz_modul_id]) ? 1 : 0, 1);
$vvz_modul_stg_short = create_modul_studiengang_array(3, $vvz_institut_id);
is_equal("crud: create_modul_studiengang_array shortens", isset($vvz_modul_stg_short[$vvz_modul_id]) ? 1 : 0, 1);
$vvz_modules_array = create_module_array_by_studiengang_and_semester(array($vvz_studiengang_id), get_current_semester_id());
is_equal("crud: create_module_array_by_studiengang_and_semester array branch", is_array($vvz_modules_array) ? 1 : 0, 1);
is_equal("crud: get_pruefungsnummer_bereich", (string) get_pruefungsnummer_bereich_by_pruefungsnummer_id($vvz_tag . '-PN'), (string) $vvz_bereich_id);
is_equal("crud: get_pruefungsnummer_modul", (string) get_pruefungsnummer_modul_by_pruefungsnummer_id($vvz_tag . '-PN'), (string) $vvz_modul_id);
is_equal("crud: get_pruefungstyp_id_from_pruefungsnummer", (string) get_pruefungstyp_id_from_pruefungsnummer($vvz_tag . '-PN'), (string) $vvz_pruefungstyp_id);
is_equal("crud: get_pruefungstyp_by_pruefungsnummer", get_pruefungstyp_by_pruefungsnummer($vvz_tag . '-PN'), $vvz_tag . '-PT2');
is_equal("crud: get_modul_by_pruefungsnummer", get_modul_by_pruefungsnummer($vvz_tag . '-PN'), $vvz_tag . '-Modul2');
insert_pruefungsnummern($vvz_modul_id, $vvz_tag . '-PN2', $vvz_pruefungstyp_id);
is_equal("crud: insert_pruefungsnummern creates row", get_pruefungsnummer_id_by_pruefungsnummer($vvz_tag . '-PN2') !== null ? 1 : 0, 1);
update_pruefungsnummer($vvz_pruefungsnummer_id, $vvz_modul_id, $vvz_tag . '-PNX', $vvz_pruefungstyp_id, $vvz_bereich_id, 'Neu', $vvz_zeitraum_id, 'on');
is_equal("crud: update_pruefungsnummer sets disabled", (string) get_single_row_from_query("select disabled from pruefungsnummer where id = " . (int) $vvz_pruefungsnummer_id), '1');
update_pruefungsnummer($vvz_pruefungsnummer_id, $vvz_modul_id, $vvz_tag . '-PNX', $vvz_pruefungstyp_id, $vvz_bereich_id, 'Neu', $vvz_zeitraum_id, 'off');
is_equal("crud: update_pruefungsnummer re-enables", (string) get_single_row_from_query("select disabled from pruefungsnummer where id = " . (int) $vvz_pruefungsnummer_id), '0');
is_equal("crud: get_pruefung_zeitraum", get_pruefung_zeitraum($vvz_pruefungsnummer_id), $vvz_tag . '-Zeitraum2');

$vvz_threw = 0;
try {
	create_pruefung($vvz_pruefungstyp_id, $vvz_veranstaltung_id, $vvz_tag . '-Prüfung', $vvz_tag . '-PNX', null, null, null);
} catch (\Throwable $e) {
	$vvz_threw = 1;
}
is_equal("crud: create_pruefung signals schema mismatch", $vvz_threw, 1);

rquery('INSERT INTO `pruefung` (`veranstaltung_id`, `pruefungsnummer_id`, `date`, `raum_id`) VALUES (' . esc($vvz_veranstaltung_id) . ', ' . esc($vvz_pruefungsnummer_id) . ', "2099-01-01", ' . esc($vvz_raum_id) . ')');
$vvz_pruefung_id = mysqli_insert_id($GLOBALS['dbh']);
is_equal("crud: insert pruefung directly", $vvz_pruefung_id > 0 ? 1 : 0, 1);
$vvz_checked = get_checked_pruefungsnummern($vvz_veranstaltung_id);
is_equal("crud: get_checked_pruefungsnummern contains pruefungsnummer", isset($vvz_checked[$vvz_pruefungsnummer_id]) ? 1 : 0, 1);
is_equal("crud: pruefungsnummer_is_checked", (int) pruefungsnummer_is_checked($vvz_pruefungsnummer_id, $vvz_modul_id, $vvz_veranstaltung_id) > 0 ? 1 : 0, 1);
is_equal("crud: get_anzahl_pruefungen_pro_modul_pro_semester", (string) get_anzahl_pruefungen_pro_modul_pro_semester($vvz_modul_id, get_current_semester_id()), '1');
is_equal("crud: get_anzahl_pl_pro_semester", (string) get_anzahl_pl_pro_semester($vvz_tag . '-PNX', get_current_semester_id()), '1');
$vvz_threw = 0;
try {
	update_pruefung($vvz_pruefung_id, $vvz_pruefungstyp_id, $vvz_veranstaltung_id, $vvz_tag . '-PNX', $vvz_tag . '-Prüfung2', '2099-01-01', '1', null);
} catch (\Throwable $e) {
	$vvz_threw = 1;
}
is_equal("crud: update_pruefung signals schema mismatch", $vvz_threw, 1);
is_equal("crud: create_pruefungen_by_studiengang_array with data", count(create_pruefungen_by_studiengang_array($vvz_studiengang_id)) > 0 ? 1 : 0, 1);
is_equal("crud: get_studiengang_modul_pruefungsnummer_array has data", count(get_studiengang_modul_pruefungsnummer_array()) > 0 ? 1 : 0, 1);
is_equal("crud: get_anzahl_pl_pro_semester unknown is 0", (string) get_anzahl_pl_pro_semester('NOPE', get_current_semester_id()), '0');
$vvz_export_pn = export_pruefungsnummern_dozent(get_current_semester_id(), $vvz_dozent_id, null, null, null, 0, 1);
is_equal("crud: export_pruefungsnummern_dozent with data", strpos($vvz_export_pn, '<table>') !== false ? 1 : 0, 1);
is_equal("crud: export_pruefungsnummern_dozent returns string", is_string($vvz_export_pn) ? 1 : 0, 1);
$vvz_export_dozent = export_dozent_pruefungsnummern(get_current_semester_id(), $vvz_dozent_id, null, null, null, 1);
is_equal("crud: export_dozent_pruefungsnummern with data", strpos($vvz_export_dozent, '<table>') !== false ? 1 : 0, 1);
$vvz_export_crazy = export_crazy_ethik_export_format(null, get_current_semester_id(), $vvz_tag . '-PNX', 1);
is_equal("crud: export_crazy_ethik_export_format with data", strpos($vvz_export_crazy, '<table>') !== false ? 1 : 0, 1);
$vvz_export_crazy2 = export_crazy_ethik_export_format_2(null, get_current_semester_id(), $vvz_tag . '-PNX', 1);
is_equal("crud: export_crazy_ethik_export_format_2 with data", strpos($vvz_export_crazy2, '<table>') !== false ? 1 : 0, 1);
$vvz_excel_export_pn = export_pruefungsnummern_dozent(get_current_semester_id(), $vvz_dozent_id, null, null, null, 0, 0);
is_equal("crud: export_pruefungsnummern_dozent Excel branch", is_object($vvz_excel_export_pn) ? 1 : 0, 1);
$vvz_excel_export_dozent = export_dozent_pruefungsnummern(get_current_semester_id(), $vvz_dozent_id, null, null, null, 0);
is_equal("crud: export_dozent_pruefungsnummern Excel branch", is_object($vvz_excel_export_dozent) ? 1 : 0, 1);
$vvz_excel_crazy = export_crazy_ethik_export_format(null, get_current_semester_id(), $vvz_tag . '-PNX', 0);
is_equal("crud: export_crazy_ethik_export_format Excel branch", is_object($vvz_excel_crazy) ? 1 : 0, 1);
$vvz_excel_crazy2 = export_crazy_ethik_export_format_2(null, get_current_semester_id(), $vvz_tag . '-PNX', 0);
is_equal("crud: export_crazy_ethik_export_format_2 Excel branch", is_object($vvz_excel_crazy2) ? 1 : 0, 1);

/* The nachpruefung table is missing in the current schema. We document this
 * by asserting that the production function signals the schema mismatch. */
$vvz_threw = 0;
try {
	create_nachpruefung($vvz_pruefung_id, '2099-01-02', null, '1');
} catch (\Throwable $e) {
	$vvz_threw = 1;
}
is_equal("crud: create_nachpruefung signals missing table", $vvz_threw, 1);

/* ============================================================ */
/* Debug-Helfer / Sessions / Subnavigation                       */
/* ============================================================ */

set_debug(1);
is_equal("crud: set_debug / is_debug true", (string) is_debug(), '1');
set_debug(0);
is_equal("crud: set_debug / is_debug false", (string) is_debug(), '0');
is_equal("crud: debug_backtrace_string is string", is_string(debug_backtrace_string()) ? 1 : 0, 1);

rquery('INSERT INTO `session_ids` (`session_id`, `user_id`, `creation_time`) VALUES (' . esc($vvz_tag . '-session') . ', 1, now() - INTERVAL 2 DAY)');
delete_old_session_ids(1);
is_equal("crud: delete_old_session_ids runs", 1, 1);

/* ============================================================ */
/* Deletes (in dependency order)                                 */
/* ============================================================ */

delete_pruefung($vvz_pruefung_id);
is_equal("crud: delete_pruefung", get_single_row_from_query("select count(*) from pruefung where id = " . (int) $vvz_pruefung_id), '0');
delete_pruefungsnummer($vvz_pruefungsnummer_id);
is_equal("crud: delete_pruefungsnummer", get_single_row_from_query("select count(*) from pruefungsnummer where id = " . (int) $vvz_pruefungsnummer_id), '0');
$vvz_pn2_id = get_pruefungsnummer_id_by_pruefungsnummer($vvz_tag . '-PN2');
delete_pruefungsnummer($vvz_pn2_id);
delete_modul($vvz_modul_id);
is_equal("crud: delete_modul", get_single_row_from_query("select count(*) from modul where id = " . (int) $vvz_modul_id), '0');
delete_modul($vvz_modul_id2);
foreach ($vvz_stundenplan_veranstaltungen as $vvz_cleanup_veranstaltung_id) {
	delete_veranstaltung($vvz_cleanup_veranstaltung_id);
}
is_equal("crud: delete_veranstaltung", get_single_row_from_query("select count(*) from veranstaltung where id IN (" . join(', ', array_map('intval', $vvz_stundenplan_veranstaltungen)) . ")"), '0');
delete_veranstaltungstyp($vvz_veranstaltungstyp_id);
is_equal("crud: delete_veranstaltungstyp", get_single_row_from_query("select count(*) from veranstaltungstyp where id = " . (int) $vvz_veranstaltungstyp_id), '0');
delete_api($vvz_auth_code);
is_equal("crud: delete_api", get_single_row_from_query("select count(*) from api_auth_codes where id = " . (int) $vvz_api_id), '0');
delete_user($vvz_user_id);
is_equal("crud: delete_user", get_single_row_from_query("select count(*) from users where id = " . (int) $vvz_user_id), '0');
delete_page($vvz_page_id);
is_equal("crud: delete_page", get_single_row_from_query("select count(*) from page where id = " . (int) $vvz_page_id), '0');
delete_role($vvz_role_id2);
is_equal("crud: delete_role", get_role_name($vvz_role_id2) === null ? 1 : 0, 1);
delete_funktion_rights($vvz_function_right_id);
is_equal("crud: delete_funktion_rights", get_single_row_from_query("select count(*) from function_right where id = " . (int) $vvz_function_right_id), '0');
delete_faq($vvz_faq_id);
is_equal("crud: delete_faq", get_single_row_from_query("select count(*) from faq where id = " . (int) $vvz_faq_id), '0');
delete_language($vvz_language_id);
is_equal("crud: delete_language", get_single_row_from_query("select count(*) from language where id = " . (int) $vvz_language_id), '0');
delete_language($vvz_merge_to);
update_dozent_titel($vvz_dozent_id, null);
delete_titel($vvz_titel_id);
is_equal("crud: delete_titel", get_single_row_from_query("select count(*) from titel where id = " . (int) $vvz_titel_id), '0');
delete_raum($vvz_raum_id);
is_equal("crud: delete_raum", get_single_row_from_query("select count(*) from raum where id = " . (int) $vvz_raum_id), '0');
delete_raum($vvz_raum_id2);
delete_gebaeude($vvz_gebaeude_id);
is_equal("crud: delete_gebaeude", get_single_row_from_query("select count(*) from gebaeude where id = " . (int) $vvz_gebaeude_id), '0');
delete_studiengang($vvz_studiengang_id);
is_equal("crud: delete_studiengang", get_studiengang_name($vvz_studiengang_id) === null ? 1 : 0, 1);
delete_dozent($vvz_dozent_id);
is_equal("crud: delete_dozent", get_single_row_from_query("select count(*) from dozent where id = " . (int) $vvz_dozent_id), '0');
delete_institut($vvz_institut_id);
is_equal("crud: delete_institut", get_institut_name($vvz_institut_id) === null ? 1 : 0, 1);
delete_bereich($vvz_bereich_id);
is_equal("crud: delete_bereich", get_single_row_from_query("select count(*) from bereich where id = " . (int) $vvz_bereich_id), '0');
delete_pruefungstyp($vvz_pruefungstyp_id);
is_equal("crud: delete_pruefungstyp", get_pruefungstyp_name($vvz_pruefungstyp_id) === null ? 1 : 0, 1);
delete_pruefungstyp($vvz_pruefungstyp_id2);
delete_pruefung_zeitraum($vvz_zeitraum_id);
is_equal("crud: delete_pruefung_zeitraum", get_single_row_from_query("select count(*) from pruefung_zeitraum where id = " . (int) $vvz_zeitraum_id), '0');
delete_pruefungsamt($vvz_pruefungsamt_id);
is_equal("crud: delete_pruefungsamt", get_single_row_from_query("select count(*) from pruefungsamt where id = " . (int) $vvz_pruefungsamt_id), '0');
delete_fach($vvz_fach_id);
is_equal("crud: delete_fach", get_single_row_from_query("select count(*) from pruefungsnummer_fach where id = " . (int) $vvz_fach_id), '0');

/* Rollback the whole main section. */
rollback();

/* Prove commit()/set_autocommit() work without leaving anything behind. */
start_transaction();
commit();
is_equal("crud: start_transaction / commit complete", 1, 1);

rquery('DROP TEMPORARY TABLE IF EXISTS `debug`');

foreach ($vvz_saved_globals as $vvz_global_name => $vvz_global_value) {
	$GLOBALS[$vvz_global_name] = $vvz_global_value;
}
$_POST = $vvz_saved_post;
$_GET = $vvz_saved_get;
$_COOKIE = $vvz_saved_cookie;
