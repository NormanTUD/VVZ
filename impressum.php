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
		<h2>Impressum</h2>
<p>
Es gilt das <a href="https://tu-dresden.de/impressum" target="_blank">Impressum der TU Dresden</a> 
mit folgenden Änderungen:
</p>

<p>
Ansprechpartner:<br> Norbert Engemaier<br>
Betreiber:<br>
Technische Universit&auml;t Dresden<br>
Institut für Philosophie<br>
Norbert Engemaier<br>
01062 Dresden<br>
Tel.: +49 351 463-32890<br>
E-Mail: norbert.engemaier@tu-dresden.de<br>
</p>

<p>
Konzeption, techn. Realisierung :<br>
Norman Koch<br>
E-Mail: <i><a href="kontakt.php">über das Kontaktformular</a></i><br>
</p>
<?php
	include("footer.php");
?>
