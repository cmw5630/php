<?php
	function gd_json_encode($array=false) {
		if (!class_exists('Services_JSON', false))
			include_once '../../_inc/json.class.php';
		$o = new Services_JSON( SERVICES_JSON_LOOSE_TYPE );
		return $o->encode($array);
	}

	$callback	= $_REQUEST["callback"];
	$setDomain	= $_REQUEST["setDomain"];

	$arrayFile = array();

	$jsonData = array();
	if ($dir = @opendir('./' . $setDomain)) {
	
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
	}
	else {
		$jsonData['result'] = false;
	}

	

	echo $callback . '(' . gd_json_encode($jsonData) . ')';
	
?>