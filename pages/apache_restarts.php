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

		Gelegentlich hört der Apache auf, Websites auszugeben. Einmal pro Minute wird daher die Seite gestestet und immer, wenn
		sie nicht erreichbar ist, der Apache automatisch neugestartet.

		Warum das so ist, ist mir nicht bekannt. Aber vielleicht hilft dieses Skript, das herauszufinden und ein Muster
		zu finden. Es liegt in <pre>/bin/tud.pl</pre> und wird per CronJob ausgeführt.<br />
<?php
		include_once('hinweise.php');
		$query = 'SELECT `t`, `reason`, `stdout`, `stderr`, `exit_code`, `success` FROM `apache_restarts`';
		$result = rquery($query);

		if(mysqli_num_rows($result)) {
?>
			<table>
				<tr>
					<th>Zeit</th>
					<th>Grund</th>
					<th>Stdout</th>
					<th>Stderr</th>
					<th>Exit-Code</th>
					<th>Erfolgreich?</th>
				</tr>
<?php
			while ($row = mysqli_fetch_row($result)) {
?>
				<tr>
					<td><?php print htmle($row[0]); ?></td>
					<td><?php print htmle($row[1]); ?></td>
					<td><?php print htmle($row[2]); ?></td>
					<td><?php print htmle($row[3]); ?></td>
					<td><?php print htmlentities($row[4]); ?></td>
					<td><?php print htmle($row[5] ? 'Ja' : 'Nein') ; ?></td>
				</tr>
<?php
			}
?>
			</table>
<?php
		} else {
?>
			<i>Bisher wurde der Apache-Server noch nicht automatisch neugestartet.</i>
<?php
		}
	}
?>
