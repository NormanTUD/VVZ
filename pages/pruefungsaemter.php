<?php
	$included_files = get_included_files();
	$included_files = array_map('basename', $included_files);

	if(!in_array('functions.php', $included_files)) {
		include_once('../functions.php');
	}

	if(check_page_rights(get_page_id_by_filename(basename(__FILE__)))) { // Wichtig, damit Niemand ohne Anmeldung etwas ändern kann
		$pruefungsaemter = create_pruefungsamt_array();
		$chosen_pruefungsamt = (get_get('pruefungsamt') ? get_get('pruefungsamt') : null);
		$institute = create_institute_array();
		$chosen_institut = (get_get('institut') ? get_get('institut') : $institute[1][0]);
		$studiengaenge = create_studiengang_array_by_institut_id($chosen_institut);
?>
	<div id="accounts">
		<?php print get_seitentext(); ?>
<?php
		include_once('hinweise.php');
?>
		<?php simple_edit(array('name'), 'pruefungsamt', array('Name', 'Speichern', 'Löschen'), $GLOBALS['this_page_number'], array('id', 'name'), 0); ?>
<?php

		if(count($pruefungsaemter)) {
			$zuordnung = array();
			$query = 'SELECT concat(pruefungsamt_id, "-", studiengang_id) AS `zuordnung` FROM `pruefungsamt_nach_studiengang`';
			$result = rquery($query);

			while ($row = mysqli_fetch_row($result)) {
				$zuordnung[$row[0]] = 1;
			}
?>
			<table>
				<tr>
					<th>Prüfungsamt</th>
					<th>Studiengänge</th>
					<th>Speichern</th>
				</tr>
<?php
			foreach ($pruefungsaemter as $this_pa) {
?>
				<form method="post" enctype="multipart/form-data" action="admin.php?page=<?php print $GLOBALS['this_page_number']; ?>">
					<input type="hidden" name="pruefungsamt_id" value="<?php print htmlentities($this_pa[0]); ?>" />
					<tr>
						<td><?php print htmle($this_pa[1]); ?></td>
						<td class="text_align_left">
<?php
							foreach ($studiengaenge as $this_studiengang) {
								$this_id = "$this_pa[0]-$this_studiengang[0]";
?>
								<input type="checkbox" name="checked_studiengaenge[]" value="<?php print htmle($this_studiengang[0]); ?>" <?php if(array_key_exists($this_id, $zuordnung)) { print 'checked="CHECKED"'; } ?> /><?php print htmle($this_studiengang[1]); ?><br />
<?php
							}
?>
						</td>
						<td>
							<input type="submit" value="Speichern" name="pruefungsamt_nach_studiengang_zuordnung" />
						</td>
					</form>
				</tr>
<?php
			}
?>
			</table>
<?php
		}
	}
?>
