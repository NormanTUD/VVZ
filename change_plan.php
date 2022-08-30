<?php
	$php_start = microtime(true);
	include_once("config.php");
	$page_title = "Vorlesungsverzeichnis ".$GLOBALS['university_name']." | FAQ";
	$filename = 'startseite';
	include("header.php");
	include_once("startseite_functions.php");
	include_once("kundenkram.php");

	if(!user_is_admin($GLOBALS["logged_in_user_id"])) {
		die("Nur Admins dürfen hier drauf");
	}

?>
	<div id="mainindex">
		<a href="startseite" border="0"><?php print_uni_logo(); ?> </a><br>
			<br>
			<?php logged_in_stuff(); ?>
			<?php print get_demo_expiry_time(); ?>

			<h1>Verfügbare Pläne</h1>
<?php
	if($product = get_get("product")) {
		$kunde_id = get_kunde_id_by_db_name($GLOBALS["dbname"]);
		$kunde_ok = kunde_is_personalized($kunde_id) ? 1 : 0;
		$urlname_exists = 0;
		if(get_post("universitaet")) {
			$urlname = create_uni_name(get_post("universitaet"));
			$urlname_exists = urlname_already_exists($urlname) ? 1 : 0;
		}

		$iban_ok = 0;
		$email_ok = 0;

		if(get_post("iban")) {
			$iban_ok = checkIBAN(get_post("iban"));
		}


		if (filter_var(get_post("email"), FILTER_VALIDATE_EMAIL)) {
			$email_ok = 1;
		}

		$uni_name_error = "";
		$email_error = "";
		$iban_error = "";

		if(!$iban_ok) {
			$iban_error = "Keine oder eine ungültige IBAN eingegeben";
		}

		if(!$email_ok) {
			$email_error = "Keine oder eine ungültige Email eingegeben";
		}

		if(get_post("update_kunde_data")) {
			if(!$urlname_exists) {
				if($email_ok) {
					if($iban_ok) {
						if($kunde_id && get_post("anrede") && get_post("universitaet") && get_post("name") && get_post("strasse") && get_post("plz") && get_post("ort") && get_get("product") && get_post("iban") && get_post("email")) {

							update_kunde($kunde_id, get_post("anrede"), get_post("universitaet"), get_post("name"), get_post("strasse"), get_post("plz"), get_post("ort"), $GLOBALS["dbname"], get_plan_id(get_get("product") ?? "basic_faculty"), get_post("iban"), get_post("email"), get_zahlungszyklus_monate_by_name(get_post("zahlungszyklus_monate")));
						}

						if(get_post("daten_uebernehmen")) {
							// TODO
						}

						$kunde_ok = kunde_is_personalized($kunde_id) ? 1 : 0;
					}
				}
			} else {
				$uni_name_error = "Dieser Name ist bereits belegt. Sie können keine URLs Anderer übernehmen. Bitte wählen Sie einen anderen Namen.";
			}
		}

		#print("urlname_exists: $urlname_exists, kunde_ok: $kunde_ok, iban_ok: $iban_ok, email_ok: $email_ok");

		if((!$urlname_exists && $kunde_ok && $iban_ok && $email_ok) || get_get("done_migration")) {
			if(!get_get("done_migration")) {
				if(!$urlname_exists || (kunde_owns_url($kunde_id, get_url_uni_name()) && $kunde_ok && $email_ok && $iban_ok)) {
					update_kunde_plan($kunde_id, get_plan_id(get_get("product")));
				}
			}

			if(get_get("done_migration")) {
				# Zahlungsinfos, DB speichern
?>
				Ihr Plan wurde geändert. Sie können die Software nun als Vorlesungsverzeichnis für Ihre Universität benutzen.<br>

				Sie können jederzeit die Rechnungen im Administrationsmenü einsehen und als PDF herunterladen.
<?php
			} else {
?>
				Ihr Plan wurde geändert. Sie werden nun auf die neue Seite umgeleitet.
<?php
				$kunde_id = get_kunde_id_by_url();
				$new_url = get_kunde_url_name_by_id($kunde_id);

				print '<meta http-equiv="refresh" content="0; url=/v/'.$new_url.'/change_plan?product='.htmlentities(get_get("product")).'&done_migration=1" />';
				flush();
				exit(0);
			}
		} else {
?>
			<h2>Wir haben es echt so lange wie möglich herausgezögert...</h2>
			<p>Aber ab diesem Punkt brauchen wir Ihre realen Daten</p>
<?php
			$uni_name = get_current_value("universitaet");
?>
			<form method="post" enctype="multipart/form-data" action="change_plan?product=<?php print htmlentities(get_get("product") ?? ""); ?>">
				<input type="hidden" name="update_kunde_data" value=1 />
				<table>
					</tr>
					<tr>
						<td>Ihre Anrede</td><td><input type="text" name="anrede" placeholder="Anrede" value="<?php print htmlentities(get_current_value("anrede") ?? "Sehr geehrte(r) Kunde"); ?>" /></td>
					</tr>
					<tr>
					<td>Universität:</td><td><input type="text" name="universitaet" placeholder="Universität" value="<?php print htmlentities(get_current_value("universitaet") ?? ""); ?>" /><?php
						if($uni_name_error) {
							print "<br><span class='red_text'>$uni_name_error</span>";
						}
?></td>
					</tr>
					<tr>
						<td>Ihr Name:</td><td><input type="text" name="name" placeholder="Ihr Name" value="<?php print htmlentities(get_current_value("name") ?? ""); ?>" /></td>
					</tr>
					<tr>
						<td>Straße, Hausnummer:</td><td><input type="text" name="strasse" placeholder="Straße" value="<?php print htmlentities(get_current_value("strasse") ?? ""); ?>" /></td>
					</tr>
					<tr>
						<td>Postleitzahl:</td><td><input type="text" name="plz" placeholder="Postleitzahl" value="<?php print htmlentities(get_current_value("plz") ?? ""); ?>" /></td>
					</tr>
					<tr>
						<td>Ort:</td><td><input type="text" name="ort" placeholder="Ort" value="<?php print htmlentities(get_current_value("ort") ?? ""); ?>" /></td>
					</tr>
					<tr>
						<td>Email:</td><td><input type="text" name="email" placeholder="Email" value="<?php print htmlentities(get_current_value("email") ?? ""); ?>" /><?php
							if($email_error) {
								print "<br><span class='red_text'>$email_error.</span>";
							}
						?></td>
					</tr>
					<tr>
						<td>IBAN für die Lastschrit:</td><td><input type="text" name="iban" placeholder="IBAN" value="<?php print htmlentities(get_current_value("iban") ?? ""); ?>" /><?php
							if($iban_error) {
									print "<br><span class='red_text'>$iban_error.</span>";
								}
							?></td>
					</tr>

					<tr>
						<td>Zahlungszyklus:</td>
						<td><?php
							$aktueller_zahlungszyklus = get_zahlungszyklus_by_kunde_id($kunde_id);
							$werte = array("Monatlich", "Jährlich");
							create_select($werte, get_zahlungszyklus_name_by_monate($aktueller_zahlungszyklus), 'zahlungszyklus_monate', 0);
						?></td>
					</tr>
					<tr>
						<td>Wenn Sie bereits reale Daten eingegeben haben, wollen Sie diese übernehmen?</td>
						<td><input type="checkbox" name="daten_uebernehmen" value=1 /></td>
					</tr>
				</table>
				<button>Ja, meine Daten sind korrekt</button>
			</form>
<?php
		}
	} else {
		$current_plan = get_kunde_plan();
?>
	<div class="container">
		<div class="plan_div <?php print get_kunde_plan() == "Demo" ? "chosen_plan" : 'possible_plan'; ?>">
			<p>Demo</p>
			<p>7 Tage kostenlose und unkomplizierte Nutzung zum Testen, danach wird der Nutzer gelöscht</p>
<?php
			if($current_plan == "Demo") {
?>
				<p><i>Ihr aktueller Plan</i></p>
<?php
			}
?>
		</div>
		<div class="plan_div <?php print preg_match("/Basic/", get_kunde_plan()) ? "chosen_plan" : 'possible_plan'; ?>">
			<p>Basic</p>
			<p>Erlaubt die Verwaltung von Stundenplänen, Dozenten und Veranstaltung für eine gesamte Fakultät oder Universität</p>
			<p>Eine Fakultät: <a href="?product=basic_faculty"><button class="buy_button"><i><?php print htmlentities(get_plan_price_by_name("basic_faculty")[0]); ?>€/Monat &mdash; oder &mdash; <?php print htmlentities(get_plan_price_by_name("basic_faculty")[1]); ?>€/Jahr</i></button></a></p>
			<p>Ganze Universität: <a href="?product=basic_university"><button class="buy_button"><i><?php print htmlentities(get_plan_price_by_name("basic_university")[0]); ?>€/Monat &mdash; oder &mdash; <?php print htmlentities(get_plan_price_by_name("basic_university")[1]); ?>€/Jahr</i></button></a></p>
		</div>
		<div class="plan_div <?php print preg_match("/Pro/", get_kunde_plan()) ? "chosen_plan" : 'possible_plan'; ?>">
			<p>Pro</p>
			<p>Alles der Basis-Variante, dazu ein halbautomatischer Stundenplanersteller, um die Anzahl der Anfragen im Erstsemester zur Stundenplanung zu reduzieren und Zusatzwünsche</p>
			<p>Eine Fakultät: <a href="?product=pro_faculty"><button class="buy_button"><i><?php print htmlentities(get_plan_price_by_name("pro_faculty")[0]); ?>€/Monat &mdash; oder &mdash; <?php print htmlentities(get_plan_price_by_name("pro_faculty")[0]); ?>€/Jahr</i></button></a></p>
			<p>Ganze Universität: <a href="?product=pro_university"><button class="buy_button"><i><?php print htmlentities(get_plan_price_by_name("pro_university")[0]); ?>€/Monat &mdash; oder &mdash; <?php print htmlentities(get_plan_price_by_name("pro_university")[1]); ?>€/Jahr</i></button></a></p>
		</div>
	</div>

	<h2>Ihre Zufriedenheit steht bei uns an erster Stelle</h2>

	Daher besteht jederzeitiges Kündigungsrecht. "Angebrochene" Monate müssen nicht bezahlt werden und Sie haben 6 Monate Zeit, eine Rechnung zu begleichen.
	Wir werden uns bei Ihnen melden, sollte eine Rechnung nicht bezahlt werden. Antworten Sie darauf nicht, dann haben Sie weiterhin eine Grace Period von 2
	Monaten, bis wir Ihre Installation löschen, wobei für Sie dabei keinerlei weitere Kosten anfallen.

	<h2>Zusatzwünsche?</h2>

	Sollten Sie eine besondere Anforderung haben, werden wir das Vorlesungsverzeichnis an Ihre Anforderungen anpassen. So etwas wären Exporte nach Excel
	oder neue Datenfelder. Dies ist in der Pro-Version bereits beinhaltet. Bei der Basic-Version müssen wir uns über die Kosten im Detail absprechen.
	In der Pro-Version sind Zusatzwünsche bis ca. 1000€ bereits beinhaltet.
<?php
	}

	include("footer.php");
?>
