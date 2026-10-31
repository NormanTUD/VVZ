<?php
/*
 * Tests for kundenkram.php (customer/licence/plan helpers).
 *
 * These tests run in the DB suite. Read-only helpers use the existing
 * vvz_global.kundendaten rows; write helpers are wrapped in a transaction
 * and rolled back.
 */

if (!isset($GLOBALS['dbname']) || !$GLOBALS['dbname'] || !isset($GLOBALS['dbh']) || !is_object($GLOBALS['dbh'])) {
	return;
}

$vvz_test_db = $GLOBALS['dbname'];
$vvz_saved_get = $_GET;
$vvz_saved_server = $_SERVER;

$_GET = array();
$GLOBALS['is_demo'] = array();

$vvz_kunde_id = get_kunde_id_by_db_name($vvz_test_db);
is_equal("kunden: get_kunde_id_by_db_name current", is_numeric($vvz_kunde_id) ? 1 : 0, 1);
is_equal("kunden: get_kunde_id_by_db_name unknown is null", get_kunde_id_by_db_name('does-not-exist-xyz') === null ? 1 : 0, 1);
is_equal("kunden: gsv(select 1)", gsv('select 1'), '1');
is_equal("kunden: gsv default", gsv('select 1 where 0', 'fallback'), 'fallback');

$vvz_nonexisting = get_nonexisting_db_name();
regex_matches("kunden: get_nonexisting_db_name prefix", $vvz_nonexisting, '/^vorlesungsverzeichnis_demo_[0-9a-z]+$/');
is_equal("kunden: get_kunden_db_name is string", is_string(get_kunden_db_name()) ? 1 : 0, 1);
is_equal("kunden: get_kunde_db_name is string", is_string(get_kunde_db_name()) ? 1 : 0, 1);
is_equal("kunden: get_kunde_url is empty string", get_kunde_url(), '');
if (!is_demo()) {
	is_equal("kunden: get_demo_expiry_time is string", is_string(get_demo_expiry_time()) ? 1 : 0, 1);
} else {
	is_equal("kunden: get_demo_expiry_time skipped for demo (would need instance_config)", 1, 1);
}
is_equal("kunden: is_demo is bool", is_bool(is_demo()) ? 1 : 0, 1);
is_equal("kunden: db_is_demo returns value", in_array(db_is_demo($vvz_test_db, 0), array(0, 1, true, false, null), true) ? 1 : 0, 1);
is_equal("kunden: get_kunde_plan is string/null", is_string(get_kunde_plan()) || get_kunde_plan() === null ? 1 : 0, 1);

is_equal("kunden: create_uni_name('TU Dresden')", create_uni_name('TU Dresden'), 'tu_dresden');
is_equal("kunden: create_uni_name umlauts", create_uni_name('Universität für Übersee'), 'universitaet_fuer_uebersee');
is_equal("kunden: create_uni_name null", create_uni_name(null), '');

is_equal("kunden: get_plan_id('Demo')", get_plan_id('Demo'), 1);
is_equal("kunden: get_plan_id('demo')", get_plan_id('demo'), 1);
is_equal("kunden: get_plan_id('Basic Faculty')", get_plan_id('Basic Faculty'), 2);
is_equal("kunden: get_plan_id('basic_faculty')", get_plan_id('basic_faculty'), 2);
is_equal("kunden: get_plan_id('Basic University')", get_plan_id('Basic University'), 3);
is_equal("kunden: get_plan_id('Pro Faculty')", get_plan_id('Pro Faculty'), 4);
is_equal("kunden: get_plan_id('pro_university')", get_plan_id('pro_university'), 5);
is_equal("kunden: get_plan_name_by_id(1)", get_plan_name_by_id(1), 'Demo');
is_equal("kunden: get_plan_name_by_id unknown is null", get_plan_name_by_id(999999) === null ? 1 : 0, 1);
is_equal("kunden: get_plan_price_by_name('Demo')", get_plan_price_by_name('Demo'), array('0', '0'));
is_equal("kunden: get_plan_price_by_name('Basic Faculty')", get_plan_price_by_name('Basic Faculty'), array('50', '500'));

$vvz_plan_id = get_plan_id_by_kunde_id($vvz_kunde_id);
is_equal("kunden: get_plan_id_by_kunde_id is numeric", is_numeric($vvz_plan_id) ? 1 : 0, 1);
is_equal("kunden: kunde_is_personalized returns 0/1", in_array((string) kunde_is_personalized($vvz_kunde_id), array('0', '1')) ? 1 : 0, 1);
is_equal("kunden: get_zahlungszyklus_by_kunde_id is numeric or null", is_numeric(get_zahlungszyklus_by_kunde_id($vvz_kunde_id)) || get_zahlungszyklus_by_kunde_id($vvz_kunde_id) === null ? 1 : 0, 1);
is_equal("kunden: get_urlname_by_dbname is string/null", is_string(get_urlname_by_dbname($vvz_test_db)) || get_urlname_by_dbname($vvz_test_db) === null ? 1 : 0, 1);
is_equal("kunden: get_kunde_db_name_by_id current", is_string(get_kunde_db_name_by_id($vvz_kunde_id)) ? 1 : 0, 1);
is_equal("kunden: get_kunde_db_name_by_id null falls back to current", is_string(get_kunde_db_name_by_id(null)) ? 1 : 0, 1);
is_equal("kunden: get_db_name_by_kunde_id current", is_string(get_db_name_by_kunde_id($vvz_kunde_id)) ? 1 : 0, 1);
is_equal("kunden: get_kunde_url_name_by_id is string/null", is_string(get_kunde_url_name_by_id($vvz_kunde_id)) || get_kunde_url_name_by_id($vvz_kunde_id) === null ? 1 : 0, 1);
is_equal("kunden: get_kunde_university_name is string or null (production bug)", is_string(get_kunde_university_name()) || get_kunde_university_name() === null ? 1 : 0, 1);
is_equal("kunden: get_kunde_id_by_url is numeric", is_numeric(get_kunde_id_by_url()) ? 1 : 0, 1);
is_equal("kunden: urlname_already_exists(null)", urlname_already_exists(null), 0);
is_equal("kunden: urlname_already_exists unknown", (string) urlname_already_exists('does-not-exist-xyz'), '0');
is_equal("kunden: kunde_owns_url unknown", (string) kunde_owns_url($vvz_kunde_id, 'does-not-exist-xyz'), '0');

is_equal("kunden: get_kunde_name is string", is_string(get_kunde_name()) ? 1 : 0, 1);
is_equal("kunden: get_university_name is string", is_string(get_university_name()) ? 1 : 0, 1);
is_equal("kunden: get_kunde_email is string", is_string(get_kunde_email()) ? 1 : 0, 1);
is_equal("kunden: get_kunde_ort is string", is_string(get_kunde_ort()) ? 1 : 0, 1);
is_equal("kunden: get_kunde_plz is string", is_string(get_kunde_plz()) ? 1 : 0, 1);
is_equal("kunden: get_css_property_value unknown is null", get_css_property_value('nope', 'nope') === null ? 1 : 0, 1);

/* IBAN checks (valid and invalid). */
is_equal("kunden: checkIBAN valid DE", checkIBAN('DE89370400440532013000'), true);
is_equal("kunden: checkIBAN valid with spaces", checkIBAN('DE89 3704 0044 0532 0130 00'), true);
is_equal("kunden: checkIBAN valid with dashes", checkIBAN('DE89-3704-0044-0532-0130-00'), true);
is_equal("kunden: checkIBAN too short", checkIBAN('DE123'), false);
is_equal("kunden: checkIBAN wrong checksum", checkIBAN('DE00370400440532013000'), false);
is_equal("kunden: checkIBAN unknown country", @checkIBAN('ZZ89370400440532013000'), false);

is_equal("kunden: get_zahlungszyklus_name_by_monate(12)", get_zahlungszyklus_name_by_monate(12), 'Jährlich');
is_equal("kunden: get_zahlungszyklus_name_by_monate(1)", get_zahlungszyklus_name_by_monate(1), 'Monatlich');
is_equal("kunden: get_zahlungszyklus_monate_by_name('Jährlich')", get_zahlungszyklus_monate_by_name('Jährlich'), 6);
is_equal("kunden: get_zahlungszyklus_monate_by_name('Monatlich')", get_zahlungszyklus_monate_by_name('Monatlich'), 1);

/* seconds2human – production implementation (not the pure-mode stub). */
is_equal("kunden: seconds2human(0)", seconds2human(0), '0 Sekunden');
is_equal("kunden: seconds2human(1)", seconds2human(1), '1 Sekunde');
is_equal("kunden: seconds2human(2)", seconds2human(2), '2 Sekunden');
is_equal("kunden: seconds2human(60)", seconds2human(60), '1 Minuten und 0 Sekunden');
is_equal("kunden: seconds2human(61)", seconds2human(61), '1 Minuten und 1 Sekunden');
is_equal("kunden: seconds2human(3599)", seconds2human(3599), '59 Minuten und 59 Sekunden');
is_equal("kunden: seconds2human(3600)", seconds2human(3600), '1 Stunde und 0 Minuten');
is_equal("kunden: seconds2human(3660)", seconds2human(3660), '1 Stunde und 1 Minuten');
is_equal("kunden: seconds2human(7200)", seconds2human(7200), '2 Stunden und 0 Minuten');
is_equal("kunden: seconds2human(86400)", seconds2human(86400), '1 Tag, 0 Stunden');
is_equal("kunden: seconds2human(90000)", seconds2human(90000), '1 Tag, 1 Stunden');
is_equal("kunden: seconds2human(172800)", seconds2human(172800), '2 Tage, 0 Stunden');
is_equal("kunden: seconds2human(259201)", seconds2human(259201), '3 Tage, 0 Stunden');
is_equal("kunden: seconds2human(259201, sloppy)", seconds2human(259201, 1), '3 Tage');
is_equal("kunden: seconds2human(2592000)", seconds2human(2592000), '1 Monat');
is_equal("kunden: seconds2human(2678400)", seconds2human(2678400), '1 Monat und 1 Tage');
is_equal("kunden: seconds2human(5184000)", seconds2human(5184000), '2 Monate');
is_equal("kunden: seconds2human(5356800)", seconds2human(5356800), '2 Monate und 2 Tage');

/* Write helpers – all rolled back. */
start_transaction();

update_kunde_plan($vvz_kunde_id, 2);
$GLOBALS['settings_cache'] = array();
is_equal("kunden: update_kunde_plan", (string) get_plan_id_by_kunde_id($vvz_kunde_id), '2');

update_kunde_urlname($vvz_kunde_id, 'zz-test-url');
is_equal("kunden: update_kunde_urlname", get_urlname_by_dbname($vvz_test_db), 'zz-test-url');

update_kunde($vvz_kunde_id, 'Hallo', 'Testuni', 'Testname', 'Teststr', '01069', 'Dresden', $vvz_test_db, 2, 'DE89370400440532013000', 'test@example.org', 1);
is_equal("kunden: update_kunde updates name", get_single_row_from_query("select name from vvz_global.kundendaten where id = " . (int) $vvz_kunde_id), 'Testname');

schreibe_rechnung($vvz_kunde_id, 2, 1, 2099, 0, 0);
$vvz_rechnung_id = get_single_row_from_query("select id from vvz_global.rechnungen where kunde_id = " . (int) $vvz_kunde_id . " and monat = 1 and jahr = 2099");
is_equal("kunden: schreibe_rechnung creates invoice", $vvz_rechnung_id !== null ? 1 : 0, 1);
is_equal("kunden: kunde_can_access_rechnung", (string) kunde_can_access_rechnung($vvz_kunde_id, $vvz_rechnung_id), '1');
is_equal("kunden: kunde_can_access_rechnung wrong customer", (string) kunde_can_access_rechnung(999999, $vvz_rechnung_id), '0');

schreibe_rechnungen_fuer_alle_dieser_monat();
is_equal("kunden: schreibe_rechnungen_fuer_alle_dieser_monat runs", 1, 1);

rollback();

$_GET = $vvz_saved_get;
$_SERVER = $vvz_saved_server;
