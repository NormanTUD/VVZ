<?php
	$php_start = microtime(true);
	include_once("config.php");
	$page_title = "Vorlesungsverzeichnis ".$GLOBALS['university_name']." | Impressum";
	$filename = 'startseite';
	include("header.php");

?>
	<div id="mainindex">
		<a href="startseite" border="0"><?php print_uni_logo(); ?></a><br>
<?php
		print get_demo_expiry_time();
?>
<h1>Lizenzbedingungen</h1>
<h2>§ 1 Vertragsgegenstand</h2>

<p>(1) Vertragsgegenstand ist die Überlassung von Software.</p>

<p>(2) Der Anbieter stellt dem Kunden während der Laufzeit dieses Vertrages die Nutzung der Software '<?php print $_SERVER["HTTP_HOST"]; ?>' zu eigenen Zwecken zur Verfügung.</p>

<p>(3) Zugriff und Nutzung der auf Servern des Anbieters gespeicherter Software erfolgen über eine Internetverbindung durch die Verwendung eines Internet-Browsers. Die Software ist über folgende Webseite erreichbar: <?php print htmlentities($_SERVER['HTTP_HOST'] ?? ""); ?></p>

<p>(4) Zu den wesentlichen vertraglichen Funktionen der Software gehören Folgende:</p>

<p>(5) Der Anbieter bietet dem Kunden die Software stets in der aktuellsten Version an.</p>

<p>(6) Da die Software laufend weiterentwickelt und aktualisiert wird, garantieren wir keine spezifische Uptime. Wir testen jedoch automatisch, ob der Service noch verfügbar ist, und reparieren ihn so schnell es geht.</p>

<p>(7) Während des Aktualisierungsvorgangs wird der Anbieter von seiner Vertragspflicht aus § 1 Abs. 2 dieses Vertrages befreit.</p>

<h2>§ 2 Beginn und Laufzeit des Nutzungsverhältnisses</h2>

<p>(1) Dieser Vertrag tritt mit Unterzeichnung in Kraft und endet mit einer formlosen Kündigungsemail.</p>

<h2>§ 3 Vergütung</h2>

<p>(1) Im Gegenzug zu den Leistungen des Anbieters hat der Kunde eine entsprechende Vergütung zu erbringen.</p>

<p>(2) Die Vergütung erfolgt unabhängig vom genutzten Volumen.</p>

<h2>§ 4 Verfügbarkeit der Software</h2>

<p>(1) Der Anbieter weist den Kunden darauf hin, dass er keine 100%ige Verfügbarkeit der Software garantieren kann, wenn Einschränkungen oder Beeinträchtigungen entstehen, die außerhalb des Einflussbereichs des Anbieters stehen. Der Anbieter kann auch außerhalb der Fälle des § 1 Abs. 6 und 7 dieses Vertrages mit Zustimmung des Kunden für einen bestimmten Zeitraum von seiner Leistungspflicht befreit werden.</p>

<p>(2) Der Kunde ist verpflichtet, den Anbieter unverzüglich schriftlich (per Brief oder per E-Mail) darüber zu unterrichten, sobald die Software nicht verfügbar ist.</p>

<h2>§ 5 Nutzungsrecht des Kunden, Zugriffsberechtigung</h2>

<p>(1) Der Kunde erhält an der Software ein auf die Laufzeit des vorliegenden Vertrages beschränktes Nutzungsrecht.</p>

<p>(2) Es erfolgt keine körperliche Überlassung der Software. Die Software bleibt jederzeit auf dem Server des Anbieters.</p>

<h2>§ 6 Schulung</h2>

<p>Der Anbieter bietet kostenfreie Schulungsvideos zu allen relevanten Themen. Bei Bedarf können Sie uns kontaktieren und wir versuchen, neue Schulungsvideos zu erstellen.</p>

<h2>§ 7 Support</h2>

<p>Der Anbieter stellt dem Kunden zur Beseitigung von technischen Störungen und Behebung von Fehlern, die im Rahmen der Nutzung der Software aufkommen, telefonisch einen Kundendienst zur Verfügung. Der Kundendienst des Anbieters ist via Email erreichbar, und zwar folgendermaßen:</p>

<p>Email: norman.koch@tu-dresden.de</p>


<h2>§ 8 Mängelansprüche</h2>

<p>(1) Der Anbieter haftet für Mängel der Vertragsleistungen.</p>

<p>(2) Ansprüche nach § 536a BGB, insbesondere die verschuldensunabhängige Garantiehaftung und das Selbstvornahmerecht betreffend, sind ausgeschlossen.</p>

<p>(3) Ein Sachmangel liegt vor, wenn die Software nicht die vertraglich vereinbarte Beschaffenheit aufweist oder sich nicht für die vertraglich vorausgesetzte Verwendung eignet. Unerhebliche Abweichungen stellen keinen Mangel dar.</p>

<p>(4) Der Kunde ist verpflichtet, den Anbieter unverzüglich schriftlich (per Brief oder per E-Mail) von aufgetretenen Mängeln zu unterrichten.</p>


<h2>§ 9 Haftung</h2>

<p>(1) Die Vertragsparteien haften für Vorsatz und grobe Fahrlässigkeit.</p>

<p>(2) Der Anbieter haftet für die Verletzung wesentlicher Vertragspflichten (sog. Kardinalspflichten). Dabei handelt es sich um solche vertraglichen Pflichten, deren Erfüllung den Vertrag so wesentlich prägt, als dass deren Verletzung eine Gefährdung der Erreichung des Vertragszwecks darstellt, und auf deren Einhaltung der Kunde vertrauen darf. Soweit die Kardinalspflichten fahrlässig verletzt wurden, ist der Schadensersatzanspruch des Kunden begrenzt auf den vertragstypisch vorhersehbaren Schaden.</p>

<p>(3) Der Anbieter haftet außerdem gemäß den gesetzlichen Bestimmungen nach den Vorschriften des Produkthaftungsgesetzes und für Schäden, die durch die Verletzung des Lebens, des Körpers oder der Gesundheit des Kunden entstanden sind.</p>

<p>(4) Der Anbieter haftet für Schäden seiner Erfüllungsgehilfen.</p>

<h2>§ 10 Herausgabe und Löschung von Daten</h2>

<p>(1) Nach Beendigung des Vertragsverhältnisses hat der Anbieter sämtliche Daten, Unterlagen und Datenträger des Kunden, die der Anbieter im Zusammenhang mit diesem Vertrag erhalten hat für den Kunden zum Download bereitgestellt werden.</p>

<p>(2) Der Anbieter hat innerhalb von 48 Stunden nach Beendigung des Vertragsverhältnisses sämtliche gespeicherte Daten des Kunden auf dem eigenen Server vollständig zu löschen.</p>

<h2>§ 11 Geheimhaltung, Vertraulichkeit</h2>

<p>(1) Die Parteien sind verpflichtet, alle ihnen im Zusammenhang mit diesem Vertrag bekannt gewordenen vertraulichen Informationen über die jeweils andere Partei dauerhaft geheim zu halten, nicht an Dritte weiterzugeben, aufzuzeichnen oder in anderer Weise zu verwerten, sofern die jeweils andere Partei der Offenlegung oder Verwendung nicht ausdrücklich und schriftlich zugestimmt hat oder die Informationen aufgrund Gesetzes, Gerichtsentscheidung oder Verwaltungsentscheidung offengelegt werden müssen. Liegt keine solche Zustimmung oder Offenlegung vor, sind die bekannt gewordenen Informationen nur zur Durchführung dieses Vertrages zu verwenden.</p>

<p>(2) Der Kunde ist insbesondere zur Geheimhaltung hinsichtlich aller Inhalte der Software verpflichtet. Der Kunde darf die Zugriffsdaten (Benutzernamen und Passwörter) nicht an Unbefugte weitergeben.</p>

<p>(3) Keine vertraulichen Informationen im Sinne des § 13 Abs. 1 dieses Vertrages sind Folgende:</p>

<p>    Informationen, die der anderen Partei bereits zuvor bekannt waren.</p>
<p>    Informationen, die allgemein bekannt sind.</p>
<p>    Informationen, die der anderen Partei von einem Dritten offenbart wurden, ohne dass dieser dadurch eine Vertraulichkeitsverpflichtung verletzt hat.</p>

<p>(4) Die Verpflichtungen aus diesem Paragraphen sind auch auf den Zeitraum nach Beendigung des Vertragsverhältnisses anzuwenden.</p>

<h2>§ 12 Schlussbestimmungen</h2>

<p>(1) Rechtserhebliche Erklärungen und Anzeigen, die nach Vertragsschluss abzugeben sind, bedürfen zu ihrer Wirksamkeit der Schriftform.</p>

<p>(2) Diese Vertragsbedingungen gelten ausschließlich. Anderweitigen Geschäftsbedingungen des Anbieters, des Kunden oder Dritter wird hier hiermit ausdrücklich widersprochen.</p>

<p>(3) Mündliche Nebenabreden bestehen nicht. Änderungen, Ergänzungen und die Aufhebung dieses Vertrages bedürfen der Schriftform. Dies gilt auch für die Änderung dieser Schriftformklausel selbst.</p>

<p>(4) Sollten einzelne Bestimmungen dieses Vertrages ganz oder teilweise unwirksam sein oder nach Vertragsschluss unwirksam werden, so wird die Wirksamkeit der übrigen Bestimmungen hierdurch nicht berührt.</p>
<p>Die Vertragsparteien sind in diesem Fall verpflichtet, über eine wirksame und zumutbare Ersatzregelung zu verhandeln, die dem mit der unwirksamen Bestimmung verfolgten Sinn und Zweck möglichst nahe kommt. Dies gilt auch im Falle einer Vertragslücke.</p>

<p>(5) Anhänge zu diesem Vertrag sind Bestandteil dieses Vertrags.</p>

<p>(6) Dieser Vertrag unterliegt ausschließlich dem Recht der Bundesrepublik Deutschland.</p>

<p>(7) Der Gerichtsstand für alle Streitigkeiten aus diesem Vertrag bestimmt sich nach den maßgeblichen Regeln der ZPO.</p>
<?php
	include("footer.php");
?>
