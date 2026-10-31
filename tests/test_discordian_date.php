<?php
/*
 * Tests for scripts/ddatelibrary.php (Discordian date conversion).
 */

if (!class_exists('PHPDiscordianDate')) {
	include_once __DIR__ . '/../scripts/ddatelibrary.php';
}

$vvz_ddate = new PHPDiscordianDate();

is_equal("discordian: GetDayName(1)", $vvz_ddate->GetDayName(1), 'Sweetmorn');
is_equal("discordian: GetDayName(2)", $vvz_ddate->GetDayName(2), 'Boomtime');
is_equal("discordian: GetDayName(3)", $vvz_ddate->GetDayName(3), 'Pungenday');
is_equal("discordian: GetDayName(4)", $vvz_ddate->GetDayName(4), 'Prickle-Prickle');
is_equal("discordian: GetDayName(5)", $vvz_ddate->GetDayName(5), 'Setting Orange');
is_equal("discordian: GetDayName(6) wraps", $vvz_ddate->GetDayName(6), 'Sweetmorn');
is_equal("discordian: GetDayName(10) wraps", $vvz_ddate->GetDayName(10), 'Setting Orange');
is_equal("discordian: GetDayName(73) wraps", $vvz_ddate->GetDayName(73), 'Pungenday');

is_equal("discordian: Ending(1)", $vvz_ddate->Ending(1), 'st');
is_equal("discordian: Ending(2)", $vvz_ddate->Ending(2), 'nd');
is_equal("discordian: Ending(3)", $vvz_ddate->Ending(3), 'rd');
is_equal("discordian: Ending(4)", $vvz_ddate->Ending(4), 'th');
is_equal("discordian: Ending(5)", $vvz_ddate->Ending(5), 'th');
is_equal("discordian: Ending(11)", $vvz_ddate->Ending(11), 'th');
is_equal("discordian: Ending(21)", $vvz_ddate->Ending(21), 'th');
is_equal("discordian: Ending(22)", $vvz_ddate->Ending(22), 'th');
is_equal("discordian: Ending(23)", $vvz_ddate->Ending(23), 'th');
is_equal("discordian: Ending(0)", $vvz_ddate->Ending(0), 'th');

$vvz_make = $vvz_ddate->MakeDay(1, 1, 2018);
is_equal("discordian: MakeDay returns DiscordianDate", is_object($vvz_make) ? 1 : 0, 1);
is_equal("discordian: MakeDay year", $vvz_make->year, 3184);
is_equal("discordian: MakeDay season", $vvz_make->season, 0);
is_equal("discordian: MakeDay day", $vvz_make->day, 1);
is_equal("discordian: MakeDay StTibsDay false", $vvz_make->StTibsDay, false);
is_equal("discordian: MakeDay yday", $vvz_make->yday, 1);

$vvz_make_end = $vvz_ddate->MakeDay(12, 31, 2018);
is_equal("discordian: MakeDay end of year season", $vvz_make_end->season, 4);
is_equal("discordian: MakeDay end of year day", $vvz_make_end->day, 73);

/* Leap-year handling. The library considers years where year%4 == 2 as leap
 * years in its own YOLD calculation. 2018 -> YOLD 3184, 3184%4 == 0, so a
 * date after February is shifted by one in the non-St-Tibs path. */
$vvz_make_after_feb = $vvz_ddate->MakeDay(3, 1, 2018);
is_equal("discordian: MakeDay March is day 60 (normal year)", $vvz_make_after_feb->day, 60);
is_equal("discordian: MakeDay March season", $vvz_make_after_feb->season, 0);

$vvz_st_tibs = $vvz_ddate->MakeDay(2, 29, 2018);
is_equal("discordian: MakeDay Feb 29 legacy StTibsDay handling", $vvz_st_tibs->StTibsDay, false);
is_equal("discordian: MakeDay Feb 29 day", $vvz_st_tibs->day, 60);

/* __toString exists and returns a human-readable string. It contains a few
 * legacy warnings, so we suppress them here. */
$vvz_string = @(string) $vvz_make;
is_equal("discordian: __toString returns string", is_string($vvz_string) ? 1 : 0, 1);
is_equal("discordian: __toString starts with Today is", strpos($vvz_string, 'Today is ') === 0 ? 1 : 0, 1);
