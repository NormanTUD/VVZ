<?php

	if(!function_exists('vvz_value_is_set')) {
		function vvz_value_is_set ($value) {
			if(is_array($value)) {
				foreach($value as $single_value) {
					if(vvz_value_is_set($single_value)) {
						return true;
					}
				}
				return false;
			}
			return $value !== null && $value !== false && $value !== '';
		}
	}

	if(!function_exists('vvz_filter_keys')) {
		function vvz_filter_keys () {
			return array(
				'vltitel',
				'modul',
				'veranstaltungstyp_id',
				'wochentag',
				'stunde',
				'dozent',
				'gebaeude',
				'pruefungstyp',
				'opal_zwingend',
				'pruefungsnummer_id',
				'fakultaet'
			);
		}
	}

	if(!function_exists('vvz_filter_active_keys')) {
		function vvz_filter_active_keys ($get) {
			$active = array();
			foreach(vvz_filter_keys() as $key) {
				if(isset($get[$key]) && vvz_value_is_set($get[$key])) {
					$active[] = $key;
				}
			}
			return $active;
		}
	}

	if(!function_exists('vvz_filter_is_active')) {
		function vvz_filter_is_active ($get) {
			return count(vvz_filter_active_keys($get)) > 0;
		}
	}

	if(!function_exists('vvz_filter_active_count')) {
		function vvz_filter_active_count ($get) {
			return count(vvz_filter_active_keys($get));
		}
	}

	if(!function_exists('vvz_startseite_url')) {
		function vvz_startseite_url ($get, $include_filters = true) {
			$params = array();
			foreach(array('semester', 'institut', 'studiengang', 'bereich') as $key) {
				if(isset($get[$key]) && vvz_value_is_set($get[$key])) {
					$params[$key] = $get[$key];
				}
			}
			if($include_filters) {
				foreach(vvz_filter_keys() as $key) {
					if(isset($get[$key]) && vvz_value_is_set($get[$key])) {
						$params[$key] = $get[$key];
					}
				}
			}
			$query = http_build_query($params);
			return 'startseite'.($query !== '' ? '?'.$query : '');
		}
	}

	if(!function_exists('vvz_filter_reset_url')) {
		function vvz_filter_reset_url ($get) {
			return vvz_startseite_url($get, false);
		}
	}

	if(!function_exists('vvz_filter_remove_url')) {
		function vvz_filter_remove_url ($get, $filter_key) {
			$copy = $get;
			unset($copy[$filter_key]);
			return vvz_startseite_url($copy, true);
		}
	}

	if(!function_exists('vvz_startseite_search_url')) {
		function vvz_startseite_search_url ($params) {
			$url_params = array_merge(array('studiengang' => 'alle'), $params);
			foreach($url_params as $key => $value) {
				if(!vvz_value_is_set($value)) {
					unset($url_params[$key]);
				}
			}
			return 'startseite?'.http_build_query($url_params);
		}
	}
?>
