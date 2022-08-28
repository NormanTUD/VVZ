<?php
	include_once("../config.php");
	include_once("../kundenkram.php");
	include_once("../functions.php");


	header("Content-type: text/css");
?>
button {
	background-color: <?php print get_css_property_value("button", "background-color"); ?>;
}
