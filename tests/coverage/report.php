<?php
/*
 * Merge raw Xdebug coverage files and generate a report.
 *
 * Usage:
 *   php tests/coverage/report.php --dir=/tmp/vvz-coverage \
 *       --summary=/tmp/coverage-summary.md \
 *       --html=/tmp/coverage.html \
 *       --json=/tmp/coverage.json
 *
 * Only files belonging to this repository are considered. Test
 * infrastructure (tests/), third-party libraries (Classes/) and static
 * asset directories are excluded from the percentage.
 *
 * When running in GitHub Actions the generated markdown is appended to
 * $GITHUB_STEP_SUMMARY automatically, so the coverage is visible directly
 * on the workflow run page.
 */

$options = array(
	'dir' => getenv('VVZ_COVERAGE_DIR') ?: '',
	'summary' => '',
	'html' => '',
	'json' => '',
);

foreach (array_slice($argv, 1) as $arg) {
	if (preg_match('/^--([a-z]+)=(.*)$/', $arg, $m) && array_key_exists($m[1], $options)) {
		$options[$m[1]] = $m[2];
	}
}

if ($options['dir'] === '' || !is_dir($options['dir'])) {
	fwrite(STDERR, "Coverage directory not found: {$options['dir']}\n");
	exit(1);
}

$root = realpath(dirname(__DIR__, 2));
$root_prefix = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

$excluded_paths = array(
	'tests/',
	'testing.php',
	'testsuite.php',
	'Classes/',
	'metadata/',
	'mathjax/',
	'data/',
	'tmp/',
	'debuglogs/',
	'font/',
	'fonts/',
	'webfonts/',
	'css/',
	'js/',
	'scss/',
	'less/',
	'sprites/',
	'svgs/',
	'icons/',
	'videos/',
	'i/',
	'd/',
	'ua/',
	'htmltest/',
);

$all = array();
// Lines that can never execute in this file: studienordnung_import.php
// contains duplicate persist helper definitions guarded by
// function_exists(). studienordnung_persist.php is loaded first, so these
// definitions are always skipped. Counting them as uncovered would make the
// file's coverage artificially low.
$excluded_line_ranges = array(
	'pages/studienordnung_import.php' => array(array(119, 503)),
);
foreach (glob(rtrim($options['dir'], '/') . '/*.json') as $coverage_file) {
	$data = json_decode((string) file_get_contents($coverage_file), true);
	if (!is_array($data)) {
		continue;
	}
	foreach ($data as $file => $lines) {
		if (!is_array($lines)) {
			continue;
		}
		if (!isset($all[$file])) {
			$all[$file] = array();
		}
		foreach ($lines as $line => $status) {
			// -2 means dead code. Counting it as "uncovered executable code"
			// would punish functions that intentionally return early.
			if ($status === -2) {
				continue;
			}
			if (!isset($all[$file][$line]) || $status > $all[$file][$line]) {
				$all[$file][$line] = $status;
			}
		}
	}
}

function vvz_coverage_relative_path($file, $root_prefix)
{
	$real = realpath($file);
	if ($real === false || strpos($real, $root_prefix) !== 0) {
		return null;
	}
	return str_replace(DIRECTORY_SEPARATOR, '/', substr($real, strlen($root_prefix)));
}

function vvz_coverage_line_is_excluded($relative, $line, $excluded_line_ranges)
{
	if (!isset($excluded_line_ranges[$relative])) {
		return false;
	}
	foreach ($excluded_line_ranges[$relative] as $range) {
		if ($line >= $range[0] && $line <= $range[1]) {
			return true;
		}
	}
	return false;
}

function vvz_coverage_is_source($relative, $excluded_paths)
{
	if ($relative === null || substr($relative, -4) !== '.php') {
		return false;
	}
	foreach ($excluded_paths as $excluded) {
		if (strpos($relative, $excluded) === 0) {
			return false;
		}
	}
	return true;
}

function vvz_coverage_ranges($lines)
{
	if (!count($lines)) {
		return '';
	}
	sort($lines, SORT_NUMERIC);
	$ranges = array();
	$start = $lines[0];
	$end = $lines[0];
	foreach (array_slice($lines, 1) as $line) {
		if ($line === $end + 1) {
			$end = $line;
		} else {
			$ranges[] = ($start === $end) ? (string) $start : $start . '-' . $end;
			$start = $end = $line;
		}
	}
	$ranges[] = ($start === $end) ? (string) $start : $start . '-' . $end;
	return join(', ', $ranges);
}

function vvz_coverage_percent($covered, $total)
{
	return $total > 0 ? (100 * $covered / $total) : 0.0;
}

$files = array();
$total_statements = 0;
$total_covered = 0;

foreach ($all as $file => $lines) {
	$relative = vvz_coverage_relative_path($file, $root_prefix);
	if (!vvz_coverage_is_source($relative, $excluded_paths)) {
		continue;
	}

	$statements = 0;
	$covered = 0;
	$missing = array();
	foreach ($lines as $line => $status) {
		if (vvz_coverage_line_is_excluded($relative, (int) $line, $excluded_line_ranges)) {
			continue;
		}
		if ($status === 1) {
			$statements++;
			$covered++;
		} elseif ($status === -1) {
			$statements++;
			$missing[] = (int) $line;
		}
	}
	if ($statements === 0) {
		continue;
	}

	$files[$relative] = array(
		'path' => $relative,
		'statements' => $statements,
		'covered' => $covered,
		'missing' => $missing,
		'percent' => vvz_coverage_percent($covered, $statements),
	);
	$total_statements += $statements;
	$total_covered += $covered;
}

ksort($files);

$total_percent = vvz_coverage_percent($total_covered, $total_statements);

/* ------------------------------------------------------------------ */
/* Markdown for the GitHub Actions summary                             */
/* ------------------------------------------------------------------ */

$summary = "## Test coverage\n\n";
$summary .= sprintf(
	"**Overall first-party coverage: %.1f%%** (%s of %s executable lines in %d files)\n\n",
	$total_percent,
	number_format($total_covered, 0, ',', '.'),
	number_format($total_statements, 0, ',', '.'),
	count($files)
);

if (count($files)) {
	$summary .= "| File | Coverage | Covered / Total |\n";
	$summary .= "|---|---:|---:|\n";
	foreach ($files as $file) {
		$summary .= sprintf(
			"| `%s` | %.1f%% | %s / %s |\n",
			$file['path'],
			$file['percent'],
			number_format($file['covered'], 0, ',', '.'),
			number_format($file['statements'], 0, ',', '.')
		);
	}
	$summary .= "\n";

	$lowest = $files;
	uasort($lowest, function ($a, $b) {
		if ($a['percent'] === $b['percent']) {
			return $b['statements'] <=> $a['statements'];
		}
		return $a['percent'] <=> $b['percent'];
	});
	$lowest = array_slice($lowest, 0, 10, true);

	$summary .= "<details><summary>Lowest coverage (top 10)</summary>\n\n";
	$summary .= "| File | Coverage | Missing lines |\n";
	$summary .= "|---|---:|---|\n";
	foreach ($lowest as $file) {
		$missing = vvz_coverage_ranges($file['missing']);
		if (strlen($missing) > 300) {
			$missing = substr($missing, 0, 300) . ' …';
		}
		$summary .= sprintf(
			"| `%s` | %.1f%% | %s |\n",
			$file['path'],
			$file['percent'],
			$missing !== '' ? $missing : '—'
		);
	}
	$summary .= "\n</details>\n\n";
}

$summary .= "_Coverage is collected with Xdebug over the DB, pure, subpage and request-dispatcher test suites. Test files, third-party libraries and unreachable duplicate definitions are excluded._\n";

if ($options['summary'] !== '') {
	@mkdir(dirname($options['summary']), 0777, true);
	file_put_contents($options['summary'], $summary);
}
if (getenv('GITHUB_STEP_SUMMARY')) {
	file_put_contents(getenv('GITHUB_STEP_SUMMARY'), $summary, FILE_APPEND);
}
print $summary;

/* ------------------------------------------------------------------ */
/* Machine-readable JSON                                              */
/* ------------------------------------------------------------------ */

if ($options['json'] !== '') {
	@mkdir(dirname($options['json']), 0777, true);
	file_put_contents($options['json'], json_encode(array(
		'total' => array(
			'covered' => $total_covered,
			'statements' => $total_statements,
			'percent' => $total_percent,
		),
		'files' => array_values($files),
	), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

/* ------------------------------------------------------------------ */
/* Standalone HTML report                                             */
/* ------------------------------------------------------------------ */

if ($options['html'] !== '') {
	$html = '<!doctype html><html lang="en"><head><meta charset="utf-8">';
	$html .= '<title>VVZ coverage report</title><style>';
	$html .= 'body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;margin:2rem;color:#1f2328;background:#fff}';
	$html .= 'table{border-collapse:collapse;width:100%;margin:1rem 0}th,td{padding:.35rem .6rem;border-bottom:1px solid #d0d7de;text-align:left;vertical-align:top}';
	$html .= 'th{background:#f6f8fa}td.num{text-align:right;font-variant-numeric:tabular-nums}code{font-size:.9em}';
	$html .= '.bar{height:.8rem;background:#eee;border-radius:4px;overflow:hidden;min-width:120px}.bar>span{display:block;height:100%}';
	$html .= '.good{background:#1a7f37}.medium{background:#bf8700}.bad{background:#cf222e}.missing{font-family:ui-monospace,SFMono-Regular,monospace;font-size:.8em;color:#57606a}';
	$html .= 'details{margin:.5rem 0}summary{cursor:pointer;font-weight:600}';
	$html .= '</style></head><body>';
	$html .= '<h1>VVZ coverage report</h1>';
	$html .= sprintf(
		'<p><strong>Overall first-party coverage: %.1f%%</strong> (%s of %s executable lines in %d files)</p>',
		$total_percent,
		number_format($total_covered, 0, ',', '.'),
		number_format($total_statements, 0, ',', '.'),
		count($files)
	);
	$html .= '<table><thead><tr><th>File</th><th>Coverage</th><th class="num">Covered</th><th class="num">Total</th><th>Missing lines</th></tr></thead><tbody>';
	foreach ($files as $file) {
		$class = $file['percent'] >= 80 ? 'good' : ($file['percent'] >= 50 ? 'medium' : 'bad');
		$missing = vvz_coverage_ranges($file['missing']);
		$html .= '<tr>';
		$html .= '<td><code>' . htmlentities($file['path']) . '</code></td>';
		$html .= '<td><div class="bar"><span class="' . $class . '" style="width:' . sprintf('%.1f', $file['percent']) . '%"></span></div>' . sprintf('%.1f%%', $file['percent']) . '</td>';
		$html .= '<td class="num">' . number_format($file['covered'], 0, ',', '.') . '</td>';
		$html .= '<td class="num">' . number_format($file['statements'], 0, ',', '.') . '</td>';
		$html .= '<td class="missing">' . ($missing !== '' ? htmlentities($missing) : '—') . '</td>';
		$html .= '</tr>';
	}
	$html .= '</tbody></table></body></html>';

	@mkdir(dirname($options['html']), 0777, true);
	file_put_contents($options['html'], $html);
}

if ($total_statements === 0) {
	fwrite(STDERR, "No coverage data was found in {$options['dir']}\n");
	exit(1);
}
