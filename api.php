<?php
	$php_start = microtime(true);
	include_once("functions.php");

	$query_string = $_SERVER['QUERY_STRING'];
	$log_data = array(
		'error' => 0,
		'parameters' => $query_string,
		'ip' => get_user_ip()
	);

	function api_emit_json ($data) {
		print json_encode(api_sanitize_data($data), JSON_PRETTY_PRINT|JSON_FORCE_OBJECT);
	}

	function api_sanitize_data ($data) {
		if(!is_array($data)) return $data;
		$out = array();
		foreach ($data as $k => $v) {
			if(is_array($v)) {
				$out[$k] = api_sanitize_data($v);
			} else {
				if(is_null($v)) {
					$out[$k] = '';
				} else if(is_string($v)) {
					$out[$k] = $v;
				} else {
					$out[$k] = $v;
				}
			}
		}
		return $out;
	}

	function api_log_call ($auth_code, $log_data) {
		$query = 'INSERT INTO `api_log` (`auth_code_id`, `time`, `parameter`, `ip`, `api_error_code_id`) VALUES ('.esc(get_auth_code_id($auth_code)).', now(), '.esc($log_data['parameters']).', '.esc($log_data['ip']).', '.esc($log_data['error']).')';
		rquery($query);
	}

	if(get_get('auth_code')) {
		rquery('delete from api_log where time < DATE_SUB(NOW(), INTERVAL 3 MONTH)');
		if(file_exists('/etc/api_debug') || is_valid_auth_code(get_get('auth_code'))) {
			if(file_exists('/etc/api_debug') || last_api_access_long_ago(get_get('auth_code'))) {

				$data = array();

				// ==================== BESTEHENDE LISTEN-ABFRAGEN ====================

				if(get_get('gebaeude_liste')) {
					$data = create_gebaeude_array();
				} else if (get_get('semester_liste')) {
					$data = create_semester_array();
				} else if (get_get('institute_liste')) {
					$data = create_institute_array();
				} else if (get_get('dozenten_liste')) {
					$data = create_dozenten_first_last_name_array();
				} else if (get_get('studiengang_liste')) {
					$data = create_studiengaenge_array();
				} else if (get_get('veranstaltungstypen')) {
					$data = create_veranstaltungstyp_abkuerzung_namen_array();
				} else if (get_get('pruefungen') && get_get('studiengang')) {
					$pruefungen = create_pruefungen_by_studiengang_array(get_get('studiengang'));

					foreach ($pruefungen as $key => $value) {
						$key = get_veranstaltungsname_by_id($key);
						$data[$key] = htmlentities($value);
					}

				// ==================== NEUE LISTEN-ABFRAGEN ====================

				} else if (get_get('raum_liste')) {
					// Liste aller Räume (ID, Gebäudekürzel, Raumnummer, optionale Bezeichnung)
					$query = 'SELECT `r`.`id`, `g`.`abkuerzung`, `r`.`raumnummer`, `r`.`name`, `r`.`kapazitaet`, `r`.`barrierefrei` '.
						'FROM `raum` `r` LEFT JOIN `gebaeude` `g` ON `g`.`id` = `r`.`gebaeude_id` '.
						'ORDER BY `g`.`abkuerzung`, `r`.`raumnummer`';
					$result = rquery($query);
					$rooms = array();
					while ($row = mysqli_fetch_row($result)) {
						$rooms[] = array(
							'id' => (int)$row[0],
							'gebaeude' => $row[1],
							'raumnummer' => $row[2],
							'name' => $row[3],
							'kapazitaet' => $row[4] !== null ? (int)$row[4] : null,
							'barrierefrei' => $row[5]
						);
					}
					$data = $rooms;
				} else if (get_get('sprachen_liste')) {
					$data = create_language_array();
				} else if (get_get('praesenztyp_liste')) {
					$data = create_praesenztypen_array();
				} else if (get_get('bezug_liste')) {
					$data = create_bezuege_array();
				} else if (get_get('wochentag_liste')) {
					$data = create_wochentag_array();
				} else if (get_get('stunden_liste')) {
					$data = create_stunden_array();
				} else if (get_get('wochenrhythmus_liste')) {
					$data = create_wann_array();
				} else if (get_get('titel_liste')) {
					$data = create_titel_array();
				} else if (get_get('bereich_liste')) {
					$data = create_bereiche_array();
				} else if (get_get('fach_liste')) {
					$query = 'SELECT `id`, `name` FROM `pruefungsnummer_fach` ORDER BY `name`';
					$result = rquery($query);
					$f = array();
					while ($row = mysqli_fetch_row($result)) {
						$f[] = array('id' => (int)$row[0], 'name' => $row[1]);
					}
					$data = $f;
				} else if (get_get('pruefungstyp_liste')) {
					$data = create_pruefungstypen_array();
				} else if (get_get('modul_liste')) {
					// Liste aller Module mit Studiengang, Bereich und Fach
					$query = 'SELECT `m`.`id`, `m`.`name`, `m`.`modulbezeichnung`, `m`.`semester`, `s`.`name` AS `studiengang_name`, `b`.`name` AS `bereich_name`, `f`.`name` AS `fach_name` '.
						'FROM `modul` `m` '.
						'LEFT JOIN `studiengang` `s` ON `s`.`id` = `m`.`studiengang_id` '.
						'LEFT JOIN `bereich` `b` ON `b`.`id` = `m`.`bereich_id` '.
						'LEFT JOIN `fach` `f` ON `f`.`id` = `m`.`fach_id` '.
						'ORDER BY `s`.`name`, `m`.`name`';
					$result = rquery($query);
					$mods = array();
					while ($row = mysqli_fetch_row($result)) {
						$mods[] = array(
							'id' => (int)$row[0],
							'name' => $row[1],
							'modulbezeichnung' => $row[2],
							'semester' => $row[3],
							'studiengang' => $row[4],
							'bereich' => $row[5],
							'fach' => $row[6]
						);
					}
					$data = $mods;
				} else if (get_get('pruefungsnummer_liste')) {
					// Liste aller Prüfungsnummern mit Modul und Prüfungstyp
					$query = 'SELECT `p`.`id`, `p`.`pruefungsnummer`, `pt`.`name` AS `pruefungstyp`, `m`.`name` AS `modul`, `s`.`name` AS `studiengang`, `b`.`name` AS `bereich`, `p`.`disabled` '.
						'FROM `pruefungsnummer` `p` '.
						'LEFT JOIN `pruefungstyp` `pt` ON `pt`.`id` = `p`.`pruefungstyp_id` '.
						'LEFT JOIN `modul` `m` ON `m`.`id` = `p`.`modul_id` '.
						'LEFT JOIN `studiengang` `s` ON `s`.`id` = `m`.`studiengang_id` '.
						'LEFT JOIN `bereich` `b` ON `b`.`id` = `p`.`bereich_id` '.
						'ORDER BY `p`.`pruefungsnummer`';
					$result = rquery($query);
					$pns = array();
					while ($row = mysqli_fetch_row($result)) {
						$pns[] = array(
							'id' => (int)$row[0],
							'pruefungsnummer' => $row[1],
							'pruefungstyp' => $row[2],
							'modul' => $row[3],
							'studiengang' => $row[4],
							'bereich' => $row[5],
							'disabled' => $row[6]
						);
					}
					$data = $pns;

				// ==================== ABFRAGE / SUCHE ====================

				} else if (get_get('veranstaltung_details') && preg_match('/^\d+$/', get_get('veranstaltung_details'))) {
					$vid = (int)get_get('veranstaltung_details');
					$query = 'SELECT `v`.`id`, `v`.`name`, `v`.`semester_id`, `s`.`typ` AS `semester_typ`, `s`.`jahr` AS `semester_jahr`, `v`.`dozent_id`, `d`.`first_name`, `d`.`last_name`, `v`.`veranstaltungstyp_id`, `vt`.`abkuerzung`, `vt`.`name` AS `veranstaltungstyp_name`, `v`.`gebaeudewunsch_id`, `v`.`raumwunsch_id`, `v`.`raummeldung`, `v`.`gebaeude_id`, `v`.`raum_id`, `v`.`institut_id`, `i`.`name` AS `institut_name`, `v`.`master_niveau`, `v`.`last_change` '.
						'FROM `veranstaltung` `v` '.
						'LEFT JOIN `semester` `s` ON `s`.`id` = `v`.`semester_id` '.
						'LEFT JOIN `dozent` `d` ON `d`.`id` = `v`.`dozent_id` '.
						'LEFT JOIN `veranstaltungstyp` `vt` ON `vt`.`id` = `v`.`veranstaltungstyp_id` '.
						'LEFT JOIN `institut` `i` ON `i`.`id` = `v`.`institut_id` '.
						'WHERE `v`.`id` = '.esc($vid);
					$result = rquery($query);
					$row = mysqli_fetch_assoc($result);
					if($row) {
						$meta_query = 'SELECT `wochentag`, `stunde`, `woche`, `anzahl_hoerer`, `erster_termin`, `abgabe_pruefungsleistungen`, `opal_link`, `videolink`, `wunsch`, `hinweis`, `related_veranstaltung`, `fester_bbb_raum` FROM `veranstaltung_metadaten` WHERE `veranstaltung_id` = '.esc($vid);
						$meta_result = rquery($meta_query);
						$meta = mysqli_fetch_assoc($meta_result);
						if(!$meta) $meta = array();
						$pn_query = 'SELECT `p`.`id`, `p`.`pruefungsnummer`, `pt`.`name` AS `pruefungstyp` FROM `pruefung` `pr` JOIN `pruefungsnummer` `p` ON `p`.`id` = `pr`.`pruefungsnummer_id` LEFT JOIN `pruefungstyp` `pt` ON `pt`.`id` = `p`.`pruefungstyp_id` WHERE `pr`.`veranstaltung_id` = '.esc($vid);
						$pn_result = rquery($pn_query);
						$pns = array();
						while ($pn_row = mysqli_fetch_row($pn_result)) {
							$pns[] = array(
								'id' => (int)$pn_row[0],
								'pruefungsnummer' => $pn_row[1],
								'pruefungstyp' => $pn_row[2]
							);
						}
						$et_query = 'SELECT `start`, `end`, `gebaeude_id`, `raum_id` FROM `einzelne_termine` WHERE `veranstaltung_id` = '.esc($vid).' ORDER BY `start`';
						$et_result = rquery($et_query);
						$termine = array();
						while ($et_row = mysqli_fetch_row($et_result)) {
							$termine[] = array(
								'start' => $et_row[0],
								'end' => $et_row[1],
								'gebaeude_id' => $et_row[2] !== null ? (int)$et_row[2] : null,
								'raum_id' => $et_row[3] !== null ? (int)$et_row[3] : null
							);
						}
						$data = array(
							'id' => (int)$row['id'],
							'name' => $row['name'],
							'semester' => array(
								'id' => (int)$row['semester_id'],
								'typ' => $row['semester_typ'],
								'jahr' => (int)$row['semester_jahr']
							),
							'dozent' => array(
								'id' => (int)$row['dozent_id'],
								'first_name' => $row['first_name'],
								'last_name' => $row['last_name']
							),
							'veranstaltungstyp' => array(
								'id' => (int)$row['veranstaltungstyp_id'],
								'abkuerzung' => $row['abkuerzung'],
								'name' => $row['veranstaltungstyp_name']
							),
							'wunsch' => array(
								'gebaeude_id' => $row['gebaeudewunsch_id'] !== null ? (int)$row['gebaeudewunsch_id'] : null,
								'raum_id' => $row['raumwunsch_id'] !== null ? (int)$row['raumwunsch_id'] : null,
								'gebaeude_id_zugewiesen' => $row['gebaeude_id'] !== null ? (int)$row['gebaeude_id'] : null,
								'raum_id_zugewiesen' => $row['raum_id'] !== null ? (int)$row['raum_id'] : null,
								'raummeldung' => $row['raummeldung']
							),
							'institut' => array(
								'id' => (int)$row['institut_id'],
								'name' => $row['institut_name']
							),
							'master_niveau' => $row['master_niveau'],
							'last_change' => $row['last_change'],
							'metadaten' => array(
								'wochentag' => $meta['wochentag'] ?? null,
								'stunde' => $meta['stunde'] ?? null,
								'woche' => $meta['woche'] ?? null,
								'anzahl_hoerer' => $meta['anzahl_hoerer'] ?? null,
								'erster_termin' => $meta['erster_termin'] ?? null,
								'abgabe_pruefungsleistungen' => $meta['abgabe_pruefungsleistungen'] ?? null,
								'opal_link' => $meta['opal_link'] ?? null,
								'videolink' => $meta['videolink'] ?? null,
								'hinweis_studenten' => $meta['hinweis'] ?? null,
								'hinweis_raumplanung' => $meta['wunsch'] ?? null,
								'related_veranstaltung_id' => $meta['related_veranstaltung'] !== null ? (int)$meta['related_veranstaltung'] : null,
								'fester_bbb_raum' => $meta['fester_bbb_raum']
							),
							'pruefungsnummern' => $pns,
							'einzelne_termine' => $termine
						);
					} else {
						$data = array('error' => 'Veranstaltung nicht gefunden', 'id' => $vid);
					}
				} else if (get_get('such_veranstaltung')) {
					$term = get_get('such_veranstaltung');
					$limit = (int)(get_get('limit') ?: 50);
					if($limit < 1) $limit = 1; if($limit > 200) $limit = 200;
					$query = 'SELECT `v`.`id`, `v`.`name`, `s`.`typ` AS `semester_typ`, `s`.`jahr` AS `semester_jahr`, `d`.`last_name`, `d`.`first_name`, `vt`.`abkuerzung` AS `typ_abk` '.
						'FROM `veranstaltung` `v` '.
						'LEFT JOIN `semester` `s` ON `s`.`id` = `v`.`semester_id` '.
						'LEFT JOIN `dozent` `d` ON `d`.`id` = `v`.`dozent_id` '.
						'LEFT JOIN `veranstaltungstyp` `vt` ON `vt`.`id` = `v`.`veranstaltungstyp_id` '.
						'WHERE `v`.`name` LIKE '.esc('%'.$term.'%').' '.
						'ORDER BY `s`.`id` DESC, `v`.`name` LIMIT '.esc($limit);
					$result = rquery($query);
					$hits = array();
					while ($row = mysqli_fetch_row($result)) {
						$hits[] = array(
							'id' => (int)$row[0],
							'name' => $row[1],
							'semester' => trim(($row[2] ?? '').' '.($row[3] ?? '')),
							'dozent' => trim(($row[4] ?? '').', '.($row[5] ?? '')),
							'typ' => $row[6]
						);
					}
					$data = $hits;
				} else if (get_get('such_dozent')) {
					$term = get_get('such_dozent');
					$limit = (int)(get_get('limit') ?: 50);
					if($limit < 1) $limit = 1; if($limit > 200) $limit = 200;
					$query = 'SELECT `id`, `last_name`, `first_name`, `titel_id`, `ausgeschieden` FROM `dozent` '.
						'WHERE `last_name` LIKE '.esc('%'.$term.'%').' OR `first_name` LIKE '.esc('%'.$term.'%').' '.
						'ORDER BY `last_name`, `first_name` LIMIT '.esc($limit);
					$result = rquery($query);
					$hits = array();
					while ($row = mysqli_fetch_row($result)) {
						$hits[] = array(
							'id' => (int)$row[0],
							'last_name' => $row[1],
							'first_name' => $row[2],
							'titel_id' => $row[3] !== null ? (int)$row[3] : null,
							'ausgeschieden' => $row[4]
						);
					}
					$data = $hits;
				} else if (get_get('stundenplan')) {
					// Stundenplan eines Dozenten (oder sich selbst per auth_code-Bezug)
					$dozent_id = (int)get_get('stundenplan');
					$semester = get_get('semester');
					if($semester == 'current') $semester = get_current_semester_id();
					$where = array('`v`.`dozent_id` = '.esc($dozent_id));
					if($semester) $where[] = '`v`.`semester_id` = '.esc($semester);
					$query = 'SELECT `v`.`id`, `v`.`name`, `vt`.`abkuerzung` AS `typ`, `vm`.`wochentag`, `vm`.`stunde`, `vm`.`woche`, `g`.`abkuerzung` AS `gebaeude`, `r`.`raumnummer`, `vm`.`erster_termin`, `vm`.`anzahl_hoerer` '.
						'FROM `veranstaltung` `v` '.
						'LEFT JOIN `veranstaltung_metadaten` `vm` ON `vm`.`veranstaltung_id` = `v`.`id` '.
						'LEFT JOIN `veranstaltungstyp` `vt` ON `vt`.`id` = `v`.`veranstaltungstyp_id` '.
						'LEFT JOIN `gebaeude` `g` ON `g`.`id` = `v`.`gebaeude_id` '.
						'LEFT JOIN `raum` `r` ON `r`.`id` = `v`.`raum_id` '.
						'WHERE '.join(' AND ', $where).' '.
						'ORDER BY `vm`.`wochentag`, `vm`.`stunde`';
					$result = rquery($query);
					$entries = array();
					while ($row = mysqli_fetch_row($result)) {
						$entries[] = array(
							'veranstaltung_id' => (int)$row[0],
							'name' => $row[1],
							'typ' => $row[2],
							'wochentag' => $row[3],
							'stunde' => $row[4],
							'woche' => $row[5],
							'gebaeude' => $row[6],
							'raum' => $row[7],
							'erster_termin' => $row[8],
							'anzahl_hoerer' => $row[9]
						);
					}
					$data = array(
						'dozent_id' => $dozent_id,
						'semester_id' => $semester ? (int)$semester : null,
						'eintraege' => $entries
					);
				} else if (get_get('statistiken')) {
					$data = array(
						'veranstaltungen_gesamt' => (int)get_single_row_from_query('SELECT COUNT(*) FROM `veranstaltung`')[0],
						'dozenten_gesamt' => (int)get_single_row_from_query('SELECT COUNT(*) FROM `dozent`')[0],
						'dozenten_aktiv' => (int)get_single_row_from_query('SELECT COUNT(*) FROM `dozent` WHERE `ausgeschieden` = "0"')[0],
						'studiengaenge' => (int)get_single_row_from_query('SELECT COUNT(*) FROM `studiengang`')[0],
						'module' => (int)get_single_row_from_query('SELECT COUNT(*) FROM `modul`')[0],
						'institute' => (int)get_single_row_from_query('SELECT COUNT(*) FROM `institut`')[0],
						'gebaeude' => (int)get_single_row_from_query('SELECT COUNT(*) FROM `gebaeude`')[0],
						'raeume' => (int)get_single_row_from_query('SELECT COUNT(*) FROM `raum`')[0],
						'pruefungsnummern' => (int)get_single_row_from_query('SELECT COUNT(*) FROM `pruefungsnummer`')[0],
						'veranstaltungen_aktuelles_semester' => (int)get_single_row_from_query('SELECT COUNT(*) FROM `veranstaltung` WHERE `semester_id` = '.esc(get_current_semester_id()))[0],
						'pruefungen_aktuelles_semester' => (int)get_single_row_from_query('SELECT COUNT(DISTINCT `p`.`id`) FROM `pruefung` `p` JOIN `veranstaltung` `v` ON `v`.`id` = `p`.`veranstaltung_id` WHERE `v`.`semester_id` = '.esc(get_current_semester_id()))[0],
						'sperrvermerk_aktuelles_semester' => (int)get_single_row_from_query('SELECT COUNT(*) FROM `sperrvermerk` WHERE `semester_id` = '.esc(get_current_semester_id()).' AND `enabled` = "1"')[0]
					);
				} else if (get_get('konflikte')) {
					// Zeitkonflikte im aktuellen Semester (gleicher Dozent, gleicher Wochentag, gleiche Stunde, aber zwei verschiedene Veranstaltungen)
					$semester = get_get('konflikte_semester') ?: get_current_semester_id();
					$query = 'SELECT `d`.`id` AS `dozent_id`, `d`.`last_name`, `d`.`first_name`, `vm`.`wochentag`, `vm`.`stunde`, COUNT(*) AS `anzahl` '.
						'FROM `veranstaltung` `v` '.
						'JOIN `veranstaltung_metadaten` `vm` ON `vm`.`veranstaltung_id` = `v`.`id` '.
						'JOIN `dozent` `d` ON `d`.`id` = `v`.`dozent_id` '.
						'WHERE `v`.`semester_id` = '.esc($semester).' '.
						'AND `vm`.`wochentag` IS NOT NULL AND `vm`.`stunde` IS NOT NULL '.
						'GROUP BY `d`.`id`, `vm`.`wochentag`, `vm`.`stunde` '.
						'HAVING COUNT(*) > 1 '.
						'ORDER BY `anzahl` DESC, `d`.`last_name`';
					$result = rquery($query);
					$conflicts = array();
					while ($row = mysqli_fetch_row($result)) {
						$detail_query = 'SELECT `v`.`id`, `v`.`name`, `vt`.`abkuerzung` FROM `veranstaltung` `v` '.
							'JOIN `veranstaltung_metadaten` `vm` ON `vm`.`veranstaltung_id` = `v`.`id` '.
							'LEFT JOIN `veranstaltungstyp` `vt` ON `vt`.`id` = `v`.`veranstaltungstyp_id` '.
							'WHERE `v`.`dozent_id` = '.esc($row[0]).' AND `vm`.`wochentag` = '.esc($row[3]).' AND `vm`.`stunde` = '.esc($row[4]).' AND `v`.`semester_id` = '.esc($semester);
						$detail_result = rquery($detail_query);
						$va_list = array();
						while ($vr = mysqli_fetch_row($detail_result)) {
							$va_list[] = array('id' => (int)$vr[0], 'name' => $vr[1], 'typ' => $vr[2]);
						}
						$conflicts[] = array(
							'dozent_id' => (int)$row[0],
							'dozent' => trim($row[1].', '.$row[2]),
							'wochentag' => $row[3],
							'stunde' => $row[4],
							'anzahl' => (int)$row[5],
							'veranstaltungen' => $va_list
						);
					}
					$data = array('semester_id' => (int)$semester, 'konflikte' => $conflicts);
				} else if (get_get('unvollstaendig')) {
					// Audit: Veranstaltungen mit fehlenden Pflicht-/wichtigen Feldern im aktuellen Semester
					$semester = get_get('unvollstaendig_semester') ?: get_current_semester_id();
					$query = 'SELECT `v`.`id`, `v`.`name`, `d`.`last_name`, `d`.`first_name`, '.
						'`vm`.`wochentag` IS NULL OR `vm`.`stunde` IS NULL AS `fehlt_zeit`, '.
						'`v`.`gebaeudewunsch_id` IS NULL AND `v`.`raumwunsch_id` IS NULL AS `fehlt_raum`, '.
						'NOT EXISTS (SELECT 1 FROM `pruefung` `p` WHERE `p`.`veranstaltung_id` = `v`.`id`) AS `fehlt_pruefungsnummer`, '.
						'`vm`.`anzahl_hoerer` IS NULL AS `fehlt_hoererzahl`, '.
						'`vm`.`hinweis` IS NULL AS `fehlt_hinweis` '.
						'FROM `veranstaltung` `v` '.
						'LEFT JOIN `veranstaltung_metadaten` `vm` ON `vm`.`veranstaltung_id` = `v`.`id` '.
						'LEFT JOIN `dozent` `d` ON `d`.`id` = `v`.`dozent_id` '.
						'WHERE `v`.`semester_id` = '.esc($semester).' '.
						'AND ( `vm`.`wochentag` IS NULL OR `vm`.`stunde` IS NULL '.
						'OR (`v`.`gebaeudewunsch_id` IS NULL AND `v`.`raumwunsch_id` IS NULL) '.
						'OR NOT EXISTS (SELECT 1 FROM `pruefung` `p` WHERE `p`.`veranstaltung_id` = `v`.`id`) '.
						'OR `vm`.`anzahl_hoerer` IS NULL OR `vm`.`hinweis` IS NULL) '.
						'ORDER BY `v`.`name`';
					$result = rquery($query);
					$incomplete = array();
					while ($row = mysqli_fetch_row($result)) {
						$missing = array();
						if((int)$row[4]) $missing[] = 'wochentag_oder_stunde';
						if((int)$row[5]) $missing[] = 'raumwunsch';
						if((int)$row[6]) $missing[] = 'pruefungsnummer';
						if((int)$row[7]) $missing[] = 'anzahl_hoerer';
						if((int)$row[8]) $missing[] = 'hinweis_studenten';
						$incomplete[] = array(
							'id' => (int)$row[0],
							'name' => $row[1],
							'dozent' => trim(($row[2] ?? '').', '.($row[3] ?? '')),
							'fehlende_felder' => $missing
						);
					}
					$data = array('semester_id' => (int)$semester, 'unvollstaendige_veranstaltungen' => $incomplete);
				} else if (get_get('letzte_aenderungen')) {
					$anzahl = (int)(get_get('letzte_aenderungen') === '1' ? 20 : get_get('letzte_aenderungen'));
					if($anzahl < 1) $anzahl = 20; if($anzahl > 100) $anzahl = 100;
					$semester = get_get('letzte_aenderungen_semester');
					$query = 'SELECT `v`.`id`, `v`.`name`, `v`.`last_change`, `d`.`last_name`, `d`.`first_name`, `s`.`typ`, `s`.`jahr` '.
						'FROM `veranstaltung` `v` '.
						'LEFT JOIN `dozent` `d` ON `d`.`id` = `v`.`dozent_id` '.
						'LEFT JOIN `semester` `s` ON `s`.`id` = `v`.`semester_id` '.
						'WHERE `v`.`last_change` IS NOT NULL ';
					if($semester) $query .= 'AND `v`.`semester_id` = '.esc($semester).' ';
					$query .= 'ORDER BY `v`.`last_change` DESC LIMIT '.esc($anzahl);
					$result = rquery($query);
					$recent = array();
					while ($row = mysqli_fetch_row($result)) {
						$recent[] = array(
							'id' => (int)$row[0],
							'name' => $row[1],
							'last_change' => $row[2],
							'dozent' => trim(($row[3] ?? '').', '.($row[4] ?? '')),
							'semester' => trim(($row[5] ?? '').' '.($row[6] ?? ''))
						);
					}
					$data = $recent;

				// ==================== BESTEHENDES VERANSTALTUNGS-LISTING ====================

				} else {
					$query = 'select `v`.`veranstaltung_typ`, `v`.`veranstaltung_name`, `v`.`gebaeude_id`, `v`.`raum_id`, concat(`v`.`last_name`, ", ", `v`.`first_name`) `dozent_name`, `v`.`wochentag`, `v`.`stunde`, `v`.`woche`, `v`.`erster_termin`, `hinweis`, `ve`.`id` `veranstaltung_id` from `view_veranstaltung_komplett` `v` JOIN `veranstaltung` `ve` ON `ve`.`id` = `v`.`veranstaltung_id`';
					$where = array();

					if(get_get('type')) {
						$where[] = '`v`.`veranstaltung_typ` = '.esc(get_get('type'));
					}

					if(get_get('gebaeude')) {
						$id = get_gebaeude_id_by_abkuerzung(get_get('gebaeude'));
						if($id) {
							$where[] = '`v`.`gebaeude_id` = '.esc($id);
						}
					}

					if(get_get('first_name') && get_get('last_name')) {
						$id = get_dozent_id(get_get('first_name'), get_get('last_name'));
						if($id) {
							$where[] = '`v`.`dozent_id` = '.esc($id);
						}
					}
					$semester = get_get('semester');
					if($semester == "current") {
						$semester = get_current_semester_id();
					}

					if($semester) {
						$where[] = '`ve`.`semester_id` = '.esc($semester);
					}

					if(get_get('institut')) {
						$where[] = '`ve`.`institut_id` = '.esc(get_get('institut'));
					}

					if(get_get('studiengang')) {
						$where[] = '`v`.`veranstaltung_id` IN(select veranstaltung_id as id from view_veranstaltung_nach_studiengang where studiengang_id = '.esc(get_get('studiengang')).')';
					}


					if(count($where)) {
						$query .= ' WHERE '.join(' AND ', $where);
					}

					$result = rquery($query);
					$data = array();
					if(!get_get('notitle')) {
						$data[0] = array('Typ', 'Name', 'Gebäude', 'Raum', 'Dozent', 'Wochentag', 'Stunde', 'Wochenrhythmus', 'Erster Termin', 'Hinweis', 'Einzelne Termine');
					}
					while ($row = mysqli_fetch_row($result)) {
						$row[2] = get_gebaeude_abkuerzung($row[2]);
						$row[3] = get_raum_name_by_id($row[3]);
						if(get_get('datetype') == 'discordian') {
							$row[8] = discordian_date($row[8]);
						} else if(get_get('datetype') == 'unix') {
							$row[8] = strtotime($row[8]);
						}

						foreach ($row as $key => $value) {
							if (is_null($value)) {
								$row[$key] = "";
							}
						}


						$row[9] = replace_hinweis_with_graphics($row[9]);
						$row[10] = nice_einzelne_veranstaltung_by_id($row[10]);
						$data[] = $row;
					}
				}

				api_emit_json($data);

				$log_data['error'] = 0;
				api_log_call(get_get('auth_code'), $log_data);
			} else {
				$log_data['error'] = 4;
				print "Der Letzte Aufruf ist weniger als 10 Sekunden her.";
				api_log_call(get_get('auth_code'), $log_data);
			}
		} else {
			print "Der Auth-Code ist leider nicht richtig.";
			$log_data['error'] = 2;
			$query = 'INSERT INTO `api_log` (`auth_code_id`, `time`, `parameter`, `ip`, `api_error_code_id`) VALUES (null, now(), '.esc($log_data['parameters']).', '.esc($log_data['ip']).', '.esc($log_data['error']).')';
			rquery($query);
		}

	} else {
		$page_title = 'Vorlesungsverzeichnis '.$GLOBALS['university_name'].' | API';
		include("header.php");
?>
	<div id="main">
		<a href="startseite" border="0"><?php print_uni_logo(); ?> </a><br>
<?php
		print get_demo_expiry_time();
?>
		<h1>Vorlesungsverzeichnis <?php print $GLOBALS['university_name']; ?></h1>
		<h2>Was ist das hier?</h2>
		Diese API erlaubt automatisierte Zugriffe auf die öffentlichen Daten des Vorlesungsverzeichnis der <?php print $GLOBALS['university_name']; ?>. Über diese Schnittstelle
		lassen sich einfach automatisierte Zugriffe erstellen, die z. B. für selbstentwickelte Software zur Verfügung stehen.


		<h2>Wie kann ich es benutzen?</h2>

		Jeder, der Interesse an einem API-Zugang hat, kann uns über die <a href="kontakt.php">Kontaktseite</a> erreichen und erhält einen API-Zugangsaccount. Mit diesem Account ist es dann möglich,
				<a href="<?php print "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?>"><?php print htmlentities("http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"); ?></a>
				mit dem Parameter <pre>?auth_code=$AUTH_CODE</pre> aufzurufen.

		<h2>Wie sind die Daten aufgebaut, die aus diesem API kommen?</h2>

		<h3>Das normale Vorlesungsverzeichnis</h3>

		Die Daten sind im JSON-Format. Im ersten Element stehen die jeweligen Überschriften, die sich immer
		auf einzelne Veranstaltungen beziehen. Durch die Überschriften sind diese Spalten selbsterklärend.

		Folgende Parameter können weiterhin hinzugefügt werden:

		<table>
			<tr>
				<th>Parameter</th>
				<th>Beschreibung</th>
			</tr>
			<tr>
				<td><pre>notitle=1</pre></td>
				<td>Deaktiviert die Überschriften im ersten Element</td>
			</tr>
			<tr>
				<td><pre>type=Seminar</pre></td>
				<td>
					Listet je nur Veranstaltungen dieses Types.
				</td>
			</tr>
			<tr>
				<td><pre>gebaeude=BZW</pre></td>
				<td>
					Listet je nur Veranstaltungen in diesem Gebäude. Als Bezeichnung muss die Abkürzung des Gebäudes gewählt werden. Eine Liste der Gebäude gibt es mit dem
					Parameter <pre>?auth_code=$AUTH_CODE&gebaeude_liste=1</pre>
				</td>
			</tr>
			<tr>
				<td><pre>first_name=Holm</pre> und <pre>last_name=Bräuer</pre></td>
				<td>
					Listet nur Veranstaltungen dieser Dozenten auf. Beide Parameter müssen immer zusammen vorkommen.
					Eine Liste der Dozenten gibt es mit dem Parameter <pre>?auth_code=$AUTH_CODE&dozenten_liste=1</pre>

				</td>
			</tr>
			<tr>
				<td><pre>datetype=discordian</pre></td>
				<td>Stellt die Datenausgaben auf den diskordianischen Kalendar um.</td>
			</tr>
			<tr>
				<td><pre>datetype=unix</pre></td>
				<td>Stellt die Datenausgaben als Unix-Zeitstempel dar.</td>
			</tr>
			<tr>
				<td><pre>pruefungen=1</pre> und <pre>studiengang=$STUDIENGANG</pre></td>
				<td>Listet alle Prüfungsnummern und Prüfungstypen eines Studienganges auf.</td>
			</tr>
			<tr>
				<td><pre>semester=1</pre></td>
				<td>Listet Veranstaltungen aus dem Semester mit der ID 1 auf (semester=current holt das aktuelle Semester).</td>
			</tr>
			<tr>
				<td><pre>institut=1</pre></td>
				<td>Listet Veranstaltungen aus dem Institut mit der ID 1 auf.</td>
			</tr>
		</table>

		Die Daten müssen in der UTF8-Kodierung übergeben werden, werden aber im JSON-Format ASCII-kompatibel maschiert.

		<h3>Liste aller Gebäude</h3>

		Mit dem Parameter <pre>?auth_code=$AUTH_CODE&gebaeude_liste=1</pre> wird im JSON-Format eine Liste aller Gebäude zurückgegeben.
		Diese besteht je aus dem Gebäudenamen und der Abkürzung.

		<h3>Liste der Institute</h3>

		Mit dem Parameter <pre>?auth_code=$AUTH_CODE&institute_liste=1</pre> wird im JSON-Format eine Liste aller Institute (bestehend aus ID und Name) zurückgegeben.

		<h3>Liste der Semester</h3>

		Mit dem Parameter <pre>?auth_code=$AUTH_CODE&semester_liste=1</pre> wird im JSON-Format eine Liste aller Semester (bestehend aus ID, Semesterjahr und Semestertyp) zurückgegeben.

		<h3>Liste aller Studiengänge</h3>

		Mit dem Parameter <pre>?auth_code=$AUTH_CODE&studiengang_liste=1</pre> wird im JSON-Format eine Liste aller Studiengänge und deren IDs zurückgegeben.

		<h3>Liste aller Dozenten</h3>

		Mit dem Parameter <pre>?auth_code=$AUTH_CODE&dozenten_liste=1</pre> wird im JSON-Format eine Liste aller Dozenten zurückgegeben.

		<h3>Liste aller Veranstaltungstypen</h3>

		Mit dem Parameter <pre>?auth_code=$AUTH_CODE&veranstaltungstypen=1</pre> wird im JSON-Format eine Liste aller Veranstaltungstypen zurückgegeben.

		<h3 id="neu">Was gibt es Neues? Nützliche Endpoints</h3>

		<p>Alle hier aufgelisteten Endpoints geben strukturierte JSON-Objekte mit klaren Feldnamen zurück und sind für Programmierer deutlich einfacher zu parsen als die Roh-Liste oben.</p>

		<h4>Stammdaten-Listen</h4>
		<table>
			<tr><th>Parameter</th><th>Beschreibung</th></tr>
			<tr><td><pre>raum_liste=1</pre></td><td>Liste aller Räume (ID, Gebäude, Raumnummer, Name, Kapazität, Barrierefreiheit).</td></tr>
			<tr><td><pre>sprachen_liste=1</pre></td><td>Liste der unterstützten Sprachen.</td></tr>
			<tr><td><pre>praesenztyp_liste=1</pre></td><td>Liste der Präsenztypen (Präsenz, Online, Hybrid, …).</td></tr>
			<tr><td><pre>bezug_liste=1</pre></td><td>Liste der Veranstaltungs-Bezüge.</td></tr>
			<tr><td><pre>wochentag_liste=1</pre></td><td>Liste der Wochentage.</td></tr>
			<tr><td><pre>stunden_liste=1</pre></td><td>Liste der Unterrichtsstunden.</td></tr>
			<tr><td><pre>wochenrhythmus_liste=1</pre></td><td>Liste der Wochenrhythmen (wöchentlich, 14-täglich, …).</td></tr>
			<tr><td><pre>titel_liste=1</pre></td><td>Liste der Titel (Prof., Dr., …).</td></tr>
			<tr><td><pre>bereich_liste=1</pre></td><td>Liste der Bereiche.</td></tr>
			<tr><td><pre>fach_liste=1</pre></td><td>Liste der Fächer.</td></tr>
			<tr><td><pre>pruefungstyp_liste=1</pre></td><td>Liste der Prüfungstypen.</td></tr>
			<tr><td><pre>modul_liste=1</pre></td><td>Liste aller Module (mit Studiengang, Bereich, Fach, Semester, Modulbezeichnung).</td></tr>
			<tr><td><pre>pruefungsnummer_liste=1</pre></td><td>Liste aller Prüfungsnummern (mit Modul, Studiengang, Prüfungstyp, Bereich, Status).</td></tr>
		</table>

		<h4>Abfragen / Suchen</h4>
		<table>
			<tr><th>Parameter</th><th>Beschreibung</th></tr>
			<tr><td><pre>veranstaltung_details=42</pre></td><td>Alle Details zu einer Veranstaltung (Metadaten, Prüfungsnummern, Einzeltermine) — strukturiertes JSON.</td></tr>
			<tr><td><pre>such_veranstaltung=Algo&amp;limit=10</pre></td><td>Sucht Veranstaltungen nach Name (LIKE), liefert id/name/Semester/Dozent/Typ.</td></tr>
			<tr><td><pre>such_dozent=Bräuer&amp;limit=10</pre></td><td>Sucht Dozenten nach Vor- oder Nachname.</td></tr>
			<tr><td><pre>stundenplan=42&amp;semester=current</pre></td><td>Stundenplan eines Dozenten (ID) optional für ein bestimmtes Semester.</td></tr>
			<tr><td><pre>letzte_aenderungen=20</pre></td><td>Die 20 zuletzt geänderten Veranstaltungen (optional mit <pre>&letzte_aenderungen_semester=X</pre> filtern).</td></tr>
		</table>

		<h4>Analyse &amp; Audit</h4>
		<table>
			<tr><th>Parameter</th><th>Beschreibung</th></tr>
			<tr><td><pre>statistiken=1</pre></td><td>Aggregierte Zahlen: Veranstaltungen, Dozenten, Studiengänge, Module, Räume, Prüfungen, Sperrvermerk-Status.</td></tr>
			<tr><td><pre>konflikte=1</pre></td><td>Zeitkonflikte im aktuellen Semester (gleicher Dozent/Wochentag/Stunde für mehrere Veranstaltungen). Optional <pre>&konflikte_semester=X</pre>.</td></tr>
			<tr><td><pre>unvollstaendig=1</pre></td><td>Veranstaltungen mit fehlenden Pflichtfeldern (Wochentag, Raumwunsch, Prüfungsnummer, Hörerzahl, Hinweis). Optional <pre>&unvollstaendig_semester=X</pre>.</td></tr>
		</table>

		<h3>Beispielaufruf</h3>

		<pre><?php print htmlentities("http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"); ?>?auth_code=$AUTH_CODE&amp;notitle=1&amp;first_name=Holm&amp;last_name=Bräuer&amp;gebaeude=BZW</pre>

		Zeigt, ohne dass die Titel in der ersten Zeile sind, die Veranstaltungen von Holm Bräuer im BZW.

		<h3>Beispiel: strukturierte Veranstaltungsdetails</h3>

		<pre><?php print htmlentities("http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"); ?>?auth_code=$AUTH_CODE&amp;veranstaltung_details=42</pre>

		<h2>Was gibt es für Beschränkungen?</h2>

		Zwischen zwei Aufrufen der API müssen mindestens 10 Sekunden liegen, um die Datenbank nicht nutzlos zu belasten. Bei übermäßiger Benutzung behalten wir uns
		vor, die API-Zugänge ohne Rückmeldung zu kündigen.

		<h2>Fragen und Feature-Wünsche</h2>

		Sollten Sie zur Benutzung der API Fragen haben oder benötigen Sie andere Daten, als die API sie gerade zur Verfügung stellt, zögern Sie nicht, uns zu <a href="kontakt.php">kontaktieren</a>.
<?php
		include("footer.php");
	}
?>
