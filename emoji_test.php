<?php
	$php_start = microtime(true);
	include_once("config.php");
	$page_title = "Vorlesungsverzeichnis ".$GLOBALS['university_name']." | FAQ";
	$filename = 'startseite';
	include("header.php");
?>
	<div id="mainindex" class="mainindex_faq">
<?php
	ob_start();
	system('cat emojis.php | grep "function print" | sed -e "s/.*function //" | sed -e "s/ .*//" | sort | sed -e "s/$/();/"');
	$x = ob_get_clean();
	eval($x);
?>
