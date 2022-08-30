<?php
	include_once("config.php");
	include_once("kundenkram.php");
	include_once("functions.php");
	include_once("selftest.php");

	header("Content-type: image/png");

	$id = get_kunde_id_by_db_name($GLOBALS["dbname"]);
	if(get_get("kunde_id")) {
		$id = get_get("kunde_id");
	}

	if($id) {
		$query = "select img from vvz_global.logos where kunde_id = ".esc($id);
		$result = get_single_row_from_query($query);

		if($result) {
			print $result;
		} else {
			readfile("default_logo.png");
		}
	} else {
		readfile("default_logo.png");
	}
?>
