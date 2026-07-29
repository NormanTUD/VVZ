<?php
/*
 * Master test runner - includes all test files.
 * Usage: php run_all_tests.php (after testing.php has been included)
 *
 * This file is meant to be included from testsuite.php after testing.php
 * has been loaded. Each included test file contains assertions only.
 */

if(!function_exists("is_equal")) {
	die("ERROR: testing framework not loaded. Include testing.php first.\n");
}

$test_dir = __DIR__;
$test_files = array(
	"test_framework.php",
	"test_framework_deep.php",
	"test_string_helpers.php",
	"test_date_functions.php",
	"test_array_functions.php",
	"test_numeric_functions.php",
	"test_day_functions.php",
	"test_html_functions.php",
	"test_security.php",
	"test_random_crypto.php",
	"test_input_output.php",
	"test_validation.php",
	"test_sort_functions.php",
	"test_db_helpers.php",
	"test_kunde_functions.php",
	"test_misc_functions.php",
	"test_language_functions.php",
	"test_edge_cases.php",
);

foreach ($test_files as $file) {
	$path = $test_dir . "/" . $file;
	if(file_exists($path)) {
		include_once($path);
	} else {
		echo "WARNING: test file not found: $file\n";
	}
}
