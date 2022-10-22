<?php
	$GLOBALS["error_page_shown"] = 0;
	$GLOBALS['function_usage'] = array();
	$GLOBALS['rquery_print'] = 0;
	$GLOBALS["is_demo"] = array();

	include_once("mysql.php");

	function set_session_id ($user_id) {
		//delete_old_session_ids($GLOBALS['logged_in_user_id']);
		$session_id = generate_random_string(128);
		if(table_exists($GLOBALS["dbname"], "session_ids")) {
			$query = 'INSERT IGNORE INTO `session_ids` (`session_id`, `user_id`) VALUES ('.esc($session_id).', '.esc($user_id).')';
			rquery($query);

			setcookie($GLOBALS["cookie_hash"].'_session_id', $session_id, time() + (7 * 86400), "/");

			$query = 'SELECT `user_id`, `username`, `dozent_id`, `institut_id`, `accepted_public_data` FROM `view_user_session_id` WHERE `user_id` = '.esc($user_id);
			$result = rquery($query);
			while ($row = mysqli_fetch_row($result)) {
				set_login_data($row);
			}
		}
	}

	function generate_random_string ($length = 50) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[mt_rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}


	function get_get ($name) {
		if(array_key_exists($name, $_GET)) {
			return $_GET[$name];
		} else {
			return NULL;
		}
	}


	function get_single_row_from_result_assoc ($result, $default = NULL) {
		while ($row = mysqli_fetch_assoc($result)) {
			return $row;
		}
		return $default;
	}

	function get_single_row_from_result ($result, $default = NULL) {
		$id = $default;
		while ($row = mysqli_fetch_row($result)) {
			$id = $row[0];
		}
		return $id;
	}


	function get_single_row_from_query_assoc ($query, $default = NULL) {
		$result = rquery($query);
		return get_single_row_from_result_assoc($result, $default);
	}

	function get_single_row_from_query ($query, $default = NULL) {
		$result = rquery($query);
		return get_single_row_from_result($result, $default);
	}

	function gsv($query, $default = NULL) {
		return get_single_row_from_query($query, $default);
	}

	function get_post ($name) {
		if(array_key_exists($name, $_POST)) {
			return $_POST[$name];
		} else {
			return NULL;
		}
	}


	function get_kunde_id_by_db_name ($dbn) {
		$query = "select id from vvz_global.kundendaten where dbname = ".esc($dbn);
		return get_single_row_from_query($query);
	}

	function get_uni_name () {
		return "db_vvz_".get_kunden_db_name();
	}

	function create_uni_name ($name) {
		$name = strtolower($name ?? "");
		$name = preg_replace("/\s+/", " ", $name);
		$name = preg_replace("/ /", "-", $name);
		$name = preg_replace("/(ä|Ä)/", "ae", $name);
		$name = preg_replace("/(ö|Ö)/", "oe", $name);
		$name = preg_replace("/(ü|Ü)/", "ue", $name);
		$name = preg_replace("/ß/", "ss", $name);
		$name = preg_replace("/\d+/", "-", $name);
		$name = preg_replace("/\s/", "_", $name);
		$name = preg_replace("/_+/", "-", $name);
		$name = preg_replace("/-+/", "-", $name);
		$name = preg_replace("/ä/", "ae", $name);
		$name = preg_replace("/ü/", "ue", $name);
		$name = preg_replace("/ö/", "oe", $name);
		$name = preg_replace("/ß/", "ss", $name);
		$name = preg_replace("/_+$/", "", $name);
		$name = preg_replace("/-+$/", "", $name);
		$name = preg_replace("/[^a-z_-]/", "", $name);
		$name = preg_replace("/^_+/", "", $name);
		$name = preg_replace("/-/", "_", $name);
		return $name;
	}

	function get_kunde_db_name_by_id($id) {
		if(!$id) {
			return get_kunden_db_name();
		} else {
			$query = "select dbname from vvz_global.kundendaten where id = ".esc($id);
			return get_single_row_from_query($query);
		}
	}

	function get_nonexisting_db_name () {
		$success = false;
		while (!$success) {
			$rndInt = rand(0, pow(36, 10) - 1);
			$rndStr = base_convert($rndInt, 10, 36);
			$rndStr = "vorlesungsverzeichnis_demo_".str_pad($rndStr, 10, "0", STR_PAD_LEFT);

			$query = "SELECT 1 FROM vvz_global.kundendaten WHERE urlname = ".esc("db_vvz_".$rndStr)." LIMIT 1";
			if (!get_single_row_from_query($query)) {
				return $rndStr;
			} else {
				// do nothing - try again in the next loop
			}
		}
	}

	#die(get_nonexisting_db_name());

	function get_kunden_db_name() {
		if(array_key_exists("REDIRECT_URL", $_SERVER)) {
			$url = $_SERVER["REDIRECT_URL"];

			if($url && preg_match("/v\/([a-z_-]+)/", $url, $matches)) {
				return "db_vvz_".$matches[1];
			}
		}

		if(!file_exists("/etc/vvztud")) {
			return $GLOBALS["dbname"] ?? "startpage";
		}

		return "vvztud";
	}

	function get_kunde_db_name() {
		$url_uni_name = get_url_uni_name();
		$query = 'select dbname from vvz_global.kundendaten where urlname = '.esc($url_uni_name);
		$res = get_single_row_from_query($query);

		if($res) {
			return $res;
		} else {
			$dbn = get_kunden_db_name();
			$n = preg_replace("/^db_vvz_/", "", $dbn);
			return $n;
		}
	}

	function get_kunde_url() {
		$n = get_kunde_db_name();
		#if(array_key_exists('REQUEST_URI', $_SERVER) && preg_match("/^\Q".$n."\E/", $_SERVER['REQUEST_URI']) || preg_match("/^v\/".$n."/", $_SERVER["REQUEST_URI"])) {
			return "";
		#} else {
		#	return "v/$n/";
		#}
	}

	function seconds2human($ss, $sloppy=0) {
		$s = $ss%60;
		$m = floor(($ss%3600)/60);
		$h = floor(($ss%86400)/3600);
		$d = floor(($ss%2592000)/86400);
		$M = floor($ss/2592000);

		if($M) {
			if($M == 1) {
				if($d) {
					return "$M Monat und $d Tage";
				} else {
					return "$M Monat";
				}
			} else {
				if($d) {
					return "$M Monate und $d Tage";
				} else {
					return "$M Monate";
				}
			}
		}

		if($d) {
			if($d == 1) {
				return "$d Tag, $h Stunden";
			}
			if($sloppy && $d > 2) {
				return "$d Tage";
			} else {
				return "$d Tage, $h Stunden";
			}
		}

		if($h) {
			if($h == 1) {
				return "$h Stunde und $m Minuten";
			} else {
				return "$h Stunden und $m Minuten";
			}
		}

		if($m) {
			return "$m Minuten und $s Sekunden";
		}

		if($s == 1) {
			return "$s Sekunde";
		} else {
			return "$s Sekunden";
		}
	}
	
	function get_kunde_plan () {
		$id = get_kunde_id_by_db_name(get_kunde_db_name());
		$query = "select p.name from vvz_global.kundendaten k left join vvz_global.plan p on k.plan_id = p.id where k.id = ".esc($id);
		return get_single_row_from_query($query);
	}

	function get_demo_expiry_time() {
		if($GLOBALS["dbname"] == "vvztud") {
			return "";
		}

		try {
			if(is_demo()) {
				$installation_ts = get_single_row_from_query("select unix_timestamp(installation_date) from ".get_kunden_db_name().".instance_config");
				if($installation_ts + ((7 * 86400) + 3600) > time()) {
					$seconds_left = -time() + $installation_ts + (86400 * 7) + 3600;
					$ablauftimer = seconds2human($seconds_left, true);
					$str = "<span class='demo_string'>Diese Installation ist eine Demo. Das heißt: sie wird nach 7 Tagen gelöscht. Ihnen verbleiben noch ".$ablauftimer." zum Testen. Sie können die Adresse in der Adresszeile mit Ihren Kollegen teilen.<br>Der Standardnutzer ist <tt>Admin</tt>/<tt>test</tt></span><br>";
				} else {
					$str = "Ihre Demo ist abgelaufen.";
				}
				return $str;
			}
		} catch (\Throwable $e) {
			dier($e);
		}
		return "";
	}

	function kunde_is_personalized ($id) {
		$query = "select personalized from vvz_global.kundendaten where id = ".esc($id);
		return get_single_row_from_query($query);
	}

	function urlname_already_exists ($urlname) {
		if(!$urlname) {
			return 0;
		}
		$query = "select count(*) from vvz_global.kundendaten where urlname = ".esc($urlname);
		return get_single_row_from_query($query);
	}

	function update_kunde ($id, $anrede, $universitaet, $name, $strasse, $plz, $ort, $dbname, $plan_id, $iban, $email, $zahlungszyklus_monate) {
		$query = 'insert into vvz_global.kundendaten (id, anrede, universitaet, name, strasse, plz, ort, personalized, dbname, plan_id, iban, email, zahlungszyklus_monate) values ('.esc($id).', '.esc($anrede).', '.esc($universitaet).', '.esc($name).', '.esc($strasse).', '.esc($plz).', '.esc($ort).', 1, '.esc($dbname).", ".esc($plan_id).", ".esc($iban).", ".esc($email).", ".esc($zahlungszyklus_monate).") on duplicate key update anrede=values(anrede), universitaet=values(universitaet), name=values(name), strasse=values(strasse), plz=values(plz), ort=values(ort), personalized=values(personalized), dbname=values(dbname), plan_id=values(plan_id), iban=values(iban), email=values(email), zahlungszyklus_monate=values(zahlungszyklus_monate)";
		rquery($query);
	}

	function get_plan_id($name) {
		$plan_id = null;
		switch($name) {
		case 'demo':
		case "Demo":
			$plan_id = 1;
			break;
		case 'basic_faculty':
		case "Basic Faculty":
			$plan_id = 2;
			break;
		case 'basic_university':
		case "Basic University":
			$plan_id = 3;
			break;
		case 'pro_faculty':
		case "Pro Faculty":
			$plan_id = 4;
			break;
		case 'pro_university':
		case 'Pro University':
			$plan_id = 5;
			break;
		default:
			#die("Unknown plan: >>".htmlentities(get_get("product") ?? "")."<<");
			exit(1);
			break;
		}

		return $plan_id;
	}

	function get_plan_price_by_name($name) {
		$plan_id = get_plan_id($name);

		$monatlich_query = "select monatliche_zahlung from vvz_global.plan where id = ".esc($plan_id);
		$monatlich = get_single_row_from_query($monatlich_query);
		$jaehrlich = get_single_row_from_query("select jaehrliche_zahlung from vvz_global.plan where id = ".esc($plan_id));

		return [$monatlich, $jaehrlich];
	}


	function is_demo () {
		if(file_exists("/etc/vvztud")) {
			return false;
		}

		if(get_kunde_plan() == "Demo") {
			return true;
		}
		return false;
	}

	function db_is_demo ($db, $cache=1) {
		if(file_exists("/etc/vvztud")) {
			return false;
		}

		if(!array_key_exists($db, $GLOBALS["is_demo"]) && $cache) {
			if(database_exists($db) && table_exists($db, "instance_config") && table_exists($db, "plan")) {
				$query = "select p.name from vvz_global.kundendaten k left join vvz_global.plan p on k.plan_id = p.id";
				$GLOBALS["is_demo"][$db] = get_single_row_from_query($query) == "Demo" ? 1 : 0;
			} else {
				$GLOBALS["is_demo"][$db] = 1;
			}
		}

		return $GLOBALS["is_demo"][$db];
	}

	function database_exists ($name) {
		$query = "SHOW DATABASES LIKE ".esc($name);
		$result = rquery($query);
		while ($row = mysqli_fetch_row($result)) {
			return 1;
		}
		return 0;
	}

	function get_kunde_url_name_by_id($id) {
		$query = "select urlname from vvz_global.kundendaten where id = ".esc($id);
		return get_single_row_from_query($query);
	}

	function get_kunde_university_name_by_id() {
		$query = "select universitaet from vvz_global.kundendaten where id = ".esc($id);
		return get_single_row_from_query($query);
	}

	function get_kunde_university_name() {
		return get_kunde_university_name_by_id(get_kunde_id_by_db_name(get_kunden_db_name()));
	}


	function get_kunde_id_by_url () {
		$urlname = get_url_uni_name();

		$query = "select id from vvz_global.kundendaten where urlname = ".esc($urlname);
		$res = get_single_row_from_query($query);
		return $res ? $res : get_kunde_id_by_db_name($GLOBALS["dbname"]);

	}

	function get_db_name_by_kunde_id ($kunde_id) {
		$query = "select dbname from vvz_global.kundendaten where id = ".esc($kunde_id);
		$res = get_single_row_from_query($query);
		return $res;
	}

	function get_plan_name_by_id($id) {
		$query = "select name from vvz_global.plan where id = ".esc($id);
		return get_single_row_from_query($query);
	}

	function checkIBAN($iban) {
		$iban = strtolower(str_replace(' ','',$iban));
		$iban = strtolower(str_replace('-','',$iban));

		if(strlen($iban) != 22) {
			return false;
		}
		$Countries = array('al'=>28,'ad'=>24,'at'=>20,'az'=>28,'bh'=>22,'be'=>16,'ba'=>20,'br'=>29,'bg'=>22,'cr'=>21,'hr'=>21,'cy'=>28,'cz'=>24,'dk'=>18,'do'=>28,'ee'=>20,'fo'=>18,'fi'=>18,'fr'=>27,'ge'=>22,'de'=>22,'gi'=>23,'gr'=>27,'gl'=>18,'gt'=>28,'hu'=>28,'is'=>26,'ie'=>22,'il'=>23,'it'=>27,'jo'=>30,'kz'=>20,'kw'=>30,'lv'=>21,'lb'=>28,'li'=>21,'lt'=>20,'lu'=>20,'mk'=>19,'mt'=>31,'mr'=>27,'mu'=>30,'mc'=>27,'md'=>24,'me'=>22,'nl'=>18,'no'=>15,'pk'=>24,'ps'=>29,'pl'=>28,'pt'=>25,'qa'=>29,'ro'=>24,'sm'=>27,'sa'=>24,'rs'=>22,'sk'=>24,'si'=>19,'es'=>24,'se'=>24,'ch'=>21,'tn'=>24,'tr'=>26,'ae'=>23,'gb'=>22,'vg'=>24);
		$Chars = array('a'=>10,'b'=>11,'c'=>12,'d'=>13,'e'=>14,'f'=>15,'g'=>16,'h'=>17,'i'=>18,'j'=>19,'k'=>20,'l'=>21,'m'=>22,'n'=>23,'o'=>24,'p'=>25,'q'=>26,'r'=>27,'s'=>28,'t'=>29,'u'=>30,'v'=>31,'w'=>32,'x'=>33,'y'=>34,'z'=>35);

		if(strlen($iban) == $Countries[substr($iban,0,2)]) {
			$MovedChar = substr($iban, 4).substr($iban,0,4);
			$MovedCharArray = str_split($MovedChar);
			$NewString = "";

			foreach($MovedCharArray AS $key => $value) {
				if(!is_numeric($MovedCharArray[$key])) {
					$MovedCharArray[$key] = $Chars[$MovedCharArray[$key]];
				}
				$NewString .= $MovedCharArray[$key];
			}

			if(bcmod($NewString, '97') == 1) {
				return true;
			}
		}
		return false;
	}

	function kunde_owns_url ($kunde, $url) {
		$query = "select count(*) from vvz_global.kundendaten where urlname = ".esc($url)." and id = ".esc($kunde);
		return get_single_row_from_query($query);
	}

	function update_kunde_plan ($kunde_id, $plan_id) {
		$query = "update vvz_global.kundendaten set plan_id = ".esc($plan_id)." where id = ".esc($kunde_id);
		rquery($query);
	}

	function get_kunde_name () {
		$kunde_id = get_kunde_id_by_db_name($GLOBALS["dbname"]);

		$query = "select name from vvz_global.kundendaten where id = ".esc($kunde_id);

		$res = get_single_row_from_query($query);
		if($res == $GLOBALS["dbname"]) {
			return "<i>Bisher nicht eingetragen</i>";
		}
		return htmlentities($res ?? "");
	}

	function get_university_name () {
		$kunde_id = get_kunde_id_by_db_name($GLOBALS["dbname"]);

		$query = "select universitaet from vvz_global.kundendaten where id = ".esc($kunde_id);

		$res = get_single_row_from_query($query);
		if($res == $GLOBALS["dbname"]) {
			return "<i>Bisher nicht eingetragen</i>";
		}
		return htmlentities($res ?? "");
	}

	function get_kunde_email () {
		$kunde_id = get_kunde_id_by_db_name($GLOBALS["dbname"]);

		$query = "select email from vvz_global.kundendaten where id = ".esc($kunde_id);

		$res = get_single_row_from_query($query);
		if($res == $GLOBALS["dbname"]) {
			return "<i>Bisher nicht eingetragen</i>";
		}
		return htmlentities($res ?? "");
	}

	function get_kunde_ort () {
		$kunde_id = get_kunde_id_by_db_name($GLOBALS["dbname"]);

		$query = "select ort from vvz_global.kundendaten where id = ".esc($kunde_id);

		$res = get_single_row_from_query($query);
		if($res == $GLOBALS["dbname"]) {
			return "<i>Bisher nicht eingetragen</i>";
		}
		return htmlentities($res ?? "");
	}

	function get_kunde_plz () {
		$kunde_id = get_kunde_id_by_db_name($GLOBALS["dbname"]);

		$query = "select plz from vvz_global.kundendaten where id = ".esc($kunde_id);

		$res = get_single_row_from_query($query);
		if($res == $GLOBALS["dbname"]) {
			return "<i>Bisher nicht eingetragen</i>";
		}
		return htmlentities($res ?? "");
	}

	function get_css_property_value($class, $prop) {
		$sql = "select val from customizations where classname = ".esc($class)." and property = ".esc($prop);

		return get_single_row_from_query($sql);
	}

/*
+--------------+------------------+------+-----+---------+----------------+
| Field        | Type             | Null | Key | Default | Extra          |
+--------------+------------------+------+-----+---------+----------------+
| id           | int(10) unsigned | NO   | PRI | <null>  | auto_increment |
| kunde_id     | int(10) unsigned | NO   | MUL | <null>  |                |
| plan_id      | int(10) unsigned | NO   | MUL | <null>  |                |
| monat        | int(10) unsigned | NO   |     | <null>  |                |
| jahr         | int(10) unsigned | NO   |     | <null>  |                |
| rabatt       | int(11)          | YES  |     | <null>  |                |
| spezialpreis | int(11)          | YES  |     | <null>  |                |
+--------------+------------------+------+-----+---------+----------------+
 */

	function get_plan_id_by_kunde_id($kunde_id) {
		return get_single_row_from_query("select plan_id from vvz_global.kundendaten where id = ".esc($kunde_id));
	}

	function schreibe_rechnung ($kunde_id, $plan_id, $monat, $jahr, $rabatt, $spezialpreis) {
		$query = "insert into vvz_global.rechnungen (kunde_id, plan_id, monat, jahr, rabatt, spezialpreis) values (".multiple_esc_join(array($kunde_id, $plan_id, $monat, $jahr, $rabatt, $spezialpreis)).") on duplicate key update jahr=values(jahr), monat=values(monat), rabatt=values(rabatt), spezialpreis=values(spezialpreis), plan_id=values(plan_id)";
		return rquery($query);
	}

	function schreibe_rechnungen_fuer_alle_dieser_monat () {
		$check_query = "select kunde_id, plan_id, monat, jahr, rabatt, spezialpreis from vvz_global.rechnungen";
		$before = query_to_status_hash($check_query, []);

		$query = "select id from vvz_global.kundendaten where plan_id not in (1, 6) and external_url is null";
		$result = rquery($query);
		while ($row = mysqli_fetch_row($result)) {
			schreibe_rechnung($row[0], get_plan_id_by_kunde_id($row[0]), date("m"), date("Y"), null, null);
		}

		$after = query_to_status_hash($check_query, []);

		if($before == $after) {
			// Keine neue Rechnung
		} else {
			// Neue Rechnung. TODO: Email schreiben
		}
	}

	if(array_key_exists("new_demo_uni", $_GET)) {
		$new_rand_name = get_nonexisting_db_name();
		$GLOBALS["dbname"] = "db_vvz_".$new_rand_name;
		include_once("selftest.php");
		print "Die neue Uni wird erstellt. Bitte warten...";
		flush();
		print '<meta http-equiv="refresh" content="0; url=v/'.create_uni_name($new_rand_name).'/?first_login=1" />';
		flush();
		exit(0);
	}

	function kunde_can_access_rechnung ($kunde_id, $rechnung_id) {
		$query = "select count(*) from vvz_global.rechnungen where id = ".esc($rechnung_id)." and kunde_id = ".esc($kunde_id);
		return get_single_row_from_query($query);
	}

	function get_zahlungszyklus_by_kunde_id($kunde_id) {
		$query = "select zahlungszyklus_monate from vvz_global.kundendaten where id = ".esc($kunde_id);
		return get_single_row_from_query($query);
	}

	function get_zahlungszyklus_name_by_monate ($name) {
		if($name == 12) {
			$name = "Jährlich";
		} else if($name == 1) {
			$name = "Monatlich";
		} else {
			die("Unbekannter Zahlungszyklus");
		}

		return $name;
	}

	function get_zahlungszyklus_monate_by_name ($name) {
		if($name == "Jährlich") {
			$name = 6;
		} else if($name == "Monatlich") {
			$name = 1;
		} else {
			die("Unbekannter Zahlungszyklus");
		}

		return $name;
	}

	function get_urlname_by_dbname ($dbn) {
		$query = "select urlname from vvz_global.kundendaten where dbname = ".esc($dbn);
		return get_single_row_from_query($query);
	}

	function update_kunde_urlname($kunde_id, $urlname) {
		$query = "update vvz_global.kundendaten set urlname = ".esc($urlname)." where id = ".esc($kunde_id);
		rquery($query);
	}
?>
