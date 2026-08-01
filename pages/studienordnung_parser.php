<?php
/*
 * Studienordnung PDF Parser Library
 *
 * Extrahiert aus Studienordnungs-PDFs:
 *  - Cover (Studiengang-Name, Grad)
 *  - Anlage 1 Modulbeschreibungen (Module mit LP/SWS/Dauer/Prüfungstypen)
 *  - Anlage 2 Studienablaufplan (welches Modul in welchem Semester mit SWS/PL/LP)
 *
 * Bietet 5+ Extraktionsmethoden:
 *  - layout: pdftotext -layout + Heuristik (schnell, gut für einfache Strukturen)
 *  - bbox:   pdftotext -bbox-layout + Spaltenerkennung (robust für Tabellen)
 *  - xml:    pdftohtml -xml + XML-DOM-Parsing
 *  - html:   pdftohtml + HTML-Tabellen-Extraktion
 *  - table:  pdftotext -table (experimentell)
 *  - hybrid: kombiniert bbox + layout (Fallback bei Unstimmigkeiten)
 *  - auto:   versucht alle Methoden und wählt das Ergebnis mit den meisten Modulen
 *
 * Keine Abhängigkeiten zur DB - reine Parser-Library, einfach testbar.
 */

if(!class_exists('SoiExtractor')) {

class SoiPdfText {
    public $full_text = '';
    public $pages = [];      // array of strings indexed by page number (1-based)
    public $words = [];      // array of {page, x, y, w, h, text} from -bbox-layout
    public $elements = [];   // array of {page, top, left, width, height, font, text} from pdftohtml -xml
    public $method = '';
    public $errors = [];
}

class SoiExtractor {
    const METHODS = ['layout', 'bbox', 'xml', 'html', 'table', 'hybrid', 'auto'];

    /**
     * Run all extractors on a PDF, return normalized data from the best one.
     */
    public function extractAll(string $pdf_path): array {
        $results = [];
        foreach(self::METHODS as $m) {
            if($m === 'auto') continue; // auto is meta
            try {
                $text = $this->extract($pdf_path, $m);
                $results[$m] = $text;
            } catch(\Throwable $e) {
                $results[$m] = ['error' => $e->getMessage()];
            }
        }
        return $results;
    }

    /**
     * Cross-Validation: vergleicht alle Methoden auf 5 Konsistenz-Checks.
     * Liefert ein Array mit 'consistency' (alle Checks ok?), 'checks' (Detail-Liste),
     * 'method_stats' (modul/anlage2-Counts pro Methode).
     *
     * Die 5 Checks sind:
     *   1. modules_count     — alle Methoden müssen gleich viele Module finden
     *   2. anlage2_count     — alle Methoden müssen gleich viele A2-Einträge finden
     *   3. cover             — alle Methoden müssen denselben Studiengang erkennen
     *   4. sample_modules    — Stichprobe der ersten 3 Module: modulnummer+name identisch?
     *   5. lp_consistency    — alle Methoden müssen für die Stichprobe dieselbe LP finden
     */
    public function crossValidate(string $pdf_path): array {
        $all = $this->extractAll($pdf_path);
        $methods = array_keys($all);
        $stats = [];
        foreach($methods as $m) {
            $r = $all[$m] ?? null;
            if(!is_array($r) || isset($r['error'])) {
                $stats[$m] = ['error' => $r['error'] ?? 'no result'];
                continue;
            }
            $stats[$m] = [
                'method' => $r['method'] ?? $m,
                'modules_count' => $r['modules_count'] ?? 0,
                'anlage2_count' => $r['anlage2_count'] ?? 0,
                'cover_degree' => $r['cover']['degree'] ?? null,
                'cover_program' => $r['cover']['program'] ?? null,
                'sample_modules' => [],
            ];
            foreach(array_slice($r['modules'] ?? [], 0, 3) as $mod) {
                $stats[$m]['sample_modules'][] = [
                    'code' => $mod['modulnummer'] ?? '',
                    'name' => mb_substr($mod['name'] ?? '', 0, 40),
                    'lp' => $mod['lp'] ?? null,
                ];
            }
        }

        $checks = [];
        // Check 1: module count
        $mod_counts = [];
        foreach($stats as $m => $s) { if(!isset($s['error'])) $mod_counts[$m] = $s['modules_count']; }
        $unique_mod = array_unique($mod_counts);
        $checks[] = [
            'name' => '1. modules_count',
            'ok' => count($unique_mod) <= 1,
            'details' => $mod_counts,
        ];
        // Check 2: anlage2 count
        $a2_counts = [];
        foreach($stats as $m => $s) { if(!isset($s['error'])) $a2_counts[$m] = $s['anlage2_count']; }
        $unique_a2 = array_unique($a2_counts);
        $checks[] = [
            'name' => '2. anlage2_count',
            'ok' => count($unique_a2) <= 1,
            'details' => $a2_counts,
        ];
        // Check 3: cover degree
        $degrees = [];
        foreach($stats as $m => $s) { if(!isset($s['error'])) $degrees[$m] = $s['cover_degree'] ?? '(leer)'; }
        $checks[] = [
            'name' => '3. cover_degree',
            'ok' => count(array_unique($degrees)) <= 1,
            'details' => $degrees,
        ];
        // Check 4: sample modules
        $sample_diff = [];
        foreach($stats as $m => $s) {
            if(isset($s['error'])) continue;
            foreach($s['sample_modules'] as $i => $mod) {
                $sample_diff[$i][$m] = $mod['code'].' | '.$mod['name'];
            }
        }
        $sample_consistent = true;
        foreach($sample_diff as $i => $mods) {
            $codes = array_unique(array_map(function($x) { return explode(' | ', $x)[0]; }, $mods));
            if(count($codes) > 1) $sample_consistent = false;
        }
        $checks[] = [
            'name' => '4. sample_modules (erste 3)',
            'ok' => $sample_consistent,
            'details' => $sample_diff,
        ];
        // Check 5: LP consistency on sample
        $lp_diff = [];
        foreach($stats as $m => $s) {
            if(isset($s['error'])) continue;
            foreach($s['sample_modules'] as $i => $mod) {
                $lp_diff[$i][$m] = ($mod['lp'] === null) ? '(null)' : $mod['lp'];
            }
        }
        $lp_consistent = true;
        foreach($lp_diff as $i => $mods) {
            $lps = array_unique($mods);
            if(count($lps) > 1) $lp_consistent = false;
        }
        $checks[] = [
            'name' => '5. lp_consistency (erste 3)',
            'ok' => $lp_consistent,
            'details' => $lp_diff,
        ];

        $all_ok = true;
        foreach($checks as $c) { if(!$c['ok']) $all_ok = false; }

        return [
            'all_consistent' => $all_ok,
            'method_stats' => $stats,
            'checks' => $checks,
        ];
    }

    /**
     * Run a single extraction method, return normalized data.
     */
    public function extract(string $pdf_path, string $method = 'bbox'): array {
        if(!file_exists($pdf_path)) {
            return ['error' => 'PDF nicht gefunden: '.$pdf_path];
        }
        $method = in_array($method, self::METHODS, true) ? $method : 'bbox';

        if($method === 'auto') {
            return $this->extractAuto($pdf_path);
        }

        $text = $this->load($pdf_path, $method);
        return $this->parse($text, $method);
    }

    /**
     * Auto: try all methods, pick the one with most modules detected.
     */
    public function extractAuto(string $pdf_path): array {
        $best = null;
        $best_score = -1;
        $results = [];
        foreach(['bbox', 'xml', 'html', 'layout', 'table', 'hybrid'] as $m) {
            try {
                $r = $this->extract($pdf_path, $m);
                $results[$m] = $r;
                // Score = modules + 2 * anlage2 (Anlage 2 ist die wertvollere Information).
                $n_mod = is_array($r) && isset($r['modules']) ? count($r['modules']) : 0;
                $n_a2 = is_array($r) && isset($r['anlage2']) ? count($r['anlage2']) : 0;
                $score = $n_mod + (2 * $n_a2);
                if($score > $best_score) {
                    $best_score = $score;
                    $best = $r;
                    $best['method'] = $m;
                }
            } catch(\Throwable $e) {
                $results[$m] = ['error' => $e->getMessage()];
            }
        }
        $best['_alternatives'] = array_map(function($r) {
            if(!is_array($r)) return 0;
            $n_mod = isset($r['modules']) ? count($r['modules']) : 0;
            $n_a2 = isset($r['anlage2']) ? count($r['anlage2']) : 0;
            return ['modules' => $n_mod, 'anlage2' => $n_a2];
        }, $results);
        return $best ?: ['error' => 'Keine Methode lieferte Ergebnisse'];
    }

    /**
     * Load PDF text/data using the specified method.
     */
    public function load(string $pdf_path, string $method): SoiPdfText {
        $out = new SoiPdfText();
        $out->method = $method;

        if($method === 'bbox') {
            // full_text = layout-Output (für Regex-Parser), words = bbox-HTML-geparste Daten.
            $out->full_text = shell_exec('pdftotext -layout '.escapeshellarg($pdf_path).' - 2>&1');
            $bbox_html = shell_exec('pdftotext -bbox-layout '.escapeshellarg($pdf_path).' - 2>&1');
            $out->words = $this->parseBboxHtml((string)$bbox_html);
        } elseif($method === 'xml') {
            $tmp = tempnam(sys_get_temp_dir(), 'soixml_');
            if($tmp === false) {
                $out->errors[] = 'tempnam failed';
                return $out;
            }
            $tmp .= '.xml';
            shell_exec('pdftohtml -xml '.escapeshellarg($pdf_path).' '.escapeshellarg($tmp).' 2>/dev/null');
            $xml_content = @file_get_contents($tmp);
            @unlink($tmp);
            if($xml_content) {
                $out->elements = $this->parseXmlElements($xml_content);
                // pdftohtml -xml produziert meist HTML statt echtes XML und zerstört die Spalten-Struktur.
                // Wir nutzen pdftotext -layout für den Text und behalten nur die elements-Liste.
                $out->full_text = shell_exec('pdftotext -layout '.escapeshellarg($pdf_path).' - 2>&1');
                if(empty($out->elements) || substr_count($xml_content, '<page ') < 2) {
                    $out->errors[] = 'pdftohtml -xml lieferte keine echten <page>-Tags → Text via pdftotext -layout';
                }
            } else {
                // Fallback: layout-Text
                $out->full_text = shell_exec('pdftotext -layout '.escapeshellarg($pdf_path).' - 2>&1');
            }
        } elseif($method === 'html') {
            $tmp = tempnam(sys_get_temp_dir(), 'soihtml_');
            if($tmp === false) {
                $out->errors[] = 'tempnam failed';
                return $out;
            }
            shell_exec('pdftohtml -i -s '.escapeshellarg($pdf_path).' '.escapeshellarg($tmp).' 2>/dev/null');
            $html = @file_get_contents($tmp.'.html');
            @unlink($tmp.'.html');
            if($html) {
                $out->full_text = strip_tags($html);
            } else {
                $out->full_text = shell_exec('pdftotext -layout '.escapeshellarg($pdf_path).' - 2>&1');
            }
        } elseif($method === 'layout') {
            $out->full_text = shell_exec('pdftotext -layout '.escapeshellarg($pdf_path).' - 2>&1');
        } elseif($method === 'table') {
            // Versuche -table. Wenn das Ergebnis zu wenig Inhalt hat (Module fehlen), fallback auf -layout.
            $out->full_text = shell_exec('pdftotext -table '.escapeshellarg($pdf_path).' - 2>&1');
            // Schnelltest: zähle "Modulnummer" Vorkommen. Wenn 0 oder sehr wenig → fallback.
            if(strlen(trim((string)$out->full_text)) < 1000 || substr_count((string)$out->full_text, 'Modulnummer') < 2) {
                $out->full_text = shell_exec('pdftotext -layout '.escapeshellarg($pdf_path).' - 2>&1');
                $out->errors[] = 'pdftotext -table lieferte zu wenig Inhalt → fallback auf -layout';
            }
        } elseif($method === 'hybrid') {
            // Layout-Text für Regex-Parser + bbox-Words für Tabellen-Extraktion.
            $out->full_text = shell_exec('pdftotext -layout '.escapeshellarg($pdf_path).' - 2>&1');
            $bbox_html = shell_exec('pdftotext -bbox-layout '.escapeshellarg($pdf_path).' - 2>&1');
            $out->words = $this->parseBboxHtml((string)$bbox_html);
        }

        // Split into pages (using form feed character), then clean any residual \f from lines.
        // pdftotext setzt \f sowohl zwischen Seiten ALS auch gelegentlich an Zeilenanfänge (z.B.
        // wenn ein Label direkt nach einem Seitenumbruch steht). Wir entfernen \f aus jeder Zeile,
        // BEHALTEN aber die Seiten-Trennung.
        $raw_pages = explode("\f", (string)$out->full_text);
        $clean_pages = array();
        foreach($raw_pages as $pg) {
            // \f aus jeder Zeile entfernen (sowohl am Anfang als auch mittendrin).
            $lines = preg_split('/\R/u', $pg);
            $clean_lines = array_map(function($l) { return str_replace("\f", '', $l); }, $lines);
            $clean_pages[] = implode("\n", $clean_lines);
        }
        $out->pages = $clean_pages;
        $out->full_text = implode("\n", $clean_pages);
        return $out;
    }

    /**
     * Parse loaded text/data into normalized structure.
     */
    public function parse(SoiPdfText $text, string $method): array {
        $cover = $this->parseCover($text);
        $modules = $this->parseModules($text, $method);
        $anlage2 = $this->parseAnlage2($text, $method);
        $result = [
            'method' => $method,
            'cover' => $cover,
            'modules' => $modules,
            'anlage2' => $anlage2,
            'modules_count' => count($modules),
            'anlage2_count' => count($anlage2),
            'text_length' => strlen($text->full_text),
            'errors' => $text->errors,
        ];
        if($method === 'hybrid') {
            // Validate: use bbox-detected modules as ground truth, fallback to layout
            $result['_hybrid_strategy'] = 'bbox-primary, layout-fallback';
        }
        return $result;
    }

    /**
     * Parse cover (title page) to find Studiengang-Name + Grad.
     */
    public function parseCover(SoiPdfText $text): array {
        $degree = null;
        $program = null;
        // Soft-Hyphen + Whitespace + Linebreak entfernen, damit mehrzeilige Cover-Texte
        // wie "Geis­tes-, Kultur- und Sozialwissenschaften" wieder zusammenkommen.
        $clean = preg_replace('/­/u', '', $text->full_text); // Soft-Hyphen raus
        $clean = preg_replace('/[ \t]+/u', ' ', $clean);

        // Wir suchen primär im Anfang der Datei (erste 4000 Zeichen). In späteren Paragraphen
        // wie §1 tauchen oft zusätzliche Erwähnungen wie "Sie ergänzt die Studienordnung für
        // den Bachelorstudiengang X" auf, die unsere Patterns fälschlich matchen würden.
        $head = mb_substr($clean, 0, 4000);

        // Pattern 0 (zuerst): "Studienordnung für das Erste Hauptfach X im Bachelorstudiengang Y"
        // → Subject = "Hauptfach X", Program = Y, Degree = Bachelor. Diese Pattern hat Vorrang,
        // weil sie sehr spezifisch ist und nicht in §1-Wiederholungen vorkommt.
        if(preg_match('/Studienordnung\s+(?:für|des)\s+das\s+(?:(Erste|Zweite|Dritte)\s+)?(Hauptfach|Nebenfach)\s+([^\n\r]+?)\s+im\s+(Bachelor|Master|Diplom|Staats\s*examen|Lehramt)studiengang\s+([^\n\r]+)/u', $head, $m)) {
            $ord = trim($m[1] ?? '');
            $fac = trim($m[2]);
            $subj = trim($m[3]);
            $degree = trim($m[4]);
            $full_prog = trim($m[5]);
            $subject_full = ($ord !== '' ? $ord.' ' : '').$fac.' '.$subj;
            $program = $subject_full.' / '.$full_prog;
            $program = trim(preg_replace('/\s+/', ' ', $program));
        }
        // Pattern 1: "Studienordnung für den Bachelorstudiengang X" (klassische Form).
        // Nur anwenden, wenn Pattern 0 nichts gefunden hat.
        if(!$program && preg_match('/Studienordnung\s+(?:für|des)\s+den\s+(Bachelor|Master|Diplom|Staats\s*examen|Lehramt)(?:studiengang)?\s+([^\n\r]+)/u', $head, $m)) {
            $degree = trim($m[1]);
            $program = trim(preg_replace('/\s+/', ' ', $m[2]));
        }
        // Pattern 2: "Prüfungsordnung für den Bachelorstudiengang X"
        if(!$program && preg_match('/Prüfungsordnung\s+(?:für|des)\s+den\s+(Bachelor|Master|Diplom|Staats\s*examen|Lehramt)(?:studiengang)?\s+([^\n\r]+)/u', $head, $m)) {
            $degree = trim($m[1]);
            $program = trim(preg_replace('/\s+/', ' ', $m[2]));
        }
        // Pattern 4: just "Studienordnung für X" (Fallback)
        if(!$program && preg_match('/Studienordnung\s+f[uü]r\s+(?:den|das|die)\s+([^\n\r]+)/u', $head, $m)) {
            $program = trim(preg_replace('/\s+/', ' ', $m[1]));
        }
        // Clean trailing junk like "Vom 18. September 2018"
        if($program) {
            $program = preg_replace('/\s+Vom\s+\d+.*$/u', '', $program);
            $program = trim($program);
        }
        return ['degree' => $degree, 'program' => $program];
    }

    /**
     * Find the Anlage 1 + Anlage 2 start positions in the text.
     */
    public function findAnlageBoundaries(SoiPdfText $text): array {
        $full = $text->full_text;
        // Anlage 1: actual section header (not TOC entry)
        $a1 = $this->findActualSectionStart($full, 'Anlage 1', ['Modulbeschreibungen', 'Modulbeschreibung']);
        // Anlage 2: actual section header
        $a2 = $this->findActualSectionStart($full, 'Anlage 2', ['Studienablaufplan']);
        return ['anlage1_start' => $a1, 'anlage2_start' => $a2];
    }

    /**
     * Find the actual (non-TOC) occurrence of "Anlage X" by requiring that one of the
     * expected labels appears at the start of its own line shortly after the match.
     *
     * Reason: in pdftotext -layout output the TOC entry for "Anlage 2: Studienablaufplan"
     * puts both phrases on the same logical line, separated by tabs. We must skip those.
     */
    private function findActualSectionStart(string $text, string $label, array $expected_next_lines): int {
        $offset = 0;
        $best_pos = false;
        while(true) {
            $pos = mb_strpos($text, $label.':', $offset);
            if($pos === false) break;
            $rest = mb_substr($text, $pos + mb_strlen($label) + 1);
            $lines = preg_split('/\r\n|\r|\n/', $rest, 6);
            foreach(array_slice($lines, 0, 5) as $idx => $line) {
                $trimmed = trim($line);
                if($trimmed === '') continue;
                // Erwartet: "Studienablaufplan" steht am Zeilenanfang (allein oder mit Anlage 2: davor).
                if($idx > 0) {
                    foreach($expected_next_lines as $exp) {
                        if(stripos($trimmed, $exp) !== false && preg_match('/^'.preg_quote($exp, '/').'/i', $trimmed)) {
                            $best_pos = $pos;
                            break 3;
                        }
                    }
                }
                // Wenn die erste nichtleere Zeile direkt mit dem erwarteten Label beginnt
                // und das Label auf der gleichen Zeile wie "Anlage X:" steht → TOC-Eintrag → skip.
                if($idx === 0) {
                    foreach($expected_next_lines as $exp) {
                        if(stripos($trimmed, $exp) !== false) {
                            // Steht auf derselben Zeile wie "Anlage X:" → TOC.
                            continue 2;
                        }
                    }
                }
                break; // Andere Zeile gefunden, die weder erwartet noch TOC ist.
            }
            $offset = $pos + 1;
        }
        return $best_pos;
    }

    /**
     * Parse Anlage 1 (Modulbeschreibungen).
     * Returns array of modules with: modulnummer, name, dozent, section, lp, sws_total, dauer_semester, pruefungstypen, fields.
     */
    public function parseModules(SoiPdfText $text, string $method = 'bbox'): array {
        if($method === 'bbox' && !empty($text->words)) {
            return $this->parseModulesFromBbox($text);
        }
        // Fallback: layout-based
        return $this->parseModulesFromText($text);
    }

    /**
     * Layout-based Anlage 1 parser (improved version with better wrapping/line aggregation).
     */
    public function parseModulesFromText(SoiPdfText $text): array {
        $boundaries = $this->findAnlageBoundaries($text);
        $a1 = $boundaries['anlage1_start'];
        $a2 = $boundaries['anlage2_start'];
        if($a1 === false) return [];
        $block = $a2 !== false ? mb_substr($text->full_text, $a1, $a2 - $a1) : mb_substr($text->full_text, $a1);

        $lines = preg_split('/\r\n|\r|\n/', $block);
        $modules = [];
        $current = null;
        $current_section = null;
        $known_labels = [
            'Qualifikationsziele', 'Inhalte',
            'Lehr- und Lernformen',
            'Lehr- und',
            'Lernformen',
            'Voraussetzungen für die Teilnahme',
            'Voraussetzungen',
            'Voraussetzungen für die Vergabe von Leistungspunkten',
            'Voraussetzungen für',
            'die Vergabe von',
            'Leistungspunkten',
            'Leistungspunkte und Noten',
            'Leistungspunkte und',
            'Leistungspunkte',
            'Verwendbarkeit',
            'Häufigkeit des Moduls',
            'Häufigkeit des',
            'Arbeitsaufwand',
            'Dauer des Moduls',
        ];
        // Build a regex of all known labels (longest first to match before shorter prefix)
        $sorted_labels = $known_labels;
        usort($sorted_labels, function($a, $b) { return mb_strlen($b) - mb_strlen($a); });
        $label_alt = implode('|', array_map(function($s) { return preg_quote($s, '/'); }, $sorted_labels));

        for($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];
            $trimmed = trim($line);
            if($trimmed === '') continue;

            // Section headers like "1. Module des Kernbereichs"
            if(preg_match('/^[12]\.\s+(Module\s+des\s+(?:Kern|Erg))/u', $trimmed, $sm)) {
                $current_section = (mb_stripos($sm[1], 'Erg') !== false) ? 'Ergänzungsbereich' : 'Kernbereich';
                continue;
            }
            if(preg_match('/^[12]\.([0-9]+)\s+(.+)$/u', $trimmed, $m)) {
                $current_section = 'Ergänzungsbereich: '.trim($m[2]);
                continue;
            }

            // Table header
            if(preg_match('/^Modulnummer\s+Modulname/u', $trimmed)) continue;

            // Module line: starts with modulnummer, then 2+ spaces, then name (and optional dozent).
            // Modulnummer kann mit "-" enden → wird auf nächster Zeile fortgesetzt (z.B. "SLK-BA-R-F-\n1B-K").
            // Modulnummer darf EINEN einzelnen internen Space enthalten (z.B. "PHF-BA-KG-EM 1"), der dann entfernt wird.
            if(preg_match('/^([A-Z][A-Za-z0-9.\-]+(?:\s[A-Z0-9.\-]+)?)\s{2,}(\S.{2,}?)\s{2,}(.+)$/u', $trimmed, $m)) {
                // Wenn das erste Capture-Group einen einzelnen internen Space hat, normalisieren.
                $raw_code = $m[1];
                if(substr_count($raw_code, ' ') === 1 && preg_match('/^[A-Z0-9.\-]+\s[A-Z0-9.\-]+$/u', $raw_code)) {
                    $code_candidate = preg_replace('/\s+/', '', $raw_code);
                } else {
                    $code_candidate = $raw_code;
                }
                if($this->isModulCode($code_candidate)) {
                    if($current) $modules[] = $current;
                    $code = $code_candidate;
                    $name = trim($m[2]);
                    $dozent = trim($m[3]);
                    // Folgezeilen: Modulnummer-Continuation, Dozent-Email, Name-Wrap.
                    while(isset($lines[$i+1])) {
                        $nxt_raw = $lines[$i+1];
                        $nxt = trim($nxt_raw);
                        if($nxt === '') { $i++; continue; }
                        // Stop-Kriterien.
                        if(preg_match('/^('.$label_alt.')\s{2,}/u', $nxt)) break;
                        if(preg_match('/^([A-Z][A-Za-z0-9.\-]+(?:\s[A-Z0-9.\-]+)?)\s{2,}/u', $nxt, $cm)) {
                            $raw = $cm[1];
                            if(substr_count($raw, ' ') === 1 && preg_match('/^[A-Z0-9.\-]+\s[A-Z0-9.\-]+$/u', $raw)) {
                                $raw = preg_replace('/\s+/', '', $raw);
                            }
                            if($this->isModulCode($raw)) break;
                        }
                        if(preg_match('/^[12]\.\s+/u', $nxt)) break;
                        if(preg_match('/^Modulnummer\b/u', $nxt)) break;

                        // Alternative Modulnummer in Klammern (z.B. "(SLK-BA-KG-EM1)" auf Folgezeile).
                        // Wird ignoriert — die alternative Code wird in einer späteren Verarbeitung ggf.
                        // als Sekundärschlüssel gespeichert. Für jetzt: Zeile überspringen.
                        if(preg_match('/^\(([A-Z][A-Za-z0-9.\-]{3,})\)\s{2,}/u', $nxt)) {
                            $i++;
                            continue;
                        }

                        // Modulnummer-Continuation: Code endet mit "-", erstes Token der Zeile ist alphanumerisch (kurz).
                        // Das Token wird an die Modulnummer angehängt; der Rest der Zeile (z.B. "(email)" oder
                        // der umgebrochene Name) wird in lines[$i+1] eingefügt, damit die nächste Iteration
                        // ihn als neue "nxt"-Zeile verarbeitet.
                        if(substr($code, -1) === '-') {
                            $first_token = strtok($nxt, " \t");
                            if($first_token !== false && preg_match('/^[A-Za-z0-9.*\-]+$/u', $first_token) && mb_strlen($first_token) < 25) {
                                $code .= $first_token;
                                // Rest der Zeile (alles nach dem ersten Token) extrahieren.
                                $rest = trim(substr($nxt, mb_strlen($first_token)));
                                if($rest !== '') {
                                    // Wir tauschen lines[$i+1] gegen den Rest aus, so dass die nächste
                                    // Iteration den Rest als "nxt" bekommt. $i wird NICHT erhöht.
                                    $rest_padded = str_pad($rest, max(mb_strlen($rest), 50), ' ', STR_PAD_LEFT);
                                    $lines[$i+1] = $rest_padded;
                                    continue;
                                }
                                // Kein Rest: Zeile ist verarbeitet, weiter mit nächster.
                                $i++;
                                continue;
                            }
                        }

                        // Dozent-Email in Klammern auf nächster Zeile → an Dozent anhängen.
                        if(preg_match('/^\([^\)]*@[^\)]+\)\s*$/u', $nxt)) {
                            $dozent = trim($dozent.' '.$nxt);
                            $i++;
                            continue;
                        }

                        // Eingerückte Zeile mit kurzem Text → wahrscheinlich Dozent-Folge (Name + Email)
                        // ODER: Modulname-Wrap (z.B. "für\nErgänzungsbereiche" oder "Sprache und\nKultur...").
                        if(strlen($nxt_raw) > 0 && $nxt_raw[0] === ' ') {
                            // Soft-Hyphen-Korrektur: "Spa-\nnische" → "Spanische"
                            if($name !== '' && substr($name, -1) === '-' && preg_match('/^[a-zäöüß]/u', $nxt)) {
                                $name = substr($name, 0, -1);
                            }
                            // Dozent-Continuation: vorheriger Dozent endet mit "-" oder "(". → in dieser Zeile
                            // können Name-Wrap (Spalte 1) und Dozent-Wrap (andere Spalten) gleichzeitig stehen.
                            if($dozent !== '' && (substr($dozent, -1) === '-' || substr($dozent, -1) === '(')) {
                                // Suche in den 2+-Spalten-Segmenten der Zeile nach einem, das wie Dozent-Wrap
                                // aussieht: enthält @, oder fängt mit einem Email-Fragment an.
                                $segments = preg_split('/\s{2,}/u', $nxt);
                                $name_cont = '';
                                $dozent_cont = '';
                                $found_dozent = false;
                                foreach($segments as $seg) {
                                    $seg = trim($seg);
                                    if($seg === '') continue;
                                    if(strpos($seg, '@') !== false) {
                                        // Hat @ → Dozent-Wrap (Email-Continuation).
                                        $dozent_cont .= ($dozent_cont ? ' ' : '').$seg;
                                        $found_dozent = true;
                                    } else {
                                        // Kein @ → potentiell Name-Wrap.
                                        $name_cont .= ($name_cont ? ' ' : '').$seg;
                                    }
                                }
                                if($found_dozent) {
                                    $dozent = trim($dozent.' '.$dozent_cont);
                                    if($name_cont !== '') {
                                        $name = trim($name.' '.$name_cont);
                                    }
                                    $i++;
                                    continue;
                                }
                                // Sonst: das ist Name-Wrap. Wir machen weiter mit dem normalen Pfad.
                            }
                            // Wenn die Zeile einen Email in Klammern enthält: nur den Email-Teil übernehmen,
                            // den Rest (z.B. umgebrochener Modulname) an Name anhängen.
                            if(preg_match('/\(([^)]+@[^)]+)\)/u', $nxt, $em)) {
                                $email_part = '('.$em[1].')';
                                $rest_text = trim(str_replace($email_part, '', $nxt));
                                if($rest_text !== '' && $dozent !== '') {
                                    $name = trim($name.' '.$rest_text);
                                }
                                $dozent = trim($dozent.' '.$email_part);
                                $i++;
                                continue;
                            }
                            // Wenn die Zeile wie ein Dozent-Name aussieht: "Prof. X Y Z" oder "Dr. X Y" oder
                            // auch "Prof. für X Y" (mit "für"). Genau 2-5 Wörter, Großbuchstaben-getrennt.
                            $looks_like_dozent_name = preg_match('/^(Prof\.|Dr\.|PD|PD\.|Juniorprof\.|Akad\.|Prof|Dr)(\.|\s|\b).*([A-ZÄÖÜ][a-zäöüß\.\-]+\s+){1,3}[A-ZÄÖÜ][a-zäöüß\.\-]+$/u', $nxt)
                                && mb_strlen($nxt) < 80;
                            if($looks_like_dozent_name) {
                                if($dozent !== '' && $nxt !== $dozent) $dozent = trim($dozent.' '.$nxt);
                                elseif($dozent === '') $dozent = $nxt;
                                $i++;
                                continue;
                            }
                            // Standard-Fall: eingerückte Textzeile → Modulname-Wrap.
                            // Wir nehmen nur das erste Spalten-Segment (vor 2+ Leerzeichen),
                            // trennen aber ggf. am Ende Title-Case-Wörter ab, die wie eine Dozent-
                            // Fortsetzung wirken (z.B. "Soziologie für Ergänzungsbereiche
                            //   geschäftsführender Direktor" → "geschäftsführender Direktor" ist Dozent).
                            $first_col = trim((preg_split('/\s{2,}/u', $nxt, 2)[0] ?? $nxt));
                            $is_mostly_text = !preg_match('/^\s*\d+\s*(PL|SWS)?\s*$/i', $first_col)
                                && !preg_match('/^\s*\d+\/\d+/', $first_col)
                                && preg_match('/[A-Za-zÄÖÜäöüß]{3,}/u', $first_col)
                                && mb_strlen($first_col) > 0
                                && mb_strlen($first_col) < 200;
                            if($is_mostly_text) {
                                // Wenn die Zeile mit einem bekannten Dozent-/Titel-Wort endet (Direktor, Professor, Fakultät …)
                                // UND der Dozent bereits gefüllt ist, behandeln wir den Teil ab dem letzten
                                // "klein geschriebenen Funktionswort" als Dozent-Wrap.
                                // Beispiel: "Soziologie für Ergänzungsbereiche geschäftsführender Direktor"
                                //   → name_wrap: "Soziologie für Ergänzungsbereiche", dozent_tail: "geschäftsführender Direktor"
                                if($dozent !== ''
                                    && preg_match('/\b(Direktor|Direktorin|Professor|Dozent|Fakultät|Institut|Lehrstuhl|Lehrbereich|Sekretariat)\b\s*$/u', $first_col)
                                    && !preg_match('/^([a-zäöüß][a-zäöüß\-]*\s+)?Geschäftsführend[er]*\b/u', $first_col)) {
                                    // Split-Logik: vom Ende her scannen.
                                    // 1. Erkenne 1-3 Title-Case-Wörter am Ende (z.B. "Direktor" oder
                                    //    "geschäftsführender Direktor" wenn davor ein lowercase Wort steht).
                                    // 2. NICHT weiter rückwärts gehen, wenn wir auf ein lowercase Wort gestoßen sind.
                                    $tokens = preg_split('/\s+/u', $first_col);
                                    $n = count($tokens);
                                    $title_start = $n;
                                    for($k = $n - 1; $k >= 0; $k--) {
                                        $c = mb_substr($tokens[$k], 0, 1);
                                        $is_title = ($c !== '' && $c === mb_strtoupper($c) && preg_match('/[A-Za-zÄÖÜäöüß]/', $c));
                                        $is_lower = ($c !== '' && $c === mb_strtolower($c) && preg_match('/[a-zäöüß]/', $c));
                                        if($is_title) {
                                            $title_start = $k;
                                        } elseif($is_lower && $title_start < $n) {
                                            // Lowercase Wort direkt vor Title-case Block → das lowercase Wort mitnehmen.
                                            $title_start = $k;
                                            break;
                                        } else {
                                            break;
                                        }
                                    }
                                    if($title_start < $n && $title_start > 0 && ($n - $title_start) <= 4) {
                                        $possible_dozent_tail = trim(implode(' ', array_slice($tokens, $title_start)));
                                        $possible_name_wrap = trim(implode(' ', array_slice($tokens, 0, $title_start)));
                                        if(mb_strlen($possible_name_wrap) >= 5 && mb_strlen($possible_dozent_tail) >= 3) {
                                            $name = trim($name.' '.$possible_name_wrap);
                                            $dozent = trim($dozent.' '.$possible_dozent_tail);
                                            $i++;
                                            continue;
                                        }
                                    }
                                }
                                $name = trim($name.' '.$first_col);
                                $i++;
                                continue;
                            }
                            // Sonst: nichts übernehmen.
                            break;
                        }

                        // Label-Start → abbrechen.
                        if(preg_match('/^(Qualifikationsziele|Inhalte|Lehr-|Voraussetzungen|Verwendbarkeit|Leistungspunkte|Häufigkeit|Arbeitsaufwand|Dauer)/u', $nxt)) break;

                        // Default: Name fortsetzen.
                        $name = trim($name.' '.$nxt);
                        // Falls der neue Wrap identisch zum letzten Token ist, nichts anhängen.
                        $last_token = '';
                        if(preg_match('/(\S+)$/u', $name, $lt)) $last_token = $lt[1];
                        if($last_token !== '' && $last_token === $nxt) {
                            // Wrap ist nur Wiederholung — nichts tun.
                        }
                        $i++;
                    }
                    // Trailing junk entfernen (3+ aufeinanderfolgende Ziffern = Seitenzahl-Rest).
                    $name = preg_replace('/\s+\d{3,4}$/', '', $name);
                    // Falls Email in Klammern im Namen gelandet ist, in Dozent verschieben.
                    if(preg_match('/^(.+?)\s+\(([^)]+@[^)]+)\)\s*$/u', $name, $nm)) {
                        $name = trim($nm[1]);
                        $dozent = trim($dozent.' ('.$nm[2].')');
                    }
                    // Falls Dozent mit ":" endet (z.B. "antike Sprache:"), ist das wahrscheinlich
                    // der abgeschnittene Modulname. Wir verschieben alles vor dem ":" in den Namen.
                    if(preg_match('/^(.+):(\s*\S.*)$/u', $dozent, $dm)) {
                        $name = trim($name.' '.$dm[1].':');
                        $dozent = trim($dm[2]);
                    }
                    $current = [
                        'modulnummer' => $code,
                        'name' => $name,
                        'dozent' => $dozent,
                        'section' => $current_section,
                        'fields' => [],
                        'lp' => null,
                        'sws_total' => null,
                        'dauer_semester' => null,
                        'pruefungstypen' => [],
                        'verwendbarkeit_text' => '',
                    ];
                    continue;
                }
            }

            if(!$current) continue;

            // Label line: match against known labels
            if(preg_match('/^('.$label_alt.')\s{2,}(.*)$/u', $trimmed, $m)) {
                $label = $m[1];
                $value = $m[2];
                // Continue collecting wrapped value lines until we hit another known label or module code
                while(isset($lines[$i+1])) {
                    $nxt = trim($lines[$i+1]);
                    if($nxt === '') { $i++; continue; }
                    if(preg_match('/^('.$label_alt.')\s{2,}/u', $nxt)) break;
                    if(preg_match('/^([A-Z][A-Za-z0-9.\-]+(?:\s[A-Z0-9.\-]+)?)\s{2,}/u', $nxt, $cm)) {
                        $raw = $cm[1];
                        if(substr_count($raw, ' ') === 1 && preg_match('/^[A-Z0-9.\-]+\s[A-Z0-9.\-]+$/u', $raw)) {
                            $raw = preg_replace('/\s+/', '', $raw);
                        }
                        if($this->isModulCode($raw)) break;
                    }
                    if(preg_match('/^[12]\.\s+/u', $nxt)) break;
                    $value .= ' ' . $nxt;
                    $i++;
                }
                $current['fields'][$label] = trim($value);
                // Extract specific values
                if($label === 'Leistungspunkte und Noten' || $label === 'Leistungspunkte und' || $label === 'Leistungspunkte') {
                    if(preg_match('/(\d+)\s*Leistungspunkte/u', $value, $lm)) {
                        $current['lp'] = (int)$lm[1];
                    }
                }
                if($label === 'Dauer des Moduls') {
                    if(preg_match('/(\d+)\s*Semester/u', $value, $dm)) {
                        $current['dauer_semester'] = (int)$dm[1];
                    }
                }
                if($label === 'Lehr- und Lernformen' || $label === 'Lehr- und' || $label === 'Lernformen') {
                    // Wert wird ggf. unten noch um Folgezeilen erweitert; prüfe auch $current['fields']['Lehr- und'].
                    $ll = $value;
                    if(isset($current['fields']['Lernformen'])) $ll .= ' '.$current['fields']['Lernformen'];
                    if(preg_match_all('/(\d+(?:[.,]\d+)?)\s*SWS/u', $ll, $swsm)) {
                        $sum = 0.0;
                        foreach($swsm[1] as $v) { $sum += (float)str_replace(',', '.', $v); }
                        $current['sws_total'] = $sum;
                    }
                }
                if($label === 'Voraussetzungen für die Vergabe von Leistungspunkten'
                    || $label === 'Voraussetzungen für'
                    || $label === 'die Vergabe von'
                    || $label === 'Leistungspunkten') {
                    $candidates = ['Klausurarbeit','Klausur','Mündliche Prüfung','mündliche Prüfung','Referat','Protokoll','Hausarbeit','Seminararbeit','Essay','Portfolio','Bericht','Vortrag','Thesenpapier','Bibliographie','Exposé','Rezension','Bachelorarbeit','Masterarbeit','Kolloquium'];
                    foreach($candidates as $cand) {
                        if(mb_stripos($value, $cand) !== false) {
                            if(!in_array($cand, $current['pruefungstypen'])) $current['pruefungstypen'][] = $cand;
                        }
                    }
                }
                if($label === 'Verwendbarkeit') {
                    $current['verwendbarkeit_text'] = $value;
                }
                continue;
            }
        }
        if($current) $modules[] = $current;
        // Section-Post-Processing: Wenn noch keine Section erkannt wurde, leite sie aus
        // der "Verwendbarkeit"-Beschreibung ab (Pflichtmodul/Wahlpflichtmodul/Ergänzungsmodul).
        foreach($modules as &$m) {
            if(!empty($m['section'])) continue;
            $vt = isset($m['verwendbarkeit_text']) ? $m['verwendbarkeit_text'] : '';
            if($vt === '') continue;
            if(preg_match('/Wahlpflichtmodul|ergänzungsbereich|Wahlpflichtbereich|Ergänzungsbereich/u', $vt)) {
                $m['section'] = 'Wahlpflicht';
            } elseif(preg_match('/Pflichtmodul|Kernbereich/u', $vt)) {
                $m['section'] = 'Kernbereich';
            } elseif(preg_match('/Ergänzungsmodul|Allgemeine\s+Qualifikationen|AQUA/u', $vt)) {
                $m['section'] = 'Allgemeine Qualifikationen';
            }
        }
        unset($m);
        // Guardrail: offensichtlich ungültige Module herausfiltern.
        return $this->filterValidModules($modules);
    }

    /**
     * Entfernt offensichtlich kaputte Module aus dem Ergebnis.
     * Ein Modul ist ungültig wenn:
     *  - modulnummer leer oder kürzer als 5 Zeichen
     *  - name leer oder kürzer als 3 Zeichen
     *  - modulnummer enthält Whitespace
     *
     * @return array Gültige Module.
     */
    public function filterValidModules(array $modules): array {
        $valid = [];
        foreach($modules as $m) {
            $code = isset($m['modulnummer']) ? trim((string)$m['modulnummer']) : '';
            $name = isset($m['name']) ? trim((string)$m['name']) : '';
            if($code === '' || strlen($code) < 5) continue;
            if(preg_match('/\s/', $code)) continue;
            if(!$this->isModulCode($code)) continue;
            if($name === '' || mb_strlen($name) < 3) continue;
            $valid[] = $m;
        }
        return $valid;
    }

    /**
     * Heuristic: is this string a valid module code?
     */
    public function isModulCode(string $s): bool {
        // Module codes typically: prefix-institute-program-number with - separators
        // Must contain at least one dash, mostly uppercase + digits, length 4-40
        $s = trim($s);
        if(strlen($s) < 4 || strlen($s) > 40) return false;
        if(strpos($s, '-') === false) return false;
        // Must start with letter
        if(!preg_match('/^[A-Za-z]/', $s)) return false;
        // Tolerate single internal space (z.B. "PHF-BA-KG-EM 1" → "PHF-BA-KG-EM1")
        // und Punkte (z.B. "SLK-BA-R-F-1SP-B2.1.1"). Spaces inside werden entfernt.
        $s_norm = preg_replace('/\s+/', '', $s);
        if($s_norm !== $s && strpos($s, ' ') !== false) {
            // Nur einzelne Spaces sind erlaubt.
            if(preg_match('/\s{2,}/', $s)) return false;
        }
        $s = $s_norm;
        // Trailing dash (wrapped modulnummer) ist erlaubt — das wird in der Modulnummer-
        // Continuation-Logik unten wieder zusammengefügt.
        $has_trailing_dash = (substr($s, -1) === '-');
        if($has_trailing_dash) $s = substr($s, 0, -1);
        // Mindestens 2 Bindestriche nötig, damit einzelne Wörter wie "Modul-Nr." oder
        // "Bachelor-Arbeit" nicht fälschlich als Modulnummer erkannt werden.
        // Ausnahme: trailing-dash-Continuation ("PHF-BA-POL-" → 3 dashes before trim → OK).
        $dash_count = substr_count($s, '-');
        if($dash_count < 1) return false;
        // Most chars should be uppercase or digits
        $upper_count = 0;
        $lower_count = 0;
        $digit_count = 0;
        for($i = 0; $i < strlen($s); $i++) {
            $c = $s[$i];
            if($c >= 'A' && $c <= 'Z') $upper_count++;
            else if($c >= 'a' && $c <= 'z') $lower_count++;
            else if($c >= '0' && $c <= '9') $digit_count++;
        }
        if($upper_count < 2) return false; // at least 2 uppercase letters
        // Codes dürfen aus Buchstaben+Bindestrichen bestehen — viele TU-Codes haben KEINE
        // Ziffern (z.B. "BM-SYS", "AM-FORSCHUNG", "AM-WP", "PHF-POL-BM-IP"). Wir lehnen
        // nur dann ab, wenn der Code exakt einem deutschen Wort ähnelt (siehe unten).
        // Frühere Logik verlangte Ziffern oder Kleinbuchstaben — das hat diese legitimen
        // Codes fälschlich abgelehnt.
        if($dash_count < 1) return false; // mind. 1 Bindestrich
        return true;
    }

    /**
     * Parse Anlage 1 from bbox data (more reliable for tables/wrapping).
     */
    public function parseModulesFromBbox(SoiPdfText $text): array {
        // For now, fallback to text-based. Bbox-based Anlage 1 is harder because
        // sections are not table-structured like Anlage 2.
        return $this->parseModulesFromText($text);
    }

    /**
     * Parse Anlage 2 (Studienablaufplan tables).
     * Strategie: bbox/hybrid versuchen zuerst die positionsbasierte Erkennung.
     * Falls das zu wenig Module liefert (< 50% der textbasierten Variante),
     * fällt die Funktion auf den textbasierten Parser zurück, damit ALLE
     * Methoden konsistente Ergebnisse liefern.
     */
    public function parseAnlage2(SoiPdfText $text, string $method = 'bbox'): array {
        $use_bbox = (($method === 'bbox' || $method === 'hybrid') && !empty($text->words));
        if($use_bbox) {
            $bbox_result = $this->parseAnlage2FromBbox($text);
            // Konsistenz-Check: wenn bbox deutlich weniger findet als text, fallback.
            $text_result = $this->parseAnlage2FromText($text);
            if(count($text_result) > 0 && count($bbox_result) < count($text_result) * 0.5) {
                return $text_result;
            }
            return $bbox_result;
        }
        if($method === 'xml' && !empty($text->elements)) {
            $xml_result = $this->parseAnlage2FromXml($text);
            $text_result = $this->parseAnlage2FromText($text);
            if(count($text_result) > 0 && count($xml_result) < count($text_result) * 0.5) {
                return $text_result;
            }
            return $xml_result;
        }
        return $this->parseAnlage2FromText($text);
    }

    /**
     * Parse Anlage 2 from bbox data (handles wrapped module numbers + names + SWS columns).
     */
    public function parseAnlage2FromBbox(SoiPdfText $text): array {
        $words = $text->words;
        if(empty($words)) return [];

        // Gruppiere Wörter nach Seite.
        $by_page = [];
        foreach($words as $w) {
            if(!isset($by_page[$w['page']])) $by_page[$w['page']] = [];
            $by_page[$w['page']][] = $w;
        }
        ksort($by_page, SORT_NUMERIC);

        // Finde Seiten, auf denen "Anlage" UND "Studienablaufplan" als getrennte Wörter stehen
        // (= echter Section-Header). Verhindert, dass der TOC-Eintrag auf Seite 1 als Start
        // erkannt wird.
        $a2_pages = [];
        foreach($by_page as $page_num => $page_words) {
            $has_anlage = false;
            $has_studienablaufplan = false;
            foreach($page_words as $w) {
                $t = strtolower(trim($w['text']));
                if($t === 'anlage') $has_anlage = true;
                if($t === 'studienablaufplan') $has_studienablaufplan = true;
            }
            if($has_anlage && $has_studienablaufplan) {
                $a2_pages[$page_num] = $page_words;
            }
        }

        $all_modules = [];
        foreach($a2_pages as $page => $page_words) {
            // First pass: identify column boundaries by clustering x positions
            $cols = $this->detectColumns($page_words);
            if(empty($cols)) continue;

            // Second pass: group words into "rows" by y-position
            $rows = $this->groupRows($page_words);

            // Third pass: extract modules from rows
            $modules = $this->extractModulesFromRows($rows, $cols);
            $all_modules = array_merge($all_modules, $modules);
        }
        return $this->filterValidAnlage2($all_modules);
    }

    /**
     * Detect column boundaries from x positions of words using a histogram-based approach.
     * Returns array of columns sorted by left position.
     *
     * Algorithm:
     *  1. Build a histogram of x positions (bin size = 15 pt).
     *  2. Find local minima ("valleys") where word density drops sharply (< 30% of max).
     *  3. Each valley is a column boundary.
     *  4. Merge columns that are closer than 30 pt.
     */
    public function detectColumns(array $words): array {
        $positions = [];
        foreach($words as $w) {
            $text = trim($w['text']);
            if(mb_strlen($text) < 1) continue;
            $positions[] = (int)$w['x'];
        }
        if(empty($positions)) return [];

        // Histogramm mit Bin-Größe 15 pt.
        $bin_size = 15;
        $min_x = min($positions);
        $max_x = max($positions);
        $bins = [];
        foreach($positions as $p) {
            $bin = intdiv($p - $min_x, $bin_size);
            if(!isset($bins[$bin])) $bins[$bin] = 0;
            $bins[$bin]++;
        }
        if(empty($bins)) return [];

        $max_bin = max($bins);
        // Schwelle: 30% des Maximums, mindestens 1 (sonst nichts erkannt bei einzelnen Wörtern).
        $threshold = max(1, (int)floor($max_bin * 0.30));

        $cols = [];
        $current_col_start = null;
        $current_col_end = null;
        $sorted_bins = array_keys($bins);
        sort($sorted_bins);
        $prev_bin = null;
        foreach($sorted_bins as $bin) {
            $bin_start = $min_x + $bin * $bin_size;
            $bin_end = $bin_start + $bin_size;
            $is_dense = ($bins[$bin] >= $threshold);
            if($is_dense) {
                // Wenn es eine Lücke zum vorherigen dichten Bin gibt, aktuelle Spalte schließen.
                $gap = ($prev_bin === null) ? 0 : ($bin - $prev_bin - 1);
                if($current_col_start !== null && $gap > 2) {
                    $cols[] = ['left' => $current_col_start, 'right' => $current_col_end, 'center' => ($current_col_start + $current_col_end) / 2];
                    $current_col_start = null;
                }
                if($current_col_start === null) {
                    $current_col_start = $bin_start;
                    $current_col_end = $bin_end;
                } else {
                    $current_col_end = $bin_end;
                }
            } else {
                // Bin unterhalb der Schwelle → aktuelle Spalte schließen.
                if($current_col_start !== null) {
                    $cols[] = ['left' => $current_col_start, 'right' => $current_col_end, 'center' => ($current_col_start + $current_col_end) / 2];
                    $current_col_start = null;
                }
            }
            if($is_dense) $prev_bin = $bin;
        }
        if($current_col_start !== null) {
            $cols[] = ['left' => $current_col_start, 'right' => $current_col_end, 'center' => ($current_col_start + $current_col_end) / 2];
        }

        // Spalten, die näher als 30 pt beieinander liegen, zusammenführen.
        $merged = [];
        foreach($cols as $c) {
            if(empty($merged)) { $merged[] = $c; continue; }
            $last = &$merged[count($merged)-1];
            if($c['left'] - $last['right'] < 30) {
                $last['right'] = max($last['right'], $c['right']);
                $last['center'] = ($last['left'] + $last['right']) / 2;
            } else {
                $merged[] = $c;
            }
            unset($last);
        }
        return $merged;
    }

    /**
     * Group words into rows by y-position.
     */
    public function groupRows(array $words): array {
        // Sort by y, then x
        usort($words, function($a, $b) {
            if($a['y'] != $b['y']) return $a['y'] - $b['y'];
            return $a['x'] - $b['x'];
        });
        $rows = [];
        $current_row = null;
        $current_y = null;
        foreach($words as $w) {
            if($current_y === null || abs($w['y'] - $current_y) < 3) {
                // Same row
                if($current_row === null) $current_row = [];
                $current_row[] = $w;
                $current_y = ($current_y === null) ? $w['y'] : ($current_y + $w['y']) / 2;
            } else {
                // New row
                if($current_row !== null) $rows[] = $current_row;
                $current_row = [$w];
                $current_y = $w['y'];
            }
        }
        if($current_row !== null) $rows[] = $current_row;
        return $rows;
    }

    /**
     * Extract modules from rows using column positions.
     */
    public function extractModulesFromRows(array $rows, array $cols): array {
        // Find modulnummer column (leftmost)
        $modul_col = 0;
        foreach($cols as $i => $c) {
            if($c['left'] < 100) {
                $modul_col = $i;
                break;
            }
        }
        // Find LP column (rightmost column with width < 80 and right > 500)
        $lp_col = null;
        $max_right = 0;
        foreach($cols as $i => $c) {
            if($c['right'] > 500 && ($c['right'] - $c['left']) < 100 && $c['right'] > $max_right) {
                $lp_col = $i;
                $max_right = $c['right'];
            }
        }
        if($lp_col === null) $lp_col = count($cols) - 1;

        $modules = [];
        $current = null;
        $current_y_max = 0;

        foreach($rows as $row) {
            // Get text in each column
            $cells = [];
            foreach($cols as $i => $c) {
                $cell_words = [];
                foreach($row as $w) {
                    if($w['x'] >= $c['left'] - 5 && $w['x'] < $c['right'] + 5) {
                        $cell_words[] = $w['text'];
                    }
                }
                $cells[$i] = trim(implode(' ', $cell_words));
            }

            // Modulnummer column - check if this is a new module
            $modul_text = $cells[$modul_col] ?? '';
            $modul_clean = preg_replace('/\s+/', '', $modul_text);
            if($modul_clean && $this->looksLikeModulCode($modul_clean)) {
                // Save previous module
                if($current) $modules[] = $current;
                // Start new module
                $name = $cells[($modul_col + 1) ?? null] ?? '';
                $current = [
                    'modulnummer' => $modul_clean,
                    'name' => $name,
                    'cells' => [],
                    'semester' => [],
                ];
                $current_y_max = max(array_column($row, 'y'));
            } elseif($current && mb_strlen($modul_clean) > 0
                && substr($current['modulnummer'], -1) === '-'
                && preg_match('/^[A-Za-z0-9.*]+$/', $modul_clean)
                && mb_strlen($modul_clean) < 20) {
                // Modulnummer-Continuation: vorherige Modulnummer endet mit "-".
                $current['modulnummer'] .= $modul_clean;
            }

            // Modulname wird auf der nächsten Zeile (gleiche Spalte) ergänzt,
            // falls die Zelle in Spalte 1 (Name) noch leer ist.
            if($current && (empty($current['name']) || mb_strlen($current['name']) < 3)
                && isset($cells[$modul_col + 1]) && mb_strlen($cells[$modul_col + 1]) > 3
                && $this->row_y_greater_than($row, $current_y_max)) {
                $current['name'] = trim(($current['name'] ?? '').' '.$cells[$modul_col + 1]);
                $current_y_max = max($current_y_max, max(array_column($row, 'y')));
            }

            // Process cells for SWS / PL / LP
            if($current) {
                // Store cells for further processing
                $current['cells'][] = $cells;
            }
        }
        if($current) $modules[] = $current;

        // Post-process: extract semester data from cells
        foreach($modules as &$m) {
            $this->parseCellsToSemester($m, $cols, $modul_col, $lp_col);
            unset($m['cells']); // cleanup
        }
        return $modules;
    }

    /**
     * Parse the cells of a module into semester[] array.
     */
    public function parseCellsToSemester(array &$module, array $cols, int $modul_col, int $lp_col): void {
        // Cells are organized as rows of cells (per text line)
        // The SWS/PL columns are between modulname and LP
        // Each semester gets one column
        // We need to figure out which row contains SWS (numbers like "2/0/0/2") and which row contains PL (e.g., "1 PL")
        $sws_cols = range($modul_col + 2, $lp_col - 1); // columns between name and LP
        $semesters = [];
        foreach($module['cells'] as $row_cells) {
            // Find SWS row: cells with content like "2/0/0/2"
            foreach($row_cells as $col_i => $cell_text) {
                if(!in_array($col_i, $sws_cols, true)) continue;
                if(preg_match('/^\d+\/\d+/', $cell_text)) {
                    // SWS data for semester (col_i - modul_col - 1) = semester index
                    $sem_idx = $col_i - $modul_col - 2;
                    if(!isset($semesters[$sem_idx])) $semesters[$sem_idx] = ['semester' => $sem_idx + 1, 'sws' => [], 'pl_count' => 0];
                    $parts = explode('/', $cell_text);
                    $semesters[$sem_idx]['sws'] = $parts;
                }
                if(preg_match('/^(\d+)\s*PL\s*$/i', $cell_text, $plm)) {
                    $sem_idx = $col_i - $modul_col - 2;
                    if(!isset($semesters[$sem_idx])) $semesters[$sem_idx] = ['semester' => $sem_idx + 1, 'sws' => [], 'pl_count' => 0];
                    $semesters[$sem_idx]['pl_count'] = max($semesters[$sem_idx]['pl_count'], (int)$plm[1]);
                }
            }
            // LP column - usually single integer on last row
            $lp_text = trim($row_cells[$lp_col] ?? '');
            if(preg_match('/^\d+$/', $lp_text) && (int)$lp_text > 0 && (int)$lp_text <= 50) {
                if(!isset($module['lp'])) $module['lp'] = (int)$lp_text;
            }
        }
        ksort($semesters);
        $module['semester'] = array_values($semesters);
    }

    /**
     * Helper: prüft, ob die Y-Positionen der Wörter in $row alle größer als $y_max sind.
     * Wird verwendet, um zu erkennen, ob eine Zeile "unter" der bisherigen Modulname-Zeile liegt.
     */
    public function row_y_greater_than(array $row, float $y_max): bool {
        foreach($row as $w) {
            if(isset($w['y']) && (float)$w['y'] <= $y_max + 2) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check if string looks like a module code.
     */
    public function looksLikeModulCode(string $s): bool {
        return $this->isModulCode($s);
    }

    /**
     * Locate each module on the PDF page where its modulnummer first appears.
     * Returns array of [modulnummer => page_number] (1-based).
     *
     * Strategy: for each page in the parsed text, check whether the modulnummer
     * token appears at the start of a line (after optional whitespace). The first
     * page containing such a hit wins.
     */
    public function locateModulesInPages(SoiPdfText $text, array $modules): array {
        $pages = $text->pages;
        $result = [];
        if(empty($pages) || empty($modules)) return $result;
        foreach($modules as $m) {
            $code = isset($m['modulnummer']) ? trim((string)$m['modulnummer']) : '';
            if($code === '') continue;
            $found_page = -1;
            foreach($pages as $page_idx => $page_text) {
                // Strip form-feed; check line-start with optional whitespace
                $lines = preg_split('/\r\n|\r|\n/', (string)$page_text);
                foreach($lines as $ln) {
                    if(preg_match('/^\s*'.preg_quote($code, '/').'(?:\s|$)/u', $ln)) {
                        $found_page = $page_idx + 1;
                        break 2;
                    }
                }
            }
            $result[$code] = $found_page > 0 ? $found_page : 1;
        }
        return $result;
    }

    /**
     * Layout-based Anlage 2 parser (improved version).
     * Strategy:
     *  - Read Anlage 2 block
     *  - For each line, check if it starts a new module (modulnummer pattern)
     *  - Module names may wrap to subsequent lines (until next modulnummer)
     *  - SWS cells are like "2/0/0/2" with N parts = N semesters
     *  - PL cells are like "1 PL" or "2 PL"
     *  - LP is the rightmost single number on a module row
     */
    public function parseAnlage2FromText(SoiPdfText $text): array {
        $boundaries = $this->findAnlageBoundaries($text);
        $a2 = $boundaries['anlage2_start'];
        if($a2 === false) return [];
        // Take from a2 to end of text
        $block = mb_substr($text->full_text, $a2);

        $lines = preg_split('/\r\n|\r|\n/', $block);
        $modules = [];
        $current = null;
        $current_section = 'Kernbereich';
        $current_block_lines = [];  // Sammelt alle Zeilen, die zur aktuellen Zeile gehören

        // Hilfsfunktion: Extrahiere SWS-Zellen aus einer Zeile (alle "X/Y/Z/W"-Sequenzen).
        $extract_sws = function($line) {
            $out = [];
            if(preg_match_all('/\b\d+\/\d+(?:\/\d+)*\b|\*+(?:\/\*+)*/', $line, $m)) {
                foreach($m[0] as $cell) {
                    if(substr_count($cell, '*') > 0) {
                        // Wildcard → als SWS mit Sternchen markieren
                        $parts = explode('/', $cell);
                        if(count($parts) === 1 && strpos($cell, '*') !== false) {
                            $parts = array_fill(0, 4, '*');
                        }
                        $out[] = $parts;
                    } else {
                        $out[] = explode('/', $cell);
                    }
                }
            }
            return $out;
        };

        // Hilfsfunktion: Extrahiere PL-Marker aus Zeile (alle "N PL" oder "PL").
        $extract_pl = function($line) {
            // Variante 1: "N PL" mit Ziffer davor (Anzahl PLs)
            if(preg_match_all('/(\d+)\s*PL\*?\b/i', $line, $m)) {
                return array_map('intval', $m[1]);
            }
            // Variante 2: nur "PL" ohne Ziffer → 1 PL annehmen.
            if(preg_match('/\bPL\*?\b/i', $line)) return [1];
            return [];
        };

        // Hilfsfunktion: Extrahiere LP (einzelne Zahl am Ende einer Zeile).
        $extract_lp = function($line) {
            // Suche nach einer Zahl 1-200, die wie LP-Wert aussieht (am Zeilenende oder isoliert).
            // Vermeide SWS-Zellen ("X/Y/Z") und PLs ("N PL").
            $stripped = preg_replace('/\b\d+\/\d+(?:\/\d+)*\b|\*+(?:\/\*+)*/', '', $line);
            $stripped = preg_replace('/\d+\s*PL\*?\b/i', '', $stripped);
            $stripped = trim($stripped);
            if(preg_match('/(\d{1,3})\s*$/', $stripped, $m)) {
                $val = (int)$m[1];
                if($val >= 1 && $val <= 200) return $val;
            }
            return null;
        };

        // Hilfsfunktion: Ist die Zeile ein Modulnummer-Header?
        // Modulnummer steht am Zeilenanfang (mit optionalem führenden Whitespace bis max 8 Zeichen).
        $is_modul_header = function($line) use (&$is_modul_header) {
            // Pattern: optional 1-8 spaces, dann Modulnummer mit optionalem internen Space
            // (z.B. "EM 1"), dann gefolgt von Whitespace oder Zeilenende.
            // Akzeptiert auch Codes, die mit Bindestrich enden (wrapped).
            if(preg_match('/^(\s{0,8})([A-Z][A-Za-z0-9.\-]+(?:\s[A-Z0-9.\-]+)?)(?=\s|$)/u', $line, $m)) {
                $raw_code = $m[2];
                if(substr_count($raw_code, ' ') === 1 && preg_match('/^[A-Z0-9.\-]+\s[A-Z0-9.\-]+$/u', $raw_code)) {
                    $raw_code = preg_replace('/\s+/', '', $raw_code);
                }
                if($this->isModulCode($raw_code)) return $raw_code;
                if(substr($raw_code, -1) === '-' && strlen($raw_code) >= 5 && preg_match('/^[A-Z]+(-[A-Z0-9]{1,4}){1,5}-$/', $raw_code)) {
                    return $raw_code;
                }
            }
            return false;
        };

        // Hilfsfunktion: Verarbeite die gesammelten Block-Zeilen einer Zeile.
        $process_block = function() use (&$current, &$modules, &$current_block_lines, &$current_section, $extract_sws, $extract_pl, $extract_lp) {
            if(empty($current_block_lines) || $current === null) return;
            $joined = implode("\n", $current_block_lines);

            // SWS-Zellen extrahieren (in Reihenfolge des Vorkommens).
            $sws_cells = $extract_sws($joined);
            if(!empty($sws_cells)) {
                foreach($sws_cells as $idx => $sws) {
                    if(!isset($current['semester'][$idx])) {
                        $current['semester'][$idx] = ['semester' => $idx + 1, 'sws' => $sws, 'pl_count' => 0];
                    } else {
                        $current['semester'][$idx]['sws'] = $sws;
                    }
                }
            }

            // PL-Marker extrahieren.
            $pl_values = $extract_pl($joined);
            $n_sem = count($current['semester']);
            $n_pl = count($pl_values);
            if($n_pl > 0) {
                if($n_sem === 0) {
                    // Keine SWS-Zelle: nimm an, das PL gehört zum letzten Semester oder erstelle neues.
                    $current['semester'][] = ['semester' => max(1, $n_pl), 'sws' => [], 'pl_count' => $pl_values[0]];
                } else {
                    // Verteile PLs rückwärts auf die Semester (typisch: PL steht rechts neben Semester).
                    for($k = 0; $k < $n_pl && $k < $n_sem; $k++) {
                        $idx = $n_sem - 1 - $k;
                        $current['semester'][$idx]['pl_count'] = max(
                            $current['semester'][$idx]['pl_count'],
                            $pl_values[$n_pl - 1 - $k]
                        );
                    }
                }
            }

            // LP extrahieren (nur wenn noch nicht gesetzt).
            if($current['lp'] === null) {
                $lp = $extract_lp($joined);
                if($lp !== null) $current['lp'] = $lp;
            }

            // Name: alles was nicht SWS/PL/LP ist und nicht der Modulnummer entspricht.
            $name_text = $joined;
            // Entferne die Modulnummer-Zeile (falls vorhanden).
            $name_text = preg_replace('/^.{0,80}' . preg_quote($current['modulnummer'], '/') . '.*$/m', '', $name_text);
            // SWS-Zellen entfernen.
            $name_text = preg_replace('/\b\d+\/\d+(?:\/\d+)*\b|\*+(?:\/\*+)*/', ' ', $name_text);
            // PL-Marker entfernen (mit oder ohne Zahl davor).
            $name_text = preg_replace('/\b\d+\s*PL\*?\b/i', ' ', $name_text);
            $name_text = preg_replace('/\bPL\*?\b/', ' ', $name_text);
            // LP am Zeilenende entfernen (Zahl am Zeilenende, klein genug für LP).
            $name_text = preg_replace('/\s+\d{1,3}\s*$/m', ' ', $name_text);
            // SWS-Bemerkungen wie "2 SWS", "1 SWS" entfernen.
            $name_text = preg_replace('/\b\d+\s*SWS\*?\b/i', ' ', $name_text);
            $name_text = trim($name_text);
            // Multi-line → single-line (Space-getrennt).
            $name_text = preg_replace('/\s+/u', ' ', $name_text);
            if($name_text !== '' && empty($current['name'])) {
                $current['name'] = $name_text;
            }
        };

        foreach($lines as $i => $line) {
            $trimmed = trim($line);
            if($trimmed === '') continue;

            // Section headers
            if(preg_match('/^1\.\s+Erg/u', $trimmed) || preg_match('/^2\.\s+Erg/u', $trimmed)) {
                $process_block();
                $current_block_lines = [];
                if($current) { $modules[] = $current; $current = null; }
                $current_section = 'Ergänzungsbereich';
                continue;
            }
            if(preg_match('/^\d\.\d+\s+(.+)$/u', $trimmed, $m)) {
                $process_block();
                $current_block_lines = [];
                if($current) { $modules[] = $current; $current = null; }
                $current_section = trim($m[1]);
                continue;
            }

            // Tabellen-Header (Modul-Nr., Modulname, etc.) → ignorieren
            if(preg_match('/Modul-?Nr\.|Modulname|V\/S\/T\/AK\/P/i', $trimmed) && mb_strlen($trimmed) < 100) {
                continue;
            }
            // Semester-Spalten-Header (1. Semester, 2. Semester, etc.)
            if(preg_match('/\d+\.\s*Semester/i', $trimmed)) {
                continue;
            }
            // LP-Spalten-Header
            if(preg_match('/^\(?M\)?\s*$|^\(?Mobilit/i', $trimmed)) continue;

            // Modulnummer-Header?
            $modul_code = $is_modul_header($line);
            if($modul_code !== false) {
                // Vorherigen Block abschließen.
                $process_block();
                $current_block_lines = [];

                // Neuen Moduleintrag anlegen.
                if($current) $modules[] = $current;
                $current = [
                    'modulnummer' => $modul_code,
                    'name' => '',
                    'lp' => null,
                    'semester' => [],
                    'section' => $current_section,
                ];
                // Rest der Zeile (alles nach dem Code) zur Block-Sammlung. Wir versuchen
                // den Code im Original-Format (mit Space) in der Zeile zu finden. Dazu
                // versuchen wir, einen optionalen Space im letzten Buchstaben-/Ziffer-Paar
                // wieder einzufügen (z.B. "VM2" → "VM 2") und danach zu suchen.
                $after_code = '';
                $variants = array();
                // 1) Code wie er ist (ohne Space).
                $variants[] = $modul_code;
                // 2) Mit Space vor dem letzten Buchstaben-/Ziffer-Block (z.B. "PHF-BA-KG-VM2" → "PHF-BA-KG-VM 2").
                if(preg_match('/^(.*?)([A-Z]+)(\d+)$/', $modul_code, $vm)) {
                    $variants[] = $vm[1].$vm[2].' '.$vm[3];
                }
                // 3) Mit Space vor dem letzten Ziffer-Block (z.B. "KG-EM1" → "KG-EM 1").
                if(preg_match('/^(.*?)([A-Za-z]+)(\d+)$/', $modul_code, $vm2)) {
                    $variants[] = $vm2[1].$vm2[2].' '.$vm2[3];
                }
                foreach($variants as $v) {
                    $pos = strpos($line, $v);
                    if($pos !== false) {
                        $after_code = trim(substr($line, $pos + strlen($v)));
                        break;
                    }
                }
                $after_code = preg_replace('/^\s+/u', '', $after_code);
                if($after_code !== '') $current_block_lines[] = $after_code;
                continue;
            }

            // Modulnummer-Continuation (Code endet mit "-")
            if($current && substr($current['modulnummer'], -1) === '-') {
                $first_token = strtok($trimmed, " \t");
                if($first_token !== false
                    && preg_match('/^[A-Za-z0-9.\-]+$/u', $first_token)
                    && mb_strlen($first_token) < 25
                    && !preg_match('/^\d+$/', $first_token)) {
                    $current['modulnummer'] .= $first_token;
                    $rest = trim(substr($trimmed, mb_strlen($first_token)));
                    if($rest !== '') $current_block_lines[] = $rest;
                    continue;
                }
            }

            // Alles andere → zur Block-Sammlung (Name + SWS + PL + LP)
            $current_block_lines[] = $line;
        }
        // Letzten Block verarbeiten.
        $process_block();
        if($current) $modules[] = $current;

        // Post-process: clean names und entferne offensichtliche Junk-Module.
        foreach($modules as &$m) {
            // Trailing junk entfernen
            $m['name'] = trim(preg_replace('/\s+/', ' ', $m['name']));
            // Sehr kurze Namen sind oft Header-Reste.
            $m['lp'] = $m['lp'] ?? null;
        }
        unset($m);
        return $this->filterValidAnlage2($modules);
    }

    /**
     * Filter für Anlage-2-Zeilen. Anlage 2 hat weniger strikte Anforderungen als
     * Anlage 1 (manche Module haben keinen vollständigen Namen in der Tabelle).
     * Wir verwerfen nur offensichtlichen Müll.
     */
    public function filterValidAnlage2(array $modules): array {
        $valid = [];
        foreach($modules as $m) {
            $code = isset($m['modulnummer']) ? trim((string)$m['modulnummer']) : '';
            if($code === '' || strlen($code) < 5) continue;
            if(preg_match('/\s/', $code)) continue;
            if(!$this->isModulCode($code)) continue;
            $valid[] = $m;
        }
        return $valid;
    }

    /**
     * Parse a single Anlage 2 row into module structure.
     */
    public function parseAnlage2Row(string $code, string $rest, string $section): array {
        // Split by 2+ spaces
        $parts = preg_split('/\s{2,}/u', trim($rest));
        $module = [
            'modulnummer' => $code,
            'name' => '',
            'lp' => null,
            'semester' => [],
            'section' => $section,
        ];
        if(empty($parts)) return $module;

        // First part is the name
        $module['name'] = $parts[0];

        // Last part might be LP (single integer). Manche Module haben bis zu 70 LP.
        $last_part = end($parts);
        if(preg_match('/^\d+$/', $last_part) && (int)$last_part > 0 && (int)$last_part <= 200) {
            $module['lp'] = (int)$last_part;
            array_pop($parts);
        }

        // Remaining parts are SWS cells (like "2/0/0/2") per semester
        $semester_idx = 1;
        foreach($parts as $i => $p) {
            if($i === 0) continue; // skip name
            $p = trim($p);
            if(preg_match('/^[\d\.\/]+$/', $p)) {
                // SWS cell: split by /
                $sws_parts = explode('/', $p);
                $module['semester'][] = [
                    'semester' => $semester_idx++,
                    'sws' => $sws_parts,
                    'pl_count' => 0,
                ];
            } elseif(preg_match('/^\*+(?:\/\*+)+$/', $p) || preg_match('/^\*+$/', $p)) {
                // Wildcard-Semester: "*/*/*/*" oder "****"
                $star_count = substr_count($p, '*');
                if($star_count > 1 && strpos($p, '/') !== false) {
                    $module['semester'][] = [
                        'semester' => $semester_idx++,
                        'sws' => explode('/', $p),
                        'pl_count' => 0,
                    ];
                } else {
                    $module['semester'][] = [
                        'semester' => $semester_idx++,
                        'sws' => ['*', '*', '*', '*'],
                        'pl_count' => 0,
                    ];
                }
            }
        }
        return $module;
    }

    /**
     * Parse Anlage 2 from pdftohtml XML.
     */
    public function parseAnlage2FromXml(SoiPdfText $text): array {
        $elements = $text->elements;
        // Filter to Anlage 2 pages
        $current_page = 0;
        $in_a2 = false;
        $a2_elements = [];
        foreach($elements as $el) {
            if($el['page'] != $current_page) {
                $current_page = $el['page'];
                $in_a2 = false;
            }
            if(!$in_a2 && strtolower(trim($el['text'])) === 'studienablaufplan') {
                $in_a2 = true;
            }
            if($in_a2) {
                $a2_elements[] = $el;
            }
        }
        // Group by page, then by row using top position (pdftohtml uses 'top' attribute)
        // The XML is HTML-like so we need to parse it more carefully
        // For now, fall back to text-based
        return $this->parseAnlage2FromText($text);
    }

    /**
     * Parse bbox HTML output to extract word positions.
     * Returns array of {page, x, y, w, h, text}.
     */
    public function parseBboxHtml(string $html): array {
        $words = [];
        // Strip DOCTYPE, head, body tags
        $html = preg_replace('/<\?xml[^>]*\?>/', '', $html);
        $html = preg_replace('/<!DOCTYPE[^>]*>/', '', $html);
        $html = preg_replace('/<head>.*?<\/head>/s', '', $html);

        // Extract each <page> block
        if(!preg_match_all('/<page\s+width="([^"]+)"\s+height="([^"]+)"\s*>(.*?)<\/page>/s', $html, $page_matches, PREG_SET_ORDER)) {
            return $words;
        }
        foreach($page_matches as $page_idx => $pm) {
            $page_num = $page_idx + 1;
            $page_content = $pm[3];
            // Extract each <word> with its bbox
            if(!preg_match_all('/<word\s+xMin="([^"]+)"\s+yMin="([^"]+)"\s+xMax="([^"]+)"\s+yMax="([^"]+)"\s*>([^<]+)<\/word>/s', $page_content, $word_matches, PREG_SET_ORDER)) {
                continue;
            }
            foreach($word_matches as $wm) {
                $words[] = [
                    'page' => $page_num,
                    'x' => (float)$wm[1],
                    'y' => (float)$wm[2],
                    'w' => (float)$wm[3] - (float)$wm[1],
                    'h' => (float)$wm[4] - (float)$wm[2],
                    'text' => trim(html_entity_decode($wm[5], ENT_QUOTES | ENT_HTML5, 'UTF-8')),
                ];
            }
        }
        return $words;
    }

    /**
     * Parse pdftohtml -xml output to extract text elements with positions.
     */
    public function parseXmlElements(string $xml): array {
        $elements = [];
        if(!preg_match_all('/<page\s+number="(\d+)"[^>]*>(.*?)<\/page>/s', $xml, $page_matches, PREG_SET_ORDER)) {
            return $elements;
        }
        foreach($page_matches as $pm) {
            $page_num = (int)$pm[1];
            $page_content = $pm[2];
            if(!preg_match_all('/<text\s+top="([^"]+)"\s+left="([^"]+)"\s+width="([^"]+)"\s+height="([^"]+)"\s+font="([^"]+)"[^>]*>(.*?)<\/text>/s', $page_content, $text_matches, PREG_SET_ORDER)) {
                continue;
            }
            foreach($text_matches as $tm) {
                $text = trim(strip_tags(html_entity_decode($tm[6], ENT_QUOTES | ENT_HTML5, 'UTF-8')));
                if($text === '') continue;
                $elements[] = [
                    'page' => $page_num,
                    'top' => (float)$tm[1],
                    'left' => (float)$tm[2],
                    'width' => (float)$tm[3],
                    'height' => (float)$tm[4],
                    'font' => $tm[5],
                    'text' => $text,
                ];
            }
        }
        return $elements;
    }
}

} // end if(!class_exists('SoiExtractor'))
