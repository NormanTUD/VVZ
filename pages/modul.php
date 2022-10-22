<?php
	$included_files = get_included_files();
	$included_files = array_map('basename', $included_files);

	if(!in_array('functions.php', $included_files)) {
		include_once('../functions.php');
	}

	if(check_page_rights(get_page_id_by_filename(basename(__FILE__)))) { // Wichtig, damit Niemand ohne Anmeldung etwas ändern kann
?>
		<div id="modul">
		<?php print get_seitentext(); ?>
<?php
		include_once('hinweise.php');
			$studiengaenge = create_studiengang_array_by_institut_id_str(0, array(), 1);
			if(count($studiengaenge)) {
				create_table_one_dependency($studiengaenge, array('studiengang_id', 'name', 'beschreibung', 'abkuerzung'), array('Studiengang', 'Name', 'Beschreibung', 'Abkuerzung', 'Speichern', 'Löschen'), 'modul', $GLOBALS['this_page_number'], 'studiengang_id', 'module');
			} else {
				print("Bisher existieren keine Studiengänge. <a href='admin?page=".get_page_id_by_filename("studiengang.php")."'>Bitte fügen Sie hier welche hinzu.</a>");
			}
?>
		</div>
<?php
	}
?>
