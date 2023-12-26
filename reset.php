<?php
	$GLOBALS["no_selftest_force"] = 1;
	include_once("functions.php");
	function isCommandLineInterface() {
		return (php_sapi_name() === 'cli');
	}

	if(isCommandLineInterface()) {
		rquery('SET foreign_key_checks = 0');
		$query = "show databases like 'db_vvz_%'";
		$result = rquery($query);
		while ($row = mysqli_fetch_row($result)) {
			print "Dropping '$row[0]'\n";
			rquery("drop database $row[0];");
		}

		try {
			print "Dropping 'startpage'\n";
			rquery("drop database if exists startpage;");
		} catch (\Throwable $e) {
			print $e;
		}

		if(database_exists("vvz_global")) {
			rquery("drop database vvz_global;");
		}
		if(database_exists("vvztud")) {
			rquery("drop database vvztud;");
		}

		rquery('SET foreign_key_checks = 1');
	} else {
		die("Can only be called from the CLI.");
	}
?>
