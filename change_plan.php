<?php
	$php_start = microtime(true);
	include_once("config.php");
	$page_title = "Vorlesungsverzeichnis ".$GLOBALS['university_name']." | FAQ";
	$filename = 'startseite';
	include("header.php");
	include_once("startseite_functions.php");
	include_once("kundenkram.php");
?>
	<div id="mainindex">
		<a href="startseite" border="0"><?php print_uni_logo(); ?> </a>
			<br>
			<?php logged_in_stuff(); ?>
			<?php print get_demo_expiry_time(); ?>

			<h1>Verfügbare Pläne</h1>

			<style>
.container {
  display: flex;
}
.container > div {
  flex: 1; /*grow*/
}
.plan_div {
	border: 1px solid blue;
}

.buy_button {
	background-color: #aaff00;
}

.chosen_plan {
	background-color: #6a8aff;
}

.possible_plan {
	background-color: #fbff00;
}
			</style>

<?php
	if($product = get_get("product")) {
		$kunde_id = get_kunde_id_by_db_name(get_kunden_db_name());
		$kunde_ok = kunde_is_personalized($kunde_id);

		if($kunde_ok) {
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
				$new_uni_name = create_uni_name(get_kunde_university_name());
				$new_db_name = "db_vvz_".$new_uni_name;
				print($new_db_name);

				print '<meta http-equiv="refresh" content="0; url=v/'.$new_uni_name.'/" />';
				flush();
				exit(0);
			}
		} else {
?>
			<h2>Wir haben es echt so lange wie möglich herausgezögert...</h2>
			<p>Aber ab diesem Punkt brauchen wir Ihre realen Daten</p>
			<form method="post" enctype="multipart/form-data" action="change_plan?product=<?php print htmlentities(get_get("product") ?? ""); ?>">
				<input type="hidden" name="update_kunde_data" value=1 />
				<table>
					</tr>
					<tr>
						<td>Anrede</td><td><input type="text" name="anrede" placeholder="Anrede" /></td>
					</tr>
					<tr>
						<td>Universität</td><td><input type="text" name="universitaet" placeholder="Universität" /></td>
					</tr>
					<tr>
						<td>Ihr Name</td><td><input type="text" name="kundename" placeholder="Ihr Name" /></td>
					</tr>
					<tr>
						<td>Straße, Hausnummer</td><td><input type="text" name="kundestrasse" placeholder="Straße" /></td>
					</tr>
					<tr>
						<td>Postleitzahl</td><td><input type="text" name="kundeplz" placeholder="Postleitzahl" /></td>
					</tr>
					<tr>
						<td>Ort</td><td><input type="text" name="kundeort" placeholder="Ort" /></td>
					</tr>
					<tr>
						<td>Name des Vorlesungsverzeichnisses</td><td><input type="text" name="name_vvz" placeholder="Name des Vorlesungsverzeichnisses (z.B. TU Dresden)" /></td>
					</tr>
					<tr>
						<td>Wenn Sie bereits reale Daten eingegeben haben, wollen Sie diese übernehmen?</td>
						<td><input type="checkbox" name="daten_uebernehmen" value=1 /><td>
					</tr>
				</table>
				<button>Ja, meine Daten sind korrekt</button>
			</form>
<?php
		}
	} else {
?>
	<div class="container">
		<div class="plan_div <?php print get_kunde_plan() == "Demo" ? "chosen_plan" : 'possible_plan'; ?>">
			<p>Demo</p>
			<p>7 Tage kostenlose und unkomplizierte Nutzung, danach wird der Nutzer gelöscht</p>
			<p><i>Ihr aktueller Plan</i></p>
		</div>
		<div class="plan_div <?php print get_kunde_plan() == "Basic" ? "chosen_plan" : 'possible_plan'; ?>">
			<p>Basic</p>
			<p>Erlaubt die Verwaltung von Stundenplänen, Dozenten und Veranstaltung für eine gesamte Fakultät oder Universität</p>
			<p>Nur eine Fakultät: <a href="?product=basic_faculty"><button class="buy_button"><i><?php print htmlentities(get_plan_price_by_name("basic_faculty")[0]); ?>€/Monat -- oder -- <?php print htmlentities(get_plan_price_by_name("basic_faculty")[1]); ?>€/Jahr</i></button></a></p>
			<p>Eine ganze Universität: <a href="?product=basic_university"><button class="buy_button"><i><?php print htmlentities(get_plan_price_by_name("basic_faculty")[0]); ?>€/Monat -- oder -- <?php print htmlentities(get_plan_price_by_name("basic_faculty")[1]); ?>€/Jahr</i></button></a></p>
		</div>
		<div class="plan_div <?php print get_kunde_plan() == "Pro" ? "chosen_plan" : 'possible_plan'; ?>">
			<p>Pro</p>
			<p>Alles der Basis-Variante, dazu ein halbautomatischer Stundenplanersteller, um die Anzahl der Anfragen im Erstsemester zur Stundenplanung zu reduzieren und Zusatzwünsche</p>
			<p>Nur eine Fakultät: <a href="?product=pro_university"><button class="buy_button"><i><?php print htmlentities(get_plan_price_by_name("pro_faculty")[0]); ?>€/Monat -- oder -- <?php print htmlentities(get_plan_price_by_name("pro_faculty")[0]); ?>€/Jahr</i></button></a></p>
			<p>Eine gesamte Universität: <a href="?product=pro_university"><button class="buy_button"><i><?php print htmlentities(get_plan_price_by_name("pro_university")[0]); ?>€/Monat -- oder -- <?php print htmlentities(get_plan_price_by_name("pro_university")[1]); ?>€/Jahr</i></button></a></p>
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
