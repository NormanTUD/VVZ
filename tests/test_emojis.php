<?php
/*
 * Tests for every icon helper in emojis.php.
 *
 * The getters are pure string functions. The print_* wrappers are checked by
 * capturing their output.
 */

if (!function_exists('get_checkbox_symbol') && file_exists(__DIR__ . '/../emojis.php')) {
	include_once __DIR__ . '/../emojis.php';
}

$vvz_icon_getters = array(
	'get_checkbox_symbol' => 'fa-check-square',
	'get_red_cross_symbol' => 'fa-times',
	'get_calendar_icon' => 'fa-calendar',
	'get_search_icon' => 'fa-search',
	'get_double_arrow_down_icon' => 'fa-angle-double-down',
	'get_worldmap_icon' => 'fa-globe',
	'get_wrench_icon' => 'fa-wrench',
	'get_document_icon' => 'fa-file',
	'get_camera_icon' => 'fa-video-camera',
	'get_lightning_icon' => 'fa-bolt',
	'get_uk_flag' => 'uk_flag.svg',
	'get_french_flag' => 'france_flag.svg',
	'get_german_flag' => 'germany_flag.svg',
	'get_male_teacher_icon' => 'fa-users',
	'get_female_teacher_icon' => 'fa-users',
	'get_write_icon' => 'fa-pencil',
	'get_warning_icon' => 'fa-exclamation-triangle',
	'get_wheelchair_icon' => 'fa-wheelchair',
	'get_person_icon' => 'fa-address-card',
	'get_person_add_icon' => 'fa-user-plus',
	'get_building_icon' => 'fa-building',
	'get_email_icon' => 'fa-envelope',
	'get_book_icon' => 'fa-book',
	'get_edit_icon' => 'fa-pencil-square',
	'get_logout_icon' => 'fa-sign-out',
	'get_hike_icon' => 'fa-person-hiking',
	'get_delete_icon' => 'fa-circle-xmark',
	'get_work_icon' => 'fa-briefcase',
	'get_info_icon' => 'fa-circle-info',
	'get_interdisciplinary_icon' => 'fa-people-arrows',
	'get_research_icon' => 'fa-chalkboard-user',
	'get_help_icon' => 'fa-question',
);

foreach ($vvz_icon_getters as $vvz_icon_function => $vvz_icon_needle) {
	if (!function_exists($vvz_icon_function)) {
		continue;
	}
	$vvz_icon_html = call_user_func($vvz_icon_function);
	is_equal("emojis: $vvz_icon_function returns string", is_string($vvz_icon_html) ? 1 : 0, 1);
	is_equal("emojis: $vvz_icon_function contains $vvz_icon_needle", strpos($vvz_icon_html, $vvz_icon_needle) !== false ? 1 : 0, 1);
}

if (function_exists('get_search_icon')) {
	is_equal("emojis: get_search_icon with class", strpos(get_search_icon('extra-class'), 'extra-class') !== false ? 1 : 0, 1);
	is_equal("emojis: get_search_icon without class", strpos(get_search_icon(), 'extra-class') === false ? 1 : 0, 1);
}

$vvz_icon_printers = array(
	'print_checkbox_symbol' => 'get_checkbox_symbol',
	'print_red_cross_symbol' => 'get_red_cross_symbol',
	'print_calendar_icon' => 'get_calendar_icon',
	'print_search_icon' => 'get_search_icon',
	'print_double_arrow_down_icon' => 'get_double_arrow_down_icon',
	'print_worldmap_icon' => 'get_worldmap_icon',
	'print_wrench_icon' => 'get_wrench_icon',
	'print_document_icon' => 'get_document_icon',
	'print_camera_icon' => 'get_camera_icon',
	'print_lightning_icon' => 'get_lightning_icon',
	'print_uk_flag' => 'get_uk_flag',
	'print_french_flag' => 'get_french_flag',
	'print_german_flag' => 'get_german_flag',
	'print_female_teacher_icon' => 'get_female_teacher_icon',
	'print_write_icon' => 'get_write_icon',
	'print_warning_icon' => 'get_warning_icon',
	'print_wheelchair_icon' => 'get_wheelchair_icon',
	'print_person_icon' => 'get_person_icon',
	'print_person_add_icon' => 'get_person_add_icon',
	'print_building_icon' => 'get_building_icon',
	'print_email_icon' => 'get_email_icon',
	'print_book_icon' => 'get_book_icon',
	'print_edit_icon' => 'get_edit_icon',
	'print_logout_icon' => 'get_logout_icon',
	'print_hike_icon' => 'get_hike_icon',
	'print_delete_icon' => 'get_delete_icon',
	'print_work_icon' => 'get_work_icon',
	'print_info_icon' => 'get_info_icon',
	'print_interdisciplinary_icon' => 'get_interdisciplinary_icon',
	'print_research_icon' => 'get_research_icon',
	'print_help_icon' => 'get_help_icon',
);

foreach ($vvz_icon_printers as $vvz_icon_function => $vvz_icon_getter) {
	if (!function_exists($vvz_icon_function) || !function_exists($vvz_icon_getter)) {
		continue;
	}
	ob_start();
	$vvz_icon_return = call_user_func($vvz_icon_function);
	$vvz_icon_output = ob_get_clean();
	is_equal("emojis: $vvz_icon_function prints icon", strpos($vvz_icon_output, call_user_func($vvz_icon_getter)) !== false ? 1 : 0, 1);
	}

is_equal("emojis: print_male_teacher_icon returns icon (legacy behaviour)", print_male_teacher_icon(), get_male_teacher_icon());
