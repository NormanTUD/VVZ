<?php
	$included_files = get_included_files();
	$included_files = array_map('basename', $included_files);

	if(!in_array('functions.php', $included_files)) {
		include_once('../functions.php');
	}

	if(check_page_rights(get_page_id_by_filename(basename(__FILE__)))) { // Wichtig, damit Niemand ohne Anmeldung etwas ändern kann
?>
	<div>
		<?php print get_seitentext(); ?>
<?php
		include_once('hinweise.php');

		$pruefungsaemter = create_pruefungsamt_array();
		$institute = create_institute_array();
		$dozenten = create_dozenten_array();
		$semester = create_semester_array_short();

		$chosen_institut = (get_get('institut') ? get_get('institut') : $institute[1][0]);
		$studiengaenge = create_studiengang_array_by_institut_id($chosen_institut);

		$chosen_semester = (get_get('semester') ? get_get('semester') : get_this_semester()[0]);
		$chosen_dozent = (get_get('dozent') ? get_get('dozent') : null);
		$chosen_studiengang = (get_get('studiengang') ? get_get('studiengang') : null);
		$chosen_pruefungsamt = (get_get('pruefungsamt') ? get_get('pruefungsamt') : null);
?>
		<form method="get">
			<input type="hidden" name="page" value="<?php print $GLOBALS['this_page_number']; ?>" />
			<input type="hidden" name="studiengang" value="<?php print htmlentities(get_get('studiengang')); ?>" />
			Semester: <?php create_select($semester, $chosen_semester, 'semester'); ?><br>
			<?php if(count($pruefungsaemter) > 1) { print "Prüfungsamt"; create_select($pruefungsaemter, $chosen_pruefungsamt, 'pruefungsamt', 1); print "</br>"; } ?>
			Einzelne Prüfungsnummern (mit Komma getrennt): <input name="einzelne_pns" value="<?php print(htmlentities(get_get("einzelne_pns"))); ?>" /><br>
			Nur Einträge mit Veränderungen nach diesem Datum anzeigen (YYYY-MM-DD HH:MM:SS): <input name="last_changed_date" value="<?php print(htmlentities(get_get("last_changed_date"))); ?>" /><br>
			<input type="submit" value="Nur Veranstaltungen anzeigen, die den Suchkriterien entsprechen" />
		</form>
<?php
		$export_html = export_crazy_ethik_export_format_2($chosen_pruefungsamt, $chosen_semester, get_get("einzelne_pns"), 1, get_get("last_changed_date"));
		print $export_html;
?>
		<form method="get" action="pruefungsleistungen_export_2.php">
			<input type="hidden" name="semester" value="<?php print htmlentities($chosen_semester); ?>" />
			<input type="hidden" name="pruefungsamt" value="<?php print htmlentities($chosen_pruefungsamt); ?>" />
			<input type="hidden" name="einzelne_pns" value="<?php print htmlentities(get_get("einzelne_pns")); ?>" />
			<input type="hidden" name="last_changed_date" value="<?php print htmlentities(get_get("last_changed_date")); ?>" />
<?php
			if($export_html) {
?>
				<input type="submit" value="Diese Liste als Excel-Datei downloaden" />
<?php
			} else {
				print "Unter den gewählten Einstellungen sind keine Veranstaltungen auffindbar.";
			}
?>
		</form>
	</div>
<?php
	}
?>
