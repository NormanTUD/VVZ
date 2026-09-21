<?php
	$GLOBALS['submit_php'] = 1;

	function submit_php_fail ($message) {
		if(!empty($GLOBALS['submit_php_failed'])) {
			return;
		}
		$GLOBALS['submit_php_failed'] = 1;

		if(is_dir("debuglogs") && is_writeable("debuglogs")) {
			$i = 0;
			$filename = "debuglogs/$i.log";
			while (file_exists($filename)) {
				$i++;
				$filename = "debuglogs/$i.log";
			}
			file_put_contents($filename, "MESSAGE >>>>>>>>>>>>>>>>\n\n".$message."\n\n<<<<<<<<<<<<<<<<\n\n");
		}

		if(!is_array($GLOBALS['error'])) {
			$GLOBALS['error'] = array();
		}
		if(!in_array($message, $GLOBALS['error'], true)) {
			$GLOBALS['error'][] = $message;
		}

		http_response_code(500);

		if(function_exists('show_output')) {
			show_output('error', 'red', 1);
		} else {
			print "<span>".htmlentities($message)."</span>\n";
		}
	}

	set_exception_handler(function ($e) {
		submit_php_fail(get_class($e).": ".$e->getMessage()." in ".$e->getFile().":".$e->getLine());
	});

	register_shutdown_function(function () {
		$e = error_get_last();
		if($e && in_array($e['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR), true)) {
			submit_php_fail($e['message']." in ".$e['file'].":".$e['line']);
		}
	});

	include_once("functions.php");

	$error_count = count($GLOBALS["error"]);
	if($error_count) {
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

	if($error_count) {
		exit(min(255, $error_count));
	}
?>
