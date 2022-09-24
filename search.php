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
			# 0	   1	   2		3		    4
			#`name`, `file`, `page_id`, `show_in_navigation`, `parent`
			//dier($pagedata);
			$pid = $thispage[0];
			if(in_array($pid, $page_rights_data)) {
				#print "<li class='margin_10px_0'>&raquo;<b><a href='admin?$linkname=$pid'>".$thispage[1]."</a></b>&laquo; &mdash; ".$thispage[3];
				$push_id = "goto_page=admin.php?page=$pid";
				$push_label = $thispage[1];

				/*
				$subpagedata = create_page_info_parent($pid, $GLOBALS['user_role_id']);
				if(count($subpagedata)) {
					#print "<ul>\n";
					foreach ($subpagedata as $thissubpage){
						if(!$thissubpage[3]) {
							$thissubpage[3] = '<i>Diese Seite wurde noch nicht beschrieben.</i>';
						}
						#print "<li class='margin_3px_0'>&raquo;<b><a href='admin?page=$thissubpage[0]'>".$thissubpage[1]."</a></b>&laquo; &mdash; ".$thissubpage[3]."</li>\n";
					}
					#print "</ul>\n";
				}
				 */

				if(preg_match("/$t/i", $push_label)) {
					$data[] = array("id" => $push_id, "label" => $push_label, "value" => $push_label);
				}
			}
		}



	} else {
		$data["error"] = "No term defined";
	}

	print json_encode($data);
?>
