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

start_transaction();

$GLOBALS['logged_in_user_id'] = 1;
$GLOBALS['user_role_id'] = 1;
$GLOBALS['user_dozent_id'] = 1;
$GLOBALS['user_institut_id'] = 1;
$GLOBALS['adminpage'] = 1;
$GLOBALS['logged_in'] = 1;
$GLOBALS['logged_in_data'] = array(1, 'Admin', 1, 1, 1);
$GLOBALS['this_semester_id'] = get_and_create_this_semester();
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
if (!$page_id) {
	$page_id = get_single_row_from_query("select id from page where file = " . esc($page_file) . " limit 1");
}
if (!$page_id) {
	// Some page files are intentionally not in the page table (hidden tools).
	// Create a temporary row so check_page_rights() lets the page run.
	rquery('INSERT INTO `page` (`name`, `file`, `show_in_navigation`, `parent`, `show_in_startpage`) VALUES (' . esc('ReqTest ' . $page_file) . ', ' . esc($page_file) . ', "0", NULL, 0)');
	$page_id = mysqli_insert_id($GLOBALS['dbh']);
	$GLOBALS['memoize'] = array();
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
		'studiengang' => 1,
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

if (strpos($variant, 'param:') === 0) {
	$key = substr($variant, strlen('param:'));
	$value = '1';
	if ($key === 'unreplied') {
		$value = '0';
	} else if ($key === 'table') {
		$value = 'language';
	} else if ($key === 'studiengang') {
		$value = '1';
	} else if ($key === 'semester') {
		$value = (string) get_current_semester_id();
	} else if ($key === 'institut' || $key === 'dozent' || $key === 'pruefungsamt' || $key === 'bereich' || $key === 'raum' || $key === 'modul' || $key === 'id' || $key === 'dozent_id') {
		$value = '1';
	}
	$_GET[$key] = $value;
	$_POST[$key] = $value;
}

if (strpos($variant, 'table:') === 0) {
	$_GET['table'] = substr($variant, strlen('table:'));
}

if (strpos($variant, 'stage:') === 0) {
	$stage = substr($variant, strlen('stage:'));
	$_GET['stage'] = $stage;
	if ($stage === 'upload' || $stage === 'upload_preview' || $stage === 'upload_empty' || $stage === 'upload_invalid') {
		$fixture = __DIR__ . '/fixtures/12_06soBA1228.08.2023.pdf';
		if (!is_file($fixture)) {
			$fixture = __DIR__ . '/fixtures/03_06soBA128.08.2023.pdf';
		}
		$_GET['ajax'] = '1';
		if ($stage === 'upload_empty') {
			$_FILES = array();
		} else if ($stage === 'upload_invalid') {
			$invalid = tempnam(sys_get_temp_dir(), 'vvz-invalid-pdf-');
			file_put_contents($invalid, 'not a pdf');
			$_FILES['pdf'] = array('error' => UPLOAD_ERR_OK, 'tmp_name' => $invalid, 'name' => 'invalid.txt');
		} else {
			$_FILES['pdf'] = array('error' => UPLOAD_ERR_OK, 'tmp_name' => $fixture, 'name' => 'reqtest.pdf');
		}
		$_POST['soi_extract_method'] = 'auto';
		$_POST['soi_institut'] = 1;
		$_POST['soi_studiengang_mode'] = 'new';
		$_POST['new_studiengang_name'] = 'ReqSoiStudiengang';
		$_POST['auto_commit'] = ($stage === 'upload_preview' ? 0 : 1);
		$_POST['create_pruefungsnummern'] = 1;
	}
	if (in_array($stage, array('detail', 'download', 'analyze', 'debug_text', 'cross_validate', 'preview', 'commit', 'commit_v2'), true)) {
		$import_id = get_single_row_from_query("select id from studienordnung_import where filename = 'page-test.pdf' limit 1");
		if (!$import_id) {
			$fixture = __DIR__ . '/fixtures/12_06soBA1228.08.2023.pdf';
			if (!is_file($fixture)) {
				$fixture = __DIR__ . '/fixtures/03_06soBA128.08.2023.pdf';
			}
			$pdf_bin = is_file($fixture) ? file_get_contents($fixture) : '%PDF-1.4';
			$pdf_b64 = base64_encode($pdf_bin);
			rquery('INSERT INTO `studienordnung_import` (`studiengang_id`, `filename`, `pdf_sha256`, `pdf_size`, `pdf_data`, `degree`, `program_name`, `modules_found`, `imported_by_user_id`) VALUES (1, "page-test.pdf", ' . esc(hash('sha256', $pdf_bin)) . ', ' . strlen($pdf_bin) . ', ' . esc($pdf_b64) . ', "Bachelor", "Teststudiengang", 0, 1)');
			$import_id = mysqli_insert_id($GLOBALS['dbh']);
		}
		$_GET['id'] = (int) $import_id;
		if ($stage === 'commit' || $stage === 'commit_v2') {
			$_POST['import_id'] = (int) $import_id;
			$_POST['soi_import_id'] = (int) $import_id;
			$_POST['create_pruefungsnummern'] = 1;
			$_POST['soi_create_pruefungsnummern'] = 1;
			$module = array(
				'include' => 1,
				'modulnummer' => 'REQ-M1',
				'name' => 'Req Modul 1',
				'lp' => 5,
				'sws' => '2',
				'sws_total' => 2,
				'dauer_semester' => 1,
				'pruefungstypen' => array('Klausur'),
			);
			$_POST['modules'] = array($module);
			$_POST['soi_modules_v2'] = json_encode(array($module));
			$_POST['soi_anlage2_v2'] = json_encode(array(array('include' => 1, 'modulnummer' => 'REQ-M1', 'name' => 'Req Modul 1', 'lp' => 5, 'semester' => array(array('semester' => 1, 'sws' => array('2', '0', '0', '0', '0', '0', '0'), 'pl_count' => 1)))));
		}
		if ($stage === 'preview') {
			$_SESSION['soi_preview'] = array(
				'import_id' => (int) $import_id,
				'filename' => 'page-test.pdf',
				'program' => 'Teststudiengang',
				'degree' => 'Bachelor',
				'studiengang_id' => 1,
				'create_pruefungsnummern' => 1,
				'reuse' => 0,
				'modules' => array(array('include' => 1, 'modulnummer' => 'REQ-M1', 'name' => 'Req Modul 1', 'lp' => 5, 'sws_total' => 2, 'dauer_semester' => 1, 'pruefungstypen' => array('Klausur'))),
				'anlage2' => array(),
				'raw_text_excerpt' => 'Test',
			);
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

if ($page_file === 'useragents.php') {
	rquery('CREATE TEMPORARY TABLE IF NOT EXISTS `ua_overview` (`browser_name` varchar(100), `browser_version` varchar(50), `os_name` varchar(100), `os_version` varchar(50), `c` int, `year` int, `month` int, `day` int)');
	rquery('INSERT INTO `ua_overview` (`browser_name`, `browser_version`, `os_name`, `os_version`, `c`, `year`, `month`, `day`) VALUES ("Firefox", "120", "Linux", "6", 5, 2026, 10, 31)');
}
if ($page_file === 'api.php') {
	rquery('INSERT IGNORE INTO `api_auth_codes` (`id`, `auth_code`, `email`, `ansprechpartner`, `grund`, `user_id`) VALUES (1, "reqtestcode", "req@example.org", "Req", "ReqGrund", 1)');
	rquery('INSERT IGNORE INTO `api_log` (`auth_code_id`, `time`, `parameter`, `ip`, `api_error_code_id`) VALUES (1, now(), "req=1", "127.0.0.1", NULL)');
	$_GET['id'] = 'reqtestcode';
}
if ($page_file === 'neuerdozent.php') {
	rquery('INSERT IGNORE INTO `institut` (`name`, `start_nr`) VALUES ("ReqTest-Institut-2", 2)');
}
if ($page_file === 'right_issues.php') {
	rquery('INSERT IGNORE INTO `right_issues` (`function`, `user_id`, `date`) VALUES ("ReqTestFunction", 1, now())');
	rquery('INSERT IGNORE INTO `right_issues_pages` (`user_id`, `page_id`, `date`) VALUES (1, 1, now())');
}
if ($page_file === 'pruefungsaemter.php') {
	rquery('INSERT IGNORE INTO `pruefungsamt` (`id`, `name`) VALUES (1, "ReqTest-Pruefungsamt")');
	rquery('INSERT IGNORE INTO `pruefungsamt_nach_studiengang` (`pruefungsamt_id`, `studiengang_id`) VALUES (1, 1)');
}
if ($page_file === 'superdozent.php') {
	rquery('INSERT IGNORE INTO `role` (`id`, `name`, `beschreibung`) VALUES (4, "Superdozent", "ReqTest")');
	rquery('INSERT IGNORE INTO `users` (`id`, `username`, `password_sha256`, `salt`, `dozent_id`, `institut_id`, `accepted_public_data`) VALUES (2, "ReqSuper", "", "req", 1, 1, "0")');
	rquery('INSERT IGNORE INTO `role_to_user` (`role_id`, `user_id`) VALUES (4, 2)');
	rquery('INSERT IGNORE INTO `superdozent` (`user_id`, `dozent_id`) VALUES (2, 1)');
}
if ($page_file === 'rechnungen.php') {
	$vvz_kunde_id = get_kunde_id_by_db_name($GLOBALS['dbname']);
	if ($vvz_kunde_id) {
		rquery('INSERT IGNORE INTO vvz_global.rechnungen (`kunde_id`, `plan_id`, `monat`, `jahr`, `rabatt`, `spezialpreis`) VALUES (' . esc($vvz_kunde_id) . ', 1, 1, 2099, 0, 0)');
	}
}

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
if (getenv('VVZ_PAGE_DEBUG') === '1') {
	fwrite(STDERR, "PAGE_OUTPUT:\n" . substr($output, 0, 2000) . "\n");
}
fwrite(STDOUT, "PAGE_STATUS: " . $status . "\n");
fwrite(STDOUT, "PAGE_OUTPUT_LENGTH: " . strlen($output) . "\n");
if ($status !== 'OK') {
	fwrite(STDERR, $status . "\n");
}
