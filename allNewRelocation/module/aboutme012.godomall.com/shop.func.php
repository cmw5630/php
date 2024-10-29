<?php

/*
* �Ϲ� ������ Ŭ���� ȣ��
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
//- Advice - telCreate �Լ�
//- ��ȭ ��ȣ ����� ����� �ݴϴ�.
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
//- Advice - flagChange �Լ�
//- ������ ���� �� �ַ�ǿ� �°� ��ȯ
//-----------------------------------------------------------
function flagChange($outType, $inParameter) {
	$arrayOldTrueFlag = array('y', 'Y', 'T', 't', 'TRUE', '1', '��ȥ', '��', '�������', '����', '���', '������', '����', '��', '����', 'm', 'M', '3', '��', '���', 'S', 's', 'YES', 'Yes', 'yes', '����', '���', 'secret', '�ΰ��� ��ǰ', '�Ǹ� ��', '�Ǹ�');
	$arrayOldFalseFlag = array('n', 'N', 'F', 'f', 'FALSE', '0', '��ȥ', '��', '���Űź�', '�ź�', '���ž���', '����', '��', '����', 'w', 'W', '2', '4', '��', '����', 'L', 'l', 'NO', 'No', 'no', '�鼼��ǰ', '�Ǹ� ����');

	$arrayNewFlag = array(
		'yn'	=> array('true' => 'y', 'false' => 'n'),
		'10'	=> array('true' => '1', 'false' => '0'),
		'deli'	=> array('true' => '����', 'false' => '�ĺ�'),
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
//- Advice - �⺻������ ���÷��̽�
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
//- Advice - ���� ��ȣ ���� �Լ�
//-----------------------------------------------------------
function zipCodeCreate ($inParam) {
	$outParam = '';
	if (strlen($inParam) === 5) {
		$outParam = $inParam;
	}
	else {
		$outParam = substr($inParam, 0, 3) . '-' . substr($inParam, 3, 3); // �����ȣ ����
	}

	return $outParam;
}

// ���� ���� �Լ�
function fileCopy($oldFile, $newFile, $msgFlag = false) {
	$msg = '';
	$result = 1;
	if (!file_exists($newFile)) {
		if (@copy($oldFile, $newFile)) {
			chmod($newFile, 0707);
			$msg = '<div style="color:blue;">���� ���� : ' . $oldFile . ' => ' . $newFile . '</div>';
		}
		else {
			$msg = '<div style="color:red;font-weight:bold;">���� ���� : ' . $oldFile . ' => ' . $newFile . '</div>';
			$result = 0;
		}
	}
	else {
		$msg = '<div style="color:blue;">���� �� ���� ���� : ' . $newFile . '</div>';
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
//- Advice - commentSetting �Լ�
//- �ڻ� �Խñ� ��� ���Ŀ� �°� �ڸ�Ʈ �����͸� ��ȯ�Ͽ� ��ȯ
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
//- Advice - extraInfoSet �Լ�
//- ��ǰ ���� ��� ��� ������ ���� �Լ�
//- �Ķ����(����, ��, ��ȣ)
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
		// 1�� ī�װ�
		$varCategoryName = addslashes($arrayData[0]);
		$tmpCatnmExist = fetchRow("select count(*) from " . $tableName . " where " . $nameField . "='" . $varCategoryName . "' and length(" . $codeField . ")=3");//�� ��ǰ�� 1�� ī�װ��� �����ϴ��� �˻�
		if(!$tmpCatnmExist){ //���� ī�װ��� ������ ����
				$tmpMaxCatgoryCode = fetchRow("select " . $codeField . " from " . $tableName . " where length(" . $codeField . ")=3 order by " . $codeField . " desc limit 1");//���� ū ī�װ��� +1

				$varCategoryCode = makeCodeNumber($tmpMaxCatgoryCode + 1);
				$qrCategoryInsert = "insert into " . $tableName . " set " . $nameField . "='" . $varCategoryName . "', " . $codeField . "='" . $varCategoryCode . "'";
				$db->query($qrCategoryInsert);
		}
		else{ //ī�װ��� ������ �Ҵ�
			$tmpCategoryCode = fetchRow("select " . $codeField . " from " . $tableName . " where " . $nameField . "='" . $varCategoryName . "' and length(" . $codeField . ")=3");
			$varCategoryCode = $tmpCategoryCode;
		}


		// ����ī�װ��� ���
		for($i=1;$i<count($arrayData);$i++){
			$arrSubCategoryName[] = $arrayData[$i];
		}

		// ����ī�װ� ����
		foreach($arrSubCategoryName as $key => $varSubCategoryName){
			if ($varSubCategoryName) {
				$varSubCategoryName = addslashes($varSubCategoryName);
				$tmpSubCatnmExist = fetchRow("select count(*) from " . $tableName . " where " . $nameField . "='" . $varSubCategoryName . "' and length(" . $codeField . ")=" . (($key + 2) * 3) . " and " . $codeField . " like '" . $varCategoryCode . "%'");
				if(!$tmpSubCatnmExist){//���� ī�װ��� ������ ����
					$tmpMaxSubCatgoryCode = fetchRow("select substring(" . $codeField . "," . ((($key + 1) * 3) + 1) . ",3) from " . $tableName . " where length(" . $codeField . ")=" . (($key+2)*3) . " and " . $codeField . " like '" . $varCategoryCode . "%' order by " . $codeField . " desc limit 1");//���� ū ī�װ��� +1
					$varCategoryCode_sub = makeCodeNumber($tmpMaxSubCatgoryCode + 1);
					$varCategoryCode .= $varCategoryCode_sub;
					$qrCategoryInsert_sub = "insert into " . $tableName . " set " . $nameField . "='" . $varSubCategoryName . "', " . $codeField . "='" . $varCategoryCode . "'";
					$db->query($qrCategoryInsert_sub);
				}
				else{//������ �Ҵ�
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
	// 1�� ī�װ�
	foreach ($arrayData as $arrayAddGoods) {
		$tmpAddGoodsExist = fetchRow("select count(addGoodsNo) from es_addgoods where goodsNm='" . addslashes($arrayAddGoods[0]) . "' and optionNm='" . addslashes($arrayAddGoods[0]) . "' and goodsPrice='" . $arrayAddGoods[1] . "'");// ������ �߰� ��ǰ ��� ���� üũ

		$newAddGoodsNo = 1000000000;
		if(!$tmpAddGoodsExist){ //���� ī�װ��� ������ ����
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
		else{ //ī�װ��� ������ �Ҵ�
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

### ���ڿ� �ڸ��� �Լ�
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
	$pregText = '^[^[:cntrl:]{1,}]+[�õ��������鸮]+[[:space:]][^[:cntrl:]{1,}]+��([[:space:]][0-9]{1,}-[0-9]{1,}|[[:space:]][0-9]{1,})*([[:space:]]|,[[:space:]])*(([0-9]{1,})*[^[:cntrl:]{1,}]+��[[:space:]])*(([0-9]{1,})*([^[:cntrl:]{1,}])*��[[:space:]]([0-9]{1,})*([^[:cntrl:]{1,}])*ȣ)*([0-9]{1,}-[0-9]{1,}[,][[:space:]]([^[:cntrl:]{1,}]+��[[:space:]])*[^[:cntrl:]{1,}]+ȣ|[0-9]{1,},[[:space:]]([^[:cntrl:]{1,}]+��[[:space:]])*[^[:cntrl:]{1,}]+ȣ)*([0-9]{1,}-[0-9]{1,}[[:space:]]|[0-9]{1,}[[:space:]])*([0-9]{1,}[[:space:]])*([(]([^[:cntrl:]]+)[)])*';

	$subPregText = '(,+[[:space:]]+)(([0-9]{1,})*(([^[:cntrl:]{1,}])*��[[:space:]])*([0-9]{1,})*([^[:cntrl:]{1,}])*ȣ)+';
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
		if(substr($tmp[2], strlen($tmp[2]) - 2, strlen($tmp[2]) - 1) == "��"){
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
// - Advice Func- �Խ������� ��ȯ ����
//------------------------------------------------------
/**
 * 10������ 26������
 *
 * @param $dec
 * @return string
 */
//  10������ 26������
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

// 26������ 10������
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

	//��� thread ��ȯ- $replyChar
	//��� thread ���� - $reply
	if(!$parentGroupThread) { //�θ� �ۿ� ���� ���� ���
		$replyChar = $beginReplyChar;
		$reply =  $replyChar;
	} else if ( $parentGroupDepth != $GroupDepth ) { //���� �۰� Depth�� �ٸ� ���
		$replyChar = $beginReplyChar;
		$reply =  $parentGroupThread . $replyChar;
	} else { //�θ�ۿ� ���� �ְ� Depth�� ���� ���
		$replyChar = c26dec(decc26($groupThreadStrBack) + $replyNumber);
		$reply =  $groupThreadStr . $replyChar;
	}

	return $reply;
}

/** �ֹ� ���� **/

function gd_htmlspecialchars_stripslashes_decode ($jsonString) {
	return gd_json_decode(htmlspecialchars_decode(stripslashes($jsonString)));
}

function gd_htmlspecialchars_addslashes_encode ($jsonString) {
	return gd_json_encode($jsonString);
}

function gdOrderItemOptionEncode ($optionArray) {
	$returnArrayValue = array(); //���� �ɼ� ����
	//�Ϲݿɼ�
	foreach($optionArray as $optionKey => $optionValue ) {
		if($optionValue && $optionKey !=2) {
			if($optionValue) {
				$returnArrayValue[]=gd_htmlspecialchars_addslashes_encode($optionValue);
			}
		}
	}
	//�߰��ɼ�
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
// - Advice - func �ֹ���ȣ ��ȯ
//------------------------------------------------------
function getNumberFigure($number, $unit, $method)
{
	if (empty($unit)) {
		return $number;
	}

	switch ($method) {
		// �ø�
		case 'up':
		case 'ceil':
			// �ڸ����� ������ ���� ��� 0 ��ȯ
			//if ($number < $unit) {
			//   $number = 0;
			//} else {
			$number = ceil($number / ($unit * 10)) * ($unit * 10);
			//}
			break;

		// �ݿø�
		case 'half':
		case 'round':
			if ($unit >= 1) {
				$number = round($number, strlen($unit) * -1);
			} else {
				$number = round($number, (strlen($unit) - 3) * 1);
			}
			break;

		// ����
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
// - Advice - func �������� ��ȯ
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
	// 0 ~ 999 ����ũ���� �� �������� sleep ó�� (���� �ð��� ���� ��� �ߺ��� ���� ���ؼ�.)
	usleep(mt_rand(0, 999));
	// 0 ~ 99 ����ũ���� �� �������� sleep ó�� (ù��° sleep �� �� ������ ��� �ߺ��� ���� ���ؼ�.)
	usleep(mt_rand(0, 99));
	// microtime() �Լ��� ����ũ�� �ʸ� ���
	list($usec) = explode(' ', microtime());
	// ����ũ������ 4�ڸ� ������ ���� (����ũ���� �� 2�ڸ��� ���� 0�� �����Ƿ� 8�ڸ��� �ƴ� 4�ڸ��� ����� - ������ 2�ڸ��� ¥��... �ʹ� ��.)
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
// - Advice - func �������� ��ȯ
//------------------------------------------------------
function getSettlekind($settlekind)
{
	$r_settlekind	= array(
		'a'	=> 'gb',		//'�������Ա�',
		'c'	=> 'pc',		//'�ſ�ī��',
		'o'	=> 'pb',		//'�ǽð� ������ü',
		'v'	=> 'pv',		//'�������',
		'd'	=> 'fp',		//'���� ����',
		'h'	=> 'ph',		//'�޴�������',
		'p'	=> 'fp',		//'����Ʈ����',
		'u'	=> 'pc'		//'�߱��ſ�ī�����'
	);

	// 1�� ���� ���
	$strKind	= $r_settlekind[$settlekind];
	return $strKind;
}
//------------------------------------------------------
// - Advice - �ֹ� ���� ��ȯ
//------------------------------------------------------
function getOrderStatus($orderStep, $orderStep2, $cyn, $dyn) {

	$statusReturn = array();
	$orderStatusArray= array($orderStep, $orderStep2, $cyn, $dyn);
	//�迭 Ű ����ũ�� ���� ���� �ֹ������ڵ��� ��� �迭 Ű ������ _������ ���Ƿ� ����
	$r_stepi	= array(
		'o1'		=>	array(0,0,'n','n'),		// �ֹ�����
		'c4_1'		=>	array(0,40,'n','n'),		// ��ҿ�û
		'c4_2'		=>	array(0,41,'y','n'),		//�������
		'c4_3'		=>	array(0,42,'y','n'),		//�������
		'c3'			=>	array(0,44,'n','n'),		// ��ҿϷ�
		'f1'			=>	array(0,50,'n','n'),		// �����õ�
		'f3'			=>	array(0,54,'n','n'),		// ��������
		'p1'		=>	array(1,0,'y','n'),		// �Ա�Ȯ��
		'r1_1'		=>	array(1,40,'y','n'),		// ȯ�ҿ�û
		'r1_2'		=>	array(1,41,'y','n'),		// ȯ������
		'r3_1'		=>	array(1,44,'r','n'),		// ȯ�ҿϷ�
		'g1'		=>	array(2,0,'y','n'),		// ����غ���
		'r1_3'		=>	array(2,40,'y','n'),		// ȯ�ҿ�û
		'r1_4'		=>	array(2,41,'y','n'),		// ȯ������
		'r3_2'		=>	array(2,44,'r','n'),		// ȯ�ҿϷ�
		'd1'		=>	array(3,0,'y','y'),		// �����
		'b1_1'		=>	array(3,40,'y','y'),		// ��ǰ��û
		'b1_2'		=>	array(3,41,'y','y'),		// ��ǰ����
		'r1_5'		=>	array(3,42,'y','y'),		// ȯ������
		'r3_3'		=>	array(3,44,'r','n'),		// ȯ�ҿϷ�
		'd2'		=>	array(4,0,'y','y'),		// ��ۿϷ�
		'b1'		=>	array(4,40,'y','y'),		// ��ǰ��û
		'r1_6'		=>	array(4,41,'y','y'),		// ȯ������
		'r2'			=>	array(4,44,'r','y'),		// ȯ�ҿϷ�
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
// - Advice - �ֹ� ��ǰ ���� ��ȯ
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
// - Advice - �ù��ڵ� ��ȯ
//------------------------------------------------------
function getDeliveryCodeStatus($enamooDeliveryCode) {
	$deliveryCode		= array(
		'1'		=>'2',		//KGB�ù�
		'8'		=>'3',		//KG���ο�ĸ
		'21'	=>'4',		//kg������ ->SC������
		'12'	=>'5',		//�����ù�
		'39'	=>'6',		//�浿�ù�
		'33'	=>'7',		//����ù�
		'4'		=>'8',		//��������ù�
		'24'	=>'9',		//��ü���->�����ù�
		'5'		=>'10',	//�����ù�
		'30'	=>'11',	//��ü��EMS
		'9'		=>'12',	//��ü���ù�
		'100'	=>'12',	//��ü���ù�
		'32'	=>'13',	//�̳������ù�
		'22'	=>'14',	//�Ͼ��ù�
		'19'	=>'15',	//õ���ù�
		'20'	=>'16',	//�ϳ����ù�
		'12'	=>'17',	//�����ù�
		'13'	=>'18',	//�����ù�
	);
	$delCode	= $deliveryCode[$enamooDeliveryCode];
	if($delCode =='') {
		$delCode = '';
	}
	return $delCode;
}
//------------------------------------------------------
// - Advice - �ֹ��α� �ڵ� ��ȯ
//------------------------------------------------------
function getOrderLogCodeStatus($enamooOrderLogCode) {
	$orderLogCode		= array(
		'�ֹ�����' =>	'o1',		// �ֹ�����
		'��ҿ�û' =>	'c4',		// ��ҿ�û
		'�������' =>	'c4',		//�������
		'�������' =>	'c4',		//�������
		'��ҿϷ�' =>	'c3',		// ��ҿϷ�
		'�����õ�' =>	'f1',		// �����õ�
		'��������' =>	'f3',		// ��������
		'�Ա�Ȯ��' =>	'p1',		// �Ա�Ȯ��
		'ȯ�ҿ�û' =>	'r1',		// ȯ�ҿ�û
		'ȯ������' =>	'r1',		// ȯ������
		'ȯ�ҿϷ�' =>	'r3',		// ȯ�ҿϷ�
		'����غ���' =>	'g1',		// ����غ���
		'ȯ�ҿϷ�' =>	'r3',		// ȯ�ҿϷ�
		'�����' =>	'd1',		// �����
		'��ǰ��û' =>	'b1',		// ��ǰ��û
		'��ǰ����' =>	'b1',		// ��ǰ����
		'��ǰ�Ϸ�' =>	'b4',		// ��ǰ����
		'ȯ�ҿϷ�' =>	'r3',		// ȯ�ҿϷ�
		'��ۿϷ�' =>	'd2',		// ��ۿϷ�
	);
	$orderLogCodeValue	=$enamooOrderLogCode . "(" . $orderLogCode[$enamooOrderLogCode]. ")";
	if($orderLogCodeValue =='') {
		$orderLogCodeValue = '';
	}
	return $orderLogCodeValue;
}

//------------------------------------------------------
// - Advice - CSV data ��
//------------------------------------------------------
function lineDataNum($data) {
	$maxLineArray = array();
	$lineData = @fopen($data.".csv", 'r');
	while (list ($lineNum, $line) = @fgetcsv($lineData, 135000, ',')) {
		$maxLineArray[] =$lineNum;
	}
	if($maxLineArray) {
	$lineRow = count($maxLineArray)-1;

	return "<font color=blue>" . $lineRow . '�� </font>';
	} else {
		return "<font color=red>".  $data . ".csv ���� �Ǵ� �����Ͱ� �����ϴ�.</font>";
	}
}

function editorCopy ($oriContents, $arrayEditorFilePath, $changeFilePath, $addFileName) {
	global $arrayEditorFileCopyList, $editorFileCopyCheck, $localCopy, $newEditorFilePath;
	$oriContents = str_replace('SRC', 'src', $oriContents);

	$contentsCopy = $oriContents;
				
	preg_match_all("/(src)=(\"|'|)[^\"'>]+/i", $contentsCopy, $media); // �̹��� �±� ����
			
	unset($contentsCopy);

	$contentsCopy = preg_replace("/(src)(\"|'|=\"|='|=)(.*)/i","$3",$media[0]); // �̹��� ���� ����
	$editorImgCnt = 0;
	// ��� �̹��� ���� ��ŭ ����
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
						
						// ���Ϻ��� ����
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