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
		die(print_r($_FILES));
?>
		<form action="admin.php?page=<?php print htmlentities(get_get("page") ?? ""); ?>" method="post" enctype="multipart/form-data">
			Select image to upload:
			<input type="file" name="neues_logo" id="neues_logo">
			<input type="submit" value="Upload Image" name="submit">
		</form>
<?php
	}
?>
