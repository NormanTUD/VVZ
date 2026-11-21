<?php
	include_once(__DIR__.'/functions.php');

	$GLOBALS['pni_embedded'] = !empty($GLOBALS['adminpage']);
	$GLOBALS['pni_page_id'] = $GLOBALS['pni_embedded'] && !empty($GLOBALS['this_page_number']) ? (int)$GLOBALS['this_page_number'] : null;
	$GLOBALS['pni_base'] = $GLOBALS['pni_page_id'] ? 'admin?page='.$GLOBALS['pni_page_id'] : 'pruefungsnummer_info';

	if(!function_exists('pni_url')) {
		function pni_url ($params = array()) {
			$base = $GLOBALS['pni_base'];
			$separator = strpos($base, '?') === false ? '?' : '&';
			return $base.$separator.http_build_query($params);
		}

		function pni_escape ($value) {
			if($value === null || $value === '') {
				return '&mdash;';
			}
			return htmlentities((string)$value, ENT_QUOTES, 'UTF-8');
		}

		function pni_date ($value) {
			if($value === null || $value === '') {
				return '&mdash;';
			}
			$time = strtotime($value);
			return $time ? date('d.m.Y', $time) : pni_escape($value);
		}

		function pni_datetime ($value) {
			if($value === null || $value === '') {
				return '&mdash;';
			}
			$time = strtotime($value);
			return $time ? date('d.m.Y H:i', $time) : pni_escape($value);
		}

		function pni_link ($url, $label = null) {
			if($label === null) {
				$label = $url;
			}
			return '<a href="'.htmlentities($url, ENT_QUOTES, 'UTF-8').'">'.$label.'</a>';
		}

		function pni_external_link ($url) {
			if(!$url) {
				return '&mdash;';
			}
			$escaped = htmlentities($url, ENT_QUOTES, 'UTF-8');
			if(!preg_match('/^https?:\/\//i', $url)) {
				return $escaped;
			}
			return '<a href="'.$escaped.'" target="_blank" rel="noopener noreferrer">'.$escaped.'</a>';
		}

		function pni_fetch_all ($query) {
			$result = rquery($query);
			$rows = array();
			while($row = mysqli_fetch_assoc($result)) {
				$rows[] = $row;
			}
			return $rows;
		}

		function pni_table_exists ($table) {
			return table_exists($GLOBALS['dbname'], $table);
		}

		function pni_semester_name ($id) {
			if(!$id) {
				return null;
			}
			$semester = get_semester($id, 1);
			return isset($semester[1]) ? $semester[1] : null;
		}

		function pni_add_pair (&$pairs, $label, $value) {
			if($value !== null && $value !== '' && $value !== '&mdash;') {
				$pairs[$label] = $value;
			}
		}

		function pni_pairs_table ($pairs) {
			if(!count($pairs)) {
				return;
			}
			print '<table class="minwidth400">'."\n";
			foreach($pairs as $label => $value) {
				print '<tr><th class="text_align_left">'.pni_escape($label).'</th><td>'.$value.'</td></tr>'."\n";
			}
			print '</table>'."\n";
		}

		function pni_section ($title, $content, $open = true) {
			print '<details class="pni_section"'.($open ? ' open' : '').'>'."\n";
			print '<summary><b>'.pni_escape($title).'</b></summary>'."\n";
			print '<div class="pni_section_body">'.$content.'</div>'."\n";
			print '</details>'."\n";
		}

		function pni_rows_table ($rows, $columns, $empty_message) {
			if(!count($rows)) {
				return '<i>'.pni_escape($empty_message).'</i>';
			}
			$html = '<table class="minwidth400">'."\n<tr>";
			foreach($columns as $column => $label) {
				$html .= '<th>'.pni_escape($label).'</th>';
			}
			$html .= "</tr>\n";
			foreach($rows as $row) {
				$html .= '<tr>';
				foreach($columns as $column => $label) {
					$value = isset($row[$column]) ? $row[$column] : null;
					$html .= '<td>'.pni_escape($value).'</td>';
				}
				$html .= "</tr>\n";
			}
			$html .= '</table>'."\n";
			return $html;
		}

		function pni_get_exams ($pruefungsnummer_id) {
			return pni_fetch_all('
				SELECT
					`pr`.`id` AS `pruefung_id`,
					`pr`.`date` AS `pruefungsdatum`,
					`pr`.`raum_id` AS `pruefung_raum_id`,
					`pr`.`last_update` AS `pruefung_last_update`,
					`r`.`raumnummer` AS `pruefung_raum_nummer`,
					`g`.`abkuerzung` AS `pruefung_gebaeude_abkuerzung`,
					`g`.`name` AS `pruefung_gebaeude_name`,
					`v`.`id` AS `veranstaltung_id`,
					`v`.`name` AS `veranstaltung_name`,
					`v`.`semester_id` AS `veranstaltung_semester_id`,
					`v`.`master_niveau`,
					`v`.`institut_id` AS `veranstaltung_institut_id`,
					`vi`.`name` AS `veranstaltung_institut_name`,
					`v`.`veranstaltungstyp_id`,
					`vt`.`name` AS `veranstaltungstyp_name`,
					`vt`.`abkuerzung` AS `veranstaltungstyp_abkuerzung`,
					`d`.`id` AS `dozent_id`,
					`d`.`first_name` AS `dozent_vorname`,
					`d`.`last_name` AS `dozent_nachname`,
					`d`.`ausgeschieden` AS `dozent_ausgeschieden`,
					`t`.`abkuerzung` AS `dozent_titel_abkuerzung`,
					`t`.`name` AS `dozent_titel_name`,
					`vm`.`wunsch`,
					`vm`.`hinweis`,
					`vm`.`opal_link`,
					`vm`.`anzahl_hoerer`,
					`vm`.`erster_termin`,
					`vm`.`wochentag`,
					`vm`.`stunde`,
					`vm`.`woche`,
					`vm`.`abgabe_pruefungsleistungen`,
					`vm`.`related_veranstaltung`,
					`vm`.`fester_bbb_raum`,
					`vm`.`videolink`
				FROM `pruefung` `pr`
				LEFT JOIN `veranstaltung` `v` ON `v`.`id` = `pr`.`veranstaltung_id`
				LEFT JOIN `semester` `sem` ON `sem`.`id` = `v`.`semester_id`
				LEFT JOIN `veranstaltungstyp` `vt` ON `vt`.`id` = `v`.`veranstaltungstyp_id`
				LEFT JOIN `institut` `vi` ON `vi`.`id` = `v`.`institut_id`
				LEFT JOIN `dozent` `d` ON `d`.`id` = `v`.`dozent_id`
				LEFT JOIN `titel` `t` ON `t`.`id` = `d`.`titel_id`
				LEFT JOIN `veranstaltung_metadaten` `vm` ON `vm`.`veranstaltung_id` = `v`.`id`
				LEFT JOIN `raum` `r` ON `r`.`id` = `pr`.`raum_id`
				LEFT JOIN `gebaeude` `g` ON `g`.`id` = `r`.`gebaeude_id`
				WHERE `pr`.`pruefungsnummer_id` = '.esc($pruefungsnummer_id).'
				ORDER BY `pr`.`date` IS NULL, `pr`.`date`, `sem`.`jahr` DESC, `sem`.`typ`, `v`.`name`
			');
		}

		function pni_course_extras ($veranstaltung_id) {
			static $cache = array();
			if(isset($cache[$veranstaltung_id])) {
				return $cache[$veranstaltung_id];
			}

			$extras = array(
				'termine' => array(),
				'sprachen' => array(),
				'praesenztypen' => array(),
				'bezuege' => array()
			);

			if(!$veranstaltung_id) {
				$cache[$veranstaltung_id] = $extras;
				return $extras;
			}

			$extras['termine'] = get_einzelne_termine_by_veranstaltung_id($veranstaltung_id);

			if(pni_table_exists('veranstaltung_to_language') && pni_table_exists('language')) {
				$extras['sprachen'] = pni_fetch_all('
					SELECT `l`.`name`
					FROM `veranstaltung_to_language` `vl`
					JOIN `language` `l` ON `l`.`id` = `vl`.`language_id`
					WHERE `vl`.`veranstaltung_id` = '.esc($veranstaltung_id).'
					ORDER BY `l`.`name`
				');
			}

			if(pni_table_exists('veranstaltung_to_praesenztyp') && pni_table_exists('praesenztyp')) {
				$extras['praesenztypen'] = pni_fetch_all('
					SELECT `p`.`name`
					FROM `veranstaltung_to_praesenztyp` `vp`
					JOIN `praesenztyp` `p` ON `p`.`id` = `vp`.`praesenztyp_id`
					WHERE `vp`.`veranstaltung_id` = '.esc($veranstaltung_id).'
					ORDER BY `p`.`name`
				');
			}

			if(pni_table_exists('veranstaltung_nach_bezuegetypen') && pni_table_exists('veranstaltung_bezuege')) {
				$extras['bezuege'] = pni_fetch_all('
					SELECT `b`.`name`
					FROM `veranstaltung_nach_bezuegetypen` `vb`
					JOIN `veranstaltung_bezuege` `b` ON `b`.`id` = `vb`.`bezuegetyp_id`
					WHERE `vb`.`veranstaltung_id` = '.esc($veranstaltung_id).'
					ORDER BY `b`.`name`
				');
			}

			$cache[$veranstaltung_id] = $extras;
			return $extras;
		}

		function pni_render_semester_rows ($modul_id) {
			$rows = array();
			if(pni_table_exists('modul_nach_semester')) {
				$rows = pni_fetch_all('
					SELECT
						`ms`.`semester`,
						`mn`.`credit_points`,
						`mn`.`anzahl_pruefungsleistungen`
					FROM `modul_nach_semester` `ms`
					LEFT JOIN `modul_nach_semester_metadata` `mn` ON `mn`.`modul_id` = `ms`.`modul_id` AND `mn`.`semester` = `ms`.`semester`
					WHERE `ms`.`modul_id` = '.esc($modul_id).'
					ORDER BY `ms`.`semester`
				');
			}

			if(!count($rows)) {
				return '<i>Keine Semesterangaben zum Modul hinterlegt.</i>';
			}

			$html = '<table class="minwidth400">'."\n<tr><th>Semester</th><th>Credit Points</th><th>Anzahl Prüfungsleistungen</th></tr>\n";
			foreach($rows as $row) {
				$html .= '<tr><td>'.pni_escape($row['semester']).'</td><td>'.pni_escape($row['credit_points']).'</td><td>'.pni_escape($row['anzahl_pruefungsleistungen']).'</td></tr>'."\n";
			}
			$html .= "</table>\n";
			return $html;
		}

		function pni_render_anlage2_rows ($modul_id) {
			if(!pni_table_exists('modul_anlage2')) {
				return '<i>Keine Anlage-2-Daten verfügbar.</i>';
			}

			$rows = pni_fetch_all('
				SELECT
					`semester`,
					`sws_vorlesung`,
					`sws_uebung`,
					`sws_seminar`,
					`sws_tutorium`,
					`sws_sprachkurs`,
					`sws_praktikum`,
					`sws_sonstige`,
					`sws_total`,
					`pruefungsleistung`,
					`lp`
				FROM `modul_anlage2`
				WHERE `modul_id` = '.esc($modul_id).'
				ORDER BY `semester`, `id`
			');

			if(!count($rows)) {
				return '<i>Keine Anlage-2-Daten zum Modul hinterlegt.</i>';
			}

			$html = '<table class="minwidth400">'."\n<tr><th>Semester</th><th>Vorlesung</th><th>Übung</th><th>Seminar</th><th>Tutorium</th><th>Sprachkurs</th><th>Praktikum</th><th>Sonstige</th><th>SWS gesamt</th><th>Prüfungsleistung</th><th>LP</th></tr>\n";
			foreach($rows as $row) {
				$html .= '<tr>';
				foreach($row as $value) {
					$html .= '<td>'.pni_escape($value).'</td>';
				}
				$html .= "</tr>\n";
			}
			$html .= "</table>\n";
			return $html;
		}

		function pni_render_veranstaltungstypen_rows ($modul_id) {
			if(!pni_table_exists('modul_nach_semester_veranstaltungstypen_anzahl')) {
				return '<i>Keine Angaben zu vorgeschriebenen Veranstaltungstypen verfügbar.</i>';
			}

			$rows = pni_fetch_all('
				SELECT
					`ma`.`semester`,
					`vt`.`name` AS `veranstaltungstyp`,
					`ma`.`anzahl`
				FROM `modul_nach_semester_veranstaltungstypen_anzahl` `ma`
				LEFT JOIN `veranstaltungstyp` `vt` ON `vt`.`id` = `ma`.`veranstaltungstyp_id`
				WHERE `ma`.`modul_id` = '.esc($modul_id).'
				ORDER BY `ma`.`semester`, `vt`.`name`
			');

			if(!count($rows)) {
				return '<i>Keine Angaben zu vorgeschriebenen Veranstaltungstypen hinterlegt.</i>';
			}

			$html = '<table class="minwidth400">'."\n<tr><th>Semester</th><th>Veranstaltungstyp</th><th>Anzahl</th></tr>\n";
			foreach($rows as $row) {
				$html .= '<tr><td>'.pni_escape($row['semester']).'</td><td>'.pni_escape($row['veranstaltungstyp']).'</td><td>'.pni_escape($row['anzahl']).'</td></tr>'."\n";
			}
			$html .= "</table>\n";
			return $html;
		}

		function pni_render_modul_zuordnungen ($modul_id) {
			if(!pni_table_exists('modul_zuordnung')) {
				return '<i>Keine Zuordnungen verfügbar.</i>';
			}

			$rows = pni_fetch_all('
				SELECT
					`s`.`name` AS `ziel_studiengang`,
					`i`.`name` AS `ziel_institut`,
					`mz`.`rolle`,
					`mz`.`lp`,
					`mz`.`notiz`,
					`mz`.`import_id`
				FROM `modul_zuordnung` `mz`
				LEFT JOIN `studiengang` `s` ON `s`.`id` = `mz`.`ziel_studiengang_id`
				LEFT JOIN `institut` `i` ON `i`.`id` = `s`.`institut_id`
				WHERE `mz`.`modul_id` = '.esc($modul_id).'
				ORDER BY `s`.`name`, `mz`.`rolle`
			');

			return pni_rows_table($rows, array(
				'ziel_studiengang' => 'Ziel-Studiengang',
				'ziel_institut' => 'Institut',
				'rolle' => 'Rolle',
				'lp' => 'LP',
				'notiz' => 'Notiz'
			), 'Keine zusätzlichen Studiengang-Zuordnungen hinterlegt.');
		}

		function pni_render_voraussetzungen ($modul_id) {
			if(!pni_table_exists('modul_voraussetzung')) {
				return '<i>Keine Voraussetzungsdaten verfügbar.</i>';
			}

			$rows = pni_fetch_all('
				SELECT
					`mv`.`typ`,
					`mv`.`notiz`,
					`vm`.`name` AS `voraussetzung_modul`,
					`vm`.`abkuerzung` AS `voraussetzung_abkuerzung`,
					`s`.`name` AS `voraussetzung_studiengang`
				FROM `modul_voraussetzung` `mv`
				LEFT JOIN `modul` `vm` ON `vm`.`id` = `mv`.`voraussetzung_modul_id`
				LEFT JOIN `studiengang` `s` ON `s`.`id` = `vm`.`studiengang_id`
				WHERE `mv`.`modul_id` = '.esc($modul_id).'
				ORDER BY `mv`.`typ`, `vm`.`name`
			');

			$html = pni_rows_table($rows, array(
				'typ' => 'Typ',
				'voraussetzung_modul' => 'Voraussetzungsmodul',
				'voraussetzung_abkuerzung' => 'Abkürzung',
				'voraussetzung_studiengang' => 'Studiengang',
				'notiz' => 'Notiz'
			), 'Keine Voraussetzungen hinterlegt.');

			$reverse_rows = pni_fetch_all('
				SELECT
					`m`.`name` AS `modul`,
					`s`.`name` AS `studiengang`,
					`mv`.`typ`,
					`mv`.`notiz`
				FROM `modul_voraussetzung` `mv`
				LEFT JOIN `modul` `m` ON `m`.`id` = `mv`.`modul_id`
				LEFT JOIN `studiengang` `s` ON `s`.`id` = `m`.`studiengang_id`
				WHERE `mv`.`voraussetzung_modul_id` = '.esc($modul_id).'
				ORDER BY `m`.`name`, `mv`.`typ`
			');

			if(count($reverse_rows)) {
				$html .= '<h4>Wird vorausgesetzt von</h4>';
				$html .= pni_rows_table($reverse_rows, array(
					'modul' => 'Modul',
					'studiengang' => 'Studiengang',
					'typ' => 'Typ',
					'notiz' => 'Notiz'
				), '');
			}

			return $html;
		}

		function pni_render_importe ($pn) {
			if(!pni_table_exists('pruefungsnummer_import') || !pni_table_exists('studienordnung_import')) {
				return '<i>Keine Importdaten verfügbar.</i>';
			}

			$rows = pni_fetch_all('
				SELECT
					`pni`.`id`,
					`pni`.`generated_nr`,
					`pni`.`pruefungstyp_name`,
					`pni`.`lp`,
					`pni`.`semester`,
					`si`.`id` AS `import_id`,
					`si`.`filename`,
					`si`.`degree`,
					`si`.`program_name`,
					`si`.`imported_at`,
					`si`.`modules_found`,
					`si`.`modules_imported`,
					`si`.`pruefungsnummern_imported`,
					`si`.`notes`
				FROM `pruefungsnummer_import` `pni`
				LEFT JOIN `studienordnung_import` `si` ON `si`.`id` = `pni`.`import_id`
				WHERE `pni`.`pruefungsnummer_id` = '.esc($pn['id']).'
					OR (`pni`.`modul_id` = '.esc($pn['modul_id']).' AND `pni`.`generated_nr` = '.esc($pn['pruefungsnummer']).')
				ORDER BY `si`.`imported_at` DESC, `pni`.`id` DESC
			');

			if(!count($rows)) {
				return '<i>Keine Import-Historie zu dieser Prüfungsnummer hinterlegt.</i>';
			}

			$html = '';
			foreach($rows as $row) {
				$pairs = array();
				pni_add_pair($pairs, 'Import-ID', pni_escape($row['import_id']));
				pni_add_pair($pairs, 'Datei', pni_escape($row['filename']));
				pni_add_pair($pairs, 'Abschluss', pni_escape($row['degree']));
				pni_add_pair($pairs, 'Studiengang laut Import', pni_escape($row['program_name']));
				pni_add_pair($pairs, 'Importiert am', pni_datetime($row['imported_at']));
				pni_add_pair($pairs, 'Generierte Nummer', pni_escape($row['generated_nr']));
				pni_add_pair($pairs, 'Prüfungstyp laut Import', pni_escape($row['pruefungstyp_name']));
				pni_add_pair($pairs, 'LP laut Import', pni_escape($row['lp']));
				pni_add_pair($pairs, 'Semester laut Import', pni_escape($row['semester']));
				pni_add_pair($pairs, 'Gefundene Module', pni_escape($row['modules_found']));
				pni_add_pair($pairs, 'Importierte Module', pni_escape($row['modules_imported']));
				pni_add_pair($pairs, 'Importierte Prüfungsnummern', pni_escape($row['pruefungsnummern_imported']));
				pni_add_pair($pairs, 'Notizen', nl2br(pni_escape($row['notes'])));
				$html .= '<div class="callout">';
				ob_start();
				pni_pairs_table($pairs);
				$html .= ob_get_clean();
				$html .= '</div>';
			}
			return $html;
		}

		function pni_render_geschwister ($pn) {
			$rows = pni_fetch_all('
				SELECT
					`pn`.`id`,
					`pn`.`pruefungsnummer`,
					`pn`.`disabled`,
					`pt`.`name` AS `pruefungstyp`
				FROM `pruefungsnummer` `pn`
				LEFT JOIN `pruefungstyp` `pt` ON `pt`.`id` = `pn`.`pruefungstyp_id`
				WHERE `pn`.`modul_id` = '.esc($pn['modul_id']).'
				ORDER BY `pn`.`pruefungsnummer`, `pn`.`id`
			');

			if(!count($rows)) {
				return '<i>Keine weiteren Prüfungsnummern im selben Modul.</i>';
			}

			$html = '<table class="minwidth400">'."\n<tr><th>Prüfungsnummer</th><th>Prüfungstyp</th><th>Status</th></tr>\n";
			foreach($rows as $row) {
				$is_current = ((int)$row['id'] === (int)$pn['id']);
				$label = pni_escape($row['pruefungsnummer']);
				if($is_current) {
					$label = '<b>'.$label.'</b>';
				} else {
					$label = pni_link(pni_url(array('id' => $row['id'])), $label);
				}
				$status = $row['disabled'] == '1' ? 'deaktiviert' : 'aktiv';
				$html .= '<tr><td>'.$label.'</td><td>'.pni_escape($row['pruefungstyp']).'</td><td>'.$status.'</td></tr>'."\n";
			}
			$html .= "</table>\n";
			return $html;
		}

		function pni_render_exam ($exam) {
			$pairs = array();
			$semester_name = pni_semester_name($exam['veranstaltung_semester_id']);

			$room = null;
			if($exam['pruefung_raum_id']) {
				$room = pni_escape($exam['pruefung_gebaeude_abkuerzung']).' '.pni_escape($exam['pruefung_raum_nummer']);
				if($exam['pruefung_gebaeude_name']) {
					$room .= ' ('.pni_escape($exam['pruefung_gebaeude_name']).')';
				}
			}

			$dozent = null;
			$dozent_name = trim(($exam['dozent_titel_abkuerzung'] ? $exam['dozent_titel_abkuerzung'].' ' : '').($exam['dozent_vorname'] ?? '').' '.($exam['dozent_nachname'] ?? ''));
			if($dozent_name !== '') {
				$dozent = pni_escape($dozent_name);
				if($exam['dozent_ausgeschieden'] == '1') {
					$dozent .= ' <i>(ausgeschieden)</i>';
				}
			}

			$veranstaltungstyp = null;
			if($exam['veranstaltungstyp_name']) {
				$veranstaltungstyp = pni_escape($exam['veranstaltungstyp_name']);
				if($exam['veranstaltungstyp_abkuerzung']) {
					$veranstaltungstyp .= ' ('.pni_escape($exam['veranstaltungstyp_abkuerzung']).')';
				}
			}

			pni_add_pair($pairs, 'Prüfungstermin', pni_date($exam['pruefungsdatum']));
			pni_add_pair($pairs, 'Raum', $room);
			pni_add_pair($pairs, 'Veranstaltung', pni_escape($exam['veranstaltung_name']));
			pni_add_pair($pairs, 'Veranstaltungstyp', $veranstaltungstyp);
			pni_add_pair($pairs, 'Semester', pni_escape($semester_name));
			pni_add_pair($pairs, 'Dozent', $dozent);
			pni_add_pair($pairs, 'Institut', pni_escape($exam['veranstaltung_institut_name']));
			pni_add_pair($pairs, 'Master-Niveau', $exam['master_niveau'] == '1' ? 'ja' : null);
			pni_add_pair($pairs, 'Erster Termin', pni_date($exam['erster_termin']));
			pni_add_pair($pairs, 'Wochentag', pni_escape($exam['wochentag']));
			pni_add_pair($pairs, 'Stunde', pni_escape($exam['stunde']));
			pni_add_pair($pairs, 'Woche', pni_escape($exam['woche']));
			pni_add_pair($pairs, 'Abgabe der Prüfungsleistungen', pni_date($exam['abgabe_pruefungsleistungen']));
			pni_add_pair($pairs, 'Anzahl Hörer', pni_escape($exam['anzahl_hoerer']));
			pni_add_pair($pairs, 'OPAL', pni_external_link($exam['opal_link']));
			pni_add_pair($pairs, 'Videolink', pni_external_link($exam['videolink']));
			pni_add_pair($pairs, 'Fester BBB-Raum', $exam['fester_bbb_raum'] ? 'ja' : null);
			pni_add_pair($pairs, 'Hinweis', nl2br(pni_escape($exam['hinweis'])));
			pni_add_pair($pairs, 'Wunsch', nl2br(pni_escape($exam['wunsch'])));
			pni_add_pair($pairs, 'Prüfungs-ID', pni_escape($exam['pruefung_id']));
			pni_add_pair($pairs, 'Veranstaltungs-ID', pni_escape($exam['veranstaltung_id']));
			pni_add_pair($pairs, 'Letzte Änderung', pni_datetime($exam['pruefung_last_update']));

			if($exam['related_veranstaltung']) {
				$related_name = get_veranstaltung_name($exam['related_veranstaltung']);
				$related = pni_escape($related_name ?: ('#'.$exam['related_veranstaltung']));
				pni_add_pair($pairs, 'Verwandte Veranstaltung', $related);
			}

			$extras = pni_course_extras($exam['veranstaltung_id']);

			if(count($extras['sprachen'])) {
				$names = array();
				foreach($extras['sprachen'] as $row) {
					$names[] = pni_escape($row['name']);
				}
				pni_add_pair($pairs, 'Sprachen', implode(', ', $names));
			}

			if(count($extras['praesenztypen'])) {
				$names = array();
				foreach($extras['praesenztypen'] as $row) {
					$names[] = pni_escape($row['name']);
				}
				pni_add_pair($pairs, 'Präsenztypen', implode(', ', $names));
			}

			if(count($extras['bezuege'])) {
				$names = array();
				foreach($extras['bezuege'] as $row) {
					$names[] = pni_escape($row['name']);
				}
				pni_add_pair($pairs, 'Bezüge', implode(', ', $names));
			}

			$html = '';
			ob_start();
			pni_pairs_table($pairs);
			$html .= ob_get_clean();

			if(count($extras['termine'])) {
				$html .= '<h4>Einzelne Termine</h4>';
				$html .= '<table class="minwidth400">'."\n<tr><th>Beginn</th><th>Ende</th><th>Gebäude</th><th>Raum</th></tr>\n";
				foreach($extras['termine'] as $termin) {
					$start = pni_datetime($termin['start_year'].'-'.$termin['start_month'].'-'.$termin['start_day'].' '.$termin['start_hour'].':'.$termin['start_minute'].':00');
					$end = pni_datetime($termin['end_year'].'-'.$termin['end_month'].'-'.$termin['end_day'].' '.$termin['end_hour'].':'.$termin['end_minute'].':00');
					$html .= '<tr><td>'.$start.'</td><td>'.$end.'</td><td>'.pni_escape($termin['gebaeude_abkuerzung']).'</td><td>'.pni_escape($termin['raumnummer']).'</td></tr>'."\n";
				}
				$html .= "</table>\n";
			}

			return $html;
		}

		function pni_render_pruefungsnummer ($pn) {
			$exams = pni_get_exams($pn['id']);

			$dozenten = array();
			$semester = array();
			$termine = array();
			foreach($exams as $exam) {
				$name = trim(($exam['dozent_titel_abkuerzung'] ? $exam['dozent_titel_abkuerzung'].' ' : '').($exam['dozent_vorname'] ?? '').' '.($exam['dozent_nachname'] ?? ''));
				if($name !== '') {
					$dozenten[$name] = $name;
				}
				$semester_name = pni_semester_name($exam['veranstaltung_semester_id']);
				if($semester_name) {
					$semester[$semester_name] = $semester_name;
				}
				if($exam['pruefungsdatum']) {
					$termine[$exam['pruefungsdatum']] = pni_date($exam['pruefungsdatum']);
				}
			}

			print '<div class="callout">';
			print '<h2>Prüfungsnummer '.pni_escape($pn['pruefungsnummer']).'</h2>'."\n";
			print '<p><b>'.pni_escape($pn['modul_name']).'</b>';
			if($pn['modulbezeichnung']) {
				print ' &mdash; '.pni_escape($pn['modulbezeichnung']);
			}
			print '</p>'."\n";

			$summary = array();
			pni_add_pair($summary, 'Angeboten von', count($dozenten) ? pni_escape(implode(', ', $dozenten)) : null);
			pni_add_pair($summary, 'Semester', count($semester) ? pni_escape(implode(', ', $semester)) : null);
			pni_add_pair($summary, 'Prüfungstermine', count($termine) ? pni_escape(implode(', ', $termine)) : null);
			pni_pairs_table($summary);

			$pairs = array();
			pni_add_pair($pairs, 'Prüfungsnummer', pni_escape($pn['pruefungsnummer']));
			pni_add_pair($pairs, 'Modulbezeichnung (PN)', pni_escape($pn['modulbezeichnung']));
			pni_add_pair($pairs, 'Modul', pni_escape($pn['modul_name']));
			pni_add_pair($pairs, 'Modulabkürzung', pni_escape($pn['modul_abkuerzung']));
			pni_add_pair($pairs, 'Modulbeschreibung', pni_escape($pn['modul_beschreibung']));
			pni_add_pair($pairs, 'Studiengang', pni_escape($pn['studiengang_name']));
			pni_add_pair($pairs, 'Institut', pni_escape($pn['institut_name']));

			if($pn['studienordnung']) {
				pni_add_pair($pairs, 'Studienordnung', pni_external_link($pn['studienordnung']));
			}

			pni_add_pair($pairs, 'Prüfungstyp', pni_escape($pn['pruefungstyp_name']));
			pni_add_pair($pairs, 'Bereich', pni_escape($pn['bereich_name']));
			pni_add_pair($pairs, 'Fach', pni_escape($pn['fach_name']));
			pni_add_pair($pairs, 'Zeitraum', pni_escape($pn['zeitraum_name']));
			pni_add_pair($pairs, 'Status', $pn['disabled'] == '1' ? '<span class="red_text">deaktiviert</span>' : 'aktiv');
			pni_add_pair($pairs, 'Datenbank-IDs', 'PN #'.pni_escape($pn['id']).' &middot; Modul #'.pni_escape($pn['modul_id']).' &middot; Studiengang #'.pni_escape($pn['studiengang_id']));
			pni_pairs_table($pairs);
			print '</div>'."\n";

			$exams_html = '';
			if(count($exams)) {
				ob_start();
				foreach($exams as $exam) {
					print '<div class="callout">';
					$date_label = $exam['pruefungsdatum'] ? pni_date($exam['pruefungsdatum']) : 'Termin noch nicht festgelegt';
					print '<h3>'.$date_label.' &mdash; '.pni_escape($exam['veranstaltung_name']).'</h3>'."\n";
					print pni_render_exam($exam);
					print '</div>'."\n";
				}
				$exams_html = ob_get_clean();
			} else {
				$exams_html = '<i>Keine Prüfungstermine oder Veranstaltungen mit dieser Prüfungsnummer verknüpft.</i>';
			}
			pni_section('Prüfungstermine und Veranstaltungen', $exams_html);

			pni_section('Studiengang-Zuordnungen', pni_render_modul_zuordnungen($pn['modul_id']), false);
			pni_section('Modul nach Semester', pni_render_semester_rows($pn['modul_id']), false);
			pni_section('Anlage 2 (SWS und Prüfungsleistung)', pni_render_anlage2_rows($pn['modul_id']), false);
			pni_section('Vorgeschriebene Veranstaltungstypen', pni_render_veranstaltungstypen_rows($pn['modul_id']), false);
			pni_section('Voraussetzungen', pni_render_voraussetzungen($pn['modul_id']), false);
			pni_section('Import-Historie', pni_render_importe($pn), false);
			pni_section('Weitere Prüfungsnummern im selben Modul', pni_render_geschwister($pn), false);
		}
	}

	$pni_input = null;
	foreach(array('pn', 'pruefungsnummer', 'pnr') as $pni_parameter) {
		$value = get_get($pni_parameter);
		if(!is_scalar($value)) {
			continue;
		}
		$value = trim((string)$value);
		if($value !== '') {
			$pni_input = $value;
			break;
		}
	}

	$pni_id = get_get('id');
	if(!$pni_id) {
		$pni_id = get_get('pruefungsnummer_id');
	}
	if(!is_scalar($pni_id) || ($pni_id !== null && !preg_match('/^\d+$/', (string)$pni_id))) {
		$pni_id = null;
	}

	$pni_rows = array();
	$pni_fuzzy_rows = array();
	$pni_exact_found = false;

	if($pni_id) {
		$pni_rows = pni_fetch_all('
			SELECT
				`pn`.`id`, `pn`.`pruefungsnummer`, `pn`.`modul_id`, `pn`.`pruefungstyp_id`, `pn`.`bereich_id`,
				`pn`.`pruefungsnummer_fach_id`, `pn`.`modulbezeichnung`, `pn`.`zeitraum_id`, `pn`.`disabled`,
				`m`.`name` AS `modul_name`, `m`.`beschreibung` AS `modul_beschreibung`, `m`.`abkuerzung` AS `modul_abkuerzung`,
				`m`.`studiengang_id`, `s`.`name` AS `studiengang_name`, `s`.`studienordnung`, `s`.`institut_id`,
				`i`.`name` AS `institut_name`, `pt`.`name` AS `pruefungstyp_name`, `b`.`name` AS `bereich_name`,
				`f`.`name` AS `fach_name`, `z`.`name` AS `zeitraum_name`
			FROM `pruefungsnummer` `pn`
			LEFT JOIN `modul` `m` ON `m`.`id` = `pn`.`modul_id`
			LEFT JOIN `studiengang` `s` ON `s`.`id` = `m`.`studiengang_id`
			LEFT JOIN `institut` `i` ON `i`.`id` = `s`.`institut_id`
			LEFT JOIN `pruefungstyp` `pt` ON `pt`.`id` = `pn`.`pruefungstyp_id`
			LEFT JOIN `bereich` `b` ON `b`.`id` = `pn`.`bereich_id`
			LEFT JOIN `pruefungsnummer_fach` `f` ON `f`.`id` = `pn`.`pruefungsnummer_fach_id`
			LEFT JOIN `pruefung_zeitraum` `z` ON `z`.`id` = `pn`.`zeitraum_id`
			WHERE `pn`.`id` = '.esc($pni_id).'
			ORDER BY `pn`.`pruefungsnummer`, `s`.`name`, `m`.`name`
		');
		$pni_exact_found = count($pni_rows) > 0;
	} else if($pni_input !== null) {
		$pni_rows = pni_fetch_all('
			SELECT
				`pn`.`id`, `pn`.`pruefungsnummer`, `pn`.`modul_id`, `pn`.`pruefungstyp_id`, `pn`.`bereich_id`,
				`pn`.`pruefungsnummer_fach_id`, `pn`.`modulbezeichnung`, `pn`.`zeitraum_id`, `pn`.`disabled`,
				`m`.`name` AS `modul_name`, `m`.`beschreibung` AS `modul_beschreibung`, `m`.`abkuerzung` AS `modul_abkuerzung`,
				`m`.`studiengang_id`, `s`.`name` AS `studiengang_name`, `s`.`studienordnung`, `s`.`institut_id`,
				`i`.`name` AS `institut_name`, `pt`.`name` AS `pruefungstyp_name`, `b`.`name` AS `bereich_name`,
				`f`.`name` AS `fach_name`, `z`.`name` AS `zeitraum_name`
			FROM `pruefungsnummer` `pn`
			LEFT JOIN `modul` `m` ON `m`.`id` = `pn`.`modul_id`
			LEFT JOIN `studiengang` `s` ON `s`.`id` = `m`.`studiengang_id`
			LEFT JOIN `institut` `i` ON `i`.`id` = `s`.`institut_id`
			LEFT JOIN `pruefungstyp` `pt` ON `pt`.`id` = `pn`.`pruefungstyp_id`
			LEFT JOIN `bereich` `b` ON `b`.`id` = `pn`.`bereich_id`
			LEFT JOIN `pruefungsnummer_fach` `f` ON `f`.`id` = `pn`.`pruefungsnummer_fach_id`
			LEFT JOIN `pruefung_zeitraum` `z` ON `z`.`id` = `pn`.`zeitraum_id`
			WHERE `pn`.`pruefungsnummer` = '.esc($pni_input).'
			ORDER BY `pn`.`pruefungsnummer`, `s`.`name`, `m`.`name`
		');
		$pni_exact_found = count($pni_rows) > 0;

		if(!$pni_exact_found) {
			$pni_like = '%'.addcslashes($pni_input, '%_\\').'%';
			$pni_fuzzy_rows = pni_fetch_all('
				SELECT
					`pn`.`id`, `pn`.`pruefungsnummer`, `pn`.`modul_id`, `pn`.`modulbezeichnung`,
					`m`.`name` AS `modul_name`, `s`.`name` AS `studiengang_name`, `pt`.`name` AS `pruefungstyp_name`
				FROM `pruefungsnummer` `pn`
				LEFT JOIN `modul` `m` ON `m`.`id` = `pn`.`modul_id`
				LEFT JOIN `studiengang` `s` ON `s`.`id` = `m`.`studiengang_id`
				LEFT JOIN `pruefungstyp` `pt` ON `pt`.`id` = `pn`.`pruefungstyp_id`
				WHERE `pn`.`pruefungsnummer` LIKE '.esc($pni_like).'
				ORDER BY `pn`.`pruefungsnummer`, `s`.`name`, `m`.`name`
				LIMIT 100
			');
		}
	}

	if(!$GLOBALS['pni_embedded']) {
		$page_title = 'Vorlesungsverzeichnis '.$GLOBALS['university_name'].' | Prüfungsnummer-Info';
		$filename = 'startseite';
		include(__DIR__.'/header.php');
	}
?>
<div id="mainindex" class="pni_main">
	<?php
		if(!$GLOBALS['pni_embedded']) {
	?>
		<a href="startseite" border="0"><?php print_uni_logo(); ?></a>
	<?php
		}
	?>
	<h1>Prüfungsnummer-Info</h1>
	<form method="get" action="<?php print htmlentities($GLOBALS['pni_base'], ENT_QUOTES, 'UTF-8'); ?>">
		<label for="pni_input"><b>Prüfungsnummer</b></label>
		<input id="pni_input" type="text" name="pn" value="<?php print htmlentities($pni_input ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="z.B. 214260" autofocus />
		<input type="submit" value="Nachschlagen" />
	</form>
	<p>Geben Sie eine Prüfungsnummer ein, um alle dazu gespeicherten Informationen anzuzeigen.</p>
<?php
	if($pni_input === null && !$pni_id) {
		print '<p><i>Noch keine Prüfungsnummer eingegeben.</i></p>';
	} else if($pni_id && !$pni_exact_found) {
		print '<div class="callout warning"><b>Kein Datensatz mit dieser ID gefunden.</b></div>';
	} else if($pni_input !== null && !$pni_exact_found) {
		print '<div class="callout warning"><b>Keine exakte Übereinstimmung für &raquo;'.pni_escape($pni_input).'&laquo; gefunden.</b></div>';
		if(count($pni_fuzzy_rows)) {
			print '<h2>Ähnliche Prüfungsnummern</h2>';
			print '<table class="minwidth400">'."\n<tr><th>Prüfungsnummer</th><th>Modul</th><th>Studiengang</th><th>Prüfungstyp</th></tr>\n";
			foreach($pni_fuzzy_rows as $row) {
				$label = pni_escape($row['pruefungsnummer']);
				print '<tr>';
				print '<td>'.pni_link(pni_url(array('id' => $row['id'])), $label).'</td>';
				print '<td>'.pni_escape($row['modul_name']).'</td>';
				print '<td>'.pni_escape($row['studiengang_name']).'</td>';
				print '<td>'.pni_escape($row['pruefungstyp_name']).'</td>';
				print '</tr>'."\n";
			}
			print "</table>\n";
		} else {
			print '<p><i>Es wurden keine ähnlichen Prüfungsnummern gefunden.</i></p>';
		}
	} else if(count($pni_rows)) {
		if(count($pni_rows) > 1) {
			print '<div class="callout"><b>'.count($pni_rows).' Datensätze gefunden:</b><ul>';
			foreach($pni_rows as $row) {
				print '<li>';
				print pni_link(pni_url(array('id' => $row['id'])), pni_escape($row['pruefungsnummer']));
				print ' &mdash; '.pni_escape($row['modul_name']);
				if($row['studiengang_name']) {
					print ' ('.pni_escape($row['studiengang_name']).')';
				}
				print '</li>';
			}
			print '</ul></div>';
		}

		foreach($pni_rows as $pni_row) {
			pni_render_pruefungsnummer($pni_row);
			print '<div class="height_20px"></div>';
		}
	}
?>
</div>
<?php
	if(!$GLOBALS['pni_embedded']) {
		include(__DIR__.'/footer.php');
	}
?>
