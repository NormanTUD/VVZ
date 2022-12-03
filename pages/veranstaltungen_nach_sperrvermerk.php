<?php
	$included_files = get_included_files();
	$included_files = array_map('basename', $included_files);

	if(!in_array('functions.php', $included_files)) {
		include_once('../functions.php');
	}

	if(check_page_rights(get_page_id_by_filename(basename(__FILE__)))) { // Wichtig, damit Niemand ohne Anmeldung etwas ändern kann
		$semester = create_semester_array();

		$semester_mit_sperrvermerk = array();

		foreach ($semester as $s) {
			if(semester_has_sperrvermerk($s[0])) {
				$semester_mit_sperrvermerk[] = $s;
			}
		}

		if(count($semester_mit_sperrvermerk)) {
			$veranstaltungen_nach_sperrvermerk = array();
?>
			<form class="form_autosubmit" method="post">
				<table>
					<tr>
						<th>Veranstaltung</th>
						<th>Dozent</th>
					</tr>
<?php

					foreach ($semester_mit_sperrvermerk as $s) {
?>
						<tr><th colspan=2><?php print get_semester_name($s[0]); ?></th></tr>
<?php
						$semester_id = $s[0];
						$sperrvermerk_zeit = get_single_row_from_query("select last_update from sperrvermerk where semester_id = ".esc($semester_id));

						$veranstaltungen_danach = array();
						$veranstaltungen_danach_query = "select v.id, v.name, concat(d.first_name, ' ', d.last_name) dozent_name from veranstaltung v left join dozent d on v.dozent_id = d.id where last_change > ".esc($sperrvermerk_zeit);
						$result = rquery($veranstaltungen_danach_query);

						while ($row = mysqli_fetch_row($result)) {
							$veranstaltungen_danach[] = $row;
						}

						foreach ($veranstaltungen_danach as $v) {
?>
							<tr>
								<td><?php print $v[1]; ?></td>
								<td><?php print $v[2]; ?></td>
							</tr>
<?php
						}
					}
?>
				</table>
			</form>
<?php
		} else {
			print "Keine Semester mit Sperrvermerk.";
		}
	}

	js(array("autosubmit.js"));
?>
