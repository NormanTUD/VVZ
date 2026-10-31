<?php
/*
 * Smoke-test all administration subpages.
 *
 * Each page is executed in its own PHP process by tests/page_runner.php.
 * This exercises the real page code (and lets Xdebug collect line coverage)
 * without one page being able to abort the whole run via exit()/die().
 *
 * Usage:
 *   php tests/run_page_tests.php
 *   XDEBUG_MODE=coverage VVZ_COVERAGE_DIR=/tmp/cov \
 *     php -d auto_prepend_file=tests/coverage/bootstrap.php tests/run_page_tests.php
 */

require_once(__DIR__ . '/cli_guard.php');

$root = realpath(__DIR__ . '/..');
$files = glob($root . '/pages/*.php');
sort($files);

$timeout_seconds = (int) (getenv('VVZ_PAGE_TEST_TIMEOUT') ?: 60);
$ok = 0;
$exceptions = 0;
$failures = 0;

print "Running " . count($files) . " page smoke tests\n";
foreach ($files as $file) {
	$relative = substr($file, strlen($root) + 1);
	$variants = array('default', 'rich');
	if (basename($file) === 'studienordnung_import.php') {
		$variants = array('default', 'rich', 'stage:detail', 'stage:download', 'stage:analyze', 'stage:debug_text', 'stage:cross_validate', 'stage:preview', 'stage:commit', 'stage:commit_v2', 'stage:upload');
	}
	if (basename($file) === 'merges.php') {
		$variants[] = 'table:language';
	}
	foreach ($variants as $variant) {
		$command = 'timeout ' . $timeout_seconds . ' ' . escapeshellarg(PHP_BINARY)
			. ' -d auto_prepend_file=' . escapeshellarg(__DIR__ . '/coverage/bootstrap.php')
			. ' ' . escapeshellarg(__DIR__ . '/page_runner.php')
			. ' ' . escapeshellarg($file) . ' ' . escapeshellarg($variant) . ' 2>&1';
		$lines = array();
		$exit_code = 0;
		exec($command, $lines, $exit_code);

		$status = null;
		foreach ($lines as $line) {
			if (strpos($line, 'PAGE_STATUS: ') === 0) {
				$status = substr($line, strlen('PAGE_STATUS: '));
			}
		}

		$label = $relative . ' [' . $variant . ']';
		if ($exit_code === 124) {
			$failures++;
			print "TIMEOUT  $label\n";
		} else if ($exit_code === 2 || $exit_code === 3) {
			$failures++;
			$last = count($lines) ? end($lines) : 'no output';
			print "FAILED   $label (exit $exit_code, " . trim($last) . ")\n";
		} else if ($status === 'OK') {
			$ok++;
			print "OK       $label\n";
		} else if ($status !== null && strpos($status, 'EXCEPTION: ') === 0) {
			$exceptions++;
			print "EXCEPTION $label (" . $status . ")\n";
		} else {
			$ok++;
			print "OK       $label (page exited)\n";
		}
	}
}

print "\n--- Page smoke summary ---\n";
print "OK:         $ok\n";
print "Exceptions: $exceptions\n";
print "Failures:   $failures\n";

if ($failures > 0) {
	exit(1);
}
exit(0);
