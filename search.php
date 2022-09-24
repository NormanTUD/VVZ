<?php
	include_once("functions.php");

	header('Content-Type: application/json; charset=utf-8');

	$data = array();

	if(isset($_GET["term"])) {
		$t = $_GET["term"];	
		//$data[] = array("id" => "black 2 id", "label" => "black 2 label", "value" => "black 2 value");



		$pagedata = create_page_info();

		$page_ids = array();
		foreach ($pagedata as $thispage){
			$page_ids[] = $thispage[0];
		}

		$page_rights_data = check_page_rights($page_ids, 0);
		
		foreach ($pagedata as $thispage){
			$pid = $thispage[0];
			if(in_array($pid, $page_rights_data)) {
				#print "<li class='margin_10px_0'>&raquo;<b><a href='admin?$linkname=$pid'>".$thispage[1]."</a></b>&laquo; &mdash; ".$thispage[3];
				$push_id = "goto_page=admin.php?page=$pid";
				$push_label = $thispage[1];

				if(preg_match("/$t/i", $push_label)) {
					$data[] = array("id" => $push_id, "label" => $push_label, "value" => $push_label);
				}
			}
		}

		$data_veranstaltung = json_decode(search_veranstaltung($t), true);
		foreach ($data_veranstaltung as $dv) {
			$data[] = $dv;
		}
	} else {
		$data["error"] = "No term defined";
	}

	print json_encode($data);
?>
