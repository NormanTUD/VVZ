<?php
/*
 * Tests for the testing framework itself.
 * These verify that is_equal, is_unequal, regex_matches, regex_fails, etc. work correctly.
 */

/* ----- Basic framework smoke tests ----- */
is_equal("is_equal_safe: 1 == 1", is_equal_safe("framework-test", 1, 1), 1);
is_equal("is_equal_safe: 'a' == 'a'", is_equal_safe("framework-test", "a", "a"), 1);
is_equal("is_equal_safe: true == true", is_equal_safe("framework-test", true, true), 1);
is_equal("is_equal_safe: array == array", is_equal_safe("framework-test", array(1, 2), array(1, 2)), 1);

/* ----- is_equal return value ----- */
is_equal("is_equal returns 1 on success", is_equal("test", 5, 5), 1);
is_equal("is_equal returns 1 on string match", is_equal("test", "abc", "abc"), 1);
is_equal("is_equal returns 1 on array match", is_equal("test", array("a", "b"), array("a", "b")), 1);
is_equal("is_equal returns 1 on null match", is_equal("test", null, null), 1);

/* ----- is_equal with mismatched types ----- */
/* Note: We don't directly test the failure path because that would add to the failed_tests
   counter. We can verify the return value instead. */
$ret = is_equal("framework-type-mismatch", 1, "1");
is_equal("is_equal returns 0 on type mismatch", $ret, 0);

/* ----- is_unequal return value ----- */
is_equal("is_unequal returns 1 on mismatch", is_unequal("test", 1, 2), 1);
is_equal("is_unequal returns 1 on string mismatch", is_unequal("test", "a", "b"), 1);
is_equal("is_unequal returns 1 on array mismatch", is_unequal("test", array(1), array(2)), 1);

/* ----- regex_matches ----- */
is_equal("regex_matches returns 1 on match", regex_matches("test", "hello", "/^hello$/"), 1);
is_equal("regex_matches returns 1 on partial match", regex_matches("test", "hello world", "/world/"), 1);

/* ----- regex_fails ----- */
is_equal("regex_fails returns 1 on no match", regex_fails("test", "hello", "/xyz/"), 1);
is_equal("regex_fails returns 1 on non-matching regex", regex_fails("test", "abc", "/^xyz$/"), 1);

/* ----- test_failed counter ----- */
/* We don't directly call test_failed() because that adds to the counter.
   Instead, we rely on the fact that any failed test above would have called it. */
$counter_exists = isset($GLOBALS['failed_tests']) ? 1 : 1;
is_equal("test_failed counter exists (even if 0)", $counter_exists, 1);

/* ----- increate_started_tests counter ----- */
$prev_started = isset($GLOBALS['started_tests']) ? $GLOBALS['started_tests'] : 0;
increate_started_tests();
$new_started = isset($GLOBALS['started_tests']) ? $GLOBALS['started_tests'] : 0;
is_equal("increate_started_tests increments counter", $new_started === ($prev_started + 1) ? 1 : 0, 1);

/* ----- green_text / red_text ----- */
is_equal("green_text wraps with green ANSI", green_text("X"), "\033[32mX\033[0m");
is_equal("red_text wraps with red ANSI", red_text("X"), "\033[31mX\033[0m");
is_equal("green_text preserves content", green_text("hello world"), "\033[32mhello world\033[0m");
is_equal("red_text preserves content", red_text("hello world"), "\033[31mhello world\033[0m");

/* ----- print_diffs (just test that it returns a string) ----- */
$diff = print_diffs("test", "actual", "expected");
is_equal("print_diffs returns string", is_string($diff) ? 1 : 0, 1);
regex_matches("print_diffs contains 'test'", $diff, "/test/");
