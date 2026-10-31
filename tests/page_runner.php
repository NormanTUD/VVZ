<?php
/*
 * Execute a single page file in an isolated PHP process.
 *
 * This is used by tests/run_page_tests.php to smoke-test the subpages and to
 * collect code coverage for them. Running each page in its own process means
 * a page that calls exit()/die() or emits headers cannot abort the whole test
 * run.
 */

require_once(__DIR__ . '/cli_guard.php');

$page = isset($argv[1]) ? $argv[1] : '';
if ($page === '' || !is_file($page)) {
	fwrite(STDERR, "Page not found: $page\n");
	exit(2);
}
$page = realpath($page);
$root = realpath(__DIR__ . '/..');

$GLOBALS['no_selftest'] = 1;
$GLOBALS['no_selftest_force'] = 1;
require_once($root . '/functions.php');

if (!isset($GLOBALS['dbh']) || !is_object($GLOBALS['dbh'])) {
	fwrite(STDERR, "No database connection\n");
	exit(3);
}

$GLOBALS['logged_in_user_id'] = 1;
$GLOBALS['user_role_id'] = 1;
$GLOBALS['user_dozent_id'] = 1;
$GLOBALS['user_institut_id'] = 1;
$GLOBALS['adminpage'] = 1;
$GLOBALS['logged_in'] = 1;
$GLOBALS['logged_in_data'] = array(1, 'Admin', 1, 1, 1);
$GLOBALS['this_semester_id'] = get_current_semester_id();
$GLOBALS['pruefungen_already_done'] = array();
$GLOBALS['pruefungen_already_chosen'] = array();
$GLOBALS['deletion_db'] = null;
$GLOBALS['debug'] = array();
$GLOBALS['toc'] = array();
$GLOBALS['shown_help_ids'] = array('google_maps_icon' => 0, 'calendar' => 0);

$page_file = basename($page);
$page_id = get_single_row_from_query("select id from page where file = " . esc($page_file) . " limit 1");
if (!$page_id) {
	$page_id = get_single_row_from_query("select id from page where file = " . esc('pages/' . $page_file) . " limit 1");
}
$GLOBALS['this_page_number'] = $page_id ? (int) $page_id : 1;
if ($page_id) {
	// Make every subpage reachable for the smoke test. The insert is rolled
	// back together with all other changes when the child process exits.
	rquery('INSERT IGNORE INTO `role_to_page` (`role_id`, `page_id`) VALUES (1, ' . esc($page_id) . ')');
}

$variant = isset($argv[2]) ? $argv[2] : 'default';
$_GET = $page_id ? array('page' => (int) $page_id) : array();
$_POST = array();

if ($variant === 'rich') {
	$vvz_semester = get_current_semester_id();
	$vvz_veranstaltung = get_single_row_from_query("select id from veranstaltung order by id limit 1");
	$_GET = array_merge($_GET, array(
		'semester' => $vvz_semester,
		'institut' => 1,
		'studiengang' => 'alle',
		'dozent' => 'alle',
		'dozent_id' => 1,
		'pruefungsamt' => 'alle',
		'bereich' => 'alle',
		'raum' => 'alle',
		'modul' => 'alle',
		'unreplied' => '0',
		'show_items' => 1,
		'show_ausgeschiedene_dozenten' => 1,
		'id' => $vvz_veranstaltung ? (int) $vvz_veranstaltung : 1,
	));
	$_POST = array(
		'from' => '2000-01-01',
		'to' => '2099-12-31',
		'show_bots' => 1,
		'soi_institut' => 1,
		'soi_studiengang_mode' => 'auto',
		'soi_extract_method' => 'auto',
	);
}

if (strpos($variant, 'stage:') === 0) {
	$stage = substr($variant, strlen('stage:'));
	$_GET['stage'] = $stage;
	if (in_array($stage, array('detail', 'download', 'analyze', 'debug_text', 'cross_validate', 'preview', 'commit', 'commit_v2'), true)) {
		$import_id = get_single_row_from_query("select id from studienordnung_import where filename = 'page-test.pdf' limit 1");
		if (!$import_id) {
			$fixture = __DIR__ . '/fixtures/12_06soBA1228.08.2023.pdf';
			if (!is_file($fixture)) {
				$fixture = __DIR__ . '/fixtures/03_06soBA128.08.2023.pdf';
			}
			$pdf_bin = is_file($fixture) ? file_get_contents($fixture) : '%PDF-1.4';
			$pdf_b64 = base64_encode($pdf_bin);
			rquery('INSERT INTO `studienordnung_import` (`filename`, `pdf_sha256`, `pdf_size`, `pdf_data`, `degree`, `program_name`, `modules_found`, `imported_by_user_id`) VALUES ("page-test.pdf", ' . esc(hash('sha256', $pdf_bin)) . ', ' . strlen($pdf_bin) . ', ' . esc($pdf_b64) . ', "Bachelor", "Teststudiengang", 0, 1)');
			$import_id = mysqli_insert_id($GLOBALS['dbh']);
		}
		$_GET['id'] = (int) $import_id;
		if ($stage === 'commit' || $stage === 'commit_v2') {
			$_POST['import_id'] = (int) $import_id;
			$_POST['soi_import_id'] = (int) $import_id;
			$_POST['modules'] = array();
			$_POST['soi_modules_v2'] = '[]';
			$_POST['soi_anlage2_v2'] = '[]';
		}
	}
}

$_COOKIE = array(
	'absolviertepruefungsleistungen' => '[]',
	'geplante_pruefungsleistungen' => '[]',
);
$_SERVER['SCRIPT_NAME'] = '/admin';
$_SERVER['PHP_SELF'] = '/admin';
$_SERVER['REQUEST_URI'] = '/admin?page=' . (int) $GLOBALS['this_page_number'];
$_SERVER['QUERY_STRING'] = 'page=' . (int) $GLOBALS['this_page_number'];
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['REQUEST_METHOD'] = 'GET';

/* Roll back any modifications when the page exits. The coverage bootstrap
 * writes its data in its own shutdown function; this one only protects the
 * test database. */
register_shutdown_function(function () {
	if (isset($GLOBALS['dbh']) && is_object($GLOBALS['dbh'])) {
		@rquery('rollback');
	}
});

chdir(dirname($page));
ob_start();
$status = 'OK';
try {
	include($page);
} catch (\Throwable $e) {
	$status = 'EXCEPTION: ' . get_class($e) . ': ' . $e->getMessage();
}
$output = ob_get_clean();
fwrite(STDOUT, "PAGE_STATUS: " . $status . "\n");
fwrite(STDOUT, "PAGE_OUTPUT_LENGTH: " . strlen($output) . "\n");
if ($status !== 'OK') {
	fwrite(STDERR, $status . "\n");
}
