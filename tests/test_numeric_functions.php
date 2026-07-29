<?php
/*
 * Tests for numeric, formatting, and conversion functions.
 * Pure functions (no DB dependency).
 */

/* ----- checkIBAN ----- */
/* Test that invalid IBANs are rejected */
is_equal("checkIBAN('') returns false", checkIBAN("") === false ? 1 : 0, 1);
is_equal("checkIBAN('short') returns false (wrong length)", checkIBAN("short") === false ? 1 : 0, 1);
is_equal("checkIBAN('DE00000000000000000000') returns false (bad checksum)", checkIBAN("DE00000000000000000000") === false ? 1 : 0, 1);
is_equal("checkIBAN returns bool", is_bool(checkIBAN("DE89370400440532013000")) ? 1 : 0, 1);

/* Valid DE IBAN */
is_equal("checkIBAN('DE89370400440532013000') returns true", checkIBAN("DE89370400440532013000") === true ? 1 : 0, 1);

/* Test with spaces and dashes (function strips them) */
is_equal("checkIBAN strips spaces - valid DE", checkIBAN("DE89 3704 0044 0532 0130 00") === true ? 1 : 0, 1);
is_equal("checkIBAN strips dashes - valid DE", checkIBAN("DE89-3704-0044-0532-0130-00") === true ? 1 : 0, 1);

/* ----- seconds2human ----- */
is_equal("seconds2human(1) returns singular", seconds2human(1), "1 Sekunde");
is_equal("seconds2human(2) returns plural", seconds2human(2), "2 Sekunden");
is_equal("seconds2human(60) returns minutes", seconds2human(60), "1 Minuten und 0 Sekunden");
is_equal("seconds2human(3600) returns hour", seconds2human(3600), "1 Stunde und 0 Minuten");
is_equal("seconds2human(7200) returns hours plural", seconds2human(7200), "2 Stunden und 0 Minuten");
is_equal("seconds2human(86400) returns days", seconds2human(86400), "1 Tag, 0 Stunden");
is_equal("seconds2human(172800) returns days plural", seconds2human(172800), "2 Tage, 0 Stunden");
is_equal("seconds2human(0) returns singular '0 Sekunden'", seconds2human(0), "0 Sekunden");
is_equal("seconds2human(2592000) returns Monat", seconds2human(2592000), "1 Monat");
is_equal("seconds2human(5184000) returns Monate plural", seconds2human(5184000), "2 Monate");

/* ----- is_valid_auth_code (DB needed for valid codes, but null should return 0) ----- */
is_equal("is_valid_auth_code(null) returns 0", is_valid_auth_code(null), 0);

/* ----- get_post_int ----- */
$_POST["some_int"] = "42";
is_equal("get_post_int('some_int') returns 42", get_post_int("some_int"), 42);
$_POST["zero"] = "0";
is_equal("get_post_int('zero') returns 0", get_post_int("zero"), 0);
$_POST["negative"] = "-7";
is_equal("get_post_int('negative') returns -7", get_post_int("negative"), -7);
$_POST["float_str"] = "3.7";
is_equal("get_post_int('float_str') returns int 3", get_post_int("float_str"), 3);
unset($_POST["some_int"]);
unset($_POST["zero"]);
unset($_POST["negative"]);
unset($_POST["float_str"]);

/* get_post_int of missing key should return 0 (intval of null) */
is_equal("get_post_int('DEFINITELY_NOT_SET_KEY_xyz') returns 0", get_post_int("DEFINITELY_NOT_SET_KEY_xyz"), 0);

/* ----- might_be_query ----- */
is_equal("might_be_query('select 1 from dual') returns 1", might_be_query("select 1 from dual"), 1);
is_equal("might_be_query('SELECT 1 FROM dual') returns 1 (case-insensitive)", might_be_query("SELECT 1 FROM dual"), 1);
is_equal("might_be_query('update foo set bar=1 where id=5') returns 1", might_be_query("update foo set bar=1 where id=5"), 1);
is_equal("might_be_query('UPDATE foo SET bar=1 WHERE id=5') returns 1", might_be_query("UPDATE foo SET bar=1 WHERE id=5"), 1);
is_equal("might_be_query('delete from foo where id=5') returns 1", might_be_query("delete from foo where id=5"), 1);
is_equal("might_be_query('DELETE FROM foo WHERE id=5') returns 1", might_be_query("DELETE FROM foo WHERE id=5"), 1);
is_equal("might_be_query('hallo welt') returns 0", might_be_query("hallo welt"), 0);
is_equal("might_be_query('insert into foo values (1)') returns 0", might_be_query("insert into foo values (1)"), 0);
is_equal("might_be_query(null) returns 0", might_be_query(null), 0);
is_equal("might_be_query('') returns 0", might_be_query(""), 0);
is_equal("might_be_query(array('select 1')) returns 0 (arrays are not queries)", might_be_query(array("select 1")), 0);

/* ----- nonce ----- */
is_equal("nonce() returns string of length 10", strlen(nonce()), 10);
is_equal("nonce() is consistent (cached)", nonce(), nonce());
regex_matches("nonce() matches alphanumeric", nonce(), "/^[a-zA-Z0-9]+$/");

/* ----- generate_random_string ----- */
is_equal("generate_random_string(50) has length 50", strlen(generate_random_string(50)), 50);
is_equal("generate_random_string(1) has length 1", strlen(generate_random_string(1)), 1);
is_equal("generate_random_string(0) has length 0", strlen(generate_random_string(0)), 0);
is_equal("generate_random_string(100) has length 100", strlen(generate_random_string(100)), 100);
is_unequal("generate_random_string produces different values each call", generate_random_string(50), generate_random_string(50));
regex_matches("generate_random_string only contains alphanumeric", generate_random_string(50), "/^[a-zA-Z0-9]+$/");

/* ----- esc (basic - depends on dbh but expects specific outputs) ----- */
is_equal("esc('a')", esc("a"), '"a"');
is_equal("esc(\"\\\"a\\\"\") escapes double quotes", esc("\"a\""), '"\"a\""');
is_equal("esc('') returns NULL", esc(""), "NULL");
is_equal("esc(null) returns NULL", esc(null), "NULL");

/* ----- multiple_esc_join ----- */
is_equal("multiple_esc_join(array('a','b','c'))", multiple_esc_join(array("a", "b", "c")), '"a", "b", "c"');
is_equal("multiple_esc_join(array()) returns empty string", multiple_esc_join(array()), "");
is_equal("multiple_esc_join('scalar') returns esc(scalar)", multiple_esc_join("x"), '"x"');

/* ----- get_sws ----- */
is_equal("get_sws(1, 'keine Angabe') returns null", get_sws(1, "keine Angabe") === null ? 1 : 0, 1);
is_equal("get_sws('1-5', 'jede Woche') returns array(0,10)", get_sws("1-5", "jede Woche"), array(0, 10));
is_equal("get_sws('1-3', 'woche') returns array(0,6)", get_sws("1-3", "woche"), array(0, 6));
is_equal("get_sws('1', 'woche') returns array(0,2)", get_sws("1", "woche"), array(0, 2));
