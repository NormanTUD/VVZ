<?php
	include_once("testing.php");

	$number_regex = "/^[1-9][0-9]*$/";

	is_equal_safe("Basistest", 1, 1);
	is_equal_safe("Basistest #2", is_equal("test von is_equal() #1", 1, 1), 1);
	is_equal_safe("Basistest #3", is_equal("test von is_equal() #1", 0, 0), 1);
	is_equal_safe("Basistest #4", is_equal("test von is_equal() #1", 0, 0), 1);
	is_equal_safe("Basistest #5", is_equal("wochentag_to_weekday('Mo')", wochentag_to_weekday("Mo"), array('Mo', 'Monday')), 1);
	is_equal_safe("Basistest #6", regex_matches("Regextest", "/a/", "/a/"), 1);

	is_equal("test is_equal()", "a", "a");
	is_unequal("test is_unequal()", "a", "b");
	regex_matches("test regex_matches()", "a", "/a/");
	regex_fails("test regex_fails()", "a", "/b/");

	is_equal("wochentag_to_weekday('Mo')", wochentag_to_weekday("Mo"), array('Mo', 'Monday'));
	is_equal("wochentag_to_weekday('Di')", wochentag_to_weekday("Di"), array('Tu', 'Tuesday'));

	is_equal("htmle('hallo')", htmle("hallo"), "hallo");
	is_equal("htmle(null)", htmle(null), "&mdash;");
	is_equal("htmle('Philosophie')", htmle("Philosophie"), 'Philosophie');
	is_equal("htmle('Philosophie', 1)", htmle("Philosophie", 1), 'Phi&shy;lo&shy;so&shy;phie');

	is_equal("table_exists(".$GLOBALS['dbname'].", 'users')", table_exists($GLOBALS["dbname"], "users"), 1);
	is_equal("table_exists('asdasaas', '33rfwsd')", table_exists("asdasaas", "33rfwsd"), 0);

	is_equal("is_valid_auth_code(null)", is_valid_auth_code(null), 0);

	is_equal("mask_module('aaa')", mask_module("aaa"), "<i>aaa</i>");

	is_equal("get_previous_letter('z')", get_previous_letter("z"), "y");

	is_equal("get_single_row_from_result(rquery('select 1'))", get_single_row_from_result(rquery('select 1')), '1');
	is_unequal("get_single_row_from_result(rquery('select 1'))", get_single_row_from_result(rquery('select 1')), '0');

	is_equal("esc('a')", esc("a"), '"a"');
	is_equal("esc('\"a\"')", esc("\"a\""), '"\"a\""');
	is_unequal("esc(select 1;)", esc("select 1;"), 'select 1;');

	is_equal('multiple_esc_join(array("a", "b", "c"))', multiple_esc_join(array("a", "b", "c")), '"a", "b", "c"');

	is_equal("strlen(generate_random_string(50)", strlen(generate_random_string(50)), 50);

	regex_matches("faq_has_entry()", faq_has_entry(), $number_regex);

	regex_matches("sizeof(get_raum_gebaeude_array())", sizeof(get_raum_gebaeude_array()), $number_regex);

	regex_matches("sizeof(get_dozent_array())", sizeof(get_dozent_array()), $number_regex);

	regex_matches("sizeof(get_veranstaltungsabkuerzung_array())", sizeof(get_veranstaltungsabkuerzung_array()), $number_regex);

	is_equal("zeit_nach_sekunde_am_tag('10:00')", zeit_nach_sekunde_am_tag('10:00'), 36000);
	is_equal("zeit_nach_sekunde_am_tag('10:01')", zeit_nach_sekunde_am_tag('10:01'), 36060);
	is_equal("zeit_nach_sekunde_am_tag('23:59')", zeit_nach_sekunde_am_tag('23:59'), 86340);

	is_equal("add_leading_zero(2)", add_leading_zero(2), "02");

	$GLOBALS['deletion_db'] = NULL;
	fill_deletion_global(array("update_veranstaltungstyp", "id"), "veranstaltungstyp");
	is_equal("fill_deletion_global (no post data)", $GLOBALS['deletion_db'], NULL);

	fill_deletion_global(array("update_veranstaltungstyp", "id"), "veranstaltungstyp", array("update_veranstaltungstyp" => 1, "id" => 2));
	is_equal("fill_deletion_global (simulated post data)", $GLOBALS['deletion_db'], "veranstaltungstyp");

	is_equal("get_bereich_name_by_id(1)", get_bereich_name_by_id(1), "Grundzüge der Logik");

	is_equal("user_braucht_barrierefreien_zugang(2)", user_braucht_barrierefreien_zugang(2), array());

	is_equal("table_exists(".$GLOBALS['dbname'].", 'users')", table_exists($GLOBALS["dbname"], "users"), 1);
	is_equal("table_exists(".$GLOBALS['dbname'].", 'IDONOTEXIST')", table_exists($GLOBALS["dbname"], "IDONOTEXIST"), 0);
	is_equal("array_value_or_null(array('a' => 5), 'a')", array_value_or_null(array('a' => 5), 'a'), 5);
	is_equal("array_value_or_null(array('a' => 5), 'b')", array_value_or_null(array('a' => 5), 'b'), null);
	is_equal("mask_module('hallo')", mask_module("hallo"), "<i>hallo</i>");

	is_equal("get_language_name(1)", get_language_name(1), "<img width=32 src='data/germany_flag.svg' />&nbsp;deutsch");
	is_equal("get_language_name(9999)", get_language_name(9999), '');

	is_equal("might_be_query('select 1 dual')", might_be_query('select 1 from dual'), 1);
	is_equal("might_be_query('update testtable set bla = 1 where id = 5')", might_be_query('update testtable set bla = 1 where id = 5'), 1);
	is_equal("might_be_query('hallo welt')", might_be_query('hallo welt'), 0);

	is_equal("generate_random_string(50)", strlen(generate_random_string(50)), 50);

	is_equal("get_single_row_from_query('select 1')", (int) get_single_row_from_query('select 1'), 1);

	is_equal("get_sws(1, 'keine Angabe')", get_sws(1, 'keine Angabe'), null);
	is_equal("get_sws('1-5', 'jede Woche')", get_sws('1-5', 'jede Woche'), array(0, 10));

	is_equal("get_salt(9999)", get_salt(9999), null);
	is_equal("add_missing_seconds_to_datetime('2019-01-05 12:12')", add_missing_seconds_to_datetime('2019-01-05 12:12'), '2019-01-05 12:12:00');

	is_equal("my_strip_tags('<br><i>hallo</i><br>')", my_strip_tags('<br><i>hallo</i><br>'), "\nhallo\n");

	is_equal("comma_list_to_array('1,2,3,a')", comma_list_to_array('1,2,3,a'), array('1', '2', '3', 'a'));

	is_equal("weekday_to_wochentag('Monday')", weekday_to_wochentag("Monday"), array("Mo", "Montag"));
	is_equal("weekday_to_wochentag('Tuesday')", weekday_to_wochentag("Tuesday"), array("Di", "Dienstag"));
	is_equal("weekday_to_wochentag('Wednesday')", weekday_to_wochentag("Wednesday"), array("Mi", "Mittwoch"));
	is_equal("weekday_to_wochentag('Thursday')", weekday_to_wochentag("Thursday"), array("Do", "Donnerstag"));
	is_equal("weekday_to_wochentag('Friday')", weekday_to_wochentag("Friday"), array("Fr", "Freitag"));
	is_equal("weekday_to_wochentag('Saturday')", weekday_to_wochentag("Saturday"), array("Sa", "Samstag"));

	is_equal("weekday_to_wochentag('hallo')", weekday_to_wochentag("hallo"), array("ERROR", "Fehler beim Bestimmen des Tages"));
	is_equal("weekday_to_wochentag(null)", weekday_to_wochentag(null), array("ERROR", "Fehler beim Bestimmen des Tages"));

	is_equal("strlen(nonce()) = 10", strlen(nonce()), 10);
	is_equal("nonce() == nonce()", nonce(), nonce());
?>
