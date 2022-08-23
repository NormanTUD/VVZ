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
			</style>


<div class="container">
	<div class="plan_div">
		<p>Demo</p>
		<p>7 Tage kostenlose und unkomplizierte Nutzung, danach wird der Nutzer gelöscht</p>
		<p><i>Ihr aktueller Plan</i></p>
	</div>
	<div class="plan_div">
		<p>Basic</p>
		<p>Erlaubt die Verwaltung von Stundenplänen, Dozenten und Veranstaltung für eine gesamte Fakultät oder Universität</p>
		<p>Nur eine Fakultät: <a href="?product=basic_faculty"><button class="buy_button"><i>50€/Monat -- oder -- 500€/Jahr</i></button></a></p>
		<p>Eine ganze Universität: <a href="?product=basic_university"><button class="buy_button"><i>80€/Monat -- oder -- 800€/Jahr</i></button></a></p>
	</div>
	<div class="plan_div">
		<p>Pro</p>
		<p>Alles der Basis-Variante, dazu ein halbautomatischer Stundenplanersteller, um die Anzahl der Anfragen im Erstsemester zur Stundenplanung zu reduzieren und Zusatzwünsche</p>
		<p>Nur eine Fakultät: <a href="?product=pro_university"><button class="buy_button"><i>60€/Monat -- oder -- 600€/Jahr</i></button></a></p>
		<p>Eine gesamte Universität: <a href="?product=pro_university"><button class="buy_button"><i>120€/Monat -- oder -- 1200€/Jahr</i></button></a></p>
	</div>
</div>

<h2>Ihre Zufriedenheit steht bei uns an erster Stelle</h2>

Daher besteht jederzeitiges Kündigungsrecht. "Angebrochene" Monate müssen nicht bezahlt werden und Sie haben 6 Monate Zeit, eine Rechnung zu begleichen.
Wir werden uns bei Ihnen melden, sollte eine Rechnung nicht bezahlt werden. Antworten Sie darauf nicht, dann haben Sie weiterhin eine Grace Period von 2
Monaten, bis wir Ihre Installation löschen, für Ihnen keinerlei Kosten anfallen.

<h2>Zusatzwünsche?</h2>

Sollten Sie eine besondere Anforderung haben, werden wir das Vorlesungsverzeichnis an Ihre Anforderungen anpassen. So etwas wären Exporte nach Excel
oder neue Datenfelder. Dies ist in der Pro-Version bereits beinhaltet. Bei der Basic-Version kostet es einmalig 200€.

<?php

	include("footer.php");
?>
