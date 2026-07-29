<?php
	$included_files = get_included_files();
	$included_files = array_map('basename', $included_files);

	if(!in_array('functions.php', $included_files)) {
		include_once('../functions.php');
	}

	if(!check_page_rights(get_page_id_by_filename(basename(__FILE__)))) {
		print '<p class="class_red">Sie haben keine Rechte, auf diese Seite zuzugreifen.</p>';
		return;
	}

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
			// Wir extrahieren den Bereich ab "Anlage 1: Modulbeschreibungen" bis "Anlage 2"
			$anlage1_start = mb_strpos($raw_text, 'Anlage 1');
			if($anlage1_start === false) {
				$anlage1_start = 0;
			}
			$anlage2_start = mb_strpos($raw_text, 'Anlage 2', $anlage1_start);
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
			$anlage2_start = mb_strpos($raw_text, 'Anlage 2');
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
			$existing = get_single_row_from_query('SELECT id FROM `studiengang` WHERE `name` = '.esc($name).' LIMIT 1');
			if(is_array($existing) && isset($existing[0])) return (int)$existing[0];
			$full_name = $name;
			if($degree && mb_stripos($full_name, $degree) === false) {
				$full_name = $degree.' '.$name;
			}
			$query = 'INSERT INTO `studiengang` (`name`, `institut_id`, `studienordnung`, `order_key`) VALUES ('.
				esc($full_name).', '.esc((int)$institut_id).', '.esc('importiert am '.date('Y-m-d H:i')).', 999999)';
			rquery($query);
			$new = get_single_row_from_query('SELECT id FROM `studiengang` WHERE `name` = '.esc($full_name).' LIMIT 1');
			if(is_array($new) && isset($new[0])) return (int)$new[0];
			return null;
		}
	}

	/** Findet oder erstellt pruefungstyp. */
	if(!function_exists('soi_ensure_pruefungstyp')) {
		function soi_ensure_pruefungstyp($name) {
			if(!$name) return null;
			$existing = get_single_row_from_query('SELECT id FROM `pruefungstyp` WHERE `name` = '.esc($name).' LIMIT 1');
			if(is_array($existing) && isset($existing[0])) return (int)$existing[0];
			rquery('INSERT INTO `pruefungstyp` (`name`) VALUES ('.esc($name).')');
			$new = get_single_row_from_query('SELECT id FROM `pruefungstyp` WHERE `name` = '.esc($name).' LIMIT 1');
			if(is_array($new) && isset($new[0])) return (int)$new[0];
			return null;
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
?>
	<div id="studienordnung_import">
		<?php print get_seitentext(); ?>
<?php
		include_once('hinweise.php');

		if($stage === 'list' || $stage === '') {
			// Übersicht der bisherigen Importe
			$query = 'SELECT `id`, `filename`, `imported_at`, `program_name`, `degree`, `modules_found`, `modules_imported`, `pruefungsnummern_imported` FROM `studienordnung_import` ORDER BY `imported_at` DESC LIMIT 50';
			$result = rquery($query);
?>
			<h2>Studienordnung (PDF) hochladen</h2>
			<p>Hier kann eine Studienordnung (PDF) hochgeladen werden. Das System extrahiert automatisch Modulnummer, Modulname, ECTS-Leistungspunkte, Prüfungstypen und Studienverlauf (Anlage 2) und legt diese in der Datenbank an.</p>
			<form method="post" enctype="multipart/form-data" action="admin?page=<?php print $GLOBALS['this_page_number']; ?>&stage=upload">
				<table>
					<tr>
						<th>PDF-Datei</th>
						<td><input noautosubmit="1" type="file" name="pdf" accept="application/pdf" required /></td>
					</tr>
					<tr>
						<th>Institut</th>
						<td><?php create_select($institute, get_get('institut'), 'institut', 1); ?></td>
					</tr>
					<tr>
						<th>Studiengang</th>
						<td>
							<select name="studiengang_mode" id="studiengang_mode">
								<option value="existing">bestehenden auswählen</option>
								<option value="auto">aus PDF automatisch ermitteln</option>
								<option value="new">neuen anlegen</option>
							</select>
							<div id="existing_studiengang_box">
								<?php
									$studiengaenge = create_studiengaenge_array();
									create_select($studiengaenge, '', 'studiengang_id', 1);
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
							<label><input noautosubmit="1" type="checkbox" name="auto_commit" value="1" /> Sofit ohne Vorschau importieren (nur empfohlen, wenn Sie dem Parser vertrauen)</label><br />
							<label><input noautosubmit="1" type="checkbox" name="create_pruefungsnummern" value="1" checked /> Prüfungsnummern automatisch erzeugen</label><br />
							<label><input noautosubmit="1" type="checkbox" name="reuse_in_other_studiengaenge" value="1" checked /> Module zusätzlich in allen Studiengängen anlegen, die sie laut Verwendbarkeit nutzen</label>
						</td>
					</tr>
					<tr>
						<td colspan="2"><input noautosubmit="1" type="submit" value="Hochladen und analysieren" /></td>
					</tr>
				</table>
			</form>

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
						var m = document.getElementById('studiengang_mode').value;
						document.getElementById('existing_studiengang_box').style.display = (m === 'existing') ? '' : 'none';
						document.getElementById('new_studiengang_box').style.display = (m === 'new') ? '' : 'none';
					}
					var sel = document.getElementById('studiengang_mode');
					if(sel) sel.addEventListener('change', update_mode);
					update_mode();
				})();
			</script>
<?php
		} elseif($stage === 'upload') {
			// Upload + Analyse
			if(!isset($_FILES['pdf']) || $_FILES['pdf']['error'] !== UPLOAD_ERR_OK) {
				warning('Keine Datei hochgeladen oder Upload-Fehler.');
				print '<p><a href="admin?page='.$GLOBALS['this_page_number'].'">Zurück</a></p>';
			} else {
				$pdf_path = $_FILES['pdf']['tmp_name'];
				$filename = $_FILES['pdf']['name'];
				if(!soi_is_pdf($pdf_path)) {
					error('Die hochgeladene Datei ist kein gültiges PDF.');
					print '<p><a href="admin?page='.$GLOBALS['this_page_number'].'">Zurück</a></p>';
				} else {
					$pdf_bytes = file_get_contents($pdf_path);
					$sha = hash('sha256', $pdf_bytes);
					$size = strlen($pdf_bytes);

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
						$mode = get_post('studiengang_mode') ?: 'auto';
						$institut_id = (int)get_post('institut');
						if($institut_id <= 0) {
							$first = reset($institute);
							if(is_array($first) && isset($first[0])) $institut_id = (int)$first[0];
						}
						$studiengang_id = null;
						if($mode === 'existing') {
							$studiengang_id = (int)get_post('studiengang_id');
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

							$query = 'INSERT INTO `studienordnung_import` (studiengang_id, filename, pdf_sha256, pdf_size, pdf_data, raw_text, degree, program_name, modules_found, modules_imported, pruefungsnummern_imported, imported_by_user_id) VALUES ('.
								esc($studiengang_id).', '.esc($filename).', '.esc($sha).', '.esc($size).', '.esc($pdf_bytes).', '.esc($raw_text).', '.
								esc($cover['degree']).', '.esc($cover['program']).', '.esc(count($modules)).', 0, 0, '.esc($user_id).')';
							rquery($query);
							$import_row = get_single_row_from_query('SELECT id FROM `studienordnung_import` WHERE `pdf_sha256` = '.esc($sha).' ORDER BY id DESC LIMIT 1');
							$import_id = is_array($import_row) && isset($import_row[0]) ? (int)$import_row[0] : 0;

							if(!$import_id) {
								error('Import-Eintrag konnte nicht angelegt werden.');
								print '<p><a href="admin?page='.$GLOBALS['this_page_number'].'">Zurück</a></p>';
							} elseif($auto_commit) {
								// Direkt committen
								require_once __DIR__ . '/../pages/studienordnung_import_commit.php';
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
			$studiengang_id = is_array($row) && isset($row[0]) ? (int)$row[0] : 0;

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
					if(!is_array($mod_row) || !isset($mod_row[0])) continue;
					$modul_id = (int)$mod_row[0];
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
							$pn_id = is_array($pn_row) && isset($pn_row[0]) ? (int)$pn_row[0] : null;
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
				print '<h3>Original-PDF</h3><p><a href="admin?page='.$GLOBALS['this_page_number'].'&stage=download&id='.$id.'">PDF herunterladen</a> ('.number_format(strlen($row['pdf_data'])/1024, 1).' KB)</p>';
				print '<p><a href="admin?page='.$GLOBALS['this_page_number'].'">Zurück</a></p>';
			}
		} elseif($stage === 'download') {
			$id = (int)get_get('id');
			$row = get_single_row_from_query_assoc('SELECT filename, pdf_data FROM `studienordnung_import` WHERE id = '.esc($id));
			if(!$row) {
				warning('Nicht gefunden.');
			} else {
				header('Content-Type: application/pdf');
				header('Content-Disposition: attachment; filename="'.preg_replace('/[^A-Za-z0-9._-]/', '_', $row['filename']).'"');
				header('Content-Length: '.strlen($row['pdf_data']));
				print $row['pdf_data'];
				exit;
			}
		} else {
			print '<p>Unbekannte Stage: '.htmlentities($stage).'</p>';
		}
?>
	</div>
<?php
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
?>
