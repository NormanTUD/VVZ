<?php
	require_once(dirname(__DIR__).'/usability_helpers.php');

	is_equal('usability: empty value is not set', vvz_value_is_set(''), false);
	is_equal('usability: null value is not set', vvz_value_is_set(null), false);
	is_equal('usability: zero string is set', vvz_value_is_set('0'), true);
	is_equal('usability: empty array is not set', vvz_value_is_set(array()), false);
	is_equal('usability: array with empty value is not set', vvz_value_is_set(array('')), false);
	is_equal('usability: array with value is set', vvz_value_is_set(array('', '5')), true);

	is_equal('usability: no active filters', vvz_filter_is_active(array()), false);
	is_equal('usability: empty title filter is inactive', vvz_filter_is_active(array('vltitel' => '')), false);
	is_equal('usability: title filter is active', vvz_filter_is_active(array('vltitel' => 'Logik')), true);
	is_equal('usability: empty module array is inactive', vvz_filter_is_active(array('modul' => array())), false);
	is_equal('usability: module array is active', vvz_filter_is_active(array('modul' => array('1'))), true);
	is_equal('usability: active filter count', vvz_filter_active_count(array('vltitel' => 'x', 'modul' => array(''), 'wochentag' => 'Mo')), 2);

	is_equal(
		'usability: startseite url keeps context and filters',
		vvz_startseite_url(array('semester' => '3', 'institut' => '2', 'studiengang' => '5', 'bereich' => '4', 'vltitel' => 'x', 'wochentag' => 'Mo', 'opal_zwingend' => '1'), true),
		'startseite?semester=3&institut=2&studiengang=5&bereich=4&vltitel=x&wochentag=Mo&opal_zwingend=1'
	);
	is_equal(
		'usability: reset url keeps only context',
		vvz_filter_reset_url(array('semester' => '3', 'institut' => '2', 'studiengang' => '5', 'bereich' => '4', 'vltitel' => 'x', 'wochentag' => 'Mo', 'opal_zwingend' => '1')),
		'startseite?semester=3&institut=2&studiengang=5&bereich=4'
	);
	is_equal(
		'usability: remove url removes one filter',
		vvz_filter_remove_url(array('semester' => '3', 'studiengang' => '5', 'vltitel' => 'x', 'wochentag' => 'Mo'), 'wochentag'),
		'startseite?semester=3&studiengang=5&vltitel=x'
	);
	is_equal(
		'usability: remove url without filters falls back to context',
		vvz_filter_remove_url(array('studiengang' => '5', 'vltitel' => 'x'), 'vltitel'),
		'startseite?studiengang=5'
	);

?>
