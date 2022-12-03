<?php
	include_once("functions.php");

	if(count($GLOBALS["error"])) {
		http_response_code(500);
	}

	foreach (array(
			array("hint", "blue"),
			array("error", "red"),
			array("right_issue", "red"),
			array("warning", "orange"),
			array("message", "blue"),
			array("easter_egg", "hotpink"),
			array("success", "green")
		) as $msg) {
		show_output($msg[0], $msg[1], 1);
	}

	if(count($GLOBALS["error"])) {
		exit(min(255, count($GLOBALS["error"])));
	}
?>
