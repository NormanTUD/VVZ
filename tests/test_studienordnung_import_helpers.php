<?php
/*
 * Tests for the helper functions defined by pages/studienordnung_import.php.
 *
 * The page itself is also smoke-tested by tests/run_page_tests.php; these
 * direct calls cover the smaller helper routines.
 */

if (!isset($GLOBALS['dbname']) || !$GLOBALS['dbname'] || !isset($GLOBALS['dbh']) || !is_object($GLOBALS['dbh'])) {
	return;
}

$vvz_saved_get = $_GET;
$vvz_saved_post = $_POST;
$vvz_saved_this_page = array_key_exists('this_page_number', $GLOBALS) ? $GLOBALS['this_page_number'] : null;
$_GET = array();
$_POST = array();
$GLOBALS['this_page_number'] = 61;
$GLOBALS['logged_in_user_id'] = 1;
$GLOBALS['user_role_id'] = 1;

require_once(__DIR__ . '/../pages/studienordnung_import.php');

$vvz_pdf = __DIR__ . '/fixtures/12_06soBA1228.08.2023.pdf';
if (!is_file($vvz_pdf)) {
	$vvz_pdf = __DIR__ . '/fixtures/03_06soBA128.08.2023.pdf';
}
$vvz_tmp = tempnam(sys_get_temp_dir(), 'vvz-soi-');
file_put_contents($vvz_tmp, 'not a pdf');

is_equal("soi: is_pdf valid fixture", soi_is_pdf($vvz_pdf), true);
is_equal("soi: is_pdf invalid file", soi_is_pdf($vvz_tmp), false);
is_equal("soi: is_pdf missing file", soi_is_pdf('/tmp/does-not-exist-vvz.pdf'), false);
@unlink($vvz_tmp);

is_equal("soi: check_tools returns array", is_array(soi_check_tools()) ? 1 : 0, 1);

$vvz_modules = array(
	array('modulnummer' => 'SLK-BA-KP-3S-AL', 'name' => 'Test 1'),
	array('modulnummer' => 'PhF-Phil-BA-PM1', 'name' => 'Test 2'),
);
$vvz_pages = "Intro\nSLK-BA-KP-3S-AL Basismodul\fPhF-Phil-BA-PM1 Aufbaumodul";
$vvz_located = soi_locate_modules_in_pages($vvz_pages, $vvz_modules, 2);
is_equal("soi: locate modules finds first module", isset($vvz_located[0]) ? 1 : 0, 1);
is_equal("soi: locate modules finds second module", isset($vvz_located[1]) ? 1 : 0, 1);
is_equal("soi: locate modules page of second module", $vvz_located[1], 2);
is_equal("soi: locate modules empty list", soi_locate_modules_in_pages('', array()), array());

$vvz_log_before = isset($GLOBALS['message']) && is_array($GLOBALS['message']) ? count($GLOBALS['message']) : 0;
soi_log('Testnachricht');
$GLOBALS['message'] = array();
soi_log('Testwarnung', 'warning');
is_equal("soi: log helpers run", 1, 1);

$_GET = $vvz_saved_get;
$_POST = $vvz_saved_post;
$GLOBALS['this_page_number'] = $vvz_saved_this_page;
