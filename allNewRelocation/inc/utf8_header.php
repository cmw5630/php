<?php
	ini_set('memory_limit', -1);
	ini_set('max_execution_time', 0);
	//mb_internal_encoding('UTF-8');
	header('Content-Type: text/html; charset=UTF-8');

	$sourcePath = dirname($_SERVER['HTTP_HOST']);

	require_once '../../utf8lib/db.class.php';
	require_once '../../utf8lib/insertSet.class.php';
	require_once '../../utf8lib/lib.func.php';

	require_once $sourcePath . '/db.conf.php';
	require_once $sourcePath . '/shop.func.php';
	include_once $sourcePath . '/setFile.class.php';

	$_POST = arrayIconv('EUC-KR', 'UTF-8', $_POST);

	//------------------------------------------------------
	// - Advice - 페이지 공통 값 설정
	//------------------------------------------------------
	$db			= new db($dbConnInfo);

	$mode			= trimPostRequest('mode');
	$afterUrl		= 'http://' . trimPostRequest('afterUrl');

	$url			= $afterUrl . "/main/relocation.php"; // 이전 후 처리 프로세스 경로
	$dumpFileName	= $sourcePath . '/' . trimPostRequest('dumpFileName') . '.sql';		// 생성 쿼리 파일명

	if($mode == "start") {
		if (file_exists($dumpFileName)) {
			if (unlink($dumpFileName)) {
				echo '<font style="color:blue">기존 덤프 파일 존재 삭제처리 파일명 : ' . $dumpFileName . '</font>';
			}
			else {
				echo '<font style="color:red;font-weight:bold;">기존 덤프 파일 존재 삭제 실패 파일명 : ' . $dumpFileName . '</font>';
			}
		}
	}

	$csvFilePath = $sourcePath . '/dbfile/';
	//------------------------------------------------------
?>