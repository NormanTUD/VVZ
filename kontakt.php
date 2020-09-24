<?php
	$php_start = microtime(true);
	include("data.php");
	if(file_exists('new_setup')) {
		include('setup.php');
		exit(0);
	}
	$page_title = "Vorlesungsverzeichnis TU Dresden";
	$filename = 'index.php';
	if(!isset($GLOBALS['adminpage'])) {
		include("header.php");
	}

	$sicherheitsfragen = array(
		0 => array("question" => "Wie lautet die Abkürzung für &raquo;Abitur&laquo;? (3 Buchstaben)", "answer" => "Abi"),
		1 => array("question" => "Wie lautet die Abkürzung für &raquo;Universität&laquo;? (3 Buchstaben)", "answer" => "Uni"),
		2 => array("question" => "Was heißt &raquo;hello&laquo; (Englisch) auf Deutsch?", "answer" => "hallo"),
		3 => array("question" => "Was ist schwerer: Eiffelturm oder Mensch?", "answer" => "Eiffelturm"),
		4 => array("question" => "Was ist höher: Hochhaus oder Ameise?", "answer" => "Hochhaus"),
		5 => array("question" => "Was ist heller: schwarz oder weiß?", "answer" => "weiß"),
		6 => array("question" => "Mit welchem Gerät, das mit &raquo;K&laquo; anfängt, kann man nur Photos machen?", "answer" => "Kamera"),
		7 => array("question" => "Wie heißt die Hauptstadt Sachsens?", "answer" => "Dresden"),
		8 => array("question" => "Was ist schneller: Auto oder Schnecke?", "answer" => "Auto"),
		9 => array("question" => "Was ist teurer: Gold oder Schlamm?", "answer" => "Gold")
	);

	$frage = rand(0, count($sicherheitsfragen) - 1);
?>
	<div id="mainindex">
<?php
		if(!isset($GLOBALS['adminpage'])) {
?>
			<a href="index.php" border="0"><img alt="TUD-Logo, Link zur Startseite"  src="tudlogo.svg" width="255" /></a>
<?php
	}
?>
		<h2>Kontakt</h2>

		Fragen? Kritik oder Lob am Vorlesungsverzeichnis? Haben Sie einen Fehler gefunden oder eine Idee
		für Verbesserungen auf der Seite? Zögern Sie nicht, uns zu kontaktieren.

		Wir sind bemüht, alle Unannehmlichkeiten aufzuspüren und zu beseitigen.
<?php
	if (function_exists('mail')) {
		$sicherheitsfrage_bestanden = get_post('sicherheitsfrage') == $sicherheitsfragen[get_post('frage_id')]['answer'] ? 1 : 0;
		if(isset($GLOBALS['logged_in']) && $GLOBALS['logged_in'] == 1) {
			$sicherheitsfrage_bestanden = 1;
		}
		if(get_post('frage_id') && array_key_exists(get_post('frage_id'), $sicherheitsfragen) && $sicherheitsfrage_bestanden) {
			if(strlen(get_post('nachricht')) >= 5) {
				$headers = '';
				$from = $GLOBALS['fromemail'];

				$to_name = $GLOBALS['contactname'];
				$to = $GLOBALS['contactemail'];
				if(get_post('natur') == 'inhaltlich') {
					$to_name = $GLOBALS['contactnameinhalt'];
					$to = $GLOBALS['contactemailinhalt'];
					$headers .= "Cc: ".$GLOBALS['contactemail']."\r\n";
				}
				$subject = "Nachricht vom Vorlesungsverzeichnis";

				$datum = date("d.m.Y");
				$uhrzeit = date("H:i");

				$message = "Name: ".htmlentities(get_post('name'))."\n";
				$message .= "Zeit: $datum $uhrzeit\n";
				if(get_post('referer')) {
					$message .= "Referer: ".htmlentities(get_post('referer'))."\n";
				}
				if(isset($_SERVER['HTTP_USER_AGENT'])) {
					$message .= "User-Agent: ".htmlentities($_SERVER['HTTP_USER_AGENT'])."\n";
				}
				$message .= "Email: ".htmlentities(get_post('email'))."\n\n";
				$message .= "Nachricht ===============================\n";
				$message .= htmlentities(get_post('nachricht'))."\n";
				$message .= "========================== Nachricht Ende\n";

				$headers .= "From:" . $from."\r\n";
				if(preg_match('/@/', get_post('email'))) {
					$headers .= 'Reply-To: '.get_post('email')."\r\n";
				}

				$fp = fsockopen("localhost", 25, $errno, $errstr, 5);
				if($fp && mail($to, $subject, $message, $headers)) {
					echo "<br /><br />Die Nachricht wurde erfolgreich versendet!";
				} else {
?>
					Die Nachricht konnte leider nicht versandt werden. Bitte klicken Sie hier und versenden Sie die Nachricht dann über Ihr lokales Emailprogramm (z.B. Thunderbird, Outlook usw.): <a href="mailto:<?php print htmlentities($to); ?>?subject=<?php print rawurlencode($subject); ?>&body=<?php print rawurlencode($message); ?>"><?php print htmlentities($to_name); ?></a> direkt.
<?php
				}
			} else {
?>
				Bitte geben Sie eine vollständige Nachricht ein.
<?php
			}
		} else {
			if(get_post('nachricht')) {
?>
				Die Sicherheitsfrage wurde nicht richtig beantwortet.
<?php
			} else {
?>
				<form input method="post">
					<input type="hidden" name="frage_id" value="<?php print $frage; ?>" />
<?php
					if(array_key_exists('HTTP_REFERER', $_SERVER) && isset($_SERVER['HTTP_REFERER'])) {
?>
						<input type="hidden" name="referer" value="<?php print htmlentities($_SERVER['HTTP_REFERER']); ?>" />
<?php
					}
?>
					<table>
<?php
						$name = null;
						if(isset($GLOBALS['logged_in_data'][1])) {
							$name = $GLOBALS['logged_in_data'][1];
						}

						if(strlen($name)) {
?>
							<input type="hidden" value="<?php print htmlentities($name); ?>" name="name" />
<?php
						} else {
?>
							<tr>
								<td>Ihr Name:</td><td><input type="text" name="name" /></td>
							</tr>
<?php
						}
?>
						<tr>
							<td>Ihre Email-Adresse:</td><td><input type="text" name="email" /></td>
						</tr>
						<tr>
							<td>Das Anliegen ist...</td>
							<td>
								<select name="natur">
									<option value="inhaltlich">... inhaltlicher Natur (Fragen zu Vorlesungen, Prüfungen, Modulen, Studienablauf usw.)</option>
									<option value="technisch">... technischer Natur (Fragen oder Verbesserungsvorschläge zum Vorlesungsverzeichnis usw.)</option>
								</select>
							</td>
						</tr>
						<tr>
							<td>Ihre Nachricht an uns:</td><td><textarea name="nachricht" class="contactfield"></textarea></td>
						</tr>
<?php
						if(!isset($GLOBALS['logged_in']) || $GLOBALS['logged_in'] == 0) {
?>
							<tr>
								<td>Sicherheitsfrage: <i><?php print $sicherheitsfragen[$frage]['question']; ?></i></td><td><input type="text" name="sicherheitsfrage" /></td>
							</tr>
<?php
						}
?>
						<tr>
							<td colspan="2"><input type="submit" value="Absenden" /></td>
						</tr>
					</table>
				</form>
<?php
			}
		}
	} else {
?>
		<p>Kontaktieren Sie bei Fragen, gefundenen Fehlern oder Ergänzungsvorschlägen <script type="text/javascript">
			//<![CDATA[
			<!--
			var x="function f(x){var i,o=\"\",ol=x.length,l=ol;while(x.charCodeAt(l/13)!" +
			"=92){try{x+=x;l+=l;}catch(e){}}for(i=l-1;i>=0;i--){o+=x.charAt(i);}return o" +
			".substr(0,ol);}f(\")86,\\\"ZPdw771\\\\b:ue049630\\\\t=3<\\\"\\\\ 000\\\\sn7" +
			"20\\\\$,,+#(d+!2.Ji530\\\\N^^V030\\\\ESY\\\\\\\\Vt320\\\\l220\\\\KAXB^t\\\\" +
			"n\\\\{ULJKAHEelxjh}wmdsyf|D,dlkgn~y6mc(kagqdr230\\\\Pt\\\\710\\\\T100\\\\72" +
			"0\\\\520\\\\230\\\\430\\\\520\\\\630\\\\2130\\\\320\\\\000\\\\500\\\\C200\\" +
			"\\n\\\\700\\\\330\\\\700\\\\t\\\\\\\\\\\\n\\\\020\\\\710\\\\310\\\\000\\\\r" +
			"\\\\}200\\\\`:>(1x6jw|=>4$&<:b?$,%2%* \\\"(f};o nruter};))++y(^)i(tAedoCrah" +
			"c.x(edoCrahCmorf.gnirtS=+o;721=%y{)++i;l<i;0=i(rof;htgnel.x=l,\\\"\\\"=o,i " +
			"rav{)y,x(f noitcnuf\")"                                                      ;
			while(x=eval(x));
			//-->
			//]]>
		</script>. Ich werde mich schnellstmöglich um Antwort bemühen.</p>
<?php
	}
	if(!isset($GLOBALS['adminpage'])) {
		include("footer.php");
	}
?>
