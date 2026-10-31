<?php
/*
 * Coverage bootstrap for the VVZ test suite.
 *
 * This file is meant to be used as PHP's `auto_prepend_file` together with
 * Xdebug's coverage mode:
 *
 *     XDEBUG_MODE=coverage VVZ_COVERAGE_DIR=/tmp/vvz-coverage \
 *       php -d auto_prepend_file=tests/coverage/bootstrap.php testsuite.php
 *
 * It starts code coverage before the real script runs and writes the raw
 * Xdebug data to a uniquely named JSON file when the process shuts down.
 * `tests/coverage/report.php` later merges all of those files into the
 * human-readable report that is posted to the GitHub Actions summary.
 *
 * Nothing here changes application behaviour. If Xdebug is not loaded (or
 * coverage is not enabled), the bootstrap is a no-op.
 */

$vvz_coverage_dir = getenv('VVZ_COVERAGE_DIR');
if ($vvz_coverage_dir && extension_loaded('xdebug') && function_exists('xdebug_start_code_coverage')) {
	$vvz_coverage_flags = 0;
	if (defined('XDEBUG_CC_UNUSED')) {
		$vvz_coverage_flags |= XDEBUG_CC_UNUSED;
	}
	if (defined('XDEBUG_CC_DEAD_CODE')) {
		$vvz_coverage_flags |= XDEBUG_CC_DEAD_CODE;
	}

	if (!is_dir($vvz_coverage_dir)) {
		@mkdir($vvz_coverage_dir, 0777, true);
	}

	xdebug_start_code_coverage($vvz_coverage_flags);

	register_shutdown_function(function () use ($vvz_coverage_dir) {
		if (!function_exists('xdebug_get_code_coverage')) {
			return;
		}
		$data = xdebug_get_code_coverage();
		$suffix = getmypid() . '-' . bin2hex(random_bytes(6));
		$file = rtrim($vvz_coverage_dir, '/') . '/coverage-' . $suffix . '.json';
		@file_put_contents($file, json_encode($data));
	});
}
