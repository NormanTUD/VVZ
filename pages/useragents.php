<?php
	$included_files = get_included_files();
	$included_files = array_map('basename', $included_files);

	if(!in_array('functions.php', $included_files)) {
		include_once('../functions.php');
	}

	if(check_page_rights(get_page_id_by_filename(basename(__FILE__)))) { // Wichtig, damit Niemand ohne Anmeldung etwas ändern kann
		$query_addon = '';
		if(get_post('from') && preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', get_post('from'))) {
			if(get_post('to') && preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', get_post('from'))) {
				$query_addon .= ' AND concat(lpad(`year`, 4, "0"), "-", lpad(`month`, 2, "0"), "-", lpad(`day`, 2, "0")) BETWEEN '.esc(get_post('from')).' AND '.esc(get_post('to'));
			}
		}

		if(!get_post('show_bots')) {
			$query_addon .= ' AND `browser_name` NOT LIKE "%libwww-perl%" AND `browser_name` NOT LIKE "%Simple %" AND `browser_name` NOT LIKE "%OpenVAS%" AND `os_name` NOT LIKE "%n/a%" AND `browser_name` NOT LIKE "%bot%" AND `browser_name` NOT LIKE "%crawler%" AND `browser_name` NOT LIKE "%bing%"';
		}
?>
		<div id="accounts">
			<form method="post" enctype="multipart/form-data" action="admin?page=<?php print $GLOBALS['this_page_number']; ?>">
				<input type="text" placeholder="Von..." style="width: 100px;" name="from" class="datepicker" value="<?php print htmlentities(get_post('from')); ?>" />
				<input type="text" placeholder="... bis" style="width: 100px;" name="to" class="datepicker" value="<?php print htmlentities(get_post('to')); ?>" />
				Bots anzeigen? <input type="checkbox" name="show_bots" value="1" <?php print get_post('show_bots') ? 'checked="CHECKED"' : ''; ?> />
				<input type="submit" value="Filtern" />
			</form>
		<?php print get_seitentext(); ?>
<?php
		include_once('hinweise.php');
?>
		</div>
<?php
			$site = '';
			$site .= print_h("Browserversion pro Zeitraum");
			$query = 'select concat(browser_name, " ", browser_version) as browser, c, year, month from ua_overview where 1';
			if(isset($query_addon)) {
				$query .= $query_addon;
			}
			$site .= query_to_table($query, array("Browser", "Count", "Year", "Month"));



			$site .= print_h("OS-Version pro Zeitraum");
			$query = 'select concat(os_name, " ", os_version) as os, sum(c), year, month from ua_overview where 1';
			if(isset($query_addon)) {
				$query .= $query_addon;
			}
			$query .= ' group by os order by c desc';
			$site .= query_to_table($query, array("OS", "Summe", "Year", "Month"));




			$site .= print_h("OS-Version pro Zeitraum");
			$query = 'select os_name as os, sum(c), year, month from ua_overview where 1';
			if(isset($query_addon)) {
				$query .= $query_addon;
			}

			$query .= ' group by os order by c desc';

			$site .= query_to_table($query, array("OS", "Summe", "Year", "Month"));





			$site .= print_h("Browsername pro Zeitraum");
			$query = 'select browser_name as browser, sum(c), year, month from ua_overview where 1';
			if(isset($query_addon)) {
				$query .= $query_addon;
			}

			$query .= ' group by browser order by c desc';

			$site .= query_to_table($query, array("Browser", "Summe", "Year", "Month"));





			$site .= print_h("OS und Browsername und Version pro Zeitraum");
			$query = 'select concat(os_name, " ", os_version, ", ", browser_name, " ", browser_version) AS `os`, sum(c) as c, year, month from ua_overview where 1';
			if(isset($query_addon)) {
				$query .= $query_addon;
			}
			$query .= ' GROUP BY `os` order by c desc';
			$site .= query_to_table($query, array("OS", "Summe", "Year", "Month"));






			$site .= print_h("Summe pro Zeitraum");
			$query = 'select sum(c) as `sum`, concat(lpad(`year`, 4, "0"), "-", lpad(`month`, 2, "0"), "-", lpad(`day`, 2, "0")) as dd from ua_overview where 1 ';
			if(isset($query_addon)) {
				$query .= $query_addon;
			}
			$query .= ' GROUP BY `dd` ORDER BY `dd`';
			$site .= query_to_table($query, array("Summe", "dd"));






			$site .= print_h("OS-Name und Version pro Zeitraum");
			$query = 'select concat(os_name, " ", os_version, ", ", browser_name) AS `os`, sum(c) as c, year, month from ua_overview where 1';
			if(isset($query_addon)) {
				$query .= $query_addon;
			}
			$query .= ' GROUP BY `os` order by c desc';
			$site .= query_to_table($query, array("OS", "Summe", "Year", "Month"));


			$toc = get_toc();

			print $toc;

			print $site;
	}
?>
