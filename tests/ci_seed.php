<?php
/*
 * CI database bootstrap.
 *
 * Creates/seeds the VVZ schema and then imports the default demo data from
 * initialdatensatz.php. This is invoked from the GitHub Actions workflow
 * only; local test runs use tests/setup_db.php as before.
 */

$GLOBALS['no_selftest'] = 1;
$GLOBALS['no_selftest_force'] = 1;
require_once(dirname(__DIR__) . '/functions.php');
require_once(dirname(__DIR__) . '/selftest.php');

$GLOBALS['no_selftest'] = 0;
selftest_startpage();
selftest();

$dbname = $GLOBALS['dbname'];
rquery('UPDATE vvz_global.kundendaten SET plan_id = 1 WHERE dbname = ' . esc($dbname));
// initialdatensatz references building GER with id 122. Remove the default
// building with the same abbreviation first so the event/room rows can be
// inserted.
rquery("DELETE FROM `raum` WHERE `gebaeude_id` IN (SELECT `id` FROM `gebaeude` WHERE `abkuerzung` = 'GER' AND `id` <> 122)");
rquery("DELETE FROM `gebaeude` WHERE `abkuerzung` = 'GER' AND `id` <> 122");

$GLOBALS['logged_in_user_id'] = 1;
$GLOBALS['user_role_id'] = 1;
$initial_source = file_get_contents(dirname(__DIR__) . '/initialdatensatz.php');
$initial_source = preg_replace('/if\s*\(\s*is_demo\(\)\s*&&\s*user_is_admin\([^)]*\)\s*\)\s*\{/', 'if(true) {', $initial_source, 1);
$initial_tmp = tempnam(sys_get_temp_dir(), 'vvz-initial-') . '.php';
file_put_contents($initial_tmp, $initial_source);
include($initial_tmp);
@unlink($initial_tmp);

echo 'Seeded initialdatensatz for ' . $dbname . "\n";
