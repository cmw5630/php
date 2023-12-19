<?php
	ini_set('memory_limit', -1);
	ini_set('max_execution_time', 0);
	setlocale(LC_CTYPE, "ko_KR.eucKR");

	include '../../lib/lib.func.php';

	$defaultPath = '../../module/';
	$defaultDirectory = opendir($defaultPath);

	$arrayDataFileList = array();

	$callback = $_REQUEST["callback"];

	while ($defaultDirectoryRow = readdir($defaultDirectory)) {
		$domainDirectoryPath = $defaultPath . $defaultDirectoryRow . '/';
		if ($defaultDirectoryRow != '.' && $defaultDirectoryRow != '..' && is_dir($domainDirectoryPath)) {
			$domainDirectory = opendir($domainDirectoryPath);
			while ($domainDirectoryRow = readdir($domainDirectory)) {
				if ($domainDirectoryRow != '.' && $domainDirectoryRow != '..') {
					if ($domainDirectoryRow == 'dbfile') {
						$dbfilePath = $domainDirectoryPath . 'dbfile/';
						$dbfileDirectory = opendir($dbfilePath);
						while ($dbfileDirectoryRow = readdir($dbfileDirectory)) {
							preg_match('/\.(sql|csv)$/', $dbfileDirectoryRow, $result);
							if (!empty($result)) {
								$arrayDataFileList[] = preg_replace('/\.\.\/\.\.\/module\//', 'module/', $dbfilePath) . $dbfileDirectoryRow;
							}
						}
					}
					else {
						preg_match('/\.(sql|csv)$/', $domainDirectoryRow, $result);
						if (!empty($result)) {
							$arrayDataFileList[] = preg_replace('/\.\.\/\.\.\/module\//', 'module/', $domainDirectoryPath) . $domainDirectoryRow;
						}
					}
				}
			}
		}
	}
	
	echo $callback . '(' . gd_json_encode($arrayDataFileList) . ')';//debug($arrayMatchFileList);

?>