<?php
	include_once("kundenkram.php");
	include_once("functions.php");

	function get_kunde_X($id, $prop) {
		return get_single_row_from_query("select $prop from vvz_global.kundendaten where id = ".esc($id));
	}

	function get_rechnung_name () {
		return "abc";
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

	$kunde_id = get_kunde_id_by_url(get_url_uni_name());

	if($kunde_id) {
		$tmp = tempdir();
		recurseCopy("rechnung_template", $tmp);

		$data_file = $tmp."/_data.tex";

		$rechnungsstellung = "2021-05-03";

		$zahlungsfrist = date('Y-m-d', strtotime("+6 months", strtotime($rechnungsstellung)));


		regex_in_file($data_file, "/ANREDE/", get_kunde_X($kunde_id, "anrede"));
		regex_in_file($data_file, '/DATUMRECHNUNGSSTELLUNG/', $rechnungsstellung);
		regex_in_file($data_file, '/DATUMZAHLUNGSFRIST/', $zahlungsfrist);
		regex_in_file($data_file, '/UNIVERSITAET/', get_kunde_X($kunde_id, "universitaet"));
		regex_in_file($data_file, '/KUNDENAME/', get_kunde_X($kunde_id, "kundename"));
		regex_in_file($data_file, '/KUNDESTRASSE/', get_kunde_X($kunde_id, "kundestrasse"));
		regex_in_file($data_file, '/KUNDEPLZ/', get_kunde_X($kunde_id, "kundeplz"));
		regex_in_file($data_file, '/KUNDEORT/', get_kunde_X($kunde_id, "kundeort"));

		$invoice_file = $tmp."/_invoice.tex";

		$fees = array(
			["Demo", "1", 0],
			["Pro", 1, 50],
			["Superduperultrapropremiumplus", 10, 500]
		);

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
		header("Content-disposition: attachment; filename=\"".get_rechnung_name().".pdf\"");

		readfile($filename);


		deleteDir($tmp);
	} else {
		die("Es konnte keine Kunden-ID gefunden werden");
	}
?>
