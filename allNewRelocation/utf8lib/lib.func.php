<?php
function gd_json_encode($array=false) {
	if (!class_exists('Services_JSON', false))
		include_once dirname(__FILE__).'/json.class.php';
	$o = new Services_JSON( SERVICES_JSON_LOOSE_TYPE );
	return $o->encode($array);
}

function gd_json_decode($json='') {
	if (!class_exists('Services_JSON', false))
		include_once dirname(__FILE__).'/json.class.php';
	$o = new Services_JSON( SERVICES_JSON_LOOSE_TYPE );
	return $o->decode($json);
}

//-----------------------------------------------------------
//- Advice - 실서버 처리 페이지 호출 및 결과 리턴
//-----------------------------------------------------------
function xmlUrlRequest($url, $arrayPostData) {
	$postData = http_build_query($arrayPostData);
	$xmlOptions = array(
		'http' => array(
			'method'	=> 'POST',
			'header'	=> 'Content-type: application/x-www-form-urlencoded',
			'content'	=> $postData,
		)
	);

	$context = stream_context_create($xmlOptions);
	$response = file_get_contents($url, false, $context);
	$object = simplexml_load_string($response);

	return $object;
}

function arrayIconv($before, $after, $arrayData) {
	foreach ($arrayData as $key => $value) {
		if (is_array($value)) {
			$arrayData[$key] = arrayIconv($before, $after, $value);
		}
		else {
			if (mb_detect_encoding($value, array('UTF-8', 'EUC-KR')) == 'EUC-KR') {
				$arrayData[$key] = iconv($before, $after, $value);
			}
		}
	}
	return $arrayData;
}

function dumpSqlFileSet ($fileName, $arrayDataQuery) {
//	$fileName = '../../' . $fileName . '.sql';
//	$fileName = $fileName . '.sql';
	
	$writeMode = 'w';

	if (file_exists($fileName)) {
		$writeMode = 'a+';
		
	}
	
	$dumpSqlFP = fopen($fileName, $writeMode);
	if ($writeMode == 'w') {
		fwrite($dumpSqlFP, 
			mb_convert_encoding("/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8*/;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
", "UTF-8"));
	}

	foreach ($arrayDataQuery as $dataQuery) {
		fwrite($dumpSqlFP, mb_convert_encoding($dataQuery, "UTF-8"));
		if (substr($dataQuery, -1) != ';') {
			fwrite($dumpSqlFP, mb_convert_encoding(';', "UTF-8"));
		}
		fwrite($dumpSqlFP, chr(13) . chr(10));
	}

	fclose($dumpSqlFP);
	
}

//-----------------------------------------------------------
//- Advice - POST 값 trim 처리 후 리턴
//-----------------------------------------------------------
function trimPostRequest ($parameterName) {
	$arrayOutParameter = array(); // 리턴 배열 변수

	if (is_array($_POST[$parameterName])) {
		foreach ($_POST[$parameterName] as $parameterValue) {
			$arrayOutParameter[] = stripslashes(trim($parameterValue));
		}
		return $arrayOutParameter;
	}
	else {
		return stripslashes(trim($_POST[$parameterName]));
	}
}

//-----------------------------------------------------------
//- Advice - Right 함수
//- 문자열의 오른쪽부터 정해진 수만큼의 문자를 반환한다.
//-----------------------------------------------------------
function Right($string, $cnt){
	$string = substr($string, (strlen($string) - $cnt), strlen($string));
	return $string;
}
//-----------------------------------------------------------

//-----------------------------------------------------------
//- Advice - Left 함수
//- 문자열의 왼쪽부터 정해진 수만큼의 문자를 반환한다.
//-----------------------------------------------------------
function Left($string, $cnt){
	return substr($string, 0, $cnt);
}

//------------------------------------------------------
// - Advice - 데이터 리플레이스 수만큼 리플레이스 처리
//------------------------------------------------------
function dataCntReplace($param, $ori, $chg, $roop_cnt){
	if($param){
		for($chg_cnt=0;$chg_cnt<$roop_cnt;$chg_cnt++){
			$param = str_replace($ori[$chg_cnt], $chg[$chg_cnt], $param);
		}
	}
	return $param;
}

//------------------------------------------------------
// - Advice - 데이터 조건을 확인 하여 변환
//------------------------------------------------------
function dataIfChange($oldParam, $ori, $chg, $roop_cnt) {
	$newParam = '';
	$changeFlag = false;
	if($oldParam){
		for($chg_cnt=0;$chg_cnt<$roop_cnt;$chg_cnt++){
			if($oldParam == $ori[$chg_cnt]){
				$newParam = $chg[$chg_cnt];
				$changeFlag = true;
			}
		}

		if (!$changeFlag) {
			$newParam = $oldParam;
		}
	}
	return $newParam;
}

//-----------------------------------------------------------
//- Advice - breakTagChange 함수
//- br 태그를 ascii형태로 변환 하여 반환
//-----------------------------------------------------------
function breakTagChange($parameter){
	$parameter = str_replace('<br/>', chr(10), $parameter);
	$parameter = str_replace('<br />', chr(10), $parameter);
	$parameter = str_replace('<br>', chr(10), $parameter);

	return $parameter;
}

function makeDir($dirPath) {
	if (!is_dir($dirPath)) {
		mkdir($dirPath);
		chmod($dirPath, 0707);
	}
}

function fetchRow($query) {
	global $db;
	
	$reault = $db->query($query) or die(mysql_error() . ' [error Query] : ' . $query);
	list($data) = mysqli_fetch_row($reault);

	return $data;
}

function subTableGetData($dataType, $dataName, $dataField, $dataSort) {
	global $db, $csvFilePath;
	$arrayData = array();

	switch ($dataType) {
		case 'csv':
			$fp = fopen($csvFilePath . $dataName . '.csv', 'r' );
			$dataRow = fgetcsv($fp, 1500000, ',');
			while($dataRow = fgetcsv($fp, 1500000, ',')) $arrayData[] = $dataRow;
		break;
		default:
			if($dataSort){
				$sort = ' order by ' . $dataSort;
			}
			$dataResult = $db->query("select " . $dataField . " from " . $dataName . $sort);

			while($dataRow = $db->fetch($dataResult)) $arrayData[] = $dataRow;
		break;
	}

	return $arrayData;
}

function dateCreate ($oldDate) {
	$newDate = '';
	if ($oldDate) {
		if (ereg('\.', $oldDate)) {
			$tempRegDt = explode('.', $oldDate);
			$oldDate = $tempRegDt[0];
		}
		$newDate = defaultReplace($oldDate);
		if (strlen($newDate) === 8 || strlen($newDate) > 10) {
			$newDate = strtotime($newDate);
		}

		if ($newDate) {
			$newDate = date('Y-m-d H:i:s', $newDate);
		}
	}
	return $newDate;
}

function debug($value) {
	print "<xmp style=\"display:block;font:9pt 'Bitstream Vera Sans Mono, Courier New';background:#202020;color:#D2FFD2;padding:10px;margin:5px;overflow:auto;\">";
	switch (gettype($value)) {
		case 'string' :
			echo $value;
			break;
		case 'array' :
		case 'object' :
		default :
			print_r($value);
			break;
	}
	print "</xmp>";
}

function logFileSetting($logFilePath, $arrayLogText) {
	$fileOpen = fopen($logFilePath, 'a+');
	fwrite($fileOpen, implode(chr(13), arrayIconv('EUC-KR', 'UTF-8', $arrayLogText)));
	fclose($fileOpen);
}

?>
