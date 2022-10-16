<?php
	$included_files = get_included_files();
	$included_files = array_map('basename', $included_files);

	if(!in_array('functions.php', $included_files)) {
		include_once('../functions.php');
	}

	if(!file_exists('/etc/x11test') && check_page_rights(get_page_id_by_filename(basename(__FILE__)))) { // Wichtig, damit Niemand ohne Anmeldung etwas ändern kann
?>
		<div id="merges">
			<?php print get_seitentext(); ?>
<?php
		include_once('hinweise.php');
			if(!get_get('table')) {
				$tables = array();

				$query = 'SHOW TABLES';
				$result = rquery($query);

				while ($row = mysqli_fetch_row($result)) {
					$table = $row[0];


					if(table_has_mergeable_structure($table)) {
						$tables[] = $table;
					}
				}
?>
				Folgende Tabellen können bearbeitet werden:
				<ul>
<?php
					foreach ($tables as $this_table) {
?>
						<li><a href="admin?page=<?php print $GLOBALS['this_page_number']; ?>&table=<?php print htmlentities($this_table); ?>"><?php print htmle($this_table); ?></a></li>
<?php
					}
?>
				<ul>
<?php
			} else {
				if(preg_match('/[a-z_0-9]/i', get_get('table'))) {
					if(table_exists($GLOBALS['dbname'], get_get('table'))) {
						if(table_has_mergeable_structure(get_get('table'))) {
?>
							<form method="post" action="admin?page=<?php print $GLOBALS['this_page_number']; ?>&table=<?php print htmlentities(get_get('table')); ?>">
								<table>
									<tr>
										<th>&mdash;</th>
										<th>ID</th>
										<th>Name</th>
									</tr>
<?php
									$query = 'SELECT `id`, `name` FROM `'.get_get('table').'` ORDER BY `name`';
									$result = rquery($query);
									while ($row = mysqli_fetch_row($result)) {
	?>
									<tr>
										<td><input type="checkbox" name="merge_from[]" value="<?php print htmlentities($row[0]); ?>"><br></td>
										<td><?php print htmle($row[0]); ?></td>
										<td><?php print htmle($row[1]); ?></td>
									</tr>
<?php
									}
?>
								</table>
								<hr />
								... nach ...
								<hr />
								<table>
									<tr>
										<th>&mdash;</th>
										<th>ID</th>
										<th>Name</th>
									</tr>
<?php
									$query = 'SELECT `id`, `name` FROM `'.get_get('table').'` ORDER BY `name`';
									$result = rquery($query);
									while ($row = mysqli_fetch_row($result)) {
?>
									<tr>
										<td><input type="radio" name="merge_to" value="<?php print htmlentities($row[0]); ?>"><br></td>
										<td><?php print htmle($row[0]); ?></td>
										<td><?php print htmle($row[1]); ?></td>
									</tr>
<?php
									}
?>
								</table>
								<input type="submit" value="Mergen!" name="merge_data" />
							</form>
<?php
						} else {
							print "Die ausgewählte Tabelle ist nicht mergebar.";
						}
					} else {
						print "Die ausgewählte Tabelle existiert nicht.";
					}
				} else {
					print "Der Tabellenname hat falsche Zeichen!\n";
				}
			}
?>
			</div>
<?php
	}
?>
