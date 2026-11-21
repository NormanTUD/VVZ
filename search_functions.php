<?php
	require_once(__DIR__.'/usability_helpers.php');

	if(!function_exists('vvz_search_push')) {
		function vvz_search_push (&$data, $id, $label, $value, $category, $icon = null) {
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

	if(!function_exists('vvz_search_results')) {
		function vvz_search_results ($term, $scope = 'admin', $logged_in_user_id = null) {
			$term = trim((string) $term);

			if($term === '') {
				return array("error" => "No term defined");
			}

			$term_escaped = addcslashes($term, '%_\\');
			$term_like_safe = '%'.$term_escaped.'%';
			$is_public = ($scope === 'public');
			$data = array();
			$limit_reached = function () use (&$data) {
				return count($data) >= 60;
			};

			$is_admin = $logged_in_user_id && function_exists('user_is_admin') && user_is_admin($logged_in_user_id);
			$is_verwalter = $logged_in_user_id && function_exists('user_is_verwalter') && user_is_verwalter($logged_in_user_id);

			if($is_public) {
				$query = 'SELECT `v`.`id`, `v`.`name`, `v`.`semester_id`, `v`.`institut_id`, `s`.`typ`, `s`.`jahr` FROM `veranstaltung` `v` LEFT JOIN `semester` `s` ON `s`.`id` = `v`.`semester_id` WHERE `v`.`name` LIKE '.esc($term_like_safe).' ORDER BY `s`.`jahr` DESC, `s`.`typ` ASC, `v`.`name` ASC LIMIT 25';
				$result = rquery($query);
				while ($row = mysqli_fetch_row($result)) {
					$label = $row[1];
					if($row[4] || $row[5]) {
						$label .= ' ('.trim($row[4].' '.$row[5]).')';
					}
					$goto = vvz_startseite_search_url(array(
						'vltitel' => $row[1],
						'semester' => $row[2],
						'institut' => $row[3]
					));
					vvz_search_push($data, 'goto_page='.$goto, $label, $label, 'Veranstaltung');
					if($limit_reached()) return $data;
				}

				$query = 'SELECT `d`.`id`, `d`.`first_name`, `d`.`last_name`, MIN(`v`.`institut_id`) AS `institut_id` FROM `dozent` `d` LEFT JOIN `veranstaltung` `v` ON `v`.`dozent_id` = `d`.`id` WHERE (`d`.`last_name` LIKE '.esc($term_like_safe).' OR `d`.`first_name` LIKE '.esc($term_like_safe).') AND `d`.`ausgeschieden` = "0" GROUP BY `d`.`id`, `d`.`first_name`, `d`.`last_name` ORDER BY `d`.`last_name`, `d`.`first_name` LIMIT 25';
				$result = rquery($query);
				while ($row = mysqli_fetch_row($result)) {
					$label = trim($row[2].', '.$row[1]);
					$goto = vvz_startseite_search_url(array(
						'dozent' => $row[0],
						'institut' => $row[3]
					));
					vvz_search_push($data, 'goto_page='.$goto, $label, $label, 'Dozent');
					if($limit_reached()) return $data;
				}

				$query = 'SELECT `m`.`id`, `m`.`name`, `s`.`institut_id` FROM `modul` `m` LEFT JOIN `studiengang` `s` ON `s`.`id` = `m`.`studiengang_id` WHERE `m`.`name` LIKE '.esc($term_like_safe).' ORDER BY `m`.`name` LIMIT 15';
				$result = rquery($query);
				while ($row = mysqli_fetch_row($result)) {
					$goto = vvz_startseite_search_url(array(
						'modul' => $row[0],
						'institut' => $row[2]
					));
					vvz_search_push($data, 'goto_page='.$goto, $row[1], $row[1], 'Modul');
					if($limit_reached()) return $data;
				}
			} else {
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
						vvz_search_push($data, "goto_page=$goto", $name, $name, 'Seite');
						if($limit_reached()) return $data;
					}
				}

				$previous_logged_in_user_id = isset($GLOBALS['logged_in_user_id']) ? $GLOBALS['logged_in_user_id'] : null;
				$GLOBALS['logged_in_user_id'] = $logged_in_user_id;
				$data_veranstaltung = json_decode(search_veranstaltung($term), true);
				$GLOBALS['logged_in_user_id'] = $previous_logged_in_user_id;
				if(is_array($data_veranstaltung)) {
					foreach ($data_veranstaltung as $dv) {
						if(!is_array($dv) || !isset($dv['value'])) continue;
						vvz_search_push($data, $dv['id'], $dv['value'], $dv['value'], 'Veranstaltung');
						if($limit_reached()) return $data;
					}
				}

				if($is_verwalter) {
					$doz_page_id = get_page_id_by_filename("veranstaltungen.php");
					$query = 'SELECT `id`, `last_name`, `first_name` FROM `dozent` WHERE `last_name` LIKE '.esc($term_like_safe).' OR `first_name` LIKE '.esc($term_like_safe).' ORDER BY `last_name`, `first_name` LIMIT 25';
					$result = rquery($query);
					while ($row = mysqli_fetch_row($result)) {
						$dn = trim($row[1].', '.$row[2]);
						$goto = $doz_page_id ? "admin?page=$doz_page_id&dozent_id=".$row[0] : "startseite?dozent=".$row[0];
						vvz_search_push($data, "goto_page=$goto", $dn, $dn, 'Dozent');
						if($limit_reached()) return $data;
					}
				}

				if($is_admin || $is_verwalter) {
					$mod_page_id = get_page_id_by_filename("modul.php");
					if($mod_page_id) {
						$query = 'SELECT `id`, `name` FROM `modul` WHERE `name` LIKE '.esc($term_like_safe).' ORDER BY `name` LIMIT 25';
						$result = rquery($query);
						while ($row = mysqli_fetch_row($result)) {
							vvz_search_push($data, "goto_page=admin?page=$mod_page_id", $row[1].'  (Modul-Übersicht)', $row[1], 'Modul');
							if($limit_reached()) return $data;
						}
					}
				}
			}

			$query = 'SELECT `id`, `name`, `institut_id` FROM `studiengang` WHERE `name` LIKE '.esc($term_like_safe).' ORDER BY `name` LIMIT 15';
			$result = rquery($query);
			while ($row = mysqli_fetch_row($result)) {
				if($is_public) {
					$goto = vvz_startseite_search_url(array(
						'studiengang' => $row[0],
						'institut' => $row[2]
					));
				} else {
					$goto = "startseite?studiengang=".$row[0];
				}
				vvz_search_push($data, "goto_page=$goto", $row[1], $row[1], 'Studiengang');
				if($limit_reached()) return $data;
			}

			if(!$is_public && ($is_admin || $is_verwalter)) {
				$query = 'SELECT `id`, `name` FROM `institut` WHERE `name` LIKE '.esc($term_like_safe).' ORDER BY `name` LIMIT 15';
				$result = rquery($query);
				while ($row = mysqli_fetch_row($result)) {
					vvz_search_push($data, "goto_page=admin?institut=".$row[0], $row[1], $row[1], 'Institut');
					if($limit_reached()) return $data;
				}
			}

			$query = 'SELECT `p`.`id`, `p`.`pruefungsnummer`, `pt`.`name` AS `pt_name`, `m`.`name` AS `modul_name`, `s`.`institut_id` FROM `pruefungsnummer` `p` LEFT JOIN `pruefungstyp` `pt` ON `pt`.`id` = `p`.`pruefungstyp_id` LEFT JOIN `modul` `m` ON `m`.`id` = `p`.`modul_id` LEFT JOIN `studiengang` `s` ON `s`.`id` = `m`.`studiengang_id` WHERE `p`.`pruefungsnummer` LIKE '.esc($term_like_safe).' ORDER BY `p`.`pruefungsnummer` LIMIT 15';
			$result = rquery($query);
			while ($row = mysqli_fetch_row($result)) {
				$pn_label = $row[1].($row[2] ? ' ('.$row[2].')' : '').($row[3] ? ' — '.$row[3] : '');
				if($is_public) {
					$goto = vvz_startseite_search_url(array(
						'pruefungsnummer_id' => $row[0],
						'institut' => $row[4]
					));
				} else {
					$goto = "startseite?pruefungsnummer_id=".$row[0];
				}
				vvz_search_push($data, "goto_page=$goto", $pn_label, $row[1].' '.$row[3], 'Prüfungsnummer');
				if($limit_reached()) return $data;
			}

			$query = 'SELECT `id`, `abkuerzung`, `name` FROM `gebaeude` WHERE `abkuerzung` LIKE '.esc($term_like_safe).' OR `name` LIKE '.esc($term_like_safe).' ORDER BY `abkuerzung` LIMIT 15';
			$result = rquery($query);
			while ($row = mysqli_fetch_row($result)) {
				$label = $row[1].($row[2] && $row[2] != $row[1] ? ' — '.$row[2] : '');
				if($is_public) {
					$goto = vvz_startseite_search_url(array('gebaeude' => $row[0]));
				} else {
					$goto = "startseite?gebaeude=".$row[0];
				}
				vvz_search_push($data, "goto_page=$goto", $label, $label, 'Gebäude');
				if($limit_reached()) return $data;
			}

			return $data;
		}
	}
?>
