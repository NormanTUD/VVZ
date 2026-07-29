<?php
/*
 * Tests for day-of-week conversion functions.
 */

/* ----- wochentag_to_weekday ----- */
is_equal("wochentag_to_weekday('Mo')", wochentag_to_weekday("Mo"), array("Mo", "Monday"));
is_equal("wochentag_to_weekday('Di')", wochentag_to_weekday("Di"), array("Tu", "Tuesday"));
is_equal("wochentag_to_weekday('Mi')", wochentag_to_weekday("Mi"), array("We", "Wednesday"));
is_equal("wochentag_to_weekday('Do')", wochentag_to_weekday("Do"), array("Th", "Thursday"));
is_equal("wochentag_to_weekday('Fr')", wochentag_to_weekday("Fr"), array("Fr", "Friday"));
is_equal("wochentag_to_weekday('Sa')", wochentag_to_weekday("Sa"), array("Sa", "Saturday"));
is_equal("wochentag_to_weekday('So')", wochentag_to_weekday("So"), array("Su", "Sunday"));

/* ----- weekday_to_wochentag ----- */
is_equal("weekday_to_wochentag('Monday')", weekday_to_wochentag("Monday"), array("Mo", "Montag"));
is_equal("weekday_to_wochentag('Tuesday')", weekday_to_wochentag("Tuesday"), array("Di", "Dienstag"));
is_equal("weekday_to_wochentag('Wednesday')", weekday_to_wochentag("Wednesday"), array("Mi", "Mittwoch"));
is_equal("weekday_to_wochentag('Thursday')", weekday_to_wochentag("Thursday"), array("Do", "Donnerstag"));
is_equal("weekday_to_wochentag('Friday')", weekday_to_wochentag("Friday"), array("Fr", "Freitag"));
is_equal("weekday_to_wochentag('Saturday')", weekday_to_wochentag("Saturday"), array("Sa", "Samstag"));
is_equal("weekday_to_wochentag('Sunday')", weekday_to_wochentag("Sunday"), array("So", "Sonntag"));

/* weekday_to_wochentag with unknown value returns ERROR */
is_equal("weekday_to_wochentag('hallo') returns ERROR", weekday_to_wochentag("hallo"), array("ERROR", "Fehler beim Bestimmen des Tages"));
is_equal("weekday_to_wochentag(null) returns ERROR", weekday_to_wochentag(null), array("ERROR", "Fehler beim Bestimmen des Tages"));
is_equal("weekday_to_wochentag('') returns ERROR", weekday_to_wochentag(""), array("ERROR", "Fehler beim Bestimmen des Tages"));
is_equal("weekday_to_wochentag('monday') (lowercase) returns ERROR", weekday_to_wochentag("monday"), array("ERROR", "Fehler beim Bestimmen des Tages"));

/* Round-trip consistency */
$days_to_english = array(
	"Mo" => array("Mo", "Montag"),
	"Di" => array("Di", "Dienstag"),
	"Mi" => array("Mi", "Mittwoch"),
	"Do" => array("Do", "Donnerstag"),
	"Fr" => array("Fr", "Freitag"),
	"Sa" => array("Sa", "Samstag"),
	"So" => array("So", "Sonntag"),
);
foreach ($days_to_english as $d => $expected_back) {
	$wd = wochentag_to_weekday($d);
	is_equal("wochentag_to_weekday('$d') returns array", is_array($wd) ? 1 : 0, 1);
	is_equal("wochentag_to_weekday('$d') returns array of 2", count($wd), 2);
	$back = weekday_to_wochentag($wd[1]);
	is_equal("round-trip wochentag_to_weekday('$d') -> weekday_to_wochentag", $back[0], $expected_back[0]);
	is_equal("round-trip back to wochentag long form '$d'", $back[1], $expected_back[1]);
}

/* ----- discordian_date ----- */
is_equal("discordian_date(null) returns null", discordian_date(null) === null ? 1 : 0, 1);
is_equal("discordian_date('') returns null", discordian_date("") === null ? 1 : 0, 1);
is_equal("discordian_date(false) returns null", discordian_date(false) === null ? 1 : 0, 1);
is_equal("discordian_date(0) returns null", discordian_date(0) === null ? 1 : 0, 1);

/* discordian_date with valid date - requires ddatelibrary (PHPDiscordianDate) */
/* This test only runs in the full environment with functions.php loaded */
if(function_exists("discordian_date") && class_exists("PHPDiscordianDate")) {
	$result = @discordian_date("2024-01-05");
	is_equal("discordian_date('2024-01-05') returns non-empty", is_string($result) && strlen($result) > 0 ? 1 : 0, 1);
}
