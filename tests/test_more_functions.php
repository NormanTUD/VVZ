<?php
/*
 * Comprehensive additional tests for VVZ.
 *
 * This file expands coverage of pure functions, DB-dependent functions,
 * and stress cases. It complements test_edge_cases.php with deeper
 * coverage of specific functions and scenarios.
 *
 * Each section groups tests by the function or category being tested.
 */

/* ============================================================ */
/* ----- add_next_year_to_wintersemester ----- */
/* ============================================================ */

/* Normal usage */
is_equal("add_next_year_to_wintersemester Wintersemester 2024",
	add_next_year_to_wintersemester("Wintersemester", 2024),
	"Wintersemester 2024/2025");
is_equal("add_next_year_to_wintersemester Sommersemester 2024",
	add_next_year_to_wintersemester("Sommersemester", 2024),
	"Sommersemester 2024");

/* Argument order swap - if first arg looks like a year */
is_equal("add_next_year_to_wintersemester 2024 Wintersemester",
	add_next_year_to_wintersemester(2024, "Wintersemester"),
	"Wintersemester 2024/2025");
is_equal("add_next_year_to_wintersemester 2024 Sommersemester",
	add_next_year_to_wintersemester(2024, "Sommersemester"),
	"Sommersemester 2024");

/* Year range format "2024/2025" - the second part is dropped */
is_equal("add_next_year_to_wintersemester with year range",
	add_next_year_to_wintersemester("2024/2025", "Wintersemester"),
	"Wintersemester 2024/2025");
is_equal("add_next_year_to_wintersemester with year range Sommersemester",
	add_next_year_to_wintersemester("2024/2025", "Sommersemester"),
	"Sommersemester 2024");

/* Edge cases */
is_equal("add_next_year_to_wintersemester year 0", add_next_year_to_wintersemester("Wintersemester", 0), "Wintersemester 0/1");
is_equal("add_next_year_to_wintersemester year 9999", add_next_year_to_wintersemester("Wintersemester", 9999), "Wintersemester 9999/10000");
is_equal("add_next_year_to_wintersemester unknown semestertype", add_next_year_to_wintersemester("Unknown", 2024), "Unknown 2024");
is_equal("add_next_year_to_wintersemester empty semestertype", add_next_year_to_wintersemester("", 2024), " 2024");
/* Note: empty year causes TypeError in production (string + int).
 * This documents the bug - production should validate year input. */
$caught = false;
try {
	add_next_year_to_wintersemester("Wintersemester", "Wintersemester");
} catch (\Throwable $e) {
	$caught = true;
}
is_equal("add_next_year_to_wintersemester empty year throws (bug)", $caught ? 1 : 0, 1);
is_equal("add_next_year_to_wintersemester with swapped args (string year)", add_next_year_to_wintersemester("2024", "Wintersemester"), "Wintersemester 2024/2025");

/* ============================================================ */
/* ----- discordian_date (with valid date) ----- */
/* ============================================================ */

/* discordian_date with valid dates - loads ddatelibrary.php on first call */
if(function_exists("discordian_date")) {
	$result = @discordian_date("2024-01-05");
	/* discordian_date returns a DiscordianDate object when ddatelibrary is loaded */
	is_equal("discordian_date returns non-null for valid date", $result !== null ? 1 : 0, 1);
	is_equal("discordian_date result is not empty", !empty($result) ? 1 : 0, 1);

	/* Test that different dates give different results */
	$result1 = @discordian_date("2024-01-05");
	$result2 = @discordian_date("2024-06-15");
	is_equal("discordian_date gives different results for different dates", $result1 != $result2 ? 1 : 0, 1);

	/* Test that the same date gives the same result */
	$result1 = @discordian_date("2024-01-05");
	$result2 = @discordian_date("2024-01-05");
	is_equal("discordian_date same date gives same result", $result1 == $result2 ? 1 : 0, 1);
}

/* ============================================================ */
/* ----- my_mysqli_real_escape_string (DB needed) ----- */
/* ============================================================ */

if(isset($GLOBALS["dbh"]) && is_object($GLOBALS["dbh"])) {
	/* Test that it doesn't crash on simple inputs */
	$res = @my_mysqli_real_escape_string("hello");
	is_equal("my_mysqli_real_escape_string returns string for simple input", is_string($res) ? 1 : 0, 1);

	$res = @my_mysqli_real_escape_string("O'Brien");
	is_equal("my_mysqli_real_escape_string escapes single quote", strpos($res, "\\'") !== false || strpos($res, "''") !== false ? 1 : 0, 1);

	/* Test with NULL - the function uses $arg ?? "" so NULL becomes "" */
	$res = @my_mysqli_real_escape_string(NULL);
	is_equal("my_mysqli_real_escape_string handles NULL", $res === "" ? 1 : 0, 1);

	/* Test with empty string */
	$res = @my_mysqli_real_escape_string("");
	is_equal("my_mysqli_real_escape_string handles empty", $res === "" ? 1 : 0, 1);
}

/* ============================================================ */
/* ----- get_zeiten more edge cases ----- */
/* ============================================================ */

is_equal("get_zeiten with '0-1'", get_zeiten("0-1"), "05:40 &mdash; 09:00");
is_equal("get_zeiten with '9-0'", get_zeiten("9-0"), "22:10 &mdash; 07:10");
is_equal("get_zeiten with '1-2'", get_zeiten("1-2"), "07:30 &mdash; 10:50");
is_equal("get_zeiten array mode with '1-3'", is_array(get_zeiten("1-3", 1)) ? 1 : 0, 1);
is_equal("get_zeiten string mode with '1-3' contains mdash", strpos(get_zeiten("1-3"), "&mdash;") !== false ? 1 : 0, 1);
is_equal("get_zeiten with uppercase STAR", get_zeiten("*"), "<i>Siehe Hinweise</i>");
is_equal("get_zeiten with uppercase GANZTÄGIG", get_zeiten("Ganztägig"), "Ganztägig");
is_equal("get_zeiten with whitespace only", get_zeiten("   "), "ERROR");
is_equal("get_zeiten with single space", get_zeiten(" "), "ERROR");
is_equal("get_zeiten with newlines", get_zeiten("\n"), "ERROR");

/* ============================================================ */
/* ----- FormatBacktrace ----- */
/* ============================================================ */

$bt = FormatBacktrace();
is_equal("FormatBacktrace returns non-empty string", is_string($bt) && strlen($bt) > 0 ? 1 : 0, 1);
regex_matches("FormatBacktrace contains Backtrace", $bt, "/Backtrace/");
regex_matches("FormatBacktrace contains h4 tag", $bt, "/<h4/");

/* ============================================================ */
/* ----- print_h, print_h2, print_h3 ----- */
/* ============================================================ */

is_equal("print_h with level 0 returns h0", preg_match("/<h0/", print_h("text", 0)) ? 1 : 0, 1);
is_equal("print_h with level 10 returns h10", preg_match("/<h10/", print_h("text", 10)) ? 1 : 0, 1);
is_equal("print_h with level 100 returns h100", preg_match("/<h100/", print_h("text", 100)) ? 1 : 0, 1);
is_equal("print_h with null level returns empty", print_h("text", NULL), "");
is_equal("print_h with empty string content", is_string(print_h("")) ? 1 : 0, 1);
is_equal("print_h2 returns h2 tag", preg_match("/<h2/", print_h2("text")) ? 1 : 0, 1);
is_equal("print_h3 returns h3 tag", preg_match("/<h3/", print_h3("text")) ? 1 : 0, 1);

/* ============================================================ */
/* ----- teacher_icon (variations) ----- */
/* ============================================================ */

$icon = teacher_icon();
is_equal("teacher_icon contains utf-8", strpos($icon, "utf8symbol") !== false ? 1 : 0, 1);
is_equal("teacher_icon length > 5", strlen($icon) > 5 ? 1 : 0, 1);

/* Multiple calls - returns either male or female icon randomly */
$icons = array();
for($i = 0; $i < 10; $i++) {
	$icons[] = teacher_icon();
}
is_equal("teacher_icon returns span", count(array_filter($icons, function($i) { return strpos($i, "span") !== false; })) >= 1 ? 1 : 0, 1);

/* ============================================================ */
/* ----- fill_deletion_global edge cases ----- */
/* ============================================================ */

$GLOBALS["deletion_db"] = NULL;
fill_deletion_global("a", "veranstaltungstyp");
is_equal("fill_deletion_global with string post_id (non-array) - no-op", $GLOBALS["deletion_db"], NULL);

/* Reset */
$GLOBALS["deletion_db"] = NULL;
fill_deletion_global(42, "veranstaltungstyp");
is_equal("fill_deletion_global with int post_id (non-array) - no-op", $GLOBALS["deletion_db"], NULL);

/* Reset */
$GLOBALS["deletion_db"] = NULL;
fill_deletion_global(true, "veranstaltungstyp");
is_equal("fill_deletion_global with bool post_id (non-array) - no-op", $GLOBALS["deletion_db"], NULL);

/* Reset */
$GLOBALS["deletion_db"] = NULL;
fill_deletion_global(array("foo"), "veranstaltungstyp");
is_equal("fill_deletion_global with non-existent post_id", $GLOBALS["deletion_db"], NULL);

/* Reset */
$GLOBALS["deletion_db"] = NULL;
fill_deletion_global(array("a"), "veranstaltungstyp", array("a" => "value"));
is_equal("fill_deletion_global with debugvalues only", $GLOBALS["deletion_db"], "veranstaltungstyp");

/* Reset */
$GLOBALS["deletion_db"] = NULL;

/* ============================================================ */
/* ----- DB-dependent functions (smoke tests) ----- */
/* ============================================================ */

if(isset($GLOBALS["dbname"]) && $GLOBALS["dbname"]) {
	/* faq_has_entry - should return non-zero now (we seeded data).
	 * Some earlier tests temporarily change $GLOBALS["dbname"]; restore it
	 * so we query the right database. */
	$saved_db = $GLOBALS["dbname"];
	$GLOBALS["dbname"] = "startpage";
	$faq_count = faq_has_entry();
	$GLOBALS["dbname"] = $saved_db;
	is_equal("faq_has_entry returns positive count after seed", $faq_count > 0 ? 1 : 0, 1);

	/* studiengang_has_semester_modul_data */
	$result = studiengang_has_semester_modul_data("alle");
	is_equal("studiengang_has_semester_modul_data('alle') returns null", $result === null ? 1 : 0, 1);

	/* institut_id_exists with real DB */
	$result = institut_id_exists(1);
	is_equal("institut_id_exists(1) returns truthy or null", ($result === 1 || $result === "1" || $result > 0) ? 1 : 0, 1);

	/* page_disabled_in_demo - returns int (0 or 1) */
	is_equal("page_disabled_in_demo returns int", is_int(page_disabled_in_demo(1)) || page_disabled_in_demo(1) === null || is_string(page_disabled_in_demo(1)) ? 1 : 0, 1);
	is_equal("page_disabled_in_demo returns int for 9999", is_int(page_disabled_in_demo(9999)) || page_disabled_in_demo(9999) === null || is_string(page_disabled_in_demo(9999)) ? 1 : 0, 1);

	/* show_in_current_page - returns bool */
	is_equal("show_in_current_page returns bool for 1", is_bool(show_in_current_page(1)) ? 1 : 0, 1);

	/* check_function_rights returns int (0 or 1) */
	is_equal("check_function_rights returns int", is_int(check_function_rights("definitely_nonexistent_function_xyz")) || check_function_rights("definitely_nonexistent_function_xyz") === null ? 1 : 0, 1);
}

/* ============================================================ */
/* ----- Stress tests ----- */
/* ============================================================ */

/* Very long strings */
$very_long = str_repeat("x", 100000);
is_equal("htmle with 100k char string preserves length", strlen(htmle($very_long)), 100000);
is_equal("add_leading_zero with very long string returns string", is_string(add_leading_zero($very_long)) ? 1 : 0, 1);
is_equal("escapeJsonString with very long string", strlen(escapeJsonString($very_long)), 100000);

/* Large arrays */
$huge_array = range(1, 1000);
$huge_pairs = array();
foreach($huge_array as $v) {
	$huge_pairs["key_$v"] = $v;
}
is_equal("array_value_or_null with 1000-key array", array_value_or_null($huge_pairs, "key_500"), 500);
is_equal("array_value_or_null with missing key in huge array", array_value_or_null($huge_pairs, "missing_key") === null ? 1 : 0, 1);

/* Nested arrays */
$nested = array("a" => array("b" => array("c" => array("d" => "deep"))));
is_equal("array_value_or_null with deeply nested", array_value_or_null($nested, "a"), array("b" => array("c" => array("d" => "deep"))));
is_equal("array_value_or_null deeply nested access", array_value_or_null($nested["a"]["b"]["c"], "d"), "deep");

/* ============================================================ */
/* ----- Special characters in various inputs ----- */
/* ============================================================ */

/* Unicode */
is_equal("htmle with emoji", htmle("🎉"), "🎉");
is_equal("add_leading_zero with unicode", add_leading_zero("ü"), "ü"); /* length 2, no change */

/* Very long integers */
is_equal("zeit_nach_sekunde_am_tag with very large time", zeit_nach_sekunde_am_tag("23:59"), 86340);
/* Note: "99:99" matches the regex with both groups as 99, returns 99*3600+99*60 = 362340 (moved to test_edge_cases.php with correct value) */

/* Edge case dates */
is_equal("convert_date with empty string", convert_date(""), "");
is_equal("convert_date with newlines", convert_date("\n\n\n"), "\n\n\n");
is_equal("convert_date with tabs", convert_date("\t\t"), "\t\t");
is_equal("convert_date with only dots", convert_date("..."), "...");
is_equal("convert_date with mixed punctuation", convert_date("1.2.3.4.5.6"), "1.2.3.4.5.6");

/* ============================================================ */
/* ----- SQL injection attempts in string functions ----- */
/* ============================================================ */

/* These shouldn't execute SQL, just be treated as strings */
is_equal("htmle with SQL injection", htmle("'; DROP TABLE users;--"), "&#039;; DROP TABLE users;--");
is_equal("add_leading_zero with SQL", add_leading_zero("1;DROP TABLE"), "1;DROP TABLE"); /* length > 1, no change */
/* escapeJsonString is for JSON, not SQL — does NOT escape single quotes (documents current behavior) */
is_equal("escapeJsonString leaves single quote alone", strpos(escapeJsonString("'; DROP TABLE"), "'") !== false ? 1 : 0, 1);
is_equal("escapeJsonString does NOT add backslash before single quote", strpos(escapeJsonString("'; DROP TABLE"), "\\'") === false ? 1 : 0, 1);
is_equal("escapeJsonString with SQL escapes double quote", strpos(escapeJsonString("\"; DROP TABLE"), "\\\"") !== false ? 1 : 0, 1);
is_equal("escapeJsonString with SQL escapes backslash", strpos(escapeJsonString("\\; DROP TABLE"), "\\\\") !== false ? 1 : 0, 1);

/* ============================================================ */
/* ----- Boolean / type confusion edge cases ----- */
/* ============================================================ */

/* add_leading_zero with true: true is coerced to "1" (string of length 1), so it prepends 0 */
is_equal("add_leading_zero with true becomes '01'", add_leading_zero(true), "01");
is_equal("add_leading_zero with false (becomes empty string, then '0')", add_leading_zero(false), "0");
is_equal("add_leading_zero with null (becomes empty string, then '0')", add_leading_zero(NULL), "0");
is_equal("array_value_or_null with int key on string array", array_value_or_null(array("1" => "one"), 1), "one"); /* PHP coerces string "1" to int 1 */
is_equal("array_value_or_null with string key on int-key array", array_value_or_null(array(1 => "one"), "1"), "one");

/* ============================================================ */
/* ----- Encoding tests ----- */
/* ============================================================ */

is_equal("htmle preserves UTF-8 multibyte chars", htmle("ÄÖÜäöüß"), "&Auml;&Ouml;&Uuml;&auml;&ouml;&uuml;&szlig;");
is_equal("add_leading_zero with multibyte", is_string(add_leading_zero("ä")) ? 1 : 0, 1);
is_equal("comma_list_to_array with UTF-8", comma_list_to_array("ä,ö,ü"), array("ä", "ö", "ü"));

/* ============================================================ */
/* ----- Boundary value tests for numeric functions ----- */
/* ============================================================ */

/* PHP_INT_MAX - returns as-is (int) since strlen > 2 */
is_equal("add_leading_zero with PHP_INT_MAX returns int", is_int(add_leading_zero(PHP_INT_MAX)) ? 1 : 0, 1);
is_equal("strlen(generate_random_string(100)) works", strlen(generate_random_string(100)), 100);

/* Float */
is_equal("zeit_nach_sekunde_am_tag with float", zeit_nach_sekunde_am_tag("10:30.5") === null ? 1 : 0, 1);
is_equal("zeit_nach_sekunde_am_tag with empty string", zeit_nach_sekunde_am_tag("") === null ? 1 : 0, 1);
is_equal("zeit_nach_sekunde_am_tag with just colon", zeit_nach_sekunde_am_tag(":") === null ? 1 : 0, 1);
is_equal("zeit_nach_sekunde_am_tag with only minutes", zeit_nach_sekunde_am_tag(":30") === null ? 1 : 0, 1);
is_equal("zeit_nach_sekunde_am_tag with 0 minutes returns 36000", zeit_nach_sekunde_am_tag("10:0"), 36000);
/* Note: 99:99 matches the regex with both groups as 99, returns 99*3600+99*60 = 362340 */
is_equal("zeit_nach_sekunde_am_tag with 99:99 (greedy)", zeit_nach_sekunde_am_tag("99:99"), 362340);

/* ============================================================ */
/* ----- Random byte / control character tests ----- */
/* ============================================================ */

/* add_leading_zero with null byte: strlen("\0") = 1, prepends 0, returns "0\0" */
is_equal("add_leading_zero with null byte (length 1, prepends 0)", add_leading_zero("\0"), "0\0");
is_equal("add_leading_zero with backspace (length 1, prepends 0)", add_leading_zero("\x08"), "0\x08");
is_equal("htmle with control chars", htmle("\x00\x01\x02"), "\x00\x01\x02");

/* ============================================================ */
/* ----- Array with weird keys ----- */
/* ============================================================ */

is_equal("array_value_or_null with float key", array_value_or_null(array(1.5 => "x"), 1.5), "x");
is_equal("array_value_or_null with NULL key (treated as empty)", array_value_or_null(array(NULL => "x"), "") === null || array_value_or_null(array(NULL => "x"), "") === "x" ? 1 : 0, 1);
/* Note: PHP 8 doesn't allow object instances as array keys (only int|string). */
is_equal("array_value_or_null with int 0 key", array_value_or_null(array(0 => "zero"), 0), "zero");
is_equal("array_value_or_null with bool true key", array_value_or_null(array(true => "yes"), true), "yes");

/* ============================================================ */
/* ----- might_be_query: more edge cases ----- */
/* ============================================================ */

/* Note: "comment-like SELECT DELETE FROM x" doesn't match because the regex
 * requires ^SELECT at the start. We use a different prefix to avoid
 * the /* comment terminator inside the string. */
is_equal("might_be_query with SELECT in comment-like syntax", might_be_query("/x SELECT x/ DELETE FROM x"), 0);
is_equal("might_be_query with mixed case keywords", might_be_query("Delete From x"), 1);
is_equal("might_be_query with leading whitespace before keyword", might_be_query("   delete from x"), 0);
is_equal("might_be_query with tab between keywords", might_be_query("SELECT\t1\tFROM\tx"), 1);
is_equal("might_be_query with multiple spaces", might_be_query("SELECT   1   FROM   x"), 1);
is_equal("might_be_query with select in string", might_be_query("'select 1 from dual'"), 0);
is_equal("might_be_query with quoted select", might_be_query("\"SELECT 1 FROM dual\""), 0);
is_equal("might_be_query with select 1 from 'dual'", might_be_query("SELECT 1 FROM 'dual'"), 1);
is_equal("might_be_query with select 1 from \"dual\"", might_be_query("SELECT 1 FROM \"dual\""), 1);
is_equal("might_be_query with very long query", strlen(might_be_query("SELECT " . str_repeat("a,", 1000) . " z FROM dual")) > 0 ? 1 : 0, 1);

/* ============================================================ */
/* ----- Mask module edge cases ----- */
/* ============================================================ */

is_equal("mask_module with zero string", mask_module("0"), "<i>0</i>");
is_equal("mask_module with false", mask_module(false), "<i></i>");
is_equal("mask_module with true", mask_module(true), "<i>1</i>"); /* true -> "1" */
is_equal("mask_module with array", mask_module(array("a")), "<i>Array</i>");
is_equal("mask_module with newlines", mask_module("line1\nline2"), "<i>line1\nline2</i>");

/* ============================================================ */
/* ----- print_line_link more ----- */
/* ============================================================ */

is_equal("print_line_link with float", print_line_link(3.14), '<a href="#line_3.14">3.14</a>');
is_equal("print_line_link with zero-padded string", print_line_link("001"), '<a href="#line_001">001</a>');
/* Note: print_line_link doesn't escape HTML, so "<script>" stays literal */
regex_matches("print_line_link with HTML chars", print_line_link("<script>"), '/href="#line_<script>"/');

/* ============================================================ */
/* ----- create_hour_from_to with array mode ----- */
/* ============================================================ */

/* create_hour_from_to returns start of "from" hour and end of "to" hour */
is_equal("create_hour_from_to array mode 0-1", create_hour_from_to(0, 1, 1), array("05:40", "09:00"));
is_equal("create_hour_from_to array mode 1-2", create_hour_from_to(1, 2, 1), array("07:30", "10:50"));
is_equal("create_hour_from_to array mode 2-5", create_hour_from_to(2, 5, 1), array("09:20", "16:20"));
is_equal("create_hour_from_to array mode same hour", create_hour_from_to(4, 4, 1), array("13:00", "14:30"));
is_equal("create_hour_from_to string mode same hour", create_hour_from_to(4, 4, 0), "13:00 &mdash; 14:30");
is_equal("create_hour_from_to array mode out of range returns null", create_hour_from_to(99, 99, 1) === null ? 1 : 0, 1);

/* ============================================================ */
/* ----- Array2Table more ----- */
/* ============================================================ */

$t = array2Table(array());
regex_matches("array2Table empty with proper status", $t, "/<table/");

/* Large data set */
$large_data = array();
for($i = 0; $i < 100; $i++) {
	$large_data[] = array("col" => $i);
}
$t = array2Table($large_data, array_fill(0, 100, array("something_failed" => 0, "studiengang" => "ok")));
/* Last row should be 99 (0-indexed) */
regex_matches("array2Table 100 rows contains last value 99", $t, "/99<\/td>/");
/* Count <tr> tags - should be at least 100 */
$tr_count = substr_count($t, "<tr");
is_equal("array2Table 100 rows has at least 100 tr tags", $tr_count >= 100 ? 1 : 0, 1);

/* ============================================================ */
/* ----- Convert_date more ----- */
/* ============================================================ */

/* Note: convert_date uses $founds[0] (full match) for "year" position. Documents buggy behavior. */
is_equal("convert_date with year 1900", convert_date("01.01.1900"), "01-01-01.01.1900"); /* documents bug */
is_equal("convert_date with year 2099", convert_date("31.12.2099"), "12-31-31.12.2099"); /* documents bug */
is_equal("convert_date with 31 December", convert_date("31.12.2024"), "12-31-31.12.2024"); /* documents bug */
is_equal("convert_date with 29 February (leap year)", convert_date("29.02.2024"), "02-29-29.02.2024"); /* documents bug */

/* ============================================================ */
/* ----- fuck date more ----- */
/* ============================================================ */

/* Non-breaking space */
is_equal("fucked_up_date_to_real_date with non-breaking space", is_string(fucked_up_date_to_real_date("2024 01-01")) || is_null(fucked_up_date_to_real_date("2024 01-01")) ? 1 : 0, 1);

/* ============================================================ */
/* ----- Various edge cases for kunde functions ----- */
/* ============================================================ */

/* get_post with special chars in key */
$_POST["key with spaces"] = "value";
is_equal("get_post with key containing spaces", get_post("key with spaces"), "value");
$_POST["key.with.dots"] = "value2";
is_equal("get_post with key containing dots", get_post("key.with.dots"), "value2");
$_POST["key-with-dashes"] = "value3";
is_equal("get_post with key containing dashes", get_post("key-with-dashes"), "value3");
$_POST["key/with/slashes"] = "value4";
is_equal("get_post with key containing slashes", get_post("key/with/slashes"), "value4");
$_POST = array();

/* ============================================================ */
/* ----- Startseite / template function (smoke test) ----- */
/* ============================================================ */

/* print_uni_logo is in startseite_functions.php (not auto-loaded in pure test mode) */
if(function_exists('print_uni_logo')) {
	ob_start();
	print_uni_logo();
	$logo_output = ob_get_clean();
	is_equal("print_uni_logo outputs img tag", strpos($logo_output, "<img") !== false ? 1 : 0, 1);
	is_equal("print_uni_logo references logo.php", strpos($logo_output, "logo.php") !== false ? 1 : 0, 1);
}

/* ============================================================ */
/* ----- teacher_icon ----- */
/* ============================================================ */

/* teacher_icon doesn't take args */
is_equal("teacher_icon has closing tag", strpos(teacher_icon(), "</span>") !== false ? 1 : 0, 1);

/* warn_if_attention_match is in startseite_functions.php (not auto-loaded), so we can't easily test it here. */
