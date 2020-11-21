<?php
	$php_start = microtime(true);
	if(file_exists('new_setup')) {
		include('setup.php');
		exit(0);
	}
	include_once("config.php");
	$page_title = "Vorlesungsverzeichnis ".$GLOBALS['university_name']." | FAQ";
	$filename = 'index.php';
	include("header.php");
?>
	<div id="mainindex" style="text-align: left!important">
		<center><a href="index.php" border="0"><img alt="TUD-Logo, Link zur Startseite"  src="tudlogo.svg" width="255" /></a></center>
		<h1>FAQ</h1>
		<p>FAQ steht für &raquo;frequently asked questions&laquo;, d.h. häufig-gestellte-Fragen. Über das Kontaktformular
		häufig an uns gerichtete Fragen werden hier beantworten. <a href="kontakt.php">Zögern Sie nicht, uns zu kontaktieren, wenn Sie auch
		eine Frage haben!</a></p>
		<h2>Fragen</h2>
<?php
		$query = 'SELECT `frage`, `antwort` FROM `faq` ORDER BY `wie_oft_gestellt` DESC, `frage` ASC';
		$result = rquery($query);

		$faq = array();
		while ($row = mysqli_fetch_row($result)) {
			$faq[] = $row;
		}

		$counter = 0;
		if(count($faq) >= 2) {
			foreach ($faq as $row) {
?>
				<a href="#frage_<?php print $counter; ?>"><?php print htmlentities($row[0]); ?></a><br />
<?php
				$counter++;
			}
		}

		$counter = 0;
		foreach ($faq as $row) {
?>
			<h3 name="frage_<?php print $counter; ?>"><?php print htmlentities($row[0]); ?></h3>
			<p><?php print $row[1]; ?> </p>
<?php
			$counter++;
		}

		if($counter == 0) {
?>
			Leider sind bisher keine häufig-gestellten-Fragen eingetragen. Bitte kontaktieren Sie uns, wenn Sie 
			Ideen für häufig-gestellte-Fragen haben.
<?php
		}
	include("footer.php");
?>
