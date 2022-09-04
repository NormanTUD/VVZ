<?php
	$php_start = microtime(true);
	include_once("config.php");
	$page_title = "Vorlesungsverzeichnis ".$GLOBALS['university_name']." | Impressum";
	$filename = 'startseite';
	include("header.php");

?>
	<div id="mainindex">
		<a href="startseite" border="0"><?php print_uni_logo(); ?></a><br>
<?php
		print get_demo_expiry_time();
?>

<h1>Lizenzbedingungen (Kurzversion)</h1>

<p>Die volle Version bekommen Sie <a href="license.php">hier</a>.</p>

<p>Die Kurzversion ist: wir versuchen zuvorkommend zu sein. Bei Problemen, auch bei Zahlungsproblemen, können Sie sich melden und wir sind kulant.</p>

<p>Sie können jederzeit formlos kündigen und es folgen keine Konsequenzen. Sie können jederzeit zurückkehren, wenn Sie vorher ein Datenbank-Backup gemacht haben (wovon wir Sie dann in Kenntnis setzen), als wäre nichts gewesen. Wir löschen wir Ihre Daten nach bis zu 48 Stunden.</p>

<p>Wir garantieren keine 100%ige Uptime. Die Software wird dauerhaft weiterentwickelt ("<a href="https://de.wikipedia.org/wiki/Rolling_Release">Rolling Release</a>"), daher kann es zu kurzen Downtimes kommen. Wir halten sie aber so gering wie möglich.</p>

<p>Wir arbeiten an einer stetigen Erweiterung unserer Schulungsvideos, so dass alle Themenbereiche von einem Video abgedeckt sind.</p>

<p>Wir verkaufen Ihre Daten nicht. Und wir schalten keine Werbung. Wir binden auch keine Tracking-Cookies ein oder verfolgen Nutzer. Alle benutzten Cookies sind technisch notwendig.</p>

<p>Und natürlich verpflichten Sie sich, nichts Illegales oder gegen das Urheberrecht verstoßendes hochzuladen. Sie sind dafür verantwortlich, dass Sie und ihre Dozenten (und Sonstige, die befugt sind, auf die Software zuzugreifen) sich daran halten und haften voll für die Konsequenzen der Nichteinhaltung..</p>
<?php
	include("footer.php");
?>
