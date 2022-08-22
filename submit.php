<?php
	include_once("functions.php");
	foreach (array(
			array("hint", "blue"),
			array("error", "red"),
			array("right_issue", "red"),
			array("warning", "orange"),
			array("message", "blue"),
			array("easter_egg", "hotpink"),
			array("success", "green")
		) as $msg) {
		show_output($msg[0], $msg[1]);
	}
?>
