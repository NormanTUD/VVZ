<?php
	function regex_in_file ($file, $regex, $replace) {
		$str = file_get_contents($file);
		$str = preg_replace($regex, $replace, $str);
		file_put_contents($file, $str);
	}
	function deleteDir($dir) {
		if(preg_match("/^\/tmp\//", $dir)) {
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

	function get_rechnung_name () {
		return "abc";
	}

	$tmp = tempdir();
	recurseCopy("rechnung_template", $tmp);

	$data_file = $tmp."/_data.tex";

	$rechnungsstellung = "2021-05-03";

	$zahlungsfrist = date('Y-m-d', strtotime("+6 months", strtotime($rechnungsstellung)));


	regex_in_file($data_file, "/ANREDE/", "Sehr geehrte Herr Testbenutzer");
	regex_in_file($data_file, '/DATUMRECHNUNGSSTELLUNG/', $rechnungsstellung);
	regex_in_file($data_file, '/DATUMZAHLUNGSFRIST/', $zahlungsfrist);
	regex_in_file($data_file, '/FIRMA/', 'Testuni');
	regex_in_file($data_file, '/KUNDENAME/', 'Hans Peter Test');
	regex_in_file($data_file, '/KUNDESTRASSE/', 'Teststraße 1');
	regex_in_file($data_file, '/KUNDEPLZ/', '123456');
	regex_in_file($data_file, '/KUNDEORT/', 'Testort');

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
?>
