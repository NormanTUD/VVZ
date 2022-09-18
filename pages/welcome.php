<?php
	$included_files = get_included_files();
	$included_files = array_map('basename', $included_files);

	if(!in_array('functions.php', $included_files)) {
		include_once('../functions.php');
	}

	if(check_page_rights(get_page_id_by_filename(basename(__FILE__)))) { // Wichtig, damit Niemand ohne Anmeldung etwas ändern kann

?>
		<div id="welcome">
<?php
			include_once('hinweise.php');
			if ($GLOBALS['user_role_id'] == 1) {
?>
				Nur für Administratoren: <a href="admin?page=<?php print get_page_id_by_filename('edit_page_info.php'); ?>">Seiteninformationen bearbeiten.</a>
<?php
			}
			if(!file_exists('/etc/x11test')) {
				print get_seitentext(1);
			}

			$aktuelles_semester = get_this_semester()[0];
?>


			<h2>Shortcuts zu wichtigen Seiten:</h2>
			<ul class="list_style_none">
<?php
				if(isset($aktuelles_semester) && isset($GLOBALS['user_dozent_id'])) {
?>
				
				<li><a href="startseite?create_stundenplan=1&semester=<?php print $aktuelles_semester; ?>&dozent[]=<?php print $GLOBALS['user_dozent_id']; ?>"><span class="utf8symbol"><?php print_calendar_icon(); ?></span> Eigenen Stundenplan für das aktuelle Semester anzeigen</a></li>
<?php
				}
?>
<?php
				if(check_page_rights(get_page_id_by_filename("neuerdozent.php"))) {
?>
					<li><a href="admin?page=<?php print get_page_id_by_filename("neuerdozent.php"); ?>"><span class='utf8symbol'><?php print_person_add_icon(); ?></span> Dozenten hinzufügen</a></li>
<?php
				}

				if(check_page_rights(get_page_id_by_filename("veranstaltungen.php"))) {
?>
					<li><a href="admin?page=<?php print get_page_id_by_filename("veranstaltungen.php"); ?>"><span class="utf8symbol">&#128214;</span> Veranstaltungen bearbeiten</a></li>
<?php
				}


				if(check_page_rights(get_page_id_by_filename("raumplanung.php"))) {
?>
					<li><a href="admin?page=<?php print get_page_id_by_filename("raumplanung.php"); ?>"><span class="utf8symbol"><?php print get_building_icon(); ?></span> Raumplanung</a></li>
<?php
				}

				if(check_page_rights(get_page_id_by_filename("anpassen.php"))) {
?>
					<li><a href="admin?page=<?php print get_page_id_by_filename("anpassen.php"); ?>"><span class="utf8symbol">&#127912;</span> Vorlesungverzeichnis personalieren</a></li>
<?php
				}

?>
				<li><a href="admin?page=<?php print get_page_id_by_filename("../kontakt.php"); ?>"><span class="utf8symbol">&#x1f4e7;</span> Kontakt</a></li>
			</ul>
			<h2>Was versteckt sich hinter der Navigationsleiste?</h2>
			<p>
				<ul class="list_style_closed">
<?php
					$pagedata = create_page_info();

					$page_ids = array();
					foreach ($pagedata as $thispage){
						$page_ids[] = $thispage[0];
					}

					$page_rights_data = check_page_rights($page_ids, 0);
					
					foreach ($pagedata as $thispage){
						# 0	   1	   2		3		    4
						#`name`, `file`, `page_id`, `show_in_navigation`, `parent`
						if(in_array($thispage[0], $page_rights_data)) {
							if(!$thispage[4]) {
								$linkname = 'page';
								if(!$thispage[2]) {
									$linkname = 'show_items';
								}
								print "<li class='margin_10px_0'>&raquo;<b><a href='admin?$linkname=$thispage[0]'>".$thispage[1]."</a></b>&laquo; &mdash; ".$thispage[3];
								$subpagedata = create_page_info_parent($thispage[0], $GLOBALS['user_role_id']);
								if(count($subpagedata)) {
									print "<ul>\n";
									foreach ($subpagedata as $thissubpage){
										if(!$thissubpage[3]) {
											$thissubpage[3] = '<i>Diese Seite wurde noch nicht beschrieben.</i>';
										}
										print "<li class='margin_3px_0'>&raquo;<b><a href='admin?page=$thissubpage[0]'>".$thissubpage[1]."</a></b>&laquo; &mdash; ".$thissubpage[3]."</li>\n";
									}
									print "</ul>\n";
								}
								print "</li>\n";
							}
						}
					}
?>
				</ul>
				In all diesen Menüs können nicht nur neue Dinge eingeführt, sondern auch Vorhandene bearbeitet oder gelöscht werden.
			</p>
		</div>
<?php
	}
?>
