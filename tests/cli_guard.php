<?php
/*
 * CLI guard - prevents this test infrastructure from running when accessed
 * via the web (Apache/Nginx/etc). Running tests via the web would:
 *   - expose the database password (printed in some error messages)
 *   - run destructive setup_db.php which creates/truncates tables
 *   - reset /etc/vvzdbpw
 *   - leak stack traces that include file paths and DB credentials
 *
 * Usage: include this from any test entry point or test file:
 *   require_once __DIR__ . '/cli_guard.php';
 *
 * Returns silently if running from CLI. Aborts with HTTP 403 + plain
 * text error otherwise.
 */

if(php_sapi_name() === 'cli' || php_sapi_name() === 'cli-server' || php_sapi_name() === 'phpdbg') {
	return;
}

/* Web access - refuse. */
if(!headers_sent()) {
	header("HTTP/1.1 403 Forbidden");
	header("Content-Type: text/plain; charset=utf-8");
}
print "This script can only be executed from the command line.\n";
exit(1);
