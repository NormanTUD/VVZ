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

			<h2>Importiere Bereiche aus einer Liste:</h2>
			<p>Format:</p>
			<pre>
bereich_name
Wissen und Technik (Referat)
Wissen und Technik (Seminararbeit)
Wissen, Natur und Technik (Referat)
			</pre>
			<form method="post" enctype="multipart/form-data" action="admin?page=<?php print $GLOBALS['this_page_number']; ?>">
				<textarea class="csv_textarea" name="csv"><?php print htmlentities(get_post("csv") ?? ""); ?></textarea>
				<input type="hidden" name="import_bereiche_from_csv" value="1">
				<input type="submit" value="Importieren">
			</form>
		</div>

<?php
	}
?>
