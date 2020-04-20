<?php
	$php_start = microtime(true);
	if(file_exists('new_setup')) {
		include('setup.php');
		exit(0);
	}
	$page_title = "Vorlesungsverzeichnis TU Dresden";
	$filename = 'index.php';
	if(!isset($GLOBALS['adminpage'])) {
		include("header.php");
	}

	$times = array(
		0 => array("from" => "05:40", "to" => "07:10"),
		1 => array("from" => "07:30", "to" => "09:00"),
		2 => array("from" => "09:20", "to" => "10:50"),
		3 => array("from" => "11:10", "to" => "12:40"),
		4 => array("from" => "13:00", "to" => "14:30"),
		5 => array("from" => "14:50", "to" => "16:20"),
		6 => array("from" => "16:40", "to" => "18:10"),
		7 => array("from" => "18:30", "to" => "20:00"),
		8 => array("from" => "20:20", "to" => "21:50"),
		9 => array("from" => "22:10", "to" => "23:40")
	);
?>
	<div id="mainindex">
		<a href="index.php" border="0"><img alt="TUD-Logo, Link zur Startseite"  src="tudlogo.svg" width="255" /></a>
		<h2>Zeitraster der TU Dresden</h2>
		<table>
			<tr>
				<th>Doppelstunde</th>
				<th>Startzeit</th>
				<th>Endzeit</th>
			</tr>
<?php
			$i = 0;
			foreach ($times as $this_time) {
?>
			<tr>
				<td><?php print $i; ?></td>
				<td><?php print $this_time["from"]; ?></td>
				<td><?php print $this_time["to"]; ?></td>
			</tr>
<?php
				$i++;
			}
?>
		</table>
<?php
	include("footer.php");
?>
