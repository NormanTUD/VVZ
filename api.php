<?php
	$php_start = microtime(true);
	$setup_mode = 0;
	include("functions.php");

	$query_string = $_SERVER['QUERY_STRING'];
	$log_data = array(
		'error' => 0,
		'parameters' => $query_string,
		'ip' => get_user_ip()
	);

	if(get_get('auth_code')) {
		rquery('delete from api_log where time < DATE_SUB(NOW(), INTERVAL 3 MONTH)');
		if(file_exists('/etc/api_debug') || is_valid_auth_code(get_get('auth_code'))) {
			if(file_exists('/etc/api_debug') || last_api_access_long_ago(get_get('auth_code'))) {
				$log_data['error'] = 1;
				$data = array();
				if(get_get('gebaeude_liste')) {
					$data = create_gebaeude_array();
				} else if (get_get('semester_liste')) {
					$data = create_semester_array();
				} else if (get_get('institute_liste')) {
					$data = create_institute_array();
				} else if (get_get('dozenten_liste')) {
					$data = create_dozenten_first_last_name_array();
				} else if (get_get('studiengang_liste')) {
					$data = create_studiengaenge_array();
				} else if (get_get('veranstaltungstypen')) {
					$data = create_veranstaltungstyp_abkuerzung_namen_array();
				} else if (get_get('pruefungen') && get_get('studiengang')) {
					$pruefungen = create_pruefungen_by_studiengang_array(get_get('studiengang'));

					foreach ($pruefungen as $key => $value) {
						$key = get_veranstaltungsname_by_id($key);
						$data[$key] = htmlentities($value);
					}
				} else {
					$query = 'select `v`.`veranstaltung_typ`, `v`.`veranstaltung_name`, `v`.`gebaeude_id`, `v`.`raum_id`, concat(`v`.`last_name`, ", ", `v`.`first_name`) `dozent_name`, `v`.`wochentag`, `v`.`stunde`, `v`.`woche`, `v`.`erster_termin`, `hinweis`, `ve`.`id` `veranstaltung_id` from `view_veranstaltung_komplett` `v` JOIN `veranstaltung` `ve` ON `ve`.`id` = `v`.`veranstaltung_id`';
					$where = array();

					if(get_get('type')) {
						$where[] = '`v`.`veranstaltung_typ` = '.esc(get_get('type'));
					}

					if(get_get('gebaeude')) {
						$id = get_gebaeude_id_by_abkuerzung(get_get('gebaeude'));
						if($id) {
							$where[] = '`v`.`gebaeude_id` = '.esc($id);
						}
					}

					if(get_get('first_name') && get_get('last_name')) {
						$id = get_dozent_id(get_get('first_name'), get_get('last_name'));
						if($id) {
							$where[] = '`v`.`dozent_id` = '.esc($id);
						}
					}

					if(get_get('semester')) {
						$where[] = '`ve`.`semester_id` = '.esc(get_get('semester'));
					}

					if(get_get('institut')) {
						$where[] = '`ve`.`institut_id` = '.esc(get_get('institut'));
					}

					if(get_get('studiengang')) {
						$where[] = '`v`.`veranstaltung_id` IN(select veranstaltung_id as id from view_veranstaltung_nach_studiengang where studiengang_id = '.esc(get_get('studiengang')).')';
					}


					if(count($where)) {
						$query .= ' WHERE '.join(' AND ', $where);
					}

					$result = rquery($query);
					$data = array();
					if(!get_get('notitle')) {
						$data[0] = array('Typ', 'Name', 'Gebäude', 'Raum', 'Dozent', 'Wochentag', 'Stunde', 'Wochenrhythmus', 'Erster Termin', 'Hinweis', 'Einzelne Termine');
					}
					while ($row = mysqli_fetch_row($result)) {
						$row[2] = get_gebaeude_abkuerzung($row[2]);
						$row[3] = get_raum_name_by_id($row[3]);
						if(get_get('datetype') == 'discordian') {
							$row[8] = discordian_date($row[8]);
						} else if(get_get('datetype') == 'unix') {
							$row[8] = strtotime($row[8]);
						}

						foreach ($row as $key => $value) {
							if (is_null($value)) {
								$row[$key] = "";
							}
						}


						$row[9] = replace_hinweis_with_graphics($row[9], 1);
						$row[10] = nice_einzelne_veranstaltung_by_id($row[10]);
						$data[] = $row;
					}
				}

				$data = sanitize_data($data);
				print json_encode($data, JSON_PRETTY_PRINT|JSON_FORCE_OBJECT);

				$query = 'INSERT INTO `api_log` (`auth_code_id`, `time`, `parameter`, `ip`, `api_error_code_id`) VALUES ('.esc(get_auth_code_id(get_get('auth_code'))).', now(), '.esc($log_data['parameters']).', '.esc($log_data['ip']).', '.esc($log_data['error']).')';
				rquery($query);
			} else {
				$log_data['error'] = 4;
				print "Der Letzte Aufruf ist weniger als 10 Sekunden her.";
				$query = 'INSERT INTO `api_log` (`auth_code_id`, `time`, `parameter`, `ip`, `api_error_code_id`) VALUES ('.esc(get_auth_code_id(get_get('auth_code'))).', now(), '.esc($log_data['parameters']).', '.esc($log_data['ip']).', '.esc($log_data['error']).')';
				rquery($query);
			}
		} else {
			print "Der Auth-Code ist leider nicht richtig.";
			$log_data['error'] = 2;
			$query = 'INSERT INTO `api_log` (`auth_code_id`, `time`, `parameter`, `ip`, `api_error_code_id`) VALUES (null, now(), '.esc($log_data['parameters']).', '.esc($log_data['ip']).', '.esc($log_data['error']).')';
			rquery($query);
		}

	} else {
		$page_title = 'Vorlesungsverzeichnis TU Dresden — API';
		include("header.php");
?>
	<div id="main">
		<a href="index.php" border="0"><img alt="TUD-Logo, Link zur Startseite"  src="tudlogo.svg" width="255" /></a>
		<h1>Vorlesungsverzeichnis TU Dresden</h1>
		<h2>Was ist das hier?</h2>
		Diese API erlaubt automatisierte Zugriffe auf die öffentlichen Daten des Vorlesungsverzeichnis der TU Dresden. Über diese Schnittstelle
		lassen sich einfach automatisierte Zugriffe erstellen, die z. B. für selbstentwickelte Software zur Verfügung stehen.


		<h2>Wie kann ich es benutzen?</h2>

		Jeder, der Interesse an einem API-Zugang hat, kann uns über die <a href="kontakt.php">Kontaktseite</a> erreichen und erhält einen API-Zugangsaccount. Mit diesem Account ist es dann möglich,
				<a href="<?php print "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?>"><?php print htmlentities("http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"); ?></a> 
				mit dem Parameter <pre>?auth_code=$AUTH_CODE</pre> aufzurufen.

		<h2>Wie sind die Daten aufgebaut, die aus diesem API kommen?</h2>

		<h3>Das normale Vorlesungsverzeichnis</h3>

		Die Daten sind im JSON-Format. Im ersten Element stehen die jeweligen Überschriften, die sich immer
		auf einzelne Veranstaltungen beziehen. Durch die Überschriften sind diese Spalten selbsterklärend.

		Folgende Parameter können weiterhin hinzugefügt werden:

		<table>
			<tr>
				<th>Parameter</th>
				<th>Beschreibung</th>
			</tr>
			<tr>
				<td><pre>notitle=1</pre></td>
				<td>Deaktiviert die Überschriften im ersten Element</td>
			</tr>
			<tr>
				<td><pre>type=Seminar</pre></td>
				<td>
					Listet je nur Veranstaltungen dieses Types.
				</td>
			</tr>
			<tr>
				<td><pre>gebaeude=BZW</pre></td>
				<td>
					Listet je nur Veranstaltungen in diesem Gebäude. Als Bezeichnung muss die Abkürzung des Gebäudes gewählt werden. Eine Liste der Gebäude gibt es mit dem
					Parameter <pre>?auth_code=$AUTH_CODE&gebaeude_liste=1</pre>
				</td>
			</tr>
			<tr>
				<td><pre>first_name=Holm</pre> und <pre>last_name=Bräuer</pre></td>
				<td>
					Listet nur Veranstaltungen dieser Dozenten auf. Beide Parameter müssen immer zusammen vorkommen.
					Eine Liste der Dozenten gibt es mit dem Parameter <pre>?auth_code=$AUTH_CODE&dozenten_liste=1</pre>

				</td>
			</tr>
			<tr>
				<td><pre>datetype=discordian</pre> oder <pre>datetype=unix</pre></td>
				<td>Stellt die Datenausgaben auf den diskordianischen Kalendar bzw. die Unixzeit um.</td>
			</tr>
			<tr>
				<td><pre>pruefungen=1</pre> und <pre>studiengang=$STUDIENGANG</pre></td>
				<td>Listet alle Prüfungsnummern und Prüfungstypen eines Studienganges auf.</td>
			</tr>
			<tr>
				<td><pre>semester=1</pre></td>
				<td>Listet Veranstaltungen aus dem Semester mit der ID 1 auf.</td>
			</tr>
			<tr>
				<td><pre>institut=1</pre></td>
				<td>Listet Veranstaltungen aus dem Institut mit der ID 1 auf.</td>
			</tr>
		</table>

		Die Daten müssen in der UTF8-Kodierung übergeben werden, werden aber im JSON-Format ASCII-kompatibel maskiert.

		<h3>Liste aller Gebäude</h3>

		Mit dem Parameter <pre>?auth_code=$AUTH_CODE&gebaeude_liste=1</pre> wird im JSON-Format eine Liste aller Gebäude zurückgegeben.
		Diese besteht je aus dem Gebäudenamen und der Abkürzung.

		<h3>Liste der Institute</h3>

		Mit dem Parameter <pre>?auth_code=$AUTH_CODE&institute_liste=1</pre> wird im JSON-Format eine Liste aller Institute (bestehend aus ID und Name) zurückgegeben.

		<h3>Liste der Semester</h3>

		Mit dem Parameter <pre>?auth_code=$AUTH_CODE&semester_liste=1</pre> wird im JSON-Format eine Liste aller Semester (bestehend aus ID, Semesterjahr und Semestertyp) zurückgegeben.

		<h3>Liste aller Studiengänge</h3>

		Mit dem Parameter <pre>?auth_code=$AUTH_CODE&studiengang_liste=1</pre> wird im JSON-Format eine Liste aller Studiengänge und deren IDs zurückgegeben.

		<h3>Liste aller Dozenten</h3>

		Mit dem Parameter <pre>?auth_code=$AUTH_CODE&dozenten_liste=1</pre> wird im JSON-Format eine Liste aller Dozenten zurückgegeben.

		<h3>Liste aller Veranstaltungstypen</h3>

		Mit dem Parameter <pre>?auth_code=$AUTH_CODE&veranstaltungstypen=1</pre> wird im JSON-Format eine Liste aller Veranstaltungstypen zurückgegeben.

		<h3>Empfehlung</h3>

		Es wird empfohlen, die Listen auf der Seite des Abrufenden zu cachen. Listen wie die Dozenten- oder Gebäudelisten verändern sich selten. Einmaliges tägliches Abrufen
		reicht völlig aus, um die Listen aktuell zu halten

		<h2>Was gibt es für Beschränkungen?</h2>

		Zwischen zwei Aufrufen der API müssen mindestens 10 Sekunden liegen, um die Datenbank nicht nutzlos zu belasten. Bei übermäßiger Benutzung behalten wir uns
		vor, die API-Zugänge ohne Rückmeldung zu kündigen.

		<h3>Beispielaufruf</h3>

		<pre><?php print htmlentities("http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"); ?>?auth_code=$AUTH_CODE&amp;notitle=1&amp;first_name=Holm&amp;last_name=Bräuer&amp;gebaeude=BZW</pre>

		Zeigt, ohne dass die Titel in der ersten Zeile sind, die Veranstaltungen von Holm Bräuer im BZW.

		<h2>Fragen und Feature-Wünsche</h2>

		Sollten Sie zur Benutzung der API Fragen haben oder benötigen Sie andere Daten, als die API sie gerade zur Verfügung stellt, zögern Sie nicht, uns zu <a href="kontakt.php">kontaktieren</a>.
<?php
		include("footer.php");
	}
?>
