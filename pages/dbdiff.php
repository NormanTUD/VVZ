<?php
	$included_files = get_included_files();
	$included_files = array_map('basename', $included_files);

	if(!in_array('functions.php', $included_files)) {
		include_once('../functions.php');
	}

	if(check_page_rights(get_page_id_by_filename(basename(__FILE__)))) { // Wichtig, damit Niemand ohne Anmeldung etwas ändern kann
?>
	<h2>DB-Diff</h2>
	<?php print get_seitentext(); ?>
<?php
	include_once('hinweise.php');
?>
	Gibt die Möglichkeit, eine Liste aller Unterschiede in Datenbankbackup und aktuellem Datenbankzustand anzuzeigen.
	<form method="post" enctype="multipart/form-data">
		<input type="file" name="sql_file">
		<input type="submit" name="datenbankvergleich" value="Datenbankbackup zum Vergleich hochladen" />
	</form>
<?php
		if(isset($GLOBALS['compare_db'])) {
			print $GLOBALS['compare_db'];
		}
	}
?>
