<?php
	function tempdir() {
		$tempfile=tempnam(sys_get_temp_dir(),'');
		// tempnam creates file on disk
		if (file_exists($tempfile)) { unlink($tempfile); }
		mkdir($tempfile);
		if (is_dir($tempfile)) { return $tempfile; }
	}

die(tempdir());

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

	$tmp = tempdir();
	recurseCopy("rechnung_template", $tmp);
	print $tmp;
	flush();
	sleep(300);
?>
