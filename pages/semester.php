<?php
	$included_files = get_included_files();
	$included_files = array_map('basename', $included_files);

	if(!in_array('functions.php', $included_files)) {
		include_once('../functions.php');
	}

	if(check_page_rights(get_page_id_by_filename(basename(__FILE__)))) { // Wichtig, damit Niemand ohne Anmeldung etwas ändern kann
?>
	<div id="accounts">
		<?php print get_seitentext(); ?>
<?php
		include_once('hinweise.php');
?>
		<?php simple_edit(array('jahr', 'typ', 'erste_veranstaltung_default'), 'semester', array('Jahr', 'Typ', 'Erste Veranstaltung', 'Speichern'), $GLOBALS['this_page_number'], array('id', 'jahr', 'typ', 'erste_veranstaltung_default'), 0, 1, null, array('jahr', 'typ'), 1, 1); ?>
<?php
	}
?>
