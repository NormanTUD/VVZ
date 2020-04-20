<?php
	require_once("functions.php");

	if($GLOBALS['logged_in_user_id']) {
		$query = 'DELETE FROM `session_ids` WHERE `user_id` = '.esc($GLOBALS['logged_in_user_id']);
		rquery($query);

		$last_id = 1;
		$last_id_query = 'select id from session_ids order by id desc limit 1';
		$result = rquery($last_id_query);

		while ($row = mysqli_fetch_row($result)) {
			$last_id = $row[0];
		}

		if(preg_match('/^\d+$/', $last_id)) {
			$reset_query = 'ALTER TABLE `session_ids` AUTO_INCREMENT = '.$last_id;
			rquery($reset_query);
		}
	}
	setcookie('session_id', '', 0, "/");

	
	$protocol = stripos($_SERVER['SERVER_PROTOCOL'],'https') === true ? 'https://' : 'http://';
	
	$header = $_SERVER['HTTP_HOST'].'/'.dirname($_SERVER['REQUEST_URI']).'/admin.php';
	$header = preg_replace('/\/{2,}/', '/', $header);
	$header = "Location: $protocol".$header;

	header($header);

	print "Du wirst auf die Startseite umgeleitet...";
?>
