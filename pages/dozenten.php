<?php
	$included_files = get_included_files();
	$included_files = array_map('basename', $included_files);

	if(!in_array('functions.php', $included_files)) {
		include_once('../functions.php');
	}

	if(check_page_rights(get_page_id_by_filename(basename(__FILE__)))) { // Wichtig, damit Niemand ohne Anmeldung etwas ändern kann
?>
	<div id="dozenten">
		<?php print get_seitentext(); ?>
<?php
		include_once('hinweise.php');
?>
		<?php simple_edit(array('first_name', 'last_name', 'ausgeschieden'), 'dozent', array('Vorname', 'Nachname', 'Ausgeschieden?', 'Speichern', 'Löschen'), $GLOBALS['this_page_number'], array('id', 'dozent_first_name', 'dozent_last_name', 'ausgeschieden'), 0, 1, null, array('last_name', 'first_name', 'ausgeschieden')) ?>
<?php
		$semester = get_get('semester');
		if(!$semester) {
			$semester = $GLOBALS['this_semester_id'][0];
		}
?>
		<form method="get" action="admin.php">
			<input type="hidden" name="page" value="<?php print $GLOBALS['this_page_number']; ?>" />
			Semester: <?php create_select(create_semester_array(), $semester, 'semester'); ?>
			<input type="submit" value="Filtern" />
		</form>
		<br />
<?php
		$query = 'select anzahl_pruefungen, id from view_anzahl_pruefungen_pro_dozent where semester_id = '.esc($semester).' order by last_name, first_name';
		$result = rquery($query);

		if(mysqli_num_rows($result)) {
			$dozenten = create_dozenten_array(1);
			$dozenten_data = array();
			while ($row = mysqli_fetch_row($result)) {
				$dozenten_data[$row[1]] = $row[0];
			}

?>

			<table>
				<tr>
					<th>Dozent</th>
					<th>Anzahl Prüfungen</th>
				</tr>
<?php
				foreach ($dozenten as $id => $name) {
					$tname = $name[1];
?>
					<tr>
						<td><?php print htmlentities($tname); ?></td>
						<td><?php print array_key_exists($id, $dozenten_data) ? $dozenten_data[$id] : '<span class="class_red">!!! 0 !!!</span>'; ?></td>
					</tr>
<?php
				}
?>
			</table>
<?php
		} else {
			print "<i>Es wurden für das ausgewählte Semester noch keine Prüfungen eingetragen.</i><br>\n";
		}
		$query = 'select d.first_name, d.last_name, d.titel_id, d.id from dozent d'; 
		$result = rquery($query);

		if(mysqli_num_rows($result)) {
?>
			<table>
				<tr>
					<th>Titel</th>
					<th>Name</th>
					<th>Speichern</th>
				</tr>
<?php
				$titelarray = create_titel_abk_array();
				while ($row = mysqli_fetch_row($result)) {
?>
					<form method="post" action="admin.php?page=<?php print $GLOBALS['this_page_number']; ?>">
						<tr>
							<input type="hidden" value="1" name="update_dozent_titel" />
							<input type="hidden" value="<?php print htmlentities($row[3]); ?>" name="id" />
							<td><?php create_select($titelarray, $row[2], 'titel_id', 1); ?></td>
							<td><?php print htmlentities($row[0].' '.$row[1]); ?></td>
							<td><input type="submit" value="Speichern" /></td>
						</tr>
					</form>
<?php
				}
?>
			</table>
<?php
		}
?>
	</div>
<?php
	}
?>
