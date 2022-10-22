<?php
	include_once("config.php");
	include_once("kundenkram.php");
	include_once("functions.php");
	include_once("selftest.php");


	$fn = get_logo_filename();

	if($fn == "tudlogo.svg") {
		header('Content-type: image/svg+xml');
		readfile($fn);
	} else if($fn == "default_logo.png") {
		header("Content-type: image/png");
		readfile($fn);
	} else {
		header("Content-type: image/png");
		print $fn;
	}
?>
