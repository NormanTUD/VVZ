<?php
	$php_start = microtime(true);

	if(file_exists('new_setup')) {
		include('setup.php');
		exit(0);
	}

	$page_title = "Vorlesungsverzeichnis TU Dresden";
	$filename = 'index.php';
	include("header.php");

	$linkicon = '<i class="fa float-right"><img alt="Link zum Studiengang" src="icon.svg" /></i>';

	$this_semester = null;

	if(preg_match('/^\d+$/', get_get('semester'))) {
		$this_semester = get_semester(get_get('semester'), 0);
	}

	if(!isset($this_semester)) {
		$this_semester = get_this_semester();
		if(!$this_semester) {
			$this_semester = get_and_create_this_semester(1);
			if(!$this_semester) {
				$valid_semesters = create_semester_array(1, 1, array(get_get('semester')));
				if(is_array($valid_semesters) && count($valid_semesters)) {
					$this_semester = $valid_semesters[1];
				} else {
					die("Es existieren keine validen, eingetragenen Semester.");
				}
			}
		}
	}

	$shown_etwa = 0;

	$institute = create_institute_array();

	$this_institut = null;

	if(preg_match('/^\d+$/', get_get('institut'))) {
		$this_institut = get_get('institut');
	} else {
		if($_SERVER['HTTP_HOST'] == 'vvz.phil.tu-dresden.de') {
			$this_institut = $institute[1][0];
		} else if ($_SERVER['HTTP_HOST'] == 'vvz.musik.tu-dresden.de') {
			$this_institut = $institute[1][0];
		}
		
		if(!$this_institut) {
			if(count($institute)) {
				if(array_key_exists(0, $institute) && array_key_exists(0, $institute[0])) {
					$this_institut = $institute[0][0];
				}
				if(!$this_institut) {
					$this_institut = array_key_first($institute);
				}
			} else {
				die("Es konnten keine Institute gefunden werden. Ohne eingetragene Institute kann die Software nicht benutzt werden. Bitte kontaktieren Sie die Administratoren über die Kontaktseite.");
			}
		}
	}

?>
	
	<div id="mainindex" <?php if($GLOBALS['show_comic_sans']) { print ' class="bgaf"'; } ?>>
		<a href="index.php?semester=<?php print isset($this_semester[0]) ? htmlentities($this_semester[0]) : ''; ?>&institut=<?php print isset($this_institut) ? htmlentities($this_institut) : ''; ?>" border="0"><img alt="TUD-Logo, Link zur Startseite" src="tudlogo.svg" width=300 /></a>
		<div class="iframewarning red_giant"></div>
		<!--<p id="hilfe"><i>Website-Tour anzeigen</i></p>-->
<?php
		if(isset($GLOBALS['logged_in_user_id'])) {
			$dozent_name = htmlentities(get_dozent_name($GLOBALS['logged_in_data'][2]));
			if(!user_is_verwalter($GLOBALS['logged_in_user_id'])) {
				if(!preg_match('/\w{2,}/', $dozent_name)) {
					$dozent_name = htmlentities($GLOBALS['logged_in_data'][1]).' <span class="class_red">!!! Ihr Account ist mit keinem Dozenten verknüpft! <a href="admin.php?page=1">Ändern Sie das hier</a> !!!</span>';
				}
			} else {
				$dozent_name = htmlentities($GLOBALS['logged_in_data'][1]);
			}

			if(!$GLOBALS['user_role_id'][0]) {
				$dozent_name = htmlentities($GLOBALS['logged_in_data'][1]).' <span class="class_red">!!! Ihr Account hat keine ihm zugeordnete Rolle! !!!</span>';
			}
?>
			<br />Willkommen, <?php print $dozent_name; ?>! &mdash; <a class="red_large" href="logout.php">Abmelden</a>
<?php
		}
?>
		<header class="header_margin_bottom">
			<noscript><span class="red_large">Bitte aktivieren Sie JavaScript in Ihrem Browser, um diese Seite vollständig nutzen zu können.</span></noscript> 
			<div class="row">
				<div class="medium-2 columns margin_top_margin_bottom_1_rem">
				<!--<div id="backbutton" class="visibility_hidden"><a onclick="history.back(-1)">&#8630;</i>&nbsp; zurück</a></div>-->
				</div>
				<div class="medium-8 columns">
<?php
					if($GLOBALS['show_comic_sans']) {
?>
						<h2 class="text-center rainbow">Vorlesungsverzeichnis</h2>
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
					if(!count($institute)) {
						print "<h2 class='class_red'>Es konnten keine Institute gefunden werden. Bitten Sie die Administratoren darum, dies zu erledigen.</h2>";
					}
					if(count($institute) >= 2) {
?>
						<form method="get">
							<p class="text-center"><?php create_select($institute, $this_institut, 'institut'); ?></p>
<?php
							if(array_key_exists('studiengang', $_GET)) {
?>
								<input type="hidden" name="studiengang" value="<?php print htmlentities(get_get('studiengang')); ?>" />
<?php
							}

							if(array_key_exists('semester', $_GET)) {
?>
								<input type="hidden" name="semester" value="<?php print htmlentities(get_get('semester')); ?>" />
<?php
							}

?>
							<input type="submit" value="Institut auswählen" />
						</form>
<?php
					}
?>
<?php
					$semester_array = create_semester_array(1, 0, array(get_get('semester'), $this_semester[0]));
					if(count($semester_array) >= 2) {
?>
						<form method="get">
							<p class="text-center"><?php create_select($semester_array, $this_semester[0], 'semester'); ?></p>
<?php
							if(array_key_exists('studiengang', $_GET)) {
?>
								<input type="hidden" name="studiengang" value="<?php print htmlentities(get_get('studiengang')); ?>" />
<?php
							}

							if(array_key_exists('institut', $_GET)) {
?>
								<input type="hidden" name="institut" value="<?php print htmlentities(get_get('institut')); ?>" />
<?php
							}
?>
							<input type="submit" value="Semester auswählen" />
						</form>
<?php
					}
					$vvz_start_message = get_vvz_start_message($this_institut);
					if($vvz_start_message) {
						print "Hinweis: <span class='orange_italic'>".htmlentities($vvz_start_message)."</span>\n";
					}
?>
				</div>
				<div class="medium-2 columns"></div>
			</div>
		</header>
<?php
		if(isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/^w3m/i', $_SERVER['HTTP_USER_AGENT'])) {
?>
			Oh, wow! Ein w3m-Nutzer! Ich hätte nie gedacht, dass neben mir tatsächlich einer diesen Browser nutzt! Leider funktionieren einige Dinge in w3m nicht, aber elinks stellt die Seite halbwegs richtig und benutzbar dar.
<?php
		} else if(isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/^lynx/i', $_SERVER['HTTP_USER_AGENT'])) {
?>
			Oh, wow! Ein lynx-Nutzer! Ich hätte nie gedacht, dass neben mir tatsächlich einer diesen Browser nutzt! Leider funktionieren einige Dinge in lynx nicht, aber elinks stellt die Seite halbwegs richtig und benutzbar dar.
<?php
		} else if(isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/^elinks/i', $_SERVER['HTTP_USER_AGENT'])) {
?>
			Oh, wow! Ein elinks-Nutzer! Ich hätte nie gedacht, dass neben mir tatsächlich einer diesen Browser nutzt!
<?php
		}

		if(get_get('veranstaltung') || (get_get('dozent') && get_get('create_stundenplan'))) {
?>
			<div class="autocenter_large">
				Studiengang, für den die Prüfungsliste generiert werden soll?
				<form>
<?php
					create_select(create_studiengang_array_by_institut_id(), get_get('studiengang'), 'studiengang');
					foreach($_GET as $key => $value) {
						if($key != 'studiengang') {
							if(is_array($value)) {
								$str = '';
								foreach($value as $ikey => $ivalue) {
									$str .= '<input type="hidden" name="'.htmlentities($key).'[]" value="'.htmlentities($ivalue).'" />'."\n";
								}
								print $str;
							} else {
								echo "<input type='hidden' name='".htmlentities($key)."' value='".htmlentities($value)."' />\n";
							}
						}
					}
?>
					<input type="submit" />
				</form>
<?php
				$generierter_stundenplan = create_stundenplan(get_get('veranstaltung'), 1, 1, get_get('bereich'), 0, get_get('studiengang'), get_get('dozent'), get_get('semester'));
				print $generierter_stundenplan[0];
?>
			</div>
<?php
			if(0 && is_writable_r('tmp', 'ttfonts', 'ttfontdata')) {
?>
				<form method="get" action="export_stundenplan.php" />
<?php
					foreach (get_get('veranstaltung') as $key => $value) {
?>
						<input type="hidden" name="veranstaltung[]" value="<?php print $value; ?>" />
<?php
					}

					if($generierter_stundenplan[2]) {
?>
					<input type="checkbox" value="1" name="show_pruefungsleistungen" /> Prüfungsleistungstabelle exportieren?<br />
<?php
			}
					if($generierter_stundenplan[1]) {
?>
						<input type="checkbox" value="1" name="show_gebaeudeliste" /> Gebäudeliste exportieren?<br />
<?php
					}
?>
					<!--<input type="submit" value="Als PDF herunterladen" />-->
				</form>
<?php
			}
		/* Einzelner Studiengang spezifiziert */
		} else if (get_get('studiengang')) {
?>
			<div class="height_20px"></div>
<?php
			if(get_get('show_pruefungen')) {
				$query = 'select modul_name, pruefungsnummer, pruefungstyp_name, name from view_pruefungsnummern_in_modulen where studiengang_id = '.esc(get_get('studiengang')).' order by modul_name, pruefungstyp_name, pruefungsnummer';
				$result = rquery($query);

				$fach = '';

				if(mysqli_num_rows($result)) {
?>
					<div class="autocenter_large">
						<table class="minwidth400">
							<tr>
								<th>Prüfungsnummer</th>
								<th>Prüfungstyp</th>
								<th>Studiengang</h>
							</tr>
<?php
							while ($row = mysqli_fetch_row($result)) {
								if($fach != $row[0]) {
?>
									<tr>
										<td class="background_color_add8e6" colspan="3"><?php print htmlentities($row[0]); ?>:</td>
									</tr>
<?php
									$fach = $row[0];
								}
?>

							<tr>
								<td><?php print htmle($row[1]); ?></td>
								<td><?php print htmle($row[2]); ?></td>
								<td><?php print htmle($row[3]); ?></td>
							</tr>
<?php
						}
?>
						</table>
<?php
				} else {
?>
					<i>Für den ausgewählten Studiengang gibt es keine eingetragenen Prüfungen.</i>
<?php
				}
			} else {
				if(!get_get('create_stundenplan')) {
?>
					<div class="font20px">
						<a id="filter_toggle">&#128269;&nbsp;Filter</a> &mdash;
						<a id="toggle_all_details">&#8650;&nbsp;Details anzeigen/ausblenden</a>
<?php
					if(studiengang_has_semester_modul_data(get_get('studiengang'))) {
?>
	&mdash; <a id="create_stundenplan_link" href="index.php?create_stundenplan=1<?php
						if(get_get('studiengang') != 'alle') {
							print '&studiengang='.htmlentities(get_get('studiengang'));
						}

						if(get_get('semester')) {
							print '&semester='.htmlentities(get_get('semester'));
						}

						if(get_get('institut')) {
							print '&institut='.htmlentities(get_get('institut'));
						}

?>">&#128197;&nbsp;Stundenplanerstellung</a>
<?php
					}

					$studienordnung_url = get_studienordnung_url(get_get('studiengang'));

					if ($studienordnung_url) {
?>
						&mdash; <a href="<?php print $studienordnung_url; ?>" id="studienordnung_link" >&#128196;&nbsp;Studienordnung</a>
<?php
					}
				}
?>

<?php

				if(get_get('create_stundenplan')) {
					if(get_get('studiengang')) {
						if(!get_get('chosen_semester')) {
							$max = 0;
							$query = 'select max(semester) from view_modul_semester where studiengang_id = '.esc(get_get('studiengang'));
							$result = rquery($query);

							while ($row = mysqli_fetch_row($result)) {
								$max = $row[0];
							}

							if($max) {
								$semester_array = range(1, $max);
?>
								<form method="get">
									<input type="hidden" name="create_stundenplan" value="1" />
									<input type="hidden" name="studiengang" value="<?php print htmlentities(get_get('studiengang')); ?>" />
									<input type="hidden" name="bereich" value="<?php print htmlentities(get_get('bereich')); ?>" />
									<input type="hidden" name="semester" value="<?php print htmlentities(get_get('semester')); ?>" />
									Semester: <?php create_select($semester_array, get_get('chosen_semester'), 'chosen_semester'); ?><br />
									<input type="submit" value="Weiter" />
								</form>
<?php
							} else {
?>
								Die Administratoren haben zu diesem Studiengang leider noch keine Informationen hinzugefügt.
<?php
							}
						} else {
							$query = 'select name, modul_id from view_modul_semester where studiengang_id = '.esc(get_get('studiengang')).' and semester = '.esc(get_get('chosen_semester'));
							$result = rquery($query);

							$metadaten = array();

							if(mysqli_num_rows($result)) {
?>
								Folgende Module sind für das ausgewählte Semester eingeplant:
<?php
								$module = array();
								while ($row = mysqli_fetch_row($result)) {
									$module[] = $row[1];
?>
									<div class="row">
										<div class="medium-4 medium-centered columns text-center">
										<div class="callout primary">
											<h4><?php print $row[0]; ?></h4>
<?php
								$veranstaltungstypen_liste = get_veranstaltungstypen_modul_semester($row[1], get_get('chosen_semester'));
								if(count($veranstaltungstypen_liste)) {
									print "Folgende Veranstaltungstypen sind laut der Studienordnung in diesem Semester in diesem Modul so viele Semesterwochenstunden zu besuchen: <br />";
									foreach ($veranstaltungstypen_liste as $anzahl_data) {
										print "$anzahl_data[0] &mdash; $anzahl_data[1]<br />";
										# modul		typ	      =     anzahl
										$metadaten[$row[1]][$anzahl_data[2]] = $anzahl_data[1];
									}
								}

								$metadata = get_credit_points_and_anzahl_pruefungsleistungen_by_modul_id_and_semester($row[1], get_get('chosen_semester'));
								if(count($metadata)) {
									if($metadata[0][0]) {
										print $metadata[0][0].' Credit-Point(s) werden durch das erfolgreichreiche Ablegen der Prüfungen erworben<br />';
									}

									if($metadata[0][1]) {
										print $metadata[0][1].' Prüfungsleistung(en) sollen abgelegt werden<br />';
									}
								}
?>
										</div>
									</div>
								</div>
<?php
							}

							$summen = get_sum_credit_points_anzahl_pruefungsleistungen_for_studiengang(get_get('studiengang'));
							print "Anzahl Credit-Points im gesamten Studiengang: $summen[0], Anzahl Prüfungen im gesamten Studium: $summen[1]\n";
?>
							<form>
								<input type="hidden" name="studiengang" value="<?php print htmlentities(get_get('studiengang')); ?>" />
								<input type="hidden" name="chosen_semester" value="<?php print htmlentities(get_get('chosen_semester')); ?>" />
								<input type="hidden" name="semester" value="<?php print htmlentities(get_get('semester')); ?>" />
								<input type="hidden" name="bereich" value="<?php print htmlentities(get_get('bereich')); ?>" />
								<input type="hidden" name="erstelle_stundenplan" value="1" />
<?php
							$metastring = '';
							foreach ($metadaten as $this_modul_id => $this_modul_veranstaltungstypen) {
								foreach ($this_modul_veranstaltungstypen as $this_vt_id => $this_vt_anzahl) {
									if($metastring) {
										$metastring = "$metastring,";
									}
									$metastring .= "modul_".htmlentities($this_modul_id).'_';
									$metastring .= 'veranstaltungstyp_'.htmlentities($this_vt_id)."_anzahl_".htmlentities($this_vt_anzahl);
								}
							}
?>
								<input type="hidden" name="metastring" value="<?php print htmlentities($metastring); ?>" />
<?php
							foreach ($module as $this_modul) {
?>
									<input type="hidden" name="modul[]" value="<?php print htmlentities($this_modul); ?>" />
<?php
							}
?>
								<input type="submit" value="Veranstaltungsauswahl anzeigen" />
							</form>
<?php
						} else {
?>
							Leider stehen für dieses Semester keine Daten zur Verfügung.<br />
<?php
						}
					}
				} else {
?>
					Studiengang:
<?php
					foreach (create_studiengang_array_with_semester_data() as $this_studiengang) {
?>
						<div class="row">
							<div class="medium-4 medium-centered columns text-center">
								<a href="index.php?create_stundenplan=1&studiengang=<?php print $this_studiengang[0]; ?>">
									<div class="callout primary">
											<?php print $linkicon; ?>
											<h4><?php print $this_studiengang[1]; ?></h4>
										</div>
									</a>
								</div>
							</div>
<?php
					}
?>
						Sollten hier Studiengänge fehlen liegt das daran, dass die Administratoren die dazu notwendigen Informationen noch
						nicht eingetragen haben.
						<i class="red_text">Achtung: Bitte überprüfen Sie diesen Stundenplan und vertrauen Sie nicht blind auf die Software! Wie
							bei jeder Software können auch hier Fehler geschehen!</i>
<?php
				}
				} else {
?>
					<form id="filter">
						<table>
							<tr>
								<td>
									Semester:
								</td>
								<td>
									<select name="semester">
<?php
					$query = 'select id, concat(typ, " ", jahr) from semester where id in (select semester_id from veranstaltung) order by jahr asc, typ asc';
					$result = rquery($query);

					if(mysqli_num_rows($result) > 1) {
?>
											<option value="<?php print htmlentities($this_semester[0]); ?>">Dieses Semester</option>
<?php
					}
					while ($row = mysqli_fetch_row($result)) {
?>
											<option value="<?php print $row[0]; ?>" <?php print $row[0] == $this_semester[0] ? 'selected' : ''; ?>><?php print htmlentities($row[1]); ?></option>
<?php
					}
?>
									</select>
								</td>
							</tr>
							<tr>
								<td>
									Vorlesungsname enthält im Titel:
								</td>
								<td>
									<input type="text" name="vltitel" value="<?php print htmlentities(get_get('vltitel')); ?>" />
								</td>
							</tr>
<?php
					if(!is_array(get_get('modul'))) {
?>
								<tr>
									<td>
										Modul:
									</td>

									<td>
										<select name="modul">
											<option value="">Alle</option>
<?php
						$query = 'SELECT `m`.`id`, if(m.abkuerzung is null, `m`.`name`, concat(m.name, " (", m.abkuerzung, ")")) as shown_name, s.name as studiengang_name FROM `modul` m join studiengang s on s.id = m.studiengang_id WHERE m.`name` IS NOT NULL AND m.`name` != ""';
						if(get_get('studiengang') != 'alle') {
							$query .= 'AND m.`studiengang_id` = '.esc(get_get('studiengang'));
						}
						$query .= ' ORDER BY studiengang_name asc, shown_name asc';
						$result = rquery($query);

						$last_studiengang_name = '';
						while ($row = mysqli_fetch_row($result)) {
							if($last_studiengang_name != $row[2]) {
								if($last_studiengang_name != "") {
?>
									</optgroup>
<?php
								}
?>
								<optgroup label="<?php print htmlentities($row[2]); ?>">
<?php
								$last_studiengang_name = $row[2];
							}
?>
												<option value="<?php print $row[0]; ?>" <?php print $row[0] == get_get('modul') ? 'selected' : ''; ?>><?php print htmlentities($row[1]); ?></option>
<?php
						}
?>
										</optgroup>
										</select>
									</td>
								</tr>
<?php
					}

					if(get_get('studiengang') == 'alle') {
?>
								<tr>
									<td>
										Studiengang:
									</td>
									<td>
										<select name="studiengang">
											<option value="alle">Alle</option>
<?php
						$query = 'SELECT `s`.`id`, `s`.`name` FROM `studiengang` `s`';
						$result = rquery($query);

						while ($row = mysqli_fetch_row($result)) {
							$str = $row[1];
?>
												<option value="<?php print $row[0]; ?>" <?php print $row[0] == get_get('studiengang') ? 'selected' : ''; ?>><?php print htmlentities($str); ?></option>
<?php
						}
?>
										</select>
									</td>
								</tr>
<?php
					} else {
?>
								<input type="hidden" name="studiengang" value="<?php print htmlentities(get_get('studiengang')); ?>" />
<?php
					}
?>
							<tr>
								<td>Veranstaltungstypen:</td>
								<td>
									<select name="veranstaltungstyp_id">
										<option value="">Alle</option>
<?php
					$query = 'SELECT `id`, concat(`abkuerzung`, " (", `name`, ")") FROM `veranstaltungstyp` WHERE `id` IN (SELECT `veranstaltungstyp_id` FROM `veranstaltung` WHERE `institut_id` = '.esc($this_institut).')';
					$result = rquery($query);

					while ($row = mysqli_fetch_row($result)) {
						if(strlen($row[1]) >= 4) { # " ()" steht da, wenn kein Typ gefunden wurde
?>
												<option value="<?php print $row[0]; ?>" <?php print $row[0] == get_get('veranstaltungstyp_id') ? 'selected' : ''; ?>><?php print htmlentities($row[1]); ?></option>
<?php
						} else {
?>
												<option value="<?php print $row[0]; ?>" <?php print $row[0] == get_get('veranstaltungstyp_id') ? 'selected' : ''; ?>>Kein Veranstaltungstyp</option>
<?php
						}
					}
?>
									</select>
								</td>
							</tr>
							<tr>
								<td>
									Wochentag:
								</td>
								<td>
									<select name="wochentag">
										<option value="">Alle</option>
<?php
					foreach (create_wochentag_array() as $wochentag) {
?>
											<option value="<?php print $wochentag; ?>" <?php print $wochentag == get_get('wochentag') ? 'selected' : ''; ?>><?php print htmlentities($wochentag); ?></option>
<?php
					}
?>
									</select>
								</td>
							</tr>
							<tr>
								<td>
									Stunde:
								</td>
								<td>
									<select name="stunde">
									<option value="">Alle</option>
<?php
					$stundenquery = 'SELECT `stunde` FROM `veranstaltung_metadaten` `vm` JOIN `veranstaltung` `v` ON `v`.`id` = `vm`.`veranstaltung_id` WHERE `v`.`institut_id` = '.esc($this_institut).' GROUP BY `stunde`';
					$rresult = rquery($stundenquery);
					$available_stunden = array();
					while ($trow = mysqli_fetch_row($rresult)) {
						$available_stunden[] = $trow[0];
					}
					foreach ($available_stunden as $stunde) {
?>
											<option value="<?php print $stunde; ?>" <?php print $stunde == get_get('stunde') ? 'selected' : ''; ?>><?php print htmlentities($stunde); ?></option>
<?php
					}
?>
									</select>
								</td>
							</tr>
							<tr>
								<td>
									Dozent:
								</td>
								<td>
									<select name="dozent">
										<option value="">Alle</option>
<?php
					$query = 'SELECT `id`, concat(`first_name`, " ", `last_name`) FROM `dozent` WHERE `id` IN (SELECT `dozent_id` FROM `veranstaltung` WHERE `institut_id` = '.esc($this_institut).')';
					$result = rquery($query);

					while ($row = mysqli_fetch_row($result)) {
?>
											<option value="<?php print $row[0]; ?>" <?php print $row[0] == get_get('dozent') ? 'selected' : ''; ?>><?php print htmlentities($row[1]); ?></option>
<?php
					}
?>
									</select>
								</td>
							</tr>
							<tr>
								<td>
									Gebäude:
								</td>
								<td>
									<select name="gebaeude">
										<option value="">Alle</option>
<?php
					$query = 'SELECT `id`, `abkuerzung`, `name` FROM `gebaeude` WHERE `id` IN (SELECT `gebaeude_id` FROM `veranstaltung`) ORDER BY `abkuerzung`';
					$result = rquery($query);

					while ($row = mysqli_fetch_row($result)) {
?>
											<option value="<?php print $row[0]; ?>" <?php print $row[0] == get_get('gebaeude') ? 'selected' : ''; ?>><?php print htmlentities($row[1])." (".htmlentities($row[2]).")"; ?></option>
<?php
					}
?>
									</select>
								</td>
							</tr>
							<tr>
								<td>
									Prüfungstyp:
								</td>
								<td>
									<select name="pruefungstyp">
										<option value="">Alle</option>
<?php
					$query = 'select pt.id, pt.name from pruefung p join pruefungsnummer pn on p.pruefungsnummer_id = pn.id join pruefungstyp pt on pt.id = pn.pruefungstyp_id where veranstaltung_id in (select id from veranstaltung where institut_id = '.esc($this_institut).') group by pt.id order by pt.name';
					$result = rquery($query);

					while ($row = mysqli_fetch_row($result)) {
?>
											<option value="<?php print $row[0]; ?>" <?php print $row[0] == get_get('pruefungstyp') ? 'selected' : ''; ?>><?php print htmlentities($row[1]); ?></option>
<?php
					}
?>
									</select>
								</td>
							</tr>
							<tr>
								<td>OPAL-Seite vorhanden?</td>
								<td><input type="checkbox" value="1" name="opal_zwingend" <?php print htmlentities(get_get('opal_zwingend')) ? 'checked="CHECKED"' : '' ?> /></td>
							</tr>
							<tr>
								<td></td>
								<td><input type="submit" value="Filtern" /></td>
							</tr>
						</table>

					</form>

					<form method="get" action="index.php">
<?php
					$where = '';

					if(get_get('veranstaltungstyp_id')) {
						$where .= '`veranstaltungstyp_id` = '.esc(get_get('veranstaltungstyp_id'));
					}

					if(get_get('dozent')) {
						if($where) { $where .= " AND "; }
						$where .= '`dozent_id` = '.esc(get_get('dozent'));
					}

					if(get_get('wochentag')) {
						if($where) { $where .= " AND "; }
						$where .= '`wochentag` = '.esc(get_get('wochentag'));
					}

					if(get_get('stunde')) {
						if($where) { $where .= " AND "; }
						$where .= '`stunde` = '.esc(get_get('stunde'));
					}

					if(get_get('opal_zwingend')) {
						if($where) { $where .= " AND "; }
						$where .= 'CHAR_LENGTH(`opal_link`) >= 1';
					}

					if(get_get('vltitel')) {
						if($where) { $where .= " AND "; }
						$where .= '`v`.`name` LIKE '.esc("%".get_get('vltitel')."%");
					}

					if(get_get('pruefungsnummer_id')) {
						if($where) { $where .= " AND "; }
						$where .= '`v`.`id` IN (select veranstaltung_id from pruefung where pruefungsnummer_id = '.esc(get_get('pruefungsnummer_id')).')';
					}

					if(get_get('fakultaet')) {
						if($where) { $where .= " AND "; }
						$where .= '`v`.`id` IN (SELECT `vs`.`veranstaltung_id` FROM `view_veranstaltung_nach_studiengang` `vs` LEFT JOIN `studiengang` `s` ON `vs`.`studiengang_id` = `s`.`id` WHERE `s`.`fakultaet_id` = '.esc(get_get('fakultaet')).')';
					}

					if(get_get('modul')) {
						if($where) { $where .= " AND "; }
						$id = get_get('modul');
						if(is_array($id)) {
							$where .= '`v`.`id` IN (SELECT `vm`.`veranstaltung_id` FROM `view_veranstaltung_nach_modul` `vm` WHERE `vm`.`modul_id` IN ('.multiple_esc_join($id).'))';
						} else {
							$where .= '`v`.`id` IN (SELECT `vm`.`veranstaltung_id` FROM `view_veranstaltung_nach_modul` `vm` WHERE `vm`.`modul_id` = '.esc($id).')';
						}
					}

					if(get_get('gebaeude')) {
						if($where) { $where .= " AND "; }
						$id = get_get('gebaeude');
						$where .= '`v`.`gebaeude_id`  = '.esc($id);
					}

					if(is_array($this_semester)) {
						if($where) { $where .= " AND "; }
						$where .= '`v`.`semester_id` = '.esc($this_semester[0]);
					} else if ($this_semester) {
						if($where) { $where .= " AND "; }
						$where .= '`v`.`semester_id` = '.esc($this_semester);
					}

					if(get_get('studiengang') && get_get('studiengang') != 'alle') {
						if($where) { $where .= " AND "; }
						$where .= '`v`.`id` IN (SELECT `vs`.`veranstaltung_id` FROM `view_veranstaltung_nach_studiengang` `vs` LEFT JOIN `studiengang` `s` ON `vs`.`studiengang_id` = `s`.`id` WHERE `s`.`id` = '.esc(get_get('studiengang')).')';
					}

					if(get_get('pruefungstyp')) {
						if($where) { $where .= " AND "; }
						$where .= 'v.id IN (select veranstaltung_id from pruefung p join pruefungsnummer pn on pn.id = p.pruefungsnummer_id where pruefungstyp_id = '.esc(get_get('pruefungstyp')).')';
					}

					if(get_get('dozent')) {
						if($where) { $where .= " AND "; }
						if(is_array(get_get('dozent'))) {
							$where .= 'v.dozent_id IN ('.join(", ", array_map('esc', get_get('dozent'))).')';
						} else {
							$where .= 'v.dozent_id = '.esc(get_get('dozent'));
						}
					}

					if($this_institut) {
						if($where) { $where .= " AND "; }
						$where .= 'v.institut_id = '.esc($this_institut);
					}

					if($where) { $where .= " AND "; }
					$where .= '`vm`.`stunde` is not null AND `vm`.`wochentag` is not null';

					if($where) {
						$where = " AND $where";
					}

					$wochentag_abk_nach_name = create_wochentag_abk_nach_name_array();

					# 0		1		2		3		   4		5			6		7
					$query = 'select `v`.`id`, `v`.`gebaeude_id`, `v`.`raum_id`, `v`.`raummeldung`, `v`.`name`, `vm`.`wochentag`, `vm`.`anzahl_hoerer`, date_format(`vm`.`erster_termin`, "%d.%m.%Y"), '.
						#8			9		10		11			12			13		14		15		16
						'`v`.`veranstaltungstyp_id`, `vm`.`woche`, `vm`.`opal_link`, `vm`.`hinweis`, `v`.`veranstaltungstyp_id`, `v`.`dozent_id`, `vm`.`stunde`, `v`.`semester_id`, date_format(`vm`.`abgabe_pruefungsleistungen`, "%d.%m.%Y"), `v`.`master_niveau`, `vm`.`related_veranstaltung` from '.
						'veranstaltung `v` join `veranstaltung_metadaten` `vm` on `vm`.`veranstaltung_id` = `v`.`id` join `dozent` `d` on `v`.`dozent_id` = `d`.`id` WHERE `d`.`ausgeschieden` = "0" '.$where.' ORDER BY `vm`.`wochentag`, `vm`.`stunde`, `v`.`name`';
					$result = rquery($query);

					$last_wochentag = '';
					$minicache['veranstaltungstyp'] = array();

					$raum_gebaeude_array = get_raum_gebaeude_array();
					$dozent_array = get_dozent_array();
					$veranstaltungsabkuerzungen_array = get_veranstaltungsabkuerzung_array();

					$shown_no_tag = 0;

					if(get_get('erstelle_stundenplan')) {
						foreach ($_GET['modul'] as $this_modul) {
?>
								<input type="hidden" name="modul[]" value="<?php print htmlentities($this_modul); ?>" />
<?php
						}
					}

					$metadata = array();
					if(get_get('metastring')) {
						$metastring = get_get('metastring');
						$single_items = explode(",", $metastring);

						foreach ($single_items as $single_item) {
							if(preg_match('/^modul_(\d+)_veranstaltungstyp_(\d+)_anzahl_(\d+)$/', $single_item, $founds)) {
								$mod_data = $founds[1];
								$vt_typ_data = $founds[2];
								$anz_data = $founds[3];

								$metadata[$mod_data][$vt_typ_data] = $anz_data;
							}
						}
					}

					$auswaehlbare_veranstaltungen_counter = 0;
					if(mysqli_num_rows($result)) {
						if(count($metadata)) {
?>
										Es wird empfohlen, folgende Anzahl an Veranstaltungstypen zu besuchen:
										<table>
											<tr>
												<th>Veranstaltungstyp</th>
												<th>Benötigte Semesterwochenstunden (SWS, <a href="faq.php">was ist das?</a>)</th>
											</tr>
<?php
							foreach ($metadata as $this_metadata_id => $this_metadata) {
								$bgc = "background_color_add8e6";
								if(in_array($this_metadata_id, $relevante_module)) {
									$bgc = "background_color_ffa500";
								}
?>
												<tr>
													<td colspan="3" class="<?php print $bgc; ?>">Modul: <i><?php print htmle(get_modul_name($this_metadata_id)); ?></i></td>
												</tr>
<?php
								foreach ($this_metadata as $veranstaltungstyp_id => $veranstaltungen_anzahl) {
?>
													<tr>
														<td><?php print htmle(get_veranstaltungstyp_name($veranstaltungstyp_id)); ?></td>
														<td><?php print htmle($veranstaltungen_anzahl); ?></td>
													</tr>
<?php
								}
							}
?>
										</table>
<?php
						}

						$pruefungen = create_pruefungen_by_studiengang_array(get_get('studiengang'), get_get('bereich'));

						while ($row = mysqli_fetch_row($result)) {
							$id = $row[0];
							$tag = $row[5];
							$stunde = $row[14];

							$veranstaltungstyp = $veranstaltungsabkuerzungen_array[$row[8]];
							$dozent = $dozent_array[$row[13]];

							$raum_gebaeude = '';
							if(array_key_exists($row[2], $raum_gebaeude_array)) {
								$raum_gebaeude = "<span class='raumgebaeude' title='Gebäude, evtl. danach der Raum'>".$raum_gebaeude_array[$row[2]]."</span>";
							}
							if(!$raum_gebaeude) {
								$geb_abk = get_gebaeude_abkuerzung($row[1]);
								if($geb_abk) {
									$raum_gebaeude = "Raum: <a href='https://navigator.tu-dresden.de/karten/dresden/geb/".htmlentities(strtolower($geb_abk))."'>".htmlentities($geb_abk).'</a>';
								}

							}
							$woche = $row[9];
							$erster_termin = $row[7];
							$hinweis = $row[11];
							$opal = $row[10];
							$name = $row[4];
							$master_niveau = $row[17];
							$related_veranstaltung = $row[18];
?>
								<div class="autocenter">
<?php

							if(!$tag && !$shown_no_tag) {
?>
										<div class="row">
											<div class="medium-4 columns">
												<h4 class="text-left small_caps">Kein eingetragener Wochentag:</h4>
											</div>
											<hr />
										</div>
<?php
								$shown_no_tag = 1;
							} else 	if($last_wochentag != $tag) {
?>
										<div class="row">
											<div class="medium-4 columns">
												<h4 class="text-left small_caps"><?php print $wochentag_abk_nach_name[$tag]; ?>:</h4>
											</div>
											<hr />
										</div>
<?php
								$last_wochentag = $tag;
							}
?>
									<div class="row">
										<div class="medium-4 columns">
											<div class="callout">
												<div class="row">
													<div class="small-10 columns">
														<p>
<?php
							if($stunde) {
								$checked = 0;
								if(get_cookie('additiver_stundenplan')) {
									$addstd = get_cookie('additiver_stundenplan');
									$data = explode(',', $addstd);
									if(in_array($id, $data)) {
										$checked = 1;
									}
								}
?>
																<input <?php if($checked) { print "checked='checked'"; } ?> id="checkbox_veranstaltung_<?php print $id; ?>" type="checkbox" name="veranstaltung[]" value="<?php print $id; ?>" />
<?php
								$auswaehlbare_veranstaltungen_counter++;
							}
?>
															<span class="font_size_13px">
<?php

							if(preg_match('/^\d+(-\d+)?$/', $stunde)) {
?>
																<?php print htmle($stunde); ?>. DS (<?php print get_zeiten($stunde); ?><?php $sws = get_sws($stunde, $woche); if($sws[0]) { print ", Etwa* "; $shown_etwa = 1; }; if($sws[1]) { if(!$shown_etwa) { print ", "; }; print htmlentities(" ".$sws[1]." SWS"); } ?>)
																<a href="event_file.php?veranstaltung[]=<?php print $id; ?>"><?php print html_calendar(); ?></a>
<?php
							} else {
?>
	<?php print htmle($stunde); ?> (<?php
	print get_zeiten($stunde); 
$sws = get_sws($stunde, $woche);
if($sws[0]) { 
	print ", Etwa* ";
	$shown_etwa = 1;
}; 
if($sws[1]) {
	if(!$shown_etwa) {
		print ", "; 
	};
	print htmlentities($sws[1]." SWS"); 
}
?>)
<?php
							}
?>
															<?php print html_map(null, null, $row[1]); ?></a>
<?php
							if(preg_match('/(warnung|achtung|vorsicht|wichtig|wichtiger)/i', $hinweis, $founds)) {
								print "<span class='calendarlarge' alt='Im Hinweis kommt das Wort ".$founds[1]." vor'>&#x26a0;</span>";
							}
?>
															</span>
<?php
							if($veranstaltungstyp) {
								print "<span class='raumgebaeude'>";
								if($stunde) {
									print " &mdash; ";
								}
								print "$veranstaltungstyp";
								print "</span>";
							}
?>
														</p>
													</div>
													<div class="small-2 columns text-right">
													<p><?php
							if($raum_gebaeude) {
								print $raum_gebaeude;
							} else {
								if($hinweis) {
									print '<i class="font_size_10px">Kein Raum, evtl. siehe Details</i>';
								} else {
									print '<i>Kein Raum</i>';
								}
							}
?></p>
													</div>
												</div>
												<h5><?php print htmle($name); ?></h5>
												<p><i><?php print htmle($dozent); ?></i></p>
<?php
							if(get_get('erstelle_stundenplan')) {
								$this_module = array();
								$query = 'select modulname from view_veranstaltung_nach_modul where veranstaltung_id = '.esc($id);
								if(count(get_get('modul'))) {
									$query .= ' and modul_id in ('.join(", ", array_map('esc', get_get('modul'))).')';
								}
								$query .= ' group by modulname ORDER BY modulname asc';
								$tresult = rquery($query);
								while ($row = mysqli_fetch_row($tresult)) {
									$this_module[] = $row[0];
								}
								if($this_module) {
?>
														<b>Relevante Angebotene Module</b>: <?php print join(' &mdash; ', array_map('mask_module', $this_module)); ?>
<?php
								}
								$relevante_module[] = $this_modul;
							}
?>
												<div class="accordion" data-accordion data-allow-all-closed="true" data-multi-expand="true">
													<div class="accordion-item" data-accordion-item>
													<a id="toggle_details_<?php print $id; ?>" class="accordion-title">Details</a>
														<div id="details_<?php print $id; ?>">
<?php
							if($hinweis) {
?>
															<p><i>Hinweis: </i><span class="prewrap"><?php print replace_hinweis_with_graphics(htmle($hinweis)); ?></span></p>
<?php
							}

							if(isset($row[16]) && $row[16] != '00.00.0000' ) {
?>
															<p>Abgabe Prüfungsleistungen: <i><?php print htmle($row[16]); ?></i></p>
<?php
							}

							$einzelne_termine = get_einzelne_termine_by_veranstaltung_id($id);

							if(is_array($einzelne_termine) && count($einzelne_termine)) {
								print "<h4>Einzelne Termine</h4>\n";
								print "<table>\n";
								print "<tr>\n";
								print "<th>Beginn</td>\n";
								print "<th>Ende</td>\n";
								print "<th>Gebäude</td>\n";
								print "<th>Raum</td>\n";
								print "</tr>\n";
								foreach ($einzelne_termine as $einzelner_termin) {
									print "<tr>\n";
									$start_year = $einzelner_termin['start_year'];
									$start_month = $einzelner_termin['start_month'];
									$start_day = $einzelner_termin['start_day'];
									$start_hour = add_leading_zero($einzelner_termin['start_hour']);
									$start_minute = add_leading_zero($einzelner_termin['start_minute']);
									$start_dayname = weekday_to_wochentag($einzelner_termin['day_start'])[0];

									$end_year = $einzelner_termin['end_year'];
									$end_month = $einzelner_termin['end_month'];
									$end_day = $einzelner_termin['end_day'];
									$end_hour = add_leading_zero($einzelner_termin['end_hour']);
									$end_minute = add_leading_zero($einzelner_termin['end_minute']);
									$end_dayname = weekday_to_wochentag($einzelner_termin['day_end'])[0];

									$gebaeude_name = $einzelner_termin['gebaeude_name'];
									$gebaeude_abkuerzung = $einzelner_termin['gebaeude_abkuerzung'];
									$gebaeude_id = $einzelner_termin['gebaeude_id'];
									$gebaeude_link = '';

									if($gebaeude_abkuerzung) {
										$gebaeude_link = "<a href='https://navigator.tu-dresden.de/karten/dresden/geb/".
											strtolower($gebaeude_abkuerzung)."'>".$gebaeude_abkuerzung.'</a> '.html_map(null, null, $gebaeude_id);
									}
									if(!$gebaeude_link) {
										$gebaeude_link = "&mdash;";
									}
									$raumnummer = $einzelner_termin['raumnummer'];
									if(!$raumnummer) {
										$raumnummer = "&mdash;";
									}
									$raum_id = $einzelner_termin['raum_id'];

									$date_start = "$start_dayname $start_day.$start_month.$start_year $start_hour:$start_minute";
									$date_end = "$end_dayname $end_day.$end_month.$end_year $end_hour:$end_minute";
									print "<td>$date_start</td><td>$date_end</td><td>$gebaeude_link</td><td>$raumnummer</td>\n";
									print "</tr>\n";
								}
								print "</table>\n";
							}

							if($woche != 'keine Angabe') {
								$woche_anzeige = '';
								if($woche == 'jede Woche') {
									$woche_anzeige = "jede Woche";
								} else if ($woche == 'gerade Woche') {
									$woche_anzeige = 'jede gerade Woche';
								} else if ($woche == 'ungerade Woche') {
									$woche_anzeige = 'jede ungerade Woche';
								}
?>
															<p>Die Veranstaltung findet <?php print $woche_anzeige; ?> statt.</p>
<?php
							}

							if($opal) {
?>
															<p><?php print "<a href='".htmlentities($opal)."'>Link zu Opal</a>"; ?></p>
<?php
							}

							if($erster_termin) {
?>
															<p>Erster Termin: <?php print htmle($erster_termin); ?></p>
<?php
							}

							if($master_niveau) {
?>
															<p>Diese Veranstaltung hat Master-Niveau</p>
<?php
							}

							$languages_from_veranstaltung = get_language_by_veranstaltung($id);
							if($languages_from_veranstaltung) {
?>
															<p>Angeboten auf: <?php print join(', ', array_map("get_language_name", $languages_from_veranstaltung)); ?>.</p>
<?php
							}

							if($related_veranstaltung) {
?>
															<p>Zugehörige Veranstaltung: <?php print get_veranstaltung_name($related_veranstaltung); ?>.</p>
<?php
							}

							/* Tabelle für die einzelnen Prüfungsleistungen anpassen */
							if(array_key_exists($id, $pruefungen)) {
								if(count($pruefungen[$id])) {
									create_veranstaltung_pruefung_tabelle($pruefungen[$id]);
								}
							}
?>
														</div>
													</div>
<?php
							if(
								isset($GLOBALS['logged_in_user_id'])
								&&
								(
									(
										isset($GLOBALS['user_role_id']) && 
										$GLOBALS['user_role_id'] == 1
									) ||
									(
										isset($GLOBALS['user_dozent_id']) &&
										$GLOBALS['user_dozent_id'] == $row[13]
									) ||
									user_is_verwalter($GLOBALS['logged_in_user_id'])
								)
							) {
?>
													<a href="admin.php?page=<?php print htmlentities(get_page_id_by_filename('veranstaltung.php')); ?>&id=<?php print htmlentities($id); ?>"><i>Diese Veranstaltung bearbeiten</i></a>
<?php
							}
?>
											</div>
										</div>
									</div>
								</div>
							</div>
<?php
						}
?>
						<input type="hidden" value="1" name="stundenplan_to_be_created" />
						<input type="hidden" value="<?php print htmlentities(get_get('bereich')); ?>" name="bereich" />
						<input type="hidden" value="<?php print htmlentities($this_institut); ?>" name="institut" />
						<input type="hidden" value="<?php print htmlentities($this_semester[0]); ?>" name="semester" />
						<input type="hidden" value="<?php print htmlentities(get_get('semester')); ?>" name="semester" />
<?php
						if($auswaehlbare_veranstaltungen_counter) {
?>
							<input type="hidden" value="<?php print htmlentities(get_get('studiengang')); ?>" name="studiengang">
							<i class="red_text">Trotz aller Sorgfaltsmaßnahmen übernehmen wir keine Gewähr dafür, dass der Stundenplan korrekt ist. <b>Bitte konsultieren Sie immer die Prüfungsordnung Ihres Studienganges und überprüfen Sie die Angaben manuell!</b> Benutzen Sie diese Software nicht, wenn Sie nicht damit einverstanden sind!</i><br /><br />
							<input type="submit" value="Aus markierten Veranstaltungen einen Stundenplan erstellen" /><br />
							<br />
							<input name="generate_cookie_stundenplan" type="submit" value="Aus Veranstaltungen und Cookies einen Stundenplan erstellen" /><br />
							<br />
<?php
							if($shown_etwa) {
?>
								<p>* Bei Veranstaltungen, die über mehrere Stunden gehen, ist die Angabe der SWS nur eine Schätzung.</p>
<?php
							}
						}
?>
					</form>
					<button id="stundenplan_addieren">Stundenplancookies updaten</button><br />
<?php
						if(get_get('studiengang') != 'alle') {
?>
						<br />
						<a id="fuer_diesen_studiengang_pruefungen_anzeigen" href="index.php?studiengang=<?php print htmlentities(get_get('studiengang')); ?>&show_pruefungen=1&semester=<?php print htmlentities($this_semester[0]); ?>&institut=<?php print htmlentities($this_institut); ?>"><i>Die für diesen Studiengang möglichen Prüfungen anzeigen.</i></a>
<?php
						}
					} else {
?>
					<i class="red_text">Mit den gegebenen Suchkriterien konnten keine Veranstaltungen gefunden werden.</i>
<?php
					}
				}
			}
		} else if (get_get('stundenplan_to_be_created')) {
?>
		<i class="class_red">Es wurden keine Veranstaltungen ausgewählt. Bitte benutzen Sie den Zurück-Button Ihres Browsers und wählen Sie mindestens eine Veranstaltung aus, um einen Stundenplan zu erstellen.</i>
<?php
			/* Übersichtsseite aller Lehrveranstaltungen */
		} else if (get_get('alle_pruefungsnummern')) {
			$studiengaenge = create_studiengang_array_by_institut_id($this_institut);
?>
		<form>
			<input type="hidden" name="alle_pruefungsnummern" value="1" />
			<input type="hidden" name="semester" value="<?php print htmlentities($this_semester[0]); ?>" />
			<input type="hidden" name="institut" value="<?php print htmlentities($this_institut); ?>" />
			<?php create_select($studiengaenge, get_get('chosen_studiengang'), 'chosen_studiengang'); ?>
			<input type="submit" value="Studiengang auswählen" />
		</form>
		<table class="autocenter_large">
			<tr>
				<th>Prüfungsnummern</th>
			</tr>
<?php
			$last_studiengang = '';

			foreach ($studiengaenge as $this_studiengang) {
				if(!get_get('chosen_studiengang') || get_get('chosen_studiengang') == $this_studiengang[0]) {
					$modul_infos = array();
					$modul_infos_query = 'select m.studiengang_id as studiengang_id, sm.modul_id, group_concat(sm.credit_points) as credit_points, sum(sm.credit_points) as sum_credit_points, group_concat(sm.semester) as semester_concat, group_concat(anzahl_pruefungsleistungen) concat_anzahl_pruefungsleistungen, sum(anzahl_pruefungsleistungen) as sum_anzahl_pruefungsleistungen from modul_nach_semester_metadata sm join modul m on m.id = sm.modul_id where studiengang_id ='.esc($this_studiengang[0]).' group by modul_id order by semester';
					$tres = rquery($modul_infos_query);
					while ($row = mysqli_fetch_row($tres)) {
						#	0		1	2		3		4		5					6
						# studiengang_id, modul_id, credit_points, sum_credit_points, semester_concat, concat_anzahl_pruefungsleistungen, sum_anzahl_pruefungsleistungen
						$row[2] = comma_list_to_array($row[2]);
						$row[4] = comma_list_to_array($row[4]);
						$row[5] = comma_list_to_array($row[5]);
						# TODO
						$modul_infos[$row[1]] = $row;
					}

					$module = create_module_array_by_studiengang($this_studiengang[0]);
					foreach ($module as $this_modul) {

						$pruefungsnummern = create_pruefungsnummern_array_by_modul_id($this_modul[0]);
						if(count($pruefungsnummern)) {
							if($last_studiengang != $this_studiengang[1]) {
?>
								<tr>
									<td colspan="2" class="background_color_add8e6">Studiengang: <?php print htmle($this_studiengang[1]); ?></td>
								</tr>
<?php
								$last_studiengang = $this_studiengang[1];
							}
?>
							<tr>
								<th>Modul: &raquo;<i><?php print htmle($this_modul[1]); ?></i>&laquo;</th>
							</tr>
<?php
							if(array_key_exists($this_modul[0], $modul_infos) && $modul_infos[$this_modul[0]][3]) {
?>
								<tr>
									<td>
										Modulinformationen (Regelstudienzeit):
										<table>
											<tr>
												<td>Credit-Points (Summe)</td>
												<td><?php print $modul_infos[$this_modul[0]][3]; ?></td>
											</tr>
											<tr>
												<td>Anzahl Prüfungen (Summe)</td>
												<td>
													<?php print $modul_infos[$this_modul[0]][6]; ?>
													<span class="display_none" id="summe_benoetigte_pruefungen_modul_<?php print htmlentities($this_modul[0]); ?>"><?php print $modul_infos[$this_modul[0]][6]; ?></span>
												</td>
											</tr>
											<tr>
												<td>Anzahl Prüfungen (bereits absolviert)</td>
												<td><div id="absolvierte_pruefungen_modul_<?php print htmlentities($this_modul[0]); ?>">&mdash;</div></td>
											</tr>
											<tr>
												<td>Anzahl Prüfungen (geplant)</td>
												<td><div id="geplante_pruefungen_modul_<?php print htmlentities($this_modul[0]); ?>">&mdash;</div></td>
											</tr>
											<tr>
												<td>Geplante + Absolvierte Prüfungen (Gesamtfortschritt)</td>
												<td><div id="geplante_absolvierte_pruefungen_modul_<?php print htmlentities($this_modul[0]); ?>">&mdash; (&mdash;%)</div></td>
											</tr>
<?php
								$max = count($modul_infos[$this_modul[0]][4]);

								if($max != -1 && $max) {
?>
												<tr>
													<td>Credit-Points per Semester</td>
													<td><?php
									foreach (range(0, $max - 1) as $index) {
										$tstr = "Semester ".$modul_infos[$this_modul[0]][4][$index].': '.$modul_infos[$this_modul[0]][2][$index]." Credit Points<br />\n";
										print $tstr;

									}
?>
														</td>
													</tr>
<?php
								}

								$max = count($modul_infos[$this_modul[0]][5]);

								if($max != -1 && $max) {
?>
												<tr>
													<td>Prüfungen pro Semester</td>
													<td>
<?php
									foreach (range(0, $max - 1) as $index) {
										$tstr = "Semester ".$modul_infos[$this_modul[0]][4][$index].': '.$modul_infos[$this_modul[0]][5][$index]." Prüfungen<br />\n";
										print $tstr;

									}
?>
													</td>
												</tr>
<?php
								}
?>
										</table>
									</td>
								</tr>
<?php
							}
?>
							<tr>
								<td class="text_align_left">
<?php
							$has_pruefungsnummer = 0;
							foreach ($pruefungsnummern as $this_pruefungsnummer) {
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
										$has_pruefungsnummer = $this_pruefungsnummer[1];
										$str .= htmle($this_pruefungsnummer[1]);
									}
								}

								//if($this_pruefungsnummer[6] && array_search($this_pruefungsnummer[6], $GLOBALS['pruefungen_already_done']) !== false) {
								if(pruefung_already_done($this_pruefungsnummer[6])) {
									$str .= html_checked();
								}

								if(pruefung_already_chosen($this_pruefungsnummer[6])) {
									$str .= html_chosen();
								}

								$strafter = '';
								if(!is_null($this_pruefungsnummer[6])) {
									$strafter .= ' &mdash; Absolviert? <input type="checkbox" id="pruefung_already_done_'.
										htmlentities($this_pruefungsnummer[6]).'" class="pruefung_already_done" value="'.
										htmlentities($this_pruefungsnummer[6]).'" ';

									if(pruefung_already_done($this_pruefungsnummer[6])) {
										$strafter .= 'checked ';
									}
									$strafter .= '/>';

									$strafter .= ' Geplant? <input type="checkbox" id="pruefung_already_chosen_'.
										htmlentities($this_pruefungsnummer[6]).'" class="pruefung_already_chosen" value="'.
										htmlentities($this_pruefungsnummer[6]).'" ';

									if(pruefung_already_chosen($this_pruefungsnummer[6])) {
										$strafter .= 'checked ';
									}
									$strafter .= '/>';
								}
?>
									<span class="display_none" id="pn_modul_<?php print htmlentities($this_pruefungsnummer[6]); ?>"><?php print htmlentities($this_modul[0]); ?></span>
<?php

								print "<a href='index.php?semester=$this_semester[0]&institut=$this_institut&studiengang=alle&pruefungsnummer_id=$this_pruefungsnummer[0]'>$str</a>$strafter\n";
?><br />
									<div class="height_10px"></div>
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
									<i>Leider keine eingetragenen Prüfungsnummern für das Modul &raquo;<?php print htmlentities($this_modul[1]); ?>&laquo; (<?php print $this_studiengang[1]; ?>)</i>
								</td>
							</tr>
<?php

						}
					}
				}
?>
				<script type="text/javascript">update_pruefungsleistung_cookies(0);</script>
<?php
			}
?>
		</table>

		<p>Jede der Prüfungsnummern kann über die Checkbox rechts von ihr als &raquo;bereits absolviert&laquo; oder als &raquo;geplant&laquo; markiert werden.
		Dadurch taucht neben den einzelnen Prüfungsleistungen überall das Zeichen &raquo;<?php print html_checked(); ?>&laquo; (für bereits absolvierte Prüfungen)
		bzw. das Zeichen &raquo;<?php print html_chosen(); ?>&laquo; (für geplante Prüfungen) auf. Dies soll Ihnen dabei helfen, nicht aus-Versehen
		Prüfungsleistungen doppelt einzuplanen.</p>

		<p>Zur Speicherung werden Cookies benutzt. Mit dem Löschen der Cookies werden auch die ausgewählten Prüfungsleistungen
		hier (jedoch nicht im Hisqis) wieder entfernt. Es werden keine Daten gespeichert, die eine Identifikation Ihrer Person
		erlauben. Mit dem Nutzen dieser Funktion stimmen Sie dem Speichern von Cookies auf Ihrem Rechner zu.</p>

		<p class="red_text"><i>Auch hier wird keine Verantwortung für die Richtigkeit der Daten übernommen! Konsultieren Sie immer zuerst die Prüfungsordnung!</i></p>

		<button nonce=<?php print($GLOBALS['nonce']); ?> onclick="update_pruefungsleistung_cookies(1)">Bereits absolvierte Prüfungsleistungen speichern.</button>
<?php
			$absolviertepruefungsleistungen = get_cookie('absolviertepruefungsleistungen', '');
			$geplante_pruefungsleistungen = get_cookie('geplante_pruefungsleistungen', '');

			$url = 'savecookie.php?absolviertepruefungsleistungen='.urlencode($absolviertepruefungsleistungen).'&geplante_pruefungsleistungen='.urlencode($geplante_pruefungsleistungen);
?>
		<button onclick="location.href='<?php print htmlentities($url); ?>'">Cookie-URL generieren</button>
<?php
		} else {
			$rows = create_studiengaenge_mit_veranstaltungen_array($this_semester[0], isset($this_institut) ? $this_institut : null);
			if(count($rows)) {
?>
			<div class="row">
				<div class="medium-4 medium-centered columns">
				<a href="index.php?studiengang=alle&semester=<?php if(is_array($this_semester)) { print $this_semester[0]; } else { print $this_semester; }; ?>&institut=<?php print htmlentities($this_institut); ?>">
						<div id="alle_lehrveranstaltungen" class="callout alert text-center"><?php print $linkicon; ?><h4>Alle Lehrveranstaltungen</h4></div>
					</a>
				</div>
			</div>
			<div class="row">
				<div class="medium-4 medium-centered columns">
				<a href="index.php?alle_pruefungsnummern=1&semester=<?php if(is_array($this_semester)) { print $this_semester[0]; } else { print $this_semester; }; ?>&institut=<?php print htmlentities($this_institut); ?>">
						<div id="alle_pruefungsleistungen_anzeigen" class="callout allepls text-center"><?php print $linkicon; ?><h4>Alle Prüfungsleistungen anzeigen</h4></div>
					</a>
				</div>
			</div>
<?php
		} else {
?>
			<i class="class_red">In diesem Semester existieren noch keine Veranstaltungen.</i><br />
<?php
		}
		$studiengang_counter = 0;
		foreach ($rows as $this_studiengang_id => $this_studiengang_name) {
			$divid = '';
			if($studiengang_counter == 0) {
				$divid = "naechster_studiengang";
			}
?>
			<div class="row">
				<div <?php print $divid ? "id='$divid'" : ''; ?> class="medium-4 medium-centered columns text-center">
					<a href="index.php?studiengang=<?php print $this_studiengang_id; ?>&semester=<?php print htmlentities($this_semester[0]); ?>&institut=<?php print htmlentities($this_institut); ?>">
						<div class="callout primary"><?php print $linkicon; ?><h4><?php
							if($this_studiengang_name[1] == "Werkstatt Philosophie") {
								print "&#128295; ";
							}
							print htmlentities($this_studiengang_name[1]);
						?></h4><?php
							if(array_key_exists(2, $this_studiengang_name)) {
?>
								<h5><?php print htmlentities($this_studiengang_name[2]); ?></h5><?php
							}
?></div>
					</a>
				</div>
			</div>
<?php
			$studiengang_counter++;
		}

	}
	include("footer.php");
?>
