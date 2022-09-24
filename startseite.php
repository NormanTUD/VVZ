<?php
	include_once("kundenkram.php");
	$php_start = microtime(true);
	$filename = "startseite.php";

	include_once("config.php");
	include("startseite_functions.php");
	include_once("functions.php");

	include_once("selftest.php");

	if(get_kunden_db_name() == "startpage") {
?>
		<title>Vorlesungsverzeichnisse</title>
		<meta name="description" content="Online-Vorlesungsverzeichnisse als Service" />
<?php
		css(array(
			"jquery-ui.css",
			"style.css",
			"startseite.css",
			"bootstrap-tour-standalone.css",
			"jquery-ui-timepicker-addon.css",
			"custom.php",
			"font-awesome.min.css"
		));
?>

		<!-- Container element -->
		<div class="parallax">
<?php
			$query = "select urlname, universitaet, plan_id, external_url, id from vvz_global.kundendaten where urlname is not null and (external_url is not null or id in (select kunde_id from vvz_global.logos)) order by external_url desc, urlname asc";
			$result = rquery($query);
?>
			<div class="bgimg-1">
				<div class="caption">
					<span class="border">
						Vorlesungsverzeichnis<br>
						&nbsp;<a target="_blank" href='?new_demo_uni=1'><button>Kostenlose Demo ohne Verpflichtungen ausprobieren</button></a>
					</span>
				</div>
			</div>
<?php
			$page_str = "<center><img src='default_logo.png'></center><br>";
			$page_str .= "omni-concept ist Ihr kreativer Ansprechpartner, wenn es um innovative Lösungen im Bereich Verwaltungsautomatisierung, Internet, Datenbanken und Printdesign geht. ";
			$page_str .= "omni-concept wurde Anfang 2000 gegründet. ";

			$page_str .= "<center><h2>Das Vorlesungsverzeichnis:</h2></center>";
			$page_str .= "Sind Sie auf der Suche nach einer Software, die sowohl für Sie als auch Ihre Studierenden zeitsparend ist? Möchten Sie nicht mehr manuell Excel-Dateien herumsenden, um Vorlesungen und Prüfung zu verwalten die man dann mühsam zusammenfügen muss? Würden Sie gerne die angebotenen Prüfungen an das Prüfungsamt melden, diese fordern es im Excel-Format?<br>";
			$page_str .= "<br>";
			$page_str .= "Auch der Raumplanungsprozess und die Abstimmungen mit den Raumplanungsdezernaten ist hier abgebildet und einfach, aus dem Browser heraus, möglich.<br>";
			$page_str .= "Das Vorlesungsverzeichnis entstand als studentisches Projekt an der philosophischen Fakultät der TU Dresden. Es läuft dort seit 2017 und ist durchgehend in der Praxis erprobt.<br>";
			$page_str .= "<br>";
			$page_str .= "<i>Für spezielle Fälle vorgesorgt</i>: Es gibt etliche schwer vorhersehbare Fälle im Uni-Alltag, z.B. wenn ein Dozent plötzlich einen Gastdozent verwalten muss. Für solche Spezialfälle ist vorgesorgt: dafür gibt es den Account-Typ Superdozent. Dieser darf seine eigenen Veranstaltungen editieren, aber auch die von ausgewählten Dozenten. Wir haben seit 2017 für jeden Spezialfall, der in der Praxis an der TU Dresden aufgetreten ist, eine technische Lösung gefunden.<br>";
			$page_str .= "<br>";
			$page_str .= "In der <b>Pro</b>-Version gibt es ein Tool, das Ihren Studenten hilft, ihren Stundenplan zu erstellen. Man gibt Studiengang und Semester an und kriegt eine exakte Auflistung aller Veranstaltungen, die für einen infragekommen. Das reduziert die Support-Anfragen von Studierenden erheblich.<br>";
			$page_str .= "<br>";
			$page_str .= "Dadurch, dass alle nur genau das sehen, was sie interessiert, finden auch alle schneller die Informationen, die sie suchen. Ob Räume, Videocall-Links, oder Informationen. Alles ist da, wo es Sinn macht, und sonst versteckt, so dass man nie überladen wird mit Informationen. Resultat: jeder bekommt genau die Information, die er sucht, undzwar zentral und live.<br>";
			$page_str .= "<br>";
			$page_str .= "Unser Vorlesungsverzeichnis bietet Ihnen diese Möglichkeit. Hier werden zentral alle Dozenten, Prüfungen, Studiengänge, Termine, Vorlesungen, Übungen und sonstige Veranstaltengen gespeichert.<br>";
			$page_str .= "<br>";
			$page_str .= "Man muss nie mehr Informationen zwei Mal eingeben. Alles, was gespeichert werden muss, wird zentral an einer Stelle gespeichert. Wenn jemand heiratet, reicht es aus, seinen Namen in einer Zeile zu ändern, dann ist es überall anders.<br>";
			$page_str .= "<br>";
			$page_str .= "Das Beste: die Dozenten verwalten sich selbst. Jeder Dozent kann sehr einfach seine Veranstaltungen erstellen und bearbeiten, und alle Änderungen sind sofort live.<br>";
			$page_str .= "Und das allerbeste? Sie können in weniger als 2 Minuten loslegen.<br>";
			$page_str .= "<br>";
			$page_str .= '<a target="_blank" href="?new_demo_uni=1"><button>Kostenlose Demo ohne Verpflichtungen ausprobieren</button></a>';


			$page_str .= "<center><h2>Aktuelle Kunden:</h2></center>";
			$page_str .= "<ul class='side_by_side list_style_none display_inline'>";
			$str_contents = "";
			while ($row = mysqli_fetch_row($result)) {
				$urlname = $row[0];
				$uniname = $row[1];
				$plan_id = $row[2];
				$kunde_id = $row[4];
				$external_url = $row[3];
				$plan_name = get_plan_name_by_id($plan_id);

				if($external_url) {
					$urlname = $external_url;
				} else {
					$urlname = "/v/$urlname";
				}

				if($plan_name != "Demo") {
					if($external_url) {
						$desc = "<img height=100 src='tudlogo.svg' />";
					} else {
						$desc = "<img height=100 src='logo.php?kunde_id=".htmlentities($kunde_id)."' />";
					}

					$str_contents .= "<li class='display_inline'><a target='_blank' href='$urlname/'>$desc</a></li>";
				}
			}

			if($str_contents) {
				print '<div class="startseite_div_content"><div class="startseite_div">';
				print $page_str;
				print $str_contents;
				print "</ul>";
				print "</div></div>";
			}
?>
			<div class="bgimg-2">
				<div class="startseite_div_content"><div class="startseite_div">
					<center>
						<h2>Übersicht:</h2>
					</center>
					<center>
					<table>
						<tr>
							<th></th>
							<th>Demo</th>
							<th>Basic Faculty</th>
							<th>Basic University</th>
							<th>Pro Faculty</th>
							<th>Pro University</th>
						</tr>
						<tr>
							<td>Einheitliche Verwaltung von Dozenten, Vorlesungen und Prüfungsleistungen</td>
							<td><?php print_checkbox_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
						</tr>
						<tr>
							<td>Weniger Fragen von Studenten, weil alle nötigen Infos zentralisiert sind</td>
							<td><?php print_checkbox_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
						</tr>

						<tr>
							<td>Automatische Dokumentenerstellung für Prüfungsämter</td>
							<td><?php print_checkbox_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
						</tr>
						<tr>
							<td>Rollenverwaltung (Dozent, Administrator, Raumplaner, ...)</td>
							<td><?php print_checkbox_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
						</tr>
						<tr>
							<td>Funktioniert auch auf älteren Rechnern oder Smartphones</td>
							<td><?php print_checkbox_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
						</tr>
						<tr>
							<td>Dozenten können ihre Veranstaltungen selbst eintragen und verwalten</td>
							<td><?php print_checkbox_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
						</tr>
						<tr>
							<td>Einheitliche Raumplanung</td>
							<td><?php print_checkbox_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
						</tr>
						<tr>
							<td>FAQ-Unterseite, um häufige Studentenfragen schneller zu beantworten</td>
							<td><?php print_checkbox_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
						</tr>
						<tr>
							<td>Ihre eigene URL der Form <?php print htmlentities($_SERVER['HTTP_HOST'] ?? ""); ?>/v/name_ihrer_uni</td>
							<td><?php print_red_cross_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
						</tr>
						<tr>
							<td>Unterstützung bei technischen Problemen</td>
							<td><?php print_red_cross_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
						</tr>
						<tr>
							<td>Automatische tägliche Backups</td>
							<td><?php print_red_cross_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
						</tr>
						<tr>
							<td>Monatlich Änderungswünsche bis zum Wert von 1000 € inkludiert</td>
							<td><?php print_red_cross_symbol(); ?></td>
							<td><?php print_red_cross_symbol(); ?></td>
							<td><?php print_red_cross_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
						</tr>

						<tr>
							<td>JSON-API</td>
							<td><?php print_checkbox_symbol(); ?></td>
							<td><?php print_red_cross_symbol(); ?></td>
							<td><?php print_red_cross_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
						</tr>
						<tr>
							<td>Halbautomatischer Stundenplanersteller für Studenten</td>
							<td><?php print_checkbox_symbol(); ?></td>
							<td><?php print_red_cross_symbol(); ?></td>
							<td><?php print_red_cross_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
						</tr>
						<tr>
							<td>Priorisierter Support</td>
							<td><?php print_red_cross_symbol(); ?></td>
							<td><?php print_red_cross_symbol(); ?></td>
							<td><?php print_red_cross_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
							<td><?php print_checkbox_symbol(); ?></td>
						</tr>
						<tr>
							<td>Einrichtungsgebühr</td>
							<td>0 €</td>
							<td>0 €</td>
							<td>0 €</td>
							<td>0 €</td>
							<td>0 €</td>
						</tr>
						<tr>
							<td>Anzahl Institute</td>
							<td>Keine Grenze</td>
							<td>1*</td>
							<td>Keine Grenze</td>
							<td>1*</td>
							<td>Keine Grenze</td>
						</tr>
						<tr>
							<td>Preis pro Monat</td>
							<td rowspan=2>7-Tage-Demo kostenlos</td>
							<td><?php print get_plan_price_by_name("basic_faculty")[0]; ?> €</td>
							<td><?php print get_plan_price_by_name("basic_university")[0]; ?> €</td>
							<td><?php print get_plan_price_by_name("pro_faculty")[0]; ?> €</td>
							<td><?php print get_plan_price_by_name("pro_university")[0]; ?> €</td>
						</tr>
						<tr>
							<td>Preis pro Semester</td>
							<td><?php print get_plan_price_by_name("basic_faculty")[1]; ?> €</td>
							<td><?php print get_plan_price_by_name("basic_university")[1]; ?> €</td>
							<td><?php print get_plan_price_by_name("pro_faculty")[1]; ?> €</td>
							<td><?php print get_plan_price_by_name("pro_university")[1]; ?> €</td>
						</tr>
						<tr>
							<td>Plan wählen</td>
							<td><a target="_blank" href="?new_demo_uni=1"><button>Demo ausprobieren</button></a></td>
							<td>A</td>
							<td>B</td>
							<td>C</td>
							<td>D</td>
						</tr>
					</table>

					* Es können weitere Lizenzen für Institute dazugebucht werden. Schreiben Sie uns dazu einfach eine Email.
					</center>
				</div></div>
			</div>

			
			<div class="footer_link"><a target="_blank" href="http://www.omni-concept.com/v1024/Pages/impressum.htm">Impressum</a> | <a target="_blank" href="http://www.omni-concept.com/v1024/Pages/datenschutz.htm">Datenschutzerklärung</a></div>
		</div> 
<?php

		exit(0);
	} else {
		if($GLOBALS["db_freshly_created"]) {
			print "<script nonce='".nonce()."'>window.location.reload();</script>";
			exit(0);
		}

		if(get_get("initialdatensatz") && is_demo()) {
			include("initialdatensatz.php");

			print "Die Daten werden eingetragen, das kann einige Sekunden dauern. Bitte warten...";
			flush();
			print '<meta http-equiv="refresh" content="1; url=startseite" />';
			flush();

			exit(0);

		}
	}

	$GLOBALS['metadata_shown'] = 0;

	$page_title = "Vorlesungsverzeichnis ".$GLOBALS['university_name'];
	$filename = 'startseite';
	include("header.php");

	$GLOBALS['linkicon'] = '<i class="fa float-right"><img alt="Link zum Studiengang" src="icon.svg" /></i>';


	$GLOBALS['this_semester'] = null;

	if(get_get("semester") && preg_match('/^\d+$/', get_get('semester'))) {
		$GLOBALS['this_semester'] = get_semester(get_get('semester'), 0);
	}

	if(!isset($GLOBALS['this_semester'])) {
		$GLOBALS['this_semester'] = get_this_semester();
		if(!$GLOBALS['this_semester']) {
			$GLOBALS['this_semester'] = get_and_create_this_semester(1);
			if(!$GLOBALS['this_semester']) {
				if(table_exists($GLOBALS["dbname"], "semester")) {
					$valid_semesters = create_semester_array(1, 1, array(get_get('semester')));
					if(is_array($valid_semesters) && count($valid_semesters)) {
						$GLOBALS['this_semester'] = $valid_semesters[1];
					} else {
						die("Es existieren keine validen, eingetragenen Semester.");
					}
				}
			}
		}
	}

	$GLOBALS['shown_etwa'] = 0;

	$GLOBALS['institute'] = table_exists($GLOBALS["dbname"], "institut") ? create_institute_array($GLOBALS["this_semester"][0]) : Array();

	$GLOBALS['this_institut'] = null;

	if(get_get("institut") && preg_match('/^\d+$/', get_get('institut'))) {
		$GLOBALS['this_institut'] = get_get('institut');
	} else {
		if(!$GLOBALS['this_institut']) {
			if(count($GLOBALS['institute'])) {
				if(array_key_exists(0, $GLOBALS['institute']) && array_key_exists(0, $GLOBALS['institute'][0])) {
					$GLOBALS['this_institut'] = $GLOBALS['institute'][0][0];
				}
				if(!$GLOBALS['this_institut']) {
					$GLOBALS['this_institut'] = key($institute);
				}
			} else {
				if(table_exists($GLOBALS["dbname"], "institut")) {
					#die("Es konnten keine Institute gefunden werden. Ohne eingetragene Institute kann die Software nicht benutzt werden. Bitte kontaktieren Sie die Administratoren über die Kontaktseite.");
				}
			}
		}
	}
?>
	<div id="mainindex" <?php if($GLOBALS['show_comic_sans']) { print ' class="bgaf"'; } ?>>
	<a href="startseite?semester=<?php print isset($GLOBALS['this_semester'][0]) ? htmlentities($GLOBALS['this_semester'][0]) : ''; ?>&institut=<?php print isset($GLOBALS['this_institut']) ? htmlentities($GLOBALS['this_institut']) : ''; ?>" border="0"><?php print_uni_logo(); ?></a>
		<div class="iframewarning red_giant"></div>

<?php
		print get_demo_expiry_time();
		logged_in_stuff();

		show_header_startseite();


		console_browser_stuff();

		if(get_get('veranstaltung') || (get_get('dozent') && get_get('create_stundenplan'))) {
			generate_stundenplan();
		} else if (get_get('studiengang')) {
?>
			<div class="height_20px"></div>
<?php
			if(get_get('show_pruefungen')) {
				show_pruefungen_fuer_studiengang();
			} else {
				if(!get_get('create_stundenplan')) {
?>
					<script nonce=<?php print($GLOBALS['nonce']); ?> >
						document.onkeypress = function (e) {
							e = e || window.event;

							if(document.activeElement == $("body")[0]) {
								var keycode =  e.keyCode;
								if(keycode >= 97 && keycode <= 122) {
									if($("#filter").css("display") == "none") {
										$("#filter").show();
									}
									$("[name=vltitel]").val($("[name=vltitel]").val() + String.fromCharCode(e.keyCode));
									$("[name=vltitel]").focus();
								}
							}
						};
					</script>
<?php
					show_veranstaltungsuebersicht_header();
				}

				if(get_get('create_stundenplan')) {
					if(get_get('studiengang')) {
						if(!get_get('chosen_semester')) {
							chose_semester();
						} else {
							show_studiengang_semester_ueberblick();
						}
					} else {
						chose_studiengang();
					}
				} else {
					show_veranstaltungen_uebersicht();
				}
			}
		} else if (get_get('stundenplan_to_be_created')) {
?>
			<i class="class_red">Es wurden keine Veranstaltungen ausgewählt. Bitte benutzen Sie den Zurück-Button Ihres Browsers und wählen Sie mindestens eine Veranstaltung aus, um einen Stundenplan zu erstellen.</i>
<?php
		} else if (get_get('alle_pruefungsnummern')) {
			show_alle_pruefungsnummern_daten();
		} else {
			show_studiengaenge_uebersicht();
		}

		include("footer.php");
?>
