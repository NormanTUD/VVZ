<?php
/*
 * Tests for string helper functions.
 * Pure functions (no DB dependency) that operate on strings.
 */

/* ----- htmle ----- */
is_equal("htmle('hallo') returns htmlentities-encoded string", htmle("hallo"), "hallo");
is_equal("htmle('<b>hallo</b>') encodes entities", htmle("<b>hallo</b>"), "&lt;b&gt;hallo&lt;/b&gt;");
is_equal("htmle('&') encodes ampersand", htmle("&"), "&amp;");
is_equal("htmle('') returns em-dash", htmle(""), "&mdash;");
is_equal("htmle(null) returns em-dash", htmle(null), "&mdash;");
/* Note: htmle('0') returns '&mdash;' because PHP's `if($str)` is falsy for '0' */

/* htmle with shy=1 wraps long German words with soft hyphens */
is_equal("htmle('Philosophie', 1) wraps with shy", htmle("Philosophie", 1), 'Phi&shy;lo&shy;so&shy;phie');
is_equal("htmle('Wissenschaft', 1) wraps with shy", htmle("Wissenschaft", 1), 'Wis&shy;sen&shy;schaft');
is_equal("htmle('Erkenntnis', 1) wraps with shy", htmle("Erkenntnis", 1), 'Er&shy;kennt&shy;nis');
is_equal("htmle('Theorie', 1) wraps with shy", htmle("Theorie", 1), 'Theo&shy;rie');
is_equal("htmle('Sprachphilosophie', 1) wraps with shy", htmle("Sprachphilosophie", 1), 'Sprach&shy;phi&shy;lo&shy;so&shy;phie');
is_equal("htmle('Religion', 1) wraps with shy", htmle("Religion", 1), 'Re&shy;li&shy;gion');
is_equal("htmle('Anthropologie', 1) wraps with shy", htmle("Anthropologie", 1), 'An&shy;thro&shy;po&shy;lo&shy;gie');
is_equal("htmle('Moralphilosophie', 1) wraps with shy", htmle("Moralphilosophie", 1), 'Mo&shy;ral&shy;phi&shy;lo&shy;so&shy;phie');
is_equal("htmle('Philosophische', 1) wraps with shy", htmle("Philosophische", 1), 'Phi&shy;lo&shy;so&shy;phi&shy;sche');
is_equal("htmle('Seminararbeit', 1) wraps with shy", htmle("Seminararbeit", 1), 'Se&shy;mi&shy;nar&shy;ar&shy;beit');

/* htmle with shy=1 on empty returns em-dash */
is_equal("htmle('', 1) returns em-dash", htmle("", 1), "&mdash;");
is_equal("htmle(null, 1) returns em-dash", htmle(null, 1), "&mdash;");

/* ----- escapeJsonString ----- */
is_equal("escapeJsonString('hello')", escapeJsonString("hello"), "hello");
is_equal("escapeJsonString('hello\"world') escapes double quote", escapeJsonString("hello\"world"), "hello\\\"world");
is_equal("escapeJsonString('a\\\\b') escapes backslash", escapeJsonString("a\\b"), "a\\\\b");
is_equal("escapeJsonString('a/b') escapes forward slash", escapeJsonString("a/b"), "a\\/b");
is_equal("escapeJsonString('a\\nb') escapes newline", escapeJsonString("a\nb"), "a\\nb");
is_equal("escapeJsonString('a\\rb') escapes carriage return", escapeJsonString("a\rb"), "a\\rb");
is_equal("escapeJsonString('a\\tb') escapes tab", escapeJsonString("a\tb"), "a\\tb");
is_equal("escapeJsonString('') returns empty", escapeJsonString(""), "");

/* ----- my_strip_tags ----- */
is_equal("my_strip_tags('<b>hallo</b>')", my_strip_tags("<b>hallo</b>"), "hallo");
is_equal("my_strip_tags('<br>hallo') converts br to newline", my_strip_tags("<br>hallo"), "\nhallo");
is_equal("my_strip_tags('<br/>hallo') converts self-closing br", my_strip_tags("<br/>hallo"), "\nhallo");
is_equal("my_strip_tags('<br />hallo') converts spaced br", my_strip_tags("<br />hallo"), "\nhallo");
is_equal("my_strip_tags('<br><i>hallo</i><br>')", my_strip_tags('<br><i>hallo</i><br>'), "\nhallo\n");
is_equal("my_strip_tags('plain text')", my_strip_tags("plain text"), "plain text");
is_equal("my_strip_tags('') returns empty", my_strip_tags(""), "");

/* ----- add_leading_zero ----- */
is_equal("add_leading_zero(2)", add_leading_zero(2), "02");
is_equal("add_leading_zero(9)", add_leading_zero(9), "09");
is_equal("add_leading_zero(0)", add_leading_zero(0), "00");
is_equal("add_leading_zero('10') keeps '10'", add_leading_zero("10"), "10");
is_equal("add_leading_zero('99') keeps '99'", add_leading_zero("99"), "99");
is_equal("add_leading_zero('5') prepends zero", add_leading_zero("5"), "05");

/* ----- get_previous_letter ----- */
is_equal("get_previous_letter('z')", get_previous_letter("z"), "y");
is_equal("get_previous_letter('Y')", get_previous_letter("Y"), "X");
/* Note: get_previous_letter('a') has buggy behavior at 'a' boundary - returns empty/garbage */
is_equal("get_previous_letter('A')", get_previous_letter("A"), "A");
is_equal("get_previous_letter('b')", get_previous_letter("b"), "a");
is_equal("get_previous_letter('B')", get_previous_letter("B"), "A");

/* ----- comma_list_to_array ----- */
is_equal("comma_list_to_array('1,2,3,a')", comma_list_to_array("1,2,3,a"), array("1", "2", "3", "a"));
is_equal("comma_list_to_array('') returns array with empty", comma_list_to_array(""), array(""));
is_equal("comma_list_to_array('a')", comma_list_to_array("a"), array("a"));
is_equal("comma_list_to_array(',a') trims leading comma", comma_list_to_array(",a"), array("a"));
is_equal("comma_list_to_array('a,') trims trailing comma", comma_list_to_array("a,"), array("a"));
is_equal("comma_list_to_array('a,,b') preserves empty between", comma_list_to_array("a,,b"), array("a", "", "b"));

/* ----- rarr ----- */
is_equal("rarr('a&rarr;b') replaces entity", rarr("a&rarr;b"), "a→b");
is_equal("rarr('plain text') unchanged", rarr("plain text"), "plain text");
is_equal("rarr('') returns empty", rarr(""), "");

/* ----- mask_module ----- */
is_equal("mask_module('aaa')", mask_module("aaa"), "<i>aaa</i>");
is_equal("mask_module('hallo')", mask_module("hallo"), "<i>hallo</i>");
is_equal("mask_module('') returns empty i tags", mask_module(""), "<i></i>");

/* ----- print_line_link ----- */
is_equal("print_line_link(42)", print_line_link(42), '<a href="#line_42">42</a>');
is_equal("print_line_link(0)", print_line_link(0), '<a href="#line_0">0</a>');
regex_matches("print_line_link('abc') produces link with abc", print_line_link("abc"), '/^<a href="#line_abc">abc<\/a>$/');
