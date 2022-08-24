<?php
	if(
		(check_page_rights(get_page_id_by_filename(basename(__FILE__))) && file_exists('/etc/vvz_debug_query')) ||
		file_exists('/etc/vvz_debug_query_all') || 
		(user_is_logged_in() && user_is_admin($GLOBALS['logged_in_user_id'])) &&
		(!is_demo() || file_exists("/etc/hardcore_debugging"))
	) { // Wichtig, damit Niemand ohne Anmeldung etwas ändern kann
		include_once('scripts/SqlFormatter.php');
		print "<div class='clear_both' />\n";
		print "<div class='autocenter_large'>\n";
		print "<a id='toggle_query_analyzer' class='outline_text'>Debugger anzeigen/verstecken?</a>\n";
		print "<div class='clear_both display_none;' id='query_analyzer' />\n";
		print "\t<table class='query_analyzer_table'><tr><th>Query</th><th>Duration</th><th>Numrows</th><th>Query doppelt?</th></tr>\n";
		$i = 0;
		$j = 0;
		$sum = 0;
		$rows = 0;
		$done_queries = array();
		$irgendeine_query_doppelt = 'Nein';
		foreach ($GLOBALS['queries'] as $item) {
			if(!preg_match('/;$/', $item['query'])) {
				$item['query'] .= ';';
			}
			$item['query'] = preg_replace('/`session_id` = "[^"]+"/', '`session_id` = "" /* !!!ausgeblendet!!! */', $item['query']);
			$item['query'] = preg_replace('/INSERT IGNORE INTO `session_ids` \(`session_id`, `user_id`\) VALUES \("[^"]+"/', 'INSERT IGNORE INTO `session_ids` (`session_id`, `user_id`) VALUES ("/* !!!ausgeblendet!!! */"', $item['query']);


			$item['query'] = preg_replace('/`password_sha256` = "[^"]+"/', '`password_sha256` = "" /* !!!ausgeblendet!!! */', $item['query']);

			if(array_key_exists('numrows', $item) && is_numeric($item['numrows'])) {
				$rows += $item['numrows'];
			} else {
				$item['numrows'] = '&mdash;';
			}
			$query_doppelt = array_key_exists($item['query'], $done_queries) ? 'Ja' : 'Nein';
			if($query_doppelt == 'Ja') {
				$irgendeine_query_doppelt = 'Ja';
			}
			print "\t\t<tr><td>".SqlFormatter::highlight($item['query'])."</td><td>".number_format($item['time'], 6)."</td><td>".$item['numrows']."</td><td>".$query_doppelt."</td>\n";
			if(preg_match('/^\s*\/\*.*\*\/\s*(UPDATE|SELECT|DELETE|INSERT)\s(?!@@)/i', $item['query'])) {
				$i++;
			} else {
				$j++;
			}
			$sum += $item['time'];
			$done_queries[$item['query']] = 1;

		}

		if($irgendeine_query_doppelt == 'Ja') {
			$irgendeine_query_doppelt = '<span class="class_red">Ja</span>';
		} else {
			$irgendeine_query_doppelt = '<span class="class_green">Nein</span>';
		}

		print "\t\t<tr><td>&mdash;</td><td>&sum;Zeit&darr;</td><td>&sum;NR&darr;</td><td>Queries Doppelt? $irgendeine_query_doppelt</td></tr>\n";
		print "\t\t<tr><td>All ".($j + $i)." Queries ($j preparational, $i functional)</td><td>".number_format($sum, 8)."</td><td>$rows</td><td></td></tr>\n";
		$php_time = microtime(true) - $php_start;
		print "\t\t<tr><td>PHP without Queries</td><td>".number_format($php_time - $sum, 8)."</td><td></td><td></td></tr>\n";
		print "\t\t<tr><td>All</td><td>".number_format($php_time, 6)."</td><td></td><td></td></tr>\n";
		print "\t</table>\n";
		if(count($GLOBALS['function_usage'])) {
			print "<br /><br />\t<table class='query_analyzer_table'><tr><th>Funktionsname</th><th>Anzahl Aufrufe</th><th>Zeit in Queries</th></tr>\n";
			foreach ($GLOBALS['function_usage'] as $name) {
				print "\t\t<tr><td>".$name['name']."</td><td>".$name['count']."</td><td>".number_format($name['time'], 6)."</td></tr>\n";
			}
			print "\t</table>\n";
		}

		$included_files = get_included_files();
		$included_files = array_map('basename', $included_files);

		print "\t<br /><table>\n";
		$i = 0;
		foreach ($included_files as $id => $name) {
			if($i == 0) {
				print "\t\t<tr><th>Benutzte Dateien:</th></tr>\n";
			}
			if(!file_exists($name)) {
				$testname = "./pages/$name";
				$testname2 = "./scripts/$name";
				if(file_exists($testname)) {
					$name = $testname;
				}else if (file_exists($testname2)) {
					$name = $testname2;
				} else {
					$name = "<span class='red_text'>$name</span>";
				}
			}
			print "\t\t<tr><td>$name</td></tr>\n";
			$i++;
		}
		print "\t</table>\n";

		#print "Number of warnings in MySQL connection: ".$GLOBALS['dbh']['warning_count']."\n";

		if (count($GLOBALS["function_debugger"])) {
			print "<table>\n";
			print "<tr><th>Used Function's name</th><th>Counter</th></tr>\n";
			foreach ($GLOBALS["function_debugger"] as $funcname => $counter) {
				print "<tr><td>$funcname</td><td>$counter</td></tr>\n";
			}
			print "</table>\n";
		}

		if (count($GLOBALS["debug"])) {
			print "<table>\n";
			print "<tr><th>File</th><th>Line</th><th>Debug-Messages</th></tr>\n";
			foreach ($GLOBALS["debug"] as $debugmsg) {
				$caller = $debugmsg["caller"];
				$msg = $debugmsg["msg"];
				print "<tr><td>".$caller["file"]."</td><td>".$caller["line"]."</td><td>$msg</td></tr>\n";
			}
			print "</table>\n";
		}

		print "</div>\n";
		print "</div>\n";

	}
	
?>
