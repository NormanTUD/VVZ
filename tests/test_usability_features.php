<?php
	if(!isset($GLOBALS['dbname']) || !$GLOBALS['dbname'] || !isset($GLOBALS['dbh']) || !is_object($GLOBALS['dbh'])) {
		return;
	}

	require_once(dirname(__DIR__).'/usability_helpers.php');
	include_once(dirname(__DIR__).'/startseite_functions.php');

	$saved_globals = array();
	foreach(array('this_institut', 'this_semester', 'memoize', 'get_modul_name_cache', 'logged_in_user_id', 'metadata_shown') as $global_name) {
		$saved_globals[$global_name] = array_key_exists($global_name, $GLOBALS) ? $GLOBALS[$global_name] : null;
	}
	$saved_get = $_GET;
	$saved_post = $_POST;

	$_GET = array();
	$_POST = array();
	$GLOBALS['memoize'] = array();

	start_transaction();

	$tag = 'ZZUsability'.substr(md5(uniqid('', true)), 0, 8);

	$institut_id = get_single_row_from_query('select id from institut order by id limit 1');
	if(!$institut_id) {
		rquery('insert into institut (name, start_nr) values ('.esc($tag.' Institut').', 1)');
		$institut_id = mysqli_insert_id($GLOBALS['dbh']);
	}

	$semester_id = get_current_semester_id();
	if(!$semester_id) {
		rquery('insert into semester (jahr, typ, `default`) values (2099, "Sommersemester", "1")');
		$semester_id = mysqli_insert_id($GLOBALS['dbh']);
	}

	rquery('insert into veranstaltungstyp (name, abkuerzung) values ('.esc($tag.' Typ').', '.esc('Z'.substr($tag, -4)).')');
	$veranstaltungstyp_id = mysqli_insert_id($GLOBALS['dbh']);

	rquery('insert into dozent (first_name, last_name, ausgeschieden) values ("Dozent", '.esc($tag).', "0")');
	$dozent_id = mysqli_insert_id($GLOBALS['dbh']);

	rquery('insert into veranstaltung (veranstaltungstyp_id, name, dozent_id, institut_id, semester_id) values ('.esc($veranstaltungstyp_id).', '.esc($tag.' Veranstaltung').', '.esc($dozent_id).', '.esc($institut_id).', '.esc($semester_id).')');

	rquery('insert into studiengang (name, institut_id, studienordnung, order_key) values ('.esc($tag.' Studiengang').', '.esc($institut_id).', "x", 1)');
	$studiengang_id = mysqli_insert_id($GLOBALS['dbh']);

	rquery('insert into modul (name, studiengang_id, beschreibung, abkuerzung) values ('.esc($tag.' Modul').', '.esc($studiengang_id).', "b", '.esc('M'.substr($tag, -4)).')');
	$modul_id = mysqli_insert_id($GLOBALS['dbh']);

	rquery('insert into gebaeude (abkuerzung, name) values ('.esc('ZZ'.substr($tag, -5)).', '.esc($tag.' Gebäude').')');
	$gebaeude_id = mysqli_insert_id($GLOBALS['dbh']);

	rquery('insert into pruefungstyp (name) values ('.esc($tag.' Prüfungstyp').')');
	$pruefungstyp_id = mysqli_insert_id($GLOBALS['dbh']);

	$bereich_id = get_single_row_from_query('select id from bereich order by id limit 1');
	if(!$bereich_id) {
		rquery('insert into bereich (name) values ('.esc($tag.' Bereich').')');
		$bereich_id = mysqli_insert_id($GLOBALS['dbh']);
	}

	$zeitraum_id = get_single_row_from_query('select id from pruefung_zeitraum order by id limit 1');
	if(!$zeitraum_id) {
		rquery('insert into pruefung_zeitraum (name) values ('.esc($tag.' Zeitraum').')');
		$zeitraum_id = mysqli_insert_id($GLOBALS['dbh']);
	}

	rquery('insert into pruefungsnummer (pruefungsnummer, modul_id, pruefungstyp_id, bereich_id, modulbezeichnung, zeitraum_id) values ('.esc($tag.'-PN').', '.esc($modul_id).', '.esc($pruefungstyp_id).', '.esc($bereich_id).', '.esc($tag.' Modul').', '.esc($zeitraum_id).')');

	$results = vvz_search_results($tag, 'public', null);
	is_equal('usability: public search returns an array', is_array($results), true);
	is_equal('usability: public search finds all categories', count($results) >= 6, true);

	$by_category = array();
	$public_ids = array();
	foreach($results as $result) {
		if(!is_array($result) || !isset($result['category']) || !isset($result['id'])) {
			continue;
		}
		$by_category[$result['category']] = $result;
		$public_ids[] = $result['id'];
	}

	foreach(array('Veranstaltung', 'Dozent', 'Modul', 'Studiengang', 'Prüfungsnummer', 'Gebäude') as $category) {
		is_equal('usability: public search category '.$category, isset($by_category[$category]), true);
	}

	foreach($public_ids as $public_id) {
		regex_matches('usability: public search link targets startseite', $public_id, '/^goto_page=startseite\?/');
		regex_fails('usability: public search link never targets admin', $public_id, '/admin\?/');
	}

	regex_matches('usability: event link contains title filter', $by_category['Veranstaltung']['id'] ?? '', '/vltitel=/');
	regex_matches('usability: event link contains institute', $by_category['Veranstaltung']['id'] ?? '', '/institut=/');
	regex_matches('usability: event link contains semester', $by_category['Veranstaltung']['id'] ?? '', '/semester=/');
	regex_matches('usability: lecturer link contains dozent', $by_category['Dozent']['id'] ?? '', '/dozent=/');
	regex_matches('usability: module link contains modul', $by_category['Modul']['id'] ?? '', '/modul=/');
	regex_matches('usability: study programme link contains studiengang', $by_category['Studiengang']['id'] ?? '', '/studiengang=/');
	regex_matches('usability: exam number link contains pruefungsnummer_id', $by_category['Prüfungsnummer']['id'] ?? '', '/pruefungsnummer_id=/');
	regex_matches('usability: building link contains gebaeude', $by_category['Gebäude']['id'] ?? '', '/gebaeude=/');

	$empty_results = vvz_search_results('', 'public', null);
	is_equal('usability: empty search term returns error', isset($empty_results['error']), true);

	$admin_results = vvz_search_results($tag, 'admin', 1);
	$admin_event_id = null;
	foreach($admin_results as $admin_result) {
		if(isset($admin_result['category']) && $admin_result['category'] === 'Veranstaltung') {
			$admin_event_id = $admin_result['id'];
			break;
		}
	}
	regex_matches('usability: admin search still links to admin', (string) $admin_event_id, '/^goto_page=admin\?/');

	$GLOBALS['this_institut'] = $institut_id;
	$GLOBALS['this_semester'] = array($semester_id, 'Sommersemester', 2099);

	$_GET = array('studiengang' => 'alle', 'institut' => $institut_id, 'vltitel' => 'Logik', 'wochentag' => 'Mo');
	ob_start();
	show_filter();
	$filter_html = ob_get_clean();
	is_equal('usability: active filter panel is open', strpos($filter_html, 'class="filter_open"') !== false, true);
	is_equal('usability: filter has label for title', strpos($filter_html, '<label for="filter_vltitel">') !== false, true);
	is_equal('usability: filter input has id', strpos($filter_html, 'id="filter_vltitel"') !== false, true);
	is_equal('usability: filter has select labels', strpos($filter_html, '<label for="filter_wochentag">') !== false, true);
	is_equal('usability: active filter summary is shown', strpos($filter_html, 'class="filter_summary"') !== false, true);
	is_equal('usability: active filter summary contains reset link', strpos($filter_html, 'Filter zurücksetzen') !== false, true);
	is_equal('usability: filter form preserves institute context', strpos($filter_html, 'name="institut"') !== false, true);

	$_GET = array('studiengang' => 'alle');
	ob_start();
	show_filter();
	$closed_filter_html = ob_get_clean();
	is_equal('usability: inactive filter panel stays closed', strpos($closed_filter_html, 'filter_open') === false, true);
	is_equal('usability: inactive filter has no summary', strpos($closed_filter_html, 'filter_summary') === false, true);

	$_GET = array('studiengang' => 'alle', 'vltitel' => 'Nichts gefunden');
	ob_start();
	show_no_veranstaltungen_gefunden();
	$empty_html = ob_get_clean();
	is_equal('usability: empty state explains the result', strpos($empty_html, 'keine Veranstaltungen gefunden') !== false, true);
	is_equal('usability: empty state offers reset', strpos($empty_html, 'Filter zurücksetzen') !== false, true);
	is_equal('usability: empty state offers search removal', strpos($empty_html, 'entfernen') !== false, true);
	is_equal('usability: empty state offers all events', strpos($empty_html, 'Alle Veranstaltungen dieses Semesters anzeigen') !== false, true);

	ob_start();
	show_global_search_input();
	$search_html = ob_get_clean();
	is_equal('usability: global search field exists', strpos($search_html, 'id="globalsearch"') !== false, true);
	is_equal('usability: global search is public scoped', strpos($search_html, 'data-search-scope="public"') !== false, true);
	is_equal('usability: global search has a label', strpos($search_html, '<label class="visually_hidden" for="globalsearch">') !== false, true);

	$css = file_get_contents(dirname(__DIR__).'/data/style.css');
	regex_matches('usability: css opens active filters', $css, '/#filter\.filter_open\s*\{/');
	regex_matches('usability: css has responsive filter layout', $css, '/@media\s*\(max-width:\s*700px\)[\s\S]*#filter table/');
	regex_matches('usability: css styles skip link', $css, '/\.skip-link\s*\{/');

	$header_source = file_get_contents(dirname(__DIR__).'/header.php');
	regex_matches('usability: header contains skip link', $header_source, '/class="skip-link"/');

	rollback();

	$_GET = $saved_get;
	$_POST = $saved_post;
	foreach($saved_globals as $global_name => $global_value) {
		$GLOBALS[$global_name] = $global_value;
	}
?>
