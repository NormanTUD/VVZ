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

$simple_words = [
	['page'=>1,'x'=>70, 'y'=>100, 'w'=>50, 'h'=>15, 'text'=>'SLK-BA-1'],
	['page'=>1,'x'=>200,'y'=>100, 'w'=>50, 'h'=>15, 'text'=>'Modname'],
	['page'=>1,'x'=>350,'y'=>100, 'w'=>30, 'h'=>15, 'text'=>'2/0/0/2'],
	['page'=>1,'x'=>400,'y'=>100, 'w'=>30, 'h'=>15, 'text'=>'10'],
	['page'=>1,'x'=>460,'y'=>100, 'w'=>30, 'h'=>15, 'text'=>'SWS'],
];
$cols = $ex->detectColumns($simple_words);
is_equal("detectColumns: mehrere Spalten gefunden", count($cols) >= 4, true);

is_equal("detectColumns: leere Eingabe → leere Spalten", $ex->detectColumns([]), []);

/* ============================ groupRows() ============================== */

$rows = $ex->groupRows($simple_words);
is_equal("groupRows: alle Wörter auf einer Zeile (gleiches y)", count($rows), 1);
is_equal("groupRows: Zeile enthält 5 Wörter", count($rows[0]), 5);

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
