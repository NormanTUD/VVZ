<?php
	$included_files = get_included_files();
	$included_files = array_map('basename', $included_files);

	if(!in_array('functions.php', $included_files)) {
		include_once('../functions.php');
	}

	if(check_page_rights(get_page_id_by_filename(basename(__FILE__)))) { // Wichtig, damit Niemand ohne Anmeldung etwas ändern kann
		$users = create_user_array();
?>
		<?php print get_seitentext(); ?>
<?php
		include_once('hinweise.php');
?>
		Jeder Benutzer hat eine Rolle. Rollen definieren, was der Nutzer sehen und was er verändern darf.
		Jeder Versuch, etwas auszuführen, für das der Nutzer keine Rechte hat, wird hier aufgezeichnet. Die Liste reicht 3 Monate zurück.
		<form method="post" action="admin.php?page=<?php print $GLOBALS['this_page_number']; ?>">
			Benutzer: <?php create_select($users, get_post('id'), 'id', 1); ?>
			<input type="submit" value="Filtern" />
		</form>
		<h3>Funktionen</h3>
<?php
		$table_shown = 0;
		$query = 'select u.username, ri.function, ri.date, ur.user_id, ur.role_id from right_issues ri join users u on u.id = ri.user_id join role_to_user ur on ur.user_id = u.id where 1';
		if(get_post('id')) {
			$query .= ' and `ur`.`user_id` = '.esc(get_post('id'));
		}
		$query .=  ' and date >= DATE_SUB(NOW(), INTERVAL 3 MONTH)';
		$result = rquery($query);
		if(mysqli_num_rows($result)) {
			$table_shown = 1;
?>
			<table>
				<tr>
					<th>Benutzer</th>
					<th>Aufgerufene Funktion</th>
					<th>Datum</th>
					<th>User darf heute darauf zugreifen?*</th>
				</tr>
<?php


				while ($row = mysqli_fetch_row($result)) {
?>
					<tr>
						<td><?php print htmlentities($row[0]); ?></td>
						<td><?php print htmlentities($row[1]); ?></td>
						<td><?php print $row[2]; ?></td>
						<td><?php print check_function_rights_role_id($row[1], $row[4], 0) ? '<span class="green">Ja</span>' : '<span class="red_text">Nein</span>'; ?></td>
					</tr>
<?php
				}
?>
			</table>
<?php
		} else {
?>
		<i>Bisher gab es keine Verstöße <?php print get_post('id') ? ("des Users <b>".get_user_name(get_post('id'))."</b> ") : ''; ?>beim Versuch, interne Funktionen aufzurufen.</i><br />
<?php
		}
?>
		<h3>Seiten</h3>
<?php
		$query = 'select username, page_id, date, ru.user_id, role_id from right_issues_pages ri join users u on u.id = ri.user_id left join role_to_user ru on ru.user_id = u.id where 1';
		if(get_post('id')) {
			$query .= ' and `ru`.`user_id` = '.esc(get_post('id'));
		}
		$query .=  ' and date >= DATE_SUB(NOW(), INTERVAL 3 MONTH)';
		$result = rquery($query);

		$page_ids = create_page_id_by_name_array();

		if(mysqli_num_rows($result)) {
			$table_shown = 1;
?>
			<table>
				<tr>
					<th>Benutzer</th>
					<th>Aufgerufene Seite</th>
					<th>Datum</th>
					<th>User darf heute darauf zugreifen?*</th>
				</tr>
<?php
				while ($row = mysqli_fetch_row($result)) {
?>
					<tr>
						<td><?php print htmlentities($row[0]); ?></td>
						<td><?php print htmlentities($page_ids[$row[1]]); ?></td>
						<td><?php print htmlentities($row[2]); ?></td>
						<td><?php print check_page_rights_role_id($row[1], $row[4], 0) ? '<span class="green">Ja</span>' : '<span class="red_text">Nein</span>'; ?></td>
					</tr>
<?php
				}
?>
			</table>
			<br />
<?php
		} else {
?>
			<i>Bisher gab es keine Verstöße <?php print get_post('id') ? ("des Users <b>".get_user_name(get_post('id'))."</b> ") : ''; ?>beim Versuch, Seiten aufzurufen.</i><br />
<?php
		}
		if($table_shown) {
?>
			<p>*Die Idee ist, dass es sich nicht immer um bösartige Angriffsversuche handeln muss, sondern eventuell auch einfach Datenbankfehler für das Fehlen eines Rechtes
			verantwortlich sein können. Weitergehend gehe ich davon aus, dass solche Fehler gemeldet und korrigiert werden. Somit ist ein grünes Ja in diesem Feld ein
			Anzeichen dafür, dass keine böse Absicht vorlag, sondern ein unvollständiger Rechtedatensatz.</p>
<?php
		}
	}
?>
