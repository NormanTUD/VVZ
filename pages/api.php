<?php
	$included_files = get_included_files();
	$included_files = array_map('basename', $included_files);

	if(!in_array('functions.php', $included_files)) {
		include_once('../functions.php');
	}

	if(check_page_rights(get_page_id_by_filename(basename(__FILE__)))) { // Wichtig, damit Niemand ohne Anmeldung etwas ändern kann
?>
	<?php print get_seitentext(); ?>
<?php
	include_once('hinweise.php');
?>
	<table>
		<tr>
			<th>Auth-Code</th>
			<th>Ansprechpartner</th>
			<th>Email</th>
			<th>Begründung</th>
			<th>Letzter Zugriff</th>
			<th>Angelegt von</th>
			<th>Log</th>
			<th>Aktion</th>
			<th>Löschen?</th>
		</tr>
<?php
		$query = 'select auth_code, ansprechpartner, email, ansprechpartner, grund, concat(last_access, " (vor ", time_to_sec(timediff(now(), last_access)), "s.)"), user_id from api_auth_codes order by email';
		$result = rquery($query);

		while ($row = mysqli_fetch_row($result)) {
?>
			<form method="post" action="admin?page=<?php print $GLOBALS['this_page_number']; ?>">
				<tr>
					<input type="hidden" name="api_change" value="api_change" />
					<input type="hidden" name="auth_code" value="<?php print htmlentities($row[0]); ?>" />
					<td><?php print htmlentities($row[0]); ?></td>
					<td><input type="text" name="ansprechpartner" value="<?php print htmlentities($row[1]); ?>"</td>
					<td><input type="text" name="email" value="<?php print htmlentities($row[2]); ?>" /></td>
					<td><textarea name="grund"><?php print htmlentities($row[4]); ?></textarea></td>
					<td><?php print $row[5]; ?></td>
					<td><?php print htmlentities(get_user_name($row[6])); ?></td>
					<td><a href="admin?page=<?php print urlencode($GLOBALS['this_page_number']); ?>&id=<?php print urlencode($row[0]); ?>">Log Anzeigen</a></td>
					<td><input type="submit" value="Speichern" /></td>
					<td><input type="submit" name="delete" value="Löschen" /></td>
				</tr>
			</form>
<?php
		}
?>
		<form method="post" action="admin?page=<?php print $GLOBALS['this_page_number']; ?>">
			<tr>
				<input type="hidden" name="api_new" value="api_new" />
				<td><i>Wird automatisch erstellt</i></td>
				<td><input type="text" name="ansprechpartner" /></td>
				<td><input type="text" name="email" /></td>
				<td><textarea name="grund"></textarea></td>
				<td>&mdash;</td>
				<td>&mdash;</td>
				<td>&mdash;</td>
				<td><input type="submit" value="Speichern" /></td>
				<td>&mdash;</td>
			</tr>
		</form>
	</table>
<?php
	if(get_get('id')) {
		$query = 'select auth_code_id, time, parameter, ip, name from view_api_access_log where auth_code_id = '.esc(get_auth_code_id(get_get('id')));
		$result = rquery($query);

		if(mysqli_num_rows($result)) {
?>
				<table>
					<tr>
						<th>Auth-Code</th>
						<th>Zeit</th>
						<th>Parameter</th>
						<th>IP</th>
						<th>Zugriff</th>
					</tr>
<?php
					while ($row = mysqli_fetch_row($result)) {
?>

						<tr>
							<td><?php print htmlentities(get_auth_code_by_id($row[0])); ?></td>
							<td><?php print htmlentities($row[1]); ?></td>
							<td><?php print htmlentities($row[2]); ?></td>
							<td><?php print htmlentities($row[3]); ?></td>
							<td><?php print htmlentities($row[4]); ?></td>
						</tr>
<?php
					}
?>
				</table>
<?php
		} else {
?>
			<i>Für den ausgewählten User stehen noch keine Logdaten zur Verfügung.</i>
<?php
		}
	}
}
?>
