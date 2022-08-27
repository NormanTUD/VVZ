<?php
	$php_start = microtime(true);
	include_once("config.php");
	$page_title = "Vorlesungsverzeichnis ".$GLOBALS['university_name']." | Kontakt";
	$filename = 'startseite';
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
		7 => array("question" => "Was ist schneller: Auto oder Schnecke?", "answer" => "Auto"),
		8 => array("question" => "Was ist teurer: Gold oder Schlamm?", "answer" => "Gold"),
		9 => array("question" => "Welches Tier macht Miau?", "answer" => "Katze")
	);

	$frage = rand(0, count($sicherheitsfragen) - 1);
?>
	<div id="mainindex">
<?php
		if(!isset($GLOBALS['adminpage'])) {
?>
		<a href="startseite" border="0"><?php print_uni_logo(); ?> </a>
<?php
		print get_demo_expiry_time();
?>
<?php
	}
?>
		<h2>Kontakt</h2>

		Fragen? Kritik oder Lob am Vorlesungsverzeichnis? Haben Sie einen Fehler gefunden oder eine Idee
		für Verbesserungen auf der Seite? Zögern Sie nicht, uns zu kontaktieren.

		Wir sind bemüht, alle Unannehmlichkeiten aufzuspüren und zu beseitigen.
<?php
	if (!file_exists('/etc/x11test')  && function_exists('mail')) {
		$got_frage_id = get_post('frage_id');
		//$sicherheitsfrage_bestanden = get_post('sicherheitsfrage') == $sicherheitsfragen[$got_frage_id]['answer'] ? 1 : 0;
		$sicherheitsfrage_bestanden = 0;
		if(get_post('sicherheitsfrage') && isset($got_frage_id)) {
			$sicherheitsfrage_bestanden = get_post('sicherheitsfrage') == $sicherheitsfragen[$got_frage_id]['answer'] ? 1 : 0;
		}
		if(isset($GLOBALS['logged_in']) && $GLOBALS['logged_in'] == 1) {
			$sicherheitsfrage_bestanden = 1;
		}
		if(get_post('frage_id') && array_key_exists(get_post('frage_id'), $sicherheitsfragen) && $sicherheitsfrage_bestanden) {
			if(strlen(get_post('nachricht')) >= 5) {
				$headers = '';

				$to_name = $GLOBALS['admin_name'];
				$to = $GLOBALS['admin_email'];

				if(get_post('natur') == 'inhaltlich') {
					$to_name = $GLOBALS['name_non_technical'];
					$to = $GLOBALS['to_non_technical'];
					if(is_array($GLOBALS['cc_non_technical'])) {
						foreach ($GLOBALS['cc_non_technical'] as $cc_email) {
							$headers .= "Cc: ".$cc_email."r\n";
						}
					} else if($GLOBALS['cc_non_technical']) {
						$headers .= "Cc: ".$GLOBALS['cc_non_technical']."r\n";
					}
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

				$headers .= "From:" . $GLOBALS['from_email']."\r\n";
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
								<td><label for="name">Ihr Name:</label></td><td><input type="text" name="name" /></td>
							</tr>
<?php
						}
?>
						<tr>
							<td><label for="email">Ihre Email-Adresse:</label></td><td><input type="text" name="email" /></td>
						</tr>
						<tr>
							<td><label for="natur">Das Anliegen ist...</label></td>
							<td>
								<select name="natur">
									<option value="inhaltlich">... inhaltlicher Natur (Fragen zu Vorlesungen, Prüfungen, Modulen, Studienablauf usw.)</option>
									<option value="technisch">... technischer Natur (Fragen oder Verbesserungsvorschläge zum Vorlesungsverzeichnis usw.)</option>
								</select>
							</td>
						</tr>
						<tr>
							<td><label for="nachricht">Ihre Nachricht an uns:</label></td><td><textarea name="nachricht" class="contactfield"></textarea></td>
						</tr>
<?php
						if(!isset($GLOBALS['logged_in']) || $GLOBALS['logged_in'] == 0) {
?>
							<tr>
								<td><label for="sicherheitsfrage">Sicherheitsfrage:<i><?php print $sicherheitsfragen[$frage]['question']; ?></i></label></td><td><input type="text" name="sicherheitsfrage" /></td>
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
	} else if (!file_exists('/etc/x11test')) {
?>
		<p>Kontaktieren Sie bei Fragen, gefundenen Fehlern oder Ergänzungsvorschlägen <a href="mailto:<?php print $GLOBALS['admin_email']; ?>><?php print $GLOBALS['admin_email']; ?></a>. 
		Ich werde mich schnellstmöglich um Antwort bemühen.</p>
<?php
	}

	if(!isset($GLOBALS['adminpage'])) {
		include("footer.php");
	}
?>
