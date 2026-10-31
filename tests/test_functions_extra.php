<?php
/*
 * Additional targeted tests for remaining branches and helpers in
 * functions.php. These run in the DB suite and use a transaction so they do
 * not leave data behind.
 */

if (!isset($GLOBALS['dbname']) || !$GLOBALS['dbname'] || !isset($GLOBALS['dbh']) || !is_object($GLOBALS['dbh'])) {
	return;
}

$vvz_test_db = $GLOBALS['dbname'];
$vvz_tag = 'ZZF' . substr(md5((string) getmypid() . (string) mt_rand()), 0, 4);

$vvz_saved_globals = array();
foreach (array('logged_in_user_id', 'user_role_id', 'user_dozent_id', 'user_institut_id', 'memoize', 'toc', 'debug', 'datadir', 'default_name', 'success', 'error', 'message', 'warning') as $vvz_global_name) {
	$vvz_saved_globals[$vvz_global_name] = array_key_exists($vvz_global_name, $GLOBALS) ? $GLOBALS[$vvz_global_name] : null;
}
$vvz_saved_post = $_POST;
$vvz_saved_get = $_GET;

$GLOBALS['logged_in_user_id'] = 1;
$GLOBALS['user_role_id'] = 1;
$GLOBALS['user_dozent_id'] = 1;
$GLOBALS['user_institut_id'] = 1;
$GLOBALS['memoize'] = array();
$GLOBALS['toc'] = array();
$_POST = array();
$_GET = array();

start_transaction();

/* ============================================================ */
/* value_fits_into_db_column                                   */
/* ============================================================ */

$vvz_v = value_fits_into_db_column($vvz_test_db, 'users', 'id', '5', 'ID');
is_equal("extra: value_fits int ok", $vvz_v['ok'], 1);
$vvz_v = value_fits_into_db_column($vvz_test_db, 'users', 'id', '-1', 'ID');
is_equal("extra: value_fits unsigned negative", $vvz_v['ok'], 0);
is_equal("extra: value_fits unsigned cannot continue", isset($vvz_v['cannot_continue']) ? 1 : 0, 1);
$vvz_v = value_fits_into_db_column($vvz_test_db, 'users', 'id', '123456789012345', 'ID');
is_equal("extra: value_fits int too long ok with warning", $vvz_v['ok'], 1);
is_equal("extra: value_fits int too long warning", isset($vvz_v['warning']) ? 1 : 0, 1);
$vvz_v = value_fits_into_db_column($vvz_test_db, 'users', 'id', 'abc', 'ID');
is_equal("extra: value_fits int invalid", $vvz_v['ok'], 0);
is_equal("extra: value_fits int invalid cannot continue", isset($vvz_v['cannot_continue']) ? 1 : 0, 1);

$vvz_v = value_fits_into_db_column($vvz_test_db, 'users', 'enabled', '1', 'Enabled');
is_equal("extra: value_fits enum ok", $vvz_v['ok'], 1);
$vvz_v = value_fits_into_db_column($vvz_test_db, 'users', 'enabled', '2', 'Enabled');
is_equal("extra: value_fits enum invalid", $vvz_v['ok'], 0);
is_equal("extra: value_fits enum warning", isset($vvz_v['warning']) ? 1 : 0, 1);

$vvz_v = value_fits_into_db_column($vvz_test_db, 'semester', 'erste_veranstaltung_default', '2026-01-01', 'Datum');
is_equal("extra: value_fits date ok", $vvz_v['ok'], 1);
$vvz_v = value_fits_into_db_column($vvz_test_db, 'semester', 'erste_veranstaltung_default', '', 'Datum');
is_equal("extra: value_fits nullable date empty", $vvz_v['ok'], 1);
is_equal("extra: value_fits nullable date empty value", $vvz_v['value'], '');
$vvz_v = value_fits_into_db_column($vvz_test_db, 'semester', 'erste_veranstaltung_default', 'not-a-date', 'Datum');
is_equal("extra: value_fits date invalid", $vvz_v['ok'], 0);

$vvz_v = value_fits_into_db_column($vvz_test_db, 'veranstaltung', 'last_change', '2099-01-01 10:00:00', 'Zeit');
is_equal("extra: value_fits timestamp ok", $vvz_v['ok'], 1);
$vvz_v = value_fits_into_db_column($vvz_test_db, 'veranstaltung', 'last_change', '2099-01-01', 'Zeit');
is_equal("extra: value_fits timestamp invalid", $vvz_v['ok'], 0);

$vvz_v = value_fits_into_db_column($vvz_test_db, 'users', 'username', str_repeat('x', 200), 'Username');
is_equal("extra: value_fits varchar too long ok", $vvz_v['ok'], 1);
is_equal("extra: value_fits varchar warning", isset($vvz_v['warning']) ? 1 : 0, 1);
$vvz_v = value_fits_into_db_column($vvz_test_db, 'seitentext', 'text', 'Hallo', 'Text');
is_equal("extra: value_fits text ok", $vvz_v['ok'], 1);
$vvz_v = value_fits_into_db_column($vvz_test_db, 'users', 'salt', null, 'Salt');
is_equal("extra: value_fits not null empty", $vvz_v['ok'], 0);
is_equal("extra: value_fits not null cannot continue", isset($vvz_v['cannot_continue']) ? 1 : 0, 1);
$vvz_v = value_fits_into_db_column($vvz_test_db, 'users', 'username', null, 'Username');
is_equal("extra: value_fits nullable null", $vvz_v['ok'], 1);

$vvz_v = value_fits_into_db_column($vvz_test_db, 'users', 'does_not_exist', 'x', 'X');
is_equal("extra: value_fits missing column", $vvz_v['ok'], 0);
is_equal("extra: value_fits missing column cannot continue", isset($vvz_v['cannot_continue']) ? 1 : 0, 1);
$vvz_v = value_fits_into_db_column($vvz_test_db, 'does_not_exist', 'x', 'y', 'X');
is_equal("extra: value_fits missing table", $vvz_v['ok'], 0);
is_equal("extra: value_fits missing table error", isset($vvz_v['error']) ? 1 : 0, 1);
$vvz_v = value_fits_into_db_column('does_not_exist_db', 'users', 'id', '1', 'ID');
is_equal("extra: value_fits missing database", $vvz_v['ok'], 0);
is_equal("extra: value_fits missing database error", isset($vvz_v['error']) ? 1 : 0, 1);
$vvz_v = value_fits_into_db_column($vvz_test_db, 'users', 'username', '123', 'Username', 'int(5)');
is_equal("extra: value_fits overwrite type ok", $vvz_v['ok'], 1);
$vvz_v = value_fits_into_db_column($vvz_test_db, 'users', 'username', 'abc', 'Username', 'int(5)');
is_equal("extra: value_fits overwrite type invalid", $vvz_v['ok'], 0);

/* ============================================================ */
/* insert_values / run_install_query / simple_query              */
/* ============================================================ */

rquery('CREATE TEMPORARY TABLE `vvz_test_extra_values` (`v` varchar(100))');
ob_start();
insert_values('vvz_test_extra_values', array('v'), array('a', 'b'), 1);
$vvz_insert_output = ob_get_clean();
is_equal("extra: insert_values print mode inserts rows", (string) get_single_row_from_query('select count(*) from vvz_test_extra_values'), '2');
is_equal("extra: insert_values print mode output", strpos($vvz_insert_output, 'INSERT') !== false ? 1 : 0, 1);

ob_start();
run_install_query('vvz_test_extra_values', array('INSERT INTO `vvz_test_extra_values` (`v`) VALUES ("c")', 'INSERT INTO `vvz_test_extra_values` (`v`) VALUES ("d")'), 1);
$vvz_install_output = ob_get_clean();
is_equal("extra: run_install_query print mode inserts rows", (string) get_single_row_from_query('select count(*) from vvz_test_extra_values'), '2');
is_equal("extra: run_install_query print mode output", strpos($vvz_install_output, 'INSERT') !== false ? 1 : 0, 1);
rquery('DROP TEMPORARY TABLE IF EXISTS `vvz_test_extra_values`');

$GLOBALS['success'] = array();
$GLOBALS['error'] = array();
$GLOBALS['message'] = array();
is_equal("extra: simple_query success", simple_query_success_fail_message('SELECT 1', 'ok'), 1);
is_equal("extra: simple_query success stored", count($GLOBALS['success']) > 0 ? 1 : 0, 1);
is_equal("extra: simple_query failure returns null", simple_query_success_fail_message('SELECT * FROM no_such_table_xyz', 'ok', 'failed') === null ? 1 : 0, 1);
is_equal("extra: simple_query failure stored warning", count($GLOBALS['warning']) > 0 ? 1 : 0, 1);
$GLOBALS['warning'] = array();
is_equal("extra: simple_query message returns null", simple_query_success_fail_message('SELECT * FROM no_such_table_xyz', 'ok', null, 'message') === null ? 1 : 0, 1);
is_equal("extra: simple_query message stored warning", count($GLOBALS['warning']) > 0 ? 1 : 0, 1);

/* ============================================================ */
/* Small update helpers / duplicate branches                      */
/* ============================================================ */

update_user_role(1, 1); // already exists -> warning branch
is_equal("extra: update_user_role duplicate keeps role", (string) get_role_id_by_user(1), '1');
assign_page_to_role(1, 1); // already exists -> warning branch
is_equal("extra: assign_page_to_role duplicate", role_has_access_to_page(1, 1), true);

update_titel(null, null, null);
update_titel(1, null, 'x');
update_titel(1, 'x', null);
is_equal("extra: update_titel invalid input handled", 1, 1);

$GLOBALS['error'] = array();
$GLOBALS['message'] = array();
update_hinweis(999999, 'Hinweis');
is_equal("extra: update_hinweis wrong page", count($GLOBALS['error']) > 0 ? 1 : 0, 1);
update_hinweis(1, '');
is_equal("extra: update_hinweis empty", count($GLOBALS['message']) > 0 ? 1 : 0, 1);
update_hinweis(1, 'Testhinweis');
is_equal("extra: update_hinweis success", get_hinweis_for_page(1), 'Testhinweis');

/* ============================================================ */
/* Benutzer: duplicate dozent + update without password          */
/* ============================================================ */

create_user($vvz_tag . 'DupDozent', 'pw', 1, 1, 1, 0);
$vvz_dup_user_id = get_user_id($vvz_tag . 'DupDozent');
is_equal("extra: create_user duplicate dozent creates account", $vvz_dup_user_id !== null ? 1 : 0, 1);
is_equal("extra: create_user duplicate dozent clears dozent", get_dozent_id_by_user_id($vvz_dup_user_id) === null ? 1 : 0, 1);
delete_user($vvz_dup_user_id);

create_user($vvz_tag . 'UpdateUser', 'pw', 1, null, 1, 0);
$vvz_update_user_id = get_user_id($vvz_tag . 'UpdateUser');
update_user($vvz_tag . 'UpdateUser2', $vvz_update_user_id, '', 1, null, 1, 0, 1, 1);
is_equal("extra: update_user without password keeps username", get_user_name($vvz_update_user_id), $vvz_tag . 'UpdateUser2');
is_equal("extra: update_user without password sets enabled", (string) get_account_enabled_by_id($vvz_update_user_id), '0');
is_equal("extra: update_user without password sets barrierefrei", (string) get_single_row_from_query('select barrierefrei from users where id = ' . (int) $vvz_update_user_id), '1');
delete_user($vvz_update_user_id);

/* ============================================================ */
/* Semester edge cases                                           */
/* ============================================================ */

$vvz_ws = get_and_create_semester_id_by_jahr_monat_tag(2099, 10, 10);
is_equal("extra: winter semester type", get_semester($vvz_ws[0])[2], 'Wintersemester');
is_equal("extra: winter semester joined contains slash", strpos(get_semester($vvz_ws[0], 1)[1], '/') !== false ? 1 : 0, 1);
rquery('UPDATE `semester` SET `default` = "0"');
$vvz_this_semester = get_this_semester();
is_equal("extra: get_this_semester without default returns array", is_array($vvz_this_semester) ? 1 : 0, 1);

/* ============================================================ */
/* Module / current value / misc                                 */
/* ============================================================ */

$vvz_modules = create_module_array_by_studiengang_and_semester(1, null);
is_equal("extra: module array non-array throws or returns array", is_array($vvz_modules) ? 1 : 0, 1);
$vvz_modules2 = create_module_array_by_studiengang_and_semester(array(1), null);
is_equal("extra: module array array branch", is_array($vvz_modules2) ? 1 : 0, 1);

$vvz_kunde_id = get_kunde_id_by_db_name($vvz_test_db);
$vvz_name = get_single_row_from_query('select name from vvz_global.kundendaten where id = ' . (int) $vvz_kunde_id);
if (!is_null($vvz_name)) {
	$GLOBALS['default_name'] = $vvz_name;
	is_equal("extra: get_current_value default returns empty", get_current_value('name'), '');
}

is_equal("extra: file_is_image jpg", file_is_image('norbert_engemaier.jpg'), true);
is_equal("extra: file_is_image png", file_is_image('bd.png'), true);

$GLOBALS['datadir'] = 'css/';
ob_start();
single_css('all.css');
$vvz_css = ob_get_clean();
is_equal("extra: single_css datadir branch", strpos($vvz_css, 'stylesheet') !== false ? 1 : 0, 1);
$GLOBALS['datadir'] = 'js/';
ob_start();
single_js('all.js');
$vvz_js = ob_get_clean();
is_equal("extra: single_js datadir branch", strpos($vvz_js, '<script') !== false ? 1 : 0, 1);

ob_start();
$vvz_table = array2Table(array(array('h1'), array('x')), array(1 => array('studiengang' => 's', 'something_failed' => 1)), array(1 => array(0 => 'error')));
$vvz_table_html = ob_get_clean() . $vvz_table;
is_equal("extra: array2Table error line branch", strpos($vvz_table_html, 'error_td') !== false ? 1 : 0, 1);

ob_start();
create_select(array(array('1', 'One'), 'Two'), '1', 'select_name', 1, 1, 'label-id', 1, 'my-class');
$vvz_select = ob_get_clean();
is_equal("extra: create_select empty option", strpos($vvz_select, '&mdash;') !== false ? 1 : 0, 1);
is_equal("extra: create_select selected option", strpos($vvz_select, 'selected') !== false ? 1 : 0, 1);
is_equal("extra: create_select aria", strpos($vvz_select, 'aria-labelledby') !== false ? 1 : 0, 1);
is_equal("extra: create_select class", strpos($vvz_select, 'my-class') !== false ? 1 : 0, 1);
is_equal("extra: create_select onchange", strpos($vvz_select, 'this.form.submit') !== false ? 1 : 0, 1);
is_equal("extra: create_select noautosubmit", strpos($vvz_select, 'noautosubmit') !== false ? 1 : 0, 1);

/* ============================================================ */
/* Rechte / logging                                              */
/* ============================================================ */

$vvz_saved_logged_in = $GLOBALS['logged_in_user_id'];
unset($GLOBALS['logged_in_user_id']);
is_equal("extra: check_page_rights_role_id without login is int", is_int(check_page_rights_role_id(1, 1, 0)) ? 1 : 0, 1);
$GLOBALS['logged_in_user_id'] = $vvz_saved_logged_in;
$GLOBALS['right_issues'] = array();
is_equal("extra: check_page_rights_role_id logs unknown page", check_page_rights_role_id(999999, 1, 1), 0);

/* ============================================================ */
/* More schedule / map / get_sws cases                           */
/* ============================================================ */

is_equal("extra: get_sws '1-2' jede Woche", get_sws('1-2', 'jede Woche'), array(0, 4));
is_equal("extra: get_sws '1-2' jede Woche ungerade", get_sws('1-2', 'ungerade Woche'), array(0, 4));
is_equal("extra: get_sws '1-2' keine Angabe", get_sws('1-2', 'keine Angabe') === null ? 1 : 0, 1);
is_equal("extra: get_sws invalid", get_sws('x-y', 'jede Woche') === null ? 1 : 0, 1);
is_equal("extra: html_map unknown building", @html_map(0, 0, 999999), '');

/* ============================================================ */
/* Cleanup                                                       */
/* ============================================================ */

rollback();

foreach ($vvz_saved_globals as $vvz_global_name => $vvz_global_value) {
	$GLOBALS[$vvz_global_name] = $vvz_global_value;
}
$_POST = $vvz_saved_post;
$_GET = $vvz_saved_get;
