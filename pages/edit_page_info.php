<?php
	$included_files = get_included_files();
	$included_files = array_map('basename', $included_files);

	if(!in_array('functions.php', $included_files)) {
		include_once('../functions.php');
	}

	if(!get_setting("x11_debugging_mode") && check_page_rights(get_page_id_by_filename(basename(__FILE__)))) { // Wichtig, damit Niemand ohne Anmeldung etwas ändern kann
?>
	<h2>Beschreibungen einzelner Seiten verändern</h2>
	<?php print get_seitentext(); ?>
<?php
	include_once('hinweise.php');
?>
	<table>
		<tr>
			<th>Seite</th>
			<th>Text</th>
			<th>Speichern</th>
		</tr>
	
<?php
		foreach (create_page_info() as $page_info) {
?>
			<form method="post">
				<input type="hidden" name="update_page_info" value="1" />
				<input type="hidden" name="id" value="<?php print $page_info[0]; ?>" />
				<tr>
					<td><?php print $page_info[1]; ?></td>
					<td><textarea class="width600pxheight100px" name="info"><?php print $page_info[3]; ?></textarea></td>
					<td><input type="submit" value="Speichern" /></td>
				</tr>
			</form>
<?php
		}
?>
	</table>
	
	<p>Diese Texte sind gesondert aufgeführt, damit die Funktionalität erstellt werden kann, die für jeden Nutzer automatisiert nur die Punkte angezeigt, die er auch sehen kann.</p>
	<h2>Hinweise bearbeiten</h2>
	Hinweise erscheinen auf der jeweiligen Seite kursiv und in blau,
	um Aufmerksamkeit auf sich zu lenken.
	<table>
		<tr>
			<th>Seite</th>
			<th>Hinweis</th>
			<th>Speichern</th>
		</tr>
<?php
	$query = 'SELECT `id`, `name`, `hinweis` FROM `view_page_and_hinweis`';
	$result = rquery($query);

	while ($row = mysqli_fetch_row($result)) {
?>
		<tr>
			<form class="form" method="post" action="admin?page=<?php print htmlentities($GLOBALS['this_page_number']) ?>">
				<input type="hidden" name="page_id" value="<?php print htmlentities($row[0]); ?>" />
				<input type="hidden" name="update_hinweis" value="<?php print htmlentities($row[0]); ?>" />
				<td><?php print htmlentities($row[1]); ?></td>
				<td><textarea class="width600pxheight100px" name="hinweis"><?php print $row[2]; ?></textarea></td>
				<td><input type="submit" value="Speichern" /></td>
			</form>
		</tr>
<?php
	}
?>
	</table>

	<h2>Seitentexte bearbeiten</h2>

	Die Seitentexte heben sich optisch, im Gegensatz zu den blauen Hinweisen,
	nicht vom sonstigen Text der Seite hervor.

	<table>
		<tr>
			<th>Seite</th>
			<th>Text am Anfang der Seite</th>
			<th>Speichern</th>
		</tr>
<?php
	$query = 'SELECT `id`, `name`, `text` FROM `view_page_and_text`';
	$result = rquery($query);

	while ($row = mysqli_fetch_row($result)) {
?>
		<tr>
			<form class="form" method="post" action="admin?page=<?php print htmlentities($GLOBALS['this_page_number']) ?>">
				<input type="hidden" name="page_id" value="<?php print $row[0]; ?>" />
				<input type="hidden" name="update_text" value="<?php print $row[0]; ?>" />
				<td><?php print htmlentities($row[1]); ?></td>
				<td><textarea class="width600pxheight100px" name="text"><?php print $row[2]; ?></textarea></td>
				<td><input type="submit" value="Speichern" /></td>
			</form>
		</tr>
<?php
	}
?>
	</table>
<?php
	}
?>
