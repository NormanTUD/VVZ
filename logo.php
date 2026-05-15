<?php
	include_once("config.php");
	include_once("kundenkram.php");
	include_once("functions.php");
	include_once("selftest.php");


	$fn = get_logo_filename();

	header("Content-type: image/svg");
	if($fn == "tudlogo.svg" || $fn == "default_logo.png") {
		readfile($fn);
	} else {
		print $fn;
	}
?>
