<?php
	if(!isset($setup_mode)) {
		$setup_mode = 0; // Im setup-Modus werden keine Anfragen ausgeführt. Setupmodus deaktiviert.
	}

	include_once("functions.php");

	if($GLOBALS['reload_page']) {
		header("Refresh:0");
	}

	if(!$page_title) {
		$page_title = 'Vorlesungsverzeichnis '.$GLOBALS['university_name'];
	}

	if(get_get("studiengang") && !$GLOBALS["setup_mode"]) {
		$page_title = "$page_title | ".get_studiengang_name(get_get('studiengang'));
	}

	if(get_get("alle_pruefungsnummern")) {
		$page_title = "$page_title | Alle Prüfungsnummern";
	}
?>
<!DOCTYPE html>
<html lang="de">
	<head>
		<meta http-equiv="Content-Security-Policy" content="<?php print $GLOBALS["csp_string"]; ?>">
		<meta charset="UTF-8" />
		<!-- Hey, wenn du die Daten dieser Seite brauchst, dann guck doch einfach in die API! Dann brauchst du hier nicht versuchen, HTML mit Regexen zu parsen... -->
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link nonce="<?php print nonce(); ?>" rel="icon" href="favicon.ico" type="image/x-icon" />

		<meta http-equiv="X-WebKit-CSP" content="<?php print $GLOBALS['csp_string']; ?>">

		<meta name="description" content="Vorlesungsverzeichnis">
		<meta name="keywords" content="Vorlesungsverzeichnis, <?php print $GLOBALS['university_name']; ?>">
		<meta name="author" content="Norman Koch">
		<meta name="viewport" content="width=device-width, user-scalable=yes">
		<script type="text/javascript" src="mathjax/es5/tex-chtml-full.js?config=TeX-AMS-MML_HTMLorMML"></script>
<?php
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

		css(array("font-awesome.min.css"));

		if(array_key_exists("SCRIPT_NAME", $_SERVER) && $_SERVER["SCRIPT_NAME"] != "/admin.php") {
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
				"jquery-1.12.4.js",
				"jquery-ui.js",
				"jquery-ui-timepicker-addon.js",
				"mainscript.php",
				"bootstrap-tour-standalone.js"
			));

			if(!file_exists('/etc/x11test')) {
				css("snake.css");
				js("snake.js");
			}
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
		if($GLOBALS['show_snow']) {
			js(array("snowflakes.min.js", "snowflakesinit.js"));
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

		js(array("color-hash.js"));
?>
	</head>
<body>
