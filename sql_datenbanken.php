<?php
	if((!isset($GLOBALS['setup_mode']) || !$GLOBALS['setup_mode']) && !(isset($argv) && array_key_exists(1, $argv) && $argv[1] == 'print')) {
		exit();
	}

	$GLOBALS['databases'] = array(
'veranstaltungstyp' => 'create table if not exists veranstaltungstyp (
	id int unsigned auto_increment primary key,
	abkuerzung varchar(5) not null,
	name varchar(20) not null,
	UNIQUE KEY abkuerzung (abkuerzung),
	UNIQUE KEY name (name)
);',

'dozent' => 'create table if not exists dozent (
	id int unsigned auto_increment primary key,
	first_name varchar(100) not null,
	last_name varchar(100) not null,
	UNIQUE KEY first_last_name (first_name, last_name)
);',

'gebaeude' => 'create table if not exists gebaeude (
	id int unsigned auto_increment primary key,
	abkuerzung varchar(10),
	name varchar(100),
	UNIQUE KEY abkuerzung (abkuerzung),
	UNIQUE KEY name (name)
);',

'raum' => 'create table if not exists raum (
	id int unsigned auto_increment primary key,
	gebaeude_id int unsigned not null,
	raumnummer varchar(10) not null,
	UNIQUE KEY gebaeude_raum (gebaeude_id, raumnummer),
	FOREIGN KEY (gebaeude_id) REFERENCES gebaeude(id) ON DELETE CASCADE
);',

'veranstaltung' => 'create table if not exists veranstaltung (
	id int unsigned auto_increment primary key,
	veranstaltungstyp_id int unsigned null,
	name varchar(500) not null,
	dozent_id int unsigned not null,
	gebaeudewunsch_id int unsigned,
	gebaeude_id int unsigned,
	raummeldung date,
	raumwunsch_id int unsigned,
	institut_id int unsigned,
	raum_id int unsigned,
	semester_id int unsigned not null,
	last_change TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	FOREIGN KEY (dozent_id) REFERENCES dozent(id) ON DELETE CASCADE,
	FOREIGN KEY (semester_id) REFERENCES semester(id) ON DELETE CASCADE,
	FOREIGN KEY (gebaeudewunsch_id) REFERENCES gebaeude(id) ON DELETE CASCADE,
	FOREIGN KEY (gebaeude_id) REFERENCES gebaeude(id) ON DELETE CASCADE,
	FOREIGN KEY (raumwunsch_id) REFERENCES raum(id) ON DELETE CASCADE,
	FOREIGN KEY (institut_id) REFERENCES institut(id) ON DELETE CASCADE,
	FOREIGN KEY (veranstaltungstyp_id) REFERENCES veranstaltungstyp(id) ON DELETE CASCADE,
	FOREIGN KEY (raum_id) REFERENCES raum(id) ON DELETE CASCADE
);',

'veranstaltung_metadaten' => 'create table if not exists veranstaltung_metadaten (
	veranstaltung_id int unsigned not null,
	wunsch varchar(500),
	hinweis varchar(500),
	opal_link varchar(500),
	anzahl_hoerer int unsigned,
	erster_termin date,
	wochentag enum ("Mo", "Di", "Mi", "Do", "Fr", "Sa", "So") DEFAULT "Mo",
	stunde enum ("1", "2", "3", "4", "5", "6", "7", "8") DEFAULT "1",
	woche enum("gerade Woche", "ungerade Woche", "jede Woche") default "jede Woche",
	abgabe_pruefungsleistungen date,
	FOREIGN KEY (veranstaltung_id) REFERENCES veranstaltung(id) ON DELETE CASCADE,
	UNIQUE KEY veranstaltung_id (veranstaltung_id)
);',

'institut' => 'create table if not exists institut (
	id int unsigned auto_increment primary key,
	name varchar(100),
	start_nr int unsigned,
	UNIQUE KEY name (name),
	UNIQUE KEY start_nr (start_nr)
);',

'bereich' => 'create table if not exists bereich (
	id int unsigned not null auto_increment,
	name varchar(500),
	primary key (id),
	unique key name (name)
);',

'pruefungsnummer_fach' => 'create table if not exists pruefungsnummer_fach (
	id int unsigned not null auto_increment,
	name varchar(500),
	primary key (id),
	unique key name (name)
);',

'studiengang' => 'create table if not exists studiengang (
	id int unsigned auto_increment primary key,
	name varchar(100),
	institut_id int unsigned not null,
	studienordnung varchar(1000),
	FOREIGN KEY (institut_id) REFERENCES institut(id) ON DELETE CASCADE,
	UNIQUE KEY name (name)
);',

'users' => 'create table if not exists users (
	id int unsigned auto_increment primary key,
	username varchar(100),
	dozent_id int unsigned,
	institut_id int unsigned,
	password_sha256 varchar(256),
	salt varchar(100) not null,
	enabled enum ("0", "1") not null default "1",
	barrierefrei enum ("0", "1") not null default "0",
	accepted_public_data enum ("0", "1") not null default "0",
	UNIQUE KEY name (username),
	UNIQUE KEY dozent_id (dozent_id),
	FOREIGN KEY (dozent_id) REFERENCES dozent(id) ON DELETE CASCADE,
	FOREIGN KEY (institut_id) REFERENCES institut(id) ON DELETE CASCADE
);',

'page' => 'create table if not exists page (
	id int unsigned auto_increment primary key,
	name varchar(50) not null,
	file varchar(50),
	show_in_navigation enum("0", "1") not null default "0",
	parent int(10) unsigned,
	UNIQUE KEY name (name),
	UNIQUE KEY file (file),
	FOREIGN KEY page(parent) REFERENCES page(id) ON DELETE SET NULL
);',

'role' => 'create table if not exists role (
	id int unsigned auto_increment primary key,
	name varchar(100),
	UNIQUE KEY name (name)
);',

'role_to_page' => 'create table if not exists role_to_page (
	role_id int unsigned not null,
	page_id int unsigned not null,
	primary key(role_id, page_id),
	FOREIGN KEY (role_id) REFERENCES role(id) ON DELETE CASCADE,
	FOREIGN KEY (page_id) REFERENCES page(id) ON DELETE CASCADE
);',

'role_to_user' => 'create table if not exists role_to_user (
	role_id int unsigned not null,
	user_id int unsigned not null,
	primary key(role_id, user_id),
	UNIQUE KEY name (user_id),
	FOREIGN KEY (role_id) REFERENCES role(id) ON DELETE CASCADE,
	FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);',

'modul' => 'create table if not exists modul (
	id int unsigned auto_increment primary key,
	name varchar(100),
	studiengang_id int unsigned not null,
	FOREIGN KEY (studiengang_id) REFERENCES studiengang(id) ON DELETE CASCADE
);',

'session_ids' => 'create table if not exists session_ids (
	id int unsigned auto_increment primary key not null,
	session_id varchar(1024) not null,
	user_id int unsigned not null,
	creation_time timestamp not null default current_timestamp,
	FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);',

'pruefungstyp' => 'create table if not exists pruefungstyp (
	id int unsigned auto_increment primary key,
	name varchar(100),
	UNIQUE KEY name (name)
);',

'pruefungsnummer' => 'create table if not exists pruefungsnummer (
	id int unsigned auto_increment primary key,
	pruefungsnummer varchar(100),
	modul_id int unsigned null,
	pruefungstyp_id int unsigned null,
	pruefungsnummer_fach_id int unsigned,
	modulbezeichnung varchar(500) null,
	FOREIGN KEY (pruefungstyp_id) REFERENCES pruefungstyp(id) ON DELETE CASCADE,
	FOREIGN KEY (pruefungsnummer_fach_id) REFERENCES pruefungsnummer_fach(id) ON DELETE CASCADE,
	FOREIGN KEY (modul_id) REFERENCES modul(id) ON DELETE CASCADE
);',

'pruefung' => 'create table if not exists pruefung (
	id int unsigned auto_increment primary key,
	veranstaltung_id int unsigned not null,
	pruefungsnummer_id int unsigned not null,
	date date,
	raum_id int unsigned,
	FOREIGN KEY (pruefungsnummer_id) REFERENCES pruefungsnummer(id) ON DELETE CASCADE,
	FOREIGN KEY (raum_id) REFERENCES raum(id) ON DELETE CASCADE,
	FOREIGN KEY (veranstaltung_id) REFERENCES veranstaltung(id) ON DELETE CASCADE,
	UNIQUE KEY first_last_name (veranstaltung_id, pruefungsnummer_id, raum_id)
);',

'hinweise' => 'create table if not exists hinweise (
	page_id int unsigned primary key not null,
	hinweis text,
	FOREIGN KEY (page_id) REFERENCES page(id) ON DELETE CASCADE
);',

'seitentext' => 'create table if not exists seitentext (
	page_id int unsigned primary key not null,
	text varchar(10000),
	FOREIGN KEY (page_id) REFERENCES page(id) ON DELETE CASCADE
);',

'semester' => 'create table if not exists semester (
	id int unsigned auto_increment primary key,
	jahr int,
	typ enum("Sommersemester", "Wintersemester"),
	UNIQUE KEY jahr_typ (jahr, typ)
);',

'function_rights' => 'create table if not exists function_rights (
	id int unsigned auto_increment primary key,
	name varchar(255),
	role_id int unsigned not null,
	FOREIGN KEY (role_id) REFERENCES role(id) ON DELETE CASCADE,
	UNIQUE KEY name_role_id (name, role_id)
);',

'right_issues_pages' => 'create table if not exists right_issues_pages (
	user_id int unsigned not null,
	page_id int unsigned not null,
	date datetime,
	FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
	FOREIGN KEY (page_id) REFERENCES page(id) ON DELETE CASCADE,
	primary key (user_id, page_id, date)
);',

'right_issues' => 'create table if not exists right_issues (
	function varchar(100),
	user_id int unsigned not null,
	date datetime,
	FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
	primary key (function, user_id, date)
);',

'api_auth_codes' => 'create table if not exists api_auth_codes (
	id int unsigned auto_increment primary key,
	auth_code varchar(100) not null,
	email varchar(200) not null,
	ansprechpartner varchar(100),
	grund varchar(500) not null,
	user_id int unsigned not null,
	last_access datetime,
	UNIQUE KEY email (email),
	FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);',

'api_error_code' => 'create table if not exists api_error_code (
	id int unsigned auto_increment primary key,
	name varchar(100) unique not null
);',

'api_log' => 'create table if not exists api_log (
	id int unsigned auto_increment primary key,
	auth_code_id int unsigned,
	time datetime,
	parameter varchar(500),
	ip BINARY(16) not null,
	api_error_code_id int unsigned,
	FOREIGN KEY (api_error_code_id) REFERENCES api_error_code(id) ON DELETE CASCADE,
	FOREIGN KEY (auth_code_id) REFERENCES api_auth_codes(id) ON DELETE CASCADE
);',

'page_info' => 'create table if not exists page_info (
	page_id int unsigned not null,
	info varchar(1000),
	primary key (page_id),
	FOREIGN KEY (page_id) REFERENCES page(id) ON DELETE CASCADE
);',

'modul_nach_semester' => 'create table if not exists modul_nach_semester (
	modul_id int unsigned not null,
	semester int unsigned,
	primary key (modul_id, semester),
	FOREIGN KEY (modul_id) REFERENCES modul(id) ON DELETE CASCADE
);'
		);

	$GLOBALS['views'] = array(
		'view_user_session_id' => 'create view view_user_session_id as select `s`.`id` AS `session_id_id`,`u`.`id` AS `user_id`,`s`.`session_id` AS `session_id`,`s`.`creation_time` AS `creation_time`,`u`.`username` AS `username`,`u`.`dozent_id` AS `dozent_id`, `u`.`institut_id`, `u`.`enabled`, `u`.`accepted_public_data` as `accepted_public_data` from (`users` `u` left join `session_ids` `s` on((`s`.`user_id` = `u`.`id`)));',
		'view_account_to_role_pages' => 'create or replace view view_account_to_role_pages AS select `p`.`id` AS `page_id`,`p`.`name` AS `name`,`p`.`file` AS `file`,`ru`.`user_id` AS `user_id`, `p`.`show_in_navigation`, `parent` from ((`role_to_user` `ru` join `role_to_page` `rp` on((`rp`.`role_id` = `ru`.`role_id`))) join `page` `p` on((`p`.`id` = `rp`.`page_id`)))',
		'view_user_to_role' => 'CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_user_to_role` AS select `u`.`id` AS `user_id`,`u`.`username` AS `username`,`ru`.`role_id` AS `role_id`,`r`.`name` AS `name`,`u`.`dozent_id` AS `dozent_id`,`u`.`institut_id` AS `institut_id`,`u`.`enabled` AS `enabled`, `barrierefrei` from ((`users` `u` left join `role_to_user` `ru` on((`u`.`id` = `ru`.`user_id`))) join `role` `r` on((`r`.`id` = `ru`.`role_id`)));',
		'view_veranstaltung_autor' => 'create or replace view view_veranstaltung_autor as select v.id veranstaltung_id, v.name veranstaltung_name, v.gebaeudewunsch_id gebaeudewunsch_id, v.gebaeude_id gebaeude_id, v.raummeldung raummeldung, v.raumwunsch_id raumwunsch_id, v.raum_id raum_id, d.id dozent_id, d.first_name, d.last_name from veranstaltung v left join dozent d on v.dozent_id = d.id;',
		'view_veranstaltung_komplett' => 'create view view_veranstaltung_komplett as select `v`.`id` AS `veranstaltung_id`,`vt`.`name` AS `veranstaltung_typ`,`v`.`name` AS `veranstaltung_name`,`v`.`gebaeudewunsch_id` AS `gebaeudewunsch_id`,`v`.`gebaeude_id` AS `gebaeude_id`,`v`.`raummeldung` AS `raummeldung`,`v`.`raumwunsch_id` AS `raumwunsch_id`,`v`.`raum_id` AS `raum_id`,`d`.`id` AS `dozent_id`,`d`.`first_name` AS `first_name`,`d`.`last_name` AS `last_name`,`vt`.`name` AS `name`,`vm`.`wochentag` AS `wochentag`,`vm`.`stunde` AS `stunde`,`vm`.`woche` AS `woche`,`v`.`semester_id` AS `semester_id`, `erster_termin` from (((`veranstaltung` `v` left join `dozent` `d` on((`v`.`dozent_id` = `d`.`id`))) left join `veranstaltungstyp` `vt` on((`vt`.`id` = `v`.`veranstaltungstyp_id`))) left join `veranstaltung_metadaten` `vm` on((`vm`.`veranstaltung_id` = `v`.`id`)))',
		'view_page_and_hinweis' => 'create or replace view `view_page_and_hinweis` AS SELECT `p`.`id`, `p`.`name`, `p`.`show_in_navigation`, `h`.`hinweis` FROM page `p` LEFT JOIN `hinweise` `h` ON `h`.`page_id` = `p`.`id`;',
		'view_modul_studiengang' => 'create or replace view view_modul_studiengang AS SELECT `m`.`id` AS `modul_id`, `m`.`name` AS `modul_name`, `s`.`name` `studiengang_name`, `s`.`id` AS `studiengang_id` FROM `modul` `m` LEFT JOIN `studiengang` `s` ON `m`.`studiengang_id` = `s`.`id`',
		'view_pruefungsnummern_in_modulen' => 'create or replace view view_pruefungsnummern_in_modulen as select `m`.`id` AS `modul_id`,`m`.`name` AS `modul_name`,`m`.`studiengang_id` AS `studiengang_id`,`p`.`pruefungsnummer` AS `pruefungsnummer`,`pt`.`name` AS `pruefungstyp_name`,`s`.`name` AS `studiengang_name`, `pt`.`id` as pruefungstyp_id, `p`.`id` as pruefungsnummer_id, b.name, modulbezeichnung from (((`modul` `m` left join `pruefungsnummer` `p` on((`p`.`modul_id` = `m`.`id`))) left join `pruefungstyp` `pt` on((`pt`.`id` = `p`.`pruefungstyp_id`))) left join `studiengang` `s` on((`m`.`studiengang_id` = `s`.`id`))) left join bereich b on b.id = p.bereich_id',
		'view_pruefungsnummern_in_modulen_not_null' => 'create or replace view view_pruefungsnummern_in_modulen_not_null as select `m`.`id` AS `modul_id`,`m`.`name` AS `modul_name`,`m`.`studiengang_id` AS `studiengang_id`,`p`.`pruefungsnummer` AS `pruefungsnummer`,`pt`.`name` AS `pruefungstyp_name`,`s`.`name` AS `studiengang_name`,`pt`.`id` AS `pruefungstyp_id`,`p`.`id` AS `pruefungsnummer_id`,`b`.`name` AS `bereich_name`,`b`.`id` AS `bereich_id`, `pruefungsnummer_fach_id`, `modulbezeichnung` from ((((`modul` `m` join `pruefungsnummer` `p` on((`p`.`modul_id` = `m`.`id`))) left join `pruefungstyp` `pt` on((`pt`.`id` = `p`.`pruefungstyp_id`))) left join `studiengang` `s` on((`m`.`studiengang_id` = `s`.`id`))) left join `bereich` `b` on((`b`.`id` = `p`.`bereich_id`)));',
		'view_veranstaltung_raumplanung' => 'create or replace view view_veranstaltung_raumplanung as SELECT `v`.`id`, `v`.`name`, `vm`.`wunsch`, `vm`.`anzahl_hoerer`, `vm`.`erster_termin`, `vm`.`wochentag`, `vm`.`stunde`, `vm`.`woche`, `vm`.`abgabe_pruefungsleistungen`, `v`.`gebaeudewunsch_id`, `v`.`raumwunsch_id`, `v`.`gebaeude_id`, `v`.`raum_id`, concat(`d`.`last_name`, ", ", `d`.`first_name`) as `dozent_name`, `vt`.`name` as `veranstaltungstyp_name`, `vt`.`abkuerzung` AS `veranstaltungstyp_abkuerzung`, `v`.`institut_id`, `v`.`raummeldung`, `f`.`name` `institut_name`, `v`.`semester_id` as `semester_id`, `dozent_id` FROM `veranstaltung_metadaten` `vm` RIGHT JOIN `veranstaltung` `v` ON `vm`.`veranstaltung_id` = `v`.`id` JOIN `dozent` `d` ON `d`.`id` = `v`.`dozent_id` JOIN `veranstaltungstyp` `vt` ON `vt`.`id` = `v`.`veranstaltungstyp_id` JOIN `institut` `f` ON `f`.`id` = `v`.`institut_id`',
		'view_api_access_log' => 'create view view_api_access_log as select al.auth_code_id, al.time, al.parameter, al.ip, ae.name from api_log al join api_error_code ae on ae.id = al.api_error_code_id',
		'view_anzahl_pruefungen_pro_dozent' => 'create view view_anzahl_pruefungen_pro_dozent as select count(*) as anzahl_pruefungen, d.first_name, d.last_name, d.id, semester_id from pruefung p join veranstaltung v on v.id = p.veranstaltung_id join dozent d on v.dozent_id = d.id group by d.id, v.semester_id',
		'view_modul_semester' => 'CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_modul_semester` AS select `m`.`name` AS `name`,`m`.`studiengang_id` AS `studiengang_id`,`ms`.`semester` AS `semester`, `m`.`id` AS `modul_id` from (`modul` `m` left join `modul_nach_semester` `ms` on((`m`.`id` = `ms`.`modul_id`)));',
		'view_veranstaltung_nach_modul' => 'create view view_veranstaltung_nach_modul as select m.name as modulname, m.id as modul_id, v.id as veranstaltung_id, v.name, m.studiengang_id as veranstaltung_name from veranstaltung v join pruefung p on v.id = p.veranstaltung_id right join pruefungsnummer pn on pn.id = p.pruefungsnummer_id join modul m on pn.modul_id = m.id where v.name is not null',
		'view_veranstaltung_nach_studiengang' => 'create view view_veranstaltung_nach_studiengang as select veranstaltung_id, modul_id, studiengang_id, s.name as studiengang_name from view_veranstaltung_nach_modul vm left join modul m on m.id = vm.modul_id join studiengang s on m.studiengang_id = s.id',
		'view_pruefungsdaten' => 'create view view_pruefungsdaten as select p.id, p.veranstaltung_id, pn.pruefungsnummer, p.date, p.raum_id, pt.name, modul_id, m.name as modul_name, abgabe_pruefungsleistungen, b.name as bereich, b.id as bereich_id, v.semester_id as semester_id from pruefung p join pruefungsnummer pn on pn.id = p.pruefungsnummer_id join pruefungstyp pt on pt.id = pn.pruefungstyp_id join modul m on pn.modul_id = m.id join veranstaltung_metadaten vm on vm.veranstaltung_id = p.veranstaltung_id left join bereich b on b.id = p.bereich_id left join veranstaltung v on v.id = vm.veranstaltung_id',
		'view_page_and_text' => 'create or replace view `view_page_and_text` AS SELECT `p`.`id`, `p`.`name`, `p`.`show_in_navigation`, `h`.`text` FROM page `p` LEFT JOIN `seitentext` `h` ON `h`.`page_id` = `p`.`id`;',
		'view_log_to_graph' => "create or replace view view_log_to_graph as select unix_timestamp(DATE_FORMAT(time, '%Y-%m-%d %H:59:59')) as t, count(*) as c from api_log group by DATE_FORMAT(time, '%Y-%m-%d %H');"
	);

	if(isset($argv) && array_key_exists(1, $argv) && $argv[1] == 'print') {
		foreach ($GLOBALS['databases'] as $key => $value) {
			print "$value\n";
		}
	}
?>
