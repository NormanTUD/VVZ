<?php
	$included_files = get_included_files();
	$included_files = array_map('basename', $included_files);

	if(!in_array('functions.php', $included_files)) {
		include_once('../functions.php');
	}

	if(check_page_rights(get_page_id_by_filename(basename(__FILE__)))) { // Wichtig, damit Niemand ohne Anmeldung etwas ändern kann
		$semester = create_semester_array();
?>
		<form class="form_autosubmit" method="post">
			<table>
				<tr>
					<th>Semester</th>
					<th>Sperrvermerk gesetzt?</th>
				</tr>
<?php
				foreach ($semester as $s) {
?>
					<input type="hidden" name='reset_sperrvermerk_semester_id_<?php print $s[0]; ?>' />
					<tr>
						<td><?php print $s[1]; ?></td>
						<td><input type='checkbox' name='sperrvermerk_semester_id_<?php print $s[0]; ?>' <?php print semester_has_sperrvermerk($s[0]) ? "checked" : "" ?> value=1 /></td>
					</tr>
<?php
				}
?>
			</table>
		</form>
<?php
	}

	js(array("autosubmit.js"));
?>
