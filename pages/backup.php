<?php
	$included_files = get_included_files();
	$included_files = array_map('basename', $included_files);

	if(!in_array('functions.php', $included_files)) {
		include_once('../functions.php');
	}

	if(check_page_rights(get_page_id_by_filename(basename(__FILE__)))) { // Wichtig, damit Niemand ohne Anmeldung etwas ändern kann
?>
		<h2>Backup-Download</h2>
		<?php print get_seitentext(); ?>
<?php
		include_once('hinweise.php');
?>
		<form method="get" action="backup_export.php" />
			<input type="submit" name="export_datenbank" value="Downloaden" />
		</form>

		<h2>Daten aus Backup wiederherstellen</h2>
		Der Prozess des Wiederherstellens kann einige Zeit in Anspruch nehmen. <b>Die Seite während des Wiederherstellens nicht neu laden! <i class="red_text">Alle Daten in der aktuellen Datenbank werden gelöscht! Dann sind nur noch Nutzer, die bereits im Backup angelegt waren, in der Lage, sich anzumelden, undzwar nur mit ihrem Passwort zur Zeit des Backups!</i></b>
		<form method="post" enctype="multipart/form-data">
			<input type="file" name="sql_file">
			<input type="submit" name="import_datenbank" value="Importieren" />
		</form>
<?php
	}
?>
