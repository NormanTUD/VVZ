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
		<form action="admin?page=<?php print htmlentities(get_get("page") ?? ""); ?>" method="post" enctype="multipart/form-data">
			Logo hochladen: <input type="file" name="neues_logo" noautosubmit=1 id="neues_logo">
			<input type="submit" noautosubmit=1 value="Neues Logo hochladen" name="submit"><br>
			<i>Sie haben selbst Verantwortung über das Logo. Mit dem Hochladen akzeptieren Sie, dass Sie für das Logo rechtlich verantwortlich sind und gegen keine Gesetze verstoßen.</i>
		</form>

		<form action="admin?page=<?php print htmlentities(get_get("page") ?? ""); ?>" method="post" enctype="multipart/form-data">
			<input type="hidden" noautosubmit=1 value="1" name="delete_logo" />
			<button>Logo löschen</button>
		</form>

		<table><tr><td>
			<table class="auto_reload_stylesheets autorowspan">
				<thead>
					<tr>
						<th width=100>Beschreibung</th>
						<th>Eigenschaft</th>
						<th width=100>Wert</th>
						<th>Standardwert</th>
					</tr>
				</thead>
				<tbody>
<?php
					$query = "select id, humanname, classname, property, val, default_val from customizations order by id";
					$results = rquery($query);

					$whole = array();
					while ($row = mysqli_fetch_assoc($results)) {
						$whole[] = $row;
					}

					$i = 0;
					foreach ($whole as $row) {
						$gui_id = hash("md5", json_encode($row));

						if(preg_match("/color/", $row["property"])) {
?>
							<form action="admin?page=<?php print htmlentities(get_get("page") ?? ""); ?>" method="post" enctype="multipart/form-data">
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
										<input type="text" id="<?php print $gui_id; ?>" name="value" <?php print preg_match("/color/", $row["property"] )? 'class="jscolor"' : ''; ?> value="<?php print addslashes(htmlentities($row["val"] ?? "")); ?>" />
									</td>
									<td>
										<button type="button" class="reset_value_button" data-gui-id="<?php print $gui_id; ?>" data-reset="<?php print htmlentities($row["default_val"] ?? ""); ?>">Zurücksetzen</button>
									</td>
<?php
									/*
									if($i == 0) {
?>
										<td class="td_iframe" rowspan="<?php print count($whole); ?>">
											<iframe id="iframe_reloader" class="full_height_iframe" src="startseite">Ihr Browser unterstützt leider keine IFrames</iframe>
										</td>
<?php
									}
									*/
?>
								</tr>
							</form>
<?php
							$i++;
						}
					}
?>
				</tbody>
			</table>
			</td><td valign=top>
				<iframe id="iframe_reloader" class="full_height_iframe" src="startseite">Ihr Browser unterstützt leider keine IFrames</iframe>
			</td></table>
<?php
		js(array("jscolor.js"));
		js(array("autorowspan.js"));
		js(array("autosubmit.js"));
	}
?>
