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
	<div id="semester">
		<?php print get_seitentext(); ?>
<?php
		include_once('hinweise.php');
?>
		<h2>Aktuelles Semester setzen</h2>

<?php
		$valid_semesters = create_semester_array(0, 0, array(get_get('semester')));
?>
		<form method="post" enctype="multipart/form-data" action="admin.php?page=<?php print $GLOBALS['this_page_number']; ?>">
<?php
			create_select($valid_semesters, get_this_semester()[0], 'setze_semester');
?>
			<input type="submit" value="Als Standardsemester setzen" />
		</form>
<?php
	}
?>
