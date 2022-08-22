<?php
	$setup_mode = 0;
	$GLOBALS['die'] = 0;
	if(!isset($setup_mode)) {
		exit(0);
	}

	$is_setup = 1;

	if(!function_exists('rquery')) {
		include_once("functions.php");
	}

	/*
		Einige Daten werden auf jeden Fall gebraucht, z.B. verschiedene
		Prüfungstypen. Dieses Skript wird von der setup.php aufgerufen
		und befüllt die (neu erstellte) Datenbank mit einigen dieser
		auf-jeden-Fall-notwendigen Werten.
	 */

	/*
	$gebaeude = array(
		array("Abstellgeb., Pienner Str.38a" => "P38"),
		array("Andreas-Pfitzmann-Bau" => "APB"),
		array("Andreas-Schubert-Bau" => "ASB"),
		array("August-Bebel-Straße" => "ABS"),
		array("Bamberger Str.1" => "B01"),
		array("Barkhausen-Bau" => "BAR"),
		array("Beamtenhaus, Pienner Str.21" => "P21"),
		array("Bergstr.69" => "B69"),
		array("Berndt-Bau" => "BER"),
		array("Beyer-Bau" => "BEY"),
		array("Binder-Bau" => "BIN"),
		array("Bioinnovationszentrum" => "BIZ"),
		array("Biologie" => "BIO"),
		array("Boselgarten Coswig" => "BOS"),
		array("Botanischer Garten" => "BOT"),
		array("Breitscheidstr.78-82, OT Dobritz" => "B78"),
		array("Bürogebäude Strehlener Str.22,24" => "BSS"),
		array("Bürogebäude Zellescher Weg 17" => "BZW"),
		array("Chemie/Hydrowissenschaften" => "CHE"),
		array("Cotta-Bau" => "COT"),
		array("Drude-Bau" => "DRU"),
		array("Dürerstr.24" => "DÜR"),
		array("Fahrzeugversuchszentrum" => "FVZ"),
		array("Falkenbrunnen" => "FAL"),
		array("Forstbotanischer Garten" => "FBG"),
		array("Forsttechnik, Dresdner Str.24" => "FOT"),
		array("Fraunhofer IWS" => "FIWS"),
		array("Freital, Tharandter Str.7" => "HAI"),
		array("Frenzel-Bau" => "FRE"),
		array("Fritz-Foerster-Bau" => "FOE"),
		array("Fritz-Löffler-Str.10a" => "L10"),
		array("Georg-Schumann-Bau" => "SCH"),
		array("Georg-Schumannstr.7a" => "S7A"),
		array("Graduiertenakademie" => "M07"),
		array("GrillCube" => "GCUB"),
		array("Görges-Bau" => "GÖR"),
		array("Günther-Landgraf-Bau" => "GLB"),
		array("Halle Nickern" => "NIC"),
		array("Hallwachsstr.3" => "HAL"),
		array("Hauptgebäude, Pienner Str.8" => "HAU"),
		array("Haus 2" => "U0002"),
		array("Haus 4" => "U0004"),
		array("Haus 5" => "U0105"),
		array("Haus 7" => "U0007"),
		array("Haus 9" => "U0009"),
		array("Haus 11" => "U0011"),
		array("Haus 13" => "U0013"),
		array("Haus 15" => "U0015"),
		array("Haus 17" => "U0017"),
		array("Haus 19" => "U0019"),
		array("Haus 21a" => "U0021A"),
		array("Haus 22" => "U0022"),
		array("Haus 25" => "U0025"),
		array("Haus 27" => "U0027"),
		array("Haus 29" => "U0029"),
		array("Haus 31" => "U0031"),
		array("Haus 33" => "U0033"),
		array("Haus 38" => "U0038"),
		array("Haus 41" => "U0041"),
		array("Haus 44" => "U0044"),
		array("Haus 47" => "U0047"),
		array("Haus 50" => "U0050"),
		array("Haus 53" => "U0053"),
		array("Haus 58" => "U0058"),
		array("Haus 60" => "U0060"),
		array("Haus 62" => "U0062"),
		array("Haus 66" => "U0066"),
		array("Haus 69" => "U0069"),
		array("Haus 71" => "U0071"),
		array("Haus 81" => "U0081"),
		array("Haus 83" => "U0083"),
		array("Haus 90" => "U0090"),
		array("Haus 97" => "U0097"),
		array("Haus 111" => "U0111"),
		array("Heidebroek-Bau" => "HEI"),
		array("Heinrich-Schütz-Str.2" => "AV1"),
		array("Helmholtz-Zentrum Dresden-Rossendorf" => "FZR"),
		array("Hermann-Krone-Bau" => "KRO"),
		array("Hohe Str.53" => "H53"),
		array("Hörsaalzentrum" => "HSZ"),
		array("Hülsse-Bau" => "HÜL"),
		array("Jante-Bau" => "JAN"),
		array("Judeich-Bau" => "JUD"),
		array("Kutzbach-Bau" => "KUT"),
		array("König-Bau" => "KÖN"),
		array("Leichtbau-Innovationszentrum" => "LIZ"),
		array("Ludwig-Ermold-Str.3" => "E03"),
		array("Marschnerstr.30,32" => "MAR"),
		array("Max-Bergmann-Zentrum" => "MBZ"),
		array("Mensa" => "M13"),
		array("Merkel-Bau" => "MER"),
		array("Mierdel-Bau" => "MIE"),
		array("Mohr-Bau" => "MOH"),
		array("Mollier-Bau" => "MOL"),
		array("Mommsenstr.5" => "M05"),
		array("Müller-Bau" => "MÜL"),
		array("Neuffer-Bau" => "NEU"),
		array("Nöthnitzer Str.60a" => "N60"),
		array("Nöthnitzer Str.73" => "N73"),
		array("Nürnberger Ei" => "NÜR"),
		array("Potthoff-Bau" => "POT"),
		array("Prozess-Entwicklungszentrum" => "PEZ"),
		array("Recknagel-Bau" => "REC"),
		array("Rektorat, Mommsenstr.11" => "REK"),
		array("Rossmässler-Bau" => "ROS"),
		array("Sachsenberg-Bau" => "SAC"),
		array("Scharfenberger Str.152, OT Kaditz" => "SBS"),
		array("Schweizer Str.3" => "SWS"),
		array("Seminargebäude 1" => "SE1"),
		array("Seminargebäude 2" => "SE2"),
		array("Semperstr.14" => "SEM"),
		array("Stadtgutstr.10 Fahrbereitschaft" => "STA"),
		array("Stöckhardt-Bau" => "STÖ"),
		array("Technische Leitzentrale" => "TLZ"),
		array("Textilmaschinenhalle" => "TEX"),
		array("Tillich-Bau" => "TIL"),
		array("Toepler-Bau" => "TOE"),
		array("Trefftz-Bau" => "TRE"),
		array("TUD-Information, Mommsenstr.9" => "M09"),
		array("Verwaltungsgebäude 2 - STURA" => "VG2"),
		array("Verwaltungsgebäude 3" => "VG3"),
		array("von-Gerber-Bau" => "GER"),
		array("von-Mises-Bau" => "VMB"),
		array("VVT-Halle" => "VVT"),
		array("Walther-Hempel-Bau" => "HEM"),
		array("Walther-Pauer-Bau" => "PAU"),
		array("Weberplatz" => "WEB"),
		array("Weißbachstr.7" => "W07"),
		array("Werner-Hartmann-Bau" => "WHB"),
		array("Wiener Str.48" => "W48"),
		array("Willers-Bau" => "WIL"),
		array("Windkanal Marschnerstraße 28" => "WIK"),
		array("Wohnheim, Pienner Str.9" => "P09"),
		array("Würzburger Str.46" => "WÜR"),
		array("Zellescher Weg 21" => "Z21"),
		array("Zellescher Weg 41c" => "Z41"),
		array("Zeltschlösschen" => "NMEN"),
		array("Zeuner-Bau" => "ZEU"),
		array("Zeunerstr.1a" => "ZS1"),
		array("Übergabestation Nöthnitzer Str.62a" => "NOE"),
		array("ÜS+Trafo Bergstr." => "BRG"),
		array('Am vereinbarten Ort' => 'AVO')
	);

	$pruefungstypen = array(
		"Klausur",
		"Essay",
		"Bibliographie",
		"Vortrag",
		"Mündliche Prüfung",
		"Protokoll",
		"Thesenpapier",
		"Referat",
		"Seminararbeit",
		"Exposé",
		"Rezension",
		"Portfolio 1",
		"Portfolio 2",
		"Bericht",
		"Nachweis SPS",
		"Nachweis 2h begl. Unterricht",
		"Nachweis Schulprakt. Studien"
	);

	$dozenten = array(
		array('Bernhard' => 'Irrgang'),
		array('Bruno' => 'Haas'),
		array('Constanze' => 'Demuth'),
		array('Gerd' => 'Grübler'),
		array('Gerhard' => 'Schönrich'),
		array('Helena' => 'Graf'),
		array('Helmut' => 'Gebauer'),
		array('Holm' => 'Bräuer'),
		array('Irena' => 'Doicescu'),
		array('Johannes' => 'Haaf'),
		array('Johannes' => 'Rohbeck'),
		array('Joydeep' => 'Bagchee'),
		array('Katharina' => 'Bruntsch'),
		array('Katrin' => 'Reichel-Wehnert'),
		array('Lucas' => 'von Ramin'),
		array('Lucilla' => 'Guidi'),
		array('Lutz' => 'Gentsch'),
		array('Markus' => 'Tiedemann'),
		array('Reinhard' => 'Hiltscher'),
		array('René' => 'Dausner'),
		array('Rico' => 'Hauswald'),
		array('Sabine' => 'Müller-Mall'),
		array('Sabine' => 'Vana-Ströhla'),
		array('Thomas' => 'Kühn'),
		array('Thomas' => 'Rentsch'),
		array('Uwe' => 'Scheffler')
	);

	$veranstaltungstypen = array(
		array('Vorlesung' => 'VL'),
		array('Fachseminar' => 'FS'),
		array('Blockseminar' => 'BS'),
		array('Proseminar' => 'PS'),
		array('Textproseminar' => 'TPS'),
		array('Tutorium' => 'TUT'),
		array('Übung' => 'Ü'),
		array('Seminar' => 'S'),
		array('Hauptseminar' => 'HS'),
		array('Oberseminar' => 'OS'),
		array('Exkursion' => 'EX'),
		array('Graduiertenseminar' => 'GS')
	);

	$studienordnung = array(
		'BA-Studiengänge' => 'https://tu-dresden.de/gsw/phil/ressourcen/dateien/stu/stu/bach/phil/BA-Phil_SO-vom-14.3.2007-i.d.F.vom-25.11.2011.pdf?lang=de'
	);

	$studiengaenge = array(
		array('Bachelor Philosophie' => 'Philosophisches Institut'),
		array('Master Philosophie' => 'Philosophisches Institut'),
		array('Bachelor Lehramt' => 'Philosophisches Institut'),
		array('Master Lehramt' => 'Philosophisches Institut'),
		array('Option Grundschule' => 'Philosophisches Institut'),
		array('Lehramt Grundschule' => 'Philosophisches Institut'),
		array('Lehramt an Mittelschulen' => 'Philosophisches Institut'),
		array('Höheres Lehramt an Gymnasien' => 'Philosophisches Institut'),
		array('Höheres Lehramt an berufsbildenden Schulen' => 'Philosophisches Institut'), 
		array('Lehramtsfach Gesundheit und Pflege' => 'Philosophisches Institut'),
		array('Lehramt an Grundschulen' => 'Philosophisches Institut')
	);

	$institute = array(
		array('Philosophisches Institut' => '651')
	);

	$raeume = array(
		array('BZW' => 'A217'),
		array('BZW' => 'A154'),
		array('BZW' => 'A152'),
		array('BZW' => 'A218'),
		array('WEB' => 'KLEM'),
		array('BZW' => 'A418'),
		array('BZW' => 'A153'),
		array('WEB' => '243'),
		array('DRU' => '68'),
		array('TOE' => '317'),
		array('ASB' => '114'),
		array('BZW' => 'A255'),
		array('WIL' => 'C133'),
		array('GER' => '54'),
		array('SE1' => '211'),
		array('GER' => '7'),
		array('GER' => '9'),
		array('ABS' => '2007'),
		array('HSZ' => '204'),
		array('HSZ' => '105'),
		array('HSZ' => 'E05'),
		array('WIL' => 'A124'),
		array('BZW' => 'B101'),
		array('ZEU' => '148'),
		array('WEB' => '235'),
		array('WEB' => '136'),
		array('BZW' => 'A253'),
		array('ASB' => '328'),
		array('SE1' => '102'),
		array('WIL' => 'C307'),
		array('WIL' => 'C105'),
		array('HSZ' => '304'),
		array('CHE' => '183'),
		array('ABS' => '213'),
		array('BZW' => 'A251'),
		array('HSZ' => '405'),
		array('GER' => '50'),
		array('GER' => '49'),
		array('ABS' => '210'),
		array('GER' => '51'),
		array('GER' => '52'),
		array('SE1' => '221'),
		array('REC' => '103'),
		array('REC' => 'D16')
	);

	$function_rights = array(
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("get_and_create_pruefungstyp", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("insert_pruefungsnummern", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("get_and_create_modul", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("get_and_create_raum_id", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("modul_zu_veranstaltung_hinzufuegen", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("studiengang_zu_veranstaltung_hinzufuegen", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("create_nachpruefung", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("create_role", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("create_page", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("create_pruefungsnummer", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("create_user", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("create_veranstaltung", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("create_dozent", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("create_veranstaltungstyp", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("create_gebaeude", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("create_raum", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("create_studiengang", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("create_modul", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("create_institut", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("create_pruefungstyp", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("create_pruefung", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("delete_pruefungsnummer", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("delete_role", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("delete_nachpruefung", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("delete_page", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("delete_pruefungstyp", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("delete_modul", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("delete_user", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("delete_studiengang", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("delete_raum", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("delete_pruefung", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("delete_gebaeude", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("delete_veranstaltung_modul", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("delete_veranstaltung_studiengang", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("delete_veranstaltungstyp", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("delete_veranstaltung", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("delete_dozent", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("delete_institut", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("update_pruefungsnummer", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("update_user", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("update_dozent", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("update_veranstaltung", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("update_veranstaltungstyp", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("update_startseitentext", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("update_raumplanung", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("update_veranstaltung_metadata", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("update_gebaeude", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("update_raum", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("update_studiengang", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("update_modul", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("update_institut", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("update_page", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("update_pruefungstyp", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("update_role", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("update_pruefung", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("update_hinweis", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("update_text", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("update_nachpruefung", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("simple_edit", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("create_select", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("create_table_one_dependency", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("create_modul_html", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("create_studiengang_html", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("create_modul_html_vvz", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("create_pruefungsmoeglichkeiten_html", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("create_studiengang_html_vvz", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("create_nachpruefung_liste", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("create_pruefungsplan", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("create_stundenplan", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("show_output", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("raum_ist_belegt", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("raumplanung", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("get_cached", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("add_leading_zero", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("get_institut_id_by_veranstaltung_id", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("pruefungsnummer_is_checked", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("get_page_id_by_filename", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("get_startnr_by_institut", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("user_is_allowed_to_access", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("assign_page_to_role", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("update_user_role", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("query_analyzer", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("delete_api", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("update_api", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("create_api", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("update_page_info", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("assign_pruefungsnummer_to_veranstaltung", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("update_modul_semester", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("update_own_password", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("kopiere_pruefungen_von_nach", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("update_bereich", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("delete_bereich", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("get_and_create_salt", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("backup_tables", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("SplitSQL", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("update_fach", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("create_fach", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("update_bereich", "1");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("create_bereich", "1");',

		// DOZENT

		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("get_and_create_raum_id", "2");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("modul_zu_veranstaltung_hinzufuegen", "2");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("studiengang_zu_veranstaltung_hinzufuegen", "2");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("create_nachpruefung", "2");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("create_veranstaltung", "2");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("create_raum", "2");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("create_pruefung", "2");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("delete_nachpruefung", "2");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("delete_pruefungstyp", "2");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("delete_pruefung", "2");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("delete_veranstaltung_modul", "2");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("delete_veranstaltung_studiengang", "2");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("delete_veranstaltung", "2");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("update_veranstaltung", "2");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("update_veranstaltung_metadata", "2");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("update_pruefung", "2");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("update_nachpruefung", "2");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("pruefungsnummer_is_checked", "2");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("assign_pruefungsnummer_to_veranstaltung", "2");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("update_own_password", "2");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("kopiere_pruefungen_von_nach", "2");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("get_and_create_salt", "2");',

		// Dozent, Raumplanung

		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("get_and_create_raum_id", "3");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("modul_zu_veranstaltung_hinzufuegen", "3");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("studiengang_zu_veranstaltung_hinzufuegen", "3");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("create_nachpruefung", "3");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("create_veranstaltung", "3");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("create_raum", "3");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("create_pruefung", "3");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("delete_nachpruefung", "3");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("delete_pruefungstyp", "3");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("delete_pruefung", "3");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("delete_veranstaltung_modul", "3");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("delete_veranstaltung_studiengang", "3");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("delete_veranstaltung", "3");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("update_veranstaltung", "3");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("update_veranstaltung_metadata", "3");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("update_pruefung", "3");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("update_nachpruefung", "3");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("pruefungsnummer_is_checked", "3");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("assign_pruefungsnummer_to_veranstaltung", "3");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("update_raumplanung", "3");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("update_own_password", "3");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("kopiere_pruefungen_von_nach", "3");',
		'INSERT INTO `function_rights` (`name`, `role_id`) VALUES ("get_and_create_salt", "3");',
	);

	$hinweise = array(
		'INSERT INTO `hinweise` (`page_id`, `hinweis`) VALUES ("14", "Hinweise dienen der Hinweisung!");',
		'INSERT INTO `hinweise` (`page_id`, `hinweis`) VALUES ("11", "Jedem Benutzer ist eine Rolle zugeordnet. Diese Rolle bestimmt, welche Seiten er sehen und editieren darf.");'
	);

	$error = array('Kein Fehler', 'Falscher Auth-Code', 'Falsche Parameter', 'Zu wenig Zeit vergangen');

	$page = array(
		'INSERT INTO `page` (`id`, `name`, `file`, `show_in_navigation`) VALUES ("1", "Accounts", "accounts.php", "1");',
		'INSERT INTO `page` (`id`, `name`, `file`, `show_in_navigation`) VALUES ("2", "Dozenten", "dozenten.php", "1");',
		'INSERT INTO `page` (`id`, `name`, `file`, `show_in_navigation`) VALUES ("3", "Institute", "institute.php", "1");',
		'INSERT INTO `page` (`id`, `name`, `file`, `show_in_navigation`) VALUES ("4", "Gebäude", "gebaeude.php", "1");',
		'INSERT INTO `page` (`id`, `name`, `file`, `show_in_navigation`) VALUES ("5", "Modul", "modul.php", "1");',
		'INSERT INTO `page` (`id`, `name`, `file`, `show_in_navigation`) VALUES ("6", "Prüfungstypen", "pruefungstypen.php", "1");',
		'INSERT INTO `page` (`id`, `name`, `file`, `show_in_navigation`) VALUES ("7", "Raumplanung", "raumplanung.php", "1");',
		'INSERT INTO `page` (`id`, `name`, `file`, `show_in_navigation`) VALUES ("8", "Studiengang", "studiengang.php", "1");',
		'INSERT INTO `page` (`id`, `name`, `file`, `show_in_navigation`) VALUES ("9", "Veranstaltung", "veranstaltungen.php", "1");',
		'INSERT INTO `page` (`id`, `name`, `file`, `show_in_navigation`) VALUES ("10", "Veranstaltungstypen", "veranstaltungstypen.php", "1");',
		'INSERT INTO `page` (`id`, `name`, `file`, `show_in_navigation`) VALUES ("11", "Rollen", "roles.php", "1");',
		'INSERT INTO `page` (`id`, `name`, `file`, `show_in_navigation`) VALUES ("12", "Prüfungsnummern", "pruefungsnummern.php", "1");',
		'INSERT INTO `page` (`id`, `name`, `file`, `show_in_navigation`) VALUES ("13", "Einzelne Veranstaltung", "veranstaltung.php", "0");',
		'INSERT INTO `page` (`id`, `name`, `file`, `show_in_navigation`) VALUES ("14", "Hinweise", "hinweise.php", "1")',
		'INSERT INTO `page` (`id`, `name`, `file`, `show_in_navigation`) VALUES ("15", "Willkommensseite editieren", "welcome_edit.php", "0")',
		'INSERT INTO `page` (`id`, `name`, `file`, `show_in_navigation`) VALUES ("16", "Rechteprobleme", "right_issues.php", "1");',
		'INSERT INTO `page` (`id`, `name`, `file`, `show_in_navigation`) VALUES ("17", "Query-Analyzer", "query_analyzer.php", "0")',
		'INSERT INTO `page` (`id`, `name`, `file`, `show_in_navigation`) VALUES ("18", "Willkommen!", "welcome.php", "0");',
		'INSERT INTO `page` (`id`, `name`, `file`, `show_in_navigation`) VALUES ("19", "API", "api.php", "1");',
		'INSERT INTO `page` (`id`, `name`, `file`, `show_in_navigation`) VALUES ("20", "Modul &rarr; Semester", "modul_nach_semester.php", "1");',
		'INSERT INTO `page` (`id`, `name`, `file`, `show_in_navigation`) VALUES ("21", "Passwort ändern", "password.php", "1");',
		'INSERT INTO `page` (`id`, `name`, `file`, `show_in_navigation`) VALUES ("22", "Bereiche", "bereiche.php", "1");',
		'INSERT INTO `page` (`id`, `name`, `file`, `show_in_navigation`) VALUES ("23", "DB-Backup", "backup.php", "1");',
		'INSERT INTO `page` (`id`, `name`, `file`, `show_in_navigation`) VALUES ("24", "DB-Backup-Export", "backup_export.php", "0");'
	);

	$page_info = array(
		'INSERT INTO `page_info` (`page_id`, `info`) VALUES ("20", "Hier kann festgelegt werden, welche Module in welchem Semester ausgeführt werden sollten, um daraus halbautomatisiert einen Stundenplan erstellen zu können.");',
		'INSERT INTO `page_info` (`page_id`, `info`) VALUES ("19", "Ermöglicht es, neue API-Zugänge zu erstellen und Vorhandene zu bearbeiten.");',
		'INSERT INTO `page_info` (`page_id`, `info`) VALUES ("1", "Hier können neue Benutzerkonten angelegt und alte gelöscht werden.");',
		'INSERT INTO `page_info` (`page_id`, `info`) VALUES ("2", "Führt neue Dozenten in das System ein.");',
		'INSERT INTO `page_info` (`page_id`, `info`) VALUES ("4", "Führt Gebäude in das System ein (»GER — Gerberbau«, ...), die dann überall im System zur Verfügung stehen.");',
		'INSERT INTO `page_info` (`page_id`, `info`) VALUES ("14", "Gibt die Möglichkeit, am Anfang von Unterseiten einen Hinweis mit Benutzungstipps etc. zu geben, der frei editiert werden kann.");',
		'INSERT INTO `page_info` (`page_id`, `info`) VALUES ("3", "Dieses Vorlesungverzeichnis ist dafür ausgelegt, an beliebig vielen Instituten benutzt zu werden. In diesem Punkt kann man neue Institute einführen.");',
		'INSERT INTO `page_info` (`page_id`, `info`) VALUES ("5", "Veranstaltungen sind normalerweise in Modulen. Hier können neue Module eingeführt werden, die später mit den Veranstaltungen verknüpft werden können.");',
		'INSERT INTO `page_info` (`page_id`, `info`) VALUES ("12", "Hier können Prüfungsnummern erstellt und Modulen und Studiengängen zugeordnet werden.");',
		'INSERT INTO `page_info` (`page_id`, `info`) VALUES ("6", "Führt neue Arten von Prüfungsleistungen ein (z. B. Klausur, Essay, ...)");',
		'INSERT INTO `page_info` (`page_id`, `info`) VALUES ("7", "Liefert eine Liste (exportierbar als Excel-Datei) von Räumen und Nutzungen.");',
		'INSERT INTO `page_info` (`page_id`, `info`) VALUES ("16", "Zeigt eine Liste der Rechteverstöße einzelner User an.");',
		'INSERT INTO `page_info` (`page_id`, `info`) VALUES ("11", "Jeder Benutzer nimmt im System eine bestimmte Rolle ein. Je nach Rolle kann er einige Seiten sehen und andere nicht. Ein Administrator kann z.B. alles sehen und bearbeiten, während ein Dozent nur Räume, Veranstaltungen, Prüfungen und Nachprüfungen einsehen und editieren darf.");',
		'INSERT INTO `page_info` (`page_id`, `info`) VALUES ("8", "Macht dem System neue Studiengänge bekannt.");',
		'INSERT INTO `page_info` (`page_id`, `info`) VALUES ("9", "Hier können neue Veranstaltungen definiert werden.");',
		'INSERT INTO `page_info` (`page_id`, `info`) VALUES ("10", "Hier können Arten von Veranstaltungen definiert werden (Vorlesung, Proseminar, Textproseminar, ...).");',
		'INSERT INTO `page_info` (`page_id`, `info`) VALUES ("21", "Hier kann jeder Nutzer sein eigenes Passwort ändern.");',
		'INSERT INTO `page_info` (`page_id`, `info`) VALUES ("22", "Hier können einzelne Bereiche der Studiengänge bearbeitet werden (Ergänzungsbereich, Kernbereich etc.).");',
		'INSERT INTO `page_info` (`page_id`, `info`) VALUES ("24", "Erstellt Datenbank-Backups.");'
	);

	$rollen = array(
		'INSERT INTO `role` (`id`, `name`) VALUES ("1", "Administrator");',
		'INSERT INTO `role` (`id`, `name`) VALUES ("2", "Dozent");',
		'INSERT INTO `role` (`id`, `name`) VALUES ("3", "Dozent, Raumplanung");'
	);

	$startseite = '
<h2>Einleitung</h2>
<p>Willkommen auf der Administrationsseite des neuen Vorlesungsverzeichnisses.
<span style="color: red;">Dies ist bisher noch ein Prototyp!</span> Daher können hier noch einige Fehler lauern.
Sollten Sie irgendwelche Fehler finden oder Verbesserungsvorschläge haben, melden Sie sich bitte unter <a href="mailto:kochnorman@rocketmail.com?Subject=Bug%20gefunden" target="_top">kochnorman@rocketmail.com</a>. Ich werde die dann so schnell es geht reparieren bzw. die Features einbauen.</p>

<h2>Idee hinter dieser Seite</h2>
<p>Die Idee hinter diese Seite ist es, die einzelnen Daten möglichst sauber getrennt voneinander zu speichern. Somit sind genauere Anzeigemöglichkeiten
machbar. Vorerst wird die Seite relativ leer sein, aber mit jedem Dozenten, Raum etc. der eingetragen wird, wird die Seite einfacher benutzbar. Jeder Datensatz muss nämlich nur exakt einmal eingetragen werden und steht danach zur Verfügung. Sollte z. B. ein neuer Studiengang eingeführt werden, muss dieser nur einmalig eingetragen werden und steht dann für immer zur Auswahl. Auch bietet die strikte Trennung der Daten die schnelle und einfache Möglichkeit, in einem Datensatz etwas zu ändern, was dann überall auftaucht. Stellen wir uns einen Dozenten vor, der heiratet und einen neuen Namen bekommt. Statt den alten durch den neuen Namen in hunderten Excel-Spalten zu ersetzen, reicht hier <b>eine</b> Änderung und die Daten des Dozenten sind im gesamten Vorlesungsverzeichnis für alle Vorlesungen geändert.</p>
<p>Sollte sich dieses System für verschiedene Instituten durchsetzen, vereinfacht es den Studenten dank des einfachen Überblicks und der halbautomatischen Sortier- und Filtermöglichkeiten die Suche nach geeigneten Kursen.</p>
<p><a href="https://en.wikipedia.org/wiki/Eating_your_own_dog_food">Ich werde außerdem versuchen, die Funktionen, die ich mir selbst von einem modernen, digitalen Vorlesungverzeichnis wünschen würde, einzubauen versuchen, darunter eine halbautomatische Stundenplanerstellung.</a></p>

<h2>Das Füllen der Seite</h2>
<p>Die Seite soll wie gesagt nach und nach gefüllt werden. Für Veranstaltungen z. B. muss erst ein Typ der Veranstaltung deklariert werden (Vorlesung, Proseminar, ...). Diese Typen stehen dann, einmalig generiert, für alle weiteren Veranstaltungen zur Verfügung. Genauso verhält es sich mit den Dozenten, den Gebäuden, den Räumen usw.</p>
<p><b>Neue Studiengänge, Institute, Prüfungstypen, Dozenten, Module und Gebäude können die Administratoren (<a href="mailto:holm.braeuer@tu-dresden.de?Subject=Neue%20Datensätze%20einfügen" target="_top">Holm Bräuer</a>, <a href="mailto:kochnorman@rocketmail.com?Subject=Neue%20Datensätze%20einfügen" target="_top">Norman Koch</a>) hinzufügen. Sollten Sie einen neuen Datensatz dieser Richtung brauchen, melden Sie sich bitte bei einem von uns.</b></p>
<h2>Datenbankmodell</h2>
<p>Das zugrundeliegende Datenbankmodell findet sich <a href="er.png">hier</a>. Daraus sind die Relationen der gespeicherten Daten ersichtlich.</p>';

	rquery('SET FOREIGN_KEY_CHECKS=0;');

	run_install_query('hinweise', $hinweise);

	run_install_query('function_rights', $function_rights);

	insert_values('api_error_code', array('name'), $error);
	insert_values('pruefungstyp', array('name'), $pruefungstypen);
	insert_values('dozent', array('first_name', 'last_name'), $dozenten);
	insert_values('gebaeude', array('name', 'abkuerzung'), $gebaeude);

	$raum = array();
	foreach ($raeume as $this_raum_key => $this_raum_value) {
		$this_raum_keys = array_keys($this_raum_value);
		$this_gebaeude = get_gebaeude_id_by_abkuerzung($this_raum_keys[0]);
		$raumnummer = $raeume[$this_raum_key][$this_raum_keys[0]];

		$raum[] = array($this_gebaeude => $raumnummer);
	}

	insert_values('veranstaltungstyp', array('name', 'abkuerzung'), $veranstaltungstypen);
	insert_values('institut', array('name', 'start_nr'), $institute);

	$studiengang = array();
	foreach ($studiengaenge as $this_studiengang_key => $this_studiengang_value) {
		$this_studiengang_keys = array_keys($this_studiengang_value);
		$this_studiengang = $this_studiengang_keys[0];
		$institut = get_institut_id($studiengaenge[$this_studiengang_key][$this_studiengang_keys[0]]);

		$studiengang[] = array($this_studiengang => $institut);
	}

	insert_values('studiengang', array('name', 'institut_id'), $studiengang);
	insert_values('raum', array('gebaeude_id', 'raumnummer'), $raum);
	run_install_query('page_info', $page_info);
	run_install_query('page', $page);
	run_install_query('role', $rollen);


	$queries = array();
	foreach (range(1, count($page)) as $i) {
		$queries[] = 'INSERT INTO `role_to_page` (`role_id`, `page_id`) VALUES ("1", '.esc($i).')';
	}

	foreach (array(9, 13, 18, 21) as $this_page) {
		$queries[] = 'INSERT INTO `role_to_page` (`role_id`, `page_id`) VALUES ("2", '.esc($this_page).')';
	}

	foreach (array(7, 9, 13, 18, 21) as $this_page) {
		$queries[] = 'INSERT INTO `role_to_page` (`role_id`, `page_id`) VALUES ("3", '.esc($this_page).')';
	}
	run_install_query('role_to_page', $queries);

	run_install_query('role_to_user', array('INSERT INTO `role_to_user` (`role_id`, `user_id`) VALUES (1, 1);'));

	foreach ($studienordnung as $this_studienordnung_name => $this_studienordnung_url) {
		$query = 'UPDATE `studiengang` SET `studienordnung` = '.esc($this_studienordnung_url).' WHERE `id` = '.esc(get_studiengang_id_by_name($this_studienordnung_name));
		rquery($query);
	}

	rquery('SET FOREIGN_KEY_CHECKS=1;');

	print "Fülle Startseite<br />\n";

	run_install_query('seitentext', array("INSERT INTO `seitentext` (`text`, `page_id`) VALUES (".esc($startseite).", '18');"));

	print "Erstelle die nächsten 10 Semester...\n";
	get_and_create_next_n_semester_years(10);

	rquery('SET FOREIGN_KEY_CHECKS=1;');
*/

	// select concat("'", modul_name, "' => array(),") from view_modul_studiengang where studiengang_id =

	$modul_semester = array(
		'BA-Studiengänge' => array(
			'Modul Philosophische Propädeutik' => array(1, 2),
			'Schwerpunktmodul Themen der Philosophie' => array(5, 6),
			'Modul Geschichte der Philosophie' => array(1, 2),
			'Grundlagen der Praktischen Philosophie' => array(3, 4),
			'Grundlagen der Philosophie der Technik, Kultur und Religion' => array(3, 4),
			'Schwerpunktmodul Klassische Autoren und Probleme der Philosophiegeschichte' => array(5, 6),
			'Grundlagen der Theoretischen Philosophie' => array(3, 4)
		),
		'Masterstudiengang' => array(
			'Theoretische Philosophie' => array(1, 2),
			'Philosophie der Wissenschaft und Technik' => array(1, 2),
			'Praktische Philosophie' => array(1, 2),
			'Philosophie der Kultur und Religion' => array(1, 2),
			'Geschichte der Philosophie' => array(1, 2),
			'Forschung' => array(1, 2)
		)

	);


	print "Verknüpfe Semester-IDs, Studiengänge und Module...\n";

	foreach ($modul_semester as $studiengang_name => $this_studiengang_value) {
		$this_studiengang_id = get_studiengang_id_by_name($studiengang_name);
		foreach ($modul_semester[$studiengang_name] as $this_modul_name => $this_modul_semester) {
			$this_modul_id = get_and_create_modul($this_modul_name, $this_studiengang_id);
			foreach ($this_modul_semester as $this_semester) {
				if($this_modul_id) {
					$query = 'INSERT IGNORE INTO `modul_nach_semester` (`modul_id`, `semester`) VALUES ('.esc($this_modul_id).', '.esc($this_semester).')';
					rquery($query);
				} else {
					print "<p>Kann kein Modul namens `$this_modul_name` finden.</p>";
				}
			}
		}
	}
?>
