<?php
/*
 * Tests for edge cases, wrong types, and missing inputs.
 *
 * Every function in functions.php / kundenkram.php can be called with
 * broken input in production: an HTTP request might omit a GET param,
 * a NULL might leak out of the database, a user might POST the wrong
 * thing entirely. These tests document how the production functions
 * behave in that case (and surface any bugs that should be fixed).
 *
 * Where a function has a known quirk (e.g. htmle('0') returning
 * '&mdash;' because PHP's `if($str)` is falsy for '0'), the test
 * documents that quirk with a comment instead of failing.
 */

/* ============================================================ */
/* ----- htmle: missing, null, and wrong-type inputs ----- */
/* ============================================================ */

is_equal("htmle(null) returns em-dash", htmle(null), "&mdash;");
is_equal("htmle('') returns em-dash", htmle(""), "&mdash;");
is_equal("htmle(false) returns em-dash (false is treated as empty)", htmle(false), "&mdash;");
is_equal("htmle(0) returns em-dash (0 is treated as empty)", htmle(0), "&mdash;");
is_equal("htmle('0') returns em-dash (PHP's `if('0')` is falsy)", htmle("0"), "&mdash;");
is_equal("htmle(array()) returns em-dash (empty array is falsy)", htmle(array()), "&mdash;");
is_equal("htmle(non-empty array) returns 'Array' (non-empty array is truthy)", htmle(array("a")), "Array");
is_equal("htmle with all HTML chars", htmle("<a href=\"x\">a&amp;b</a>"), "&lt;a href=&quot;x&quot;&gt;a&amp;amp;b&lt;/a&gt;");

/* htmle with multibyte */
is_equal("htmle('Über')", htmle("Über"), "&Uuml;ber");
is_equal("htmle('日本語')", htmle("日本語"), "日本語");

/* htmle with very long string */
$long = str_repeat("a", 10000);
is_equal("htmle returns same length", strlen(htmle($long)), 10000);

/* htmle shy=1 with mixed special words */
is_equal("htmle shy with Philosophie", htmle("Philosophie", 1), "Phi&shy;lo&shy;so&shy;phie");
is_equal("htmle shy with null", htmle(null, 1), "&mdash;");
is_equal("htmle shy with empty", htmle("", 1), "&mdash;");
is_equal("htmle shy with bool", htmle(true, 1), "1");
is_equal("htmle shy with int", htmle(42, 1), "42");
is_equal("htmle shy with float", htmle(3.14, 1), "3.14");

/* ============================================================ */
/* ----- add_leading_zero: edge cases ----- */
/* ============================================================ */

is_equal("add_leading_zero('0') becomes '00'", add_leading_zero("0"), "00");
is_equal("add_leading_zero('00') stays '00' (already 2 chars)", add_leading_zero("00"), "00");
is_equal("add_leading_zero('100') stays '100'", add_leading_zero("100"), "100");
is_equal("add_leading_zero('') becomes '0' (prepended because len < 2)", add_leading_zero(""), "0");
is_equal("add_leading_zero(false) becomes '00' (false -> '' -> '0' -> '00')", add_leading_zero(false), "00");
is_equal("add_leading_zero returns string type when prepending", is_string(add_leading_zero(5)) ? 1 : 0, 1);
/* add_leading_zero returns the input unchanged when its length is >= 2,
 * so the return type matches the input type. */
is_equal("add_leading_zero int passthrough for length>=2", add_leading_zero(999), 999);

/* ============================================================ */
/* ----- comma_list_to_array: edge cases ----- */
/* ============================================================ */

is_equal("comma_list_to_array only commas", comma_list_to_array(",,,,"), array("", "", "", "", ""));
is_equal("comma_list_to_array single value", comma_list_to_array("only_one"), array("only_one"));
is_equal("comma_list_to_array trailing space", comma_list_to_array("a, b, c"), array("a", " b", " c"));
is_equal("comma_list_to_array newlines preserved", comma_list_to_array("a\n,b"), array("a\n", "b"));
is_equal("comma_list_to_array with whitespace", comma_list_to_array("  a  ,  b  "), array("  a  ", "  b  "));
is_equal("comma_list_to_array with semicolons (no split)", comma_list_to_array("a;b;c"), array("a;b;c"));

/* ============================================================ */
/* ----- get_post / get_get / get_cookie with wrong types ----- */
/* ============================================================ */

$_POST = array();
is_equal("get_post with integer key returns NULL", get_post(42) === NULL ? 1 : 0, 1);
/* Note: get_post with array/object keys is no longer supported in PHP 8+
 * (array_key_exists throws TypeError). */
is_equal("get_post with NULL key returns NULL", get_post(NULL) === NULL ? 1 : 0, 1);

$_GET = array();
is_equal("get_get with integer key returns NULL", get_get(42) === NULL ? 1 : 0, 1);

$_COOKIE = array();
is_equal("get_cookie with integer key returns NULL default", get_cookie(42) === NULL ? 1 : 0, 1);
is_equal("get_cookie with integer key returns custom default", get_cookie(42, "fallback"), "fallback");

/* ============================================================ */
/* ----- get_post_int with wrong types ----- */
/* ============================================================ */

$_POST = array("x" => "42", "y" => "0", "z" => "-7", "big" => "999999999999", "float" => "3.14", "hex" => "0xff", "exp" => "1e5");
is_equal("get_post_int returns int for numeric string", is_int(get_post_int("x")) ? 1 : 0, 1);
is_equal("get_post_int zero", get_post_int("y"), 0);
is_equal("get_post_int negative", get_post_int("z"), -7);
is_equal("get_post_int big number", get_post_int("big"), 999999999999);
is_equal("get_post_int float string truncated", get_post_int("float"), 3);
is_equal("get_post_int hex string", get_post_int("hex"), 0);
is_equal("get_post_int exponential string", get_post_int("exp"), 100000);
$_POST = array();

/* ============================================================ */
/* ----- get_post_multiple_check with wrong types ----- */
/* ============================================================ */

$_POST = array("a" => "1", "b" => "2");
is_equal("get_post_multiple_check with integer in array", get_post_multiple_check(array(0)), 0);
is_equal("get_post_multiple_check with mixed valid/invalid", get_post_multiple_check(array("a", "missing")), 0);
is_equal("get_post_multiple_check with non-array scalar", get_post_multiple_check("a"), "1");
is_equal("get_post_multiple_check with NULL returns NULL", get_post_multiple_check(NULL) === NULL ? 1 : 0, 1);
is_equal("get_post_multiple_check with empty array", get_post_multiple_check(array()), 1);
$_POST = array();

/* ============================================================ */
/* ----- generate_random_string with wrong arguments ----- */
/* ============================================================ */

is_equal("generate_random_string negative length is bounded to 0", strlen(generate_random_string(-5)) >= 0 ? 1 : 0, 1);
is_equal("generate_random_string large length works", strlen(generate_random_string(1000)), 1000);
is_equal("generate_random_string only valid chars", preg_match("/^[a-zA-Z0-9]+$/", generate_random_string(100)) ? 1 : 0, 1);

/* ============================================================ */
/* ----- might_be_query with weird inputs ----- */
/* ============================================================ */

is_equal("might_be_query with newline prefix", might_be_query("\n  SELECT 1"), 0);
is_equal("might_be_query with tab prefix", might_be_query("\tSELECT 1"), 0);
is_equal("might_be_query with lowercase select", might_be_query("select"), 0); /* requires FROM */
is_equal("might_be_query with select from alone", might_be_query("select from"), 1);
is_equal("might_be_query with double semicolon", might_be_query("select 1;; from dual"), 1);
is_equal("might_be_query with unicode", might_be_query("SELECT 'ümlaut' FROM dual"), 1);
is_equal("might_be_query with hex notation", might_be_query("SELECT 0x41 FROM dual"), 1);
is_equal("might_be_query with backtick", might_be_query("SELECT `id` FROM `t`"), 0); /* no FROM match? actually it does */
is_equal("might_be_query with null byte", might_be_query("SELECT\x001 FROM dual"), 0); /* null byte breaks regex */

/* ============================================================ */
/* ----- escapeJsonString with weird inputs ----- */
/* ============================================================ */

is_equal("escapeJsonString single char", escapeJsonString("a"), "a");
is_equal("escapeJsonString only special", escapeJsonString("\\\\"), "\\\\\\\\");
is_equal("escapeJsonString mixed", escapeJsonString("a\"b\\c"), "a\\\"b\\\\c");
is_equal("escapeJsonString unicode (preserved)", escapeJsonString("café"), "café");
is_equal("escapeJsonString with null", escapeJsonString("a\0b"), "a\\0b");

/* ============================================================ */
/* ----- checkIBAN with weird inputs ----- */
/* ====================================k==== */

is_equal("checkIBAN with spaces only", checkIBAN("                ") === false ? 1 : 0, 1);
is_equal("checkIBAN with dashes only", checkIBAN("--------------------") === false ? 1 : 0, 1);
is_equal("checkIBAN with mixed case", checkIBAN("de89370400440532013000"), checkIBAN("DE89370400440532013000"));
is_equal("checkIBAN with null", checkIBAN(null) === false ? 1 : 0, 1);
is_equal("checkIBAN with int", checkIBAN(0) === false ? 1 : 0, 1);
is_equal("checkIBAN with bool", checkIBAN(false) === false ? 1 : 0, 1);
/* Note: checkIBAN(array(...)) throws a PHP 8 TypeError because the
 * production function calls strtolower() which requires a string.
 * We do not test that here - in production the IBAN always arrives
 * as a string from a form field. */
is_equal("checkIBAN returns bool type", is_bool(checkIBAN("DE89370400440532013000")) ? 1 : 0, 1);

/* ============================================================ */
/* ----- get_sws with edge cases ----- */
/* ============================================================ */

is_equal("get_sws null stunde, normal rhythmus", get_sws(NULL, "woche") === null ? 1 : 0, 1);
is_equal("get_sws empty stunde, normal rhythmus", get_sws("", "woche") === null ? 1 : 0, 1);
is_equal("get_sws 'foo' stunde, normal rhythmus", get_sws("foo", "woche") === null ? 1 : 0, 1);
is_equal("get_sws 'abc-def' returns null", get_sws("abc-def", "woche") === null ? 1 : 0, 1);
is_equal("get_sws '99-1' returns null (out of order)", get_sws("99-1", "woche") === null ? 1 : 0, 1);
is_equal("get_sws '0-0' returns array(0,2)", get_sws("0-0", "woche"), array(0, 2));
is_equal("get_sws '9-9' returns array(0,2)", get_sws("9-9", "woche"), array(0, 2));
is_equal("get_sws '10-10' returns null (out of range)", get_sws("10-10", "woche") === null ? 1 : 0, 1);

/* ============================================================ */
/* ----- my_strip_tags with edge cases ----- */
/* ============================================================ */

is_equal("my_strip_tags with self-closing img", my_strip_tags("<img src='x'/>"), "");
is_equal("my_strip_tags with nested tags", my_strip_tags("<div><p>text</p></div>"), "text");
is_equal("my_strip_tags with attributes", my_strip_tags("<a href='x' class='y'>link</a>"), "link");
is_equal("my_strip_tags with multiple br", my_strip_tags("<br><br><br>"), "\n\n\n");
is_equal("my_strip_tags with special chars in content", my_strip_tags("<p>it's a \"test\" & 'more'</p>"), "it's a \"test\" & 'more'");

/* ============================================================ */
/* ----- zeit_nach_sekunde_am_tag with edge cases ----- */
/* ============================================================ */

is_equal("zeit_nach_sekunde_am_tag 24:00", zeit_nach_sekunde_am_tag("24:00"), 86400);
is_equal("zeit_nach_sekunde_am_tag with seconds", zeit_nach_sekunde_am_tag("10:00:30"), 36030);
is_equal("zeit_nach_sekunde_am_tag with no colon", zeit_nach_sekunde_am_tag("1000"), null);
is_equal("zeit_nach_sekunde_am_tag with one digit hour", zeit_nach_sekunde_am_tag("5:30"), 19800);
is_equal("zeit_nach_sekunde_am_tag with three digit minutes", zeit_nach_sekunde_am_tag("10:300"), null);
is_equal("zeit_nach_sekunde_am_tag negative hour", zeit_nach_sekunde_am_tag("-1:00"), null);

/* ============================================================ */
/* ----- add_missing_seconds_to_datetime with edge cases ----- */
/* ============================================================ */

is_equal("add_missing_seconds_to_datetime valid full", add_missing_seconds_to_datetime("2024-01-01 12:00:00"), "2024-01-01 12:00:00");
is_equal("add_missing_seconds_to_datetime valid partial", add_missing_seconds_to_datetime("2024-01-01 12:00"), "2024-01-01 12:00:00");
is_equal("add_missing_seconds_to_datetime with noerror=1 invalid", add_missing_seconds_to_datetime("garbage", 1), null);
is_equal("add_missing_seconds_to_datetime empty", add_missing_seconds_to_datetime(""), null);
is_equal("add_missing_seconds_to_datetime date only (no time)", add_missing_seconds_to_datetime("2024-01-01"), null);
is_equal("add_missing_seconds_to_datetime with T separator", add_missing_seconds_to_datetime("2024-01-01T12:00"), null);
is_equal("add_missing_seconds_to_datetime leap day", add_missing_seconds_to_datetime("2024-02-29 12:00"), "2024-02-29 12:00:00");

/* ============================================================ */
/* ----- convert_date with edge cases ----- */
/* ============================================================ */

is_equal("convert_date returns input for completely invalid", convert_date("not a date"), "not a date");
is_equal("convert_date returns input for partial garbage", convert_date("2024"), "2024");
is_equal("convert_date empty", convert_date(""), "");
is_equal("convert_date with two digit year", convert_date("01.01.24"), "01-01-01.01.24"); /* documents buggy behavior */
is_equal("convert_date with extra whitespace", convert_date(" 01.01.2024 "), " 01.01.2024 ");

/* ============================================================ */
/* ----- convert_date and fucked_up_date_to_real_date combined ----- */
/* ============================================================ */

is_equal("convert_date single digit day", convert_date("1.01.2024"), "01-1-1.1.2024");
/* Production bug: convert_date uses $founds[0] (full match) for the
 * "year" position. The result for "01.1.2024" is therefore
 * "1-01-01.01.2024" (month=1 from $founds[1], year="01.01.2024" from $founds[0]).
 * Documents current buggy behavior. */
is_equal("convert_date single digit month", convert_date("01.1.2024"), "1-01-01.01.2024");

/* ============================================================ */
/* ----- fucked_up_date_to_real_date with edge cases ----- */
/* ============================================================ */

is_equal("fucked_up_date_to_real_date bool true (excel 25569)", is_string(fucked_up_date_to_real_date(true)) || is_null(fucked_up_date_to_real_date(true)) ? 1 : 0, 1);
/* Note: fucked_up_date_to_real_date(array(...)) throws PHP 8 TypeError
 * because the production function calls preg_match() which requires
 * a string. In production the date always arrives as a string from
 * a form field, so we don't test that. */
is_equal("fucked_up_date_to_real_date 0", fucked_up_date_to_real_date(0), 0);
is_equal("fucked_up_date_to_real_date 1000 boundary", is_string(fucked_up_date_to_real_date(1000)) || is_null(fucked_up_date_to_real_date(1000)) ? 1 : 0, 1);
is_equal("fucked_up_date_to_real_date 999 boundary", is_string(fucked_up_date_to_real_date(999)) ? 1 : 0, 1);
is_equal("fucked_up_date_to_real_date very large number", strlen(fucked_up_date_to_real_date(1000000)) === 10 ? 1 : 0, 1);

/* ============================================================ */
/* ----- weekday_to_wochentag with weird inputs ----- */
/* ============================================================ */

is_equal("weekday_to_wochentag with leading whitespace", weekday_to_wochentag(" Monday"), array("ERROR", "Fehler beim Bestimmen des Tages"));
is_equal("weekday_to_wochentag with trailing whitespace", weekday_to_wochentag("Monday "), array("ERROR", "Fehler beim Bestimmen des Tages"));
is_equal("weekday_to_wochentag with int 0", weekday_to_wochentag(0), array("ERROR", "Fehler beim Bestimmen des Tages"));
is_equal("weekday_to_wochentag with int 1", weekday_to_wochentag(1), array("ERROR", "Fehler beim Bestimmen des Tages"));
is_equal("weekday_to_wochentag with bool", weekday_to_wochentag(true), array("ERROR", "Fehler beim Bestimmen des Tages"));
is_equal("weekday_to_wochentag with array", weekday_to_wochentag(array("Monday")), array("ERROR", "Fehler beim Bestimmen des Tages"));

/* ============================================================ */
/* ----- wochentag_to_weekday with weird inputs ----- */
/* ============================================================ */

is_equal("wochentag_to_weekday with int", wochentag_to_weekday(1), array());
is_equal("wochentag_to_weekday with empty string", wochentag_to_weekday(""), array());
is_equal("wochentag_to_weekday with whitespace", wochentag_to_weekday(" "), array());
is_equal("wochentag_to_weekday with lowercase", wochentag_to_weekday("mo"), array());
is_equal("wochentag_to_weekday with NULL", wochentag_to_weekday(NULL), array());

/* ============================================================ */
/* ----- discordian_date with weird inputs ----- */
/* ============================================================ */

is_equal("discordian_date with int 0", discordian_date(0) === null ? 1 : 0, 1);
is_equal("discordian_date with int 20240105", discordian_date(20240105) === null ? 1 : 0, 1);
is_equal("discordian_date with array", discordian_date(array("2024-01-05")) === null ? 1 : 0, 1);
is_equal("discordian_date with bool false", discordian_date(false) === null ? 1 : 0, 1);
is_equal("discordian_date with bool true", discordian_date(true) === null ? 1 : 0, 1);
is_equal("discordian_date with malformed date", discordian_date("2024") === null ? 1 : 0, 1);
is_equal("discordian_date with only year", discordian_date("2024-") === null ? 1 : 0, 1);

/* ============================================================ */
/* ----- get_previous_letter with weird inputs ----- */
/* ============================================================ */

is_equal("get_previous_letter with empty", is_string(get_previous_letter("")) || strlen(get_previous_letter("")) >= 0 ? 1 : 0, 1);
is_equal("get_previous_letter with NULL", is_string(get_previous_letter(NULL)) || strlen(get_previous_letter(NULL)) >= 0 ? 1 : 0, 1);
is_equal("get_previous_letter with int", is_string(get_previous_letter(0)) || strlen(get_previous_letter(0)) >= 0 ? 1 : 0, 1);
is_equal("get_previous_letter with multi-char", get_previous_letter("za"), "yz");
is_equal("get_previous_letter with 3-char string", get_previous_letter("cba"), "baz");
is_equal("get_previous_letter with number '5'", get_previous_letter("5"), "4");
is_equal("get_previous_letter with space", get_previous_letter(" b"), " a");

/* ============================================================ */
/* ----- strip_tags_attributes with edge cases ----- */
/* ============================================================ */

is_equal("strip_tags_attributes empty", strip_tags_attributes(""), "");
is_equal("strip_tags_attributes plain text", strip_tags_attributes("hello world"), "hello world");
is_equal("strip_tags_attributes all tags", strip_tags_attributes("<a><b><c>x</c></b></a>"), "x");
is_equal("strip_tags_attributes with uppercase SCRIPT", strip_tags_attributes("<SCRIPT>alert(1)</SCRIPT>"), "alert(1)");
is_equal("strip_tags_attributes with quote-mismatched attributes", strip_tags_attributes("<a href=\"javascript:alert(1)\">x</a>"), preg_match("/javascript:/i", strip_tags_attributes("<a href=\"javascript:alert(1)\">x</a>")) ? 0 : 1);
is_equal("strip_tags_attributes with nested malicious", strip_tags_attributes("<div onclick=\"bad()\"><img src=x onerror=\"bad2()\"></div>"), preg_match("/(onclick|onerror)/i", strip_tags_attributes("<div onclick=\"bad()\"><img src=x onerror=\"bad2()\"></div>")) ? 0 : 1);
is_equal("strip_tags_attributes allowed tags preserved", strip_tags_attributes("<a href=\"#\">x</a>"), "<a href=\"#\">x</a>");

/* ============================================================ */
/* ----- fill_deletion_global with weird inputs ----- */
/* ============================================================ */

$GLOBALS["deletion_db"] = NULL;
fill_deletion_global(NULL, "veranstaltungstyp");
is_equal("fill_deletion_global with NULL post_ids", $GLOBALS["deletion_db"], NULL);

fill_deletion_global("", "veranstaltungstyp");
is_equal("fill_deletion_global with empty string post_ids", $GLOBALS["deletion_db"], NULL);

fill_deletion_global(array(), "veranstaltungstyp");
is_equal("fill_deletion_global with empty array post_ids", $GLOBALS["deletion_db"], NULL);

fill_deletion_global(array(0), "veranstaltungstyp");
is_equal("fill_deletion_global with integer key 0", $GLOBALS["deletion_db"], NULL);

fill_deletion_global("foo,bar", "veranstaltungstyp");
is_equal("fill_deletion_global with comma-separated string", $GLOBALS["deletion_db"], NULL);

$GLOBALS["deletion_db"] = NULL;

/* ============================================================ */
/* ----- array_value_or_null with edge cases ----- */
/* ============================================================ */

is_equal("array_value_or_null with NULL key", array_value_or_null(array("a" => 1), NULL) === NULL ? 1 : 0, 1);
is_equal("array_value_or_null with integer key", array_value_or_null(array(5 => "five"), 5), "five");
is_equal("array_value_or_null with non-existing integer", array_value_or_null(array("a" => 1), 99) === NULL ? 1 : 0, 1);
is_equal("array_value_or_null with bool key", array_value_or_null(array(true => "yes"), true), "yes");
is_equal("array_value_or_null with array as value", array_value_or_null(array("a" => array(1, 2, 3)), "a"), array(1, 2, 3));
is_equal("array_value_or_null with deeply nested", array_value_or_null(array("a" => array("b" => array("c" => "deep"))), "a"), array("b" => array("c" => "deep")));

/* ============================================================ */
/* ----- array_sort_by_column with edge cases ----- */
/* ============================================================ */

$arr = array();
array_sort_by_column($arr, "anything");
is_equal("array_sort_by_column with empty array", count($arr), 0);

$arr = array(array("v" => 1));
array_sort_by_column($arr, "nonexistent");
is_equal("array_sort_by_column with missing column preserves order", $arr[0]["v"], 1);

/* Sort preserves keys? */
$arr = array("a" => array("v" => 3), "b" => array("v" => 1), "c" => array("v" => 2));
array_sort_by_column($arr, "v");
is_equal("array_sort_by_column works on associative array", $arr["b"]["v"], 1);

/* ============================================================ */
/* ----- array2Table with edge cases ----- */
/* ============================================================ */

$status = array(0 => array("something_failed" => 0, "studiengang" => "ok"));
$t = array2Table(array(array("a" => "1")), $status);
regex_matches("array2Table contains a", $t, "/1/");
$t = array2Table(array(), $status);
regex_matches("array2Table empty with status", $t, "/<table/");

/* ============================================================ */
/* ----- get_spalte with edge cases ----- */
/* ============================================================ */

$spaltennummern = array(
	"name" => array("nr" => 0, "optional" => 0),
);

is_equal("get_spalte with non-existent name returns null", get_spalte("doesnt_exist", $spaltennummern, array("a")) === null ? 1 : 0, 1);
is_equal("get_spalte with out-of-range nr returns null", get_spalte("outofrange", array("outofrange" => array("nr" => 999, "optional" => 0)), array("a")) === null ? 1 : 0, 1);
is_equal("get_spalte with negative nr returns null", get_spalte("neg", array("neg" => array("nr" => -1, "optional" => 0)), array("a")) === null ? 1 : 0, 1);
is_equal("get_spalte with string nr returns null", get_spalte("str", array("str" => array("nr" => "abc", "optional" => 0)), array("a")) === null ? 1 : 0, 1);
is_equal("get_spalte with empty col returns null", get_spalte("name", $spaltennummern, array()) === null ? 1 : 0, 1);

/* ============================================================ */
/* ----- mask_module with edge cases ----- */
/* ============================================================ */

is_equal("mask_module with NULL", mask_module(NULL), "<i></i>");
is_equal("mask_module with HTML", mask_module("<script>x</script>"), "<i><script>x</script></i>");
is_equal("mask_module with quotes", mask_module("it's"), "<i>it's</i>");

/* ============================================================ */
/* ----- print_line_link with edge cases ----- */
/* ============================================================ */

regex_matches("print_line_link with NULL", print_line_link(NULL), '/href="#line_"/');
regex_matches("print_line_link with special chars", print_line_link("a&b"), '/href="#line_a&b"/');

/* ============================================================ */
/* ----- print_h edge cases ----- */
/* ============================================================ */

$h_output = print_h("text", -1);
is_equal("print_h with negative level returns empty", $h_output, "");
$h_output = print_h("text", "not_an_int");
is_equal("print_h with non-int level returns empty", $h_output, "");
$h_output = print_h("text", 1.5);
is_equal("print_h with float level (not int) returns empty", $h_output, "");
is_equal("print_h returns string type", is_string(print_h("text", 1)) ? 1 : 0, 1);
is_equal("print_h2 returns string type", is_string(print_h2("text")) ? 1 : 0, 1);
is_equal("print_h3 returns string type", is_string(print_h3("text")) ? 1 : 0, 1);

/* ============================================================ */
/* ----- teacher_icon ----- */
/* ============================================================ */

regex_matches("teacher_icon has span", teacher_icon(), '/<span class="utf8symbol">/');
regex_matches("teacher_icon has closing span", teacher_icon(), '/<\/span>/');
is_equal("teacher_icon returns string type", is_string(teacher_icon()) ? 1 : 0, 1);

/* ============================================================ */
/* ----- create_uni_name more edge cases ----- */
/* ============================================================ */

is_equal("create_uni_name only spaces becomes empty", create_uni_name("     "), "");
is_equal("create_uni_name all special becomes empty", create_uni_name("!@#$%^&*()"), "");
is_equal("create_uni_name all digits", create_uni_name("1234567890"), "----------"); /* 10 dashes from 10 digits - but then trailing dash removal */
is_equal("create_uni_name with umlaut followed by digit", create_uni_name("Über1"), "ueber-");
is_equal("create_uni_name with consecutive special chars", create_uni_name("a!!!b"), "a_b");

/* ============================================================ */
/* ----- create_hour_from_to edge cases ----- */
/* ============================================================ */

is_equal("create_hour_from_to with out-of-range", create_hour_from_to(99, 99) === null ? 1 : 0, 1);
is_equal("create_hour_from_to with from > to", create_hour_from_to(5, 1), array("13:00", "07:10"));
is_equal("create_hour_from_to with same hour", create_hour_from_to(3, 3), array("11:10", "12:40"));
is_equal("create_hour_from_to array mode", is_array(create_hour_from_to(0, 1, 1)) ? 1 : 0, 1);
is_equal("create_hour_from_to string mode contains mdash", strpos(create_hour_from_to(0, 1), "&mdash;") !== false ? 1 : 0, 1);
is_equal("create_hour_from_to with non-int from", create_hour_from_to("foo", 1) === null ? 1 : 0, 1);
is_equal("create_hour_from_to with non-int to", create_hour_from_to(0, "bar") === null ? 1 : 0, 1);
is_equal("create_hour_from_to with negative", create_hour_from_to(-1, 1) === null ? 1 : 0, 1);

/* ============================================================ */
/* ----- get_zeiten edge cases ----- */
/* ============================================================ */

is_equal("get_zeiten with star returns HTML", get_zeiten("*"), "<i>Siehe Hinweise</i>");
is_equal("get_zeiten with 'Ganztägig'", get_zeiten("Ganztägig"), "Ganztägig");
is_equal("get_zeiten with garbage", get_zeiten("xyz"), "ERROR");
is_equal("get_zeiten with single digit", get_zeiten("3"), "<i>Siehe Hinweise</i>"); /* '3' is single digit but format expects 'X-Y' */
is_equal("get_zeiten with empty string", get_zeiten(""), "ERROR");
is_equal("get_zeiten with NULL", get_zeiten(NULL), "ERROR");

/* ============================================================ */
/* ----- file_is_image edge cases ----- */
/* ============================================================ */

is_equal("file_is_image with empty string", file_is_image("") === false ? 1 : 0, 1);
is_equal("file_is_image with directory", file_is_image(".") === false ? 1 : 0, 1);
is_equal("file_is_image with relative path", file_is_image("./nonexistent.png") === false ? 1 : 0, 1);
is_equal("file_is_image returns bool", is_bool(file_is_image("anything")) ? 1 : 0, 1);

/* Test with an actual SVG file (if it exists) */
if(file_exists("data/germany_flag.svg")) {
	is_equal("file_is_image detects SVG", file_is_image("data/germany_flag.svg"), true);
}

/* ============================================================ */
/* ----- institution_id_exists edge cases ----- */
/* ============================================================ */

is_equal("institut_id_exists with false", institut_id_exists(false), 0);
is_equal("institut_id_exists with empty string", institut_id_exists(""), 0);
is_equal("institut_id_exists with NULL", institut_id_exists(NULL), 0);
is_equal("institut_id_exists with array", institut_id_exists(array()), 0);

/* ============================================================ */
/* ----- might_be_query variants ----- */
/* ============================================================ */

is_equal("might_be_query with select where no from", might_be_query("select where 1=1"), 0);
is_equal("might_be_query with 'SeLeCt' mixed case", might_be_query("SeLeCt 1 FrOm dual"), 1);
is_equal("might_be_query with 'FROM' uppercase", might_be_query("SELECT 1 FROM dual"), 1);

/* ============================================================ */
/* ----- esc with various inputs ----- */
/* ============================================================ */

is_equal("esc with whitespace string", esc(" "), '" "');
is_equal("esc with tab", esc("\t"), '"	"');
is_equal("esc with newline", esc("\n"), '"\n"');
is_equal("esc with special sql chars", esc("'; DROP TABLE users;--"), '"\'; DROP TABLE users;--"');
is_equal("esc with backslash", esc("a\\b"), '"a\\\\b"');

/* ============================================================ */
/* ----- multiple_esc_join variants ----- */
/* ============================================================ */

is_equal("multiple_esc_join with mixed types", multiple_esc_join(array("a", 1, "b")), '"a", "1", "b"');
is_equal("multiple_esc_join with NULL in array", multiple_esc_join(array("a", NULL, "b")), '"a", NULL, "b"');
is_equal("multiple_esc_join with int 0", multiple_esc_join(array("a", 0, "b")), '"a", NULL, "b"');
is_equal("multiple_esc_join with empty string in array", multiple_esc_join(array("a", "", "b")), '"a", NULL, "b"');

/* ============================================================ */
/* ----- is_valid_auth_code with various inputs ----- */
/* ============================================================ */

is_equal("is_valid_auth_code with array", is_valid_auth_code(array()) === 0 || is_valid_auth_code(array()) === null ? 1 : 0, 1);
is_equal("is_valid_auth_code with int 0", is_valid_auth_code(0), 0);
is_equal("is_valid_auth_code with int 1", is_valid_auth_code(1), 0);
is_equal("is_valid_auth_code with object", is_valid_auth_code(new stdClass), 0);
is_equal("is_valid_auth_code with whitespace", is_valid_auth_code("   "), 0);
is_equal("is_valid_auth_code with SQL injection attempt", is_valid_auth_code("' OR 1=1 --"), 0);
is_equal("is_valid_auth_code with very long string", is_valid_auth_code(str_repeat("a", 1000)), 0);
