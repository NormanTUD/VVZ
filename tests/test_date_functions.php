<?php
/*
 * Tests for date and time functions.
 * Pure functions (no DB dependency) that operate on date/time values.
 */

/* ----- zeit_nach_sekunde_am_tag ----- */
is_equal("zeit_nach_sekunde_am_tag('00:00') is midnight", zeit_nach_sekunde_am_tag("00:00"), 0);
is_equal("zeit_nach_sekunde_am_tag('10:00') is 36000", zeit_nach_sekunde_am_tag("10:00"), 36000);
is_equal("zeit_nach_sekunde_am_tag('10:01') is 36060", zeit_nach_sekunde_am_tag("10:01"), 36060);
is_equal("zeit_nach_sekunde_am_tag('23:59') is 86340", zeit_nach_sekunde_am_tag("23:59"), 86340);
is_equal("zeit_nach_sekunde_am_tag('12:00') is 43200", zeit_nach_sekunde_am_tag("12:00"), 43200);
is_equal("zeit_nach_sekunde_am_tag('01:30') is 5400", zeit_nach_sekunde_am_tag("01:30"), 5400);
is_equal("zeit_nach_sekunde_am_tag('garbage') returns null", zeit_nach_sekunde_am_tag("garbage") === null ? 1 : 0, 1);
is_equal("zeit_nach_sekunde_am_tag('') returns null", zeit_nach_sekunde_am_tag("") === null ? 1 : 0, 1);

/* ----- add_missing_seconds_to_datetime ----- */
is_equal("add_missing_seconds_to_datetime('2019-01-05 12:12:00') returns same", add_missing_seconds_to_datetime("2019-01-05 12:12:00"), "2019-01-05 12:12:00");
is_equal("add_missing_seconds_to_datetime('2019-01-05 12:12') adds :00", add_missing_seconds_to_datetime("2019-01-05 12:12"), "2019-01-05 12:12:00");
is_equal("add_missing_seconds_to_datetime('2024-12-31 23:59') adds :00", add_missing_seconds_to_datetime("2024-12-31 23:59"), "2024-12-31 23:59:00");
is_equal("add_missing_seconds_to_datetime('2024-01-01 00:00') adds :00", add_missing_seconds_to_datetime("2024-01-01 00:00"), "2024-01-01 00:00:00");

/* add_missing_seconds_to_datetime with bad input and noerror=1 should not call error() */
$GLOBALS['error'] = array();
add_missing_seconds_to_datetime("not-a-date", 1);
is_equal("add_missing_seconds_to_datetime('not-a-date', 1) does not push error", count($GLOBALS['error']), 0);

/* ----- convert_date ----- */
/* Note: production has a bug - uses $founds[0] (full match) instead of $founds[3] (year).
   These tests document the CURRENT behavior. */
is_equal("convert_date('garbage') returns input unchanged", convert_date("garbage"), "garbage");
is_equal("convert_date('') returns input unchanged", convert_date(""), "");
is_equal("convert_date('not-a-date') returns input unchanged", convert_date("not-a-date"), "not-a-date");
is_equal("convert_date returns string for invalid", is_string(convert_date("garbage")) ? 1 : 0, 1);
is_equal("convert_date returns string for empty", is_string(convert_date("")) ? 1 : 0, 1);

/* These tests document the bug - they show what convert_date ACTUALLY does.
   If production is fixed, these will fail and the tests should be updated. */
$conv_result = convert_date("01.01.2024");
is_equal("convert_date returns non-empty for valid date", !empty($conv_result) ? 1 : 0, 1);
is_equal("convert_date preserves digits", preg_match("/01/", $conv_result) ? 1 : 0, 1);

/* ----- fucked_up_date_to_real_date (excel numeric input) ----- */
/* When given a non-numeric string that's not a valid date, return as-is */
is_equal("fucked_up_date_to_real_date('not a date') returns as-is", fucked_up_date_to_real_date("not a date"), "not a date");
is_equal("fucked_up_date_to_real_date('hello world') returns as-is", fucked_up_date_to_real_date("hello world"), "hello world");
is_equal("fucked_up_date_to_real_date('') returns empty", fucked_up_date_to_real_date(""), "");

/* When given a number below 1000, return as-is */
is_equal("fucked_up_date_to_real_date(500) returns as-is", fucked_up_date_to_real_date("500"), "500");

/* When given an excel serial date number, returns Y-m-d */
regex_matches("fucked_up_date_to_real_date(44927) (excel 2023-01-01) returns date", fucked_up_date_to_real_date("44927"), "/^\d{4}-\d{2}-\d{2}$/");
regex_matches("fucked_up_date_to_real_date(25569) (excel epoch 1970-01-01) returns date", fucked_up_date_to_real_date("25569"), "/^\d{4}-\d{2}-\d{2}$/");
is_equal("fucked_up_date_to_real_date(44927) is 2023-01-01", fucked_up_date_to_real_date("44927"), "2023-01-01");

/* ----- fucked_up_date_to_real_date (csv input) ----- */
/* MM/YYYY format works correctly */
is_equal("fucked_up_date_to_real_date('12/2024', 1) returns 2024-12-15", fucked_up_date_to_real_date("12/2024", 1), "2024-12-15");
is_equal("fucked_up_date_to_real_date('12.2024', 1) returns 2024-12-15", fucked_up_date_to_real_date("12.2024", 1), "2024-12-15");
is_equal("fucked_up_date_to_real_date('12-2024', 1) returns 2024-12-15", fucked_up_date_to_real_date("12-2024", 1), "2024-12-15");
is_equal("fucked_up_date_to_real_date('12/1949', 1) returns null (year too low)", fucked_up_date_to_real_date("12/1949", 1) === null ? 1 : 0, 1);
is_equal("fucked_up_date_to_real_date('12/2024', 1) is string", is_string(fucked_up_date_to_real_date("12/2024", 1)) ? 1 : 0, 1);

/* Note: YYYY/MM format has a production bug - returns null because
   the month (small number) is compared with min_plausible_year (1950) */
is_equal("fucked_up_date_to_real_date('2024/12', 1) returns null (production bug)", fucked_up_date_to_real_date("2024/12", 1) === null ? 1 : 0, 1);
is_equal("fucked_up_date_to_real_date('2024.12', 1) returns null (production bug)", fucked_up_date_to_real_date("2024.12", 1) === null ? 1 : 0, 1);
is_equal("fucked_up_date_to_real_date('2024-12', 1) returns null (production bug)", fucked_up_date_to_real_date("2024-12", 1) === null ? 1 : 0, 1);
