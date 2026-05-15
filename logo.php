<?php
	include_once("config.php");
	include_once("kundenkram.php");
	include_once("functions.php");
	include_once("selftest.php");


	$fn = get_logo_filename();

	// Check if the specific TUD logo is requested and if the SVG version exists
	if ($fn == "tudlogo.png" && file_exists("tudlogo.svg")) {
		header("Content-type: image/svg+xml");
		readfile("tudlogo.svg");
	} 
	// Fallback for the PNG versions
	elseif ($fn == "tudlogo.png" || $fn == "default_logo.png") {
		header("Content-type: image/png");
		readfile($fn);
	} 
	// Default behavior for other filenames
	else {
		header("Content-type: text/plain");
		print $fn;
	}
?>
