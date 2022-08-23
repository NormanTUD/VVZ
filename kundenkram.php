<?php
	$GLOBALS["error_page_shown"] = 0;

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
		$name = preg_replace("/_+/", "_", $name);
		$name = preg_replace("/-+/", "_", $name);
		$name = preg_replace("/ä/", "ae", $name);
		$name = preg_replace("/ü/", "ue", $name);
		$name = preg_replace("/ö/", "oe", $name);
		$name = preg_replace("/ß/", "ss", $name);
		$name = preg_replace("/_$/", "", $name);
		$name = preg_replace("/[^a-z_]/", "", $name);
		$name = preg_replace("/technische_universitaet_/", "tu_", $name);
		$name = preg_replace("/^_+/", "", $name);
		return $name;
	}

	function get_kunden_db_name() {
		if(array_key_exists("new_uni_name", $_GET)) {
			print "Die neue Uni wird erstellt. Bitte warten...";
			flush();
			print '<meta http-equiv="refresh" content="0; url=v/'.create_uni_name(get_uni_name()).'/" />';
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
		if($h) {
			return "$h Stunden und $m Minuten";
		}

		if($m) {
			return "$m Minuten und $s Sekunden";
		}

		return "$s Sekunden";
	}
	
	function get_kunde_plan () {
		$query = "select p.name from ".get_kunden_db_name().".instance_config ic left join plan p on ic.plan_id = p.id";
		return get_single_row_from_query($query);
	}

	function get_demo_expiry_time() {
		if(get_kunde_plan() == "Demo") {
			$ablauftimer = get_single_row_from_query("select now() - installation_date from ".get_kunden_db_name().".instance_config");
			$ablauftimer = seconds2human(86400 - $ablauftimer);
			return "<br><span class='demo_string'>Diese Installation ist eine Demo. Das heißt: sie wird nach 24 Stunden gelöscht.<br>Ihnen verbleiden noch ".$ablauftimer." zum Testen.</span><br>";
		}
		return "";
	}
?>
