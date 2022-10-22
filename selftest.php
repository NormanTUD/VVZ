<?php
	$GLOBALS["auto_login_id"] = null;
	$GLOBALS["no_selftest"] = 0;

        $included_files = get_included_files();
        $included_files = array_map('basename', $included_files);

        if(!in_array('functions.php', $included_files)) {
                include_once('functions.php');
        }

	include_once("sql_datenbanken.php");

	if(!function_exists("insert_settings")) {
	function insert_settings ($errormsg = array()) {
		$settings = array(
			"x11_debugging_mode" => array("setting" => "0", "default_value" => "0", "description" => "Deaktiviert Dinge, die im randomisiertem X11-Test Probleme machen (1 ja, 0 nein)", "category" => "Debug")
		);
				$GLOBALS['settings_cache'] = array();

		foreach ($settings as $this_setting_name => $this_setting_values) {
			if(is_null(get_setting($this_setting_name))) {
				$errormsg[] = "Fehlende Einstellung ".fq($this_setting_name)." existierte nicht und wurde eingefügt.";
				$query = 'insert into config (name, setting, default_value, description, category) values ('.esc($this_setting_name).', '.
					esc($this_setting_values['setting']).', '.esc($this_setting_values['default_value']).', '.esc($this_setting_values['description']).
					', '.esc($this_setting_values['category']).
					') on duplicate key update setting=values(setting), description=values(description), category=values(category)';
				if(!rquery($query)) {
					$errormsg[] = "Die Einstellung ".fq($this_setting_name)." wurde ***nicht*** eingerichtet";
				}
			}
		}

		return $errormsg;
	}
	}



	function already_initialized ($name) {
		if(get_get("force_reinitialize")) {
			return 0;
		}

		$query = "select count(*) from initialized_db where name = ".esc($name);
		return get_single_row_from_query($query);
	}

	function initialized ($name) {
		$query = "insert ignore into initialized_db (name) values (".esc($name).")";
		rquery($query);
	}

	function table_exists_and_has_entries($table) {
		if(!table_exists($GLOBALS["dbname"], $table)) {
			return 0;
		}
		if(get_single_row_from_query("select count(*) from ".$table)[0]) {
			return 1;
		}

		return 0;
	}

	function add_system_versioning () {
		$tables_res = rquery("show full tables where Table_Type != 'VIEW'");
		while ($row = mysqli_fetch_row($tables_res)) {
			$query = '
ALTER TABLE '.$row[0].' ADD COLUMN ts TIMESTAMP(6) GENERATED ALWAYS AS ROW START,
              ADD COLUMN te TIMESTAMP(6) GENERATED ALWAYS AS ROW END,
              ADD PERIOD FOR SYSTEM_TIME(ts, te),
              ADD SYSTEM VERSIONING;
';
			try {
				rquery("SET @@system_versioning_alter_history = 1;");
				rquery($query, 0);
			} catch (\Throwable $e) {
				//
			}
			rquery("SET @@system_versioning_alter_history = 0;");
		}
	}

	function selftest () {
		if(array_key_exists("no_selftest_force", $GLOBALS) || $GLOBALS["no_selftest"]) {
			#return;
		}

		try {
			$tables = $GLOBALS["databases"];

			$missing_tables = array();

			rquery('SET foreign_key_checks = 0');

			rquery("CREATE DATABASE IF NOT EXISTS ".$GLOBALS["dbname"]);
			rquery('use `'.$GLOBALS['dbname'].'`');

			$done_sth = 0;
			foreach ($tables as $this_table => $create_query) {
				if(!table_exists($GLOBALS["dbname"], $this_table)) {
					$missing_tables[] = $this_table;
					if(is_array($create_query)) {
						foreach ($create_query as $this_create_query) {
							rquery("create database if not exists ".$GLOBALS["dbname"]);
							rquery("use ".$GLOBALS["dbname"]);
							rquery($this_create_query);
							while (mysqli_next_result($GLOBALS["dbh"])); // Flush out the results.
							$done_sth++;
						}
					} else {
						try {
							rquery("create database if not exists ".$GLOBALS["dbname"]);
							rquery("use ".$GLOBALS["dbname"]);
							rquery($create_query);
							$done_sth++;
						} catch (\Throwable $e) {
							//
						}
					}
					$GLOBALS['settings_cache'] = array();
				}
			}
			if($done_sth) {
				sleep(1);
			}

			$views = $GLOBALS["views"];

			$done_sth = 0;
			$missing_views = array();
			foreach ($views as $this_view => $create_query) {
				if(!table_exists($GLOBALS['dbname'], $this_view)) {
					$missing_views[] = $this_view;
					rquery("create database if not exists ".$GLOBALS["dbname"]);
					rquery("use ".$GLOBALS["dbname"]);
					rquery($create_query);
					$done_sth++;
					while (mysqli_next_result($GLOBALS["dbh"])); // Flush out the results.
				}
			}
			if($done_sth) {
				sleep(1);
			}

			$errormsg = array();

			$errormsg = insert_settings($errormsg);

			if(count($missing_tables)) {
				$errormsg[] = "Fehlende Tabellen:\t\n\t".join("\n\t", $missing_tables)."\n\n";
			}

			if(count($missing_views)) {
				$errormsg[] = "Fehlende views:\t\n\t".join("\n\t", $missing_views)."\n\n";
			}

			if(!table_exists_and_has_entries("plan")) {
				rquery("insert ignore into vvz_global.plan (name, monatliche_zahlung, jaehrliche_zahlung) VALUES ('Demo', 0, 0)");
				rquery("insert ignore into vvz_global.plan (name, monatliche_zahlung, jaehrliche_zahlung) VALUES ('Basic Faculty', 50, 500)");
				rquery("insert ignore into vvz_global.plan (name, monatliche_zahlung, jaehrliche_zahlung) VALUES ('Basic University', 500, 3000)");
				rquery("insert ignore into vvz_global.plan (name, monatliche_zahlung, jaehrliche_zahlung) VALUES ('Pro Faculty', 70, 700)");
				rquery("insert ignore into vvz_global.plan (name, monatliche_zahlung, jaehrliche_zahlung) VALUES ('Pro University', 350, 4000)");
				rquery("insert ignore into vvz_global.plan (name, monatliche_zahlung, jaehrliche_zahlung) VALUES ('Superadmin', 0, 0)");
			}

			if(!table_exists_and_has_entries("vvz_global.kundendaten")) {
				if($GLOBALS["dbname"]) {
					$query = "";
					$urlname = get_url_uni_name();
					if($GLOBALS["dbname"] != "startpage") {
						$query = 'insert ignore into vvz_global.kundendaten (anrede, universitaet, name, ort, plz, strasse, email, dbname, urlname, plan_id) values ("Hallo", "'.$GLOBALS["default_universitaet"].'", "'.$GLOBALS["default_name"].'", "'.$GLOBALS["default_ort"].'", "'.$GLOBALS["default_plz"].'", "'.$GLOBALS["default_strasse"].'", "'.$GLOBALS["default_email"].'", '.esc($GLOBALS["dbname"]).', '.esc($urlname).', 1)';
					} else if ($GLOBALS["dbname"] == "startpage" && get_single_row_from_query("select count(*) from vvz_global.kundendaten where universitaet = 'startpage'") == 0) {
						if(!get_single_row_from_query("select count(*) from vvz_global.kundendaten where plan_id = 6")) {
							$query = 'insert ignore into vvz_global.kundendaten (anrede, universitaet, name, ort, plz, strasse, email, dbname, urlname, plan_id) values ("Hallo Superadmin", "Admin", "'.$GLOBALS["default_name"].'", "'.$GLOBALS["default_ort"].'", "'.$GLOBALS["default_plz"].'", "'.$GLOBALS["default_strasse"].'", "'.$GLOBALS["default_email"].'", '.esc($GLOBALS["dbname"]).', '.esc($urlname).', 6)';
						}
					}
					if($query) {
						rquery($query);
					}
				}
			}

			if(!get_single_row_from_query("select count(*) from vvz_global.kundendaten where external_url = 'https://vvz.phil.tu-dresden.de/'")) {
				$query = 'insert ignore into vvz_global.kundendaten (anrede, universitaet, name, ort, plz, strasse, dbname, urlname, plan_id, external_url) values ("Hallo Norbert,", "Technische Universität Dresden", "Norbert Engemaier", "Dresden", "01069", "Helmholtzstr. 10", "vvz_phil", "", 4, "https://vvz.phil.tu-dresden.de")';
				rquery($query);
			}

			if(!table_exists_and_has_entries("instance_config")) {
				$name = $_SERVER["REDIRECT_SFURI"] ?? get_kunden_db_name();
				$shortlink = $name;
				$query = "insert into `instance_config` (name, shortlink, dbname, kunde_id) VALUES (".esc($name).", ".esc($shortlink).", ".esc($GLOBALS['dbname']).", ".get_kunde_id_by_db_name($GLOBALS["dbname"]).")";
				rquery($query);
			}

			if(!table_exists_and_has_entries("dozent") && !already_initialized("dozent")) {
				rquery("insert INTO `dozent` (id, first_name, last_name, titel_id, ausgeschieden) VALUES (1,'Admin','Istrator', null, '0')");
				initialized("dozent");
			}

			if(!table_exists_and_has_entries("institut") && !already_initialized("institut")) {
				$default_institut_name = "Demo-Institut";
				rquery("insert into institut (id, name, start_nr) VALUES (1, ".esc($default_institut_name).", 1);");
				initialized("institut");
			}

			if(!table_exists_and_has_entries("role") && !already_initialized("role")) {
				rquery("insert into role (id, name, beschreibung) values ('1', 'Administrator', 'Darf alles');");
				rquery("insert into role (id, name, beschreibung) values ('2', 'Dozent', 'Darf eigene Sachen bearbeiten');");
				rquery("insert into role (id, name, beschreibung) values ('3', 'Dozent, Raumplanung', 'Darf eigene Sachen bearbeiten und auf Raumplanung zugreifen');");
				rquery("insert into role (id, name, beschreibung) values ('4', 'Superdozent', 'Darf eigene Sachen bearbeiten und auf Veranstaltungen anderer zugreifen ,');");
				rquery("insert into role (id, name, beschreibung) values ('5', 'Verwalter', 'Darf auf Statistiken und Veranstaltungen zugreifen');");
				rquery("insert into role (id, name, beschreibung) values ('6', 'Studienverwalter', 'Darf Studiengange und Prüfungsnummern editieren');");
				rquery("insert into role (id, name, beschreibung) values ('7', 'Raumplanungsverwalter', 'Darf alles was der Verwalter darf + Raumplanung');");
				initialized("role");
			}

			if(!table_exists_and_has_entries("users") || $GLOBALS["db_freshly_created"]) {
				$salt = generate_random_string(100);
				$default_username = "Admin";
				$default_password = "test";

				$accepted_public_data = 0;
				if(file_exists("/etc/always_accept_public_data")) {
					$accepted_public_data = 1;
				}

				$query = 'insert into users (`id`, `username`, `password_sha256`, `salt`, `dozent_id`, `institut_id`, `accepted_public_data`) values (1, '.esc($default_username).', '.esc(hash('sha256', $default_password.$salt)).', '.esc($salt).', 1, 1, '.esc($accepted_public_data).')';
				$result = rquery($query);

				$query = "insert into role_to_user (role_id, user_id) values (1, 1)";
				rquery($query);

				if($result) {
					set_session_id(1);
					$GLOBALS["auto_login_id"] = 1;
				}
			}

			if(!get_single_row_from_query("select count(*) from vvz_global.users")) {
				$salt = generate_random_string(100);
				$default_username = "Admin";
				$default_password = "test";
				$accepted_public_data = 0;
				if(file_exists("/etc/always_accept_public_data")) {
					$accepted_public_data = 1;
				}
				$query = 'insert into vvz_global.users (`id`, `username`, `password_sha256`, `salt`, `dozent_id`, `institut_id`, `accepted_public_data`) values (1, '.esc($default_username).', '.esc(hash('sha256', $default_password.$salt)).', '.esc($salt).', 1, 1, '.esc($accepted_public_data).')';
				$result = rquery($query);

				$query = "insert into vvz_global.role_to_user (role_id, user_id) values (1, 1)";
				rquery($query);

				if($result) {
					set_session_id(1);
					$GLOBALS["auto_login_id"] = 1;
				}
			}

			if(!table_exists_and_has_entries("page") && !already_initialized("page")) {
				rquery(
					"insert INTO `page` (id, name, file, show_in_navigation, parent, show_in_startpage) VALUES 
					(1,'Accounts','accounts.php','1',25, 1),
					(2,'Dozenten','dozenten.php','1',28, 0),
					(3,'Institute','institute.php','1',28, 0),
					(4,'Gebäude','gebaeude.php','1',28, 0),
					(5,'Module','modul.php','1',26, 0),
					(6,'Prüfungstypen','pruefungstypen.php','1',27, 0),
					(7,'Raumplanung','raumplanung.php','1',NULL, 0),
					(8,'Studiengänge','studiengang.php','1',28, 0),
					(9,'Veranstaltungen','veranstaltungen.php','1',NULL, 0),
					(10,'Veranstaltungstypen','veranstaltungstypen.php','1',28, 0),
					(11,'Rollen','roles.php','1',25, 1),
					(12,'Prüfungsnummern','pruefungsnummern.php','1',27, 0),
					(13,'Einzelne Veranstaltung','veranstaltung.php','1',28, 0),
					(15,'Seiteninformationen','edit_page_info.php','1',25, 0),
					(16,'Rechteprobleme','right_issues.php','0',25, 1),
					(17,'Query-Analyzer','query_analyzer.php','0',25, 1),
					(18,'Willkommen!','welcome.php','0',NULL, 1),
					(19,'API','api.php','1',25, 0),
					(20,'Modul &rarr; Semester','modul_nach_semester.php','1',26, 0),
					(21,'Eigene Daten ändern','password.php','1',NULL, 1),
					(22,'Bereiche','bereiche.php','1',28, 0),
					(23,'DB-Backup','backup.php','1',25, 1),
					(24,'DB-Backup-Export','backup_export.php','0',25, 1),
					(25,'System',NULL,'1',NULL, 1),
					(26,'Moduldaten',NULL,'1',NULL, 0),
					(27,'Prüfungsdaten',NULL,'1',NULL, 0),
					(28,'Stammdaten',NULL,'1',NULL, 1),
					(30,'Merges','merges.php','1',28, 0),
					(31,'DB-Diff','dbdiff.php','1',25, 1),
					(32,'User-Agents','useragents.php','1',25, 1),
					(33,'Alte Daten löschen','delete_old_data.php','1',28,0),
					(34,'Aktuelles Semester setzen','default_semester.php','1',25,0),
					(35,'Dozent &rarr; Prüfungsnummern','export_dozent_pruefungsnummern.php','1',27,0),
					(36,'Prüfungsämter','pruefungsaemter.php','1',27, 0),
					(37,'FAQ','faq.php','1',28, 0),
					(38,'Superdozent','superdozent.php','1',28, 0),
					(40,'Prüfungsnummer &rarr; Dozent','export_pruefungsnummern_dozent.php','1',27, 0),
					(41,'Prüfung &rarr; Zeitraum','pruefung_zeitraum.php','1',27, 0),
					(42,'Titel','titel.php','1',28, 0),
					(43,'Neue Seite','newpage.php','1',25, 0),
					(44,'Kontakt','../kontakt.php','1',NULL, 1),
					(45,'Funktionen','funktionen.php','0',25, 0),
					(46,'Stundenpläne','stundenplaene.php','1',28, 0),
					(47,'Semester','semester.php','1',28, 0),
					(48,'Sprachen','sprachen.php','1',28, 0),
					(49,'Neuer Dozent','neuerdozent.php','1',28,0),
					(51,'Exportiere Prüfungsleistungen','pruefungsleistungen_export.php','1',27, 0),
					(52,'Exportiere Prüfungsleistungen 2','pruefungsleistungen_export_2.php','1',27, 0),
					(53,'Module ohne PLs','module_ohne_pls.php','1',26, 0),
					(54,'PL pro Semester','pl_pro_semester.php','1',26, 0), 
					(55, 'Rechnungen', 'rechnungen.php', '1', null, 0),
					(56, 'Anpassen', 'anpassen.php', '1', NULL, 1),
					(57, 'Importer', 'import.php', '1', 25, 1),
					(58, 'Einstellungen', 'settings.php', '1', 25, 1)"
				);
				rquery("update page set disable_in_demo = 1 where id in (23, 24, 31, 55)");
				initialized("page");
			}

			if(!table_exists_and_has_entries("role_to_page") && !already_initialized("role_to_page")) {
				# removed: (1,32),
				rquery("insert ignore INTO `role_to_page` (role_id, page_id) VALUES (1,1),
					(1,2),
					(1,3),
					(1,4),
					(1,5),
					(1,6),
					(1,7),
					(1,8),
					(1,9),
					(1,10),
					(1,11),
					(1,12),
					(1,13),
					(1,15),
					(1,16),
					(1,17),
					(1,18),
					(1,19),
					(1,20),
					(1,21),
					(1,22),
					(1,23),
					(1,24),
					(1,25),
					(1,26),
					(1,27),
					(1,28),
					(1,30),
					(1,31),
					(1,33),
					(1,34),
					(1,35),
					(1,36),
					(1,37),
					(1,38),
					(1,40),
					(1,41),
					(1,42),
					(1,43),
					(1,44),
					(1,45),
					(1,46),
					(1,47),
					(1,48),
					(1,49),
					(1,51),
					(1,52),
					(1,53),
					(1,54),
					(1,57),
					(1,58),
					(2,7),
					(2,9),
					(2,13),
					(2,18),
					(2,21),
					(2,28),
					(2,44),
					(2,46),
					(3,7),
					(3,9),
					(3,13),
					(3,18),
					(3,21),
					(3,28),
					(3,44),
					(3,46),
					(3,52),
					(4,7),
					(4,9),
					(4,13),
					(4,18),
					(4,21),
					(4,28),
					(4,44),
					(4,46),
					(5,7),
					(5,9),
					(5,13),
					(5,18),
					(5,21),
					(5,25),
					(5,27),
					(5,28),
					(5,34),
					(5,35),
					(5,36),
					(5,37),
					(5,38),
					(5,40),
					(5,44),
					(5,46),
					(5,47),
					(5,48),
					(5,52),
					(6,5),
					(6,8),
					(6,12),
					(6,18),
					(6,20),
					(6,22),
					(6,26),
					(6,27),
					(6,28),
					(6,44),
					(7,5),
					(7,7),
					(7,8),
					(7,9),
					(7,12),
					(7,13),
					(7,18),
					(7,20),
					(7,21),
					(7,22),
					(7,26),
					(7,27),
					(7,28),
					(7,35),
					(7,40),
					(7,41),
					(7,44),
					(7,46),
					(7,49),
					(7,51),
					(7,52),
					(8,1),
					(9,2),
					(1,55),
					(1,56),
					(1,43);");
					initialized("role_to_page");
			}

			if(!table_exists_and_has_entries("function_right") && !already_initialized("function_right")) {
				rquery("insert ignore INTO `function_right` (id, function_name, description) VALUES 
				(61,'assign_page_to_role',NULL),(62,'assign_pruefungsnummer_to_veranstaltung',NULL),(66,'backup_tables',NULL),(67,'compare_db',NULL),(68,'create_api',NULL),(69,'create_bereich',NULL),(70,'create_dozent',NULL),(71,'create_fach',NULL),(72,'create_faq',NULL),(73,'create_gebaeude',NULL),(74,'create_institut',NULL),(75,'create_modul',NULL),(78,'create_nachpruefung',NULL),(82,'create_nachpruefung_liste',NULL),(83,'create_new_page',NULL),(85,'create_pruefung',NULL),(89,'create_pruefungsamt',NULL),(91,'create_pruefungsnummer',NULL),(93,'create_pruefungstyp',NULL),(94,'create_pruefung_zeitraum',NULL),(95,'create_raum',NULL),(99,'create_role',NULL),(101,'create_studiengang',NULL),(106,'create_title',NULL),(107,'create_user',NULL),(108,'create_veranstaltung',NULL),(112,'create_veranstaltungstyp',NULL),(113,'delete_api',NULL),(114,'delete_bereich',NULL),(115,'delete_dozent',NULL),(116,'delete_faq',NULL),(117,'delete_gebaeude',NULL),(118,'delete_institut',NULL),(119,'delete_modul',NULL),(120,'delete_nachpruefung',NULL),(124,'delete_page',NULL),(131,'delete_pruefung',NULL),(135,'delete_pruefungsamt',NULL),(136,'delete_pruefungsnummer',NULL),(137,'delete_pruefungstyp',NULL),(141,'delete_pruefung_zeitraum',NULL),(142,'delete_raum',NULL),(143,'delete_role',NULL),(144,'delete_semester',NULL),(145,'delete_studiengang',NULL),(146,'delete_titel',NULL),(147,'delete_user',NULL),(148,'delete_veranstaltung',NULL),(152,'delete_veranstaltungstyp',NULL),(153,'delete_veranstaltung_modul',NULL),(159,'delete_veranstaltung_studiengang',NULL),(170,'get_and_create_salt',NULL),(178,'insert_pruefungsnummern',NULL),(179,'kopiere_pruefungen_von_nach',NULL),(183,'merge_data',NULL),(185,'modul_zu_veranstaltung_hinzufuegen',NULL),(194,'raumplanung',NULL),(196,'setze_semester',NULL),(199,'SplitSQL',NULL),(200,'studiengang_zu_veranstaltung_hinzufuegen',NULL),(206,'update_api',NULL),(207,'update_barrierefrei',NULL),(211,'update_bereich',NULL),(212,'update_dozent',NULL),(213,'update_dozent_titel',NULL),(214,'update_fach',NULL),(215,'update_faq',NULL),(216,'update_gebaeude',NULL),(217,'update_hinweis',NULL),(218,'update_institut',NULL),(219,'update_modul',NULL),(220,'update_modul_semester',NULL),(221,'update_modul_semester_data',NULL),(222,'update_nachpruefung',NULL),(226,'update_or_create_role_to_page',NULL),(227,'update_own_data',NULL),(235,'update_page',NULL),(241,'update_page_full',NULL),(242,'update_page_info',NULL),(243,'update_pruefung',NULL),(247,'update_pruefungsamt',NULL),(248,'update_pruefungsamt_studiengang',NULL),(249,'update_pruefungsnummer',NULL),(250,'update_pruefungstyp',NULL),(251,'update_pruefung_zeitraum',NULL),(252,'update_raum',NULL),(253,'update_raumplanung',NULL),(255,'update_role',NULL),(256,'update_startseitentext',NULL),(257,'update_studiengang',NULL),(258,'update_superdozent',NULL),(259,'update_text',NULL),(260,'update_titel',NULL),(262,'update_user',NULL),(263,'update_user_role',NULL),(264,'update_veranstaltung',NULL),(268,'update_veranstaltungstyp',NULL),
				(269,'update_veranstaltung_metadata',NULL),(278,'delete_fach',NULL),(279,'update_role_to_page_page_info_hinweis',NULL),(280,'change_own_data',NULL),(281,'update_funktion_rights',NULL),(283,'delete_funktion_rights',NULL),(284,'create_function_rights',NULL),(285,'update_function_rights',NULL),(286,'create_function_right',NULL),(287,'update_right_to_page',NULL),(289,'update_right_to_user_role',NULL),(290,'update_semester',NULL),(291,'update_language',NULL),(293,'create_language',NULL),(294,'delete_language',NULL);");
				initialized("function_right");
			}

			if(!table_exists_and_has_entries("function_right_to_page") && !already_initialized("function_right_to_page")) {
				rquery("insert ignore into `function_right_to_page` (function_right_id, page_id) VALUES (61,11),(62,13),(66,23),(67,31),(68,19),(69,22),(70,2),(72,37),(73,4),(74,3),(75,5),(83,43),(89,36),(91,12),(93,6),(94,41),(95,7),(99,11),(101,8),(106,42),(107,1),(108,9),(108,13),(112,10),(113,19),(114,22),(115,2),(116,37),(117,4),(118,3),(119,5),(124,43),(135,36),(136,12),(137,6),(141,41),(142,7),(143,11),(144,33),(145,8),(146,42),(147,1),(148,9),(152,10),(170,1),(170,21),(183,30),(196,34),(199,23),(206,19),(207,2),(207,21),(211,22),(212,2),(213,2),(215,37),(216,4),(217,15),(217,43),(218,3),(219,5),(220,20),(226,43),(227,21),(235,43),(241,43),(242,15),(242,43),(247,36),(248,36),(249,12),(250,6),(251,41),(253,7),(255,11),(257,8),(258,38),(259,15),(260,42),(262,1),(263,11),(264,9),(264,13),(268,10),(269,13),(279,43),(280,21),(281,45),(283,45),(284,45),(290,47),(291,48),(293,48),(294,48);");
				initialized("function_right_to_page");
			}

			if(!table_exists_and_has_entries("function_right_to_user_role") && !already_initialized("function_right_to_user_role")) {
				rquery("insert ignore into `function_right_to_user_role` (function_right_id, role_id) VALUES (61,1),(62,1),(62,2),(62,3),(62,4),(62,5),(66,1),(67,1),(68,1),(69,1),(70,1),(71,1),(72,1),(73,1),(74,1),(75,1),(78,1),(78,2),(78,3),(78,4),(82,1),(83,1),(85,1),(85,2),(85,3),(85,4),(89,1),(91,1),(93,1),(94,1),(95,1),(95,2),(95,3),(95,4),(99,1),(101,1),(106,1),(107,1),(108,1),(108,2),(108,3),(108,4),(112,1),(113,1),(114,1),(115,1),(116,1),(117,1),(118,1),(119,1),(120,1),(120,2),(120,3),(120,4),(124,1),(131,1),(131,2),(131,3),(131,4),(135,1),(136,1),(137,1),(137,2),(137,3),(137,4),(141,1),(142,1),(143,1),(144,1),(145,1),(146,1),(147,1),(148,1),(148,2),(148,3),(148,4),(152,1),(153,1),(153,2),(153,3),(153,4),(159,1),(159,2),(159,3),(159,4),(170,1),(170,2),(170,3),(170,4),(178,1),(179,1),(179,2),(179,3),(179,4),(183,1),(185,1),(185,2),(185,3),(185,4),(194,1),(196,1),(196,5),(199,1),(200,1),(200,2),(200,3),(200,4),(206,1),(207,1),(207,2),(207,3),(207,4),(211,1),(212,1),(213,1),(214,1),(215,1),(216,1),(217,1),(218,1),(219,1),(220,1),(221,1),(222,1),(222,2),(222,3),(222,4),(226,1),(227,1),(227,2),(227,3),(227,4),(235,1),(241,1),(242,1),(243,1),(243,2),(243,3),(243,4),(247,1),(248,1),(249,1),(250,1),(251,1),(252,1),(253,1),(253,3),(255,1),(256,1),(257,1),(258,1),(259,1),(260,1),(262,1),(263,1),(264,1),(264,2),(264,3),(264,4),(264,5),(268,1),(269,1),(269,2),(269,3),(269,4),(269,5),(281,1),(283,1),(284,1),(286,1),(287,1),(289,1);");
				initialized("function_right_to_user_role");
			}
			#### FROM HERE

			if(!table_exists_and_has_entries("hinweise") && !already_initialized("hinweise")) {
				rquery("insert ignore into `hinweise` (page_id, hinweis) VALUES (11,'Jedem Benutzer ist eine Rolle zugeordnet. Diese Rolle bestimmt, welche Seiten er sehen und editieren darf.'),(13,'Bitte achten Sie darauf, nachträgliche Änderungen gegebenenfalls der Raumplanung oder dem Prüfungsamt mitzuteilen.');");
				initialized("hinweise");
			}

			if(!table_exists_and_has_entries("language") && !already_initialized("language")) {
				rquery("insert ignore into `language` (id, abkuerzung, name) VALUES (1,'deu','deutsch'),(2,'eng','englisch'),(3,'tlh','klingonisch'),(4,'frz','französisch');");
				initialized("language");
			}

			if(!table_exists_and_has_entries("page_info") && !already_initialized("page_info")) {
				rquery("insert ignore into `page_info` (page_id, info) VALUES (1,'Hier können neue Benutzerkonten angelegt und alte gelöscht werden.'),(2,'Führt neue Dozenten in das System ein.'),(3,'Dieses Vorlesungverzeichnis ist dafür ausgelegt, an beliebig vielen Instituten benutzt zu werden. In diesem Punkt kann man neue Institute einfügen.'),(4,'Führt Gebäude in das System ein (»GER — Gerberbau«, ...), die dann überall im System zur Verfügung stehen.'),(5,'Veranstaltungen sind normalerweise in Modulen. Hier können neue Module eingeführt werden, die später mit den Veranstaltungen verknüpft werden können.'),(6,'Führt neue Arten von Prüfungsleistungen ein (z. B. Klausur, Essay, ...)'),(7,'Liefert eine Liste (exportierbar als Excel-Datei) von Räumen und Nutzungen.'),(8,'Macht dem System neue Studiengänge bekannt.'),(9,'Hier können neue Veranstaltungen definiert und vorhandene bearbeitet werden.'),(10,'Hier können Arten von Veranstaltungen definiert werden (Vorlesung, Proseminar, Textproseminar, ...).'),(11,'Jeder Benutzer nimmt im System eine bestimmte Rolle ein. Je nach Rolle kann er einige Seiten sehen und andere nicht. Ein Administrator kann z.B. alles sehen und bearbeiten, während ein Dozent nur Räume, Veranstaltungen, Prüfungen und Nachprüfungen einsehen und editieren darf. Hier können die Rollen selbst, d. h. das, was der Benutzer darf, geändert werden.'),(12,'Hier können Prüfungsnummern erstellt und Modulen und Studiengängen zugeordnet werden.'),(13,'Hier können Sie ihre Lehrveranstaltungen einzeln editieren. Bitte achten Sie darauf, nachträgliche Änderungen gegebenenfalls der Raumplanung oder dem Prüfungsamt mitzuteilen.'),
				(15,'Ermöglicht das Editieren der Startseiteninformationen'),(16,'Zeigt eine Liste der Rechteverstöße einzelner User an.'),(17,'Analysiert Queries'),(18,'Willkommensseite'),(19,'Ermöglicht es, neue API-Zugänge zu erstellen und Vorhandene zu bearbeiten.'),(20,'Hier kann festgelegt werden, welche Module in welchem Semester ausgeführt werden sollten, um daraus halbautomatisiert einen Stundenplan erstellen zu können.'),(21,'Hier kann jeder Nutzer sein eigenen Benutzermetadaten ändern.'),(22,'Hier können einzelne Bereiche der Studiengänge bearbeitet werden (Ergänzungsbereich, Kernbereich etc.).'),(23,'Ermöglicht halbautomatische Datenbankbackups.'),(24,'Erstellt Datenbank-Backups.'),(25,'In diesem Unterpunkt sind alle internen Systemseiten.'),(26,'In diesem Unterpunkt sind alle die Module betreffenden Optionen.'),(27,'Hier können alle Daten, die zu den Prüfungsinformationen gehören, bearbeitet werden.'),(28,'Hier können verschiedene Stammdaten bearbeitet werden (Dozenten, Gebäude, Institute etc.).'),(30,'Erlaubt das Mergen von Datensätzen (wenn z. B. zwei Module mit leicht unterschiedlichen Namen eigentlich eines sind, kann man hier diese Daten zusammenfügen.'),(31,'Zeigt Unterschiede zwischen Datenbankbackups und aktueller Datenbank.'),(32,'Zeigt die häufigsten User-Agents'),(33,'Erlaubt es, Veranstaltungen aus vorherigen Semestern zu löschen'),(34,'Setzt das aktuelle Semester'),(35,'Exportiert eine Liste von Dozenten und den von Ihnen angebotenen Prüfungsnummern'),(36,'Erlaubt die Zuordnung von einzelnen Prüfungsämtern zu Studiengängen'),(37,'Erlaubt es, die Einträge des FAQs zu bearbeiten'),(38,'Superdozenten haben das Recht, auf Veranstaltungen von (definierten) anderen Personen zugreifen zu können.'),(40,'Exportiert eine Liste von Prüfungsnummern den Dozenten, die sie anbieten.'),(41,'Erlaubt die Definition und Änderung von Prüfungszeiträumen'),(42,'Erlaubt die Definition und Änderung akademischer Titel zur Zuordnung zu Dozenten.'),(43,'Erlaubt die Bearbeitung von Seitentiteln, Rechten und Position in der Navigation.'),(44,'Kontakt zu den Administratoren herstellen.'),(45,'Definiert, welche Rolle bzw. welche Seite auf welche Funktionen zugreifen darf.'),(46,'Listet die Stundenpläne aller Dozenten auf'),(47,'Erlaubt die Bearbeitung von Semesterdaten'),(48,'Editiert angebotene Sprachen'),(49,NULL),(51,NULL),(52,NULL),(53,NULL),(54,NULL), ('55', NULL), ('56', 'Hier können Farben, Logos usw. angepasst werden.'), ('57', 'Hier können Daten importiert werden'), ('58', 'Hier können allgemeine Einstellungen geändert werden')");
				initialized("page_info");
			}

			if(!table_exists_and_has_entries("praesenztyp") && !already_initialized("praesenztyp")) {
				rquery("insert ignore into `praesenztyp` (id, name) VALUES (1,'Digital'),(2,'Hybrid'),(3,'Präsenz');");
				initialized("praesenztyp");
			}

			if(!table_exists_and_has_entries("pruefung_zeitraum") && !already_initialized("pruefung_zeitraum")) {
				rquery("insert ignore into `pruefung_zeitraum` (id, name) VALUES (1,'Kein spezifischer Zeitraum'),(2,'Erster Zeitraum'), (3, 'Zweiter Zeitraum');");
				initialized("pruefung_zeitraum");
			}

			/*
			if(!table_exists_and_has_entries("seitentext")) {
				rquery("insert ignore into `seitentext` VALUES (18,'<h2>Willkommen auf der Administrationsseite des Vorlesungsverzeichnisses</h2>');");
			}
			 */

			if(!table_exists_and_has_entries("titel") && !already_initialized("titel")) {
				rquery("insert ignore into `titel` (id, name, abkuerzung) VALUES (1,'Doktor','Dr.'),(2,'Privatdozent','PD Dr.'),(3,'Professor Doktor','Prof. Dr.'), (4, 'Professor', 'Prof.');");
				initialized("titel");
			}

			if(!table_exists_and_has_entries("page_info") && !already_initialized("role_to_user")) {
				rquery("INSERT ignore INTO `role_to_user` VALUES (1,1);");
				initialized("role_to_user");
			}

			$show_importer = 0;

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


			if(!table_exists_and_has_entries("pruefungstyp") && !already_initialized("pruefungstyp")) {
				insert_values('pruefungstyp', array('name'), $pruefungstypen);
				initialized("pruefungstyp");
			}

			if(!table_exists_and_has_entries("veranstaltungstyp") && !already_initialized("veranstaltungstyp")) {
				insert_values('veranstaltungstyp', array('name', 'abkuerzung'), $veranstaltungstypen);
				initialized("veranstaltungstyp");
			}

			if(!table_exists_and_has_entries("api_error_code") && !already_initialized("api_error_code")) {
				$sql = "insert into api_error_code (name) values ('Kein Fehler'), ('Falscher Auth-Code'), ('Falsche Parameter'), ('Zu wenig Zeit vergangen');";
				rquery($sql);
				initialized("api_error_code");
			}

			if(table_exists($GLOBALS['dbname'], 'users')) {
				$query = 'SELECT username FROM `'.$GLOBALS['dbname'].'`.`users` `u` JOIN `role_to_user` `ur` ON `ur`.`user_id` = `u`.`id` WHERE `role_id` = 1';
				$result = rquery($query);
				while ($row = mysqli_fetch_row($result)) {
					$admin_accounts[] = $row[0];
				}
			}

			#$query = 'SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = "'.$GLOBALS['dbname'].'"';
			#$result = rquery($query);


			if(!table_exists_and_has_entries("bereiche") && !already_initialized("bereich")) {
				$bereiche_query = "INSERT IGNORE INTO `bereich` (id, name) VALUES (1, '-');";
				rquery($bereiche_query);
				initialized("bereich");
			}

			if(!table_exists_and_has_entries("customizations") && !already_initialized("customizations")) {
				$default_values = array(
					"button:hover, input[type='button']:hover, input[type='submit']:hover" => array(
						"name" => "Buttons Mouseover",
						"values" => [
							[ "background-color", "d1ffce" ],
							[ "color", "black" ],
							[ "border", "1px solid 006092" ],
							[ "cursor", "unset" ],
							[ "border-radius", "5px" ],
						],
					),
					"button, input[type='button'], input[type='submit']" => array(
						"name" => "Buttons",
						"values" => [
							[ "background-color", "ffffff" ],
							[ "color", "black" ],
							[ "border", "1px solid" ],
							[ "border-color", "006092" ],
							[ "cursor", "unset" ],
							[ "border-radius", "5px" ],
							[ "box-shadow", "none" ],
							[ "padding", " 4px" ]
						],
					),
					"th" => array(
						"name" => "Tabellenheader",
						"values" => [
							[ "background-color", "006092" ],
							[ "color", "ffffff" ],
							[ "padding-left", "10px" ],
							[ "padding-right", "10px" ]
						]
					),
					".topnav li:hover a" => array(
						"name" => "Mouseover-Farbe für Tabs in der Navigationsleiste",
						"values" => [
							[ "background-color", "008fd6" ]
						]
					),
					".selected_tab" => array(
						"name" => "Farbe des ausgewählten Tabs in der Navigationsleiste",
						"values" => [
							[ "background-color", "00aaff" ]
						]
					),

					"table tbody tr:nth-child(2n)" => array(
						"name" => "Jede gerade Zeile in einer Tabelle",
						"values" => [
							[ "background-color", "F5F5F5" ]
						]
					),
					"table tbody tr:nth-child(2n+1)" => array(
						"name" => "Jede ungerade Zeile in einer Tabelle",
						"values" => [
							[ "background-color", "EDEDED" ]
						]
					),


					".topnav" => array(
						"name" => "Navigationsleiste",
						"values" => array(
							[ "background-color", "006092" ],
							[ "overflow", "hidden" ],
							[ "padding-left", "0px" ]
						)
					),

					".topnav ul" => array(
						"name" => "Navigationsleiste (Einzelnes Element)",
						"values" => array(
							[ "list-style-type", "none" ],
							[ "margin", "0" ],
							[ "padding", "0" ],
							[ "overflow", "hidden" ]
						)
					),

					".dropdown" => array(
						"name" => "Navigationsleiste Dropdown",
						"values" => array(
							[ "background-color", "004d74" ]
						)
					),


					".bg_multispan_col_header" => array(
						"name" => "Spaltenüberschreitende Überschriften in Tabellen",
						"values" => array(
							[ "background-color", "ADD8E6"]
						)
					),

					"#alle_lehrveranstaltungen" => array(
						"name" => "Farbe des Links zu 'Alle Lehrveranstaltungen' auf der Startseite",
						"values" => array(
							[ "background-color", "f7e4e1" ],
							[ "color", "0a0a0a" ]
						)
					),


					".allepls" => array(
						"name" => "Farbe des Links zu 'Alle Prüfungsleistungen' auf der Startseite",
						"values" => array(
							[ "background-color", "98FB98" ],
							[ "color", "black" ]
						)
					)


				);

	#'customizations' => 'create table customizations (id int unsigned primary key, classname varchar(100), property varchar(100), val varchar(100))'
				foreach ($default_values as $default_key => $default_item) {
					foreach ($default_item["values"] as $real_default_item) {
						$query = "insert into customizations (humanname, classname, property, val, default_val) values (".esc($default_item["name"]).", ".esc($default_key).", ".esc($real_default_item[0]).", ".esc($real_default_item[1]).", ".esc($real_default_item[1]).")";
						rquery($query);
					}
				}

				initialized("customizations");
			}

			if(!table_exists_and_has_entries("veranstaltung_bezuegetypen") && !already_initialized("veranstaltung_bezuegetypen")) {
				$bereiche_query = "INSERT IGNORE INTO `veranstaltung_bezuegetypen` (id, name) VALUES (1, 'Bezug zu einem Forschungsprojekt');";
				$bereiche_query = "INSERT IGNORE INTO `veranstaltung_bezuegetypen` (id, name) VALUES (2, 'Berufspraktische Bezüge');";
				$bereiche_query = "INSERT IGNORE INTO `veranstaltung_bezuegetypen` (id, name) VALUES (3, 'Interdisziplinäre Bezüge');";
				$bereiche_query = "INSERT IGNORE INTO `veranstaltung_bezuegetypen` (id, name) VALUES (4, 'Beinhaltet eine Exkursion');";
				rquery($bereiche_query);
				initialized("bereich");
			}

			rquery('set FOREIGN_KEY_CHECKS=1');

			add_system_versioning();
		} catch (\Throwable $e) {
			stderrw($e);

			sleep(1);
			print "Es dauert nur noch einen Moment. Bitte warten Sie...";
			print '<meta http-equiv="refresh" content="0; url=./" />';
			flush();
			exit(0);
		}
	}


        if(!get_get('noselftest') && !$GLOBALS["no_selftest"] && !$GLOBALS["selftest_already_done"]) {
                selftest();
		$GLOBALS["selftest_already_done"] = 1;
		schreibe_rechnungen_fuer_alle_dieser_monat();


		if(is_demo($GLOBALS["dbname"]) && !$GLOBALS["logged_in_user_id"] && get_get("first_login")) {
			if(!file_exists("/etc/vvztud")) {
				set_session_id(1);
			}
		}
        }

?>
