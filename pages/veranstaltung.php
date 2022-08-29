<?php
	$included_files = get_included_files();
	$included_files = array_map('basename', $included_files);

	if(!in_array('functions.php', $included_files)) {
		include_once('../functions.php');
	}

	if(check_page_rights(get_page_id_by_filename(basename(__FILE__)))) { // Wichtig, damit Niemand ohne Anmeldung etwas ändern kann
		$veranstaltung_id = get_get('id');
		$institut_id = null;
		$stunden = create_stunden_array();
		if(!preg_match('/^\d+$/', $veranstaltung_id ?? "")) {
			$veranstaltung_id = null;
		}

		if($veranstaltung_id) {
			$institut_id = get_institut_id_by_veranstaltung_id($veranstaltung_id);
			if(!$institut_id) {
				$veranstaltung_id = null;
			}
		}

		$valid_to_edit_dozenten = array();
		if(array_key_exists('user_dozent_id', $GLOBALS) && isset($GLOBALS['user_dozent_id'])) {
			$valid_to_edit_dozenten[] = $GLOBALS['user_dozent_id'];
		}

		foreach (get_user_per_superdozent($GLOBALS['logged_in_user_id']) as $superuser_item => $superuser_id) {
			$valid_to_edit_dozenten[] = $superuser_id[0];
		}

		if(user_is_verwalter($GLOBALS['logged_in_user_id'])) {
			foreach (get_user_array() as $user_id => $user_name) {
				$valid_to_edit_dozenten[] = $user_id;
			}
		}

		$va = create_veranstaltungen_array($GLOBALS['user_role_id'] == 1 ? '' : $valid_to_edit_dozenten, null, 80);

		if($veranstaltung_id) {
			$studiengaenge = create_studiengang_array_by_institut_id($institut_id);
			$gebaeude = create_gebaeude_abkuerzungen_array();
			$dozent_id = get_dozent_id_by_veranstaltung_id($veranstaltung_id);
			$this_institut_id = get_institut_id_by_veranstaltung_id($veranstaltung_id);
			if($dozent_id == $GLOBALS['user_dozent_id'] || $GLOBALS['user_role_id'] == 1 || in_array($dozent_id, $valid_to_edit_dozenten)) {
					if(count($studiengaenge)) {
?>
				<h2>Veranstaltung &raquo;<?php print htmlentities(get_veranstaltungsname_by_id($veranstaltung_id)); ?>&laquo;<?php

				$last_update = '';
				$metainfos = '';
				if($GLOBALS['user_dozent_id'] != $dozent_id) {
					$metainfos .= ", gehalten von ".get_dozent_name($dozent_id);
				}
				$veranstaltung_semester = get_veranstaltung_semester(get_get('id'));
				if($veranstaltung_semester != $GLOBALS['this_semester_id'][0]) {
					$semester_data = get_semester($veranstaltung_semester);
					$metainfos .= ', '.$semester_data[2].' '.$semester_data[1];
				}
				$query = 'SELECT DATE_FORMAT(`last_change`, "%d.%m.%Y %H:%i:%s") FROM `veranstaltung` WHERE `id` = '.esc(get_get('id'));
				$result = rquery($query);
				while ($row = mysqli_fetch_row($result)) {
					$last_update = $row[0];
				}
				if($last_update) {
					$metainfos .= ", letztes Update: $last_update\n";
				}

				if($metainfos) {
					print "<span class='font_size_13px'>".htmlentities($metainfos)."<span>\n";
				}
?></h2>
				<?php print get_seitentext(); ?>
<?php
				include_once('hinweise.php');
?>
				<form method="post" action="admin?page=<?php print $GLOBALS['this_page_number']; ?>&id=<?php print $veranstaltung_id; ?>">
					<input type="submit" class="text_30px sticky_save" value="Speichern" />

					<table>
						<tr>
							<th>Sprache (optional)</th>
							<th>Präsenztyp</th>
							<th>Tag</th>
							<th>Stunde</th>
							<th>Woche</th>
							<th>Erster Termin</th>
							<th>Anzahl Hörer</th>
							<th>Opal-Link</th>
							<th>Abgabe Prüfungsleistung</th>
							<th>Gebäude&shy;wunsch</th>
							<th>Raum&shy;wunsch</th>
							<th>Master-Niveau?</th>
							<th>Fester BBB-Raum?</th>
						</tr>
<?php
						$columns = array('wunsch', 'hinweis', 'opal_link', 'wochentag', 'stunde', 'woche', 'anzahl_hoerer', 'erster_termin', 'abgabe_pruefungsleistungen', 'raumwunsch', 'gebaeudewunsch', 'last_change', 'master_niveau', 'language_id', 'related_veranstaltung', 'fester_bbb_raum', 'videolink');
						$this_data = array();

						foreach ($columns as $this_column) {
							$this_data[$this_column] = '';
						}

						$query = 'SELECT `vm`.`wunsch`, `vm`.`hinweis`, `vm`.`opal_link`, `vm`.`wochentag`, `vm`.`stunde`, `vm`.`woche`, `vm`.`anzahl_hoerer`, `vm`.`erster_termin`, `vm`.`abgabe_pruefungsleistungen`, `v`.`raumwunsch_id`, `v`.`gebaeudewunsch_id`, `v`.`last_change`, `v`.`master_niveau`, `vm`.`language_id`, `vm`.`related_veranstaltung`, `vm`.`fester_bbb_raum`, `vm`.`videolink` FROM `veranstaltung_metadaten` `vm` JOIN `veranstaltung` `v` ON `v`.`id` = `vm`.`veranstaltung_id` WHERE `veranstaltung_id` = '.esc(get_get('id'));
						$result = rquery($query);
						while ($row = mysqli_fetch_row($result)) {
							$i = 0;
							foreach ($columns as $this_column) {
								if(isset($row[$i])) {
									$this_data[$this_column] = $row[$i];;
								}
								$i++;
							}
						}
?>
						<tr>
							<td class="text_align_left_nowrap"><?php
								foreach (create_language_array() as $this_language) {
									$praesenz_id = $this_language[0];
									$language_name = $this_language[1];

									$checked = '';

									if(veranstaltung_has_language($veranstaltung_id, $praesenz_id)) {
										$checked = ' checked="checked" ';
									}
?>
									<input type="checkbox" name="language[]" value="<?php print htmlentities($praesenz_id); ?>" <?php print $checked; ?> /> <?php print htmlentities($language_name); ?><br />
<?php
								}
							?></td>
							<td class="text_align_left_nowrap">
								<select name="praesenztyp[]">
									<?php
										if(get_praesenztyp_from_veranstaltung_id($veranstaltung_id) == null) {
											print "<option>Bisher noch nicht gesetzt</option>";
										}
										foreach (create_praesenztypen_array() as $this_praesenztyp) {
											$praesenztyp_id = $this_praesenztyp[0];
											$praesenz_name = $this_praesenztyp[1];

											$checked = '';

											if(veranstaltung_has_praesenztyp($veranstaltung_id, $praesenztyp_id)) {
												$checked = ' selected="SELECTED" ';
											}
		?>
											<option value="<?php print htmlentities($praesenztyp_id); ?>" <?php print $checked; ?>> <?php print htmlentities($praesenz_name); ?></option>
		<?php
										}
									?>
								</select>
							</td>
							<td><?php print create_select(create_wochentag_array(), $this_data['wochentag'], 'tag'); ?></td>
							<td><?php print create_select($stunden, $this_data['stunde'], 'stunde'); ?></td>
							<td><?php print create_select(create_wann_array(), $this_data['woche'], 'woche'); ?></td>
							<td><input type="text" placeholder="erster_termin" name="erster_termin" class="datepicker" value="<?php print htmlentities($this_data['erster_termin']); ?>" /></td>
							<td><input type="text" placeholder="anzahl_hoerer" name="anzahl_hoerer" value="<?php print htmlentities($this_data['anzahl_hoerer']); ?>" /></td>

							<td><input type="text" name="opal_link" value="<?php print htmlentities($this_data['opal_link']); ?>" /></td>
							<td><input type="text" placeholder="abgabe_pruefungsleistungen" name="abgabe_pruefungsleistungen" class="datepicker" value="<?php print htmlentities($this_data['abgabe_pruefungsleistungen']); ?>" /></td>
							<td><?php print create_select($gebaeude, $this_data['gebaeudewunsch'], 'gebaeudewunsch', 1); ?></td>
							<td><input type="text" value="<?php print get_raum_name_by_id($this_data['raumwunsch']); ?>" name="raumwunsch" />
							<td><input type="checkbox" value="1" name="master_niveau" <?php print $this_data['master_niveau'] ? 'checked="checked"' : ''; ?>/></td>
							<td><input type="checkbox" value="1" name="fester_bbb_raum" <?php print $this_data['fester_bbb_raum'] ? 'checked="checked"' : ''; ?>/></td>
						</tr>
					</table>
					<br />
<?php
						if(user_braucht_barrierefreien_zugang($dozent_id)) {
?>
							<p><i>In die Raumplanungshinweise wird automatisch `Barrierefrei` eingefügt. Eine Manuelle Eingabe ist nicht nötig. Diese Information erscheint hier jedoch nicht.</i></p>
<?php
						}
?>
					<table>
						<tr>
							<th>Konferenzraum-Link</th>
							<th>Hinweise für Raumplanung</th>
							<th>Hinweise für Studenten</th>
						</tr>
						<tr>
							<td><textarea name="videolink" class="veranstaltung_textarea" placeholder="https://example.org/videokonferenzlink"><?php print htmlentities($this_data['videolink']); ?></textarea></td>
							<td><textarea name="wunsch" class="veranstaltung_textarea" placeholder="Diese Hinweise werden für den Raumplaner angezeigt"><?php print htmlentities($this_data['wunsch']); ?></textarea></td>
							<td><textarea name="hinweis" class="veranstaltung_textarea" placeholder="Diese Hinweise werden auf der Startseite angezeigt"><?php print htmlentities($this_data['hinweis']); ?></textarea></td>
						</tr>
					</table>
					<br />
					<p>Die Spalte &raquo;<b>Hinweise für Studenten</b>&laquo; erscheint im Vorlesungsverzeichnis im Menü zu jeder einzelnen Veranstaltung. &raquo;<b>Hinweise für die Raumplanung</b>&laquo; bleibt dagegen intern.</p>
					<p>Zugehörige Veranstaltung:
<?php
						create_select($va, $this_data['related_veranstaltung'], 'related_veranstaltung', 1);
?>
					</p>
					<input type="hidden" value="update_einzelne_veranstaltung" name="update_einzelne_veranstaltung" />
					<input type="hidden" value="<?php print htmlentities($veranstaltung_id ?? ""); ?>" name="id" />
					<br />

					<h3>Einzelne Termine hinzufügen</h3>

<?php
					$last_id = null;
?>
					<table id="einzelne_termine">
						<tbody>
							<tr>
								<th>Start</th>
								<th>Ende</th>
								<th>Gebäude</th>
								<th>Raum</th>
								<th><button type="button" id="new_row_einzelner_termin">Neuen Termin hinzufügen</a></th>
							</tr>
<?php
							$einzelne_termine_query = 'select e.start, e.end, g.id as gebaeude_id, r.raumnummer from einzelne_termine e left join raum r on e.raum_id = r.id left join gebaeude g on g.id = r.gebaeude_id  where veranstaltung_id = '.esc(get_get('id'));
							$einzelne_termine_results = rquery($einzelne_termine_query);

							while ($row = mysqli_fetch_row($einzelne_termine_results)) {
								$et_start = $row[0];
								$et_end = $row[1];
								$et_gebaeude_id = $row[2];
								$et_raumname = $row[3];
?>
								<tr>
									<td>
									<input type="text" name="einzelner_termin_start[]" value="<?php print htmlentities($et_start); ?>" class="datetimepicker" />
									</td>
									<td>
										<input type="text" name="einzelner_termin_ende[]" value="<?php print htmlentities($et_end); ?>" class="datetimepicker" />
									</td>
									<td>
										<?php print create_select($gebaeude, $et_gebaeude_id, 'einzelner_termin_gebaeude[]', 1); ?>
									</td>
									<td>

										<input type="text" value="<?php print htmlentities($et_raumname); ?>" name="einzelner_termin_raum[]" />
									</td>
									<td>
										<span class="remove_this_tr"><img src="./i/remove.svg" alt="Zeile entfernen" width="30" /></span>
									</td>
								</tr>
<?php
							}
?>
						</tbody>
					</table>

<?php
# TODO $last_id;
?>

					<div class="display_none">
						Bitte ignorieren Sie diese Gebäudeauswahl! Aktivieren Sie CSS oder aktualisieren Sie Ihren Browser, damit diese Meldung hier nicht mehr auftaucht.
						<div id="gebaeude_selection">
							<?php print create_select($gebaeude, null, 'TOREMOVE_einzelner_termin_gebaeude[]', 1); ?>
						</div>

						<div id="last_id"><?php if(isset($last_id)) { print $last_id; } ?></div>
					</div>

					<br />
					<br />
<?php
					$studiengang_ids = array();
					foreach ($studiengaenge as $this_studiengang) {
						$studiengang_ids[] = $this_studiengang[0];
					}
					$module = create_module_array_by_studiengang($studiengang_ids);
					if(count($module)) {
?>
						<table class="width_auto">
							<tr>
								<th>Modul</th>
								<th>Prüfungsnummern</th>
							</tr>
<?php
							$checked_pruefungsnummern = get_checked_pruefungsnummern(get_get('id'));

							$last_studiengang = '';

							foreach ($studiengaenge as $this_studiengang) {
								$module_ids = array();

								$this_studiengang_index = $this_studiengang;
								if(is_array($this_studiengang)) {
									$this_studiengang_index = $this_studiengang[0];
								}

								if($this_studiengang_index == 'Alle Studiengänge') {
									continue;
								}

								$counter = 0;
								if(array_key_exists($this_studiengang_index, $module)) {
									foreach ($module[$this_studiengang_index] as $this_modul) {
										$module_ids[] = $this_modul[0];
									}

									$pruefungsnummern = create_pruefungsnummern_array_by_modul_id($module_ids);
									foreach ($module[$this_studiengang_index] as $this_modul) {
										if(array_key_exists($this_modul[0], $pruefungsnummern) && count($pruefungsnummern[$this_modul[0]])) {
										if($last_studiengang != $this_studiengang[1]) {
											$last_studiengang = $this_studiengang[1];
?>
												<tr>
													<td colspan="10" class="bg_add8e6"><?php print htmlentities($this_studiengang[1]); ?></td>
												</tr>
<?php
											}
?>
											<tr>

												<td><?php print htmle($this_modul[1]); ?></td>
												<td class="text_align_left">
<?php
												foreach ($pruefungsnummern[$this_modul[0]] as $this_pruefungsnummer) {
													$counter = $counter + 1;
													$is_checked = 0;
													if(array_key_exists($this_pruefungsnummer[0], $checked_pruefungsnummern) && array_key_exists($this_modul[0], $checked_pruefungsnummern[$this_pruefungsnummer[0]])) {
														$is_checked = 1;
													}
?>
													<div class="bg_<?php print (($counter % 2 == 1) ? 'DCDCDC' : 'A9A9A9'); ?>">
														<input type="checkbox" name="pruefungsnummer[]" value="<?php
														print htmlentities($this_pruefungsnummer[0]);
													?>" <?php
														print $is_checked == 1 ? 'checked="CHECKED"' : '';
													?>/><?php
														$str = '';
														if(isset($this_pruefungsnummer[4])) {
															if($str) { $str .= ', '; }
															$str .= htmle($this_pruefungsnummer[4]);
														}

														if(isset($this_pruefungsnummer[3])) {
															if($str) { $str .= ', '; }
															$str .= htmle($this_pruefungsnummer[3]);
														}

														if(isset($this_pruefungsnummer[5]) && strlen($this_pruefungsnummer[5])) {
															if($str) { $str .= ', '; }
															$str .= htmle($this_pruefungsnummer[5]);
														}

														if(isset($this_pruefungsnummer[1])) {
															if($str) { $str .= ', '; }
															if($this_pruefungsnummer[1] == '-') {
																if($str) {
																	$str .= '<i>k';
																} else {
																	$str .= '<i>K';
																}
																$str .= 'eine weiteren Informationen zu dieser Prüfungsleistung angegeben</i>';
															} else {
																$str .= htmle($this_pruefungsnummer[1]);
															}
														}

														print $str;
													?></div>
<?php
												}
?>
												</td>
											</tr>
<?php
										} else {
?>
											<tr>
												<td colspan="2">
													<i>Leider keine eingetragenen Prüfungsnummern für das Modul &raquo;<?php print $this_modul[1]; ?>&laquo; (<?php print $this_studiengang[1]; ?>)</i>
												</td>
											</tr>
<?php
										}
									}
								}
							}
?>
						</table>
<?php
					} else {
?>
						<i class="class_red">Leider existieren noch keine Studiengänge bzw. Module. Kontaktieren Sie einen Administrator mit der Bitte, für das ausgewählte Institut Studiengänge, Module und Prüfungsnummern einzutragen.</i><br />
<?php
					}
?>

							<br />
						</form>
					<form method="get">
						<input type="hidden" value="1" name="all_semesters_copy" />
						<input type="hidden" value="<?php print $GLOBALS['this_page_number']; ?>" name="page" />
						<input type="hidden" value="<?php print $veranstaltung_id; ?>" name="id" />
						<input type="submit" value="Zum Kopieren von Prüfungsleistungen Veranstaltungen aus allen Semestern anzeigen" />
					</form>
<?php
						$veranstaltungen = array();
						if(get_get('all_semesters_copy')) {
							$veranstaltungen = create_veranstaltungen_array($GLOBALS['user_role_id'] == 1 ? '' : $valid_to_edit_dozenten, get_get('id'), 80, null, $this_institut_id);
						} else {
							$veranstaltungen = create_veranstaltungen_array($GLOBALS['user_role_id'] == 1 ? '' : $valid_to_edit_dozenten, get_get('id'), 80, get_veranstaltung_semester(get_get('id')), $this_institut_id);
						}
						if(count($veranstaltungen)) {
?>
						<form method="post" action="admin?page=<?php print $GLOBALS['this_page_number']; ?>&id=<?php print $veranstaltung_id; ?>">
							<p>Beim Kopieren von Prüfungen werden alle eingetragenen Prüfungen gelöscht und die ausgewählten Prüfungsnummern einer anderen Veranstaltung dieser hier zugeordnet. Dies dient dazu, einfach Veranstaltungenseinstellungen zu &raquo;klonen&laquo;.</p>
<?php
								usort($veranstaltungen, function($a, $b) {
									return strcmp($a['1'], $b['1']);
								});


								create_select($veranstaltungen, get_post('kopieren_von'), 'kopieren_von');
?>
								<p>Sollen dabei die bisher zu dieser Veranstaltung gespeichert Daten überschrieben werden? <input type="checkbox" value="1" name="delete_old_data" /></p>
								<input type="submit" name="pruefungen_kopieren" value="Prüfungen von der ausgewählten Veranstaltung kopieren" />
						</form>
<?php
						} else if (get_get('all_semesters_copy')) {
?>
							<i class="class_red">Aktuell existieren noch keine Veranstaltungen, aus denen etwas kopiert werden kann (sie müssen am gleichen Institut sein, wie diese Veranstaltung!).</i>
<?php
						}
					} else {
						print "<h2>Es müssen für jeden Studiengang erst Module und Prüfungsnummern definiert werden, bevor diese Seite angezeigt werden kann. Bitten Sie einen Administrator darum, dies zu tun (oder tun Sie es selbst, sofern Sie die notwendigen Rechte besitzen).</h2>";
					}
			} else {
				print "<span class='red_large'>Sie haben keine Rechte, auf diese Veranstaltung zuzugreifen.</span>";
			}

			js(array("autosubmit.js"));
		} else {
?>
			<form method="get">
				<input type="hidden" name="page" value="<?php print $GLOBALS['this_page_number']; ?>" />
<?php
				create_select($va, '', 'id', 1);
?>
				<input type="submit" value="Veranstaltung auswählen" />
			</form>
<?php
		}
	}
?>
