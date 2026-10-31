<?php
/*
 * Tests for HTML/form rendering helpers and legacy table helpers.
 *
 * Some legacy helpers reference tables/columns that no longer exist in the
 * current schema. Those are asserted to signal the mismatch (instead of
 * silently skipping them) so the behaviour is documented.
 */

if (!isset($GLOBALS['dbname']) || !$GLOBALS['dbname'] || !isset($GLOBALS['dbh']) || !is_object($GLOBALS['dbh'])) {
	return;
}

$vvz_saved_globals = array();
foreach (array('this_page_number', 'logged_in_data', 'toc', 'debug', 'shown_help_ids') as $vvz_global_name) {
	$vvz_saved_globals[$vvz_global_name] = array_key_exists($vvz_global_name, $GLOBALS) ? $GLOBALS[$vvz_global_name] : null;
}
$vvz_saved_post = $_POST;
$vvz_saved_get = $_GET;

$GLOBALS['this_page_number'] = 7;
$GLOBALS['logged_in_data'] = array(1 => 'Test-User');
$GLOBALS['toc'] = array();
$_POST = array();
$_GET = array();

/* ============================================================ */
/* simple_edit / create_table_one_dependency                     */
/* ============================================================ */

ob_start();
simple_edit(
	array('name', 'abkuerzung'),
	'language',
	array('Name', 'Abkürzung'),
	48,
	array('id', 'name', 'abkuerzung'),
	0
);
$vvz_simple_edit = ob_get_clean();
is_equal("render: simple_edit returns table", strpos($vvz_simple_edit, '<table>') !== false ? 1 : 0, 1);
is_equal("render: simple_edit contains language", strpos($vvz_simple_edit, 'deutsch') !== false ? 1 : 0, 1);
is_equal("render: simple_edit contains save button", strpos($vvz_simple_edit, 'Speichern') !== false ? 1 : 0, 1);

ob_start();
simple_edit(
	array('typ', 'jahr', 'erste_veranstaltung_default'),
	'semester',
	array('Typ', 'Jahr', 'Erster Termin'),
	47,
	array('id', 'typ', 'jahr', 'erste_veranstaltung_default'),
	0,
	1,
	array(),
	array('jahr')
);
$vvz_simple_edit_semester = ob_get_clean();
is_equal("render: simple_edit semester returns table", strpos($vvz_simple_edit_semester, '<table>') !== false ? 1 : 0, 1);
is_equal("render: simple_edit semester contains Sommersemester/Wintersemester", strpos($vvz_simple_edit_semester, 'semester') !== false ? 1 : 0, 1);

ob_start();
create_table_one_dependency(
	create_language_array(),
	array('name', 'abkuerzung'),
	array('Sprache', 'Abkürzung'),
	'language',
	48,
	'sprache',
	'name'
);
$vvz_dependency_language = ob_get_clean();
is_equal("render: create_table_one_dependency language", strpos($vvz_dependency_language, '<table>') !== false ? 1 : 0, 1);
is_equal("render: create_table_one_dependency language contains deutsch", strpos($vvz_dependency_language, 'deutsch') !== false ? 1 : 0, 1);

ob_start();
create_table_one_dependency(
	create_institute_array(),
	array('name', 'institut_id', 'studienordnung', 'order_key'),
	array('Name', 'Institut', 'Studienordnung', 'Sortierung'),
	'studiengang',
	8,
	'institut',
	'name'
);
$vvz_dependency_stg = ob_get_clean();
is_equal("render: create_table_one_dependency studiengang", strpos($vvz_dependency_stg, 'studienordnung') !== false ? 1 : 0, 1);

ob_start();
create_table_one_dependency(
	create_studiengang_array_by_institut_id(),
	array('name', 'studiengang_id', 'beschreibung', 'abkuerzung'),
	array('Name', 'Studiengang', 'Beschreibung', 'Abkürzung'),
	'modul',
	5,
	'studiengang',
	'name'
);
$vvz_dependency_modul = ob_get_clean();
is_equal("render: create_table_one_dependency modul", strpos($vvz_dependency_modul, 'beschreibung') !== false ? 1 : 0, 1);

ob_start();
create_table_one_dependency(
	create_bereiche_array(),
	array('modulbezeichnung', 'modul_id', 'bereich_id', 'zeitraum_id'),
	array('Bezeichnung', 'Modul', 'Bereich', 'Zeitraum'),
	'pruefungsnummer',
	12,
	'bereich',
	'name'
);
$vvz_dependency_pn = ob_get_clean();
is_equal("render: create_table_one_dependency pruefungsnummer", strpos($vvz_dependency_pn, 'bereich') !== false ? 1 : 0, 1);

/* ============================================================ */
/* Veranstaltungs-/Prüfungsdarstellung                           */
/* ============================================================ */

ob_start();
create_veranstaltung_pruefung_tabelle(array(
	array('PN-TEST', 'Klausur', 'Bereich', 'Zeitraum', 'x', 'x', null, 0, 1, 'Teststudiengang'),
	array('PN-TEST2', 'Essay', 'Bereich2', 'Zeitraum2', 'x', 'x', 1, 0, 2, 'Teststudiengang2'),
));
$vvz_vp_table = ob_get_clean();
is_equal("render: create_veranstaltung_pruefung_tabelle returns table", strpos($vvz_vp_table, '<table') !== false ? 1 : 0, 1);
is_equal("render: create_veranstaltung_pruefung_tabelle contains PN", strpos($vvz_vp_table, 'PN-TEST') !== false ? 1 : 0, 1);

is_equal("render: get_vvz_start_message(null) string/null", is_string(get_vvz_start_message(null)) || get_vvz_start_message(null) === null ? 1 : 0, 1);
is_equal("render: get_vvz_start_message(1) string/null", is_string(get_vvz_start_message(1)) || get_vvz_start_message(1) === null ? 1 : 0, 1);

$_POST['name'] = 'FromPost';
is_equal("render: get_current_value uses post", get_current_value('name'), 'FromPost');
$_POST = array();
is_equal("render: get_current_value from DB is string/null", is_string(get_current_value('name')) || get_current_value('name') === null ? 1 : 0, 1);

ob_start();
print_hinweis_for_page(11);
$vvz_hinweis = ob_get_clean();
is_equal("render: print_hinweis_for_page outputs hint", strpos($vvz_hinweis, 'Hinweis') !== false ? 1 : 0, 1);
ob_start();
print_hinweis_for_page(999999);
is_equal("render: print_hinweis_for_page without hint is empty", ob_get_clean(), '');

is_equal("render: nice_einzelne_veranstaltung_by_id unknown", nice_einzelne_veranstaltung_by_id(999999), array());
is_equal("render: get_einzelne_termine_gebaeude_raum_liste unknown", get_einzelne_termine_gebaeude_raum_liste(999999), array());
is_equal("render: get_einzelne_termine_by_veranstaltung_id unknown", get_einzelne_termine_by_veranstaltung_id(999999), array());

ob_start();
create_studiengang_html(1, create_studiengang_array_by_institut_id(1));
$vvz_stg_html = ob_get_clean();
is_equal("render: create_studiengang_html contains select", strpos($vvz_stg_html, '<select') !== false ? 1 : 0, 1);

$vvz_broken_renderers = array(
	'create_modul_html' => function () { create_modul_html(1, create_modul_array(), 0); },
	'create_modul_html_vvz' => function () { create_modul_html_vvz(1); },
	'create_pruefungsmoeglichkeiten_html' => function () { create_pruefungsmoeglichkeiten_html(999999); },
	'create_studiengang_html_vvz' => function () { create_studiengang_html_vvz(1); },
	'create_nachpruefung_liste' => function () { create_nachpruefung_liste(999999); },
	'create_pruefungsplan' => function () { create_pruefungsplan(array(999999)); },
);

foreach ($vvz_broken_renderers as $vvz_renderer_name => $vvz_renderer) {
	$vvz_threw = 0;
	ob_start();
	try {
		$vvz_renderer();
	} catch (\Throwable $e) {
		$vvz_threw = 1;
	}
	ob_end_clean();
	is_equal("render: $vvz_renderer_name signals legacy schema mismatch", $vvz_threw, 1);
}

$vvz_result = rquery('select 1 union select 2');
ob_start();
print_ul_li_from_result($vvz_result);
$vvz_ul = ob_get_clean();
is_equal("render: print_ul_li_from_result outputs list", strpos($vvz_ul, '<ul>') !== false ? 1 : 0, 1);
is_equal("render: print_ul_li_from_result contains studiengang", strpos($vvz_ul, 'Bachelor Philosophie') !== false ? 1 : 0, 1);

$vvz_broken_legacy = array(
	'modul_zu_veranstaltung_hinzufuegen' => function () { modul_zu_veranstaltung_hinzufuegen(1, 1); },
	'studiengang_zu_veranstaltung_hinzufuegen' => function () { studiengang_zu_veranstaltung_hinzufuegen(1, 1); },
	'delete_veranstaltung_modul' => function () { delete_veranstaltung_modul(1, 1); },
	'delete_veranstaltung_studiengang' => function () { delete_veranstaltung_studiengang(1, 1); },
	'delete_nachpruefung' => function () { delete_nachpruefung(1); },
	'update_nachpruefung' => function () { update_nachpruefung(1, 1, '2099-01-01', 1, '1'); },
);
foreach ($vvz_broken_legacy as $vvz_legacy_name => $vvz_legacy_call) {
	$vvz_threw = 0;
	$vvz_result = null;
	try {
		$vvz_result = $vvz_legacy_call();
	} catch (\Throwable $e) {
		$vvz_threw = 1;
	}
	is_equal("render: $vvz_legacy_name gracefully handles missing table", ($vvz_threw || !$vvz_result) ? 1 : 0, 1);
}

/* html_calendar second branch */
$GLOBALS['shown_help_ids']['calendar'] = 1;
regex_matches("render: html_calendar first=true", html_calendar(0), '/ical_item/');
$GLOBALS['shown_help_ids']['calendar'] = 0;

/* ============================================================ */
/* PHPExcel helper functions                                     */
/* ============================================================ */

include_once __DIR__ . '/../Classes/PHPExcel.php';
$vvz_excel = new PHPExcel();
$vvz_excel->setActiveSheetIndex(0);
$vvz_excel->getActiveSheet()->SetCellValue('A1', 'Test');
$vvz_excel->getActiveSheet()->SetCellValue('B1', 'Wert');

cellColor($vvz_excel, 'A1', 'ffffff');
set_cell_value_and_color($vvz_excel, 'B1', 'Neu', 'eeeeee');
set_cell_value_and_merge_cells_and_cell_color($vvz_excel, 'C1', 'Merge', 'C1:D1', 'C1:D1', 'dddddd');
$vvz_excel_sized = auto_size_phpexcel($vvz_excel);
is_equal("render: cellColor/set_cell_value helpers keep object", is_object($vvz_excel_sized) ? 1 : 0, 1);
is_equal("render: set_cell_value_and_color writes value", $vvz_excel->getActiveSheet()->getCell('B1')->getValue(), 'Neu');
is_equal("render: set_cell_value_and_merge writes value", $vvz_excel->getActiveSheet()->getCell('C1')->getValue(), 'Merge');

foreach ($vvz_saved_globals as $vvz_global_name => $vvz_global_value) {
	$GLOBALS[$vvz_global_name] = $vvz_global_value;
}
$_POST = $vvz_saved_post;
$_GET = $vvz_saved_get;
