<?php
	echo '<div>拙穣 獣拙 獣娃 : ' . date('Y-m-d H:i:s') . '</div>';
	include '../../inc/header.php';
	setlocale(LC_CTYPE, 'ko_KR.eucKR');

if($mode == "start") {
	$relocationData = $_POST['relocationData'];
	$url = $_POST['afterUrl'];
	
	$insertMode = (!trimPostRequest('insertMode')) ? 'y' : trimPostRequest('insertMode');
	
	$insertSet					= new insertSet('es_order', $insertMode);
	$orderInfoSet				= new insertSet('es_orderInfo', $insertMode);
	$orderDeliverySet			= new insertSet('es_orderDelivery', $insertMode);
	$orderGoodsSet				= new insertSet('es_orderGoods', $insertMode);
	$memberMileageSet			= new insertSet('es_memberMileage', $insertMode, 4999);
	$adminMemoSet				= new insertSet('es_adminOrderGoodsMemo', $insertMode);
	$tmpOrderNoSet				= new insertSet('tmp_orderno', $insertMode, 4999);
	
	$orderDataCount				= 1;
	$orderInfoCount				= 1;
	$orderDeliveryCount			= 1;
	$orderGoodsCount			= 1;
	$memberMileageCount			= 1;
	$adminMemoCount				= 1;
	$tmpOrderNoSetCount			= 1;
	//------------------------------------------------------
	// - Advice - 菰銅 汀軒 窒径 穿遂 琶球葵 実特
	//------------------------------------------------------
	$insertSet->arrayFieldName = array(
		'orderNo',
		'memNo',
		'orderStatus',
		'orderIp',
		'orderChannelFl',
		'orderTypeFl',
		'orderEmail',
		'orderGoodsNm',
		'orderGoodsCnt',
		'SettlePrice',
		'useMileage',
		'totalGoodsPrice',
		'totalDeliveryCharge',
		'totalMemberDcPrice',
		'totalCouponOrderDcPrice',
		'totalMileage',
		'SettleKind',
		'bankAccount',
		'bankSender',
		'adminMemo',
		'pgAppNo',
		'pgSettleNm',
		'pgSettleCd',
		'pgFailReason',
		'pgCancelFl',
		'escrowSendNo',
		'paymentDt',
		'regDt',
		'modDt',
	);

	$orderInfoSet->arrayFieldName = array(
		'orderNo',
		'orderName',
		'orderEmail',
		'orderPhone',
		'orderCellPhone',
		'orderZipcode',
		'orderZonecode',
		'orderAddress',
		'orderAddressSub',
		'receiverName',
		'receiverPhone',
		'receiverCellPhone',
		'receiverZipcode',
		'receiverZonecode',
		'receiverAddress',
		'receiverAddressSub',
		'orderMemo',
		'regDt',
		'modDt',
	);

	$orderDeliverySet->arrayFieldName = array(
		'orderNo',
		'deliverySno',
		'deliveryCharge',
		'deliveryFixFl',
		'regDt',
		'modDt',
	);

	$orderGoodsSet->arrayFieldName = array(
		'orderNo',
		'orderCd',
		'orderGroupCd',
		'userHandleSno',
		'handleSno',
		'orderStatus',
		'orderDeliverySno',
		'invoiceCompanySno',
		'invoiceNo',
		'commission',
		'goodsNo',
		'goodsCd',
		'goodsModelNo',
		'goodsNm',
		'goodsWeight',
		'goodsCnt',
		'goodsPrice',
		'paymentDt',
		'optionTextPrice',
		'fixedPrice',
		'costPrice',
		'goodsDcPrice',
		'memberDcPrice',
		'goodsMileage',
		'optionInfo',
		'optionTextInfo',
		'brandCd',
		'makerNm',
		'originNm',
		'deliveryDt',
		'deliveryCompleteDt',
		'cancelDt',
		'goodsDeliveryCollectPrice',
		'regDt',
		'modDt',
	);

	$memberMileageSet->arrayFieldName = array(
		'memNo',
		'managerId',
		'beforeMileage',
		'afterMileage',
		'mileage',
		'reasonCd',
		'handleCd',
		'contents',
		'deleteFl',
		'regIp',
		'regDt',
	);

	$adminMemoSet->arrayFieldName = array(
		'managerSno',
		'orderNo',
		'orderGoodsSno',
		'type',
		'memoCd',
		'content',
		'delFl',
		'deleter',
		'regDt',
	);

	$tmpOrderNoSet->arrayFieldName = array(
		'originalOrderNo',
		'godo5OrderNo',
		'orderGoodsRegDt',
		'regDt',
	);
	//------------------------------------------------------
	/*
	$optionInsertSet		= new insertSet('es_goodsOption', $insertMode);
	$goodsImageInsertSet	= new insertSet('es_goodsImage', $insertMode);
	$extraInfoInsertSet		= new insertSet('es_goodsAddInfo', $insertMode);
	*/

	$arrayDataQuery = array();
	//--- 爽庚 砺戚鷺 段奄鉢

	$db->query('DROP TABLE IF EXISTS `tmp_orderno`;');
	$db->query("CREATE TABLE IF NOT EXISTS `tmp_orderno` (
						`sno` int(10) NOT NULL AUTO_INCREMENT,
						`originalOrderNo` char(200) NOT NULL,
						`godo5OrderNo` char(200) NOT NULL,
						`orderGoodsRegDt` datetime NOT NULL,
						`regDt` datetime NOT NULL,
						PRIMARY KEY (`sno`),
						KEY `originalOrderNo` (`originalOrderNo`),
						KEY `godo5OrderNo` (`godo5OrderNo`)
					) ENGINE=MyISAM DEFAULT CHARSet=euckr AUTO_INCREMENT=1;");
			
	//------------------------------------------------------
	// - Advice - 績獣 痕発 砺戚鷺 爽庚腰硲 蓄窒
	//------------------------------------------------------
	$arrayGetOrderTempNo = array(
		'mode'		=> 'getRelocationOrderTempNo',
	);
	$getOrderTempNoReault = xmlUrlRequest('http://' . $url . '/main/relocation.php', $arrayGetOrderTempNo);
	$arrayGodo5OrderNo		= array();
	$arrayMakeNewOrderNo	= array();
	//$arrayOrderGoodsRegDt	= array();
	if ($getOrderTempNoReault->tempOrderNoResult) {
		foreach ($getOrderTempNoReault->godo5OrderNo as $godo5OrderNo) {
			$arrayGodo5OrderNo[(string)$godo5OrderNo->attributes()->originalOrderNo] = (string)$godo5OrderNo;
			$arrayMakeNewOrderNo[(string)$godo5OrderNo] = 1;
			$Proc_Query = "Insert Into `tmp_orderno` (originalOrderNo, godo5OrderNo, orderGoodsRegDt, regDt) Values ('".(string)$godo5OrderNo->attributes()->originalOrderNo."','".(string)$godo5OrderNo."','" . (string)$godo5OrderNo->attributes()->orderGoodsRegDt . "','".date('Y-m-d H:i:s')."')";
			$db->query($Proc_Query);
			//$arrayOrderGoodsRegDt[(string)$godo5OrderNo->attributes()->originalOrderNo] = (string)$godo5OrderNo->attributes()->orderGoodsRegDt;
		}
	}

	$arrayMemberCheckPostData			= array();			// 戚穿 板 珠芭侯 噺据 汽戚斗 端滴
	$arrayMemberCheckPostData['mode']	= 'memberCheck';	//坦軒 覗稽室什 奄沙 乞球 葵 諮脊
	$arrayMemberCheckPostData['memberDeleteFlag']	= 0;	//坦軒 覗稽室什 奄沙 乞球 葵 諮脊

	$object = xmlUrlRequest("http://" . $url . "/main/relocation.php", $arrayMemberCheckPostData);
	$memberData = $object->memberData;

	$arrayMember = array();
	foreach($memberData as $value) {
		$newMno = (int)$value->attributes()->memNo;
		$arrayMember[urldecode((string)$value)] = $newMno;
	}

	$arrayGoodsNo = array();
	$goodsNoResult = $db->query("Select originalGoodsKey, godo5GoodsNo From tmp_goodsno");
	while ($goodsNoRow = $db->fetch($goodsNoResult)) {
		$arrayGoodsNo[$goodsNoRow['originalGoodsKey']] = $goodsNoRow['godo5GoodsNo'];
	}

	$arrayOrderInsertData = array();
	$arrayAdminMemoInsertData = array();
	if(@in_array("order", $relocationData) && file_exists($csvFilePath . "order.csv")) {
		$arrayDataQuery[] =  'TRUNCATE TABLE es_order;';
		$arrayDataQuery[] =  'TRUNCATE TABLE es_orderGoods;';
		$arrayDataQuery[] =  'TRUNCATE TABLE es_orderInfo;';
		$arrayDataQuery[] =  'TRUNCATE TABLE es_orderDelivery;';
		$arrayDataQuery[] =  'TRUNCATE TABLE es_adminOrderGoodsMemo;';
		$arrayDataQuery[] =  'DROP TABLE IF EXISTS tmp_orderno;';
		$arrayDataQuery[] =  "CREATE TABLE IF NOT EXISTS `tmp_orderno` (
							`sno` int(10) NOT NULL AUTO_INCREMENT,
							`originalOrderNo` char(200) NOT NULL,
							`godo5OrderNo` char(200) NOT NULL,
							`orderGoodsRegDt` datetime NOT NULL,
							`regDt` datetime NOT NULL,
							PRIMARY KEY (`sno`),
							KEY `originalOrderNo` (`originalOrderNo`),
							KEY `godo5OrderNo` (`godo5OrderNo`)
						) ENGINE=MyISAM DEFAULT CHARSet=euckr AUTO_INCREMENT=1;";
		//------------------------------------------------------
		
		//------------------------------------------------------
		// - Advice - 去系 吉 噺据 舛左 蓄窒
		//------------------------------------------------------
		$tmpOrderTableQuery = array();
		$orderCnt = 0;
		$itemCnt = 0;
		$cancelCnt = 0;
		$emoneyCnt = 0;
		$iCno = 0;
		
		$fp = fopen($csvFilePath . "order.csv", 'r' );
		$fields = fgetcsv( $fp, 1000, "," );
		while($orderDataRow = fgetcsv( $fp, 1000, "," )) {
			$esOrderGoods = array();

			$oldOrderNo = $orderDataRow[0]; // 奄糎 爽庚腰硲
			$esOrddt	= ordnoDateTypeSetting($orderDataRow[1]);	// 爽庚析
			if ($arrayGodo5OrderNo[$oldOrderNo]) {
				$esOrderNo = $arrayGodo5OrderNo[$oldOrderNo];
			}
			else {
				$arrayString = array();

				$esOrderNo = getGodomall5Ordno($esOrddt);		// 重鋭 爽庚腰硲
				$arrayGodo5OrderNo[$oldOrderNo] = $esOrderNo;
				$arrayMakeNewOrderNo[(string)$esOrderNo] = 1;
				//$arrayOrderGoodsRegDt[$oldOrderNo] = $esOrddt;
				
				$arrayString['originalOrderNo'] = $oldOrderNo;
				$arrayString['godo5OrderNo'] = $esOrderNo;
				$arrayString['orderGoodsRegDt'] = $esOrddt;
				$arrayString['regDt'] = date('Y-m-d H:i:s');

				

				$tmpOrderNoSet->querySet($arrayString, $tmpOrderNoSetCount);
				$tmpOrderNoSetCount++;
				
				if ($tmpOrderNoSetCount % 10000 === 0) {
					$tmpOrderTableQuery = $tmpOrderNoSet->getQuery($tmpOrderTableQuery);
					$arrayDataQuery = array_merge($arrayDataQuery, $tmpOrderTableQuery);
					dumpSqlFileSet ($dumpFileName, $arrayDataQuery);
					foreach ($tmpOrderTableQuery as $tmpOrderNoTableQuery) {
						$db->query($tmpOrderNoTableQuery);
					}
					unset($arrayDataQuery);
					unset($tmpOrderTableQuery);
					$arrayDataQuery = array();
					$tmpOrderTableQuery = array();
				}
				/*
				//爽庚腰硲 痕井 鎧蝕 績獣 砺戚鷺 煽舌
				$Proc_Query = "Insert Into `tmp_orderno` (originalOrderNo, godo5OrderNo, orderGoodsRegDt, regDt) Values ('".$oldOrderNo."','".$esOrderNo."','" . $esOrddt . "','".date('Y-m-d H:i:s')."')";
				$db->query($Proc_Query);
				*/
			}
		}

		//$tmpOrderNoSetCount % 10000 === 0 繕闇拭 幻膳馬走 省精 10000鯵 戚馬税 蟹袴走 葵級精 食奄辞 insert
		$tmpOrderTableQuery = $tmpOrderNoSet->getQuery($tmpOrderTableQuery);
		$arrayDataQuery = array_merge($arrayDataQuery, $tmpOrderTableQuery);
		foreach ($tmpOrderTableQuery as $tmpOrderNoTableQuery) {
			$db->query($tmpOrderNoTableQuery);
		}
		dumpSqlFileSet ($dumpFileName, $arrayDataQuery);
		unset($tmpOrderNoSet);
		unset($arrayDataQuery);
		unset($tmpOrderTableQuery);
		$arrayDataQuery = array();
		$tmpOrderTableQuery = array();

		$fp = fopen($csvFilePath . "order.csv", 'r' );
		$fields = fgetcsv( $fp, 135000, "," );
		while($orderDataRow = fgetcsv( $fp, 135000, "," )) {
			$esOrderGoods = array();

			$oldOrderNo = $orderDataRow[0]; // 奄糎 爽庚腰硲
			$esOrddt	= ordnoDateTypeSetting($orderDataRow[1]);	// 爽庚析
			if ($arrayGodo5OrderNo[$oldOrderNo]) {
				$esOrderNo = $arrayGodo5OrderNo[$oldOrderNo];
			}
			else {
				$esOrderNo = getGodomall5Ordno($esOrddt);		// 重鋭 爽庚腰硲
				$arrayGodo5OrderNo[$oldOrderNo] = $esOrderNo;
				//$arrayOrderGoodsRegDt[$oldOrderNo] = $esOrddt;
				//爽庚腰硲 痕井 鎧蝕 績獣 砺戚鷺 煽舌
				$Proc_Query = "Insert Into `tmp_orderno` (originalOrderNo, godo5OrderNo, orderGoodsRegDt, regDt) Values ('".$oldOrderNo."','".$esOrderNo."','" . $esOrddt . "','".date('Y-m-d H:i:s')."')";
				$db->query($Proc_Query);
			}

			$SettlePrice = '';		// 雌念, 壕勺搾 杯域 榎衝
			$totalGoodsPrice = '';			// 雌念 杯域榎衝
			$totalDeliveryCharge = '';			// 壕勺搾
			$totalMemberDcPrice = '';			// 噺据 拝昔亜 旋遂
			$totalMileage = '';				// 紫遂旋験榎
			$totalCouponOrderDcPrice = '';				// 庭肉榎衝
			$invoiceNo = '';		// 勺舌腰硲

			

			$arrayOrderInsertData[$esOrderNo]['orderNo'] = $esOrderNo;
			$arrayOrderInsertData[$esOrderNo]['memNo'] = $arrayMember[$orderDataRow[4]]; //噺据 析恵腰硲
			$arrayOrderInsertData[$esOrderNo]['orderStatus'] = $orderDataRow[22];
			$arrayOrderInsertData[$esOrderNo]['orderIp'] = $orderDataRow[39];
			$arrayOrderInsertData[$esOrderNo]['orderChannelFl'] ='shop';
			$arrayOrderInsertData[$esOrderNo]['orderTypeFl'] =	$orderDataRow[2];		//爽庚 展脊
			$arrayOrderInsertData[$esOrderNo]['orderEmail'] = $orderDataRow[23];
			$arrayOrderInsertData[$esOrderNo]['orderGoodsNm'] ='';
			$arrayOrderInsertData[$esOrderNo]['orderGoodsCnt'] ='';
			$arrayOrderInsertData[$esOrderNo]['SettlePrice'] = $orderDataRow[19];
			//$arrayOrderInsertData[$esOrderNo]['taxSupplyPrice'] ='';
			//$arrayOrderInsertData[$esOrderNo]['taxVatPrice'] ='';
			//$arrayOrderInsertData[$esOrderNo]['taxFreePrice'] ='';
			//$arrayOrderInsertData[$esOrderNo]['realTaxSupplyPrice'] ='';
			//$arrayOrderInsertData[$esOrderNo]['realTaxVatPrice'] ='';
			//$arrayOrderInsertData[$esOrderNo]['realTaxFreePrice'] ='';
			$arrayOrderInsertData[$esOrderNo]['useMileage'] = $orderDataRow[29];
			//$arrayOrderInsertData[$esOrderNo]['useDeposit'] =''; //森帖榎
			$arrayOrderInsertData[$esOrderNo]['totalGoodsPrice'] = $orderDataRow[20]; //恥 雌念
			$arrayOrderInsertData[$esOrderNo]['totalDeliveryCharge'] = $orderDataRow[21]; // 恥 壕勺搾
			//$arrayOrderInsertData[$esOrderNo]['totalGoodsDcPrice'] = '';
			$arrayOrderInsertData[$esOrderNo]['totalMemberDcPrice'] = $orderDataRow[32];
			//$arrayOrderInsertData[$esOrderNo]['totalMemberOverlapDcPrice'] = '';
			//$arrayOrderInsertData[$esOrderNo]['totalCouponGoodsDcPrice'] = '';
			$arrayOrderInsertData[$esOrderNo]['totalCouponOrderDcPrice'] = $orderDataRow[31];
			//$arrayOrderInsertData[$esOrderNo]['totalCouponDeliveryDcPrice'] ='';
			$arrayOrderInsertData[$esOrderNo]['totalMileage'] = $orderDataRow[30];
			//$arrayOrderInsertData[$esOrderNo]['totalGoodsMileage'] ='';
			//$arrayOrderInsertData[$esOrderNo]['totalMemberMileage'] = '';
			//$arrayOrderInsertData[$esOrderNo]['totalCouponGoodsMileage'] ='';
			//$arrayOrderInsertData[$esOrderNo]['totalCouponOrderMileage'] ='';
			//$arrayOrderInsertData[$esOrderNo]['mileageGiveExclude'] ='';
			//$arrayOrderInsertData[$esOrderNo]['minusDepositFl'] ='';
			//$arrayOrderInsertData[$esOrderNo]['minusRestoreDepositFl'] ='';
			//$arrayOrderInsertData[$esOrderNo]['minusMileageFl'] ='';
			//$arrayOrderInsertData[$esOrderNo]['minusRestoreMileageFl'] ='';
			//$arrayOrderInsertData[$esOrderNo]['plusMileageFl'] = '';
			//$arrayOrderInsertData[$esOrderNo]['plusRestoreMileageFl'] = '';
			//$arrayOrderInsertData[$esOrderNo]['firstSaleFl'] = 'y';
			//$arrayOrderInsertData[$esOrderNo]['sendMailSmsFl'] = '';
			$arrayOrderInsertData[$esOrderNo]['SettleKind'] = $orderDataRow[18];
			$arrayOrderInsertData[$esOrderNo]['bankAccount'] = $orderDataRow[33];
			$arrayOrderInsertData[$esOrderNo]['bankSender'] = $orderDataRow[34];
			//$arrayOrderInsertData[$esOrderNo]['receiptFl'] = '';
			//$arrayOrderInsertData[$esOrderNo]['depositPolicy'] = '';
			//$arrayOrderInsertData[$esOrderNo]['mileagePolicy'] = '';
			//$arrayOrderInsertData[$esOrderNo]['statusPolicy'] ='';
			//$arrayOrderInsertData[$esOrderNo]['memberPolicy'] ='';
			//$arrayOrderInsertData[$esOrderNo]['couponPolicy'] ='';
			//$arrayOrderInsertData[$esOrderNo]['userRequestMemo'] ='';
			//$arrayOrderInsertData[$esOrderNo]['userConsultMemo'] ='';
			$arrayOrderInsertData[$esOrderNo]['adminMemo'] = str_replace('\n',chr(10),$orderDataRow[48]);
			//$arrayOrderInsertData[$esOrderNo]['orderPGLog'] ='';
			//$arrayOrderInsertData[$esOrderNo]['orderDeliveryLog'] ='';
			//$arrayOrderInsertData[$esOrderNo]['orderAdminLog'] ='';
			//$arrayOrderInsertData[$esOrderNo]['pgName'] =$orderDataRow[41];
			//$arrayOrderInsertData[$esOrderNo]['pgResultCode'] =$orderDataRow[44];
			//$arrayOrderInsertData[$esOrderNo]['pgTid'] =
			$arrayOrderInsertData[$esOrderNo]['pgAppNo'] = $orderDataRow[43];
			//$arrayOrderInsertData[$esOrderNo]['pgAppDt'] =
			//$arrayOrderInsertData[$esOrderNo]['pgCardCd'] =
			$arrayOrderInsertData[$esOrderNo]['pgSettleNm'] = '';
			$arrayOrderInsertData[$esOrderNo]['pgSettleCd'] ='';
			$arrayOrderInsertData[$esOrderNo]['pgFailReason'] ='';
			$arrayOrderInsertData[$esOrderNo]['pgCancelFl'] = ($orderDataRow[47] =='y') ? 'y' : 'n';
			$arrayOrderInsertData[$esOrderNo]['escrowSendNo'] = $orderDataRow[40];
			//$arrayOrderInsertData[$esOrderNo]['escrowDeliveryFl'] ='';
			//$arrayOrderInsertData[$esOrderNo]['escrowDeliveryDt'] ='';
			//$arrayOrderInsertData[$esOrderNo]['escrowDeliveryCd'] ='';
			//$arrayOrderInsertData[$esOrderNo]['escrowInvoiceNo'] ='';
			//$arrayOrderInsertData[$esOrderNo]['escrowConfirmFl'] ='';
			//$arrayOrderInsertData[$esOrderNo]['escrowDenyFl'] ='';
			//$arrayOrderInsertData[$esOrderNo]['fintechData'] ='';
			//$arrayOrderInsertData[$esOrderNo]['checkoutData'] ='';
			//$arrayOrderInsertData[$esOrderNo]['checksumData'] ='';
			$arrayOrderInsertData[$esOrderNo]['paymentDt'] =  $orderDataRow[35];
			$arrayOrderInsertData[$esOrderNo]['regDt'] = dateCreate($orderDataRow[1]);
			$arrayOrderInsertData[$esOrderNo]['modDt'] = 'now()';
			
			if($orderDataRow[48]) {
				$arrayAdminMemoInsertData['managerSno']		= '1';
				$arrayAdminMemoInsertData['orderNo']		= $esOrderNo;
				$arrayAdminMemoInsertData['orderGoodsSno']	= '';
				$arrayAdminMemoInsertData['type']			= 'order';
				$arrayAdminMemoInsertData['memoCd']			= '04004001';
				$arrayAdminMemoInsertData['content']		= str_replace('\n',chr(10),$orderDataRow[48]);
				$arrayAdminMemoInsertData['delFl']			= 'n';
				$arrayAdminMemoInsertData['deleter']		= '';
				$arrayAdminMemoInsertData['regDt']			= 'now()';
				
				$adminMemoSet->querySet($arrayAdminMemoInsertData, $adminMemoCount);
				$adminMemoCount++;
			}

			$esOrderInfo['orderNo'] = $esOrderNo;
			$esOrderInfo['orderName'] = $orderDataRow[3];
			$esOrderInfo['orderEmail'] = $orderDataRow[23];
			$esOrderInfo['orderPhone'] = $orderDataRow[5];
			$esOrderInfo['orderCellPhone'] = $orderDataRow[6];
			$esOrderInfo['orderZipcode'] = $orderDataRow[7];
			$esOrderInfo['orderZonecode'] = $orderDataRow[8];
			$esOrderInfo['orderAddress'] = $orderDataRow[9];
			$esOrderInfo['orderAddressSub'] = $orderDataRow[10];
			$esOrderInfo['receiverName'] = $orderDataRow[11];
			$esOrderInfo['receiverPhone'] = $orderDataRow[12];
			$esOrderInfo['receiverCellPhone'] = $orderDataRow[13];
			$esOrderInfo['receiverZipcode'] = $orderDataRow[14];
			$esOrderInfo['receiverZonecode'] = $orderDataRow[15];
			$esOrderInfo['receiverAddress'] = $orderDataRow[16];
			$esOrderInfo['receiverAddressSub'] = $orderDataRow[17];
			//$esOrderInfo['customIdNumber'] ='';
			$esOrderInfo['orderMemo'] = $orderDataRow[24];
			$esOrderInfo['regDt'] ='now()';
			$esOrderInfo['modDt'] ='now()';

			$orderInfoSet->querySet($esOrderInfo, $orderInfoCount);
			$orderInfoCount++;

			$esOrderDelivery['orderNo']	=	$esOrderNo;
			$esOrderDelivery['deliverySno']	=	'';//$orderDataRow['deliveryno'];//get_order_delivery($enamooOrderDataRow['deliveryno']); 敗呪 拙穣 背醤 敗.
			$esOrderDelivery['deliveryCharge']	=	$orderDataRow[21];
			$esOrderDelivery['deliveryFixFl']	=	'price';
			$esOrderDelivery['regDt']	=	'now()';
			$esOrderDelivery['modDt']	=	'now()';
			
			$orderDeliverySet->querySet($esOrderDelivery, $orderDeliveryCount);
			$orderDeliveryCount++;

			$orderCnt++;
			/*
			if ($orderCnt % 3000 === 0) {
				echo "<script>prograss_up({$orderCnt});</script>";
			}
			*/

			if ($orderCnt % 1000 === 0) {
				$arrayDataQuery = $orderInfoSet->getQuery($arrayDataQuery);
				$arrayDataQuery = $orderDeliverySet->getQuery($arrayDataQuery);
				$arrayDataQuery = $adminMemoSet->getQuery($arrayDataQuery);

				dumpSqlFileSet ($dumpFileName, $arrayDataQuery);
				unset($arrayDataQuery);
				$arrayDataQuery = array();
			}
		}

		$arrayDataQuery = $orderInfoSet->getQuery($arrayDataQuery);
		$arrayDataQuery = $orderDeliverySet->getQuery($arrayDataQuery);
		$arrayDataQuery = $adminMemoSet->getQuery($arrayDataQuery);

		dumpSqlFileSet ($dumpFileName, $arrayDataQuery);
		unset($orderInfoSet);
		unset($orderDeliverySet);
		unset($adminMemoSet);
		unset($arrayDataQuery);
		$arrayDataQuery = array();

		echo "<br />爽庚 汽戚斗 痕井 {$orderCnt} 闇 戚穿 刃戟<br/>";

		//------------------------------------------------------
		// - Advice - 爽庚腰硲 績獣砺戚鷺 query 持失
		//------------------------------------------------------
		/*
		$newOrderNoTableCreateResult = $db->query("show create table tmp_orderno");
		$newOrderNoTableCreateRow = $db->fetch($newOrderNoTableCreateResult);
		$arrayDataQuery[] = 'DROP TABLE IF EXISTS tmp_orderno;';
		$arrayDataQuery[] = $newOrderNoTableCreateRow['Create Table'] . ';';

		$selectOrderNoResult = $db->query("Select * From tmp_orderno Order By sno");
		while ($selectNewOrderNoRow = $db->fetch($selectOrderNoResult, 1)) {
			$arrayString = array();

			foreach ($selectNewOrderNoRow as $fieldName => $value) {
				$arrayString[$fieldName] = $value;
			}

			$tmpOrderNoSet->querySet($arrayString, $tmpOrderNoSetCount);
			$tmpOrderNoSetCount++;
			
			if ($tmpOrderNoSetCount % 1000 === 0) {
				$arrayDataQuery = $tmpOrderNoSet->getQuery($arrayDataQuery);
				dumpSqlFileSet ($dumpFileName, $arrayDataQuery);
				unset($arrayDataQuery);
				$arrayDataQuery = array();
			}
		}
		$db->query( "Optimize Table tmp_orderno;");
		//$arrayDataQuery[]	= 'Optimize Table tmp_orderno;';*/
	}

	//$arrayDataQuery = $tmpOrderNoSet->getQuery($arrayDataQuery);
	//dumpSqlFileSet ($dumpFileName, $arrayDataQuery);
	//unset($tmpOrderNoSet);
	//unset($arrayDataQuery);
	//$arrayDataQuery = array();

	if(@in_array("orderGoods", $relocationData) && file_exists($csvFilePath . "orderGoods.csv")) {

		$arrayDataQuery[]	= 'TRUNCATE TABLE  es_orderGoods;';
		
		//------------------------------------------------------
		// - Advice - 爽庚腰硲 蓄窒
		//------------------------------------------------------
		/*
		$orderNo = array();
		$arrayOrderGoodsRegDt = array();
		$orderNoQuery = $db -> query("select originalOrderNo, godo5OrderNo, orderGoodsRegDt from `tmp_orderno`;");
		while($row = $db -> fetch($orderNoQuery)) {
			$orderNo[$row['originalOrderNo']] = $row['godo5OrderNo'];
			$arrayOrderGoodsRegDt[$row['originalOrderNo']] = $row['orderGoodsRegDt'];
		}
		*/
		
		//------------------------------------------------------
		// - Advice - 績獣 痕発 砺戚鷺 崎沓球腰硲 蓄窒
		//------------------------------------------------------
		$arrayGodo5BrandCode = array();
		$arrayGodo5BrandCode = getTempBrandCode();
		//------------------------------------------------------

		//------------------------------------------------------
		// - Advice - 爽庚雌念 汀軒
		//------------------------------------------------------
		$orderCdCountCheck = 1;
		$orderCdCountArray =array();
		$orderGoods = fopen($csvFilePath . "orderGoods.csv", 'r' );
		$resItem = fgetcsv( $orderGoods, 300000, "," );
		$firstFlag = true;
		while($orderGoodsDataRow = fgetcsv( $orderGoods, 300000, "," )) {
			$goodEsOrderNo = $arrayGodo5OrderNo[trim($orderGoodsDataRow[0])];
			
			if (!$goodEsOrderNo) continue;
			if (empty($arrayOrderInsertData[$goodEsOrderNo])) continue;
			//------------------------------------------------------
			// - Advice - es_orderGoods orderCd 装亜 葵 域至
			//------------------------------------------------------
			$orderCdCountArray['cnt'][] = $orderCdCountCheck;
			$orderCdCountArray['orderno'][] = $goodEsOrderNo;
			$orderCdCountArray['goodsnm'][] = $orderGoodsDataRow[3];
			if($goodEsOrderNo == $orderCdCountArray['orderno'][0]) {
				$orderCdCount = count($orderCdCountArray['orderno']);
				if ($firstFlag) {
					$orderGoodsCnt = count($orderCdCountArray['orderno'])-1;

					$arrayOrderInsertData[$goodEsOrderNo]['orderGoodsNm'] = $orderCdCountArray['goodsnm'][$orderGoodsCnt];
					$arrayOrderInsertData[$goodEsOrderNo]['orderGoodsCnt'] = $orderGoodsCnt;

					$insertSet->querySet($arrayOrderInsertData[$goodEsOrderNo], $orderDataCount);
					$orderDataCount++;

					unset($orderCdCountArray);
					$orderCdCountArray['cnt'][] = $orderCdCountCheck;
					$orderCdCountArray['orderno'][] = $goodEsOrderNo;
					$orderCdCountArray['goodsnm'][] = $orderGoodsDataRow[3];
					$orderCdCount = '1';

					$firstFlag = false;
				}
			} else {
				$orderGoodsCnt = count($orderCdCountArray['orderno'])-1;

				$arrayOrderInsertData[$goodEsOrderNo]['orderGoodsNm'] = $orderCdCountArray['goodsnm'][$orderGoodsCnt];
				$arrayOrderInsertData[$goodEsOrderNo]['orderGoodsCnt'] = $orderGoodsCnt;
				
				$insertSet->querySet($arrayOrderInsertData[$goodEsOrderNo], $orderDataCount);
				$orderDataCount++;

				unset($orderCdCountArray);
				$orderCdCountArray['cnt'][] = $orderCdCountCheck;
				$orderCdCountArray['orderno'][] = $goodEsOrderNo;
				$orderCdCountArray['goodsnm'][] = $orderGoodsDataRow[3];
				$orderCdCount = '1';
			}
			
			if ($orderDataCount % 1000 === 0) {
				$arrayDataQuery = $insertSet->getQuery($arrayDataQuery);
				/*
				echo '<pre>';
				print_r($arrayDataQuery);
				echo '</pre>';
				echo '酔嬢嬢錠 せせせせせせせせせ';
				*/
				dumpSqlFileSet ($dumpFileName, $arrayDataQuery);
				unset($arrayDataQuery);
				$arrayDataQuery = array();
			}
			

			$esOrderGoods['orderNo'] = 		$goodEsOrderNo;//爽庚腰硲
			$esOrderGoods['orderCd'] = 		$orderCdCount;//爽庚坪球(授辞)
			$esOrderGoods['orderGroupCd'] = 		'';//呪勲紺 採歳 昼社獣 爽庚雌念 益血坪球
			$esOrderGoods['userHandleSno'] = '';//坦軒坪球(発災/鋼念/嘘発)
			$esOrderGoods['handleSno'] = 		'';//坦軒坪球(発災/鋼念/嘘発)
			$esOrderGoods['orderStatus'] = 		$orderGoodsDataRow[5];//爽庚雌殿
			$esOrderGoods['orderDeliverySno'] = 	'';//壕勺砺戚鷺. SNO
			$esOrderGoods['invoiceCompanySno'] = 	'';//澱壕紫 SNO
			$esOrderGoods['invoiceNo'] = $orderGoodsDataRow[19];//勺舌腰硲
			//$esOrderGoods['scmNo'] = '';//SCM ID
			$esOrderGoods['commission'] = '';//因厭紫 呪呪戟晴
			$esOrderGoods['goodsNo'] = 	$arrayGoodsNo[$orderGoodsDataRow[1]];//雌念腰硲
			$esOrderGoods['goodsCd'] = 	$orderGoodsDataRow[2];//雌念坪球
			$esOrderGoods['goodsModelNo'] = 	$orderGoodsDataRow[7];//雌念乞季誤
			$esOrderGoods['goodsNm'] = 	$orderGoodsDataRow[3];//雌念誤
			$esOrderGoods['goodsWeight'] = 	$orderGoodsDataRow[8];//雌念巷惟
			$esOrderGoods['goodsCnt'] = 	$orderGoodsDataRow[6];//雌念呪勲
			$esOrderGoods['goodsPrice'] = $orderGoodsDataRow[4]	;//雌念亜維
			//$esOrderGoods['taxSupplyGoodsPrice'] = 	'';//差杯引室 雌念 因厭亜
			//$esOrderGoods['taxVatGoodsPrice'] = 		'';//差杯引室 雌念 採亜室
			//$esOrderGoods['taxFreeGoodsPrice'] = 	'';//差杯引室 雌念 檎室
			//$esOrderGoods['divisionUseDeposit'] = 	'';//爽庚拝昔 榎衝税 照歳吉 森帖榎
			//$esOrderGoods['divisionUseMileage'] = 	'';//爽庚拝昔 榎衝税 照歳吉 原析軒走
			//$esOrderGoods['divisionCouponOrderDcPrice'] = 	'';//爽庚拝昔 榎衝税 照歳吉 庭肉
			//$esOrderGoods['divisionCouponOrderMileage'] = 	'';//爽庚拝昔 榎衝税 照歳吉 庭肉
			//$esOrderGoods['addGoodsCnt'] = 	'';//蓄亜 雌念 姐呪
			//$esOrderGoods['addGoodsPrice'] = ''	;//蓄亜 雌念 榎衝
			//$esOrderGoods['optionPrice'] = ''	;//蓄亜 雌念 榎衝
			$esOrderGoods['paymentDt'] = $orderGoodsDataRow[23];
			$esOrderGoods['optionTextPrice'] = 	'';//努什闘 辛芝 榎衝
			$esOrderGoods['fixedPrice'] =  '';//舛亜
			$esOrderGoods['costPrice'] = 	'';//古脊亜
			$esOrderGoods['goodsDcPrice'] = 	$orderGoodsDataRow[18];//庭肉拝昔榎衝
			$esOrderGoods['memberDcPrice'] = 	$orderGoodsDataRow[17];//噺据拝昔榎衝
			//$esOrderGoods['memberOverlapDcPrice'] = 	'';//噺据 益血 掻差 拝昔 榎衝(蓄亜雌念薦須)
			//$esOrderGoods['couponGoodsDcPrice'] = 	'';//雌念庭肉 拝昔 榎衝(蓄亜雌念薦須)
			$esOrderGoods['goodsMileage'] = 	$orderGoodsDataRow[16];//雌念 旋験原析軒走(蓄亜雌念薦須)
			//$esOrderGoods['memberMileage'] = 	'';//噺据 旋験原析軒走(蓄亜雌念薦須)
			//$esOrderGoods['couponGoodsMileage'] = 	'';//雌念 庭肉 旋験 原析軒走(蓄亜雌念薦須)
			//$esOrderGoods['minusStockFl'] = 	'';//託姶 食採 (仙壱)
			//$esOrderGoods['minusRestoreStockFl'] = ''	;//差据 食採(仙壱)
			//$esOrderGoods['optionSno'] = 	'';//雌念 辛芝 析恵腰硲
			$optionMergeArray = array();
			$optionMergeSecondArray = array();
			$optionMergeAddArray = array();
			$orderItemOptionAllArray = array();
			if($orderGoodsDataRow[12]) {
				$optionMergeArray[] = "辛芝1";
				$optionMergeArray[]= $orderGoodsDataRow[12];
				$optionMergeArray[]= 'null';
			}
			if ($orderGoodsDataRow[13]) {
				$optionMergeSecondArray[] = "辛芝2";
				$optionMergeSecondArray[]= $orderGoodsDataRow[13];
				$optionMergeSecondArray[]= 'null';
			}
			if ($orderGoodsDataRow[14]) {
				$addOptFirstSettingValue = explode('^', $orderGoodsDataRow['addopt']);
				for ($addOptI = 0; $addOptI < count($addOptFirstSettingValue); $addOptI++) {
					$addOptSettingValue = explode(':', $addOptFirstSettingValue[$addOptI]);
					$optionMergeAddArray[$addOptI][] = $addOptSettingValue[0];
					$optionMergeAddArray[$addOptI][] = $addOptSettingValue[1];
					$optionMergeAddArray[$addOptI][]= 'null';
				}
			}
			$orderItemOptionAllArray = array($optionMergeArray, $optionMergeSecondArray, $optionMergeAddArray);

			$esOrderGoods['optionInfo'] =  gdOrderItemOptionEncode($orderItemOptionAllArray);//辛芝舛左
			$esOrderGoods['optionTextInfo'] = '';//努什闘 辛芝舛左
			//$esOrderGoods['goodsTaxInfo'] = 	'';//雌念 採亜室 舛左
			//$esOrderGoods['cateCd'] = 	'';//朝砺壱軒 坪球
			$esOrderGoods['brandCd'] = 	$arrayGodo5BrandCode[$orderGoodsDataRow[10]];//崎沓球 坪球
			$esOrderGoods['makerNm'] = 	$orderGoodsDataRow[9];//薦繕紫
			$esOrderGoods['originNm'] = 	$orderGoodsDataRow[11];//据至走
			//$esOrderGoods['deliveryLog'] = ''	;//壕勺淫恵稽益
			$esOrderGoods['paymentDt'] = 	$orderGoodsDataRow[23];//脊榎析切
			$esOrderGoods['deliveryDt'] = 	$orderGoodsDataRow[20];//壕勺析切
			$esOrderGoods['deliveryCompleteDt'] = 	$orderGoodsDataRow[21];//壕勺刃戟析切
			$esOrderGoods['cancelDt'] = 	$orderGoodsDataRow[22];//昼社刃戟析切
			$esOrderGoods['goodsDeliveryCollectPrice'] = 	$orderGoodsDataRow[24];//壕勺搾
			//$esOrderGoods['finishDt'] = 	'';//刃戟析切
			$esOrderGoods['regDt'] = 	$arrayOrderInsertData[$goodEsOrderNo]['regDt'];//去系析切
			$esOrderGoods['modDt'] = 	'now()';//呪舛析切

			$orderGoodsSet->querySet($esOrderGoods, $orderGoodsCount);
			$orderGoodsCount++;


			$orderCdCountCheck++;
			$itemCnt++;
			/*
			if ($itemCnt % 10000 === 0) {
				echo "<script>itemPrograss_up({$itemCnt});</script>";
			}
			*/

			if ($itemCnt % 1000 === 0) {
				
				$arrayDataQuery = $orderGoodsSet->getQuery($arrayDataQuery);
				dumpSqlFileSet ($dumpFileName, $arrayDataQuery);
				unset($arrayDataQuery);
				$arrayDataQuery = array();
			}
		
		}

		$arrayDataQuery = $insertSet->getQuery($arrayDataQuery);
		$arrayDataQuery = $orderGoodsSet->getQuery($arrayDataQuery);

		dumpSqlFileSet ($dumpFileName, $arrayDataQuery);
		unset($insertSet);
		unset($orderGoodsSet);
		unset($arrayDataQuery);
		$arrayDataQuery = array();
		
		//薦析 笛 爽庚坪球授辞 雌念 淫恵 爽庚 穣汽戚闘
		$outRoopOrderCdCount =	count($orderCdCountArray['orderno']) - 1;
		$arrayDataQuery[] = "Update es_order Set orderGoodsCnt ='" . count($orderCdCountArray['orderno']) . "',orderGoodsNm = '". addslashes($orderCdCountArray['goodsnm'][$outRoopOrderCdCount]) ."'  Where orderNo = '" . $orderCdCountArray['orderno'][$outRoopOrderCdCount] . "';";
		
		echo "爽庚 焼戚奴 汽戚斗 痕井 {$itemCnt} 闇 戚穿 刃戟<br/>";
	}
	unset($arrayOrderInsertData);
	$arrayOrderInsertData = array();

	$arrayGetSelectTableRow = array(
		'mode'			=> 'getSelectTableRow',
		'selectTable'	=> 'es_manager',
		'orderByField'	=> 'regDt',
	);
	$getSelectTableRowReault = xmlUrlRequest('http://' . $url . '/main/relocation.php', $arrayGetSelectTableRow);
	$firstManageId = '';
	foreach ($getSelectTableRowReault->dataRow as $result) {
		if ((int)$result->result) {
			$db->query(str_replace('json', 'text', str_replace('ENGINE=InnoDB', 'ENGINE=MyISAM', str_replace('utf8mb4', 'utf8', urldecode($result->query)))));
		}
	}
	
	$firstManagerResult = $db->query("Select managerId From es_manager Where sno = '1'");
	list($firstManageId) = mysqli_fetch_row($firstManagerResult);

	if(@in_array("Mileage", $relocationData) && file_exists($csvFilePath . "Mileage.csv")) {
		$arrayDataQuery[]  =  'Truncate Table es_memberMileage;';
		
		//------------------------------------------------------
		// - Advice - 旋験榎 汀軒
		//------------------------------------------------------
		$logEmoney = fopen($csvFilePath . "Mileage.csv", 'r' );
		$mileRow = fgetcsv($logEmoney, 135000, ",");
		while($mileRow = fgetcsv($logEmoney, 135000, ",")) {
			
			$esMile['memNo'] = $arrayMember[$mileRow[0]];
			$esMile['managerId'] = $firstManageId;
			//$esMile['handleMode'] = '';
			//$esMile['handleNo'] = '';
			$esMile['beforeMileage'] = $mileRow[1];
			$esMile['afterMileage'] = $mileRow[2];
			$esMile['mileage'] = 0;
			$esMile['reasonCd'] = $mileRow[4];
			$esMile['handleCd'] = $arrayGodo5OrderNo[$mileRow[7]];
			$esMile['contents'] = $mileRow[5] . '(降持 旋験榎 : ' . $mileRow[3] . 'P)';
			$esMile['deleteFl'] = 'complete';
			//$esMile['deleteScheduleDt'] = '';
			//$esMile['deleteDt'] = '';
			$esMile['regIp'] = '';
			$esMile['regDt'] = $mileRow[6];
			
			$memberMileageSet->querySet($esMile, $memberMileageCount);
			$memberMileageCount++;

			$emoneyCnt++;

			if ($emoneyCnt % 5000 === 0) {
				echo $emoneyCnt . '<br/>';
				$arrayDataQuery = $memberMileageSet->getQuery($arrayDataQuery);
				dumpSqlFileSet ($dumpFileName, $arrayDataQuery);
				unset($arrayDataQuery);
				$arrayDataQuery = array();
			}
			//echo "<script>emoneyPrograss_up({$emoneyCnt});</script>";

		}

		$arrayDataQuery = $memberMileageSet->getQuery($arrayDataQuery);

		$arrayDataQuery[] = "Insert Into es_memberMileage (memNo, handleMode, reasonCd, deleteFl, afterMileage, mileage, contents, regDt) SELECT memNo, 'm' as handleMode, '01005011' as reasonCd, 'n' as deleteFl, mileage as afterMileage, mileage, '汽戚斗 戚穿 : 紫遂亜管 旋験榎引 旋験榎 鎧蝕 疑奄鉢 鯉旋 蓄亜 稽益' as memo, now() as regDt FROM `es_member` WHERE mileage > 0;";

		$arrayDataQuery[] = "Update es_memberMileage set deleteFl = 'n' WHERE mileage < 0;";
		$arrayDataQuery[] = "Optimize Table es_memberMileage;";

		echo "旋験榎 汽戚斗 痕井 戚穿 {$emoneyCnt} 闇刃戟<br/>";
	}

	$arrayDataQuery[] = "Optimize Table es_orderDelivery;";
	$arrayDataQuery[] = "Optimize Table es_orderInfo;";
	$arrayDataQuery[] = "Optimize Table es_order;";
	$arrayDataQuery[] = "Optimize Table es_orderGoods;";
	$arrayDataQuery[] = 'Optimize Table es_adminOrderGoodsMemo;';
	$arrayDataQuery[] = "Optimize Table tmp_orderno;";
	$arrayDataQuery[] = "UPDATE `es_orderGoods` a set orderDeliverySno = (Select sno From es_orderDelivery b Where a.orderNo = b.orderNo);";

	if($arrayDataQuery) {
		if (is_file($dumpFileName . '.sql')) {
			unlink($dumpFileName . '.sql');
		}
		dumpSqlFileSet ($dumpFileName, $arrayDataQuery);
		echo '<div>拙穣 刃戟 恥 : ' . number_format($orderCnt + $itemCnt + $emoneyCnt) . '闇</div><script type="text/javascript">parent.configSubmitComplete("' . number_format($orderCnt + $itemCnt + $emoneyCnt) . '");</script>';
	} else {
		echo "<script>alert('汽戚斗亜 蒸暗蟹 識澱鞠走 省紹柔艦陥.');</script>";
	}

}
echo '<div>拙穣 刃戟 獣娃 : ' . date('Y-m-d H:i:s') . '</div>'
/**
 * Date = 置段 鯵降 拙穣析(2016.04.15)
 * ETC = 壱亀侯 5 展紫戚穿 乞汲
 * Developer = 酵殿層
 */
?>