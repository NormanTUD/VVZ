<?php
/*
 * Tests for input/output functions and request handling.
 */

/* ----- get_user_ip ----- */
$orig_remote = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
$_SERVER['REMOTE_ADDR'] = '192.168.1.1';
is_equal("get_user_ip returns valid IP", get_user_ip(), "192.168.1.1");

$_SERVER['REMOTE_ADDR'] = '10.0.0.1';
is_equal("get_user_ip returns another IP", get_user_ip(), "10.0.0.1");

$_SERVER['REMOTE_ADDR'] = '::1';
is_equal("get_user_ip accepts IPv6", get_user_ip(), "::1");

$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
is_equal("get_user_ip accepts localhost", get_user_ip(), "127.0.0.1");

$_SERVER['REMOTE_ADDR'] = 'not-an-ip';
$ip = get_user_ip();
is_equal("get_user_ip returns null/empty for invalid", $ip === null || $ip === "" ? 1 : 0, 1);

/* Restore */
if($orig_remote !== null) {
	$_SERVER['REMOTE_ADDR'] = $orig_remote;
}

/* ----- referrer_from_same_domain ----- */
$orig_http_referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
$orig_http_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null;

$_SERVER['HTTP_REFERER'] = '';
$_SERVER['HTTP_HOST'] = 'example.com';
is_equal("referrer_from_same_domain with empty referer", referrer_from_same_domain() === 0 ? 1 : 0, 1);

$_SERVER['HTTP_REFERER'] = 'http://example.com/page';
$_SERVER['HTTP_HOST'] = 'example.com';
is_equal("referrer_from_same_domain with same domain", referrer_from_same_domain() === 1 ? 1 : 0, 1);

$_SERVER['HTTP_REFERER'] = 'http://different.com/page';
$_SERVER['HTTP_HOST'] = 'example.com';
is_equal("referrer_from_same_domain with different domain", referrer_from_same_domain() === 0 ? 1 : 0, 1);

if($orig_http_referer !== null) {
	$_SERVER['HTTP_REFERER'] = $orig_http_referer;
} else {
	unset($_SERVER['HTTP_REFERER']);
}
if($orig_http_host !== null) {
	$_SERVER['HTTP_HOST'] = $orig_http_host;
} else {
	unset($_SERVER['HTTP_HOST']);
}

/* ----- get_get_or_cookie ----- */
$_GET["shared"] = "from_get";
is_equal("get_get_or_cookie returns from GET when present", get_get_or_cookie("shared"), "from_get");

unset($_GET["shared"]);
$_COOKIE["shared"] = "from_cookie";
is_equal("get_get_or_cookie returns from COOKIE when no GET", get_get_or_cookie("shared"), "from_cookie");

unset($_GET["shared"]);
unset($_COOKIE["shared"]);
is_equal("get_get_or_cookie returns null when neither", get_get_or_cookie("shared") === null ? 1 : 0, 1);
