<?php
	$included_files = get_included_files();
	$included_files = array_map('basename', $included_files);

	if(!in_array('functions.php', $included_files)) {
		include_once('../functions.php');
	}

	if(check_page_rights(get_page_id_by_filename(basename(__FILE__)))) { // Wichtig, damit Niemand ohne Anmeldung etwas ändern kann
?>
		<div id="studiengang">
			<?php print get_seitentext(); ?>
<?php
			include_once('hinweise.php');
			$institute = create_institute_array();
			if(count($institute)) {
				if(preg_match('/^\d+$/', $GLOBALS['user_institut_id'])) {
					if(get_get('institut')) {
?>
						<form class="form" method="get">
							<input type="hidden" value="<?php print $GLOBALS['this_page_number']; ?>" name="page" />
							<input type="submit" value="Die Daten aller Fakultäten anzeigen" />
						</form>
<?php
					} else {
						if(count($institute) >= 2) {
?>
							<form class="form" method="get">
								<input type="hidden" value="<?php print $GLOBALS['this_page_number']; ?>" name="page" />
								<input type="hidden" value="<?php print $GLOBALS['user_institut_id']; ?>" name="institut" />
								<input type="submit" value="Nur die Daten meines Institutes anzeigen" />
							</form>
<?php
						}
					}
				}

				$where = '';
				if(get_get('institut')) {
					$where = ' WHERE `institut_id` = '.esc(get_get('institut'));
				}

				$studienordnung = '<div class="tooltip">Studienordnung-URL<span class="tooltiptext">Sofern hier eine URL eingegeben wird, wird sie im Vorlesungsverzeichnis für den jeweiligen Studiengang eingeblendet und ermöglicht es, ohne großes Suchen direkt auf die Studienordnung zu gelangen.</span></div>';

				//function create_table_one_dependency ($data, $columnnames, $headlines, $table, $page, $select_name, $dataname, $where = null) {
				create_table_one_dependency(
					$institute,
					array('institut_id', 'name', 'studienordnung', 'order_key'), 
					array('Institut', 'Name', $studienordnung, 'Order-Key', 'Speichern', 'Löschen'), 
					'studiengang', 
					$GLOBALS['this_page_number'], 
					'institut_id', 
					'studiengang', 
					$where,
					"order by order_key asc, name asc"
				);
			} else {
				print "Keine Institute. Bitte fügen Sie zuerst ein Institut hinzu.";
			}
?>
		</div>

<?php
	}
?>
