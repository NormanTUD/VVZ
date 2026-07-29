<?php
/*
 * Tests for DB query helpers. These tests assume the database is working.
 * They are designed to be flexible - they don't depend on specific data,
 * only on schema and behavior.
 */

/* ----- table_exists ----- */
/* For these we use a known-good table (users should exist) and a known-bad table */
if(isset($GLOBALS["dbname"]) && $GLOBALS["dbname"]) {
	is_equal("table_exists('users') returns 1", table_exists($GLOBALS["dbname"], "users"), 1);
	is_equal("table_exists('IDONOTEXIST_XYZ') returns 0", table_exists($GLOBALS["dbname"], "IDONOTEXIST_XYZ"), 0);
	is_equal("table_exists('users', empty) returns 0", table_exists($GLOBALS["dbname"], ""), 0);
}

/* ----- database_exists ----- */
if(isset($GLOBALS["dbname"]) && $GLOBALS["dbname"]) {
	is_equal("database_exists(current dbname) returns 1", database_exists($GLOBALS["dbname"]), 1);
	is_equal("database_exists('non_existent_db_xyz_123') returns 0", database_exists("non_existent_db_xyz_123"), 0);
}

/* ----- is_valid_auth_code ----- */
is_equal("is_valid_auth_code(null) returns 0", is_valid_auth_code(null), 0);
is_equal("is_valid_auth_code('') returns 0", is_valid_auth_code(""), 0);
is_equal("is_valid_auth_code('this_is_definitely_not_a_valid_auth_code_xyz_123') returns 0", is_valid_auth_code("this_is_definitely_not_a_valid_auth_code_xyz_123"), 0);

/* ----- esc preserves quotes ----- */
is_equal("esc('a')", esc("a"), '"a"');
is_equal("esc(\"\\\"a\\\"\") returns quoted escaped", esc("\"a\""), '"\"a\""');
is_unequal("esc('select 1;') returns quoted, not raw", esc("select 1;"), 'select 1;');
is_equal("esc('') returns NULL string", esc(""), "NULL");

/* ----- rquery simple query (assumes DB) ----- */
if(isset($GLOBALS["dbname"]) && $GLOBALS["dbname"]) {
	$res = rquery("SELECT 1");
	is_equal("rquery('SELECT 1') returns result object", is_object($res) ? 1 : 0, 1);

	$res2 = rquery("SELECT 1 AS x");
	is_equal("rquery result has correct row count", mysqli_num_rows($res2), 1);
	if($res2) {
		$res2->free();
	}
	if($res) {
		$res->free();
	}
}

/* ----- get_single_row_from_result / get_single_row_from_query ----- */
if(isset($GLOBALS["dbname"]) && $GLOBALS["dbname"]) {
	$result = rquery("SELECT 42");
	is_equal("get_single_row_from_result returns first column", get_single_row_from_result($result), "42");
	if($result) {
		$result->free();
	}

	is_equal("get_single_row_from_query('SELECT 1') returns '1'", get_single_row_from_query("SELECT 1"), "1");
	is_equal("(int) get_single_row_from_query('SELECT 1') = 1", (int) get_single_row_from_query("SELECT 1"), 1);
}

/* ----- table_exists returns 0 or 1 (an integer) ----- */
if(isset($GLOBALS["dbname"]) && $GLOBALS["dbname"]) {
	$result = table_exists($GLOBALS["dbname"], "users");
	is_equal("table_exists returns integer", is_int($result) ? 1 : 0, 1);
}

/* ----- database_exists returns 0 or 1 ----- */
if(isset($GLOBALS["dbname"]) && $GLOBALS["dbname"]) {
	$result = database_exists($GLOBALS["dbname"]);
	is_equal("database_exists returns integer", is_int($result) ? 1 : 0, 1);
}

/* ----- get_raum_gebaeude_array (may need DB, but flexible) ----- */
if(isset($GLOBALS["dbname"]) && $GLOBALS["dbname"]) {
	$arr = get_raum_gebaeude_array();
	is_equal("get_raum_gebaeude_array returns array", is_array($arr) ? 1 : 0, 1);
}

/* ----- get_dozent_array (may need DB, but flexible) ----- */
if(isset($GLOBALS["dbname"]) && $GLOBALS["dbname"]) {
	$arr = get_dozent_array();
	is_equal("get_dozent_array returns array", is_array($arr) ? 1 : 0, 1);
}

/* ----- get_veranstaltungsabkuerzung_array (may need DB, but flexible) ----- */
if(isset($GLOBALS["dbname"]) && $GLOBALS["dbname"]) {
	$arr = get_veranstaltungsabkuerzung_array();
	is_equal("get_veranstaltungsabkuerzung_array returns array", is_array($arr) ? 1 : 0, 1);
}

/* ----- faq_has_entry (may need DB, but flexible) ----- */
if(isset($GLOBALS["dbname"]) && $GLOBALS["dbname"]) {
	$result = faq_has_entry();
	regex_matches("faq_has_entry returns numeric string", (string)$result, "/^\d+$/");
}

/* ----- fill_deletion_global ----- */
/* fill_deletion_global with no matching POST data */
$GLOBALS["deletion_db"] = NULL;
fill_deletion_global(array("nonexistent_key_xyz", "another_nonexistent_xyz"), "veranstaltungstyp");
is_equal("fill_deletion_global (no post data) keeps GLOBALS deletion_db = NULL", $GLOBALS["deletion_db"], NULL);

/* fill_deletion_global with simulated post data via debugvalues */
fill_deletion_global(array("update_veranstaltungstyp", "id"), "veranstaltungstyp", array("update_veranstaltungstyp" => 1, "id" => 2));
is_equal("fill_deletion_global (with debugvalues) sets GLOBALS deletion_db", $GLOBALS["deletion_db"], "veranstaltungstyp");

/* Reset */
$GLOBALS["deletion_db"] = NULL;
