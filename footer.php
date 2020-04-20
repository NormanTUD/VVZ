		<div class="footer_style">
<?php
	$this_page_file = ($_SERVER['REQUEST_URI']);
	if(preg_match('/\/(\?.*)?$/', $this_page_file)) {
		$this_page_file = 'index.php';

	}
	$this_page_file = basename($this_page_file);
	$this_page_file = preg_replace('/\?.*/', '', $this_page_file);

	$sites = array(
		'index.php' => array("name" => 'Startseite', "id" => 'startseite_link'),
		'api.php' => array("name" => 'API', "id" => 'api_link'),
		'admin.php' => array("name" => 'Administration', "id" => 'admin_link'),
		'dokumente.php' => array("name" => 'Dokumente', "id" => 'dokumente_link'),
		'rechtliches.php' => array("name" => 'Rechtliches', "id" => 'rechtliches_link'),
		'impressum.php' => array("name" => 'Impressum', "id" => 'impressum_link'),
		'zeitraster.php' => array("name" => "Zeitraster", "id" => "zeitraster"),
		'faq.php' => array("name" => 'FAQ', "id" => 'faq_link'),
		'front.pdf' => array("name" => 'Dokumentation', "id" => 'doku_link'),
		#'simpsons.php' => array("name" => 'Die Simpsons', "id" => 'simpsons'),
		'kontakt.php' => array("name" => 'Kontakt', "id" => 'kontakt_link')
	);
?>
	<i>
<?php
	$c = 0;
	foreach ($sites as $url => $site_data) {
		$name = $site_data['name'];
		$id = $site_data['id'];
		if(!($url == 'faq.php' && !faq_has_entry())) {
			if($url == $this_page_file) {
?>
				<b><a id="<?php print $id; ?>" href="<?php print $url; ?>"><?php print htmlentities($name); ?></a></b>
<?php
			} else {
?>
				<a id="<?php print $id; ?>" href="<?php print $url; ?>"><?php print htmlentities($name); ?></a>
<?php
			}
			$c++;
			if($c != count($sites)) {
				print " / ";
			}
		} else {
			$c++;
		}
	}
?>
	</i>
	<br />
	<br />
	&copy; <?php
			$thisyear = date('Y');
			if($thisyear == 2017) {
				print date('Y');
			} else if($thisyear <= date('Y')) {
				print "2017&nbsp;&mdash;&nbsp;$thisyear";
			} else {
				print "2017 &mdash;<span class='class_red'>An die Administratoren: Falsch eingestellte Server-Zeit. Bitte überprüfen.</span> &mdash; ";
			}

			if(date('j') == 10 && date('m') == 8 || get_get('geburtstag')) {
				$alter = $thisyear - 1993;
				//$params = array_merge($_GET, array("sende_geburtstagsgruss" => "1"));
				//$url = $_SERVER['REQUEST_URI'].http_build_query($params);
				//print " <a href='$url' title='Geburtstagsgruß senden'>&#x1F382; frohen $alter. Geburtstag, </a>";
				print " &#x1f408;&#x1F382;&#127878; frohen $alter. Geburtstag, ";
			}

			if((date('j') == 31 || date('j') == 1) && (date('m') == 1 || date('m') == 12)|| get_get('silvester')) {
				$alter = $thisyear - 1993;
				//$params = array_merge($_GET, array("sende_geburtstagsgruss" => "1"));
				//$url = $_SERVER['REQUEST_URI'].http_build_query($params);
				//print " <a href='$url' title='Geburtstagsgruß senden'>&#x1F382; frohen $alter. Geburtstag, </a>";
				print " &#127878; Happy New Year! ";
			}
		?> Norman Koch, TU Dresden
	</div>

<?php
	include('query_analyzer.php');

	if($GLOBALS['end_html']) {
	
		js("footer.js");
?>
	</body>
</html>
<?php
	}
?>
