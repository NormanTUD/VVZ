<?php
	function import_csv ($content = null) {
		$GLOBALS['return_null_if_anlage_creation_failed'] = 0;

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
				$data[$i] = $line;
				$i++;
			}

			fclose($file);
		} else if (!is_null($content)) {
			$stream = fopen("php://memory", "r+");
			fwrite($stream, $content);
			rewind($stream);
			while (($line = fgetcsv($stream, 0, $delimiter)) !== FALSE) {
				$data[$i] = $line;
				$i++;
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
			"ort" => array ("nr" => null, "regex" => "/^\s*ort/i", "optional" => 0),
			"firma_name" => array ("nr" => null, "regex" => "/^\s*kunde\s*$/i", "optional" => 0),
			"anlage_kommentar" => array ("nr" => null, "regex" => "/kommentar/i", "optional" => 1),
			"ort_anlage" => array ("nr" => null, "regex" => "/^\s*ort/i", "optional" => 1),
			"anlage_name" => array ("nr" => null, "regex" => "/^\s*anlage\s*$/i", "optional" => 0),
			"zeit_pro_wartung" => array ("nr" => null, "regex" => "/zeit\s*pro\s*wartung/i", "optional" => 1),
			"wartungspauschale" => array ("nr" => null, "regex" => "/pauschale/i", "optional" => 1),
			"letzte_wartung" => array ("nr" => null, "regex" => "/letzte\s*wartung/i", "optional" => 0),
			"kein_vertrag" => array ("nr" => null, "regex" => "/vertrag/i", "optional" => 1),
			"anzahl_wartungen_pro_jahr" => array ("nr" => null, "regex" => "/Anz/i", "optional" => 1),
			"tage_zwischen_wartungen" => array ("nr" => null, "regex" => "/Tage/i", "optional" => 1),
			"naechste_wartung" => array ("nr" => null, "regex" => "/n.*chste.*wartung/i", "optional" => 0),
			"strasse" => array ("nr" => null, "regex" => "/stra.*e\s*$/i", "optional" => 1),
			"plz" => array ("nr" => null, "regex" => "/plz/i", "optional" => 1),

			"ansprechpartner_email" => array ("nr" => null, "regex" => "/1.*mail/i", "optional" => 1),
			"ansprechpartner_name" => array ("nr" => null, "regex" => "/1.*sprechpartner/i", "optional" => 1),
			"ansprechpartner_telnr" => array ("nr" => null, "regex" => "/1.*tele/i", "optional" => 1),
			"ansprechpartner_mobil" => array ("nr" => null, "regex" => "/1.*mobil/i", "optional" => 1),

			"ansprechpartner2_email" => array ("nr" => null, "regex" => "/2.*mail/i", "optional" => 1),
			"ansprechpartner2_name" => array ("nr" => null, "regex" => "/2.*sprechpartner/i", "optional" => 1),
			"ansprechpartner2_telnr" => array ("nr" => null, "regex" => "/2.*tele/i", "optional" => 1),
			"ansprechpartner2_mobil" => array ("nr" => null, "regex" => "/2.*mobil/i", "optional" => 1),

			"material_wartung" => array("nr" => null, "regex" => "/material.*wartung/i", "optional" => 1),

			"rechnungsnummer" => array("nr" => null, "regex" => "/rechnungsnummer/i", "optional" => 1),
			"erste_wartung" => array("nr" => null, "regex" => "/erste_wartung/i", "optional" => 1)
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

			$ersatzteile = array_slice($headlines, $max_spalte);

			foreach ($ersatzteile as $name) {
				create_ersatzteil($name);
			}

			$status = array();
			$error_lines = array();
			$line = 1;

			$failed_kunden = 0;
			$failed_anlagen = 0;
			$ok_kunden = 0;
			$ok_anlagen = 0;
			$ok_anlagen_mit_turnus = 0;
			$anzeige_tabelle = 0;

			$anlage_ohne_name_nr = 1;
			foreach (array_slice($data, 1) as $anlage) {
				$wartungen_pro_monat = 1;
				$ort_firma = get_spalte("ort", $spaltennummern, $anlage);
				$erste_wartung_status = 1;
				if(!preg_match('/[a-zA-ZäöüÖÄÜß]/', $ort_firma ?? "")) {
					$error_lines[$line][$spaltennummern["ort"]["nr"]] = 'warning';
					warning("In Zeile ".print_line_link($line)." beinhaltet der Firmenort keine Buchstaben");
				}

				$material_wartung = get_spalte("material_wartung", $spaltennummern, $anlage);

				$firma_name = get_spalte("firma_name", $spaltennummern, $anlage);

				$anlage_kommentar = get_spalte("anlage_kommentar", $spaltennummern, $anlage);
				$anlage_name = get_spalte("anlage_name", $spaltennummern, $anlage);

				if(!$anlage_name) {
					$anlage_name = "Anlage ohne Namen Nr. $anlage_ohne_name_nr";
					$anlage_ohne_name_nr = $anlage_ohne_name_nr + 1;
					$data[$line][$spaltennummern["anlage_name"]["nr"]] = $anlage_name;
					$error_lines[$line][$spaltennummern["anlage_name"]["nr"]] = 'corrected';
				}

				$zeit_pro_wartung = get_spalte("zeit_pro_wartung", $spaltennummern, $anlage);

				if(!preg_match('/^\d+(?:[,\.]\d+)?$/', $zeit_pro_wartung ?? "")) {
					$error_lines[$line][$spaltennummern["zeit_pro_wartung"]["nr"]] = 'warning';
					$zeit_pro_wartung = null;
					warning("In Zeile ".print_line_link($line)." ist die Zeit pro Wartung keine valide Zahl");
				}

				$wartungspauschale = get_spalte("wartungspauschale", $spaltennummern, $anlage);
				$wartungspauschale = preg_replace("/\s*/", "", $wartungspauschale ?? "");
				$wartungspauschale = preg_replace("/€/", "", $wartungspauschale ?? "");
				if(preg_match("/^(\d+\.\d+)*,(\d+)/", $wartungspauschale ?? "", $match)) {
					$match[1] = preg_replace("/\./", "", $match[1]);
					$wartungspauschale = "$match[1].$match[2]";
				}

				if(!preg_match('/^\d+(?:[,\.]\d+)?$/', $wartungspauschale ?? "")) {
					$error_lines[$line][$spaltennummern["wartungspauschale"]["nr"]] = 'warning';
					$wartungspauschale = null;
					warning("In Zeile ".print_line_link($line)." ist die Wartungspauschale keine valide Zahl");
				}


				#$kein_vertrag = get_spalte("kein_vertrag", $spaltennummern, $anlage);
				#$tage_zwischen_wartungen = (365 / ($anzahl_wartungen_pro_jahr + 3));# get_spalte("tage_zwischen_wartungen", $spaltennummern, $anlage);
				#ceil($tage_zwischen_wartungen / 31);

				$letzte_wartung = fucked_up_date_to_real_date($anlage[$spaltennummern["letzte_wartung"]["nr"]], $is_csv); ### TODO geht nicht
				$naechste_wartung = fucked_up_date_to_real_date($anlage[$spaltennummern["naechste_wartung"]["nr"]], $is_csv);

				if(!preg_match('/^\d{4}-\d\d-\d\d$/', $letzte_wartung ?? "")) {
					$letzte_wartung = null;
					$error_lines[$line][$spaltennummern["letzte_wartung"]["nr"]] = 'warning';
				}

				if(!preg_match('/^\d{4}-\d\d-\d\d$/', $naechste_wartung ?? "")) {
					$naechste_wartung = null;
					$error_lines[$line][$spaltennummern["naechste_wartung"]["nr"]] = 'warning';
				}

				$anzahl_wartungen_pro_jahr = get_spalte("anzahl_wartungen_pro_jahr", $spaltennummern, $anlage);
				$monate_laut_anzahl_wartungen_pro_jahr = 0;
				if(is_valid_number($anzahl_wartungen_pro_jahr)) {
					$monate_laut_anzahl_wartungen_pro_jahr = 12 / $anzahl_wartungen_pro_jahr;
					if($monate_laut_anzahl_wartungen_pro_jahr > 0 && $monate_laut_anzahl_wartungen_pro_jahr < 1) {
						$wartungen_pro_monat = ceil(1 / ($monate_laut_anzahl_wartungen_pro_jahr));
						$monate_laut_anzahl_wartungen_pro_jahr = 1;
					}
				} else {
					$error_lines[$line][$spaltennummern["anzahl_wartungen_pro_jahr"]["nr"]] = 'warning';
				}

				if($letzte_wartung == $naechste_wartung && $monate_laut_anzahl_wartungen_pro_jahr > 0) {
					$letzte_wartung = null;
				}

				if(!$letzte_wartung && $naechste_wartung && $monate_laut_anzahl_wartungen_pro_jahr > 0) {
					$erste_wartung_status = 2;
					$letzte_wartung = $naechste_wartung;

					$naechste_wartung_date = new Datetime($letzte_wartung);
					$naechste_wartung_date->modify("+$monate_laut_anzahl_wartungen_pro_jahr month");
					$naechste_wartung = $naechste_wartung_date->format('Y-m-d');
					#dier("$letzte_wartung + $monate_laut_anzahl_wartungen_pro_jahr monate = $naechste_wartung");

					$data[$line][$spaltennummern["naechste_wartung"]["nr"]] = $naechste_wartung;
					$error_lines[$line][$spaltennummern["naechste_wartung"]["nr"]] = 'corrected';

					$data[$line][$spaltennummern["letzte_wartung"]["nr"]] = $letzte_wartung;
					$error_lines[$line][$spaltennummern["letzte_wartung"]["nr"]] = 'corrected';
				}

				#$data[$line][$spaltennummern["letzte_wartung"]["nr"]] = $letzte_wartung;
				#$error_lines[$line][$spaltennummern["letzte_wartung"]["nr"]] = 'corrected';

				#$data[$line][$spaltennummern["naechste_wartung"]["nr"]] = $naechste_wartung;
				#$error_lines[$line][$spaltennummern["naechste_wartung"]["nr"]] = 'corrected';

				$strasse_unbearbeitet = get_spalte("strasse", $spaltennummern, $anlage);
				$strasse = $strasse_unbearbeitet;
				$hausnummer = '';

				if(preg_match('/^(.*?)\s*(\d+[\/\s\da-zA-ZäöüßÖÄÜá\.-]*)$/', $strasse_unbearbeitet ?? "", $matches)) {
					$strasse = $matches[1];
					$hausnummer = $matches[2];
				} else if(preg_match('/^[\d\.\w\s]+$/', $strasse_unbearbeitet ?? "")) {
					$strasse = $strasse_unbearbeitet;
					$error_lines[$line][$spaltennummern["strasse"]["nr"]] = 'warning';
					warning("In Zeile ".print_line_link($line)." scheint die Hausnummer zu fehlen");
				} else {
					$error_lines[$line][$spaltennummern["strasse"]["nr"]] = 'warning';
					warning("In Zeile ".print_line_link($line)." fehlt ein plausibler Straßenname");
				}

				$plz_unbearbeitet = get_spalte("plz", $spaltennummern, $anlage);
				$plz = $plz_unbearbeitet;
				$land = '';
				if(preg_match('/([a-zA-Z]+)?(?:\s*|-)*([\d-]{4,6}?)$/', $plz ?? "", $matches)) {
					$land = $matches[1];
					if($land == "D") {
						$land = "Deutschland";
					} else if ($land == "F") {
						$land = "Frankreich";
					} else if ($land == "PL") {
						$land = "Polen";
					} else {
						$land = "Deutschland";
					}
					$plz = $matches[2];
					$plz = preg_replace('/^-/', '', $plz);
				} else if (preg_match('/^\d{5}$/', $plz_unbearbeitet ?? "")) {
					$land = "Deutschland";
					$plz = $plz_unbearbeitet;
				} else if (preg_match('/^[a-zA-Z]$/', $plz_unbearbeitet ?? "") || preg_match('/^\d{1,4}$/', $plz_unbearbeitet ?? "") || !empty($plz_unbearbeitet ?? "")) {
					$land = "Deutschland";
					$plz = $plz_unbearbeitet;
					$error_lines[$line][$spaltennummern["plz"]["nr"]] = 'warning';
					warning("Die PLZ in Zeile $line ist nicht plausibel (".fq($plz).")");
				} else {
					$error_lines[$line][$spaltennummern["plz"]["nr"]] = 'warning';
					warning("In Zeile ".print_line_link($line)." fehlt die PLZ");
				}

				$ort_anlage = get_spalte("ort_anlage", $spaltennummern, $anlage);
				if(empty($ort_anlage)) {
					$error_lines[$line][$spaltennummern["ort_anlage"]["nr"]] = 'warning';
					warning("In Zeile ".print_line_link($line)." fehlt der Ort");
				}

				### ASP 1
				$ansprechpartner_email = get_spalte("ansprechpartner_email", $spaltennummern, $anlage);
				$ansprechpartner_name = get_spalte("ansprechpartner_name", $spaltennummern, $anlage);
				if(!$ansprechpartner_name) {
					$error_lines[$line][$spaltennummern["ansprechpartner_name"]["nr"]] = 'warning';
				}
				$ansprechpartner_telnr = get_spalte("ansprechpartner_telnr", $spaltennummern, $anlage);
				$ansprechpartner_mobil = get_spalte("ansprechpartner_mobil", $spaltennummern, $anlage);
				$ansprechpartner_telnr = preg_replace('/\s*/', '', $ansprechpartner_telnr ?? "");
				$ansprechpartner_telnr = preg_replace('/\//', '', $ansprechpartner_telnr ?? "");
				$ansprechpartner_telnr = preg_replace('/Tel\.:\s*/', '', $ansprechpartner_telnr ?? "");

				if(!preg_match('/^[\(\),;0-9\s\/–+-]{3,}$/', $ansprechpartner_telnr ?? "")) {
					$error_lines[$line][$spaltennummern["ansprechpartner_telnr"]["nr"]] = 'warning';
					warning("In Zeile ".print_line_link($line)." scheint irgendwas mit der Telefonnummer vom ASP1 nicht zu stimmen");
				}

				if($ansprechpartner_telnr && $ansprechpartner_mobil) {
					$ansprechpartner_telnr = "Fest: $ansprechpartner_telnr, Mobil: $ansprechpartner_mobil";
				} else if (!$ansprechpartner_telnr && $ansprechpartner_mobil) {
					$ansprechpartner_telnr = "Mobil: $ansprechpartner_mobil";
				}

				$ansprechpartner_id = get_or_create_ansprechpartner($ansprechpartner_email, $ansprechpartner_telnr, $ansprechpartner_name);

				### ASP 2
				$ansprechpartner2_email = get_spalte("ansprechpartner2_email", $spaltennummern, $anlage);
				$ansprechpartner2_name = get_spalte("ansprechpartner2_name", $spaltennummern, $anlage);
				$ansprechpartner2_mobil = get_spalte("ansprechpartner2_mobil", $spaltennummern, $anlage);
				if(!$ansprechpartner2_name) {
					$error_lines[$line][$spaltennummern["ansprechpartner2_name"]["nr"]] = 'warning';
				}
				$ansprechpartner2_telnr = get_spalte("ansprechpartner2_telnr", $spaltennummern, $anlage);
				$ansprechpartner2_telnr = preg_replace('/\s*/', '', $ansprechpartner2_telnr ?? "");
				$ansprechpartner2_telnr = preg_replace('/\//', '', $ansprechpartner2_telnr ?? "");
				$ansprechpartner2_telnr = preg_replace('/Tel\.:\s*/', '', $ansprechpartner2_telnr ?? "");

				if(!preg_match('/^[\(\),;0-9\s\/–+-]{3,}$/', $ansprechpartner2_telnr ?? "")) {
					$error_lines[$line][$spaltennummern["ansprechpartner2_telnr"]["nr"]] = 'warning';
					warning("In Zeile ".print_line_link($line)." scheint irgendwas mit der Telefonnummer vom ASP2 nicht zu stimmen");
				}

				if($ansprechpartner2_telnr && $ansprechpartner2_mobil) {
					$ansprechpartner2_telnr = "Fest: $ansprechpartner2_telnr, Mobil: $ansprechpartner2_mobil";
				} else if (!$ansprechpartner2_telnr && $ansprechpartner2_mobil) {
					$ansprechpartner2_telnr = "Mobil: $ansprechpartner2_mobil";
				}

				$ansprechpartner2_id = get_or_create_ansprechpartner($ansprechpartner2_email, $ansprechpartner2_telnr, $ansprechpartner2_name);

				$rechnungsnummer = get_spalte("rechnungsnummer", $spaltennummern, $anlage);
				$erste_wartung = get_spalte("erste_wartung", $spaltennummern, $anlage);

				$months_between_wartungen = months_between_two_dates($letzte_wartung, $naechste_wartung);
				$turnus_id = null;

				if($months_between_wartungen) {
					$turnus_id = get_or_create_turnus_by_anzahl_monate($months_between_wartungen, $wartungen_pro_monat);
				} else if($monate_laut_anzahl_wartungen_pro_jahr > 0) {
					$error_lines[$line][$spaltennummern["letzte_wartung"]["nr"]] = 'warning';
					$error_lines[$line][$spaltennummern["naechste_wartung"]["nr"]] = 'warning';
					$turnus_id = get_or_create_turnus_by_anzahl_monate($monate_laut_anzahl_wartungen_pro_jahr, $wartungen_pro_monat);
				} else {
					error("Für die Anlage ".fq($anlage_name)." konnte keine Anzahl von Monaten zwischen den Wartungen gefunden werden (Zeile ".print_line_link($line).")");
					$error_lines[$line][$spaltennummern["letzte_wartung"]["nr"]] = 'warning';
					$error_lines[$line][$spaltennummern["naechste_wartung"]["nr"]] = 'warning';
				}

				$anlage_id = null;
				$kunde_id = null;

				if($firma_name) {
					$kunde_id = create_kunde($firma_name, null, null, null, null, 0, 0, 0, null, null, $land, null, null);
					if($kunde_id) {
						$anlage_id = create_anlage($kunde_id, $anlage_name, $turnus_id, null, $letzte_wartung, null, $anlage_kommentar, $erste_wartung_status, $wartungspauschale, $ansprechpartner_id, $zeit_pro_wartung, $plz, $ort_anlage, $strasse, $hausnummer, $land, $material_wartung, $ansprechpartner2_id, $rechnungsnummer, $erste_wartung);

						$data[$line][$spaltennummern["firma_name"]["nr"]] .= "<br><span class='info_td'>(->kunde_id:&nbsp;$kunde_id)</span>";
						$data[$line][$spaltennummern["anlage_name"]["nr"]] .= "<br><span class='info_td'>(->anlage_id:&nbsp;".link_anlage($anlage_id).")</span>";

						if(is_null($turnus_id)) {
							if($months_between_wartungen == 0) {
								error("Konnte keine Turnus-ID für die Anlage &raquo;".htmle($anlage_name)."&laquo; erstellen (Zeile ".print_line_link($line).")");
								$error_lines[$line][$spaltennummern["letzte_wartung"]["nr"]] = 'warning';
								$error_lines[$line][$spaltennummern["naechste_wartung"]["nr"]] = 'warning';
							} else {
								error("Konnte keine Turnus-ID erstellen (Zeile ".print_line_link($line).")");
								$error_lines[$line][$spaltennummern["letzte_wartung"]["nr"]] = 'warning';
								$error_lines[$line][$spaltennummern["naechste_wartung"]["nr"]] = 'warning';
							}
						}
					} else {
						error("Konnte Kunde ".fq($firma_name)." nicht erstellen");
					}
				} else {
					if(empty($firma_name)) {
						error("Konnte Kunde nicht erstellen, weil der Name fehlt");
						$error_lines[$line][$spaltennummern["firma_name"]["nr"]] = 'error'; # $firma_name
					} else {
						error("Konnte Kunde nicht erstellen");
					}
				}

				$status[$line]['something_failed'] = 0;
				if(is_null($kunde_id)) {
					$status[$line]['kunde'] = "<span style='color: red;'>&#x2717;</span>";
					$failed_kunden = $failed_kunden + 1;
					$status[$line]['something_failed'] = 1;
				} else {
					$status[$line]['kunde'] = "<span style='color: green;'>&#9989;</span>";
					$ok_kunden = $ok_kunden + 1;
				}

				if(is_null($anlage_id)) {
					$status[$line]['anlage'] = "<span style='color: red;'>&#x2717;</span>";
					$failed_anlagen = $failed_anlagen + 1;
					$status[$line]['something_failed'] = 1;
				} else {
					$status[$line]['anlage'] = "<span style='color: green;'>&#9989;</span>";
					$ok_anlagen = $ok_anlagen + 1;
					if($turnus_id) {
						$ok_anlagen_mit_turnus = $ok_anlagen_mit_turnus + 1;
						if($letzte_wartung) {
							$anzeige_tabelle = $anzeige_tabelle + 1;
						}
					}
				}
				$line++;
			}

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

			$GLOBALS['import_table'] .= array2Table($data, $status, $error_lines);

			$GLOBALS['import_table'] .= "<br>Anzahl OKer Kundenimporte: $ok_kunden<br>";
			$GLOBALS['import_table'] .= "<br>Anzahl fehlgeschlagener Kundenimporte: $failed_kunden<br>";

			$GLOBALS['import_table'] .= "<br>Anzahl OKer Anlagenimporte: $ok_anlagen<br>";
			$GLOBALS['import_table'] .= "<br>Anzahl fehlgeschlagener Anlagenimporte: $failed_anlagen<br>";

			$failed_anlagen_mit_turnus = $ok_anlagen - $ok_anlagen_mit_turnus ;
			$GLOBALS['import_table'] .= "<br>Anzahl OKer Anlagenimporte mit Turnus: $ok_anlagen_mit_turnus<br>";
			$GLOBALS['import_table'] .= "<br>Anzahl OKer Anlagenimporte ohne Turnus: $failed_anlagen_mit_turnus<br>";

			$GLOBALS['import_table'] .= "<br>Anzahl Anlagen die in der Tabelle angezeigt werden sollten: $anzeige_tabelle<br>";
		}
		return $error_occured;
	}

	if(get_post('start_import')) {
		import_csv();
	}
?>
