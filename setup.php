<?php
/*
Diese Datei soll helfen, ein neues System aufzusetzen, in dem es die nötigen
Ordner, Datenbanken etc. erstellt und mit den ersten, einfachen Daten befüllt.
 */
	$GLOBALS['setup_mode'] = 1;
	$php_start = microtime(true);
	if(file_exists('new_setup')) {
		$page_title = "Vorlesungsverzeichnis TU Dresden";
		$filename = 'index.php';
		include("header.php");
?>
	<div id="mainindex">
		<a href="index.php" border="0"><img alt="TUD-Logo, Link zur Startseite"  src="tudlogo.svg" width="255" /></a>
		<h1>Setup</h1>

<?php
		$result = rquery('use '.$GLOBALS['dbname'], 0);
		if ($result === false) {
			print "<h2>Erstelle die Datenbank `".$GLOBALS['dbname']."`</h2>\n";
			rquery('CREATE DATABASE `'.$GLOBALS['dbname'].'`');
		}


		$admin_accounts = array();

		if(array_key_exists('username', $_POST) && array_key_exists('password', $_POST)) {
			include("sql_datenbanken.php");

			#rquery('SET @@global.innodb_large_prefix = 1');
			rquery('select @@FOREIGN_KEY_CHECKS');
			rquery('set FOREIGN_KEY_CHECKS=0');

			foreach ($GLOBALS['databases'] as $name => $create) {
				if(table_exists($GLOBALS['dbname'], $name)) {
					print "Die Tabelle `$name` existiert bereits. Daher erstelle ich sie nicht neu...<br>\n";
				} else {
					print "Die Tabelle `$name` existiert noch nicht. <i><b>Daher erstelle ich sie gerade neu...</b></i><br>\n";
					rquery($create);
				}
			}
			rquery('set FOREIGN_KEY_CHECKS=1');

			foreach ($GLOBALS['views'] as $name => $create) {
				if(view_exists($GLOBALS['dbname'], $name)) {
					print "Die View `$name` existiert bereits. Daher erstelle ich sie nicht neu...<br>\n";
				} else {
					print "Die View `$name` existiert noch nicht. <i><b>Daher erstelle ich sie gerade neu...</b></i><br>\n";
					rquery($create);
				}
			}

			rquery('use `'.$GLOBALS['dbname'].'`');
			$salt = generate_random_string(100);

			print "<h2>Creating Administrator-account ".htmle(get_post('username'))."</h2>\n";

			$query = 'insert ignore into '.$GLOBALS['dbname'].'.users (`username`, `password_sha256`, `salt`) values ('.esc(get_post('username')).', '.esc(hash('sha256', get_post('password').$salt)).', '.esc($salt).')';

			if(rquery($query)) {
				print "OK Creating user";
			} else {
				print "ERROR Creating user";
			}

			print "<h2>Creating roles</h2>";

			$query = "INSERT ignore INTO `role` VALUES (1,'Administrator','darf alles'),(2,'Dozent','darf eigene sachen bearbeiten'),(3,'Dozent, Raumplanung','darf eigene sachen bearbeiten und auf raumplanung zugreifen'),(4,'Superdozent','darf eigene sachen bearbeiten und auf veranstaltungen anderer zugreifen'),(5,'Verwalter','darf auf statistiken und veranstaltungen zugreifen'),(6,'Studienverwalter','darf studiengange und pns editieren');";

			if(rquery($query)) {
				print "OK Creating roles";
			} else {
				print "ERROR Creating roles";
			}

			print "<h2>Assigning ".htmle(get_post("username"))." the Administrator-role</h2>";

			$account_id = get_user_id(get_post('username'));
			$query = 'INSERT IGNORE INTO `role_to_user` VALUES (1, '.esc($account_id).')';
			if(rquery($query)) {
				print "Assignment OK";
			} else {
				print "Assignment ERROR";
			}

			print "<br>";
		}

		if(table_exists($GLOBALS['dbname'], 'users')) {
			$query = 'SELECT username FROM `'.$GLOBALS['dbname'].'`.`users` `u` JOIN `role_to_user` `ur` ON `ur`.`user_id` = `u`.`id` WHERE `role_id` = 1';
			$result = rquery($query);
			while ($row = mysqli_fetch_row($result)) {
				$admin_accounts[] = $row[0];
			}
		}

		$query = 'SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = "'.$GLOBALS['dbname'].'"';
		$result = rquery($query);


		if(count($admin_accounts)) {

			print "<h2>Folgende Administrator-Accounts existieren bereits: </h2><br />\n";
			print "<ul>\n";
			foreach ($admin_accounts as $this_admin) {
				print "\t<li>$this_admin</li>\n";
			}
			print "</ul>\n";

			if($GLOBALS['slurped_sql_file'] == 0) {
				print "<h2>Initialdatensatzfüllung</h2>\n";
				#include('fill_database.php');

				$GLOBALS['fill_database'] = array(
					"SET FOREIGN_KEY_CHECKS=0;",
					"INSERT IGNORE INTO `page` VALUES (1,'Accounts','accounts.php','1',25),(2,'Dozenten','dozenten.php','1',28),(3,'Institute','institute.php','1',28),(4,'Gebäude','gebaeude.php','1',28),(5,'Module','modul.php','1',26),(6,'Prüfungstypen','pruefungstypen.php','1',27),(7,'Raumplanung','raumplanung.php','1',NULL),(8,'Studiengänge','studiengang.php','1',28),(9,'Veranstaltungen','veranstaltungen.php','1',NULL),(10,'Veranstaltungstypen','veranstaltungstypen.php','1',28),(11,'Rollen','roles.php','1',25),(12,'Prüfungsnummern','pruefungsnummern.php','1',27),(13,'Einzelne Veranstaltung','veranstaltung.php','1',28),(15,'Seiteninformationen','edit_page_info.php','1',25),(16,'Rechteprobleme','right_issues.php','0',25),(17,'Query-Analyzer','query_analyzer.php','0',25),(18,'Willkommen!','welcome.php','0',NULL),(19,'API','api.php','1',25),(20,'Modul &rarr; Semester','modul_nach_semester.php','1',26),(21,'Eigene Daten ändern','password.php','1',NULL),(22,'Bereiche','bereiche.php','1',28),(23,'DB-Backup','backup.php','1',25),(24,'DB-Backup-Export','backup_export.php','0',25),(25,'System',NULL,'1',NULL),(26,'Moduldaten',NULL,'1',NULL),(27,'Prüfungsdaten',NULL,'1',NULL),(28,'Stammdaten',NULL,'1',NULL),(30,'Merges','merges.php','1',28),(31,'DB-Diff','dbdiff.php','1',25),(32,'User-Agents','useragents.php','1',25),(33,'Alte Daten löschen','delete_old_data.php','1',28),(34,'Aktuelles Semester setzen','default_semester.php','1',25),(35,'Dozent &rarr; Prüfungsnummern','export_dozent_pruefungsnummern.php','1',27),(36,'Prüfungsämter','pruefungsaemter.php','1',27),(37,'FAQ','faq.php','1',28),(38,'Superdozent','superdozent.php','1',28),(39,'Apache-Neustarts','apache_restarts.php','1',25),(40,'Prüfungsnummer &rarr; Dozent','export_pruefungsnummern_dozent.php','1',27),(41,'Prüfung &rarr; Zeitraum','pruefung_zeitraum.php','1',27),(42,'Titel','titel.php','1',28),(43,'Neue Seite','newpage.php','1',25),(44,'Kontakt','../kontakt.php','1',NULL),(45,'Funktionen','funktionen.php','1',25),(46,'Stundenpläne','stundenplaene.php','1',28),(47,'Semester','semester.php','1',28),(48,'Sprachen','sprachen.php','1',28);",
					"INSERT INTO `page_info` VALUES (1,'Hier können neue Benutzerkonten angelegt und alte gelöscht werden.'),(2,'Führt neue Dozenten in das System ein.'),(3,'Dieses Vorlesungverzeichnis ist dafür ausgelegt, an beliebig vielen Instituten benutzt zu werden. In diesem Punkt kann man neue Institute einfügen.'),(4,'Führt Gebäude in das System ein (»GER — Gerberbau«, ...), die dann überall im System zur Verfügung stehen.'),(5,'Veranstaltungen sind normalerweise in Modulen. Hier können neue Module eingeführt werden, die später mit den Veranstaltungen verknüpft werden können.'),(6,'Führt neue Arten von Prüfungsleistungen ein (z. B. Klausur, Essay, ...)'),(7,'Liefert eine Liste (exportierbar als Excel-Datei) von Räumen und Nutzungen.'),(8,'Macht dem System neue Studiengänge bekannt.'),(9,'Hier können neue Veranstaltungen definiert und vorhandene bearbeitet werden.'),(10,'Hier können Arten von Veranstaltungen definiert werden (Vorlesung, Proseminar, Textproseminar, ...).'),(11,'Jeder Benutzer nimmt im System eine bestimmte Rolle ein. Je nach Rolle kann er einige Seiten sehen und andere nicht. Ein Administrator kann z.B. alles sehen und bearbeiten, während ein Dozent nur Räume, Veranstaltungen, Prüfungen und Nachprüfungen einsehen und editieren darf. Hier können die Rollen selbst, d. h. das, was der Benutzer darf, geändert werden.'),(12,'Hier können Prüfungsnummern erstellt und Modulen und Studiengängen zugeordnet werden.'),(13,'Hier können Sie ihre Lehrveranstaltungen einzeln editieren. Bitte achten Sie darauf, nachträgliche Änderungen gegebenenfalls der Raumplanung oder dem Prüfungsamt mitzuteilen.'),(15,'Ermöglicht das Editieren der Startseiteninformationen'),(16,'Zeigt eine Liste der Rechteverstöße einzelner User an.'),(17,'Analysiert Queries'),(18,'Willkommensseite'),(19,'Ermöglicht es, neue API-Zugänge zu erstellen und Vorhandene zu bearbeiten.'),(20,'Hier kann festgelegt werden, welche Module in welchem Semester ausgeführt werden sollten, um daraus halbautomatisiert einen Stundenplan erstellen zu können.'),(21,'Hier kann jeder Nutzer sein eigenen Benutzermetadaten ändern.'),(22,'Hier können einzelne Bereiche der Studiengänge bearbeitet werden (Ergänzungsbereich, Kernbereich etc.).'),(23,'Ermöglicht halbautomatische Datenbankbackups.'),(24,'Erstellt Datenbank-Backups.'),(25,'In diesem Unterpunkt sind alle internen Systemseiten.'),(26,'In diesem Unterpunkt sind alle die Module betreffenden Optionen.'),(27,'Hier können alle Daten, die zu den Prüfungsinformationen gehören, bearbeitet werden.'),(28,'Hier können verschiedene Stammdaten bearbeitet werden (Dozenten, Gebäude, Institute etc.).'),(30,'Erlaubt das Mergen von Datensätzen (wenn z. B. zwei Module mit leicht unterschiedlichen Namen eigentlich eines sind, kann man hier diese Daten zusammenfügen.'),(31,'Zeigt Unterschiede zwischen Datenbankbackups und aktueller Datenbank.'),(32,'Zeigt die häufigsten User-Agents'),(33,'Erlaubt es, Veranstaltungen aus vorherigen Semestern zu löschen'),(34,'Setzt das aktuelle Semester'),(35,'Exportiert eine Liste von Dozenten und den von Ihnen angebotenen Prüfungsnummern'),(36,'Erlaubt die Zuordnung von einzelnen Prüfungsämtern zu Studiengängen'),(37,'Erlaubt es, die Einträge des FAQs zu bearbeiten'),(38,'Superdozenten haben das Recht, auf Veranstaltungen von (definierten) anderen Personen zugreifen zu können.'),(39,'Übersicht der automatischen Apache-Neustarts.'),(40,'Exportiert eine Liste von Prüfungsnummern den Dozenten, die sie anbieten.'),(41,'Erlaubt die Definition und Änderung von Prüfungszeiträumen'),(42,'Erlaubt die Definition und Änderung akademischer Titel zur Zuordnung zu Dozenten.'),(43,'Erlaubt die Bearbeitung von Seitentiteln, Rechten und Position in der Navigation.'),(44,'Kontakt zu den Administratoren herstellen.'),(45,'Definiert, welche Rolle bzw. welche Seite auf welche Funktionen zugreifen darf.'),(46,'Listet die Stundenpläne aller Dozenten auf'),(47,'Erlaubt die Bearbeitung von Semesterdaten'),(48,'Editiert angebotene Sprachen');",
					"INSERT INTO `role_to_page` VALUES (1,1),(1,2),(1,3),(1,4),(1,5),(1,6),(1,7),(1,8),(1,9),(1,10),(1,11),(1,12),(1,13),(1,15),(1,16),(1,17),(1,18),(1,19),(1,20),(1,21),(1,22),(1,23),(1,24),(1,25),(1,26),(1,27),(1,28),(1,30),(1,31),(1,32),(1,33),(1,34),(1,35),(1,36),(1,37),(1,38),(1,39),(1,40),(1,41),(1,42),(1,43),(1,44),(1,45),(1,46),(1,47),(1,48),(2,7),(2,9),(2,13),(2,18),(2,21),(2,28),(2,44),(2,46),(3,7),(3,9),(3,13),(3,18),(3,21),(3,28),(3,44),(3,46),(4,7),(4,9),(4,13),(4,18),(4,21),(4,28),(4,44),(4,46),(5,7),(5,9),(5,13),(5,18),(5,21),(5,25),(5,27),(5,28),(5,34),(5,35),(5,36),(5,37),(5,38),(5,40),(5,44),(5,46),(5,47),(5,48),(6,5),(6,8),(6,12),(6,18),(6,20),(6,22),(6,26),(6,27),(6,28),(6,44);",
					"INSERT INTO `function_right` VALUES (61,'assign_page_to_role',NULL),(62,'assign_pruefungsnummer_to_veranstaltung',NULL),(66,'backup_tables',NULL),(67,'compare_db',NULL),(68,'create_api',NULL),(69,'create_bereich',NULL),(70,'create_dozent',NULL),(71,'create_fach',NULL),(72,'create_faq',NULL),(73,'create_gebaeude',NULL),(74,'create_institut',NULL),(75,'create_modul',NULL),(78,'create_nachpruefung',NULL),(82,'create_nachpruefung_liste',NULL),(83,'create_new_page',NULL),(85,'create_pruefung',NULL),(89,'create_pruefungsamt',NULL),(91,'create_pruefungsnummer',NULL),(93,'create_pruefungstyp',NULL),(94,'create_pruefung_zeitraum',NULL),(95,'create_raum',NULL),(99,'create_role',NULL),(101,'create_studiengang',NULL),(106,'create_title',NULL),(107,'create_user',NULL),(108,'create_veranstaltung',NULL),(112,'create_veranstaltungstyp',NULL),(113,'delete_api',NULL),(114,'delete_bereich',NULL),(115,'delete_dozent',NULL),(116,'delete_faq',NULL),(117,'delete_gebaeude',NULL),(118,'delete_institut',NULL),(119,'delete_modul',NULL),(120,'delete_nachpruefung',NULL),(124,'delete_page',NULL),(131,'delete_pruefung',NULL),(135,'delete_pruefungsamt',NULL),(136,'delete_pruefungsnummer',NULL),(137,'delete_pruefungstyp',NULL),(141,'delete_pruefung_zeitraum',NULL),(142,'delete_raum',NULL),(143,'delete_role',NULL),(144,'delete_semester',NULL),(145,'delete_studiengang',NULL),(146,'delete_titel',NULL),(147,'delete_user',NULL),(148,'delete_veranstaltung',NULL),(152,'delete_veranstaltungstyp',NULL),(153,'delete_veranstaltung_modul',NULL),(159,'delete_veranstaltung_studiengang',NULL),(170,'get_and_create_salt',NULL),(178,'insert_pruefungsnummern',NULL),(179,'kopiere_pruefungen_von_nach',NULL),(183,'merge_data',NULL),(185,'modul_zu_veranstaltung_hinzufuegen',NULL),(194,'raumplanung',NULL),(196,'setze_semester',NULL),(199,'SplitSQL',NULL),(200,'studiengang_zu_veranstaltung_hinzufuegen',NULL),(206,'update_api',NULL),(207,'update_barrierefrei',NULL),(211,'update_bereich',NULL),(212,'update_dozent',NULL),(213,'update_dozent_titel',NULL),(214,'update_fach',NULL),(215,'update_faq',NULL),(216,'update_gebaeude',NULL),(217,'update_hinweis',NULL),(218,'update_institut',NULL),(219,'update_modul',NULL),(220,'update_modul_semester',NULL),(221,'update_modul_semester_data',NULL),(222,'update_nachpruefung',NULL),(226,'update_or_create_role_to_page',NULL),(227,'update_own_data',NULL),(235,'update_page',NULL),(241,'update_page_full',NULL),(242,'update_page_info',NULL),(243,'update_pruefung',NULL),(247,'update_pruefungsamt',NULL),(248,'update_pruefungsamt_studiengang',NULL),(249,'update_pruefungsnummer',NULL),(250,'update_pruefungstyp',NULL),(251,'update_pruefung_zeitraum',NULL),(252,'update_raum',NULL),(253,'update_raumplanung',NULL),(255,'update_role',NULL),(256,'update_startseitentext',NULL),(257,'update_studiengang',NULL),(258,'update_superdozent',NULL),(259,'update_text',NULL),(260,'update_titel',NULL),(262,'update_user',NULL),(263,'update_user_role',NULL),(264,'update_veranstaltung',NULL),(268,'update_veranstaltungstyp',NULL),(269,'update_veranstaltung_metadata',NULL),(278,'delete_fach',NULL),(279,'update_role_to_page_page_info_hinweis',NULL),(280,'change_own_data',NULL),(281,'update_funktion_rights',NULL),(283,'delete_funktion_rights',NULL),(284,'create_function_rights',NULL),(285,'update_function_rights',NULL),(286,'create_function_right',NULL),(287,'update_right_to_page',NULL),(289,'update_right_to_user_role',NULL),(290,'update_semester',NULL),(291,'update_language',NULL),(293,'create_language',NULL),(294,'delete_language',NULL);",
					"INSERT INTO `function_right_to_page` VALUES (61,11),(62,13),(66,23),(67,31),(68,19),(69,22),(70,2),(72,37),(73,4),(74,3),(75,5),(83,43),(89,36),(91,12),(93,6),(94,41),(95,7),(99,11),(101,8),(106,42),(107,1),(108,9),(108,13),(112,10),(113,19),(114,22),(115,2),(116,37),(117,4),(118,3),(119,5),(124,43),(135,36),(136,12),(137,6),(141,41),(142,7),(143,11),(144,33),(145,8),(146,42),(147,1),(148,9),(152,10),(170,1),(170,21),(183,30),(196,34),(199,23),(206,19),(207,2),(207,21),(211,22),(212,2),(213,2),(215,37),(216,4),(217,15),(217,43),(218,3),(219,5),(220,20),(226,43),(227,21),(235,43),(241,43),(242,15),(242,43),(247,36),(248,36),(249,12),(250,6),(251,41),(253,7),(255,11),(257,8),(258,38),(259,15),(260,42),(262,1),(263,11),(264,9),(264,13),(268,10),(269,13),(279,43),(280,21),(281,45),(283,45),(284,45),(290,47),(291,48),(293,48),(294,48);",
					"INSERT INTO `titel` VALUES (1,'Doktor','Dr.'),(2,'Privatdozent','PD Dr.'),(3,'Professor','Prof. Dr.');",
					"INSERT INTO `veranstaltungstyp` VALUES (1,'VL','Vorlesung'),(2,'FS','Fachseminar'),(3,'BS','Blockseminar'),(4,'PS','Proseminar'),(5,'TPS','Textproseminar'),(6,'TUT','Tutorium'),(7,'Ü','Übung'),(8,'S','Seminar'),(9,'HS','Hauptseminar'),(10,'OS','Oberseminar'),(11,'EX','Exkursion'),(12,'GS','Graduiertenseminar'),(13,'-','-'),(14,'LG','Lesegruppe'),(15,'FK','Forschungskolloquium'),(16,'VT','Vortrag');",
					"INSERT INTO `language` VALUES (1,'deu','deutsch'),(2,'eng','englisch'),(3,'tlh','klingonisch'),(4,'frz','französisch');",
					"INSERT INTO `pruefungstyp` VALUES (34,'-'),(14,'Bericht'),(3,'Bibliographie'),(39,'Bibliographie (unbenotet)'),(20,'Bürgeruni'),(25,'Doktoranden-/Forschungsseminar'),(2,'Essay'),(32,'Essay (unbenotet)'),(10,'Exposé'),(22,'Geeignet für asylsuchende Gasthörer'),(37,'Kein spezifischer Prüfungstyp'),(1,'Klausur'),(26,'Lektürebericht'),(38,'Lektürebericht (unbenotet)'),(27,'mdl. Prüfung'),(5,'Mündliche Prüfung'),(33,'nach Absprache'),(16,'Nachweis 2h begl. Unterricht'),(17,'Nachweis Schulprakt. Studien'),(15,'Nachweis SPS'),(18,'NF Physik'),(28,'Portfolio'),(12,'Portfolio 1'),(13,'Portfolio 2'),(6,'Protokoll'),(30,'Protokoll (unbenotet)'),(35,'Prüfungsleistung in zugehöriger Veranstaltung'),(8,'Referat'),(31,'Referat (unbenotet)'),(11,'Rezension'),(21,'Schülervorlesung'),(9,'Seminararbeit'),(19,'SG'),(7,'Thesenpapier'),(36,'Thesenpapier (unbenotet)'),(4,'Vortrag');",
					"INSERT INTO `pruefung_zeitraum` VALUES (1,'Erster Zeitraum'),(2,'Zweiter Zeitraum');",
					"INSERT INTO `gebaeude` VALUES (1,'P38','Abstellgeb., Pienner Str. 38a',50.978606182219444,13.580351711659777),(2,'APB','Andreas-Pfitzmann-Bau',51.02547423949613,13.722953826118134);",
					"INSERT INTO `function_right_to_user_role` VALUES (61,1),(62,1),(62,2),(62,3),(62,4),(62,5),(66,1),(67,1),(68,1),(69,1),(70,1),(71,1),(72,1),(73,1),(74,1),(75,1),(78,1),(78,2),(78,3),(78,4),(82,1),(83,1),(85,1),(85,2),(85,3),(85,4),(89,1),(91,1),(93,1),(94,1),(95,1),(95,2),(95,3),(95,4),(99,1),(101,1),(106,1),(107,1),(108,1),(108,2),(108,3),(108,4),(112,1),(113,1),(114,1),(115,1),(116,1),(117,1),(118,1),(119,1),(120,1),(120,2),(120,3),(120,4),(124,1),(131,1),(131,2),(131,3),(131,4),(135,1),(136,1),(137,1),(137,2),(137,3),(137,4),(141,1),(142,1),(143,1),(144,1),(145,1),(146,1),(147,1),(148,1),(148,2),(148,3),(148,4),(152,1),(153,1),(153,2),(153,3),(153,4),(159,1),(159,2),(159,3),(159,4),(170,1),(170,2),(170,3),(170,4),(178,1),(179,1),(179,2),(179,3),(179,4),(183,1),(185,1),(185,2),(185,3),(185,4),(194,1),(196,1),(196,5),(199,1),(200,1),(200,2),(200,3),(200,4),(206,1),(207,1),(207,2),(207,3),(207,4),(211,1),(212,1),(213,1),(214,1),(215,1),(216,1),(217,1),(218,1),(219,1),(220,1),(221,1),(222,1),(222,2),(222,3),(222,4),(226,1),(227,1),(227,2),(227,3),(227,4),(235,1),(241,1),(242,1),(243,1),(243,2),(243,3),(243,4),(247,1),(248,1),(249,1),(250,1),(251,1),(252,1),(253,1),(253,3),(255,1),(256,1),(257,1),(258,1),(259,1),(260,1),(262,1),(263,1),(264,1),(264,2),(264,3),(264,4),(264,5),(268,1),(269,1),(269,2),(269,3),(269,4),(269,5),(281,1),(283,1),(284,1),(286,1),(287,1),(289,1);",
					"SET FOREIGN_KEY_CHECKS=1;"
				);

				foreach ($GLOBALS['fill_database'] as $query) {
					rquery($query);
				}
			}

			print "<br /><span style='color: red; font-size: 25px;'>Bitte l&ouml;sche nun die `new_setup`-Datei!</span><br>";
		} else {
?>
			<form method="post" enctype="multipart/form-data">
				Die Datei muss im SQL-Format sein und die Initialdatensatzfüllungsbefehle enthalten.
				<input type="file" name="sql_file">
				<input type="submit" name="import_datenbank" value="Importieren" />
			</form>


			<h2>&mdash; oder &mdash;</h2>

			<form method="post">
				Benutzername: <input type="text" name="username" />
				Passwort: <input type="password" name="password" />
				<input type="submit" value="Adminkonto speichern" />
			</form>
<?php
		}
	} else {
		exit(0);
	}

	include("footer.php");
?>
