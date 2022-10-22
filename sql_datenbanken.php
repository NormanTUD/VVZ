<?php
	$GLOBALS['databases'] = array(
'instance_config' => "create table if not exists `instance_config` (
	`name` varchar(200) not null,
	`dbname` varchar(200) not null,
	`shortlink` varchar(200) not null,
	`installation_date` DATETIME NOT NULL DEFAULT current_timestamp(),
	`kunde_id` int unsigned,
	CONSTRAINT `kunde_fk` FOREIGN KEY (`kunde_id`) REFERENCES `vvz_global`.`kundendaten` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB Default CHARSET=utf8;",

'apache_restarts' => "create table if not exists `apache_restarts` (
  `t` datetime DEFAULT NULL,
  `reason` varchar(200) DEFAULT NULL,
  `stdout` text DEFAULT NULL,
  `stderr` text DEFAULT NULL,
  `exit_code` int DEFAULT NULL,
  `success` enum('0','1') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;",

'veranstaltungstyp' => 'create table if not exists `veranstaltungstyp` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `abkuerzung` varchar(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `abkuerzung` (`abkuerzung`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;',

'dozent' => "create table if not exists `dozent` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `titel_id` int(11) DEFAULT NULL,
  `ausgeschieden` enum('0','1') NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `first_last_name` (`first_name`,`last_name`),
  KEY `titel_id_fk` (`titel_id`),
  CONSTRAINT `titel_id_fk` FOREIGN KEY (`titel_id`) REFERENCES `titel` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;",

'faq' => 'create table if not exists `faq` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `frage` text NOT NULL,
  `antwort` text NOT NULL,
  `wie_oft_gestellt` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;',

'einzelne_termine' => "create table if not exists `einzelne_termine` (
  `veranstaltung_id` int(10) unsigned NOT NULL DEFAULT 0,
  `start` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `end` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `raum_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`veranstaltung_id`,`start`,`end`),
  KEY `raum_id` (`raum_id`),
  CONSTRAINT `einzelne_termine_ibfk_1` FOREIGN KEY (`veranstaltung_id`) REFERENCES `veranstaltung` (`id`) ON DELETE CASCADE,
  CONSTRAINT `einzelne_termine_ibfk_2` FOREIGN KEY (`raum_id`) REFERENCES `raum` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;",

'gebaeude' => 'create table if not exists `gebaeude` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `abkuerzung` varchar(10) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `latitude` double DEFAULT NULL,
  `longitude` double DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `abkuerzung` (`abkuerzung`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;',

'function_right_to_page' => 'create table if not exists `function_right_to_page` (
  `function_right_id` int(10) unsigned NOT NULL,
  `page_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`function_right_id`,`page_id`),
  KEY `page_id` (`page_id`),
  CONSTRAINT `function_right_to_page_ibfk_1` FOREIGN KEY (`function_right_id`) REFERENCES `function_right` (`id`) ON DELETE CASCADE,
  CONSTRAINT `function_right_to_page_ibfk_2` FOREIGN KEY (`page_id`) REFERENCES `page` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;',

'function_right_to_user_role' => 'create table if not exists `function_right_to_user_role` (
  `function_right_id` int(10) unsigned NOT NULL,
  `role_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`function_right_id`,`role_id`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `function_right_to_user_role_ibfk_1` FOREIGN KEY (`function_right_id`) REFERENCES `function_right` (`id`) ON DELETE CASCADE,
  CONSTRAINT `function_right_to_user_role_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `role` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;',

'raum' => 'create table if not exists `raum` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `gebaeude_id` int(10) unsigned NOT NULL,
  `raumnummer` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `gebaeude_raum` (`gebaeude_id`,`raumnummer`),
  CONSTRAINT `raum_ibfk_1` FOREIGN KEY (`gebaeude_id`) REFERENCES `gebaeude` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;',

'raumplanung_relevante_daten_geaendert' => 'create table if not exists `raumplanung_relevante_daten_geaendert` (
  `veranstaltung_id` int(10) unsigned NOT NULL,
  `veranstaltung_aenderung` datetime DEFAULT NULL,
  `raumplanung_aenderung` datetime DEFAULT NULL,
  PRIMARY KEY (`veranstaltung_id`),
  CONSTRAINT `raumplanung_relevante_daten_geaendert_ibfk_1` FOREIGN KEY (`veranstaltung_id`) REFERENCES `veranstaltung` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;',

'veranstaltung' => "create table if not exists `veranstaltung` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `veranstaltungstyp_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(500) NOT NULL,
  `dozent_id` int(10) unsigned NOT NULL,
  `gebaeudewunsch_id` int(10) unsigned DEFAULT NULL,
  `gebaeude_id` int(10) unsigned DEFAULT NULL,
  `raummeldung` date DEFAULT NULL,
  `raumwunsch_id` int(10) unsigned DEFAULT NULL,
  `institut_id` int(10) unsigned DEFAULT NULL,
  `raum_id` int(10) unsigned DEFAULT NULL,
  `semester_id` int(10) unsigned NOT NULL,
  `last_change` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `master_niveau` enum('0','1') DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `dozent_id` (`dozent_id`),
  KEY `semester_id` (`semester_id`),
  KEY `gebaeudewunsch_id` (`gebaeudewunsch_id`),
  KEY `gebaeude_id` (`gebaeude_id`),
  KEY `raumwunsch_id` (`raumwunsch_id`),
  KEY `institut_id` (`institut_id`),
  KEY `veranstaltungstyp_id` (`veranstaltungstyp_id`),
  KEY `raum_id` (`raum_id`),
  CONSTRAINT `veranstaltung_ibfk_1` FOREIGN KEY (`dozent_id`) REFERENCES `dozent` (`id`) ON DELETE CASCADE,
  CONSTRAINT `veranstaltung_ibfk_2` FOREIGN KEY (`semester_id`) REFERENCES `semester` (`id`) ON DELETE CASCADE,
  CONSTRAINT `veranstaltung_ibfk_3` FOREIGN KEY (`gebaeudewunsch_id`) REFERENCES `gebaeude` (`id`) ON DELETE CASCADE,
  CONSTRAINT `veranstaltung_ibfk_4` FOREIGN KEY (`gebaeude_id`) REFERENCES `gebaeude` (`id`) ON DELETE CASCADE,
  CONSTRAINT `veranstaltung_ibfk_5` FOREIGN KEY (`raumwunsch_id`) REFERENCES `raum` (`id`) ON DELETE CASCADE,
  CONSTRAINT `veranstaltung_ibfk_6` FOREIGN KEY (`institut_id`) REFERENCES `institut` (`id`) ON DELETE CASCADE,
  CONSTRAINT `veranstaltung_ibfk_7` FOREIGN KEY (`veranstaltungstyp_id`) REFERENCES `veranstaltungstyp` (`id`) ON DELETE CASCADE,
  CONSTRAINT `veranstaltung_ibfk_8` FOREIGN KEY (`raum_id`) REFERENCES `raum` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;",

'veranstaltung_metadaten' => "create table if not exists `veranstaltung_metadaten` (
  `veranstaltung_id` int(10) unsigned NOT NULL,
  `wunsch` varchar(5000) DEFAULT NULL,
  `hinweis` varchar(5000) DEFAULT NULL,
  `opal_link` varchar(500) DEFAULT NULL,
  `anzahl_hoerer` varchar(100) DEFAULT NULL,
  `erster_termin` date DEFAULT NULL,
  `wochentag` enum('Mo','Di','Mi','Do','Fr','Sa','So','BS') DEFAULT 'Mo',
  `stunde` enum('1','1-2','1-3','1-4','1-5','1-6','1-7','1-8','2','2-3','2-4','2-5','2-6','2-7','2-8','3','3-4','3-5','3-6','3-7','3-8','4','4-5','4-6','4-7','4-8','5','5-6','5-7','5-8','6','6-7','6-8','7','7-8','8','*','Ganztägig') DEFAULT '1',
  `woche` enum('gerade Woche','ungerade Woche','jede Woche','keine Angabe') DEFAULT 'jede Woche',
  `abgabe_pruefungsleistungen` date DEFAULT NULL,
  `related_veranstaltung` int(10) unsigned DEFAULT NULL,
  `fester_bbb_raum` int(11) DEFAULT 0,
  `videolink` varchar(1000) DEFAULT NULL,
  UNIQUE KEY `veranstaltung_id` (`veranstaltung_id`),
  KEY `fk_related_veranstaltung` (`related_veranstaltung`),
  CONSTRAINT `fk_related_veranstaltung` FOREIGN KEY (`related_veranstaltung`) REFERENCES `veranstaltung` (`id`) ON DELETE CASCADE,
  CONSTRAINT `related_veranstaltung_fk` FOREIGN KEY (`related_veranstaltung`) REFERENCES `veranstaltung` (`id`) ON DELETE CASCADE,
  CONSTRAINT `veranstaltung_metadaten_ibfk_1` FOREIGN KEY (`veranstaltung_id`) REFERENCES `veranstaltung` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;",

'veranstaltung_nach_lv_nr' => 'create table if not exists `veranstaltung_nach_lv_nr` (
  `veranstaltung_id` int(11) NOT NULL,
  `lv_nr` int(11) NOT NULL,
  PRIMARY KEY (`veranstaltung_id`,`lv_nr`),
  UNIQUE KEY `veranstaltung_id` (`veranstaltung_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;',

'veranstaltung_to_praesenztyp' => 'create table if not exists `veranstaltung_to_praesenztyp` (
  `veranstaltung_id` int(10) unsigned NOT NULL DEFAULT 0,
  `praesenztyp_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`veranstaltung_id`,`praesenztyp_id`),
  KEY `veranstaltung_id` (`veranstaltung_id`),
  KEY `praesenztyp_id` (`praesenztyp_id`),
  CONSTRAINT `veranstaltung_to_praesenztyp_ibfk_1` FOREIGN KEY (`praesenztyp_id`) REFERENCES `praesenztyp` (`id`) ON DELETE CASCADE,
  CONSTRAINT `veranstaltung_to_praesenztyp_ibfk_2` FOREIGN KEY (`veranstaltung_id`) REFERENCES `veranstaltung` (`id`) ON DELETE CASCADE,
  CONSTRAINT `veranstaltung_to_praesenztyp_ibfk_3` FOREIGN KEY (`veranstaltung_id`) REFERENCES `veranstaltung` (`id`) ON DELETE CASCADE,
  CONSTRAINT `veranstaltung_to_praesenztyp_ibfk_4` FOREIGN KEY (`praesenztyp_id`) REFERENCES `praesenztyp` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;',

'veranstaltung_to_language' => 'create table if not exists `veranstaltung_to_language` (
  `veranstaltung_id` int(10) unsigned NOT NULL DEFAULT 0,
  `language_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`veranstaltung_id`,`language_id`),
  KEY `veranstaltung_id` (`veranstaltung_id`),
  KEY `language_id` (`language_id`),
  CONSTRAINT `language_id_fk` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE CASCADE,
  CONSTRAINT `veranstaltung_id_fk` FOREIGN KEY (`veranstaltung_id`) REFERENCES `veranstaltung` (`id`) ON DELETE CASCADE,
  CONSTRAINT `veranstaltung_to_language_ibfk_1` FOREIGN KEY (`veranstaltung_id`) REFERENCES `veranstaltung` (`id`) ON DELETE CASCADE,
  CONSTRAINT `veranstaltung_to_language_ibfk_2` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;',

'institut' => 'create table if not exists `institut` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `start_nr` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;',

'language' => 'create table if not exists `language` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `abkuerzung` varchar(3) DEFAULT NULL,
  `name` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;',

'bereich' => 'create table if not exists `bereich` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;',

'pruefungsnummer_fach' => 'create table if not exists `pruefungsnummer_fach` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8',

'studiengang' => 'create table if not exists `studiengang` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `institut_id` int(10) unsigned NOT NULL,
  `studienordnung` varchar(1000) DEFAULT NULL,
  `order_key` int(10) default 999999,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `institut_id` (`institut_id`),
  CONSTRAINT `studiengang_ibfk_1` FOREIGN KEY (`institut_id`) REFERENCES `institut` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;',

'superdozent' => 'create table if not exists `superdozent` (
  `user_id` int(10) unsigned NOT NULL DEFAULT 0,
  `dozent_id` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`user_id`,`dozent_id`),
  KEY `dozent_id` (`dozent_id`),
  CONSTRAINT `superdozent_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `superdozent_ibfk_2` FOREIGN KEY (`dozent_id`) REFERENCES `dozent` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;',

'titel' => 'create table if not exists `titel` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `abkuerzung` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `abkuerzung` (`abkuerzung`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;', 

'users' => "create table if not exists `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(100) DEFAULT NULL,
  `dozent_id` int(10) unsigned DEFAULT NULL,
  `institut_id` int(10) unsigned DEFAULT NULL,
  `password_sha256` varchar(256) DEFAULT NULL,
  `salt` varchar(100) NOT NULL,
  `enabled` enum('0','1') NOT NULL DEFAULT '1',
  `barrierefrei` enum('0','1') NOT NULL DEFAULT '0',
  `accepted_public_data` enum('0','1') NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`username`),
  UNIQUE KEY `dozent_id` (`dozent_id`),
  KEY `institut_id` (`institut_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`dozent_id`) REFERENCES `dozent` (`id`) ON DELETE CASCADE,
  CONSTRAINT `users_ibfk_2` FOREIGN KEY (`institut_id`) REFERENCES `institut` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;",

'page' => "create table if not exists `page` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `file` varchar(50) DEFAULT NULL,
  `show_in_navigation` enum('0','1') NOT NULL DEFAULT '0',
  `parent` int(10) unsigned DEFAULT NULL,
  `disable_in_demo` int(1) unsigned not null default 0,
  `show_in_startpage` int(1) unsigned not null default 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `file` (`file`),
  KEY `page` (`parent`),
  CONSTRAINT `page_ibfk_1` FOREIGN KEY (`parent`) REFERENCES `page` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;",

'role' => 'create table if not exists `role` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `beschreibung` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;',

'role_to_page' => 'create table if not exists `role_to_page` (
  `role_id` int(10) unsigned NOT NULL,
  `page_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`page_id`),
  KEY `page_id` (`page_id`),
  CONSTRAINT `role_to_page_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `role` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_to_page_ibfk_2` FOREIGN KEY (`page_id`) REFERENCES `page` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;',

'role_to_user' => 'create table if not exists `role_to_user` (
  `role_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`user_id`),
  UNIQUE KEY `name` (`user_id`),
  CONSTRAINT `role_to_user_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `role` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_to_user_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;',

'modul' => 'create table if not exists `modul` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `studiengang_id` int(10) unsigned NOT NULL,
  `beschreibung` varchar(500) DEFAULT NULL,
  `abkuerzung` varchar(600) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `studiengang_id` (`studiengang_id`),
  CONSTRAINT `modul_ibfk_1` FOREIGN KEY (`studiengang_id`) REFERENCES `studiengang` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;',

'session_ids' => 'create table if not exists `session_ids` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `session_id` varchar(1024) NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `creation_time` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `session_ids_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;',

'pruefungstyp' => 'create table if not exists `pruefungstyp` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;',

  'pruefungsnummer' => "create table if not exists `pruefungsnummer` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pruefungsnummer` varchar(100) DEFAULT NULL,
  `modul_id` int(10) unsigned DEFAULT NULL,
  `pruefungstyp_id` int(10) unsigned DEFAULT NULL,
  `bereich_id` int(10) unsigned DEFAULT NULL,
  `pruefungsnummer_fach_id` int(10) unsigned DEFAULT NULL,
  `modulbezeichnung` varchar(500) DEFAULT NULL,
  `zeitraum_id` int(11) DEFAULT NULL,
  `disabled` enum('0','1') DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `pruefungstyp_id` (`pruefungstyp_id`),
  KEY `bereich_id` (`bereich_id`),
  KEY `modul_id` (`modul_id`),
  KEY `pruefungsnummer_fach_id` (`pruefungsnummer_fach_id`),
  KEY `pruefunsnummer` (`zeitraum_id`),
  CONSTRAINT `pruefungsnummer_ibfk_1` FOREIGN KEY (`pruefungstyp_id`) REFERENCES `pruefungstyp` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pruefungsnummer_ibfk_2` FOREIGN KEY (`bereich_id`) REFERENCES `bereich` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pruefungsnummer_ibfk_3` FOREIGN KEY (`modul_id`) REFERENCES `modul` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pruefungsnummer_ibfk_4` FOREIGN KEY (`pruefungsnummer_fach_id`) REFERENCES `pruefungsnummer_fach` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pruefunsnummer` FOREIGN KEY (`zeitraum_id`) REFERENCES `pruefung_zeitraum` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;",

'pruefung' => 'create table if not exists `pruefung` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `veranstaltung_id` int(10) unsigned NOT NULL,
  `pruefungsnummer_id` int(10) unsigned NOT NULL,
  `date` date DEFAULT NULL,
  `raum_id` int(10) unsigned DEFAULT NULL,
  `last_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `first_last_name` (`veranstaltung_id`,`pruefungsnummer_id`,`raum_id`),
  KEY `pruefungsnummer_id` (`pruefungsnummer_id`),
  KEY `raum_id` (`raum_id`),
  CONSTRAINT `pruefung_ibfk_1` FOREIGN KEY (`pruefungsnummer_id`) REFERENCES `pruefungsnummer` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pruefung_ibfk_2` FOREIGN KEY (`raum_id`) REFERENCES `raum` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pruefung_ibfk_3` FOREIGN KEY (`veranstaltung_id`) REFERENCES `veranstaltung` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;',

'hinweise' => 'create table if not exists `hinweise` (
  `page_id` int(10) unsigned NOT NULL,
  `hinweis` text DEFAULT NULL,
  PRIMARY KEY (`page_id`),
  CONSTRAINT `hinweise_ibfk_1` FOREIGN KEY (`page_id`) REFERENCES `page` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;',

'seitentext' => 'create table if not exists `seitentext` (
  `page_id` int(10) unsigned NOT NULL,
  `text` varchar(10000) DEFAULT NULL,
  PRIMARY KEY (`page_id`),
  CONSTRAINT `seitentext_ibfk_1` FOREIGN KEY (`page_id`) REFERENCES `page` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;',

'semester' => "create table if not exists `semester` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `jahr` int(11) DEFAULT NULL,
  `typ` enum('Sommersemester','Wintersemester') DEFAULT NULL,
  `default` enum('0','1') NOT NULL DEFAULT '0',
  `erste_veranstaltung_default` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `jahr_typ` (`jahr`,`typ`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;",

'pruefungsamt' => 'create table if not exists `pruefungsamt` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;',

'pruefungsamt_nach_studiengang' => 'create table if not exists `pruefungsamt_nach_studiengang` (
  `pruefungsamt_id` int(10) unsigned NOT NULL,
  `studiengang_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`pruefungsamt_id`,`studiengang_id`),
  KEY `studiengang_id` (`studiengang_id`),
  CONSTRAINT `pruefungsamt_nach_studiengang_ibfk_1` FOREIGN KEY (`pruefungsamt_id`) REFERENCES `pruefungsamt` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pruefungsamt_nach_studiengang_ibfk_2` FOREIGN KEY (`studiengang_id`) REFERENCES `studiengang` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;',

'pruefung_zeitraum' => 'create table if not exists `pruefung_zeitraum` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;',

'start_message' => 'create table if not exists `start_message` (
  `institut_id` int(10) unsigned NOT NULL,
  `message` varchar(1000) DEFAULT NULL,
  PRIMARY KEY (`institut_id`),
  CONSTRAINT `institut_id_fk1` FOREIGN KEY (`institut_id`) REFERENCES `institut` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;',

'function_right' => 'create table if not exists `function_right` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `function_name` varchar(150) DEFAULT NULL,
  `description` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `function_name` (`function_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;',

'function_rights' => 'create table if not exists function_rights (
	id int unsigned auto_increment primary key,
	name varchar(255),
	role_id int unsigned not null,
	FOREIGN KEY (role_id) REFERENCES role(id) ON DELETE CASCADE,
	UNIQUE KEY name_role_id (name, role_id)
);',

'right_issues_pages' => "create table if not exists `right_issues_pages` (
  `user_id` int(10) unsigned NOT NULL,
  `page_id` int(10) unsigned NOT NULL,
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`user_id`,`page_id`,`date`),
  KEY `page_id` (`page_id`),
  CONSTRAINT `right_issues_pages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `right_issues_pages_ibfk_2` FOREIGN KEY (`page_id`) REFERENCES `page` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;",

'right_issues' => "create table if not exists `right_issues` (
  `function` varchar(100) NOT NULL DEFAULT '',
  `user_id` int(10) unsigned NOT NULL,
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`function`,`user_id`,`date`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `right_issues_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;",

'api_auth_codes' => 'create table if not exists `api_auth_codes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `auth_code` varchar(100) NOT NULL,
  `email` varchar(200) NOT NULL,
  `ansprechpartner` varchar(100) DEFAULT NULL,
  `grund` varchar(500) NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `last_access` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `api_auth_codes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;',

'api_error_code' => 'create table if not exists `api_error_code` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;',

'api_log' => 'create table if not exists `api_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `auth_code_id` int(10) unsigned DEFAULT NULL,
  `time` datetime DEFAULT NULL,
  `parameter` varchar(500) DEFAULT NULL,
  `ip` binary(16) NOT NULL,
  `api_error_code_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `api_error_code_id` (`api_error_code_id`),
  KEY `auth_code_id` (`auth_code_id`),
  CONSTRAINT `api_log_ibfk_1` FOREIGN KEY (`api_error_code_id`) REFERENCES `api_error_code` (`id`) ON DELETE CASCADE,
  CONSTRAINT `api_log_ibfk_2` FOREIGN KEY (`auth_code_id`) REFERENCES `api_auth_codes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;',

'page_info' => 'create table if not exists `page_info` (
  `page_id` int(10) unsigned NOT NULL,
  `info` varchar(1000) DEFAULT NULL,
  PRIMARY KEY (`page_id`),
  KEY `page_id` (`page_id`),
  CONSTRAINT `page_info_ibfk_1` FOREIGN KEY (`page_id`) REFERENCES `page` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
',

'praesenztyp' => 'create table if not exists `praesenztyp` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;',

'modul_nach_semester' => 'create table if not exists `modul_nach_semester` (
  `modul_id` int(10) unsigned NOT NULL,
  `semester` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`modul_id`,`semester`),
  CONSTRAINT `modul_nach_semester_ibfk_1` FOREIGN KEY (`modul_id`) REFERENCES `modul` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;',

'modulbezeichnung' => 'create table if not exists `modulbezeichnung` (
  `id` int(10) unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;',

'modul_nach_semester_veranstaltungstypen_anzahl' => 'create table if not exists `modul_nach_semester_veranstaltungstypen_anzahl` (
  `modul_id` int(10) unsigned NOT NULL DEFAULT 0,
  `semester` int(10) unsigned NOT NULL DEFAULT 0,
  `veranstaltungstyp_id` int(10) unsigned NOT NULL DEFAULT 0,
  `anzahl` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`modul_id`,`semester`,`veranstaltungstyp_id`),
  KEY `veranstaltungstyp_id` (`veranstaltungstyp_id`),
  CONSTRAINT `modul_nach_semester_veranstaltungstypen_anzahl_ibfk_1` FOREIGN KEY (`modul_id`) REFERENCES `modul` (`id`) ON DELETE CASCADE,
  CONSTRAINT `modul_nach_semester_veranstaltungstypen_anzahl_ibfk_2` FOREIGN KEY (`veranstaltungstyp_id`) REFERENCES `veranstaltungstyp` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;',

'veranstaltung_bezuegetypen' => 'create table if not exists `veranstaltung_bezuege` (
  `id` int(10) unsigned NOT NULL,
  `name` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;',

'veranstaltung_nach_bezuegetypen' => 'create table if not exists `veranstaltung_nach_bezuegetypen` (
  `veranstaltung_id` int(10) unsigned NOT NULL,
  `bezuegetyp_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`veranstaltung_id`, `bezuegetyp_id`),
  CONSTRAINT `vbezkey1` FOREIGN KEY (`veranstaltung_id`) REFERENCES `veranstaltung` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vbezkey2` FOREIGN KEY (`bezuegetyp_id`) REFERENCES `veranstaltung_bezuegetypen` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;',

'modul_nach_semester_metadata' => 'create table if not exists `modul_nach_semester_metadata` (
  `modul_id` int(10) unsigned NOT NULL DEFAULT 0,
  `semester` int(10) unsigned NOT NULL DEFAULT 0,
  `credit_points` int(10) unsigned DEFAULT NULL,
  `anzahl_pruefungsleistungen` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`modul_id`,`semester`),
  CONSTRAINT `modul_id_key` FOREIGN KEY (`modul_id`) REFERENCES `modul` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;',

'customizations' => 'create table if not exists customizations (id int unsigned primary key AUTO_INCREMENT, humanname varchar(100), classname varchar(100), property varchar(100), val varchar(100), default_val varchar(100))',

'initialized_db' => 'create table if not exists initialized_db (id int unsigned primary key AUTO_INCREMENT, name varchar(200) unique)',

"config" => "CREATE TABLE `config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `setting` varchar(100) DEFAULT NULL,
  `default_value` varchar(100) DEFAULT NULL,
  `description` varchar(100) DEFAULT NULL,
 category varchar(50),
 PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4"
		);

	$GLOBALS['views'] = array(
		"view_page_and_text" => "create view if not exists `view_page_and_text` AS select `p`.`id` AS `id`,`p`.`name` AS `name`,`p`.`show_in_navigation` AS `show_in_navigation`,`h`.`text` AS `text` from (`page` `p` left join `seitentext` `h` on(`h`.`page_id` = `p`.`id`));",

		"view_veranstaltung_nach_modul" => "create view if not exists `view_veranstaltung_nach_modul` AS select `m`.`name` AS `modulname`,`m`.`id` AS `modul_id`,`v`.`id` AS `veranstaltung_id`,`v`.`name` AS `name`,`m`.`studiengang_id` AS `veranstaltung_name` from ((`pruefungsnummer` `pn` left join (`veranstaltung` `v` join `pruefung` `p` on(`v`.`id` = `p`.`veranstaltung_id`)) on(`pn`.`id` = `p`.`pruefungsnummer_id`)) join `modul` `m` on(`pn`.`modul_id` = `m`.`id`)) where 1;",

		"view_veranstaltung_nach_studiengang" => "create view if not exists `view_veranstaltung_nach_studiengang` AS select `vm`.`veranstaltung_id` AS `veranstaltung_id`,`vm`.`modul_id` AS `modul_id`,`m`.`studiengang_id` AS `studiengang_id`,`s`.`name` AS `studiengang_name` from ((`view_veranstaltung_nach_modul` `vm` left join `modul` `m` on(`m`.`id` = `vm`.`modul_id`)) join `studiengang` `s` on(`m`.`studiengang_id` = `s`.`id`));",

		"view_account_to_role_pages" => "create view if not exists `view_account_to_role_pages` AS select `p`.`id` AS `page_id`,`p`.`name` AS `name`,`p`.`file` AS `file`,`ru`.`user_id` AS `user_id`,`p`.`show_in_navigation` AS `show_in_navigation`,`p`.`parent` AS `parent` from ((`role_to_user` `ru` join `role_to_page` `rp` on(`rp`.`role_id` = `ru`.`role_id`)) join `page` `p` on(`p`.`id` = `rp`.`page_id`));",

		"view_log_to_graph" => "create view if not exists `view_log_to_graph` AS select unix_timestamp(date_format(`api_log`.`time`,'%Y-%m-%d %H:59:59')) AS `t`,count(0) AS `c` from `api_log` group by date_format(`api_log`.`time`,'%Y-%m-%d %H');",

		"view_pruefungsnummern_in_modulen_not_null" => "create view if not exists `view_pruefungsnummern_in_modulen_not_null` AS select `m`.`id` AS `modul_id`,`m`.`name` AS `modul_name`,`m`.`studiengang_id` AS `studiengang_id`,`p`.`pruefungsnummer` AS `pruefungsnummer`,`pt`.`name` AS `pruefungstyp_name`,`s`.`name` AS `studiengang_name`,`pt`.`id` AS `pruefungstyp_id`,`p`.`id` AS `pruefungsnummer_id`,`b`.`name` AS `bereich_name`,`b`.`id` AS `bereich_id`,`p`.`pruefungsnummer_fach_id` AS `pruefungsnummer_fach_id`,`p`.`modulbezeichnung` AS `modulbezeichnung`,`p`.`zeitraum_id` AS `zeitraum_id`,`p`.`disabled` AS `disabled` from ((((`modul` `m` join `pruefungsnummer` `p` on(`p`.`modul_id` = `m`.`id`)) left join `pruefungstyp` `pt` on(`pt`.`id` = `p`.`pruefungstyp_id`)) left join `studiengang` `s` on(`m`.`studiengang_id` = `s`.`id`)) left join `bereich` `b` on(`b`.`id` = `p`.`bereich_id`));",

		"view_pruefungsdaten" => "create view if not exists `view_pruefungsdaten` AS select `p`.`id` AS `id`,`p`.`veranstaltung_id` AS `veranstaltung_id`,`pn`.`pruefungsnummer` AS `pruefungsnummer`,`p`.`date` AS `date`,`p`.`raum_id` AS `raum_id`,`pt`.`name` AS `name`,`pn`.`modul_id` AS `modul_id`,`m`.`name` AS `modul_name`,`vm`.`abgabe_pruefungsleistungen` AS `abgabe_pruefungsleistungen`,`b`.`name` AS `bereich`,`b`.`id` AS `bereich_id`,`m`.`studiengang_id` AS `studiengang_id`,`d`.`first_name` AS `dozent_first_name`,`d`.`last_name` AS `dozent_last_name`,`d`.`id` AS `dozent_id` from (((((((`pruefung` `p` join `pruefungsnummer` `pn` on(`pn`.`id` = `p`.`pruefungsnummer_id`)) join `pruefungstyp` `pt` on(`pt`.`id` = `pn`.`pruefungstyp_id`)) join `modul` `m` on(`pn`.`modul_id` = `m`.`id`)) join `veranstaltung_metadaten` `vm` on(`vm`.`veranstaltung_id` = `p`.`veranstaltung_id`)) join `bereich` `b` on(`b`.`id` = `pn`.`bereich_id`)) join `veranstaltung` `v` on(`v`.`id` = `vm`.`veranstaltung_id`)) join `dozent` `d` on(`d`.`id` = `v`.`dozent_id`));",

		"view_veranstaltung_autor" => "create view if not exists `view_veranstaltung_autor` AS select `v`.`id` AS `veranstaltung_id`,`v`.`name` AS `veranstaltung_name`,`v`.`gebaeudewunsch_id` AS `gebaeudewunsch_id`,`v`.`gebaeude_id` AS `gebaeude_id`,`v`.`raummeldung` AS `raummeldung`,`v`.`raumwunsch_id` AS `raumwunsch_id`,`v`.`raum_id` AS `raum_id`,`d`.`id` AS `dozent_id`,`d`.`first_name` AS `first_name`,`d`.`last_name` AS `last_name` from (`veranstaltung` `v` left join `dozent` `d` on(`v`.`dozent_id` = `d`.`id`));",

		"view_page_and_hinweis" => "create view if not exists `view_page_and_hinweis` AS select `p`.`id` AS `id`,`p`.`name` AS `name`,`p`.`show_in_navigation` AS `show_in_navigation`,`h`.`hinweis` AS `hinweis` from (`page` `p` left join `hinweise` `h` on(`h`.`page_id` = `p`.`id`));",


		"view_api_access_log" => "create view if not exists `view_api_access_log` AS select `al`.`auth_code_id` AS `auth_code_id`,`al`.`time` AS `time`,`al`.`parameter` AS `parameter`,`al`.`ip` AS `ip`,`ae`.`name` AS `name` from (`api_log` `al` join `api_error_code` `ae` on(`ae`.`id` = `al`.`api_error_code_id`));",

		"view_veranstaltung_raumplanung" => "create view if not exists `view_veranstaltung_raumplanung` AS select `v`.`id` AS `id`,`v`.`name` AS `name`,`vm`.`wunsch` AS `wunsch`,`vm`.`anzahl_hoerer` AS `anzahl_hoerer`,`vm`.`erster_termin` AS `erster_termin`,`vm`.`wochentag` AS `wochentag`,`vm`.`stunde` AS `stunde`,`vm`.`woche` AS `woche`,`vm`.`abgabe_pruefungsleistungen` AS `abgabe_pruefungsleistungen`,`v`.`gebaeudewunsch_id` AS `gebaeudewunsch_id`,`v`.`raumwunsch_id` AS `raumwunsch_id`,`v`.`gebaeude_id` AS `gebaeude_id`,`v`.`raum_id` AS `raum_id`,concat(`d`.`last_name`,', ',`d`.`first_name`) AS `dozent_name`,`vt`.`name` AS `veranstaltungstyp_name`,`vt`.`abkuerzung` AS `veranstaltungstyp_abkuerzung`,`v`.`institut_id` AS `institut_id`,`v`.`raummeldung` AS `raummeldung`,`f`.`name` AS `institut_name`,`v`.`semester_id` AS `semester_id`,`v`.`dozent_id` AS `dozent_id` from ((((`veranstaltung` `v` left join `veranstaltung_metadaten` `vm` on(`vm`.`veranstaltung_id` = `v`.`id`)) join `dozent` `d` on(`d`.`id` = `v`.`dozent_id`)) join `veranstaltungstyp` `vt` on(`vt`.`id` = `v`.`veranstaltungstyp_id`)) join `institut` `f` on(`f`.`id` = `v`.`institut_id`));",

		"view_user_to_role" => "create view if not exists `view_user_to_role` AS select `u`.`id` AS `user_id`,`u`.`username` AS `username`,`ru`.`role_id` AS `role_id`,`r`.`name` AS `name`,`u`.`dozent_id` AS `dozent_id`,`u`.`institut_id` AS `institut_id`,`u`.`enabled` AS `enabled`,`u`.`barrierefrei` AS `barrierefrei` from ((`users` `u` left join `role_to_user` `ru` on(`u`.`id` = `ru`.`user_id`)) join `role` `r` on(`r`.`id` = `ru`.`role_id`));",

		"view_modul_semester" => "create view if not exists `view_modul_semester` AS select `m`.`name` AS `name`,`m`.`studiengang_id` AS `studiengang_id`,`ms`.`semester` AS `semester`,`m`.`id` AS `modul_id` from (`modul` `m` left join `modul_nach_semester` `ms` on(`m`.`id` = `ms`.`modul_id`));",

		"view_user_session_id" => "create view if not exists `view_user_session_id` AS select `s`.`id` AS `session_id_id`,`u`.`id` AS `user_id`,`s`.`session_id` AS `session_id`,`s`.`creation_time` AS `creation_time`,`u`.`username` AS `username`,`u`.`dozent_id` AS `dozent_id`,`u`.`institut_id` AS `institut_id`,`u`.`enabled` AS `enabled`,`u`.`accepted_public_data` AS `accepted_public_data` from (`users` `u` left join `session_ids` `s` on(`s`.`user_id` = `u`.`id`));",

		"view_pruefungsnummern_in_modulen" => "create view if not exists `view_pruefungsnummern_in_modulen` AS select `m`.`id` AS `modul_id`,`m`.`name` AS `modul_name`,`m`.`studiengang_id` AS `studiengang_id`,`p`.`pruefungsnummer` AS `pruefungsnummer`,`pt`.`name` AS `pruefungstyp_name`,`s`.`name` AS `studiengang_name`,`pt`.`id` AS `pruefungstyp_id`,`p`.`id` AS `pruefungsnummer_id`,`b`.`name` AS `name`,`p`.`modulbezeichnung` AS `modulbezeichnung` from ((((`modul` `m` left join `pruefungsnummer` `p` on(`p`.`modul_id` = `m`.`id`)) left join `pruefungstyp` `pt` on(`pt`.`id` = `p`.`pruefungstyp_id`)) left join `studiengang` `s` on(`m`.`studiengang_id` = `s`.`id`)) left join `bereich` `b` on(`b`.`id` = `p`.`bereich_id`));",


		"view_modul_studiengang" => "create view if not exists `view_modul_studiengang` AS select `m`.`id` AS `modul_id`,`m`.`name` AS `modul_name`,`s`.`name` AS `studiengang_name`,`s`.`id` AS `studiengang_id` from (`modul` `m` left join `studiengang` `s` on(`m`.`studiengang_id` = `s`.`id`));",

		"view_anzahl_pruefungen_pro_dozent" => "create view if not exists `view_anzahl_pruefungen_pro_dozent` AS select count(0) AS `anzahl_pruefungen`,`d`.`first_name` AS `first_name`,`d`.`last_name` AS `last_name`,`d`.`id` AS `id`,`v`.`semester_id` AS `semester_id` from ((`pruefung` `p` join `veranstaltung` `v` on(`v`.`id` = `p`.`veranstaltung_id`)) join `dozent` `d` on(`v`.`dozent_id` = `d`.`id`)) group by `d`.`id`,`v`.`semester_id`;",

		"view_veranstaltung_komplett" => "create view if not exists `view_veranstaltung_komplett` AS select `v`.`id` AS `veranstaltung_id`,`vt`.`name` AS `veranstaltung_typ`,`v`.`name` AS `veranstaltung_name`,`v`.`gebaeudewunsch_id` AS `gebaeudewunsch_id`,`v`.`gebaeude_id` AS `gebaeude_id`,`v`.`raummeldung` AS `raummeldung`,`v`.`raumwunsch_id` AS `raumwunsch_id`,`v`.`raum_id` AS `raum_id`,`d`.`id` AS `dozent_id`,`d`.`first_name` AS `first_name`,`d`.`last_name` AS `last_name`,`vt`.`name` AS `name`,`vm`.`wochentag` AS `wochentag`,`vm`.`stunde` AS `stunde`,`vm`.`woche` AS `woche`,`v`.`semester_id` AS `semester_id`,`vm`.`erster_termin` AS `erster_termin`,`vm`.`hinweis` AS `hinweis` from (((`veranstaltung` `v` left join `dozent` `d` on(`v`.`dozent_id` = `d`.`id`)) left join `veranstaltungstyp` `vt` on(`vt`.`id` = `v`.`veranstaltungstyp_id`)) left join `veranstaltung_metadaten` `vm` on(`vm`.`veranstaltung_id` = `v`.`id`));"
	);

/*
	if(isset($argv) && array_key_exists(1, $argv) && $argv[1] == 'print') {
		foreach ($GLOBALS['databases'] as $key => $value) {
			print "$value\n";
		}
	}
*/
?>
