<?php
	/* ��ǰ ������ ���� �ҽ� ����
		* �ϴ� �ּ� Ȯ�� �� ���ʿ� �ּ� ���� �� �۾� ���� ��Ź �帮�� �ű� �۾��� �ش� �ҽ��� �̿��Ͽ�
		�ֽñ� �ٶ��ϴ�.
	*/
	$goodsPrice		= $goodsRow[22];
	$fixedPrice		= 0;
	$costPrice		= $goodsRow[21];
	$totalStock		= 0;
	$optionFl		= 'n';
	
	$soldOutFl = 'n';
	if (!empty($arrayFlagData[$goodsCd])) {
		$goodsSellFl			= flagChange('yn', $arrayFlagData[$goodsCd][1]);	//PC �Ǹ� ����
		$goodsSellMobileFl		= flagChange('yn', $arrayFlagData[$goodsCd][1]);	//����� �Ǹ� ����
		$goodsDisplayFl			= flagChange('yn', $arrayFlagData[$goodsCd][0]);	//PC ���� ����
		$goodsDisplayMobileFl	= flagChange('yn', $arrayFlagData[$goodsCd][0]);	//����� ���� ����

		$soldOutFl				= ($arrayFlagData[$goodsCd][1] == 'Y') ? 'n' : 'y' ; //ǰ������
	}

	//-------------------------------------------------
	//- Advice - �⺻ �ɼ� �ɼǸ�1 ~ �ɼǸ�5 ����
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


	$arrayNewGoods['goodsNo']					= $goodsNo;									// ��ǰ ��ȣ
	$arrayNewGoods['goodsNmFl']					= '';										// ��ǰ�� Ȯ�� ���� (d,e)
	$arrayNewGoods['goodsNm']					= $goodsNm;									// ��ǰ��
	$arrayNewGoods['goodsNmMain']				= '';										// ��ǰ��-����
	$arrayNewGoods['goodsNmList']				= '';										// ��ǰ��-����Ʈ
	$arrayNewGoods['goodsNmDetail']				= '';										// ��ǰ��-��
	$arrayNewGoods['goodsDisplayFl']			= $goodsDisplayFl;							// ��ǰ ��� ���� - PC(y,n)
	$arrayNewGoods['goodsDisplayMobileFl']		= $goodsDisplayMobileFl;					// ��ǰ ��� ���� - ����ϼ�(y,n)
	$arrayNewGoods['goodsSellFl']				= $goodsSellFl;								// ��ǰ �Ǹ� ���� - PC(y,n)
	$arrayNewGoods['goodsSellMobileFl']			= $goodsSellMobileFl;						// ��ǰ �Ǹ� ���� - ����ϼ�(y,n)
	$arrayNewGoods['applyFl']					= 'y';										// ���޻� ��ǰ ���ο��� a:���ο�û y:���οϷ� n:���ΰź�
	$arrayNewGoods['applyType']					= 'r';										// ���޻� ��ǰ ���α���(r,m,d)
	$arrayNewGoods['applyMsg']					= '';										// ���λ��¿� ���� �߰� �޽���
	$arrayNewGoods['applyDt']					= '';										// ���޻� ��ǰ ���� ��û �ð�
	$arrayNewGoods['commission']				= '0.00';									// ���޻� ��������
	$arrayNewGoods['goodsCd']					= $goodsCd;									// ��ǰ�ڵ�
	$arrayNewGoods['goodsSearchWord']			= $goodsSearchWord;							// ��ǰ �˻���	
	$arrayNewGoods['goodsOpenDt']				= date('Y-m-d H:i:s');						// ��ǰ ���� �ð�
	$arrayNewGoods['goodsState']				= $goodsState;								// ��ǰ���� n = ����ǰ | u = �߰��ǰ | r = ��ǰ/����ǰ
	$arrayNewGoods['goodsColor']				= '';										// ��ǰ ��ǥ ����
	$arrayNewGoods['imageStorage']				= (trimPostRequest('imgHostingDomain')) ? 'url' : 'local';									// �̹��� ����� ��ġ local:�⺻ ���
	$arrayNewGoods['imagePath']					= $imagePath;			// ���� ��� ('��/��/��/��ǰ��ȣ')
	$arrayNewGoods['brandCd']					= $brandCd;									// �귣�� �ڵ�
	$arrayNewGoods['makerNm']					= $makerNm;									// ������
	$arrayNewGoods['originNm']					= $originNm;								// ������
	$arrayNewGoods['goodsModelNo']				= $goodsModelNo;							// �𵨸�
	$arrayNewGoods['makeYmd']					= $makeYmd;									// ������('Y-m-d')
	$arrayNewGoods['launchYmd']					= $launchYmd;								// �����('Y-m-d')
	$arrayNewGoods['effectiveStartYmd']			= '';			//��ȿ���� ����('Y-m-d H:i:s')
	$arrayNewGoods['effectiveEndYmd']			= '';			//��ȿ���� ����('Y-m-d H:i:s')
	$arrayNewGoods['qrCodeFl']					= 'n';										// 	QR�ڵ� ��� ����(y,n)
	$arrayNewGoods['goodsPermission']			= 'all';									// ���Ű��� ȸ���׷� ����(all, member, group)
	$arrayNewGoods['goodsPermissionGroup']		= '';										// ���Ű��� ȸ���׷�
	$arrayNewGoods['onlyAdultFl']				= $onlyAdultFl;								// ���� ���� ��� ����(y,n)
	$arrayNewGoods['goodsMustInfo']				= '';										// ��ǰ �ʼ� ����
	$arrayNewGoods['taxFreeFl']					= $taxFreeFl;								// ����/�����/�鼼 ����(t, n, f)
	$arrayNewGoods['taxPercent']				= $taxPercent;								// ������
	$arrayNewGoods['totalStock']				= $totalStock;								// ���� ���
	$arrayNewGoods['stockFl']					= $stockFl;		// �Ǹ� ��� ����
	$arrayNewGoods['soldOutFl']					= $soldOutFl;										// ǰ������
	$arrayNewGoods['minOrderCnt']				= 1;							// �ּ� ���� ����
	$arrayNewGoods['maxOrderCnt']				= 0;										// �ִ� ���� ����
	$arrayNewGoods['restockFl']					= 'n';										// ���԰�˸�(y,n)
	$arrayNewGoods['mileageFl']					= 'c';										// ���ϸ��� ��å(c,g)
	$arrayNewGoods['mileageGoods']				= '';							// ���ϸ��� ��������
	$arrayNewGoods['mileageGoodsUnit']			= 'price';	// ���ϸ��� �������� ����(percent, price)
	$arrayNewGoods['goodsDiscountFl']			= 'n';										// ��ǰ ���� ����(y,n)
	$arrayNewGoods['goodsDiscount']				= '0.00';									// ��ǰ ���ΰ�
	$arrayNewGoods['goodsDiscountUnit']			= 'percent';								// ��ǰ���� ����(percent, price)
	$arrayNewGoods['goodsPriceString']			= $goodsPriceString;						// ��ǰ���� ��ü����
	$arrayNewGoods['goodsPrice']				= $goodsPrice;							// ��ǰ����
	$arrayNewGoods['fixedPrice']				= $fixedPrice;										// ����
	$arrayNewGoods['costPrice']					= $costPrice;							// ���԰�
	$arrayNewGoods['optionFl']					= $optionFl;								// �ɼ� ��� ����(y,n)
	$arrayNewGoods['optionDisplayFl']			= 's';										// �ɼ� ��� ���(s,d)
	$arrayNewGoods['optionName']				= (!empty($arrayOptionName[$goodsRow[$goodsNoChange[0]]])) ? implode('^|^', $arrayOptionName[$goodsRow[$goodsNoChange[0]]]) : '';			// �ɼǸ� (����^|^)
	$arrayNewGoods['optionTextFl']				= '';										// �ؽ�Ʈ �ɼ� ��� ����
	$arrayNewGoods['addGoodsFl']				= 'n';										// �߰���ǰ ��뿩��(y,n)
	//$arrayNewGoods['addGoods']				= '';										// �߰� ��ǰ ����
	$arrayNewGoods['shortDescription']			= $shortDescription;						// ª�� ����
	$arrayNewGoods['goodsDescription']			= $goodsDescription;						// ��ǰ ����
	$arrayNewGoods['goodsDescriptionMobile']	= '<p>&nbsp;</p>';							// ����ϼ� ��ǰ ����
	$arrayNewGoods['goodsDescriptionSameFl']	= $goodsDescriptionSameFl;					// ��ǰ ���� ����ϼ� ���� ��� ����
	//$arrayNewGoods['deliverySno']				= 5;										// ��� ���
	$arrayNewGoods['relationFl']				= 'n';										// ���û�ǰ ����(n, a, m)
	$arrayNewGoods['relationSameFl']			= 'n';										// ���û�ǰ���ε��(y,n)
	$arrayNewGoods['relationCnt']				= 0;										// ���û�ǰ �ڵ� ����
	$arrayNewGoods['relationGoodsNo']			= '';										// ���û�ǰ ��ǰ�ڵ�
	$arrayNewGoods['relationGoodsDate']			= '';										// ���û�ǰ ��¥����	
	$arrayNewGoods['goodsIconStartYmd']			= '';										// ��ǰ ������ �����Ⱓ - ����
	$arrayNewGoods['goodsIconEndYmd']			= '';										// ��ǰ ������ �����Ⱓ - ����
	$arrayNewGoods['goodsIconCdPeriod']			= '';										// ������ �ڵ� (�Ⱓ���ѿ�)
	$arrayNewGoods['goodsIconCd']				= '';										// ��ǰ ������ (�����ѿ�)
	$arrayNewGoods['imgDetailViewFl']			= 'n';										// ��ǰ �̹��� ������ ȿ��(y,n)
	$arrayNewGoods['externalVideoFl']			= 'n';										// �ܺ� ���� ���� ����(y,n)
	$arrayNewGoods['externalVideoUrl']			= '';										// �ܺ� ���� �ּ�
	$arrayNewGoods['externalVideoWidth']		= '';										// �ܺ� ���� ����
	$arrayNewGoods['externalVideoHeight']		= '';										// �ܺ� ���� ����
	$arrayNewGoods['detailInfoDelivery']		= '';									// ��۾ȳ�
	$arrayNewGoods['detailInfoAS']				= '';									// AS�ȳ�
	$arrayNewGoods['detailInfoRefund']			= '';									// ȯ�Ҿȳ�
	$arrayNewGoods['detailInfoExchange']		= '';									// ��ȯ�ȳ�
	$arrayNewGoods['memo']						= $memo;									// ��ǰ �޸�
	$arrayNewGoods['delFl']						= 'n';										// ��ǰ ���� ����(y,n)
	$arrayNewGoods['regDt']						= $regDt;						// �����
	$arrayNewGoods['modDt']						= date('Y-m-d H:i:s');						// ������
	$arrayNewGoods['delDt']						= '0000-00-00 00:00:00';					// ������
	$arrayNewGoods['scmNo']						= $scmNo;
	$arrayNewGoods['deliverySno']				= $deliverySno;
	
	
	if (!empty($arrayGoodsExtraInfo['exValue'])) {
		$i = 1;
		foreach ($arrayGoodsExtraInfo['exValue'] as $exValue) {
			$arrayExtraInfoNewData = array();
			$arrayExtraInfoNewData['goodsNo']	= $newGoodsNo;
			$arrayExtraInfoNewData['infoTitle'] = '�߰� �׸�' . $i;
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
        list($arrayCategoryQuery, $cateCd) = setCategoryBrand(array('�ӽ�ī�װ�'));
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