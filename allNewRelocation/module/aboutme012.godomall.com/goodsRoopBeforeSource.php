<?php
	/* ��ǰ ������ ���� �� �ҽ� ����
		setDataCode (data, mode) // ���� �� ���θ� ī�װ� ���� �� �귣�� ���� �� �ڵ尪 ���� �Լ�
		data = ������ ������ ex) ī�װ� = array('1�� ī�װ�', '2�� ī�װ�'), �귣�� = '����Ű'
		mode = ���� ��� ex) ī�װ� = 'setCategory' �� �� ����, �귣�� = 'setBrand'
		
		* �ϴ� �ּ� Ȯ�� �� ���ʿ� �ּ� ���� �� �۾� ���� ��Ź �帮�� �ű� �۾��� �ش� �ҽ��� �̿��Ͽ�
		�ֽñ� �ٶ��ϴ�.
	*/

	$arrayCategory = array();
	$categoryFp = fopen($csvFilePath . '/category.csv', 'r' );
	$categoryRow = fgetcsv($categoryFp, 1500000, ',');
	while($categoryRow = fgetcsv($categoryFp, 1500000, ',')) {

        $categoryDepth = strlen($categoryRow[0]);

        if ($categoryDepth == 3) {
            $arrayCategory[$categoryRow[0]]		= array($categoryRow[2]);
        }
        else if ($categoryDepth == 6) {
            $arrayCategory[$categoryRow[0]]		= array($arrayCategory[$categoryRow[4]][0], $categoryRow[2]);
        }
        else if ($categoryDepth == 9) {
            $arrayCategory[$categoryRow[0]]		= array($arrayCategory[substr($categoryRow[4], 0, 3)][0], $arrayCategory[$categoryRow[4]][1], $categoryRow[2]);
        }

        setCategoryBrand($arrayCategory[$categoryRow[0]]);
	}



    $arrayCategoryLink = array();
	$cateLinkDataFp = fopen($csvFilePath . '/categoryLink.csv', 'r' );
	$linkDataRow = fgetcsv($cateLinkDataFp, 1500000, ',');
	while($linkDataRow = fgetcsv($cateLinkDataFp, 1500000, ',')) {

        $arrayCategoryLink[$linkDataRow[0]][] = $linkDataRow[1];
    }

    $arrayGosiData = array();
    $gosiDataFp = fopen($csvFilePath . '/gosi.csv', 'r' );
    $gosiDataRow = fgetcsv($gosiDataFp, 1500000, ',');
    while($gosiDataRow = fgetcsv($gosiDataFp, 1500000, ',')) {

        $productKey = $gosiDataRow[12];

        if($gosiDataRow[0])$title = "�뷮 �Ǵ� �߷�";
        else if ($gosiDataRow[1]) $title = "����Ǻ�Ÿ��";
        else if ($gosiDataRow[2]) $title = "������";
        else if ($gosiDataRow[3]) $title = "�������� / å���Ǹž���";
        else if ($gosiDataRow[4]) $title = "������ �Ǵ� ���� �� ���Ⱓ";
        else if ($gosiDataRow[5]) $title = "�������";
        else if ($gosiDataRow[6]) $title = "ȭ��ǰ���� ���� ��ǰ�Ǿ�ǰ����ó �ɻ� �� ����";
        else if ($gosiDataRow[7]) $title = "�����";
        else if ($gosiDataRow[8]) $title = "�Һ��� ��� ����ó";

        $arrayGosiData[$gosiDataRow[12]] = array('subject' => $title, 'value' => $gosiDataRow[0]);


    }
/*echo "<pre>";
print_R($arrayCategoryLink);
echo "</pre>";
exit*/

/*
	$arrayOptionData = array();
	$optionDataFp = fopen($csvFilePath . '/goods_option.csv', 'r' );
	$optionDataRow = fgetcsv($optionDataFp, 1500000, ',');
	while($optionDataRow = fgetcsv($optionDataFp, 1500000, ',')) {
		$arrayOptionData[$optionDataRow[9]] = array(
			'optionStock' => $optionDataRow[2],
		);
	}

	$arrayOptionMasterData = array();
	$optionMasterDataFp = fopen($csvFilePath . '/goods_option_master.csv', 'r' );
	$optionMasterDataRow = fgetcsv($optionMasterDataFp, 1500000, ',');
	while($optionMasterDataRow = fgetcsv($optionMasterDataFp, 1500000, ',')) {
		if ($optionMasterDataRow[5] == 'T') {
			$optionStock = ($arrayOptionData[$optionMasterDataRow[0]]['optionStock']) ? $arrayOptionData[$optionMasterDataRow[0]]['optionStock'] : 0;
		}
		else {
			$optionStock = 0;
		}

		$arrayOptionMasterData[$optionMasterDataRow[0]] = array(
			'optionDefaultFl' => $optionMasterDataRow[10],
			'optionStock' => $optionStock,
		);
	}
	
	$arrayShopInfoCount = array();
	$arrayOptionShopInfoData	= array();
	$arrayOptionName			= array();
	$optionShopInfoDataFp = fopen($csvFilePath . '/goods_option_shop_info.csv', 'r' );
	$optionShopInfoDataRow = fgetcsv($optionShopInfoDataFp, 1500000, ',');
	while($optionShopInfoDataRow = fgetcsv($optionShopInfoDataFp, 1500000, ',')) {
		if (empty($arrayOptionName[$optionShopInfoDataRow[2]])) {
			$arrayOptionName[$optionShopInfoDataRow[2]] = explode('#$%', $optionShopInfoDataRow[4]);
		}

		if (!$arrayShopInfoCount[$optionShopInfoDataRow[2]]) {
			$arrayShopInfoCount[$optionShopInfoDataRow[2]] = 0;
		}

		$arrayOptionValueTemp = explode('#$%', $optionShopInfoDataRow[5]);
		foreach ($arrayOptionValueTemp as $optionValue) {
			$arrayOptionShopInfoData[$optionShopInfoDataRow[2]][$arrayShopInfoCount[$optionShopInfoDataRow[2]]]['optionValue'][] = $optionValue;
		}
		$arrayOptionShopInfoData[$optionShopInfoDataRow[2]][$arrayShopInfoCount[$optionShopInfoDataRow[2]]]['optionPrice']	= $optionShopInfoDataRow[8];
		$arrayOptionShopInfoData[$optionShopInfoDataRow[2]][$arrayShopInfoCount[$optionShopInfoDataRow[2]]]['optionViewFl']	= ($optionShopInfoDataRow[6] == 'T') ? 'y' : 'n';
		$arrayOptionShopInfoData[$optionShopInfoDataRow[2]][$arrayShopInfoCount[$optionShopInfoDataRow[2]]]['optionSellFl']	= ($optionShopInfoDataRow[7] == 'T') ? 'y' : 'n';
		$arrayOptionShopInfoData[$optionShopInfoDataRow[2]][$arrayShopInfoCount[$optionShopInfoDataRow[2]]]['regDt']		= $optionShopInfoDataRow[10];

		$arrayOptionShopInfoData[$optionShopInfoDataRow[2]][$arrayShopInfoCount[$optionShopInfoDataRow[2]]] = array_merge($arrayOptionShopInfoData[$optionShopInfoDataRow[2]][$arrayShopInfoCount[$optionShopInfoDataRow[2]]], $arrayOptionMasterData[$optionShopInfoDataRow[1]]);
		$arrayShopInfoCount[$optionShopInfoDataRow[2]]++;
	}
	
	unset($arrayOptionData);
	unset($arrayOptionMasterData);
	
	$arrayGoodsCategory = array();
	$goodsCategoryFp = fopen($csvFilePath . '/goods_category.csv', 'r' );
	$goodsCategoryRow = fgetcsv($goodsCategoryFp, 1500000, ',');
	while ($goodsCategoryRow = fgetcsv($goodsCategoryFp, 1500000, ',')) {
		$arrayGoodsCategory[$goodsCategoryRow[1]][] = $goodsCategoryRow[0];
	}

	$arrayGoodsRelated = array();
	$goodsRelatedFp = fopen($csvFilePath . '/goods_related.csv', 'r' );
	$goodsRelatedRow = fgetcsv($goodsRelatedFp, 1500000, ',');
	while($goodsRelatedRow = fgetcsv($goodsRelatedFp, 1500000, ',')) {
		$arrayGoodsRelated[$goodsRelatedRow[1]][] = array($goodsRelatedRow[2], $goodsRelatedRow[4], $goodsRelatedRow[5]);
	}
	
	$arrayGoodsDesc = array();
	$goodsDescFp = fopen($csvFilePath . '/goods_desc.csv', 'r' );
	$goodsDescRow = fgetcsv($goodsDescFp, 1500000, ',');
	while($goodsDescRow = fgetcsv($goodsDescFp, 1500000, ',')) {
		$arrayGoodsDesc[$goodsDescRow[0]][] = array(
			1 => $goodsDescRow[1],
			12 => $goodsDescRow[2],
		);

	}

	$arrayGoodsExtraInfo = array();
	$goodsExtraInfoFp = fopen($csvFilePath . '/goods_extra_info.csv', 'r' );
	$goodsExtraInfoRow = fgetcsv($goodsExtraInfoFp, 1500000, ',');
	while($goodsExtraInfoRow = fgetcsv($goodsExtraInfoFp, 1500000, ',')) {
		if (ereg('prd_tax_type_per', $goodsExtraInfoRow[1])) {
			$arrayGoodsExtraInfo[$goodsExtraInfoRow[0]]['tax_per'] = $goodsExtraInfoRow[2];
		}
		else {
			$arrayGoodsExtraInfo[$goodsExtraInfoRow[0]]['exValue'][] = $goodsExtraInfoRow[2];
		}
	}

	$arrayGoodsOptionSet = array();
	$optionSetCount = 0;
	$goodsOptionSetFp = fopen($csvFilePath . '/goods_option_set.csv', 'r' );
	$goodsOptionSetRow = fgetcsv($goodsOptionSetFp, 1500000, ',');
	while($goodsOptionSetRow = fgetcsv($goodsOptionSetFp, 1500000, ',')) {
		if (empty($arrayGoodsOptionSet[$goodsOptionSetRow[0]])) {
			$arrayGoodsOptionSet[$goodsOptionSetRow[0]]['name'] = array();
			$arrayGoodsOptionSet[$goodsOptionSetRow[0]]['value'] = array();
			$optionSetCount = 0;
		}
		if (!in_array($goodsOptionSetRow[6], $arrayGoodsOptionSet[$goodsOptionSetRow[0]]['name'])) {
			$arrayGoodsOptionSet[$goodsOptionSetRow[0]]['name'][$optionSetCount] = $goodsOptionSetRow[6];
			$arrayGoodsOptionSet[$goodsOptionSetRow[0]]['value'][$optionSetCount][] = $goodsOptionSetRow[7];
			$arrayGoodsOptionSet[$goodsOptionSetRow[0]]['price'][$optionSetCount][] = $goodsOptionSetRow[12];
			$optionSetCount++;
		}
		else {
			$parentKey = array_search($goodsOptionSetRow[6], $arrayGoodsOptionSet[$goodsOptionSetRow[0]]['name']);
			$arrayGoodsOptionSet[$goodsOptionSetRow[0]]['value'][$parentKey][] = $goodsOptionSetRow[7];
			$arrayGoodsOptionSet[$goodsOptionSetRow[0]]['price'][$parentKey][] = $goodsOptionSetRow[12];
		}
	}*/
?>