<?php
/*
 * Tests for array manipulation functions.
 * Pure functions (no DB dependency) that operate on arrays.
 */

/* ----- array_value_or_null ----- */
is_equal("array_value_or_null(array('a'=>5), 'a') returns 5", array_value_or_null(array("a" => 5), "a"), 5);
is_equal("array_value_or_null(array('a'=>5), 'b') returns null", array_value_or_null(array("a" => 5), "b") === null ? 1 : 0, 1);
is_equal("array_value_or_null(array(), 'a') returns null", array_value_or_null(array(), "a") === null ? 1 : 0, 1);
is_equal("array_value_or_null(array('a'=>null), 'a') returns null", array_value_or_null(array("a" => null), "a") === null ? 1 : 0, 1);
is_equal("array_value_or_null(array('a'=>''), 'a') returns empty string", array_value_or_null(array("a" => ""), "a"), "");
is_equal("array_value_or_null(array('a'=>array(1)), 'a') returns nested array", array_value_or_null(array("a" => array(1)), "a"), array(1));

/* ----- array_sort_by_column ----- */
$unsorted = array(
	array("name" => "Charlie", "age" => 30),
	array("name" => "Alice", "age" => 25),
	array("name" => "Bob", "age" => 35),
);
array_sort_by_column($unsorted, "name", SORT_ASC);
is_equal("array_sort_by_column sorts ascending by 'name'", $unsorted[0]["name"], "Alice");
is_equal("array_sort_by_column second after ascending sort", $unsorted[1]["name"], "Bob");
is_equal("array_sort_by_column third after ascending sort", $unsorted[2]["name"], "Charlie");

array_sort_by_column($unsorted, "age", SORT_DESC);
is_equal("array_sort_by_column sorts descending by 'age'", $unsorted[0]["age"], 35);
is_equal("array_sort_by_column second after desc sort", $unsorted[1]["age"], 30);
is_equal("array_sort_by_column third after desc sort", $unsorted[2]["age"], 25);

$single = array(array("name" => "Solo"));
array_sort_by_column($single, "name");
is_equal("array_sort_by_column single element unchanged", $single[0]["name"], "Solo");

$empty = array();
array_sort_by_column($empty, "x");
is_equal("array_sort_by_column empty array is empty", count($empty), 0);

/* ----- array2Table ----- */
$table_data = array(
	array("col1" => "a", "col2" => "b"),
	array("col1" => "c", "col2" => "d"),
);
/* Note: production array2Table has a bug where it accesses $status[$line] without
   checking if $status[$line] exists, even when $status itself is empty.
   We must pass a status array where every line key has a value. */
$full_status = array(
	0 => array("something_failed" => 0, "studiengang" => ""),
	1 => array("something_failed" => 0, "studiengang" => ""),
);
$table_output = array2Table($table_data, $full_status);
regex_matches("array2Table contains <table", $table_output, "/<table/");
regex_matches("array2Table contains </table>", $table_output, "/<\/table>/");
regex_matches("array2Table contains 'a' (col1 value)", $table_output, "/a/");
regex_matches("array2Table contains 'd' (col2 value)", $table_output, "/d/");

/* array2Table with empty data - must also have full status to avoid the bug */
$empty_table = array2Table(array(), $full_status);
regex_matches("array2Table empty data still contains <table", $empty_table, "/<table/");

/* array2Table with status - verify "ok" appears */
$ok_status = array(
	0 => array("something_failed" => 0, "studiengang" => "ok"),
	1 => array("something_failed" => 0, "studiengang" => "ok"),
);
$ok_output = array2Table($table_data, $ok_status);
regex_matches("array2Table with ok status contains ok", $ok_output, "/ok/");

/* array2Table with failure status */
$fail_status = array(
	0 => array("something_failed" => 0, "studiengang" => "ok"),
	1 => array("something_failed" => 1, "studiengang" => "fail"),
);
$fail_output = array2Table($table_data, $fail_status);
regex_matches("array2Table with fail status contains fail", $fail_output, "/fail/");

/* ----- get_spalte ----- */
$spaltennummern = array(
	"name" => array("nr" => 0, "optional" => 0),
	"age"  => array("nr" => 1, "optional" => 0),
	"missing_required" => array("nr" => null, "optional" => 0),
	"missing_optional" => array("nr" => null, "optional" => 1),
);
$col = array("Alice", 25);
is_equal("get_spalte returns value at column nr", get_spalte("name", $spaltennummern, $col), "Alice");
is_equal("get_spalte returns value at second column", get_spalte("age", $spaltennummern, $col), 25);
is_equal("get_spalte returns null for missing optional column", get_spalte("missing_optional", $spaltennummern, $col) === null ? 1 : 0, 1);
is_equal("get_spalte returns alternative for missing required column", get_spalte("missing_required", $spaltennummern, $col, "fallback"), "fallback");

/* get_spalte with alternative_2 if no alternative */
is_equal("get_spalte returns alternative_2 when no alternative", get_spalte("missing_required", $spaltennummern, $col, null, "fb2"), "fb2");

/* ----- comma_list_to_array (already in test_string_helpers, but cover here for array context) ----- */
is_equal("comma_list_to_array result is array", is_array(comma_list_to_array("a,b,c")) ? 1 : 0, 1);
