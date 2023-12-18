<?php
	echo '<div>작업 시작 시간 : ' . date('Y-m-d H:i:s') . '</div>';
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
	// - Advice - 멀티 쿼리 출력 전용 필드값 셋팅
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
	//--- 주문 테이블 초기화

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
	// - Advice - 임시 변환 테이블 주문번호 추출
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

	$arrayMemberCheckPostData			= array();			// 이전 후 쇼핑몰 회원 데이터 체크
	$arrayMemberCheckPostData['mode']	= 'memberCheck';	//처리 프로세스 기본 모드 값 삽입
	$arrayMemberCheckPostData['memberDeleteFlag']	= 0;	//처리 프로세스 기본 모드 값 삽입

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
		// - Advice - 등록 된 회원 정보 추출
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

			$oldOrderNo = $orderDataRow[0]; // 기존 주문번호
			$esOrddt	= ordnoDateTypeSetting($orderDataRow[1]);	// 주문일
			if ($arrayGodo5OrderNo[$oldOrderNo]) {
				$esOrderNo = $arrayGodo5OrderNo[$oldOrderNo];
			}
			else {
				$arrayString = array();

				$esOrderNo = getGodomall5Ordno($esOrddt);		// 신규 주문번호
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
				//주문번호 변경 내역 임시 테이블 저장
				$Proc_Query = "Insert Into `tmp_orderno` (originalOrderNo, godo5OrderNo, orderGoodsRegDt, regDt) Values ('".$oldOrderNo."','".$esOrderNo."','" . $esOrddt . "','".date('Y-m-d H:i:s')."')";
				$db->query($Proc_Query);
				*/
			}
		}

		//$tmpOrderNoSetCount % 10000 === 0 조건에 만족하지 않은 10000개 이하의 나머지 값들은 여기서 insert
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

			$oldOrderNo = $orderDataRow[0]; // 기존 주문번호
			$esOrddt	= ordnoDateTypeSetting($orderDataRow[1]);	// 주문일
			if ($arrayGodo5OrderNo[$oldOrderNo]) {
				$esOrderNo = $arrayGodo5OrderNo[$oldOrderNo];
			}
			else {
				$esOrderNo = getGodomall5Ordno($esOrddt);		// 신규 주문번호
				$arrayGodo5OrderNo[$oldOrderNo] = $esOrderNo;
				//$arrayOrderGoodsRegDt[$oldOrderNo] = $esOrddt;
				//주문번호 변경 내역 임시 테이블 저장
				$Proc_Query = "Insert Into `tmp_orderno` (originalOrderNo, godo5OrderNo, orderGoodsRegDt, regDt) Values ('".$oldOrderNo."','".$esOrderNo."','" . $esOrddt . "','".date('Y-m-d H:i:s')."')";
				$db->query($Proc_Query);
			}

			$SettlePrice = '';		// 상품, 배송비 합계 금액
			$totalGoodsPrice = '';			// 상품 합계금액
			$totalDeliveryCharge = '';			// 배송비
			$totalMemberDcPrice = '';			// 회원 할인가 적용
			$totalMileage = '';				// 사용적립금
			$totalCouponOrderDcPrice = '';				// 쿠폰금액
			$invoiceNo = '';		// 송장번호

			

			$arrayOrderInsertData[$esOrderNo]['orderNo'] = $esOrderNo;
			$arrayOrderInsertData[$esOrderNo]['memNo'] = $arrayMember[$orderDataRow[4]]; //회원 일련번호
			$arrayOrderInsertData[$esOrderNo]['orderStatus'] = $orderDataRow[22];
			$arrayOrderInsertData[$esOrderNo]['orderIp'] = $orderDataRow[39];
			$arrayOrderInsertData[$esOrderNo]['orderChannelFl'] ='shop';
			$arrayOrderInsertData[$esOrderNo]['orderTypeFl'] =	$orderDataRow[2];		//주문 타입
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
			//$arrayOrderInsertData[$esOrderNo]['useDeposit'] =''; //예치금
			$arrayOrderInsertData[$esOrderNo]['totalGoodsPrice'] = $orderDataRow[20]; //총 상품
			$arrayOrderInsertData[$esOrderNo]['totalDeliveryCharge'] = $orderDataRow[21]; // 총 배송비
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
			$esOrderDelivery['deliverySno']	=	'';//$orderDataRow['deliveryno'];//get_order_delivery($enamooOrderDataRow['deliveryno']); 함수 작업 해야 함.
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

		echo "<br />주문 데이터 변경 {$orderCnt} 건 이전 완료<br/>";

		//------------------------------------------------------
		// - Advice - 주문번호 임시테이블 query 생성
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
		// - Advice - 주문번호 추출
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
		// - Advice - 임시 변환 테이블 브랜드번호 추출
		//------------------------------------------------------
		$arrayGodo5BrandCode = array();
		$arrayGodo5BrandCode = getTempBrandCode();
		//------------------------------------------------------

		//------------------------------------------------------
		// - Advice - 주문상품 쿼리
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
			// - Advice - es_orderGoods orderCd 증가 값 계산
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
				echo '우어어엉 ㅋㅋㅋㅋㅋㅋㅋㅋㅋ';
				*/
				dumpSqlFileSet ($dumpFileName, $arrayDataQuery);
				unset($arrayDataQuery);
				$arrayDataQuery = array();
			}
			

			$esOrderGoods['orderNo'] = 		$goodEsOrderNo;//주문번호
			$esOrderGoods['orderCd'] = 		$orderCdCount;//주문코드(순서)
			$esOrderGoods['orderGroupCd'] = 		'';//수량별 부분 취소시 주문상품 그룹코드
			$esOrderGoods['userHandleSno'] = '';//처리코드(환불/반품/교환)
			$esOrderGoods['handleSno'] = 		'';//처리코드(환불/반품/교환)
			$esOrderGoods['orderStatus'] = 		$orderGoodsDataRow[5];//주문상태
			$esOrderGoods['orderDeliverySno'] = 	'';//배송테이블. SNO
			$esOrderGoods['invoiceCompanySno'] = 	'';//택배사 SNO
			$esOrderGoods['invoiceNo'] = $orderGoodsDataRow[19];//송장번호
			//$esOrderGoods['scmNo'] = '';//SCM ID
			$esOrderGoods['commission'] = '';//공급사 수수료율
			$esOrderGoods['goodsNo'] = 	$arrayGoodsNo[$orderGoodsDataRow[1]];//상품번호
			$esOrderGoods['goodsCd'] = 	$orderGoodsDataRow[2];//상품코드
			$esOrderGoods['goodsModelNo'] = 	$orderGoodsDataRow[7];//상품모델명
			$esOrderGoods['goodsNm'] = 	$orderGoodsDataRow[3];//상품명
			$esOrderGoods['goodsWeight'] = 	$orderGoodsDataRow[8];//상품무게
			$esOrderGoods['goodsCnt'] = 	$orderGoodsDataRow[6];//상품수량
			$esOrderGoods['goodsPrice'] = $orderGoodsDataRow[4]	;//상품가격
			//$esOrderGoods['taxSupplyGoodsPrice'] = 	'';//복합과세 상품 공급가
			//$esOrderGoods['taxVatGoodsPrice'] = 		'';//복합과세 상품 부가세
			//$esOrderGoods['taxFreeGoodsPrice'] = 	'';//복합과세 상품 면세
			//$esOrderGoods['divisionUseDeposit'] = 	'';//주문할인 금액의 안분된 예치금
			//$esOrderGoods['divisionUseMileage'] = 	'';//주문할인 금액의 안분된 마일리지
			//$esOrderGoods['divisionCouponOrderDcPrice'] = 	'';//주문할인 금액의 안분된 쿠폰
			//$esOrderGoods['divisionCouponOrderMileage'] = 	'';//주문할인 금액의 안분된 쿠폰
			//$esOrderGoods['addGoodsCnt'] = 	'';//추가 상품 갯수
			//$esOrderGoods['addGoodsPrice'] = ''	;//추가 상품 금액
			//$esOrderGoods['optionPrice'] = ''	;//추가 상품 금액
			$esOrderGoods['paymentDt'] = $orderGoodsDataRow[23];
			$esOrderGoods['optionTextPrice'] = 	'';//텍스트 옵션 금액
			$esOrderGoods['fixedPrice'] =  '';//정가
			$esOrderGoods['costPrice'] = 	'';//매입가
			$esOrderGoods['goodsDcPrice'] = 	$orderGoodsDataRow[18];//쿠폰할인금액
			$esOrderGoods['memberDcPrice'] = 	$orderGoodsDataRow[17];//회원할인금액
			//$esOrderGoods['memberOverlapDcPrice'] = 	'';//회원 그룹 중복 할인 금액(추가상품제외)
			//$esOrderGoods['couponGoodsDcPrice'] = 	'';//상품쿠폰 할인 금액(추가상품제외)
			$esOrderGoods['goodsMileage'] = 	$orderGoodsDataRow[16];//상품 적립마일리지(추가상품제외)
			//$esOrderGoods['memberMileage'] = 	'';//회원 적립마일리지(추가상품제외)
			//$esOrderGoods['couponGoodsMileage'] = 	'';//상품 쿠폰 적립 마일리지(추가상품제외)
			//$esOrderGoods['minusStockFl'] = 	'';//차감 여부 (재고)
			//$esOrderGoods['minusRestoreStockFl'] = ''	;//복원 여부(재고)
			//$esOrderGoods['optionSno'] = 	'';//상품 옵션 일련번호
			$optionMergeArray = array();
			$optionMergeSecondArray = array();
			$optionMergeAddArray = array();
			$orderItemOptionAllArray = array();
			if($orderGoodsDataRow[12]) {
				$optionMergeArray[] = "옵션1";
				$optionMergeArray[]= $orderGoodsDataRow[12];
				$optionMergeArray[]= 'null';
			}
			if ($orderGoodsDataRow[13]) {
				$optionMergeSecondArray[] = "옵션2";
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

			$esOrderGoods['optionInfo'] =  gdOrderItemOptionEncode($orderItemOptionAllArray);//옵션정보
			$esOrderGoods['optionTextInfo'] = '';//텍스트 옵션정보
			//$esOrderGoods['goodsTaxInfo'] = 	'';//상품 부가세 정보
			//$esOrderGoods['cateCd'] = 	'';//카테고리 코드
			$esOrderGoods['brandCd'] = 	$arrayGodo5BrandCode[$orderGoodsDataRow[10]];//브랜드 코드
			$esOrderGoods['makerNm'] = 	$orderGoodsDataRow[9];//제조사
			$esOrderGoods['originNm'] = 	$orderGoodsDataRow[11];//원산지
			//$esOrderGoods['deliveryLog'] = ''	;//배송관련로그
			$esOrderGoods['paymentDt'] = 	$orderGoodsDataRow[23];//입금일자
			$esOrderGoods['deliveryDt'] = 	$orderGoodsDataRow[20];//배송일자
			$esOrderGoods['deliveryCompleteDt'] = 	$orderGoodsDataRow[21];//배송완료일자
			$esOrderGoods['cancelDt'] = 	$orderGoodsDataRow[22];//취소완료일자
			$esOrderGoods['goodsDeliveryCollectPrice'] = 	$orderGoodsDataRow[24];//배송비
			//$esOrderGoods['finishDt'] = 	'';//완료일자
			$esOrderGoods['regDt'] = 	$arrayOrderInsertData[$goodEsOrderNo]['regDt'];//등록일자
			$esOrderGoods['modDt'] = 	'now()';//수정일자

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
		
		//제일 큰 주문코드순서 상품 관련 주문 업데이트
		$outRoopOrderCdCount =	count($orderCdCountArray['orderno']) - 1;
		$arrayDataQuery[] = "Update es_order Set orderGoodsCnt ='" . count($orderCdCountArray['orderno']) . "',orderGoodsNm = '". addslashes($orderCdCountArray['goodsnm'][$outRoopOrderCdCount]) ."'  Where orderNo = '" . $orderCdCountArray['orderno'][$outRoopOrderCdCount] . "';";
		
		echo "주문 아이템 데이터 변경 {$itemCnt} 건 이전 완료<br/>";
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
		// - Advice - 적립금 쿼리
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
			$esMile['contents'] = $mileRow[5] . '(발생 적립금 : ' . $mileRow[3] . 'P)';
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

		$arrayDataQuery[] = "Insert Into es_memberMileage (memNo, handleMode, reasonCd, deleteFl, afterMileage, mileage, contents, regDt) SELECT memNo, 'm' as handleMode, '01005011' as reasonCd, 'n' as deleteFl, mileage as afterMileage, mileage, '데이터 이전 : 사용가능 적립금과 적립금 내역 동기화 목적 추가 로그' as memo, now() as regDt FROM `es_member` WHERE mileage > 0;";

		$arrayDataQuery[] = "Update es_memberMileage set deleteFl = 'n' WHERE mileage < 0;";
		$arrayDataQuery[] = "Optimize Table es_memberMileage;";

		echo "적립금 데이터 변경 이전 {$emoneyCnt} 건완료<br/>";
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
		echo '<div>작업 완료 총 : ' . number_format($orderCnt + $itemCnt + $emoneyCnt) . '건</div><script type="text/javascript">parent.configSubmitComplete("' . number_format($orderCnt + $itemCnt + $emoneyCnt) . '");</script>';
	} else {
		echo "<script>alert('데이터가 없거나 선택되지 않았습니다.');</script>";
	}

}
echo '<div>작업 완료 시간 : ' . date('Y-m-d H:i:s') . '</div>'
/**
 * Date = 최초 개발 작업일(2016.04.15)
 * ETC = 고도몰 5 타사이전 모듈
 * Developer = 박태준
 */
?>