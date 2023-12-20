<?php
	ini_set('memory_limit', -1);
	ini_set('max_execution_time', 0);
	setlocale(LC_CTYPE, 'ko_KR.eucKR');

	include '../../lib/lib.func.php';

	$arrangeCount = (trimPostRequest('arrangeCount')) ? trimPostRequest('arrangeCount') : 0;
	
	$arrayBeforeFileName	= trimPostRequest('beforeFileName');
	$arrayAfterFileName		= trimPostRequest('afterFileName');
	$targetDomain			= trimPostRequest('targetDomain');
	
	$arrayCafeCSVList = array(
		'member'	=> array(
			'fileName'		=> '\[1\.9\]\[몰이전\]\[회원\]\[회원기본정보\]',
			'selectField'	=> array(
				'회원 탈퇴여부'		=> 'F',
			),
			'sortField'		=> array(
				'가입일시',
			),
			'rowArrage'		=> false,
		),

		'board'	=> array(
			'fileName'		=> '\[1\.9\]\[뉴상품\]\[몰이전\]\[게시판\]\[게시물\]',
			'selectField'	=> array(
				'삭제여부'		=> 'F',
				'샵번호'		=> '1',
			),
			'sortField'		=> array(
				'게시판번호',
				'게시글번호',
				'번호',
			),
			'rowArrage'		=> true,
		),
		
		'old_board'	=> array(
			'fileName'		=> '\[1\.9\]\[구상품\]\[몰이전\]\[게시판\]\[게시물\]',
			'selectField'	=> array(
				'삭제여부'		=> 'F',
			),
			'sortField'		=> array(
				'게시판번호',
				'게시글번호',
				'번호',
			),
			'rowArrage'		=> true,
		),
		
		'comment'	=> array(
			'fileName'		=> '\[1\.9\]\[몰이전\]\[게시판\]\[코멘트\]',
			'selectField'	=> array(
				'삭제 여부'		=> 'F',
			),
			'sortField'		=> array(
				'게시판 번호',
				'게시물 고유번호',
				'auto increment 번호',
			),
			'rowArrage'		=> true,
		),

		'board_file'	=> array(
			'fileName'		=> '\[1\.9\]\[몰이전\]\[게시판\]\[첨부파일\]',
			'selectField'	=> array(
				'업로드시원본파일명'	=> 'true',
			),
			'sortField'		=> array(
				'게시물번호',
				'순서',
			),
			'rowArrage'		=> false,
		),

		'goods'		=> array(
			'fileName'		=> '\[1\.9\]\[몰이전\]\[뉴상품\]\[상품기본정보\]',
			'selectField'	=> array(
				'샵번호'		=> '1',
				'삭제 여부'		=> 'F',
			),
			'sortField'		=> array(
				'등록 일시',
				'상품 번호',
			),
			'rowArrage'		=> false,
		),

		'goods_category'	=> array(
			'fileName'		=> '\[1\.9\]\[몰이전\]\[뉴상품\]\[카테고리상품매칭정보\]',
			'selectField'	=> array(
				'샵 넘버'		=> '1',
			),
			'sortField'		=> array(
				'상품 번호',
				'Group 내 출력 순서',
			),
			'rowArrage'		=> false,
		),

		'category'	=> array(
			'fileName'		=> '\[1\.9\]\[몰이전\]\[뉴상품\]\[카테고리정보\]',
			'selectField'	=> array(
				'멀티샵샵번호'		=> '1',
			),
			'sortField'		=> array(
				'유형별 참조 단계',
				'카테고리 번호',
				'상위 카테고리 번호',
			),
			'rowArrage'		=> false,
		),

		'goods_extra_info'	=> array(
			'fileName'		=> '\[1\.9\]\[몰이전\]\[뉴상품\]\[상품추가옵션\]',
			'selectField'	=> array(
				'샵 번호'		=> '1',
			),
			'sortField'		=> array(
				'상품번호',
			),
			'rowArrage'		=> false,
		),

		'goods_desc'	=> array(
			'fileName'		=> '\[1\.9\]\[몰이전\]\[뉴상품\]\[상품상세정보\]',
			'selectField'	=> array(
				'샵넘버'		=> '1',
				'삭제 여부'		=> 'F',
			),
			'sortField'		=> array(
				'상품 번호',
			),
			'rowArrage'		=> true,
		),

		'goods_related'	=> array(
			'fileName'		=> '\[1\.9\]\[몰이전\]\[뉴상품\]\[관련상품\]',
			'selectField'	=> array(
			),
			'sortField'		=> array(
				'원 상품코드',
				'관련상품진열순서',
			),
			'rowArrage'		=> false,
		),

		'member_address'	=> array(
			'fileName'		=> '\[1\.9\]\[몰이전\]\[회원\]\[회원주소\]',
			'selectField'	=> array(
			),
			'sortField'		=> array(
				'주소 번호',
			),
			'rowArrage'		=> false,
		),

		'goods_option_shop_info'	=> array(
			'fileName'		=> '\[1\.9\]\[몰이전\]\[뉴상품\]\[상품품목샵별정보\]',
			'selectField'	=> array(
				'샵 번호'	=> '1',
				'삭제 여부'	=> 'F',
			),
			'sortField'		=> array(
				'상품번호',
				'품목코드',
			),
			'rowArrage'		=> false,
		),

		'goods_option_master'	=> array(
			'fileName'		=> '\[1\.9\]\[몰이전\]\[뉴상품\]\[상품품목master테이블\]',
			'selectField'	=> array(
				'삭제여부'	=> 'F',
			),
			'sortField'		=> array(
				'상품코드',
				'품목코드',
			),
			'rowArrage'		=> false,
		),

		'goods_option'	=> array(
			'fileName'		=> '\[1\.9\]\[몰이전\]\[뉴상품\]\[상품옵션\]',
			'selectField'	=> array(
			),
			'sortField'		=> array(
				'상품 코드',
				'품목 코드',
			),
			'rowArrage'		=> false,
		),

		'goods_option_set'	=> array(
			'fileName'		=> '\[뉴상품\]연동형 옵션 데이터 추출',
			'selectField'	=> array(
			),
			'sortField'		=> array(
				'옵션순서',
			),
			'rowArrage'		=> false,
		),
	);

	$oldDataBasePath	= '../../excelData/';
	$newDataBasePath	= '../../module/' . $targetDomain;
	makeDir($newDataBasePath);
	$newDataBasePath .= '/dbfile/';
	makeDir($newDataBasePath);
	$logPath			= $oldDataBasePath . 'log/cafe/';
	makeDir($logPath);

	$arraySelectData	= array();
	$arraySortKey1		= array();
	$arraySortKey2		= array();
	$arraySortKey3		= array();
	$arrayKey1		= array();
	$arrayKey2		= array();
	$arrayKey3		= array();

	$beforeRowCount		= 0;
	$afterRowCount		= 0;

	$arrayLogText = array();
	$logFileName = date('Ymd') . '.log';
	if ((int)$arrangeCount === 0) {
		$arrayLogText[] = "#############################################";
		$arrayLogText[] = "[log start time] : " . date('Y-m-d H:i:s');
		$arrayLogText[] = "[target domain] : " . $targetDomain . chr(13);
		$arrayLogText[] = "---------------------------------------------";
	}
	
	foreach ($arrayCafeCSVList as $key => $arrangeInfo) {
		$pregText = '^([a-zA-Z0-9]{1,}[\_]{0,}[\_]{0,1})+' . $arrangeInfo['fileName'] . '\.csv';
		preg_match('/' . $pregText . '/', $arrayBeforeFileName[$arrangeCount], $result);
		if (!empty($result)) {
			$arrayLogText[] = "[arrange start time] : " . date('Y-m-d H:i:s');
			$arrayLogText[] = "[arrange data name] : " . $arrayBeforeFileName[$arrangeCount] . ' => ' . $arrayAfterFileName[$arrangeCount];
			$arrayLogText[] = "[arrange before data size] : " . number_format(filesize($oldDataBasePath . $arrayBeforeFileName[$arrangeCount]) / 1048576, 2) . ' MB';
			$arrayLogText[] = "[sort field name] : " . implode(', ', $arrangeInfo['sortField']);
			if (!empty($arrangeInfo['selectField'])) {
				$arrayLogText[] = "[select field info] : ";
				foreach ($arrangeInfo['selectField'] as $key => $value) {
					$arrayLogText[] = "{$key} = {$value}";
				}
			}

			?>
				<script type="text/javascript">parent.setProgress("<?=$arrangeCount?>", "loading");</script>
			<?php
			
			$oldFile		= $oldDataBasePath . $arrayBeforeFileName[$arrangeCount];
			$changeTarget	= $arrayAfterFileName[$arrangeCount];
			$tmpFileName	= $newDataBasePath . preg_replace('/\.csv/', '_tmp$0', $changeTarget);
			$tmpFileName2	= $newDataBasePath . preg_replace('/\.csv/', '_tmp2$0', $changeTarget);

			$splitData = '';
			$fileOpen = fopen($oldFile,'r');
			$splitData = fread($fileOpen,250000000);
			fclose($fileOpen);

			$splitData = file_get_contents($oldFile);

			$arraySplitData = mb_str_split($splitData, 10000000);
			unset($splitData);
			$splitData = '';
			$fileOpen = fopen($tmpFileName,'w');
			
			foreach ($arraySplitData as $splitData) {
				if (mb_detect_encoding($splitData, array('UTF-8', 'EUC-KR')) == 'UTF-8') {
					$splitData = mb_convert_encoding($splitData, 'cp949', 'UTF-8');
				}
				$splitData = preg_replace('/[\"](\d{2,4}\-\d{2}\-\d{2}[[:space:]]\d{1,2}\:\d{1,2}\:\d{1,2})(\.\d{1,})[\"]/', '"$1"', $splitData);
				$splitData = preg_replace('/(\"\")([^"",]+)(\"\")/i', '|DD|$2|DD|',$splitData);
				$splitData = preg_replace('/([^\"])(,)(\"\")/i', '$1$2|DD|', $splitData);
				$splitData = preg_replace('/(\"\")(,)([^\"])/i', '|DD|$2$3', $splitData);
				$splitData = preg_replace('/([^\"])(,)([^\"])/i', '$1|C|$3', $splitData);
				$splitData = preg_replace('/([^\"])(,)([^\"])/i', '$1|C|$3', $splitData);
				$splitData = preg_replace('/([^\"])(,)([^\"])/i', '$1|C|$3', $splitData);
				$splitData = preg_replace('/([^\"])(,)([^\"])/i', '$1|C|$3', $splitData);
				$splitData = preg_replace('/([^,])(\"\")([^,])/i', '$1|DD|$3', $splitData);
				$splitData = preg_replace('/([^,])(\")([^,])/i', '$1|D|$3', $splitData);
				$splitData = preg_replace('/([^,])(\")([^,])/i', '$1|D|$3', $splitData);
				$splitData = preg_replace('/([^,])(\")([^,])/i', '$1|D|$3', $splitData);
				$splitData = preg_replace('/([^,])(\")([^,])/i', '$1|D|$3', $splitData);
				$splitData = preg_replace('/^\?[|]D[|]+/i', '"', $splitData);
				$splitData = preg_replace('/^[|]D[|]/i', '"', $splitData);
				$splitData = preg_replace('/[|]D[|]$/i', '"', $splitData);
				$splitData = preg_replace('/[|]D[|](\n)/i', '"$1', $splitData);
				$splitData = preg_replace('/(\n)[|]D[|]/i', '$1"', $splitData);
				$splitData = preg_replace('/[|]D[|](\r)/i', '"$1', $splitData);
				$splitData = preg_replace('/(\r)[|]D[|]/i', '$1"', $splitData);

				fwrite($fileOpen, $splitData);
				unset($splitData);
				$splitData = '';
			}

			fclose($fileOpen);
			unset($splitData);
			$splitData = '';

			$selectField	= array();
			$sortField		= array();

			$fileOpen = fopen($tmpFileName, 'r' );
			$dataField = fgetcsv($fileOpen, 1500000, ',');
			$newField = array();
			$newField = $dataField;

			if (!empty($arrangeInfo['selectField'])) {
				foreach ($arrangeInfo['selectField'] as $searchField => $fieldValue) {
					$selectField[$searchField] = array_search($searchField, $newField);
				}
			}

			if (!empty($arrangeInfo['sortField'])) {
				foreach ($arrangeInfo['sortField'] as $searchField) {
					$sortField[$searchField] = array_search($searchField, $newField);
				}
			}
			
			/* -------------------------------------------
			* - Advice - 데이터 소팅 전 임시 저장 문서 관련 변수
			------------------------------------------- */
			$tempFileOpen = fopen($tmpFileName2,'w');
			$tempLineCnt		= 0;
			// -------------------------------------------

			while($dataRow = fgetcsv($fileOpen, 1500000, "," )) {
				$selectFl		= true;
				if (!empty($selectField)) {
					foreach ($selectField as $oldField => $selectValue) {
						if ($arrangeInfo['selectField'][$oldField] == 'true') {
							if ($dataRow[$selectValue] == '') $selectFl = false;
						}
						else {
							if ((string)$dataRow[$selectValue] != (string)$arrangeInfo['selectField'][$oldField]) $selectFl = false;
						}
					}
				}

				if (!$selectFl) continue;
				
				fwrite($tempFileOpen, rowSetting("\"".implode("\",\"", $dataRow)."\"", 1) . chr(13) . chr(10));
				if (!empty($sortField)) {
					switch (count($sortField)) {
						case 1 :
							$sortKey = getSortKey($dataRow[$sortField[$arrangeInfo['sortField'][0]]]);	
						
							$arraySelectData[$sortKey][] = $tempLineCnt;

							if (!$arrayKey1[$sortKey]) {
								$arraySortKey1[] = $sortKey;
								$arrayKey1[$sortKey] = true;
							}

							break;
						case 2 :
							$sortKey = getSortKey($dataRow[$sortField[$arrangeInfo['sortField'][0]]]);
							$sortKey2 = getSortKey($dataRow[$sortField[$arrangeInfo['sortField'][1]]]);

							$arraySelectData[$sortKey][$sortKey2][] = $tempLineCnt;
							
							if (!$arrayKey1[$sortKey]) {
								$arraySortKey1[] = $sortKey;
								$arrayKey1[$sortKey] = true;
							}

							if (!empty($arraySortKey2[$sortKey])) {
								if (!$arrayKey2[$sortKey][$sortKey2]) {
									$arraySortKey2[$sortKey][] = $sortKey2;
									$arrayKey2[$sortKey][$sortKey2] = true;
								}
							}
							else {
								$arraySortKey2[$sortKey][] = $sortKey2;
								$arrayKey2[$sortKey][$sortKey2] = true;
							}
							break;
						case 3 :
							$sortKey = getSortKey($dataRow[$sortField[$arrangeInfo['sortField'][0]]]);
							$sortKey2 = getSortKey($dataRow[$sortField[$arrangeInfo['sortField'][1]]]);
							$sortKey3 = getSortKey($dataRow[$sortField[$arrangeInfo['sortField'][2]]]);

							$arraySelectData[$sortKey][$sortKey2][$sortKey3][] = $tempLineCnt;
							
							if (!$arrayKey1[$sortKey]) {
								$arraySortKey1[] = $sortKey;
								$arrayKey1[$sortKey] = true;
							}

							if (!empty($arraySortKey2[$sortKey])) {
								if (!$arrayKey2[$sortKey][$sortKey2]) {
									$arraySortKey2[$sortKey][] = $sortKey2;
									$arrayKey2[$sortKey][$sortKey2] = true;
								}
							}
							else {
								$arraySortKey2[$sortKey][] = $sortKey2;
								$arrayKey2[$sortKey][$sortKey2] = true;
							}

							if (!empty($arraySortKey3[$sortKey][$sortKey2])) {
								if (!$arrayKey3[$sortKey][$sortKey2][$sortKey3]) {
									$arraySortKey3[$sortKey][$sortKey2][] = $sortKey3;
									$arrayKey3[$sortKey][$sortKey2][$sortKey3] = true;
								}
							}
							else {
								$arraySortKey3[$sortKey][$sortKey2][] = $sortKey3;
								$arrayKey3[$sortKey][$sortKey2][$sortKey3] = true;
							}
							break;
					}
				}
				else {
					$arraySelectData[$dataRow[0]][] = $tempLineCnt;
					$arraySortKey1[] = $dataRow[0];
				}

				$tempLineCnt++;
			}
			$beforeRowCount = $tempLineCnt;
			fclose($tempFileOpen);
			fclose($fileOpen);

			$arrayKey1		= array();
			$arrayKey2		= array();
			$arrayKey3		= array();
			unset($arrayKey1);
			unset($arrayKey2);
			unset($arrayKey3);

			$newFilePath = $newDataBasePath . $changeTarget;
			$fileOpen = fopen($newFilePath,'w');
			$tempFileOpen = file($tmpFileName2);

			$reviewText		= '';
			$qnaText		= '';
			
			fwrite($fileOpen, rowSetting('"' . preg_replace('/[\"]{2,},/i', '",', implode('","', $newField) . "\""), $arrangeInfo['rowArrage']) . chr(13) . chr(10));
			if ($changeTarget == 'board_sort' || $changeTarget == 'new_board_sort') {
				$newGoodsReview = "./arrangeData/goods_review.csv";
				$newGoodsQna = "./arrangeData/goods_qna.csv";

				$goodsReviewWriteCSV = fopen($newGoodsReview, 'w' );
				$goodsQnaWriteCSV = fopen($newGoodsQna, 'w' );

				fwrite($goodsReviewWriteCSV, rowSetting('"' . preg_replace('/[\"]{2,},/i', '",', implode('","', $newField) . "\""), $arrangeInfo['rowArrage']) . chr(13) . chr(10));
				fwrite($goodsQnaWriteCSV, rowSetting('"' . preg_replace('/[\"]{2,},/i', '",', implode('","', $newField) . "\""), $arrangeInfo['rowArrage']) . chr(13) . chr(10));
			}

			sort($arraySortKey1);
			switch (count($sortField)) {
				case 1 :
					foreach ($arraySortKey1 as $sortKey1) {
						foreach ($arraySelectData[$sortKey1] as $dataLine) {
							fwrite($fileOpen, rowSetting($tempFileOpen[$dataLine], 0, 0, 0));
							$afterRowCount++;
						}
						$arraySelectData[$sortKey1] = array();
						unset($arraySelectData[$sortKey1]);
					}
					break;
				case 2 :
					foreach ($arraySortKey1 as $sortKey1) {
						sort($arraySortKey2[$sortKey1]);
						foreach ($arraySortKey2[$sortKey1] as $sortKey2) {
							foreach ($arraySelectData[$sortKey1][$sortKey2] as $dataLine) {
								fwrite($fileOpen, rowSetting($tempFileOpen[$dataLine], 0, 0, 0));
								$afterRowCount++;
							}
							$arraySelectData[$sortKey1][$sortKey2] = array();
							unset($arraySelectData[$sortKey1][$sortKey2]);
						}
					}
					break;
				case 3 :
					foreach ($arraySortKey1 as $sortKey1) {
						sort($arraySortKey2[$sortKey1]);
						foreach ($arraySortKey2[$sortKey1] as $sortKey2) {
							sort($arraySortKey3[$sortKey1][$sortKey2]);
							foreach ($arraySortKey3[$sortKey1][$sortKey2] as $sortKey3) {
								foreach ($arraySelectData[$sortKey1][$sortKey2][$sortKey3] as $dataLine) {
									fwrite($fileOpen, rowSetting($tempFileOpen[$dataLine], 0, 0, 0));
									$afterRowCount++;
									if ($changeTarget == 'board_sort' || $changeTarget == 'new_board_sort') {
										if ($sortKey1 === '4') { // 상품 후기
											fwrite($goodsReviewWriteCSV, rowSetting($tempFileOpen[$dataLine], 0, 0, 0));
										}
										
										if ($sortKey1 === '6') { // 상품 문의
											fwrite($goodsQnaWriteCSV, rowSetting($tempFileOpen[$dataLine], 0, 0, 0));
										}
									}
									//echo "\"".implode("\",\"", str_replace("\"", "\"\"", $selectData))."\"\n";
									//exit;
								}
								$arraySelectData[$sortKey1][$sortKey2][$sortKey3] = array();
								unset($arraySelectData[$sortKey1][$sortKey2][$sortKey3]);
							}
						}
					}
					break;
			}

			unset($arraySelectData);
			unset($arraySortKey1);
			unset($arraySortKey2);
			unset($arraySortKey3);
			$arraySelectData = array();
			$arraySortKey1 = array();
			$arraySortKey2 = array();
			$arraySortKey3 = array();
			
			fclose($fileOpen);
			if ($changeTarget == 'board_sort' || $changeTarget == 'new_board_sort') {
				fclose($goodsReviewWriteCSV);
				fclose($goodsQnaWriteCSV);
			}
			unlink($tmpFileName);
			unlink($tmpFileName2);
			unset($tempFileOpen);
			$arrayLogText[] = "[arrange row count] : before count {$beforeRowCount} / after count {$afterRowCount}";
			$arrayLogText[] = "[arrange end time] : " . date('Y-m-d H:i:s');
			$arrayLogText[] = "---------------------------------------------" . chr(13);
			if ((int)$arrangeCount === count($arrayBeforeFileName) - 1) {
				$arrayLogText[] = "[log end time] : " . date('Y-m-d H:i:s');
				$arrayLogText[] = "#############################################" . chr(13);
			}
			logFileSetting($logPath . $logFileName, $arrayLogText);
			?>
			<script type="text/javascript">
				parent.setProgress("<?=$arrangeCount?>", "complate");
			</script>
			<?php
		}
	}

	function rowSetting($splitData, $rowArrage, $encodingFl=true, $commaFl=true) {
		if ($commaFl) {
			$splitData = str_replace("|C|", ",", $splitData);
			$splitData = str_replace("|D|", '""', $splitData);
			$splitData = str_replace("|DD|", '""', $splitData);
		}
		
		$splitData = str_replace('"""', '"', $splitData);
		$splitData = str_replace('"""', '"', $splitData);
		$splitData = str_replace('"""', '"', $splitData);
		$splitData = str_replace('alt="" ', 'alt="""" ', $splitData);
		$splitData = str_replace('alt = "" ', 'alt = """" ', $splitData);
		

		if ($rowArrage) {
			$splitData = preg_replace('/(\n)(["][0-9]{1,}+["][,])/i', '$1<chr>$2', $splitData);
			$splitData = preg_replace('/\r\n/i', '<br />', $splitData);
			$splitData = preg_replace('/\r\n/i', '<br />', $splitData);
			$splitData = preg_replace('/\n/i', '<br />', $splitData);
			$splitData = preg_replace('/\n/i', '<br />', $splitData);
			$splitData = str_replace('<br /><chr>', chr(13) . chr(10), $splitData);

			preg_match_all('/\"\<br\ \/\>$/i', $splitData, $result);
			if (!empty($result)) {
				$splitData = preg_replace('/\"\<br\ \/\>$/i', '"', $splitData);
			}
		}

		return $splitData;
	}

	function getSortKey($inData) {
		$datePreg = '^\d{2,4}\-\d{2}\-\d{2}([[:space:]]\d{1,2}\:\d{1,2}\:\d{1,2}\.\d{1,}|[[:space:]]\d{1,2}\:\d{1,2}\:\d{1,2}|[[:space:]]\d{1,2}\:\d{1,2}|[[:space:]]\d{1,2}|)$';

		$outData = $inData;
		preg_match_all('/' . $datePreg . '/i', $inData, $result);

		if ($result[0]) {
			$outData = strtotime($outData);
		}

		return $outData;
	}
?>