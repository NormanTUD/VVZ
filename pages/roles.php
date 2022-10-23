<?php
	$included_files = get_included_files();
	$included_files = array_map('basename', $included_files);

	if(!in_array('functions.php', $included_files)) {
		include_once('../functions.php');
	}

	if(!get_setting("x11_debugging_mode") && check_page_rights(get_page_id_by_filename(basename(__FILE__)))) { // Wichtig, damit Niemand ohne Anmeldung etwas ändern kann
		$rollen = create_rollen_array();
		$seiten = create_seiten_array();
?>
	<div id="roles">
		<?php print get_seitentext(); ?>
<?php
		include_once('hinweise.php');
?>
		<table>
			<tr>
				<th>Rollenname</th>
				<th>Erlaubte Seiten</th>
				<th>Beschreibung</th>
				<th>Speichern</th>
				<th>Löschen</th>
			</tr>
<?php
			foreach (create_rollen_array() as $row) {
?>
				<tr>
					<form method="post" enctype="multipart/form-data" action="admin?page=<?php print htmlentities($GLOBALS['this_page_number']); ?>">
						<input type="hidden" value="<?php print htmlentities($row[0]); ?>" name="id" />
						<td><input type="text" value="<?php print htmlentities($row[1]); ?>" name="neue_rolle" /></td>
						<td>
<?php
							foreach ($seiten as $data) {
?>
								<input name="page[]" type="checkbox" value="<?php print $data[0]; ?>" <?php print role_has_access_to_page($row[0], $data[0]) ? 'checked="checked"' : ''; ?>><?php print $data[1]; ?><br>
<?php
							}
?>
						</td>
						<td><input name="beschreibung" type="text" value="<?php print get_rolle_beschreibung($row[0]); ?>" /></td>
						<td><input type="submit" value="Speichern" /></td>
						<td><input type="submit" name="delete" value="Löschen" /></td>
					</form>
				</tr>
				<tr>
					<td colspan="5"><hr><hr><hr></td>
				</tr>
<?php
			}
?>
			<tr>
				<form method="post" enctype="multipart/form-data" action="admin?page=<?php print htmlentities($GLOBALS['this_page_number']); ?>">
					<td><input type="text" value="" name="neue_rolle" /></td>
					<td>
<?php
						foreach ($seiten as $data) {
?>
							<input name="page[]" type="checkbox" value="<?php print $data[0]; ?>" ><?php print $data[1]; ?><br>
<?php
						}
?>
					</td>
					<td><input name="beschreibung" type="text" value="" /></td>
					<td><input type="submit" value="Speichern" /></td>
					<td>&mdash;</td>
				</form>
			</tr>
		</table>
	</div>
<?php
	}
?>
