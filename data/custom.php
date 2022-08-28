<?php
	include_once("../config.php");
	include_once("../kundenkram.php");
	include_once("../functions.php");


	header("Content-type: text/css");

	$sql = "select classname, property, val from customizations order by classname, property, val";
	$res = rquery($sql);

	$last_classname = "";
	while ($row = mysqli_fetch_row($res)) {
		$classname = $row[0];
		$prop = $row[1];
		$val = $row[2];

		if($last_classname != $classname) {
			if($last_classname != "") {
				print "}\n";
			}
			$last_classname = $classname;
			print "$classname {\n";
		}
		print "\t$prop: $val;\n";
	}

	if($last_classname != "") {
		print "}\n";
	}
?>
