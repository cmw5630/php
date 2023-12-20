<?php
	ini_set('memory_limit', -1);
	ini_set('max_execution_time', 0);
	setlocale(LC_CTYPE, "ko_KR.eucKR");

	include '../../lib/lib.func.php';

	$oldDataBasePath = '../../excelData';
	//$_POST['dataType'] = 'xlsReader';
	$callback = $_REQUEST["callback"];

	$arrayExcelList = array(
		'cafe'	=> array(
			'member'			=> '\[1\.9\]\[몰이전\]\[회원\]\[회원기본정보\]',
			'member_address'	=> '\[1\.9\]\[몰이전\]\[회원\]\[회원주소\]',
			'board'				=> '\[1\.9\]\[뉴상품\]\[몰이전\]\[게시판\]\[게시물\]',
			'old_board'			=> '\[1\.9\]\[구상품\]\[몰이전\]\[게시판\]\[게시물\]',
			'comment'			=> '\[1\.9\]\[몰이전\]\[게시판\]\[코멘트\]',
			'board_file'		=> '\[1\.9\]\[몰이전\]\[게시판\]\[첨부파일\]',
			'goods'				=> '\[1\.9\]\[몰이전\]\[뉴상품\]\[상품기본정보\]',
			'goods_category'	=> '\[1\.9\]\[몰이전\]\[뉴상품\]\[카테고리상품매칭정보\]',
			'category'			=> '\[1\.9\]\[몰이전\]\[뉴상품\]\[카테고리정보\]',
			'goods_extra_info'	=> '\[1\.9\]\[몰이전\]\[뉴상품\]\[상품추가옵션\]',
			'goods_desc'		=> '\[1\.9\]\[몰이전\]\[뉴상품\]\[상품상세정보\]',
			'goods_related'		=> '\[1\.9\]\[몰이전\]\[뉴상품\]\[관련상품\]',
			'goods_option_shop_info'	=> '\[1\.9\]\[몰이전\]\[뉴상품\]\[상품품목샵별정보\]',
			'goods_option_master'	=> '\[1\.9\]\[몰이전\]\[뉴상품\]\[상품품목master테이블\]',
			'goods_option'		=> '\[1\.9\]\[몰이전\]\[뉴상품\]\[상품옵션\]',
			'goods_option_set'	=> '\[뉴상품\]연동형 옵션 데이터 추출',
		),
		'cafeorder' => array(
			'orderGoods'	=>	'\[1\.9\]\[몰이전\]\[주문\]\[주문상품\]',
			'orderGoodsEa'	=>	'\[1\.9\]\[몰이전\]\[주문\]\[주문관리\]',
			//'orderRefund'	=>	'\[1\.9\]\[몰이전\]\[주문\]\[주문환불\]',
			'order'			=>	'\[1\.9\]\[몰이전\]\[주문\]\[주문서\]',
			'Mileage'		=>	'\[NEW\]\[1\.9\]\[몰이전\]\[마일리지로그\]',
		),
		'make'	=> array(
			'member'	=> 'member',
			'board'		=> 'board|게시글',
			'comment'	=> 'comment|댓글',
			'qa'		=> 'qa|문의',
			'review'	=> 'review|리뷰',
			'goods'		=> 'brand',
		),
	);

	$dataType = preg_replace('/^ucafe/', 'cafe', trimPostRequest('dataType'));
	$pregText = '';
	$arrayMatchFileList = array();
	$arrayMatchFileList['dataType'] = $dataType;
	$oldDataBaseDirectory = opendir($oldDataBasePath);
	$boardFl = false;

	if (!empty($arrayExcelList[$dataType])) {
		while ($oldDataBaseDirectoryRow = readdir($oldDataBaseDirectory)) {
			foreach ($arrayExcelList[$dataType] as $afterName => $beforeName) {
				if ($dataType == 'make') {
					$pregText = '^(' . $beforeName . ')_[^\.]+\.(csv|xml)';
				}
				else {
					$pregText = '^([a-zA-Z0-9]{1,}[\_]{0,}[\_]{0,1})+' . $beforeName . '\.csv';
				}

				preg_match('/' . $pregText . '/', $oldDataBaseDirectoryRow, $result);

				if (!empty($result)) {
					if ($dataType == 'make') {
						if ($beforeName == 'brand') {
							$afterFileName = $afterName . '.csv';
						}
						else {
							$afterFileName = preg_replace('/^' . $beforeName . '/', $afterName , $oldDataBaseDirectoryRow);
						}
					}
					else {
						if ($afterName == 'old_board' && !$boardFl) {
							$boardFl = true;
						}
						else if ($afterName == 'old_board' && $boardFl) {
							continue;
						}

						if ($afterName == 'old_board' || $afterName == 'board') {
							$boardFl = true;
						}
						$afterFileName = $afterName . '.csv';
					}
					$arrayMatchFileList['dataList'][preg_replace('/^old_/', '', $afterFileName)] = array($oldDataBaseDirectoryRow, $afterFileName);
				}
			}
		}
	}
	else if ($dataType == 'xlsReader') {
		$allow_url_override = 1; // Set to 0 to not allow changed VIA POST or GET

		if(!$allow_url_override || !isset($max_rows))
		{
			$max_rows = 0; //USE 0 for no max
		}
		if(!$allow_url_override || !isset($max_cols))
		{
			$max_cols = 0; //USE 0 for no max
		}
		if(!$allow_url_override || !isset($debug))
		{
			$debug = 0;  //1 for on 0 for off
		}
		if(!$allow_url_override || !isset($force_nobr))
		{
			$force_nobr = 1;  //Force the info in cells not to wrap unless stated explicitly (newline)
		}

		$targetFileName = (mb_detect_encoding(trimPostRequest('targetFileName'), array('UTF-8', 'EUC-KR')) == 'UTF-8') ? mb_convert_encoding(trimPostRequest('targetFileName'), 'cp949', 'UTF-8') : trimPostRequest('targetFileName');
		//$targetFileName = 'member.xls';

		require_once 'Spreadsheet/Excel/reader.php';
		$data = new Spreadsheet_Excel_Reader();
		$data->setOutputEncoding('euc-kr'); 
		$data->read($oldDataBasePath . '/' . $targetFileName);
		error_reporting(E_ALL ^ E_NOTICE);

		$arraySheetName = array();
		$row = 1;
		for($sheet = 0; $sheet < count($data->sheets); $sheet++) {
			$sheetName = $data->boundsheets[$sheet]['name'];
			$arrayMatchFileList['sheetList'][] = $sheetName;
			for ($col = 1; $col <= $data->sheets[$sheet]['numCols'] && ($col <= $max_cols || $max_cols == 0); $col++) {
				$arrayMatchFileList['fieldList'][$sheetName][] = $data->sheets[$sheet]['cells'][$row][$col];
			}
		}
	}
	else {
		while ($oldDataBaseDirectoryRow = readdir($oldDataBaseDirectory)) {
			preg_match('/\.' . $dataType . '$/', $oldDataBaseDirectoryRow, $result);
			if (!empty($result)) {
				$arrayMatchFileList['dataList'][] = $oldDataBaseDirectoryRow;
			}
		}
	}

	//debug($arrayMatchFileList);
	echo $callback . '(' . gd_json_encode($arrayMatchFileList) . ')';//debug($arrayMatchFileList);
?>