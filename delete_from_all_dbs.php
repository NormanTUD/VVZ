<?php
	$GLOBALS["no_selftest_force"] = 1;
	include("functions.php");
	function isCommandLineInterface() {
		return (php_sapi_name() === 'cli');
	}

	if(isCommandLineInterface()) {
		rquery('SET foreign_key_checks = 0');
		$query = "show tables from vvztud";
		$result = rquery($query);
		while ($row = mysqli_fetch_row($result)) {
			if(!preg_match("/^view_/", $row[0]) && $row[0] != "ua_overview") {
				print "Deleting all from '$row[0]'\n";
				try {
					rquery("delete from $row[0];");
				} catch (\Throwable $e) {
					print $e;
				}
			}
		}
		rquery('SET foreign_key_checks = 1');
	} else {
		die("Can only be called from the CLI.");
	}
?>
