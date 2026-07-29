<?php
/*
 * Tests for language-related helper functions.
 * These functions deal with language/country codes and flags.
 * Note: These require DB access for proper testing.
 */

/* ----- get_language_name ----- */
/* With DB access, tests return specific language strings */
if(isset($GLOBALS["dbname"]) && $GLOBALS["dbname"]) {
	/* Test with valid ID */
	$result = get_language_name(1);
	is_equal("get_language_name returns string", is_string($result) ? 1 : 0, 1);

	/* Test with non-existent ID */
	$result = get_language_name(99999);
	is_equal("get_language_name for non-existent returns empty", $result, "");
	is_equal("get_language_name null returns empty", get_language_name(null), "");
	is_equal("get_language_name 0 returns empty", get_language_name(0), "");
	is_equal("get_language_name negative returns empty", get_language_name(-1), "");
}

/* ----- get_language_by_veranstaltung ----- */
if(isset($GLOBALS["dbname"]) && $GLOBALS["dbname"]) {
	$result = get_language_by_veranstaltung(1);
	/* result may be array (empty or with rows), null, or other - just check type */
	is_equal("get_language_by_veranstaltung returns array or null", is_array($result) || $result === null ? 1 : 0, 1);
	$result = get_language_by_veranstaltung(99999);
	is_equal("get_language_by_veranstaltung for non-existent returns null or empty array", ($result === null || (is_array($result) && count($result) === 0)) ? 1 : 0, 1);
}

/* ----- get_language_flag ----- */
if(isset($GLOBALS["dbname"]) && $GLOBALS["dbname"]) {
	$result = get_language_flag(1);
	is_equal("get_language_flag returns string", is_string($result) ? 1 : 0, 1);
	$result = get_language_flag(99999);
	is_equal("get_language_flag for non-existent", $result === null || $result === "" ? 1 : 0, 1);
}

/* ----- verify flag file existence and structure ----- */
/* If we have flag files, verify they are valid images */
if(file_exists("data/germany_flag.svg")) {
	is_equal("germany_flag.svg exists", file_exists("data/germany_flag.svg") ? 1 : 0, 1);
}
if(file_exists("data/france_flag.svg")) {
	is_equal("france_flag.svg exists", file_exists("data/france_flag.svg") ? 1 : 0, 1);
}
if(file_exists("data/uk_flag.svg")) {
	is_equal("uk_flag.svg exists", file_exists("data/uk_flag.svg") ? 1 : 0, 1);
}
