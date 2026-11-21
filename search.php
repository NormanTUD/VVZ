<?php
	include_once("functions.php");

	header('Content-Type: application/json; charset=utf-8');

	$data = array();
	$term = isset($_GET["term"]) ? trim($_GET["term"]) : '';

	if(strlen($term) < 1) {
		print json_encode(array("error" => "No term defined"));
		exit;
	}

	$term_escaped = addcslashes($term, '%_\\');
	$term_like_safe = '%'.$term_escaped.'%';
	$MAX = 60;

	if(!function_exists('search_push')) {
		function search_push (&$data, $id, $label, $value, $category, $icon = null) {
			$entry = array(
				"id" => $id,
				"label" => '<span class="search_category search_category_'.htmlspecialchars($category, ENT_QUOTES).'">'.htmlspecialchars($category).'</span> '.htmlspecialchars($label),
				"value" => $value,
				"category" => $category,
				"raw" => $label
			);
			if($icon !== null) {
				$entry['icon'] = $icon;
			}
			$data[] = $entry;
		}
	}

	$logged_in_user_id = isset($GLOBALS['logged_in_user_id']) ? $GLOBALS['logged_in_user_id'] : null;
	$is_admin = $logged_in_user_id && function_exists('user_is_admin') && user_is_admin($logged_in_user_id);
	$is_verwalter = $logged_in_user_id && function_exists('user_is_verwalter') && user_is_verwalter($logged_in_user_id);

	$emit = function () use (&$data) {
		print json_encode($data);
		exit;
	};

	// 1) Admin-Seiten (mit Rechteprüfung)
	$pagedata = create_page_info();
	$page_ids = array();
	foreach ($pagedata as $thispage) {
		$page_ids[] = $thispage[0];
	}
	$page_rights_data = check_page_rights($page_ids, 0);
	if(!is_array($page_rights_data)) {
		$page_rights_data = array();
	}
	foreach ($pagedata as $thispage) {
		$pid = $thispage[0];
		$fn = $thispage[2];
		$name = $thispage[1];
		if(!in_array($pid, $page_rights_data)) continue;
		if(preg_match('/'.preg_quote($term_escaped, '/').'/i', $name)) {
			$goto = $fn ? "admin?page=$pid" : "admin?show_items=$pid";
			search_push($data, "goto_page=$goto", $name, $name, 'Seite');
			if(count($data) >= 60) $emit();
		}
	}

	// 2) Veranstaltungen
	$data_veranstaltung = json_decode(search_veranstaltung($term), true);
	if(is_array($data_veranstaltung)) {
		foreach ($data_veranstaltung as $dv) {
			if(!is_array($dv) || !isset($dv['value'])) continue;
			search_push($data, $dv['id'], $dv['value'], $dv['value'], 'Veranstaltung');
			if(count($data) >= 60) $emit();
		}
	}

	// 3) Dozenten — Admins/Verwalter bekommen alle, normale Dozenten nur sich selbst (über ihre Veranstaltungen ohnehin erreichbar)
	if($is_verwalter) {
		$doz_page_id = get_page_id_by_filename("veranstaltungen.php");
		$query = 'SELECT `id`, `last_name`, `first_name` FROM `dozent` WHERE `last_name` LIKE '.esc($term_like_safe).' OR `first_name` LIKE '.esc($term_like_safe).' ORDER BY `last_name`, `first_name` LIMIT 25';
		$result = rquery($query);
		while ($row = mysqli_fetch_row($result)) {
			$dn = trim($row[1].', '.$row[2]);
			$goto = $doz_page_id ? "admin?page=$doz_page_id&dozent_id=".$row[0] : "startseite?dozent=".$row[0];
			search_push($data, "goto_page=$goto", $dn, $dn, 'Dozent');
			if(count($data) >= 60) $emit();
		}
	}

	// 4) Module — Liste der Module (Link auf die Modul-Übersichtsseite, im Admin-Kontext)
	if($is_admin || $is_verwalter) {
		$mod_page_id = get_page_id_by_filename("modul.php");
		if($mod_page_id) {
			$query = 'SELECT `id`, `name` FROM `modul` WHERE `name` LIKE '.esc($term_like_safe).' ORDER BY `name` LIMIT 25';
			$result = rquery($query);
			while ($row = mysqli_fetch_row($result)) {
				search_push($data, "goto_page=admin?page=$mod_page_id", $row[1].'  (Modul-Übersicht)', $row[1], 'Modul');
				if(count($data) >= 60) $emit();
			}
		}
	}

	// 5) Studiengänge (öffentliche Startseite)
	$query = 'SELECT `id`, `name` FROM `studiengang` WHERE `name` LIKE '.esc($term_like_safe).' ORDER BY `name` LIMIT 15';
	$result = rquery($query);
	while ($row = mysqli_fetch_row($result)) {
		search_push($data, "goto_page=startseite?studiengang=".$row[0], $row[1], $row[1], 'Studiengang');
		if(count($data) >= 60) $emit();
	}

	// 6) Institute (Admin-Filter)
	if($is_admin || $is_verwalter) {
		$query = 'SELECT `id`, `name` FROM `institut` WHERE `name` LIKE '.esc($term_like_safe).' ORDER BY `name` LIMIT 15';
		$result = rquery($query);
		while ($row = mysqli_fetch_row($result)) {
			search_push($data, "goto_page=admin?institut=".$row[0], $row[1], $row[1], 'Institut');
			if(count($data) >= 60) $emit();
		}
	}

	// 7) Prüfungsnummern — Sprung zur gefilterten Startseite
	$query = 'SELECT `p`.`id`, `p`.`pruefungsnummer`, `pt`.`name` AS `pt_name`, `m`.`name` AS `modul_name` FROM `pruefungsnummer` `p` LEFT JOIN `pruefungstyp` `pt` ON `pt`.`id` = `p`.`pruefungstyp_id` LEFT JOIN `modul` `m` ON `m`.`id` = `p`.`modul_id` WHERE `p`.`pruefungsnummer` LIKE '.esc($term_like_safe).' ORDER BY `p`.`pruefungsnummer` LIMIT 15';
	$result = rquery($query);
	while ($row = mysqli_fetch_row($result)) {
		$pn_label = $row[1].($row[2] ? ' ('.$row[2].')' : '').($row[3] ? ' — '.$row[3] : '');
		search_push($data, "goto_page=startseite?pruefungsnummer_id=".$row[0], $pn_label, $row[1].' '.$row[3], 'Prüfungsnummer');
		if(count($data) >= 60) $emit();
	}

	// 8) Gebäude — Sprung zur gefilterten Startseite
	$query = 'SELECT `id`, `abkuerzung`, `name` FROM `gebaeude` WHERE `abkuerzung` LIKE '.esc($term_like_safe).' OR `name` LIKE '.esc($term_like_safe).' ORDER BY `abkuerzung` LIMIT 15';
	$result = rquery($query);
	while ($row = mysqli_fetch_row($result)) {
		$label = $row[1].($row[2] && $row[2] != $row[1] ? ' — '.$row[2] : '');
		search_push($data, "goto_page=startseite?gebaeude=".$row[0], $label, $label, 'Gebäude');
		if(count($data) >= 60) $emit();
	}

	$emit();
?>
