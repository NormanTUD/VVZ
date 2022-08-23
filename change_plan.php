<?php
	$php_start = microtime(true);
	include_once("config.php");
	$page_title = "Vorlesungsverzeichnis ".$GLOBALS['university_name']." | FAQ";
	$filename = 'startseite';
	include("header.php");
	include_once("startseite_functions.php");
?>
	<div id="mainindex" style="text-align: left!important">
		<a href="startseite" border="0"><?php print_uni_logo(); ?> </a>
<?php
	logged_in_stuff();

	include("footer.php");
?>
