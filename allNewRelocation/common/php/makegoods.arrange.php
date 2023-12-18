<?php

	ini_set('memory_limit', -1);
	ini_set('max_execution_time', 0);
	setlocale(LC_CTYPE, "ko_KR.eucKR");

	include '../../lib/lib.func.php';

	$arrangeCount = (trimPostRequest('arrangeCount')) ? trimPostRequest('arrangeCount') : 0;
	
	$arrayBeforeFileName	= trimPostRequest('beforeFileName');
	$arrayAfterFileName		= trimPostRequest('afterFileName');
	$targetDomain			= trimPostRequest('targetDomain');

	$arrayMakeCSVList = array(
		'goods'		=> array(
			'fileName'		=> 'brand',
			'selectField'	=> array(
			),
			'sortField'		=> array(
				'등록일자',
				'상품 고유번호'
			),
			'rowArrage'		=> false,
		)
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
		$pregText = '^' . $arrangeInfo['fileName'] . '_[^\.]+\.xml';
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
			$tmpFileName	= $newDataBasePath . preg_replace('/\.csv/', '_tmp.xml', $changeTarget);
			$tmpFileName2	= $newDataBasePath . preg_replace('/\.csv/', '_tmp2.xml', $changeTarget);
			$tmpFileName3	= $newDataBasePath . preg_replace('/\.csv/', '_tmp3.xml', $changeTarget);

			$splitData = '';
			$fileOpen = fopen($oldFile,'r');
			$splitData = fread($fileOpen, 270000000);
			fclose($fileOpen);
				
			$splitData = preg_replace('/([s][s])[:]/i', '$1', $splitData);
			//$splitData = preg_replace('/\!\&\#45\;\-/i','--', $splitData);
			$splitData = preg_replace('/\&\#10\;/i', '|BRAKE|', $splitData);
			$splitData = preg_replace('/\&/i', '|NPER|', $splitData);
			$splitData = preg_replace('/\&/i', '|NPER|', $splitData);
			$splitData = preg_replace('/\&/i', '|NPER|', $splitData);
			$splitData = preg_replace('/\&/i', '|NPER|', $splitData);
			$splitData = preg_replace('/[,]/i', '|COMMA|', $splitData);
			$splitData = preg_replace('/[,]/i', '|COMMA|', $splitData);
			$splitData = preg_replace('/[,]/i', '|COMMA|', $splitData);
			$splitData = preg_replace('/[,]/i', '|COMMA|', $splitData);
			$splitData = preg_replace('/\<(Worksheet)[[:space:]]([^\>]+)\>/i', '<ss$1 $2>', $splitData);
			$splitData = preg_replace('/\<\/(Worksheet)\>/i', '</ss$1>', $splitData);
			//$splitData = htmlspecialchars_decode($splitData);
			$fileOpen = fopen($tmpFileName,'w');
			fwrite($fileOpen, $splitData);
			fclose($fileOpen);
			unset($splitData);
			
			$response = file_get_contents($tmpFileName, false);
			$object = simplexml_load_string($response);
			
			$goodsNumberFieldName = '상품 고유번호';
			$optionCoordinationFieldName = '옵션 필수 여부';
			$optionCoordinationFieldNumber = 0;
			$goodsNumberOriField	= 0;
			$fieldTotalCount = 0;

			$arrayDataFieldName = array();
			$arrayData = array();
			$parentRowNumber = 0;
			$rowCount = 0;

			foreach ($object->ssWorksheet as $oriData) {
				foreach ($oriData->Table as $tableData) {
					foreach ($tableData->Row as $rowData) {

						$fieldCount = 1;
						$goodsNumberSettingFl	= false;
						foreach ($rowData->Cell as $cellData) {
							$ssIndex = $cellData->attributes()->ssIndex;
							$dataValue = (string)$cellData->Data;
							if (mb_detect_encoding($dataValue, array('UTF-8', 'EUC-KR')) == 'UTF-8') {
								$dataValue = mb_convert_encoding($dataValue, 'cp949', 'UTF-8');
							}

							if ($rowCount === 0) {
								if (ereg('\|BRAKE\|', $dataValue)) {
									$arrayFieldTempName = explode('|BRAKE|', $dataValue);
									$dataValue = $arrayFieldTempName[0];
								}

								if ($dataValue == $goodsNumberFieldName) {
									$arrayDataFieldName[0] = $dataValue;
									$goodsNumberOriField = $fieldCount - 1;
								}
								else if ($dataValue == $optionCoordinationFieldName) {
									$optionCoordinationFieldNumber = $fieldCount;
									$arrayDataFieldName[$fieldCount] = $dataValue;
									$fieldCount++;
								}
								else {
									$arrayDataFieldName[$fieldCount] = $dataValue;
									$fieldCount++;
								}
							}
							else if ($rowCount >= 2) {
								$fieldTotalCount = count($arrayDataFieldName);
								if ($ssIndex) {
									for ($fieldCount; $fieldCount < $ssIndex - 1; $fieldCount++) {
										$arrayData[$rowCount - 2][$fieldCount - 1] = '';
									}
								}
								if (($fieldCount - 1) === $goodsNumberOriField && !$goodsNumberSettingFl) {
									$arrayData[$rowCount - 2][0] = str_replace('"', '|DOUBLE|', $dataValue);
									$goodsNumberSettingFl = true;
								}
								else {
									$arrayData[$rowCount - 2][$fieldCount] = str_replace('"', '|DOUBLE|', $dataValue);
									$fieldCount++;
								}
							}
						}
						if ($rowCount >= 2) {
							if (!$arrayData[$rowCount - 2][0]) {
								
								foreach ($arrayData[$rowCount - 2] as $key => $value) {
									if ($value != '') {
										if ($arrayData[$parentRowNumber][$key] != '') {

											if ($arrayData[$rowCount - 2][$optionCoordinationFieldNumber] == '') {
												$arrayData[$parentRowNumber][$key] .= ',' . $value;
											}
											else {
												$arrayData[$parentRowNumber][$key] .= '@#$' . $value;
											}
										}
										else {
											$arrayData[$parentRowNumber][$key] .= $value;
										}
									}
								}
								unset($arrayData[$rowCount - 2]);
								//$arrayData[$rowCount - 2] = array();
								$rowCount--;
							}
							else {
								$parentRowNumber = $rowCount - 2;
							}
						}
						
						$rowCount++;
						//echo '--------------------------------------------------------------------<br/>';
					}
				}
			}

			$beforeRowCount = $parentRowNumber + 1;

			ksort($arrayDataFieldName);
			$fileText = '';
			$fileText .= '"' . preg_replace('/[\"]{2,},/i', '",', implode('","', $arrayDataFieldName) . "\"") . chr(13) . chr(10);

			foreach ($arrayData as $arrayDataKey => $arrayDataRow) {
				ksort($arrayDataRow);
				$arrayData[$arrayDataKey] = $arrayDataRow;
				$fileText .= '"' . implode('","', $arrayData[$arrayDataKey]) . '"' . chr(13) . chr(10);
			}

			$fileText = preg_replace('/(\n)(["][0-9]{1,}+["][,])/i', '$1<chr>$2', $fileText);
			$fileText = preg_replace('/\r\n/i', '<br />', $fileText);
			$fileText = preg_replace('/\r\n/i', '<br />', $fileText);
			$fileText = preg_replace('/\n/i', '<br />', $fileText);
			$fileText = preg_replace('/\n/i', '<br />', $fileText);
			$fileText = str_replace('<br /><chr>', chr(13) . chr(10), $fileText);

			$fileText = str_replace("|COMMA|", ",", $fileText);
			$fileText = str_replace("|DOUBLE|", '""', $fileText);

			$fileText = str_replace('"""', '"', $fileText);
			$fileText = str_replace('"""', '"', $fileText);
			$fileText = str_replace('"""', '"', $fileText);

			preg_match_all('/\"\<br\ \/\>$/i', $fileText, $result);
			if (!empty($result)) {
				$fileText = preg_replace('/\"\<br\ \/\>$/i', '"', $fileText);
			}

			$fileOpen = fopen($tmpFileName2,'w');
			fwrite($fileOpen, $fileText);
			fclose($fileOpen);
			unset($fileText);
			
			$fileOpen = fopen($tmpFileName2,'r');
			$splitData = fread($fileOpen, 100000000);
			fclose($fileOpen);

			$fileOpen = fopen($tmpFileName3,'w');
			if (mb_detect_encoding($splitData, array('UTF-8', 'EUC-KR')) == 'UTF-8') {
				$splitData = mb_convert_encoding($splitData, 'cp949', 'UTF-8');
			}
			$splitData = preg_replace('/([^,])(\")([^,])/i', '$1|DOUBLE|$3', $splitData);
			$splitData = preg_replace('/([^,])(\")([^,])/i', '$1|DOUBLE|$3', $splitData);
			$splitData = preg_replace('/([^,])(\")([^,])/i', '$1|DOUBLE|$3', $splitData);
			$splitData = preg_replace('/([^,])(\")([^,])/i', '$1|DOUBLE|$3', $splitData);
			
			$splitData = preg_replace('/^\?[|]DOUBLE[|]+/i', '"', $splitData);
			$splitData = preg_replace('/^[|]DOUBLE[|]/i', '"', $splitData);
			$splitData = preg_replace('/[|]DOUBLE[|]$/i', '"', $splitData);
			$splitData = preg_replace('/[|]DOUBLE[|](\n)/i', '"$1', $splitData);
			$splitData = preg_replace('/(\n)[|]DOUBLE[|]/i', '$1"', $splitData);
			$splitData = preg_replace('/[|]DOUBLE[|](\r)/i', '"$1', $splitData);
			$splitData = preg_replace('/(\r)[|]DOUBLE[|]/i', '$1"', $splitData);

			fwrite($fileOpen, $splitData);
			fclose($fileOpen);
			unset($splitData);
			$splitData = '';

			$fileOpen = fopen($tmpFileName3, 'r' );
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
			}

			fclose($fileOpen);

			$arrayKey1		= array();
			$arrayKey2		= array();
			$arrayKey3		= array();
			unset($arrayKey1);
			unset($arrayKey2);
			unset($arrayKey3);

			$fileText		= '';
			$splitData = rowSetting('"' . preg_replace('/[\"]{2,},/i', '",', implode('","', $newField) . "\""), $arrangeInfo['rowArrage']) . chr(13) . chr(10);
			$fileText		.= $splitData;

			sort($arraySortKey1);
			switch (count($sortField)) {
				case 1 :
					foreach ($arraySortKey1 as $sortKey1) {
						foreach ($arraySelectData[$sortKey1] as $selectData) {
							$splitData = rowSetting("\"".implode("\",\"", $selectData)."\"", $arrangeInfo['rowArrage']) . chr(13) . chr(10);
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
								$splitData = rowSetting("\"".implode("\",\"", $selectData)."\"", $arrangeInfo['rowArrage']) . chr(13) . chr(10);
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
						sort($arraySortKey2[$sortKey1]);
						foreach ($arraySortKey2[$sortKey1] as $sortKey2) {
							sort($arraySortKey3[$sortKey1][$sortKey2]);
							foreach ($arraySortKey3[$sortKey1][$sortKey2] as $sortKey3) {
								foreach ($arraySelectData[$sortKey1][$sortKey2][$sortKey3] as $selectData) {
									$splitData = rowSetting("\"".implode("\",\"", $selectData)."\"", $arrangeInfo['rowArrage']) . chr(13) . chr(10);
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
			
			$targetFilePath = $newDataBasePath . $changeTarget;

			$fileOpen = fopen($targetFilePath,'w');
			fwrite($fileOpen, $fileText);
			fclose($fileOpen);
			unset($setData);
			$setData = '';

			unlink($tmpFileName);
			unlink($tmpFileName2);
			unlink($tmpFileName3);

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

	function getSortKey($inData) {
		$datePreg = '^\d{2,4}\-\d{2}\-\d{2}([[:space:]]\d{1,2}\:\d{1,2}\:\d{1,2}\.\d{1,}|[[:space:]]\d{1,2}\:\d{1,2}\:\d{1,2}|[[:space:]]\d{1,2}\:\d{1,2}|[[:space:]]\d{1,2}|)$';

		$outData = $inData;
		preg_match_all('/' . $datePreg . '/i', $inData, $result);

		if ($result[0]) {
			$outData = strtotime($outData);
		}

		return $outData;
	}

	function rowSetting($splitData) {
		$splitData = str_replace("|COMMA|", ",", $splitData);
		$splitData = str_replace("|DOUBLE|", '""', $splitData);
		$splitData = str_replace("|BRAKE|", '<br />', $splitData);
		$splitData = str_replace("|NPER|", '&', $splitData);

		$splitData = str_replace('"""', '"', $splitData);
		$splitData = str_replace('"""', '"', $splitData);
		$splitData = str_replace('"""', '"', $splitData);

		return $splitData;
	}
?>