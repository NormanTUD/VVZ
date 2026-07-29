<?php
/*
 * Tests for HTML output and formatting functions.
 */

/* ----- strip_tags_attributes ----- */
$stripped = strip_tags_attributes("<b>hello</b>");
is_equal("strip_tags_attributes keeps <b>", strip_tags_attributes("<b>hello</b>"), "<b>hello</b>");
is_equal("strip_tags_attributes strips <script>", strip_tags_attributes("<script>alert('xss')</script>"), "alert('xss')");

/* strip onClick */
$with_event = "<a href=\"#\" onclick=\"alert(1)\">click</a>";
$cleaned = strip_tags_attributes($with_event);
is_equal("strip_tags_attributes removes onclick", preg_match("/onclick/i", $cleaned) ? 0 : 1, 1);
regex_matches("strip_tags_attributes still contains <a> tag", $cleaned, "/<a/");

/* onmouseover */
$mouseover = "<div onmouseover=\"bad()\">content</div>";
$cleaned2 = strip_tags_attributes($mouseover);
is_equal("strip_tags_attributes removes onmouseover", preg_match("/onmouseover/i", $cleaned2) ? 0 : 1, 1);

/* javascript: URLs */
$js_url = "<a href=\"javascript:alert(1)\">x</a>";
$cleaned3 = strip_tags_attributes($js_url);
is_equal("strip_tags_attributes removes javascript: URL", preg_match("/javascript:/i", $cleaned3) ? 0 : 1, 1);

/* ----- teacher_icon ----- */
$icon = teacher_icon();
regex_matches("teacher_icon returns span with utf8symbol class", $icon, '/<span class="utf8symbol">/');
regex_matches("teacher_icon contains closing span", $icon, '/<\/span>/');

/* ----- print_h, print_h2, print_h3 ----- */
/* Note: print_h returns the output as a string, it doesn't print directly */
$h1_output = print_h("Header 1");
regex_matches("print_h contains h1 tag", $h1_output, "/<h1/");
regex_matches("print_h contains 'Header 1'", $h1_output, "/Header 1/");

$h2_output = print_h2("Header 2");
regex_matches("print_h2 contains h2 tag", $h2_output, "/<h2/");
regex_matches("print_h2 contains 'Header 2'", $h2_output, "/Header 2/");

$h3_output = print_h3("Header 3");
regex_matches("print_h3 contains h3 tag", $h3_output, "/<h3/");
regex_matches("print_h3 contains 'Header 3'", $h3_output, "/Header 3/");

/* print_h with level=2 */
$h2_level_output = print_h("Level 2 Header", 2);
regex_matches("print_h with level 2 uses h2", $h2_level_output, "/<h2/");

/* print_h with level=3 */
$h3_level_output = print_h("Level 3 Header", 3);
regex_matches("print_h with level 3 uses h3", $h3_level_output, "/<h3/");

/* print_h invalid level returns empty */
$invalid_output = print_h("Invalid", "not_a_level");
is_equal("print_h with invalid level returns empty", $invalid_output, "");

/* print_h with level 0 still produces h0 tag */
$h0_output = print_h("Level 0", 0);
regex_matches("print_h with level 0 uses h0", $h0_output, "/<h0/");

/* ----- print_line_link (already in string helpers but verify HTML output) ----- */
is_equal("print_line_link(1) returns HTML link", print_line_link(1), '<a href="#line_1">1</a>');

/* ----- htmle HTML escaping edge cases ----- */
is_equal("htmle encodes single quotes", htmle("it's"), "it&#039;s");
is_equal("htmle encodes double quotes", htmle('say "hi"'), "say &quot;hi&quot;");
is_equal("htmle encodes greater-than", htmle("a > b"), "a &gt; b");
is_equal("htmle encodes less-than", htmle("a < b"), "a &lt; b");
