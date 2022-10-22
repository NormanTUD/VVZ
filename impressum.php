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
		<h2>Impressum</h2>
<p>
Es gilt das <a href="<?php print $GLOBALS['impressum_university_page']; ?>" target="_blank">Impressum der <?php print $GLOBALS['university_name']; ?></a> 
mit folgenden Änderungen:
</p>

<p>
Ansprechpartner: <br><?php print get_kunde_name(); ?><br>
Betreiber:<br>
<?php print get_university_name(); ?><br>
<?php print get_kunde_plz(); ?> <?php print get_kunde_ort(); ?><br />
<?php
	$email = get_kunde_email();
	if($email) {
?>

	E-Mail: <?php print get_kunde_email(); ?><br>
<?php
	}
?>
</p>

<p>
Konzeption, techn. Realisierung:<br>
Norman Koch<br>
E-Mail: <i><a href="kontakt.php">über das Kontaktformular</a></i><br>
</p>
<?php
	include("footer.php");
?>
