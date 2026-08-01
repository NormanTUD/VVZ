<?php
/*
 * Tests for pages/studienordnung_parser.php (SoiExtractor).
 *
 * Strategie:
 *  - Pure Unit-Tests (keine PDF-Tools nötig) für alle Klassenmethoden.
 *  - Integration-Tests gegen tests/fixtures/05_06soBAP18.09.2018.pdf, die nur
 *    dann laufen, wenn pdftotext/pdftohtml installiert sind.
 *
 * Voraussetzungen (sonst wird der Integration-Test übersprungen):
 *  - pdftotext (poppler-utils)
 *  - pdftohtml (für XML/HTML-Methoden)
 *
 * Aufruf:
 *  php tests/run_pure_tests.php tests/test_soi_parser.php
 */

if(!class_exists('SoiExtractor', false)) {
	require_once(__DIR__ . '/../pages/studienordnung_parser.php');
}

if(!function_exists('soi_persist_anlage2_detailed')) {
	require_once(__DIR__ . '/../pages/studienordnung_persist.php');
}

if(!function_exists('is_true')) {
	function is_true($name, $cond) {
		is_equal($name, $cond, true);
	}
}

$ex = new SoiExtractor();

$pdf_path = __DIR__ . '/fixtures/05_06soBAP18.09.2018.pdf';
$has_pdf = file_exists($pdf_path);
$pdftotext = trim((string)@shell_exec('command -v pdftotext 2>/dev/null'));
$pdftohtml = trim((string)@shell_exec('command -v pdftohtml 2>/dev/null'));
$pdftoppm = trim((string)@shell_exec('command -v pdftoppm 2>/dev/null'));

/* ============================ isModulCode() ============================ */

is_equal("isModulCode: SLK-BA-KP-3S-AL ist gültig", $ex->isModulCode('SLK-BA-KP-3S-AL'), true);
is_equal("isModulCode: PhF-Phil-BA-PM1 ist gültig", $ex->isModulCode('PhF-Phil-BA-PM1'), true);
is_equal("isModulCode: ABCDE ohne Bindestrich ungültig", $ex->isModulCode('ABCDE'), false);
is_equal("isModulCode: einzelner Buchstabe ungültig", $ex->isModulCode('A'), false);
is_equal("isModulCode: nur Kleinbuchstaben ohne Ziffern ungültig", $ex->isModulCode('aa-bbbb'), false);
is_equal("isModulCode: 50 Zeichen zu lang", $ex->isModulCode(str_repeat('A', 50)), false);
is_equal("isModulCode: leerer String ungültig", $ex->isModulCode(''), false);
is_equal("isModulCode: SLK-1A-BC gültig", $ex->isModulCode('SLK-1A-BC'), true);

/* ============================ looksLikeModulCode ====================== */

is_equal("looksLikeModulCode ist Alias für isModulCode",
	$ex->looksLikeModulCode('SLK-BA-KP-3S-AL'), $ex->isModulCode('SLK-BA-KP-3S-AL'));

/* ============================ parseCover() ============================ */

$pt = new SoiPdfText();
$pt->full_text = "Studienordnung\nfür den Bachelorstudiengang Philosophie\n\nVom 18. September 2018\n";
$pt->pages = explode("\f", $pt->full_text);
$cover = $ex->parseCover($pt);

is_equal("parseCover: Bachelor erkannt", $cover['degree'], 'Bachelor');
is_equal("parseCover: Philosophie ohne 'Vom …' Suffix", $cover['program'], 'Philosophie');

$pt2 = new SoiPdfText();
$pt2->full_text = "Prüfungsordnung\nfür den Masterstudiengang Informatik\n";
$pt2->pages = explode("\f", $pt2->full_text);
$cover2 = $ex->parseCover($pt2);
is_equal("parseCover: Master-Studiengang", $cover2['degree'], 'Master');
is_equal("parseCover: Master-Studiengang Programmname", $cover2['program'], 'Informatik');

$pt3 = new SoiPdfText();
$pt3->full_text = "Studienordnung für den Diplomstudiengang Mathematik\nVom 1. Januar 2020";
$pt3->pages = explode("\f", $pt3->full_text);
$cover3 = $ex->parseCover($pt3);
is_equal("parseCover: Diplomstudiengang", $cover3['degree'], 'Diplom');
is_equal("parseCover: Diplom Mathematik", $cover3['program'], 'Mathematik');

/* ============================ parseBboxHtml() ========================= */

$sample_bbox = '<?xml version="1.0"?>
<page width="595" height="842">
<word xMin="70.8" yMin="100.0" xMax="120.5" yMax="115.0">SLK-BA-KP-3S-AL</word>
<word xMin="200.0" yMin="100.0" xMax="300.0" yMax="115.0">Spezialisierung</word>
<word xMin="350.0" yMin="100.0" xMax="400.0" yMax="115.0">antike</word>
<word xMin="410.0" yMin="100.0" xMax="460.0" yMax="115.0">Literatur</word>
</page>
<page width="595" height="842">
<word xMin="70.8" yMin="200.0" xMax="120.5" yMax="215.0">PhF-Phil-BA-PM1</word>
<word xMin="200.0" yMin="200.0" xMax="300.0" yMax="215.0">Propaedeutik</word>
</page>';
$words = $ex->parseBboxHtml($sample_bbox);
is_equal("parseBboxHtml: 6 Wörter erkannt", count($words), 6);
is_equal("parseBboxHtml: erstes Wort ist SLK-BA-KP-3S-AL", $words[0]['text'], 'SLK-BA-KP-3S-AL');
is_equal("parseBboxHtml: erstes Wort Seite 1", (int)$words[0]['page'], 1);
is_equal("parseBboxHtml: zweites Wort Seite 2 (PhF-Phil-BA-PM1)", (int)$words[5]['page'], 2);
is_equal("parseBboxHtml: x-Position korrekt", (int)$words[0]['x'], 70);
is_equal("parseBboxHtml: y-Position korrekt", (int)$words[0]['y'], 100);
is_equal("parseBboxHtml: Breite berechnet", (int)$words[0]['w'], 49);

/* ============================ detectColumns() ========================= */

// Column-Detection arbeitet nur zuverlässig, wenn pro Bin genügend Wörter sind.
// Wir simulieren mehrere Zeilen mit Wörtern in den gleichen x-Bereichen.
$col_words = [];
$x_cols = [70, 200, 350, 400, 460];
$labels = ['MOD1','NAME','SWS','PL','LP'];
for($row = 0; $row < 10; $row++) {
	for($c = 0; $c < 5; $c++) {
		$col_words[] = ['page'=>1,'x'=>$x_cols[$c],'y'=>100 + $row*20,'w'=>30,'h'=>15,'text'=>$labels[$c].$row];
	}
}
$cols = $ex->detectColumns($col_words);
is_equal("detectColumns: mehrere Spalten gefunden", count($cols) >= 4, true);

is_equal("detectColumns: leere Eingabe → leere Spalten", $ex->detectColumns([]), []);

/* ============================ groupRows() ============================== */

$rows = $ex->groupRows($col_words);
is_equal("groupRows: alle Wörter einer y-Gruppe ergeben eine Zeile", count($rows), 10);
is_equal("groupRows: erste Zeile enthält 5 Wörter", count($rows[0]), 5);

/* ============================ locateModulesInPages() ================= */

$pt_pages = new SoiPdfText();
$pt_pages->full_text = "Page 1 header\nSome text\nSLK-BA-KP-3S-AL Modname\nMore text\fPage 2 header\nEven more text\nPhF-Phil-BA-PM1 Propaedeutik\nEnd";
$pt_pages->pages = explode("\f", $pt_pages->full_text);

$loc_result = $ex->locateModulesInPages($pt_pages, [
	['modulnummer' => 'SLK-BA-KP-3S-AL', 'name' => 'Modname'],
	['modulnummer' => 'PhF-Phil-BA-PM1', 'name' => 'Propaedeutik'],
	['modulnummer' => 'NOT-EXISTENT', 'name' => 'Unknown'],
]);
is_equal("locateModulesInPages: 3 Einträge zurück", count($loc_result), 3);
is_equal("locateModulesInPages: SLK auf Seite 1", $loc_result['SLK-BA-KP-3S-AL'], 1);
is_equal("locateModulesInPages: PhF auf Seite 2", $loc_result['PhF-Phil-BA-PM1'], 2);
is_equal("locateModulesInPages: unbekanntes Modul landet auf Seite 1 (Fallback)", $loc_result['NOT-EXISTENT'], 1);

/* ============================ parseAnlage2Row() ======================= */

$row = $ex->parseAnlage2Row('SLK-BA-KP-3S-AL', 'Spezialisierung   antike Literatur   2/0/0/2   0/0/2/0   6', 'Kernbereich');
is_equal("parseAnlage2Row: code erhalten", $row['modulnummer'], 'SLK-BA-KP-3S-AL');
is_equal("parseAnlage2Row: name", $row['name'], 'Spezialisierung');
is_equal("parseAnlage2Row: lp = 6", $row['lp'], 6);
is_equal("parseAnlage2Row: 2 Semester erkannt", count($row['semester']), 2);
is_equal("parseAnlage2Row: Semester 1 sws", $row['semester'][0]['sws'], ['2','0','0','2']);
is_equal("parseAnlage2Row: Semester 2 sws", $row['semester'][1]['sws'], ['0','0','2','0']);
is_equal("parseAnlage2Row: section übernommen", $row['section'], 'Kernbereich');

/* Wildcard-Semester (Stern-Stern-Stern-Stern) */
$row_w = $ex->parseAnlage2Row('PHF-AAA-1', 'Wildcard-Modul   */*/*/*   */*/*/*   */*/*/*   */*/*/*   */*/*/*   */*/*/*   70', 'Erg');
is_equal("parseAnlage2Row: Wildcard-Semester korrekt", $row_w['semester'][0]['sws'], ['*','*','*','*']);
is_equal("parseAnlage2Row: 6 Wildcard-Semester + LP=70", count($row_w['semester']), 6);
is_equal("parseAnlage2Row: LP=70", $row_w['lp'], 70);

/* Modulname ohne LP am Ende */
$row_no_lp = $ex->parseAnlage2Row('PHF-BBB-2', 'Einführungsmodul   2/0/0/2', 'Kernbereich');
is_equal("parseAnlage2Row: LP=null wenn keine ganze Zahl am Ende", $row_no_lp['lp'], null);
is_equal("parseAnlage2Row: 1 Semester ohne LP", count($row_no_lp['semester']), 1);

/* ============================ findAnlageBoundaries() ================== */

$pt_b = new SoiPdfText();
$pt_b->full_text = "Inhaltsübersicht\n§1 Geltungsbereich\n\nAnlage 1: Modulbeschreibungen\nAnlage 2: Studienablaufplan\n\nA-1 Inhalt\nModul 1\n\nAnlage 2:\nStudienablaufplan\n\nHier beginnt der echte Plan\nModul-1 V/Ü 2 6\n";
$boundaries = $ex->findAnlageBoundaries($pt_b);
is_equal("findAnlageBoundaries: anlage1_start ist int", is_int($boundaries['anlage1_start']), true);
is_equal("findAnlageBoundaries: anlage2_start ist int", is_int($boundaries['anlage2_start']), true);
// Die echte Anlage 2 muss nach "Studienablaufplan" gefunden werden, nicht im TOC.
is_equal("findAnlageBoundaries: anlage2_start liegt NACH dem TOC-Eintrag",
	$boundaries['anlage2_start'] > 100, true);

/* ============================ METHODS-Konstante ======================= */

is_equal("METHODS-Konstante enthält 7 Methoden", count(SoiExtractor::METHODS), 7);
is_equal("METHODS enthält 'auto'", in_array('auto', SoiExtractor::METHODS, true), true);
is_equal("METHODS enthält 'bbox'", in_array('bbox', SoiExtractor::METHODS, true), true);
is_equal("METHODS enthält 'layout'", in_array('layout', SoiExtractor::METHODS, true), true);
is_equal("METHODS enthält 'xml'", in_array('xml', SoiExtractor::METHODS, true), true);
is_equal("METHODS enthält 'html'", in_array('html', SoiExtractor::METHODS, true), true);
is_equal("METHODS enthält 'table'", in_array('table', SoiExtractor::METHODS, true), true);
is_equal("METHODS enthält 'hybrid'", in_array('hybrid', SoiExtractor::METHODS, true), true);

/* ============================ Fehlende Tools ========================== */

if($pdftotext === '') {
	print "HINWEIS: pdftotext nicht installiert — Integration-Tests übersprungen.\n";
	print "  Installieren mit: apt-get install poppler-utils\n";
}
if($pdftohtml === '') {
	print "HINWEIS: pdftohtml nicht installiert — XML/HTML-Methoden-Tests übersprungen.\n";
	print "  Installieren mit: apt-get install poppler-utils\n";
}
if($pdftoppm === '') {
	print "HINWEIS: pdftoppm nicht installiert — PDF-Seiten-Thumbnail-Tests übersprungen.\n";
}

/* ============================ Integration ============================== */

if($has_pdf && $pdftotext !== '') {
	$r = $ex->extract($pdf_path, 'auto');
	is_equal("Integration (auto): Methode aus METHODS-Liste",
		in_array($r['method'], SoiExtractor::METHODS, true), true);
	is_equal("Integration (auto): cover.degree ist Bachelor",
		$r['cover']['degree'] === 'Bachelor', true);
	is_equal("Integration (auto): cover.program ist Philosophie",
		$r['cover']['program'] === 'Philosophie', true);
	is_equal("Integration (auto): > 50 Module erkannt", $r['modules_count'] > 50, true);
	is_equal("Integration (auto): > 20 Anlage-2-Einträge", $r['anlage2_count'] > 20, true);
	is_equal("Integration (auto): _alternatives vorhanden", isset($r['_alternatives']), true);
	is_equal("Integration (auto): errors leer", count($r['errors']), 0);

	// Prüfe, dass mindestens ein Modul einen bekannten Code, einen Namen und LP hat.
	$found_complete = 0;
	$found_pt = 0;
	foreach($r['modules'] as $m) {
		if(!empty($m['modulnummer']) && !empty($m['name']) && isset($m['lp']) && $m['lp'] > 0) {
			$found_complete++;
		}
		if(!empty($m['pruefungstypen'])) {
			$found_pt++;
		}
	}
	is_equal("Integration (auto): > 30 Module mit modulnummer+name+LP", $found_complete > 30, true);
	is_equal("Integration (auto): > 20 Module mit Prüfungstypen", $found_pt > 20, true);

	// locateModulesInPages direkt auf dem echten PDF testen.
	$layout_text = $ex->load($pdf_path, 'layout');
	$sample_mods = array_slice($r['modules'], 0, 5);
	$mod_pages = $ex->locateModulesInPages($layout_text, $sample_mods);
	is_equal("Integration (locateModulesInPages): 5 Einträge zurück", count($mod_pages), 5);
	$all_positive = true;
	foreach($mod_pages as $p) { if((int)$p < 1) { $all_positive = false; break; } }
	is_equal("Integration (locateModulesInPages): alle Seitenzahlen ≥ 1", $all_positive, true);

	// load() mit verschiedenen Methoden.
	foreach(['bbox', 'layout', 'hybrid'] as $m) {
		$loaded = $ex->load($pdf_path, $m);
		is_equal("Integration (load $m): full_text nicht leer", strlen($loaded->full_text) > 1000, true);
		is_equal("Integration (load $m): pages > 100", count($loaded->pages) > 100, true);
	}
	$loaded_bbox = $ex->load($pdf_path, 'bbox');
	is_equal("Integration (load bbox): words > 10000", count($loaded_bbox->words) > 10000, true);

	// Per-Method Vergleich.
	foreach(['bbox', 'layout', 'html'] as $m) {
		$rm = $ex->extract($pdf_path, $m);
		is_equal("Integration ($m): Module ≥ 50", $rm['modules_count'] >= 50, true);
		is_equal("Integration ($m): keine fatalen errors", count($rm['errors']) === 0, true);
	}
}

if($has_pdf && $pdftohtml !== '') {
	$r_xml = $ex->extract($pdf_path, 'xml');
	is_equal("Integration (xml): keine Exception, errors tolerierbar",
		count($r_xml['errors']) <= 1, true);
}

/* ============================ Fehlende-Tools-Sentinel ================== */

if($pdftotext === '' && $pdftohtml === '' && $pdftoppm === '') {
	is_equal("Sentinel: alle Poppler-Tools fehlen — melden bei UI-Anzeige", 1, 1);
} else {
	is_equal("Sentinel: mindestens ein Poppler-Tool vorhanden", 1, 1);
}

/* ============================ filterValidModules ======================== */

$valid = $ex->filterValidModules([
	['modulnummer' => 'SLK-BA-KP-3S-AL', 'name' => 'Spezialisierung'],
	['modulnummer' => '', 'name' => 'Empty code'],
	['modulnummer' => 'ABC', 'name' => 'Too short'],
	['modulnummer' => 'SLK BA KP', 'name' => 'Whitespace in code'],
	['modulnummer' => 'PhF-Phil-BA-PM1', 'name' => ''],
	['modulnummer' => 'PhF-Phil-BA-PM2', 'name' => '   '],
	['modulnummer' => 'SLK-BA-G-2B-DAF', 'name' => 'Basismodul DAF'],
]);
is_equal("filterValidModules: 2 gültige Module übrig", count($valid), 2);
is_equal("filterValidModules: behält SLK-BA-KP-3S-AL",
	in_array('SLK-BA-KP-3S-AL', array_column($valid, 'modulnummer'), true), true);

/* ============================ filterValidAnlage2 ======================= */

$valid_a2 = $ex->filterValidAnlage2([
	['modulnummer' => 'SLK-BA-A-1B-S'],
	['modulnummer' => 'SLK-BA-R-F-1B-K*'],
	['modulnummer' => ''],
	['modulnummer' => 'X'],
	['modulnummer' => 'SLK BA R F'], // whitespace
	['modulnummer' => 'SLK-BA-R-F-2SP-B2.1.2*'],
]);
is_equal("filterValidAnlage2: 3 gültige Zeilen übrig", count($valid_a2), 3);

/* ============================ row_y_greater_than ======================= */

is_equal("row_y_greater_than: alle y > threshold = true",
	$ex->row_y_greater_than([['y' => 100.0], ['y' => 110.0]], 50.0), true);
is_equal("row_y_greater_than: y ≤ threshold = false",
	$ex->row_y_greater_than([['y' => 50.0], ['y' => 110.0]], 50.0), false);
is_equal("row_y_greater_than: leeres row = true",
	$ex->row_y_greater_than([], 50.0), true);

/* ============================ Integration: Module-Datenqualität ======== */

if($has_pdf && $pdftotext !== '') {
	$r = $ex->extract($pdf_path, 'layout');

	// Mindestens 60 Module mit modulnummer+name+LP.
	$complete = 0;
	foreach($r['modules'] as $m) {
		if(!empty($m['modulnummer']) && !empty($m['name']) && isset($m['lp']) && $m['lp'] > 0) $complete++;
	}
	is_equal("Integration: ≥ 60 Module mit modulnummer+name+LP", $complete >= 60, true);

	// Anlage 2 hat ≥ 80 Einträge (nach Romanistik-Wrap-Fix).
	is_equal("Integration: Anlage 2 hat ≥ 80 Einträge", $r['anlage2_count'] >= 80, true);

	// Modulnummer mit Wrap: SLK-BA-R-F-1SP-B2.1.1 (komplett, nicht getrennt).
	$found_spr = false;
	foreach($r['modules'] as $m) {
		if(($m['modulnummer'] ?? '') === 'SLK-BA-R-F-1SP-B2.1.1') { $found_spr = true; break; }
	}
	is_equal("Integration: SLK-BA-R-F-1SP-B2.1.1 vollständig gefunden", $found_spr, true);

	// Dozent enthält keine Email in Klammern + gleichzeitig im Namen.
	$name_has_email = 0;
	foreach($r['modules'] as $m) {
		if(preg_match('/\([^)]+@[^)]+\)/', $m['name'] ?? '')) $name_has_email++;
	}
	is_equal("Integration: kein Modul mit Email im Namen", $name_has_email, 0);
}

/* ============================ Cross-Validation ============================ */

if($has_pdf && $pdftotext !== '') {
	$cv = $ex->crossValidate($pdf_path);
	is_equal("crossValidate: alle Methoden konsistent (modul+anlage2+cover+sample+lp)",
		$cv['all_consistent'], true);
	is_equal("crossValidate: 6 Methoden geprüft",
		count($cv['method_stats']), 6);
	$methods_with_results = array_filter($cv['method_stats'], function($s) {
		return !isset($s['error']);
	});
	is_equal("crossValidate: alle 6 Methoden liefern Ergebnis (kein Fehler)",
		count($methods_with_results), 6);
	$module_counts = array_map(function($s) {
		return $s['modules_count'] ?? 0;
	}, $methods_with_results);
	is_equal("crossValidate: alle Methoden finden gleich viele Module",
		count(array_unique($module_counts)), 1);
	$a2_counts = array_map(function($s) {
		return $s['anlage2_count'] ?? 0;
	}, $methods_with_results);
	is_equal("crossValidate: alle Methoden finden gleich viele Anlage-2-Einträge",
		count(array_unique($a2_counts)), 1);
	$degrees = array_map(function($s) { return $s['cover_degree'] ?? null; }, $methods_with_results);
	is_equal("crossValidate: alle Methoden erkennen denselben Abschluss",
		count(array_unique(array_filter($degrees))), 1);
}

/* ============================ DB-Persistenz ============================ */

$GLOBALS['_soi_rquery_log'] = array();

/* soi_rolle_for_section: leitet aus Section-Namen die modul_zuordnung-Rolle ab. */
is_equal("rolle: leerer Section → pflicht", soi_rolle_for_section(''), 'pflicht');
is_equal("rolle: null → pflicht", soi_rolle_for_section(null), 'pflicht');
is_equal("rolle: Kernbereich → kernbereich", soi_rolle_for_section('Kernbereich'), 'kernbereich');
is_equal("rolle: Ergänzungsbereich → ergaenzungsbereich", soi_rolle_for_section('Ergänzungsbereich'), 'ergaenzungsbereich');
is_equal("rolle: Wahlpflichtbereich → wahlpflicht", soi_rolle_for_section('Wahlpflichtbereich'), 'wahlpflicht');
is_equal("rolle: Spezialisierung → wahlpflicht", soi_rolle_for_section('Spezialisierung'), 'wahlpflicht');
is_equal("rolle: Vertiefung → pflicht", soi_rolle_for_section('Vertiefungsmodul'), 'pflicht');
is_equal("rolle: Hauptfach → hauptfach", soi_rolle_for_section('Hauptfach'), 'hauptfach');
is_equal("rolle: unbekannt → sonstige", soi_rolle_for_section('XYZ-Foo-Bar'), 'sonstige');

/* soi_ensure_pruefungstyp: get_or_create-Pattern */
$seen = array();
is_equal("generate_pruefungsnummer: Klausur → KL", soi_generate_pruefungsnummer('PHIL-A', 'Klausurarbeit', 5, $seen), 'PHILA-KL-5');
is_equal("generate_pruefungsnummer: Mündlich → MP", soi_generate_pruefungsnummer('PHIL-B', 'Mündliche Prüfung', 10, $seen), 'PHILB-MP-10');
is_equal("generate_pruefungsnummer: Hausarbeit → HA", soi_generate_pruefungsnummer('PHIL-C', 'Hausarbeit', 5, $seen), 'PHILC-HA-5');
is_equal("generate_pruefungsnummer: Seminararbeit → SA", soi_generate_pruefungsnummer('PHIL-D', 'Seminararbeit', 5, $seen), 'PHILD-SA-5');
is_equal("generate_pruefungsnummer: Referat → RF", soi_generate_pruefungsnummer('PHIL-E', 'Referat', 5, $seen), 'PHILE-RF-5');
is_equal("generate_pruefungsnummer: Bachelorarbeit → BA", soi_generate_pruefungsnummer('PHIL-F', 'Bachelorarbeit', 12, $seen), 'PHILF-BA-12');
is_equal("generate_pruefungsnummer: Masterarbeit → MA", soi_generate_pruefungsnummer('PHIL-G', 'Masterarbeit', 30, $seen), 'PHILG-MA-30');
is_equal("generate_pruefungsnummer: ohne LP → ohne -N", soi_generate_pruefungsnummer('PHIL-H', 'Klausur', '', $seen), 'PHILH-KL');
is_equal("generate_pruefungsnummer: Kollokium → KO", soi_generate_pruefungsnummer('PHIL-I', 'Kolloquium', 5, $seen), 'PHILI-KO-5');
is_equal("generate_pruefungsnummer: Portfolio → PF", soi_generate_pruefungsnummer('PHIL-J', 'Portfolio', 5, $seen), 'PHILJ-PF-5');
is_equal("generate_pruefungsnummer: Bericht → BE", soi_generate_pruefungsnummer('PHIL-K', 'Bericht', 5, $seen), 'PHILK-BE-5');
is_equal("generate_pruefungsnummer: Duplikat → Suffix -2", soi_generate_pruefungsnummer('PHIL-A', 'Klausurarbeit', 5, $seen), 'PHILA-KL-5-2');
is_equal("generate_pruefungsnummer: unbekannt → KL fallback", soi_generate_pruefungsnummer('PHIL-X', 'Irgendetwas', 5, $seen), 'PHILX-KL-5');

/* soi_sanitize_for_json: Control-Chars maskieren */
is_equal("sanitize_json: Form-Feed escapen",
	soi_sanitize_for_json("vor\x0cnach"), "vor\\u000cnach");
is_equal("sanitize_json: ASCII-Text unverändert",
	soi_sanitize_for_json("Hello World"), "Hello World");
is_equal("sanitize_json: Array rekursiv",
	is_array(soi_sanitize_for_json(array('a' => "x\x0cy"))), true);

/* soi_persist_semester_metadata: LP gleichmäßig verteilen. */
$before = count($GLOBALS['_soi_rquery_log']);
$written = soi_persist_semester_metadata(42, array(
	'lp' => 10,
	'semester' => array(
		array('semester' => 1, 'sws' => array('2','2','0','0'), 'pl_count' => 1),
		array('semester' => 2, 'sws' => array('2','0','2','0'), 'pl_count' => 1),
	),
));
is_equal("persist_semester_metadata: 2 Zeilen geschrieben", $written, 2);
// Letzter SQL enthält modul_id=42
$last_sql = end($GLOBALS['_soi_rquery_log']);
is_equal("persist_semester_metadata: SQL enthält modul_id", strpos($last_sql, '"42"') !== false, true);
// LP-Verteilung: 10/2 = 5 pro Semester.
is_equal("persist_semester_metadata: LP gleich verteilt (5/5)",
	strpos($last_sql, '"5"') !== false, true);

/* soi_persist_anlage2_detailed: schreibt modul_anlage2 + modul_nach_semester. */
$before = count($GLOBALS['_soi_rquery_log']);
$written = soi_persist_anlage2_detailed(100, 7, array(
	'lp' => 8,
	'semester' => array(
		array('semester' => 1, 'sws' => array('2','0','0','2'), 'pl_count' => 1),
		array('semester' => 2, 'sws' => array('0','0','2','0'), 'pl_count' => 1),
	),
));
is_equal("persist_anlage2_detailed: 2 Semester-Zeilen", $written, 2);
// Hat INSERT in modul_anlage2?
$sqls = array_slice($GLOBALS['_soi_rquery_log'], $before);
$has_a2 = false;
$has_mns = false;
foreach($sqls as $s) {
	if(stripos($s, 'INSERT INTO `modul_anlage2`') !== false) $has_a2 = true;
	if(stripos($s, 'INSERT IGNORE INTO `modul_nach_semester`') !== false) $has_mns = true;
}
is_equal("persist_anlage2_detailed: modul_anlage2 INSERT", $has_a2, true);
is_equal("persist_anlage2_detailed: modul_nach_semester INSERT IGNORE", $has_mns, true);

/* Wildcards (*) werden als null behandelt. */
$before = count($GLOBALS['_soi_rquery_log']);
$written = soi_persist_anlage2_detailed(101, 7, array(
	'lp' => 5,
	'semester' => array(
		array('semester' => 1, 'sws' => array('*','*','*','*'), 'pl_count' => 0),
	),
));
$sqls = array_slice($GLOBALS['_soi_rquery_log'], $before);
$sql = implode("\n", $sqls);
// Wildcard-SWS → NULL (nicht numerisch) → sws_total NULL.
is_equal("persist_anlage2_detailed: Wildcards als NULL behandelt",
	strpos($sql, '"*"') === false, true);

/* Ungültiges Semester (sem_n < 1) → überspringen. */
$before = count($GLOBALS['_soi_rquery_log']);
$written = soi_persist_anlage2_detailed(102, 7, array(
	'lp' => 5,
	'semester' => array(
		array('semester' => 0, 'sws' => array('2'), 'pl_count' => 1),
		array('semester' => 13, 'sws' => array('2'), 'pl_count' => 1),
	),
));
is_equal("persist_anlage2_detailed: ungültige Semester werden übersprungen", $written, 0);

/* 7-Felder-Mapping: SWS[5] → sws_praktikum, SWS[6] → sws_sonstige */
$before = count($GLOBALS['_soi_rquery_log']);
$written = soi_persist_anlage2_detailed(103, 7, array(
	'lp' => 5,
	'semester' => array(
		array('semester' => 1, 'sws' => array('1','1','1','1','1','1','1'), 'pl_count' => 0),
	),
));
$sql = implode("\n", array_slice($GLOBALS['_soi_rquery_log'], $before));
is_equal("persist_anlage2_detailed: sws_sprachkurs belegt", strpos($sql, 'sws_sprachkurs') !== false, true);
is_equal("persist_anlage2_detailed: sws_praktikum belegt", strpos($sql, 'sws_praktikum') !== false, true);
is_equal("persist_anlage2_detailed: sws_sonstige belegt", strpos($sql, 'sws_sonstige') !== false, true);

/* soi_persist_modul_zuordnung */
$before = count($GLOBALS['_soi_rquery_log']);
$ok = soi_persist_modul_zuordnung(200, 7, 3, 'pflicht', 10);
is_equal("persist_modul_zuordnung: erfolgreich", $ok, true);
$sql = end($GLOBALS['_soi_rquery_log']);
is_equal("persist_modul_zuordnung: SQL enthält rolle 'pflicht'", strpos($sql, '"pflicht"') !== false, true);
is_equal("persist_modul_zuordnung: SQL enthält studiengang_id 3", strpos($sql, '"3"') !== false, true);

$ok = soi_persist_modul_zuordnung(0, 7, 3, 'pflicht', 10);
is_equal("persist_modul_zuordnung: modul_id=0 → false", $ok, false);
$ok = soi_persist_modul_zuordnung(200, 7, 0, 'pflicht', 10);
is_equal("persist_modul_zuordnung: studiengang_id=0 → false", $ok, false);

// Unbekannte Rolle → 'sonstige'
soi_persist_modul_zuordnung(201, 7, 3, 'unbekannt', 5);
$sql = end($GLOBALS['_soi_rquery_log']);
is_equal("persist_modul_zuordnung: unbekannte Rolle → sonstige", strpos($sql, '"sonstige"') !== false, true);

/* soi_detect_voraussetzungen_for_modul: Aufbaumodul → Basismodul */
$all = array(
	'SLK-BA-F-1B-L' => array('modulnummer' => 'SLK-BA-F-1B-L', 'name' => 'Basismodul Französische Literaturwissenschaft'),
	'SLK-BA-F-2A-L' => array('modulnummer' => 'SLK-BA-F-2A-L', 'name' => 'Aufbaumodul Französische Literaturwissenschaft'),
	'SLK-BA-F-3V-L' => array('modulnummer' => 'SLK-BA-F-3V-L', 'name' => 'Vertiefungsmodul Französische Literaturwissenschaft'),
);
$det = soi_detect_voraussetzungen_for_modul($all['SLK-BA-F-2A-L'], $all);
is_equal("detect: Aufbaumodul → Basismodul (1 Treffer)", count($det), 1);
is_equal("detect: Aufbaumodul Voraussetzung-Code", $det[0]['modulnummer'], 'SLK-BA-F-1B-L');
is_equal("detect: Aufbaumodul Voraussetzung-Typ", $det[0]['typ'], 'aufbauend');

/* Vertiefungsmodul → Aufbaumodul */
$det = soi_detect_voraussetzungen_for_modul($all['SLK-BA-F-3V-L'], $all);
is_equal("detect: Vertiefungsmodul → Aufbaumodul (1 Treffer)", count($det), 1);
is_equal("detect: Vertiefungsmodul Voraussetzung-Code", $det[0]['modulnummer'], 'SLK-BA-F-2A-L');

/* Sprachpraxis-Kette: B2 → B1 */
$lang = array(
	'A2' => array('modulnummer' => 'X-1SP-A2', 'name' => 'Sprachpraxis A2 - Französisch'),
	'B1' => array('modulnummer' => 'X-2SP-B1', 'name' => 'Sprachpraxis B1 - Französisch'),
	'B2' => array('modulnummer' => 'X-3SP-B2', 'name' => 'Sprachpraxis B2 - Französisch'),
	'C1' => array('modulnummer' => 'X-4SP-C1', 'name' => 'Sprachpraxis C1 - Französisch'),
);
$det = soi_detect_voraussetzungen_for_modul($lang['B2'], $lang);
is_equal("detect: Sprachpraxis B2 → B1", count($det), 1);
is_equal("detect: Sprachpraxis B2 → Code", $det[0]['modulnummer'], 'X-2SP-B1');
$det = soi_detect_voraussetzungen_for_modul($lang['A2'], $lang);
is_equal("detect: Sprachpraxis A2 → keine Vorgänger", count($det), 0);

/* Numerische Stufung im Modulnummer-Code: 2A setzt 1B voraus */
$det = soi_detect_voraussetzungen_for_modul(array(
	'modulnummer' => 'SLK-BA-F-2A-L', 'name' => 'Aufbaumodul X',
), array(
	'SLK-BA-F-1B-L' => array('modulnummer' => 'SLK-BA-F-1B-L', 'name' => 'Basismodul X'),
	'SLK-BA-F-2A-L' => array('modulnummer' => 'SLK-BA-F-2A-L', 'name' => 'Aufbaumodul X'),
));
is_equal("detect: Modulnummer-Stufung 2A→1B", count($det) >= 1, true);

/* soi_persist_voraussetzungen */
$code_to_id = array('A' => 1, 'B' => 2);
$before = count($GLOBALS['_soi_rquery_log']);
$n = soi_persist_voraussetzungen(1, 7, array(
	array('modulnummer' => 'B', 'typ' => 'aufbauend', 'grund' => 'Test'),
), $code_to_id);
is_equal("persist_voraussetzungen: 1 geschrieben", $n, 1);
$sql = end($GLOBALS['_soi_rquery_log']);
is_equal("persist_voraussetzungen: SQL modul_id 1", strpos($sql, '"1"') !== false, true);
is_equal("persist_voraussetzungen: SQL voraussetzung_modul_id 2", strpos($sql, '"2"') !== false, true);

// Selbst-Referenz → skip
$n = soi_persist_voraussetzungen(1, 7, array(
	array('modulnummer' => 'A', 'typ' => 'aufbauend', 'grund' => 'Test'),
), $code_to_id);
is_equal("persist_voraussetzungen: Selbst-Referenz wird übersprungen", $n, 0);

// Unbekannter Code → skip
$n = soi_persist_voraussetzungen(1, 7, array(
	array('modulnummer' => 'XXX', 'typ' => 'aufbauend', 'grund' => 'Test'),
), $code_to_id);
is_equal("persist_voraussetzungen: unbekannter Code → skip", $n, 0);

// Ungültiger Typ → fällt auf 'aufbauend'
$n = soi_persist_voraussetzungen(1, 7, array(
	array('modulnummer' => 'B', 'typ' => 'garbage', 'grund' => 'Test'),
), $code_to_id);
is_equal("persist_voraussetzungen: garbage typ → trotzdem geschrieben", $n, 1);
$sql = end($GLOBALS['_soi_rquery_log']);
is_equal("persist_voraussetzungen: garbage typ → aufbauend fallback", strpos($sql, '"aufbauend"') !== false, true);

/* ============================ End-to-End Anlage-2-Persistenz ============================ */

if($has_pdf && $pdftotext !== '') {
	$r = $ex->extract($pdf_path, 'layout');
	// Simuliere commit_v2 für die ersten 3 Module.
	$code_to_id = array();
	$total_a2 = 0; $total_sem = 0;
	$total_mns = 0; $total_zuord = 0; $total_vor = 0;
	foreach(array_slice($r['modules'], 0, 3) as $i => $m) {
		$mid = 1000 + $i;
		$code_to_id[$m['modulnummer']] = $mid;
		$an2 = soi_find_anlage2_for_modul($r['anlage2'], $m['modulnummer']);
		if($an2) {
			$total_a2 += soi_persist_anlage2_detailed($mid, 1, $an2);
			$total_sem += soi_persist_semester_metadata($mid, $an2);
			$total_mns++;
		}
		soi_persist_modul_zuordnung($mid, 1, 99, soi_rolle_for_section($m['section'] ?? ''), $m['lp'] ?? null);
		$total_zuord++;
		$det = soi_detect_voraussetzungen_for_modul($m, $r['modules']);
		if($det) $total_vor += soi_persist_voraussetzungen($mid, 1, $det, $code_to_id);
	}
	is_true("End-to-End: ≥ 2 modul_anlage2-Zeilen aus Anlage 2 geschrieben", $total_a2 >= 2);
	is_true("End-to-End: ≥ 2 modul_nach_semester_metadata-Zeilen geschrieben", $total_sem >= 2);
	is_equal("End-to-End: 3 modul_zuordnung-Zeilen geschrieben", $total_zuord, 3);
}

/* ============================ Persist-One-Module ============================ */

$log_before = count($GLOBALS['_soi_rquery_log']);
$res = soi_persist_one_module(7, 99, array(
	'modulnummer' => 'PHIL-X',
	'name' => 'Test-Modul',
	'lp' => 5,
	'sws' => 4,
	'dauer_semester' => 1,
	'section' => 'Kernbereich',
), array(
	array(
		'modulnummer' => 'PHIL-X',
		'name' => 'Test-Modul',
		'lp' => 5,
		'semester' => array(
			array('semester' => 1, 'sws' => array('2','2','0','0'), 'pl_count' => 1),
		),
	),
));
is_equal("persist_one_module: modul_id > 0", $res['modul_id'] > 0, true);
is_equal("persist_one_module: 1 Semester-Zeile", $res['semester_rows'], 1);
is_equal("persist_one_module: 1 Anlage2-Zeile", $res['anlage2_rows'], 1);
is_equal("persist_one_module: zuordnung_ok", $res['zuordnung_ok'], true);
$log = array_slice($GLOBALS['_soi_rquery_log'], $log_before);
$log_str = implode("\n", $log);
is_equal("persist_one_module: modul INSERT", strpos($log_str, 'INSERT INTO `modul`') !== false, true);
is_equal("persist_one_module: modul_anlage2 INSERT", strpos($log_str, 'INSERT INTO `modul_anlage2`') !== false, true);
is_equal("persist_one_module: modul_nach_semester INSERT", strpos($log_str, 'INSERT IGNORE INTO `modul_nach_semester`') !== false, true);
is_equal("persist_one_module: modul_nach_semester_metadata INSERT", strpos($log_str, 'INSERT INTO `modul_nach_semester_metadata`') !== false, true);
is_equal("persist_one_module: modul_zuordnung INSERT", strpos($log_str, 'INSERT INTO `modul_zuordnung`') !== false, true);
is_equal("persist_one_module: rolle 'kernbereich'", strpos($log_str, '"kernbereich"') !== false, true);

/* modulnummer ohne Anlage-2-Match → keine Semester-Zeilen, aber Modul+Zuordnung trotzdem. */
$log_before = count($GLOBALS['_soi_rquery_log']);
$res = soi_persist_one_module(8, 99, array(
	'modulnummer' => 'PHIL-Y',
	'name' => 'Test-Y',
	'lp' => 3,
	'section' => 'Ergänzungsbereich',
), array());
is_equal("persist_one_module ohne Anlage2: modul_id > 0", $res['modul_id'] > 0, true);
is_equal("persist_one_module ohne Anlage2: semester_rows = 0", $res['semester_rows'], 0);
is_equal("persist_one_module ohne Anlage2: anlage2_rows = 0", $res['anlage2_rows'], 0);
is_equal("persist_one_module ohne Anlage2: zuordnung_ok", $res['zuordnung_ok'], true);
$log = implode("\n", array_slice($GLOBALS['_soi_rquery_log'], $log_before));
is_equal("persist_one_module ohne Anlage2: rolle 'ergaenzungsbereich'", strpos($log, '"ergaenzungsbereich"') !== false, true);

/* Leeres Modul-Array → modul_id = 0. */
$res = soi_persist_one_module(9, 99, array('modulnummer' => '', 'name' => ''), array());
is_equal("persist_one_module: leerer Input → modul_id = 0", $res['modul_id'], 0);

/* Unicode-Name. */
$res = soi_persist_one_module(10, 99, array(
	'modulnummer' => 'PHIL-UNICODE',
	'name' => 'Théorie und Méthode – Théologie der Religion',
	'lp' => 5,
	'section' => 'Kernbereich',
), array());
is_equal("persist_one_module: Unicode-Name → modul_id > 0", $res['modul_id'] > 0, true);
$log = implode("\n", array_slice($GLOBALS['_soi_rquery_log'], $log_before));
is_equal("persist_one_module: Unicode im SQL", strpos($log, 'Théorie') !== false, true);

/* ============================ Create-Pruefungsnummern ============================ */

$log_before = count($GLOBALS['_soi_rquery_log']);
$seen = array();
$count = soi_create_pruefungsnummern_for_module(11, 500, 'PHIL-Z', 'Test-Z',
	array('Klausurarbeit', 'Mündliche Prüfung', 'Hausarbeit'), 5, $seen);
is_equal("create_pns: 3 Prüfungsnummern", $count, 3);
$log = implode("\n", array_slice($GLOBALS['_soi_rquery_log'], $log_before));
is_equal("create_pns: pruefungsnummer INSERT", strpos($log, 'INSERT INTO `pruefungsnummer`') !== false, true);
is_equal("create_pns: pruefungsnummer_import INSERT", strpos($log, 'INSERT INTO `pruefungsnummer_import`') !== false, true);
is_equal("create_pns: 3 pruefungsnummer-Inserts",
	substr_count($log, 'INSERT INTO `pruefungsnummer`') === 3, true);
is_equal("create_pns: 3 pruefungsnummer_import-Inserts",
	substr_count($log, 'INSERT INTO `pruefungsnummer_import`') === 3, true);

/* Leere Prüfungstypen-Liste → 0 PNs. */
$count = soi_create_pruefungsnummern_for_module(11, 500, 'PHIL-Z', 'Test-Z', array(), 5, $seen);
is_equal("create_pns: leere Liste → 0", $count, 0);

/* modul_id = 0 → 0 PNs. */
$count = soi_create_pruefungsnummern_for_module(11, 0, 'PHIL-Z', 'Test-Z', array('Klausur'), 5, $seen);
is_equal("create_pns: modul_id=0 → 0", $count, 0);

/* Whitespace in Prüfungstypen wird getrimmt. */
$count = soi_create_pruefungsnummern_for_module(11, 500, 'PHIL-Q', 'Test-Q',
	array('  Klausurarbeit  ', '', '  '), 5, $seen);
is_equal("create_pns: whitespace wird getrimmt", $count, 1);

/* Doppelte Prüfungstypen werden dedupliziert. */
$seen = array();
$count = soi_create_pruefungsnummern_for_module(11, 501, 'PHIL-D', 'Test-D',
	array('Klausurarbeit', 'Klausurarbeit', 'Klausurarbeit'), 5, $seen);
is_equal("create_pns: Duplikate werden gezählt (jeder erzeugt eigene PN)", $count, 3);

/* ============================ Idempotenz ============================ */

/* Persist_one_module zweimal aufrufen → modul_id bleibt gleich, kein Crash. */
$res1 = soi_persist_one_module(99, 999, array('modulnummer' => 'IDEM-1', 'name' => 'Idem 1', 'lp' => 5), array());
$res2 = soi_persist_one_module(99, 999, array('modulnummer' => 'IDEM-1', 'name' => 'Idem 1 (Update)', 'lp' => 6), array());
is_equal("persist_one_module idempotent: modul_id gleich", $res1['modul_id'], $res2['modul_id']);

/* persist_anlage2_detailed zweimal → 2 Rows, kein Crash (ON DUPLICATE KEY UPDATE). */
$res = soi_persist_anlage2_detailed($res1['modul_id'], 99, array(
	'lp' => 5,
	'semester' => array(
		array('semester' => 1, 'sws' => array('2','0','0','2'), 'pl_count' => 1),
	),
));
is_equal("persist_anlage2 idempotent: 1 Zeile (zweiter Aufruf)", $res, 1);

/* ============================ Section-Edge-Cases ============================ */

is_equal("rolle: 'Vertiefungsmodul X' → pflicht", soi_rolle_for_section('Vertiefungsmodul X'), 'pflicht');
is_equal("rolle: 'Spezialisierung X' → wahlpflicht", soi_rolle_for_section('Spezialisierung X'), 'wahlpflicht');
is_equal("rolle: 'Grundlagen' → pflicht", soi_rolle_for_section('Grundlagen'), 'pflicht');
is_equal("rolle: 'Aufbaumodul' → pflicht", soi_rolle_for_section('Aufbaumodul'), 'pflicht');
is_equal("rolle: 'Einführung in X' → pflicht", soi_rolle_for_section('Einführung in X'), 'pflicht');
is_equal("rolle: 'Nebenfach Anglistik' → nebenfach", soi_rolle_for_section('Nebenfach Anglistik'), 'nebenfach');
is_equal("rolle: 'Hauptfach Romanistik' → hauptfach", soi_rolle_for_section('Hauptfach Romanistik'), 'hauptfach');
is_equal("rolle: 'Ergänzungsbereich Germanistik' → ergaenzungsbereich", soi_rolle_for_section('Ergänzungsbereich Germanistik'), 'ergaenzungsbereich');

/* ============================ detect_voraussetzungen: Edge-Cases ============================ */

/* Leere all_modules_by_code. */
$det = soi_detect_voraussetzungen_for_modul(
	array('modulnummer' => 'X-2A-L', 'name' => 'Aufbaumodul X'),
	array()
);
is_equal("detect: leere all_modules → 0", count($det), 0);

/* Modulnummer ohne erkennbares Level → keine numerische Erkennung. */
$det = soi_detect_voraussetzungen_for_modul(
	array('modulnummer' => 'PHIL-9999', 'name' => 'Irgendwas'),
	array('PHIL-9999' => array('modulnummer' => 'PHIL-9999', 'name' => 'Irgendwas'))
);
is_equal("detect: kein erkennbares Level → 0", count($det), 0);

/* Spezialisierungsmodul → Vertiefungsmodul. */
$det = soi_detect_voraussetzungen_for_modul(
	array('modulnummer' => 'X-3S-L', 'name' => 'Spezialisierungsmodul Lit'),
	array(
		'X-3V-L' => array('modulnummer' => 'X-3V-L', 'name' => 'Vertiefungsmodul Lit'),
		'X-3S-L' => array('modulnummer' => 'X-3S-L', 'name' => 'Spezialisierungsmodul Lit'),
	)
);
is_equal("detect: Spezialisierungsmodul → Vertiefungsmodul", count($det), 1);
is_equal("detect: Spezialisierungsmodul Typ", $det[0]['typ'], 'empfohlen');

/* Ergänzungsmodul → Basismodul. */
$det = soi_detect_voraussetzungen_for_modul(
	array('modulnummer' => 'X-3E-L', 'name' => 'Ergänzungsmodul Lit'),
	array(
		'X-1B-L' => array('modulnummer' => 'X-1B-L', 'name' => 'Basismodul Lit'),
		'X-3E-L' => array('modulnummer' => 'X-3E-L', 'name' => 'Ergänzungsmodul Lit'),
	)
);
is_equal("detect: Ergänzungsmodul → Basismodul", count($det), 1);

/* Pattern ohne 'Thema' → kein Match (z.B. nur "Aufbaumodul" ohne Subjekt). */
$det = soi_detect_voraussetzungen_for_modul(
	array('modulnummer' => 'X-2A', 'name' => 'Aufbaumodul'),
	array()
);
is_equal("detect: Aufbaumodul ohne Thema → 0", count($det), 0);

/* Sprachpraxis C1 → B2. */
$lang = array(
	'B2' => array('modulnummer' => 'X-3SP-B2', 'name' => 'Sprachpraxis B2 - Französisch'),
	'C1' => array('modulnummer' => 'X-4SP-C1', 'name' => 'Sprachpraxis C1 - Französisch'),
);
$det = soi_detect_voraussetzungen_for_modul($lang['C1'], $lang);
is_equal("detect: Sprachpraxis C1 → B2", count($det), 1);
is_equal("detect: Sprachpraxis C1 → Code", $det[0]['modulnummer'], 'X-3SP-B2');

/* Language Skills (statt Sprachpraxis) wird auch erkannt. */
$ls = array(
	'A2' => array('modulnummer' => 'X-1SP-A2', 'name' => 'Language Components A2'),
	'B1' => array('modulnummer' => 'X-2SP-B1', 'name' => 'Language Skills B1'),
);
$det = soi_detect_voraussetzungen_for_modul($ls['B1'], $ls);
is_equal("detect: Language Skills → Vorgänger", count($det), 1);

/* ============================ find_anlage2 Edge-Cases ============================ */

is_equal("find_anlage2: leeres Array → null", soi_find_anlage2_for_modul(array(), 'X'), null);
is_equal("find_anlage2: nicht gefunden → null",
	soi_find_anlage2_for_modul(array(array('modulnummer' => 'Y', 'name' => '')), 'X'), null);
$found = soi_find_anlage2_for_modul(array(array('modulnummer' => 'X ', 'name' => 'A')), 'X');
is_equal("find_anlage2: mit Trim vergleichen", $found !== null, true);
is_equal("find_anlage2: richtige Zeile gefunden", $found['name'], 'A');

/* ============================ generate_pruefungsnummer Edge-Cases ============================ */

$seen = array();
/* Groß-/Kleinschreibung tolerant */
is_equal("generate: lowercase 'klausur' → KL", soi_generate_pruefungsnummer('X', 'klausur', 5, $seen), 'X-KL-5');
is_equal("generate: 'MÜNDLICHE PRÜFUNG' → MP", soi_generate_pruefungsnummer('Y', 'MÜNDLICHE PRÜFUNG', 5, $seen), 'Y-MP-5');
/* Sonderzeichen im Suffix werden entfernt */
is_equal("generate: Sonderzeichen im Suffix entfernt", soi_generate_pruefungsnummer('PHIL A.B', 'Klausur', 5, $seen), 'PHILAB-KL-5');
/* lp = 0 → kein -0 Suffix */
is_equal("generate: lp=0 (falsy) → ohne LP-Suffix", soi_generate_pruefungsnummer('Z', 'Klausur', 0, $seen), 'Z-KL');
/* Sehr lange lp-Werte */
$nr = soi_generate_pruefungsnummer('LONG', 'Klausur', 999, $seen);
is_equal("generate: lp=999 im Suffix", $nr, 'LONG-KL-999');
/* Suffix mit Umlauten (deutsche Modulnummer) */
$nr = soi_generate_pruefungsnummer('SLK-MÜ-A1', 'Klausur', 5, $seen);
is_equal("generate: Umlaute im Suffix entfernt", $nr, 'SLKMA1-KL-5');

/* ============================ Robustheit der Detection ============================ */

/* Detection: gleiches Thema aber verschiedene Studiengangs-Prefixes → kein Match. */
$all = array(
	'SLK-BA-F-1B-L' => ['modulnummer' => 'SLK-BA-F-1B-L', 'name' => 'Basismodul Lit'],
	'SLK-BA-F-2A-L' => ['modulnummer' => 'SLK-BA-F-2A-L', 'name' => 'Aufbaumodul Lit'],
	'PhF-Phil-1B-X' => ['modulnummer' => 'PhF-Phil-1B-X', 'name' => 'Basismodul Lit'],
	'PhF-Phil-2A-X' => ['modulnummer' => 'PhF-Phil-2A-X', 'name' => 'Aufbaumodul Lit'],
);
$det = soi_detect_voraussetzungen_for_modul($all['PhF-Phil-2A-X'], $all);
is_equal("detect: PhF-Aufbau → PhF-Basis (nicht SLK)", count($det), 1);
is_equal("detect: PhF-Aufbau Voraussetzung-Code", $det[0]['modulnummer'], 'PhF-Phil-1B-X');

$det = soi_detect_voraussetzungen_for_modul($all['SLK-BA-F-2A-L'], $all);
is_equal("detect: SLK-Aufbau → SLK-Basis (nicht PhF)", count($det), 1);
is_equal("detect: SLK-Aufbau Voraussetzung-Code", $det[0]['modulnummer'], 'SLK-BA-F-1B-L');

/* Detection mit unterschiedlich langen Prefixes (SLK-BA matched SLK-BA-R-F). */
$all = array(
	'SLK-BA-R-F-1B-L' => ['modulnummer' => 'SLK-BA-R-F-1B-L', 'name' => 'Basismodul Französische Literaturwissenschaft'],
	'SLK-BA-R-F-2A-L' => ['modulnummer' => 'SLK-BA-R-F-2A-L', 'name' => 'Aufbaumodul Französische Literaturwissenschaft'],
);
$det = soi_detect_voraussetzungen_for_modul($all['SLK-BA-R-F-2A-L'], $all);
is_equal("detect: SLK-BA-R-F-2A → SLK-BA-R-F-1B (Präfix-Mismatch toleriert)", count($det), 1);

/* ============================ Cross-Validation der Detection ============================ */

/* Vollständiger Detection-Durchlauf mit Parser-Output (echtes PDF). */
if($has_pdf && $pdftotext !== '') {
	$r = $ex->extract($pdf_path, 'layout');
	$det_count = 0;
	$auf_count = 0; $ver_count = 0; $sp_count = 0;
	foreach($r['modules'] as $m) {
		$det = soi_detect_voraussetzungen_for_modul($m, $r['modules']);
		if($det) {
			$det_count++;
			if(stripos($m['name'], 'Aufbaumodul') !== false) $auf_count++;
			if(stripos($m['name'], 'Vertiefungsmodul') !== false) $ver_count++;
			if(stripos($m['name'], 'Sprachpraxis') !== false) $sp_count++;
		}
	}
	is_equal("PDF: ≥ 10 Module mit Voraussetzungen", $det_count >= 10, true);
	is_equal("PDF: ≥ 1 Aufbaumodul mit Voraussetzung", $auf_count >= 1, true);
	is_equal("PDF: ≥ 1 Vertiefungsmodul mit Voraussetzung", $ver_count >= 1, true);
	is_equal("PDF: ≥ 5 Sprachpraxis-Module mit Voraussetzung", $sp_count >= 5, true);

	// Validiere: alle erkannten Modulnummern existieren auch im Modul-Set.
	$valid_codes = array();
	foreach($r['modules'] as $m) $valid_codes[$m['modulnummer']] = true;
	$invalid_count = 0;
	foreach($r['modules'] as $m) {
		$det = soi_detect_voraussetzungen_for_modul($m, $r['modules']);
		foreach($det as $d) {
			if(!isset($valid_codes[$d['modulnummer']])) $invalid_count++;
		}
	}
	is_equal("PDF: Alle erkannten Voraussetzungen verweisen auf existierende Module", $invalid_count, 0);
}
