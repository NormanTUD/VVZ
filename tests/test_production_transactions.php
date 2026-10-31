<?php
/*
 * Tests for production functions that manage their own transactions
 * (start_transaction / commit / rollback) and therefore cannot be exercised
 * inside the outer transaction used by test_production_crud.php.
 *
 * These tests use a unique tag and clean up every row they create.
 */

if (!isset($GLOBALS['dbname']) || !$GLOBALS['dbname'] || !isset($GLOBALS['dbh']) || !is_object($GLOBALS['dbh'])) {
	return;
}

$vvz_test_db = $GLOBALS['dbname'];
$vvz_tag = 'ZZX' . substr(md5((string) getmypid() . (string) mt_rand()), 0, 4);

$vvz_saved_globals = array();
foreach (array('logged_in_user_id', 'user_role_id', 'user_dozent_id', 'user_institut_id', 'memoize', 'toc', 'debug', 'get_gebaeude_name_cache', 'this_page_number', 'db_freshly_created', 'already_deleted_old_session_ids') as $vvz_global_name) {
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
$_POST = array();
$_GET = array();
$_COOKIE = array();

$vvz_semester_id = get_current_semester_id();
rquery('DELETE FROM `sperrvermerk` WHERE `semester_id` = ' . (int) $vvz_semester_id);
$vvz_dozent_id = 1;
$vvz_institut_id = 1;

/* ============================================================ */
/* update_right_to_user_role / update_right_to_page              */
/* ============================================================ */

create_function_right($vvz_tag . '_Right');
$vvz_right_id = get_single_row_from_query("select id from function_right where function_name = " . esc($vvz_tag . '_Right'));
create_role($vvz_tag . '-RechteRolle', 'Testrolle');
$vvz_role_id = get_role_id($vvz_tag . '-RechteRolle');

update_right_to_user_role(array($vvz_right_id => array($vvz_role_id => 1)));
is_equal("tx: update_right_to_user_role inserts mapping", (string) get_single_row_from_query("select count(*) from function_right_to_user_role where function_right_id = " . (int) $vvz_right_id . " and role_id = " . (int) $vvz_role_id), '1');
rollback();

update_right_to_page(array($vvz_right_id => array(1 => 1)));
is_equal("tx: update_right_to_page inserts mapping", (string) get_single_row_from_query("select count(*) from function_right_to_page where function_right_id = " . (int) $vvz_right_id . " and page_id = 1"), '1');
rollback();

rquery('DELETE FROM `function_right` WHERE `id` = ' . (int) $vvz_right_id);
rquery('DELETE FROM `role` WHERE `id` = ' . (int) $vvz_role_id);

/* ============================================================ */
/* Superdozent                                                   */
/* ============================================================ */

update_superdozent(1, array($vvz_dozent_id));
is_equal("tx: update_superdozent creates mapping", (string) get_single_row_from_query("select count(*) from superdozent where user_id = 1 and dozent_id = " . (int) $vvz_dozent_id), '1');
update_superdozent(1, array());
is_equal("tx: update_superdozent removes mapping", (string) get_single_row_from_query("select count(*) from superdozent where user_id = 1"), '0');

/* ============================================================ */
/* Prüfungsamt -> Studiengang                                    */
/* ============================================================ */

create_pruefungsamt($vvz_tag . '-PA');
$vvz_pruefungsamt_id = get_single_row_from_query("select id from pruefungsamt where name = " . esc($vvz_tag . '-PA'));
update_pruefungsamt_studiengang($vvz_pruefungsamt_id, array(1));
is_equal("tx: update_pruefungsamt_studiengang creates mapping", (string) get_single_row_from_query("select count(*) from pruefungsamt_nach_studiengang where pruefungsamt_id = " . (int) $vvz_pruefungsamt_id), '1');
update_pruefungsamt_studiengang($vvz_pruefungsamt_id, array());
is_equal("tx: update_pruefungsamt_studiengang clears mapping", (string) get_single_row_from_query("select count(*) from pruefungsamt_nach_studiengang where pruefungsamt_id = " . (int) $vvz_pruefungsamt_id), '0');
rquery('DELETE FROM `pruefungsamt` WHERE `id` = ' . (int) $vvz_pruefungsamt_id);

/* ============================================================ */
/* Modul <-> Semester                                            */
/* ============================================================ */

create_modul($vvz_tag . '-Modul', 1, 'Testbeschreibung', $vvz_tag . 'M');
$vvz_modul_id = get_modul_id($vvz_tag . '-Modul', 1);

update_modul_semester($vvz_modul_id, '1,2');
is_equal("tx: update_modul_semester assigns two semesters", (string) get_single_row_from_query("select count(*) from modul_nach_semester where modul_id = " . (int) $vvz_modul_id), '2');
update_modul_semester($vvz_modul_id, '99,0');
is_equal("tx: update_modul_semester rolls back invalid input (previous state remains)", (string) get_single_row_from_query("select count(*) from modul_nach_semester where modul_id = " . (int) $vvz_modul_id), '2');
update_modul_semester($vvz_modul_id, '');
is_equal("tx: update_modul_semester empty removes assignments", (string) get_single_row_from_query("select count(*) from modul_nach_semester where modul_id = " . (int) $vvz_modul_id), '0');

create_pruefungstyp($vvz_tag . '-PT');
$vvz_pruefungstyp_id = get_pruefungstyp_id($vvz_tag . '-PT');
update_modul_semester_data(1, 1, '5', '2', array(1 => 3), $vvz_modul_id);
is_equal("tx: update_modul_semester_data stores credit points", get_single_row_from_query("select credit_points from modul_nach_semester_metadata where modul_id = " . (int) $vvz_modul_id . " and semester = 1"), '5');
is_equal("tx: update_modul_semester_data stores exam count", (string) get_single_row_from_query("select anzahl_pruefungsleistungen from modul_nach_semester_metadata where modul_id = " . (int) $vvz_modul_id . " and semester = 1"), '2');
is_equal("tx: update_modul_semester_data stores veranstaltungstyp count", (string) get_single_row_from_query("select anzahl from modul_nach_semester_veranstaltungstypen_anzahl where modul_id = " . (int) $vvz_modul_id . " and semester = 1 and veranstaltungstyp_id = 1"), '3');
update_modul_semester_data(1, 1, 'keine-zahl', '2', array(), $vvz_modul_id);
is_equal("tx: update_modul_semester_data rejects invalid credit points", (string) get_single_row_from_query("select credit_points from modul_nach_semester_metadata where modul_id = " . (int) $vvz_modul_id . " and semester = 1"), '5');
update_modul_semester_data(1, 1, '5', '2', array(), $vvz_modul_id);
is_equal("tx: update_modul_semester_data clears veranstaltungstyp counts", (string) get_single_row_from_query("select count(*) from modul_nach_semester_veranstaltungstypen_anzahl where modul_id = " . (int) $vvz_modul_id), '0');
$vvz_veranstaltungstyp_anzahlen = create_array_veranstaltungstyp_anzahl_by_modul_id_semester($vvz_modul_id, 1);
is_equal("tx: create_array_veranstaltungstyp_anzahl... returns array", is_array($vvz_veranstaltungstyp_anzahlen) ? 1 : 0, 1);
$vvz_veranstaltungstypen = get_veranstaltungstypen_modul_semester($vvz_modul_id, 1);
is_equal("tx: get_veranstaltungstypen_modul_semester returns array", is_array($vvz_veranstaltungstypen) ? 1 : 0, 1);
$vvz_credit = get_credit_points_and_anzahl_pruefungsleistungen_by_modul_id_and_semester($vvz_modul_id, 1);
is_equal("tx: get_credit_points... returns array", is_array($vvz_credit) ? 1 : 0, 1);
rquery('DELETE FROM `modul_nach_semester_veranstaltungstypen_anzahl` WHERE `modul_id` = ' . (int) $vvz_modul_id);
rquery('DELETE FROM `modul_nach_semester_metadata` WHERE `modul_id` = ' . (int) $vvz_modul_id);
rquery('DELETE FROM `modul_nach_semester` WHERE `modul_id` = ' . (int) $vvz_modul_id);
rquery('DELETE FROM `modul` WHERE `id` = ' . (int) $vvz_modul_id);
rquery('DELETE FROM `pruefungstyp` WHERE `id` = ' . (int) $vvz_pruefungstyp_id);

/* ============================================================ */
/* Prüfungen kopieren / zuordnen                                 */
/* ============================================================ */

rquery('INSERT INTO `veranstaltung` (`veranstaltungstyp_id`, `name`, `dozent_id`, `institut_id`, `semester_id`) VALUES (1, ' . esc($vvz_tag . '-Quelle') . ', ' . (int) $vvz_dozent_id . ', ' . (int) $vvz_institut_id . ', ' . (int) $vvz_semester_id . ')');
$vvz_von_id = mysqli_insert_id($GLOBALS['dbh']);
rquery('INSERT INTO `veranstaltung` (`veranstaltungstyp_id`, `name`, `dozent_id`, `institut_id`, `semester_id`) VALUES (1, ' . esc($vvz_tag . '-Ziel') . ', ' . (int) $vvz_dozent_id . ', ' . (int) $vvz_institut_id . ', ' . (int) $vvz_semester_id . ')');
$vvz_nach_id = mysqli_insert_id($GLOBALS['dbh']);

rquery('INSERT INTO `pruefungsnummer` (`pruefungsnummer`, `modul_id`, `pruefungstyp_id`, `bereich_id`, `modulbezeichnung`) VALUES (' . esc($vvz_tag . '-PN1') . ', NULL, NULL, NULL, ' . esc($vvz_tag . ' Modul') . ')');
$vvz_pn1 = mysqli_insert_id($GLOBALS['dbh']);
rquery('INSERT INTO `pruefungsnummer` (`pruefungsnummer`, `modul_id`, `pruefungstyp_id`, `bereich_id`, `modulbezeichnung`) VALUES (' . esc($vvz_tag . '-PN2') . ', NULL, NULL, NULL, ' . esc($vvz_tag . ' Modul') . ')');
$vvz_pn2 = mysqli_insert_id($GLOBALS['dbh']);

rquery('INSERT INTO `pruefung` (`veranstaltung_id`, `pruefungsnummer_id`, `date`, `raum_id`) VALUES (' . (int) $vvz_von_id . ', ' . (int) $vvz_pn1 . ', "2099-03-01", NULL)');
kopiere_pruefungen_von_nach($vvz_von_id, $vvz_nach_id, 0);
is_equal("tx: kopiere_pruefungen_von_nach copies", (string) get_single_row_from_query("select count(*) from pruefung where veranstaltung_id = " . (int) $vvz_nach_id), '1');
kopiere_pruefungen_von_nach($vvz_von_id, $vvz_nach_id, 1);
is_equal("tx: kopiere_pruefungen_von_nach replaces old data", (string) get_single_row_from_query("select count(*) from pruefung where veranstaltung_id = " . (int) $vvz_nach_id), '1');

assign_pruefungsnummer_to_veranstaltung(array($vvz_pn1, $vvz_pn2), $vvz_nach_id);
is_equal("tx: assign_pruefungsnummer_to_veranstaltung array", (string) get_single_row_from_query("select count(*) from pruefung where veranstaltung_id = " . (int) $vvz_nach_id), '2');
assign_pruefungsnummer_to_veranstaltung($vvz_pn1, $vvz_nach_id);
is_equal("tx: assign_pruefungsnummer_to_veranstaltung scalar", (string) get_single_row_from_query("select count(*) from pruefung where veranstaltung_id = " . (int) $vvz_nach_id), '1');

/* ============================================================ */
/* Veranstaltungs-Metadaten                                      */
/* ============================================================ */

rquery('INSERT INTO `veranstaltung` (`veranstaltungstyp_id`, `name`, `dozent_id`, `institut_id`, `semester_id`) VALUES (1, ' . esc($vvz_tag . '-Meta') . ', ' . (int) $vvz_dozent_id . ', ' . (int) $vvz_institut_id . ', ' . (int) $vvz_semester_id . ')');
$vvz_meta_v_id = mysqli_insert_id($GLOBALS['dbh']);

update_veranstaltung_metadata(
	$vvz_meta_v_id,
	'Mo',
	'1',
	'jede Woche',
	'2099-01-01',
	'42',
	'Wunsch',
	'Hinweis',
	'https://example.org/opal',
	'2099-02-01',
	null,
	null,
	array($vvz_pn1),
	0,
	array(1),
	null,
	array(),
	array(1),
	0,
	'https://example.org/video',
	array(1)
);

is_equal("tx: update_veranstaltung_metadata creates row", (string) get_single_row_from_query("select count(*) from veranstaltung_metadaten where veranstaltung_id = " . (int) $vvz_meta_v_id), '1');
is_equal("tx: update_veranstaltung_metadata wochentag", get_single_row_from_query("select wochentag from veranstaltung_metadaten where veranstaltung_id = " . (int) $vvz_meta_v_id), 'Mo');
is_equal("tx: update_veranstaltung_metadata videolink", get_single_row_from_query("select videolink from veranstaltung_metadaten where veranstaltung_id = " . (int) $vvz_meta_v_id), 'https://example.org/video');
is_equal("tx: update_veranstaltung_metadata language", (string) get_single_row_from_query("select count(*) from veranstaltung_to_language where veranstaltung_id = " . (int) $vvz_meta_v_id), '1');
is_equal("tx: update_veranstaltung_metadata praesenztyp", (string) get_single_row_from_query("select count(*) from veranstaltung_to_praesenztyp where veranstaltung_id = " . (int) $vvz_meta_v_id), '1');
is_equal("tx: update_veranstaltung_metadata bezug", (string) get_single_row_from_query("select count(*) from veranstaltung_nach_bezuegetypen where veranstaltung_id = " . (int) $vvz_meta_v_id), '1');
is_equal("tx: update_veranstaltung_metadata pruefung", (string) get_single_row_from_query("select count(*) from pruefung where veranstaltung_id = " . (int) $vvz_meta_v_id), '1');
is_equal("tx: get_raumplanung_relevante_daten after metadata", is_array(get_raumplanung_relevante_daten($vvz_meta_v_id)) ? 1 : 0, 1);
is_equal("tx: veranstaltung_hat_praesenztyp", veranstaltung_has_praesenztyp($vvz_meta_v_id, 1), true);
is_equal("tx: veranstaltung_hat_bezug", veranstaltung_has_bezug($vvz_meta_v_id, 1), true);
is_equal("tx: veranstaltung_hat_language", veranstaltung_has_language($vvz_meta_v_id, 1), true);
regex_matches("tx: video_conference_link after metadata", video_conference_link($vvz_meta_v_id), '/example\.org\/video/');

/* Update path (ON DUPLICATE KEY + relation replacement). */
update_veranstaltung_metadata(
	$vvz_meta_v_id,
	'Di',
	'2',
	'gerade Woche',
	'2099-01-02',
	'12',
	'Wunsch2',
	'Hinweis2',
	'https://example.org/opal2',
	'2099-02-02',
	null,
	null,
	array($vvz_pn2),
	1,
	array(2),
	null,
	array(),
	array(2),
	1,
	'https://example.org/video2',
	array(2)
);
is_equal("tx: update_veranstaltung_metadata updates wochentag", get_single_row_from_query("select wochentag from veranstaltung_metadaten where veranstaltung_id = " . (int) $vvz_meta_v_id), 'Di');
is_equal("tx: update_veranstaltung_metadata updates language", (string) get_single_row_from_query("select language_id from veranstaltung_to_language where veranstaltung_id = " . (int) $vvz_meta_v_id), '2');
is_equal("tx: update_veranstaltung_metadata updates praesenztyp", (string) get_single_row_from_query("select praesenztyp_id from veranstaltung_to_praesenztyp where veranstaltung_id = " . (int) $vvz_meta_v_id), '2');
is_equal("tx: update_veranstaltung_metadata updates bezug", (string) get_single_row_from_query("select bezuegetyp_id from veranstaltung_nach_bezuegetypen where veranstaltung_id = " . (int) $vvz_meta_v_id), '2');
is_equal("tx: update_veranstaltung_metadata updates pruefung", (string) get_single_row_from_query("select pruefungsnummer_id from pruefung where veranstaltung_id = " . (int) $vvz_meta_v_id), (string) $vvz_pn2);
is_equal("tx: update_veranstaltung_metadata master niveau", (string) get_single_row_from_query("select master_niveau from veranstaltung where id = " . (int) $vvz_meta_v_id), '1');

/* ============================================================ */
/* create_veranstaltung (uses update_veranstaltung_metadata)     */
/* ============================================================ */

$vvz_created_v_id = create_veranstaltung($vvz_tag . '-Neu', $vvz_dozent_id, 1, $vvz_institut_id, $vvz_semester_id, array(1), null, array(1));
is_equal("tx: create_veranstaltung returns id", $vvz_created_v_id !== null ? 1 : 0, 1);
is_equal("tx: create_veranstaltung creates row", get_veranstaltung_name($vvz_created_v_id), $vvz_tag . '-Neu');
is_equal("tx: create_veranstaltung creates metadata", (string) get_single_row_from_query("select count(*) from veranstaltung_metadaten where veranstaltung_id = " . (int) $vvz_created_v_id), '1');
easter_egg($vvz_tag . ' Sex');
is_equal("tx: easter_egg detects word", global_exists('easter_egg'), 1);
$GLOBALS['easter_egg'] = array();

/* ============================================================ */
/* Sperrvermerk                                                  */
/* ============================================================ */

set_semester_sperrvermerk($vvz_semester_id, 1);
is_equal("tx: semester_has_sperrvermerk true", semester_has_sperrvermerk($vvz_semester_id), true);
rquery('UPDATE `sperrvermerk` SET `last_update` = now() - INTERVAL 1 HOUR WHERE `semester_id` = ' . (int) $vvz_semester_id);
rquery('UPDATE `veranstaltung` SET `name` = ' . esc($vvz_tag . '-Neu2') . ' WHERE `id` = ' . (int) $vvz_created_v_id);
ob_start();
$vvz_sperrvermerk_result = sperrvermerk_table(array(array($vvz_semester_id)), 'Test', 0);
$vvz_sperrvermerk_html = ob_get_clean();
is_equal("tx: sperrvermerk_table returns int", is_int($vvz_sperrvermerk_result) ? 1 : 0, 1);
is_equal("tx: sperrvermerk_table renders table", strpos($vvz_sperrvermerk_html, '<table>') !== false ? 1 : 0, 1);
set_semester_sperrvermerk($vvz_semester_id, 0);
is_equal("tx: semester_has_sperrvermerk false", semester_has_sperrvermerk($vvz_semester_id), false);
ob_start();
$vvz_sperrvermerk_empty = sperrvermerk_table(array(array($vvz_semester_id)), '', 0);
ob_end_clean();
is_equal("tx: sperrvermerk_table without lock returns 0", $vvz_sperrvermerk_empty, 0);

/* ============================================================ */
/* Cleanup                                                       */
/* ============================================================ */

foreach (array($vvz_meta_v_id, $vvz_created_v_id, $vvz_von_id, $vvz_nach_id) as $vvz_cleanup_v_id) {
	rquery('DELETE FROM `pruefung` WHERE `veranstaltung_id` = ' . (int) $vvz_cleanup_v_id);
	rquery('DELETE FROM `veranstaltung_metadaten` WHERE `veranstaltung_id` = ' . (int) $vvz_cleanup_v_id);
	rquery('DELETE FROM `veranstaltung_to_language` WHERE `veranstaltung_id` = ' . (int) $vvz_cleanup_v_id);
	rquery('DELETE FROM `veranstaltung_to_praesenztyp` WHERE `veranstaltung_id` = ' . (int) $vvz_cleanup_v_id);
	rquery('DELETE FROM `veranstaltung_nach_bezuegetypen` WHERE `veranstaltung_id` = ' . (int) $vvz_cleanup_v_id);
	rquery('DELETE FROM `veranstaltung_nach_lv_nr` WHERE `veranstaltung_id` = ' . (int) $vvz_cleanup_v_id);
	rquery('DELETE FROM `einzelne_termine` WHERE `veranstaltung_id` = ' . (int) $vvz_cleanup_v_id);
	rquery('DELETE FROM `raumplanung_relevante_daten_geaendert` WHERE `veranstaltung_id` = ' . (int) $vvz_cleanup_v_id);
	rquery('DELETE FROM `veranstaltung` WHERE `id` = ' . (int) $vvz_cleanup_v_id);
}
rquery('DELETE FROM `pruefungsnummer` WHERE `id` IN (' . (int) $vvz_pn1 . ', ' . (int) $vvz_pn2 . ')');

foreach ($vvz_saved_globals as $vvz_global_name => $vvz_global_value) {
	$GLOBALS[$vvz_global_name] = $vvz_global_value;
}
$_POST = $vvz_saved_post;
$_GET = $vvz_saved_get;
$_COOKIE = $vvz_saved_cookie;
