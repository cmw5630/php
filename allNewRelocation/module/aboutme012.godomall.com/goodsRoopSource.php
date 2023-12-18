<?php
	/* 상품 데이터 루프 소스 설명
		* 하단 주석 확인 후 불필요 주석 삭제 후 작업 진행 부탁 드리며 신규 작업시 해당 소스를 이용하여
		주시기 바랍니다.
	*/
	$goodsPrice		= $goodsRow[22];
	$fixedPrice		= 0;
	$costPrice		= $goodsRow[21];
	$totalStock		= 0;
	$optionFl		= 'n';
	
	$soldOutFl = 'n';
	if (!empty($arrayFlagData[$goodsCd])) {
		$goodsSellFl			= flagChange('yn', $arrayFlagData[$goodsCd][1]);	//PC 판매 여부
		$goodsSellMobileFl		= flagChange('yn', $arrayFlagData[$goodsCd][1]);	//모바일 판매 여부
		$goodsDisplayFl			= flagChange('yn', $arrayFlagData[$goodsCd][0]);	//PC 노출 여부
		$goodsDisplayMobileFl	= flagChange('yn', $arrayFlagData[$goodsCd][0]);	//모바일 노출 여부

		$soldOutFl				= ($arrayFlagData[$goodsCd][1] == 'Y') ? 'n' : 'y' ; //품절여부
	}

	//-------------------------------------------------
	//- Advice - 기본 옵션 옵션명1 ~ 옵션명5 지원
	//-------------------------------------------------
	$arrayNewOptionData = array();

    $arrayNewOptionData['goodsNo'] = $goodsNo;
    $arrayNewOptionData['optionNo'] = 1;
    $arrayNewOptionData['optionValue1'] = '';
    $arrayNewOptionData['optionValue2'] = '';
    $arrayNewOptionData['optionValue3'] = '';
    $arrayNewOptionData['optionValue4'] = '';
    $arrayNewOptionData['optionValue5'] = '';
    $arrayNewOptionData['optionPrice'] = '0';
    $arrayNewOptionData['optionViewFl'] = 'y';
    $arrayNewOptionData['optionSellFl'] = 'y';
    $arrayNewOptionData['optionCode'] = 'NULL';
    $arrayNewOptionData['stockCnt'] = '0';

    $optionInsertSet->querySet($arrayNewOptionData, $optionInsertCount);


	$arrayNewGoods['goodsNo']					= $goodsNo;									// 상품 번호
	$arrayNewGoods['goodsNmFl']					= '';										// 상품명 확장 여부 (d,e)
	$arrayNewGoods['goodsNm']					= $goodsNm;									// 상품명
	$arrayNewGoods['goodsNmMain']				= '';										// 상품명-메인
	$arrayNewGoods['goodsNmList']				= '';										// 상품명-리스트
	$arrayNewGoods['goodsNmDetail']				= '';										// 상품명-상세
	$arrayNewGoods['goodsDisplayFl']			= $goodsDisplayFl;							// 상품 출력 여부 - PC(y,n)
	$arrayNewGoods['goodsDisplayMobileFl']		= $goodsDisplayMobileFl;					// 상품 출력 여부 - 모바일샵(y,n)
	$arrayNewGoods['goodsSellFl']				= $goodsSellFl;								// 상품 판매 여부 - PC(y,n)
	$arrayNewGoods['goodsSellMobileFl']			= $goodsSellMobileFl;						// 상품 판매 여부 - 모바일샵(y,n)
	$arrayNewGoods['applyFl']					= 'y';										// 공급사 상품 승인여부 a:승인요청 y:승인완료 n:승인거부
	$arrayNewGoods['applyType']					= 'r';										// 공급사 상품 승인구분(r,m,d)
	$arrayNewGoods['applyMsg']					= '';										// 승인상태에 따른 추가 메시지
	$arrayNewGoods['applyDt']					= '';										// 공급사 상품 승인 요청 시각
	$arrayNewGoods['commission']				= '0.00';									// 공급사 수수료율
	$arrayNewGoods['goodsCd']					= $goodsCd;									// 상품코드
	$arrayNewGoods['goodsSearchWord']			= $goodsSearchWord;							// 상품 검색어	
	$arrayNewGoods['goodsOpenDt']				= date('Y-m-d H:i:s');						// 상품 노출 시간
	$arrayNewGoods['goodsState']				= $goodsState;								// 상품상태 n = 새상품 | u = 중고상품 | r = 반품/재고상품
	$arrayNewGoods['goodsColor']				= '';										// 상품 대표 색상
	$arrayNewGoods['imageStorage']				= (trimPostRequest('imgHostingDomain')) ? 'url' : 'local';									// 이미지 저장소 위치 local:기본 경로
	$arrayNewGoods['imagePath']					= $imagePath;			// 저장 경로 ('년/월/일/상품번호')
	$arrayNewGoods['brandCd']					= $brandCd;									// 브랜드 코드
	$arrayNewGoods['makerNm']					= $makerNm;									// 제조사
	$arrayNewGoods['originNm']					= $originNm;								// 원산지
	$arrayNewGoods['goodsModelNo']				= $goodsModelNo;							// 모델명
	$arrayNewGoods['makeYmd']					= $makeYmd;									// 제조일('Y-m-d')
	$arrayNewGoods['launchYmd']					= $launchYmd;								// 출시일('Y-m-d')
	$arrayNewGoods['effectiveStartYmd']			= '';			//유효일자 시작('Y-m-d H:i:s')
	$arrayNewGoods['effectiveEndYmd']			= '';			//유효일자 종료('Y-m-d H:i:s')
	$arrayNewGoods['qrCodeFl']					= 'n';										// 	QR코드 사용 여부(y,n)
	$arrayNewGoods['goodsPermission']			= 'all';									// 구매가능 회원그룹 설정(all, member, group)
	$arrayNewGoods['goodsPermissionGroup']		= '';										// 구매가능 회원그룹
	$arrayNewGoods['onlyAdultFl']				= $onlyAdultFl;								// 성인 인증 사용 여부(y,n)
	$arrayNewGoods['goodsMustInfo']				= '';										// 상품 필수 정보
	$arrayNewGoods['taxFreeFl']					= $taxFreeFl;								// 과세/비과세/면세 여부(t, n, f)
	$arrayNewGoods['taxPercent']				= $taxPercent;								// 과세율
	$arrayNewGoods['totalStock']				= $totalStock;								// 통합 재고량
	$arrayNewGoods['stockFl']					= $stockFl;		// 판매 재고 여부
	$arrayNewGoods['soldOutFl']					= $soldOutFl;										// 품절여부
	$arrayNewGoods['minOrderCnt']				= 1;							// 최소 구매 수량
	$arrayNewGoods['maxOrderCnt']				= 0;										// 최대 구매 수량
	$arrayNewGoods['restockFl']					= 'n';										// 재입고알림(y,n)
	$arrayNewGoods['mileageFl']					= 'c';										// 마일리지 정책(c,g)
	$arrayNewGoods['mileageGoods']				= '';							// 마일리지 개별설정
	$arrayNewGoods['mileageGoodsUnit']			= 'price';	// 마일리지 개별설정 단위(percent, price)
	$arrayNewGoods['goodsDiscountFl']			= 'n';										// 상품 할인 설정(y,n)
	$arrayNewGoods['goodsDiscount']				= '0.00';									// 상품 할인가
	$arrayNewGoods['goodsDiscountUnit']			= 'percent';								// 상품할인 단위(percent, price)
	$arrayNewGoods['goodsPriceString']			= $goodsPriceString;						// 상품가격 대체문구
	$arrayNewGoods['goodsPrice']				= $goodsPrice;							// 상품가격
	$arrayNewGoods['fixedPrice']				= $fixedPrice;										// 정가
	$arrayNewGoods['costPrice']					= $costPrice;							// 매입가
	$arrayNewGoods['optionFl']					= $optionFl;								// 옵션 사용 여부(y,n)
	$arrayNewGoods['optionDisplayFl']			= 's';										// 옵션 출력 방식(s,d)
	$arrayNewGoods['optionName']				= (!empty($arrayOptionName[$goodsRow[$goodsNoChange[0]]])) ? implode('^|^', $arrayOptionName[$goodsRow[$goodsNoChange[0]]]) : '';			// 옵션명 (구분^|^)
	$arrayNewGoods['optionTextFl']				= '';										// 텍스트 옵션 사용 여부
	$arrayNewGoods['addGoodsFl']				= 'n';										// 추가상품 사용여부(y,n)
	//$arrayNewGoods['addGoods']				= '';										// 추가 상품 정보
	$arrayNewGoods['shortDescription']			= $shortDescription;						// 짧은 설명
	$arrayNewGoods['goodsDescription']			= $goodsDescription;						// 상품 설명
	$arrayNewGoods['goodsDescriptionMobile']	= '<p>&nbsp;</p>';							// 모바일샵 상품 설명
	$arrayNewGoods['goodsDescriptionSameFl']	= $goodsDescriptionSameFl;					// 상품 설명 모바일샵 공통 사용 여부
	//$arrayNewGoods['deliverySno']				= 5;										// 배송 방법
	$arrayNewGoods['relationFl']				= 'n';										// 관련상품 종류(n, a, m)
	$arrayNewGoods['relationSameFl']			= 'n';										// 관련상품서로등록(y,n)
	$arrayNewGoods['relationCnt']				= 0;										// 관련상품 자동 개수
	$arrayNewGoods['relationGoodsNo']			= '';										// 관련상품 상품코드
	$arrayNewGoods['relationGoodsDate']			= '';										// 관련상품 날짜설정	
	$arrayNewGoods['goodsIconStartYmd']			= '';										// 상품 아이콘 설정기간 - 시작
	$arrayNewGoods['goodsIconEndYmd']			= '';										// 상품 아이콘 설정기간 - 종료
	$arrayNewGoods['goodsIconCdPeriod']			= '';										// 아이콘 코드 (기간제한용)
	$arrayNewGoods['goodsIconCd']				= '';										// 상품 아이콘 (무제한용)
	$arrayNewGoods['imgDetailViewFl']			= 'n';										// 상품 이미지 돋보기 효과(y,n)
	$arrayNewGoods['externalVideoFl']			= 'n';										// 외부 비디오 연결 여부(y,n)
	$arrayNewGoods['externalVideoUrl']			= '';										// 외부 비디오 주소
	$arrayNewGoods['externalVideoWidth']		= '';										// 외부 비디오 넓이
	$arrayNewGoods['externalVideoHeight']		= '';										// 외부 비디오 높이
	$arrayNewGoods['detailInfoDelivery']		= '';									// 배송안내
	$arrayNewGoods['detailInfoAS']				= '';									// AS안내
	$arrayNewGoods['detailInfoRefund']			= '';									// 환불안내
	$arrayNewGoods['detailInfoExchange']		= '';									// 교환안내
	$arrayNewGoods['memo']						= $memo;									// 상품 메모
	$arrayNewGoods['delFl']						= 'n';										// 상품 삭제 여부(y,n)
	$arrayNewGoods['regDt']						= $regDt;						// 등록일
	$arrayNewGoods['modDt']						= date('Y-m-d H:i:s');						// 수정일
	$arrayNewGoods['delDt']						= '0000-00-00 00:00:00';					// 삭제일
	$arrayNewGoods['scmNo']						= $scmNo;
	$arrayNewGoods['deliverySno']				= $deliverySno;
	
	
	if (!empty($arrayGoodsExtraInfo['exValue'])) {
		$i = 1;
		foreach ($arrayGoodsExtraInfo['exValue'] as $exValue) {
			$arrayExtraInfoNewData = array();
			$arrayExtraInfoNewData['goodsNo']	= $newGoodsNo;
			$arrayExtraInfoNewData['infoTitle'] = '추가 항목' . $i;
			$arrayExtraInfoNewData['infoValue'] = $exValue;
			$arrayExtraInfoNewData['regDt']		= 'now()';
			
			$extraInfoInsertSet->querySet($arrayExtraInfoNewData, $extraInfoInsertCount);

			$extraInfoInsertCount++;
			$i++;
		}
	}
	

	$arrayNewGoods['detailInfoDeliveryFl']		= 'selection';
	$arrayNewGoods['detailInfoDelivery']		= '002001';
	$arrayNewGoods['detailInfoASFl']			= 'selection';
	$arrayNewGoods['detailInfoAS']				= '003001';
	$arrayNewGoods['detailInfoRefundFl']		= 'selection';
	$arrayNewGoods['detailInfoRefund']			= '004001';
	$arrayNewGoods['detailInfoExchangeFl']		= 'selection';
	$arrayNewGoods['detailInfoExchange']		= '005001';


	if (!empty($arrayGoodsRelated[$goodsNoChange[0]])) {
		$arrayRelatedGoodsNumber	= array();
		$arrayPeriod				= array();
		
		foreach ($arrayGoodsRelated[$goodsNoChange[0]] as $relationRow) {
			$newRelatedGoodsNumber = $relationRow[0];
			$arrayRelatedGoodsNumber[] = $newRelatedGoodsNumber;
			if ($relationRow[1]) {
				$arrayPeriod[$newRelatedGoodsNumber] = array(
					'startYmd' => ($relationRow[1]),
					'endYmd' => ($relationRow[2]),
				);
			}
		}
		if (!empty($arrayRelatedGoodsNumber)) {
			$arrayNewGoods['relationGoodsNo']		= "'" . implode('||', $arrayRelatedGoodsNumber) . "'";
			if (!empty($arrayPeriod)) {
				$arrayNewGoods['relationGoodsDate']		= "'" . gd_json_encode($arrayPeriod) . "'";
			}
			$arrayNewGoods['relationFl']				= 'm';
		}
	}

	if(!empty($arrayGosiData[$goodsCd])){
        $mustInfoCount = 0;

	   // for($i=0; $i <= count($arrayGosiData[$goodsCd]); $i++){
       // }
        foreach($arrayGosiData[$goodsCd] as $key => $value){
            $arrayNewMustInfo['line' . $mustInfoCount]['step0'] = array(
                'infoTitle'	=> addslashes($key),
                'infoValue'	=> addslashes($value),
            );

            $mustInfoCount++;
        }

        $arrayNewGoods['goodsMustInfo'] = preg_replace("/(\\\')+/i", "'$2", gd_json_encode($arrayNewMustInfo));
    }


    $arrayCategoryQuery = array();


    if(!empty($arrayCategoryLink[$goodsCd])){
        for($i=0; $i < count($arrayCategoryLink[$goodsCd]); $i++){
            list($arrayCategoryQuery, $cateCd) = setCategoryBrand($arrayCategoryLink[$goodsCd][$i]);
        }
    }else{
        list($arrayCategoryQuery, $cateCd) = setCategoryBrand(array('임시카테고리'));
    }

    foreach ($arrayCategoryQuery as $categoryQuery) {
        if ($mode == 'start_q') {
            echo $queryPrintCount . " : " . htmlentities($categoryQuery, ENT_NOQUOTES, "EUC-JP") . "<br/><br/>";
        }
        else {
            $arrayQueryPostData[] = $categoryQuery;
        }
    }
	
	$arrayNewGoods['cateCd']					= $cateCd;
	
	if (!empty($arrayBrandQuery)) {
		foreach ($arrayBrandQuery as $brandQuery) {
			$arrayQueryPostData[] = $brandQuery;
		}
	}
	
	$insertSet->querySet($arrayNewGoods, $dataCnt + 1);
?>