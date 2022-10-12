<?php
	include_once("emojis.php");

	$flags = array(
		array("always_accept_public_data" => "Automatisch die Datenschutzabfrage akzeptieren (NUR FÜR DEVELOPER!)"),
		array("api_debug", "Debugging für die API"),
		array("hardcore_debugging", "Stirbt sofort bei jedem Fehler, jede Warning ist sofort fatal."),
		array("vvz_comic_sans", "Easter-Egg aktivieren"),
		array("vvz_debug_query", "Zeige den Query-Debugger"),
		array("vvz_debug_query_all", "Zeige alle Queries im Query-Debugger"),
		array("x11test", "Deaktiviert einige Sachen, um automatisches X11-Testen einfacher zu machen."),
		array("fill_form_by_default", "Fuellt die change plan seite mit default daten aus (NUR FÜR DEVELOPER)"),
		array("no_cache", "Zwingt dazu, KEINEN Cache anzulegen"),
		array("vvztud", "Für die Philosophen der TUD, keine Startseite, direkt VVZ")
	);
?>
	<table>
		<tr>
			<th>Dateipfad</th>
			<th>Beschreibung</th>
			<th>Aktiv?</th>
		</tr>
<?php
		foreach ($flags as $row) {
			if($row[0]) {
?>
				<tr>
					<td>/etc/<?php print $row[0]; ?></td>
					<td><?php print $row[1]; ?></td>
					<td><?php file_exists("/etc/".$row[0]) ? print_checkbox_symbol() : print_red_cross_symbol(); ?></td>
				</tr>
<?php
			}
		}
?>
	</table>
