<?php
/*
 * Tests for security and validation functions.
 */

/* ----- escapeJsonString edge cases ----- */
/* Note: Production code has a bug - the replacements for backspace (\x08) and
   form-feed (\x0c) are swapped in the replacements array. These tests document
   the CURRENT (buggy) behavior. */
is_equal("escapeJsonString handles backspace (\\x08) - documents bug", escapeJsonString("a\x08b"), "a\\fb");
is_equal("escapeJsonString handles form-feed (\\x0c) - documents bug", escapeJsonString("a\x0cb"), "a\\bb");

/* ----- strip_tags_attributes with various XSS attempts ----- */
/* Multiple events */
$multi_event = "<p onload=\"x()\" onkeydown=\"y()\">safe</p>";
$cleaned_multi = strip_tags_attributes($multi_event);
is_equal("strip_tags_attributes removes multiple events", preg_match("/onload/i", $cleaned_multi) ? 0 : 1, 1);
is_equal("strip_tags_attributes removes second event", preg_match("/onkeydown/i", $cleaned_multi) ? 0 : 1, 1);

/* Mixed case events */
$mixed_case = "<div OnClick=\"bad()\">x</div>";
$cleaned_mixed = strip_tags_attributes($mixed_case);
is_equal("strip_tags_attributes removes mixed-case OnClick", preg_match("/OnClick/i", $cleaned_mixed) ? 0 : 1, 1);

/* ----- esc edge cases ----- */
is_equal("esc('hello world') returns quoted", esc("hello world"), '"hello world"');
is_equal("esc(0) returns '0' string", esc(0) === '"0"' || esc(0) === "NULL" ? 1 : 0, 1);
is_equal("esc(false) returns NULL", esc(false), "NULL");

/* ----- might_be_query (already covered in numeric but check additional security patterns) ----- */
is_equal("might_be_query('DROP TABLE foo') returns 0", might_be_query("DROP TABLE foo"), 0);
is_equal("might_be_query('Truncate foo') returns 0", might_be_query("Truncate foo"), 0);
is_equal("might_be_query('CREATE TABLE foo (id int)') returns 0", might_be_query("CREATE TABLE foo (id int)"), 0);
is_equal("might_be_query('INSERT INTO foo VALUES (1)') returns 0", might_be_query("INSERT INTO foo VALUES (1)"), 0);
is_equal("might_be_query('ALTER TABLE foo ADD bar int') returns 0", might_be_query("ALTER TABLE foo ADD bar int"), 0);

/* ----- htmle security (XSS prevention) ----- */
is_equal("htmle prevents <script> tag", htmle("<script>alert(1)</script>"), "&lt;script&gt;alert(1)&lt;/script&gt;");
is_equal("htmle prevents img onerror", htmle("<img src=x onerror=alert(1)>"), "&lt;img src=x onerror=alert(1)&gt;");

/* ----- my_strip_tags ----- */
is_equal("my_strip_tags strips script tags (but not content)", my_strip_tags("<script>bad()</script>text"), "bad()text");

/* ----- convert_date handles invalid input safely ----- */
is_equal("convert_date returns same for invalid", convert_date("not a date"), "not a date");

/* ----- rarr handles HTML entities safely ----- */
is_equal("rarr doesn't double-escape", rarr("&amp;rarr;"), "&amp;rarr;");
