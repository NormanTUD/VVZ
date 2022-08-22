<?php
	$GLOBALS["error_page_shown"] = 0;
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
		return $name;
	}

	function get_kunden_db_name() {
		if(array_key_exists("new_uni_name", $_GET)) {
			print "Die neue Uni wird erstellt. Bitte warten";
			flush();
			print '<meta http-equiv="refresh" content="0; url=v/'.create_uni_name($_GET["new_uni_name"]).'/" />';
			flush();
			exit;
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
		if(array_key_exists('REQUEST_URI', $_SERVER) && preg_match("/^\Q".$n."\E/", $_SERVER['REQUEST_URI']) || preg_match("/^v\/".$n."/", $_SERVER["REQUEST_URI"])) {
			return "";
		} else {
			return "v/$n/";
		}
	}
?>
