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
	<div id="mail">
		<?php print get_seitentext(); ?>
<?php
		include_once('hinweise.php');
		if(preg_match('/^\d+$/', get_get('mailid'))) {
			$mailid = get_get('mailid');
			$query = 'SELECT `id`, `predecessor_id`, `name`, `time`, `subject`, `message`, `sent` FROM `emails` WHERE `id` = '.esc($mailid).' OR `predecessor_id` = '.esc($mailid).' ORDER BY `time`';
			dier($query);
			print "a<br>\n";
		} else {
			if(preg_match('/^0|1$/', get_get('unreplied'))) {
				$query = 'select id, predecessor_id, name, time, subject, sent from emails';
				if(get_get("unreplied") == 1) {
					$query .= ' where predecessor_id is null';
				}

				$result = rquery($query);

				while ($row = mysqli_fetch_row($result)) {
					$id = $row[0];
					$predecessor_id = $row[1];
					$name = $row[2];
					$time = $row[3];
					$subject = $row[4];
					$sent = $row[5];
					print "$id - $name<br>\n";
				}
			} else {
?>
				<a href="admin?page=<?php print htmlentities(get_get('page')); ?>&unreplied=1">Noch nicht beantwortete Mails</a><br />
				<a href="admin?page=<?php print htmlentities(get_get('page')); ?>&unreplied=0">Beantwortete Mails</a><br />
<?php
			}
		}
	}
?>
