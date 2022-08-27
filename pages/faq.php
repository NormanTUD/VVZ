<?php
	$included_files = get_included_files();
	$included_files = array_map('basename', $included_files);

	if(!in_array('functions.php', $included_files)) {
		include_once('../functions.php');
	}

	if(check_page_rights(get_page_id_by_filename(basename(__FILE__)))) { // Wichtig, damit Niemand ohne Anmeldung etwas ändern kann
		$rollen = create_rollen_array();
		$dozenten = create_dozenten_array();
		$instituten = create_institute_array();
?>
	<div id="accounts">
		<?php print get_seitentext(); ?>
<?php
		include_once('hinweise.php');

		$query = 'SELECT `id`, `frage`, `antwort`, `wie_oft_gestellt` FROM `faq` ORDER BY `wie_oft_gestellt` DESC, `frage` ASC';
		$result = rquery($query);

?>
		<table>
			<tr>
				<th>Frage</th>
				<th>Antwort</th>
				<th>Wie oft gestellt</th>
				<th>Speichern</th>
				<th>Löschen</th>
			</tr>
<?php
			while ($row = mysqli_fetch_row($result)) {
?>
				<form method="post" enctype="multipart/form-data" action="admin?page=<?php print $GLOBALS['this_page_number']; ?>">
					<tr>
						<input type="hidden" name="id" value="<?php print htmlentities($row[0] ?? ""); ?>" />
						<input type="hidden" name="faq_update" value="faq_update" />
						<td><input type="text" name="frage" value="<?php print htmlentities($row[1] ?? ""); ?>" /></td>
						<td><textarea class="faq_textarea" name="antwort"><?php print htmlentities($row[2] ?? ""); ?></textarea></td>
						<td><input type="text" name="wie_oft_gestellt" value="<?php print htmlentities($row[3] ?? ""); ?>" /></td>
						<td><input type="submit" value="Speichern" /></td>
						<td><input name="delete" type="submit" value="Löschen" /></td>
					</tr>
				</form>
<?php		
			}
?>
			<form method="post" enctype="multipart/form-data" action="admin?page=<?php print $GLOBALS['this_page_number']; ?>">
				<tr>
					<input type="hidden" name="create_faq" value="faq_update" />
					<td><input type="text" name="frage" value="" /></td>
					<td><textarea class="faq_textarea" name="antwort"></textarea></td>
					<td><input type="text" name="wie_oft_gestellt" value="<?php print htmlentities($row[3] ?? ""); ?>" /></td>
					<td><input type="submit" value="Speichern" /></td>
					<td>&mdash;</td>
				</tr>
			</form>
		</table>
<?php
	}
?>
