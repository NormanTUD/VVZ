<?php
/*
 * Test setup: Ensure all required DB tables exist.
 *
 * If VVZ has never been installed on this system (or the database
 * password file /etc/vvzdbpw was just created and no tables exist
 * yet), this script creates the full schema before any test runs.
 *
 * It does so by calling the same selftest functions that production
 * uses when a new installation is bootstrapped:
 *   - selftest_startpage()  -> creates the vvz_global database
 *                             and its tables (plan, kundendaten,
 *                             users, role, page, ...)
 *   - selftest()            -> creates the per-customer database
 *                             (defaulting to "startpage" for tests)
 *                             and all its tables and views, plus
 *                             the initial seed data (plans, admin
 *                             user, roles, institute, ...)
 *
 * After running this file, the database should be in the same state
 * as a freshly installed VVZ, and the test suite can run without
 * any manual setup steps.
 *
 * Usage: include this file from testsuite.php *after* testing.php
 * has been loaded (so the DB connection exists) but *before* any
 * tests are executed.
 */

if(!isset($GLOBALS['dbh']) || !is_object($GLOBALS['dbh'])) {
	// No DB connection - nothing to set up.
	return;
}

if(!function_exists("table_exists")) {
	// testing.php -> functions.php -> mysql.php should have loaded this,
	// but be defensive in case someone includes this file in isolation.
	return;
}

$dbname = isset($GLOBALS["dbname"]) && $GLOBALS["dbname"] ? $GLOBALS["dbname"] : "startpage";

/* ---------- 1. vvz_global tables ---------- */

$vvz_global_essential = array("plan", "kundendaten", "users", "role", "page");

$missing_vvz_global = array();
foreach($vvz_global_essential as $t) {
	try {
		if(!table_exists("vvz_global", $t)) {
			$missing_vvz_global[] = $t;
		}
	} catch(\Throwable $e) {
		// information_schema might not be queryable yet - that's fine,
		// just assume the table is missing.
		$missing_vvz_global[] = $t;
	}
}

if(!empty($missing_vvz_global)) {
	print "\n";
	print "[DB Setup] vvz_global is missing tables: " . implode(", ", $missing_vvz_global) . "\n";
	print "[DB Setup] Running selftest_startpage() to create them...\n";

	if(function_exists("selftest_startpage")) {
		try {
			selftest_startpage();
			print "[DB Setup] vvz_global tables created successfully.\n";
		} catch(\Throwable $e) {
			print "[DB Setup] ERROR creating vvz_global tables: " . $e->getMessage() . "\n";
		}
	} else {
		print "[DB Setup] ERROR: selftest_startpage() is not available.\n";
	}
}

/* ---------- 2. customer DB tables ---------- */

/* selftest.php is what populates $GLOBALS["databases"] with the
 * per-customer table schema. Include it if it hasn't been loaded yet. */
if(!isset($GLOBALS["databases"]) || !is_array($GLOBALS["databases"])) {
	$selftest_file = __DIR__ . "/../selftest.php";
	if(file_exists($selftest_file)) {
		// Suppress any output from include_once
		@include_once($selftest_file);
	}
}

$missing_customer = array();
if(isset($GLOBALS["databases"]) && is_array($GLOBALS["databases"])) {
	// Probe just a handful of essential tables - we don't need to know
	// about all of them, just whether the DB looks initialised.
	$probe_tables = array("instance_config", "veranstaltungstyp", "dozent", "users", "role", "page", "institut");
	foreach($probe_tables as $t) {
		if(!array_key_exists($t, $GLOBALS["databases"])) {
			continue;
		}
		try {
			if(!table_exists($dbname, $t)) {
				$missing_customer[] = $t;
			}
		} catch(\Throwable $e) {
			$missing_customer[] = $t;
		}
	}
}

if(!empty($missing_customer)) {
	print "\n";
	print "[DB Setup] Customer DB '" . $dbname . "' is missing tables: " . implode(", ", $missing_customer) . "\n";
	print "[DB Setup] Running selftest() to create them...\n";

	if(function_exists("selftest")) {
		try {
			selftest();
			print "[DB Setup] Customer DB tables created successfully.\n";
		} catch(\Throwable $e) {
			print "[DB Setup] ERROR creating customer DB tables: " . $e->getMessage() . "\n";
		}
	} else {
		print "[DB Setup] ERROR: selftest() is not available.\n";
	}
} else if(isset($GLOBALS["databases"])) {
	print "[DB Setup] Customer DB '" . $dbname . "' is fully initialised.\n";
}

/* ---------- 3. seed data sanity check ---------- */

/* selftest() should have inserted the standard plan rows. If they
 * are missing for some reason (e.g. partial install, manual SQL),
 * re-insert them here. Using "insert ignore" is safe - it won't
 * duplicate rows that are already there. */
if(isset($GLOBALS["dbh"]) && function_exists("rquery") && function_exists("esc")) {
	try {
		$plan_rows = array(
			array("Demo", 0, 0),
			array("Basic Faculty", 50, 500),
			array("Basic University", 500, 3000),
			array("Pro Faculty", 70, 700),
			array("Pro University", 350, 4000),
			array("Superadmin", 0, 0),
		);
		foreach($plan_rows as $row) {
			@rquery("insert ignore into vvz_global.plan (name, monatliche_zahlung, jaehrliche_zahlung) VALUES (" . esc($row[0]) . ", " . (int)$row[1] . ", " . (int)$row[2] . ")");
		}
	} catch(\Throwable $e) {
		// best-effort
	}
}
