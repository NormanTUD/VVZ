<?php
	include_once("config.php");

	function einzelne_termine ($id) {
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
					$gebaeude_link = "<a href='".$GLOBALS['navigator_base_url'].
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
	}

	function studienordnung_link () {
		$studienordnung_url = get_studienordnung_url(get_get('studiengang'));

		if ($studienordnung_url) {
?>
			&mdash; <a href="<?php print $studienordnung_url; ?>" id="studienordnung_link" >&#128196;&nbsp;Studienordnung</a>
<?php
		}
	}

	function stundenplanerstellung_link () {
		if(studiengang_has_semester_modul_data(get_get('studiengang'))) {
?>
			&mdash; <a id="create_stundenplan_link" href="startseite?create_stundenplan=1<?php
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
	}

	function show_filter_veranstaltungstypen () {
?>
		<select name="veranstaltungstyp_id">
			<option value="">Alle</option>
<?php
			$query = 'SELECT `id`, concat(`abkuerzung`, " (", `name`, ")") FROM `veranstaltungstyp` WHERE `id` IN (SELECT `veranstaltungstyp_id` FROM `veranstaltung` WHERE `institut_id` = '.esc($GLOBALS['this_institut']).')';
			$result = rquery($query);

			while ($row = mysqli_fetch_row($result)) {
				$selected_str = $row[0] == get_get('veranstaltungstyp_id') ? 'selected' : '';
				if(strlen($row[1]) >= 4) { # " ()" steht da, wenn kein Typ gefunden wurde
?>
					<option value="<?php print $row[0]; ?>" <?php print $selected_str; ?>><?php print htmlentities($row[1]); ?></option>
<?php
				} else {
?>
					<option value="<?php print $row[0]; ?>" <?php print $selected_str; ?>>Kein Veranstaltungstyp</option>
<?php
				}
			}
?>
		</select>
<?php
	}

	function show_filter_pruefungstyp () {
?>
		<select name="pruefungstyp">
			<option value="">Alle</option>
<?php
			$query = 'select pt.id, pt.name from pruefung p join pruefungsnummer pn on p.pruefungsnummer_id = pn.id join pruefungstyp pt on pt.id = pn.pruefungstyp_id where veranstaltung_id in (select id from veranstaltung where institut_id = '.esc($GLOBALS['this_institut']).') group by pt.id order by pt.name';
			$result = rquery($query);

			while ($row = mysqli_fetch_row($result)) {
?>
				<option value="<?php print $row[0]; ?>" <?php print $row[0] == get_get('pruefungstyp') ? 'selected' : ''; ?>><?php print htmlentities($row[1]); ?></option>
<?php
		}
?>
		</select>
<?php
	}

	function show_filter_modul () {
?>
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
<?php
	}

	function show_filter_studiengang () {
?>
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
<?php
	}

	function show_filter_semester () {
?>
		<select name="semester">
<?php
			$query = 'select id, concat(typ, " ", jahr) from semester where id in (select semester_id from veranstaltung) order by jahr asc, typ asc';
			$result = rquery($query);

			if(mysqli_num_rows($result) > 1) {
?>
				<option value="<?php print htmlentities($GLOBALS['this_semester'][0]); ?>">Dieses Semester</option>
<?php
			}

			while ($row = mysqli_fetch_row($result)) {
?>
					<option value="<?php print $row[0]; ?>" <?php print $row[0] == $GLOBALS['this_semester'][0] ? 'selected' : ''; ?>><?php print htmlentities($row[1]); ?></option>
<?php
			}
?>
		</select>
<?php
	}

	function show_filter_wochentag () {
?>
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
<?php
	}

	function show_filter_stunde () {
?>
		<select name="stunde">
		<option value="">Alle</option>
<?php
			$stundenquery = 'SELECT `stunde` FROM `veranstaltung_metadaten` `vm` JOIN `veranstaltung` `v` ON `v`.`id` = `vm`.`veranstaltung_id` WHERE `v`.`institut_id` = '.esc($GLOBALS['this_institut']).' GROUP BY `stunde`';
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
<?php
	}

	function show_filter_dozent () {
?>
		<select name="dozent">
			<option value="">Alle</option>
<?php
			$query = 'SELECT `id`, concat(`first_name`, " ", `last_name`) FROM `dozent` WHERE `id` IN (SELECT `dozent_id` FROM `veranstaltung` WHERE `institut_id` = '.esc($GLOBALS['this_institut']).')';
			$result = rquery($query);

			while ($row = mysqli_fetch_row($result)) {
?>
				<option value="<?php print $row[0]; ?>" <?php print $row[0] == get_get('dozent') ? 'selected' : ''; ?>><?php print htmlentities($row[1]); ?></option>
<?php
			}
?>
		</select>
<?php
	}

	function show_filter_gebaeude () {
?>
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
<?php
	}

	function show_filter () {
?>
		<form id="filter">
			<table>
				<tr>
					<td>
						Semester:
					</td>
					<td>
						<?php show_filter_semester($GLOBALS['this_semester']); ?>
					</td>
				</tr>
				<tr>
					<td>
						Vorlesungsname enthält im Titel:
					</td>
					<td>
						<input label="Vorlesungsname enthält im Titel" type="text" name="vltitel" value="<?php print htmlentities(get_get('vltitel')); ?>" />
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
							<?php show_filter_modul(); ?>
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
							<?php show_filter_studiengang(); ?>
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
						<?php show_filter_veranstaltungstypen($GLOBALS['this_institut']); ?>
					</td>
				</tr>
				<tr>
					<td>
						Wochentag:
					</td>
					<td>
						<?php show_filter_wochentag(); ?>
					</td>
				</tr>
				<tr>
					<td>
						Stunde:
					</td>
					<td>
						<?php show_filter_stunde($GLOBALS['this_institut']); ?>
					</td>
				</tr>
				<tr>
					<td>
						Dozent:
					</td>
					<td>
						<?php show_filter_dozent($GLOBALS['this_institut']); ?>
					</td>
				</tr>
				<tr>
					<td>
						Gebäude:
					</td>
					<td>
						<?php show_filter_gebaeude(); ?>
					</td>
				</tr>
				<tr>
					<td>
						Prüfungstyp:
					</td>
					<td>
						<?php show_filter_pruefungstyp($GLOBALS['this_institut']); ?>
					</td>
				</tr>
				<tr>
					<td>OPAL-Seite vorhanden?</td>
					<td><input label="OPAL-Seite vorhanden?" type="checkbox" value="1" name="opal_zwingend" <?php print htmlentities(get_get('opal_zwingend')) ? 'checked="CHECKED"' : '' ?> /></td>
				</tr>
				<tr>
					<td></td>
					<td><input type="submit" value="Filtern" /></td>
				</tr>
			</table>

		</form>
<?php
	}

	function build_query_startseite () {
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

		if(is_array($GLOBALS['this_semester'])) {
			if($where) { $where .= " AND "; }
			$where .= '`v`.`semester_id` = '.esc($GLOBALS['this_semester'][0]);
		} else if ($GLOBALS['this_semester']) {
			if($where) { $where .= " AND "; }
			$where .= '`v`.`semester_id` = '.esc($GLOBALS['this_semester']);
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

		if($GLOBALS['this_institut']) {
			if($where) { $where .= " AND "; }
			$where .= 'v.institut_id = '.esc($GLOBALS['this_institut']);
		}

		if($where) { $where .= " AND "; }
		$where .= '`vm`.`stunde` is not null AND `vm`.`wochentag` is not null';

		if($where) {
			$where = " AND $where";
		}

		# 0		1		2		3		   4		5			6		7
		$query = 'select `v`.`id`, `v`.`gebaeude_id`, `v`.`raum_id`, `v`.`raummeldung`, `v`.`name`, `vm`.`wochentag`, `vm`.`anzahl_hoerer`, date_format(`vm`.`erster_termin`, "%d.%m.%Y"), '.
			#8			9		10		11			12			13		14		15		16
			'`v`.`veranstaltungstyp_id`, `vm`.`woche`, `vm`.`opal_link`, `vm`.`hinweis`, `v`.`veranstaltungstyp_id`, `v`.`dozent_id`, `vm`.`stunde`, `v`.`semester_id`, date_format(`vm`.`abgabe_pruefungsleistungen`, "%d.%m.%Y"), `v`.`master_niveau`, `vm`.`related_veranstaltung` from '.
			'veranstaltung `v` join `veranstaltung_metadaten` `vm` on `vm`.`veranstaltung_id` = `v`.`id` join `dozent` `d` on `v`.`dozent_id` = `d`.`id` WHERE `d`.`ausgeschieden` = "0" '.$where.' ORDER BY `vm`.`wochentag`, `vm`.`stunde`, `v`.`name`';

		return $query;
	}

	function show_metadata ($relevante_module) {
		if($GLOBALS['metadata_shown']) {
			return;
		}

		$GLOBALS['metadata_shown'] = 1;

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
	}

	function get_raum_gebaeude_startseite ($row, $raum_gebaeude_array) {
		$raum_gebaeude = '';
		if(array_key_exists($row[2], $raum_gebaeude_array)) {
			$raum_gebaeude = "<span class='raumgebaeude' title='Gebäude, evtl. danach der Raum'>".$raum_gebaeude_array[$row[2]]."</span>";
		}
		if(!$raum_gebaeude) {
			$geb_abk = get_gebaeude_abkuerzung($row[1]);
			if($geb_abk) {
				$raum_gebaeude = "Raum: <a href='".$GLOBALS['navigator_base_url'].htmlentities(strtolower($geb_abk))."'>".htmlentities($geb_abk).'</a>';
			}

		}
		return $raum_gebaeude;
	}

	function show_wochentag ($tag, $wochentag_abk_nach_name) {
		if(!$tag && !$GLOBALS['shown_no_tag']) {
?>
			<div class="row">
				<div class="medium-4 columns">
					<h4 class="text-left small_caps">Kein eingetragener Wochentag:</h4>
				</div>
				<hr />
			</div>
<?php
			$GLOBALS['shown_no_tag'] = 1;
		} else 	if($GLOBALS['last_wochentag'] != $tag) {
?>
			<div class="row">
				<div class="medium-4 columns">
					<h4 class="text-left small_caps"><?php print $wochentag_abk_nach_name[$tag]; ?>:</h4>
				</div>
				<hr />
			</div>
<?php
			$GLOBALS['last_wochentag'] = $tag;
		}

		$GLOBALS['last_wochentag'];
	}

	function show_hinweis ($hinweis) {
		if($hinweis) {
?>
			<p><i>Hinweis: </i><span class="prewrap"><?php print replace_hinweis_with_graphics(htmle($hinweis)); ?></span></p>
<?php
		}
	}

	function show_abgabe_pruefungsleistungen ($abgabe_pruefungsleistungen) {
		if(isset($abgabe_pruefungsleistungen) && $abgabe_pruefungsleistungen != '00.00.0000' ) {
?>
			<p>Abgabe Prüfungsleistungen: <i><?php print htmle($abgabe_pruefungsleistungen); ?></i></p>
<?php
		}
	}

	function show_woche ($woche) {

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
	}

	function show_opal ($opal) {
		if($opal) {
?>
			<p><?php print "<a href='".htmlentities($opal)."'>Link zu Opal</a>"; ?></p>
<?php
		}
	}

	function show_master_niveau ($master_niveau) {
		if($master_niveau) {
?>
			<p>Diese Veranstaltung hat Master-Niveau</p>
<?php
		}
	}

	function show_erster_termin ($erster_termin) {
		if($erster_termin) {
?>
			<p>Erster Termin: <?php print htmle($erster_termin); ?></p>
<?php
		}
	}

	function show_angebotene_sprachen ($id) {
		$languages_from_veranstaltung = get_language_by_veranstaltung($id);
		if($languages_from_veranstaltung) {
?>
			<p>Angeboten auf: <?php print join(', ', array_map("get_language_name", $languages_from_veranstaltung)); ?>.</p>
<?php
		}
	}

	function show_related_veranstaltung ($related_veranstaltung) {
		if($related_veranstaltung) {
?>
			<p>Zugehörige Veranstaltung: <?php print get_veranstaltung_name($related_veranstaltung); ?>.</p>
<?php
		}
	}

	function warn_if_attention_match ($hinweis) {
		if(preg_match('/(warnung|achtung|vorsicht)/i', $hinweis, $founds)) {
			print "<span class='calendarlarge' alt='Im Hinweis kommt das Wort ".$founds[1]." vor'>&#x26a0;</span>";
		}
	}

	function show_raum_gebaeude ($raum_gebaeude, $hinweis, $id) {
?>
		<div class="small-3 columns text-right"><p>
<?php
			if($raum_gebaeude) {
				print $raum_gebaeude;
			} else {
				if($hinweis) {
					print '<i class="font_size_10px">Kein&nbsp;Raum, evtl. siehe&nbsp;Details</i>';
				} else {
					print '<i>Kein Raum</i>';
				}
			}
			print video_conference_link($id);
?>
		</p></div>
<?php
	}

	function show_veranstaltungstyp_mdash_if_stunde ($veranstaltungstyp, $stunde) {
		if($veranstaltungstyp) {
			print "<span class='raumgebaeude'>";
			if($stunde) {
				print " &mdash; ";
			}
			print "$veranstaltungstyp";
			print "</span>";
		}
	}

	function show_sws ($stunde, $woche) {
				$sws = get_sws($stunde, $woche);
				if($sws[0]) { 
					print ", Etwa* ";
					$GLOBALS['shown_etwa'] = 1;
				}
				if($sws[1]) {
					if(!$GLOBALS['shown_etwa']) {
						print ", "; 
					};
					print htmlentities($sws[1]." SWS"); 
				}
	}

	function show_stunde_header ($id, $stunde, $woche) {
		if(preg_match('/^\d+(-\d+)?$/', $stunde)) {
?>
			<?php print htmle($stunde); ?>. DS (<?php print get_zeiten($stunde); ?><?php 
				show_sws($stunde, $woche);
			?>)
			<a href="event_file.php?veranstaltung[]=<?php print $id; ?>"><?php print html_calendar(); ?></a>
<?php
		} else {
?>
			<?php print htmle($stunde); ?> (<?php
				print get_zeiten($stunde); 
				show_sws($stunde, $woche);
			?>)
<?php
		}
	}

	function show_veranstaltungsbox_header($id, $stunde, $woche, $row, $hinweis) {
?>
		<span class="font_size_13px">
<?php
			$GLOBALS['shown_etwa'] = show_stunde_header($id, $stunde, $woche);
?>
			<?php print html_map(null, null, $row[1]); ?></a>
<?php
			warn_if_attention_match($hinweis);
?>
		</span>
<?php
	}

	function show_veranstaltungsbox_full_header ($id, $stunde, $woche, $row, $hinweis, $veranstaltungstyp, $raum_gebaeude) {
?>
		<div class="row">
			<div class="small-9 columns">
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
						<input <?php if($checked) { print "checked='checked'"; } ?> aria-labelledby="title_vorlesung_id_<?php print $id; ?>" id="checkbox_veranstaltung_<?php print $id; ?>" type="checkbox" name="veranstaltung[]" value="<?php print $id; ?>" />
<?php
						$GLOBALS['auswaehlbare_veranstaltungen_counter']++;
					}
					$GLOBALS['shown_etwa'] = show_veranstaltungsbox_header($id, $stunde, $woche, $row, $hinweis);

					show_veranstaltungstyp_mdash_if_stunde($veranstaltungstyp, $stunde)
?>
					</p>
			</div>
<?php
			show_raum_gebaeude($raum_gebaeude, $hinweis, $id);
?>
		</div>
<?php
	}

	function print_title_dozent ($id, $name, $dozent) {
?>
		<h5 id="title_vorlesung_id_<?php print $id; ?>"><?php print htmle($name); ?></h5>
		<p><i><?php print htmle($dozent); ?></i></p>
<?php
	}

	function show_relevante_module_fuer_stundenplanerstellung ($id) {
		$relevante_module = array();
		$string = '';
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
				$string = "<b>Relevante Angebotene Module</b>: ".join(' &mdash; ', array_map('mask_module', $this_module));
			}
			$relevante_module[] = $this_module;
		}
		return array($relevante_module, $string);
	}

	function show_pruefungen_tabelle ($id, $pruefungen) {
		if(array_key_exists($id, $pruefungen)) {
			if(count($pruefungen[$id])) {
				create_veranstaltung_pruefung_tabelle($pruefungen[$id]);
			}
		}
	}

	function show_veranstaltung_bearbeiten ($row, $id) {
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
	}

	function show_veranstaltung_box ($id, $name, $abgabe_pruefungsleistungen, $related_veranstaltung, $stunde, $woche, $row, $hinweis, $veranstaltungstyp, $raum_gebaeude, $dozent, $relevante_module_string, $master_niveau, $erster_termin, $opal, $pruefungen) {
?>
		<div class="row">
			<div class="medium-4">
				<div class="callout">
<?php
					show_veranstaltungsbox_full_header($id, $stunde, $woche, $row, $hinweis, $veranstaltungstyp, $raum_gebaeude);
					print_title_dozent($id, $name, $dozent);
					print $relevante_module_string;
?>
					<div class="accordion" data-accordion data-allow-all-closed="true" data-multi-expand="true">
						<div class="accordion-item" data-accordion-item>
							<a tabindex="0" id="toggle_details_<?php print $id; ?>" class="accordion-title">Details</a>
							<div id="details_<?php print $id; ?>">
<?php
							show_hinweis($hinweis);
							show_abgabe_pruefungsleistungen($abgabe_pruefungsleistungen);
							einzelne_termine($id);
							show_woche($woche);
							show_opal($opal);
							show_erster_termin($erster_termin);
							show_master_niveau($master_niveau);
							show_angebotene_sprachen($id);
							show_related_veranstaltung($related_veranstaltung);
							show_pruefungen_tabelle($id, $pruefungen);
?>
							</div>
						</div>
					</div>
<?php
					show_veranstaltung_bearbeiten($row, $id);
?>
				</div>
			</div>
		</div>
<?php
	}

	function create_veranstaltung_box_from_row ($row, $veranstaltungsabkuerzungen_array, $raum_gebaeude_array, $dozent_array, $wochentag_abk_nach_name, $pruefungen) {
		$id = $row[0];
		$tag = $row[5];
		$abgabe_pruefungsleistungen = $row[16];
		$stunde = $row[14];

		$veranstaltungstyp = $veranstaltungsabkuerzungen_array[$row[8]];
		$dozent = $dozent_array[$row[13]];

		$raum_gebaeude = get_raum_gebaeude_startseite($row, $raum_gebaeude_array);

		$woche = $row[9];
		$erster_termin = $row[7];
		$hinweis = $row[11];
		$opal = $row[10];
		$name = $row[4];
		$master_niveau = $row[17];
		$related_veranstaltung = $row[18];

		$relevante_module_and_string = show_relevante_module_fuer_stundenplanerstellung($id);
		$relevante_module = $relevante_module_and_string[0];
		$relevante_module_string = $relevante_module_and_string[1];

		show_metadata($relevante_module);
?>
		<div class="autocenter">
<?php
			show_wochentag($tag, $wochentag_abk_nach_name);

			show_veranstaltung_box($id, $name, $abgabe_pruefungsleistungen, $related_veranstaltung, $stunde, $woche, $row, $hinweis, $veranstaltungstyp, $raum_gebaeude, $dozent, $relevante_module_string, $master_niveau, $erster_termin, $opal, $pruefungen);
?>
		</div>
<?php
	}

	function show_link_alle_pruefungen_for_studiengang () {
		if(get_get('studiengang') != 'alle') {
?>
			<br />
			<a id="fuer_diesen_studiengang_pruefungen_anzeigen" href="startseite?studiengang=<?php print htmlentities(get_get('studiengang')); ?>&show_pruefungen=1&semester=<?php print htmlentities($GLOBALS['this_semester'][0]); ?>&institut=<?php print htmlentities($GLOBALS['this_institut']); ?>"><i>Die für diesen Studiengang möglichen Prüfungen anzeigen.</i></a>
<?php
		}
	}

	function show_auswaehlbare_veranstaltungen_stuff () {
		if($GLOBALS['auswaehlbare_veranstaltungen_counter']) {
?>
			<input type="hidden" value="<?php print htmlentities(get_get('studiengang')); ?>" name="studiengang">
			<center><div style="width: 500px"><i class="red_text">Trotz aller Sorgfaltsmaßnahmen übernehmen wir keine Gewähr dafür, dass der Stundenplan korrekt ist. <b>Bitte konsultieren Sie immer die Prüfungsordnung Ihres Studienganges und überprüfen Sie die Angaben manuell!</b> Benutzen Sie diese Software nicht, wenn Sie nicht damit einverstanden sind!</i></div></center>
			<input type="submit" value="Aus markierten Veranstaltungen einen Stundenplan erstellen" /><br />
			<br />
			<input name="generate_cookie_stundenplan" type="submit" value="Aus Veranstaltungen und Cookies einen Stundenplan erstellen" /><br />
			<br />
<?php
			if($GLOBALS['shown_etwa']) {
?>
				<p>* Bei Veranstaltungen, die über mehrere Stunden gehen, ist die Angabe der SWS nur eine Schätzung.</p>
<?php
			}
		}
	}

	function console_browser_stuff() {
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
	}

	function generate_stundenplan() {
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
				<input type="submit" value="Auswählen" />
			</form>
<?php
			$generierter_stundenplan = create_stundenplan(get_get('veranstaltung'), 1, 1, get_get('bereich'), 0, get_get('studiengang'), get_get('dozent'), get_get('semester'));
			print $generierter_stundenplan[0];
?>
		</div>
<?php
	}

	function show_pruefungen_fuer_studiengang () {
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
	}

	function show_veranstaltungsuebersicht_header () {
?>
		<div class="font20px">
			<a id="filter_toggle">&#128269;&nbsp;Filter</a> &mdash;
			<a id="toggle_all_details">&#8650;&nbsp;Details anzeigen/ausblenden</a>
<?php
			stundenplanerstellung_link();
			studienordnung_link();
?>
		</div>
<?php
	}

	function show_gesamte_creditanzahl () {
		$summen = get_sum_credit_points_anzahl_pruefungsleistungen_for_studiengang(get_get('studiengang'));
		print "Anzahl Credit-Points im gesamten Studiengang: $summen[0], Anzahl Prüfungen im gesamten Studium: $summen[1]\n";
	}

	function show_anzahl_credit_points_pruefungen ($row) {
		$metadata = get_credit_points_and_anzahl_pruefungsleistungen_by_modul_id_and_semester($row[1], get_get('chosen_semester'));
		if(count($metadata)) {
			if($metadata[0][0]) {
				print $metadata[0][0].' Credit-Point(s) werden durch das erfolgreichreiche Ablegen der Prüfungen erworben<br />';
			}

			if($metadata[0][1]) {
				print $metadata[0][1].' Prüfungsleistung(en) sollen abgelegt werden<br />';
			}
		}
	}

	function show_vorgeschriebene_veranstaltungen ($row, $metadaten) {
		$metadaten = array();
		$veranstaltungstypen_liste = get_veranstaltungstypen_modul_semester($row[1], get_get('chosen_semester'));
		if(count($veranstaltungstypen_liste)) {
			print "Folgende Veranstaltungstypen sind laut der Studienordnung in diesem Semester in diesem Modul so viele Semesterwochenstunden zu besuchen: <br />";
			foreach ($veranstaltungstypen_liste as $anzahl_data) {
				print "$anzahl_data[0] &mdash; $anzahl_data[1]<br />";
				# modul		typ	      =     anzahl
				$metadaten[$row[1]][$anzahl_data[2]] = $anzahl_data[1];
			}
		}
		return $metadaten;
	}

	function show_eingeplante_module ($result) {
		print "Folgende Module sind für das ausgewählte Semester eingeplant: ";
		$module = array();
		$metadaten = array();
		while ($row = mysqli_fetch_row($result)) {
			$module[] = $row[1];
?>
			<div class="row">
				<div class="medium-4 medium-centered columns text-center">
					<div class="callout primary">
						<h4><?php print $row[0]; ?></h4>
<?php
						$metadaten = show_vorgeschriebene_veranstaltungen($row, $metadaten);

						show_anzahl_credit_points_pruefungen($row);
?>
					</div>
				</div>
			</div>
<?php
		}
		return array($module, $metadaten);
	}

	function show_form_planung ($metadaten, $module) {
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
	}

	function show_studiengang_semester_ueberblick () {
		$query = 'select name, modul_id from view_modul_semester where studiengang_id = '.esc(get_get('studiengang')).' and semester = '.esc(get_get('chosen_semester'));
		$result = rquery($query);

		$metadaten = array();

		if(mysqli_num_rows($result)) {
			$module_and_metadaten = show_eingeplante_module($result);
			$module = $module_and_metadaten[0];
			$metadaten = $module_and_metadaten[1];

			show_gesamte_creditanzahl();
			show_form_planung($metadaten, $module);
		} else {
?>
			Leider stehen für dieses Semester keine Daten zur Verfügung.<br />
<?php
		}
	}

	function chose_semester () {
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
	}

	function chose_studiengang () {
?>
		Studiengang:
<?php
		foreach (create_studiengang_array_with_semester_data() as $this_studiengang) {
?>
			<div class="row">
				<div class="medium-4 medium-centered columns text-center">
					<a href="startseite?create_stundenplan=1&studiengang=<?php print $this_studiengang[0]; ?>">
						<div class="callout primary">
							<?php print $GLOBALS['linkicon']; ?>
							<h4><?php print $this_studiengang[1]; ?></h4>
						</div>
					</a>
				</div>
			</div>
<?php
		}
?>
		Sollten hier Studiengänge fehlen liegt das daran, dass die Administratoren die dazu notwendigen Informationen noch nicht eingetragen haben.
		<i class="red_text">Achtung: Bitte überprüfen Sie diesen Stundenplan und vertrauen Sie nicht blind auf die Software! Wie bei jeder Software können auch hier Fehler geschehen!</i>
<?php
	}

	function keine_eingetragene_pn ($this_modul, $this_studiengang) {
?>
		<tr>
			<td colspan="2">
				<i>Leider keine eingetragenen Prüfungsnummern für das Modul &raquo;<?php print htmlentities($this_modul[1]); ?>&laquo; (<?php print $this_studiengang[1]; ?>)</i>
			</td>
		</tr>
<?php
	}

	function pruefung_already_done_string ($this_pruefungsnummer) {
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
		return $strafter;
	}

	function get_str_pruefungen ($this_pruefungsnummer) {
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

		if(pruefung_already_done($this_pruefungsnummer[6])) {
			$str .= html_checked();
		}

		if(pruefung_already_chosen($this_pruefungsnummer[6])) {
			$str .= html_chosen();
		}
		return $str;
	}

	function show_regelstudienzeitinfo_tabelle_content ($modul_infos, $this_modul) {
?>
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
				<td>
<?php
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

	}

	function show_regelstudienzeitinfo_tabelle ($this_modul, $modul_infos) {
		if(array_key_exists($this_modul[0], $modul_infos) && $modul_infos[$this_modul[0]][3]) {
?>
			<tr>
				<td>
					Modulinformationen (Regelstudienzeit):
					<table>
<?php
					show_regelstudienzeitinfo_tabelle_content($modul_infos, $this_modul);
?>
					</table>
				</td>
			</tr>
<?php
		}
	}

	function get_modul_infos ($this_studiengang) {
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
		return $modul_infos;
	}

	function show_pruefungsnummern_from_studiengang($pruefungsnummern, $this_modul, $this_studiengang) {
?>
		<tr>
			<td class="text_align_left">
<?php
				foreach ($pruefungsnummern as $this_pruefungsnummer) {
					$str_pruefungen = get_str_pruefungen($this_pruefungsnummer);

					$pruefung_already_done_string = pruefung_already_done_string($this_pruefungsnummer);
?>
					<span class="display_none" id="pn_modul_<?php print htmlentities($this_pruefungsnummer[6]); ?>"><?php print htmlentities($this_modul[0]); ?></span>
<?php
					print "<a href='startseite?semester=".$GLOBALS['this_semester'][0]."&institut=".$GLOBALS['this_institut']."&studiengang=alle&pruefungsnummer_id=$this_pruefungsnummer[0]'>$str_pruefungen</a>$pruefung_already_done_string\n";
?><br />
					<div class="height_10px"></div>
<?php
				}
?>
			</td>
		</tr>
<?php
	}

	function show_alle_pruefungsnummern_daten () {
		$studiengaenge = create_studiengang_array_by_institut_id($GLOBALS['this_institut']);
?>
		<form>
			<input type="hidden" name="alle_pruefungsnummern" value="1" />
			<input type="hidden" name="semester" value="<?php print htmlentities($GLOBALS['this_semester'][0]); ?>" />
			<input type="hidden" name="institut" value="<?php print htmlentities($GLOBALS['this_institut']); ?>" />
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
					$modul_infos = get_modul_infos($this_studiengang);
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
							show_regelstudienzeitinfo_tabelle($this_modul, $modul_infos);

							show_pruefungsnummern_from_studiengang($pruefungsnummern, $this_modul, $this_studiengang, $GLOBALS['this_semester'], $GLOBALS['this_institut']);
?>

<?php
						} else {
							keine_eingetragene_pn($this_modul, $this_studiengang);
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
	}

	function show_institute_selector() {
		if(!count($GLOBALS['institute'])) {
			print "<h2 class='class_red'>Es konnten keine Institute gefunden werden. Bitten Sie die Administratoren darum, dies zu erledigen.</h2>";
		}

		if(count($GLOBALS['institute']) >= 2) {
?>
			<form method="get">
				<p class="text-center"><?php create_select($GLOBALS['institute'], $GLOBALS['this_institut'], 'institut'); ?></p>
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
	}

	function show_semester_selector () {
		$semester_array = create_semester_array(1, 0, array(get_get('semester'), $GLOBALS['this_semester'][0]));
		if(count($semester_array) >= 2) {
?>
			<form method="get">
				<p class="text-center"><?php create_select($semester_array, $GLOBALS['this_semester'][0], 'semester', 0, 0, "semester-input"); ?></p>
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
				<input type="submit" id="semester-input" value="Semester auswählen" />
			</form>
<?php
		}
	}

	function show_veranstaltungen_uebersicht () {
		show_filter($GLOBALS['this_institut'], $GLOBALS['this_semester']);
?>
		<form method="get" action="startseite">
<?php
			$wochentag_abk_nach_name = create_wochentag_abk_nach_name_array();

			$query = build_query_startseite($GLOBALS['this_institut'], $GLOBALS['this_semester']);
			$result = rquery($query);

			$GLOBALS['last_wochentag'] = '';
			$minicache['veranstaltungstyp'] = array();

			$raum_gebaeude_array = get_raum_gebaeude_array();
			$dozent_array = get_dozent_array();
			$veranstaltungsabkuerzungen_array = get_veranstaltungsabkuerzung_array();

			$GLOBALS['shown_no_tag'] = 0;

			if(get_get('erstelle_stundenplan')) {
				foreach ($_GET['modul'] as $this_modul) {
?>
						<input type="hidden" name="modul[]" value="<?php print htmlentities($this_modul); ?>" />
<?php
				}
			}

			$GLOBALS['auswaehlbare_veranstaltungen_counter'] = 0;
			if(mysqli_num_rows($result)) {
				$pruefungen = create_pruefungen_by_studiengang_array(get_get('studiengang'), get_get('bereich'));

				while ($row = mysqli_fetch_row($result)) {
					create_veranstaltung_box_from_row($row, $veranstaltungsabkuerzungen_array, $raum_gebaeude_array, $dozent_array, $wochentag_abk_nach_name, $pruefungen);
				}
?>
				<input type="hidden" value="1" name="stundenplan_to_be_created" />
				<input type="hidden" value="<?php print htmlentities(get_get('bereich')); ?>" name="bereich" />
				<input type="hidden" value="<?php print htmlentities($GLOBALS['this_institut']); ?>" name="institut" />
				<input type="hidden" value="<?php print htmlentities($GLOBALS['this_semester'][0]); ?>" name="semester" />
				<input type="hidden" value="<?php print htmlentities(get_get('semester')); ?>" name="semester" />
<?php
				show_auswaehlbare_veranstaltungen_stuff();
?>
				</form>
				<button id="stundenplan_addieren">Stundenplancookies updaten</button><br />
<?php
				show_link_alle_pruefungen_for_studiengang($GLOBALS['this_institut'], $GLOBALS['this_semester']);
			} else {
?>
				<i class="red_text">Mit den gegebenen Suchkriterien konnten keine Veranstaltungen gefunden werden.</i>
<?php
			}
?>
		</form>
<?php
	}

	function show_studiengaenge_uebersicht () {
		$width_determining_class = "medium-6";
		$rows = create_studiengaenge_mit_veranstaltungen_array($GLOBALS['this_semester'][0], isset($GLOBALS['this_institut']) ? $GLOBALS['this_institut'] : null);
		if(count($rows)) {
?>
			<div class="row">
				<div class="<?php print $width_determining_class; ?> medium-centered columns">
				<a href="startseite?studiengang=alle&semester=<?php if(is_array($GLOBALS['this_semester'])) { print $GLOBALS['this_semester'][0]; } else { print $GLOBALS['this_semester']; }; ?>&institut=<?php print htmlentities($GLOBALS['this_institut']); ?>">
						<div id="alle_lehrveranstaltungen" class="callout alert text-center"><?php print $GLOBALS['linkicon']; ?><h4>Alle Lehrveranstaltungen</h4></div>
					</a>
				</div>
			</div>
			<div class="row">
				<div class="<?php print $width_determining_class; ?> medium-centered columns">
				<a href="startseite?alle_pruefungsnummern=1&semester=<?php if(is_array($GLOBALS['this_semester'])) { print $GLOBALS['this_semester'][0]; } else { print $GLOBALS['this_semester']; }; ?>&institut=<?php print htmlentities($GLOBALS['this_institut']); ?>">
						<div id="alle_pruefungsleistungen_anzeigen" class="callout allepls text-center"><?php print $GLOBALS['linkicon']; ?><h4>Alle Prüfungsleistungen anzeigen</h4></div>
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
				<div <?php print $divid ? "id='$divid'" : ''; ?> class="<?php print $width_determining_class; ?> medium-centered columns text-center">
					<a href="startseite?studiengang=<?php print $this_studiengang_id; ?>&semester=<?php print htmlentities($GLOBALS['this_semester'][0]); ?>&institut=<?php print htmlentities($GLOBALS['this_institut']); ?>">
						<div class="callout primary"><?php print $GLOBALS['linkicon']; ?><h4><?php
							if($this_studiengang_name[1] == "Werkstatt Philosophie") {
								print "&#128295; ";
							}
							print htmlentities($this_studiengang_name[1]);
?></h4>
<?php
							if(array_key_exists(2, $this_studiengang_name)) {
?>
								<h5><?php print htmlentities($this_studiengang_name[2]); ?></h5>
<?php
							}
?>
						</div>
					</a>
				</div>
			</div>
<?php
			$studiengang_counter++;
		}

	}

	function show_header_startseite () {
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
						<h1 class="text-center rainbow">Vorlesungsverzeichnis</h1>
<?php
					} else {
?>
						<h1 class="text-center">Vorlesungsverzeichnis</h1>
<?php
					}

?>
					<h2 class="text-center"><?php print isset($GLOBALS['this_institut']) ? htmlentities(get_institut_name($GLOBALS['this_institut'])) : ''; ?></h2>
					<h3 class="text-center"><?php print add_next_year_to_wintersemester($GLOBALS['this_semester'][1], $GLOBALS['this_semester'][2]); ?></h3>
					<p class="text-center"><?php print htmlentities(get_studiengang_name(get_get('studiengang'))); ?></p>
<?php
					show_institute_selector();

					show_semester_selector();


					if(is_array($GLOBALS["this_institut"]) && array_key_exists(0, $GLOBALS['this_institut'])) {
						$vvz_start_message = get_vvz_start_message($GLOBALS['this_institut'][0]);
						if($vvz_start_message) {
							print "Hinweis: <span class='orange_italic'>".htmlentities($vvz_start_message)."</span>\n";
						}
					}
?>
				</div>
				<div class="medium-2 columns"></div>
			</div>
		</header>
<?php
	}

	function logged_in_stuff () {
		if(isset($GLOBALS['logged_in_user_id'])) {
			$dozent_name = htmlentities(get_dozent_name($GLOBALS['logged_in_data'][2]));
			if(!user_is_verwalter($GLOBALS['logged_in_user_id'])) {
				if(!preg_match('/\w{2,}/', $dozent_name)) {
					$dozent_name = htmlentities($GLOBALS['logged_in_data'][1]).' <span class="class_red">!!! Ihr Account ist mit keinem Dozenten verknüpft! !!!</span>';
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
	}
?>
