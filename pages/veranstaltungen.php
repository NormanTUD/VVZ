<?php
	$included_files = get_included_files();
	$included_files = array_map('basename', $included_files);

	if(!in_array('functions.php', $included_files)) {
		include_once('../functions.php');
	}

	if(check_page_rights(get_page_id_by_filename(basename(__FILE__)))) { // Wichtig, damit Niemand ohne Anmeldung etwas ändern kann
		get_and_create_next_n_semester_years(1);
		$is_superdozent = user_is_superdozent($GLOBALS['logged_in_user_id']);

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


		$dozenten = create_dozenten_array(user_is_admin($GLOBALS['logged_in_user_id']) ? 1 : 0);
		if($is_superdozent) {
			$dozenten = create_dozenten_by_ids_array($valid_to_edit_dozenten);
		}
		$veranstaltungstypen = create_veranstaltungstyp_abkuerzung_array();
		$institute = create_institute_array();
		$semester = create_semester_array_short();

		$pruefungen = create_pruefungen_veranstaltungen_array();

		$chosen_semester = (get_get('semester') ? get_get('semester') : get_this_semester()[0]);
		$user_role_id = get_role_id_by_user($GLOBALS['logged_in_user_id']);

		$show_table = 1;

		if(!count($veranstaltungstypen)) {
			$show_table = 0;

			print "Keine Veranstaltungstypen definiert.";
		}

		if($show_table) {
?>
			<div id="veranstaltungen">
				<script nonce=<?php print($GLOBALS['nonce']); ?> >
					$(window).bind('keydown', function(event) {
						if (event.ctrlKey || event.metaKey) {
							switch (String.fromCharCode(event.which).toLowerCase()) {
								case 's':
									event.preventDefault();
									if(["textarea", "select-one"].includes($(":focus")[0].type)) {
										$(":focus").parent().parent().find(".save_buttons").click();
									}
									break;
								case 'e':
									event.preventDefault();
									if(["textarea", "select-one"].includes($(":focus")[0].type)) {
										$(":focus").parent().parent().find(".edit_buttons").click();
									}
									break;
							}
						}
					});
				</script>

				<?php print get_seitentext(); ?>
<?php
				include_once('hinweise.php');
				if(isset($GLOBALS['user_institut_id'])) {
?>
					<form method="get">
						<input type="hidden" name="page" value="<?php print $GLOBALS['this_page_number']; ?>" />
						<input type="hidden" name="dozent_id" value="<?php print htmlentities(get_get('dozent_id') ?? ""); ?>" />
						<?php create_select($semester, $chosen_semester, 'semester'); ?>
						<input type="submit" value="Nur Veranstaltungen aus diesem Semester anzeigen" />
					</form>
<?php
					if($user_role_id == 1 || $is_superdozent) {
						print "&mdash; oder &mdash;\n";
						if(isset($GLOBALS['user_dozent_id'])) {
							if(!get_get('dozent_id')) {
?>
								<form method="get">
									<input type="hidden" name="dozent_id" value="<?php print $GLOBALS['user_dozent_id']; ?>" />
									<input type="hidden" name="page" value="<?php print $GLOBALS['this_page_number']; ?>" />
									<input type="hidden" name="semester" value="<?php print htmlentities(get_get('semester') ?? ""); ?>" />
									<input type="submit" value="Nur meine Veranstaltungen anzeigen" />
								</form>
<?php
							} else {
?>
								<form method="get">
									<input type="hidden" name="page" value="<?php print $GLOBALS['this_page_number']; ?>" />
									<input type="hidden" name="semester" value="<?php print htmlentities(get_get('semester') ?? ""); ?>" />
									<input type="submit" value="Wieder alle Veranstaltungen anzeigen" />
 								</form>					
<?php
							}

							print "&mdash; oder &mdash;\n";
?>
							<form method="get">
								<input type="hidden" name="page" value="<?php print $GLOBALS['this_page_number']; ?>" />
								<input type="hidden" name="semester" value="<?php print htmlentities(get_get('semester') ?? ""); ?>" />
<?php
								print create_select($dozenten, get_get('dozent_id'), 'dozent_id', 1);
?>
								<input type="submit" value="Nur Veranstaltungen dieses Dozenten anzeigen" />
							</form>
<?php
						}
						if(preg_match('/^\d+$/', $GLOBALS['user_institut_id']) && count($institute) >= 2) {
							if(isset($GLOBALS['user_dozent_id'])) {
								print "&mdash; oder &mdash;\n";
							}
							if(get_get('institut')) {
?>
								<form class="form" method="get">
									<input type="hidden" value="<?php print $GLOBALS['this_page_number']; ?>" name="page" />
									<input type="hidden" name="dozent_id" value="<?php print htmlentities(get_get('dozent_id')); ?>" />
									<input type="hidden" name="semester" value="<?php print htmlentities(get_get('semester')); ?>" />
									<input type="submit" value="Die Daten aller Institute anzeigen" />
								</form>
<?php
							} else {
?>
								<form class="form" method="get">
									<input type="hidden" value="<?php print $GLOBALS['this_page_number']; ?>" name="page" />
									<input type="hidden" value="<?php print $GLOBALS['user_institut_id']; ?>" name="institut" />
									<input type="hidden" name="dozent_id" value="<?php print htmlentities(get_get('dozent_id') ?? ""); ?>" />
									<input type="hidden" name="semester" value="<?php print htmlentities(get_get('semester') ?? ""); ?>" />
									<input type="submit" value="Nur die Daten meines Institutes anzeigen" />
								</form>
<?php
							}
						}
					}
?>
<?php
					if(semester_has_sperrvermerk($chosen_semester)) {
?>
						<h1 class="red_giant">Für die Lehrveranstaltungen dieses Semesters gibt es bereits einen Sperrvermerk. Vermutlich wurden die Angaben bereits an die Raumvergabe gemeldet. Informieren Sie die Verantwortlichen über Änderungen</h1>
<?php
					}
?>

					<table class="veranstaltungen_table">
						<tr>
							<th>Titel</th>
							<th>Semester</th>
<?php
							if($user_role_id == 1 || ($is_superdozent && count($dozenten) > 1)) {
?>
								<th>Dozent</th>
<?php
							}

							if($user_role_id == 1 && count($institute) >= 2) {
?>
								<th>Institut</th>
<?php
							}
?>
							<th>Typ</th>
							<th>Details</th>
							<th>Anz. Prfg.</th>
							<!--<th>Letztes Update</th>-->
							<th>Löschen?</th>
						</tr>
<?php
					$gebaeude = create_gebaeude_array(0);
					#		 0	1			2	3		4			5		6		7	8		9
					$query = 'SELECT `v`.`id`, `veranstaltungstyp_id`, `name`, `dozent_id`, `gebaeudewunsch_id`, `raumwunsch_id`, `raummeldung`, `gebaeude_id`, `raum_id`, `institut_id`, `semester_id`, `vm`.`wochentag`, `d`.`last_name`, `d`.`first_name`, (unix_timestamp(now()) - unix_timestamp(`v`.`last_change`)) as since_last_change FROM `veranstaltung` `v` LEFT JOIN `veranstaltung_metadaten` `vm` ON `v`.`id` = `vm`.`veranstaltung_id` LEFT JOIN `dozent` `d` ON `d`.`id` = `v`.`dozent_id`';
					$where = array();
					if(($user_role_id == 1 || ($is_superdozent && in_array(get_get('dozent_id'), $valid_to_edit_dozenten))) && get_get('dozent_id')) {
						$where[] = ' `dozent_id` = '.esc(get_get('dozent_id'));
					}
					if(get_get('institut')) {
						$where[] = ' `institut_id` = '.esc(get_get('institut'));
					}
					if($chosen_semester) {
						$where[] = ' `semester_id` = '.esc($chosen_semester);
					}

					if($user_role_id != 1 && !$is_superdozent) {
						// Wenn der User kein Admin ist, darf er nur seine eigenen Sachen sehen
						$where[] = ' `dozent_id` = '.esc(get_dozent_id_by_user_id($GLOBALS['logged_in_user_id']));
					} else if ($is_superdozent) {
						$where[] = ' `dozent_id` IN ('.multiple_esc_join($valid_to_edit_dozenten).')';
					}

					if(count($where)) {
						$query .= ' WHERE '.join(' AND ', $where);
					}

					$query = "$query ORDER BY `vm`.`wochentag`, `vm`.`stunde`, `d`.`last_name`, `d`.`first_name`, `v`.`name`";

					$result = rquery($query);
					$anzahl_pruefungen = 0;
					$last_weekday = '';
					$wochentage = create_wochentag_abk_nach_name_array();
					$no_date_shown = 0;
					while ($row = mysqli_fetch_row($result)) {
						if($last_weekday != $row[11] || !$row[11]) {
							$last_weekday = $row[11];
							if($row[11]) {
?>
								<tr>
									<td colspan="9" class="bg_multispan_col_header"><?php print $wochentage[$row[11]]; ?></td>
								</tr>
<?php
							} else if (!$no_date_shown) {
?>
								<tr>
									<td colspan="9" class="bg_225599_ddaa66">Bisher kein eingetragener Wochentag. Bitte unter &raquo;<i>Bearbeiten</i>&laquo; eintragen!</td>
								</tr>
<?php
								$no_date_shown = 1;
							}
						}
?>
							<tr>
								<form class="form_autosubmit" method="post" enctype="multipart/form-data" action="admin?page=<?php print $GLOBALS['this_page_number']; ?><?php
										if(isset($GLOBALS['user_dozent_id']) && get_get('dozent_id')) {
											print '&dozent_id='.$GLOBALS['user_dozent_id'];
										}
										print '&semester='.htmlentities(get_get('semester') ?? "");
									?>">
									<input type="hidden" value="<?php print $GLOBALS['this_page_number']; ?>" name="page" />
									<input type="hidden" value="<?php print htmlentities(get_get('semester') ?? ""); ?>" name="semester" />
									<input type="hidden" value="1" name="update_veranstaltung" />
									<input type="hidden" value="<?php print $row[0]; ?>" name="id" />
									<td><textarea class="veranstaltungen_textarea" name="name"><?php print htmlentities($row[2]); ?></textarea></td>
									<td><?php create_select($semester, $row[10], 'semester', 1); ?></td>
<?php
									if($user_role_id == 1 || ($is_superdozent && count($dozenten) > 1)) {
?>
										<td><?php create_select($dozenten, $row[3], 'dozent'); ?></td>
<?php
									} else {
?>
										<input type="hidden" value="<?php print htmlentities($row[3]); ?>" name="dozent" />
<?php
									}
									if($user_role_id == 1 && count($institute) >= 2) {
?>
										<td><?php create_select($institute, $row[9], 'institut'); ?></td>
<?php
									} else {
?>
										<input type="hidden" value="<?php print htmlentities($GLOBALS['user_institut_id']); ?>" name="institut" />
<?php
									}
?>
									<td><?php create_select($veranstaltungstypen, $row[1], 'veranstaltungstyp'); ?></td>
									<td><input type="submit" name="speichern_metainfos" class="edit_buttons" value="Bearbeiten" /></td>
									<td><?php print array_key_exists($row[0], $pruefungen) ? $pruefungen[$row[0]] : '<span class="class_red">!!! 0 !!!</span>'; ?></td>
									<!--<td>Etwa <?php print(htmlentities(sprintf("%0.1f", $row[14] / 86400))); ?> Tage her</td>-->
									<td><input type="submit" name="delete" value="Löschen" /></td>
								</form>
							</tr>
<?php
						if(array_key_exists($row[0], $pruefungen)) {
							$anzahl_pruefungen += $pruefungen[$row[0]];
						}
					} // id, name, dozent, veranstaltungstyp, gebaeudewunsch, delete
?>
							<tr>
								<td colspan="9" class="neue_veranstaltung">Neue Veranstaltungen eintragen</td>
							</tr>
							<tr>
								<form method="post" enctype="multipart/form-data" action="admin?page=<?php print $GLOBALS['this_page_number']; ?><?php
									if(isset($GLOBALS['user_dozent_id']) && get_get('dozent_id')) {
										print '&dozent_id='.$GLOBALS['user_dozent_id'];
									}

									if(get_get('semester')) {
										print '&semester='.htmlentities(get_get('semester') ?? "");
									}

									?>">
									<input type="hidden" value="<?php print $GLOBALS['this_page_number']; ?>" name="page" />
									<input type="hidden" value="" name="id" />
									<input type="hidden" value="<?php print htmlentities(get_get('semester') ?? ""); ?>" name="semester" />
									<input type="hidden" value="1" name="neue_veranstaltung" noautosubmit=1"  />
									<td><textarea noautosubmit=1 class="veranstaltungen_textarea" name="name"></textarea></td>
									<td><?php create_select($semester, $chosen_semester, 'semester', 0, 1); ?></td>
<?php
									if($user_role_id == 1 || ($is_superdozent && count($dozenten) > 1)) {
?>
										<td><?php create_select($dozenten, $GLOBALS['user_dozent_id'], 'dozent', 0, 1); ?></td>
<?php
									} else {
?>
										<input type="hidden" value="<?php print htmlentities($GLOBALS['user_dozent_id']); ?>" name="dozent" />
<?php
									}
									if($user_role_id == 1 && count($institute) >= 2) {
?>
										<td><?php create_select($institute, '', 'institut', 0, 1); ?></td>
<?php
									} else {
?>
										<input type="hidden" value="<?php print htmlentities($GLOBALS['user_institut_id']); ?>" name="institut" />
<?php
									}
?>
									<td><?php create_select($veranstaltungstypen, null, 'veranstaltungstyp', 0, 1); ?></td>
									<td><input type="submit" name="speichern_und_bearbeiten" class="save_buttons" value="Eintragen" /></td>
									<td>&uarr;&sum; = <?php print $anzahl_pruefungen; ?></td>
									<td>&mdash;</td>
								</form>
							</tr>
						</form>
					</table>
					<h2>Veranstaltungstypen</h2>
						<ul>
<?php
							foreach (create_veranstaltungstyp_array() as $this_veranstaltungstyp) {
								print "<li>".$this_veranstaltungstyp[1]."</li>\n";
							}
?>
						</ul>
					</div>
<?php
				} else {
?>
					<h2 class="class_red">Sie haben keine zugeordnete Instituts-ID. Ohne diese kann die Seite nicht aufgerufen werden. Bitten Sie einen Administrator, Ihnen ein Institut zuzuordnen.</h2>
<?php
				}
			}
		}
		js(array("autosubmit.js"));
?>
