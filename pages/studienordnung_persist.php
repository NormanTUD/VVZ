<?php
/*
 * Studienordnung-Persistenz: reine DB-Helper für den Studienordnung-Import.
 *
 * Diese Datei enthält KEINEN Seitenkopf, keine Auth-Checks, keine Templates.
 * Sie kann deshalb gefahrlos von Unit-Tests eingebunden werden.
 *
 * Erwartete Hilfsfunktionen (aus functions.php / mysql.php):
 *   - esc($v)        – SQL-Escape (gibt "..." zurück oder NULL)
 *   - rquery($sql)   – führt SQL aus
 *   - get_single_row_from_query($sql) – liefert Spaltenwert oder null
 *
 * Diese Datei wird von pages/studienordnung_import.php geladen und stellt
 * die Funktionen für commit_v2, soi_persist_semester_metadata etc. bereit.
 */

if(!function_exists('soi_ensure_pruefungstyp')) {
	function soi_ensure_pruefungstyp($name) {
		if(!$name) return null;
		$existing = get_single_row_from_query('SELECT id FROM `pruefungstyp` WHERE `name` = '.esc($name).' LIMIT 1');
		if(!is_null($existing) && $existing !== '' && $existing !== false) return (int)$existing;
		rquery('INSERT INTO `pruefungstyp` (`name`) VALUES ('.esc($name).')');
		$new = get_single_row_from_query('SELECT id FROM `pruefungstyp` WHERE `name` = '.esc($name).' LIMIT 1');
		if(!is_null($new) && $new !== '' && $new !== false) return (int)$new;
		return null;
	}
}

if(!function_exists('soi_generate_pruefungsnummer')) {
	function soi_generate_pruefungsnummer($suffix, $pruefungstyp, $lp, &$seen) {
		$t = mb_strtolower(trim((string)$pruefungstyp));
		$type_code = 'KL';
		if(strpos($t, 'klausur') !== false) $type_code = 'KL';
		elseif(strpos($t, 'mündlich') !== false || strpos($t, 'muendlich') !== false) $type_code = 'MP';
		elseif(strpos($t, 'hausarbeit') !== false) $type_code = 'HA';
		elseif(strpos($t, 'seminararbeit') !== false) $type_code = 'SA';
		elseif(strpos($t, 'referat') !== false) $type_code = 'RF';
		elseif(strpos($t, 'protokoll') !== false) $type_code = 'PK';
		elseif(strpos($t, 'präsentation') !== false || strpos($t, 'praesentation') !== false) $type_code = 'PR';
		elseif(strpos($t, 'portfolio') !== false) $type_code = 'PF';
		elseif(strpos($t, 'bericht') !== false) $type_code = 'BE';
		elseif(strpos($t, 'exposé') !== false || strpos($t, 'expose') !== false) $type_code = 'EX';
		elseif(strpos($t, 'rezension') !== false) $type_code = 'RZ';
		elseif(strpos($t, 'bibliographie') !== false) $type_code = 'BG';
		elseif(strpos($t, 'thesenpapier') !== false) $type_code = 'TP';
		elseif(strpos($t, 'bachelorarbeit') !== false) $type_code = 'BA';
		elseif(strpos($t, 'masterarbeit') !== false) $type_code = 'MA';
		elseif(strpos($t, 'kolloquium') !== false) $type_code = 'KO';

		$base = preg_replace('/[^A-Za-z0-9]/', '', $suffix) . '-' . $type_code . ($lp ? '-'.$lp : '');
		$nr = $base;
		$i = 2;
		while(isset($seen[$nr])) {
			$nr = $base.'-'.$i;
			$i++;
		}
		$seen[$nr] = true;
		return $nr;
	}
}

if(!function_exists('soi_sanitize_for_json')) {
	function soi_sanitize_for_json($v) {
		if(is_array($v)) {
			$out = array();
			foreach($v as $k => $vv) { $out[$k] = soi_sanitize_for_json($vv); }
			return $out;
		}
		if(is_string($v)) {
			return preg_replace_callback('/[\x00-\x08\x0b\x0c\x0e-\x1f]/', function($m) {
				return '\\u' . str_pad(dechex(ord($m[0])), 4, '0', STR_PAD_LEFT);
			}, $v);
		}
		return $v;
	}
}

if(!function_exists('soi_ensure_studiengang')) {
	function soi_ensure_studiengang($name, $degree, $institut_id) {
		if(!$name) return null;
		$full_name = trim($name);
		if($degree && mb_stripos($full_name, $degree) === false) {
			$full_name = $degree.' '.$full_name;
		}
		$full_name = mb_substr($full_name, 0, 100);

		$existing = get_single_row_from_query('SELECT id FROM `studiengang` WHERE `name` = '.esc($full_name).' LIMIT 1');
		if(!is_null($existing) && $existing !== '' && $existing !== false) return (int)$existing;
		$existing = get_single_row_from_query('SELECT id FROM `studiengang` WHERE `name` = '.esc(mb_substr($name, 0, 100)).' LIMIT 1');
		if(!is_null($existing) && $existing !== '' && $existing !== false) return (int)$existing;

		rquery('INSERT INTO `studiengang` (`name`, `institut_id`, `studienordnung`, `order_key`) VALUES ('.
			esc($full_name).', '.esc((int)$institut_id).', '.esc('importiert am '.date('Y-m-d H:i')).', 999999)');
		$new = get_single_row_from_query('SELECT id FROM `studiengang` WHERE `name` = '.esc($full_name).' LIMIT 1');
		if(!is_null($new) && $new !== '' && $new !== false) return (int)$new;
		return null;
	}
}

if(!function_exists('soi_persist_semester_metadata')) {
	function soi_persist_semester_metadata(int $modul_id, array $anlage2_row): int {
		if($modul_id <= 0) return 0;
		if(empty($anlage2_row['semester']) || !is_array($anlage2_row['semester'])) return 0;

		$total_lp = isset($anlage2_row['lp']) && is_numeric($anlage2_row['lp']) ? (int)$anlage2_row['lp'] : null;
		$n_sem = count($anlage2_row['semester']);

		$lp_per_sem = array();
		if($total_lp !== null && $n_sem > 0) {
			$base = intdiv($total_lp, $n_sem);
			$rest = $total_lp - ($base * $n_sem);
			for($i = 0; $i < $n_sem; $i++) {
				$lp_per_sem[$i] = $base + ($i < $rest ? 1 : 0);
			}
		}

		$written = 0;
		foreach($anlage2_row['semester'] as $idx => $sem) {
			$sem_n = isset($sem['semester']) ? (int)$sem['semester'] : ($idx + 1);
			$pl_n = isset($sem['pl_count']) ? (int)$sem['pl_count'] : 0;
			$lp = isset($lp_per_sem[$idx]) ? $lp_per_sem[$idx] : null;
			if($lp === null && $pl_n === 0) continue;

			$query = 'INSERT INTO `modul_nach_semester_metadata` (`modul_id`, `semester`, `credit_points`, `anzahl_pruefungsleistungen`) VALUES ('.
				esc($modul_id).', '.esc($sem_n).', '.esc($lp).', '.esc($pl_n).
				') ON DUPLICATE KEY UPDATE `anzahl_pruefungsleistungen` = '.esc($pl_n);
			if($lp !== null) {
				$query = 'INSERT INTO `modul_nach_semester_metadata` (`modul_id`, `semester`, `credit_points`, `anzahl_pruefungsleistungen`) VALUES ('.
					esc($modul_id).', '.esc($sem_n).', '.esc($lp).', '.esc($pl_n).
					') ON DUPLICATE KEY UPDATE `credit_points` = '.esc($lp).', `anzahl_pruefungsleistungen` = '.esc($pl_n);
			}
			rquery($query);
			$written++;
		}
		return $written;
	}
}

if(!function_exists('soi_find_anlage2_for_modul')) {
	function soi_find_anlage2_for_modul(array $anlage2_rows, string $modulnummer): ?array {
		foreach($anlage2_rows as $row) {
			if(isset($row['modulnummer']) && trim((string)$row['modulnummer']) === $modulnummer) {
				return $row;
			}
		}
		return null;
	}
}

if(!function_exists('soi_rolle_for_section')) {
	function soi_rolle_for_section(?string $section): string {
		$s = mb_strtolower(trim((string)$section));
		if($s === '') return 'pflicht';
		if(strpos($s, 'kernbereich') !== false) return 'kernbereich';
		if(strpos($s, 'ergänzungsbereich') !== false || strpos($s, 'ergaenzungsbereich') !== false) return 'ergaenzungsbereich';
		if(strpos($s, 'wahlpflicht') !== false) return 'wahlpflicht';
		if(strpos($s, 'hauptfach') !== false) return 'hauptfach';
		if(strpos($s, 'nebenfach') !== false) return 'nebenfach';
		if(strpos($s, 'grundlagen') !== false) return 'pflicht';
		if(strpos($s, 'aufbau') !== false) return 'pflicht';
		if(strpos($s, 'vertiefung') !== false) return 'pflicht';
		if(strpos($s, 'spezialisierung') !== false) return 'wahlpflicht';
		if(strpos($s, 'einführung') !== false) return 'pflicht';
		return 'sonstige';
	}
}

if(!function_exists('soi_persist_anlage2_detailed')) {
	function soi_persist_anlage2_detailed(int $modul_id, int $import_id, array $anlage2_row): int {
		if($modul_id <= 0 || empty($anlage2_row['semester']) || !is_array($anlage2_row['semester'])) return 0;
		$written = 0;
		foreach($anlage2_row['semester'] as $sem) {
			$sem_n = isset($sem['semester']) ? (int)$sem['semester'] : 0;
			if($sem_n < 1 || $sem_n > 12) continue;
			$sws = isset($sem['sws']) && is_array($sem['sws']) ? $sem['sws'] : array();

			$fields = array('sws_vorlesung', 'sws_uebung', 'sws_seminar', 'sws_tutorium', 'sws_sprachkurs', 'sws_praktikum', 'sws_sonstige');
			$values = array();
			$total = 0.0;
			for($i = 0; $i < 7; $i++) {
				$v = isset($sws[$i]) ? trim((string)$sws[$i]) : '';
				if($v !== '' && $v !== '*' && is_numeric($v)) {
					$values[$fields[$i]] = (float)$v;
					$total += (float)$v;
				} else {
					$values[$fields[$i]] = null;
				}
			}
			$lp = isset($anlage2_row['lp']) && is_numeric($anlage2_row['lp']) ? (float)$anlage2_row['lp'] : null;
			$pl_count = isset($sem['pl_count']) ? (int)$sem['pl_count'] : 0;

			$cols = array('modul_id', 'import_id', 'semester');
			$vals = array((int)$modul_id, (int)$import_id, (int)$sem_n);
			$updates = array();
			foreach($fields as $f) {
				$cols[] = $f; $vals[] = $values[$f];
				if($values[$f] !== null) $updates[] = "`$f` = VALUES(`$f`)";
			}
			$cols[] = 'sws_total'; $vals[] = $total > 0 ? $total : null;
			$cols[] = 'pruefungsleistung'; $vals[] = $pl_count > 0 ? 'Ja' : null;
			$cols[] = 'lp'; $vals[] = $lp;
			if($total > 0) $updates[] = '`sws_total` = VALUES(`sws_total`)';
			if($lp !== null) $updates[] = '`lp` = VALUES(`lp`)';
			if($pl_count > 0) $updates[] = "`pruefungsleistung` = VALUES(`pruefungsleistung`)";

			$col_list = '`'.implode('`,`', $cols).'`';
			$val_list = implode(',', array_map('esc', $vals));
			$sql = "INSERT INTO `modul_anlage2` ($col_list) VALUES ($val_list)";
			if(!empty($updates)) $sql .= ' ON DUPLICATE KEY UPDATE '.implode(', ', $updates);
			try { rquery($sql); $written++; } catch(\Throwable $e) { }

			try {
				rquery('INSERT IGNORE INTO `modul_nach_semester` (`modul_id`, `semester`) VALUES ('.esc($modul_id).', '.esc($sem_n).')');
			} catch(\Throwable $e) { }
		}
		return $written;
	}
}

if(!function_exists('soi_persist_modul_zuordnung')) {
	function soi_persist_modul_zuordnung(int $modul_id, int $import_id, int $studiengang_id, string $rolle, $lp = null): bool {
		if($modul_id <= 0 || $studiengang_id <= 0) return false;
		$valid = array('kernbereich','ergaenzungsbereich','wahlpflicht','pflicht','hauptfach','nebenfach','sonstige');
		if(!in_array($rolle, $valid, true)) $rolle = 'sonstige';
		try {
			rquery('INSERT INTO `modul_zuordnung` (`modul_id`, `import_id`, `ziel_studiengang_id`, `rolle`, `lp`) VALUES ('.
				esc($modul_id).', '.esc($import_id).', '.esc($studiengang_id).', '.esc($rolle).', '.esc($lp).
				') ON DUPLICATE KEY UPDATE `lp` = VALUES(`lp`)');
			return true;
		} catch(\Throwable $e) { return false; }
	}
}

if(!function_exists('soi_detect_voraussetzungen_for_modul')) {
	function soi_detect_voraussetzungen_for_modul(array $mod, array $all_modules_by_code): array {
		$out = array();
		$name = isset($mod['name']) ? trim((string)$mod['name']) : '';
		$code = isset($mod['modulnummer']) ? trim((string)$mod['modulnummer']) : '';
		if($name === '' || $code === '') return $out;

		$patterns = array(
			array('typ' => 'Aufbaumodul',       'vor' => 'Basismodul',        'rel' => 'aufbauend'),
			array('typ' => 'Vertiefungsmodul',  'vor' => 'Aufbaumodul',       'rel' => 'aufbauend'),
			array('typ' => 'Spezialisierungsmodul', 'vor' => 'Vertiefungsmodul', 'rel' => 'empfohlen'),
			array('typ' => 'Ergänzungsmodul',   'vor' => 'Basismodul',        'rel' => 'empfohlen'),
		);
		foreach($patterns as $p) {
			if(stripos($name, $p['typ']) === false) continue;
			$them = trim(preg_replace('/^.*?' . preg_quote($p['typ'], '/') . '\s*[:\-–]?\s*/iu', '', $name));
			if($them === '') continue;
			// Modulnummer-Studiengangs-Prefix: alles vor dem ersten Segment, das mit Ziffer beginnt.
			// Beispiele: "SLK-BA-F-2A-L" → "SLK-BA-F", "PhF-Phil-2A-X" → "PhF-Phil",
			// "INF-B-101-a" → "INF-B", "MA-PHYS-2024-Q1" → "MA-PHYS-2024".
			// Wir nutzen daher "kürzeste nicht-leere Sequenz gefolgt von -<Ziffer>" — das ist die
			// minimal-informative Prefix-Distanz zwischen Modulen verschiedener Studiengänge
			// und gleichzeitig robust gegenüber beliebigen Fakultäts-/Studiengangs-Codes.
			$own_studiengang_prefix = '';
			if(preg_match('/^(.+?)(?:-[0-9])/u', $code, $pm)) {
				$own_studiengang_prefix = $pm[1];
			}
			foreach($all_modules_by_code as $other_code => $other) {
				$other_num = isset($other['modulnummer']) ? trim((string)$other['modulnummer']) : $other_code;
				if($other_num === $code) continue;
				$other_name = isset($other['name']) ? $other['name'] : '';
				if(stripos($other_name, $p['vor']) === false || stripos($other_name, $them) === false) continue;
				// Studiengangs-Prefix muss passen (oder einer ist Anfang des anderen).
				if($own_studiengang_prefix !== '') {
					$other_studiengang_prefix = '';
					if(preg_match('/^(.+?)(?:-[0-9])/u', $other_num, $opm)) {
						$other_studiengang_prefix = $opm[1];
					}
					if($own_studiengang_prefix !== $other_studiengang_prefix
						&& strpos($other_studiengang_prefix.'-', $own_studiengang_prefix.'-') !== 0
						&& strpos($own_studiengang_prefix.'-', $other_studiengang_prefix.'-') !== 0) {
						continue;
					}
				}
				$out[] = array('modulnummer' => $other_num, 'typ' => $p['rel'], 'grund' => $p['typ'].' '.$them.' baut auf '.$p['vor'].' '.$them.' auf');
				break;
			}
		}

		if(stripos($name, 'Sprachpraxis') !== false || stripos($name, 'Language') !== false) {
			$stufen = array('A1' => 1, 'A2' => 2, 'B1' => 3, 'B2' => 4, 'C1' => 5, 'C2' => 6);
			$found_stufe = null;
			foreach($stufen as $s => $v) {
				if(stripos($name, $s) !== false) { $found_stufe = $s; break; }
			}
			if($found_stufe !== null) {
				$stufen_keys = array_keys($stufen);
				$idx = array_search($found_stufe, $stufen_keys);
				if($idx > 0) {
					$prev = $stufen_keys[$idx - 1];
					foreach($all_modules_by_code as $other_code => $other) {
						$other_num = isset($other['modulnummer']) ? trim((string)$other['modulnummer']) : $other_code;
						if($other_num === $code) continue;
						$on = isset($other['name']) ? $other['name'] : '';
						// Match: enthält Sprachpraxis ODER Language (Skills/Components/Creativity).
						$is_lang_other = (stripos($on, 'Sprachpraxis') !== false || stripos($on, 'Language') !== false);
						if(stripos($on, $prev) !== false && $is_lang_other) {
							$out[] = array('modulnummer' => $other_num, 'typ' => 'aufbauend', 'grund' => 'Sprachpraxis '.$prev.' ist Voraussetzung für '.$found_stufe);
							break;
						}
					}
				}
			}
		}

		if(preg_match('/^(.*?)-(?:1B|2A|3V|3S|3E|2V|1SP|2SP|3SP|4SP|2B|3A|4B|2S|3P)/u', $code, $cm)) {
			$prefix = $cm[1];
			$current_level = null;
			// Generische Level-Map (TU-typisch): Buchstabe kennzeichnet Schwierigkeit,
			// Ziffer die Stufe. Niedrigere Ziffer = Voraussetzung.
			$levels = array(
				'1B' => 1, '1SP' => 1, '1E' => 1,
				'2A' => 2, '2V' => 2, '2SP' => 2, '2B' => 2, '2S' => 2, '2E' => 2,
				'3V' => 3, '3S' => 3, '3E' => 3, '3A' => 3, '3SP' => 3, '3P' => 3,
				'4V' => 4, '4SP' => 4, '4B' => 4, '4A' => 4,
			);
			foreach($levels as $l => $n) {
				if(strpos($code, '-'.$l) !== false) { $current_level = $l; break; }
			}
			if($current_level !== null) {
				$prev_levels = array();
				foreach($levels as $l => $n) if($n < $levels[$current_level]) $prev_levels[] = $l;
				rsort($prev_levels);
				foreach($prev_levels as $pl) {
					$prev_code = $prefix.'-'.$pl;
					// Suche per modulnummer (nicht per Array-Key, da Input verschiedene Keys haben kann).
					$found_prev = null;
					foreach($all_modules_by_code as $other) {
						$other_num = isset($other['modulnummer']) ? trim((string)$other['modulnummer']) : '';
						if($other_num === $prev_code) { $found_prev = $other_num; break; }
					}
					if($found_prev !== null) {
						$out[] = array('modulnummer' => $found_prev, 'typ' => 'aufbauend',
							'grund' => 'Modulnummer-Stufung: '.$current_level.' setzt '.$pl.' voraus (gleicher Fachcode)');
						break;
					}
				}
			}
		}

		// Generische Stufung aus kurzen Buchstaben-Codes (typisch TU-TUD):
		// "-BM-" (Basismodul, level 1), "-AM-" (Aufbaumodul, level 2),
		// "-VM-" / "-V-" (Vertiefungsmodul, level 3), "-SM-" / "-S-" (Spezialisierung, level 4).
		// "-WP-" / "-W-" (Wahlpflicht), "-AQ-" / "-AQUA" (Allgemeine Qualifikationen).
		// Funktioniert für Codes wie "PHF-BA-POL-BM-SYS" / "-AM-SYS" / "-VM-XYZ".
		if(preg_match('/-(BM|AM|VM|SM|WP|AQ|AQUA|V|W|S|A|E)(-[A-Z][A-Z0-9]+)?$/u', $code, $lm)) {
			$level_code = $lm[1];
			$subj_code = isset($lm[2]) ? $lm[2] : '';
			$level_map = array(
				'BM' => 1, 'V' => 1, 'W' => 1, 'S' => 1, 'A' => 1, 'E' => 1,
				'AM' => 2, 'VM' => 3, 'SM' => 4, 'WP' => 5, 'AQ' => 0, 'AQUA' => 0,
			);
			$lvl = isset($level_map[$level_code]) ? $level_map[$level_code] : null;
			if($lvl !== null && $lvl > 0) {
				// Voraussetzung ist das Modul mit dem gleichen Sub-Code auf der nächst-niedrigeren Stufe.
				// Stufung: BM(1) < AM(2) < VM(3) < SM(4).
				$chains = array(
					array(2, 'BM'), // AM → BM
					array(3, 'AM'), // VM → AM
					array(4, 'VM'), // SM → VM
				);
				// Wenn das aktuelle Level AM ist: finde BM mit gleichem Sub-Code
				if($lvl === 2) {
					$prev_lc = 'BM';
					$prefix_match = preg_quote($code, '/');
					if(preg_match('/^(.*?)-AM/u', $code, $pm)) {
						$prefix = $pm[1];
						$prev_code = $prefix.'-BM'.$subj_code;
						// Suche prev_code in all_modules_by_code
						$found = null;
						foreach($all_modules_by_code as $other) {
							$other_num = isset($other['modulnummer']) ? trim((string)$other['modulnummer']) : '';
							if($other_num === $prev_code) { $found = $other_num; break; }
						}
						// Fallback: nur BM mit beliebigem Sub-Code im selben Prefix
						if($found === null) {
							foreach($all_modules_by_code as $other) {
								$other_num = isset($other['modulnummer']) ? trim((string)$other['modulnummer']) : '';
								if(strpos($other_num, $prefix.'-BM-') === 0) { $found = $other_num; break; }
							}
						}
						if($found !== null) {
							$out[] = array('modulnummer' => $found, 'typ' => 'aufbauend',
								'grund' => 'Aufbaumodul setzt Basismodul voraus (gleicher Fachcode)');
						}
					}
				}
				// VM → AM
				elseif($lvl === 3) {
					if(preg_match('/^(.*?)-VM/u', $code, $pm)) {
						$prefix = $pm[1];
						$found = null;
						foreach($all_modules_by_code as $other) {
							$other_num = isset($other['modulnummer']) ? trim((string)$other['modulnummer']) : '';
							if(strpos($other_num, $prefix.'-AM-') === 0) { $found = $other_num; break; }
						}
						if($found !== null) {
							$out[] = array('modulnummer' => $found, 'typ' => 'aufbauend',
								'grund' => 'Vertiefungsmodul setzt Aufbaumodul voraus (gleicher Fachcode)');
						}
					}
				}
				// SM → VM
				elseif($lvl === 4) {
					if(preg_match('/^(.*?)-SM/u', $code, $pm)) {
						$prefix = $pm[1];
						$found = null;
						foreach($all_modules_by_code as $other) {
							$other_num = isset($other['modulnummer']) ? trim((string)$other['modulnummer']) : '';
							if(strpos($other_num, $prefix.'-VM-') === 0) { $found = $other_num; break; }
						}
						if($found !== null) {
							$out[] = array('modulnummer' => $found, 'typ' => 'empfohlen',
								'grund' => 'Spezialisierungsmodul setzt Vertiefungsmodul voraus');
						}
					}
				}
			}
		}
		return $out;
	}
}

if(!function_exists('soi_persist_voraussetzungen')) {
	function soi_persist_voraussetzungen(int $modul_id, int $import_id, array $items, array $code_to_id): int {
		if($modul_id <= 0 || empty($items)) return 0;
		$written = 0;
		foreach($items as $it) {
			$other_code = isset($it['modulnummer']) ? trim((string)$it['modulnummer']) : '';
			$typ = isset($it['typ']) ? $it['typ'] : 'aufbauend';
			$grund = isset($it['grund']) ? mb_substr($it['grund'], 0, 500) : null;
			if(!isset($code_to_id[$other_code])) continue;
			$other_id = (int)$code_to_id[$other_code];
			if($other_id <= 0 || $other_id === $modul_id) continue;
			$valid = array('empfohlen','pflicht','aufbauend');
			if(!in_array($typ, $valid, true)) $typ = 'aufbauend';
			try {
				rquery('INSERT INTO `modul_voraussetzung` (`modul_id`, `voraussetzung_modul_id`, `import_id`, `typ`, `notiz`) VALUES ('.
					esc($modul_id).', '.esc($other_id).', '.esc($import_id).', '.esc($typ).', '.esc($grund).
					') ON DUPLICATE KEY UPDATE `notiz` = VALUES(`notiz`)');
				$written++;
			} catch(\Throwable $e) { }
		}
		return $written;
	}
}

/** Persistiert ein einzelnes Modul in einem Rutsch: modul + anlage2 + zuordnung.
 *  Wird von beiden Commit-Pfaden (AJAX + Form) verwendet.
 *  Liefert ['modul_id' => N, 'semester_rows' => N, 'anlage2_rows' => N, 'zuordnung_ok' => bool].
 */
if(!function_exists('soi_persist_one_module')) {
	function soi_persist_one_module(int $import_id, int $studiengang_id, array $m, array $anlage2_rows): array {
		$out = array('modul_id' => 0, 'semester_rows' => 0, 'anlage2_rows' => 0, 'zuordnung_ok' => false, 'pns' => 0);
		$modulnummer = trim((string)($m['modulnummer'] ?? ''));
		$name = trim((string)($m['name'] ?? ''));
		if($modulnummer === '' || $name === '') return $out;

		$beschreibung_parts = array();
		if(isset($m['lp']) && $m['lp'] !== '' && is_numeric($m['lp'])) $beschreibung_parts[] = 'LP: '.(int)$m['lp'];
		if(isset($m['sws']) && $m['sws'] !== '' && $m['sws'] !== null && is_numeric($m['sws'])) $beschreibung_parts[] = 'SWS: '.(float)$m['sws'];
		if(isset($m['dauer_semester']) && $m['dauer_semester'] !== '' && is_numeric($m['dauer_semester'])) $beschreibung_parts[] = 'Dauer: '.(int)$m['dauer_semester'].' Sem.';
		$beschreibung = mb_substr(implode('; ', $beschreibung_parts), 0, 500);

		rquery('INSERT INTO `modul` (`name`, `studiengang_id`, `abkuerzung`, `beschreibung`) VALUES ('.
			esc($name).', '.esc($studiengang_id).', '.esc($modulnummer).', '.esc($beschreibung).
			') ON DUPLICATE KEY UPDATE name=VALUES(name), beschreibung=VALUES(beschreibung)');

		$mod_row = get_single_row_from_query('SELECT id FROM `modul` WHERE `studiengang_id` = '.esc($studiengang_id).
			' AND `abkuerzung` = '.esc($modulnummer).' LIMIT 1');
		if(is_null($mod_row) || $mod_row === '' || $mod_row === false) return $out;
		$modul_id = (int)$mod_row;
		$out['modul_id'] = $modul_id;

		$anlage2_match = soi_find_anlage2_for_modul($anlage2_rows, $modulnummer);
		if($anlage2_match) {
			if(isset($m['lp']) && $m['lp'] !== '' && is_numeric($m['lp'])) {
				$anlage2_match['lp'] = (int)$m['lp'];
			}
			$out['semester_rows'] = soi_persist_semester_metadata($modul_id, $anlage2_match);
			$out['anlage2_rows'] = soi_persist_anlage2_detailed($modul_id, $import_id, $anlage2_match);
		}

		$rolle = soi_rolle_for_section(isset($m['section']) ? (string)$m['section'] : '');
		$lp = isset($m['lp']) && is_numeric($m['lp']) ? (float)$m['lp'] : null;
		$out['zuordnung_ok'] = soi_persist_modul_zuordnung($modul_id, $import_id, $studiengang_id, $rolle, $lp);

		return $out;
	}
}

/** Erzeugt Prüfungsnummern für ein Modul und schreibt sie in pruefungsnummer + pruefungsnummer_import.
 *  Liefert Anzahl erzeugter PNs zurück. */
if(!function_exists('soi_create_pruefungsnummern_for_module')) {
	function soi_create_pruefungsnummern_for_module(int $import_id, int $modul_id, string $modulnummer, string $name, array $pruefungstypen, $lp, array &$seen_pns): int {
		if($modul_id <= 0) return 0;
		$count = 0;
		foreach($pruefungstypen as $ptname) {
			if(!is_string($ptname) || trim($ptname) === '') continue;
			$ptname = trim($ptname);
			$pt_id = soi_ensure_pruefungstyp($ptname);
			if(!$pt_id) continue;
			$generated_nr = soi_generate_pruefungsnummer($modulnummer, $ptname, $lp ?? '', $seen_pns);
			rquery('INSERT INTO `pruefungsnummer` (`pruefungsnummer`, `modul_id`, `pruefungstyp_id`, `modulbezeichnung`) VALUES ('.
				esc($generated_nr).', '.esc($modul_id).', '.esc($pt_id).', '.esc($modulnummer.' '.$name).')');
			$pn_row = get_single_row_from_query('SELECT id FROM `pruefungsnummer` WHERE `pruefungsnummer` = '.esc($generated_nr).' LIMIT 1');
			$pn_id = (!is_null($pn_row) && $pn_row !== '' && $pn_row !== false) ? (int)$pn_row : null;
			rquery('INSERT INTO `pruefungsnummer_import` (import_id, modul_id, pruefungsnummer_id, generated_nr, pruefungstyp_name, lp) VALUES ('.
				esc($import_id).', '.esc($modul_id).', '.esc($pn_id).', '.esc($generated_nr).', '.esc($ptname).', '.esc($lp).')');
			$count++;
		}
		return $count;
	}
}
