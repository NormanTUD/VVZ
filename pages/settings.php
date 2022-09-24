<?php
	$included_files = get_included_files();
	$included_files = array_map('basename', $included_files);

	if(!in_array('functions.php', $included_files)) {
		include_once('../functions.php');
	}

	if(check_page_rights(get_page_id_by_filename(basename(__FILE__)))) { // Wichtig, damit Niemand ohne Anmeldung etwas ändern kann
		$rollen = create_rollen_array();
?>
	<div id="accounts">
		<table class="invisible_table">
			<tr>
				<td class="invisible_td"><img src="i/wrench.svg"></td>
				<td class="invisible_td">&nbsp;Hier kannst du die Einstellungen ALLER Benutzer ändern.</td>
				<td class="invisible_td"><img class="skull" src="i/skull.svg"/></td>
			</tr>
		</table>


		<?php print get_seitentext(); ?>
<?php
		include_once('hinweise.php');
		if(!get_setting('x11_debugging_mode') || (get_setting('x11_debugging_mode') && get_get('verification_text') == 'Ja, ich will Einstellungen aendern')) {
?>
		<table>
			<tr>
				<th>Name</th>
				<th>Einstellung</th>
				<th>Speichern</th>
				<th>Nach Default resetten</th>
			</tr>
<?php
			$prev_category = '';
			foreach (get_config() as $name => $setting) {
				$default_setting = get_setting_default($name);
				$description = get_setting_desc($name);
				$category = get_setting_category($name);
?>
				<form class="update_setting" id="setting_<?php print $name; ?>" method="post" action="<?php print $_SERVER['REQUEST_URI'] ?>">
<?php
					if($prev_category != $category) {
?>
						<tr>
							<th colspan="5"><?php print htmle($category); ?></th>
						</tr>
<?php
						$prev_category = $category;
					}
?>
					<tr>
						<input type="hidden" value="<?php print $name; ?>" name="name" />
						<input type="hidden" value="1" name="update_setting" />
<?php
						$ja_nein_regex = '/(\(1 ja, 0 nein\))|(\(0 nein, 1 ja\))/';
?>
						<td><?php print $name; ?>: <?php print preg_replace($ja_nein_regex, "", $description) ? htmlentities(preg_replace($ja_nein_regex, "", $description)) : ""; ?></td>
<?php
						if(preg_match($ja_nein_regex, $description)) {
?>
							<td>
								<select name="value">
									<option <?php if($setting) { print " selected "; } ?> value="1">ja</option>
									<option <?php if(!$setting) { print " selected "; } ?> value="0">nein</option>
								</select>
							</td>
<?php
						} else if(preg_match('/color/', $name)) {
?>
							<td><input data-jscolor="{hash:true}" type="text" class="jscolor" name="value" value="<?php print $setting; ?>" /></td>
<?php
						} else if(preg_match('/status/', $name)) {
?>
							<td>
<?php
								print create_select_for_status(0, "value", 0, $setting);
?>
							</td>
<?php
						} else {
?>
							<td><input style="width: 350px;" type="text" name="value" value="<?php print $setting; ?>" /></td>
<?php
						}
?>
						<td><input type="submit" style="font-size: 10px;" value="Speichern" /></td>
						<td>
<?php
							if($setting == $default_setting) {
?>
								&mdash;
<?php
							} else {
?>
								<input type="submit" style="font-size: 10px;" name="reset_setting" value="Reset nach <?php print $default_setting ? htmlentities($default_setting) : ""; ?>" />
<?php
							}
?>
						</td>
					</tr>
				</form>
<?php
			}
?>
		</table>
<?php
		} else {
?>
		<br><i>Die Seite ist deaktiviert, weil der x11_debugging_mode aktiv ist</i><br>
		Geb 1:1 den Text "Ja, ich will Einstellungen aendern" hier ein und drücke Weiter:
		<form method="get">
			<input type="hidden" name="page" value="<?php print get_get('page'); ?>" />
			<input type="text" name="verification_text" value="" />
			<input type="submit" value="Weiter" />
		</form>
<?php
		}
	}
?>
