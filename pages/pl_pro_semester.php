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

			$studiengang_module_pn = get_studiengang_modul_pruefungsnummer_array();
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
			if(count($studiengang_module_pn)) {
?>
				<table>
					<tr>
						<th>Studiengang</th>
						<th>Modul</th>
						<th>Bereich</th>
						<th>Prüfungstyp</th>
						<th>Prüfungsnummer</th>
						<th>Anzahl Prüfungen</th>
					</tr>
<?php

					foreach ($studiengang_module_pn as $item) {
						$studiengang = $item[0];
						$modul = $item[1];
						$pruefungsnummer = $item[2];
						$bereich_name = $item[3];
						$pruefungstyp = $item[4];

						$anzahl = get_anzahl_pl_pro_semester($pruefungsnummer, $chosen_semester);
						$anzahl_str = $anzahl;

						if($anzahl == 0) {
							$anzahl_str = "<span class='red_text'>$anzahl</span>";
						}

						if((get_get("only_zero") && $anzahl == 0) || !get_get("only_zero")) {
							print "<tr>";
							print "<td>$studiengang</td>";
							print "<td>$modul</td>";
							print "<td>$bereich_name</td>";
							print "<td>$pruefungstyp</td>";
							print "<td>$pruefungsnummer</td>";
							print "<td>$anzahl_str</td>";
							print "</tr>\n";
						}
					}
?>
				</table>
<?php
			} else {
				print "<i>Keine Module und/oder Studiengänge</i>";
			}
?>
		</div>
<?php
	}
?>
