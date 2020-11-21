<?php
	$included_files = get_included_files();
	$included_files = array_map('basename', $included_files);

	if(!in_array('functions.php', $included_files)) {
		include_once('../functions.php');
	}

	if(!file_exists('/etc/x11test') && check_page_rights(get_page_id_by_filename(basename(__FILE__)))) { // Wichtig, damit Niemand ohne Anmeldung etwas ändern kann
		$barrierefrei = 0;
?>
	<div id="accounts">
		<?php print get_seitentext(); ?>
<?php
		include_once('hinweise.php');
?>
		<form method="post" enctype="multipart/form-data" action="admin.php?page=<?php print $GLOBALS['this_page_number']; ?>">
			<table>
				<tr>
					<th>Benutzer</th>
					<th>Passwort</th>
					<th>Passwort erneut eingeben</th>
					<th>Speichern</th>
				</tr>
<?php
				$query = 'SELECT `user_id`, `username`, `role_id`, `dozent_id`, `institut_id`, `barrierefrei` FROM `view_user_to_role` WHERE `user_id` = '.esc($GLOBALS['logged_in_user_id']);
				$result = rquery($query);

				while ($row = mysqli_fetch_row($result)) {
					$barrierefrei = $row[5];
?>
					<tr>
						<input type="hidden" name="change_own_data" value="1" />
						<td><?php print htmlentities($row[1]); ?></td>
						<td><input type="password" name="password" value="" /></td>
						<td><input type="password" name="password_repeat" value="" /></td>
						<td><input type="submit" value="Speichern" /></td>
					</tr>
<?php
				}
?>
			</table>
		</form>
		<form method="post" enctype="multipart/form-data" action="admin.php?page=<?php print $GLOBALS['this_page_number']; ?>">
			<table>
				<tr>
					<th>Barrierefreie Gebäude bevorzugen?</th>
					<th>Speichern</th>
				</tr>
				<tr>
					<input type="hidden" name="update_barrierefrei" value="1" />
					<td><input type="checkbox" name="barrierefrei" value="1" <?php print $barrierefrei == '1' ? 'checked="CHECKED"' : ''; ?>/></td>
					<td><input type="submit" value="Speichern" /></td>
				</tr>
			</table>
		</form>
	</div>
<?php
	}
?>
