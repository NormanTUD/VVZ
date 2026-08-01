<?php
/*
 * Debug-Script für den Studienordnungs-Parser.
 *
 * Aufruf:
 *   php tests/debug_soi_parser.php [--pdf=<path>] [--filter=<prefix>] [--method=auto|bbox|layout|html|hybrid|table|xml] [--show-raw=N]
 *
 * Beispiele:
 *   php tests/debug_soi_parser.php
 *   php tests/debug_soi_parser.php --filter=PhF-Soz-AM4
 *   php tests/debug_soi_parser.php --method=layout --show-raw=3
 *
 * Ausgabe: für jedes Modul den Code, Name, Dozent, gefundene Werte,
 *          sowie die Roh-Layout-Zeilen, aus denen das Modul geparst wurde.
 *          Module mit verdächtig kurzem Namen oder fehlenden Daten werden
 *          mit "!!!" markiert.
 */

if(php_sapi_name() !== 'cli') {
	die("Nur CLI.\n");
}

/*
 * Wenn der Parser etwas falsch parst, ist das hier der schnellste Weg zum Debuggen.
 * Die Ausgabe enthält für jedes gefundene Modul:
 *  - Modulnummer, Name, Dozent, LP, SWS, Dauer, Prüfungstypen
 *  - Alle Felder (Qualifikationsziele, Inhalte, etc.)
 *  - Roh-Layout-Zeilen ±N um die gefundene Modulnummer herum
 *  - Markierung [OK] oder [!!!] je nachdem, ob das Modul verdächtig aussieht
 *
 * Beispiele:
 *   php tests/debug_soi_parser.php --filter=PhF-Soz-GM1-EB --show-raw=5
 *   php tests/debug_soi_parser.php --list-failures
 *   php tests/debug_soi_parser.php --method=auto
 *   php tests/debug_soi_parser.php --pdf=/pfad/zu/anderer.pdf
 */

require_once(__DIR__ . '/cli_guard.php');
require_once(__DIR__ . '/../pages/studienordnung_parser.php');

$opts = getopt('', ['pdf::', 'filter::', 'method::', 'show-raw::', 'list-failures']);
$pdf = $opts['pdf'] ?? (__DIR__ . '/fixtures/05_06soBAP18.09.2018.pdf');
$filter = $opts['filter'] ?? '';
$method = $opts['method'] ?? 'layout';
$show_raw = (int)($opts['show-raw'] ?? 2);
$list_failures = isset($opts['list-failures']);

if(!file_exists($pdf)) {
	fwrite(STDERR, "PDF nicht gefunden: $pdf\n");
	exit(1);
}

$ex = new SoiExtractor();
$r = $ex->extract($pdf, $method);

$pt = $ex->load($pdf, 'layout');
$boundaries = $ex->findAnlageBoundaries($pt);
$block = mb_substr($pt->full_text, $boundaries['anlage1_start'], $boundaries['anlage2_start'] - $boundaries['anlage1_start']);
$lines = preg_split('/\r\n|\r|\n/', $block);

function color($s, $c) {
	if(getenv('NO_COLOR') !== false) return $s;
	$colors = ['red'=>31, 'green'=>32, 'yellow'=>33, 'blue'=>34, 'magenta'=>35, 'cyan'=>36, 'gray'=>90, 'bold'=>1];
	$code = $colors[$c] ?? 0;
	return "\033[{$code}m{$s}\033[0m";
}

function is_suspicious($m) {
	$name = $m['name'] ?? '';
	$code = $m['modulnummer'] ?? '';
	$suspicious = false;
	$reasons = [];
	if(mb_strlen($name) < 5) { $suspicious = true; $reasons[] = 'name<5'; }
	if($name !== '' && substr($name, -1) === '-' || substr($name, -1) === ':' || substr($name, -2) === ' ' . mb_substr($name, -1)) {
		// endet mit Bindestrich → vermutlich abgeschnitten
		if(preg_match('/[-:]$/u', $name)) { $suspicious = true; $reasons[] = 'name-endet-mit-trenn'; }
	}
	if(empty($m['lp']) && !empty($code)) { $reasons[] = 'lp-fehlt'; $suspicious = true; }
	if(empty($m['sws_total']) && !empty($code)) { $reasons[] = 'sws-fehlt'; }
	if(empty($m['pruefungstypen']) && !empty($code)) { $reasons[] = 'pt-fehlt'; }
	return [$suspicious, $reasons];
}

function show_raw_lines($lines, $target, $context = 3) {
	$found = false;
	$matches = [];
	foreach($lines as $i => $l) {
		if(strpos($l, $target) !== false) {
			$matches[] = $i;
			$found = true;
		}
	}
	if(!$found) {
		echo color("    (Code nicht in Anlage-1-Block gefunden — vielleicht nur in Anlage 2 oder im TOC)", 'gray') . "\n";
		return;
	}
	foreach($matches as $i) {
		$from = max(0, $i - $context);
		$to = min(count($lines) - 1, $i + $context);
		echo color("    --- Zeilen " . ($from+1) . "-" . ($to+1) . " ---", 'gray') . "\n";
		for($j = $from; $j <= $to; $j++) {
			$marker = ($j === $i) ? '►' : ' ';
			$line_no = $j + 1;
			echo color("    {$marker} {$line_no}: ", 'gray') . $lines[$j] . "\n";
		}
	}
}

echo color("=== DEBUG SoiExtractor ===", 'bold') . "\n";
echo "PDF:           $pdf\n";
echo "Methode:       $method\n";
echo "Module:        " . count($r['modules']) . "\n";
echo "Anlage 2:      " . count($r['anlage2']) . "\n";
echo "Filter:        " . ($filter ?: '(alle)') . "\n";
echo "show-raw:      ±{$show_raw} Zeilen um den Code\n";
echo "\n";

if($list_failures) {
	echo color("=== VERDÄCHTIGE MODULE ===", 'bold') . "\n";
	foreach($r['modules'] as $m) {
		[$bad, $reasons] = is_suspicious($m);
		if(!$bad) continue;
		$code = $m['modulnummer'] ?? '';
		if($filter !== '' && strpos($code, $filter) !== 0) continue;
		echo color("[!!!] $code | name=\"" . mb_substr($m['name'] ?? '', 0, 60) . "\" | dozent=\"" . mb_substr($m['dozent'] ?? '', 0, 30) . "\"", 'red') . "\n";
		echo "       Reasons: " . implode(', ', $reasons) . "\n";
	}
	echo "\n";
}

if($filter !== '') {
	echo color("=== MODULE MIT FILTER '$filter' (Modulnummer oder Name) ===", 'bold') . "\n";
	foreach($r['modules'] as $m) {
		$code = $m['modulnummer'] ?? '';
		$name = $m['name'] ?? '';
		// Match against modulnummer prefix OR substring in name.
		if(strpos($code, $filter) !== 0 && stripos($name, $filter) === false) continue;
		[$bad, $reasons] = is_suspicious($m);
		$mark = $bad ? color('[!!!]', 'red') : color('[OK]', 'green');
		echo "$mark " . color($code, 'cyan') . " | " . color($m['name'] ?? '', 'yellow') . "\n";
		echo "    dozent:    " . ($m['dozent'] ?? '') . "\n";
		echo "    lp:        " . ($m['lp'] ?? '?') . "\n";
		echo "    sws_total: " . ($m['sws_total'] ?? '?') . "\n";
		echo "    dauer:     " . ($m['dauer_semester'] ?? '?') . "\n";
		echo "    pruefungstypen: " . implode(', ', $m['pruefungstypen'] ?? []) . "\n";
		echo "    section:   " . ($m['section'] ?? '?') . "\n";
		echo color("    Felder (Anlage 1):", 'gray') . "\n";
		foreach($m['fields'] ?? [] as $k => $v) {
			echo "      " . color($k, 'blue') . ": " . mb_substr($v, 0, 80) . (mb_strlen($v) > 80 ? "..." : "") . "\n";
		}
		echo "\n";
		show_raw_lines($lines, $code, $show_raw);
		echo "\n";
	}
} else {
	echo color("=== ALLE MODULE ===", 'bold') . "\n";
	foreach($r['modules'] as $m) {
		$code = $m['modulnummer'] ?? '';
		[$bad, $reasons] = is_suspicious($m);
		$mark = $bad ? color('[!!!]', 'red') : color('[OK]', 'green');
		echo "$mark " . color($code, 'cyan') . " | " . color(mb_substr($m['name'] ?? '', 0, 50), 'yellow');
		if($bad) echo color(" [" . implode(',', $reasons) . "]", 'red');
		echo "\n";
	}
}
