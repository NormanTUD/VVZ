		<div class="footer_style">
<?php
	if(file_exists('/etc/x11test')) {
		exit(0);
	}
	$this_page_file = ($_SERVER['REQUEST_URI']);

	$redirect_status = array_key_exists("REDIRECT_STATUS", $_SERVER) ? $_SERVER['REDIRECT_STATUS'] : null;

	if(preg_match('/\/(\?.*)?$/', $this_page_file)) {
		$this_page_file = 'startseite.php';

	}
	$this_page_file = basename($this_page_file);
	$this_page_file = preg_replace('/\?.*/', '', $this_page_file);

	$sites = array(
		'startseite' => array("name" => 'Startseite', "id" => 'startseite_link'),
		'api' => array("name" => 'API', "id" => 'api_link'),
		'admin' => array("name" => 'Administration', "id" => 'admin_link'),
		'dokumente' => array("name" => 'Dokumente', "id" => 'dokumente_link'),
		//'rechtliches' => array("name" => 'Rechtliches', "id" => 'rechtliches_link'),
		'impressum' => array("name" => 'Impressum', "id" => 'impressum_link'),
		'zeitraster' => array("name" => "Zeitraster", "id" => "zeitraster"),
		'faq' => array("name" => 'FAQ', "id" => 'faq_link'),
		'front.pdf' => array("name" => 'Dokumentation', "id" => 'doku_link'),
		#'simpsons' => array("name" => 'Die Simpsons', "id" => 'simpsons'),
		'kontakt' => array("name" => 'Kontakt', "id" => 'kontakt_link')
	);
?>
	<i>
<?php
	if(get_kunden_db_name() != "startpage") {
		$c = 0;
		foreach ($sites as $url => $site_data) {
			$name = $site_data['name'];
			$id = $site_data['id'];
			if(!($url == 'faq' && !faq_has_entry())) {
				if($url == $this_page_file) {
	?>
					<b><a id="<?php print $id; ?>" href="vvz_<?php print get_kunde_url().$url; ?>"><?php print htmlentities($name); ?></a></b>
	<?php
				} else {
	?>
					<a id="<?php print $id; ?>" href="vvz_<?php print get_kunde_url().$url; ?>"><?php print htmlentities($name); ?></a>
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
	}
?>
	</i>
	<br />
	</div>

<?php
	if(get_kunden_db_name() != "startpage") {
		include('query_analyzer.php');
	}

	if($GLOBALS['end_html']) {
	
		js("footer.js");
?>
                <script type="text/x-mathjax-config">
                        MathJax.Hub.Config({
                                tex2jax: {
                                        inlineMath: [['$','$']]
                                },
                                "showMathMenu": true
                        });
                </script>

	</body>
</html>
<?php
	}
?>
