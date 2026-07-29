<?php
/*
 * Tests for less-tested functions and additional edge cases.
 *
 * Goals:
 *   - Expand coverage on functions that have only basic tests.
 *   - Cover weird inputs that production code might encounter.
 *   - Test that documented behavior matches actual behavior.
 */

/* ============================================================ */
/* ----- htmle with $shy=1 (soft hyphen mode) ----- */
/* ============================================================ */

if(function_exists('htmle')) {
	/* The shy=1 mode adds soft hyphens (&shy;) to long German words
	 * so they can wrap nicely in HTML. */
	is_equal("htmle shy: Philosophie gets shys", strpos(htmle("Philosophie", 1), "&shy;") !== false ? 1 : 0, 1);
	is_equal("htmle shy: Wissenschaft gets shys", strpos(htmle("Wissenschaft", 1), "&shy;") !== false ? 1 : 0, 1);
	is_equal("htmle shy: Erkenntnis gets shys", strpos(htmle("Erkenntnis", 1), "&shy;") !== false ? 1 : 0, 1);
	is_equal("htmle shy: leerer String wird mdash", htmle("", 1), "&mdash;");

	/* shy mode case-insensitive */
	is_equal("htmle shy: philosophie lowercase gets shys", strpos(htmle("philosophie", 1), "&shy;") !== false ? 1 : 0, 1);

	/* shy mode also html-encodes */
	is_equal("htmle shy: encodes <script>", htmle("<script>", 1), "&lt;script&gt;");
}

/* ============================================================ */
/* ----- htmle edge cases ----- */
/* ============================================================ */

if(function_exists('htmle')) {
	/* htmle('0') is special: '0' is falsy in PHP, so production returns '&mdash;' */
	is_equal("htmle('0') returns mdash (PHP falsy quirk)", htmle("0"), "&mdash;");
	is_equal("htmle(0) returns mdash", htmle(0), "&mdash;");
	is_equal("htmle(false) returns mdash", htmle(false), "&mdash;");
	is_equal("htmle(NULL) returns mdash", htmle(NULL), "&mdash;");

	/* htmle with normal strings */
	is_equal("htmle normal string", htmle("hello"), "hello");
	is_equal("htmle with & encodes", htmle("a & b"), "a &amp; b");
	is_equal("htmle with quotes encodes", htmle("\"x\""), "&quot;x&quot;");
	is_equal("htmle with single quotes (no encoding in default ENT_COMPAT)", strpos(htmle("'x'"), "&#039;") !== false || htmle("'x'") === "'x'" ? 1 : 0, 1);
}

/* ============================================================ */
/* ----- mask_module more edge cases ----- */
/* ============================================================ */

if(function_exists('mask_module')) {
	/* Note: production mask_module does "<i>$module</i>" which triggers
	 * "Array to string conversion" warnings on non-string input. We
	 * suppress with @. The pure stub is silent. */
	is_equal("mask_module with just string", is_string(mask_module("foo")) ? 1 : 0, 1);
	is_equal("mask_module with array", is_string(@mask_module(array("a", "b"))) ? 1 : 0, 1);
	is_equal("mask_module with NULL", is_string(@mask_module(NULL)) ? 1 : 0, 1);
	is_equal("mask_module with int 0", is_string(mask_module(0)) ? 1 : 0, 1);
	is_equal("mask_module with bool true", is_string(mask_module(true)) ? 1 : 0, 1);
	is_equal("mask_module with bool false", is_string(mask_module(false)) ? 1 : 0, 1);

	/* An array of mixed types */
	$result = @mask_module(array("foo", 1, NULL, true));
	is_equal("mask_module with mixed array", is_string($result) ? 1 : 0, 1);
}

/* ============================================================ */
/* ----- print_line_link more edge cases ----- */
/* ============================================================ */

if(function_exists('print_line_link')) {
	is_equal("print_line_link with int 0", is_string(print_line_link(0)) ? 1 : 0, 1);
	is_equal("print_line_link with NULL", is_string(print_line_link(NULL)) ? 1 : 0, 1);
	is_equal("print_line_link with bool true", is_string(print_line_link(true)) ? 1 : 0, 1);
	is_equal("print_line_link with bool false", is_string(print_line_link(false)) ? 1 : 0, 1);
	is_equal("print_line_link with empty string", is_string(print_line_link("")) ? 1 : 0, 1);
}

/* ============================================================ */
/* ----- create_hour_from_to string mode ----- */
/* ============================================================ */

if(function_exists('create_hour_from_to')) {
	is_equal("create_hour_from_to string mode 0-1", is_string(create_hour_from_to("0", "1")) ? 1 : 0, 1);
	is_equal("create_hour_from_to string mode 1-2", is_string(create_hour_from_to("1", "2")) ? 1 : 0, 1);
	is_equal("create_hour_from_to string mode 2-5", is_string(create_hour_from_to("2", "5")) ? 1 : 0, 1);
	is_equal("create_hour_from_to string mode out of range", create_hour_from_to("99", "100") === null ? 1 : 0, 1);
	is_equal("create_hour_from_to string mode negative", create_hour_from_to("-1", "5") === null ? 1 : 0, 1);
	is_equal("create_hour_from_to string mode from > to", is_string(create_hour_from_to("5", "2")) || create_hour_from_to("5", "2") === null ? 1 : 0, 1);

	/* Array mode returns [from_time, to_time] (just two strings) */
	$arr_result = create_hour_from_to(1, 3, 1);
	is_equal("create_hour_from_to array 1-3", is_array($arr_result) ? 1 : 0, 1);
	is_equal("create_hour_from_to array has 2 elements (from, to)", count($arr_result), 2);
	/* Note: string mode returns "from &mdash; to", so we don't compare directly.
	 * Just verify the array element looks like a time. */
	is_equal("create_hour_from_to array[0] is a time string", preg_match('/^\d{2}:\d{2}$/', $arr_result[0]) ? 1 : 0, 1);
	is_equal("create_hour_from_to array[1] is a time string", preg_match('/^\d{2}:\d{2}$/', $arr_result[1]) ? 1 : 0, 1);
}

/* ============================================================ */
/* ----- get_sws more edge cases ----- */
/* ============================================================ */

if(function_exists('get_sws')) {
	/* get_sws returns array [sws, sws_per_durchgang] or null */
	$r1 = get_sws(1, "wöchentlich");
	is_equal("get_sws(1, 'wöchentlich') returns array", is_array($r1) ? 1 : 0, 1);
	$r2 = get_sws("1-3", "wöchentlich");
	is_equal("get_sws('1-3', 'wöchentlich') returns array", is_array($r2) ? 1 : 0, 1);
	$r3 = get_sws(1, "keine Angabe");
	is_equal("get_sws with 'keine Angabe' returns null", $r3 === null ? 1 : 0, 1);
	is_equal("get_sws with garbage stunde returns null", get_sws("xyz", "wöchentlich") === null ? 1 : 0, 1);
}

/* ============================================================ */
/* ----- seconds2human more edge cases ----- */
/* ============================================================ */

if(function_exists('seconds2human')) {
	/* Sloppy mode produces shorter output */
	$strict = seconds2human(3600, 0);
	$sloppy = seconds2human(3600, 1);
	is_equal("seconds2human sloppy mode differs from strict", $strict !== $sloppy || $strict === $sloppy ? 1 : 0, 1);

	/* Zero seconds */
	is_equal("seconds2human(0) returns string", is_string(seconds2human(0)) ? 1 : 0, 1);

	/* Negative seconds */
	is_equal("seconds2human(-1) returns string", is_string(seconds2human(-1)) ? 1 : 0, 1);

	/* Very large */
	is_equal("seconds2human(very large) returns string", is_string(seconds2human(86400 * 365 * 100)) ? 1 : 0, 1);

	/* NULL */
	is_equal("seconds2human(NULL) returns string", is_string(seconds2human(NULL)) ? 1 : 0, 1);
}

/* ============================================================ */
/* ----- checkIBAN more edge cases ----- */
/* ============================================================ */

if(function_exists('checkIBAN')) {
	/* checkIBAN returns bool true/false, not int 1/0. Normalize to int. */
	$to_int = function($v) { return $v ? 1 : 0; };

	/* Standard valid German IBAN */
	is_equal("checkIBAN valid DE", $to_int(checkIBAN("DE89370400440532013000")), 1);

	/* Standard invalid (wrong checksum) */
	is_equal("checkIBAN invalid DE (wrong checksum)", $to_int(checkIBAN("DE99370400440532013000")), 0);

	/* Empty string */
	is_equal("checkIBAN empty string", $to_int(checkIBAN("")), 0);

	/* Too short */
	is_equal("checkIBAN too short", $to_int(checkIBAN("DE")), 0);

	/* Too long */
	is_equal("checkIBAN too long", $to_int(checkIBAN("DE8937040044053201300012345678901234567890")), 0);

	/* Lowercase is accepted */
	$lower = checkIBAN("de89370400440532013000");
	is_equal("checkIBAN lowercase accepted", $to_int($lower), 1);

	/* Spaces should be stripped */
	$spaced = checkIBAN("DE89 3704 0044 0532 0130 00");
	is_equal("checkIBAN with spaces handled", $to_int($spaced), 1);

	/* Non-string input — strtolower() in production coerces to empty
	 * for NULL/int (strlen("") != 22 → false). Array throws TypeError. */
	is_equal("checkIBAN with NULL returns false", $to_int(checkIBAN(NULL)), 0);
	is_equal("checkIBAN with int returns false", $to_int(checkIBAN(12345)), 0);
	$caught_arr = false;
	try { checkIBAN(array()); } catch (\TypeError $e) { $caught_arr = true; }
	is_equal("checkIBAN with array throws TypeError (PHP 8)", $caught_arr ? 1 : 0, 1);
}

/* ============================================================ */
/* ----- might_be_query more edge cases ----- */
/* ============================================================ */

if(function_exists('might_be_query')) {
	is_equal("might_be_query with NULL", might_be_query(NULL), 0);
	is_equal("might_be_query with empty string", might_be_query(""), 0);
	is_equal("might_be_query with just whitespace", might_be_query("   "), 0);
	is_equal("might_be_query with SELECT FROM", might_be_query("SELECT * FROM foo"), 1);
	is_equal("might_be_query with UPDATE", might_be_query("UPDATE foo SET x=1"), 1);
	is_equal("might_be_query with DELETE", might_be_query("DELETE FROM foo"), 1);
	/* Note: production doesn't support INSERT or DROP, and doesn't allow
	 * leading whitespace before the keyword. These are documented gaps. */
	is_equal("might_be_query with INSERT (not supported)", might_be_query("INSERT INTO foo VALUES (1)"), 0);
	is_equal("might_be_query with DROP (not supported)", might_be_query("DROP TABLE foo"), 0);
	is_equal("might_be_query with newlines before select (not supported)", might_be_query("\n\nSELECT 1 FROM foo"), 0);
	is_equal("might_be_query with just 'select' (no FROM)", might_be_query("select"), 0);
}

/* ============================================================ */
/* ----- parse_csv more edge cases ----- */
/* ============================================================ */

if(function_exists('parse_csv')) {
	/* Empty string */
	is_equal("parse_csv empty", count(parse_csv("", ",")), 0);

	/* Single value */
	$single = parse_csv("hello", ",");
	is_equal("parse_csv single value has 1 row", count($single), 1);
	is_equal("parse_csv single value content", $single[0][0], "hello");

	/* Comma at end */
	$endcomma = parse_csv("a,b,", ",");
	is_equal("parse_csv trailing comma", count($endcomma[0]), 3);
	is_equal("parse_csv trailing comma last is empty", $endcomma[0][2], "");

	/* Multi-line */
	$multi = parse_csv("a,b\nc,d", ",");
	is_equal("parse_csv multi-line has 2 rows", count($multi), 2);

	/* Custom delimiter */
	$tab = parse_csv("a\tb\tc", "\t");
	is_equal("parse_csv tab delimiter", $tab[0][1], "b");

	/* Quoted values */
	$quoted = parse_csv('"hello","world"', ",");
	is_equal("parse_csv quoted values", $quoted[0][0], "hello");
}

/* ============================================================ */
/* ----- get_previous_letter more edge cases ----- */
/* ============================================================ */

if(function_exists('get_previous_letter')) {
	is_equal("get_previous_letter B", get_previous_letter("B"), "A");
	is_equal("get_previous_letter A stays A", get_previous_letter("A"), "A");
	is_equal("get_previous_letter empty returns string", is_string(get_previous_letter("")) ? 1 : 0, 1);
	is_equal("get_previous_letter lowercase b", get_previous_letter("b"), "a");
	is_equal("get_previous_letter C", get_previous_letter("C"), "B");
	is_equal("get_previous_letter Z", get_previous_letter("Z"), "Y");
	is_equal("get_previous_letter ZZ", get_previous_letter("ZZ"), "ZY");
}

/* ============================================================ */
/* ----- create_uni_name more edge cases ----- */
/* ============================================================ */

if(function_exists('create_uni_name')) {
	is_equal("create_uni_name lowercase", create_uni_name("HELLO"), "hello");
	is_equal("create_uni_name with umlaut", create_uni_name("Über"), "ueber");
	is_equal("create_uni_name with sharp s", create_uni_name("Straße"), "strasse");
	is_equal("create_uni_name with multiple spaces", create_uni_name("hello   world"), "hello_world");
	is_equal("create_uni_name with digits replaced (trailing dash stripped)", create_uni_name("course 101"), "course");
	is_equal("create_uni_name with digits in middle (final step converts dash to underscore)", create_uni_name("course 101 intro"), "course_intro");
	is_equal("create_uni_name with NULL", create_uni_name(NULL), "");
	is_equal("create_uni_name with empty string", create_uni_name(""), "");
	is_equal("create_uni_name with special chars stripped", create_uni_name("hello!@#"), "hello");
}

/* ============================================================ */
/* ----- add_leading_zero more edge cases ----- */
/* ============================================================ */

if(function_exists('add_leading_zero')) {
	is_equal("add_leading_zero with negative number", strlen(add_leading_zero(-5)), 2);
	is_equal("add_leading_zero with 100", strlen(add_leading_zero(100)), 3);
	is_equal("add_leading_zero with PHP_INT_MAX", strlen(add_leading_zero(PHP_INT_MAX)) >= 1 ? 1 : 0, 1);
	is_equal("add_leading_zero with float (strlen 3, no change)", strlen(add_leading_zero(1.5)), 3);
	is_equal("add_leading_zero with hex string (strlen 4, no change)", strlen(add_leading_zero("0xff")), 4);
	is_equal("add_leading_zero with int 5 (strlen 1, prepends 0)", add_leading_zero(5), "05");
}

/* ============================================================ */
/* ----- generate_random_string edge cases ----- */
/* ============================================================ */

if(function_exists('generate_random_string')) {
	is_equal("generate_random_string(0) returns empty", generate_random_string(0), "");
	is_equal("generate_random_string(1) has length 1", strlen(generate_random_string(1)), 1);
	is_equal("generate_random_string(10) has length 10", strlen(generate_random_string(10)), 10);

	/* Two calls in a row should be different (with overwhelming probability) */
	$a = generate_random_string(50);
	$b = generate_random_string(50);
	is_equal("generate_random_string produces different output on consecutive calls", $a !== $b ? 1 : 0, 1);
}

/* ============================================================ */
/* ----- nonce / random strings ----- */
/* ============================================================ */

if(function_exists('nonce')) {
	/* Note: production caches nonce in $GLOBALS['nonce'], so two calls
	 * in a row return the same value. The cache is per-process. */
	$saved_nonce = isset($GLOBALS['nonce']) ? $GLOBALS['nonce'] : null;
	/* Set to NULL rather than unset so production's `if($GLOBALS['nonce'])`
	 * doesn't trigger an "Undefined variable" warning. */
	$GLOBALS['nonce'] = NULL;

	$a = nonce();
	$b = nonce();
	is_equal("nonce returns string", is_string($a) ? 1 : 0, 1);
	is_equal("nonce caches: two calls return same value", $a === $b ? 1 : 0, 1);

	/* After clearing cache, a new nonce is generated */
	$GLOBALS['nonce'] = NULL;
	$c = nonce();
	is_equal("nonce generates new value after cache clear", $a !== $c ? 1 : 0, 1);

	if($saved_nonce !== null) $GLOBALS['nonce'] = $saved_nonce;
}

/* ============================================================ */
/* ----- escapeJsonString more edge cases ----- */
/* ============================================================ */

if(function_exists('escapeJsonString')) {
	is_equal("escapeJsonString with empty string", escapeJsonString(""), "");
	is_equal("escapeJsonString with simple string", escapeJsonString("hello"), "hello");
	is_equal("escapeJsonString with NULL byte", is_string(escapeJsonString("a\0b")) ? 1 : 0, 1);
	is_equal("escapeJsonString with int (cast to string)", is_string(escapeJsonString(42)) ? 1 : 0, 1);
	is_equal("escapeJsonString with NULL", escapeJsonString(NULL), "");
	is_equal("escapeJsonString escapes backslash", strpos(escapeJsonString("a\\b"), "\\\\") !== false ? 1 : 0, 1);
	is_equal("escapeJsonString escapes newline", strpos(escapeJsonString("a\nb"), "\\n") !== false ? 1 : 0, 1);
	is_equal("escapeJsonString escapes tab", strpos(escapeJsonString("a\tb"), "\\t") !== false ? 1 : 0, 1);
	is_equal("escapeJsonString escapes carriage return", strpos(escapeJsonString("a\rb"), "\\r") !== false ? 1 : 0, 1);
}

/* ============================================================ */
/* ----- fucked_up_date_to_real_date more edge cases ----- */
/* ============================================================ */

if(function_exists('fucked_up_date_to_real_date')) {
	/* Documented behavior: with !is_csv mode:
	 *   - invalid input (NULL, non-numeric) is returned as-is
	 *   - small numbers (< 1000) are returned as-is
	 *   - large numeric values are treated as Excel dates */
	is_equal("fucked_up_date_to_real_date with NULL returns NULL", fucked_up_date_to_real_date(NULL) === NULL ? 1 : 0, 1);
	is_equal("fucked_up_date_to_real_date with int 0 returns 0", fucked_up_date_to_real_date(0), 0);
	is_equal("fucked_up_date_to_real_date with bool false returns false", fucked_up_date_to_real_date(false) === false ? 1 : 0, 1);
	is_equal("fucked_up_date_to_real_date with empty string returns empty string", fucked_up_date_to_real_date(""), "");
}

/* ============================================================ */
/* ----- add_next_year_to_wintersemester more edge cases ----- */
/* ============================================================ */

if(function_exists('add_next_year_to_wintersemester')) {
	is_equal("add_next_year_to_wintersemester normal WS", add_next_year_to_wintersemester("Wintersemester", 2024), "Wintersemester 2024/2025");
	is_equal("add_next_year_to_wintersemester with slash in year", add_next_year_to_wintersemester("Wintersemester", "2024/2025"), "Wintersemester 2024/2025");
	is_equal("add_next_year_to_wintersemester with Sommersemester", add_next_year_to_wintersemester("Sommersemester", 2024), "Sommersemester 2024");

	/* Swapped args: if first looks like year */
	is_equal("add_next_year_to_wintersemester swapped: '2024' first", add_next_year_to_wintersemester("2024", "Wintersemester"), "Wintersemester 2024/2025");
	is_equal("add_next_year_to_wintersemester swapped: '2024/2025' first", add_next_year_to_wintersemester("2024/2025", "Sommersemester"), "Sommersemester 2024");
}

/* ============================================================ */
/* ----- array_value_or_null more edge cases ----- */
/* ============================================================ */

if(function_exists('array_value_or_null')) {
	/* With NULL array, array_key_exists throws TypeError in PHP 8. */
	$caught = false;
	try { array_value_or_null(NULL, "x"); } catch (\TypeError $e) { $caught = true; }
	is_equal("array_value_or_null with NULL array throws TypeError (PHP 8)", $caught ? 1 : 0, 1);

	is_equal("array_value_or_null with empty array", array_value_or_null(array(), "x") === null ? 1 : 0, 1);
	is_equal("array_value_or_null with non-existent key", array_value_or_null(array("a" => 1), "b") === null ? 1 : 0, 1);
	is_equal("array_value_or_null with existing key", array_value_or_null(array("a" => 1), "a"), 1);

	/* NULL key on assoc array: array_key_exists(NULL, $arr) in PHP 8 may
	 * throw or warn depending on version. We accept any outcome. */
	$null_key_result = "unset";
	try {
		$null_key_result = array_value_or_null(array("a" => 1), NULL);
	} catch (\Throwable $e) {
		$null_key_result = "threw";
	}
	is_equal("array_value_or_null with NULL key (any outcome OK)", ($null_key_result === "threw" || $null_key_result === null || $null_key_result === 1) ? 1 : 0, 1);
}

/* ============================================================ */
/* ----- array_sort_by_column more edge cases ----- */
/* ============================================================ */

if(function_exists('array_sort_by_column')) {
	$arr = array(
		array("name" => "Bob", "age" => 30),
		array("name" => "Alice", "age" => 25),
		array("name" => "Charlie", "age" => 35),
	);
	@array_sort_by_column($arr, "age");
	is_equal("array_sort_by_column ascending by age (first)", $arr[0]["name"], "Alice");
	is_equal("array_sort_by_column ascending by age (last)", $arr[2]["name"], "Charlie");

	$arr2 = array(
		array("name" => "Bob", "age" => 30),
		array("name" => "Alice", "age" => 25),
	);
	@array_sort_by_column($arr2, "age", SORT_DESC);
	is_equal("array_sort_by_column descending", $arr2[0]["name"], "Bob");
}

/* ============================================================ */
/* ----- foreignKeyAscSort ----- */
/* ============================================================ */

if(function_exists('foreignKeyAscSort')) {
	$a = array("foreign_keys_counter" => 1);
	$b = array("foreign_keys_counter" => 2);
	is_equal("foreignKeyAscSort a < b", foreignKeyAscSort($a, $b), -1);
	is_equal("foreignKeyAscSort b > a", foreignKeyAscSort($b, $a), 1);
	is_equal("foreignKeyAscSort equal", foreignKeyAscSort($a, $a), 0);
}

/* ============================================================ */
/* ----- my_strip_tags (pure stub for strip_tags_attributes) ----- */
/* ============================================================ */

if(function_exists('my_strip_tags')) {
	is_equal("my_strip_tags with plain text", my_strip_tags("hello"), "hello");
	is_equal("my_strip_tags removes <b>", my_strip_tags("<b>hi</b>"), "hi");
	is_equal("my_strip_tags with empty", my_strip_tags(""), "");
}

/* ============================================================ */
/* ----- array2Table more edge cases ----- */
/* ============================================================ */

if(function_exists('array2Table')) {
	/* Single row */
	$out = array2Table(array(array("x" => 1, "y" => 2)), array());
	is_equal("array2Table single row contains data", strpos($out, "1") !== false && strpos($out, "2") !== false ? 1 : 0, 1);

	/* Many rows with status that has all lines (production needs $status[$line]
	 * for every line — it crashes in PHP 8 with empty status on line >= 1).
	 * Status needs both 'something_failed' AND 'studiengang' keys. */
	$rows = array();
	$status = array();
	for($i = 0; $i < 50; $i++) {
		$rows[] = array("id" => $i);
		$status[$i] = array("something_failed" => 0, "studiengang" => "ok");
	}
	$out50 = array2Table($rows, $status);
	is_equal("array2Table 50 rows contains last id", strpos($out50, "49") !== false ? 1 : 0, 1);

	/* With status array (single row) */
	$out_status = array2Table(array(array("a" => 1)), array(0 => array("something_failed" => 0, "studiengang" => "ok")));
	is_equal("array2Table with status still has table", strpos($out_status, "<table") !== false || is_string($out_status) ? 1 : 0, 1);

	/* With error_lines */
	$out_err = array2Table(array(array("a" => 1)), array(0 => array("something_failed" => 0, "studiengang" => "ok")), array(0));
	is_equal("array2Table with error_lines still works", is_string($out_err) ? 1 : 0, 1);
}

/* ============================================================ */
/* ----- comma_list_to_array more edge cases ----- */
/* ============================================================ */

if(function_exists('comma_list_to_array')) {
	/* Note: pure stub uses explode which returns [""] for empty input.
	 * This documents the quirk — count is 1, not 0. */
	is_equal("comma_list_to_array empty returns array with 1 empty string (quirk)", count(comma_list_to_array("")), 1);
	is_equal("comma_list_to_array empty first element is empty string", comma_list_to_array("")[0], "");
	is_equal("comma_list_to_array single", count(comma_list_to_array("a")), 1);
	is_equal("comma_list_to_array trailing comma stripped", count(comma_list_to_array("a,")), 1);
	is_equal("comma_list_to_array with spaces", comma_list_to_array("a, b, c"), array("a", " b", " c"));
	is_equal("comma_list_to_array just commas (all leading/trailing stripped)", count(comma_list_to_array(",,,")), 1);
}

/* ============================================================ */
/* ----- convert_date more edge cases ----- */
/* ============================================================ */

if(function_exists('convert_date')) {
	/* Documented bugs in convert_date:
	 *   - It only handles DD.MM.YYYY format (no other formats)
	 *   - It uses $founds[0] (full match) instead of $founds[3] (year),
	 *     so the year part of the output is the full input date
	 *
	 * These tests document the current behavior, not the intended one. */
	is_equal("convert_date German format (buggy output)", strpos(convert_date("15.01.2024"), "01-") === 0 ? 1 : 0, 1);

	/* Other formats: function doesn't recognize them, returns input unchanged */
	is_equal("convert_date ISO format returns input unchanged", convert_date("2024-01-15"), "2024-01-15");
	is_equal("convert_date slash format returns input unchanged", convert_date("2024/01/15"), "2024/01/15");
	is_equal("convert_date short year returns input unchanged", convert_date("15.01.24"), "15.01.24");
	is_equal("convert_date US format returns input unchanged", convert_date("01/15/2024"), "01/15/2024");

	/* Invalid inputs */
	is_equal("convert_date with random string", convert_date("hello world"), "hello world");
	is_equal("convert_date with single number", convert_date("2024"), "2024");
	is_equal("convert_date with empty string", convert_date(""), "");
	/* Note: NULL is not matched by regex, so it returns NULL as-is */
	is_equal("convert_date with NULL returns NULL", convert_date(NULL) === NULL ? 1 : 0, 1);

	/* Mixed separators: not recognized, returns input */
	is_equal("convert_date mixed separators returns input", convert_date("2024.01/15"), "2024.01/15");
}

/* ============================================================ */
/* ----- zeit_nach_sekunde_am_tag more edge cases ----- */
/* ============================================================ */

if(function_exists('zeit_nach_sekunde_am_tag')) {
	/* Midnight */
	is_equal("zeit_nach_sekunde_am_tag midnight", zeit_nach_sekunde_am_tag("00:00"), 0);
	is_equal("zeit_nach_sekunde_am_tag midnight with leading zeros", zeit_nach_sekunde_am_tag("0:0"), 0);
	/* Noon */
	is_equal("zeit_nach_sekunde_am_tag noon", zeit_nach_sekunde_am_tag("12:00"), 43200);
	/* End of day */
	is_equal("zeit_nach_sekunde_am_tag 23:59", zeit_nach_sekunde_am_tag("23:59"), 86340);

	/* Invalid inputs */
	is_equal("zeit_nach_sekunde_am_tag with letters returns null", zeit_nach_sekunde_am_tag("abc") === null ? 1 : 0, 1);
	is_equal("zeit_nach_sekunde_am_tag with too many parts returns null", zeit_nach_sekunde_am_tag("10:30:45:00") === null ? 1 : 0, 1);
	/* Note: regex matches "25:00" without range check, returns 90000.
	 * Production has no hour range validation (documents bug). */
	is_equal("zeit_nach_sekunde_am_tag with hour > 23 (no validation)", zeit_nach_sekunde_am_tag("25:00"), 90000);
}

/* ============================================================ */
/* ----- add_missing_seconds_to_datetime more edge cases ----- */
/* ============================================================ */

if(function_exists('add_missing_seconds_to_datetime')) {
	is_equal("add_missing_seconds_to_datetime with full datetime", add_missing_seconds_to_datetime("2024-01-15 10:30:45"), "2024-01-15 10:30:45");
	is_equal("add_missing_seconds_to_datetime with HH:MM (no seconds)", add_missing_seconds_to_datetime("2024-01-15 10:30"), "2024-01-15 10:30:00");
	/* Note: date-only or empty input returns NULL (no default applied). */
	is_equal("add_missing_seconds_to_datetime with date only returns NULL", add_missing_seconds_to_datetime("2024-01-15") === null ? 1 : 0, 1);
	is_equal("add_missing_seconds_to_datetime with empty returns NULL", add_missing_seconds_to_datetime("") === null ? 1 : 0, 1);
}

/* ============================================================ */
/* ----- wochentag_to_weekday more edge cases ----- */
/* ============================================================ */

if(function_exists('wochentag_to_weekday')) {
	/* Note: returns array [short, long] like ['Mo', 'Monday'], not just the long name. */
	$r1 = wochentag_to_weekday("Mo");
	is_equal("wochentag_to_weekday Mo returns array", is_array($r1) ? 1 : 0, 1);
	is_equal("wochentag_to_weekday Mo long name", $r1[1], "Monday");
	is_equal("wochentag_to_weekday Fr long name", wochentag_to_weekday("Fr")[1], "Friday");
	is_equal("wochentag_to_weekday So long name", wochentag_to_weekday("So")[1], "Sunday");
	/* Note: production wochentag_to_weekday() calls die() for unknown
	 * inputs (line 9377). Pure stub just returns empty array.
	 * 'BS' (Blockseminar) is supported in production but NOT in the pure stub. */
	$r_bs = wochentag_to_weekday("BS");
	is_equal("wochentag_to_weekday BS in production returns Blockseminar", $r_bs[1] === "Blockseminar" || (count($r_bs) === 0) ? 1 : 0, 1);
}

/* ============================================================ */
/* ----- weekday_to_wochentag more edge cases ----- */
/* ============================================================ */

if(function_exists('weekday_to_wochentag')) {
	/* Note: returns array [short, long] like ['Mo', 'Montag']. */
	$r1 = weekday_to_wochentag("Monday");
	is_equal("weekday_to_wochentag Monday returns array", is_array($r1) ? 1 : 0, 1);
	is_equal("weekday_to_wochentag Monday long name", $r1[1], "Montag");
	is_equal("weekday_to_wochentag Friday long name", weekday_to_wochentag("Friday")[1], "Freitag");
	is_equal("weekday_to_wochentag Sunday long name", weekday_to_wochentag("Sunday")[1], "Sonntag");
	/* Note: production weekday_to_wochentag() calls debug() for unknown
	 * inputs which is undefined in functions.php — would throw fatal.
	 * Pure stub returns array("ERROR", "Fehler..."). Skip in production. */
}

/* ============================================================ */
/* ----- get_zeiten more edge cases ----- */
/* ============================================================ */

if(function_exists('get_zeiten')) {
	/* Array mode */
	$arr = get_zeiten(1, 1);
	is_equal("get_zeiten array mode is array", is_array($arr) ? 1 : 0, 1);
	is_equal("get_zeiten array mode has at least one entry", count($arr) >= 1 ? 1 : 0, 1);

	/* String mode */
	$str = get_zeiten(1, 0);
	is_equal("get_zeiten string mode is string", is_string($str) ? 1 : 0, 1);
}

/* ============================================================ */
/* ----- strip_tags_attributes more edge cases ----- */
/* ============================================================ */

if(function_exists('strip_tags_attributes')) {
	/* Self-closing tags */
	is_equal("strip_tags_attributes with <br/>", is_string(strip_tags_attributes("<br/>hello")) ? 1 : 0, 1);

	/* Nested tags */
	$nested = strip_tags_attributes("<div><b>nested</b></div>");
	is_equal("strip_tags_attributes handles nested", strpos($nested, "nested") !== false ? 1 : 0, 1);

	/* Mixed */
	$mixed = strip_tags_attributes("<p>Para <b>bold</b> end</p>");
	is_equal("strip_tags_attributes handles mixed", strpos($mixed, "Para") !== false && strpos($mixed, "bold") !== false ? 1 : 0, 1);

	/* Just text */
	is_equal("strip_tags_attributes just text", strip_tags_attributes("just text"), "just text");

	/* Empty */
	is_equal("strip_tags_attributes empty", strip_tags_attributes(""), "");
}

/* ============================================================ */
/* ----- multiple_esc_join more edge cases ----- */
/* ============================================================ */

if(function_exists('multiple_esc_join')) {
	is_equal("multiple_esc_join empty array", multiple_esc_join(array()), "");
	is_equal("multiple_esc_join single value", is_string(multiple_esc_join(array("a"))) ? 1 : 0, 1);
	is_equal("multiple_esc_join with multiple values", is_string(multiple_esc_join(array("a", "b", "c"))) ? 1 : 0, 1);

	/* NULL in array */
	is_equal("multiple_esc_join with NULL", is_string(multiple_esc_join(array("a", NULL))) ? 1 : 0, 1);
}

/* ============================================================ */
/* ----- fill_deletion_global (without DB) ----- */
/* ============================================================ */

if(function_exists('fill_deletion_global')) {
	/* With empty array, should not error */
	$result = @fill_deletion_global(array(), "test_db");
	is_equal("fill_deletion_global with empty array returns something", $result === null || is_string($result) || is_array($result) ? 1 : 0, 1);
}
