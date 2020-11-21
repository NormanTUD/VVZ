<?php
	$included_files = get_included_files();
	$included_files = array_map('basename', $included_files);

	if(!in_array('functions.php', $included_files)) {
		include_once('../functions.php');
	}

	if(check_page_rights(get_page_id_by_filename(basename(__FILE__)))) { // Wichtig, damit Niemand ohne Anmeldung etwas ändern kann
?>
		<div id="institute">
			<?php print get_seitentext(); ?>
<?php
		include_once('hinweise.php');
?>
			<?php simple_edit(array('name', 'start_nr'), 'institut', array('Name', 'Startnummer', 'Speichern', 'Löschen'), $GLOBALS['this_page_number'], array('id', 'faculty_name', 'start_nr'), 0) ?>

			Die Startnummer bezieht sich auf den Wert, mit dem die Aufzählung für die Raumplanung beginnen soll. Das Philosophische Institut z. B. beginnt mit der Nummer 651, also muss hier die Nummer 651 eingegeben werden. Damit werden alle Veranstaltungen in der Raumplanungsliste der philosophischen Fakultät (inklusiv) von 651 aufwärts nummeriert.
		</div>
<?php
	}
?>
