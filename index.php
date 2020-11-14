<?php
	$php_start = microtime(true);

	if(file_exists('new_setup')) {
		include('setup.php');
		exit(0);
	}

	$GLOBALS['metadata_shown'] = 0;

	$page_title = "Vorlesungsverzeichnis TU Dresden";
	$filename = 'index.php';
	include("header.php");
	include("startseite_functions.php");

	$GLOBALS['linkicon'] = '<i class="fa float-right"><img alt="Link zum Studiengang" src="icon.svg" /></i>';

	$GLOBALS['this_semester'] = null;

	if(preg_match('/^\d+$/', get_get('semester'))) {
		$GLOBALS['this_semester'] = get_semester(get_get('semester'), 0);
	}

	if(!isset($GLOBALS['this_semester'])) {
		$GLOBALS['this_semester'] = get_this_semester();
		if(!$GLOBALS['this_semester']) {
			$GLOBALS['this_semester'] = get_and_create_this_semester(1);
			if(!$GLOBALS['this_semester']) {
				$valid_semesters = create_semester_array(1, 1, array(get_get('semester')));
				if(is_array($valid_semesters) && count($valid_semesters)) {
					$GLOBALS['this_semester'] = $valid_semesters[1];
				} else {
					die("Es existieren keine validen, eingetragenen Semester.");
				}
			}
		}
	}

	$GLOBALS['shown_etwa'] = 0;

	$GLOBALS['institute'] = create_institute_array();

	$GLOBALS['this_institut'] = null;

	if(preg_match('/^\d+$/', get_get('institut'))) {
		$GLOBALS['this_institut'] = get_get('institut');
	} else {
		if($_SERVER['HTTP_HOST'] == 'vvz.phil.tu-dresden.de') {
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
				die("Es konnten keine Institute gefunden werden. Ohne eingetragene Institute kann die Software nicht benutzt werden. Bitte kontaktieren Sie die Administratoren über die Kontaktseite.");
			}
		}
	}
?>
	<div id="mainindex" <?php if($GLOBALS['show_comic_sans']) { print ' class="bgaf"'; } ?>>
		<a href="index.php?semester=<?php print isset($GLOBALS['this_semester'][0]) ? htmlentities($GLOBALS['this_semester'][0]) : ''; ?>&institut=<?php print isset($GLOBALS['this_institut']) ? htmlentities($GLOBALS['this_institut']) : ''; ?>" border="0"><img alt="TUD-Logo, Link zur Startseite" src="tudlogo.svg" width=300 /></a>
		<div class="iframewarning red_giant"></div>

<?php
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
?>
