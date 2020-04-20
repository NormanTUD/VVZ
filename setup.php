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
