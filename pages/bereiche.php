<?php
	$included_files = get_included_files();
	$included_files = array_map('basename', $included_files);

	if(!in_array('functions.php', $included_files)) {
		include_once('../functions.php');
	}

	if(check_page_rights(get_page_id_by_filename(basename(__FILE__)))) { // Wichtig, damit Niemand ohne Anmeldung etwas ändern kann
?>
		<div id="bereiche">
			<?php print get_seitentext(); ?>

<?php
			include_once('hinweise.php');
?>
			<?php simple_edit(array('name'), 'bereich', array('Name', 'Speichern', 'Löschen'), $GLOBALS['this_page_number'], array('id', 'name'), 0, array(), null, 0, 0, 0, array(700)); ?>
		</div>

<?php
	}
?>
