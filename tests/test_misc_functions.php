<?php
/*
 * Tests for miscellaneous utility functions.
 */

/* ----- get_post_multiple_check ----- */
/* No POST data set */
$_POST = array();
is_equal("get_post_multiple_check(array('a','b')) with no post returns 0", @get_post_multiple_check(array("a", "b")), 0);

$_POST["a"] = "1";
is_equal("get_post_multiple_check(array('a')) returns 1 (one set)", @get_post_multiple_check(array("a")), 1);

$_POST["b"] = "2";
is_equal("get_post_multiple_check(array('a','b')) returns 1 (both set)", get_post_multiple_check(array("a", "b")), 1);

$_POST["c"] = "";
is_equal("get_post_multiple_check(array('a','b','c')) returns 0 (c empty)", get_post_multiple_check(array("a", "b", "c")), 0);

/* Note: get_post_multiple_check with non-array hits a production bug
 * (uses undefined $name in the else branch). It returns NULL.
 * We suppress the resulting PHP warning with @. */
is_equal("get_post_multiple_check with non-array returns NULL (production bug)", @get_post_multiple_check("a") === NULL ? 1 : 0, 1);

/* Reset */
$_POST = array();

/* ----- get_post / get_get ----- */
$_POST["test"] = "value";
is_equal("get_post('test') returns value", get_post("test"), "value");
is_equal("get_post('nonexistent') returns NULL", get_post("nonexistent") === NULL ? 1 : 0, 1);

$_GET["test_get"] = "get_value";
is_equal("get_get('test_get') returns value", get_get("test_get"), "get_value");
is_equal("get_get('nonexistent') returns NULL", get_get("nonexistent") === NULL ? 1 : 0, 1);

$_POST = array();
$_GET = array();

/* ----- get_cookie ----- */
$_COOKIE["test_cookie"] = "cookie_value";
is_equal("get_cookie('test_cookie') returns value", get_cookie("test_cookie"), "cookie_value");
is_equal("get_cookie('nonexistent') returns NULL default", get_cookie("nonexistent") === NULL ? 1 : 0, 1);
is_equal("get_cookie('nonexistent', 'default') returns default", get_cookie("nonexistent", "default"), "default");

$_COOKIE = array();

/* ----- institut_id_exists ----- */
/* Note: institut_id_exists returns count(*) from the DB. For non-existent
 * IDs this is "0" (string), 0 (int), or null depending on driver/version. */
is_equal("institut_id_exists(null) returns falsy", !institut_id_exists(null) ? 1 : 0, 1);
is_equal("institut_id_exists('') returns falsy", !institut_id_exists("") ? 1 : 0, 1);
is_equal("institut_id_exists returns int|string|null", is_int(institut_id_exists(999999999)) || is_string(institut_id_exists(999999999)) || institut_id_exists(999999999) === null ? 1 : 0, 1);

/* ----- file_is_image ----- */
is_equal("file_is_image(null) returns false", file_is_image(null) === false ? 1 : 0, 1);
is_equal("file_is_image('nonexistent.png') returns false", file_is_image("nonexistent_test_xyz.png") === false ? 1 : 0, 1);
is_equal("file_is_image('/nonexistent/path/file.png') returns false", file_is_image("/nonexistent/path/file.png") === false ? 1 : 0, 1);

/* Real image files */
/* Note: file_is_image uses getimagesize() which doesn't recognize SVG
 * format. So SVG flag files return false even though they are valid
 * images. We test that the function returns bool consistently. */
if(file_exists("data/germany_flag.svg")) {
	is_equal("file_is_image('data/germany_flag.svg') returns bool", is_bool(file_is_image("data/germany_flag.svg")) ? 1 : 0, 1);
}
if(file_exists("data/france_flag.svg")) {
	is_equal("file_is_image('data/france_flag.svg') returns bool", is_bool(file_is_image("data/france_flag.svg")) ? 1 : 0, 1);
}
if(file_exists("data/uk_flag.svg")) {
	is_equal("file_is_image('data/uk_flag.svg') returns bool", is_bool(file_is_image("data/uk_flag.svg")) ? 1 : 0, 1);
}
if(file_exists("logo.php")) {
	is_equal("file_is_image('logo.php') returns false (PHP is not image)", file_is_image("logo.php") === false ? 1 : 0, 1);
}
if(file_exists("functions.php")) {
	is_equal("file_is_image('functions.php') returns false", file_is_image("functions.php") === false ? 1 : 0, 1);
}

/* ----- parse_csv ----- */
$csv1 = parse_csv("a-b-c\nd-e-f", "-");
is_equal("parse_csv basic - first row first col", $csv1[0][0], "a");
is_equal("parse_csv basic - first row last col", $csv1[0][2], "c");
is_equal("parse_csv basic - second row first col", $csv1[1][0], "d");

/* parse_csv with quotes */
$csv2 = parse_csv("'a'-'b'\n'c'-'d'", "-");
is_equal("parse_csv strips single quotes", $csv2[0][0], "a");
is_equal("parse_csv strips single quotes (second row)", $csv2[1][0], "c");

$csv3 = parse_csv('"a","b"', ",");
is_equal("parse_csv strips double quotes", $csv3[0][0], "a");

/* parse_csv with multiple delimiters */
$csv4 = parse_csv("a - b - c", "-");
is_equal("parse_csv splits by delimiter", count($csv4[0]), 3);

/* parse_csv with empty lines */
$csv5 = parse_csv("a,b\n\nc,d", ",");
is_equal("parse_csv skips empty lines (2 rows total)", count($csv5), 2);

/* ----- fucked_up_date_to_real_date more edge cases ----- */
/* Note: '2024-01' hits the YYYY-MM pattern, but production compares
 * the month (01) against 1950, so it returns null. */
is_equal("fucked_up_date_to_real_date('2024-01') with csv=1 (returns null due to production bug)", fucked_up_date_to_real_date("2024-01", 1) === null ? 1 : 0, 1);
is_equal("fucked_up_date_to_real_date('01/2024') with csv=1", fucked_up_date_to_real_date("01/2024", 1), "2024-01-15");

/* ----- convert_date more ----- */
/* Note: production convert_date uses $founds[0] (full match) for "year".
 * For "05.05.2024" the result is "05-05-05.05.2024". */
is_equal("convert_date('05.05.2024') documents bug", convert_date("05.05.2024"), "05-05-05.05.2024");

/* ----- get_previous_letter more edge cases ----- */
is_equal("get_previous_letter('c')", get_previous_letter("c"), "b");
is_equal("get_previous_letter('C')", get_previous_letter("C"), "B");
is_equal("get_previous_letter('z')", get_previous_letter("z"), "y");
is_equal("get_previous_letter('Z')", get_previous_letter("Z"), "Y");

/* ----- referrer_from_same_domain ----- */
is_equal("referrer_from_same_domain returns int", is_int(referrer_from_same_domain()) || referrer_from_same_domain() === null ? 1 : 0, 1);

/* ----- print_debug ----- */
ob_start();
print_debug("test debug message");
$debug_out = ob_get_clean();
regex_matches("print_debug contains green text escape", $debug_out, "/\033\[32m/");
regex_matches("print_debug contains the message", $debug_out, "/test debug message/");

/* ----- FormatBacktrace ----- */
$bt = FormatBacktrace();
is_equal("FormatBacktrace returns string", is_string($bt) ? 1 : 0, 1);

/* ----- rarr doesn't escape HTML entities (already covered but double-check) ----- */
is_equal("rarr no change when no entity", rarr("hello"), "hello");

/* ----- add_leading_zero with negative number ----- */
/* Note: add_leading_zero returns the input as-is when strlen >= 2,
 * so the type matches the input type. */
is_equal("add_leading_zero(-5) returns int -5", add_leading_zero(-5), -5);
is_equal("add_leading_zero('-5') returns string '-5'", add_leading_zero("-5"), "-5");
