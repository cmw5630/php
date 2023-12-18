<?php

/*
* 일반 페이지 클래스 호출
*/
function class_load ($classname) {
	static $class_arr=array();
	include_once ($classname.'.class.php');
	$class_arr[$classname]=new $classname();
	return $class_arr[$classname];
}

function godo5RelocationGoodsOption ($arrayOption1, $arrayOption2 = array(), $arrayOption3 = array(), $arrayOption4 = array(), $arrayOption5 = array()) 
{
	global $goodsNo, $optionInsertSet, $optionInsertCount;
	
	$arrayNewOptionData = array();
	$arrayOptionQuery = array();
	$arrayIndex = array();
	$totalRoopCount = 1;
	$optionCount = 0;

	$optionFl = false;
	for ($i = 1; $i <= 5; $i++) {
		if (!empty(${'arrayOption' . $i})) {
			$optionFl = true;
			$arrayIndex[$i - 1] = 0;
			$totalRoopCount *= count(${'arrayOption' . $i}['value']);
			$optionCount++;
		}
	}
	
	if ($optionFl) {
		$optionNumber = 1;
		for($i = 0; $i < $totalRoopCount; $i++) {
			$arrayNewOptionData = array();

			$arrayOptionValue = array();
			$arrayOptionPrice = array();
			$arrayOptionStock = array();
			
			for($j = $optionCount - 1; $j >= 0 ; $j--) {
				if ((int)$arrayIndex[$j] === (int)count(${'arrayOption' . ($j + 1)}['value'])) {
					$arrayIndex[$j] = 0; 
					$arrayIndex[($j - 1)]++;
				}

				$arrayOptionValue[$j] = ${'arrayOption' . ($j + 1)}['value'][$arrayIndex[$j]];
				$arrayOptionPrice[$j] = (${'arrayOption' . ($j + 1)}['price'][$arrayIndex[$j]]) ? ${'arrayOption' . ($j + 1)}['price'][$arrayIndex[$j]] : 0;
				$arrayOptionStock[$j] = ($j + 1 === 1) ? ${'arrayOption' . ($j + 1)}['stock'][$arrayIndex[$j]] : 0;
				${'arrayOption' . ($j + 1)}['stock'][$arrayIndex[$j]] = 0;
			}

			$arrayIndex[$optionCount - 1]++;

			$arrayNewOptionData['goodsNo'] = $goodsNo;
			$arrayNewOptionData['optionNo'] = $optionNumber;
			$arrayNewOptionData['optionValue1'] = $arrayOptionValue[0];
			$arrayNewOptionData['optionValue2'] = $arrayOptionValue[1];
			$arrayNewOptionData['optionValue3'] = $arrayOptionValue[2];
			$arrayNewOptionData['optionValue4'] = $arrayOptionValue[3];
			$arrayNewOptionData['optionValue5'] = $arrayOptionValue[4];
			$arrayNewOptionData['optionPrice'] = array_sum($arrayOptionPrice);
			$arrayNewOptionData['optionViewFl'] = 'y';
			$arrayNewOptionData['optionSellFl'] = 'y';
			$arrayNewOptionData['optionCode'] = 'NULL';
			$arrayNewOptionData['stockCnt'] = array_sum($arrayOptionStock);

			$optionInsertSet->querySet($arrayNewOptionData, $optionInsertCount);

			$optionNumber++;
			$optionInsertCount++;
		}
	}
}

//-----------------------------------------------------------
//- Advice - telCreate 함수
//- 전화 번호 양식을 만들어 줍니다.
//-----------------------------------------------------------
function telCreate($pram){
	$pram = str_replace('-', '', $pram);
	if(substr($pram,0,2)=="02"){
		$tel1=substr($pram,0,2);
		$tel2=substr($pram,2,strlen($pram)-6);
		$tel3=substr($pram,-4);
	} else {
		$tel1=substr($pram,0,3);
		$tel2=substr($pram,3,strlen($pram)-7);
		$tel3=substr($pram,-4);
	}
	$pram = $tel1."-".$tel2."-".$tel3;
	return $pram;
}

//-----------------------------------------------------------
//- Advice - flagChange 함수
//- 프래그 값을 고도 솔루션에 맞게 변환
//-----------------------------------------------------------
function flagChange($outType, $inParameter) {
	$arrayOldTrueFlag = array('y', 'Y', 'T', 't', 'TRUE', '1', '기혼', '기', '수신허용', '수신', '허용', '수신함', '남자', '남', '남성', 'm', 'M', '3', '양', '양력', 'S', 's', 'YES', 'Yes', 'yes', '공지', '비밀', 'secret', '부가세 상품', '판매 중', '판매');
	$arrayOldFalseFlag = array('n', 'N', 'F', 'f', 'FALSE', '0', '미혼', '미', '수신거부', '거부', '수신안함', '여자', '여', '여성', 'w', 'W', '2', '4', '음', '음력', 'L', 'l', 'NO', 'No', 'no', '면세상품', '판매 중지');

	$arrayNewFlag = array(
		'yn'	=> array('true' => 'y', 'false' => 'n'),
		'10'	=> array('true' => '1', 'false' => '0'),
		'deli'	=> array('true' => '선불', 'false' => '후불'),
		'sex'	=> array('true' => 'm', 'false' => 'w'),
		'o'		=> array('true' => 'o', 'false' => 'x'),
		'sl'	=> array('true' => 's', 'false' => 'l'),
		'tf'	=> array('true' => 't', 'false' => 'f'),
		'cg'	=> array('true' => 'c', 'false' => 'g'),
	);

	if (in_array($inParameter, $arrayOldTrueFlag)) {
		$outParmeter = $arrayNewFlag[$outType]['true'];
	}
	else if (in_array($inParameter, $arrayOldFalseFlag)) {
		$outParmeter = $arrayNewFlag[$outType]['false'];
	}

	if ($outParmeter == '') {
		if (trim($inParameter)) {
			$outParmeter = $arrayNewFlag[$outType]['true'];
		}
		else {
			$outParmeter = $arrayNewFlag[$outType]['false'];
		}
	}

	if ($outParmeter == 'x') {
		$outParmeter = '';
	}
	return $outParmeter;
}

//-----------------------------------------------------------
//- Advice - 기본데이터 리플레이스
//-----------------------------------------------------------
function defaultReplace($param){
	$param = str_replace('[','',$param);
	$param = str_replace(']','',$param);
	$param = str_replace(' ','',$param);
	$param = str_replace('/','',$param);
	$param = str_replace('(','',$param);
	$param = str_replace(')','',$param);
	$param = str_replace(':','',$param);
	$param = str_replace('-','',$param);
	$param = str_replace(',','',$param);
	$param = str_replace('.','',$param);

	return $param;

	return $param;
}

//-----------------------------------------------------------
//- Advice - 우편 번호 생성 함수
//-----------------------------------------------------------
function zipCodeCreate ($inParam) {
	$outParam = '';
	if (strlen($inParam) === 5) {
		$outParam = $inParam;
	}
	else {
		$outParam = substr($inParam, 0, 3) . '-' . substr($inParam, 3, 3); // 우편번호 생성
	}

	return $outParam;
}

// 파일 복사 함수
function fileCopy($oldFile, $newFile, $msgFlag = false) {
	$msg = '';
	$result = 1;
	if (!file_exists($newFile)) {
		if (@copy($oldFile, $newFile)) {
			chmod($newFile, 0707);
			$msg = '<div style="color:blue;">복사 성공 : ' . $oldFile . ' => ' . $newFile . '</div>';
		}
		else {
			$msg = '<div style="color:red;font-weight:bold;">복사 실패 : ' . $oldFile . ' => ' . $newFile . '</div>';
			$result = 0;
		}
	}
	else {
		$msg = '<div style="color:blue;">이전 후 파일 존재 : ' . $newFile . '</div>';
	}
	if ($msgFlag) {
		echo $msg;
	}

	return $result;
}

function fileRename($oldFileName, $newFileName) {
	$arrayFileExt = explode('.', $oldFileName);
	if (count($arrayFileExt) > 1) {
		$fileExt = $arrayFileExt[count($arrayFileExt) - 1];
		$newFileName = $newFileName . '.' . $fileExt;
	}

	return $newFileName;
}

function fileExtSet ($filePath) {
	$arrayFileExt = explode('.', $filePath);
	$arrayNewFilePath = array();
	if (count($arrayFileExt) > 1) {
		$fileExt = $arrayFileExt[count($arrayFileExt) - 1];
		if ($fileExt != 'jpg' && $fileExt != 'jpeg' && $fileExt != 'gif' && $fileExt != 'png' && $fileExt != 'JPG' && $fileExt != 'JPEG' && $fileExt != 'GIF' && $fileExt != 'PNG') {
			$fileExt = image_type_to_extension(exif_imagetype($filePath));
			for ($i = 0; $i < count($arrayFileExt) - 1; $i++) {
				if ($arrayFileExt[$i]) {
					$arrayNewFilePath[] = $arrayFileExt[$i];
				}
			}
			rename($filePath, implode($arrayNewFilePath) . '.' . $fileExt);
		}
		else {
			$fileExt = '';
		}
	}
	else {
		$fileExt = image_type_to_extension(exif_imagetype($filePath));
		rename($filePath, $filePath . '.' . $fileExt);
	}

	return $fileExt;
}

//-----------------------------------------------------------
//- Advice - commentSetting 함수
//- 자사 게시글 댓글 형식에 맞게 코멘트 데이터를 변환하여 반환
//-----------------------------------------------------------
function commentSetting($comment){
	$comment = str_replace(chr(10) . chr(13), '\n', $comment);
	$comment = str_replace(chr(10), '\n', $comment);
	$comment = str_replace(chr(13), '\n', $comment);
	$comment = str_replace('<br/>', '\n', $comment);
	$comment = str_replace('<br />', '\n', $comment);
	$comment = str_replace('<br>', '\n', $comment);
	$comment = strip_tags($comment);

	return $comment;
}

//-----------------------------------------------------------
//- Advice - extraInfoSet 함수
//- 상품 정보 재고 고시 데이터 셋팅 함수
//- 파라미터(제목, 값, 번호)
//-----------------------------------------------------------
function goodsInfoSet ($arrayExtraInfo) {
	$lineCount = 0;
	$stepCount = 0;

	foreach ($arrayExtraInfo as $key => $extraInfoRow) {
		$arrayNewExtraInfo['line' . $lineCount]['step' . $stepCount] = array(
			'infoTitle'	=> addslashes($extraInfoRow['title']),
			'infoValue'	=> addslashes($extraInfoRow['desc']),
		);

		if (($key % 2)) {
			$lineCount++;
			$stepCount = 0;
		}
		else if (!($key % 2)) {
			$stepCount++;
		}
	}

	return preg_replace("/(\\\')+/i", "'$2", gd_json_encode($arrayNewExtraInfo));
}

function setCategoryBrand($arrayData, $type='') {
	global $db, $goodsNo, $arrayGoodsLink, $arrayGoodsLinkSort;
	if (!$arrayGoodsLink) $arrayGoodsLink = array();
	if (!$arrayGoodsLinkSort) $arrayGoodsLinkSort = array();
	
	$arrayQuery = array();

	$tableName = '';
	$nameField = '';
	$codeField = '';
	if ($type == 'brand') {
		$tableName			= 'es_categoryBrand';
		$relationTableName	= 'es_goodsLinkBrand';
	}
	else {
		$tableName			= 'es_categoryGoods';
		$relationTableName	= 'es_goodsLinkCategory';
	}
	$nameField = 'cateNm';
	$codeField = 'cateCd';

	$varCategoryCode = '';
	$qrCategoryInsert = '';
	$arrSubCategoryName=array();

	if (!is_array($arrayData)) {
		$arrayData = array($arrayData);
	}

	if ($arrayData[0]) {
		// 1차 카테고리
		$varCategoryName = addslashes($arrayData[0]);
		$tmpCatnmExist = fetchRow("select count(*) from " . $tableName . " where " . $nameField . "='" . $varCategoryName . "' and length(" . $codeField . ")=3");//이 상품의 1차 카테고리가 존재하는지 검사
		if(!$tmpCatnmExist){ //같은 카테고리가 없으면 생성
				$tmpMaxCatgoryCode = fetchRow("select " . $codeField . " from " . $tableName . " where length(" . $codeField . ")=3 order by " . $codeField . " desc limit 1");//가장 큰 카테고리값 +1

				$varCategoryCode = makeCodeNumber($tmpMaxCatgoryCode + 1);
				$qrCategoryInsert = "insert into " . $tableName . " set " . $nameField . "='" . $varCategoryName . "', " . $codeField . "='" . $varCategoryCode . "'";
				$db->query($qrCategoryInsert);
		}
		else{ //카테고리가 있으면 할당
			$tmpCategoryCode = fetchRow("select " . $codeField . " from " . $tableName . " where " . $nameField . "='" . $varCategoryName . "' and length(" . $codeField . ")=3");
			$varCategoryCode = $tmpCategoryCode;
		}


		// 서브카테고리명 목록
		for($i=1;$i<count($arrayData);$i++){
			$arrSubCategoryName[] = $arrayData[$i];
		}

		// 서브카테고리 루프
		foreach($arrSubCategoryName as $key => $varSubCategoryName){
			if ($varSubCategoryName) {
				$varSubCategoryName = addslashes($varSubCategoryName);
				$tmpSubCatnmExist = fetchRow("select count(*) from " . $tableName . " where " . $nameField . "='" . $varSubCategoryName . "' and length(" . $codeField . ")=" . (($key + 2) * 3) . " and " . $codeField . " like '" . $varCategoryCode . "%'");
				if(!$tmpSubCatnmExist){//같은 카테고리가 없으면 생성
					$tmpMaxSubCatgoryCode = fetchRow("select substring(" . $codeField . "," . ((($key + 1) * 3) + 1) . ",3) from " . $tableName . " where length(" . $codeField . ")=" . (($key+2)*3) . " and " . $codeField . " like '" . $varCategoryCode . "%' order by " . $codeField . " desc limit 1");//가장 큰 카테고리값 +1
					$varCategoryCode_sub = makeCodeNumber($tmpMaxSubCatgoryCode + 1);
					$varCategoryCode .= $varCategoryCode_sub;
					$qrCategoryInsert_sub = "insert into " . $tableName . " set " . $nameField . "='" . $varSubCategoryName . "', " . $codeField . "='" . $varCategoryCode . "'";
					$db->query($qrCategoryInsert_sub);
				}
				else{//있으면 할당
					$tmpSubCategoryCode = fetchRow("select " . $codeField . " from " . $tableName . " where " . $nameField . "='" . $varSubCategoryName . "' and  length(" . $codeField . ")=" . (($key + 2) * 3) . " and " . $codeField . " like '" . $varCategoryCode . "%'");
					$varCategoryCode = $tmpSubCategoryCode;
				}
			}
		}
	}

	for ($codeCnt = 0; $codeCnt < strlen($varCategoryCode); $codeCnt += 3) {
		$newCategoryNumber = substr($varCategoryCode, 0, $codeCnt + 3);
		if (!$arrayGoodsLink[$type][$newCategoryNumber]) {
			$arrayGoodsLink[$type][$newCategoryNumber] = true;
			$arrayGoodsLinkSort[$type][$newCategoryNumber] = (!$arrayGoodsLinkSort[$type][$newCategoryNumber]) ? 1 : $arrayGoodsLinkSort[$type][$newCategoryNumber] + 1;
			$queryGoodsLinkQuery = "insert into " . $relationTableName . " set goodsNo='" . $goodsNo . "', cateCd='" . $newCategoryNumber . "', goodsSort=" . $arrayGoodsLinkSort[$type][$newCategoryNumber] . ", regDt=now();";
			$arrayQuery[] = $queryGoodsLinkQuery;
		}
	}

	return array($arrayQuery, $newCategoryNumber, $arrayGoodsLink);

}

function setAddOption($arrayData) {
	global $db, $goodsNo;
	
	$arrayAddGoodsNo = array();
	// 1차 카테고리
	foreach ($arrayData as $arrayAddGoods) {
		$tmpAddGoodsExist = fetchRow("select count(addGoodsNo) from es_addgoods where goodsNm='" . addslashes($arrayAddGoods[0]) . "' and optionNm='" . addslashes($arrayAddGoods[0]) . "' and goodsPrice='" . $arrayAddGoods[1] . "'");// 동일한 추가 상품 등록 여부 체크

		$newAddGoodsNo = 1000000000;
		if(!$tmpAddGoodsExist){ //같은 카테고리가 없으면 생성
				$tmpMaxAddGoodsNo = fetchRow("select max(addGoodsNo) from es_addgoods");
				
				if ($tmpMaxAddGoodsNo) {
					$newAddGoodsNo = $tmpMaxAddGoodsNo + 1;
				}
				$addGoodsFilePath = './data/add_goods/';
				makeDir($addGoodsFilePath);
				$addGoodsPath	= date('y/m/') . $newAddGoodsNo . '/';
				makeDir($addGoodsFilePath . date('y/'));
				makeDir($addGoodsFilePath . date('y/m/'));
				makeDir($addGoodsFilePath . $addGoodsPath);

				$queryAddGoodsInsert = "insert into es_addgoods set addGoodsNo='" . $newAddGoodsNo . "', applyFl='y', applyType = 'r', goodsNm='" . addslashes($arrayAddGoods[0]) . "', optionNm='" . addslashes($arrayAddGoods[1]) . "', goodsPrice='" . $arrayAddGoods[2] . "', imagePath='" . $addGoodsPath . "', regDt=now()";
				$db->query($queryAddGoodsInsert);
				
		}
		else{ //카테고리가 있으면 할당
			$tmpAddGoodsNo = fetchRow("select addGoodsNo from  es_addgoods where goodsNm='" . addslashes($arrayAddGoods[0]) . "' and optionNm='" . addslashes($arrayAddGoods[0]) . "' and goodsPrice='" . $arrayAddGoods[1] . "'");

			$newAddGoodsNo = $tmpAddGoodsNo;
		}
		
		$arrayAddGoodsNo[] = $newAddGoodsNo;
	}

	return $arrayAddGoodsNo;
}

function makeCodeNumber ($no) {
	$no -= 0;
	while (strlen($no) < 3) $no = "0" . $no;
	return $no;
}

### 문자열 자르기 함수
function strCut($string, $length) {
	if (strlen($string) > $length){
		$length = $length - 2;
		for ($position = $length; $position > 0 && ord($string[$position-1]) >= 127; $position--);
		if (($length - $position) % 2 == 0) $string = substr($string, 0, $length) . "..";
		else $string = substr($string, 0, $length + 1) . "..";
	}
	return $string;
}

function addressMake($oriAddress) {
	$pregText = '^[^[:cntrl:]{1,}]+[시도구동읍면리]+[[:space:]][^[:cntrl:]{1,}]+로([[:space:]][0-9]{1,}-[0-9]{1,}|[[:space:]][0-9]{1,})*([[:space:]]|,[[:space:]])*(([0-9]{1,})*[^[:cntrl:]{1,}]+길[[:space:]])*(([0-9]{1,})*([^[:cntrl:]{1,}])*동[[:space:]]([0-9]{1,})*([^[:cntrl:]{1,}])*호)*([0-9]{1,}-[0-9]{1,}[,][[:space:]]([^[:cntrl:]{1,}]+동[[:space:]])*[^[:cntrl:]{1,}]+호|[0-9]{1,},[[:space:]]([^[:cntrl:]{1,}]+동[[:space:]])*[^[:cntrl:]{1,}]+호)*([0-9]{1,}-[0-9]{1,}[[:space:]]|[0-9]{1,}[[:space:]])*([0-9]{1,}[[:space:]])*([(]([^[:cntrl:]]+)[)])*';

	$subPregText = '(,+[[:space:]]+)(([0-9]{1,})*(([^[:cntrl:]{1,}])*동[[:space:]])*([0-9]{1,})*([^[:cntrl:]{1,}])*호)+';
	$subPregTextSecond = '(,+[[:space:]]+)';

	$address		= '';
	$addressSub		= '';
	if (ereg($pregText, $oriAddress)) {
		preg_match('/' . $pregText . '/i', $oriAddress, $result);
		preg_match('/' . $subPregText . '/i', $result[0], $resultSub);

		$result[0] = preg_replace('/' . $subPregText . '/i', ' ', $result[0]);

		$address		= $result[0];

		$addressSub = preg_replace('/' . $subPregTextSecond . '/i', '', $resultSub[0]) . preg_replace('/' . $pregText . '/i', '$20', $oriAddress);
	}
	else {
		$tmp=explode(' ', $oriAddress);
		if(substr($tmp[2], strlen($tmp[2]) - 2, strlen($tmp[2]) - 1) == "구"){
			$address .= $tmp[0] . ' '  . $tmp[1] . ' ' . $tmp[2] . " " . $tmp[3];
			for($i=4;$i<count($tmp);$i++){
				$addressSub .= $tmp[$i] . ' ';
			}
		}
		else{
			$address.=$tmp[0] . ' ' . $tmp[1] . ' '  . $tmp[2];
			for($i = 3; $i < count($tmp); $i++){
				$addressSub .= $tmp[$i] . ' ';
			}
		}

	}

	return array(trim($address), trim($addressSub));
}
function getTempBrandCode() {
	global $db;
	$arrayTempBrandCode = array();
	$tempBrandCodeResult = $db->query("Select originalSno, godo5BrandCode From tmp_brandcode order by sno");
	while ($tempBrandCodeRow = $db->fetch($tempBrandCodeResult, 1)) {
		$arrayTempBrandCode[$tempBrandCodeRow['originalSno']] = $tempBrandCodeRow['godo5BrandCode'];
	}

	return $arrayTempBrandCode;
}
function changeFlag($valueFlag, $type) {
	if($type=='on') {
		if($valueFlag =='on') {
			$changeFlagResult ='y';
		} else {
			$changeFlagResult ='n';
		}
	}
	else if($type=='o' ) {
		if($valueFlag == 'o') {
			$changeFlagResult ='y';
		} else {
			$changeFlagResult ='n';
		}
	}
	else {
		$changeFlagResult='0';
	}
	return $changeFlagResult;
}
function createBoardTableFunction($boardId) {
	$createQuery = file_get_contents("http://hym1987.godomall.com/main/relocation.php?mode=getGodo5BaordTable");
	$createQuery = urldecode($createQuery);
	$createQuery = preg_replace('/(`es_bd_)([0-9a-z-]+)(`)*/i','$1' . $boardId . '$3', mb_convert_encoding($createQuery, 'EUC-KR', 'UTF-8'));
	

	return $createQuery;

}

//------------------------------------------------------
// - Advice Func- 게시판정렬 변환 힘수
//------------------------------------------------------
/**
 * 10진수를 26진수로
 *
 * @param $dec
 * @return string
 */
//  10진수를 26진수로
function c26dec($dec)
{
	//$key = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
	$key = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$len = strlen($key);
	$tmp = floor($dec/$len);
	$c62 = $key[$dec-($tmp*$len)];
	if ($tmp)
		$c62 = c26dec($tmp).$c62;
	return $c62;
}

// 26진수를 10진수로
function decc26($c62)
{
	//$key = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
	$key = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$len = strlen($key);
	$c62 = strrev($c62);
	$dec = 0;
	for ($i=0; $i <= strlen($c62)-1; $i++){
		$dec += strpos($key,$c62[$i])*pow($len,$i);
	}
	return $dec;
}

function createGroupThread($groupNo , $GroupDepth = '' , $parentGroupDepth = '' , $parentGroupThread = '') {
	$beginReplyChar = 'AA';
	$endReplyChar = 'ZZ';
	$replyNumber = +1;
	//if(!$parentGroupThread) $parentGroupThread = $beginReplyChar;
	//echo "groupNo : " . $groupNo ."<br/>";
	//echo "GroupDepth : " . $GroupDepth ."<br/>";
	//echo "parentGroupDepth : " . $parentGroupDepth ."<br/>";
	//echo "parentGroupThread : " . $parentGroupThread ."<br/>";
	$replyLen = 1;
	//$replyLen2 = strlen($parentGroupThread);
	$groupThreadStr =substr($parentGroupThread, 0, 1);
	$groupThreadStrBack =substr($parentGroupThread, $replyLen, 1);
	//echo "groupThreadStr : " . $groupThreadStr ."<br/>";
	//echo "groupThreadStrBack : " . $groupThreadStrBack ."<br/>";

	//답글 thread 변환- $replyChar
	//답글 thread 결합 - $reply
	if(!$parentGroupThread) { //부모 글에 값이 없을 경우
		$replyChar = $beginReplyChar;
		$reply =  $replyChar;
	} else if ( $parentGroupDepth != $GroupDepth ) { //이전 글과 Depth가 다를 경우
		$replyChar = $beginReplyChar;
		$reply =  $parentGroupThread . $replyChar;
	} else { //부모글에 값이 있고 Depth가 같은 경우
		$replyChar = c26dec(decc26($groupThreadStrBack) + $replyNumber);
		$reply =  $groupThreadStr . $replyChar;
	}

	return $reply;
}

/** 주문 관련 **/

function gd_htmlspecialchars_stripslashes_decode ($jsonString) {
	return gd_json_decode(htmlspecialchars_decode(stripslashes($jsonString)));
}

function gd_htmlspecialchars_addslashes_encode ($jsonString) {
	return gd_json_encode($jsonString);
}

function gdOrderItemOptionEncode ($optionArray) {
	$returnArrayValue = array(); //리턴 옵션 변수
	//일반옵션
	foreach($optionArray as $optionKey => $optionValue ) {
		if($optionValue && $optionKey !=2) {
			if($optionValue) {
				$returnArrayValue[]=gd_htmlspecialchars_addslashes_encode($optionValue);
			}
		}
	}
	//추가옵션
	if($optionArray[2]) {
		foreach($optionArray[2] as $addOptionKey => $addOptionValue) {
			if($addOptionValue) {
				$returnArrayValue[] = gd_htmlspecialchars_addslashes_encode($addOptionValue) ;
			}
		}
	}
	$returnTextValue = "[" . implode(",", $returnArrayValue). "]";
	return $returnTextValue;
}

function ordnoDateTypeSetting($dateValue) {
	$outDateValue = '';
	if ($dateValue) {
		$dateValue = str_replace('-', '', $dateValue);
		$dateValue = str_replace(':', '', $dateValue);
		$dateValue = str_replace(' ', '', $dateValue);

		if (strlen($dateValue) === 10) {
			$outDateValue = date('YmdHis', $dateValue);
		}
		else {
			$outDateValue = date('YmdHis', strtotime($dateValue));
		}
	}
	else {
		$outDateValue = date('YmdHis','0000-00-00 00:00:00');
	}

	return $outDateValue;
}

//------------------------------------------------------
// - Advice - func 주문번호 변환
//------------------------------------------------------
function getNumberFigure($number, $unit, $method)
{
	if (empty($unit)) {
		return $number;
	}

	switch ($method) {
		// 올림
		case 'up':
		case 'ceil':
			// 자리수의 값보다 작은 경우 0 반환
			//if ($number < $unit) {
			//   $number = 0;
			//} else {
			$number = ceil($number / ($unit * 10)) * ($unit * 10);
			//}
			break;

		// 반올림
		case 'half':
		case 'round':
			if ($unit >= 1) {
				$number = round($number, strlen($unit) * -1);
			} else {
				$number = round($number, (strlen($unit) - 3) * 1);
			}
			break;

		// 버림
		case 'down':
		case 'floor':
			$number = floor($number / ($unit * 10)) * ($unit * 10);
			break;
	}

	if ($unit >= 0.1) {
		return (int) $number;
	} else {
		return (float) $number;
	}
}


//------------------------------------------------------
// - Advice - func 결제수단 변환
//------------------------------------------------------
function getGodomall5Ordno($orderDate, $check = '') {
	global $arrayMakeNewOrderNo;
	
	$tmpNo = mt_rand(1000,9999);
	$newOrderNo = date('ymdHis', strtotime($orderDate)) . $tmpNo;

	if ($arrayMakeNewOrderNo[(string)$newOrderNo]) {
		$newOrderNo = getGodomall5Ordno($orderDate, '1');
	}
	
	return $newOrderNo;
	/*
	// 0 ~ 999 마이크로초 중 랜덤으로 sleep 처리 (동일 시간에 들어온 경우 중복을 막기 위해서.)
	usleep(mt_rand(0, 999));
	// 0 ~ 99 마이크로초 중 랜덤으로 sleep 처리 (첫번째 sleep 이 또 동일한 경우 중복을 막기 위해서.)
	usleep(mt_rand(0, 99));
	// microtime() 함수의 마이크로 초만 사용
	list($usec) = explode(' ', microtime());
	// 마이크로초을 4자리 정수로 만듬 (마이크로초 뒤 2자리는 거의 0이 나오므로 8자리가 아닌 4자리만 사용함 - 나머지 2자리도 짜름... 너무 길어서.)
	$tmpNo = sprintf('%04d', round($usec * 999));
	*/
	
	/*
	$orderNum_Query = "SELECT godo5OrderNo FROM tmp_orderno WHERE godo5OrderNo = '".$newOrderNo."'";
	$result= $db->query($orderNum_Query) or die(mysql_error().$orderNum_Query);
	$total = mysqli_num_rows($result);
	if ($total) {
		$newOrderNo = getGodomall5Ordno($orderDate, '1');
	}
	return $newOrderNo;
	*/
}
//------------------------------------------------------
// - Advice - func 결제수단 변환
//------------------------------------------------------
function getSettlekind($settlekind)
{
	$r_settlekind	= array(
		'a'	=> 'gb',		//'무통장입금',
		'c'	=> 'pc',		//'신용카드',
		'o'	=> 'pb',		//'실시간 계좌이체',
		'v'	=> 'pv',		//'가상계좌',
		'd'	=> 'fp',		//'전액 할인',
		'h'	=> 'ph',		//'휴대폰결제',
		'p'	=> 'fp',		//'포인트결제',
		'u'	=> 'pc'		//'중국신용카드결제'
	);

	// 1차 결제 방법
	$strKind	= $r_settlekind[$settlekind];
	return $strKind;
}
//------------------------------------------------------
// - Advice - 주문 상태 변환
//------------------------------------------------------
function getOrderStatus($orderStep, $orderStep2, $cyn, $dyn) {

	$statusReturn = array();
	$orderStatusArray= array($orderStep, $orderStep2, $cyn, $dyn);
	//배열 키 유니크를 위해 같은 주문상태코드의 경우 배열 키 순으로 _증가값 임의로 붙임
	$r_stepi	= array(
		'o1'		=>	array(0,0,'n','n'),		// 주문접수
		'c4_1'		=>	array(0,40,'n','n'),		// 취소요청
		'c4_2'		=>	array(0,41,'y','n'),		//취소접수
		'c4_3'		=>	array(0,42,'y','n'),		//취소진행
		'c3'			=>	array(0,44,'n','n'),		// 취소완료
		'f1'			=>	array(0,50,'n','n'),		// 결제시도
		'f3'			=>	array(0,54,'n','n'),		// 결제실패
		'p1'		=>	array(1,0,'y','n'),		// 입금확인
		'r1_1'		=>	array(1,40,'y','n'),		// 환불요청
		'r1_2'		=>	array(1,41,'y','n'),		// 환불접수
		'r3_1'		=>	array(1,44,'r','n'),		// 환불완료
		'g1'		=>	array(2,0,'y','n'),		// 배송준비중
		'r1_3'		=>	array(2,40,'y','n'),		// 환불요청
		'r1_4'		=>	array(2,41,'y','n'),		// 환불접수
		'r3_2'		=>	array(2,44,'r','n'),		// 환불완료
		'd1'		=>	array(3,0,'y','y'),		// 배송중
		'b1_1'		=>	array(3,40,'y','y'),		// 반품요청
		'b1_2'		=>	array(3,41,'y','y'),		// 반품접수
		'r1_5'		=>	array(3,42,'y','y'),		// 환불접수
		'r3_3'		=>	array(3,44,'r','n'),		// 환불완료
		'd2'		=>	array(4,0,'y','y'),		// 배송완료
		'b1'		=>	array(4,40,'y','y'),		// 반품요청
		'r1_6'		=>	array(4,41,'y','y'),		// 환불접수
		'r2'			=>	array(4,44,'r','y'),		// 환불완료
	);
	$statusReturn = array_keys($r_stepi, $orderStatusArray);
	if($statusReturn[0] =='') {
		$statusReturn[0] ='o1';
	}
	unset($orderStatusArray);
	$statusReturn[0] = substr($statusReturn[0],0,2);
	return $statusReturn[0];
}


//------------------------------------------------------
// - Advice - 주문 상품 상태 변환
//------------------------------------------------------
function getOrderItemStatus($orderStep) {
	$r_istep		= array(
		'0'		=> 'o1',
		'1'		=> 'p1',
		'2'		=> 'g1',
		'3'		=> 'd1',
		'4'		=> 'd2',
		'41'	=> 'c4',
		'42'	=> 'c4',
		'44'	=> 'c4',
		'50'	=> 'f1',
		'51'	=> 'f3',
		'54'	=> 'f3',
	);
	$step	= $r_istep[$orderStep];
	if($step =='') {
		$step = 'o1';
	}
	return $step;
}

//------------------------------------------------------
// - Advice - 택배코드 변환
//------------------------------------------------------
function getDeliveryCodeStatus($enamooDeliveryCode) {
	$deliveryCode		= array(
		'1'		=>'2',		//KGB택배
		'8'		=>'3',		//KG옐로우캡
		'21'	=>'4',		//kg로지스 ->SC로지스
		'12'	=>'5',		//한진택배
		'39'	=>'6',		//경동택배
		'33'	=>'7',		//대신택배
		'4'		=>'8',		//대한통운택배
		'24'	=>'9',		//자체배송->동부택배
		'5'		=>'10',	//로젠택배
		'30'	=>'11',	//우체국EMS
		'9'		=>'12',	//우체국택배
		'100'	=>'12',	//우체국택배
		'32'	=>'13',	//이노지스택배
		'22'	=>'14',	//일양택배
		'19'	=>'15',	//천일택배
		'20'	=>'16',	//하나로택배
		'12'	=>'17',	//한진택배
		'13'	=>'18',	//현대택배
	);
	$delCode	= $deliveryCode[$enamooDeliveryCode];
	if($delCode =='') {
		$delCode = '';
	}
	return $delCode;
}
//------------------------------------------------------
// - Advice - 주문로그 코드 변환
//------------------------------------------------------
function getOrderLogCodeStatus($enamooOrderLogCode) {
	$orderLogCode		= array(
		'주문접수' =>	'o1',		// 주문접수
		'취소요청' =>	'c4',		// 취소요청
		'취소접수' =>	'c4',		//취소접수
		'취소진행' =>	'c4',		//취소진행
		'취소완료' =>	'c3',		// 취소완료
		'결제시도' =>	'f1',		// 결제시도
		'결제실패' =>	'f3',		// 결제실패
		'입금확인' =>	'p1',		// 입금확인
		'환불요청' =>	'r1',		// 환불요청
		'환불접수' =>	'r1',		// 환불접수
		'환불완료' =>	'r3',		// 환불완료
		'배송준비중' =>	'g1',		// 배송준비중
		'환불완료' =>	'r3',		// 환불완료
		'배송중' =>	'd1',		// 배송중
		'반품요청' =>	'b1',		// 반품요청
		'반품접수' =>	'b1',		// 반품접수
		'반품완료' =>	'b4',		// 반품접수
		'환불완료' =>	'r3',		// 환불완료
		'배송완료' =>	'd2',		// 배송완료
	);
	$orderLogCodeValue	=$enamooOrderLogCode . "(" . $orderLogCode[$enamooOrderLogCode]. ")";
	if($orderLogCodeValue =='') {
		$orderLogCodeValue = '';
	}
	return $orderLogCodeValue;
}

//------------------------------------------------------
// - Advice - CSV data 수
//------------------------------------------------------
function lineDataNum($data) {
	$maxLineArray = array();
	$lineData = @fopen($data.".csv", 'r');
	while (list ($lineNum, $line) = @fgetcsv($lineData, 135000, ',')) {
		$maxLineArray[] =$lineNum;
	}
	if($maxLineArray) {
	$lineRow = count($maxLineArray)-1;

	return "<font color=blue>" . $lineRow . '개 </font>';
	} else {
		return "<font color=red>".  $data . ".csv 파일 또는 데이터가 없습니다.</font>";
	}
}

function editorCopy ($oriContents, $arrayEditorFilePath, $changeFilePath, $addFileName) {
	global $arrayEditorFileCopyList, $editorFileCopyCheck, $localCopy, $newEditorFilePath;
	$oriContents = str_replace('SRC', 'src', $oriContents);

	$contentsCopy = $oriContents;
				
	preg_match_all("/(src)=(\"|'|)[^\"'>]+/i", $contentsCopy, $media); // 이미지 태그 추출
			
	unset($contentsCopy);

	$contentsCopy = preg_replace("/(src)(\"|'|=\"|='|=)(.*)/i","$3",$media[0]); // 이미지 파일 추출
	$editorImgCnt = 0;
	// 등록 이미지 갯수 만큼 루프
	foreach ($contentsCopy as $copyUrl) {
		$copyUrl = trim($copyUrl);
		foreach ($arrayEditorFilePath as $arrayOldEditorFilePath) {
			if (ereg($arrayOldEditorFilePath[0], $copyUrl) || ereg($arrayOldEditorFilePath[1], $copyUrl) || (substr($copyUrl, 0, 1) == '/' && substr($copyUrl, 0, 2) != '//')) {
				$oriCopyUrl = $copyUrl;
				$newCopyUrl = '';
				$copyUrl = str_replace($arrayOldEditorFilePath[0], '', $copyUrl);
				$copyUrl = str_replace($arrayOldEditorFilePath[1], '', $copyUrl);
				$copyUrl = preg_replace('/^' . str_replace('/', '\/', $arrayOldEditorFilePath[4]) . '/', '', $copyUrl);

				//echo $oldFilePath.'<br/>';
				$arrayFilePath = explode('/', $copyUrl);
				$filePath = $newEditorFilePath;
				$fileName = $arrayFilePath[count($arrayFilePath)-1];
				for ($i = 0; $i < count($arrayFilePath) - 1; $i++){
					$filePath .= '/' . trim($arrayFilePath[$i]);
					makeDir($filePath);
				}
				//echo $copyUrl . ' => ' . str_replace($fileName, $arrayEditorFileCopyList[$copyUrl], $copyUrl);
				if (!$arrayEditorFileCopyList[$copyUrl]) {
					$newEditorFileName = fileRename($fileName, $addFileName . $editorImgCnt);
					$newCopyUrl = str_replace($arrayOldEditorFilePath[0], $changeFilePath, $oriCopyUrl);
					$newCopyUrl = str_replace($arrayOldEditorFilePath[1], $changeFilePath, $newCopyUrl);
					preg_match('/' . str_replace('/', '\/', $changeFilePath) . '/', $newCopyUrl, $testResult);
					if (empty($testResult)) {
						$newCopyUrl = preg_replace('/^\//', $changeFilePath, $newCopyUrl);
					}
					
					$newCopyUrl = str_replace($fileName, $newEditorFileName, $newCopyUrl);

					$arrayEditorFileCopyList[$copyUrl] = $newCopyUrl;

					if (!trimPostRequest('file_rename_yn')) {
						$changeUrl = $filePath . '/' . $newEditorFileName;
						if (trimPostRequest('localCopy') == 'Y') {
							$oldEditorPath = './oldSite' . $arrayOldEditorFilePath[4] . $copyUrl;
						}
						else {
							$oldEditorPath = $arrayOldEditorFilePath[0] . $copyUrl;
							//$oldEditorPath = mb_convert_encoding($oldEditorPath, 'UTF-8', 'EUC-KR');
							$oldEditorPath = str_replace('+', '%20', str_replace('%3A', ':', str_replace('%2F', '/', urlencode($oldEditorPath))));
							//$oldEditorPath = iconv('utf-8', 'CP949//TRANSLIT', $oldEditorPath);

							//$oldEditorPath = file_get_contents('http://love1004.co.kr/relocation_cafe_godo5/Hangul/Hangul.php?Hangul=' . iconv('euc-kr', 'utf-8', $oldEditorPath));

							//echo ;

							//exit;
						}
						
						// 파일복사 실행
						fileCopy($oldEditorPath, $changeUrl, $editorFileCopyCheck);	
					}

					$oriContents = str_replace($oriCopyUrl, $newCopyUrl, $oriContents);
					$editorImgCnt++;
				}
				else {
					$oriContents = str_replace($oriCopyUrl, $arrayEditorFileCopyList[$copyUrl], $oriContents);
				}
			}
		}
	}

	return $oriContents;
}
?>