<?php
	function get_kunden_name() {
		if(array_key_exists("REDIRECT_URL", $_SERVER)) {
			$url = $_SERVER["REDIRECT_URL"];

			if($url && preg_match("/vvz_([a-z_-]+)/", $url, $matches)) {
				return $matches[1];
			}
		}

		return "uni";
	}
/*
	Global stuff
 */
	$GLOBALS['university_name'] = "TU Dresden";
	$GLOBALS['university_full_name'] = "Technische Universität Dresden";
	$GLOBALS['impressum_university_page'] = "https://tu-dresden.de/impressum";
	$GLOBALS['university_plz_city'] = "01062 Dresden";
	$GLOBALS['institut'] = "Institut für Philosophie";
	$GLOBALS['faculty'] = "Fakultät für Philosophie";
	$GLOBALS['ansprechpartner'] = "Norbert Engemaier";
	$GLOBALS['ansprechpartner_tel_nr'] = "+49 351 463-32890";
	$GLOBALS['ansprechpartner_email'] = "norbert.engemaier@tu-dresden.de";
	$GLOBALS['vvz_base_url'] = "vvz.phil.tu-dresden.de";
	$GLOBALS['university_page_url'] = "https://tu-dresden.de/";
	$GLOBALS["calname"] = "Philosophie";
	$GLOBALS['timezone_name'] = "Europe/Berlin";

/*
	Navigator

	For campus navigator. This makes the assumption that the navigator's base url, when appended the abbreviation of a building,
	leads to the pages' site about that building.
 */

	$GLOBALS['navigator_base_url'] = "https://navigator.tu-dresden.de/karten/dresden/geb/";

/*
	DB Config

	Set the password in the file '/etc/vvzdbpw'
 */
	$GLOBALS['dbname'] = get_kunden_name();
	$GLOBALS["db_username"] = 'root';

/*
	Email Settings
 */
	$GLOBALS['from_email'] = "vvz.phil@tu-dresden.de";
	$GLOBALS['admin_email'] = "norman.koch@tu-dresden.de";
	$GLOBALS['admin_name'] = "Norman Koch";

	$GLOBALS['name_non_technical'] = "Holm Bräuer";
	$GLOBALS['to_non_technical'] = "holm.braeuer@tu-dresden.de";
	$GLOBALS['cc_non_technical'] = array('nengemaier@gmail.com', $GLOBALS['admin_email']);
?>
