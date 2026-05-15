<?php
	include_once("functions.php");

	if(isset($GLOBALS["reload_page"]) && $GLOBALS['reload_page']) {
		header("Refresh:0");
	}

	if(!$page_title) {
		$page_title = 'Vorlesungsverzeichnis '.$GLOBALS['university_name'];
	}

	if(get_get("studiengang")) {
		$page_title = "$page_title | ".get_studiengang_name(get_get('studiengang'));
	}

	if(get_get("alle_pruefungsnummern")) {
		$page_title = "$page_title | Alle Prüfungsnummern";
	}
?>
<!DOCTYPE html>
<html lang="de">
	<head>
<?php
		#<meta http-equiv="Content-Security-Policy" content="<?php print $GLOBALS["csp_string"]; ? >">
?>
		<meta charset="UTF-8" />
		<!-- Hey, wenn du die Daten dieser Seite brauchst, dann guck doch einfach in die API! Dann brauchst du hier nicht versuchen, HTML mit Regexen zu parsen... -->
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link nonce="<?php print nonce(); ?>" rel="icon" href="favicon.ico" type="image/x-icon" />

<?php
		#<meta http-equiv="X-WebKit-CSP" content="<?php print $GLOBALS['csp_string']; ? >">
?>

		<meta name="description" content="Vorlesungsverzeichnis">
		<meta name="keywords" content="Vorlesungsverzeichnis, <?php print $GLOBALS['university_name']; ?>">
		<meta name="author" content="Norman Koch">
		<meta name="viewport" content="width=device-width, user-scalable=yes">
		<!--<script type="text/javascript" src="mathjax/es5/tex-chtml-full.js?config=TeX-AMS-MML_HTMLorMML"></script>-->
<?php
		if(is_demo()) {
?>
			<meta name="robots" content="noindex" />
<?php
		}
		if(preg_match('/admin/', basename($_SERVER['SCRIPT_NAME']))) {
?>
			<title><?php
				print htmlentities($page_title);
				$chosen_page_id = get_get('page');
				if(!$chosen_page_id) {
					$chosen_page_id = get_get('show_items');
				}
				if($chosen_page_id) {
					if(check_page_rights($chosen_page_id, 0)) {
						$father_page = get_father_page($chosen_page_id);
						if($father_page) {
							print " | ".get_page_name_by_id($father_page);
						}

						$this_page_title = get_page_name_by_id($chosen_page_id);
						if($this_page_title) {
							print " | ".$this_page_title;
						}
					} else {
						print " &mdash; Kein Zugriff auf diese Seite";
					}
				}
			?></title>
<?php
		} else {
?>
			<title><?php print htmlentities($page_title); ?></title>
<?php
		}

		//css(array("font-awesome.min.css"));

		css(
			"css/all.css",
			"css/v4-shims.css"
		);

		if(array_key_exists("SCRIPT_NAME", $_SERVER) && !($_SERVER["SCRIPT_NAME"] == "/admin" || $_SERVER["SCRIPT_NAME"] == "/admin.php")) {
			css(array("foundation.min.css"));
		} else {
			css(array("admin.css"));
		}
?>
		<meta http-equiv="content-type" content="text/html; charset=utf-8">

		<?php
			css(array(
				"jquery-ui.css",
				"style.css",
				"bootstrap-tour-standalone.css",
				"jquery-ui-timepicker-addon.css"
			));

			js(array(
				"jquery-3.6.1.min.js",
				"jquery-ui.js",
				"jquery-ui-timepicker-addon.js",
				"bootstrap-tour-standalone.js",
				"toastr.js",
				"msgs.php",
				"mainscript.php",
				"js/all.js"
			));
		?>
<?php
		if(array_key_exists("logged_in_user_id", $GLOBALS) && $GLOBALS['logged_in_user_id']) {
			js(array("loggedin.js"));
		}
		if($GLOBALS['show_comic_sans']) {
			css("comicsans.css");

			if(preg_match('/startseite/', $_SERVER['SCRIPT_NAME']) && (!get_get('studiengang') || is_null(get_get('studiengang')))) {
				css("clippy.css");
				js("clippy.js");
				js("merlin.js");
			}
		}

		if(basename($_SERVER['SCRIPT_NAME']) == 'api.php') {
			js(array("Chart.bundle.js", "utils.js"));
		}

		if(preg_match('/admin/', basename($_SERVER['SCRIPT_NAME']))) {
?>
			<meta name="robots" content="noindex, nofollow" /> 
<?php
		}

		css("custom.php");
		css("toastr.min.css");

		js(array("color-hash.js"));
?>
		<!-- Anti-FOUC: Apply dark mode class to <html> before anything renders -->
		<script>
			(function() {
				var stored = localStorage.getItem('darkModeEnabled');
				if (stored === 'true' || (stored === null && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
					document.documentElement.classList.add('dark-mode');
				}
			})();
		</script>

		<!--
			DARK MODE — ALL INLINE, LAST IN HEAD.
			This guarantees it loads, has correct path, and overrides everything.
		-->
		<style>
			/* ============================================================
			   DARK MODE MASTER STYLESHEET
			   Using html.dark-mode as root selector + !important on everything
			   to guarantee override of Foundation, jQuery UI, style.css, etc.
			   ============================================================ */

			/* --- BASE --- */
			html.dark-mode,
			html.dark-mode body {
				background-color: #1a1a2e !important;
				color: #e0e0e0 !important;
			}

			html.dark-mode body {
				background-image: none !important;
			}

			/* --- MAIN CONTAINERS --- */
			html.dark-mode #main,
			html.dark-mode #mainindex,
			html.dark-mode #mainindexnocenter,
			html.dark-mode .blurbox,
			html.dark-mode .info,
			html.dark-mode #startseite_text,
			html.dark-mode .startseite_div,
			html.dark-mode .callout,
			html.dark-mode .row,
			html.dark-mode .columns,
			html.dark-mode .column {
				background-color: #16213e !important;
				color: #e0e0e0 !important;
			}

			html.dark-mode #startseite_header {
				background-color: #0f0f1a !important;
				color: #fff !important;
			}

			/* --- HEADINGS --- */
			html.dark-mode h1,
			html.dark-mode h2,
			html.dark-mode h3,
			html.dark-mode h4,
			html.dark-mode h5,
			html.dark-mode h6 {
				color: #e0e0e0 !important;
			}

			/* --- TEXT & PARAGRAPHS --- */
			html.dark-mode p,
			html.dark-mode span,
			html.dark-mode div,
			html.dark-mode li,
			html.dark-mode label,
			html.dark-mode legend,
			html.dark-mode figcaption,
			html.dark-mode blockquote,
			html.dark-mode dt,
			html.dark-mode dd {
				color: inherit !important;
			}

			html.dark-mode .message_text,
			html.dark-mode .color_333,
			html.dark-mode .black_white {
				color: #e0e0e0 !important;
			}

			html.dark-mode .black_white {
				background-color: #1e1e3a !important;
			}

			/* --- LINKS --- */
			html.dark-mode a,
			html.dark-mode a:link,
			html.dark-mode a:visited,
			html.dark-mode a:hover,
			html.dark-mode a:active {
				color: #7eb8ff !important;
			}

			html.dark-mode .no_link,
			html.dark-mode .no_link:visited,
			html.dark-mode .no_link:active {
				color: inherit !important;
			}

			html.dark-mode .no_link:hover {
				color: #7eb8ff !important;
			}

			/* --- TABLES --- */
			html.dark-mode table {
				color: #e0e0e0 !important;
			}

			html.dark-mode td,
			html.dark-mode th {
				border-color: #3a3a5a !important;
				color: inherit !important;
			}

			html.dark-mode th {
				color: #fff !important;
			}

			html.dark-mode tr:hover {
				color: #7eb8ff !important;
			}

			html.dark-mode .bg_ededed,
			html.dark-mode .bg_f5f5f5 {
				background-color: #252540 !important;
			}

			html.dark-mode .bg_DCDCDC {
				background-color: #2a2a4a !important;
			}

			html.dark-mode .bg_A9A9A9 {
				background-color: #3a3a5a !important;
			}

			html.dark-mode .trenner td {
				border-bottom-color: #666 !important;
			}

			/* --- NAVIGATION / MENU --- */
			html.dark-mode div#menu ul {
				background-color: #0f3460 !important;
			}

			html.dark-mode div#menu li a {
				color: #e0e0e0 !important;
			}

			html.dark-mode div#menu li a:hover {
				background-color: #1a1a2e !important;
			}

			html.dark-mode .topnav li a {
				color: #e0e0e0 !important;
				border-color: #444 !important;
			}

			html.dark-mode .topnav ul {
				background: #1e1e3a !important;
			}

			html.dark-mode .topnav li:hover ul li a:hover {
				background: #0f3460 !important;
			}

			html.dark-mode .subheadline {
				background: #0f3460 !important;
				color: #fff !important;
			}

			html.dark-mode .subsubheadline {
				background: #1a4a7a !important;
				color: #fff !important;
			}

			html.dark-mode .stunde_und_zeit {
				background: #0a4070 !important;
				color: #fff !important;
			}

			/* --- FORM ELEMENTS --- */
			html.dark-mode input,
			html.dark-mode input[type="text"],
			html.dark-mode input[type="password"],
			html.dark-mode input[type="email"],
			html.dark-mode input[type="number"],
			html.dark-mode input[type="search"],
			html.dark-mode input[type="tel"],
			html.dark-mode input[type="url"],
			html.dark-mode input[type="date"],
			html.dark-mode input[type="time"],
			html.dark-mode input[type="datetime-local"],
			html.dark-mode textarea,
			html.dark-mode select {
				background-color: #1e1e3a !important;
				color: #e0e0e0 !important;
				border-color: #555 !important;
			}

			html.dark-mode input:focus,
			html.dark-mode textarea:focus,
			html.dark-mode select:focus {
				border-color: #7eb8ff !important;
				box-shadow: 0 0 5px 1px #3a6ea5 !important;
			}

			html.dark-mode input[type="submit"],
			html.dark-mode input[type="button"],
			html.dark-mode button:not(.dark-mode-toggle) {
				background-color: #0f3460 !important;
				color: #e0e0e0 !important;
				border-color: #555 !important;
			}

			html.dark-mode input[type="submit"]:hover,
			html.dark-mode input[type="button"]:hover,
			html.dark-mode button:not(.dark-mode-toggle):hover {
				background-color: #1a4a7a !important;
			}

			/* --- JQUERY UI --- */
			html.dark-mode .ui-widget,
			html.dark-mode .ui-widget-content {
				background: #1e1e3a !important;
				color: #e0e0e0 !important;
				border-color: #444 !important;
			}

			html.dark-mode .ui-widget-header {
				background: #0f3460 !important;
				color: #e0e0e0 !important;
				border-color: #444 !important;
			}

			html.dark-mode .ui-state-default,
			html.dark-mode .ui-widget-content .ui-state-default,
			html.dark-mode .ui-widget-header .ui-state-default {
				background: #2a2a4a !important;
				color: #e0e0e0 !important;
				border-color: #555 !important;
			}

			html.dark-mode .ui-state-hover,
			html.dark-mode .ui-widget-content .ui-state-hover,
			html.dark-mode .ui-state-focus {
				background: #3a3a6a !important;
				color: #fff !important;
			}

			html.dark-mode .ui-state-active,
			html.dark-mode .ui-widget-content .ui-state-active {
				background: #0f3460 !important;
				color: #fff !important;
			}

			html.dark-mode .ui-dialog {
				background: #1e1e3a !important;
				border-color: #444 !important;
			}

			html.dark-mode .ui-dialog .ui-dialog-titlebar {
				background: #0f3460 !important;
				color: #fff !important;
			}

			html.dark-mode .ui-autocomplete {
				background: #1e1e3a !important;
				border-color: #444 !important;
			}

			html.dark-mode .ui-menu-item {
				color: #e0e0e0 !important;
			}

			html.dark-mode .ui-menu-item:hover,
			html.dark-mode .ui-menu-item .ui-state-focus {
				background: #3a3a6a !important;
			}

			/* --- FOUNDATION --- */
			html.dark-mode .button,
			html.dark-mode .button.primary {
				background-color: #0f3460 !important;
				color: #e0e0e0 !important;
			}

			html.dark-mode .button:hover {
				background-color: #1a4a7a !important;
			}

			html.dark-mode .button.secondary {
				background-color: #2a2a4a !important;
			}

			html.dark-mode .button.success {
				background-color: #1b4332 !important;
			}

			html.dark-mode .button.alert {
				background-color: #6b1d1d !important;
			}

			html.dark-mode .callout {
				background-color: #1e1e3a !important;
				border-color: #444 !important;
				color: #e0e0e0 !important;
			}

			html.dark-mode .card,
			html.dark-mode .media-object,
			html.dark-mode .accordion-item,
			html.dark-mode .accordion-content,
			html.dark-mode .tabs-content,
			html.dark-mode .tabs-panel,
			html.dark-mode .dropdown-pane {
				background-color: #1e1e3a !important;
				color: #e0e0e0 !important;
				border-color: #444 !important;
			}

			html.dark-mode .tabs {
				background: #16213e !important;
				border-color: #444 !important;
			}

			html.dark-mode .tabs-title > a {
				color: #e0e0e0 !important;
			}

			html.dark-mode .tabs-title > a:hover,
			html.dark-mode .tabs-title.is-active > a {
				background: #0f3460 !important;
				color: #fff !important;
			}

			/* --- TOASTR --- */
			html.dark-mode .toast {
				background-color: #2a2a4a !important;
				color: #e0e0e0 !important;
			}

			html.dark-mode #toast-container > .toast-info {
				background-color: #0f3460 !important;
			}

			html.dark-mode #toast-container > .toast-success {
				background-color: #1b4332 !important;
			}

			html.dark-mode #toast-container > .toast-warning {
				background-color: #5c4a1e !important;
			}

			html.dark-mode #toast-container > .toast-error {
				background-color: #6b1d1d !important;
			}

			/* --- DIFF VIEWER --- */
			html.dark-mode .Differences thead th {
				background: #2a2a4a !important;
				color: #e0e0e0 !important;
				border-bottom-color: #555 !important;
			}

			html.dark-mode .Differences tbody th {
				background: #2e2e4e !important;
				color: #ccc !important;
				border-right-color: #555 !important;
			}

			html.dark-mode .Differences td {
				color: #ddd !important;
			}

			html.dark-mode .DifferencesSideBySide .ChangeInsert td.Left { background: #1b4332 !important; }
			html.dark-mode .DifferencesSideBySide .ChangeInsert td.Right { background: #2d6a4f !important; }
			html.dark-mode .DifferencesSideBySide .ChangeDelete td.Left { background: #6b1d1d !important; }
			html.dark-mode .DifferencesSideBySide .ChangeDelete td.Right { background: #8b2e2e !important; }
			html.dark-mode .DifferencesSideBySide .ChangeReplace .Left { background: #5c4a1e !important; }
			html.dark-mode .DifferencesSideBySide .ChangeReplace .Right { background: #6b5a2e !important; }

			html.dark-mode .DifferencesSideBySide .ChangeReplace ins,
			html.dark-mode .DifferencesSideBySide .ChangeReplace del { background: #7a6a2e !important; }

			html.dark-mode .Differences .Skipped { background: #252540 !important; }

			html.dark-mode .DifferencesInline .ChangeReplace .Left,
			html.dark-mode .DifferencesInline .ChangeDelete .Left { background: #4a1e1e !important; }

			html.dark-mode .DifferencesInline .ChangeReplace .Right,
			html.dark-mode .DifferencesInline .ChangeInsert .Right { background: #1e4a2e !important; }

			html.dark-mode .DifferencesInline .ChangeReplace ins { background: #2d6a4f !important; }
			html.dark-mode .DifferencesInline .ChangeReplace del { background: #6b2e2e !important; }

			/* --- ADMIN SPECIFIC --- */
			html.dark-mode .blurbox {
				background-color: #1e1e3a !important;
				border-color: #444 !important;
				color: #e0e0e0 !important;
			}

			/* --- MISC BACKGROUNDS --- */
			html.dark-mode .details_class { background-color: #252540 !important; }
			html.dark-mode .query_analyzer_table { background-color: #2a2a4a !important; }
			html.dark-mode .c5e3ed_background { background-color: #1a3a4a !important; }
			html.dark-mode .neue_veranstaltung { background-color: #1a3a5a !important; }
			html.dark-mode .used_files { background-color: #1a3a5a !important; color: #e0e0e0 !important; }
			html.dark-mode .buy_button { background-color: #4a8c00 !important; }
			html.dark-mode .chosen_plan { background-color: #036b07 !important; }
			html.dark-mode .possible_plan { background-color: #3a7a38 !important; }
			html.dark-mode .plan_div { border-color: #555 !important; }
			html.dark-mode .square { border-color: #555 !important; }

			/* --- TOOLTIP --- */
			html.dark-mode .tooltip { border-bottom-color: #888 !important; }
			html.dark-mode .tooltip .tooltiptext {
				background-color: #333 !important;
				color: #fff !important;
			}

			/* --- OUTLINE TEXT --- */
			html.dark-mode .outline_text {
				color: #fff !important;
				text-shadow: 1px 1px 0 #000, -1px -1px 0 #000, 1px -1px 0 #000, -1px 1px 0 #000 !important;
			}

			/* --- SCROLLBAR (Webkit) --- */
			html.dark-mode ::-webkit-scrollbar {
				width: 10px;
				height: 10px;
			}

			html.dark-mode ::-webkit-scrollbar-track {
				background: #1a1a2e;
			}

			html.dark-mode ::-webkit-scrollbar-thumb {
				background: #3a3a6a;
				border-radius: 5px;
			}

			html.dark-mode ::-webkit-scrollbar-thumb:hover {
				background: #5a5a8a;
			}

			/* --- CATCH-ALL: any white/light backgrounds we missed --- */
			html.dark-mode [style*="background-color: white"],
			html.dark-mode [style*="background-color:#fff"],
			html.dark-mode [style*="background-color: #fff"],
			html.dark-mode [style*="background-color:#ffffff"],
			html.dark-mode [style*="background-color: #ffffff"],
			html.dark-mode [style*="background: white"],
			html.dark-mode [style*="background:#fff"],
			html.dark-mode [style*="background: #fff"] {
				background-color: #16213e !important;
			}

			html.dark-mode [style*="color: black"],
			html.dark-mode [style*="color:#000"],
			html.dark-mode [style*="color: #000"],
			html.dark-mode [style*="color:#000000"],
			html.dark-mode [style*="color: #000000"] {
				color: #e0e0e0 !important;
			}

			/* ============================================================
			   TOGGLE BUTTON STYLES
			   ============================================================ */
			.dark-mode-toggle {
				position: fixed;
				top: 10px;
				right: 14px;
				z-index: 999999;
				background: none;
				border: none;
				width: 32px;
				height: 32px;
				cursor: pointer;
				display: flex;
				align-items: center;
				justify-content: center;
				border-radius: 50%;
				opacity: 0.4;
				transition: opacity 0.3s ease, transform 0.3s ease, background-color 0.3s ease;
				padding: 0;
				font-size: 18px;
				line-height: 1;
			}

			.dark-mode-toggle:hover {
				opacity: 1;
				transform: scale(1.15);
				background-color: rgba(128, 128, 128, 0.15);
			}

			.dark-mode-toggle:active {
				transform: scale(0.9);
			}

			.dark-mode-toggle:focus {
				outline: none !important;
			}

			.dark-mode-toggle .toggle-icon {
				position: absolute;
				transition: opacity 0.4s ease, transform 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
			}

			.dark-mode-toggle .toggle-icon.sun {
				opacity: 1;
				transform: rotate(0deg) scale(1);
			}

			.dark-mode-toggle .toggle-icon.moon {
				opacity: 0;
				transform: rotate(-90deg) scale(0.5);
			}

			html.dark-mode .dark-mode-toggle .toggle-icon.sun {
				opacity: 0;
				transform: rotate(90deg) scale(0.5);
			}

			html.dark-mode .dark-mode-toggle .toggle-icon.moon {
				opacity: 1;
				transform: rotate(0deg) scale(1);
			}
		</style>
	</head>
<body>

<!-- Dark Mode Toggle — subtle, top-right, non-intrusive -->
<button id="darkModeToggle" class="dark-mode-toggle" aria-label="Dunkelmodus umschalten" title="Hell/Dunkel umschalten">
	<span class="toggle-icon sun">☀️</span>
	<span class="toggle-icon moon">🌙</span>
</button>

<!--
	CRITICAL: Inline script immediately after body + button.
	Syncs class and attaches click handler. Cannot fail.
-->
<script>
(function() {
	// 1. Sync html -> body
	if (document.documentElement.classList.contains('dark-mode')) {
		document.body.classList.add('dark-mode');
	}

	// 2. Click handler
	var btn = document.getElementById('darkModeToggle');
	if (btn) {
		btn.addEventListener('click', function() {
			var isDarkNow = document.body.classList.contains('dark-mode');
			if (isDarkNow) {
				document.body.classList.remove('dark-mode');
				document.documentElement.classList.remove('dark-mode');
				localStorage.setItem('darkModeEnabled', 'false');
			} else {
				document.body.classList.add('dark-mode');
				document.documentElement.classList.add('dark-mode');
				localStorage.setItem('darkModeEnabled', 'true');
			}
		});
	}

	// 3. System preference listener
	if (window.matchMedia) {
		try {
			window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
				if (localStorage.getItem('darkModeEnabled') === null) {
					if (e.matches) {
						document.body.classList.add('dark-mode');
						document.documentElement.classList.add('dark-mode');
					} else {
						document.body.classList.remove('dark-mode');
						document.documentElement.classList.remove('dark-mode');
					}
				}
			});
		} catch(err) {}
	}
})();
</script>

<!--
<span id="help_icon">
	<span onclick="help()"><?php print_help_icon(); ?></span>
</span>
-->
