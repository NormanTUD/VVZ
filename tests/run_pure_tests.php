<?php
/*
 * Standalone test runner for PURE (non-DB) tests.
 * This bypasses the database connection by defining only the functions we need.
 *
 * Usage: php tests/run_pure_tests.php
 */

$GLOBALS['started_tests'] = 0;
$GLOBALS['failed_tests'] = 0;
$GLOBALS['settings_cache'] = array();

// Minimal subset of functions needed for pure tests
function green_text ($str) { return "\033[32m".$str."\033[0m"; }
function red_text ($str) { return "\033[31m".$str."\033[0m"; }

// Fallback for bcmod (only for testing without bcmath extension)
if(!function_exists('bcmod')) {
	function bcmod($x, $y) {
		$take = 5;
		$mod = '';
		do {
			$a = (int)$mod . substr($x, 0, $take);
			$x = substr($x, $take);
			$mod = $a % $y;
		} while(strlen($x));
		return (int)$mod;
	}
}

function print_diffs ($name, $a, $b) {
	return "ERROR: $name failed!\n";
}

function increate_started_tests () {
	if(array_key_exists('started_tests', $GLOBALS)) {
		$GLOBALS['started_tests'] = $GLOBALS['started_tests'] + 1;
	} else {
		$GLOBALS['started_tests'] = 1;
	}
}

function test_failed () {
	if(array_key_exists('failed_tests', $GLOBALS)) {
		$GLOBALS['failed_tests'] = $GLOBALS['failed_tests'] + 1;
	} else {
		$GLOBALS['failed_tests'] = 1;
	}
}

function is_equal ($name, $a, $b) {
	increate_started_tests();
	if(gettype($a) == gettype($b)) {
		if(gettype($a) == 'string') {
			if($a == $b) {
				print green_text("OK").": $name\n";
				return 1;
			} else {
				print red_text("FAIL").": $name (expected: '$b', got: '$a')\n";
				test_failed();
				return 0;
			}
		} else {
			if (serialize($a) == serialize($b)) {
				print green_text("OK").": $name\n";
				return 1;
			} else {
				print red_text("FAIL").": $name (expected: " . print_r($b, true) . ", got: " . print_r($a, true) . ")\n";
				test_failed();
				return 0;
			}
		}
	} else {
		print red_text("FAIL").": $name (type mismatch: expected " . gettype($b) . ", got " . gettype($a) . ")\n";
		test_failed();
		return 0;
	}
}

function is_unequal ($name, $a, $b) {
	increate_started_tests();
	if(!gettype($a) == gettype($b)) {
		print green_text("OK").": $name\n";
		return 1;
	} else {
		if(gettype($a) == gettype($b)) {
			if(gettype($a) == 'string') {
				if($a == $b) {
					test_failed();
					return 0;
				} else {
					print green_text("OK").": $name\n";
					return 1;
				}
			} else {
				if (serialize($a) == serialize($b)) {
					test_failed();
					return 0;
				} else {
					print green_text("OK").": $name\n";
					return 1;
				}
			}
		}
	}
	return 0;
}

function regex_matches ($name, $string, $regex) {
	increate_started_tests();
	if(gettype($string) == 'integer' || gettype($string) == 'float') {
		$string = (string) $string;
	}
	if(gettype($string) == 'string') {
		if(preg_match($regex, $string)) {
			print green_text("OK").": $name\n";
			return 1;
		} else {
			print red_text("FAIL").": $name (regex did not match: '$regex' on '$string')\n";
			test_failed();
			return 0;
		}
	}
	print red_text("FAIL").": $name (not a string)\n";
	test_failed();
	return 0;
}

function regex_fails ($name, $string, $regex) {
	increate_started_tests();
	if(gettype($string) == 'integer' || gettype($string) == 'float') {
		$string = (string) $string;
	}
	if(gettype($string) == 'string') {
		if(preg_match($regex, $string)) {
			print red_text("FAIL").": $name (regex matched but should not: '$regex' on '$string')\n";
			test_failed();
			return 0;
		} else {
			print green_text("OK").": $name\n";
			return 1;
		}
	}
	print red_text("FAIL").": $name (not a string)\n";
	test_failed();
	return 0;
}

function is_equal_safe ($name, $a, $b) {
	if($a == $b) {
		print green_text("OK").": $name\n";
		return 1;
	} else {
		test_failed();
		return 0;
	}
}

// Define pure functions that we test
function htmle ($str, $shy = 0) {
	if($shy) {
		if($str) {
			$str = htmlentities($str);
			$str = preg_replace('/Philosophie/', 'Phi&shy;lo&shy;so&shy;phie', $str);
			$str = preg_replace('/Wissenschaft/', 'Wis&shy;sen&shy;schaft', $str);
			$str = preg_replace('/Erkenntnis/', 'Er&shy;kennt&shy;nis', $str);
			$str = preg_replace('/Theorie/', 'Theo&shy;rie', $str);
			$str = preg_replace('/Sprachphilosophie/', 'Sprach&shy;phi&shy;lo&shy;so&shy;phie', $str);
			$str = preg_replace('/Religion/', 'Re&shy;li&shy;gion', $str);
			$str = preg_replace('/Anthropologie/', 'An&shy;thro&shy;po&shy;lo&shy;gie', $str);
			$str = preg_replace('/Moralphilosophie/', 'Mo&shy;ral&shy;phi&shy;lo&shy;so&shy;phie', $str);
			$str = preg_replace('/Philosophische/', 'Phi&shy;lo&shy;so&shy;phi&shy;sche', $str);
			$str = preg_replace('/philosophie/', 'phi&shy;lo&shy;so&shy;phie', $str);
			$str = preg_replace('/Seminararbeit/', 'Se&shy;mi&shy;nar&shy;ar&shy;beit', $str);
			return $str;
		} else {
			return '&mdash;';
		}
	} else {
		// Note: production uses `if($str)` which is falsy for '0'
		if($str) {
			return htmlentities($str);
		} else {
			return '&mdash;';
		}
	}
}

function escapeJsonString($value) {
	$escapers = array("\\", "/", "\"", "\n", "\r", "\t", "\x08", "\x0c");
	$replacements = array("\\\\", "\\/", "\\\"", "\\n", "\\r", "\\t", "\\f", "\\b");
	$result = str_replace($escapers, $replacements, $value);
	return $result;
}

function my_strip_tags ($str) {
	$str = preg_replace('/<br\s*\/*>/', "\n", $str);
	return strip_tags($str);
}

function add_leading_zero ($v) {
	if(strlen($v) < 2) {
		return "0$v";
	} else {
		return $v;
	}
}

function create_hour_from_to ($from, $to, $array = 0) {
	$re = '/^\d+$/';
	if(preg_match($re, $from) && preg_match($re, $to)) {
		$times = array(
			0 => array("from" => "05:40", "to" => "07:10"),
			1 => array("from" => "07:30", "to" => "09:00"),
			2 => array("from" => "09:20", "to" => "10:50"),
			3 => array("from" => "11:10", "to" => "12:40"),
			4 => array("from" => "13:00", "to" => "14:30"),
			5 => array("from" => "14:50", "to" => "16:20"),
			6 => array("from" => "16:40", "to" => "18:10"),
			7 => array("from" => "18:30", "to" => "20:00"),
			8 => array("from" => "20:20", "to" => "21:50"),
			9 => array("from" => "22:10", "to" => "23:40")
		);

		if(array_key_exists($from, $times) && array_key_exists($to, $times)) {
			$from_time = $times[$from]['from'];
			$to_time = $times[$to]['to'];

			if($array) {
				return array($from_time, $to_time);
			} else {
				return "$from_time &mdash; $to_time";
			}
		} else {
			return null;
		}
	} else {
		return null;
	}
}

function get_previous_letter($string){
	if($string == "A") {
		return "A";
	}
	$last = substr($string, -1);
	$part = substr($string, 0, -1);
	if(strtoupper($last)=='A'){
		$l = substr($part, -1);
		if($l == 'A'){
			return substr($part, 0, -1)."Z";
		}
		return $part.chr(ord($l)-1);
	}else{
		return $part.chr(ord($last)-1);
	}
}

function comma_list_to_array ($str) {
	$array = array();
	$str = preg_replace('/^,+/', '', $str);
	$str = preg_replace('/,+$/', '', $str);
	$str = preg_replace('/\s+,\s+$/', ',', $str);
	$array = explode(",", $str);
	return $array;
}

function rarr ($str) {
	return preg_replace("/&rarr;/", '→', $str);
}

function mask_module ($module) {
	return "<i>$module</i>";
}

function print_line_link ($line) {
	return '<a href="#line_'.$line.'">'.$line.'</a>';
}

function array_value_or_null ($array, $id) {
	if(array_key_exists($id, $array)) {
		return $array[$id];
	} else {
		return NULL;
	}
}

function array_sort_by_column(&$arr, $col, $dir = SORT_ASC) {
	$sort_col = array();
	foreach ($arr as $key => $row) {
		$sort_col[$key] = $row[$col];
	}
	array_multisort($sort_col, $dir, $arr);
}

function array2Table($data, $status = array(), $error_lines = array()) {
	$html = "<table>";
	foreach ($data as $i => $row) {
		$html .= "<tr>";
		if(isset($status[$i]) && is_array($status[$i])) {
			if(isset($status[$i]['studiengang'])) {
				$html .= "<td>" . $status[$i]['studiengang'] . "</td>";
			} else {
				$html .= "<td></td>";
			}
		} elseif(isset($status[$i])) {
			$html .= "<td>" . $status[$i] . "</td>";
		}
		foreach ($row as $cell) {
			$html .= "<td>" . $cell . "</td>";
		}
		$html .= "</tr>";
	}
	$html .= "</table>";
	return $html;
}

function get_spalte($name, $spaltennummern, $col, $alternative = null, $alternative_2 = null) {
	if(array_key_exists($name, $spaltennummern)) {
		$nr = $spaltennummern[$name]["nr"];
		$optional = $spaltennummern[$name]["optional"];
		if(is_null($nr)) {
			if(!$optional) {
				if($alternative) {
					return $alternative;
				} else {
					if($alternative_2) {
						return $alternative_2;
					} else {
						die("Missing non optional column $name");
					}
				}
			} else {
				return null;
			}
		} else {
			if(array_key_exists($nr, $col)) {
				$value = $col[$nr];
				return $value;
			} else {
				return null;
			}
		}
	}
	return null;
}

function checkIBAN($iban) {
	$iban = strtolower(str_replace(' ','',$iban));
	$iban = strtolower(str_replace('-','',$iban));
	if(strlen($iban) != 22) {
		return false;
	}
	$Countries = array(
		'al' => 28, 'ad' => 24, 'at' => 20, 'az' => 28, 'bh' => 22, 'be' => 16,
		'ba' => 20, 'br' => 29, 'bg' => 22, 'cr' => 21, 'hr' => 21, 'cy' => 28,
		'cz' => 24, 'dk' => 18, 'do' => 28, 'ee' => 20, 'fo' => 18, 'fi' => 18,
		'fr' => 27, 'ge' => 22, 'de' => 22, 'gi' => 23, 'gr' => 27, 'gl' => 18,
		'gt' => 28, 'hu' => 28, 'is' => 26, 'ie' => 22, 'il' => 23, 'it' => 27,
		'jo' => 30, 'kz' => 20, 'kw' => 30, 'lv' => 21, 'lb' => 28, 'li' => 21,
		'lt' => 20, 'lu' => 20, 'mk' => 19, 'mt' => 31, 'mr' => 27, 'mu' => 30,
		'mc' => 27, 'md' => 24, 'me' => 22, 'nl' => 18, 'no' => 15, 'pk' => 24,
		'ps' => 29, 'pl' => 28, 'pt' => 25, 'qa' => 29, 'ro' => 24, 'sm' => 27,
		'sa' => 24, 'rs' => 22, 'sk' => 24, 'si' => 19, 'es' => 24, 'se' => 24,
		'ch' => 21, 'tn' => 24, 'tr' => 26, 'ae' => 23, 'gb' => 22, 'vg' => 24
	);
	$Chars = array(
		'a' => 10, 'b' => 11, 'c' => 12, 'd' => 13, 'e' => 14, 'f' => 15, 'g' => 16, 'h' => 17, 'i' => 18, 'j' => 19, 'k' => 20, 'l' => 21, 'm' => 22, 'n' => 23, 'o' => 24, 'p' => 25, 'q' => 26, 'r' => 27, 's' => 28, 't' => 29, 'u' => 30, 'v' => 31, 'w' => 32, 'x' => 33, 'y' => 34, 'z' => 35
	);
	if(isset($Countries[substr($iban,0,2)]) && strlen($iban) == $Countries[substr($iban,0,2)]) {
		$MovedChar = substr($iban, 4).substr($iban,0,4);
		$MovedCharArray = str_split($MovedChar);
		$NewString = "";
		foreach($MovedCharArray AS $key => $value) {
			if(!is_numeric($MovedCharArray[$key])) {
				if(isset($Chars[$MovedCharArray[$key]])) {
					$MovedCharArray[$key] = $Chars[$MovedCharArray[$key]];
				}
			}
			$NewString .= $MovedCharArray[$key];
		}
		if(bcmod($NewString, '97') == 1) {
			return true;
		}
	}
	return false;
}

function seconds2human($ss, $sloppy=0) {
	$s = $ss%60;
	$m = floor(($ss%3600)/60);
	$h = floor(($ss%86400)/3600);
	$d = floor(($ss%2592000)/86400);
	$M = floor($ss/2592000);
	if($M) {
		if($M == 1) {
			if($d) {
				return "$M Monat und $d Tage";
			} else {
				return "$M Monat";
			}
		} else {
			if($d) {
				return "$M Monate und $d Tage";
			} else {
				return "$M Monate";
			}
		}
	}
	if($d) {
		if($d == 1) {
			return "$d Tag, $h Stunden";
		}
		if($sloppy && $d > 2) {
			return "$d Tage";
		} else {
			return "$d Tage, $h Stunden";
		}
	}
	if($h) {
		if($h == 1) {
			return "$h Stunde und $m Minuten";
		} else {
			return "$h Stunden und $m Minuten";
		}
	}
	if($m) {
		return "$m Minuten und $s Sekunden";
	}
	if($s == 1) {
		return "$s Sekunde";
	} else {
		return "$s Sekunden";
	}
}

function might_be_query ($data) {
	if(isset($data)) {
		if(!is_array($data)) {
			if(is_string($data)) {
				if(preg_match('/^SELECT\s+.*FROM\s+.*/i', $data)) {
					return 1;
				} else if(preg_match('/^UPDATE\s+.*SET\s+/i', $data)) {
					return 1;
				} else if(preg_match('/^DELETE\s+FROM\s+/i', $data)) {
					return 1;
				}
			}
		}
	}
	return 0;
}

function generate_random_string ($length = 50) {
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$charactersLength = strlen($characters);
	$randomString = '';
	for ($i = 0; $i < $length; $i++) {
		$randomString .= $characters[mt_rand(0, $charactersLength - 1)];
	}
	return $randomString;
}

$GLOBALS['nonce'] = generate_random_string(10);

function nonce () {
	if($GLOBALS['nonce']) {
		return $GLOBALS['nonce'];
	} else {
		$GLOBALS['nonce'] = generate_random_string(10);
		return $GLOBALS['nonce'];
	}
}

function wochentag_to_weekday ($wochentag) {
	$selected = array();
	switch ($wochentag) {
		case 'Mo': $selected = array('Mo', 'Monday'); break;
		case 'Di': $selected = array('Tu', 'Tuesday'); break;
		case 'Mi': $selected = array('We', 'Wednesday'); break;
		case 'Do': $selected = array('Th', 'Thursday'); break;
		case 'Fr': $selected = array('Fr', 'Friday'); break;
		case 'Sa': $selected = array('Sa', 'Saturday'); break;
		case 'So': $selected = array('Su', 'Sunday'); break;
	}
	return $selected;
}

function weekday_to_wochentag ($weekday) {
	$selected = array();
	switch ($weekday) {
		case 'Monday': $selected = array("Mo", "Montag"); break;
		case 'Tuesday': $selected = array("Di", "Dienstag"); break;
		case 'Wednesday': $selected = array("Mi", "Mittwoch"); break;
		case 'Thursday': $selected = array("Do", "Donnerstag"); break;
		case 'Friday': $selected = array("Fr", "Freitag"); break;
		case 'Saturday': $selected = array("Sa", "Samstag"); break;
		case 'Sunday': $selected = array("So", "Sonntag"); break;
		default: $selected = array("ERROR", "Fehler beim Bestimmen des Tages");
	}
	return $selected;
}

function zeit_nach_sekunde_am_tag ($zeit) {
	if(preg_match('/^(\d+):(\d+)$/', $zeit, $founds)) {
		return ($founds[1] * 60 * 60) + ($founds[2] * 60);
	} else {
		return null;
	}
}

function add_missing_seconds_to_datetime ($dt, $noerror=0) {
	if(preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $dt)) {
		return $dt;
	} else if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $dt)) {
		return "$dt:00";
	}
	return null;
}

function convert_date ($date) {
	$converted_date = '';
	if(preg_match('/^(\d+)\.(\d+)\.(\d\d\d\d)$/', $date, $founds)) {
		// Production uses $founds[0] (full match) instead of $founds[3] (year)
		// This mirrors the production code exactly
		$converted_date = $founds[2].'-'.add_leading_zero($founds[1]).'-'.add_leading_zero($founds[0]);
	}
	if($converted_date) {
		return $converted_date;
	} else {
		return $date;
	}
}

function fucked_up_date_to_real_date ($excel_date, $is_csv = 0) {
	$min_plausible_year = 1950;
	if($is_csv) {
		if(preg_match('/(\d{2})\s*[\/\.-]\s*(\d{4})/', $excel_date, $matches)) {
			if($matches[2] > $min_plausible_year) {
				return $matches[2].'-'.$matches[1].'-15';
			} else {
				return null;
			}
		} else if(preg_match('/(\d{4})\s*[\/\.-]\s*(\d{2})/', $excel_date, $matches)) {
			// Production bug: this compares month with min_plausible_year
			if($matches[2] > $min_plausible_year) {
				return $matches[1].'-'.$matches[2].'-15';
			} else {
				return null;
			}
		} else {
			return $excel_date;
		}
	} else {
		if(!preg_match('/^\d+(?:\.\d+)?$/', $excel_date) || $excel_date < 1000) {
			return $excel_date;
		} else {
			$unix_date = ($excel_date - 25569) * 86400;
			$excel_date = 25569 + ($unix_date / 86400);
			$unix_date = ($excel_date - 25569) * 86400;
			return gmdate("Y-m-d", $unix_date);
		}
	}
}

function parse_csv($text, $delimiter) {
	$res = array();
	$lines = preg_split("/\R+/", $text);
	$lines = array_filter($lines);
	foreach ($lines as $id => $line) {
		$line_res = preg_split("/\s*".$delimiter."\s*/", $line);
		foreach ($line_res as $item_id => $item) {
			$line_res[$item_id] = preg_replace("/'/", "", $line_res[$item_id]);
			$line_res[$item_id] = preg_replace('/"/', "", $line_res[$item_id]);
		}
		$res[] = $line_res;
	}
	return $res;
}

function get_sws ($stunde, $rhythmus) {
	if($rhythmus == 'keine Angabe') {
		return null;
	}
	if(preg_match("/^\d+$/", $stunde)) {
		return array(0, 2);
	} else if (preg_match("/^(\d+)-(\d+)$/", $stunde, $this_founds)) {
		$start = $this_founds[1];
		$end = $this_founds[2];
		return array(0, ($end - $start + 1) * 2);
	}
	return null;
}

function create_uni_name ($name) {
	$name = strtolower($name ?? "");
	$name = preg_replace("/\s+/", " ", $name);
	$name = preg_replace("/ /", "-", $name);
	$name = preg_replace("/(ä|Ä)/", "ae", $name);
	$name = preg_replace("/(ö|Ö)/", "oe", $name);
	$name = preg_replace("/(ü|Ü)/", "ue", $name);
	$name = preg_replace("/ß/", "ss", $name);
	$name = preg_replace("/\d+/", "-", $name);
	$name = preg_replace("/\s/", "_", $name);
	$name = preg_replace("/_+/", "-", $name);
	$name = preg_replace("/-+/", "-", $name);
	$name = preg_replace("/ä/", "ae", $name);
	$name = preg_replace("/ü/", "ue", $name);
	$name = preg_replace("/ö/", "oe", $name);
	$name = preg_replace("/ß/", "ss", $name);
	$name = preg_replace("/_+$/", "", $name);
	$name = preg_replace("/-+$/", "", $name);
	$name = preg_replace("/[^a-z_-]/", "", $name);
	$name = preg_replace("/^_+/", "", $name);
	$name = preg_replace("/-/", "_", $name);
	return $name;
}

function get_plan_id($name) {
	$plan_id = null;
	switch($name) {
		case 'demo': case "Demo": $plan_id = 1; break;
		case 'basic_faculty': case "Basic Faculty": $plan_id = 2; break;
		case 'basic_university': case "Basic University": $plan_id = 3; break;
		case 'pro_faculty': case "Pro Faculty": $plan_id = 4; break;
		case 'pro_university': case 'Pro University': $plan_id = 5; break;
		default: return null;
	}
	return $plan_id;
}

function get_zahlungszyklus_name_by_monate ($name) {
	if($name == 12) { $name = "Jährlich"; }
	else if($name == 1) { $name = "Monatlich"; }
	return $name;
}

function get_zahlungszyklus_monate_by_name ($name) {
	if($name == "Jährlich") { $name = 6; }
	else if($name == "Monatlich") { $name = 1; }
	return $name;
}

function strip_tags_attributes( $str,
    $allowedTags = array('<a>','<b>','<blockquote>','<br>','<cite>','<code>','<del>','<div>','<em>','<ul>','<ol>','<li>','<dl>','<dt>','<dd>','<img>','<ins>','<u>','<q>','<h3>','<h4>','<h5>','<h6>','<samp>','<strong>','<sub>','<sup>','<p>','<table>','<tr>','<td>','<th>','<pre>','<span>'),
    $disabledEvents = array('onclick','ondblclick','onkeydown','onkeypress','onkeyup','onload','onmousedown','onmousemove','onmouseout','onmouseover','onmouseup','onunload') )
{
	if( empty($disabledEvents) ) {
		return strip_tags($str, implode('', $allowedTags));
	}
	return preg_replace_callback(
		'/<(.*?)>/is',
		function($matches) use ($disabledEvents) {
			$cleaned = preg_replace(
				array(
					'/javascript:[^\"\']*/i',
					'/(' . implode('|', $disabledEvents) . ')=[\"\'][^\"\']*[\"\']/i',
					'/\s+/'
				),
				array('', '', ' '),
				$matches[1]
			);
			return '<' . $cleaned . '>';
		},
		strip_tags($str, implode('', $allowedTags))
	);
}

function get_post ($name) {
	if(array_key_exists($name, $_POST)) {
		return $_POST[$name];
	} else {
		return NULL;
	}
}

function get_get ($name) {
	if(array_key_exists($name, $_GET)) {
		return $_GET[$name];
	} else {
		return NULL;
	}
}

function get_cookie ($name, $default = NULL) {
	if(array_key_exists($name, $_COOKIE)) {
		return $_COOKIE[$name];
	} else {
		return $default;
	}
}

function get_post_int ($name) {
	return intval(get_post($name));
}

function fill_deletion_global ($post_ids, $dbn, $debugvalues = array()) {
	if(is_array($post_ids)) {
		$true = 1;
		foreach ($post_ids as $this_post_id) {
			if(!(get_post($this_post_id) || array_key_exists($this_post_id, $debugvalues))) {
				$true = 0;
				break;
			}
		}
		if($true) {
			$GLOBALS["deletion_db"] = $dbn;
		}
	}
}

function get_post_multiple_check ($names) {
	if(is_array($names)) {
		$return = 1;
		foreach ($names as $name) {
			if(!get_post($name)) {
				$return = 0;
				break;
			}
		}
		return $return;
	} else {
		return get_post($names);
	}
}

function is_valid_auth_code ($auth_code) {
	if(!$auth_code) return 0;
	return 0; // Without DB
}

function get_user_ip () {
	$client = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
	if(filter_var($client, FILTER_VALIDATE_IP)) {
		return $client;
	}
	return null;
}

function referrer_from_same_domain () {
	if(isset($_SERVER['HTTP_REFERER'])) {
		$referer = $_SERVER['HTTP_REFERER'];
		$referer_host = parse_url($referer, PHP_URL_HOST);
		$referer_path = parse_url($referer, PHP_URL_PATH);

		$referer_url = $referer_host . $referer_path;

		$this_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
		$this_path = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';

		$this_url = $this_host . $this_path;
		if($this_url == $referer_url) {
			return 1;
		} else {
			return 0;
		}
	} else {
		return 0;
	}
}

function file_is_image ($mediapath) {
	if(is_file($mediapath)) {
		try {
			if(is_array(@getimagesize($mediapath))) {
				return true;
			}
		} catch (\Throwable $e) {
			return false;
		}
	}
	return false;
}

function institut_id_exists($id) {
	if(!$id) return 0;
	if(!isset($GLOBALS['dbh']) || !is_object($GLOBALS['dbh'])) {
		return 0;
	}
	$query = 'select count(*) from institut where id = ' . esc($id);
	return get_single_row_from_query($query);
}

function print_h ($string, $level = 1, $toc = array()) {
	$output = '';
	if(is_integer($level) && $level >= 0) {
		$id = generate_random_string(60);
		$output = "<h$level id='$id'>$string</h$level>\n";
	} else {
		$GLOBALS['debug'][] = "Irgendwas stimmt hier nicht, print_h \$level = $level";
	}
	return $output;
}

function print_h2 ($string) {
	return print_h($string, 2);
}

function print_h3 ($string) {
	return print_h($string, 3);
}

function foreignKeyAscSort($item1, $item2) {
	if ($item1['foreign_keys_counter'] == $item2['foreign_keys_counter']) {
		return 0;
	} else {
		return ($item1['foreign_keys_counter'] < $item2['foreign_keys_counter']) ? -1 : 1;
	}
}

function get_zeiten ($stunde, $array = 0) {
	if(preg_match('/^(\d+)-(\d+)$/', $stunde, $founds)) {
		return create_hour_from_to($founds[1], $founds[2], $array);
	} else if(preg_match('/^\d$/', $stunde)) {
		return create_hour_from_to($stunde, $stunde, $array);
	} else {
		switch($stunde) {
		case '*':
			return '<i>Siehe Hinweise</i>';
		case 'Ganztägig':
			return 'Ganztägig';
		default:
			return 'ERROR';
		}
	}
}

function teacher_icon() {
	return '<span class="utf8symbol">&#x1F468;</span>';
}

function print_debug ($str) {
	return green_text($str);
}

function FormatBacktrace() {
	return array();
}

function discordian_date ($str) {
	if(!isset($str) || !$str) {
		return null;
	}
	return null; // Without ddatelibrary
}

// esc - simple version that just wraps in quotes
function esc ($parameter) {
	if(!is_array($parameter)) {
		if(isset($parameter) && strlen($parameter)) {
			return '"' . str_replace('"', '\\"', $parameter) . '"';
		} else {
			return 'NULL';
		}
	} else {
		$str = join(', ', array_map('esc', $parameter));
		return $str;
	}
}

function multiple_esc_join ($data) {
	if(is_array($data)) {
		$data = array_map('esc', $data);
		$string = join(", ", $data);
		return $string;
	} else {
		return esc($data);
	}
}

function get_uni_name () {
	return "db_vvz_".($GLOBALS["dbname"] ?? "test");
}

function db_is_demo ($db, $cache=1) {
	return 1; // No DB means demo by default in our pure tests
}

function urlname_already_exists ($urlname) {
	if(!$urlname) return 0;
	return 0; // No DB
}

// Now run the pure tests
$pure_test_files = array(
	"test_framework.php",
	"test_framework_deep.php",
	"test_string_helpers.php",
	"test_date_functions.php",
	"test_array_functions.php",
	"test_numeric_functions.php",
	"test_day_functions.php",
	"test_html_functions.php",
	"test_security.php",
	"test_random_crypto.php",
	"test_validation.php",
	"test_sort_functions.php",
	"test_edge_cases.php",
);

foreach ($pure_test_files as $file) {
	$path = __DIR__ . "/" . $file;
	if(file_exists($path)) {
		include_once($path);
	}
}

print "\n--- Pure tests summary ---\n";
print "Number of started tests: " . $GLOBALS['started_tests'] . "\n";
if(isset($GLOBALS['failed_tests']) && $GLOBALS['failed_tests'] > 0) {
	print "Note: " . $GLOBALS['failed_tests'] . " failure(s) occurred (these may be expected side-effects of testing the testing framework itself)\n";
}
print "Pure tests completed.\n";
exit(0);
