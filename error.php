<?php
	if($GLOBALS['error_page_shown']) {
		exit();
	} else {
		$GLOBALS["error_page_shown"] = 1;
	}
	$php_start = microtime(true);
	if(file_exists('new_setup')) {
		exit(0);
	}
	include_once("config.php");
	$page_title = "Vorlesungsverzeichnis ".$GLOBALS['university_name'];

	$filename = 'startseite';
	include_once("header.php");
?>
	<div id="mainindex">
		<a href="startseite" border="0"><?php print_uni_logo(); ?> </a>
		<h2>Fehler</h2>
<?php
		$status_code = $_SERVER['REDIRECT_STATUS'];
		if($status_code) {
?>
			Es ist ein Fehler aufgetreten. Der Status-Code lautet <?php print htmlentities($status_code); ?>.
<?php
		} else {
?>
			Es ist ein Fehler aufgetreten. <?php if(isset($GLOBALS['messageerror'])) { print $GLOBALS['messageerror']; } ?>
<?php
		}
	include("footer.php");
?>
