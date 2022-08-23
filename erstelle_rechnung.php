<?php
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
