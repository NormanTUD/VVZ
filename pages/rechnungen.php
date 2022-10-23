<?php
	$included_files = get_included_files();
	$included_files = array_map('basename', $included_files);

	if(!in_array('functions.php', $included_files)) {
		include_once('../functions.php');
	}

	if(!get_setting("x11_debugging_mode") && check_page_rights(get_page_id_by_filename(basename(__FILE__)))) { // Wichtig, damit Niemand ohne Anmeldung etwas ändern kann
		$query = "select id, plan_id, monat, jahr, rabatt, spezialpreis from vvz_global.rechnungen where kunde_id = ".esc(get_kunde_id_by_db_name($GLOBALS["dbname"]))." order by jahr, monat";
		$result = rquery($query);
?>
		<table>
			<tr>
				<th>Jahr, Monat</th>
				<th>Plan</th>
				<th>Preis</th>
				<th>Rechnung</th>
			</tr>
			<tr>
<?php
				while ($row = mysqli_fetch_assoc($result)) {
					$plan_name = get_plan_name_by_id($row["plan_id"]);
					$plan_price = get_plan_price_by_name($plan_name)[0];
?>
						<td><?php print $row["jahr"].", ".$row['monat']; ?></td>
						<td><?php print $plan_name; ?></td>
						<td><?php print $plan_price; ?>&nbsp;€</td>
						<td><a href='erstelle_rechnung.php?id=<?php print $row["id"]; ?>'><button>Downloade Rechnung</button></a></td>
<?php
				}
?>
			</tr>
		</table>
<?php
	}
?>
