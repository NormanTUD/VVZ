<?php
	$GLOBALS["error_page_shown"] = 0;
	$GLOBALS['function_usage'] = array();
	$GLOBALS['rquery_print'] = 0;

	include_once("mysql.php");

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

	function rquery ($internalquery, $die = 1) {
		$debug_backtrace = debug_backtrace();
		$caller_file = $debug_backtrace[0]['file'];
		$caller_line = $debug_backtrace[0]['line'];
		$caller_function = '';
		if(array_key_exists(1, $debug_backtrace) && array_key_exists('function', $debug_backtrace[1])) {
			$caller_function = $debug_backtrace[1]['function'];
		}
		$start = microtime(true);
		$result = mysqli_query($GLOBALS['dbh'], $internalquery);
		$end = microtime(true);
		$used_time = $end - $start;
		$numrows = "&mdash;";
		if(!is_bool($result)) {
			$numrows = mysqli_num_rows($result);
		}
		$GLOBALS['queries'][] = array('query' => "/* $caller_file, $caller_line".($caller_function ? " ($caller_function)" : '').": */\n$internalquery", 'time' => $used_time, 'numrows' => $numrows);

		if($caller_function) {
			if(array_key_exists($caller_function, $GLOBALS['function_usage'])) {
				$GLOBALS['function_usage'][$caller_function]['count']++;
				$GLOBALS['function_usage'][$caller_function]['time'] += $used_time;
			} else {
				$GLOBALS['function_usage'][$caller_function]['count'] = 1;
				$GLOBALS['function_usage'][$caller_function]['time'] = $used_time;
				$GLOBALS['function_usage'][$caller_function]['name'] = $caller_function;
			}
		}

		if(!$result) {
			if($die) {
				if($GLOBALS['dbh']) {
					dier("Ung&uuml;ltige Anfrage: <p><pre>".$internalquery."</pre></p>".htmlentities(mysqli_error($GLOBALS['dbh'])), 0, 1);
				} else {
					dier("Ung&uuml;ltige Anfrage: <p><pre>".htmlentities($internalquery)."</pre></p><p>DBH undefined! This must never happen unless there is something seriously wrong with the database.</p>", 0, 0);
				}
			}
		}

		if($GLOBALS['rquery_print']) {
			print "<p>".htmlentities($internalquery)."</p>\n";
		}

		return $result;
	}



	function get_single_row_from_query ($query, $default = NULL) {
		$result = rquery($query);
		return get_single_row_from_result($result, $default);
	}

	function esc ($parameter) { 
		if(!is_array($parameter)) { // Kein array
			if(isset($parameter) && strlen($parameter)) {
				return '"'.mysqli_real_escape_string($GLOBALS['dbh'], $parameter).'"';
			} else {
				return 'NULL';
			}
		} else { // Array
			$str = join(', ', array_map('esc', array_map('my_mysqli_real_escape_string', $parameter)));
			return $str;
		}
	}



	function get_post ($name) {
		if(array_key_exists($name, $_POST)) {
			return $_POST[$name];
		} else {
			return NULL;
		}
	}


	function get_kunde_id_by_db_name ($dbn) {
		$query = "select kunde_id from instance_config where dbname = ".esc($dbn);
		return get_single_row_from_query($query);
	}

	function get_uni_name () {
		if(array_key_exists("new_uni_name", $_GET)) {
			return $_GET["new_uni_name"];
		}
		if(array_key_exists("REDIRECT_SFURI", $_SERVER)) {
			return "db_vvz_".get_kunden_db_name();
			print "Die neue Uni wird erstellt. Bitte warten (A)...";
			flush();
			print '<meta http-equiv="refresh" content="0; url=v/'.create_uni_name(get_uni_name()).'/" />';
			flush();
		}

		return "db_vvz_" + get_kunden_db_name();
	}

	function create_uni_name ($name) {
		$name = strtolower($name ?? "");
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
		$name = preg_replace("/[^a-z_]/", "", $name);
		$name = preg_replace("/technische_universitaet_/", "tu_", $name);
		$name = preg_replace("/^_+/", "", $name);
		return $name;
	}

	function get_kunden_db_name() {
		if(array_key_exists("new_demo_uni", $_GET)) {
			print "Die neue Uni wird erstellt. Bitte warten (B)...";
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

		return "startpage";
	}

	function get_kunde_name() {
		$dbn = get_kunden_db_name();
		$n = preg_replace("/^db_vvz_/", "", $dbn);
		return $n;
	}

	function get_kunde_url() {
		$n = get_kunde_name();
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
		$query = "select p.name from ".get_kunden_db_name().".instance_config ic left join plan p on ic.plan_id = p.id";
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
		$query = "select personalized from kundendaten where id = ".esc($id);
		return get_single_row_from_query($query);
	}

	function update_kunde ($id, $anrede, $firma, $kundename, $kundestrasse, $kundeplz, $kundeort) {
		$query = 'update kundendaten set anrede = '.esc($anrede).', firma = '.esc($firma).', kundename = '.esc($kundename).', kundestrasse = '.esc($kundestrasse).', kundeplz = '.esc($kundeplz).', kundeort = '.esc($kundeort).', personalized = 1 where id = '.esc($id);
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
				die("Unknown plan: >>".get_get("product")."<<");
				break;
		}

		return $plan_id;
	}

	function get_plan_price_by_name($name) {
		$plan_id = get_plan_id($name);

		$monatlich_query = "select monatliche_zahlung from plan where id = ".esc($plan_id);
		$monatlich = get_single_row_from_query($monatlich_query);
		$jaehrlich = get_single_row_from_query("select jaehrliche_zahlung from plan where id = ".esc($plan_id));

		return [$monatlich, $jaehrlich];
	}

	if(get_post("update_kunde_data")) {
		$kunde_id = get_kunde_id_by_db_name(get_kunden_db_name());

		if($kunde_id && get_post("anrede") && get_post("firma") && get_post("kundename") && get_post("kundestrasse") && get_post("kundeplz") && get_post("kundeort")) {
			update_kunde($kunde_id, "anrede", "firma", "kundename", "kundestrasse", "kundeplz", "kundeort");
		}

		if(get_post("name_vvz")) {

		}

		if(get_post("daten_uebernehmen")) {

		}
	}

	function is_demo () {
		if(get_kunde_plan() == "Demo") {
			return true;
		}
		return false;
	}

	function db_is_demo ($db) {
		if(database_exists($db) && table_exists($db, "instance_config") && table_exists($db, "plan")) {
			$query = "select p.name from ".$db.".instance_config ic left join plan p on ic.plan_id = p.id";
			return get_single_row_from_query($query) == "Demo" ? 1 : 0;
		}
		return 0;
	}

	function database_exists ($name) {
		$query = "SHOW DATABASES LIKE ".esc($name);
		$result = rquery($query);
		while ($row = mysqli_fetch_row($result)) {
			return 1;
		}
		return 0;
	}



	/*
	 * @Name: Mysql Mariadb Rename Database PHP
	 * @Author: Max Base
	 * @Date: 2020-12-26
	 * @Repository: https://github.com/basemax/mysql-mariadb-rename-database-php
	 */

	function rename_db ($from, $to, $reload) {
		if(database_exists($to)) {
			return;
		}
		// Limit
		ini_set('max_execution_time', 0);
		set_time_limit(0);


		// Config
		$tables = [];

		// Open
		$mysqli = new mysqli("localhost", $GLOBALS["db_username"], $GLOBALS["db_password"], $from);
		if($mysqli->connect_error) {
			die("Connection failed: " . $mysqli->connect_error);
		}

		
		$sql = "CREATE DATABASE ".$to.";";
		$result = mysqli_query($mysqli, $sql);;

		$mysqli->autocommit(FALSE);

		$sql = "SHOW tables;";
		$result = mysqli_query($mysqli, $sql);
		if(mysqli_num_rows($result) > 0) {
			while($row = mysqli_fetch_assoc($result)) {
				if(isset($row["Tables_in_".$from])) {
					$tables[] = $row["Tables_in_".$from];
				}
			}
		}

		// Print tables
		#print_r($tables);

		// Move tables
		$mysqli->query("SET FOREIGN_KEY_CHECKS = 0;");
		foreach($tables as $table) {
			$newTable = $to. ".".$table;
			$table = $from.".".$table;
			$query = "ALTER TABLE $table RENAME $newTable;";
			$mysqli->query($query);
		}
		$mysqli->query("SET FOREIGN_KEY_CHECKS = 1;");

		// Close transactions
		$mysqli->commit();

		foreach($tables as $table) {
			$mysqli->query("DROP TABLE ".$table);
		}

		// Close
		mysqli_close($mysqli);

		if($reload) {
			print "Die Uni wird umbenannt. Bitte warten (A)...";
			flush();
			print '<meta http-equiv="refresh" content="0; url=/v/'.create_uni_name($to).'/" />';
			flush();
			exit;
		}
	}

	#rename_db("db_vvz_wcefv_snwuiao_utsn", "TESTADASDASDAAAAAAAAAAASDASD", 1);
?>
