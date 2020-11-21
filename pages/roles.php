<?php
	$included_files = get_included_files();
	$included_files = array_map('basename', $included_files);

	if(!in_array('functions.php', $included_files)) {
		include_once('../functions.php');
	}

	if(!file_exists('/etc/x11test') && check_page_rights(get_page_id_by_filename(basename(__FILE__)))) { // Wichtig, damit Niemand ohne Anmeldung etwas ändern kann
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
					<form method="post" enctype="multipart/form-data" action="admin.php?page=<?php print htmlentities($GLOBALS['this_page_number']); ?>">
						<input type="hidden" value="<?php print htmlentities($row[0]); ?>" name="id" />
						<td><input type="text" value="<?php print htmlentities($row[1]); ?>" name="neue_rolle" /></td>
						<td>
<?php
							$tquery = 'SELECT `page_id` FROM `role_to_page` WHERE `role_id` = '.esc($row[0]);
							$tresult = rquery($tquery);

							while ($trow = mysqli_fetch_row($tresult)) {
?>
								<select name="page[]">
									<option value="">Diese Seite löschen</option>
<?php
									foreach ($seiten as $data) {
?>
										<option value="<?php print $data[0]; ?>" <?php print $data[0] == $trow[0] ? 'selected' : ''; ?>><?php print htmlentities($data[1]); ?></option>
<?php
									}
?>
								</select>
								<br />
<?php
							}
?>
							<select name="page[]">
								<option value="">Neue Seite hinzufügen</option>
<?php
								foreach ($seiten as $data) {
?>
									<option value="<?php print $data[0]; ?>"><?php print htmlentities($data[1]); ?></option>
<?php
								}
?>
							</select>
						</td>
						<td><input name="beschreibung" type="text" value="<?php print get_rolle_beschreibung($row[0]); ?>" /></td>
						<td><input type="submit" value="Speichern" /></td>
						<td><input type="submit" name="delete" value="Löschen" /></td>
					</form>
				</tr>
<?php
			}
?>
			<tr>
				<form method="post" enctype="multipart/form-data" action="admin.php?page=<?php print htmlentities($GLOBALS['this_page_number']); ?>">
					<td><input type="text" value="" name="neue_rolle" /></td>
					<td>
						<select name="page[]">
<?php
							foreach ($seiten as $data) {
?>
								<option value="<?php print $data[0]; ?>"><?php print htmlentities($data[1]); ?></option>
<?php
							}
?>
						</select>
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
