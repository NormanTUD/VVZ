<?php
	$included_files = get_included_files();
	$included_files = array_map('basename', $included_files);

	if(!in_array('functions.php', $included_files)) {
		include_once('../functions.php');
	}

	if(!file_exists('/etc/x11test') && check_page_rights(get_page_id_by_filename(basename(__FILE__)))) { // Wichtig, damit Niemand ohne Anmeldung etwas ändern kann
?>
		<div id="bereiche">
			<?php print get_seitentext(); ?>
<?php
			include_once('hinweise.php');
			$rollen = create_rollen_array();
			$query = 'SELECT `id`, `name`, `file`, `show_in_navigation`, `parent` FROM `'.$GLOBALS['dbname'].'`.`page`';
			$result = rquery($query);
			$eltern = create_page_parent_array();

			if(mysqli_num_rows($result)) {
?>
				<table>
					<tr>
						<th>Titel</th>
						<th>Datei</th>
						<th>In Navigation anzeigen?</th>
						<th>Vater</th>
						<th>Rollen, die darauf zugreifen können</th>
						<th>Beschreibung</th>
						<th>Hinweis</th>
						<th>Speichern</th>
						<th>Löschen</th>
					</tr>
<?php
					while ($row = mysqli_fetch_row($result)) {
						$id = $row[0];
						$name = $row[1];
						$file = $row[2];
						$show_in_navigation = $row[3];
						$elter = $row[4];

						$checked = '';
						if($show_in_navigation == 1) {
							$checked = ' checked="CHECKED" ';
						}

						$this_page_rollen = get_roles_for_page($id);
						$beschreibung = get_page_info_by_id($id);
						if(is_array($beschreibung) && count($beschreibung) == 0) {
							$beschreibung = '';
						}
						$hinweis = get_hinweis_for_page($id);
?>
						<form method="post" action="admin?page=<?php print $GLOBALS['this_page_number']; ?>">
							<tr>
								<input type="hidden" name="updatepage" value="1" />
								<input type="hidden" name="id" value="<?php print htmle($id); ?>" />
								<td><input type="text" name="titel" value="<?php print htmle($name); ?>" /></td>
<?php
								if($file) {
?>
									<td><input type="text" name="datei" value="<?php print htmle($file); ?>" /></td>
<?php
								} else {
?>
									<td><i>Keine Datei, da Oberkategorie</i></td>
<?php
								}
?>
								<td><input type="checkbox" name="show_in_navigation" value="1" <?php print $checked; ?> /></td>
								<td><?php create_select($eltern, $elter, 'eltern', 1); ?></td>
								<td><?php
									foreach ($rollen as $trow) {
										$rolle_id = $trow[0];
										$rolle_name = $trow[1];
										$tchecked = '';
										if(in_array($rolle_id, $this_page_rollen)) {
											$tchecked = ' checked="CHECKED" ';
										}

										print "<input $tchecked type='checkbox' value='$rolle_id' name='role_to_page[]' />".htmle($rolle_name)."<br />";
									}
								?></td>
								<td><textarea name="beschreibung"><?php print htmlentities($beschreibung); ?></textarea></td>
								<td><textarea name="hinweis"><?php print htmlentities($hinweis); ?></textarea></td>
								<td><input type="submit" value="Speichern" /></td>
								<td><input type="submit" name="delete" value="Löschen" /></td>
							</tr>
						</form>
<?php
				}
?>
					<form method="post" action="admin?page=<?php print $GLOBALS['this_page_number']; ?>">
						<tr>
							<input type="hidden" name="newpage" value="1" />
							<input type="hidden" name="id" value="" />
							<td><input type="text" name="titel" value="" placeholder="Neuer Seitenname" /></td>
							<td><input type="text" name="datei" value="" /></td>
							<td><input type="checkbox" name="show_in_navigation" value="1" checked="checked" /></td>
							<td><?php create_select($eltern, '', 'eltern', 1); ?></td>
							<td><?php
								foreach ($rollen as $trow) {
									$rolle_id = $trow[0];
									$rolle_name = $trow[1];
									print "<input type='checkbox' value='$rolle_id' name='role_to_page[]' /> ".htmle($rolle_name)."<br />";
								}
							?></td>
							<td><textarea name="beschreibung"></textarea></td>
							<td><textarea name="hinweis"></textarea></td>
							<td><input type="submit" value="Speichern" /></td>
							<td>&mdash;</td>
						</tr>
					</form>
				</table>
<?php
			} else {
				print "Keine Seiten gefunden!";
			}
?>
		</div>
<?php

//insert into page (id, name, file, show_in_navigation, parent) values (43, "Neue Seite", "newpage.php", "1", 25);
//insert into role_to_page values (1, 43)
	}
?>
