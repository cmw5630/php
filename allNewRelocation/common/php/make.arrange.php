<?php
	ini_set('memory_limit', -1);
	ini_set('max_execution_time', 0);
	setlocale(LC_CTYPE, 'ko_KR.eucKR');

	include '../../lib/lib.func.php';

	$arrangeCount = (trimPostRequest('arrangeCount')) ? trimPostRequest('arrangeCount') : 0;
	
	$arrayBeforeFileName	= trimPostRequest('beforeFileName');
	$arrayAfterFileName		= trimPostRequest('afterFileName');
	$targetDomain			= trimPostRequest('targetDomain');

	$arrayMakeCSVList = array(
		'member'		=> array(
			'fileName'		=> 'member',
			'selectField'	=> array(
			),
			'sortField'		=> array(
				'가입일',
			),
			'rowArrage'		=> false,
		),

		'board'		=> array(
			'fileName'		=> 'board|게시글',
			'selectField'	=> array(
			),
			'sortField'		=> array(
				'그룹번호',
				'답글여부',
				'날짜',
			),
			'rowArrage'		=> false,
		),

		'comment'		=> array(
			'fileName'		=> 'comment|댓글',
			'selectField'	=> array(
			),
			'sortField'		=> array(
				'날짜',
			),
			'rowArrage'		=> false,
		),

		'qa'		=> array(
			'fileName'		=> 'qa|문의',
			'selectField'	=> array(
			),
			'sortField'		=> array(
				'등록일',
			),
			'rowArrage'		=> false,
		),

		'review'		=> array(
			'fileName'		=> 'review|리뷰',
			'selectField'	=> array(
			),
			'sortField'		=> array(
				'등록일',
			),
			'rowArrage'		=> false,
		),
	);

	$oldDataBasePath	= '../../excelData/';
	$newDataBasePath	= '../../module/' . $targetDomain;
	makeDir($newDataBasePath);
	$newDataBasePath .= '/dbfile/';
	makeDir($newDataBasePath);
	$logPath			= $oldDataBasePath . 'log/make/';
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

	foreach ($arrayMakeCSVList as $key => $arrangeInfo) {
		$pregText = '^' . $arrangeInfo['fileName'] . '_[^\.]+\.csv';
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

			$splitData = '';
			$fileOpen = fopen($oldFile,'r');
			$splitData = fread($fileOpen,250000000);
			fclose($fileOpen);
			
			//echo $splitData;
			$splitData = preg_replace('/\r\n[\']+[0-9]{1,}[[:space:]]\/[[:space:]][0-9]{1,}$/', '', $splitData);
			$splitData = preg_replace('/\r[\']+[0-9]{1,}[[:space:]]\/[[:space:]][0-9]{1,}$/', '', $splitData);
			$splitData = preg_replace('/\n[\']+[0-9]{1,}[[:space:]]\/[[:space:]][0-9]{1,}$/', '', $splitData);
			
			/*//회원 데이터 ID 필드가 A열에 있는 경우
			$splitData = preg_replace('/(\r\n)(\"\=\"\")([^\"]+)(\"\,)/', '<br /><chr>"$3",', $splitData);
			$splitData = preg_replace('/(\r)(\"\=\"\")([^\"]+)(\"\,)/', '<br /><chr>"$3",', $splitData);
			$splitData = preg_replace('/(\n)(\"\=\"\")([^\"]+)(\"\,)/', '<br /><chr>"$3",', $splitData);

			$splitData = preg_replace('/(\r\n)([^\,]+)(\,)/', '<br /><chr>$2$3', $splitData);
			$splitData = preg_replace('/(\r)([^\,]+)(\,)/', '<br /><chr>$2$3', $splitData);
			$splitData = preg_replace('/(\n)([^\,]+)(\,)/', '<br /><chr>$2$3', $splitData);
			*/

			$splitData = preg_replace('/(\d{2,4})\/(\d{2})\/(\d{2})/i', '$1-$2-$3', $splitData);
			$splitData = preg_replace('/([[:space:]])[\(](\d{1,2}\:\d{1,2}\:\d{1,2}\.\d{1,}|\d{1,2}\:\d{1,2}\:\d{1,2}|\d{1,2}\:\d{1,2}|\d{1,2})[\)]/i', '$1$2', $splitData);
			$splitData = preg_replace('/(\n)([=]["][^\"]*["][,])/i', '$1<chr>$2', $splitData);
			$splitData = preg_replace('/(\n)(["][0-9]{1,}+["][,])/i', '$1<chr>$2', $splitData);
			$splitData = preg_replace('/(\n)[\"](\d{2,4}\-\d{2}\-\d{2}[[:space:]]\d{1,2}\:\d{1,2}\:\d{1,2}\.\d{1,}|\d{2,4}\-\d{2}\-\d{2}[[:space:]]\d{1,2}\:\d{1,2}\:\d{1,2}|\d{2,4}\-\d{2}\-\d{2}[[:space:]]\d{1,2}\:\d{1,2}|\d{2,4}\-\d{2}\-\d{2}[[:space:]]\d{1,2}|\d{2,4}\-\d{2}\-\d{2})[\"]/i', '$1<chr>"$2"', $splitData);
			$splitData = preg_replace('/\r\n/i', '<br />', $splitData);
			$splitData = preg_replace('/\r/i', '<br />', $splitData);
			$splitData = preg_replace('/\n/i', '<br />', $splitData);
			$splitData = preg_replace('/([\']|)[=](["][0-9]{1,}+["][,])/i', '$2', $splitData);
			$splitData = preg_replace('/([\']|)[=]([\"]?[0-9]{4}\-[0-9]{2}\-[0-9]{2}[[:space:]][0-9]{2}\:[0-9]{2}(\:[0-9]{2}|)[\"]?)/i', '$2', $splitData);
			
			$splitData = preg_replace('/\<br \/\>\<chr\>/i', chr(10), $splitData);

			$splitData = preg_replace('/[\"]\<br \/\>\n(["][0-9]{1,}+["])/i', '"' . chr(10) . '$1', $splitData);
			$splitData = preg_replace('/[,]\<br \/\>\n(["][0-9]{1,}+["])/i', chr(10) . '$1', $splitData);

			$splitData = preg_replace('/[\"]\<br \/\>\n[\"](\d{2,4}\-\d{2}\-\d{2}[[:space:]]\d{1,2}\:\d{1,2}\:\d{1,2}\.\d{1,}|\d{2,4}\-\d{2}\-\d{2}[[:space:]]\d{1,2}\:\d{1,2}\:\d{1,2}|\d{2,4}\-\d{2}\-\d{2}[[:space:]]\d{1,2}\:\d{1,2}|\d{2,4}\-\d{2}\-\d{2}[[:space:]]\d{1,2}|\d{2,4}\-\d{2}\-\d{2})[\"]/i', '"' . chr(10) . '$1', $splitData);

			$splitData = preg_replace('/\<br \/\>\n(["][0-9]{1,}+["])/i', chr(10) . '$1', $splitData);
			$splitData = preg_replace('/\<br \/\>([\"]?[0-9]{4}\-[0-9]{2}\-[0-9]{2}[[:space:]][0-9]{2}\:[0-9]{2}(\:[0-9]{2}|)[\"]?)/i', chr(10) . '$1', $splitData);
			$splitData = preg_replace('/\<br \/\>$/i', '', $splitData);
			$splitData = preg_replace('/\<br \/\>\n$/i', '', $splitData);
			$splitData = preg_replace('/[,]\<br \/\>$/i', '', $splitData);
			$splitData = preg_replace('/[,]\<br \/\>\n$/i', '', $splitData);
			$splitData = preg_replace('/[,]$/i', '', $splitData);


			$splitData = preg_replace('/(\,\"\=\"\")([^\"]*)(\"\"\")/i', ',"$2"', $splitData);
			//,"=""112.218.168.236"

			$splitData = preg_replace('/(\,\"\=\"\")([^\"]+)(\"\,\")/i', ',"$2$3$4', $splitData);
			$splitData = preg_replace('/(\,\"\=\"\")([^\"]+)(\"\,\")/i', ',"$2$3$4', $splitData);
			$splitData = preg_replace('/\"\,\"\=\"\,"/i', '","","', $splitData);

			$splitData = preg_replace('/(\,\=\")([^\"]*)(\")(\n)/i', ',"$2"$4', $splitData);
			$splitData = preg_replace('/(\,\=\")([^\"]*)(\")$/i', ',"$2"', $splitData);
			$splitData = preg_replace('/(\n)(\=\")([^\"]*)(\"\,)/i', '$1"$3",', $splitData);

			$splitData = preg_replace('/(\,\=\")([^\"]*)(\"\,)/i', ',"$2",', $splitData);
			$splitData = preg_replace('/\/\/[[:space:]]\(\:\)/', '', $splitData);

			$fileOpen = fopen($tmpFileName,'w');
			fwrite($fileOpen, $splitData);
			fclose($fileOpen);
			unset($splitData);
			$splitData = '';

			$selectField	= array();
			$sortField		= array();

			$fileOpen = fopen($tmpFileName, 'r' );
			$dataField = fgetcsv($fileOpen, 1500000, ',');
			if (ereg('\|COMMA\|', $dataField[0])) {
				$dataField = explode(',', $dataField[0]);
			}

			$newField = array();
			foreach ($dataField as $key => $value) {
				if (mb_detect_encoding($value, array('UTF-8', 'EUC-KR')) == 'UTF-8') {
					$value = mb_convert_encoding($value, 'cp949', 'UTF-8');
				}
				preg_match_all('/^\?["]/i', $value, $result);
				if (!empty($result)) {
					$value = preg_replace('/^\?["]/i', '', $value);
					$value = preg_replace('/["",]$/i', '', $value);
				}
				$newField[$key] = $value;
			}
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

				if (!empty($sortField)) {
					switch (count($sortField)) {
						case 1 :
							$sortKey = getSortKey($dataRow[$sortField[$arrangeInfo['sortField'][0]]]);	
							$arraySelectData[$sortKey][] = $dataRow;

							if (!$arrayKey1[$sortKey]) {
								$arraySortKey1[] = $sortKey;
								$arrayKey1[$sortKey] = true;
							}

							break;
						case 2 :
							$sortKey = getSortKey($dataRow[$sortField[$arrangeInfo['sortField'][0]]]);
							$sortKey2 = getSortKey($dataRow[$sortField[$arrangeInfo['sortField'][1]]]);

							$arraySelectData[$sortKey][$sortKey2][] = $dataRow;
							
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

							$arraySelectData[$sortKey][$sortKey2][$sortKey3][] = $dataRow;
							
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
					$arraySelectData[$dataRow[0]][] = $dataRow;
					$arraySortKey1[] = $dataRow[0];
				}
				$beforeRowCount++;
			}

			fclose($fileOpen);

			$fileText		= '';
			$splitData = rowSetting('"' . implode('","', $newField) . "\"") . chr(13) . chr(10);
			$fileText		.= $splitData;
			
			sort($arraySortKey1);
			switch (count($sortField)) {
				case 1 :
					foreach ($arraySortKey1 as $sortKey1) {
						foreach ($arraySelectData[$sortKey1] as $selectData) {
							$splitData = rowSetting("\"".implode("\",\"", str_replace("\"", "\"\"", $selectData))."\"") . chr(13) . chr(10);
							$fileText .= $splitData;
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
							foreach ($arraySelectData[$sortKey1][$sortKey2] as $selectData) {
								$splitData = rowSetting("\"".implode("\",\"", str_replace("\"", "\"\"", $selectData))."\"") . chr(13) . chr(10);
								$fileText .= $splitData;
								$afterRowCount++;
							}
							$arraySelectData[$sortKey1][$sortKey2] = array();
							unset($arraySelectData[$sortKey1][$sortKey2]);
						}
					}
					break;
				case 3 :
					foreach ($arraySortKey1 as $sortKey1) {
						if ($arrangeInfo['sortField'][1] == '답글여부') {
							rsort($arraySortKey2[$sortKey1]);
						}
						else {
							sort($arraySortKey2[$sortKey1]);
						}
						foreach ($arraySortKey2[$sortKey1] as $sortKey2) {
							sort($arraySortKey3[$sortKey1][$sortKey2]);
							foreach ($arraySortKey3[$sortKey1][$sortKey2] as $sortKey3) {
								foreach ($arraySelectData[$sortKey1][$sortKey2][$sortKey3] as $selectData) {
									$splitData = rowSetting("\"".implode("\",\"", str_replace("\"", "\"\"", $selectData))."\"") . chr(13) . chr(10);
									$fileText .= $splitData;
									$afterRowCount++;
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

			$newFilePath = $newDataBasePath . $changeTarget;

			arrangeFileSetting($newFilePath, $fileText);
			unset($fileText);
			$fileText = '';

			$fileOpen = fopen($oldFile,'r');
			$splitData = fread($fileOpen, 250000000);
			fclose($fileOpen);

			$fileOpen = fopen($oldFile,'w');
			$splitData = str_replace("|COMMA|", ",", $splitData);
			$splitData = str_replace("|DOUBLE|", '""', $splitData);
			$splitData = str_replace('"""', '"', $splitData);
			$splitData = str_replace('"""', '"', $splitData);
			$splitData = str_replace('"""', '"', $splitData);
			fwrite($fileOpen, $splitData);
			fclose($fileOpen);
			unset($splitData);
			$splitData = '';
			
			unlink($tmpFileName);

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

	function arrangeFileSetting($targetFilePath, $setData) {
		$fileOpen = fopen($targetFilePath,'w');
		fwrite($fileOpen, $setData);
		fclose($fileOpen);
		unset($setData);
		$setData = '';
	}
	
	function rowSetting($splitData) {
		$splitData = str_replace("|COMMA|", ",", $splitData);
		$splitData = str_replace("|DOUBLE|", '""', $splitData);

		$splitData = str_replace('"""', '"', $splitData);
		$splitData = str_replace('"""', '"', $splitData);
		$splitData = str_replace('"""', '"', $splitData);

		if (mb_detect_encoding($splitData, array('UTF-8', 'EUC-KR')) == 'UTF-8') {
			$splitData = mb_convert_encoding($splitData, 'cp949', 'UTF-8');
			preg_match_all('/^\?["]/i', $splitData, $result);
			if (!empty($result)) {
				$splitData = preg_replace('/^\?["]/i', '"', $splitData);
			}
			preg_match_all('/^["]\?["]/i', $splitData, $result);
			if (!empty($result)) {
				$splitData = preg_replace('/^["]\?["]/i', '"', $splitData);
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

    //echo "<script>alert('done');</script>";

/**
* Date = 개발 완료일(2016.11.09)
* ETC = 카페24 데이터 자동 가공 프로세스 개발
* Developer = 한영민
*/

/**
* Date = 수정일(2016.11.18)
* ETC = 속도 개선 및 메모리 관리, log 처리 기능 추가
* Developer = 한영민
*/

/**
* Date = 수정일(2016.11.22)
* ETC = 페이징 처리
* Developer = 한영민
*/
?>