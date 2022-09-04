<?php
	include_once("kundenkram.php");
	include_once("functions.php");

	function get_kunde_X($id, $prop) {
		return get_single_row_from_query("select $prop from vvz_global.kundendaten where id = ".esc($id));
	}

	function get_rechnung_name ($kunde_id, $monat, $jahr) {
		return get_kunde_name($kunde_id)."_${jahr}_${monat}";
	}

	function regex_in_file ($file, $regex, $replace) {
		$str = file_get_contents($file);
		$str = preg_replace($regex, $replace, $str);
		file_put_contents($file, $str);
	}

	function deleteDir($dir) {
		if(preg_match("/^\/tmp\//", $dir) && !preg_match("/\.\./", $dir)) {
			system("rm -rf ".escapeshellarg($dir));
		} else {
			die("Bist du bekloppt? Ich lass dich doch nix löschen!");
		}
	}

	function tempdir() {
		$tempfile=tempnam(sys_get_temp_dir(),'');
		// tempnam creates file on disk
		if (file_exists($tempfile)) { unlink($tempfile); }
		mkdir($tempfile);
		if (is_dir($tempfile)) { return $tempfile; }
	}

	function recurseCopy(
		string $sourceDirectory,
		string $destinationDirectory,
		string $childFolder = ''
	): void {
		$directory = opendir($sourceDirectory);

		if (is_dir($destinationDirectory) === false) {
			mkdir($destinationDirectory);
		}

		if ($childFolder !== '') {
			if (is_dir("$destinationDirectory/$childFolder") === false) {
				mkdir("$destinationDirectory/$childFolder");
			}

			while (($file = readdir($directory)) !== false) {
				if ($file === '.' || $file === '..') {
					continue;
				}

				if (is_dir("$sourceDirectory/$file") === true) {
					recurseCopy("$sourceDirectory/$file", "$destinationDirectory/$childFolder/$file");
				} else {
					copy("$sourceDirectory/$file", "$destinationDirectory/$childFolder/$file");
				}
			}

			closedir($directory);

			return;
		}

		while (($file = readdir($directory)) !== false) {
			if ($file === '.' || $file === '..') {
				continue;
			}

			if (is_dir("$sourceDirectory/$file") === true) {
				recurseCopy("$sourceDirectory/$file", "$destinationDirectory/$file");
			}
			else {
				copy("$sourceDirectory/$file", "$destinationDirectory/$file");
			}
		}

		closedir($directory);
	}

	function get_kunde_rechnungsnummer($kunde_id, $datum) {
		$datum = preg_replace("/-/", "", $datum);
		$rechnungsnummer = $kunde_id.$datum;
		return $rechnungsnummer;
	}

	$kunde_id = get_kunde_id_by_db_name($GLOBALS["dbname"]);

	if($kunde_id) {
		$rechnung_id = get_get("id");
		if($rechnung_id) {
			if(kunde_can_access_rechnung($kunde_id, $rechnung_id)) {
				$tmp = tempdir();
				recurseCopy("rechnung_template", $tmp);

				$query = "select jahr from vvz_global.rechnungen where id = ".esc($rechnung_id);
				$jahr = get_single_row_from_query($query);
				$query = "select monat from vvz_global.rechnungen where id = ".esc($rechnung_id);
				$monat = get_single_row_from_query($query);

				$data_file = $tmp."/_data.tex";

				$rechnungsstellung = "1.$monat.$jahr";

				#$zahlungsfrist = date('Y-m-d', strtotime("+6 months", strtotime($rechnungsstellung)));

				regex_in_file($data_file, "/ANREDE/", get_kunde_X($kunde_id, "anrede"));
				regex_in_file($data_file, '/DATUMRECHNUNGSSTELLUNG/', $rechnungsstellung);
				regex_in_file($data_file, '/UNIVERSITAET/', get_kunde_X($kunde_id, "universitaet"));
				regex_in_file($data_file, '/KUNDENAME/', get_kunde_X($kunde_id, "name"));
				regex_in_file($data_file, '/KUNDESTRASSE/', get_kunde_X($kunde_id, "strasse"));
				regex_in_file($data_file, '/KUNDEPLZ/', get_kunde_X($kunde_id, "plz"));
				regex_in_file($data_file, '/KUNDEORT/', get_kunde_X($kunde_id, "ort"));
				regex_in_file($data_file, '/RECHNUNGSNUMMER/', $rechnung_id);

				$invoice_file = $tmp."/_invoice.tex";

				$plan_id = get_plan_id_by_kunde_id($kunde_id);
				$plan_name = get_plan_name_by_id($plan_id);
				// [Name, Anzahl, Preis]

				$number_of_faculties = get_single_row_from_query("select count(*) from ".$GLOBALS["dbname"].".institut");

				$fees = array();
				$fees_query = "select * from vvz_global.view_rechnung where kunde_id = ".esc($kunde_id);
				$result = rquery($fees_query);
				while ($row = mysqli_fetch_assoc($result)) {
					// [ $plan_name, 1, get_plan_price_by_name($plan_name)[0] ]
					if(preg_match("/Faculty/", $row["plan_name"])) {
						$fees[] = [$row["plan_name"], get_plan_price_by_name($plan_name)[$row["zahlungszyklus_monate"] == 1 ? 0 : 1], $number_of_faculties ];
						for ($j = 0; $j < $number_of_faculties; $j++) {
						}
					} else {
						$fees[] = [$row["plan_name"], get_plan_price_by_name($plan_name)[$row["zahlungszyklus_monate"] == 1 ? 0 : 1], 1 ];
					}
				}

				$fees_string = "";
				for ($i = 0; $i < count($fees); $i++) {
					$fees_string .= "\\Fee{".$fees[$i][0]."}{".$fees[$i][1]."}{".$fees[$i][2]."}\n";
				}

				regex_in_file($invoice_file, "/FEESHERE/", $fees_string);

				ob_start();
				system("cd $tmp && latexmk -quiet -pdf _main.tex 2>&1 > /dev/null");
				ob_clean();

				$filename = $tmp."/_main.pdf";

				header('Content-Type: application/octet-stream');
				header("Content-Transfer-Encoding: Binary");
				header("Content-disposition: attachment; filename=\"".get_rechnung_name($kunde_id, $jahr, $monat).".pdf\"");

				readfile($filename);


				deleteDir($tmp);
			} else {
				die("Sie dürfen nicht die Rechnung Anderer einsehen. Das sollte eigentlich einleuchten, oder?");
			}
		} else {
			die("Keine Rechnungs-ID eingegeben");
		}
	} else {
		die("Es konnte keine Kunden-ID gefunden werden");
	}
?>
