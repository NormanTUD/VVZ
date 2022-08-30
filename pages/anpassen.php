<?php
	$included_files = get_included_files();
	$included_files = array_map('basename', $included_files);

	if(!in_array('functions.php', $included_files)) {
		include_once('../functions.php');
	}

	if(!file_exists('/etc/x11test') && check_page_rights(get_page_id_by_filename(basename(__FILE__)))) { // Wichtig, damit Niemand ohne Anmeldung etwas ändern kann
		$rollen = create_rollen_array();
		$dozenten = create_dozenten_array(1);
		$instituten = create_institute_array();
?>
	<div id="accounts">
		<?php print get_seitentext(); ?>
<?php
		include_once('hinweise.php');

?>
		<form action="admin.php?page=<?php print htmlentities(get_get("page") ?? ""); ?>" method="post" enctype="multipart/form-data">
			Logo hochladen: <input type="file" name="neues_logo" noautosubmit=1 id="neues_logo">
			<input type="submit" noautosubmit=1 value="Neues Logo hochladen" name="submit"><br>
			<i>Sie haben selbst Verantwortung über das Logo. Mit dem Hochladen akzeptieren Sie, dass Sie für das Logo rechtlich verantwortlich sind und gegen keine Gesetze verstoßen.</i>
		</form>

		<form action="admin.php?page=<?php print htmlentities(get_get("page") ?? ""); ?>" method="post" enctype="multipart/form-data">
			<input type="hidden" noautosubmit=1 value="1" name="delete_logo" />
			<button>Logo löschen</button>
		</form>

		<table class="auto_reload_stylesheets">
			<tr>
				<th>Beschreibung</th>
				<th>Eigenschaft</th>
				<th>Wert</th>
				<th>Standardwert</th>
			</tr>
<?php
			$query = "select id, humanname, classname, property, val, default_val from customizations order by humanname, classname, property, id, val";
			$results = rquery($query);

			while ($row = mysqli_fetch_assoc($results)) {
?>
				<form action="admin.php?page=<?php print htmlentities(get_get("page") ?? ""); ?>" method="post" enctype="multipart/form-data">
					<input type="hidden" value="<?php print htmlentities($row["id"] ?? ""); ?>" name="id" />
					<input type="hidden" value="1" name="customize_value" />
					<tr>
						<td>
							<?php print htmlentities($row["humanname"] ?? ""); ?>
						</td>
						<td>
							<?php print htmlentities($row["property"] ?? ""); ?>
						</td>
						<td>
							<input type="text" name="value" value="<?php print addslashes(htmlentities($row["val"] ?? "")); ?>" />
						</td>
						<td>
							<?php print htmlentities($row["default_val"] ?? ""); ?>
						</td>
					</tr>
				</form>
<?php
			}
?>
			</table>
<?php
			js(array("autosubmit.js"));
	}
?>
