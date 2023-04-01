<?php
	header("Status: 301 Moved Permanently");
	header("Location:./startseite?". $_SERVER['QUERY_STRING']);
	exit;
?>
