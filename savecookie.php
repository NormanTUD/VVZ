<?php
	include_once('functions.php');
	if(get_get('geplante_pruefungsleistungen')) {
		$gp = get_get('geplante_pruefungsleistungen');
		if(preg_match('/^\[[,"\'\d]*\]$/', $gp)) {
			setcookie('geplante_pruefungsleistungen', $gp, time() + 86400 * 365, "/");
		}
	}

	if(get_get('absolviertepruefungsleistungen')) {
		$gp = get_get('absolviertepruefungsleistungen');
		if(preg_match('/^\[[,"\'\d]*\]$/', $gp)) {
			setcookie('absolviertepruefungsleistungen', $gp, time() + 86400 * 365, "/");
		}
	}

	include("header.php");
?>
	<div id="mainindex">
		<a href="index.php?semester=<?php print isset($this_semester[0]) ? htmlentities($this_semester[0]) : ''; ?>&institut=<?php print isset($this_institut) ? htmlentities($this_institut) : ''; ?>" border="0"><img alt="TUD-Logo, Link zur Startseite"  src="tudlogo.svg" width="255" /></a>
<?php
		if(isset($GLOBALS['logged_in_user_id'])) {
			$dozent_name = htmlentities(get_dozent_name($GLOBALS['logged_in_data'][2]));
			if(!preg_match('/\w{2,}/', $dozent_name)) {
				$dozent_name = htmlentities($GLOBALS['logged_in_data'][1]).' <span class="class_red">!!! Ihr Account ist mit keinem Dozenten verknüpft! !!!</span>';
			}
			if(!$GLOBALS['user_role_id'][0]) {
				$dozent_name = htmlentities($GLOBALS['logged_in_data'][1]).' <span class="class_red">!!! Ihr Account hat keine ihm zugeordnete Rolle! !!!</span>';
			}
?>
			<br />Willkommen, <?php print $dozent_name; ?>! &mdash; <a style="color: red; font-size: 20" href="logout.php">Abmelden</a>
<?php
		}
?>
		<header style="margin-bottom:1rem;">
			<div class="row">
				<div class="medium-2 columns" style="margin-top: 1rem; margin-bottom: 1rem;">
				<div id="backbutton" style="visibility: hidden"><a onclick="history.back(-1)"><i class="fa fa-long-arrow-left"></i>&nbsp; zurück</a></div>
				</div>
				<div class="medium-8 columns">
<?php
					if($GLOBALS['show_comic_sans']) {
?>
						<h2 class="text-center rainbow">Vorlesungsverzeichnis</h2>
						&mdash; Frohen 1. April! &mdash;
<?php
					} else {
?>
						<h2 class="text-center">Vorlesungsverzeichnis</h2>
<?php
					}

?>
					<h3 class="text-center"><?php print isset($this_institut) ? htmlentities(get_institut_name($this_institut)) : ''; ?></h3>
					<h5 class="text-center"><?php print add_next_year_to_wintersemester($this_semester[1], $this_semester[2]); ?></h5>
					<p class="text-center"><?php print htmlentities(get_studiengang_name(get_get('studiengang'))); ?></p>
<?php
					$vvz_start_message = get_vvz_start_message($this_institut);
					if($vvz_start_message) {
						print "Hinweis: <span style='color: orange; font-style: italic;'>".htmlentities($vvz_start_message)."</span>\n";
					}
?>
				</div>
				<div class="medium-2 columns"></div>
			</div>
		</header>

Speichern Sie sich den Link zu dieser Seite (oben in der Adressleiste), um jederzeit
Ihre Cookies bezüglich der Prüfungen wiederherstellen zu können.
<?php
	include("footer.php");
?>
