<?
ini_set('memory_limit', -1);
ini_set('max_execution_time', 0);

include 'encodingReplace.php';
include '../../lib/lib.func.php';

$arrangeCount = 0;

$arrayBeforeFileName	= trimPostRequest('beforeFileName');
$targetDomain			= trimPostRequest('targetDomain');
$arraySheetName			= trimPostRequest('sheetName');
$arrayDataSort			= trimPostRequest('dataSort');
$arraySheetDate			= trimPostRequest('sheetDate');
$pregType				= trimPostRequest('pregType');

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

$arrayDataSortFieldName = array();
$arrayDateFieldName = array();
if (!empty($arraySheetName)) {
	foreach ($arraySheetName as $sheetNumber => $sheetName) {
		$arrayDataSortFieldName[$sheetName] = explode(',', $arrayDataSort[$sheetNumber]);
		$arrayDateFieldName[$sheetName] = explode(',', $arraySheetDate[$sheetNumber]);
	}
}

$targetFileName = $arrayBeforeFileName[$arrangeCount];
$oldDataBasePath	= '../../excelData/';
$newDataBasePath	= '../../module/' . $targetDomain;
makeDir($newDataBasePath);
$newDataBasePath .= '/dbfile/';
makeDir($newDataBasePath);
$logPath			= $oldDataBasePath . 'log/xls/';
makeDir($logPath);

$arrayLogText = array();
$logFileName = date('Ymd') . '.log';
$arrayLogText[] = "#############################################";
$arrayLogText[] = "[log start time] : " . date('Y-m-d H:i:s');
$arrayLogText[] = "[target domain] : " . $targetDomain;

require_once 'Spreadsheet/Excel/reader.php';
$data = new Spreadsheet_Excel_Reader();
$data->setOutputEncoding('euc-kr'); 
$data->read($oldDataBasePath . $targetFileName);
error_reporting(E_ALL ^ E_NOTICE);

//$arraySheetName = array();
for($sheet = 0; $sheet < count($data->sheets); $sheet++) {
	$beforeRowCount = 0;
	$afterRowCount = 0;
	//$arraySheetName[] = $data->boundsheets[$sheet]['name'];
	$arrayLogText[] = "[arrange start time] : " . date('Y-m-d H:i:s');
	$arrayLogText[] = "[arrange data name] : " . $data->boundsheets[$sheet]['name'] . ' => ' . $data->boundsheets[$sheet]['name'] . '.csv';

	$arrayDataField				= array();
	$arraySortFieldNumber		= array();
	$arrayDateFieldNumber		= array();
	$arraySeparateFieldNumber	= array();
	$arraySeparateSortFieldNumber	= array();
	$arraySeparateDataField		= array();

	$parentMatchFieldNumber		= 0;

	$arrayData					= array();
	//$arraySeparateData			= array();
	$parentMatchData			= '';

	$arraySortField			= array();
	//$arrayDataTypeSortField	= array();

	$sortFieldFlag			= false;
	$sortFieldCount			= 0;

	foreach ($arrayDataSortFieldName as $sheetName => $arraySortInfo) {
		if ($data->boundsheets[$sheet]['name'] == $sheetName) {
			$arraySortField = $arraySortInfo;
		}
	}

	$arrayLogText[] = "[sort field name] : " . implode(', ', $arraySortField);

	for ($row = 1;$row <= $data->sheets[$sheet]['numRows'] && ($row <= $max_rows || $max_rows == 0); $row++) {
		$separateDataFl		= array();
		$parentMatchDataFl	= array();
		
		$tempData			= array();
		for ($col = 1; $col <= $data->sheets[$sheet]['numCols'] && ($col <= $max_cols || $max_cols == 0); $col++) {
			$separateFieldFl	= false;
			$tempDataFl[$row - 2]	= false;
			if ($data->sheets[$sheet]['cellsInfo'][$row][$col]['colspan'] >=1 && $data->sheets[$sheet]['cellsInfo'][$row][$col]['rowspan'] >=1) {
				$this_cell_colspan = " COLSPAN=" . $data->sheets[$sheet]['cellsInfo'][$row][$col]['colspan'];
				$this_cell_rowspan = " ROWSPAN=" . $data->sheets[$sheet]['cellsInfo'][$row][$col]['rowspan'];
				for($i = 1; $i < $data->sheets[$sheet]['cellsInfo'][$row][$col]['colspan']; $i++) {
					$data->sheets[$sheet]['cellsInfo'][$row][$col+$i]['dontprint'] = 1;
				}
				for($i = 1; $i < $data->sheets[$sheet]['cellsInfo'][$row][$col]['rowspan']; $i++) {
					for($j = 0; $j < $data->sheets[$sheet]['cellsInfo'][$row][$col]['colspan']; $j++) {
						$data->sheets[$sheet]['cellsInfo'][$row+$i][$col+$j]['dontprint'] = 1;
					}
				}
			}
			else if($data->sheets[$sheet]['cellsInfo'][$row][$col]['colspan'] >=1) {
				$this_cell_colspan = " COLSPAN=" . $data->sheets[$sheet]['cellsInfo'][$row][$col]['colspan'];
				$this_cell_rowspan = "";
				for($i = 1; $i < $data->sheets[$sheet]['cellsInfo'][$row][$col]['colspan']; $i++) {
					$data->sheets[$sheet]['cellsInfo'][$row][$col + $i]['dontprint'] = 1;
				}
			}
			else if($data->sheets[$sheet]['cellsInfo'][$row][$col]['rowspan'] >=1) {
				$this_cell_colspan = "";
				$this_cell_rowspan = " ROWSPAN=" . $data->sheets[$sheet]['cellsInfo'][$row][$col]['rowspan'];
				for($i = 1; $i < $data->sheets[$sheet]['cellsInfo'][$row][$col]['rowspan']; $i++) {
					$data->sheets[$sheet]['cellsInfo'][$row+$i][$col]['dontprint'] = 1;
				}
			}
			else {
				$this_cell_colspan = "";
				$this_cell_rowspan = "";
			}
			
			if(!($data->sheets[$sheet]['cellsInfo'][$row][$col]['dontprint']))
			{
				$matchFieldFl		= false;
				
				if ($row === 1) {
					$fieldName = nl2br($data->sheets[$sheet]['cells'][$row][$col]);
					if (in_array($fieldName, $arraySortField)) {
						$arraySortFieldNumber[array_search($fieldName, $arraySortField)] = $col;
					}

					if (in_array($fieldName, $arrayDateFieldName[$data->boundsheets[$sheet]['name']])) {
						$arrayDateFieldNumber[] = $col;
					}
					debug($arrayDateFieldNumber);
					if ($parentMatchField === $fieldName) {
						$parentMatchFieldNumber = $col;
						$matchFieldFl = true;
					}

					$arrayDataField[] = $fieldName;
				}
				else {
					
					$dataValue = encodingReplace(str_replace(',', '|COMMA|', str_replace('"', '|DOUBLE|', nl2br($data->sheets[$sheet]['cells'][$row][$col]))));

					if (in_array($col, $arrayDateFieldNumber)) {
						preg_match('/^[0-9]{5}\.[0-9]{1,}$/', $dataValue, $dateResult);
						if (!empty($dateResult)) {
							$dataValue = date('Y-m-d H:i:s', Exl2phpTime($dataValue));
						}
					}

					if ($parentMatchFieldNumber === $col) {
						if ($dataValue) {
							$parentMatchData = $dataValue;
						}
					}
					
					$tempDataValue = ($dataValue == 'NULL' || $dataValue == 'null') ? '' : $dataValue;
					
					$tempData[$col-1] = $tempDataValue;

					if ($tempDataValue) {
						$tempDataFl[$row - 2] = true;
					}

					if ($tempDataFl[$row - 2]) {
						$arrayData[$row - 2] = $tempData;
					}
				}
			}
		}
		if ($row === 1) {
			ksort($arraySortFieldNumber);
		}
		else {
			$beforeRowCount++;
		}
	}

	if (!empty($arrayData)) {
		$arraySortData = array();

		$newFileName = $newDataBasePath . $data->boundsheets[$sheet]['name'] . '.csv';
		$fileOpen = fopen($newFileName,'w');
		fwrite($fileOpen, '"' . rowSetting(implode('","', $arrayDataField) . '"') . chr(13) . chr(10));
		//echo count($arraySortFieldNumber) . '<br/>';

		foreach ($arrayData as $dataRow) {
			//echo getSortKey($dataRow[$arraySortFieldNumber[0]-1]) . ' => ' .getSortKey( $dataRow[$arraySortFieldNumber[1]-1]) . ' => ' . getSortKey($dataRow[$arraySortFieldNumber[2]-1]) . '<br/>';
			
			if (!count($arraySortFieldNumber)) {
				fwrite($fileOpen, '"' . rowSetting(implode('","', $dataRow)) . '"' . chr(13) . chr(10));
			}
			else if (count($arraySortFieldNumber) === 1) {
				$arraySortData[getSortKey($dataRow[$arraySortFieldNumber[0]-1])][]	= $dataRow;
			}
			else if (count($arraySortFieldNumber) === 2) {
				$arraySortData[getSortKey($dataRow[$arraySortFieldNumber[0]-1])][getSortKey($dataRow[$arraySortFieldNumber[1]-1])][]	= $dataRow;
			}
			else if (count($arraySortFieldNumber) === 3) {
				$arraySortData[getSortKey($dataRow[$arraySortFieldNumber[0]-1])][getSortKey($dataRow[$arraySortFieldNumber[1]-1])][getSortKey($dataRow[$arraySortFieldNumber[2]-1])][]	= $dataRow;
			}
		}
		unset($arrayData);
		$arrayData = array();
		if (count($arraySortFieldNumber) === 1) {
			ksort($arraySortData);
			foreach ($arraySortData as $firstDataRow) {
				foreach($firstDataRow as $secondDataRow) {
					fwrite($fileOpen, '"' . rowSetting(implode('","', $secondDataRow)) . '"' . chr(13) . chr(10));
					$afterRowCount++;
				}
			}
		}
		else if (count($arraySortFieldNumber) === 2) {
			ksort($arraySortData);
			foreach ($arraySortData as $firstDataRow) {
				ksort($firstDataRow);
				foreach($firstDataRow as $secondDataRow) {
					foreach ($secondDataRow as $thirdDataRow) {
						fwrite($fileOpen, '"' . rowSetting(implode('","', $thirdDataRow)) . '"' . chr(13) . chr(10));
						$afterRowCount++;
					}
				}
			}
		}
		else if (count($arraySortFieldNumber) === 3) {
			ksort($arraySortData);
			foreach ($arraySortData as $firstDataRow) {
				ksort($firstDataRow);
				foreach($firstDataRow as $secondDataRow) {
					ksort($secondDataRow);
					foreach ($secondDataRow as $thirdDataRow) {
						foreach ($thirdDataRow as $forthRow) {
							fwrite($fileOpen, '"' . rowSetting(implode('","', $forthRow)) . '"' . chr(13) . chr(10));
							$afterRowCount++;
						}
					}
				}
			}
		}
		fclose($fileOpen);
	}
	$arrayLogText[] = "[arrange row count] : before count {$beforeRowCount} / after count {$afterRowCount}";
	$arrayLogText[] = "[arrange end time] : " . date('Y-m-d H:i:s');
}

$arrayLogText[] = "[log end time] : " . date('Y-m-d H:i:s');
$arrayLogText[] = "#############################################" . chr(13);
logFileSetting($logPath . $logFileName, $arrayLogText);

?>
<script type="text/javascript">
	parent.setProgress("<?=$arrangeCount?>", "complate");
</script>
<?php

function Exl2phpTime( $tRes, $dFormat="1900" ) { 
    if( $dFormat == "1904" ) $fixRes = 24107.375; 
    else $fixRes = 25569.375; 
    return intval( ( ( $tRes - $fixRes) * 86400 ) ); 
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

	$splitData = str_replace('"""', '"', $splitData);
	$splitData = str_replace('"""', '"', $splitData);
	$splitData = str_replace('"""', '"', $splitData);

	$splitData = str_replace('alt="" ', 'alt="""" ', $splitData);
	$splitData = str_replace('alt = "" ', 'alt = """" ', $splitData);

	return $splitData;
}
?>