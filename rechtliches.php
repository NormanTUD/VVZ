<?php
	$php_start = microtime(true);
	if(file_exists('new_setup')) {
		include('setup.php');
		exit(0);
	}
	include_once("config.php");
	$page_title = "Vorlesungsverzeichnis ".$GLOBALS['university_name'];
	$filename = 'index.php';
	include("header.php");
?>
	<div id="mainindex">
		<a href="index.php" border="0"><img alt="TUD-Logo, Link zur Startseite"  src="tudlogo.svg" width="255" /></a>
		<h2>Impressum</h2>
		Es gilt das Impressum der <a href="<?php print $GLOBALS['impressum_university_page']; ?>"><?php print $GLOBALS['university_name']; ?></a>.
		<h2>Rechtliches</h2>
		<p>Die Rechte für diese Software liegen bei Norman Koch.
		Die Funktionen zum Exportieren
		in Excel-Tabellen wurden <a href="https://github.com/PHPOffice/PHPExcel">diesem Projekt</a>
		entnommen und stehen unter der
		<a href="https://www.gnu.org/licenses/lgpl-3.0.en.html">LGPL-Lizenz</a>,
		die sowohl private, kommerzielle als auch öffentliche Nutzung erlaubt.
		JQuery steht unter der <a href="https://opensource.org/licenses/MIT">MIT-Lizenz</a>.
		Im Rahmen der Debug-Funktionalität wird das Modul <a href="https://github.com/jdorn/sql-formatter">SQL-Formatter</a> benutzt.
		Dieser steht unter der MIT-Lizenz und darf damit frei verwendet werden.
		<a href="https://github.com/bietiekay/PHPDDate">PHPDDate</a> steht
		unter der Creative-Commons-Lizenz. <a href="https://github.com/chartjs/Chart.js">Chart.js</a> steht
		unter der MIT-Lizenz.<a> <a href="https://github.com/chrisboulton/php-diff">PHP-Diff</a> steht
		unter der BSD-Lizenz. Der Benutzer erklärt sich durch die Benutzung einverstanden, dass auf seinem
		Rechner Cookies gespeichert werden, in denen unter Anderem eine Session-ID und der Benutzername, mit
		dem man angemeldet ist, gespeichert werden. Diese Daten werden nicht zur Benutzeridentifikation benutzt,
		außer bei API-Aufrufen mit gültigem Schlüssel.</p>
<?php
	include("footer.php");
?>
