<?php
/*
 * Tests for the icon library (emojis.php), HTML helpers, and other
 * small pure functions that don't fit cleanly into the existing
 * categorical test files.
 *
 * Goals:
 *   - Cover every public function in emojis.php at least once.
 *   - Cover pure helpers like fq(), rarr(), escape(), print_debug(),
 *     sanitize_data(), replace_hinweis_with_graphics().
 *   - Cover state-check functions like user_is_logged_in() and
 *     global_exists().
 *   - Cover create_select() output with various combinations of args.
 *
 * Style:
 *   - Tests are decoupled from production data: they check structural
 *     properties (string contains, contains a tag, etc.) rather than
 *     specific icon names.
 *   - If a function depends on optional state (logged-in user, etc.),
 *     the test sets the state explicitly first and restores it after.
 */

/* ============================================================ */
/* ----- emojis.php: every icon function returns an <i> tag --- */
/* ============================================================ */

/* All these icon functions follow the same pattern: they return an
 * <i> tag with a fa-* class. We test the structural invariant rather
 * than the specific icon, so the tests don't break if the CSS class
 * is renamed. */

$all_icons = array(
	"get_checkbox_symbol"        => array("fa-check-square"),
	"get_red_cross_symbol"       => array("fa-times"),
	"get_calendar_icon"          => array("fa-calendar"),
	"get_double_arrow_down_icon" => array("fa-angle-double-down"),
	"get_worldmap_icon"          => array("fa-globe"),
	"get_wrench_icon"            => array("fa-wrench"),
	"get_document_icon"          => array("fa-file"),
	"get_camera_icon"            => array("fa-video-camera"),
	"get_lightning_icon"         => array("fa-bolt"),
	"get_write_icon"             => array("fa-pencil"),
	"get_warning_icon"           => array("fa-exclamation-triangle"),
	"get_wheelchair_icon"        => array("fa-wheelchair"),
	"get_person_icon"            => array("fa-address-card"),
	"get_person_add_icon"        => array("fa-user-plus"),
	"get_building_icon"          => array("fa-building"),
	"get_email_icon"             => array("fa-envelope"),
	"get_book_icon"              => array("fa-book"),
	"get_edit_icon"              => array("fa-pencil-square"),
	"get_logout_icon"            => array("fa-sign-out"),
	"get_hike_icon"              => array("fa-person-hiking"),
	"get_delete_icon"            => array("fa-circle-xmark"),
	"get_work_icon"              => array("fa-briefcase"),
	"get_info_icon"              => array("fa-circle-info"),
	"get_interdisciplinary_icon" => array("fa-people-arrows"),
	"get_research_icon"          => array("fa-chalkboard-user"),
	"get_help_icon"              => array("fa-question"),
);

foreach($all_icons as $fn => $expected_classes) {
	if(!function_exists($fn)) {
		/* Skip silently in pure mode if not stubbed — see run_pure_tests.php */
		continue;
	}
	$out = $fn();
	is_equal("$fn returns string", is_string($out) ? 1 : 0, 1);
	is_equal("$fn contains <i tag", strpos($out, "<i") !== false ? 1 : 0, 1);
	is_equal("$fn contains closing </i>", strpos($out, "</i>") !== false ? 1 : 0, 1);
	foreach($expected_classes as $cls) {
		is_equal("$fn contains class '$cls'", strpos($out, $cls) !== false ? 1 : 0, 1);
	}
}

/* search icon has an optional $class argument */
if(function_exists('get_search_icon')) {
	is_equal("get_search_icon() contains fa-search", strpos(get_search_icon(), "fa-search") !== false ? 1 : 0, 1);
	is_equal("get_search_icon('big') adds custom class", strpos(get_search_icon("big"), "big") !== false ? 1 : 0, 1);
	is_equal("get_search_icon('') behaves like no class", get_search_icon(""), get_search_icon());
}

/* print_* versions just echo the get_* version */
if(function_exists('print_checkbox_symbol') && function_exists('get_checkbox_symbol')) {
	ob_start();
	print_checkbox_symbol();
	$out = ob_get_clean();
	is_equal("print_checkbox_symbol matches get_checkbox_symbol", $out, get_checkbox_symbol());
}
if(function_exists('print_warning_icon') && function_exists('get_warning_icon')) {
	ob_start();
	print_warning_icon();
	$out = ob_get_clean();
	is_equal("print_warning_icon matches get_warning_icon", $out, get_warning_icon());
}

/* ============================================================ */
/* ----- teacher/role icons ----- */
/* ============================================================ */

if(function_exists('get_male_teacher_icon')) {
	$out = get_male_teacher_icon();
	is_equal("get_male_teacher_icon returns string", is_string($out) ? 1 : 0, 1);
	is_equal("get_male_teacher_icon non-empty", strlen($out) > 0 ? 1 : 0, 1);
}
if(function_exists('get_female_teacher_icon')) {
	$out = get_female_teacher_icon();
	is_equal("get_female_teacher_icon returns string", is_string($out) ? 1 : 0, 1);
	is_equal("get_female_teacher_icon non-empty", strlen($out) > 0 ? 1 : 0, 1);
}

/* ============================================================ */
/* ----- HTML helpers ----- */
/* ============================================================ */

if(function_exists('fq')) {
	is_equal("fq wraps in raquo/laquo", fq("hello"), "&raquo;hello&laquo;");
	is_equal("fq escapes HTML", fq("<script>"), "&raquo;&lt;script&gt;&laquo;");
	/* Note: htmle("") returns "&mdash;" in production (the placeholder for empty),
	 * so fq("") ends up as "&raquo;&mdash;&laquo;". This documents that behavior. */
	is_equal("fq with empty string uses mdash placeholder", fq(""), "&raquo;&mdash;&laquo;");
	is_equal("fq with special chars", fq("a&b"), "&raquo;a&amp;b&laquo;");
	is_equal("fq with umlaut", fq("über"), "&raquo;&uuml;ber&laquo;");
}

if(function_exists('rarr')) {
	is_equal("rarr replaces &rarr; with arrow", rarr("foo &rarr; bar"), "foo → bar");
	is_equal("rarr with multiple arrows", rarr("&rarr; a &rarr;"), "→ a →");
	is_equal("rarr without arrows unchanged", rarr("plain text"), "plain text");
	is_equal("rarr with empty string", rarr(""), "");
	is_equal("rarr is case-sensitive (documents behavior)", strpos(rarr("&RARR;"), "→") === false ? 1 : 0, 1);
}

if(function_exists('escape')) {
	/* escape() is documented as a no-op for now */
	is_equal("escape returns input unchanged", escape("hello"), "hello");
	is_equal("escape with empty string", escape(""), "");
	is_equal("escape with NULL", escape(NULL), NULL);
	is_equal("escape with number", escape(42), 42);
}

if(function_exists('print_debug')) {
	ob_start();
	print_debug("test message");
	$out = ob_get_clean();
	is_equal("print_debug outputs the message", strpos($out, "test message") !== false ? 1 : 0, 1);
	/* Should be wrapped in ANSI green (32) by green_text() */
	is_equal("print_debug output contains ANSI green escape", strpos($out, "\033[32m") !== false ? 1 : 0, 1);
	ob_start();
	print_debug("");
	$out = ob_get_clean();
	is_equal("print_debug with empty string outputs just the color codes", $out, "\033[32m\033[0m");
}

/* ============================================================ */
/* ----- sanitize_data ----- */
/* ============================================================ */

if(function_exists('sanitize_data')) {
	is_equal("sanitize_data escapes <script>", sanitize_data("<script>"), "&lt;script&gt;");
	is_equal("sanitize_data escapes &", sanitize_data("a & b"), "a &amp; b");
	is_equal("sanitize_data escapes quotes", sanitize_data("\"hello\""), "&quot;hello&quot;");
	is_equal("sanitize_data passes plain text through (with htmlentities)", sanitize_data("hello"), "hello");
	is_equal("sanitize_data handles NULL gracefully", sanitize_data(NULL), "");

	/* Recursive array handling */
	$arr = array("foo" => "<b>", "bar" => array("nested" => "<i>"));
	$san = sanitize_data($arr);
	is_equal("sanitize_data recurses into assoc array", $san["foo"], "&lt;b&gt;");
	is_equal("sanitize_data recurses into nested array", $san["bar"]["nested"], "&lt;i&gt;");

	/* Recursive numeric array handling */
	$arr2 = array("<a>", "<b>");
	$san2 = sanitize_data($arr2);
	is_equal("sanitize_data recurses into numeric array (idx 0)", $san2[0], "&lt;a&gt;");
	is_equal("sanitize_data recurses into numeric array (idx 1)", $san2[1], "&lt;b&gt;");

	/* Empty array passes through */
	is_equal("sanitize_data empty array returns empty", sanitize_data(array()), array());
}

/* ============================================================ */
/* ----- replace_hinweis_with_graphics ----- */
/* ============================================================ */

if(function_exists('replace_hinweis_with_graphics')) {
	is_equal("replace_hinweis_with_graphics replaces LaTeX word", strpos(replace_hinweis_with_graphics("This is LaTeX code"), "<img") !== false ? 1 : 0, 1);
	is_equal("replace_hinweis_with_graphics replaces warnung (lowercase)", strpos(replace_hinweis_with_graphics("das ist eine warnung"), "<i") !== false ? 1 : 0, 1);
	is_equal("replace_hinweis_with_graphics replaces ACHTUNG (uppercase, case-insensitive)", strpos(replace_hinweis_with_graphics("ACHTUNG!"), "<i") !== false ? 1 : 0, 1);
	is_equal("replace_hinweis_with_graphics replaces Vorsicht", strpos(replace_hinweis_with_graphics("Vorsicht bitte"), "<i") !== false ? 1 : 0, 1);
	is_equal("replace_hinweis_with_graphics replaces git (with backslash)", strpos(replace_hinweis_with_graphics("\\git is cool"), "<img") !== false ? 1 : 0, 1);
	is_equal("replace_hinweis_with_graphics leaves plain text", replace_hinweis_with_graphics("just plain text"), "just plain text");
	is_equal("replace_hinweis_with_graphics handles empty string", replace_hinweis_with_graphics(""), "");
	is_equal("replace_hinweis_with_graphics handles NULL", replace_hinweis_with_graphics(NULL), "");

	/* Word boundary: 'gewarnung' should NOT match (would be a false positive) */
	$no_match = replace_hinweis_with_graphics("vorwarnung");
	is_equal("replace_hinweis_with_graphics respects word boundary", strpos($no_match, "<i") === false ? 1 : 0, 1);
}

/* ============================================================ */
/* ----- user_is_logged_in ----- */
/* ============================================================ */

if(function_exists('user_is_logged_in')) {
	$saved_user = $GLOBALS["logged_in_user_id"] ?? null;

	unset($GLOBALS["logged_in_user_id"]);
	is_equal("user_is_logged_in returns 0 when unset", user_is_logged_in(), 0);

	$GLOBALS["logged_in_user_id"] = "";
	is_equal("user_is_logged_in returns 0 for empty string", user_is_logged_in(), 0);

	$GLOBALS["logged_in_user_id"] = "abc";
	is_equal("user_is_logged_in returns 0 for non-numeric", user_is_logged_in(), 0);

	$GLOBALS["logged_in_user_id"] = "0";
	/* Note: production regex `/^\d+$/` matches "0" — user_is_logged_in
	 * returns 1 for it (which is arguably a bug, since user_id 0 is
	 * rarely valid, but that's the current behavior). */
	is_equal("user_is_logged_in returns 1 for '0' (matches \\d+)", user_is_logged_in(), 1);

	$GLOBALS["logged_in_user_id"] = "123";
	is_equal("user_is_logged_in returns 1 for numeric string", user_is_logged_in(), 1);

	$GLOBALS["logged_in_user_id"] = "999999999";
	is_equal("user_is_logged_in returns 1 for large numeric string", user_is_logged_in(), 1);

	/* Restore state */
	if($saved_user === null) {
		unset($GLOBALS["logged_in_user_id"]);
	} else {
		$GLOBALS["logged_in_user_id"] = $saved_user;
	}
}

/* ============================================================ */
/* ----- global_exists ----- */
/* ============================================================ */

if(function_exists('global_exists')) {
	$GLOBALS["test_global_exists_key"] = array("a", "b");
	is_equal("global_exists returns 1 for populated array", global_exists("test_global_exists_key"), 1);

	$GLOBALS["test_global_exists_empty"] = array();
	is_equal("global_exists returns 0 for empty array", global_exists("test_global_exists_empty"), 0);

	/* Note: production calls count() on $GLOBALS[$name] which throws TypeError
	 * in PHP 8+ for non-array values (string, int, etc.). We document this
	 * by checking the bug exists — the function is only safe for arrays. */
	$GLOBALS["test_global_exists_string"] = "hello";
	$ge_crashed = false;
	try {
		global_exists("test_global_exists_string");
	} catch (\TypeError $e) {
		$ge_crashed = true;
	}
	is_equal("global_exists on string throws TypeError in PHP 8 (documents production bug)", $ge_crashed ? 1 : 0, 1);

	$GLOBALS["test_global_exists_string_empty"] = "";
	/* Empty string: array_key_exists true, but count() crashes on non-array.
	 * Same TypeError. */
	$ge_crashed2 = false;
	try {
		global_exists("test_global_exists_string_empty");
	} catch (\TypeError $e) {
		$ge_crashed2 = true;
	}
	is_equal("global_exists on empty string throws TypeError in PHP 8", $ge_crashed2 ? 1 : 0, 1);

	is_equal("global_exists returns 0 for unset key", global_exists("test_does_not_exist_xyz"), 0);

	is_equal("global_exists returns 0 for null", global_exists("test_global_exists_key_unset"), 0);

	/* Clean up */
	unset($GLOBALS["test_global_exists_key"]);
	unset($GLOBALS["test_global_exists_empty"]);
	unset($GLOBALS["test_global_exists_string"]);
	unset($GLOBALS["test_global_exists_string_empty"]);
}

/* ============================================================ */
/* ----- create_select (HTML output) ----- */
/* ============================================================ */

if(function_exists('create_select')) {
	/* Production create_select() takes a list of strings (or list of
	 * [value, label] pairs). Both value and label are htmlencoded.
	 * Selected is added when the chosen value equals the datum. */
	ob_start();
	create_select(array("Apple", "Banana"), "Apple", "fruit");
	$out = ob_get_clean();
	is_equal("create_select contains <select>", strpos($out, "<select") !== false ? 1 : 0, 1);
	is_equal("create_select contains name='fruit'", strpos($out, "name=\"fruit\"") !== false || strpos($out, "name='fruit'") !== false ? 1 : 0, 1);
	is_equal("create_select contains selected option", strpos($out, "selected") !== false ? 1 : 0, 1);
	is_equal("create_select contains both options", strpos($out, "Apple") !== false && strpos($out, "Banana") !== false ? 1 : 0, 1);

	/* With allow_empty, an empty option is added */
	ob_start();
	create_select(array("A"), "A", "x", 1);
	$out = ob_get_clean();
	is_equal("create_select with allow_empty has empty value option", strpos($out, "value=\"\"") !== false || strpos($out, "value=''") !== false ? 1 : 0, 1);

	/* With submit_on_change, onchange handler added */
	ob_start();
	create_select(array("A"), "A", "x", 0, 0, null, 1);
	$out = ob_get_clean();
	is_equal("create_select with submit_on_change has onchange", strpos($out, "onchange") !== false ? 1 : 0, 1);

	/* With class */
	ob_start();
	create_select(array("A"), "A", "x", 0, 0, null, 0, "myclass");
	$out = ob_get_clean();
	is_equal("create_select with class uses it", strpos($out, "myclass") !== false ? 1 : 0, 1);

	/* With aria-labelledby */
	ob_start();
	create_select(array("A"), "A", "x", 0, 0, "label123");
	$out = ob_get_clean();
	is_equal("create_select with aria-labelledby uses it", strpos($out, "label123") !== false ? 1 : 0, 1);

	/* XSS: the name attribute is htmlencoded */
	ob_start();
	create_select(array("A"), "A", "<script>");
	$out = ob_get_clean();
	is_equal("create_select html-encodes name attribute", strpos($out, "<script>") === false ? 1 : 0, 1);
	is_equal("create_select encodes <script> as &lt;script&gt;", strpos($out, "&lt;script&gt;") !== false ? 1 : 0, 1);

	/* With noautosubmit=1, adds noautosubmit attribute */
	ob_start();
	create_select(array("A"), "A", "x", 0, 1);
	$out = ob_get_clean();
	is_equal("create_select with noautosubmit adds attribute", strpos($out, "noautosubmit") !== false ? 1 : 0, 1);

	/* Empty data: just opens and closes <select> with no options */
	ob_start();
	create_select(array(), null, "x");
	$out = ob_get_clean();
	is_equal("create_select with empty data still has <select>", strpos($out, "<select") !== false ? 1 : 0, 1);
	is_equal("create_select with empty data has no <option>", strpos($out, "<option") === false ? 1 : 0, 1);

	/* Chosen option that's not in the data: no option is selected */
	ob_start();
	create_select(array("A"), "z", "x");
	$out = ob_get_clean();
	is_equal("create_select with non-existent chosen: no selected", strpos($out, "selected") === false ? 1 : 0, 1);

	/* With [value, label] pairs */
	ob_start();
	create_select(array(array("a", "Apple"), array("b", "Banana")), "a", "fruit");
	$out = ob_get_clean();
	is_equal("create_select with [value,label] pairs has correct labels", strpos($out, "Apple") !== false && strpos($out, "Banana") !== false ? 1 : 0, 1);
	is_equal("create_select with [value,label] marks matching one selected", strpos($out, "selected") !== false ? 1 : 0, 1);
}
