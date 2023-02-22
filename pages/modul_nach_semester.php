<?php
	$included_files = get_included_files();
	$included_files = array_map('basename', $included_files);

	if(!in_array('functions.php', $included_files)) {
		include_once('../functions.php');
	}

	if(check_page_rights(get_page_id_by_filename(basename(__FILE__)))) { // Wichtig, damit Niemand ohne Anmeldung etwas ändern kann
		$studiengang = get_get('studiengang');
?>
	<div id="modul_nach_semester">
		<?php print get_seitentext(); ?>
<?php
		include_once('hinweise.php');
		if($studiengaenge) {
?>
			<form method="get" action="admin">
				<input type="hidden" name="page" value="<?php print $GLOBALS['this_page_number']; ?>" />
				Studiengang: <?php create_select(create_studiengang_array_by_institut_id_str(), $studiengang, 'studiengang', 0, 1); ?>
				<input type="submit" value="Filtern" />
			</form>
			<br />
<?php
		}
		if($studiengang) {
			$query = 'select name, modul_id, group_concat(semester) from view_modul_semester where studiengang_id ='.esc($studiengang).' group by `modul_id`';
			$result = rquery($query);
			if(mysqli_num_rows($result)) {
?>
				<table>
					<tr>
						<th>Modul</th>
						<th>Semester (Mit Komma getrennt)</th>
						<th>Aktion</th>
					</tr>
<?php
					while ($row = mysqli_fetch_row($result)) {
?>
						<form method="post" enctype="multipart/form-data" action="admin?page=<?php print $GLOBALS['this_page_number']; ?>&studiengang=<?php print $studiengang; ?>">
							<tr>
								<input type="hidden" name="modul_nach_semester" value="1" />
								<input type="hidden" name="modul" value="<?php print $row[1]; ?>" />
								<td><?php print htmlentities($row[0] ?? ""); ?></td>
								<td><input noautosubmit=1 type="text" name="semester" value="<?php print htmlentities($row[2] ?? ""); ?>" /></td>
								<td><input noautosubmit=1 type="submit" value="Speichern" /></td>
							</tr>
						</form>
<?php
				}
?>
				</table>
<?php
				$query = 'select max(semester) from view_modul_semester where studiengang_id = '.esc($studiengang);
				$result = rquery($query);
				$max_semester = 0;

				while ($row = mysqli_fetch_row($result)) {
					$max_semester = $row[0];
				}

				if($max_semester > 32) {
					$max_semester = 32;
					print "WARNUNG: Die Maximalsemesteranzahl liegt bei 32. Alle Werte darüber werden auf 32 gesetzt.";
				}

				$veranstaltungstypen = create_veranstaltungstyp_abkuerzung_array();

				if($max_semester) {
?>
					<br />
					<table>
						<tr>
							<th>Semester</th>
							<th>Modul</th>
							<th>Anzahl Ver&shy;an&shy;stal&shy;tungen pro Typ</th>
							<th>Anzahl Credit-Points</th>
							<th>Anzahl Prü&shy;fungs&shy;leis&shy;tungen</th>
						</tr>
<?php
						foreach (range(1, $max_semester) as $this_semester) {
							foreach (create_module_array_by_studiengang_and_semester($studiengang, $this_semester) as $this_modul) {
								$credit_points = 0;
								$anzahl_pruefungsleistungen = 0;
								$query = 'SELECT `credit_points`, `anzahl_pruefungsleistungen` FROM `modul_nach_semester_metadata` WHERE `modul_id` = '.esc($this_modul[0]).' AND `semester` = '.esc($this_semester);
								$result = rquery($query);
								while ($row = mysqli_fetch_row($result)) {
									$credit_points = $row[0];
									$anzahl_pruefungsleistungen = $row[1];
								}

								$veranstaltungstyp_anzahl = create_array_veranstaltungstyp_anzahl_by_modul_id_semester($this_modul[0], $this_semester);
	?>
								<form method="post" enctype="multipart/form-data" action="admin?page=<?php print $GLOBALS['this_page_number']; ?>&studiengang=<?php print htmlentities($studiengang ?? ""); ?>">
									<tr>
										<input type="hidden" name="semester" value="<?php print htmlentities($this_semester ?? ""); ?>" />
										<input type="hidden" name="update_modul_semester_data" value="1" />
										<input type="hidden" name="studiengang" value="<?php print htmlentities($studiengang ?? ""); ?>" />
										<input type="hidden" name="modul" value="<?php print htmlentities($this_modul[0]); ?>" />
										<td><?php print htmle($this_semester); ?></td>
										<td><?php print htmle($this_modul[1]); ?></td>
										<td>
	<?php
											foreach ($veranstaltungstypen as $this_veranstaltungstyp) {
	?>
												<table>
													<tr>
														<td><input class="width50px" type="text" value="<?php print htmlentities($veranstaltungstyp_anzahl[$this_veranstaltungstyp[0] ?? ""]); ?>" name="<?php print "veranstaltungstyp_$this_veranstaltungstyp[0]"; ?>" /></td>
														<td><?php print htmle($this_veranstaltungstyp[1]); ?></td>
													</tr>
												</table>
	<?php
											}
	?>
										</td>
										<td><input type="text" class="width50px" value="<?php print htmlentities($credit_points ?? ""); ?>" name="credit_points" /></td>
										<td><input type="text" class="width50px" value="<?php print htmlentities($anzahl_pruefungsleistungen ?? ""); ?>" name="pruefungsleistung_anzahl" /></td>
									</tr>
								</form>
	<?php
							}
						}
?>
					</table>
	<?php
				}
			} else {
?>
				<i>Für diesen Studiengang sind keine Module verfügbar.</i>
<?php
			}
		} else {
			print "<i>Kein Studiengang ausgewählt oder es existieren keine Studiengänge";
		}

		js(array("autosubmit.js"));
?>
	</div>
<?php
	}
?>
