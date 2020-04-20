<?php
	$included_files = get_included_files();
	$included_files = array_map('basename', $included_files);

	if(!in_array('functions.php', $included_files)) {
		include_once('../functions.php');
	}

	if(check_page_rights(get_page_id_by_filename(basename(__FILE__)))) { // Wichtig, damit Niemand ohne Anmeldung etwas ändern kann
?>
		<div id="semester">
		<?php print get_seitentext(); ?>
<?php
		include_once('hinweise.php');
			$query = 'select semester_id from veranstaltung group by semester_id order by semester_id';
			$result = rquery($query);

			if(mysqli_num_rows($result)) {
?>
				<table>
					<tr>
						<th>Semester</th>
						<th>Veranstaltungen löschen?</th>
					</tr>
<?php
					while ($row = mysqli_fetch_row($result)) {
?>
						<tr>
							<td><?php print htmlentities(get_semester($row[0], 1)[1]); ?></td>
							<td>
<?php
								if($row[0] == $GLOBALS['this_semester_id'][0]) {
?>
									<i>Das aktuelle Semester kann nicht gelöscht werden.</i>
<?php
								} else if (is_future_semester(get_semester($row[0]))) {
?>
									<i>Zukünftige Semester können nicht gelöscht werden.</i>
<?php
								} else {
?>
									<form method="post" enctype="multipart/form-data" action="admin.php?page=<?php print $GLOBALS['this_page_number']; ?>">
										<input type="hidden" name="delete_semester" value="1">
										<input type="hidden" name="delete" value="1">
										<input type="hidden" name="id" value="<?php print htmlentities($row[0]); ?>">
										<input type="submit" value="Löschen!" />
									</form>
<?php
								}
?>
							</td>
						</tr>
<?php
					}
			} else {
?>
				<i>Leider sind hier noch keine Daten vorhanden.</i>
<?php
			}
?>
		</div>
<?php
	}
?>
