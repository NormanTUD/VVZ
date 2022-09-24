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
			<form method="post" enctype="multipart/form-data" action="index.php?page=<?php print $GLOBALS['this_page_number']; ?>">
				<input noautosubmit="1" type="hidden" name="start_import" value="1" />
				<input noautosubmit="1" type="file" name="excelfile" />
				<input noautosubmit="1" type="hidden" name="XDEBUG_PROFILE" value="1" />
				<input noautosubmit="1" type="submit" value="Importieren" />
			</form><br>
<?php
			if($GLOBALS['import_table']) {
				echo $GLOBALS['import_table'];

				echo "<iframe style='display: none;' width='0' height='0' src='index.php?page=".get_page_id_by_filename('wartungstermine2.php')."&jahreplus=5'></iframe>";
			}
		} else {
?>
			<br><i>Die Seite ist deaktiviert, weil der x11_debugging_mode aktiv ist</i>
<?php
		}
	}
?>
