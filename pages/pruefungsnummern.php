<?php
	$included_files = get_included_files();
	$included_files = array_map('basename', $included_files);

	if(!in_array('functions.php', $included_files)) {
		include_once('../functions.php');
	}

	if(check_page_rights(get_page_id_by_filename(basename(__FILE__)))) { // Wichtig, damit Niemand ohne Anmeldung etwas ändern kann
		$institute = create_institute_array();
		$chosen_institut = get_get('institut');
		if(count($institute) == 0) {
?>
			<h2 class="class_red">Keine Institute vorhanden. Bitten Sie den Administrator, Institute anzulegen.</h2>
<?php
		}

		if(!isset($chosen_institut) && isset($GLOBALS['user_institut_id'])) {
			$chosen_institut = $GLOBALS['user_institut_id'];
		}
		
		if (!isset($chosen_institut) && count($institute) == 1) {
			$chosen_institut = $institute;
			foreach ($institute as $key => $value) {
				$chosen_institut = $value[0];
			}
		}

		if(count($institute) >= 2) {
?>
			<form method="get">
				Für welches Institut soll die Prüfungsnummernverwaltung angezeigt werden? 
				<input type="hidden" name="page" value="<?php print htmlentities(get_get('page') ?? ""); ?>" />
				<?php create_select($institute, $chosen_institut, 'institut'); ?>
				<input type="submit" value="Anzeigen" />
			</form>
<?php
		}

		if($chosen_institut) {
			$studiengaenge_module = array();
			$studiengaenge = create_studiengaenge_array($chosen_institut);
			$zeitraum = create_zeitraum_array();
			if(count($studiengaenge)) {
				$module = create_modul_studiengang_array(70, $chosen_institut);
				if(count($module)) {
					$pruefungstypen = create_pruefungstypen_array();

					$query = 'SELECT `ms`.`modul_id`, `ms`.`studiengang_id`, `ms`.`modul_name`, `ms`.`studiengang_name` FROM `view_modul_studiengang` `ms` LEFT JOIN `studiengang` `s` ON `s`.`id` = `ms`.`studiengang_id` ';
					$query .= ' WHERE `s`.`institut_id` = '.esc($chosen_institut);
					$query .= ' ORDER BY `ms`.`studiengang_id`';
					$result = rquery($query);

					while ($row = mysqli_fetch_row($result)) {
						$studiengaenge_module[] = $row;
					}

					print get_seitentext();
					include_once('hinweise.php');
?>
					<table>
						<tr>
							<th>Modul</th>
							<th>Bereich</th>
							<th>Prüfungstyp</th>
							<th>Modulbezeichnung</th>
							<th class="pn_th">PN</th>
							<th>Zeitraum</th>
							<th>PN Deaktiviert?</th>
							<th>Löschen?</th>
						</tr>
<?php
					$query = '
SELECT
	studiengang_name,
	modul_name,
	pruefungsnummer,
	pruefungstyp_id,
	modul_id,
	pruefungsnummer_id,
	bereich_id,
	pruefungsnummer_fach_id,
	modulbezeichnung,
	zeitraum_id,
	disabled
FROM
	view_pruefungsnummern_in_modulen_not_null pn
JOIN
	studiengang s on s.id = pn.studiengang_id
';
						$query .= 'where `institut_id` = '.esc($chosen_institut);
						$query .= 'order by studiengang_name asc, modul_name asc, pruefungsnummer_fach_id asc, pruefungstyp_name asc, pruefungsnummer asc';
						$result = rquery($query);

						$rows = array();

						$modul_namen_counter = array();
						$this_studiengang = null;
						while ($row = mysqli_fetch_row($result)) {
							$rows[] = $row;
							$key = $row[0].'---'.$row[1];
							if(array_key_exists($key, $modul_namen_counter)) {
								$modul_namen_counter[$row[0].'---'.$row[1]]++;
							} else {
								$modul_namen_counter[$row[0].'---'.$row[1]] = 1;
							}
						}

						$i = 0;
						$j = 0;

						$bereiche = create_bereiche_array();

						foreach ($rows as $row) {
							if(is_null($this_studiengang) || $this_studiengang != $row[0]) {
								$this_studiengang = $row[0];
								print "<tr class='colordarkblue'><td class='bg_add8e6' colspan='9'>".htmlentities($this_studiengang ?? "")."</td></tr>\n";
							}

							$bgcolor = 'ededed';
							if($j % 2 == 0) {
								$bgcolor = 'f5f5f5';
							}
?>
							<tr class="bg_<?php print $bgcolor; ?>">
							<form class="form form_autosubmit" method="post" action="admin.php?page=<?php print htmlentities($GLOBALS['this_page_number'] ?? "") ?>&institut=<?php print htmlentities($chosen_institut ?? ""); ?>">
									<input type="hidden" value="update_pruefungsnummer" name="update_pruefungsnummer" />
									<input type="hidden" value="<?php print $row[4]; ?>" name="modul_id" />
									<input type="hidden" value="<?php print $row[5]; ?>" name="id" />
<?php
									$key = $row[0].'---'.$row[1];
									if($i == 0) {
										$i = $modul_namen_counter[$key];
?>
										<td rowspan="<?php print $modul_namen_counter[$key] ?>"><?php print htmle($row[1]); ?></td>
<?php
									}
?>
									<td><?php create_select($bereiche, $row[6], 'bereich'); ?></td>
									<td><?php create_select($pruefungstypen, $row[3], 'pruefungstyp'); ?></td>
									<td><input type="text" name="modulbezeichnung" class="width_auto" value="<?php print htmlentities($row[8] ?? ""); ?>" /></td>
									<td><input type="text" name="pruefungsnummer" class="width_auto" value="<?php print htmlentities($row[2] ?? ""); ?>" /></td>
									<td><?php create_select($zeitraum, $row[9], 'zeitraum'); ?></td>
									<td><?php create_select(array("0" => "Nein", "1" => "Ja"), $row[10] ? 'Ja' : 'Nein', 'pndisabled')  ?></td>
									<td><input type="submit" name="delete" value="Löschen" /></td>
								</form>
							</tr>
<?php
							$i--;
							$j++;
						}
?>
							<form class="form" method="post" action="admin.php?page=<?php print htmlentities($GLOBALS['this_page_number']) ?>&institut=<?php print htmlentities($chosen_institut ?? ""); ?>">
							<input data-id="noautosubmit" type="hidden" value="neue_pruefungsnummer" name="neue_pruefungsnummer" />
							<td><?php create_select($module, '', 'modul', 0, 1); ?></td>
							<td><?php create_select($bereiche, '', 'bereich', 0, 1); ?></td>
							<td><?php create_select($pruefungstypen, '', 'pruefungstyp', 0, 1); ?></td>
							<td><input type="text" name="modulbezeichnung" class="width_auto" noautosubmit="1" value="" /></td>
							<td><input type="text" name="pruefungsnummer" class="width_auto" noautosubmit="1" placeholder="pruefungsnummer" /></td>
							<td><?php create_select($zeitraum, '', 'zeitraum', 0, 1); ?></td>
							<td>&mdash;</td>
							<td><input type="submit" value="Speichern" /></td>
						</form>
					</table>
<?php
				} else {
?>
					<h2 class="class_red">Für dieses Institut sind noch keine Module vorhanden. Bitten Sie einen Administrator, Module hinzuzufügen.</h2>
<?php
				}
			} else {
				print("Bisher existieren keine Studiengänge. <a href='admin.php?page=".get_page_id_by_filename("studiengang.php")."'>Bitte fügen Sie hier welche hinzu.</a>");
			}
		} else {
?>
			<h2 class="class_red">Aktuell sind noch keine Institute eingetragen.</h2>
<?php
		}
	}
	js(array("autosubmit.js"));
?>
