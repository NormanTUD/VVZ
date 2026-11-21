<?php
	include_once("functions.php");
	include_once("search_functions.php");

	header('Content-Type: application/json; charset=utf-8');

	$term = isset($_GET["term"]) ? trim($_GET["term"]) : '';
	$scope = isset($_GET['scope']) ? $_GET['scope'] : 'admin';
	$logged_in_user_id = isset($GLOBALS['logged_in_user_id']) ? $GLOBALS['logged_in_user_id'] : null;

	print json_encode(vvz_search_results($term, $scope, $logged_in_user_id));
?>
