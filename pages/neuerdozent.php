<?php
	$included_files = get_included_files();
	$included_files = array_map('basename', $included_files);

	if(!in_array('functions.php', $included_files)) {
		include_once('../functions.php');
	}

	if(!file_exists('/etc/x11test') && check_page_rights(get_page_id_by_filename(basename(__FILE__)))) { // Wichtig, damit Niemand ohne Anmeldung etwas ändern kann
?>
	<div id="accounts">
		<?php print get_seitentext(); ?>
<?php
		include_once('hinweise.php');

		$titelarray = create_titel_abk_array();
		$instituten = create_institute_array();
?>
		<form method="post" enctype="multipart/form-data" action="admin.php?page=<?php print $GLOBALS['this_page_number']; ?>">
			<input type="hidden" name="dozent_wizard" value="1" />
<?php
			if(count($instituten) == 1) {
?>
				<input type="hidden" name="institut" value="<?php print $instituten[1][0]; ?>" />
<?php
			}
?>
			<table>
				<tr>
					<th>Option</th>
					<th class="neuerdozent_input">Wert</th>
					<th>Speichern</th>
				</tr>
				<tr>
					<td>Titel</td>
					<td><?php create_select($titelarray, null, 'titel_id', 1); ?></td>
					<td rowspan="5"><input type="submit" value="Neuen Dozenten&Account anlegen"></td>
				</tr>
<?php
				if(count($instituten) > 1) {
?>
					<tr>
						<td>Institut</td>
						<td><?php create_select($instituten, '', 'institut', 0); ?></td>
					</tr>
<?php
				}
?>
				<tr>
					<td>Vorname</td>
					<td><input type="text" class="neuerdozent_input" name="first_name" value="" /></td>
				</tr>
				<tr>
					<td>Nachname</td>
					<td><input type="text" class="neuerdozent_input" name="last_name" value="" /></td>
				</tr>
				<tr>
					<td>Passwort</td>
					<td><input type="text" class="neuerdozent_input" name="password" value="<?php print generate_random_string(20); ?>" /></td>
				</tr>
				<tr>
					<td><span class="utf8symbol">&#128104;&#8205;&#129455;</span> Barrierefreie Gebäude bevorzugen?</td>
					<td><input type="checkbox" value="1" name="barrierefrei" /></td>
				</tr>
			</table>
		</form>
<?php
	}
?>
