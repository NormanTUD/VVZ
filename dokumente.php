<?php
	ini_set('memory_limit', '512M');
	include_once("functions.php");

	$dokumente = array(
		0 => array(
			"title" => "Anmeldung zu einer nicht online ausgeschriebenen Prüfung",
			"master" => "pruefung.png",
			"fields" => array(
				array(
					"name" => "Name",
					"x" => 1540,
					"y" => 1780,
					"type" => "text",
					"value" => get_get_or_cookie('Name'),
					"cookie" => 1
				),
				array(
					"name" => "Vorname",
					"x" => 1540,
					"y" => 2080,
					"type" => "text",
					"value" => get_get_or_cookie('Vorname'),
					"cookie" => 1
				),
				array(
					"name" => "Matrikelnummer",
					"x" => 1540,
					"y" => 2480,
					"type" => "text",
					"value" => get_get_or_cookie('Matrikelnummer'),
					"cookie" => 1
				),
				array(
					"name" => "Email",
					"x" => 1540,
					"y" => 2790,
					"type" => "text",
					"value" => get_get_or_cookie('Email'),
					"cookie" => 1
				),
				array(
					"name" => "Prüfungsnummer",
					"x" => 573,
					"y" => 3850,
					"type" => "text",
					"value" => get_get_or_cookie("Prüfungsnummer"),
					"cookie" => 0
				),
				array(
					"name" => "Prüfer",
					"x" => 3580,
					"y" => 3770,
					"type" => "text",
					"value" => get_get_or_cookie("Prüfer"),
					"cookie" => 0
				),
				array(
					"name" => "Beschreibung",
					"x" => 1650,
					"y" => 3750,
					"type" => "pruefungsdaten",
					"cookie" => 0
				)
			)
		)
	);

	$id = get_get('id');
	if(!is_null($id) && get_get('save_cookies')) {
		foreach ($_GET as $key => $value) {
			if(array_key_exists($id, $dokumente)) {
				setcookie('save_cookies', 1);
				foreach ($dokumente[$id] as $field_key => $field_data) {
					if(is_array($dokumente[$id][$field_key])) {
						foreach ($dokumente[$id][$field_key] as $this_field) {
							if($this_field['cookie'] == 1) {
								setcookie($this_field['name'], $this_field['value']);
							}
						}
					}
				}
			}
		}
	}

	$php_start = microtime(true);
	if(file_exists('new_setup')) {
		include('setup.php');
		exit(0);
	}
	$page_title = "Vorlesungsverzeichnis TU Dresden | FAQ";
	$filename = 'index.php';
	include("header.php");
?>
	<div id="mainindex">
		<a href="index.php" border="0"><img alt="TUD-Logo, Link zur Startseite"  src="tudlogo.svg" width="255" /></a>
		<h1>Dokumente</h1>
<?php
		$id = get_get('id');

		if(array_key_exists($id, $dokumente)) {
			$dokument = $dokumente[$id];
			$title = $dokument['title'];
			$fields = $dokument['fields'];
			$master = $dokument['master'];
			$master_file = './dokumente/'.$master;

			if(file_exists($master_file)) {
				if(get_get('generate')) {
					$im = imagecreatefrompng($master_file);

					if(!$im) {
						print "Image could not be created!";
					}

					foreach ($fields as $field) {
						$type = $field['type'];
						$x = $field['x'];
						$y = $field['y'];
						$value = null;
						$pn = $fields[4]['value'];

						if($type == 'text') {
							$value = $field['value'];
						} else if($type == 'pruefungsdaten') {
							if(!is_null(get_pruefungsnummer_id_by_pruefungsnummer($pn))) {
								$value = get_pruefungstyp_by_pruefungsnummer($pn).",\n".get_modul_by_pruefungsnummer($pn);
							}
						}

						if(!is_null($value)) {
							$black = imagecolorallocate($im, 0, 0, 0);
							$fontsize = 70;
							$angle = 0;
							$font = './dokumente/FreeSans.ttf';
							imagettftext($im, $fontsize, $angle, $x, $y, $black, $font, $value);
						}
					}

					$im = imagescale($im, 1210, 1711);
					ob_start();
					imagepng($im);
					$imagedata = ob_get_contents();
					ob_end_clean();
					$base64_encoded = base64_encode($imagedata);
					if($base64_encoded) {
						print "Die folgende Grafik können Sie nun ausdrucken. Sie enthält die Daten, die Sie eingetragen haben.<br />\n";
						$base64 = 'data:image/png;base64,'.$base64_encoded;
						print '<p><a href="'.$base64.'"><img src="'.$base64.'" alt="image 1" width="800" /></a></p>';
					} else {
						print "ERROR: Das Bild konnte nicht erstellt werden.";
					}

					imagedestroy($im);
				} else {
					print "<h3>".$title."</h3>\n";
					print "<h4>Felder</h4>\n";
					print "<form method='get'>\n";
					print "<input type='hidden' name='id' value='".htmlentities($id)."' />\n";
					print "<input type='hidden' name='generate' value='1' />\n";
					print "<table>\n";
					foreach ($fields as $field) {
						$fieldname = $field['name'];
						$fieldtype = $field['type'];
						$fieldvalue = array_key_exists('value', $field) ? $field['value'] : '';
						$cookie = $field['cookie'];
						if($fieldtype == "text") {
							print "<tr>\n";
							print "<td>\n";
							print $fieldname.($cookie ? '*' : '')."\n";
							print "</td>\n";
							print "<td>\n";
							if($fieldtype == 'text') {
								print "<input type='text' name='".$fieldname."' value='".$fieldvalue."' />\n";
							}
							print "</td>\n";
							print "</tr>\n";
						}
					}
					print "<tr>\n";
					print "<td>\n";
					print "Mit Sternchen markierte Daten im Cookie auf eigenem PC speichern?";
					print "</td>\n";
					print "<td>\n";
					print "<input type='checkbox' value='1' name='save_cookies' ".(get_get_or_cookie('save_cookies') ? 'checked' : '')."/>";
					print "</td>\n";
					print "</tr>\n";
					print "</table>\n";
					print "<input type='submit' value='Dokument generieren' />\n";
					print "</form>\n";
				}
			} else {
				print "ERROR: Der Master konnte nicht gefunden werden!\n";
			}
		} else if ($id) {
			print "Invalide Id!\n";
		} else {
			print "Dieses Tool soll Ihnen helfen, Dokumente automatisch zu erstellen. Mit einem Klick auf das jeweilige Dokument können Sie die Felder eintragen und das fertige Dokument ausdrucken. Das Tool hilft, indem es Daten, die im Vorlesungsverzeichnis gespeichert sind (wie z.B. Modul- und Prüfungstypen aus der Prüfungsnummer), automatisch einträgt. Die eingetragenen Daten werden <i><b>nicht</b></i> auf dem Server gespeichert.<br />";
			print "<ul>\n";
			foreach ($dokumente as $dokument_id => $dokument_data) {
				print "<li><a href='dokumente.php?id=".$dokument_id."'>".$dokument_data['title']."</a></li>\n";
			}
			print "</ul>\n";
		}
?>
<?php
	include("footer.php");
?>
