<?php
/*
 * Tests for type validation and input checking functions.
 */

/* ----- is_valid_auth_code ----- */
is_equal("is_valid_auth_code(null) returns 0", is_valid_auth_code(null), 0);
is_equal("is_valid_auth_code('') returns 0", is_valid_auth_code(""), 0);
is_equal("is_valid_auth_code(false) returns 0", is_valid_auth_code(false), 0);
is_equal("is_valid_auth_code returns int", is_int(is_valid_auth_code("invalid_code_xyz_999")) ? 1 : 0, 1);
is_equal("is_valid_auth_code returns 0 or 1", in_array(is_valid_auth_code("invalid_xyz"), array(0, 1)) ? 1 : 0, 1);

/* ----- institut_id_exists (DB needed) ----- */
if(isset($GLOBALS["dbname"]) && $GLOBALS["dbname"]) {
	$result = institut_id_exists(99999999);
	/* institut_id_exists returns the count(*) result, which can be 0, null, or "0" depending on
	   the DB driver. For non-existent IDs it should be falsy. */
	is_equal("institut_id_exists returns falsy for non-existent", !$result ? 1 : 0, 1);
	$result_existing = institut_id_exists(1);
	is_equal("institut_id_exists(1) returns truthy for existing", (int)$result_existing > 0 ? 1 : 0, 1);
}

/* ----- file_is_image ----- */
is_equal("file_is_image(null) returns false", file_is_image(null) === false ? 1 : 0, 1);
is_equal("file_is_image(empty) returns false", file_is_image("") === false ? 1 : 0, 1);
is_equal("file_is_image('nonexistent.png') returns false", file_is_image("this_file_definitely_does_not_exist_xyz.png") === false ? 1 : 0, 1);
is_equal("file_is_image returns bool", is_bool(file_is_image("anyfile.txt")) ? 1 : 0, 1);

/* ----- check_values - only test if function exists (defined in functions.php) ----- */
/* In production with full functions.php loaded, check_values is defined.
   In pure test runner, it's not. We skip if missing. */
if(function_exists("check_values")) {
	is_equal("check_values function exists", 1, 1);
}

/* ----- get_post_multiple_check edge cases ----- */
$_POST = array();
is_equal("get_post_multiple_check empty array with no post", get_post_multiple_check(array()), 1);
is_equal("get_post_multiple_check single key returns 0 if not set", get_post_multiple_check(array("nonexistent_key_xyz")), 0);

$_POST["x"] = "1";
$_POST["y"] = "2";
is_equal("get_post_multiple_check all set returns 1", get_post_multiple_check(array("x", "y")), 1);

$_POST = array();

/* ----- get_post_int edge cases ----- */
$_POST["zero_str"] = "0";
is_equal("get_post_int('zero_str') returns 0", get_post_int("zero_str"), 0);

$_POST["negative_str"] = "-100";
is_equal("get_post_int('negative_str') returns -100", get_post_int("negative_str"), -100);

$_POST["empty_str"] = "";
is_equal("get_post_int('empty_str') returns 0", get_post_int("empty_str"), 0);

$_POST["big_str"] = "999999";
is_equal("get_post_int('big_str') returns 999999", get_post_int("big_str"), 999999);

$_POST = array();
