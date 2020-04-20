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
		Es gilt das Impressum der <a href="https://tu-dresden.de/impressum">TU Dresden</a>.
		<h2>Rechtliches</h2>
		<p>Die Rechte für diese Software liegen bei Norman Koch.

			Allein die philosophische Fakultät der Technischen
			Universität Dresden hat eine kostenlose Nutzungsberechtigung.
			Nutzung weiterer Institutionen muss mit Norman Koch abgesprochen und
			seperat lizensiert werden. Die Funktionen zum Exportieren
			in Excel-Tabellen wurden <a href="https://github.com/PHPOffice/PHPExcel">diesem Projekt</a>
			entnommen und stehen unter der
			<a href="https://www.gnu.org/licenses/lgpl-3.0.en.html">LGPL-Lizenz</a>,
			die sowohl private, kommerzielle als auch öffentliche Nutzung erlaubt.
			JQuery steht unter der <a href="https://opensource.org/licenses/MIT">MIT-Lizenz</a>.
			Im Rahmen der Debug-Funktionalität wird das Modul <a href="https://github.com/jdorn/sql-formatter">SQL-Formatter</a> benutzt.
			Dieser steht unter der MIT-Lizenz und darf damit frei verwendet werden.
			<a href="http://www.mpdf1.com/mpdf/index.php">mPDF</a> steht
			unter der GPL-Lizenz und kann somit frei benutzt werden.
			<a href="https://github.com/bietiekay/PHPDDate">PHPDDate</a> steht
			unter der Creative-Commons-Lizenz. <a href="https://github.com/chartjs/Chart.js">Chart.js</a> steht
			unter der MIT-Lizenz.<a> <a href="https://github.com/chrisboulton/php-diff">PHP-Diff</a> steht
			unter der BSD-Lizenz. Der Benutzer erklärt sich durch die Benutzung einverstanden, dass auf seinem
			Rechner Cookies gespeichert werden, in denen unter Anderem eine Session-ID und der Benutzername, mit
			dem man angemeldet ist, gespeichert werden. Diese Daten werden nicht zur Benutzeridentifikation benutzt,
			außer bei API-Aufrufen mit gültigem Schlüssel.</p>
		<h2>Lizensierung</h2>
		Allein das philosophische Institut der technischen Universität Dresden
		hat eine kostenfreie Lizenz für dieses Vorlesungsverzeichnis und die
		dazugehörige Verwaltungssoftware. Für die Benutzung an anderen Instituten
		und Universitäten müssen seperate Lizenzen erworben werden. Für das
		Erwerben einer Lizenz kontaktieren uns Sie bitte über <a href="kontakt.php">unser Kontaktformular</a>.
		Zur Lizenz gehört ein technischer Support und die Möglichkeit,
		Funktionswünsche &mdash; nach Absprache &mdash; einbauen zu lassen.
		Die tatsächliche Umsetzung von Wünschen ist jedoch nicht verpflichtend
		für den Lizenzgeber. Die Lizenz beinhaltet <b>keine</b> Initialdatensatzfüllung,
		d.&nbsp;h. alle Datensätze müssen vom Vertragsnehmer eingegeben werden.
		Sowohl für inhaltliche als auch für Softwarefehler und die daraus
		resultierenden Schäden übernimmt der Vertragsnehmer die volle Verantwortung.
		Eigenmächtige Änderungen am Quellcode sind für den Vertragsnehmer
		nicht erlaubt. Jede Änderung benötigt vonseiten des Vertragsnehmers
		benötigt eine schriftliche Bestätigung. Ausgenommen hiervon sind
		kritische Sicherheitslücken. Jede gefundene Sicherheitslücke muss
		unverzüglich angezeigt werden und, sofern sie bereits geschlossen wurde,
		muss der Patch zum Schließen an den Autor gesendet werden.
<?php
	include("footer.php");
?>
