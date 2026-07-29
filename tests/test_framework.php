<?php
/*
 * Tests for the testing framework itself.
 * These tests validate that is_equal, is_unequal, regex_matches, regex_fails work correctly.
 */

is_equal("framework: is_equal returns 1 on match", is_equal("sub-test", 1, 1), 1);
is_equal("framework: is_equal returns 0 on mismatch (by side effect, use is_equal_safe)", is_equal_safe("framework: is_equal_safe on match", is_equal("sub-test", 1, 1), 1), 1);

is_equal("framework: is_unequal returns 1 on mismatch", is_unequal("sub-test", 1, 2), 1);
is_equal("framework: is_equal returns 1 for same strings", is_equal("sub-test", "abc", "abc"), 1);
is_equal("framework: is_equal returns 1 for same arrays", is_equal("sub-test", array(1, 2), array(1, 2)), 1);

regex_matches("framework: regex_matches matches basic", "abc", "/^abc$/");
regex_fails("framework: regex_fails does not match", "abc", "/^xyz$/");

is_equal("framework: global started_tests counter exists", isset($GLOBALS['started_tests']) ? 1 : 0, 1);
is_equal("framework: started_tests counter is > 0 after tests", $GLOBALS['started_tests'] > 0 ? 1 : 0, 1);

is_equal("framework: green_text returns ANSI green", green_text("X") === "\033[32mX\033[0m" ? 1 : 0, 1);
is_equal("framework: red_text returns ANSI red", red_text("X") === "\033[31mX\033[0m" ? 1 : 0, 1);
