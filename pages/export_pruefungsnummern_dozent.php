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

		$chosen_institut = null;

		try {
			$chosen_institut = (get_get('institut') ? get_get('institut') : $institute[key($institute)][0]);
		} catch (\Throwable $e) {
			// Wenn kein Institut definiert
		}

		if(is_null($chosen_institut)) {
			print "Es existiert kein Institut";
		} else {
			$studiengaenge = create_studiengang_array_by_institut_id($chosen_institut);

			$chosen_semester = (get_get('semester') ? get_get('semester') : get_this_semester()[0]);
			$chosen_dozent = (get_get('dozent') ? get_get('dozent') : null);
			$chosen_studiengang = (get_get('studiengang') ? get_get('studiengang') : null);
			$chosen_pruefungsamt = (get_get('pruefungsamt') ? get_get('pruefungsamt') : null);
			$studiengang_group_by = (get_get('studiengang_group_by') ? get_get("studiengang_group_by") : null);
?>
			<form method="get">
				<input type="hidden" name="page" value="<?php print $GLOBALS['this_page_number']; ?>" />
				<input type="hidden" name="studiengang" value="<?php print htmlentities(get_get('studiengang') ?? ""); ?>" />
				Semester: <?php create_select($semester, $chosen_semester, 'semester'); ?><br>
				<?php if(count($institute) > 1) { print "Institut: "; create_select($institute, $chosen_institut, 'institut'); print "<br />"; } ?>
				<?php if(count($dozenten) > 1) { print "Dozent:"; create_select($dozenten, $chosen_dozent, 'dozent', 1); print "</br>"; } ?>
				<?php if(count($studiengaenge) > 1) { print "Studiengang: "; create_select($studiengaenge, $chosen_studiengang, 'studiengang', 1); print "</br>"; } ?>
				<?php if(count($pruefungsaemter) > 1) { print "Prüfungsamt"; create_select($pruefungsaemter, $chosen_pruefungsamt, 'pruefungsamt', 1); print "</br>"; } ?>
				Nach Studiengang gruppieren? <input <?php if(!is_null($studiengang_group_by)) { print "checked='CHECKED'"; } ?> name="studiengang_group_by" type="checkbox"><br />
				<input type="submit" value="Nur Veranstaltungen anzeigen, die den Suchkriterien entsprechen" />
			</form>
<?php
			print export_pruefungsnummern_dozent($chosen_semester, $chosen_dozent, $chosen_institut, $chosen_studiengang, $chosen_pruefungsamt, $studiengang_group_by);
?>
			<form method="get" action="export_pruefungsnummern_dozenten.php">
				<input type="hidden" name="semester" value="<?php print htmlentities($chosen_semester ?? ""); ?>" />
				<input type="hidden" name="institut" value="<?php print htmlentities($chosen_institut ?? ""); ?>" />
				<input type="hidden" name="dozent" value="<?php print htmlentities($chosen_dozent ?? ""); ?>" />
				<input type="hidden" name="studiengang" value="<?php print htmlentities($chosen_studiengang ?? ""); ?>" />
				<input type="hidden" name="pruefungsamt" value="<?php print htmlentities($chosen_pruefungsamt ?? ""); ?>" />
				<input type="submit" value="Diese Liste als Excel-Datei downloaden" />
			</form>
<?php
		}
?>

	</div>
<?php
	}
?>
