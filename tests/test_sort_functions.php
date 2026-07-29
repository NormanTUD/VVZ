<?php
/*
 * Tests for sort, comparison, and ordering functions.
 */

/* ----- foreignKeyAscSort ----- */
is_equal("foreignKeyAscSort equal values returns 0", foreignKeyAscSort(array("foreign_keys_counter" => 5), array("foreign_keys_counter" => 5)), 0);
is_equal("foreignKeyAscSort smaller first returns -1", foreignKeyAscSort(array("foreign_keys_counter" => 3), array("foreign_keys_counter" => 5)), -1);
is_equal("foreignKeyAscSort larger first returns 1", foreignKeyAscSort(array("foreign_keys_counter" => 10), array("foreign_keys_counter" => 5)), 1);
is_equal("foreignKeyAscSort with zero vs one", foreignKeyAscSort(array("foreign_keys_counter" => 0), array("foreign_keys_counter" => 1)), -1);
is_equal("foreignKeyAscSort with one vs zero", foreignKeyAscSort(array("foreign_keys_counter" => 1), array("foreign_keys_counter" => 0)), 1);

/* ----- array_multisort behavior via array_sort_by_column ----- */
$arr1 = array(
	array("v" => 3, "label" => "third"),
	array("v" => 1, "label" => "first"),
	array("v" => 2, "label" => "second"),
);
array_sort_by_column($arr1, "v");
is_equal("array_sort_by_column sorts by v ascending", $arr1[0]["label"], "first");
is_equal("array_sort_by_column second", $arr1[1]["label"], "second");
is_equal("array_sort_by_column third", $arr1[2]["label"], "third");

/* Sort with duplicate values */
$arr2 = array(
	array("v" => 2),
	array("v" => 1),
	array("v" => 2),
	array("v" => 1),
);
array_sort_by_column($arr2, "v");
is_equal("array_sort_by_column handles duplicates", $arr2[0]["v"] + $arr2[1]["v"] + $arr2[2]["v"] + $arr2[3]["v"], 6);

/* ----- htmle ordering with shy=1 ----- */
is_equal("htmle shy normal text unchanged", htmle("hallo welt", 1), "hallo welt");
is_equal("htmle shy preserves umlauts in non-special words", htmle("Über den Wolken", 1), "&Uuml;ber den Wolken");

/* ----- print_line_link variations ----- */
is_equal("print_line_link large number", print_line_link(999999), '<a href="#line_999999">999999</a>');
regex_matches("print_line_link with string", print_line_link("L42"), '/href="#line_L42"/');
regex_matches("print_line_link with hyphen", print_line_link("a-b"), '/href="#line_a-b"/');
