<?php
	ini_set('memory_limit', -1);
	ini_set('max_execution_time', 0);
	setlocale(LC_CTYPE, "ko_KR.eucKR");

	include '../../lib/lib.func.php';

	$dataFileListCheck	= trimPostRequest('dataFileListCheck');
	$failList = array();

	foreach($dataFileListCheck as $delFilePath) {
		$delFilePath = preg_replace('/^module\//', '../../module/', $delFilePath);
		if (is_file($delFilePath)) {
			if (!unlink($delFilePath)) {
				$failList[] = preg_replace('/\.\.\/\.\.\/module\//', 'module/', $delFilePath);
			}
		}
	}

	if (!empty($failList)) {
		echo '<script type="text/javascript">parent.alert("일부 데이터가 삭제 되지 않았습니다.\\n' . implode('\\n', $failList) . '");parent.dataListCheck(false);</script>';
	}
	else {
		echo '<script type="text/javascript">parent.alert("선택된 데이터 정상 삭제 되었습니다.");parent.dataListCheck(false);</script>';
	}
?>