<?php
	$included_files = get_included_files();
	$included_files = array_map('basename', $included_files);

	if(!in_array('functions.php', $included_files)) {
		include_once('../functions.php');
	}

	if(check_page_rights(get_page_id_by_filename(basename(__FILE__)))) { // Wichtig, damit Niemand ohne Anmeldung etwas ändern kann

		function dashboard_get_count ($query) {
			$row = get_single_row_from_query($query);
			if($row && isset($row[0])) {
				return (int)$row[0];
			}
			return 0;
		}

		$current_semester_id = get_current_semester_id();
		$aktuelles_semester = get_this_semester()[0];
		$is_admin = user_is_admin($GLOBALS['logged_in_user_id']);
		$is_verwalter = user_is_verwalter($GLOBALS['logged_in_user_id']);
		$my_dozent_id = isset($GLOBALS['user_dozent_id']) ? $GLOBALS['user_dozent_id'] : null;

		$tasks = array();

		if($my_dozent_id) {
			$scope_dozent_ids = array($my_dozent_id);
			foreach (get_user_per_superdozent($GLOBALS['logged_in_user_id']) as $su) {
				$scope_dozent_ids[] = (int)$su[0];
			}
			$scope_dozent_ids = array_unique($scope_dozent_ids);
			$doz_in = join(',', array_map('intval', $scope_dozent_ids));

			if($aktuelles_semester) {
				$cnt_missing_pn = dashboard_get_count(
					'SELECT COUNT(*) FROM `veranstaltung` `v` '.
					'WHERE `v`.`dozent_id` IN ('.$doz_in.') '.
					'AND `v`.`semester_id` = '.esc($aktuelles_semester).' '.
					'AND NOT EXISTS (SELECT 1 FROM `pruefung` `p` WHERE `p`.`veranstaltung_id` = `v`.`id`)'
				);
				if($cnt_missing_pn > 0) {
					$tasks[] = array(
						'count' => $cnt_missing_pn,
						'text' => 'Veranstaltung(en) im aktuellen Semester ohne Prüfungsnummer',
						'link' => 'admin?page='.get_page_id_by_filename("veranstaltungen.php").'&semester='.$aktuelles_semester.'&dozent_id='.$my_dozent_id.'&missing=pn',
						'severity' => 'warn'
					);
				}

				$cnt_missing_meta = dashboard_get_count(
					'SELECT COUNT(*) FROM `veranstaltung` `v` '.
					'WHERE `v`.`dozent_id` IN ('.$doz_in.') '.
					'AND `v`.`semester_id` = '.esc($aktuelles_semester).' '.
					'AND (NOT EXISTS (SELECT 1 FROM `veranstaltung_metadaten` `vm` WHERE `vm`.`veranstaltung_id` = `v`.`id`) '.
					'OR NOT EXISTS (SELECT 1 FROM `veranstaltung_metadaten` `vm2` WHERE `vm2`.`veranstaltung_id` = `v`.`id` AND `vm2`.`wochentag` IS NOT NULL))'
				);
				if($cnt_missing_meta > 0) {
					$tasks[] = array(
						'count' => $cnt_missing_meta,
						'text' => 'Veranstaltung(en) im aktuellen Semester ohne Wochentag',
						'link' => 'admin?page='.get_page_id_by_filename("veranstaltungen.php").'&semester='.$aktuelles_semester.'&dozent_id='.$my_dozent_id,
						'severity' => 'warn'
					);
				}
			}
		}

		if($is_admin || $is_verwalter) {
			$cnt_datenschutz = dashboard_get_count(
				'SELECT COUNT(*) FROM `users` WHERE `enabled` = "1" AND (`accepted_public_data` = "0" OR `accepted_public_data` IS NULL)'
			);
			if($cnt_datenschutz > 0) {
				$tasks[] = array(
					'count' => $cnt_datenschutz,
					'text' => 'aktive Nutzer ohne bestätigten Datenschutz',
					'link' => 'admin?page='.get_page_id_by_filename("accounts.php"),
					'severity' => 'info'
				);
			}

			$cnt_accounts_ohne_dozent = dashboard_get_count(
				'SELECT COUNT(*) FROM `view_user_to_role` WHERE `enabled` = "1" AND `role_id` != 1 AND (`dozent_id` = 0 OR `dozent_id` IS NULL)'
			);
			if($cnt_accounts_ohne_dozent > 0) {
				$tasks[] = array(
					'count' => $cnt_accounts_ohne_dozent,
					'text' => 'Accounts ohne zugeordneten Dozenten',
					'link' => 'admin?page='.get_page_id_by_filename("accounts.php"),
					'severity' => 'warn'
				);
			}

			$cnt_accounts_ohne_rolle = dashboard_get_count(
				'SELECT COUNT(*) FROM `users` WHERE `enabled` = "1" AND NOT EXISTS (SELECT 1 FROM `user_to_role` WHERE `user_id` = `users`.`id`)'
			);
			if($cnt_accounts_ohne_rolle > 0) {
				$tasks[] = array(
					'count' => $cnt_accounts_ohne_rolle,
					'text' => 'Accounts ohne zugewiesene Rolle',
					'link' => 'admin?page='.get_page_id_by_filename("accounts.php"),
					'severity' => 'warn'
				);
			}
		}

		$recent_changes = array();
		$recent_query = 'SELECT `v`.`id`, `v`.`name`, `v`.`last_change`, `d`.`last_name`, `d`.`first_name`, `s`.`typ`, `s`.`jahr` '.
			'FROM `veranstaltung` `v` '.
			'LEFT JOIN `dozent` `d` ON `d`.`id` = `v`.`dozent_id` '.
			'LEFT JOIN `semester` `s` ON `s`.`id` = `v`.`semester_id` '.
			'WHERE `v`.`last_change` IS NOT NULL ';
		if($my_dozent_id && !$is_admin && isset($scope_dozent_ids)) {
			$recent_query .= 'AND `v`.`dozent_id` IN ('.join(',', array_map('intval', $scope_dozent_ids)).') ';
		}
		$recent_query .= 'ORDER BY `v`.`last_change` DESC LIMIT 8';
		$recent_result = rquery($recent_query);
		if($recent_result) {
			while ($row = mysqli_fetch_row($recent_result)) {
				$recent_changes[] = $row;
			}
		}

		$va_page_id = get_page_id_by_filename("veranstaltung.php");

		$stat_counts = array();
		if($is_admin || $is_verwalter) {
			$stat_counts['veranstaltungen'] = dashboard_get_count('SELECT COUNT(*) FROM `veranstaltung`');
			$stat_counts['dozenten']       = dashboard_get_count('SELECT COUNT(*) FROM `dozent` WHERE `ausgeschieden` = "0"');
			$stat_counts['studiengaenge']  = dashboard_get_count('SELECT COUNT(*) FROM `studiengang`');
			$stat_counts['module']         = dashboard_get_count('SELECT COUNT(*) FROM `modul`');
			if($current_semester_id) {
				$stat_counts['aktuelle_va'] = dashboard_get_count(
					'SELECT COUNT(*) FROM `veranstaltung` WHERE `semester_id` = '.esc($current_semester_id)
				);
				$stat_counts['aktuelle_pruefungen'] = dashboard_get_count(
					'SELECT COUNT(DISTINCT `p`.`id`) FROM `pruefung` `p` JOIN `veranstaltung` `v` ON `v`.`id` = `p`.`veranstaltung_id` WHERE `v`.`semester_id` = '.esc($current_semester_id)
				);
			}
		}

?>
		<div id="welcome">
<?php
			include_once('hinweise.php');

			if(!get_setting("x11_debugging_mode")) {
				print get_seitentext(1);
			}
?>

			<div id="dashboard_header">
				<div class="dashboard_greeting">
					<h1 style="margin: 0 0 4px 0;">Willkommen zurück!</h1>
<?php
					if($aktuelles_semester) {
						$sem = get_semester($aktuelles_semester);
						if($sem) {
							print '<p class="dashboard_subtitle">Aktuelles Semester: <b>'.htmlentities($sem[2].' '.$sem[1]).'</b>';
							if($is_admin && semester_has_sperrvermerk($aktuelles_semester)) {
								print ' &mdash; <span class="class_red"><i>Sperrvermerk aktiv</i></span>';
							}
							print '</p>';
						}
					}
?>
				</div>
			</div>

<?php
			if(count($tasks) > 0) {
?>
				<div class="dashboard_card dashboard_card_tasks">
					<h2 class="dashboard_card_title"><span class="dashboard_card_icon">⚑</span> Offene Aufgaben</h2>
					<ul class="dashboard_task_list">
<?php
						foreach ($tasks as $t) {
							$cls = isset($t['severity']) ? 'dashboard_task_severity_'.$t['severity'] : 'dashboard_task_severity_info';
?>
							<li class="dashboard_task_item <?php print $cls; ?>">
								<a href="<?php print htmlspecialchars($t['link']); ?>">
									<span class="dashboard_task_count"><?php print (int)$t['count']; ?></span>
									<span class="dashboard_task_text"><?php print htmlentities($t['text']); ?></span>
								</a>
							</li>
<?php
						}
?>
					</ul>
				</div>
<?php
			}
?>

			<div id="wrapper">
				<div id="left">
					<div class="dashboard_card">
						<h2 class="dashboard_card_title"><span class="dashboard_card_icon">★</span> Shortcuts</h2>
						<ul class="list_style_none">
<?php
							if(isset($aktuelles_semester) && isset($GLOBALS['user_dozent_id'])) {
?>
								<li><a class="no_link" href="startseite?create_stundenplan=1&semester=<?php print $aktuelles_semester; ?>&dozent[]=<?php print $GLOBALS['user_dozent_id']; ?>"><span class="utf8symbol"><?php print_calendar_icon(); ?></span> Eigenen Stundenplan für das aktuelle Semester anzeigen</a></li>
<?php
							}
							if(check_page_rights(get_page_id_by_filename("neuerdozent.php"))) {
?>
								<li><a class="no_link" href="admin?page=<?php print get_page_id_by_filename("neuerdozent.php"); ?>"><span class='utf8symbol'><?php print_person_add_icon(); ?></span> Dozenten hinzufügen</a></li>
<?php
							}
							if(check_page_rights(get_page_id_by_filename("veranstaltungen.php"))) {
?>
								<li><a class="no_link" href="admin?page=<?php print get_page_id_by_filename("veranstaltungen.php"); ?>"><span class="utf8symbol"><?php print_book_icon(); ?></span> Veranstaltungen bearbeiten</a></li>
<?php
							}
							if(check_page_rights(get_page_id_by_filename("raumplanung.php"))) {
?>
								<li><a class="no_link" href="admin?page=<?php print get_page_id_by_filename("raumplanung.php"); ?>"><span class="utf8symbol"><?php print get_building_icon(); ?></span> Raumplanung</a></li>
<?php
							}
							if(check_page_rights(get_page_id_by_filename("anpassen.php"))) {
?>
								<li><a class="no_link" href="admin?page=<?php print get_page_id_by_filename("anpassen.php"); ?>"><span class="utf8symbol"><?php print_edit_icon(); ?></span> Vorlesungsverzeichnis personalieren</a></li>
<?php
							}
?>
							<li><a class="no_link" href="admin?page=<?php print get_page_id_by_filename("../kontakt.php"); ?>"><span class="utf8symbol"><?php print_email_icon(); ?></span> Kontakt</a></li>
						</ul>
					</div>

<?php
					if($is_admin || $is_verwalter) {
?>
						<div class="dashboard_card dashboard_card_stats">
							<h2 class="dashboard_card_title"><span class="dashboard_card_icon">∑</span> Übersicht</h2>
							<table class="dashboard_stats_table">
<?php
								if(isset($stat_counts['veranstaltungen'])) {
?>
									<tr><td>Veranstaltungen gesamt</td><td class="dashboard_stats_value"><?php print number_format($stat_counts['veranstaltungen'], 0, ',', '.'); ?></td></tr>
<?php
								}
								if(isset($stat_counts['aktuelle_va'])) {
?>
									<tr><td>davon im aktuellen Semester</td><td class="dashboard_stats_value"><?php print number_format($stat_counts['aktuelle_va'], 0, ',', '.'); ?></td></tr>
<?php
								}
								if(isset($stat_counts['aktuelle_pruefungen'])) {
?>
									<tr><td>Prüfungen im aktuellen Semester</td><td class="dashboard_stats_value"><?php print number_format($stat_counts['aktuelle_pruefungen'], 0, ',', '.'); ?></td></tr>
<?php
								}
								if(isset($stat_counts['dozenten'])) {
?>
									<tr><td>Aktive Dozenten</td><td class="dashboard_stats_value"><?php print number_format($stat_counts['dozenten'], 0, ',', '.'); ?></td></tr>
<?php
								}
								if(isset($stat_counts['studiengaenge'])) {
?>
									<tr><td>Studiengänge</td><td class="dashboard_stats_value"><?php print number_format($stat_counts['studiengaenge'], 0, ',', '.'); ?></td></tr>
<?php
								}
								if(isset($stat_counts['module'])) {
?>
									<tr><td>Module</td><td class="dashboard_stats_value"><?php print number_format($stat_counts['module'], 0, ',', '.'); ?></td></tr>
<?php
								}
?>
							</table>
						</div>
<?php
					}
?>
				</div>

				<div id="right">
<?php
					if($is_admin) {
						sperrvermerk_table(array(array($current_semester_id)), "Veranstaltungen nach Sperrvermerk im aktuellen Semester:", 0);
					}
?>

<?php
					if(count($recent_changes) > 0) {
?>
						<div class="dashboard_card dashboard_card_recent">
							<h2 class="dashboard_card_title"><span class="dashboard_card_icon">⏱</span> Letzte Änderungen</h2>
							<ul class="dashboard_recent_list">
<?php
								foreach ($recent_changes as $rc) {
									$ago = '';
									if($rc[2]) {
										$ts = strtotime($rc[2]);
										if($ts) {
											$diff = time() - $ts;
											if($diff < 60)         $ago = 'gerade eben';
											else if($diff < 3600)  $ago = floor($diff/60).' Min.';
											else if($diff < 86400) $ago = floor($diff/3600).' Std.';
											else if($diff < 604800)$ago = floor($diff/86400).' Tage';
											else                   $ago = date('d.m.Y', $ts);
										}
									}
									$doz = trim(($rc[4] ?? '').' '.($rc[3] ?? ''));
									$sem = trim(($rc[5] ?? '').' '.($rc[6] ?? ''));
?>
									<li class="dashboard_recent_item">
<?php
										if($va_page_id) {
?>
											<a href="admin?page=<?php print $va_page_id; ?>&id=<?php print (int)$rc[0]; ?>" title="<?php print htmlspecialchars($rc[1]); ?>">
												<?php print htmlentities(mb_substr($rc[1], 0, 60)); ?><?php print strlen($rc[1]) > 60 ? '…' : ''; ?>
											</a>
<?php
										} else {
?>
											<span><?php print htmlentities(mb_substr($rc[1], 0, 60)); ?><?php print strlen($rc[1]) > 60 ? '…' : ''; ?></span>
<?php
										}
?>
										<span class="dashboard_recent_meta"><?php print htmlentities($doz); ?><?php if($sem) print ' &middot; '.htmlentities($sem); ?> &middot; <span class="dashboard_recent_time"><?php print htmlentities($ago); ?></span></span>
									</li>
<?php
								}
?>
							</ul>
						</div>
<?php
					}
?>
				</div>
			</div>

			<div class="dashboard_card dashboard_card_nav_help">
				<h2 class="dashboard_card_title"><span class="dashboard_card_icon">🧭</span> Schnellzugriff auf alle Menüs</h2>
				<p>Eine Übersicht aller Menüpunkte finden Sie oben in der Navigationsleiste oder jederzeit über das <b>Suchfeld</b> oben rechts (tippen Sie einfach los). Die wichtigsten Menüs sind hier noch einmal aufgelistet:</p>
				<ul class="dashboard_nav_list">
<?php
					$pagedata = create_page_info();
					$page_ids = array();
					foreach ($pagedata as $thispage) {
						$page_ids[] = $thispage[0];
					}
					$page_rights_data = check_page_rights($page_ids, 0);
					foreach ($pagedata as $thispage) {
						if(!in_array($thispage[0], $page_rights_data)) continue;
						if($thispage[4]) continue;
						$linkname = $thispage[2] ? 'page' : 'show_items';
						$desc = trim(strip_tags($thispage[3] ?? ''));
						if($desc === '') $desc = '<i>Keine Beschreibung vorhanden.</i>';
?>
						<li class="dashboard_nav_item">
							<b><a href="admin?<?php print $linkname; ?>=<?php print $thispage[0]; ?>"><?php print htmlentities($thispage[1]); ?></a></b>
							<span class="dashboard_nav_desc"> &mdash; <?php print $desc; ?></span>
						</li>
<?php
					}
?>
				</ul>
			</div>
		</div>
<?php
	}
?>
