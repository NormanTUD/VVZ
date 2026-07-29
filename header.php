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

		if(array_key_exists("SCRIPT_NAME", $_SERVER) && !in_array(basename($_SERVER["SCRIPT_NAME"]), array("admin", "admin.php"), true)) {
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
		<style>
			.password_change_warning {
				background-color: #fff3cd;
				border: 1px solid #ffe082;
				color: #664d03;
				padding: 10px 36px 10px 12px;
				margin: 10px 0;
				border-radius: 4px;
				position: relative;
				font-size: 14px;
				line-height: 1.4;
			}
			.password_change_warning .password_change_link {
				font-weight: bold;
				color: #664d03;
				text-decoration: underline;
				margin-left: 4px;
			}
			.password_change_warning .password_change_link:hover {
				color: #b58900;
			}
			.password_change_warning .password_change_dismiss {
				position: absolute;
				top: 6px;
				right: 10px;
				color: #664d03;
				text-decoration: none;
				font-size: 18px;
				line-height: 1;
				opacity: 0.6;
			}
			.password_change_warning .password_change_dismiss:hover {
				opacity: 1;
			}
			html.dark-mode .password_change_warning {
				background-color: #5c4a1e !important;
				border-color: #8a6e2e !important;
				color: #f5d5a5 !important;
			}
			html.dark-mode .password_change_warning a,
			html.dark-mode .password_change_warning .password_change_link {
				color: #f5d5a5 !important;
			}

			/* Globale Suche: Kategorie-Badges in den Autocomplete-Vorschlägen */
			.ui-autocomplete {
				max-width: 520px;
				max-height: 420px;
				overflow-y: auto;
				overflow-x: hidden;
			}
			.ui-autocomplete .ui-menu-item {
				margin: 0;
			}
			.ui-autocomplete .ui-menu-item a {
				display: flex !important;
				align-items: center;
				gap: 8px;
				padding: 6px 10px;
				font-size: 13px;
				line-height: 1.3;
				white-space: normal;
				text-overflow: ellipsis;
			}
			.ui-autocomplete .ui-menu-item a .search_category_badge {
				display: inline-block;
				flex: 0 0 auto;
				min-width: 78px;
				padding: 1px 6px;
				border-radius: 3px;
				background: #555;
				color: #fff !important;
				font-size: 10px;
				font-weight: 600;
				text-transform: uppercase;
				letter-spacing: 0.3px;
				text-align: center;
				line-height: 1.4;
			}
			.ui-autocomplete .ui-menu-item a .search_item_raw {
				flex: 1 1 auto;
				min-width: 0;
				overflow: hidden;
				text-overflow: ellipsis;
			}
			.ui-autocomplete .ui-menu-item.ui-state-active a {
				background: #3875d7 !important;
				border: 1px solid #2a5dab !important;
				color: #fff !important;
				margin: 0;
			}
			.ui-autocomplete .ui-menu-item.ui-state-active a .search_category_badge {
				background: rgba(0,0,0,0.35) !important;
				color: #fff !important;
			}
			html.dark-mode .ui-autocomplete .ui-menu-item a {
				color: #e0e0e0 !important;
				border-color: #444 !important;
			}
			html.dark-mode .ui-autocomplete .ui-menu-item.ui-state-active a {
				background: #0f3460 !important;
				border-color: #1a4a7a !important;
				color: #fff !important;
			}

			/* Dashboard (Willkommensseite) */
			#dashboard_header { margin: 12px 0 18px 0; }
			.dashboard_greeting h1 { font-size: 26px; }
			.dashboard_subtitle { margin: 0; color: #555; font-size: 14px; }
			html.dark-mode .dashboard_subtitle { color: #b0b0c0 !important; }

			.dashboard_card {
				background: #fafafa;
				border: 1px solid #e2e2e2;
				border-radius: 6px;
				padding: 12px 16px;
				margin: 0 0 16px 0;
				box-shadow: 0 1px 2px rgba(0,0,0,0.03);
			}
			html.dark-mode .dashboard_card {
				background: #16213e !important;
				border-color: #3a3a5a !important;
			}
			.dashboard_card_title {
				font-size: 15px;
				margin: 0 0 10px 0;
				padding: 0;
				border: 0;
				color: #333;
				display: flex;
				align-items: center;
				gap: 6px;
			}
			.dashboard_card_icon {
				font-size: 15px;
				opacity: 0.8;
			}

			.dashboard_card_tasks { border-left: 4px solid #ef6c00; }
			.dashboard_card_stats { }
			.dashboard_card_recent { }
			.dashboard_card_nav_help { }

			.dashboard_task_list { list-style: none; margin: 0; padding: 0; }
			.dashboard_task_item { margin: 0; padding: 0; }
			.dashboard_task_item a {
				display: flex;
				align-items: center;
				gap: 10px;
				padding: 8px 10px;
				border-radius: 4px;
				text-decoration: none;
				color: #222;
				border-bottom: 1px solid #ececec;
			}
			.dashboard_task_item:last-child a { border-bottom: 0; }
			.dashboard_task_item a:hover { background: #fff5e0; }
			html.dark-mode .dashboard_task_item a {
				color: #e0e0e0 !important;
				border-bottom-color: #2a2a4a !important;
			}
			html.dark-mode .dashboard_task_item a:hover { background: #2a2a4a !important; }
			.dashboard_task_count {
				display: inline-block;
				min-width: 28px;
				padding: 2px 6px;
				text-align: center;
				background: #f0a020;
				color: #fff;
				border-radius: 10px;
				font-weight: bold;
				font-size: 12px;
				flex: 0 0 auto;
			}
			.dashboard_task_severity_warn .dashboard_task_count { background: #e65100; }
			.dashboard_task_severity_info .dashboard_task_count { background: #1976d2; }
			.dashboard_task_severity_error .dashboard_task_count { background: #c62828; }
			.dashboard_task_text { flex: 1 1 auto; }

			.dashboard_stats_table {
				width: 100%;
				border-collapse: collapse;
			}
			.dashboard_stats_table td {
				padding: 4px 8px;
				border-bottom: 1px solid #ececec;
			}
			.dashboard_stats_table tr:last-child td { border-bottom: 0; }
			.dashboard_stats_value {
				text-align: right;
				font-weight: bold;
				font-variant-numeric: tabular-nums;
			}

			.dashboard_recent_list { list-style: none; margin: 0; padding: 0; }
			.dashboard_recent_item {
				padding: 6px 0;
				border-bottom: 1px solid #ececec;
				display: flex;
				flex-direction: column;
				gap: 1px;
			}
			.dashboard_recent_item:last-child { border-bottom: 0; }
			.dashboard_recent_meta {
				font-size: 11px;
				color: #777;
			}
			html.dark-mode .dashboard_recent_meta { color: #a0a0b0 !important; }
			.dashboard_recent_time { font-style: italic; }

			.dashboard_nav_list {
				list-style: none;
				margin: 0;
				padding: 0;
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
				gap: 6px 18px;
			}
			.dashboard_nav_item { padding: 4px 0; }
			.dashboard_nav_desc { color: #555; font-size: 12px; }
			html.dark-mode .dashboard_nav_desc { color: #b0b0c0 !important; }

			@media (max-width: 800px) {
				.dashboard_nav_list { grid-template-columns: 1fr; }
			}
		</style>

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
				border-color: #2a2a4a !important;
				color: #e0e0e0 !important;
			}

			/* Tabellen-Zellen: im Lightmode weiße Borders (unsichtbar), im Darkmode
			   wären sie als helle Linien sichtbar — durch dezenten Dunkelton ersetzen. */
			html.dark-mode td,
			html.dark-mode th {
				border-color: #2a2a4a !important;
			}
			html.dark-mode table {
				border-color: #2a2a4a !important;
			}
			html.dark-mode .invisiblebg,
			html.dark-mode .invisiblebg td,
			html.dark-mode .invisiblebg th,
			html.dark-mode .invisiblebg > tbody > tr,
			html.dark-mode .invisiblebg > thead > tr {
				background-color: transparent !important;
				border: 0 !important;
			}

			/* Navigation oben (Topnav) im Admin-Bereich: die Tab-Trennlinien */
			html.dark-mode .topnav li a {
				border-color: #2a2a4a !important;
			}
			html.dark-mode .topnav {
				border-bottom: 1px solid #2a2a4a;
			}

			/* Formular-Hilfen (gelbe Tooltips etc.) */
			html.dark-mode .callout,
			html.dark-mode .info {
				background-color: #1a2d4a !important;
				border-color: #2a4a7a !important;
				color: #e0e0e0 !important;
			}

			/* Eingebettete Bilder mit text-shadow-Filter (Logo) bekommen im Darkmode
			   einen leichten Glow statt einer schwarzen Outline */
			html.dark-mode img.logo_limits {
				filter: drop-shadow(1px 0 0 #2a2a4a) drop-shadow(-1px 0 0 #2a2a4a)
				        drop-shadow(0 1px 0 #2a2a4a) drop-shadow(0 -1px 0 #2a2a4a) !important;
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
/* ============================================================
   DARK MODE — TABLES (COMPLETE)
   thead, tbody, tfoot, striped, bordered, hover, captions
   ============================================================ */

/* --- TABLE BASE --- */
html.dark-mode table {
	background-color: #16213e !important;
	color: #e0e0e0 !important;
	border-color: #2a2a4a !important;
}

html.dark-mode table caption {
	color: #aaa !important;
	padding: 8px 0 !important;
}

/* --- THEAD --- */
html.dark-mode table thead,
html.dark-mode table thead tr {
	background-color: #0f2940 !important;
	color: #fff !important;
	border-color: #3a3a5a !important;
}

html.dark-mode table thead th,
html.dark-mode table thead td {
	background-color: #0f2940 !important;
	color: #fff !important;
	border-color: #3a3a5a !important;
	border-bottom: 2px solid #4a6fa5 !important;
	padding: 10px 8px !important;
	font-weight: bold !important;
}

/* --- TBODY --- */
html.dark-mode table tbody {
	background-color: transparent !important;
}

html.dark-mode table tbody tr {
	background-color: #16213e !important;
	color: #e0e0e0 !important;
	border-color: #2a2a4a !important;
	transition: background-color 0.2s ease !important;
}

html.dark-mode table tbody td {
	background-color: inherit !important;
	color: #e0e0e0 !important;
	border-color: #2a2a4a !important;
	padding: 8px !important;
}

html.dark-mode table tbody th {
	background-color: #1a2d4a !important;
	color: #ccd6e0 !important;
	border-color: #2a2a4a !important;
	font-weight: bold !important;
}

/* --- TFOOT --- */
html.dark-mode table tfoot,
html.dark-mode table tfoot tr {
	background-color: #0f2940 !important;
	color: #ccd6e0 !important;
	border-color: #3a3a5a !important;
}

html.dark-mode table tfoot td,
html.dark-mode table tfoot th {
	background-color: #0f2940 !important;
	color: #ccd6e0 !important;
	border-color: #3a3a5a !important;
	border-top: 2px solid #4a6fa5 !important;
	padding: 10px 8px !important;
	font-weight: bold !important;
}

/* --- STRIPED ROWS (alternating) --- */
html.dark-mode table tbody tr:nth-child(even) {
	background-color: #1a2744 !important;
}

html.dark-mode table tbody tr:nth-child(odd) {
	background-color: #16213e !important;
}

/* --- HOVER STATE --- */
html.dark-mode table tbody tr:hover {
	background-color: #1e3a5f !important;
	color: #7eb8ff !important;
}

html.dark-mode table tbody tr:hover td {
	background-color: inherit !important;
	color: inherit !important;
}

/* --- BORDERED TABLE --- */
html.dark-mode table[border],
html.dark-mode table.bordered,
html.dark-mode table.table-bordered {
	border: 1px solid #3a3a5a !important;
}

html.dark-mode table[border] td,
html.dark-mode table[border] th,
html.dark-mode table.bordered td,
html.dark-mode table.bordered th,
html.dark-mode table.table-bordered td,
html.dark-mode table.table-bordered th {
	border: 1px solid #3a3a5a !important;
}

/* --- TABLE GROUP DIVIDERS (between thead/tbody/tfoot) --- */
html.dark-mode table tbody + tbody {
	border-top: 3px solid #4a6fa5 !important;
}

html.dark-mode table thead + tbody {
	border-top: none !important;
}

/* --- COLGROUP / COL --- */
html.dark-mode table colgroup,
html.dark-mode table col {
	background-color: transparent !important;
}

/* --- SELECTED / ACTIVE ROW --- */
html.dark-mode table tbody tr.active,
html.dark-mode table tbody tr.selected,
html.dark-mode table tbody tr[class*="active"],
html.dark-mode table tbody tr[class*="selected"] {
	background-color: #1a3a5f !important;
	color: #fff !important;
}

/* --- STATUS ROWS (colored rows) --- */
html.dark-mode table tbody tr.success,
html.dark-mode table tbody tr.table-success {
	background-color: #1b4332 !important;
	color: #a3d9b1 !important;
}

html.dark-mode table tbody tr.danger,
html.dark-mode table tbody tr.table-danger,
html.dark-mode table tbody tr.error {
	background-color: #4a1e1e !important;
	color: #f5a5a5 !important;
}

html.dark-mode table tbody tr.warning,
html.dark-mode table tbody tr.table-warning {
	background-color: #4a3a1e !important;
	color: #f5d5a5 !important;
}

html.dark-mode table tbody tr.info,
html.dark-mode table tbody tr.table-info {
	background-color: #1e3a4a !important;
	color: #a5d5f5 !important;
}

/* --- EMPTY TABLE / NO DATA --- */
html.dark-mode table tbody tr.empty td,
html.dark-mode table tbody td.dataTables_empty,
html.dark-mode table tbody td[colspan] {
	color: #888 !important;
	background-color: #1a1a2e !important;
}

/* --- RESPONSIVE TABLE WRAPPER --- */
html.dark-mode .table-responsive,
html.dark-mode .table-scroll,
html.dark-mode .table-wrapper {
	background-color: #16213e !important;
	border-color: #3a3a5a !important;
}

/* --- SORTABLE TABLE HEADERS --- */
html.dark-mode table thead th[class*="sort"],
html.dark-mode table thead th.sorting,
html.dark-mode table thead th.sorting_asc,
html.dark-mode table thead th.sorting_desc {
	background-color: #0f2940 !important;
	color: #fff !important;
}

html.dark-mode table thead th.sorting_asc {
	border-bottom-color: #7eb8ff !important;
}

html.dark-mode table thead th.sorting_desc {
	border-bottom-color: #ff7e7e !important;
}

html.dark-mode table thead th:hover {
	background-color: #1a3a5f !important;
	color: #7eb8ff !important;
	cursor: pointer;
}

/* --- COMPACT / CONDENSED TABLE --- */
html.dark-mode table.compact td,
html.dark-mode table.compact th,
html.dark-mode table.condensed td,
html.dark-mode table.condensed th {
	padding: 4px 6px !important;
}

/* --- FIXED HEADER TABLE --- */
html.dark-mode table thead.fixed-header th,
html.dark-mode table.sticky-header thead th {
	background-color: #0f2940 !important;
	box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3) !important;
}

/* --- YOUR CUSTOM TABLE CLASSES --- */
html.dark-mode .stundenplan {
	background-color: #16213e !important;
}

html.dark-mode .stundenplan td,
html.dark-mode .stundenplan_td {
	background-color: #1a2744 !important;
	color: #e0e0e0 !important;
	border-color: #3a3a5a !important;
}

html.dark-mode .raumplanungtable {
	background-color: #16213e !important;
	color: #e0e0e0 !important;
}

html.dark-mode .raumplanungtable td,
html.dark-mode .raumplanungtable th {
	border-color: #3a3a5a !important;
}

html.dark-mode .veranstaltungen_table {
	background-color: #16213e !important;
}

html.dark-mode .veranstaltungen_table td,
html.dark-mode .veranstaltungen_table th {
	border-color: #3a3a5a !important;
	color: #e0e0e0 !important;
}

/* --- BACKGROUND COLOR CLASSES USED IN TABLES --- */
html.dark-mode .background_color_006092,
html.dark-mode .background_color_003377_color_white {
	background-color: #0a3050 !important;
	color: #fff !important;
}

html.dark-mode .background_color_225599_color_white {
	background-color: #1a3a6a !important;
	color: #fff !important;
}

html.dark-mode .bg_225599_ddaa66 {
	background-color: #1a3a6a !important;
	color: #ddaa66 !important;
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
