<?php
	$php_start = microtime(true);
	if(file_exists('new_setup')) {
		include('setup.php');
		exit(0);
	}
	$page_title = "Vorlesungsverzeichnis TU Dresden";
	$filename = 'index.php';
	include("header.php");
?>
	<div id="mainindex">
		<a href="index.php" border="0"><img alt="TUD-Logo, Link zur Startseite"  src="tudlogo.svg" width="255" /></a>
		<h2>Rechtliches</h2>
		<p>Diese Software ist lizensiert unter der GPL, ihr Autor ist Norman Koch. Der Quellcode liegt unter <a href="https://github.com/NormanTUD/VVZ">https://github.com/NormanTUD/VVZ</a></p>
<?php
	include("footer.php");
?>
