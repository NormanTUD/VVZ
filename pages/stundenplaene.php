<?php
	$included_files = get_included_files();
	$included_files = array_map('basename', $included_files);

	if(!in_array('functions.php', $included_files)) {
		include_once('../functions.php');
	}

	if(check_page_rights(get_page_id_by_filename(basename(__FILE__)))) { // Wichtig, damit Niemand ohne Anmeldung etwas ändern kann
		$aktuelles_semester = get_this_semester()[0];
		$dozenten = create_dozenten_array();
?>
	<div id="roles">
		<?php print get_seitentext(); ?>
<?php
		include_once('hinweise.php');

?>
		<ul>
<?php
			foreach ($dozenten as $id => $name) {
	?>
				<li><a href="startseite?create_stundenplan=1&semester=<?php print $aktuelles_semester; ?>&dozent[]=<?php print $id; ?>">Stundenplan von <?php print htmle(get_dozent_name($id)); ?></a></li>
<?php
			}
?>
		</ul>
<?php
	}
?>
