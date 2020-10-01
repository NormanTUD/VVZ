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
			<form method="post" enctype="multipart/form-data" action="admin.php?page=<?php print $GLOBALS['this_page_number']; ?>">
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

		<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
		<script type="text/javascript">
			google.charts.load("current", {packages:["corechart"]});
			google.charts.setOnLoadCallback(drawChart);
			function drawChart() {
			var data = google.visualization.arrayToDataTable([
			  ['Browser', 'Anzahl']
<?php
			$query = 'select concat(browser_name, " ", browser_version) as browser, c, year, month from ua_overview where 1';

			if(isset($query_addon)) {
				$query .= $query_addon;
			}

			$result = rquery($query);
			while ($row = mysqli_fetch_row($result)) {
				print ",[".esc($row[0]).", ".$row[1]."]\n";
			}
?>
			]);

			var options = {
			  title: 'Browser',
			  is3D: true,
			};

			var chart = new google.visualization.PieChart(document.getElementById('browser_3d'));
				chart.draw(data, options);
			}
		</script>
		<script type="text/javascript">
			google.charts.load("current", {packages:["corechart"]});
			google.charts.setOnLoadCallback(drawChart);
			function drawChart() {
			var data = google.visualization.arrayToDataTable([
			  ['OS', 'Anzahl']
<?php
			$query = 'select concat(os_name, " ", os_version) as os, sum(c), year, month from ua_overview where 1';
			if(isset($query_addon)) {
				$query .= $query_addon;
			}
			$query .= ' group by os order by c desc';

			$result = rquery($query);
			while ($row = mysqli_fetch_row($result)) {
				print ",[".esc($row[0]).", ".$row[1]."]\n";
			}
?>
			]);

			var options = {
			  title: 'OS',
			  is3D: true,
			};

			var chart = new google.visualization.PieChart(document.getElementById('os_3d'));
				chart.draw(data, options);
			}
		</script>
		<script type="text/javascript">
			google.charts.load("current", {packages:["corechart"]});
			google.charts.setOnLoadCallback(drawChart);
			function drawChart() {
			var data = google.visualization.arrayToDataTable([
			  ['OS', 'Anzahl']
<?php
			$query = 'select os_name as os, sum(c), year, month from ua_overview where 1';
			if(isset($query_addon)) {
				$query .= $query_addon;
			}

			$query .= ' group by os order by c desc';

			$result = rquery($query);
			while ($row = mysqli_fetch_row($result)) {
				print ",[".esc($row[0]).", ".$row[1]."]\n";
			}
?>
			]);

			var options = {
			  title: 'OS (nur Typ)',
			  is3D: true,
			};

			var chart = new google.visualization.PieChart(document.getElementById('os_name_only_3d'));
				chart.draw(data, options);
			}
		</script>
		<script type="text/javascript">
			google.charts.load("current", {packages:["corechart"]});
			google.charts.setOnLoadCallback(drawChart);
			function drawChart() {
			var data = google.visualization.arrayToDataTable([
			  ['OS', 'Anzahl']
<?php
			$query = 'select browser_name as browser, sum(c), year, month from ua_overview where 1';
			if(isset($query_addon)) {
				$query .= $query_addon;
			}

			$query .= ' group by browser order by c desc';

			$result = rquery($query);
			while ($row = mysqli_fetch_row($result)) {
				print ",[".esc($row[0]).", ".$row[1]."]\n";
			}
?>
			]);

			var options = {
			  title: 'Browser (nur Typ)',
			  is3D: true,
			};

			var chart = new google.visualization.PieChart(document.getElementById('browser_only_typ_3d'));
				chart.draw(data, options);
			}
		</script>
		<script type="text/javascript">
			google.charts.load("current", {packages:["corechart"]});
			google.charts.setOnLoadCallback(drawChart);
			function drawChart() {
			var data = google.visualization.arrayToDataTable([
			  ['OS, Browser', 'Anzahl']
<?php
			$query = 'select concat(os_name, " ", os_version, ", ", browser_name, " ", browser_version) AS `os`, sum(c) as c, year, month from ua_overview where 1';
			if(isset($query_addon)) {
				$query .= $query_addon;
			}
			$query .= ' GROUP BY `os` order by c desc';
			$result = rquery($query);
			while ($row = mysqli_fetch_row($result)) {
				print ",[".esc($row[0]).", ".$row[1]."]\n";
			}
?>
			]);

			var options = {
			  title: 'OS und Browser',
			  is3D: true,
			};

			var chart = new google.visualization.PieChart(document.getElementById('os_browser_3d'));
				chart.draw(data, options);
			}
		</script>
		<script type="text/javascript">
			google.charts.load('current', {'packages':['corechart']});
			google.charts.setOnLoadCallback(drawChart);

			function drawChart() {
				var data = google.visualization.arrayToDataTable([
					['Tag', 'Aufrufe']
<?php
					$query = 'select sum(c) as `sum`, concat(lpad(`year`, 4, "0"), "-", lpad(`month`, 2, "0"), "-", lpad(`day`, 2, "0")) as dd from ua_overview where 1 ';
					if(isset($query_addon)) {
						$query .= $query_addon;
					}
					$query .= ' GROUP BY `dd` ORDER BY `dd`';
					$result = rquery($query);
					while ($row = mysqli_fetch_row($result)) {
						print ",[".esc($row[1]).", ".$row[0]."]\n";
					}
?>

				]);

				var options = {
					title: 'Aufrufe pro Tag',
					curveType: 'function',
					legend: { position: 'bottom' }
				};

				var chart = new google.visualization.LineChart(document.getElementById('aufrufe_pro_tag'));

				chart.draw(data, options);
			}
		</script>

		<script type="text/javascript">
			google.charts.load("current", {packages:["corechart"]});
			google.charts.setOnLoadCallback(drawChart);
			function drawChart() {
			var data = google.visualization.arrayToDataTable([
			  ['OS + Version, Browser', 'Anzahl']
<?php
			$query = 'select concat(os_name, " ", os_version, ", ", browser_name) AS `os`, sum(c) as c, year, month from ua_overview where 1';
			if(isset($query_addon)) {
				$query .= $query_addon;
			}
			$query .= ' GROUP BY `os` order by c desc';
			$result = rquery($query);
			while ($row = mysqli_fetch_row($result)) {
				print ",[".esc($row[0]).", ".$row[1]."]\n";
			}
?>
			]);

			var options = {
			  title: 'OS + Version und Browser',
			  is3D: true,
			};

			var chart = new google.visualization.PieChart(document.getElementById('os_browser_typ'));
				chart.draw(data, options);
			}
		</script>

		<div id="os_3d" style="width: 900px; height: 500px;"></div>
		<div id="os_name_only_3d" style="width: 900px; height: 500px;"></div>
		<div id="browser_only_typ_3d" style="width: 900px; height: 500px;"></div>
		<div id="browser_3d" style="width: 900px; height: 500px;"></div>
		<div id="os_browser_3d" style="width: 900px; height: 500px;"></div>
		<div id="os_browser_typ" style="width: 900px; height: 500px;"></div>
		<div id="aufrufe_pro_tag" style="width: 900px; height: 500px;"></div>

<?php
	}
?>
