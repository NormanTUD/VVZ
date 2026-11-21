<?php
	require_once(__DIR__.'/cli_guard.php');

	$GLOBALS['no_selftest'] = 1;
	$GLOBALS['no_selftest_force'] = 1;

	include_once(dirname(__DIR__).'/testing.php');

	if(file_exists(__DIR__.'/setup_db.php')) {
		include_once(__DIR__.'/setup_db.php');
	}

	include_once(__DIR__.'/test_usability_helpers.php');
	include_once(__DIR__.'/test_usability_features.php');
?>
