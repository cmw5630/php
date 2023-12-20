<?php
	require ('../lib/lib.func.php');

	$callback = $_REQUEST["callback"];

	$arrayFile = array();

	$jsonData = array();
	$dir = opendir('./source');
	
	while($fileRow = readdir($dir)){
		preg_match('/^source_[^_]+_[[:alnum:]]{1,}_[[:alnum:]]{1,}_v[.]+([0-9]{1,2}[.]|[0-9]{1,2}[.][0-9]{1,2}[.])(zip|ZIP)$/i', $fileRow, $result);
		if($result[0]) {
			$patchName = array();
			$arrayPatchNameSet = explode('_', $fileRow);
			$patchName[] = $arrayPatchNameSet[2];
			$patchName[] = str_replace('.', ' ', $arrayPatchNameSet[1]);
			$patchName[] = preg_replace('/^v[.]+([0-9]{1,2}|[0-9]{1,2}[.][0-9]{1,2})[.](zip|ZIP)/i' , '버전 $1', $arrayPatchNameSet[4]);

			$jsonData['fileList'][urlencode(iconv('EUC-KR', 'UTF-8', $fileRow))] = urlencode( iconv('EUC-KR', 'UTF-8', implode(' ',$patchName)));
		}	
	}
	
	closedir($dir);

	if (!empty($jsonData)) {
		$jsonData['result'] = true;
	}
	else {
		$jsonData['result'] = false;
	}

	echo $callback . '(' . gd_json_encode($jsonData) . ')';
	
?>