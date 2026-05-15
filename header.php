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

			css(array(
				"data/style_darkmode.css",
				"data/styles_darkmode.css",
				"data/admin_darkmode.css"
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

		<style>
			/* Toggle button styles */
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
				outline: none;
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

			/* When dark-mode is on html OR body, swap icons */
			html.dark-mode .dark-mode-toggle .toggle-icon.sun,
			body.dark-mode .dark-mode-toggle .toggle-icon.sun {
				opacity: 0;
				transform: rotate(90deg) scale(0.5);
			}

			html.dark-mode .dark-mode-toggle .toggle-icon.moon,
			body.dark-mode .dark-mode-toggle .toggle-icon.moon {
				opacity: 1;
				transform: rotate(0deg) scale(1);
			}
		</style>
	</head>
<body<?php if(isset($_COOKIE['darkModeEnabled']) || (isset($GLOBALS['dark_mode']) && $GLOBALS['dark_mode'])) { /* optional server-side hint */ } ?>>

<!-- Dark Mode Toggle -->
<button id="darkModeToggle" class="dark-mode-toggle" aria-label="Dunkelmodus umschalten" title="Hell/Dunkel umschalten">
	<span class="toggle-icon sun">☀️</span>
	<span class="toggle-icon moon">🌙</span>
</button>

<!-- 
	CRITICAL: This inline script runs immediately after <body> opens.
	It syncs the dark-mode class from <html> to <body> AND sets up the click handler.
	Do NOT move this to an external file or to end of body.
-->
<script>
(function() {
	// 1. Sync: if <html> has dark-mode (set by anti-FOUC), apply to <body> immediately
	if (document.documentElement.classList.contains('dark-mode')) {
		document.body.classList.add('dark-mode');
	}

	// 2. Set up click handler right now (button already exists above this script)
	var btn = document.getElementById('darkModeToggle');
	if (btn) {
		btn.addEventListener('click', function() {
			var isDarkNow = document.body.classList.contains('dark-mode');

			if (isDarkNow) {
				// Switch to light
				document.body.classList.remove('dark-mode');
				document.documentElement.classList.remove('dark-mode');
				localStorage.setItem('darkModeEnabled', 'false');
			} else {
				// Switch to dark
				document.body.classList.add('dark-mode');
				document.documentElement.classList.add('dark-mode');
				localStorage.setItem('darkModeEnabled', 'true');
			}
		});
	}

	// 3. Listen for system preference changes (only if no manual choice saved)
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
