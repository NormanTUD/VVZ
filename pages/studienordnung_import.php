<?php
	$included_files = get_included_files();
	$included_files = array_map('basename', $included_files);

	if(!in_array('functions.php', $included_files)) {
		include_once('../functions.php');
	}

	if(!check_page_rights(get_page_id_by_filename(basename(__FILE__)))) {
		$is_ajax_check = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
		$is_ajax_check = $is_ajax_check || get_get('ajax') === '1';
		if($is_ajax_check) {
			header('Content-Type: application/json; charset=utf-8');
			header('HTTP/1.0 403 Forbidden');
			print json_encode(array('ok' => false, 'error' => 'Sie haben keine Rechte für diese Seite.'));
			exit;
		}
		print '<p class="class_red">Sie haben keine Rechte, auf diese Seite zuzugreifen.</p>';
		return;
	}

	// Debug-Helfer: bei ?debug_soi=1 zusätzliche Statusinfos ausgeben
	$debug_soi = get_get('debug_soi') === '1';

	// -------- Hilfsfunktionen (Parser) --------

	if(!function_exists('soi_log')) {
		function soi_log($msg, $cls = 'hint') {
			if(class_exists('ReflectionClass') && $cls === 'warning') { warning($msg); }
			else { message($msg); }
		}
	}

	/** Extrahiert Text aus einem PDF mit pdftotext. */
	if(!function_exists('soi_extract_pdf_text')) {
		function soi_extract_pdf_text($pdf_path) {
			$pdftotext = trim((string)@shell_exec('command -v pdftotext 2>/dev/null'));
			if($pdftotext === '') {
				throw new RuntimeException('pdftotext wurde nicht gefunden. Bitte poppler-utils installieren.');
			}
			$cmd = escapeshellcmd($pdftotext) . ' -layout ' . escapeshellarg($pdf_path) . ' -';
			$out = shell_exec($cmd . ' 2>&1');
			if($out === null || $out === false) {
				throw new RuntimeException('pdftotext konnte das PDF nicht lesen.');
			}
			return $out;
		}
	}

	/** Validiert, dass die Datei ein PDF ist (magic bytes). */
	if(!function_exists('soi_is_pdf')) {
		function soi_is_pdf($tmp_path) {
			$h = @fopen($tmp_path, 'rb');
			if(!$h) return false;
			$head = fread($h, 5);
			fclose($h);
			return $head === '%PDF-';
		}
	}

	/** Findet Studiengang-Name + Grad auf der Titelseite. */
	if(!function_exists('soi_parse_cover')) {
		function soi_parse_cover($text) {
			$degree = null;
			$program = null;
			if(preg_match('/Studienordnung\s+(?:für|des)\s+den\s+(Bachelor|Master|Diplom|Staats\s*examen|Lehramt)\s*studiengang\s+([^\n\r]+)/u', $text, $m)) {
				$degree = trim($m[1]);
				$program = trim(preg_replace('/\s+/', ' ', $m[2]));
			} elseif(preg_match('/Prüfungsordnung\s+(?:für|des)\s+den\s+(Bachelor|Master|Diplom|Staats\s*examen|Lehramt)\s*studiengang\s+([^\n\r]+)/u', $text, $m)) {
				$degree = trim($m[1]);
				$program = trim(preg_replace('/\s+/', ' ', $m[2]));
			} elseif(preg_match('/Studienordnung\s+f[uü]r\s+den\s+([^\n\r]+)/u', $text, $m)) {
				$program = trim(preg_replace('/\s+/', ' ', $m[1]));
			}
			return array('degree' => $degree, 'program' => $program);
		}
	}

	/** Findet Section-Marker in Anlage 1. */
	if(!function_exists('soi_find_section')) {
		function soi_find_section($line, $current_section) {
			// Formate: "1. Module des Kernbereichs", "2. Module des Ergänzungsbereichs",
			//          "2.1   Evangelische Theologie (70 Leistungspunkte)"
			if(preg_match('/^[12]\.\s+Module\s+des\s+(Kernbereichs|Erg[äa]nzungsbereichs)/u', $line, $m)) {
				return 'Sektion '.ucfirst($m[1]);
			}
			if(preg_match('/^[12]\.([0-9]+)\s+(.+?)\s*\(\d+\s*Leistungspunkte\)?/u', $line, $m)) {
				return 'Ergänzungsbereich: '.trim($m[2]);
			}
			if(preg_match('/^[12]\.([0-9]+)\s+(.+)$/u', $line, $m)) {
				return 'Ergänzungsbereich: '.trim($m[2]);
			}
			return $current_section;
		}
	}

	/** Heuristik für ein typisches Modulcode-Pattern. */
	if(!function_exists('soi_is_modul_code')) {
		function soi_is_modul_code($s) {
			return preg_match('/^[A-Z][A-Za-z0-9]{0,4}[A-Z][A-Za-z0-9-]{2,}$/u', $s) === 1
				&& preg_match('/-/', $s) === 1
				&& strlen($s) >= 5
				&& strlen($s) <= 40;
		}
	}

	/** Parst alle Module aus dem Anlage-1-Textblock. */
	if(!function_exists('soi_parse_modules')) {
		function soi_parse_modules($raw_text) {
			// Wir extrahieren den Bereich ab "Anlage 1:\nModulbeschreibungen" (echter
			// Section-Header mit Zeilenumbruch) bis "Anlage 2:\nStudienablaufplan". Damit
			// werden die Vorkommen im Inhaltsverzeichnis zuverlässig übersprungen.
			$anlage1_start = mb_strpos($raw_text, "Anlage 1:\nModulbeschreibungen");
			if($anlage1_start === false) {
				$anlage1_start = mb_strpos($raw_text, 'Anlage 1:');
			}
			if($anlage1_start === false) {
				$anlage1_start = mb_strpos($raw_text, 'Modulbeschreibungen');
			}
			if($anlage1_start === false) {
				$anlage1_start = 0;
			}
			$anlage2_start = mb_strpos($raw_text, "Anlage 2:\nStudienablaufplan", $anlage1_start);
			if($anlage2_start === false) {
				$anlage2_start = mb_strpos($raw_text, 'Studienablaufplan', $anlage1_start);
			}
			if($anlage2_start === false) {
				$anlage2_start = mb_strpos($raw_text, 'Anlage 2', $anlage1_start);
			}
			$block = $anlage2_start !== false ? mb_substr($raw_text, $anlage1_start, $anlage2_start - $anlage1_start) : mb_substr($raw_text, $anlage1_start);

			$lines = preg_split('/\r\n|\r|\n/', $block);
			$modules = array();
			$current = null;
			$current_section = null;
			$expect_modul_header = false;
			$known_labels = array(
				'Qualifikationsziele', 'Inhalte',
				'Lehr- und', 'Lernformen',
				'Lehr- und Lernformen',
				'Voraussetzungen', 'für die Teilnahme',
				'Verwendbarkeit',
				'Voraussetzungen für', 'die Vergabe von', 'Leistungspunkten',
				'Voraussetzungen für die Vergabe von Leistungspunkten',
				'Leistungspunkte', 'und Noten',
				'Leistungspunkte und Noten',
				'Häufigkeit des', 'Moduls',
				'Häufigkeit des Moduls',
				'Arbeitsaufwand', 'Dauer des Moduls',
			);

			for($i = 0; $i < count($lines); $i++) {
				$line = $lines[$i];
				$trimmed = trim($line);
				if($trimmed === '') continue;

				// Section-Update
				$possible_section = soi_find_section($trimmed, $current_section);
				if($possible_section !== $current_section && preg_match('/^(Sektion|Erg)/', $possible_section)) {
					$current_section = $possible_section;
					if($current) $current['section'] = $current_section;
				}

				// Tabellenkopf "Modulnummer   Modulname   Verantwortlicher Dozent"
				if(preg_match('/^Modulnummer\b/u', $trimmed)) {
					$expect_modul_header = true;
					continue;
				}

				// Wenn Code-Pattern am Anfang der Zeile → neues Modul
				if(preg_match('/^([A-Z][A-Za-z0-9-]{3,})\s{2,}(\S.{2,})$/u', $trimmed, $m)) {
					$code_candidate = $m[1];
					$rest = $m[2];
					if(soi_is_modul_code($code_candidate)) {
						// Vorheriges Modul speichern
						if($current) $modules[] = $current;
						$parts = preg_split('/\s{2,}/u', $rest, 2);
						$name = trim($parts[0]);
						$dozent = isset($parts[1]) ? trim($parts[1]) : '';
						$current = array(
							'modulnummer' => $code_candidate,
							'name' => $name,
							'dozent' => $dozent,
							'section' => $current_section,
							'fields' => array(),
							'lp' => null,
							'sws_total' => null,
							'dauer_semester' => null,
							'pruefungstypen' => array(),
							'verwendbarkeit_text' => '',
						);
						$expect_modul_header = false;
						continue;
					}
				}

				if(!$current) continue;

				// Wenn die Zeile genau der Header ist, überspringen
				if($expect_modul_header && preg_match('/^Modulnummer\b/', $trimmed)) {
					$expect_modul_header = false;
					continue;
				}

				// Label-Zeilen erkennen: typischerweise Spalte1 <30 Zeichen, dann Whitespace, dann Wert
				// Bekannte Labels mit/ohne Umbruch:
				$label_patterns = array(
					array('Qualifikationsziele'),
					array('Inhalte'),
					array('Lehr- und', 'Lernformen'),
					array('Lehr- und Lernformen'),
					array('Voraussetzungen', 'für die Teilnahme'),
					array('Voraussetzungen für die Teilnahme'),
					array('Verwendbarkeit'),
					array('Voraussetzungen für', 'die Vergabe von', 'Leistungspunkten'),
					array('Voraussetzungen für die Vergabe von Leistungspunkten'),
					array('Leistungspunkte', 'und Noten'),
					array('Leistungspunkte und Noten'),
					array('Häufigkeit des', 'Moduls'),
					array('Häufigkeit des Moduls'),
					array('Arbeitsaufwand'),
					array('Dauer des Moduls'),
				);

				foreach($label_patterns as $lp) {
					$regex = '/^(' . implode('\s+', array_map(function($s){ return preg_quote($s, '/'); }, $lp)) . ')\s{2,}(.*)$/u';
					if(preg_match($regex, $trimmed, $m)) {
						$label = implode(' ', $lp);
						$value = $m[2];
						// Mehrzeilige Werte: Folgezeilen mit gleichem Einzug (>4 Spaces oder einfach alle, die nicht mit neuem Label beginnen)
						while(isset($lines[$i+1])) {
							$nxt = trim($lines[$i+1]);
							if($nxt === '') { $i++; continue; }
							$matches_known_label = false;
							foreach($label_patterns as $lp2) {
								$regex2 = '/^(' . implode('\s+', array_map(function($s){ return preg_quote($s, '/'); }, $lp2)) . ')\s{2,}/u';
								if(preg_match($regex2, $nxt)) { $matches_known_label = true; break; }
							}
							if($matches_known_label) break;
							// Modulcode-Start?
							if(preg_match('/^([A-Z][A-Za-z0-9-]{3,})\s{2,}/u', $nxt, $cm) && soi_is_modul_code($cm[1])) break;
							// Section-Header?
							if(preg_match('/^[12]\.\s+/u', $nxt)) break;
							$value .= ' ' . $nxt;
							$i++;
						}
						$current['fields'][$label] = $value;
						// Spezifische Extraktionen
						if($label === 'Leistungspunkte und Noten' || $label === 'Leistungspunkte') {
							if(preg_match('/(\d+)\s*Leistungspunkte/u', $value, $lm)) {
								$current['lp'] = (int)$lm[1];
							}
						}
						if($label === 'Dauer des Moduls') {
							if(preg_match('/(\d+)\s*Semester/u', $value, $dm)) {
								$current['dauer_semester'] = (int)$dm[1];
							}
						}
						if($label === 'Lehr- und Lernformen' || ($label === 'Lehr- und' && isset($current['fields']['Lernformen']))) {
							$ll = $current['fields']['Lehr- und Lernformen'] ?? ($current['fields']['Lehr- und'].' '.$current['fields']['Lernformen']);
							preg_match_all('/(\d+(?:[.,]\d+)?)\s*SWS/u', $ll, $swsm);
							if(isset($swsm[1])) {
								$sum = 0.0;
								foreach($swsm[1] as $v) { $sum += (float)str_replace(',', '.', $v); }
								$current['sws_total'] = $sum;
							}
						}
						if($label === 'Verwendbarkeit') {
							$current['verwendbarkeit_text'] = $value;
						}
						// Prüfungstypen aus Voraussetzungen-für-die-Vergabe-von-Leistungspunkten
						if($label === 'Voraussetzungen für die Vergabe von Leistungspunkten') {
							$candidates = array('Klausurarbeit', 'Klausur', 'Mündliche Prüfung', 'mündliche Prüfung',
								'Referat', 'Protokoll', 'Hausarbeit', 'Seminararbeit', 'Essay',
								'Portfolio', 'Bericht', 'Vortrag', 'Thesenpapier', 'Bibliographie',
								'Exposé', 'Rezension', 'Bachelorarbeit', 'Masterarbeit', 'Kolloquium');
							foreach($candidates as $cand) {
								if(mb_stripos($value, $cand) !== false) {
									if(!in_array($cand, $current['pruefungstypen'])) $current['pruefungstypen'][] = $cand;
								}
							}
						}
						continue 2;
					}
				}
			}
			if($current) $modules[] = $current;

			// Nachbearbeitung: Lehrformen mergen, falls zweizeilig erfasst
			foreach($modules as &$m) {
				if(isset($m['fields']['Lehr- und']) && isset($m['fields']['Lernformen']) && !isset($m['fields']['Lehr- und Lernformen'])) {
					$m['fields']['Lehr- und Lernformen'] = $m['fields']['Lehr- und'].' / '.$m['fields']['Lernformen'];
				}
				if(isset($m['fields']['Häufigkeit des']) && isset($m['fields']['Moduls']) && !isset($m['fields']['Häufigkeit des Moduls'])) {
					$m['fields']['Häufigkeit des Moduls'] = $m['fields']['Häufigkeit des'].' '.$m['fields']['Moduls'];
				}
			}

			return $modules;
		}
	}

	/** Parst Anlage 2 (Studienablaufplan). */
	if(!function_exists('soi_parse_anlage2')) {
		function soi_parse_anlage2($raw_text) {
			$anlage2_start = mb_strpos($raw_text, "Anlage 2:\nStudienablaufplan");
			if($anlage2_start === false) {
				$anlage2_start = mb_strpos($raw_text, 'Studienablaufplan');
			}
			if($anlage2_start === false) {
				$anlage2_start = mb_strpos($raw_text, 'Anlage 2');
			}
			if($anlage2_start === false) return array();
			$block = mb_substr($raw_text, $anlage2_start);
			$lines = preg_split('/\r\n|\r|\n/', $block);
			$out = array();
			$current = null;
			$sws_header_seen = false;
			$current_sws_labels = array();
			$sws_count = 0;

			// Wir versuchen die Spaltenüberschriften zu erkennen: "1. Sem. 2. Sem. ..." und das SWS-Layout "V/Ü/S/T" oder "V/PS/S/Ü/T" etc.
			// Strategie: Wenn wir eine Zeile mit "Modul-Nr." finden, scannen wir die Header-Zeilen danach.
			for($i = 0; $i < count($lines); $i++) {
				$line = $lines[$i];
				$trimmed = trim($line);
				if(preg_match('/^Modul-?Nr\.?\s+Modulname/u', $trimmed)) {
					// Header-Scan
					$sws_header_seen = false;
					$current_sws_labels = array();
					$sws_count = 0;
					for($j = $i + 1; $j < min($i + 6, count($lines)); $j++) {
						$h = trim($lines[$j]);
						if(preg_match('/^([0-9]+)\.\s*Sem\./u', $h) || preg_match('/\bV[\/ÜÜ]/u', $h)) {
							// Mögliche Headerzeile mit "V/Ü/S/T"
							if(preg_match_all('/([VÜSÜTPSKMO]+)(?=[\/])/u', $h, $m)) {
								$sws_count = count($m[1]);
								$current_sws_labels = $m[1];
								$sws_header_seen = true;
							}
							if(preg_match('/^([0-9]+)\.\s*Sem/u', $h)) {
								// Semesterheader erkannt; Spaltenanzahl bleibt aus V/Ü/S/T-Zeile
								if(!$sws_header_seen) {
									// Fallback: 4 Spalten wenn nicht erkannt
									$sws_count = 4;
									$current_sws_labels = array('V', 'Ü', 'S', 'T');
									$sws_header_seen = true;
								}
							}
						}
					}
					continue;
				}

				if(!$sws_header_seen) continue;

				// Modul-Zeile
				if(preg_match('/^([A-Z][A-Za-z0-9-]{3,})\s{2,}(\S.{2,}?)\s{2,}(.+)$/u', $trimmed, $m)) {
					$code = $m[1];
					if(!soi_is_modul_code($code)) continue;
					$name = trim($m[2]);
					$rest = $m[3];
					// Suche LP als letzte ganze Zahl
					$lp = null;
					if(preg_match('/\s(\d{1,3})\s*$/u', $rest, $lpm)) {
						$lp = (int)$lpm[1];
						$rest = preg_replace('/\s+\d{1,3}\s*$/u', '', $rest);
					}
					// Rest in Semester-Spalten aufteilen (Whitespace-getrennt)
					$cells = preg_split('/\s{2,}/u', $rest);
					$cell_data = array();
					foreach($cells as $c) {
						$c = trim($c);
						if($c === '') continue;
						if(preg_match('/^[\d\/\.]+$/u', $c)) {
							$parts = explode('/', $c);
							while(count($parts) < $sws_count) array_push($parts, '0');
							$cell_data[] = $parts;
						}
					}
					if($current) $out[] = $current;
					$current = array(
						'modulnummer' => $code,
						'name' => $name,
						'lp' => $lp,
						'semester' => array(),
						'_next_is_pl' => false,
					);
					foreach($cell_data as $idx => $parts) {
						$sws = array();
						for($k = 0; $k < $sws_count; $k++) {
							$sws[$current_sws_labels[$k] ?? ('col'.$k)] = isset($parts[$k]) ? (float)str_replace(',', '.', $parts[$k]) : 0.0;
						}
						$current['semester'][] = array('semester' => $idx + 1, 'sws' => $sws, 'pl_count' => 0);
					}
					continue;
				}

				// PL-Zeile: "1 PL   2 PL" oder "1PL"
				if($current && preg_match_all('/(\d+)\s*PL/u', $trimmed, $plm)) {
					// PL-Anzahl pro Semester zuordnen
					$pl_indices = array();
					foreach($plm[1] as $plc) $pl_indices[] = (int)$plc;
					// Heuristik: die Reihenfolge der PL entspricht der Reihenfolge der Semester-Spalten
					// Wenn weniger PL als Semester, fülle vorne; wenn mehr, kürze.
					$n_sem = count($current['semester']);
					$n_pl = count($pl_indices);
					if($n_pl > 0 && $n_sem > 0) {
						if($n_pl === $n_sem) {
							for($k = 0; $k < $n_sem; $k++) {
								$current['semester'][$k]['pl_count'] = $pl_indices[$k];
							}
						} elseif($n_pl < $n_sem) {
							// Annahme: PLs beginnen mit dem ersten Semester mit PL
							for($k = 0; $k < $n_pl; $k++) {
								$current['semester'][$k]['pl_count'] = $pl_indices[$k];
							}
						} else {
							for($k = 0; $k < $n_sem; $k++) {
								$current['semester'][$k]['pl_count'] = $pl_indices[$k] ?? 0;
							}
						}
					}
				}
			}
			if($current) $out[] = $current;
			return $out;
		}
	}

	/** Erzeugt eine generierte Prüfungsnummer für ein Modul + Prüfungstyp. */
	if(!function_exists('soi_generate_pruefungsnummer')) {
		function soi_generate_pruefungsnummer($modulnummer, $pruefungstyp, $lp, $seen) {
			// Heuristik: nehme letzten 4–5 Ziffern-/Buchstaben-Block aus Modulnummer
			// und hänge Prüfungstyp-Kürzel + LP an
			$suffix = mb_substr($modulnummer, -3);
			$type_code = 'KL';
			$t = mb_strtolower($pruefungstyp);
			if(strpos($t, 'klausur') !== false) $type_code = 'KL';
			elseif(strpos($t, 'mündl') !== false || strpos($t, 'muendl') !== false) $type_code = 'MP';
			elseif(strpos($t, 'referat') !== false) $type_code = 'RF';
			elseif(strpos($t, 'hausarbeit') !== false) $type_code = 'HA';
			elseif(strpos($t, 'portfolio') !== false) $type_code = 'PO';
			elseif(strpos($t, 'protokoll') !== false) $type_code = 'PR';
			elseif(strpos($t, 'seminararbeit') !== false) $type_code = 'SA';
			elseif(strpos($t, 'essay') !== false) $type_code = 'ES';
			elseif(strpos($t, 'vortrag') !== false) $type_code = 'VR';
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

	/** Findet einen Studiengang anhand des Namens oder legt ihn an. */
	if(!function_exists('soi_ensure_studiengang')) {
		function soi_ensure_studiengang($name, $degree, $institut_id) {
			if(!$name) return null;
			$full_name = trim($name);
			if($degree && mb_stripos($full_name, $degree) === false) {
				$full_name = $degree.' '.$full_name;
			}
			$full_name = mb_substr($full_name, 0, 100);

			// get_single_row_from_query gibt bei Match einen String (Spaltenwert) zurück,
			// KEIN Array. Deshalb prüfen wir auf !is_null.
			$existing = get_single_row_from_query('SELECT id FROM `studiengang` WHERE `name` = '.esc($full_name).' LIMIT 1');
			if(!is_null($existing) && $existing !== '' && $existing !== false) return (int)$existing;
			$existing = get_single_row_from_query('SELECT id FROM `studiengang` WHERE `name` = '.esc(mb_substr($name, 0, 100)).' LIMIT 1');
			if(!is_null($existing) && $existing !== '' && $existing !== false) return (int)$existing;

			$query = 'INSERT INTO `studiengang` (`name`, `institut_id`, `studienordnung`, `order_key`) VALUES ('.
				esc($full_name).', '.esc((int)$institut_id).', '.esc('importiert am '.date('Y-m-d H:i')).', 999999)';
			rquery($query);
			$new = get_single_row_from_query('SELECT id FROM `studiengang` WHERE `name` = '.esc($full_name).' LIMIT 1');
			if(!is_null($new) && $new !== '' && $new !== false) return (int)$new;
			return null;
		}
	}

	/** Findet oder erstellt pruefungstyp. */
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

	/** Rendert eine bestimmte Seite eines PDFs als PNG-Thumbnail.
	 *  Speichert im Verzeichnis data/imported_so/<import_id>/pages/.
	 *  Gibt den absoluten Pfad zum PNG zurück oder null bei Fehler.
	 */
	if(!function_exists('soi_render_page')) {
		function soi_render_page($import_id, $pdf_data, $page_number, $dpi = 110) {
			$dir = $GLOBALS['datadir'].'imported_so/'.intval($import_id).'/pages';
			if(!is_dir($dir)) { @mkdir($dir, 0775, true); }
			$png = $dir.'/page_'.intval($page_number).'.png';
			if(file_exists($png) && filesize($png) > 0) return $png;

			// PDF in temporäre Datei schreiben, pdftoppm darauf anwenden
			$tmp_pdf = tempnam(sys_get_temp_dir(), 'soipdf_');
			if($tmp_pdf === false) return null;
			$tmp_pdf .= '.pdf';
			if(file_put_contents($tmp_pdf, $pdf_data) === false) { @unlink($tmp_pdf); return null; }

			$cmd = escapeshellcmd('pdftoppm') .
				' -png -r '.intval($dpi) .
				' -f '.intval($page_number) .
				' -l '.intval($page_number).
				' '.escapeshellarg($tmp_pdf) .
				' '.escapeshellarg($dir.'/page');
			$out = @shell_exec($cmd.' 2>&1');
			@unlink($tmp_pdf);

			// pdftoppm schreibt "page_N.png" (1-basiert), wir wollen explizit page_<n>.png
			$generated = $dir.'/page-'.str_pad($page_number, 3, '0', STR_PAD_LEFT).'.png';
			if(!file_exists($generated)) {
				$generated = $dir.'/page-'.$page_number.'.png';
			}
			if(file_exists($generated)) {
				if($generated !== $png) @rename($generated, $png);
				return $png;
			}
			return null;
		}
	}

	/** Bestimmt für jede Modul-Zeile, auf welcher PDF-Seite sie beginnt. */
	if(!function_exists('soi_locate_modules_in_pages')) {
		function soi_locate_modules_in_pages($raw_text, $modules, $total_pages = 0) {
			// pdftotext liefert mit -layout Seitenzahl-Banner: "^      19" oder "Page 19" etc.
			// Wir nähern: jedes "L N" / "Seite N" / "L  N" am Zeilenende zählt.
			$lines = preg_split('/\r\n|\r|\n/', $raw_text);
			$page_starts = array();
			$cur_page = 1;
			foreach($lines as $i => $ln) {
				// Format-Decision: typische TU-Dresden PDFs haben rechts unten eine Zahl
				// Wir versuchen: Zeile mit nur einer Zahl (1-3 Stellen) am Ende eines Blocks
				if(preg_match('/^\s*(\d{1,3})\s*$/', $ln, $pm) && (int)$pm[1] <= ($total_pages ?: 9999)) {
					$n = (int)$pm[1];
					if($n > $cur_page) {
						$page_starts[$i] = $n;
						$cur_page = $n;
					}
				}
			}

			// Bestimme für jedes Modul die Seitenzahl
			$modul_positions = array();
			$modul_starts = array();
			foreach($modules as $idx => $m) {
				$modul_starts[$idx] = -1;
			}
			$modul_keys = array();
			foreach($modules as $idx => $m) {
				$modul_keys[$idx] = $m['modulnummer'];
			}
			$current_page = 1;
			foreach($lines as $i => $ln) {
				if(isset($page_starts[$i])) $current_page = $page_starts[$i];
				foreach($modul_keys as $idx => $code) {
					if($modul_starts[$idx] === -1 && strpos($ln, $code) === 0) {
						$modul_starts[$idx] = $current_page;
					}
				}
			}
			return $modul_starts;
		}
	}

	/** Liefert für ein Modul die Zeile, in der es im Text auftaucht (für Quellenangabe). */
	if(!function_exists('soi_find_line_for_modul')) {
		function soi_find_line_for_modul($raw_text, $modulnummer) {
			$lines = preg_split('/\r\n|\r|\n/', $raw_text);
			foreach($lines as $i => $ln) {
				if(preg_match('/^'.preg_quote($modulnummer, '/').'\s/s', $ln)) {
					return $i;
				}
			}
			return -1;
		}
	}

	// -------- Seitenlogik --------

	$stage = get_get('stage') ?: 'list';
	$user_id = isset($GLOBALS['logged_in_user_id']) ? (int)$GLOBALS['logged_in_user_id'] : null;
	$institute = create_institute_array();

	$show_msg = function($msg, $cls='hint') {
		if($cls === 'hint') message($msg);
		elseif($cls === 'warning') warning($msg);
		elseif($cls === 'error') error($msg);
		elseif($cls === 'success') success($msg);
		else message($msg);
	};

	$is_ajax_stage = $is_ajax_admin && in_array($stage, $ajax_only_stages, true);
?>
<?php if(!$is_ajax_stage): ?>
	<div id="studienordnung_import">
		<?php print get_seitentext(); ?>
<?php
	endif;
	if(!$is_ajax_stage) {
		include_once('hinweise.php');
	}
?>
<?php
		if($stage === 'list' || $stage === '') {
			// Übersicht der bisherigen Importe
			$query = 'SELECT `id`, `filename`, `imported_at`, `program_name`, `degree`, `modules_found`, `modules_imported`, `pruefungsnummern_imported` FROM `studienordnung_import` ORDER BY `imported_at` DESC LIMIT 50';
			$result = rquery($query);
?>
			<h2>Studienordnung (PDF) hochladen</h2>
			<p>Hier kann eine Studienordnung (PDF) hochgeladen werden. Das System extrahiert automatisch Modulnummer, Modulname, ECTS-Leistungspunkte, Prüfungstypen und Studienverlauf (Anlage 2) und legt diese in der Datenbank an. Die Verarbeitung erfolgt live: nach der Auswahl der Datei wird sofort eine Vorschau mit allen erkannten Daten und Seitenvorschauen angezeigt.</p>
			<form id="soi_upload_form" enctype="multipart/form-data" action="admin?page=<?php print $GLOBALS['this_page_number']; ?>&stage=upload&ajax=1" method="post">
				<table>
					<tr>
						<th>PDF-Datei</th>
						<td>
							<input id="soi_pdf_input" noautosubmit="1" type="file" name="pdf" accept="application/pdf" />
							<div id="soi_upload_progress" style="display:none;">
								<div class="soi-spinner"></div>
								<span id="soi_upload_status">Wird hochgeladen…</span>
							</div>
						</td>
					</tr>
					<tr>
						<th>Institut</th>
						<td><?php create_select($institute, get_get('institut'), 'soi_institut', 1); ?></td>
					</tr>
					<tr>
						<th>Studiengang</th>
						<td>
							<select name="soi_studiengang_mode" id="soi_studiengang_mode">
								<option value="auto" selected>aus PDF automatisch ermitteln</option>
								<option value="existing">bestehenden auswählen</option>
								<option value="new">neuen anlegen</option>
							</select>
							<div id="existing_studiengang_box" style="display:none;">
								<?php
									$studiengaenge = create_studiengaenge_array();
									create_select($studiengaenge, '', 'soi_studiengang_id', 1);
								?>
							</div>
							<div id="new_studiengang_box" style="display:none;">
								<input noautosubmit="1" type="text" name="new_studiengang_name" placeholder="Name des Studiengangs" />
							</div>
						</td>
					</tr>
					<tr>
						<th>Optionen</th>
						<td>
							<label><input noautosubmit="1" type="checkbox" name="create_pruefungsnummern" value="1" checked /> Prüfungsnummern automatisch erzeugen</label><br />
							<label><input noautosubmit="1" type="checkbox" name="reuse_in_other_studiengaenge" value="1" checked /> Module zusätzlich in allen Studiengängen anlegen, die sie laut Verwendbarkeit nutzen</label>
						</td>
					</tr>
				</table>
			</form>

			<div id="soi_preview_container" style="display:none;">
				<h2 id="soi_preview_title">Vorschau</h2>
				<div id="soi_preview_meta" class="dashboard_card" style="margin-bottom:16px;"></div>
				<div id="soi_preview_status" class="soi-status" style="display:none;"></div>
				<div class="soi-tabs">
					<button type="button" class="soi-tab-button soi-tab-active" data-soi-tab="modules">Module (<span id="soi_count_modules">0</span>)</button>
					<button type="button" class="soi-tab-button" data-soi-tab="anlage2">Anlage 2 (<span id="soi_count_anlage2">0</span>)</button>
					<button type="button" class="soi-tab-button" data-soi-tab="pages">PDF-Seiten</button>
				</div>
				<div class="soi-tab-panel" id="soi_tab_modules">
					<div id="soi_modules_list"></div>
				</div>
				<div class="soi-tab-panel" id="soi_tab_anlage2" style="display:none;">
					<div id="soi_anlage2_list"></div>
				</div>
				<div class="soi-tab-panel" id="soi_tab_pages" style="display:none;">
					<div id="soi_pages_list"></div>
				</div>
				<div class="soi-actions">
					<button type="button" id="soi_btn_commit" class="soi-btn-primary">Auswahl in Datenbank eintragen</button>
					<button type="button" id="soi_btn_cancel" class="soi-btn-secondary">Abbrechen</button>
				</div>
			</div>

			<style nonce=<?php print($GLOBALS['nonce']); ?> >
				#soi_preview_container { margin-top: 24px; }
				.soi-spinner {
					display: inline-block;
					width: 18px; height: 18px;
					border: 3px solid #ccc;
					border-top-color: #1976d2;
					border-radius: 50%;
					animation: soi-spin 0.8s linear infinite;
					vertical-align: middle;
					margin-right: 8px;
				}
				@keyframes soi-spin { to { transform: rotate(360deg); } }
				#soi_upload_progress { margin-top: 8px; padding: 6px 0; font-size: 13px; }
				#soi_upload_progress .progress-bar {
					height: 8px; background: #e0e0e0; border-radius: 4px; margin-top: 6px; overflow: hidden;
				}
				#soi_upload_progress .progress-bar > div {
					height: 100%; background: #1976d2; width: 0%; transition: width 0.2s;
				}
				.soi-status { padding: 10px 14px; border-radius: 4px; margin: 10px 0; font-size: 13px; }
				.soi-status-success { background: #e6f4ea; color: #1e4620; }
				.soi-status-error { background: #fce8e6; color: #762c2a; }
				html.dark-mode .soi-status-success { background: #1b3a23 !important; color: #b7e1c0 !important; }
				html.dark-mode .soi-status-error { background: #3a1f1d !important; color: #f5b8b3 !important; }

				.soi-tabs {
					display: flex; gap: 2px; margin: 12px 0 0 0; border-bottom: 1px solid #ccc;
				}
				html.dark-mode .soi-tabs { border-bottom-color: #3a3a5a !important; }
				.soi-tab-button {
					padding: 8px 16px; background: #f0f0f0; border: 1px solid #ccc; border-bottom: none;
					cursor: pointer; font-size: 13px; margin-bottom: -1px; border-radius: 4px 4px 0 0;
				}
				html.dark-mode .soi-tab-button {
					background: #1e1e3a !important; border-color: #3a3a5a !important; color: #e0e0e0 !important;
				}
				.soi-tab-active { background: #fff; border-bottom: 1px solid #fff; font-weight: bold; }
				html.dark-mode .soi-tab-active { background: #16213e !important; }

				.soi-tab-panel {
					background: #fff; border: 1px solid #ccc; border-top: none; padding: 12px;
					border-radius: 0 4px 4px 4px;
				}
				html.dark-mode .soi-tab-panel {
					background: #16213e !important; border-color: #3a3a5a !important;
				}

				.soi-module-row {
					display: grid;
					grid-template-columns: 30px 130px 1fr 60px 60px 60px 1fr;
					gap: 8px; align-items: center;
					padding: 6px 0; border-bottom: 1px solid #eee;
				}
				html.dark-mode .soi-module-row { border-bottom-color: #2a2a4a !important; }
				.soi-module-row label.soi-check { text-align: center; }
				.soi-module-row input[type=text] { padding: 4px 6px; }
				.soi-module-header {
					font-weight: bold; background: #f5f5f5; padding: 6px 8px; margin-top: 8px;
					border-radius: 3px; font-size: 12px;
				}
				html.dark-mode .soi-module-header { background: #2a2a4a !important; }

				.soi-pages-grid {
					display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
					gap: 8px;
				}
				.soi-page-card {
					border: 1px solid #ccc; border-radius: 4px; overflow: hidden;
					background: #fff;
				}
				html.dark-mode .soi-page-card { border-color: #3a3a5a !important; background: #16213e !important; }
				.soi-page-card img {
					width: 100%; height: auto; display: block; background: #f0f0f0;
				}
				.soi-page-card .soi-page-num {
					padding: 4px 6px; font-size: 11px; color: #555; border-top: 1px solid #eee;
				}
				html.dark-mode .soi-page-card .soi-page-num { color: #b0b0c0 !important; border-top-color: #2a2a4a !important; }
				.soi-page-card.has-modules { border-color: #1976d2; }
				html.dark-mode .soi-page-card.has-modules { border-color: #7eb8ff !important; }

				.soi-page-modal-bg {
					position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 99999;
					display: none; align-items: center; justify-content: center;
				}
				.soi-page-modal-bg.soi-open { display: flex; }
				.soi-page-modal {
					background: #fff; max-width: 92vw; max-height: 92vh; overflow: auto;
					border-radius: 6px; padding: 12px; position: relative;
				}
				html.dark-mode .soi-page-modal { background: #1e1e3a !important; color: #e0e0e0 !important; }
				.soi-page-modal img { max-width: 90vw; max-height: 80vh; }
				.soi-page-modal-close {
					position: absolute; top: 6px; right: 10px; cursor: pointer; font-size: 22px;
					background: none; border: none;
				}

				.soi-anlage2-row {
					display: grid; grid-template-columns: 130px 1fr;
					gap: 8px; padding: 4px 0; border-bottom: 1px dashed #eee;
				}
				html.dark-mode .soi-anlage2-row { border-bottom-color: #2a2a4a !important; }

				.soi-actions { margin-top: 20px; padding-top: 12px; border-top: 1px solid #ccc; display: flex; gap: 8px; }
				html.dark-mode .soi-actions { border-top-color: #3a3a5a !important; }
				.soi-btn-primary {
					padding: 8px 16px; background: #1976d2; color: #fff; border: none;
					border-radius: 4px; cursor: pointer; font-size: 13px; font-weight: bold;
				}
				.soi-btn-primary:hover { background: #1565c0; }
				.soi-btn-primary:disabled { background: #999; cursor: not-allowed; }
				.soi-btn-secondary {
					padding: 8px 16px; background: #fff; color: #333; border: 1px solid #ccc;
					border-radius: 4px; cursor: pointer; font-size: 13px;
				}
				html.dark-mode .soi-btn-secondary {
					background: #2a2a4a !important; color: #e0e0e0 !important; border-color: #3a3a5a !important;
				}
			</style>

			<div id="soi_page_modal" class="soi-page-modal-bg">
				<div class="soi-page-modal">
					<button type="button" class="soi-page-modal-close" onclick="soi_close_modal()">&times;</button>
					<img id="soi_page_modal_img" src="" alt="" />
				</div>
			</div>

			<h2>Bisherige Importe</h2>
			<table>
				<tr>
					<th>ID</th>
					<th>Datei</th>
					<th>Hochgeladen</th>
					<th>Studiengang</th>
					<th>Grad</th>
					<th>Module (gefunden / importiert)</th>
					<th>Prüfungsnummern</th>
					<th>Aktion</th>
				</tr>
<?php
				if($result && mysqli_num_rows($result)) {
					while ($row = mysqli_fetch_assoc($result)) {
?>
						<tr>
							<td><?php print (int)$row['id']; ?></td>
							<td><?php print htmlentities($row['filename']); ?></td>
							<td><?php print htmlentities($row['imported_at']); ?></td>
							<td><?php print htmlentities($row['program_name']); ?></td>
							<td><?php print htmlentities($row['degree']); ?></td>
							<td><?php print (int)$row['modules_found'].' / '.(int)$row['modules_imported']; ?></td>
							<td><?php print (int)$row['pruefungsnummern_imported']; ?></td>
							<td>
								<a href="admin?page=<?php print $GLOBALS['this_page_number']; ?>&stage=detail&id=<?php print (int)$row['id']; ?>">Details</a>
							</td>
						</tr>
<?php
					}
				} else {
?>
					<tr><td colspan="8"><i>Noch keine Importe vorhanden.</i></td></tr>
<?php
				}
?>
			</table>

			<script nonce=<?php print($GLOBALS['nonce']); ?> >
			(function(){
				function update_mode() {
					var m = document.getElementById('soi_studiengang_mode').value;
					document.getElementById('existing_studiengang_box').style.display = (m === 'existing') ? '' : 'none';
					document.getElementById('new_studiengang_box').style.display = (m === 'new') ? '' : 'none';
				}
				var sel = document.getElementById('soi_studiengang_mode');
				if(sel) sel.addEventListener('change', update_mode);
				update_mode();

				// ---------- AJAX-Upload + Live-Vorschau ----------
				var SOI = {
					data: null,
					pageSize: 0,
					currentImportId: null,
				};

				function $(id) { return document.getElementById(id); }

				function setStatus(msg, type) {
					var el = $('soi_preview_status');
					if(!el) return;
					el.className = 'soi-status ' + (type === 'error' ? 'soi-status-error' : 'soi-status-success');
					el.style.display = msg ? '' : 'none';
					el.innerHTML = msg;
				}

				function escapeHtml(s) {
					if(s === null || s === undefined) return '';
					return String(s).replace(/[&<>"']/g, function(c) {
						return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];
					});
				}

				function showSpinner(text) {
					$('soi_upload_progress').style.display = '';
					$('soi_upload_status').textContent = text || 'Wird verarbeitet…';
				}
				function hideSpinner() {
					$('soi_upload_progress').style.display = 'none';
				}

				function renderModules(data) {
					var container = $('soi_modules_list');
					container.innerHTML = '';
					var modules = data.modules || [];
					$('soi_count_modules').textContent = modules.length;
					if(!modules.length) {
						container.innerHTML = '<p class="class_red">Keine Module erkannt.</p>';
						return;
					}
					var bySection = {};
					modules.forEach(function(m, i) {
						var sec = m.section || 'Ohne Sektion';
						if(!bySection[sec]) bySection[sec] = [];
						bySection[sec].push({m: m, idx: i});
					});
					Object.keys(bySection).forEach(function(sec) {
						var h = document.createElement('div');
						h.className = 'soi-module-header';
						h.textContent = sec + ' (' + bySection[sec].length + ' Modul' + (bySection[sec].length === 1 ? '' : 'e') + ')';
						container.appendChild(h);
						bySection[sec].forEach(function(item) {
							var m = item.m;
							var idx = item.idx;
							var row = document.createElement('div');
							row.className = 'soi-module-row';
							row.dataset.modulIdx = idx;
							row.innerHTML =
								'<label class="soi-check"><input type="checkbox" class="soi-mod-include" ' + (m.modulnummer ? 'checked' : '') + ' /></label>' +
								'<input type="text" class="soi-mod-nr" value="' + escapeHtml(m.modulnummer) + '" placeholder="Modulnr." />' +
								'<input type="text" class="soi-mod-name" value="' + escapeHtml(m.name) + '" placeholder="Name" />' +
								'<input type="text" class="soi-mod-lp" value="' + (m.lp !== null && m.lp !== undefined ? m.lp : '') + '" placeholder="LP" />' +
								'<input type="text" class="soi-mod-sws" value="' + (m.sws_total !== null && m.sws_total !== undefined ? m.sws_total : '') + '" placeholder="SWS" />' +
								'<input type="text" class="soi-mod-dauer" value="' + (m.dauer_semester !== null && m.dauer_semester !== undefined ? m.dauer_semester : '') + '" placeholder="Dauer" />' +
								'<input type="text" class="soi-mod-pn" value="' + escapeHtml((m.pruefungstypen || []).join(', ')) + '" placeholder="Prüfungstypen (Komma-separiert)" />' +
								'<a href="#" class="soi-mod-page-link" data-page="' + (SOI.pageMap && SOI.pageMap[m.modulnummer] ? SOI.pageMap[m.modulnummer] : '') + '">Seite</a>';
							container.appendChild(row);
						});
					});

					// Click handler für "Seite"-Links
					Array.prototype.forEach.call(container.querySelectorAll('.soi-mod-page-link'), function(a) {
						a.addEventListener('click', function(ev) {
							ev.preventDefault();
							var p = parseInt(a.dataset.page, 10);
							if(p && p > 0) soi_show_page(p);
						});
					});
				}

				function renderAnlage2(data) {
					var container = $('soi_anlage2_list');
					container.innerHTML = '';
					var rows = data.anlage2 || [];
					$('soi_count_anlage2').textContent = rows.length;
					if(!rows.length) {
						container.innerHTML = '<p><i>Anlage 2 konnte nicht geparst werden (keine Tabelle gefunden).</i></p>';
						return;
					}
					rows.forEach(function(r) {
						var div = document.createElement('div');
						div.className = 'soi-anlage2-row';
						div.innerHTML =
							'<b>' + escapeHtml(r.modulnummer) + '</b>' +
							'<div>' + escapeHtml(r.name) + ' &mdash; LP: ' + (r.lp !== null && r.lp !== undefined ? r.lp : '?') +
								(r.semester && r.semester.length ? ' &mdash; ' + r.semester.map(function(s) {
									var sws = Object.keys(s.sws || {}).map(function(k) { return s.sws[k]; }).reduce(function(a,b){return a+b;}, 0);
									return 'S' + s.semester + ': ' + sws + ' SWS, ' + s.pl_count + ' PL';
								}).join(' &middot; ') : '') + '</div>';
						container.appendChild(div);
					});
				}

				function renderPages(data) {
					var container = $('soi_pages_list');
					container.innerHTML = '';
					var pagesHtml = '<div class="soi-pages-grid">';
					var numPages = data.page_count || 0;
					var modulePages = data.modul_pages || {};
					SOI.pageMap = {};
					Object.keys(modulePages).forEach(function(modulnr) {
						SOI.pageMap[modulnr] = modulePages[modulnr];
					});
					for(var p = 1; p <= numPages; p++) {
						var hasMod = false;
						Object.keys(modulePages).forEach(function(modulnr) {
							if(modulePages[modulnr] === p) hasMod = true;
						});
						pagesHtml += '<div class="soi-page-card' + (hasMod ? ' has-modules' : '') + '" data-soi-page="' + p + '">' +
							'<img src="data/admin?page=<?php print $GLOBALS['this_page_number']; ?>&stage=page_image&id=' + SOI.currentImportId + '&pdf_page=' + p + '" alt="Seite ' + p + '" loading="lazy" />' +
							'<div class="soi-page-num">Seite ' + p + (hasMod ? ' &mdash; enthält Modul' : '') + '</div>' +
							'</div>';
					}
					pagesHtml += '</div>';
					container.innerHTML = pagesHtml;
					Array.prototype.forEach.call(container.querySelectorAll('.soi-page-card'), function(card) {
						card.addEventListener('click', function() {
							soi_show_page(parseInt(card.dataset.soiPage, 10));
						});
					});
				}

			window.soi_show_page = function(p) {
				var img = $('soi_page_modal_img');
				img.src = 'data/admin?page=<?php print $GLOBALS['this_page_number']; ?>&stage=page_image&id=' + SOI.currentImportId + '&pdf_page=' + p;
				$('soi_page_modal').classList.add('soi-open');
			};
				window.soi_close_modal = function() {
					$('soi_page_modal').classList.remove('soi-open');
				};

				function renderPreview(data) {
					SOI.data = data;
					SOI.currentImportId = data.import_id;
					$('soi_count_modules').textContent = (data.modules || []).length;
					$('soi_count_anlage2').textContent = (data.anlage2 || []).length;
					var cover = data.cover || {};
					var meta = '<b>' + escapeHtml(data.filename || '') + '</b><br />' +
						'SHA256: <code>' + escapeHtml(data.sha256 || '') + '</code><br />' +
						'Studiengang: <b>' + escapeHtml((cover.program || data.studiengang_name) || '?') + '</b>' +
						(cover.degree ? ' (' + escapeHtml(cover.degree) + ')' : '') + '<br />' +
						'Größe: ' + ((data.size || 0)/1024).toFixed(1) + ' KB &middot; Text: ' + (data.text_length || 0) + ' Zeichen &middot; Module: ' + (data.modules || []).length + ' &middot; Anlage 2 Einträge: ' + (data.anlage2 || []).length;
					$('soi_preview_meta').innerHTML = meta;
					renderModules(data);
					renderAnlage2(data);
					renderPages(data);
					$('soi_preview_container').style.display = '';
					$('soi_btn_commit').disabled = false;
				}

				function uploadPdf(file) {
					var fd = new FormData($('soi_upload_form'));
					fd.set('pdf', file);
					showSpinner('Lade hoch und analysiere PDF…');
					setStatus('', '');
					$('soi_preview_container').style.display = 'none';
					$('soi_btn_commit').disabled = true;

					var xhr = new XMLHttpRequest();
					xhr.open('POST', 'admin?page=<?php print $GLOBALS['this_page_number']; ?>&stage=upload&ajax=1', true);
					xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

					xhr.upload.onprogress = function(e) {
						if(e.lengthComputable) {
							var pct = Math.round((e.loaded / e.total) * 100);
							$('soi_upload_status').textContent = 'Lade hoch… ' + pct + '%';
						}
					};
					xhr.onload = function() {
						hideSpinner();
						try {
							var resp = JSON.parse(xhr.responseText);
						} catch(e) {
							setStatus('Server-Antwort war kein gültiges JSON.', 'error');
							return;
						}
						if(!resp.ok) {
							setStatus('Fehler: ' + escapeHtml(resp.error || 'Unbekannt'), 'error');
							return;
						}
						setStatus('Analyse abgeschlossen — ' + (resp.modules || []).length + ' Module, ' + (resp.anlage2 || []).length + ' Anlage-2-Einträge.', 'success');
						// Seitenzahl separat ermitteln
						fetch('admin?page=<?php print $GLOBALS['this_page_number']; ?>&stage=analyze&id=' + resp.import_id, {credentials:'same-origin'})
							.then(function(r){ return r.json(); })
							.then(function(full) {
								full.page_count = (full.modules && full.modules.length) ? Math.max.apply(null, Object.values(full.modul_pages || {}).concat([1])) : 1;
								// Heuristik: wenn raw_text enthält Page-Counts, nimm das Maximum
								renderPreview(full);
							}).catch(function(e) {
								renderPreview(resp);
							});
					};
					xhr.onerror = function() {
						hideSpinner();
						setStatus('Netzwerkfehler beim Upload.', 'error');
					};
					xhr.send(fd);
				}

				var fileInput = $('soi_pdf_input');
				if(fileInput) {
					fileInput.addEventListener('change', function() {
						if(fileInput.files && fileInput.files[0]) {
							uploadPdf(fileInput.files[0]);
						}
					});
				}

				// Tab-Wechsel
				Array.prototype.forEach.call(document.querySelectorAll('.soi-tab-button'), function(btn) {
					btn.addEventListener('click', function() {
						var tab = btn.dataset.soiTab;
						Array.prototype.forEach.call(document.querySelectorAll('.soi-tab-button'), function(b) { b.classList.remove('soi-tab-active'); });
						btn.classList.add('soi-tab-active');
						Array.prototype.forEach.call(document.querySelectorAll('.soi-tab-panel'), function(p) { p.style.display = 'none'; });
						$('soi_tab_' + tab).style.display = '';
					});
				});

				// Commit-Button
				var commitBtn = $('soi_btn_commit');
				if(commitBtn) {
					commitBtn.addEventListener('click', function() {
						if(!SOI.data) return;
						commitBtn.disabled = true;
						var rows = document.querySelectorAll('#soi_modules_list .soi-module-row');
						var modulesOut = [];
						rows.forEach(function(row) {
							modulesOut.push({
								include: row.querySelector('.soi-mod-include').checked ? 1 : 0,
								modulnummer: row.querySelector('.soi-mod-nr').value,
								name: row.querySelector('.soi-mod-name').value,
								lp: row.querySelector('.soi-mod-lp').value,
								sws: row.querySelector('.soi-mod-sws').value,
								dauer_semester: row.querySelector('.soi-mod-dauer').value,
								pruefungstypen_str: row.querySelector('.soi-mod-pn').value
							});
						});
						var fd = new FormData();
						fd.set('soi_import_id', SOI.currentImportId);
						fd.set('soi_create_pruefungsnummern', '1');
						fd.set('soi_modules_v2', JSON.stringify(modulesOut));

						setStatus('Importiere…', '');
						fetch('admin?page=<?php print $GLOBALS['this_page_number']; ?>&stage=commit_v2', { method: 'POST', body: fd, credentials: 'same-origin' })
							.then(function(r){ return r.json(); })
							.then(function(resp) {
								if(resp.ok) {
									setStatus('Import abgeschlossen: ' + resp.modules_imported + ' Modul(e), ' + resp.pruefungsnummern_imported + ' Prüfungsnummer(n).', 'success');
									commitBtn.disabled = false;
									setTimeout(function() { window.location.href = 'admin?page=<?php print $GLOBALS['this_page_number']; ?>&stage=detail&id=' + SOI.currentImportId; }, 1500);
								} else {
									setStatus('Fehler: ' + escapeHtml(resp.error || 'Unbekannt'), 'error');
									commitBtn.disabled = false;
								}
							}).catch(function(e) {
								setStatus('Netzwerkfehler beim Commit.', 'error');
								commitBtn.disabled = false;
							});
					});
				}

				// Abbrechen
				var cancelBtn = $('soi_btn_cancel');
				if(cancelBtn) {
					cancelBtn.addEventListener('click', function() {
						$('soi_preview_container').style.display = 'none';
						setStatus('', '');
						fileInput.value = '';
					});
				}

				// Modal-Klick schließt
				var modalBg = $('soi_page_modal');
				if(modalBg) {
					modalBg.addEventListener('click', function(e) {
						if(e.target === modalBg) soi_close_modal();
					});
				}
			})();
			</script>
<?php
		} elseif($stage === 'upload') {
			// Upload + Analyse
			$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
			$is_ajax = $is_ajax || get_get('ajax') === '1';
			if(!isset($_FILES['pdf']) || $_FILES['pdf']['error'] !== UPLOAD_ERR_OK) {
				if($is_ajax) {
					header('Content-Type: application/json; charset=utf-8');
					print json_encode(array('ok' => false, 'error' => 'Keine Datei hochgeladen oder Upload-Fehler.'));
					exit;
				}
				warning('Keine Datei hochgeladen oder Upload-Fehler.');
				print '<p><a href="admin?page='.$GLOBALS['this_page_number'].'">Zurück</a></p>';
			} else {
				$pdf_path = $_FILES['pdf']['tmp_name'];
				$filename = $_FILES['pdf']['name'];
				if(!soi_is_pdf($pdf_path)) {
					if($is_ajax) {
						header('Content-Type: application/json; charset=utf-8');
						print json_encode(array('ok' => false, 'error' => 'Die hochgeladene Datei ist kein gültiges PDF.'));
						exit;
					}
					error('Die hochgeladene Datei ist kein gültiges PDF.');
					print '<p><a href="admin?page='.$GLOBALS['this_page_number'].'">Zurück</a></p>';
				} else {
					$pdf_bytes = file_get_contents($pdf_path);
					$sha = hash('sha256', $pdf_bytes);
					$size = strlen($pdf_bytes);
					// PDF in DB als Base64 speichern, um SQL-Injection durch Binärdaten zu vermeiden
					$pdf_b64 = base64_encode($pdf_bytes);

					// Text extrahieren
					try {
						$raw_text = soi_extract_pdf_text($pdf_path);
					} catch (Exception $e) {
						error('Fehler bei der Textextraktion: '.htmlentities($e->getMessage()));
						print '<p><a href="admin?page='.$GLOBALS['this_page_number'].'">Zurück</a></p>';
						$raw_text = null;
					}

					if($raw_text !== null) {
						$cover = soi_parse_cover($raw_text);
						$modules = soi_parse_modules($raw_text);
						$anlage2 = soi_parse_anlage2($raw_text);

					// Studiengang bestimmen
					$mode = get_post('soi_studiengang_mode') ?: 'auto';
					$institut_id = (int)get_post('soi_institut');
					if($institut_id <= 0) {
						$first = reset($institute);
						if(is_array($first) && isset($first[0])) $institut_id = (int)$first[0];
					}
					$studiengang_id = null;
					if($mode === 'existing') {
						$studiengang_id = (int)get_post('soi_studiengang_id');
					} elseif($mode === 'new') {
						$new_name = trim((string)get_post('new_studiengang_name'));
							if($new_name === '') $new_name = $cover['program'] ?: 'Unbekannt';
							$studiengang_id = soi_ensure_studiengang($new_name, $cover['degree'], $institut_id);
						} else {
							// auto
							if($cover['program']) {
								$studiengang_id = soi_ensure_studiengang($cover['program'], $cover['degree'], $institut_id);
							}
						}
						if(!$studiengang_id) {
							error('Es konnte kein Studiengang ermittelt werden. Bitte manuell auswählen oder anlegen.');
							print '<p><a href="admin?page='.$GLOBALS['this_page_number'].'">Zurück</a></p>';
						} else {
							// Import-Eintrag anlegen
							$auto_commit = get_post('auto_commit') ? 1 : 0;
							$create_pns = get_post('create_pruefungsnummern') ? 1 : 0;
							$reuse = get_post('reuse_in_other_studiengaenge') ? 1 : 0;

							// Geparste Daten für spätere Vorschau in notes ablegen
							$parsed_payload = array(
								'modules' => $modules,
								'anlage2' => $anlage2,
								'cover' => $cover,
								'created_at' => date('c'),
							);
							$notes_json = json_encode($parsed_payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE);
							// PDF-Text enthält oft Form-Feed (0x0c) und andere Control-Chars, die JSON ungültig machen.
							// Wir ersetzen sie durch \x escapes, damit der Browser sie korrekt parsen kann.
							$notes_json = preg_replace_callback('/[\x00-\x1f]/', function($m) {
								return '\\u' . str_pad(dechex(ord($m[0])), 4, '0', STR_PAD_LEFT);
							}, $notes_json);

							$query = 'INSERT INTO `studienordnung_import` (studiengang_id, filename, pdf_sha256, pdf_size, pdf_data, raw_text, degree, program_name, modules_found, modules_imported, pruefungsnummern_imported, imported_by_user_id, notes) VALUES ('.
								esc($studiengang_id).', '.esc($filename).', '.esc($sha).', '.esc($size).', '.esc($pdf_b64).', '.esc($raw_text).', '.
								esc($cover['degree']).', '.esc($cover['program']).', '.esc(count($modules)).', 0, 0, '.esc($user_id).', '.esc($notes_json).')';
							// rquery kann bei großen BLOBs/Strings fehlschlagen, daher direkter mysqli_query mit Exception-Handling
							try {
								$insert_result = mysqli_query($GLOBALS['dbh'], $query);
							} catch (\Throwable $e) {
								$insert_result = false;
							}
							$import_row = get_single_row_from_query('SELECT id FROM `studienordnung_import` WHERE `pdf_sha256` = '.esc($sha).' ORDER BY id DESC LIMIT 1');
							$import_id = (!is_null($import_row) && $import_row !== '' && $import_row !== false) ? (int)$import_row : 0;

							if(!$import_id) {
								if($is_ajax) {
									header('Content-Type: application/json; charset=utf-8');
									print json_encode(array('ok' => false, 'error' => 'Import-Eintrag konnte nicht angelegt werden.'.($insert_result === false ? ' (DB-Fehler)' : '')));
									exit;
								}
								error('Import-Eintrag konnte nicht angelegt werden.'.($insert_result === false ? ' (Datenbank-Fehler beim INSERT)' : ''));
								print '<p><a href="admin?page='.$GLOBALS['this_page_number'].'">Zurück</a></p>';
							} elseif($is_ajax) {
								// Sofortige JSON-Antwort mit allen geparsten Daten + Seitenzuordnungen
								$modul_pages = soi_locate_modules_in_pages($raw_text, $modules);
								header('Content-Type: application/json; charset=utf-8');
								print json_encode(array(
									'ok' => true,
									'import_id' => $import_id,
									'filename' => $filename,
									'sha256' => $sha,
									'studiengang_id' => $studiengang_id,
									'studiengang_name' => $cover['program'] ?: '',
									'degree' => $cover['degree'] ?: '',
									'modules' => $modules,
									'anlage2' => $anlage2,
									'modules_found' => count($modules),
									'anlage2_found' => count($anlage2),
									'modul_pages' => $modul_pages,
									'create_pruefungsnummern' => $create_pns,
									'reuse' => $reuse,
									'text_length' => strlen($raw_text),
									'size' => $size,
								), JSON_UNESCAPED_UNICODE);
								exit;
							} elseif($auto_commit) {
								// Direkt committen: in $_POST umkopieren und Stage auf 'commit' setzen
								$_POST = array();
								$_POST['soi_import_id'] = $import_id;
								$_POST['soi_create_pruefungsnummern'] = $create_pns;
								$_POST['soi_reuse'] = $reuse;
								$mod_post = array();
								foreach($modules as $idx => $m) {
									$mod_post[$idx] = array(
										'include' => 1,
										'modulnummer' => $m['modulnummer'],
										'name' => $m['name'],
										'lp' => $m['lp'],
										'sws' => $m['sws_total'],
										'dauer_semester' => $m['dauer_semester'],
										'pruefungstypen' => $m['pruefungstypen'] ?? array(),
									);
								}
								$_POST['soi_modules'] = $mod_post;
								$_SERVER['REQUEST_METHOD'] = 'POST';

								// Commit-Logik inline aufrufen (gleicher Code wie $stage === 'commit')
								$commit_studiengang_id = $studiengang_id;
								$commit_import_id = $import_id;
								$commit_modules_post = $mod_post;
								$commit_create_pns = $create_pns;

								$imported_modules = 0;
								$imported_pns = 0;
								$seen_pns = array();

								foreach($commit_modules_post as $idx => $m_post) {
									if(empty($m_post['include'])) continue;
									$modulnummer = trim((string)($m_post['modulnummer'] ?? ''));
									$name = trim((string)($m_post['name'] ?? ''));
									if($modulnummer === '' || $name === '') continue;

									$beschreibung_parts = array();
									if(isset($m_post['lp']) && $m_post['lp'] !== '') $beschreibung_parts[] = 'LP: '.(int)$m_post['lp'];
									if(isset($m_post['sws']) && $m_post['sws'] !== '' && $m_post['sws'] !== null) $beschreibung_parts[] = 'SWS: '.(float)$m_post['sws'];
									if(isset($m_post['dauer_semester']) && $m_post['dauer_semester'] !== '') $beschreibung_parts[] = 'Dauer: '.(int)$m_post['dauer_semester'].' Sem.';
									$beschreibung = mb_substr(implode('; ', $beschreibung_parts), 0, 500);

									rquery('INSERT INTO `modul` (`name`, `studiengang_id`, `abkuerzung`, `beschreibung`) VALUES ('.esc($name).', '.esc($commit_studiengang_id).', '.esc($modulnummer).', '.esc($beschreibung).') ON DUPLICATE KEY UPDATE name=VALUES(name), beschreibung=VALUES(beschreibung)');

									$mod_row = get_single_row_from_query('SELECT id FROM `modul` WHERE `studiengang_id` = '.esc($commit_studiengang_id).' AND `abkuerzung` = '.esc($modulnummer).' LIMIT 1');
									if(is_null($mod_row) || $mod_row === '' || $mod_row === false) continue;
									$modul_id = (int)$mod_row;
									$imported_modules++;

									if($commit_create_pns) {
										$ptypes = isset($m_post['pruefungstypen']) && is_array($m_post['pruefungstypen']) ? $m_post['pruefungstypen'] : array('Klausurarbeit');
										foreach($ptypes as $ptname) {
											if(!$ptname) continue;
											$pt_id = soi_ensure_pruefungstyp($ptname);
											if(!$pt_id) continue;
											$generated_nr = soi_generate_pruefungsnummer($modulnummer, $ptname, $m_post['lp'] ?? '', $seen_pns);
											rquery('INSERT INTO `pruefungsnummer` (`pruefungsnummer`, `modul_id`, `pruefungstyp_id`, `modulbezeichnung`) VALUES ('.esc($generated_nr).', '.esc($modul_id).', '.esc($pt_id).', '.esc($modulnummer.' '.$name).')');
											$pn_row = get_single_row_from_query('SELECT id FROM `pruefungsnummer` WHERE `pruefungsnummer` = '.esc($generated_nr).' LIMIT 1');
											$pn_id = (!is_null($pn_row) && $pn_row !== '' && $pn_row !== false) ? (int)$pn_row : null;
											rquery('INSERT INTO `pruefungsnummer_import` (import_id, modul_id, pruefungsnummer_id, generated_nr, pruefungstyp_name, lp) VALUES ('.esc($commit_import_id).', '.esc($modul_id).', '.esc($pn_id).', '.esc($generated_nr).', '.esc($ptname).', '.esc($m_post['lp'] ?? null).')');
											$imported_pns++;
										}
									}
								}

								rquery('UPDATE `studienordnung_import` SET `modules_imported` = '.esc($imported_modules).', `pruefungsnummern_imported` = '.esc($imported_pns).' WHERE `id` = '.esc($commit_import_id));
								success('Import (auto-commit) abgeschlossen: '.$imported_modules.' Modul(e), '.$imported_pns.' Prüfungsnummer(n) angelegt.');
								print '<p><a href="admin?page='.$GLOBALS['this_page_number'].'">Zurück zur Übersicht</a> &middot; <a href="admin?page='.$GLOBALS['this_page_number'].'&stage=detail&id='.$commit_import_id.'">Details ansehen</a></p>';
							} else {
								// In Session ablegen, damit der User die Vorschau bearbeiten kann
								$_SESSION['soi_preview'] = array(
									'import_id' => $import_id,
									'filename' => $filename,
									'program' => $cover['program'],
									'degree' => $cover['degree'],
									'studiengang_id' => $studiengang_id,
									'create_pruefungsnummern' => $create_pns,
									'reuse' => $reuse,
									'modules' => $modules,
									'anlage2' => $anlage2,
									'raw_text_excerpt' => mb_substr($raw_text, 0, 4000),
								);
								print '<p>PDF wurde hochgeladen und analysiert. <a href="admin?page='.$GLOBALS['this_page_number'].'&stage=preview">Zur Vorschau &rarr;</a></p>';
								print '<p>Studiengang: <b>'.htmlentities($cover['program'] ?: 'unbekannt').'</b> &middot; Module gefunden: '.count($modules).' &middot; Anlage 2 Einträge: '.count($anlage2).'</p>';
							}
						}
					}
				}
			}
		} elseif($stage === 'preview') {
			$preview = isset($_SESSION['soi_preview']) ? $_SESSION['soi_preview'] : null;
			if(!$preview) {
				warning('Keine Vorschau-Daten vorhanden. Bitte zuerst ein PDF hochladen.');
				print '<p><a href="admin?page='.$GLOBALS['this_page_number'].'">Zurück</a></p>';
			} else {
?>
				<h2>Vorschau: <?php print htmlentities($preview['filename']); ?></h2>
				<p>Studiengang: <b><?php print htmlentities($preview['program'] ?: '(unbekannt)'); ?></b> &middot; Module gefunden: <b><?php print count($preview['modules']); ?></b></p>
				<form method="post" action="admin?page=<?php print $GLOBALS['this_page_number']; ?>&stage=commit">
					<input type="hidden" name="import_id" value="<?php print (int)$preview['import_id']; ?>" />
					<input type="hidden" name="create_pruefungsnummern" value="<?php print (int)$preview['create_pruefungsnummern']; ?>" />
					<input type="hidden" name="reuse" value="<?php print (int)$preview['reuse']; ?>" />
					<table>
						<tr>
							<th>Importieren?</th>
							<th>Modulnummer</th>
							<th>Name</th>
							<th>Dozent</th>
							<th>Sektion</th>
							<th>LP</th>
							<th>SWS</th>
							<th>Dauer (Sem.)</th>
							<th>Prüfungstypen</th>
						</tr>
<?php
						foreach($preview['modules'] as $idx => $m) {
?>
							<tr>
								<td><input noautosubmit="1" type="checkbox" name="modules[<?php print $idx; ?>][include]" value="1" checked /></td>
								<td>
									<input noautosubmit="1" type="text" name="modules[<?php print $idx; ?>][modulnummer]" value="<?php print htmlentities($m['modulnummer']); ?>" size="14" />
								</td>
								<td>
									<input noautosubmit="1" type="text" name="modules[<?php print $idx; ?>][name]" value="<?php print htmlentities($m['name']); ?>" size="40" />
								</td>
								<td><?php print htmlentities($m['dozent']); ?></td>
								<td><?php print htmlentities($m['section'] ?? ''); ?></td>
								<td><?php print $m['lp'] !== null ? (int)$m['lp'] : '?'; ?></td>
								<td><?php print $m['sws_total'] !== null ? htmlentities((string)$m['sws_total']) : '?'; ?></td>
								<td><?php print $m['dauer_semester'] !== null ? (int)$m['dauer_semester'] : '?'; ?></td>
								<td><?php print htmlentities(implode(', ', $m['pruefungstypen'] ?? array())); ?></td>
							</tr>
<?php
						}
?>
					</table>
					<p><input noautosubmit="1" type="submit" value="Auswahl importieren" /> <a href="admin?page=<?php print $GLOBALS['this_page_number']; ?>">Abbrechen</a></p>
				</form>

				<h3>Anlage 2 — Studienablaufplan</h3>
				<details>
					<summary>Rohdaten anzeigen</summary>
					<pre style="max-height: 400px; overflow: auto; background: #f5f5f5; padding: 8px; font-size: 11px;"><?php
						foreach($preview['anlage2'] as $row) {
							print htmlentities($row['modulnummer']).'  '.htmlentities($row['name']).'  LP='.($row['lp'] ?? '?')."\n";
							foreach($row['semester'] as $s) {
								$sws_str = '';
								foreach($s['sws'] as $lbl => $v) { $sws_str .= $lbl.'='.(float)$v.' '; }
								print '    Sem '.$s['semester'].': '.trim($sws_str).' | PL='.$s['pl_count']."\n";
							}
						}
					?></pre>
				</details>

				<h3>Extrahierter Rohtext (Anfang)</h3>
				<details>
					<summary>Erste 4000 Zeichen anzeigen</summary>
					<pre style="max-height: 400px; overflow: auto; background: #f5f5f5; padding: 8px; font-size: 11px;"><?php print htmlentities($preview['raw_text_excerpt']); ?></pre>
				</details>
<?php
			}
		} elseif($stage === 'commit') {
			$import_id = (int)get_post('import_id');
			$create_pns = get_post('create_pruefungsnummern') ? 1 : 0;
			$reuse = get_post('reuse') ? 1 : 0;
			$modules_post = get_post('modules') ?: array();

			// Studiengang-ID aus Import holen
			$row = get_single_row_from_query('SELECT studiengang_id FROM `studienordnung_import` WHERE id = '.esc($import_id));
			$studiengang_id = (!is_null($row) && $row !== '' && $row !== false) ? (int)$row : 0;

			if(!$studiengang_id) {
				error('Import-Eintrag nicht gefunden.');
			} else {
				$imported_modules = 0;
				$imported_pns = 0;
				$seen_pns = array();

				foreach($modules_post as $idx => $m_post) {
					if(empty($m_post['include'])) continue;
					$modulnummer = trim((string)($m_post['modulnummer'] ?? ''));
					$name = trim((string)($m_post['name'] ?? ''));
					if($modulnummer === '' || $name === '') continue;

					// Beschreibung aus SWS / LP / Dauer / Lehre zusammenbauen
					$beschreibung_parts = array();
					if(isset($m_post['lp']) && $m_post['lp'] !== '') $beschreibung_parts[] = 'LP: '.(int)$m_post['lp'];
					if(isset($m_post['sws']) && $m_post['sws'] !== '') $beschreibung_parts[] = 'SWS: '.htmlentities($m_post['sws']);
					if(isset($m_post['dauer_semester']) && $m_post['dauer_semester'] !== '') $beschreibung_parts[] = 'Dauer: '.(int)$m_post['dauer_semester'].' Sem.';
					$beschreibung = mb_substr(implode('; ', $beschreibung_parts), 0, 500);

					// Modul anlegen
					$query = 'INSERT INTO `modul` (`name`, `studiengang_id`, `abkuerzung`, `beschreibung`) VALUES ('.esc($name).', '.esc($studiengang_id).', '.esc($modulnummer).', '.esc($beschreibung).') ON DUPLICATE KEY UPDATE name=VALUES(name), beschreibung=VALUES(beschreibung)';
					rquery($query);

					$mod_row = get_single_row_from_query('SELECT id FROM `modul` WHERE `studiengang_id` = '.esc($studiengang_id).' AND `abkuerzung` = '.esc($modulnummer).' LIMIT 1');
					if(is_null($mod_row) || $mod_row === '' || $mod_row === false) continue;
					$modul_id = (int)$mod_row;
					$imported_modules++;

					// Prüfungsnummern erzeugen
					if($create_pns) {
						$ptypes = array();
						if(isset($m_post['pruefungstypen']) && is_array($m_post['pruefungstypen'])) {
							$ptypes = $m_post['pruefungstypen'];
						} elseif(isset($m_post['pruefungstypen_str']) && trim($m_post['pruefungstypen_str']) !== '') {
							$ptypes = array_map('trim', explode(',', $m_post['pruefungstypen_str']));
						}
						if(!$ptypes) $ptypes = array('Klausurarbeit');
						foreach($ptypes as $ptname) {
							if($ptname === '') continue;
							$pt_id = soi_ensure_pruefungstyp($ptname);
							if(!$pt_id) continue;
							$generated_nr = soi_generate_pruefungsnummer($modulnummer, $ptname, $m_post['lp'] ?? '', $seen_pns);
							rquery('INSERT INTO `pruefungsnummer` (`pruefungsnummer`, `modul_id`, `pruefungstyp_id`, `modulbezeichnung`) VALUES ('.esc($generated_nr).', '.esc($modul_id).', '.esc($pt_id).', '.esc($modulnummer.' '.$name).')');
							$pn_row = get_single_row_from_query('SELECT id FROM `pruefungsnummer` WHERE `pruefungsnummer` = '.esc($generated_nr).' LIMIT 1');
							$pn_id = (!is_null($pn_row) && $pn_row !== '' && $pn_row !== false) ? (int)$pn_row : null;
							rquery('INSERT INTO `pruefungsnummer_import` (import_id, modul_id, pruefungsnummer_id, generated_nr, pruefungstyp_name, lp) VALUES ('.esc($import_id).', '.esc($modul_id).', '.esc($pn_id).', '.esc($generated_nr).', '.esc($ptname).', '.esc($m_post['lp'] ?? null).')');
							$imported_pns++;
						}
					}
				}

				// Import-Statistik aktualisieren
				rquery('UPDATE `studienordnung_import` SET `modules_imported` = '.esc($imported_modules).', `pruefungsnummern_imported` = '.esc($imported_pns).' WHERE `id` = '.esc($import_id));

				unset($_SESSION['soi_preview']);
				success('Import abgeschlossen: '.$imported_modules.' Modul(e), '.$imported_pns.' Prüfungsnummer(n) angelegt.');
				print '<p><a href="admin?page='.$GLOBALS['this_page_number'].'">Zurück zur Übersicht</a> &middot; <a href="admin?page='.$GLOBALS['this_page_number'].'&stage=detail&id='.$import_id.'">Details ansehen</a></p>';
			}
		} elseif($stage === 'detail') {
			$id = (int)get_get('id');
			$row = get_single_row_from_query_assoc('SELECT * FROM `studienordnung_import` WHERE id = '.esc($id));
			if(!$row) {
				warning('Import nicht gefunden.');
			} else {
				print '<h2>Import #'.htmlentities($id).': '.htmlentities($row['filename']).'</h2>';
				print '<p>Hochgeladen: '.htmlentities($row['imported_at']).' &middot; Studiengang: <b>'.htmlentities($row['program_name']).'</b>'.($row['degree'] ? ' &middot; Grad: '.htmlentities($row['degree']) : '').'</p>';
				print '<p>Module gefunden: '.(int)$row['modules_found'].' &middot; importiert: '.(int)$row['modules_imported'].' &middot; Prüfungsnummern: '.(int)$row['pruefungsnummern_imported'].' &middot; SHA256: <code>'.htmlentities($row['pdf_sha256']).'</code></p>';

				$mod_rows_result = rquery('SELECT `m`.`id`, `m`.`abkuerzung`, `m`.`name`, `m`.`beschreibung`, `s`.`name` AS `studiengang` FROM `modul` `m` LEFT JOIN `studienordnung_import` `i` ON 1=1 LEFT JOIN `studiengang` `s` ON `s`.`id` = `m`.`studiengang_id` WHERE `m`.`abkuerzung` IN (SELECT `modulnummer` FROM `pruefungsnummer_import` WHERE import_id = '.esc($id).') GROUP BY `m`.`id` ORDER BY `m`.`abkuerzung`');
				if($mod_rows_result && mysqli_num_rows($mod_rows_result)) {
					print '<h3>Importierte Module</h3><table><tr><th>Abkürzung</th><th>Name</th><th>Studiengang</th></tr>';
					while ($r = mysqli_fetch_assoc($mod_rows_result)) {
						print '<tr><td>'.htmlentities($r['abkuerzung']).'</td><td>'.htmlentities($r['name']).'</td><td>'.htmlentities($r['studiengang']).'</td></tr>';
					}
					print '</table>';
				}

				$pn_rows_result = rquery('SELECT `pi`.`generated_nr`, `pi`.`pruefungstyp_name`, `pi`.`lp`, `m`.`abkuerzung`, `m`.`name` FROM `pruefungsnummer_import` `pi` JOIN `modul` `m` ON `m`.`id` = `pi`.`modul_id` WHERE `pi`.`import_id` = '.esc($id).' ORDER BY `m`.`abkuerzung`');
				if($pn_rows_result && mysqli_num_rows($pn_rows_result)) {
					print '<h3>Importierte Prüfungsnummern</h3><table><tr><th>PNr</th><th>Modul</th><th>Prüfungstyp</th><th>LP</th></tr>';
					while ($r = mysqli_fetch_assoc($pn_rows_result)) {
						print '<tr><td>'.htmlentities($r['generated_nr']).'</td><td>'.htmlentities($r['abkuerzung'].' — '.$r['name']).'</td><td>'.htmlentities($r['pruefungstyp_name']).'</td><td>'.htmlentities((string)$r['lp']).'</td></tr>';
					}
					print '</table>';
				}

				// PDF Download anbieten
				$pdf_decoded = base64_decode($row['pdf_data'], true);
				if($pdf_decoded === false) $pdf_decoded = $row['pdf_data'];
				print '<h3>Original-PDF</h3><p><a href="admin?page='.$GLOBALS['this_page_number'].'&stage=download&id='.$id.'">PDF herunterladen</a> ('.number_format(strlen($pdf_decoded)/1024, 1).' KB)</p>';
				print '<p><a href="admin?page='.$GLOBALS['this_page_number'].'">Zurück</a></p>';
			}
		} elseif($stage === 'download') {
			$id = (int)get_get('id');
			$row = get_single_row_from_query_assoc('SELECT filename, pdf_data FROM `studienordnung_import` WHERE id = '.esc($id));
			if(!$row) {
				warning('Nicht gefunden.');
			} else {
				$pdf_bin = base64_decode($row['pdf_data'], true);
				if($pdf_bin === false) {
					$pdf_bin = $row['pdf_data']; // Rückwärtskompatibilität für unverschlüsselte Einträge
				}
				header('Content-Type: application/pdf');
				header('Content-Disposition: attachment; filename="'.preg_replace('/[^A-Za-z0-9._-]/', '_', $row['filename']).'"');
				header('Content-Length: '.strlen($pdf_bin));
				print $pdf_bin;
				exit;
			}
		} elseif($stage === 'analyze') {
			// AJAX: geparste Daten zu einem bestehenden Import-Eintrag zurückgeben.
			header('Content-Type: application/json; charset=utf-8');
			$id = (int)get_get('id');
			$row = get_single_row_from_query_assoc('SELECT id, filename, raw_text, notes, studiengang_id, degree, program_name FROM `studienordnung_import` WHERE id = '.esc($id));
			if(!$row) {
				print json_encode(array('ok' => false, 'error' => 'Import nicht gefunden.'));
				exit;
			}
			$payload = json_decode($row['notes'] ?? '', true);
			$modules = is_array($payload) && isset($payload['modules']) ? $payload['modules'] : null;
			$anlage2 = is_array($payload) && isset($payload['anlage2']) ? $payload['anlage2'] : null;
			if($modules === null) {
				$modules = soi_parse_modules($row['raw_text']);
			}
			if($anlage2 === null) {
				$anlage2 = soi_parse_anlage2($row['raw_text']);
			}
			$cover = is_array($payload) && isset($payload['cover']) ? $payload['cover'] : array('degree' => $row['degree'], 'program' => $row['program_name']);
			$modul_pages = soi_locate_modules_in_pages($row['raw_text'], $modules);
			print json_encode(array(
				'ok' => true,
				'import_id' => (int)$row['id'],
				'filename' => $row['filename'],
				'studiengang_id' => (int)$row['studiengang_id'],
				'studiengang_name' => $row['program_name'] ?? '',
				'degree' => $row['degree'] ?? '',
				'cover' => $cover,
				'modules' => $modules,
				'anlage2' => $anlage2,
				'modul_pages' => $modul_pages,
				'modules_found' => count($modules),
				'anlage2_found' => count($anlage2),
			), JSON_UNESCAPED_UNICODE);
			exit;
		} elseif($stage === 'page_image') {
			// AJAX: Liefert PNG der Seite N eines Imports
			$id = (int)get_get('id');
			$page = (int)(get_get('pdf_page') ?: get_get('page'));
			if($page < 1) $page = 1;
			$row = get_single_row_from_query_assoc('SELECT pdf_data FROM `studienordnung_import` WHERE id = '.esc($id));
			if(!$row) {
				header('HTTP/1.0 404 Not Found');
				exit;
			}
			$pdf_bin = base64_decode($row['pdf_data'], true);
			if($pdf_bin === false) $pdf_bin = $row['pdf_data'];
			$png_path = soi_render_page($id, $pdf_bin, $page, 110);
			if(!$png_path || !file_exists($png_path)) {
				header('HTTP/1.0 500 Internal Server Error');
				print 'Rendern fehlgeschlagen';
				exit;
			}
			header('Content-Type: image/png');
			header('Content-Length: '.filesize($png_path));
			header('Cache-Control: private, max-age=86400');
			readfile($png_path);
			exit;
		} elseif($stage === 'commit_v2') {
			// AJAX: Vom Frontend bearbeitete Modul-Liste persistieren
			header('Content-Type: application/json; charset=utf-8');
			$import_id = (int)get_post('soi_import_id');
			$create_pns = get_post('soi_create_pruefungsnummern') ? 1 : 0;
			$modules_in = get_post('soi_modules_v2') ?: array();
			$new_studiengang_name = trim((string)get_post('soi_new_studiengang_name'));

			$import_row = get_single_row_from_query_assoc('SELECT studiengang_id, program_name, notes FROM `studienordnung_import` WHERE id = '.esc($import_id));
			if(!$import_row) {
				print json_encode(array('ok' => false, 'error' => 'Import-Eintrag nicht gefunden.'));
				exit;
			}

			$studiengang_id = (int)$import_row['studiengang_id'];
			if($studiengang_id <= 0 && $new_studiengang_name !== '') {
				$studiengang_id = soi_ensure_studiengang($new_studiengang_name, '', 1);
			}
			if($studiengang_id <= 0) {
				print json_encode(array('ok' => false, 'error' => 'Kein Studiengang wählbar. Bitte neuen anlegen.'));
				exit;
			}

			$imported_modules = 0;
			$imported_pns = 0;
			$seen_pns = array();
			$messages = array();

			foreach($modules_in as $idx => $m) {
				if(!is_array($m)) continue;
				if(empty($m['include'])) continue;
				$modulnummer = trim((string)($m['modulnummer'] ?? ''));
				$name = trim((string)($m['name'] ?? ''));
				if($modulnummer === '' || $name === '') continue;

				$beschreibung_parts = array();
				if(isset($m['lp']) && $m['lp'] !== '') $beschreibung_parts[] = 'LP: '.(int)$m['lp'];
				if(isset($m['sws']) && $m['sws'] !== '' && $m['sws'] !== null) $beschreibung_parts[] = 'SWS: '.(float)$m['sws'];
				if(isset($m['dauer_semester']) && $m['dauer_semester'] !== '') $beschreibung_parts[] = 'Dauer: '.(int)$m['dauer_semester'].' Sem.';
				$beschreibung = mb_substr(implode('; ', $beschreibung_parts), 0, 500);

				rquery('INSERT INTO `modul` (`name`, `studiengang_id`, `abkuerzung`, `beschreibung`) VALUES ('.esc($name).', '.esc($studiengang_id).', '.esc($modulnummer).', '.esc($beschreibung).') ON DUPLICATE KEY UPDATE name=VALUES(name), beschreibung=VALUES(beschreibung)');

				$mod_row = get_single_row_from_query('SELECT id FROM `modul` WHERE `studiengang_id` = '.esc($studiengang_id).' AND `abkuerzung` = '.esc($modulnummer).' LIMIT 1');
				if(is_null($mod_row) || $mod_row === '' || $mod_row === false) {
					$messages[] = 'Konnte modul_id für '.$modulnummer.' nicht ermitteln.';
					continue;
				}
				$modul_id = (int)$mod_row;
				$imported_modules++;

				if($create_pns) {
					$ptypes = array();
					if(isset($m['pruefungstypen']) && is_array($m['pruefungstypen'])) {
						$ptypes = $m['pruefungstypen'];
					} elseif(isset($m['pruefungstypen_str']) && trim($m['pruefungstypen_str']) !== '') {
						$ptypes = array_map('trim', explode(',', $m['pruefungstypen_str']));
					}
					if(!$ptypes) $ptypes = array('Klausurarbeit');
					foreach($ptypes as $ptname) {
						if(!$ptname) continue;
						$pt_id = soi_ensure_pruefungstyp($ptname);
						if(!$pt_id) continue;
						$generated_nr = soi_generate_pruefungsnummer($modulnummer, $ptname, $m['lp'] ?? '', $seen_pns);
						rquery('INSERT INTO `pruefungsnummer` (`pruefungsnummer`, `modul_id`, `pruefungstyp_id`, `modulbezeichnung`) VALUES ('.esc($generated_nr).', '.esc($modul_id).', '.esc($pt_id).', '.esc($modulnummer.' '.$name).')');
						$pn_row = get_single_row_from_query('SELECT id FROM `pruefungsnummer` WHERE `pruefungsnummer` = '.esc($generated_nr).' LIMIT 1');
						$pn_id = (!is_null($pn_row) && $pn_row !== '' && $pn_row !== false) ? (int)$pn_row : null;
						rquery('INSERT INTO `pruefungsnummer_import` (import_id, modul_id, pruefungsnummer_id, generated_nr, pruefungstyp_name, lp) VALUES ('.esc($import_id).', '.esc($modul_id).', '.esc($pn_id).', '.esc($generated_nr).', '.esc($ptname).', '.esc($m['lp'] ?? null).')');
						$imported_pns++;
					}
				}
			}

			rquery('UPDATE `studienordnung_import` SET `modules_imported` = '.esc($imported_modules).', `pruefungsnummern_imported` = '.esc($imported_pns).', `studiengang_id` = '.esc($studiengang_id).' WHERE `id` = '.esc($import_id));

			print json_encode(array(
				'ok' => true,
				'import_id' => $import_id,
				'modules_imported' => $imported_modules,
				'pruefungsnummern_imported' => $imported_pns,
				'studiengang_id' => $studiengang_id,
				'messages' => $messages,
			), JSON_UNESCAPED_UNICODE);
			exit;
		} else {
			print '<p>Unbekannte Stage: '.htmlentities($stage).'</p>';
		}
?>
<?php if(!$is_ajax_stage): ?>
	</div>
<?php
	endif;
	if(!$is_ajax_stage) {
		foreach (array(
				array('hint', 'blue'),
				array('error', 'red'),
				array('right_issue', 'red'),
				array('warning', 'orange'),
				array('message', 'blue'),
				array('easter_egg', 'hotpink'),
				array('success', 'green')
			) as $msg) {
			show_output($msg[0], $msg[1]);
		}
	}
?>
