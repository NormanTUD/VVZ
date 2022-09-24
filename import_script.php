<?php
	include_once("functions.php");

	function import_csv ($content = null) {
		$data = array();
		$is_csv = 1;

		$i = 0;
		$delimiter = ",";

		if(array_key_exists('excelfile', $_FILES) && array_key_exists('tmp_name', $_FILES['excelfile'])) {
			$filename = $_FILES['excelfile']['tmp_name'];
			if(!file_exists($filename)) {
				die("Die Datei $filename konnte nicht geöffnet werden");
			}

			$file = fopen($filename, 'r');
			while (($line = fgetcsv($file, 0, $delimiter)) !== FALSE) {
				if(!preg_match("/^\s*$/", join("", $line))) {
					$data[$i] = $line;
					$i++;
				}
			}

			fclose($file);
		} else if (!is_null($content)) {
			$stream = fopen("php://memory", "r+");
			fwrite($stream, $content);
			rewind($stream);
			while (($line = fgetcsv($stream, 0, $delimiter)) !== FALSE) {
				if(!preg_match("/^\s*$/", join("", $line))) {
					$data[$i] = $line;
					$i++;
				}
			}
		}

		if(count($data)) {
			if(import_internal($data, $is_csv)) {
				return 0;
			} else {
				return 1;
			}
		} else {
			$GLOBALS['import_table'] .= "Bitte uploade eine Datei";
			return null;
		}
	}

	function import_internal ($data, $is_csv) {
		$headlines = $data[0];
		$headlines = preg_replace("/\s+/", " ", $headlines);
		$headlines = preg_replace("/^\s+/", "", $headlines);
		$headlines = preg_replace("/\s+$/", "", $headlines);
		$headlines = preg_replace("/-\s+/", "-", $headlines);
		$headlines = preg_replace("/-([a-z])/", "$1", $headlines);

		$spaltennummern = array(
			"studiengang" => array("nr" => null, "regex" => "/studieng.*ng/", "optional" => 0),
			"studienordnung" => array("nr" => null, "regex" => "/studienordnung/", "optional" => 1),
			"institut" => array("nr" => null, "regex" => "/(?:institut|fakult.*t)/", "optional" => 0)
		);

		$used_columns = array();
		foreach ($spaltennummern as $this_spalte_name => $this_spalte_content) {
			$regex = $spaltennummern[$this_spalte_name]["regex"];

			$i = 0;
			$found = 0;
			foreach ($headlines as $this_headline) {
				if(!$found) {
					if(!array_key_exists($i, $used_columns)) {
						if(preg_match($regex, $this_headline ?? "")) {
							$spaltennummern[$this_spalte_name]["nr"] = $i;
							$used_columns[$i] = 1;
							$found = 1;
						}
					}

					$i = $i + 1;
				}
			}
		}

		$error_occured = 0;

		foreach ($spaltennummern as $this_spalte_name => $this_spalte_content) {
			if($this_spalte_content['optional'] == 0) {
				if(is_null($this_spalte_content['nr'])) {
					error("Die nicht-optionale Spalte $this_spalte_name konnte nicht gefunden werden (Regex: ".$this_spalte_content['regex'].").");
					$error_occured = 1;
				}
			}
		}

		if(!$error_occured) {
			$max_spalte = 0;
			foreach ($spaltennummern as $this_spalte_name => $this_spalte_content) {
				if($this_spalte_content['nr'] > $max_spalte) {
					$max_spalte = $this_spalte_content['nr'];
				}
			}

			$status = array();
			$error_lines = array();
			$line = 1;

			$failed_studiengaenge = 0;
			$failed_anlagen = 0;
			$ok_studiengaenge = 0;
			$ok_anlagen = 0;
			$ok_anlagen_mit_turnus = 0;
			$anzeige_tabelle = 0;

			foreach (array_slice($data, 1) as $l) {
				try {
					$studiengang = get_spalte("studiengang", $spaltennummern, $l);
					$institut = get_spalte("institut", $spaltennummern, $l);
					$studienordnung = get_spalte("studienordnung", $spaltennummern, $l);

					if(!preg_match('/[a-zA-ZäöüÖÄÜß1-9]/', $studiengang ?? "")) {
						$error_lines[$line][$spaltennummern["studiengang"]["nr"]] = 'warning';
						warning("In Zeile ".print_line_link($line)." beinhaltet der Studiengangsname keine Buchstaben");
					}

					$status[$line]['something_failed'] = 0;

					$institut_id = create_institut($institut, 1);

					$studiengang_id = create_studiengang($studiengang, $institut_id, $studienordnung);

					if(is_null($studiengang_id)) {
						$status[$line]['studiengang'] = "<span class='red_text'>&#x2717;</span>";
						$failed_studiengaenge = $failed_studiengaenge + 1;
						$status[$line]['something_failed'] = 1;
					} else {
						$status[$line]['studiengang'] = "<span class='green_text'>&#9989;</span>";
						$ok_studiengaenge = $ok_studiengaenge + 1;
					}

					$line++;
				} catch (\Throwable $e) {
					dier($e);
				}
			}

/*
			$jahrstart = date('Y');
			$jahrauswahl = date('Y');
			$monatauswahl = null;
			$show_status_id = null;
			$show_anlage_id = null;
			$show_kunde_id = null;
			$plzbeginswith = null;
			$jahreplus = 10;

			add_wartungstermine($jahrstart + $jahreplus + 1);
			$wartungstabelle_data = prepare_wartungstermine($jahrstart, $jahreplus, null, null, null, null, 1, null, null, null);
			create_wartungstabelle_table_from_data($wartungstabelle_data, $jahrstart, $jahreplus, $monatauswahl, $jahrauswahl);
*/

			$GLOBALS['import_table'] .= array2Table($data, $status, $error_lines);

			$GLOBALS['import_table'] .= "<br>Anzahl OKer Studiengangsimporte: $ok_studiengaenge<br>";
			$GLOBALS['import_table'] .= "<br>Anzahl fehlgeschlagener Studiengangsimporte: $failed_studiengaenge<br>";
		}

		return $error_occured;
	}

	import_csv("hallo,welt\n1,2");
?>
