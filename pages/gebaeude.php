<?php
	$included_files = get_included_files();
	$included_files = array_map('basename', $included_files);

	if(!in_array('functions.php', $included_files)) {
		include_once('../functions.php');
	}

	if(check_page_rights(get_page_id_by_filename(basename(__FILE__)))) { // Wichtig, damit Niemand ohne Anmeldung etwas ändern kann
?>
		<div id="gebaeude">
<?php 
			print get_seitentext();
			include_once('hinweise.php');
			simple_edit(array('name', 'abkuerzung'), 'gebaeude', array('Name', 'Abkürzung', 'Speichern', 'Löschen'), $GLOBALS['this_page_number'], array('id', 'gebaeude_name', 'abkuerzung'), 0);
?>
			<h2>Importiere Gebäude aus einer CSV-Datei:</h2>
			<p>Format:</p>
			<pre>
gebaeude_name, gebaeude_abkuerzung
Gerberbau, GER
Falkenbrunnen, FAL
			</pre>
			<form method="post" enctype="multipart/form-data" action="admin?page=<?php print $GLOBALS['this_page_number']; ?>">
				<textarea class="csv_textarea" name="csv"><?php print htmlentities(get_post("csv") ?? ""); ?></textarea>
				<input type="hidden" name="import_gebaeude_from_csv" value="1">
				<input type="submit" value="Importieren">
			</form>
		</div>

<?php
	}
?>
