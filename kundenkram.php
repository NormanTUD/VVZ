<?php
	$GLOBALS["error_page_shown"] = 0;
	$GLOBALS['function_usage'] = array();
	$GLOBALS['rquery_print'] = 0;
	$GLOBALS["is_demo"] = array();

	include_once("mysql.php");

	function set_session_id ($user_id) {
		//delete_old_session_ids($GLOBALS['logged_in_user_id']);
		$session_id = generate_random_string(1024);
		$query = 'INSERT IGNORE INTO `session_ids` (`session_id`, `user_id`) VALUES ('.esc($session_id).', '.esc($user_id).')';
		rquery($query);

		setcookie('session_id', $session_id, time() + (7 * 86400), "/");
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



	function get_single_row_from_result ($result, $default = NULL) {
		$id = $default;
		while ($row = mysqli_fetch_row($result)) {
			$id = $row[0];
		}
		return $id;
	}


	function get_single_row_from_query ($query, $default = NULL) {
		$result = rquery($query);
		return get_single_row_from_result($result, $default);
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
		/*
		if(array_key_exists("new_uni_name", $_GET)) {
			return $_GET["new_uni_name"];
		}
		if(array_key_exists("REDIRECT_SFURI", $_SERVER)) {
			return "db_vvz_".get_kunden_db_name();
			print "Die neue Uni wird erstellt. Bitte warten...";
			flush();
			print '<meta http-equiv="refresh" content="0; url=v/'.create_uni_name(get_uni_name()).'/" />';
			flush();
		}
		 */

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
		$name = preg_replace("/technische_universitaet_/", "tu_", $name);
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

	function get_kunden_db_name() {
		if(array_key_exists("new_demo_uni", $_GET)) {
			print "Die neue Uni wird erstellt. Bitte warten...";
			flush();
			$randname = generate_random_string(20);
			print '<meta http-equiv="refresh" content="0; url=v/'.create_uni_name($randname).'/" />';
			flush();
			exit(0);
		}

		if(array_key_exists("REDIRECT_URL", $_SERVER)) {
			$url = $_SERVER["REDIRECT_URL"];

			if($url && preg_match("/v\/([a-z_-]+)/", $url, $matches)) {
				return "db_vvz_".$matches[1];
			}
		}

		return $GLOBALS["dbname"] ?? "startpage";
		return "startpage";
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

	function seconds2human($ss) {
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
			return "$d Tage, $h Stunden";
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
		if(is_demo()) {
			$installation_age = get_single_row_from_query("select now() - installation_date from ".get_kunden_db_name().".instance_config");
			$ablauftimer = seconds2human((86400 * 7) - $installation_age);
			return "<br><span class='demo_string'>Diese Installation ist eine Demo. Das heißt: sie wird nach 7 Tagen gelöscht.<br>Ihnen verbleiden noch ".$ablauftimer." zum Testen.<br>Der Standardnutzer ist <tt>Admin</tt>/<tt>test</tt></span><br>";
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

	function update_kunde ($id, $anrede, $universitaet, $kundename, $kundestrasse, $kundeplz, $kundeort, $dbname, $plan_id, $iban, $email) {
		$urlname = create_uni_name($universitaet);
		if(urlname_already_exists($urlname)) {
			return 1;
		}
		$query = 'insert into vvz_global.kundendaten (id, anrede, universitaet, kundename, kundestrasse, kundeplz, kundeort, personalized, dbname, urlname, plan_id, iban, email) values ('.esc($id).', '.esc($anrede).', '.esc($universitaet).', '.esc($kundename).', '.esc($kundestrasse).', '.esc($kundeplz).', '.esc($kundeort).', 1, '.esc($dbname).", ".esc($urlname).", ".esc($plan_id).", ".esc($iban).", ".esc($email).") on duplicate key update anrede=values(anrede), universitaet=values(universitaet), kundename=values(kundename), kundestrasse=values(kundestrasse), kundeplz=values(kundeplz), kundeort=values(kundeort), personalized=values(personalized), dbname=values(dbname), urlname=values(urlname), plan_id=values(plan_id), iban=values(iban), email=values(email)";
		rquery($query);
	}

	function get_plan_id($name) {
		$plan_id = null;
		switch($name) {
		case 'demo':
			$plan_id = 1;
			break;
		case 'basic_faculty':
			$plan_id = 2;
			break;
		case 'basic_university':
			$plan_id = 3;
			break;
		case 'pro_faculty':
			$plan_id = 4;
			break;
		case 'pro_university':
			$plan_id = 5;
			break;
		default:
			die("Unknown plan: >>".htmlentities(get_get("product"))."<<");
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
		if(get_kunde_plan() == "Demo") {
			return true;
		}
		return false;
	}

	function db_is_demo ($db) {
		if(!array_key_exists($db, $GLOBALS["is_demo"])) {
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
		$Countries = array('al'=>28,'ad'=>24,'at'=>20,'az'=>28,'bh'=>22,'be'=>16,'ba'=>20,'br'=>29,'bg'=>22,'cr'=>21,'hr'=>21,'cy'=>28,'cz'=>24,'dk'=>18,'do'=>28,'ee'=>20,'fo'=>18,'fi'=>18,'fr'=>27,'ge'=>22,'de'=>22,'gi'=>23,'gr'=>27,'gl'=>18,'gt'=>28,'hu'=>28,'is'=>26,'ie'=>22,'il'=>23,'it'=>27,'jo'=>30,'kz'=>20,'kw'=>30,'lv'=>21,'lb'=>28,'li'=>21,'lt'=>20,'lu'=>20,'mk'=>19,'mt'=>31,'mr'=>27,'mu'=>30,'mc'=>27,'md'=>24,'me'=>22,'nl'=>18,'no'=>15,'pk'=>24,'ps'=>29,'pl'=>28,'pt'=>25,'qa'=>29,'ro'=>24,'sm'=>27,'sa'=>24,'rs'=>22,'sk'=>24,'si'=>19,'es'=>24,'se'=>24,'ch'=>21,'tn'=>24,'tr'=>26,'ae'=>23,'gb'=>22,'vg'=>24);
		$Chars = array('a'=>10,'b'=>11,'c'=>12,'d'=>13,'e'=>14,'f'=>15,'g'=>16,'h'=>17,'i'=>18,'j'=>19,'k'=>20,'l'=>21,'m'=>22,'n'=>23,'o'=>24,'p'=>25,'q'=>26,'r'=>27,'s'=>28,'t'=>29,'u'=>30,'v'=>31,'w'=>32,'x'=>33,'y'=>34,'z'=>35);

		if(strlen($iban) == $Countries[substr($iban,0,2)]){

			$MovedChar = substr($iban, 4).substr($iban,0,4);
			$MovedCharArray = str_split($MovedChar);
			$NewString = "";

			foreach($MovedCharArray AS $key => $value){
				if(!is_numeric($MovedCharArray[$key])){
					$MovedCharArray[$key] = $Chars[$MovedCharArray[$key]];
				}
				$NewString .= $MovedCharArray[$key];
			}

			if(bcmod($NewString, '97') == 1)
			{
				return true;
			}
		}
		return false;
	}
?>
