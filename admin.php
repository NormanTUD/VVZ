<?php
$php_start = microtime(true);
include_once("config.php");
include_once("mysql.php");
include_once("functions.php");
$filename = 'admin';
$GLOBALS['adminpage'] = 1;
$page_title = $GLOBALS['university_name']." | Administration";
include("header.php");
include_once("selftest.php");

if(get_kunden_db_name() == "startpage") {
	print "Not allowed";
	exit(0);
}

if(!$GLOBALS['logged_in']) {
?>
		<div class="blurbox">
		<div id="main">
			<a href="admin" border="0"><?php print_uni_logo(); ?></a>
			<div id="wrapper" class="text_align_center">
<?php
			print get_demo_expiry_time();
?>
			<div class="login_admin">
<?php
	if($GLOBALS['logged_in_was_tried']) {
		if(get_post('username') || get_post('password')) {
			sleep(5);
?>
						<span class="red_text">Benutzername oder Passwort falsch</span><br />
<?php
		} else {
?>
						<span class="red_text">Benutzername und Passwort dürfen nicht leer sein.</span><br />
<?php
		}
	}
?>
				<form method="post">
					<input type="hidden" name="try_login" value="1" />
					<div class="height_10px"></div>
					<input type="text" name="username" placeholder="Benutzername" />
					<div class="height_10px"></div>
					<input type="password" name="password" placeholder="Passwort" />
					<div class="height_10px"></div>
					<input type="submit" value="Anmelden" />
				</form>
			</div>
			</div>
<?php
	$GLOBALS['end_html'] = 0;
?>
		</body>
	</html>
<?php
} else {
	$chosen_institut = get_get('institut');
	$institute = create_institute_array();
	if(count($institute) == 0) {
		if(user_is_admin($GLOBALS['logged_in_user_id'])) {
			error("Keine Institute vorhanden. <a href='admin?page=".get_page_id_by_filename("institute.php")."'>Legen Sie hier welche an</a>.");
		} else {
			error("Keine Institute vorhanden. Bitten Sie den Administrator, Institute anzulegen.");
		}
	}

	if(!isset($chosen_institut) && isset($GLOBALS['user_institut_id']) && get_institut_name($GLOBALS["user_institut_id"])) {
		$chosen_institut = $GLOBALS['user_institut_id'];
	}

	if (!isset($chosen_institut) && count($institute) == 1) {
		$chosen_institut = $institute;
		foreach ($institute as $key => $value) {
			$chosen_institut = $value[0];
		}
	}

	#die(">>$chosen_institut<<");

	$studiengaenge = create_studiengaenge_array($chosen_institut);
	$zeitraum = create_zeitraum_array();
	if(!count($studiengaenge)) {
		$fehler = "Für das Institut &raquo;".htmlentities(get_institut_name($chosen_institut) ?? "!!! Institutsfehler: es existiert kein Institut !!!")."&laquo; sind noch keine Studiengänge vorhanden. ";
		if(user_is_admin($GLOBALS['logged_in_user_id'])) {
			$fehler .= "<a href='admin?page=".get_page_id_by_filename("studiengang.php")."'>Hier können Sie welche hinzufügen.</a>";
		} else {
			$fehler .= "Bitten Sie einen Administrator, Studiengänge hinzuzufügen.";
		}
		message($fehler);
	}

	$veranstaltungstypen = create_veranstaltungstyp_array();
	if(!count($veranstaltungstypen)) {
		$fehler = "Es sind keine Veranstaltungstypen vorhanden. ";
		if(user_is_admin($GLOBALS['logged_in_user_id'])) {
			$fehler .= "<a href='admin?page=".get_page_id_by_filename("veranstaltungstypen.php")."'>Hier können Sie welche hinzufügen.</a>";
		} else {
			$fehler .= "Bitten Sie einen Administrator, Veranstaltungstypen hinzuzufügen.";
		}
		message($fehler);
	}

	$pruefungstypen = create_pruefungstypen_array();
	if(!count($pruefungstypen)) {
		$fehler = "Es sind keine Prüfungstypen vorhanden. ";
		if(user_is_admin($GLOBALS['logged_in_user_id'])) {
			$fehler .= "<a href='admin?page=".get_page_id_by_filename("pruefungstypen.php")."'>Hier können Sie welche hinzufügen.</a>";
		} else {
			$fehler .= "Bitten Sie einen Administrator, Prüfungstypen hinzuzufügen.";
		}
		message($fehler);
	}

	$bereiche = create_bereiche_array();
	if(!count($bereiche)) {
		$fehler = "Es sind keine Bereiche vorhanden. ";
		if(user_is_admin($GLOBALS['logged_in_user_id'])) {
			$fehler .= "<a href='admin?page=".get_page_id_by_filename("bereiche.php")."'>Hier können Sie welche hinzufügen.</a>";
		} else {
			$fehler .= "Bitten Sie einen Administrator, Module hinzuzufügen.";
		}
		message($fehler);
	}

	$module = create_modul_array();
	if(!count($module)) {
		$fehler = "Es sind keine Module vorhanden. ";
		if(user_is_admin($GLOBALS['logged_in_user_id'])) {
			$fehler .= "<a href='admin?page=".get_page_id_by_filename("modul.php")."'>Hier können Sie welche hinzufügen.</a>";
		} else {
			$fehler .= "Bitten Sie einen Administrator, Module hinzuzufügen.";
		}
		message($fehler);
	}

	$gebaeude = create_gebaeude_array();
	if(!count($gebaeude)) {
		$fehler = "Es sind keine Gebäude vorhanden. ";
		if(user_is_admin($GLOBALS['logged_in_user_id'])) {
			$fehler .= "<a href='admin?page=".get_page_id_by_filename("gebaeude.php")."'>Hier können Sie welche hinzufügen.</a>";
		} else {
			$fehler .= "Bitten Sie einen Administrator, Module hinzuzufügen.";
		}
		message($fehler);
	}

	$pruefungsnummern = create_pruefungsnummern_array();
	if(!count($pruefungsnummern)) {
		$fehler = "Es sind keine Prüfungsnummern vorhanden. ";
		if(user_is_admin($GLOBALS['logged_in_user_id'])) {
			$fehler .= "<a href='admin?page=".get_page_id_by_filename("pruefungsnummern.php")."'>Hier können Sie welche hinzufügen.</a>";
		} else {
			$fehler .= "Bitten Sie einen Administrator, Module hinzuzufügen.";
		}
		message($fehler);
	}

	if(get_get('make_all_foreign_keys_on_delete_cascade') == 1) {
		$datestring = md5(date('Y-m-d H'));
		if (get_get('iamsure') == $datestring) {
			make_all_foreign_keys_on_delete_cascade();
		} else {
			warning("<span class='red_text text_30px'>Lasse make_all_foreign_keys_on_delete_cascade() laufen, wenn der Parameter &iamsure=$datestring eingegeben wird. ICH HOFFE DU HAST EIN BACKUP DER DATENBANK!</span>\n");
		}
	}
	$dozent_name = htmlentities(get_dozent_name($GLOBALS['logged_in_data'][2]));
	if(!user_is_verwalter($GLOBALS['logged_in_user_id'])) {
		if(!preg_match('/\w{1,}/', $dozent_name)) {
			$dozent_name = htmlentities($GLOBALS['logged_in_data'][1]).' <span class="class_red">!!! Ihr Account ist mit keinem Dozenten verknüpft! !!!</span>';
		}
	} else {
		$dozent_name = htmlentities($GLOBALS['logged_in_data'][1]);
	}
	if(!$GLOBALS['user_role_id'][0]) {
		$dozent_name = htmlentities($GLOBALS['logged_in_data'][1]).' <span class="class_red">!!! Ihr Account hat keine ihm zugeordnete Rolle! !!!</span>';
	}
?>
		<div class="blurbox">
		<div id="main">

		<table class="invisiblebg fullwidth">
			<tr class="invisiblebg">
				<td class="invisiblebg">
<?php
	if(!file_exists('/etc/x11test')) {
?>
						<a href="admin" border="0"><?php print_uni_logo(); ?></a>
<?php
	}
?>
				</td>
				<td valign="middle" class="invisiblebg">
					Willkommen, <?php print htmlentities($dozent_name ?? ""); ?>!
					<div class="tooltip"><a class="red_large" href="logout.php">Abmelden <?php print_logout_icon(); ?></a><span class="tooltiptext">Meldet alle angemeldeten Geräte ab</span></div>
				</td>
				<td class="float_right display_inline">
					<div class="ui-widget">
						<input type="text" id="globalsearch" placeholder="Suche" name="search" />
					</div>
				</td>
			</tr>
		</table>
<?php
			if(get_post('password') == 'test' && get_post('try_login') && !db_is_demo($GLOBALS["dbname"], 1)) {
?>
				<script type="text/javascript">
					alert("Bitte ändern Sie Ihr Passwort! Dies können Sie unter dem Menüpunkt 'Eigene Daten ändern' machen. Diese Meldung wird bei jedem Anmelden kommen, solange Sie Ihr Passwort nicht geändert haben.");
				</script>
<?php
			}
			if(!file_exists('/etc/x11test')) {
?>
				 <?php print get_demo_expiry_time(); ?>
<?php
			}
			if($GLOBALS['user_role_id'] == 1) {
				$df = sprintf("%0.2f", disk_free_space($_SERVER['DOCUMENT_ROOT']) / 1024 / 1024 / 1024);
				if($df <= 1) {
					print("<br /><span class='class_red'>Warnung: nur noch $df GB freier Speicher auf der Festplatte!</span>");
				}
			}
?>
			<div class="height_5px"></div>
				<ul class="topnav">
					<li class="menu_item"><a class='menu_link <?php print (get_get('page') || get_get('show_items')) ? '' : 'selected_tab'; ?>' href="admin" ><?php print (get_get('page') || get_get('show_items')) ? '' : '&rarr; '; ?>Willkommen!</a></li>
<?php
					if(count($GLOBALS['pages'])) {
						foreach ($GLOBALS['pages'] as $this_page) {
							# 0	   1	   2		3		    4
							#`name`, `file`, `page_id`, `show_in_navigation`, `parent`
							if($this_page[3]) {
								if($this_page[1]) { # Kein Dropdown
									if(show_in_current_page($this_page[2])) {
										if(!$this_page[4]) {
											if($this_page[2] == get_get('page') || $this_page[2] == get_get('show_items')) {
												print "<li class='selected_tab menu_item'><a class='menu_link' href='admin?page=".$this_page[2]."'>&rarr; $this_page[0]</a></li>\n";
											} else {
												print "<li class='menu_item'><a class='menu_link' href='admin?page=".$this_page[2]."'>$this_page[0]</a></li>\n";
											}
										}
									}
								} else { # Dropdown
									$subnav_data = print_subnavigation($this_page[2]);
									if(show_in_current_page($this_page[2])) {
										if($subnav_data[0]) {
?>
											<li class='selected_tab'><a class='menu_link' href='admin?show_items=<?php print $this_page[2];?>'>&rarr; <?php print $this_page[0]; ?> &darr;</a><?php print $subnav_data[1]; ?></li>
<?php
										} else {
											if($this_page[2] == get_get('page') || $this_page[2] == get_get('show_items')) {
?>
												<li class="dropdown selected_tab menu_item"><a class='menu_link' href='admin?show_items=<?php print $this_page[2];?>'>&rarr; <?php print $this_page[0]; ?> &darr;</a><?php print $subnav_data[1]; ?></li>
<?php
											} else {
?>
												<li class="dropdown menu_item"><a class='menu_link' href='admin?show_items=<?php print $this_page[2];?>'><?php print $this_page[0]; ?> &darr;</a><?php print $subnav_data[1]; ?></li>
<?php
											}
										}
									}
								}
							}
						}
					} else {
						print "<h2 class='class_red'>Fehler beim Holen der Seiten!</h2>";
					}
?>
				</ul>
<?php
			foreach (array(
					array("error", "red"),
					array("right_issue", "red"),
					array("warning", "orange"),
					array("message", "blue"),
					array("easter_egg", "hotpink"),
					array("success", "green")
				) as $msg) {
				show_output($msg[0], $msg[1]);
			}

			if($GLOBALS['accepted_public_data']) {
				$pagenr = get_get('page');
				if(!$pagenr) {
					$pagenr = get_post('page');
				}

				if(!preg_match('/^\d+$/', $pagenr ?? "")) {
					$pagenr = null;
				}

				if(get_get('show_items')) {
					$query = 'SELECT `id`, `name` FROM `page` WHERE `parent` = '.esc(get_get('show_items')).' AND `show_in_navigation` = "1" AND `id` IN (SELECT `page_id` FROM `role_to_page` WHERE `role_id` = '.esc($GLOBALS['user_role_id'][0]).') ';
					$query .= ' ORDER BY `name`';
					$result = rquery($query);

					if(mysqli_num_rows($result)) {
						$subpage_data = array();
						$subpage_ids = array();
						while ($row = mysqli_fetch_row($result)) {
							if($row[1]) {
								$subpage_data[] = array($row[0], $row[1]);
								$subpage_ids[] = $row[0];
							}
						}
						$subpage_texts = get_page_info_by_id($subpage_ids);
						print "<h2>Untermenüs von <i>".get_page_name_by_id(get_get('show_items'))."</i></h2>\n";
						$GLOBALS['submenu_id'] = get_get('show_items');
						include('hinweise.php');
						print "<ul class='submenu_ul'>\n";
						foreach ($subpage_data as $row) {
							if($row[1]) {
								print "<li class='submenu_li margin_5px_0'><a href='admin?page=$row[0]'>$row[1]</a> ".($subpage_texts[$row[0]] ? "&mdash; ".htmlentities($subpage_texts[$row[0]]) : "")."</li>\n";
							}
						}
						print "</ul>\n";
					} else {
						print "<h2 class='class_red'>Der ausgewählte Menüpunkt ist leider nicht im System vorhanden oder Sie haben keine Rechte, auf ihn zuzugreifen.</h2>\n";
					}
				} else {
					if(!isset($pagenr)) {
						include(dirname(__FILE__).'/pages/welcome.php');
					} else {
						$page_file = '';
						if(array_key_exists($pagenr, $GLOBALS['pages'])) {
							$page_file = $GLOBALS['pages'][$pagenr][1];
						} else {
							$page_file = get_page_file_by_id($pagenr);
						}

						$page_file_basename = $page_file;

						$page_file = dirname(__FILE__).'/pages/'.$page_file;

						if(!file_exists($page_file)) {
							print "Die Datei `$page_file_basename` konnte nicht gefunden werden!";
						} else if (!$page_file_basename) {
							print "Die Unterseite konnte in der Datenbank nicht gefunden werden!";
						} else {
							if(check_page_rights($page_file_basename)) {
								if($GLOBALS['deletion_page']) {

									warning("<h2>Sicher, dass das alles gelöscht werden soll?</h2>");
									show_output("warning", "orange");
?>
									

									Um die <a href="https://de.wikipedia.org/wiki/Konsistenz_%28Datenspeicherung%29">Datenintegrität</a> zu gewährleisten, werden
									alle Datensätze, die von dem, der gelöscht werden soll, abhängig sind, auch gelöscht. Dies kann mitunter gewaltige
									Auswirkungen auf das gesamte System haben. Daher soll das Löschen extra bestätigt werden, bevor es ausgeführt wird.

									In den folgenden Tabellen sehen Sie alle Daten, die, mit diesem Datensatz zusammen, gelöscht werden. Am unteren Ende der
									Seite haben Sie die Möglichkeit, das Löschen tatsächlich auszuführen bzw. abzubrechen.
<?php
									if($GLOBALS['deletion_db'] && $GLOBALS['deletion_where']) {
										$db = $GLOBALS['dbname'];
										$ddb = $GLOBALS['deletion_db'];
										$dbw = $GLOBALS['deletion_where'];
										print get_foreign_key_deleted_data_html($db, $ddb, $dbw);
									}
?>
									<form method="post" enctype="multipart/form-data" action="<?php print $_SERVER['HTTP_REFERER']; ?>">
<?php
										foreach ($_POST as $this_post_name => $this_post_value) {
											if(!is_array($this_post_value)) {
?>
												<input type="hidden" name="<?php print htmlentities($this_post_name); ?>" value="<?php print htmlentities($this_post_value); ?>" />
<?php
											} else {
												foreach ($this_post_value as $array_this_post_name => $array_this_post_value) {
?>
													<input type="hidden" name="<?php print htmlentities($this_post_name); ?>[]" value="<?php print htmlentities($array_this_post_value); ?>" />
<?php
												}
											}
										}
?>
										<input type="hidden" name="delete_for_sure" value="1" />
										<input type="submit" value="Ja, ich bin mir sicher!" />
									</form>
									<form>
										<input type="button" value="Nein, lieber nicht." id="neinliebernicht" />
									</form>
<?php
								} else {
									$GLOBALS['this_page_number'] = $pagenr;
									$GLOBALS['this_page_file'] = $page_file;
									include('hinweise.php');
									if(page_disabled_in_demo($pagenr) && is_demo()) {
										print "Diese Seite ist im Demo-Modus deaktiviert.";
									} else {
										include($page_file);
									}
								}
							} else {
								print "<i class='class_red'>Sie haben kein Recht, auf diese Seite zuzugreifen.</i>";
							}
						}
					}
				}
			} else {
?>
				<h3>Datenschutz-/Einwilligungserklärung </h3>

				<p>Hiermit bestätige ich, dass ich <b>freiwillig</b> in die Verarbeitung meiner personenbezogenen Daten:<br />
				1. Titel<br />
				2. Name<br />
				3. Angaben zu Lehrveranstaltungen<br />
				ausschließlich zum Zweck der weltweiten Veröffentlichung in einem Vorlesungsverzeichnis im Internet <b>einwillige</b>.<br />
				Eine darüberhinausgehende Übermittlung von Daten erfolgt nicht, soweit dies nicht anders gesetzlich bestimmt ist.<br />
				Mir ist bekannt, dass ich diese Einwilligung ohne Angabe von Gründen und ohne Rechtsfolgen verweigern oder mit Wirkung für die Zukunft bei unten genannter datenverarbeitenden Stelle widerrufen kann.<br />
				Im Falle der Verweigerung der Einwilligung oder eines Widerrufes kann das Vorlesungsverzeichnis nicht oder nicht mehr genutzt werden.<br /></p>

				<p>Datenverarbeitende Stelle<br />
				<?php print get_university_name(); ?><br />
				<?php print get_kunde_plz(); ?> <?php print get_kunde_ort(); ?><br /></p>

				<p>Kontakt: <?php print get_kunde_name(); ?><br />
				Mail: <?php print get_kunde_email(); ?><br />
<?php
				if(get_get('page') || get_get('show_items')) {
					$id = get_get('page');
					if(!$id) {
						$id = get_get('show_items');
					}
?>
					<p class="red_text">Die Seite &raquo;<?php print get_page_name_by_id($id); ?>&laquo; konnte nicht aufgerufen werden. Bitte stimmen
					Sie zuerst den Datenschutzbedingungen zu.</p>
<?php
				}
?>
				
				<form>
					<input type="hidden" name="page" value="<?php print htmlentities(get_get('page') ?? ""); ?>" />
					<input type="hidden" name="show_items" value="<?php print htmlentities(get_get('show_items') ?? ""); ?>" />
					Ankreuzeln, wenn einverstanden, dann &raquo;Akzeptieren&laquo; drücken! &rarr; <input type="checkbox" name="sdsg_einverstanden" value="1" />
					<input type="submit" value="Akzeptieren" />
				</form>
<?php
			}
?>

			</div>

			<script nonce=<?php print($GLOBALS['nonce']); ?> >
				document.onkeypress = function (e) {
					e = e || window.event;

					if(document.activeElement == $("body")[0]) {
						var keycode =  e.keyCode;
						if(keycode >= 97 && keycode <= 122) {
							$("#globalsearch").val("");
							$("#globalsearch").val($("#globalsearch").val() + String.fromCharCode(e.keyCode));
							$("#globalsearch").focus();
						}
					}
				};
			</script>
<?php
		}
		include("footer.php");
?>
