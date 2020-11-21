<?php
	$php_start = microtime(true);
	if(file_exists('new_setup')) {
		include('setup.php');
		exit(0);
	}
	include_once("config.php");
	$page_title = "Vorlesungsverzeichnis ".$GLOBALS['university_name']." | Impressum";
	$filename = 'index.php';
	include("header.php");
?>
	<div id="mainindex">
		<a href="index.php" border="0"><img alt="TUD-Logo, Link zur Startseite"  src="tudlogo.svg" width="255" /></a>
		<h2>Impressum</h2>
<p>
Es gilt das <a href="<?php print $GLOBALS['impressum_university_page']; ?>" target="_blank">Impressum der <?php print $GLOBALS['university_name']; ?></a> 
mit folgenden Änderungen:
</p>

<p>
Ansprechpartner: <br><?php print $GLOBALS['ansprechpartner']; ?><br>
Betreiber:<br>
<?php print $GLOBALS['university_full_name']; ?><br>
<?php print $GLOBALS['institut']; ?><br>
<?php print $GLOBALS['ansprechpartner']; ?><br>
<?php print $GLOBALS['university_plz_city']; ?><br>
Tel.: <?php print $GLOBALS['ansprechpartner_tel_nr']; ?><br>
E-Mail: <?php print $GLOBALS['ansprechpartner_email']; ?><br>
</p>

<p>
Konzeption, techn. Realisierung :<br>
Norman Koch<br>
E-Mail: <i><a href="kontakt.php">über das Kontaktformular</a></i><br>
</p>
<?php
	include("footer.php");
?>
