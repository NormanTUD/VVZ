<?php
	include("functions.php");
	function isCommandLineInterface() {
		return (php_sapi_name() === 'cli');
	}

	if(isCommandLineInterface()) {
		$query = "show databases like 'db_vvz_%'";
		$result = rquery($query);
		while ($row = mysqli_fetch_row($result)) {
			print "Dropping '$row[0]'\n";
			rquery("drop database $row[0];");
		}

		try {
			print "Dropping 'startpage'\n";
			rquery("drop database startpage;");
		} catch (\Throwable $e) {
			print $e;
		}
		rquery("drop database vvz_global;");
	} else {
		die("Can only be called from the CLI.");
	}
?>
