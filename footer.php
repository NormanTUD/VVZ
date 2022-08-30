		<div class="footer_style">
<?php
	if(file_exists('/etc/x11test')) {
		exit(0);
	}
	$this_page_file = ($_SERVER['REQUEST_URI']);
	if(preg_match("/^\/v\/[a-z-_0-9]+\/$/", $this_page_file)) {
		$this_page_file = "startseite";
	}

	$redirect_status = array_key_exists("REDIRECT_STATUS", $_SERVER) ? $_SERVER['REDIRECT_STATUS'] : null;

	if(preg_match('/\/(\?.*)?$/', $this_page_file)) {
		$this_page_file = 'startseite.php';

	}
	$this_page_file = basename($this_page_file);
	$this_page_file = preg_replace('/\?.*/', '', $this_page_file);

	$sites = array(
		'startseite' => array("name" => 'Startseite', "id" => 'startseite_link', 'admin_only' => 0),
		'api' => array("name" => 'API', "id" => 'api_link', 'admin_only' => 0),
		'dokumente' => array("name" => 'Dokumente', "id" => 'dokumente_link', 'admin_only' => 0),
		'zeitraster' => array("name" => "Zeitraster", "id" => "zeitraster", 'admin_only' => 0),
		'faq' => array("name" => 'FAQ', "id" => 'faq_link', 'admin_only' => 0),
		'front.pdf' => array("name" => 'Dokumentation', "id" => 'doku_link', 'admin_only' => 0),
		'admin' => array("name" => 'Administration', "id" => 'admin_link', 'admin_only' => 0),
		"change_plan" => array("name" => "Business-Plan ändern", "id" => "change_plan", 'admin_only' => 1),
		'kontakt' => array("name" => 'Kontakt', "id" => 'kontakt_link', 'admin_only' => 0),
		'impressum' => array("name" => 'Impressum', "id" => 'impressum_link', 'admin_only' => 0)
	);
?>
	<i>
<?php
	$c = 0;
	foreach ($sites as $url => $site_data) {
		$name = $site_data['name'];
		$id = $site_data['id'];
		$admin_only = $site_data['admin_only'];
		if(
			!($url == 'faq' && !faq_has_entry()) &&
			(!$admin_only || $admin_only && user_is_admin($GLOBALS["logged_in_user_id"]))
		) {
			if($url == $this_page_file) {
?>
				<b><a id="<?php print $id; ?>" href="<?php print get_kunde_url().$url; ?>"><?php print htmlentities($name); ?></a></b>
<?php
			} else {
?>
				<a id="<?php print $id; ?>" href="<?php print get_kunde_url().$url; ?>"><?php print htmlentities($name); ?></a>
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
	</div>

<?php
	if(get_kunden_db_name() != "startpage") {
		include('query_analyzer.php');
	}

	if($GLOBALS['end_html']) {
	
		js("footer.js");
?>
<!--
		<script nonce="<?php print nonce(); ?>" type="text/x-mathjax-config">
		<script type="text/x-mathjax-config">
			MathJax.Hub.Config({
				tex2jax: {
					inlineMath: [['$','$']]
				},
				"showMathMenu": true
			});
                </script>

	</body>
-->
</html>
<?php
	}
?>
