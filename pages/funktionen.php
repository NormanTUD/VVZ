<?php
	$included_files = get_included_files();
	$included_files = array_map('basename', $included_files);

	if(!in_array('functions.php', $included_files)) {
		include_once('../functions.php');
	}

	if(!file_exists('/etc/x11test') && check_page_rights(get_page_id_by_filename(basename(__FILE__)))) { // Wichtig, damit Niemand ohne Anmeldung etwas ändern kann
?>
	<div>
		<?php print get_seitentext(); ?>
<?php
		include_once('hinweise.php');
?>
		<h2>Funktionsnamen</h2>
		<table width="800">
			<tr>
				<th>Funktionsname</th>
				<th>Speichern</th>
				<th>Löschen</th>
			</tr>
<?php
			#		0	1	2			3
			#$query = 'select fr.id as function_right_id, fr.function_name, fr.description, frur.role_id, r.name, p.name as page_name from function_right fr join function_right_to_user_role frur on fr.id = frur.function_right_id join role r on r.id = frur.role_id join function_right_to_page frp on frp.function_right_id = fr.id join page p on frp.page_id = p.id';
			$query = 'select id, function_name from function_right';
			$result = rquery($query);

			$last_name = '';
			while ($row = mysqli_fetch_row($result)) {
?>
				<tr>
					<form method="post" enctype="multipart/form-data" action="admin.php?page=<?php print $GLOBALS['this_page_number']; ?>">
						<input type="hidden" value="<?php print htmlentities($row[0]); ?>" name="id" />
						<td><input type="text" value="<?php print htmlentities($row[1]); ?>" name="funktion_name" /></td>
						<td><input type="submit" value="Speichern" /></td>
						<td><input type="submit" name="delete" value="Löschen" /></td>
					</form>
				</tr>
<?php
			}
?>
			<tr>
				<form method="post" enctype="multipart/form-data" action="admin.php?page=<?php print $GLOBALS['this_page_number']; ?>">
					<input type="hidden" value="1" name="new_function_right" />
					<td><input type="text" value="" placeholder="Name der neuen Funktion" name="funktion_name" /></td>
					<td><input type="submit" value="Speichern" /></td>
					<td>&mdash;</td>
				</form>
			</tr>
		</table>

		<h2>Seitenzuweisung</h2>

		<table>
			<tr>
				<th>Funktionsname</th>
				<th>Seiten</th>
				<th>Speichern</th>
			</tr>
<?php
			$pages = create_page_id_by_name_array();
			$query = 'select frp.function_right_id, group_concat(frp.page_id) as pages, fr.function_name from function_right_to_page frp right join function_right fr on fr.id = frp.function_right_id group by function_right_id';
			$result = rquery($query);

			while ($row = mysqli_fetch_row($result)) {
				$selected_pages = explode(',', $row[1]);
?>
				<form method="post" enctype="multipart/form-data" action="admin.php?page=<?php print $GLOBALS['this_page_number']; ?>">
					<tr>
						<input type="hidden" name="update_right_to_page" value="1" />
						<td><?php print $row[2]; ?></td>
						<td><?php
					foreach ($pages as $this_page_key => $this_page) {
						$selected = 0;
						if(in_array($this_page_key, $selected_pages)) {
							$selected = 1;
						}
						print "<input type='checkbox' name='checkbox_".$row[0]."_".$this_page_key."' ".($selected == 1 ? ' checked="CHECKED" ' : '')."/>".htmle(get_page_name_by_id($this_page_key))."<br>\n";
					}
						?></td>
						<td><input type="submit" value="Speichern" /></td>
					</tr>
				</form>
<?php
			}
?>
		</table>

		<h2>Rollenzuordnung</h2>

		<table>
			<tr>
				<th>Funktionsname</th>
				<th>Rollen</th>
				<th>Speichern</th>
			</tr>
<?php
			$roles = create_rollen_array();
			$query = 'select fra.id, group_concat(fr.role_id) as roles, fra.function_name from function_right_to_user_role fr join function_right fra on fra.id = fr.function_right_id group by function_right_id';
			$result = rquery($query);

			while ($row = mysqli_fetch_row($result)) {
				$selected_roles = explode(',', $row[1]);
?>
				<form method="post" enctype="multipart/form-data" action="admin.php?page=<?php print $GLOBALS['this_page_number']; ?>">
					<tr>
						<input type="hidden" name="update_right_to_user_role" value="1" />
						<td><?php print $row[2]; ?></td>
						<td><?php
					foreach ($roles as $this_role_key => $this_role) {
						$selected = 0;
						if(in_array($this_role_key, $selected_roles)) {
							$selected = 1;
						}
						print "<input type='checkbox' name='checkbox_".$row[0]."_".$this_role_key."' ".($selected == 1 ? ' checked="CHECKED" ' : '')."/>".htmle(get_role_name($this_role_key))."<br>\n";
					}
						?></td>
						<td><input type="submit" value="Speichern" /></td>
					</tr>
				</form>
<?php
			}
?>
		</table>

	</div>
<?php
	}
?>
