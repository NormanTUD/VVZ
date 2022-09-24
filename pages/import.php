<?php
	$included_files = get_included_files();
	$included_files = array_map('basename', $included_files);

	if(!in_array('functions.php', $included_files)) {
		include_once('../functions.php');
	}


	if(check_page_rights(get_page_id_by_filename(basename(__FILE__)))) { // Wichtig, damit Niemand ohne Anmeldung etwas ändern kann
		if(!get_setting('x11_debugging_mode')) {
			if(get_post('start_import')) {
				include("import_script.php");
			}
?>
			<form method="post" enctype="multipart/form-data" action="admin?page=<?php print $GLOBALS['this_page_number']; ?>">
				<input noautosubmit="1" type="hidden" name="start_import" value="1" />
				<input noautosubmit="1" type="file" name="excelfile" />
				<input noautosubmit="1" type="hidden" name="XDEBUG_PROFILE" value="1" />
				<input noautosubmit="1" type="submit" value="Importieren" />
			</form><br>
<?php
			if($GLOBALS['import_table']) {
				echo $GLOBALS['import_table'];

				echo "<iframe class='display_none' width='0' height='0' src='admin?page=".get_page_id_by_filename('wartungstermine2.php')."&jahreplus=5'></iframe>";
			}
		} else {
?>
			<br><i>Die Seite ist deaktiviert, weil der x11_debugging_mode aktiv ist</i>
<?php
		}
	}

	foreach (array(
			array("hint", "blue"),
			array("error", "red"),
			array("right_issue", "red"),
			array("warning", "orange"),
			array("message", "blue"),
			array("easter_egg", "hotpink"),
			array("success", "green")
		) as $msg) {
		show_output($msg[0], $msg[1]);
	}

?>
