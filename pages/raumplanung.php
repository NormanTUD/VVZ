<?php
	$included_files = get_included_files();
	$included_files = array_map('basename', $included_files);

	if(!in_array('functions.php', $included_files)) {
		include_once('../functions.php');
	}

	if(check_page_rights(get_page_id_by_filename(basename(__FILE__)))) { // Wichtig, damit Niemand ohne Anmeldung etwas ändern kann
		$institute = create_institute_array();
		$semester = create_semester_array(1);
?>
		<div id="raumplanung">
			<?php print get_seitentext(); ?>
<?php
			include_once('hinweise.php');
			if($GLOBALS['user_role_id'] == 1) {
				$this_institut = get_get('institut');
				if(!preg_match('/^\d+$/', $this_institut) && strlen($this_institut)) {
					$this_institut = 1;
				}
			} else {
				$this_institut = $GLOBALS['user_institut_id'];
			}

			if(count($semester)) {
				$this_semester = get_get('semester') ? get_get('semester') : get_and_create_this_semester()[0];
				$this_semester_is_valid = 0;
				foreach ($semester as $tsemester) {
					if($tsemester[0] == $this_semester) {
						$this_semester_is_valid = 1;
					}

				}
				if(!$this_semester_is_valid) {
					if($semester[1][0]) {
						$new_semester = get_semester($semester[1][0], 1);
?>
						<i class="red_text">Für das ausgewählte bzw. ausgewählte Semester sind leider keine Veranstaltungen eingetragen. <b>Statt &raquo;<?php print get_semester($this_semester, 1)[1]; ?>&laquo; wird das &raquo;<?php print $new_semester[1]; ?>&laquo; ausgewählt.</i></b> Um das ausgewählte Semester anzeigen zu können, tragen Sie bitte Veranstaltungen ein und weisen Sie diesen dieses Semester zu.
<?php
						$this_semester = $new_semester[0];
					} else {
?>
						<i class="class_red">Es sind noch keine Daten vorhanden, mit denen das Vorlesungsverzeichnis arbeiten könnte. Bitte fügen Sie Veranstaltungen hinzu, bevor Sie die Raumplanung aufrufen.</i>
<?php
					}
				}
				if(preg_match('/^\d+$/', $GLOBALS['user_institut_id']) && count($institute) >= 2 && $GLOBALS['user_role_id'] == 1) {
?>
					<form class="form" method="get">
						<input type="hidden" value="<?php print $GLOBALS['this_page_number']; ?>" name="page" />
						<input type="hidden" value="<?php print $GLOBALS['user_institut_id']; ?>" name="institut" />
						<input type="hidden" value="<?php print htmlentities(get_get('semester')); ?>" name="semester" />
						<input type="submit" value="Nur die Daten meines Institutes anzeigen" />
					</form>
<?php
				}
?>
				<form class="form" method="get">
<?php
					if($GLOBALS['user_role_id'] == 1) {
?>
						Für welches Institut soll die Raumplanung angezeigt werden? <?php print create_select($institute, get_get('institut'), 'institut', 1, 1); ?>
<?php
					}
?>
					<input type="hidden" value="<?php print $GLOBALS['this_page_number']; ?>" name="page" />
					Semester?
<?php
					create_select($semester, $this_semester, 'semester', 0, 1);
?>
					<input type="submit" value="Filtern" />
				</form>
<?php
				$has_printed_rows = raumplanung($this_institut, $this_semester, 1);
				if($has_printed_rows) {
?>
					<form method="get" action="raumplanung_export.php">
						<input type="hidden" name="institut" value="<?php print htmlentities($this_institut); ?>" />
						<input type="hidden" name="semester" value="<?php print htmlentities($this_semester); ?>" />
						<input type="submit" value="Als Microsoft-Excel-Tabelle herunterladen" />
					</form>
					<form method="get" action="raumplanung_export_2.php">
						<input type="hidden" name="institut" value="<?php print htmlentities($this_institut); ?>" />
						<input type="hidden" name="semester" value="<?php print htmlentities($this_semester); ?>" />
						<input type="submit" value="Als Microsoft-Excel-Tabelle herunterladen (verr&uuml;cktes Format)" />
					</form>
<?php
				}
			} else {
?>
				<i>Bisher gibt es noch kein Semester mit eingetragenen Veranstaltungen, das über diese Seite verwaltet werden könnte.</i>
<?php
			}
?>
		</div>
<?php
		js(array("autosubmit.js"));
	}
?>
