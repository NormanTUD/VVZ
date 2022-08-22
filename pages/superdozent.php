<?php
	$included_files = get_included_files();
	$included_files = array_map('basename', $included_files);

	if(!in_array('functions.php', $included_files)) {
		include_once('../functions.php');
	}

	if(check_page_rights(get_page_id_by_filename(basename(__FILE__)))) { // Wichtig, damit Niemand ohne Anmeldung etwas ändern kann

?>
	<div id="accounts">
		<?php print get_seitentext(); ?>
<?php
		include_once('hinweise.php');

		$superdozent_role_id = get_role_id('Superdozent');

		$user = create_user_array(1, $superdozent_role_id);
		if(isset($superdozent_role_id) && count($user)) {
			$dozenten = create_dozenten_array();
			$all_users = create_user_array();
?>
			<table>
				<tr>
					<th>Accountname</th>
					<th>Dozenten, deren Veranstaltungen er bearbeiten kann</th>
					<th>Speichern</th>
				</tr>
<?php
			foreach ($user as $this_user_id => $this_user) {
?>
				<form method="post" enctype="multipart/form-data" action="admin?page=<?php print $GLOBALS['this_page_number']; ?>">
					<input type="hidden" name="dozent_id" value="<?php print htmlentities($this_user[0]); ?>" />
					<tr>
						<td><?php print htmlentities($this_user[1]); ?></td>
						<td>
<?php
							foreach ($dozenten as $all_user_id => $this_all_user) {
?>
								<input type="checkbox" name="editable_users[]" value="<?php print htmlentities($this_all_user[0]); ?>" <?php print user_can_edit_other_users_veranstaltungen($this_user[0], $this_all_user[0]) ? 'checked=CHECKED' : ''; ?>/>
								<?php print htmlentities($this_all_user[1]); ?> <br />
<?php
							}
?>
						</td>
						<td><input type="submit" value="Speichern" /></td>
					</tr>
				</form>
<?php
			}
?>
			</table>
<?php
		} else {
			print "Es gibt aktuell noch keinen User mit Rolle &raquo;Superdozent&laquo;. Bitte legen Sie einen solchen User an und versuchen Sie es erneut!";
		}
?>
	</div>
<?php
	}
?>
