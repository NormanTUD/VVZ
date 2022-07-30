<?php
	$included_files = get_included_files();
	$included_files = array_map('basename', $included_files);

	if(!in_array('functions.php', $included_files)) {
		include_once('../functions.php');
	}

	if(!file_exists('/etc/x11test') && check_page_rights(get_page_id_by_filename(basename(__FILE__)))) { // Wichtig, damit Niemand ohne Anmeldung etwas ändern kann
		$rollen = create_rollen_array();
		$dozenten = create_dozenten_array(1);
		$instituten = create_institute_array();
?>
	<div id="accounts">
		<?php print get_seitentext(); ?>
<?php
		include_once('hinweise.php');
?>
		<table>
			<tr>
				<th>Benutzer</th>
				<th>Passwort</th>
				<th>Rolle</th>
				<th>Zugeordneter Dozent</th>
<?php
				if(count($instituten) > 1) {
?>
					<th>Institut</th>
<?php
				}
?>
				<th>Barriere&shy;freier Zu&shy;gang*</th>
				<th>Daten&shy;schutz&shy;frage ak&shy;zep&shy;tiert</th>
				<th>Speichern</th>
				<th>Account deaktivieren**</th>
				<th>Löschen?</th>
			</tr>
<?php
			$query = 'SELECT `v`.`user_id`, `v`.`username`, `v`.`role_id`, `v`.`dozent_id`, `v`.`institut_id`, `v`.`enabled`, `v`.`barrierefrei`, `u`.`accepted_public_data` FROM `view_user_to_role` `v` JOIN `users` `u` ON `u`.`id` = `v`.`user_id` ORDER BY `v`.`enabled` DESC, `v`.`username`';
			$result = rquery($query);

			while ($row = mysqli_fetch_row($result)) {
?>
				<tr>
					<form method="post" enctype="multipart/form-data" action="admin.php?page=<?php print $GLOBALS['this_page_number']; ?>">
						<input type="hidden" name="id" value="<?php print htmlentities($row[0]); ?>" />
						<td><input type="text" name="name" value="<?php print htmlentities($row[1]); ?>" /></td>
						<td><input type="password" name="password" value="" placeholder="passwort" /></td>
						<td><?php create_select($rollen, $row[2], 'role'); ?></td>
						<td><?php create_select($dozenten, $row[3], 'dozent', $row[3] ? 0 : 1); ?></td>
<?php
						if(count($instituten) > 1) {
?>
							<td><?php create_select($instituten, $row[4], 'institut', $row[4] ? 0 : 1); ?></td>
<?php
						} else {
?>
							<input type="hidden" name="institut" value="<?php print $instituten[1][0]; ?>" />
<?php
						}
?>
						<td><input type="checkbox" name="barrierefrei" value="1" <?php print $row[6] == 1 ? 'checked="CHECKED"' : ''; ?>/></td>
						<td><input type="checkbox" name="accepted_public_data" value="1" <?php print $row[7] == 1 ? 'checked="CHECKED"' : ''; ?>/></td>
						<td><input type="submit" value="Speichern" /></td>
<?php
						if($row[5] == "1") {
?>
							<td><input class="red_background" type="submit" name="disable_account" value="Deaktivieren" /></td>
<?php
						} else {
?>

							<td><input class="green_background" type="submit" name="enable_account" value="Bereits deaktiviert. Reaktivieren?" /></td>
<?php
						}
?>
						<td><input type="submit" name="delete" value="Löschen" /></td>
					</form>
				</tr>
<?php
			}
?>
			<tr>
				<form method="post" enctype="multipart/form-data" action="admin.php?page=<?php print $GLOBALS['this_page_number']; ?>">
					<input type="hidden" name="new_user" value="1" />
					<td><input type="text" name="name" value="" /></td>
					<td><input type="password" name="password" value="" placeholder="passwort" /></td>
					<td><?php create_select($rollen, 2, 'role'); ?></td>
					<td><?php create_select($dozenten, '', 'dozent', 0); ?></td>
<?php
					if(count($instituten) > 1) {
?>
						<td><?php create_select($instituten, '', 'institut', 0); ?></td>
<?php
					} else {
?>
						<input type="hidden" name="institut" value="<?php print $instituten[1][0]; ?>" />
<?php
					}
?>
					<td><input type="checkbox" name="barrierefrei" value="1" /></td>
					<td><input type="checkbox" name="accepted_public_data" disabled="disabled" /></td>
					<td><input type="submit" value="Speichern" /></td>
					<td>&mdash;</td>
					<td>&mdash;</td>
				</form>
			</tr>
		</table>
	</div>


	<p>* Setzt standardmäßig die Option `Barrierefrei` für die Raumplanung, gedacht für Nutzer, die <i>immer</i> einen barrierefreien Raum wünschen</p>
	<p>** Viele Daten hängen von den Accounts ab und diese werden gelöscht, wenn der Account gelöscht wird. Daher gibt es die Möglichkeit, den Account zu deaktivieren.
	Dies verhindert das Löschen der abhängigen Datensätze und sorgt dafür, dass der Nutzer sich nicht mehr anmelden kann.</p>
<?php
	}
?>
