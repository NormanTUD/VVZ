<?php
/*
 * Exercise the request dispatcher in functions.php.
 *
 * The dispatcher is top-level code that only runs while functions.php is
 * included. To test it without touching the real database we create a
 * disposable clone (db_vvz_reqtest), run each scenario in its own PHP process,
 * and drop the clone afterwards.
 */

require_once(__DIR__ . '/cli_guard.php');

$GLOBALS['no_selftest'] = 1;
$GLOBALS['no_selftest_force'] = 1;
require_once(dirname(__DIR__) . '/functions.php');

if (!isset($GLOBALS['dbh']) || !is_object($GLOBALS['dbh'])) {
	fwrite(STDERR, "No database connection\n");
	exit(3);
}

$source_db = $GLOBALS['dbname'];
$dbh = $GLOBALS['dbh'];
$clone_db = 'db_vvz_reqtest';

function req_query($dbh, $sql)
{
	try {
		return $dbh->query($sql);
	} catch (\Throwable $e) {
		if (getenv('VVZ_REQUEST_DEBUG') === '1') {
			fwrite(STDERR, "DEBUG query failed: " . $e->getMessage() . "\nSQL: " . substr($sql, 0, 300) . "\n");
		}
		return false;
	}
}

$dbh->query('SET FOREIGN_KEY_CHECKS=0');
req_query($dbh, 'DROP DATABASE IF EXISTS `' . $clone_db . '`');
if (!req_query($dbh, 'CREATE DATABASE `' . $clone_db . '` CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci')) {
	fwrite(STDERR, "Could not create clone database\n");
	exit(3);
}

$tables = array();
$res = $dbh->query("SELECT TABLE_NAME, TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA = '" . $dbh->real_escape_string($source_db) . "'");
while ($res && $row = $res->fetch_row()) {
	$tables[$row[0]] = $row[1];
}

$dbh->select_db($clone_db);
foreach ($tables as $table => $type) {
	if (!in_array($type, array('BASE TABLE', 'SYSTEM VERSIONED'), true)) {
		continue;
	}
	$res = req_query($dbh, 'SHOW CREATE TABLE `' . $source_db . '`.`' . $table . '`');
	$row = $res ? $res->fetch_row() : null;
	if ($row) {
		req_query($dbh, $row[1]);
	}
}

/* Minimal seed so login/session/page navigation works inside the clone. */
$salt = 'reqtestsalt';
$password_hash = hash('sha256', 'test' . $salt);
$seed_queries = array(
	"INSERT IGNORE INTO `institut` (`id`, `name`, `start_nr`) VALUES (1, 'ReqTest-Institut', 1)",
	"INSERT IGNORE INTO `dozent` (`id`, `first_name`, `last_name`, `ausgeschieden`) VALUES (1, 'Admin', 'Istrator', '0')",
	"INSERT IGNORE INTO `role` (`id`, `name`, `beschreibung`) VALUES (1, 'Administrator', 'ReqTest')",
	"INSERT IGNORE INTO `users` (`id`, `username`, `password_sha256`, `salt`, `dozent_id`, `institut_id`, `accepted_public_data`) VALUES (1, 'Admin', '" . $dbh->real_escape_string($password_hash) . "', '" . $dbh->real_escape_string($salt) . "', 1, 1, '0')",
	"INSERT IGNORE INTO `role_to_user` (`role_id`, `user_id`) VALUES (1, 1)",
	"INSERT IGNORE INTO `page` (`id`, `name`, `file`, `show_in_navigation`, `parent`, `show_in_startpage`) VALUES (1, 'Accounts', 'accounts.php', '1', NULL, 1)",
	"INSERT IGNORE INTO `role_to_page` (`role_id`, `page_id`) VALUES (1, 1)",
	"INSERT IGNORE INTO `semester` (`id`, `jahr`, `typ`, `default`) VALUES (1, 2099, 'Sommersemester', '1')",
	"INSERT IGNORE INTO `session_ids` (`id`, `session_id`, `user_id`) VALUES (1, 'reqtest-session', 1)",
	"INSERT IGNORE INTO `language` (`id`, `abkuerzung`, `name`) VALUES (1, 'deu', 'deutsch')",
	"INSERT IGNORE INTO `titel` (`id`, `name`, `abkuerzung`) VALUES (1, 'Doktor', 'Dr.')",
	"INSERT IGNORE INTO `veranstaltungstyp` (`id`, `name`, `abkuerzung`) VALUES (1, 'Vorlesung', 'VL')",
	"INSERT IGNORE INTO `pruefungstyp` (`id`, `name`) VALUES (1, 'Klausur')",
	"INSERT IGNORE INTO `pruefung_zeitraum` (`id`, `name`) VALUES (1, 'Kein Zeitraum')",
	"INSERT IGNORE INTO `bereich` (`id`, `name`) VALUES (1, 'ReqBereich')",
	"INSERT IGNORE INTO `pruefungsnummer_fach` (`id`, `name`) VALUES (1, 'ReqFach')",
	"INSERT IGNORE INTO `pruefungsamt` (`id`, `name`) VALUES (1, 'ReqPruefungsamt')",
	"INSERT IGNORE INTO `gebaeude` (`id`, `abkuerzung`, `name`) VALUES (1, 'REQ', 'ReqGebaeude')",
	"INSERT IGNORE INTO `raum` (`id`, `gebaeude_id`, `raumnummer`) VALUES (1, 1, 'R1')",
	"INSERT IGNORE INTO `studiengang` (`id`, `name`, `institut_id`, `studienordnung`, `order_key`) VALUES (1, 'ReqStudiengang', 1, 'x', 1)",
	"INSERT IGNORE INTO `modul` (`id`, `name`, `studiengang_id`, `beschreibung`, `abkuerzung`) VALUES (1, 'ReqModul', 1, 'b', 'RM')",
	"INSERT IGNORE INTO `veranstaltung` (`id`, `veranstaltungstyp_id`, `name`, `dozent_id`, `institut_id`, `semester_id`) VALUES (1, 1, 'ReqVeranstaltung', 1, 1, 1)",
	"INSERT IGNORE INTO `veranstaltung_metadaten` (`veranstaltung_id`, `wochentag`, `stunde`, `woche`) VALUES (1, 'Mo', '1', 'jede Woche')",
	"INSERT IGNORE INTO `pruefungsnummer` (`id`, `pruefungsnummer`, `modul_id`, `pruefungstyp_id`, `bereich_id`, `modulbezeichnung`, `zeitraum_id`) VALUES (1, 'ReqPN', 1, 1, 1, 'ReqModul', 1)",
	"INSERT IGNORE INTO `faq` (`id`, `frage`, `antwort`, `wie_oft_gestellt`) VALUES (1, 'ReqFrage', 'ReqAntwort', 1)",
	"INSERT IGNORE INTO `function_right` (`id`, `function_name`) VALUES (1, 'ReqFunction')",
	"INSERT IGNORE INTO `api_auth_codes` (`id`, `auth_code`, `email`, `ansprechpartner`, `grund`, `user_id`) VALUES (1, 'reqtestcode', 'req@example.org', 'Req', 'ReqGrund', 1)",
	"INSERT IGNORE INTO `page_info` (`page_id`, `info`) VALUES (1, 'ReqInfo')",
	"INSERT IGNORE INTO `hinweise` (`page_id`, `hinweis`) VALUES (1, 'ReqHinweis')",
);
foreach ($seed_queries as $sql) {
	req_query($dbh, $sql);
}

/* Create views. The cloned definitions point at the source DB; this is fine
 * for read-only views, but login/navigation must see the clone data, so those
 * two are replaced afterwards. */
for ($pass = 0; $pass < 3; $pass++) {
	foreach ($tables as $table => $type) {
		if ($type !== 'VIEW') {
			continue;
		}
		$res = req_query($dbh, 'SHOW CREATE VIEW `' . $source_db . '`.`' . $table . '`');
		$row = $res ? $res->fetch_row() : null;
		if ($row) {
			req_query($dbh, $row[1]);
		}
	}
}
req_query($dbh, 'CREATE OR REPLACE VIEW `view_user_session_id` AS SELECT `s`.`id` AS `session_id_id`, `u`.`id` AS `user_id`, `s`.`session_id` AS `session_id`, `s`.`creation_time` AS `creation_time`, `u`.`username` AS `username`, `u`.`dozent_id` AS `dozent_id`, `u`.`institut_id` AS `institut_id`, `u`.`enabled` AS `enabled`, `u`.`accepted_public_data` AS `accepted_public_data` FROM `users` `u` LEFT JOIN `session_ids` `s` ON `s`.`user_id` = `u`.`id`');
req_query($dbh, 'CREATE OR REPLACE VIEW `view_account_to_role_pages` AS SELECT `p`.`id` AS `page_id`, `p`.`name` AS `name`, `p`.`file` AS `file`, `ru`.`user_id` AS `user_id`, `p`.`show_in_navigation` AS `show_in_navigation`, `p`.`parent` AS `parent` FROM `role_to_user` `ru` JOIN `role_to_page` `rp` ON `rp`.`role_id` = `ru`.`role_id` JOIN `page` `p` ON `p`.`id` = `rp`.`page_id`');
$dbh->query('SET FOREIGN_KEY_CHECKS=1');
if (getenv('VVZ_REQUEST_DEBUG') === '1') {
	$r = req_query($dbh, "SELECT COUNT(*) FROM view_user_session_id WHERE session_id = 'reqtest-session'");
	$row = $r ? $r->fetch_row() : null;
	print "DEBUG view_user_session_id rows: " . ($row ? $row[0] : 'query failed') . "\n";
	$r = req_query($dbh, "SHOW TABLES LIKE 'view_user_session_id'");
	print "DEBUG view exists: " . ($r && $r->num_rows ? 'yes' : 'no') . "\n";
}

$variants = array(
	'baseline', 'login_post', 'sdsg', 'pages_direct', 'pages_die', 'auto_login',
	'updates', 'pages', 'roles', 'users', 'rights', 'creates', 'deletes',
);
$failures = 0;
foreach ($variants as $variant) {
	$command = 'timeout 90 ' . escapeshellarg(PHP_BINARY)
		. ' -d auto_prepend_file=' . escapeshellarg(__DIR__ . '/coverage/bootstrap.php')
		. ' ' . escapeshellarg(__DIR__ . '/request_runner.php')
		. ' ' . escapeshellarg($clone_db) . ' ' . escapeshellarg($variant) . ' 2>&1';
	$lines = array();
	$exit_code = 0;
	exec($command, $lines, $exit_code);
	$status = 'no status';
	foreach ($lines as $line) {
		if (strpos($line, 'REQUEST_STATUS: ') === 0) {
			$status = substr($line, strlen('REQUEST_STATUS: '));
		}
	}
	if ($exit_code === 124 || $exit_code === 2 || $exit_code === 3) {
		$failures++;
		print "FAILED   $variant (exit $exit_code)\n";
	} else {
		print "OK       $variant ($status)\n";
	}
}

req_query($dbh, 'DROP DATABASE IF EXISTS `' . $clone_db . '`');
$dbh->select_db($source_db);

print "\n--- Request dispatcher summary ---\n";
print 'Scenarios: ' . count($variants) . "\n";
print "Failures:  $failures\n";
if ($failures > 0) {
	exit(1);
}
exit(0);
