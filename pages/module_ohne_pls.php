<?php
	$included_files = get_included_files();
	$included_files = array_map('basename', $included_files);

	if(!in_array('functions.php', $included_files)) {
		include_once('../functions.php');
	}

	if(!get_setting("x11_debugging_mode") && check_page_rights(get_page_id_by_filename(basename(__FILE__)))) { // Wichtig, damit Niemand ohne Anmeldung etwas ändern kann
?>
		<div id="bereiche">
			<?php print get_seitentext(); ?>
<?php
			include_once('hinweise.php');

			$module = get_module();
			$semester = create_semester_array_short();
			$chosen_semester = (get_get('semester') ? get_get('semester') : get_this_semester()[0]);


?>
			<form method="get">
				<input type="hidden" name="page" value="<?php print $GLOBALS['this_page_number']; ?>" />
				Nur Module ohne PLs anzeigen? <input type="checkbox" name="only_zero" value="1" <?php print get_get("only_zero") == 1 ? 'checked="CHECKED"' : '' ?> /><br>
				Semester: <?php create_select($semester, $chosen_semester, 'semester'); ?><br>
				<input type="submit" value="Einstellungen wählen" />
			</form>
<?php
			if(count($module)) {
?>
				<table>
					<tr>
						<th>Studiengang</th>
						<th>Modul</th>
						<th>Anzahl Prüfungen</th>
					</tr>
<?php

					foreach ($module as $modul) {
						$modul_id = $modul[0];
						$modul_name = $modul[1];

						$studiengang = get_studiengang_name_by_modul_id($modul_id);

						$anzahl = get_anzahl_pruefungen_pro_modul_pro_semester($modul_id, $chosen_semester);
						$anzahl_str = $anzahl;

						if($anzahl == 0) {
							$anzahl_str = "<span class='red_text'>$anzahl</span>";
						}

						if((get_get("only_zero") && $anzahl == 0) || !get_get("only_zero")) {
							print "<tr><td>$studiengang</td><td>$modul_name</td><td>$anzahl_str</td></tr>\n";
						}
					}
?>
				</table>
<?php
			} else {
				print "<i>Bisher existieren keine Module</i>";
			}
?>
		</div>
<?php
	}
?>
