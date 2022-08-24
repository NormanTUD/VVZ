<?php
	include_once("kundenkram.php");
	$php_start = microtime(true);
	$filename = "startseite.php";

	include_once("config.php");
	include("startseite_functions.php");
	include_once("functions.php");

	include("selftest.php");


	if(get_kunden_db_name() == "startpage") {
		$query = "show databases like 'db_vvz_%'";
		$result = rquery($query);

		print "<h2>VVZ-Startseite</h2>";
		print "<a href='?new_demo_uni=1'><button>Sofort eine kostenlose Demo ohne Verpflichtungen ausprobieren</button></a>";
		print "<br><br>Aktuelle Instanzen:<br>";
		print "<ul>";
		print "<li><form method=get><input name='new_uni_name' placeholder='Name der Uni'><input type='submit'><form></li>";
		while ($row = mysqli_fetch_row($result)) {
			$db_name = $row[0];
			$kunde_name = $db_name;
			$kunde_name = preg_replace("/^db_vvz_/", "", $kunde_name);

			print "<li><a href='/v/$kunde_name/'>$kunde_name</a></li>";
		}
		print "</ul>";

		exit(0);
	} else {
		if($GLOBALS["db_freshly_created"]) {
			print "<script nonce='".nonce()."'>window.location.reload();</script>";
			exit(0);
		}

		if(get_get("initialdatensatz") && is_demo()) {
			include("initialdatensatz.php");

			print "Die Daten werden eingetragen, das kann einige Sekunden dauern. Bitte warten...";
			flush();
			print '<meta http-equiv="refresh" content="1; url=startseite" />';
			flush();
			exit(0);

		}
	}

	$GLOBALS['metadata_shown'] = 0;

	$page_title = "Vorlesungsverzeichnis ".$GLOBALS['university_name'];
	$filename = 'startseite';
	include("header.php");

	$GLOBALS['linkicon'] = '<i class="fa float-right"><img alt="Link zum Studiengang" src="icon.svg" /></i>';


	$GLOBALS['this_semester'] = null;

	if(get_get("semester") && preg_match('/^\d+$/', get_get('semester'))) {
		$GLOBALS['this_semester'] = get_semester(get_get('semester'), 0);
	}

	if(!isset($GLOBALS['this_semester'])) {
		$GLOBALS['this_semester'] = get_this_semester();
		if(!$GLOBALS['this_semester']) {
			$GLOBALS['this_semester'] = get_and_create_this_semester(1);
			if(!$GLOBALS['this_semester']) {
				if(table_exists($GLOBALS["dbname"], "semester")) {
					$valid_semesters = create_semester_array(1, 1, array(get_get('semester')));
					if(is_array($valid_semesters) && count($valid_semesters)) {
						$GLOBALS['this_semester'] = $valid_semesters[1];
					} else {
						die("Es existieren keine validen, eingetragenen Semester.");
					}
				}
			}
		}
	}

	$GLOBALS['shown_etwa'] = 0;

	$GLOBALS['institute'] = table_exists($GLOBALS["dbname"], "institut") ? create_institute_array() : Array();

	$GLOBALS['this_institut'] = null;

	if(get_get("institut") && preg_match('/^\d+$/', get_get('institut'))) {
		$GLOBALS['this_institut'] = get_get('institut');
	} else {
		if($_SERVER['HTTP_HOST'] == $GLOBALS['vvz_base_url']) {
			$GLOBALS['this_institut'] = $GLOBALS['institute'][1][0];
		}
		
		if(!$GLOBALS['this_institut']) {
			if(count($GLOBALS['institute'])) {
				if(array_key_exists(0, $GLOBALS['institute']) && array_key_exists(0, $GLOBALS['institute'][0])) {
					$GLOBALS['this_institut'] = $GLOBALS['institute'][0][0];
				}
				if(!$GLOBALS['this_institut']) {
					$GLOBALS['this_institut'] = 1;
				}
			} else {
				if(table_exists($GLOBALS["dbname"], "institut")) {
					#die("Es konnten keine Institute gefunden werden. Ohne eingetragene Institute kann die Software nicht benutzt werden. Bitte kontaktieren Sie die Administratoren über die Kontaktseite.");
				}
			}
		}
	}
?>
	<div id="mainindex" <?php if($GLOBALS['show_comic_sans']) { print ' class="bgaf"'; } ?>>
	<a href="startseite?semester=<?php print isset($GLOBALS['this_semester'][0]) ? htmlentities($GLOBALS['this_semester'][0]) : ''; ?>&institut=<?php print isset($GLOBALS['this_institut']) ? htmlentities($GLOBALS['this_institut']) : ''; ?>" border="0"><?php print_uni_logo(); ?></a>
		<div class="iframewarning red_giant"></div>

<?php
		print get_demo_expiry_time();
		logged_in_stuff();

		show_header_startseite();


		console_browser_stuff();

		if(get_get('veranstaltung') || (get_get('dozent') && get_get('create_stundenplan'))) {
			generate_stundenplan();
		} else if (get_get('studiengang')) {
?>
			<div class="height_20px"></div>
<?php
			if(get_get('show_pruefungen')) {
				show_pruefungen_fuer_studiengang();
			} else {
				if(!get_get('create_stundenplan')) {
?>
					<script nonce=<?php print($GLOBALS['nonce']); ?> >
						document.onkeypress = function (e) {
							e = e || window.event;

							if(document.activeElement == $("body")[0]) {
								var keycode =  e.keyCode;
								if(keycode >= 97 && keycode <= 122) {
									if($("#filter").css("display") == "none") {
										$("#filter").show();
									}
									$("[name=vltitel]").val($("[name=vltitel]").val() + String.fromCharCode(e.keyCode));
									$("[name=vltitel]").focus();
								}
							}
						};
					</script>
<?php
					show_veranstaltungsuebersicht_header();
				}

				if(get_get('create_stundenplan')) {
					if(get_get('studiengang')) {
						if(!get_get('chosen_semester')) {
							chose_semester();
						} else {
							show_studiengang_semester_ueberblick();
						}
					} else {
						chose_studiengang();
					}
				} else {
					show_veranstaltungen_uebersicht();
				}
			}
		} else if (get_get('stundenplan_to_be_created')) {
?>
			<i class="class_red">Es wurden keine Veranstaltungen ausgewählt. Bitte benutzen Sie den Zurück-Button Ihres Browsers und wählen Sie mindestens eine Veranstaltung aus, um einen Stundenplan zu erstellen.</i>
<?php
		} else if (get_get('alle_pruefungsnummern')) {
			show_alle_pruefungsnummern_daten();
		} else {
			show_studiengaenge_uebersicht();
		}

		include("footer.php");
		delete_demo();
?>
