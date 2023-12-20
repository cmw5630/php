<?php
	$callback = $_GET['callback'];

	$arrayNewVersion = explode(chr(13), file_get_contents('http://relocation.godo.co.kr/module/_version/relocation/version.info'));
	$arrayOldVersion = explode(chr(13), file_get_contents('../version.info'));
	
	$updateFl = 'false';
	if ($arrayOldVersion[0] != $arrayNewVersion[0]) {
		$updateFl = 'true';
	}
	echo $callback . "({\"result\":{$updateFl}})";
?>