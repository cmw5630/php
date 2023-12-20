<?php
	ini_set('memory_limit', -1);
	ini_set('max_execution_time', 0);
	header('Content-Type: text/html; charset=UTF-8');

	include '../../utf8lib/lib.func.php';
	
	$arrayBeforeFileName	= trimPostRequest('beforeFileName');
	$arrayAfterFileName		= trimPostRequest('afterFileName');
	$targetDomain			= trimPostRequest('targetDomain');

	$arrangeCount = count($arrayBeforeFileName);

	$oldDataBasePath	= '../../excelData/';
	$newDataBasePath	= '../../module/' . $targetDomain;
	makeDir($newDataBasePath);
	$newDataBasePath .= '/dbfile/';
	makeDir($newDataBasePath);
	$logPath			= $oldDataBasePath . 'log/ucafe/';
	makeDir($logPath);

	$arrangeNumber = array();

	$arrayCafeCSVList = array(
		'orderGoods'	=>	'\[1\.9\]\[몰이전\]\[주문\]\[주문상품\]',
		'orderGoodsEa'	=>	'\[1\.9\]\[몰이전\]\[주문\]\[주문관리\]',
		//'orderRefund'	=>	'\[1\.9\]\[몰이전\]\[주문\]\[주문환불\]',
		'order'			=>	'\[1\.9\]\[몰이전\]\[주문\]\[주문서\]',
		'Mileage'		=>	'\[NEW\]\[1\.9\]\[몰이전\]\[마일리지로그\]',
	);

	$arrayLogText = array();
	$logFileName = date('Ymd') . '.log';
	$arrayLogText[] = "#############################################";
	$arrayLogText[] = "[log start time] : " . date('Y-m-d H:i:s');
	$arrayLogText[] = "[target domain] : " . $targetDomain . chr(13);
	$arrayLogText[] = "---------------------------------------------";

	foreach ($arrayAfterFileName as $key => $afterFileName) {
		$arrayLogText[] = "[temp file make start time] : " . date('Y-m-d H:i:s');
		$arrayLogText[] = "[arrange before data size] : " . number_format(filesize($oldDataBasePath . $arrayBeforeFileName[$key]) / 1048576, 2) . ' MB';

		$csvListKey = preg_replace('/\.csv/', '', $afterFileName);
		$tempFileName = preg_replace('/.csv/', '_tmp$0', $afterFileName);

		$arrayArrangeList[$csvListKey] = $tempFileName;
		$arrangeNumber[$csvListKey] = $key;
						
		$oldFile	=	$oldDataBasePath . $arrayBeforeFileName[$key];
		$splitData = '';

		$splitData = file_get_contents($oldFile);
		
		$fileOpen = fopen($newDataBasePath . $tempFileName,'w');

		$splitData = str_replace('﻿', '', $splitData);	// 카페24 CSV 양식 문서내 시작 부분 보이지 않는 UTF-8 공백문자 치환
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
		if ($csvListKey === 'order' || $csvListKey === 'orderGoods' || $csvListKey === 'orderRefund') {
			$splitData = preg_replace('/(\n)(["][0-9]{1,}+[-]+[0-9]{1,}+["][,])/i', '$1<chr>$2', $splitData);
		}
		else {
			$splitData = preg_replace('/(\n)(["][0-9]{1,}+["][,])/i', '$1<chr>$2', $splitData);
		}
		$splitData = preg_replace('/\r\n/i', '<br />', $splitData);
		$splitData = preg_replace('/\n/i', '<br />', $splitData);
		$splitData = preg_replace('/\r/i', '<br />', $splitData);
		$splitData = str_replace('<br /><chr>', chr(13) . chr(10), $splitData);

		preg_match_all('/\"\<br\ \/\>$/i', $splitData, $result);
		if (!empty($result)) {
			$splitData = preg_replace('/\"\<br\ \/\>$/i', '"', $splitData);
		}

		fwrite($fileOpen, $splitData);
		unset($splitData);
		$splitData = '';

		fclose($fileOpen);
		$arrayLogText[] = "[temp file make]" . $oldFile . ' => ' . $newDataBasePath . $tempFileName;
		$arrayLogText[] = "[temp file make end time] : " . date('Y-m-d H:i:s');
	}

	$arrayOrderGoodsEa		= array();
	$arrayOrderShareData	= array();
	if ($arrayArrangeList['orderGoodsEa']) {
		
		$arrayLogText[] = "[sort info set start time] : " . date('Y-m-d H:i:s');
		$arrayLogText[] = "[arrange data name] : " . $arrayArrangeList['orderGoodsEa'] . ' => orderInfo.csv';

		$arrangeStartTime = date('Y-m-d H:i:s');
		$orderGoodsEaKey1 = 0;
		$orderGoodsEaKey2 = 0;
		$orderGoodsEaKey3 = 0;

		$orderGoodsEaValue1 = 0;
		$orderGoodsEaValue2 = 0;

		$oldFile = $newDataBasePath . $arrayArrangeList['orderGoodsEa'];
		$fileOpen = fopen($oldFile, 'r' );
		$dataField = fgetcsv($fileOpen, 10000, ',');

		$orderGoodsEaKey1 = array_search('주문 아이디', $dataField);
		$orderGoodsEaKey2 = array_search('상품 코드', $dataField);
		$orderGoodsEaKey3 = array_search('옵션 아이디', $dataField);

		$orderGoodsEaValue1 = array_search('수량', $dataField);
		$orderGoodsEaValue2 = array_search('현재 처리 상태', $dataField);
		$orderGoodsEaValue3 = array_search('송장 아이디', $dataField);

		$orderShareValue1	= array_search('배송시작일', $dataField);
		$orderShareValue2	= array_search('배송완료일', $dataField);
		$orderShareValue3	= array_search('취소일', $dataField);

		$stopCnt = 0;
		$arrayData		= array();
		$arraySortKey1	= array();
		$arraySortKey2	= array();
		$arraySortKey3	= array();
		$arrayKey1		= array();
		$arrayKey2		= array();
		$arrayKey3		= array();
		$lineCnt = 1;
		while($dataRow = fgetcsv($fileOpen, 10000000, "," )) {
			$sortKey = $dataRow[$orderGoodsEaKey1];
			$sortKey2 = $dataRow[$orderGoodsEaKey2];
			$sortKey3 = $dataRow[$orderGoodsEaKey3];
			
			$arrayData[$sortKey][$sortKey2][$sortKey3][] = $lineCnt;
			
			unset($dataRow);
			$dataRow = array();
			
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
			$lineCnt++;
		}
		
		fclose($fileOpen);
		$arrayLogText[] = "[sort info set end time | arrange start time] : " . date('Y-m-d H:i:s');

		$newFilePath = $newDataBasePath . '/orderInfo.csv';
		$fileOpen = fopen($newFilePath,'w');
		$oriFileOpen = file($oldFile);
		
		fwrite($fileOpen, rowSetting("\"".implode("\",\"", $dataField)."\"", 0, 0) . chr(13) . chr(10));
		sort($arraySortKey1);
		foreach ($arraySortKey1 as $sortKey1) {
			sort($arraySortKey2[$sortKey1]);
			foreach ($arraySortKey2[$sortKey1] as $sortKey2) {
				sort($arraySortKey3[$sortKey1][$sortKey2]);
				foreach ($arraySortKey3[$sortKey1][$sortKey2] as $sortKey3) {
					foreach ($arrayData[$sortKey1][$sortKey2][$sortKey3] as $dataLine) {
						//fwrite($fileOpen, rowSetting("\"".implode("\",\"", str_replace("\"", "\"\"", $sortDataRow))."\"", 0) . chr(13) . chr(10));
						fwrite($fileOpen, rowSetting($oriFileOpen[$dataLine], 0, 0, 0));
					}
					$arrayData[$sortKey1][$sortKey2][$sortKey3] = array();
					unset($arrayData[$sortKey1][$sortKey2][$sortKey3]);
				}
			}
		}
		fclose($fileOpen);
		unlink($oldFile);

		$arraySortKey1 = array();
		$arraySortKey2 = array();
		$arraySortKey3 = array();
		$arrayKey1		= array();
		$arrayKey2		= array();
		$arrayKey3		= array();
		unset($arraySortKey1);
		unset($arraySortKey2);
		unset($arraySortKey3);
		unset($arrayKey1);
		unset($arrayKey2);
		unset($arrayKey3);
/*
		$arrangeEndTime = date('Y-m-d H:i:s');

		$logMsgtext .= '[arrange start time] : ' . $arrangeStartTime  . chr(13) . chr(10);
		$logMsgtext .= iconv('EUC-KR', 'UTF-8', $arrayArrangeList['orderGoodsEa']) . ' => orderInfo.csv' . chr(13) . chr(10);
		$logMsgtext .= '[arrange end time] : ' . $arrangeEndTime . chr(13) . chr(10);
*/
		$fileOpen = fopen($newDataBasePath . '/orderInfo.csv', 'r' );
		$dataField = fgetcsv($fileOpen, 10000, ',');

		$orderGoodsEaKey1 = array_search('주문 아이디', $dataField);
		$orderGoodsEaKey2 = array_search('상품 코드', $dataField);
		$orderGoodsEaKey3 = array_search('옵션 아이디', $dataField);

		$orderGoodsEaValue1 = array_search('수량', $dataField);
		$orderGoodsEaValue2 = array_search('현재 처리 상태', $dataField);
		$orderGoodsEaValue3 = array_search('송장 아이디', $dataField);
		$orderGoodsEaValue4 = array_search('배송비', $dataField);

		$orderShareValue1	= array_search('배송시작일', $dataField);
		$orderShareValue2	= array_search('배송완료일', $dataField);
		$orderShareValue3	= array_search('취소일', $dataField);

		$arrayOrderStatusChange = array(
			'b1'	=>	'반품신청',			// 반품접수
			'b3'	=>	'반품보류',			// 반품보류
			'b4'	=>	'반품처리중',		// 반품회수완료

			'c1'	=>	'입금전취소',		// 자동취소
			'c3'	=>	'취소완료',			// 관리자취소
			'c4'	=>	'취소신청',			// 고객취소요청

			'd1'	=>	'배송중',			// 배송중
			's1'	=>	'배송완료',			// 배송완료

			'e1'	=>	'교환신청',			// 교환접수
			'e3'	=>	'재배송 준비중',	// 재배송중
			'e5'	=>	'교환완료',			// 교환완료

			'f2'	=>	'결제중도포기',		// 고객결제중단

			'o1'	=>	'입금전',			// 입금대기

			'g1'	=>	'배송준비중',		// 상품준비중
			'p1'	=>	'상품준비중',		// 결제완료

			'r2'	=>	'배송보류',			// 환불보류
			'r2'	=>	'취소처리중',		// 환불보류
			'r3'	=>	'반품완료',			// 환불완료
		);

		while($dataRow = fgetcsv($fileOpen, 10000000, "," )) {
			$orderStatus = array_search($dataRow[$orderGoodsEaValue2], $arrayOrderStatusChange);
			
			$arrayOrderGoodsEa[$dataRow[$orderGoodsEaKey1]][$dataRow[$orderGoodsEaKey2]][$dataRow[$orderGoodsEaKey3]] = array($dataRow[$orderGoodsEaValue1], $orderStatus, $dataRow[$orderGoodsEaValue3], $dataRow[$orderShareValue1], $dataRow[$orderShareValue2], $dataRow[$orderShareValue3], $dataRow[$orderGoodsEaValue4]);

			$arrayOrderShareData[$dataRow[$orderGoodsEaKey1]] = array($orderStatus, $dataRow[$orderGoodsEaValue3], $dataRow[$orderShareValue1], $dataRow[$orderShareValue2], $dataRow[$orderShareValue3]);

			
		}

		fclose($fileOpen);
		$arrayLogText[] = "[arrange end time] : " . date('Y-m-d H:i:s');
		
		?>
		<script type="text/javascript">
			parent.setProgress(<?=$arrangeNumber['orderGoodsEa']?>, "complate");
		</script>
		<?php
	}
	
	$orderGoodsShareData	= array();
		
	if ($arrayArrangeList['order']) {
		$arrayLogText[] = "[sort info set start time] : " . date('Y-m-d H:i:s');
		$arrayLogText[] = "[arrange data name] : " . $arrayArrangeList['order'] . ' => order.csv';
		$arrayFieldMatch = array(
			'주문번호'			=>	'주문번호',
			'주문일자'			=>	'주문일시',
			'주문 타입'			=>	'모바일 구분',
			'주문자명'			=>	'주문자명',
			'주문자아이디'		=>	'회원 아이디',
			'주문자전화번호'	=>	'주문자 유선 전화',
			'주문자핸드폰'		=>	'주문자 휴대 전화',
			'주문자 우편번호'	=>	'주문자 우편 번호',
			'주문자 구역 번호'	=>	'zoneCode',
			'주문자 주소'		=>	'주문자 앞 주소',
			'주문자 상세주소'	=>	'주문자 뒷 주소',
			'받는분이름'		=>	'수신자 이름',
			'받는분전화번호'	=>	'수신자 유선 전화',
			'받는분핸드폰'		=>	'수신자 휴대 전화',
			'받는분 우편번호'	=>	'수신자 우편 번호',
			'받는분 구역번호'	=>	'zoneCode',
			'받는분 주소'		=>	'수신자 앞 주소',
			'받는분 상세 주소'	=>	'수신자 뒷 주소',
			'결제수단'			=>	'결제수단',
			'상품, 배송비 총 합계 금액'	=>	'초기 지불 완료 금액',
			'상품 합계금액'		=>	'goodsPrice',
			'배송비'			=>	'배송비',
			'주문상태'			=>	'arrayOrderShareData1',
			'주문자 이메일'		=>	'이메일',
			'배송메세지'		=>	'배송 메지시',
			'배송업체'			=>	'',
			'송장번호'			=>	'arrayOrderShareData2',
			'착불여부'			=>	'',
			'결제금액'			=>	'초기 지불 완료 금액',
			'사용 적립금'		=>	'마일리지 사용액',
			'적립된 적립금'		=>	'',
			'쿠폰 금액'			=>	'쿠폰 할인금액',
			'회원레벨 할인'		=>	'추가할인정보 해당주문의 합산금액', // 기본 : '그룹할인', 푸딩팩토리 임의 처리
			'은행계좌'			=>	'입금계좌',
			'입금자명'			=>	'결제자',
			'결제일'			=>	'결제일시',
			'배송일'			=>	'arrayOrderShareData3',
			'배송완료일'		=>	'arrayOrderShareData4',
			'결제확인일'		=>	'결제일시',
			'주문자IP'			=>	'주문자ip',
			'에스크로 번호'		=>	'',
			'결제PG사명'		=>	'PG사 이름',
			'pg사 거래번호'		=>	'Transaction ID',
			'PG 승인번호'		=>	'승인번호',
			'PG 승인카드사 코드'	=>	'',
			'승인일자'			=>	'',
			'PG 취소여부'		=>	'카드취소여부',
			'취소일'			=>	'arrayOrderShareData5',
			'관리자 메모'		=>	'',
		);

		$oldFile = $newDataBasePath . $arrayArrangeList['order'];
	
		$fileOpen = fopen($oldFile, 'r' );
		$dataField = fgetcsv($fileOpen, 10000, ',');

		$orderGoodsEaKey1			= array_search('주문번호', $dataField);
		$orderSortKey1				= array_search('주문일시', $dataField);
		$orderSelectKey				= array_search('상점고유번호', $dataField);
		$orderGoodsShareValue1		= array_search('결제일시', $dataField);

		$newFieldCount = 0;
		$arrayOldFieldList = array();
		$arrayNewFieldList = array();
		foreach ($arrayFieldMatch as $newFieldName => $oldFieldName) {
			if ($oldFieldName != '' && !ereg('arrayOrderShareData', $oldFieldName) && !ereg('goodsPrice', $oldFieldName)) {
				if ($oldFieldName === '결제수단') {
					$arrayOldFieldList[$newFieldCount] = 'settleKind' . array_search($oldFieldName, $dataField);
				}
				else if ($oldFieldName === '모바일 구분') {
					$arrayOldFieldList[$newFieldCount] = 'orderType' . array_search($oldFieldName, $dataField);
				}
				else if ($oldFieldName === '주문자 우편 번호') {
					$arrayOldFieldList[$newFieldCount] = 'orderZipCode' . array_search($oldFieldName, $dataField);
				}
				else if ($oldFieldName === '수신자 우편 번호') {
					$arrayOldFieldList[$newFieldCount] = 'receiverZipCode' . array_search($oldFieldName, $dataField);
				}
				else if ($oldFieldName === 'zoneCode') {
					$arrayOldFieldList[$newFieldCount] = 'zoneCode';
				}
				else {
					$arrayOldFieldList[$newFieldCount] = array_search($oldFieldName, $dataField);
				}
			}
			else if (ereg('arrayOrderShareData', $oldFieldName) || ereg('goodsPrice', $oldFieldName)) {
				$arrayOldFieldList[$newFieldCount] = $oldFieldName;
			}
			else {
				$arrayOldFieldList[$newFieldCount] = '';
			}
			$arrayNewFieldList[$newFieldCount] = $newFieldName;
			$newFieldCount++;
		}
		
		$arrayData = array();
		$arraySortKey1 = array();
		$arraySortKey2 = array();
		$arraySortKey3 = array();
		$arrayKey1		= array();
		$arrayKey2		= array();
		$arrayKey3		= array();

		/* -------------------------------------------
		* - Advice - 데이터 소팅 전 임시 저장 문서 관련 변수
		------------------------------------------- */
		$tempFilePath =	$newDataBasePath . '/order_tmp2.csv';
		$tempFileOpen = fopen($tempFilePath,'w');
		$tempLineCnt		= 0;
		// -------------------------------------------
		
		while($dataRow = fgetcsv($fileOpen, 10000000, "," )) {
			$arrayNewData = array();
			$orderGoodsShareData[$dataRow[$orderGoodsEaKey1]][] = $dataRow[$orderGoodsShareValue1];
			
			if ($arrayOrderShareData[$dataRow[$orderGoodsEaKey1]][0] == 'f2' && !$dataRow[$orderSortKey1]) {
				$dataRow[$orderSortKey1] = date('Y-m-d H:i:s', strtotime(substr($dataRow[$orderGoodsEaKey1], 0, 8)));
			}

			$sortKey = getSortKey($dataRow[$orderSortKey1]);

			if ((int)$dataRow[$orderSelectKey] <> 1) continue;
			if (!$dataRow[$orderGoodsEaKey1]) continue;

			foreach($arrayOldFieldList as $key => $oldField) {
				if (!ereg('arrayOrderShareData', $oldField) && !ereg('settleKind', $oldField) && !ereg('orderType', $oldField)  && !ereg('orderZipCode', $oldField)  && !ereg('receiverZipCode', $oldField) && !ereg('zoneCode', $oldField)) {
					if ($oldField === 'goodsPrice') {
						$arrayNewData[$key] = ($dataRow[array_search('초기 지불 완료 금액', $dataField)] - $dataRow[array_search('배송비', $dataField)]) + $dataRow[array_search('추가할인정보 해당주문의 합산금액', $dataField)] + $dataRow[array_search('쿠폰 할인금액', $dataField)];
					}
					else {
						if ((string)$oldField != '') {
							$arrayNewData[$key] = $dataRow[$oldField];
						}
						else {
							$arrayNewData[$key] = '';
						}
					}
				}
				else if (ereg('settleKind', $oldField)) {
					switch ($dataRow[(int)str_replace('settleKind', '', $oldField)]) {
						case 'cash' :
							$arrayNewData[$key] = 'gb';
							break;
						case 'card' :
							$arrayNewData[$key] = 'pc';
							break;
						case 'cell' :
							$arrayNewData[$key] = 'ph';
							break;
						case 'easypay' :
							$arrayNewData[$key] = 'pc';
							break;
						case 'esc_rcash' :
							$arrayNewData[$key] = 'pv';
							break;
						case 'esc_vcash' :
							$arrayNewData[$key] = 'pv';
							break;
						case 'icash' :
							$arrayNewData[$key] = 'gb';
							break;
						case 'tcash' :
							$arrayNewData[$key] = 'gb';
							break;
						case 'ncash' :
							$arrayNewData[$key] = 'gb';
							break;
						case 'mileage' :
							$arrayNewData[$key] = 'fp';
							break;
						case 'point' :
							$arrayNewData[$key] = 'fp';
							break;
						default : 
							$arrayNewData[$key] = 'pc';
							break;
					}
				}
				else if (ereg('orderType', $oldField)) {
					switch ($dataRow[(int)str_replace('orderType', '', $oldField)]) {
						case 'F' :
							$arrayNewData[$key] = 'pc';
							break;
						default :
							$arrayNewData[$key] = 'mobile';
							break;
					}
				}
				else if (ereg('arrayOrderShareData', $oldField)) {
					$arrayNewData[$key] = $arrayOrderShareData[$dataRow[$orderGoodsEaKey1]][((int)str_replace('arrayOrderShareData', '', $oldField) - 1)];
				}
				else if (ereg('orderZipCode', $oldField)) {
					$arrayNewData[$key] = $dataRow[(int)str_replace('orderZipCode', '', $oldField)];
					if (strlen($arrayNewData[$key]) === 5) {
						$arrayNewData[($key + 1)] = $arrayNewData[$key];
					}
					else {
						$arrayNewData[($key + 1)] = '';
					}
				}
				else if (ereg('receiverZipCode', $oldField)) {
					$arrayNewData[$key] = $dataRow[(int)str_replace('receiverZipCode', '', $oldField)];
					if (strlen($arrayNewData[$key]) === 5) {
						$arrayNewData[($key + 1)] = $arrayNewData[$key];
					}
					else {
						$arrayNewData[($key + 1)] = '';
					}
				}
			}
			
			$arrayOrderShareData[$dataRow[$orderGoodsEaKey1]] = array();
			unset($arrayOrderShareData[$dataRow[$orderGoodsEaKey1]]);
			fwrite($tempFileOpen, rowSetting("\"".implode("\",\"", $arrayNewData)."\"", 1) . chr(13) . chr(10));
			$arrayData[$sortKey][] = $tempLineCnt;
			
			if (!$arrayKey1[$sortKey]) {
				$arraySortKey1[] = $sortKey;
				$arrayKey1[$sortKey] = true;
			}

			$tempLineCnt++;
		}

		fclose($fileOpen);
		fclose($tempFileOpen);
		$arrayLogText[] = "[sort info set end time | arrange start time] : " . date('Y-m-d H:i:s');

		$newFilePath = $newDataBasePath . '/order.csv';
		$fileOpen = fopen($newFilePath,'w');
		$tempFileOpen = file($tempFilePath);

		fwrite($fileOpen, rowSetting("\"".implode("\",\"", $arrayNewFieldList)."\"", 0, 0) . chr(13) . chr(10));
		sort($arraySortKey1);
		foreach ($arraySortKey1 as $sortKey1) {
			foreach ($arrayData[$sortKey1] as $dataLine) {
				//fwrite($fileOpen, rowSetting("\"".implode("\",\"", $selectData)."\"", 0, 0, 0) . chr(13) . chr(10));
				fwrite($fileOpen, rowSetting($tempFileOpen[$dataLine], 0, 0, 0));
			}
			$arrayData[$sortKey1] = array();
			unset($arrayData[$sortKey1]);
		}
		
		unlink($oldFile);
		unlink($tempFilePath);
		fclose($fileOpen);

		$arraySortKey1 = array();
		$arraySortKey2 = array();
		$arraySortKey3 = array();
		$arrayKey1		= array();
		$arrayKey2		= array();
		$arrayKey3		= array();
		unset($arraySortKey1);
		unset($arraySortKey2);
		unset($arraySortKey3);
		unset($arrayKey1);
		unset($arrayKey2);
		unset($arrayKey3);

		$arrayLogText[] = "[arrange end time] : " . date('Y-m-d H:i:s');
		?>
		<script type="text/javascript">
			parent.setProgress(<?=$arrangeNumber['order']?>, "complate");
		</script>
		<?php
	}

	if ($arrayArrangeList['orderGoods']) {
		$arrayLogText[] = "[sort info set start time] : " . date('Y-m-d H:i:s');
		$arrayLogText[] = "[arrange data name] : " . $arrayArrangeList['orderGoods'] . ' => orderGoods.csv';
		$orderGoodsEaKey1 = 0;
		$orderGoodsEaKey2 = 0;
		$orderGoodsEaKey3 = 0;

		$arrayFieldMatch = array(
			'주문번호'		=>	'주문 아이디',
			'상품번호'		=>	'상품 번호',
			'상품코드'		=>	'상품 코드',
			'상품명'		=>	'상품 이름',
			'상품가격'		=>	'상품 판매가',
			'주문상태'		=>	'orderGoodsEaValue2',
			'구매수량'		=>	'orderGoodsEaValue1',
			'모델명'		=>	'',
			'상품 무게'		=>	'',
			'제조사'		=>	'',
			'브랜드명'		=>	'',
			'원산지'		=>	'',
			'옵션1'		=>	'옵션 값 스트링',
			'옵션2'		=>	'',
			'추가 옵션'		=>	'',
			'매입가'		=>	'매입가',
			'지급 적립금'		=>	'마일리지 발생액',
			'회원할인'		=>	'상품추가할인액',
			'쿠폰할인'		=>	'세트상품 할인가',
			'송장번호'		=>	'orderGoodsEaValue3',
			'배송시작일'	=>	'orderGoodsEaValue4',
			'배송완료일'	=>	'orderGoodsEaValue5',
			'취소일'		=>	'orderGoodsEaValue6',
			'입금확인일'	=>	'orderGoodsShareData1',
			'배송비'		=>	'orderGoodsEaValue7',
		);

		$oldFile = $newDataBasePath . $arrayArrangeList['orderGoods'];
		
		$fileOpen = fopen($oldFile, 'r' );
		$dataField = fgetcsv($fileOpen, 10000, ',');

		$orderGoodsEaKey1	= array_search('주문 아이디', $dataField);
		$orderGoodsEaKey2	= array_search('상품 번호', $dataField);
		$orderGoodsEaKey3	= array_search('옵션 아이디', $dataField);
		$orderDateKey		= array_search('주문일시', $dataField);
		
		$newFieldCount = 0;
		$arrayOldFieldList = array();
		$arrayNewFieldList = array();
		foreach ($arrayFieldMatch as $newFieldName => $oldFieldName) {
			if ($oldFieldName != '' && !ereg('orderGoodsEaValue', $oldFieldName) && !ereg('orderGoodsShareData', $oldFieldName)) {
				$arrayOldFieldList[$newFieldCount] = array_search($oldFieldName, $dataField);
			}
			else if (ereg('orderGoodsEaValue', $oldFieldName) || ereg('orderGoodsShareData', $oldFieldName)) {
				$arrayOldFieldList[$newFieldCount] = $oldFieldName;
			}
			else {
				$arrayOldFieldList[$newFieldCount] = '';
			}
			$arrayNewFieldList[$newFieldCount] = $newFieldName;
			$newFieldCount++;
		}
		
		$stopCount = 0;
		$arrayData = array();
		$arraySortKey1 = array();
		$arraySortKey2 = array();
		$arraySortKey3 = array();
		$arrayKey1		= array();
		$arrayKey2		= array();
		$arrayKey3		= array();
		
		/* -------------------------------------------
		* - Advice - 데이터 소팅 전 임시 저장 문서 관련 변수
		------------------------------------------- */
		$tempFilePath =	$newDataBasePath . '/orderGoods_tmp2.csv';
		$tempFileOpen = fopen($tempFilePath,'w');
		$tempLineCnt		= 0;
		// -------------------------------------------
		while($dataRow = fgetcsv($fileOpen, 10000000, "," )) {

			$arrayNewData = array();
			$sortKey = $dataRow[$orderGoodsEaKey1];
			$sortKey2 = $dataRow[$orderGoodsEaKey2];
			$sortKey3 = $dataRow[$orderGoodsEaKey3];
			
			if (empty($arrayOrderGoodsEa[$sortKey][$sortKey2][$sortKey3])) continue;
			
			foreach($arrayOldFieldList as $key => $oldField) {
				if (!ereg('orderGoodsEaValue', $oldField) && !ereg('orderGoodsShareData', $oldField)) {
					if ($dataField[$oldField] === '상품 판매가') {
						$arrayNewData[$key] = $dataRow[$oldField] + $dataRow[array_search('옵션 추가 가격', $dataField)];
					}
					else {
						if ((string)$oldField != '') {
							$arrayNewData[$key] = $dataRow[$oldField];
						}
						else {
							$arrayNewData[$key] = '';
						}
					}
				}
				else if (ereg('orderGoodsEaValue', $oldField)) {
					$arrayNewData[$key] = $arrayOrderGoodsEa[$sortKey][$sortKey2][$sortKey3][((int)str_replace('orderGoodsEaValue', '', $oldField) - 1)];
				}
				else if (ereg('orderGoodsShareData', $oldField)) {
					$arrayNewData[$key] = $orderGoodsShareData[$sortKey][((int)str_replace('orderGoodsShareData', '', $oldField) - 1)];
				}
			}

			$arrayOrderGoodsEa[$sortKey][$sortKey2][$sortKey3] = array();
			unset($arrayOrderGoodsEa[$sortKey][$sortKey2][$sortKey3]);
			fwrite($tempFileOpen, rowSetting("\"".implode("\",\"", $arrayNewData)."\"", 1) . chr(13) . chr(10));	
			$arrayData[$sortKey][$sortKey2][$sortKey3][] = $tempLineCnt;
			
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

			$tempLineCnt++;
		}
		fclose($fileOpen);
		fclose($tempFileOpen);

		$arrayLogText[] = "[sort info set end time | arrange start time] : " . date('Y-m-d H:i:s');
		
		$newFilePath = $newDataBasePath . '/orderGoods.csv';
		$fileOpen = fopen($newFilePath,'w');
		$tempFileOpen = file($tempFilePath);

		fwrite($fileOpen, rowSetting("\"".implode("\",\"", $arrayNewFieldList)."\"", 0, 0) . chr(13) . chr(10));
		sort($arraySortKey1);
		foreach ($arraySortKey1 as $sortKey1) {
			sort($arraySortKey2[$sortKey1]);
			foreach ($arraySortKey2[$sortKey1] as $sortKey2) {
				sort($arraySortKey3[$sortKey1][$sortKey2]);
				foreach ($arraySortKey3[$sortKey1][$sortKey2] as $sortKey3) {
					foreach ($arrayData[$sortKey1][$sortKey2][$sortKey3] as $dataLine) {
						//fwrite($fileOpen, rowSetting("\"".implode("\",\"", str_replace("\"", "\"\"", $dataRow))."\"", 0) . chr(13) . chr(10));
						//fwrite($fileOpen, rowSetting("\"".implode("\",\"", $dataRow)."\"", 0) . chr(13) . chr(10));	
						fwrite($fileOpen, rowSetting($tempFileOpen[$dataLine], 0, 0, 0));
					}
					$arrayData[$sortKey1][$sortKey2][$sortKey3] = array();
					unset($arrayData[$sortKey1][$sortKey2][$sortKey3]);
				}
			}
		}
		
		unlink($oldFile);
		unlink($tempFilePath);
		fclose($fileOpen);

		$arraySortKey1 = array();
		$arraySortKey2 = array();
		$arraySortKey3 = array();
		$arrayKey1		= array();
		$arrayKey2		= array();
		$arrayKey3		= array();
		unset($arraySortKey1);
		unset($arraySortKey2);
		unset($arraySortKey3);
		unset($arrayKey1);
		unset($arrayKey2);
		unset($arrayKey3);

		$arrayLogText[] = "[arrange end time] : " . date('Y-m-d H:i:s');
		?>
		<script type="text/javascript">
			parent.setProgress(<?=$arrangeNumber['orderGoods']?>, "complate");
		</script>
		<?php
	}

	if ($arrayArrangeList['Mileage']) {
		$arrayLogText[] = "[sort info set start time] : " . date('Y-m-d H:i:s');
		$arrayLogText[] = "[arrange data name] : " . $arrayArrangeList['Mileage'] . ' => Mileage.csv';

		$arrayFieldMatch = array(
			'회원아이디'		=>	'회원아이디',
			'이전 적립금'		=>	'beforeMileage',
			'이후 적립금'		=>	'가용마일리지',
			'적립금'			=>	'mileage',
			'지급모드'			=>	'type',
			'지급/차감사유'		=>	'마일리지설명',
			'지급일'			=>	'발생일자',
			'주문번호'			=>	'주문번호',
		);

		$oldFile = $newDataBasePath . $arrayArrangeList['Mileage'];
	
		$fileOpen = fopen($oldFile, 'r' );
		$dataField = fgetcsv($fileOpen, 10000, ',');
		
		$afterMileageKey = 0;
		$newFieldCount = 0;
		$arrayOldFieldList = array();
		$arrayNewFieldList = array();
		foreach ($arrayFieldMatch as $newFieldName => $oldFieldName) {
			if ($oldFieldName != '' && $oldFieldName != 'beforeMileage' && $oldFieldName != 'mileage' && $oldFieldName != 'type') {
				$key = array_search($oldFieldName, $dataField);
				$arrayOldFieldList[$newFieldCount] = $key;

				if ($oldFieldName == '가용마일리지') {
					$afterMileageKey = $key;
				}
			}
			else if ($oldFieldName == 'beforeMileage' || $oldFieldName == 'mileage' || $oldFieldName == 'type') {
				$arrayOldFieldList[$newFieldCount] = $oldFieldName;
			}
			else {
				$arrayOldFieldList[$newFieldCount] = '';
			}
			$arrayNewFieldList[$newFieldCount] = $newFieldName;
			$newFieldCount++;
		}
		
		$mileageSortKey1	= array_search('발생일자', $dataField);

		$mileageSelectKey1	= array_search('상점번호', $dataField);
		$mileageSelectKey2	= array_search('삭제여부', $dataField);

		$mileagePlusFlKey	= array_search('가감구분', $dataField);

		$mileageKey			= array_search('적립혹은차감된적립금', $dataField);

		$mileageTypeKey		= array_search('마일리지구분', $dataField);

		$arraySortKey1	= array();
		$arrayKey1		= array();

		/* -------------------------------------------
		* - Advice - 데이터 소팅 전 임시 저장 문서 관련 변수
		------------------------------------------- */
		$tempFilePath =	$newDataBasePath . '/Mileage_tmp2.csv';
		$tempFileOpen = fopen($tempFilePath,'w');
		$tempLineCnt		= 0;
		// -------------------------------------------

		while($dataRow = fgetcsv($fileOpen, 10000000, "," )) {
			if ($dataRow[$mileageSelectKey1] !== '1') continue;
			if ($dataRow[$mileageSelectKey2] == 'T') continue;

			$arrayNewData = array();

			$sortKey = getSortKey($dataRow[$mileageSortKey1]);

			foreach($arrayOldFieldList as $key => $oldField) {
				if ($oldField != 'beforeMileage' && $oldField != 'mileage' && $oldField != 'type') {
					if ((string)$dataRow[$oldField] != '') {
						$arrayNewData[$key] = $dataRow[$oldField];
					}
					else {
						$arrayNewData[$key] = '';
					}
				}
				else {
					$newData = '';
					switch ($oldField) {
						case 'beforeMileage' :
							if ($dataRow[$mileagePlusFlKey] == '-') {
								$newData = $dataRow[$afterMileageKey] + $dataRow[$mileageKey];
							}
							else {
								$newData = $dataRow[$afterMileageKey] - $dataRow[$mileageKey];
							}
							break;
						case 'mileage' :
							if ($dataRow[$mileagePlusFlKey] == '-') {
								$newData = 0 - $dataRow[$mileageKey];
							}
							else {
								$newData = 0 + $dataRow[$mileageKey];
							}
							//eval("\$newData = 0 " . $dataRow[$mileagePlusFlKey] . " \$dataRow[$mileageKey];");
							break;
						default :
							switch ($dataRow[$mileageTypeKey]) {
								case 'A' :
								case 'L' :
								case 'N' :
								case 'Z' :
									//$newData = 'm';
									$newData = '01005011';
									break;
								default :
									//$newData = 'o';
									$newData = '01005011';
									break;
							}
							break;
					}
					$arrayNewData[$key] = $newData;
				}
			}
			
			fwrite($tempFileOpen, rowSetting("\"".implode("\",\"", $arrayNewData)."\"", 1) . chr(13) . chr(10));
			$arrayData[$sortKey][] = $tempLineCnt;
			/*
			print_r($arrayNewData);
			echo '<br/>';
			*/

			if (!$arrayKey1[$sortKey]) {
				$arraySortKey1[] = $sortKey;
				$arrayKey1[$sortKey] = true;
			}
			$tempLineCnt++;
		}
		
		fclose($fileOpen);
		fclose($tempFileOpen);

		$arrayLogText[] = "[sort info set end time | arrange start time] : " . date('Y-m-d H:i:s');

		$newFilePath = $newDataBasePath . '/Mileage.csv';
		$fileOpen = fopen($newFilePath,'w');
		$tempFileOpen = file($tempFilePath);
		fwrite($fileOpen, rowSetting("\"". implode("\",\"", $arrayNewFieldList) ."\"", 0) . chr(13) . chr(10));
		sort($arraySortKey1);
		foreach ($arraySortKey1 as $sortKey1) {
			foreach ($arrayData[$sortKey1] as $dataLine) {
				//fwrite($fileOpen, rowSetting("\"".implode("\",\"", $selectData)."\"", 0) . chr(13) . chr(10));
				fwrite($fileOpen, rowSetting($tempFileOpen[$dataLine], 0, 0, 0));
			}
			$arrayData[$sortKey1] = array();
			unset($arrayData[$sortKey1]);
		}
		
		unlink($oldFile);
		unlink($tempFilePath);
		fclose($fileOpen);
		
		$arraySortKey1 = array();
		$arrayKey1		= array();
		unset($arraySortKey1);
		unset($arrayKey1);

		$arrayLogText[] = "[arrange end time] : " . date('Y-m-d H:i:s');
	}

	$arrayLogText[] = "[log end time] : " . date('Y-m-d H:i:s');
	$arrayLogText[] = "#############################################" . chr(13);
	
	logFileSetting($logPath . $logFileName, $arrayLogText);
	//echo $arrangeCount . '<br/>';
	//exit;
	?>
	<script type="text/javascript">
		parent.setProgress(<?=$arrangeNumber['Mileage']?>, "complate");
	</script>
	<?php

function rowSetting($splitData, $rowArrage, $encodingFl=true, $commaFl=true) {
	if ($commaFl) {
		$splitData = str_replace("|C|", ",", $splitData);
		$splitData = str_replace("|D|", '""', $splitData);
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
	/*
	if (mb_detect_encoding($splitData, array('UTF-8', 'EUC-KR')) == 'UTF-8' && $encodingFl) {
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
	*/
	

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
* Date = 수정일(2016.12.14)
* ETC = 적립금 내역 가공 기능 추가
* Developer = 한영민
*/
?>
