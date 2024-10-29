<?
include '../../inc/header.php';

$insertSet				= new insertSet('es_goods', trimPostRequest('insertMode'));
$optionInsertSet		= new insertSet('es_goodsOption', trimPostRequest('insertMode'));
$goodsImageInsertSet	= new insertSet('es_goodsImage', trimPostRequest('insertMode'));
$extraInfoInsertSet		= new insertSet('es_goodsAddInfo', trimPostRequest('insertMode'));
$arrayQueryPostData		= array();		// ���� ���� ���� �迭

$arrayGoodsLinkSort				= array(); // ī�װ� ��ǰ ���� ���� ����
$sFilePathNew = $sourcePath . '/data/goods/';
$sFilePathOrg = '';						// ���� ���� ��� ����
$imgHostingDomain = trimPostRequest('imgHostingDomain'); // ���� �� �̹��� ȣ���� ���

//------------------------------------------------------
// - Advice - ��ǰ ���� ��� ���� ���� �غ�
//------------------------------------------------------
$fileCopyFl			= (trimPostRequest('file_copy_yn') == 'Y') ? true : false;
$editorFileCopyFl	= (trimPostRequest('editor_file_copy_yn') == 'Y') ? true : false;

$setFile	= new setFile(trimPostRequest('bulkFileFl'), trimPostRequest('localCopy'));
if ($fileCopyFl || $editorFileCopyFl) {
	if (is_dir($sourcePath . '/data/goods')) {
		$setFile->fileListCheck($sourcePath . '/data/goods');
	}
	else {
		$setFile->makeDir($sourcePath . '/data/goods');
	}
	if (is_dir($sourcePath . '/data/editor/goods')) {
		$setFile->fileListCheck($sourcePath . '/data/editor/goods');
	}
	else {
		$setFile->makeDir($sourcePath . '/data/editor');
		$setFile->makeDir($sourcePath . '/data/editor/goods');
	}

	if ($editorFileCopyFl) {
		$setFile->editorFileInfoSet(trimPostRequest('editorFileDomain'), trimPostRequest('editorFileDefaultPath'), 'goods');
	}

	if (trimPostRequest('localCopy') == 'Y') {
		$attachImageUrlMatchText = '^(http|https):\/\/[0-9a-zA-Z]+(.[0-9a-zA-Z]{1,50}|:[0-9]{0,5})([\.][0-9a-zA-Z]{1,50}|[\:][0-9]{0,5}|)([\.][0-9a-zA-Z]{1,50}|[\:][0-9]{0,5}|)([\.][0-9a-zA-Z]{1,50}|[\:][0-9]{0,5}|)[^\/]';
		$sFilePathOrg = preg_replace('/' . $attachImageUrlMatchText . '/i', './oldSite', trimPostRequest('file_before'));
	}
	else {
		$sFilePathOrg = trimPostRequest('file_before');
	}
}
//------------------------------------------------------

//------------------------------------------------------
// - Advice - ��ǰ ���̺� ���� �� �ʵ��
//------------------------------------------------------
$insertSet->arrayFieldName = array(
	'goodsNo',
	'goodsNmFl',
	'goodsNm',
	'goodsNmMain',
	'goodsNmList',
	'goodsNmDetail',
	'goodsDisplayFl',
	'goodsDisplayMobileFl',
	'goodsSellFl',
	'goodsSellMobileFl',
	'goodsWeight',
	'scmNo',
	'applyFl',
	'applyType',
	'applyMsg',
	'applyDt',
	'commission',
	'goodsCd',
	'goodsSearchWord',
	'goodsOpenDt',
	'goodsState',
	'goodsColor',
	'imageStorage',
	'imagePath',
	'brandCd',
	'makerNm',
	'originNm',
	'goodsModelNo',
	'makeYmd',
	'launchYmd',
	'effectiveStartYmd',
	'effectiveEndYmd',
	'qrCodeFl',
	'goodsPermission',
	'goodsPermissionGroup',
	'onlyAdultFl',
	'goodsMustInfo',
	'taxFreeFl',
	'taxPercent',
	'totalStock',
	'stockFl',
	'soldOutFl',
	'salesStartYmd',
	'salesEndYmd',
	'minOrderCnt',
	'maxOrderCnt',
	'restockFl',
	'mileageFl',
	'mileageGoods',
	'mileageGoodsUnit',
	'goodsDiscountFl',
	'goodsDiscount',
	'goodsDiscountUnit',
	'goodsPriceString',
	'goodsPrice',
	'fixedPrice',
	'costPrice',
	'optionFl',
	'optionDisplayFl',
	'optionName',
	'optionTextFl',
	'addGoodsFl',
	'addGoods',
	'shortDescription',
	'goodsDescription',
	'goodsDescriptionMobile',
	'goodsDescriptionSameFl',
	'deliverySno',
	'relationFl',
	'relationSameFl',
	'relationCnt',
	'relationGoodsNo',
	'relationGoodsDate',
	'goodsIconStartYmd',
	'goodsIconEndYmd',
	'goodsIconCdPeriod',
	'goodsIconCd',
	'imgDetailViewFl',
	'externalVideoFl',
	'externalVideoUrl',
	'externalVideoWidth',
	'externalVideoHeight',
	'detailInfoDelivery',
	'detailInfoAS',
	'detailInfoRefund',
	'detailInfoExchange',
	'memo',
	'delFl',
	'regDt',
	'modDt',
	'delDt',
	'cateCd',
	'purchaseNo',
	'purchaseGoodsNm',
	'detailInfoDeliveryFl',
	'detailInfoASFl',
	'detailInfoRefundFl',
	'detailInfoExchangeFl',
);

$optionInsertSet->arrayFieldName = array(
	'goodsNo',
	'optionNo',
	'optionValue1',
	'optionValue2',
	'optionValue3',
	'optionValue4',
	'optionValue5',
	'optionPrice',
	'optionViewFl',
	'optionSellFl',
	'optionCode',
	'stockCnt',
);

$goodsImageInsertSet->arrayFieldName = array(
	'goodsNo',
	'imageNo',
	'imageKind',
	'imageName',
	'regDt',
);

$extraInfoInsertSet->arrayFieldName = array(
	'goodsNo',
	'infoTitle',
	'infoValue',
	'regDt',
);
//------------------------------------------------------

//------------------------------------------------------
// - Advice - ��ǰ ���� �ʵ� ���� ����
//------------------------------------------------------
$goodsNoChange				= trimPostRequest('goodsNo_change');		//�Ϸù�ȣ
$goodsCdChange				= trimPostRequest('goodsCd_change');		//��ǰ�ڵ�
$goodsNmChange				= trimPostRequest('goodsNm_change');		//��ǰ��
$goodsPriceStringChange		= trimPostRequest('goodsPriceString_change');	//�ǸŰ� ��ü ����
$memoChange					= trimPostRequest('memo_change');			//������ �޸�
$makerNmChange				= trimPostRequest('makerNm_change');		//������
$originNmChange				= trimPostRequest('originNm_change');		//������
$brandCdChange				= trimPostRequest('brandCd_change');		//�귣��
$goodsModelNoChange			= trimPostRequest('goodsModelNo_change');	//�𵨸�
$goodsWeightChange			= trimPostRequest('goodsWeight_change');	//��ǰ����
$minOrderCntChange			= trimPostRequest('minOrderCnt_change');	//�ּ� ���� ����
$maxOrderCntChange			= trimPostRequest('maxOrderCnt_change');	//�ִ� ���� ����
$goodsSellFlChange			= trimPostRequest('goodsSellFl_change');	//PC �Ǹ� ����
$goodsSellMobileFlChange	= trimPostRequest('goodsSellMobileFl_change');	//����� �Ǹ� ����
$goodsDisplayFlChange		= trimPostRequest('goodsDisplayFl_change');	//PC ���� ����
$goodsDisplayMobileFlChange	= trimPostRequest('goodsDisplayMobileFl_change');	//����� ���� ����
$stockFlChange				= trimPostRequest('stockFl_change');		//��� ���� ����
$soldOutFlChange			= trimPostRequest('soldOutFl_change');		//ǰ�� ����
$taxFreeFlChange			= trimPostRequest('taxFreeFl_change');		//���� ����
$taxPercentChange			= trimPostRequest('taxPercent_change');		//������
$onlyAdultFlChange			= trimPostRequest('onlyAdultFl_change');	//�������� ��� ����
$mileageFlChange			= trimPostRequest('mileageFl_change');		//��ǰ�� ���ϸ��� ����
$imgMagnifyChange			= trimPostRequest('imgMagnify');			//Ȯ�� �̹���
$imgDetailChange			= trimPostRequest('imgDetail');			//�� �̹���
$imgListChange				= trimPostRequest('imgList');				//����� �̹���
$imgMainChange				= trimPostRequest('imgMain');				//���� �̹���
$imgAdd1Change				= trimPostRequest('imgAdd1');				//����Ʈ �׷��� �̹���
$imgAdd2Change				= trimPostRequest('imgAdd2');				//���� �̹���
$goodsSearchWordChange		= trimPostRequest('goodsSearchWord_change');	//�˻���
$goodsStateChange			= trimPostRequest('goodsState_change');		//��ǰ ����
$goodsDescriptionChange		= trimPostRequest('goodsDescription');		//�󼼳���
$goodsDescriptionMobileChange	= trimPostRequest('goodsDescriptionMobile');	//����� �� ����
$shortDescriptionChange		= trimPostRequest('shortDescription');		//ª������
$scmNoChange				= trimPostRequest('scmNo');					//���޻� ��Ī����
$salesStartYmdChange		= trimPostRequest('salesStartYmd');			//��ǰ �ǸűⰣ ������
$salesEndYmdChange			= trimPostRequest('salesEndYmd');			//��ǰ �ǸűⰣ ������
$makeYmdChange				= trimPostRequest('makeYmd');				//������
$launchYmdChange			= trimPostRequest('launchYmd');				//�����
$modDtChange				= trimPostRequest('modDt');					//������
$regDtChange				= trimPostRequest('regDt');					//�����
//------------------------------------------------------

//------------------------------------------------------
// - Advice - �ΰ� ����
//------------------------------------------------------
$deleteField				= trimPostRequest('delete_field');			//��ǰ ���� ���� �ʵ�
//------------------------------------------------------

//------------------------------------------------------
// - Advice - ��� ���θ� ���� ���̺�
//------------------------------------------------------
$db->query("DROP TABLE IF EXISTS es_categoryBrand;");
$db->query("DROP TABLE IF EXISTS es_categoryGoods;");
$db->query("DROP TABLE IF EXISTS es_scmManage;");
$db->query("DROP TABLE IF EXISTS es_scmDeliveryBasic;");
$db->query("DROP TABLE IF EXISTS es_addgoods;");
//------------------------------------------------------

//------------------------------------------------------
// - Advice - ��� ���θ� ��ǰ ���� ���̺� ���� ����
//------------------------------------------------------
$getRelationDataReault = xmlUrlRequest($url, array('mode' => 'getGoodsRelationData'));
foreach ($getRelationDataReault->dataRow as $result) {
	if ((int)$result->result) {
		$relatedQuery = str_replace('json', 'text', str_replace('ENGINE=InnoDB', 'ENGINE=MyISAM', str_replace('utf8mb4', 'utf8', urldecode($result->query))));
		preg_match_all('/^CREATE TABLE [\`]([[:alnum:]\_]{0,})[\`]/m', $relatedQuery, $matches);

		if ($matches[1][0]) {
			$existsQuery = 'DROP TABLE IF EXISTS ' . $matches[1][0];
			$db->query($existsQuery) or die (mysql_error() . ' [error Query] : ' . $existsQuery);
		}

		$db->query($relatedQuery) or die (mysql_error() . ' [error Query] : ' . $relatedQuery);
	}
}

if ($scmNoChange) {
	$db->query("DROP TABLE IF EXISTS es_scmManage");
	$arrayGetSelectTableRow = array(
		'mode'			=> 'getSelectTableRow',
		'selectTable'	=> 'es_scmManage',
		'orderByField'	=> 'scmNo',
	);
	$getSelectTableRowReault = xmlUrlRequest($url, $arrayGetSelectTableRow);
	
	foreach ($getSelectTableRowReault->dataRow as $result) {
		if ((int)$result->result) {
			$db->query(str_replace('json', 'text', str_replace('ENGINE=InnoDB', 'ENGINE=MyISAM', str_replace('utf8mb4', 'utf8', urldecode($result->query)))));
		}
	}

	$arrayScmNo = array();
	$scmNoResult = $db->query("Select scmNo, scmCode From es_scmManage order by scmNo");
	if (trimPostRequest('scmNo_type') == 'scmCode') {
		while ($scmNoRow = $db->fetch($scmNoResult)) {
			$arrayScmNo[$scmNoRow['scmCode']] = $scmNoRow['scmNo'];
		}
	}
	else {
		while ($scmNoRow = $db->fetch($scmNoResult)) {
			$arrayScmNo[$scmNoRow['scmNo']] = $scmNoRow['scmNo'];
		}
	}
}

$arrayDeliveryNo = array();
$deliveryNoResult = $db->query("Select min(sno) sno, scmNo From es_scmDeliveryBasic group by scmNo");
while ($deliveryNoRow = $db->fetch($deliveryNoResult)) {
	$arrayDeliveryNo[$deliveryNoRow['scmNo']] = $deliveryNoRow['sno'];
}
//------------------------------------------------------

//------------------------------------------------------
// - Advice - ���� �� ���θ� ������ �ʱ�ȭ
//------------------------------------------------------
if ($mode == 'start') {
	$arrayQueryPostData[] = "Truncate table es_addGoods";
	$arrayQueryPostData[] = "Truncate table es_goods";
	$arrayQueryPostData[] = "Truncate table es_goodsAddInfo";
	$arrayQueryPostData[] = "Truncate table es_goodsImage";
	$arrayQueryPostData[] = "Truncate table es_goodsLinkBrand";
	$arrayQueryPostData[] = "Truncate table es_goodsLinkCategory";
	$arrayQueryPostData[] = "Truncate table es_goodsMustInfo";
	$arrayQueryPostData[] = "Truncate table es_goodsOption";
	$arrayQueryPostData[] = "Truncate table es_goodsOptionIcon";
	$arrayQueryPostData[] = "Truncate table es_goodsOptionText";
	$arrayQueryPostData[] = "Truncate table es_goodsSaleStatistics";
	$arrayQueryPostData[] = "Truncate table es_goodsSearch";
	//$arrayQueryPostData[] = "Delete From es_categoryBrand Where goodsNo >= 1000000000";
	//$arrayQueryPostData[] = "Delete From es_goodsMustInfo Where goodsNo >= 1000000000";
	//$arrayQueryPostData[] = "Delete From es_categoryGoods Where goodsNo >= 1000000000";
	//$arrayQueryPostData[] = "Delete From es_goodsOption Where goodsNo >= 1000000000";
	//$arrayQueryPostData[] = "Delete From es_goodsOptionIcon Where goodsNo >= 1000000000";
	//$arrayQueryPostData[] = "Delete From es_goodsOptionText Where goodsNo >= 1000000000";
	//$arrayQueryPostData[] = "Delete From es_goodsSaleStatistics Where goodsNo >= 1000000000";
	//$arrayQueryPostData[] = "Delete From es_goodsSearch Where goodsNo >= 1000000000";
	//$arrayQueryPostData[] = "Delete From es_goodsUpdateNaver Where goodsNo >= 1000000000";
	//$arrayQueryPostData[] = "Delete From es_logAddGoods Where goodsNo >= 1000000000";
	//$arrayQueryPostData[] = "Delete From es_logGoods Where goodsNo >= 1000000000";
}
//------------------------------------------------------

//------------------------------------------------------
// - Advice - ������ ���÷��̽� ���� ����
//------------------------------------------------------
$repimgMagnifyBefore		= trimPostRequest('rep_imgMagnify_before');		//Ȯ�� �̹��� ���÷��̽� ���� �� ����
$repimgMagnifyAfter			= trimPostRequest('rep_imgMagnify_after');		//Ȯ�� �̹��� ���÷��̽� ���� �� ����
$repIMCnt					= trimPostRequest('repIMCnt');					//Ȯ�� �̹��� ���÷��̽� ī���� ����

$repImgDetailBefore			= trimPostRequest('rep_imgDetail_before');		//�� �̹��� ���÷��̽� ���� �� ����
$repImgDetailAfter			= trimPostRequest('rep_imgDetail_after');		//�� �̹��� ���÷��̽� ���� �� ����
$repIDCnt					= trimPostRequest('repIDCnt');					//�� �̹��� ���÷��̽� ī���� ����

$repImgListBefore			= trimPostRequest('rep_imgList_before');		//����� �̹��� ���÷��̽� ���� �� ����
$repImgListAfter			= trimPostRequest('rep_imgList_after');			//����� �̹��� ���÷��̽� ���� �� ����
$repImgListCnt				= trimPostRequest('repimgListCnt');				//����� �̹��� ���÷��̽� ī���� ����

$repImgMainBefore			= trimPostRequest('rep_imgMain_before');		//����Ʈ �̹��� ���÷��̽� ���� �� ����
$repImgMainAfter			= trimPostRequest('rep_imgMain_after');			//����Ʈ �̹��� ���÷��̽� ���� �� ����
$repImgMainCnt				= trimPostRequest('repimgMainCnt');				//����Ʈ �̹��� ���÷��̽� ī���� ����

$repImgAdd1Before			= trimPostRequest('rep_imgAdd1_before');		//����Ʈ�׷� �̹��� ���÷��̽� ���� �� ����
$repImgAdd1After			= trimPostRequest('rep_imgAdd1_after');			//����Ʈ�׷� �̹��� ���÷��̽� ���� �� ����
$repImgAdd1Cnt				= trimPostRequest('repimgAdd1Cnt');				//����Ʈ�׷� �̹��� ���÷��̽� ī���� ����

$repImgAdd2Before			= trimPostRequest('rep_imgAdd2_before');		//���� �̹��� ���÷��̽� ���� �� ����
$repImgAdd2After			= trimPostRequest('rep_imgAdd2_after');			//���� �̹��� ���÷��̽� ���� �� ����
$repImgAdd2Cnt				= trimPostRequest('repimgAdd2Cnt');				//���� �̹��� ���÷��̽� ī���� ����

$repGoodsSearchWordBefore	= trimPostRequest('rep_goodsSearchWord_before');	//�˻��� ���÷��̽� ���� �� ����
$repGoodsSearchWordAfter	= trimPostRequest('rep_goodsSearchWord_after');		//�˻��� ���÷��̽� ���� �� ����
$repGoodsSearchWordCnt		= trimPostRequest('repgoodsSearchWordCnt');			//�˻��� ���÷��̽� ī���� ����

$repGoodsDescriptionBefore	= trimPostRequest('rep_goodsDescription_before');	//�� ���� ���÷��̽� ���� �� ����
$repGoodsDescriptionAfter	= trimPostRequest('rep_goodsDescription_after');	//�� ���� ���÷��̽� ���� �� ����
$repGoodsDescriptionCnt		= trimPostRequest('repgoodsDescriptionCnt');		//�� ���� ���÷��̽� ī���� ����

$repGoodsDescriptionMobileBefore	= trimPostRequest('rep_goodsDescriptionMobile_before');	//����� �� ���� ���÷��̽� ���� �� ����
$repGoodsDescriptionMobileAfter		= trimPostRequest('rep_goodsDescriptionMobile_after');	//����� �� ���� ���÷��̽� ���� �� ����
$repGoodsDescriptionMobileCnt		= trimPostRequest('repgoodsDescriptionMobileCnt');		//����� �� ���� ���÷��̽� ī���� ����

$repShortDescriptionBefore	= trimPostRequest('rep_shortDescription_before');	//ª�� ���� ���÷��̽� ���� �� ����
$repShortDescriptionAfter	= trimPostRequest('rep_shortDescription_after');	//ª�� ���� ���÷��̽� ���� �� ����
$repShortCnt				= trimPostRequest('repshortDescriptionCnt');		//ª�� ���� ���÷��̽� ī���� ����

$repGoodsStateBefore		= trimPostRequest('rep_goodsState_before');			//��ǰ ���� ���÷��̽� ���� �� ����
$repGoodsStateAfter			= trimPostRequest('rep_goodsState_after');			//��ǰ ���� ���÷��̽� ���� �� ����
$repGoodsStateCnt			= trimPostRequest('repgoodsStateCnt');				//��ǰ ���� ���÷��̽� ī���� ����
//------------------------------------------------------

$db->query('DROP TABLE IF EXISTS `tmp_goodsno`;');
$db->query("CREATE TABLE IF NOT EXISTS `tmp_goodsno` (
					`sno` int(10) NOT NULL AUTO_INCREMENT,
					`originalGoodsKey` char(200) NOT NULL,
					`godo5GoodsNo` int(10) NOT NULL,
					`regDt` datetime NOT NULL,
					PRIMARY KEY (`sno`),
					KEY `originalGoodsKey` (`originalGoodsKey`),
					KEY `godo5GoodsNo` (`godo5GoodsNo`)
				) ENGINE=MyISAM DEFAULT CHARSet=euckr AUTO_INCREMENT=1;");

include $sourcePath . '/goodsRoopBeforeSource.php';		// ��ǰ ���� ���� ���̺� ��� ���� ������

$dataCnt				= 0;
$fileInsertCount		= 1;
$optionInsertCount		= 1;
$extraInfoInsertCount	= 1;

$arrayGoodsDel = array();
$goodsDelFp = fopen($csvFilePath . '/productSub.csv', 'r' );
$goodsDelRow = fgetcsv($goodsDelFp, 1500000, ',');
while($goodsDelRow = fgetcsv($goodsDelFp, 1500000, ',')) {

    $arrayGoodsDel[$goodsDelRow[0]] = 'y';
}
/*echo "<pre>";
print_R($arrayGoodsDel);
echo "</pre>";
exit;*/

$goodsNo = 1;
$arrayGoodsData = array();
if(trimPostRequest('data_type') === 'csv'){// CSV �� ÷������ ������ �ε�
	$goodsFp = fopen($csvFilePath . trimPostRequest('data_name') . '.csv', 'r' );
	$goodsRow = fgetcsv($goodsFp, 1500000, ',');
} else if(trimPostRequest('data_type') === 'sql') {// SQL �� ÷������ ������ �ε�
	if(trimPostRequest('sort')){
		$goodsSort = ' order by ' . trimPostRequest('sort');
	}
	$goodsResult = $db->query("select " . stripslashes(trimPostRequest('select_field')) . " from " . trimPostRequest('data_name') . $goodsSort);
}

while($goodsRow = (trimPostRequest('data_type') === 'csv') ? fgetcsv($goodsFp, 1500000, ',') : $db->fetch($goodsResult, 1)) {
	if($deleteField){
		if($goodsRow[$deleteField] == trimPostRequest('delete_type')) continue;
	}
	$arrayNewGoods	= array(); // ���� ���� �ӽ� �迭 '�ʵ�' => '��' ����
	$arrayGoodsLink = array(); // ���� ī�װ� ���� ���� �迭
	
	//------------------------------------------------------
	// - Advice - ���� �ʱ�ȭ
	//------------------------------------------------------
	$goodsCd				= '';	//��ǰ�ڵ�
	$goodsNm				= '';	//��ǰ��
	$goodsPriceString		= '';	//�ǸŰ� ��ü ����
	$memo					= '';	//������ �޸�
	$makerNm				= '';	//������
	$originNm				= '';	//������
	$brandCd				= '';	//�귣��
	$goodsModelNo			= '';	//�𵨸�
	$goodsWeight			= '';	//��ǰ����
	$minOrderCnt			= '';	//�ּ� ���� ����
	$maxOrderCnt			= '';	//�ִ� ���� ����
	$goodsSellFl			= '';	//PC �Ǹ� ����
	$goodsSellMobileFl		= '';	//����� �Ǹ� ����
	$goodsDisplayFl			= '';	//PC ���� ����
	$goodsDisplayMobileFl	= '';	//����� ���� ����
	$stockFl				= '';	//��� ���� ����
	$soldOutFl				= '';	//ǰ�� ����
	$taxFreeFl				= '';	//���� ����
	$taxPercent				= '';	//������
	$onlyAdultFl			= '';	//�������� ��� ����
	$goodsState				= '';	//��ǰ����
	$mileageFl				= '';	//��ǰ�� ���ϸ��� ����
	$imgMagnify				= '';	//Ȯ�� �̹���
	$imgDetail				= '';	//�� �̹���
	$imgList				= '';	//����Ʈ �̹���
	$imgMain				= '';	//���� �̹���
	$imgAdd1				= '';	//
	$imgAdd2				= '';	//
	$goodsSearchWord		= '';	//�˻���
	$goodsDescription		= '';	//�󼼳���
	$goodsDescriptionMobile	= '';	//����� �� ����
	$shortDescription		= '';	//ª������
	$salesStartYmd			= '';	//��ǰ �ǸűⰣ ������
	$salesEndYmd			= '';	//��ǰ �ǸűⰣ ������
	$makeYmd				= '';	//������
	$launchYmd				= '';	//�����
	$modDt					= '';	//������
	$regDt					= '';	//�����
	//------------------------------------------------------
	
	$goodsCd = $goodsRow[$goodsCdChange];		// ��ǰ �ڵ�

    if($arrayGoodsDel[$goodsCd] != 'y') continue;

	if ($goodsNoChange != '') {
		$goodsNo = $goodsRow[$goodsNoChange];
		$db->query("Insert Into tmp_goodsno Set godo5GoodsNo = '" . $goodsNo ."', originalGoodsKey = '" . $goodsRow[$goodsNoChange] . "', regDt = now()");
	}
	else {
		$goodsNo++;
		$db->query("Insert Into tmp_goodsno Set godo5GoodsNo = '" . $goodsNo ."', originalGoodsKey = '" . $goodsRow[$goodsCdChange] . "', regDt = now()");
	}

	//-----------------------------------------------------------
	//- Advice - ��¥ ���� ������
	//- ���� �ʵ�
	//-----------------------------------------------------------
	if(trimPostRequest('regdtCnt') > 1){
		for ($i = 0; $i < count($regDtChange); $i++){
			$regDt .= $goodsRow[$regDtChange[$i]];
		}
	} else {
		if ($goodsRow[$regDtChange[0]]) {
			$regDt = $goodsRow[$regDtChange[0]];
		}
		else {
			$regDt = date('Y-m-d H:i:s');
		}
	}

	$regDt = dateCreate($regDt);

	if(trimPostRequest('salesStartYmdCnt') > 1){
		for ($i = 0; $i < count($salesStartYmdChange); $i++){
			$salesStartYmd .= $goodsRow[$salesStartYmdChange[$i]];
		}
	} else {
		if ($goodsRow[$salesStartYmdChange[0]]) {
			$salesStartYmd = $goodsRow[$salesStartYmdChange[0]];
		}
		else {
			$salesStartYmd = date('Y-m-d H:i:s');
		}
	}

	$salesStartYmd = dateCreate($salesStartYmd);

	if(trimPostRequest('salesEndYmdCnt') > 1){
		for ($i = 0; $i < count($salesEndYmdChange); $i++){
			$salesEndYmd .= $goodsRow[$salesEndYmdChange[$i]];
		}
	} else {
		if ($goodsRow[$salesEndYmdChange[0]]) {
			$salesEndYmd = $goodsRow[$salesEndYmdChange[0]];
		}
		else {
			$salesEndYmd = date('Y-m-d H:i:s');
		}
	}

	$salesEndYmd = dateCreate($salesEndYmd);

	if(trimPostRequest('modDtCnt') > 1){
		for ($i = 0; $i < count($modDtChange); $i++){
			$modDt .= $goodsRow[$modDtChange[$i]];
		}
	} else {
		if ($goodsRow[$modDtChange[0]]) {
			$modDt = $goodsRow[$modDtChange[0]];
		}
		else {
			$modDt = date('Y-m-d H:i:s');
		}
	}

	$modDt = dateCreate($modDt);


	if(trimPostRequest('makeYmdCnt') > 1){
		for ($i = 0; $i < count($makeYmdChange); $i++){
			$makeYmd .= $goodsRow[$makeYmdChange[$i]];
		}
	} else {
		if ($goodsRow[$makeYmdChange[0]]) {
			$makeYmd = $goodsRow[$makeYmdChange[0]];
		}
		else {
			$makeYmd = '';
		}
	}

	$makeYmd = dateCreate($makeYmd);
	$makeYmd = ($makeYmd) ?  date('Y-m-d', strtotime($makeYmd)) : '';

	if(trimPostRequest('launchYmdCnt') > 1){
		for ($i = 0; $i < count($launchYmdChange); $i++){
			$launchYmd .= $goodsRow[$launchYmdChange[$i]];
		}
	} else {
		if ($goodsRow[$launchYmdChange[0]]) {
			$launchYmd = $goodsRow[$launchYmdChange[0]];
		}
		else {
			$launchYmd = '';
		}
	}
	if ($launchYmd === '0000-00-00') {
		$launchYmd = '';
	}
	$launchYmd = dateCreate($launchYmd);
	$launchYmd = ($launchYmd) ?  date('Y-m-d', strtotime($launchYmd)) : '';
	
	if(trimPostRequest('modDtCnt') > 1){
		for ($i = 0; $i < count($modDtChange); $i++){
			$modDt .= $goodsRow[$modDtChange[$i]];
		}
	} else {
		if ($goodsRow[$modDtChange[0]]) {
			$modDt = $goodsRow[$modDtChange[0]];
		}
		else {
			$modDt = date('Y-m-d H:i:s');
		}
	}

	$modDt = dateCreate($modDt);

	$imagePath = date('y/m/d/', strtotime($regDt)) . $goodsNo . '/';
			
	$godo5GoodsImagePath	= $sFilePathNew . $imagePath;
	
	if (!is_dir($godo5GoodsImagePath)) {
		$arrayNewGoodsFilePath = explode('/', $godo5GoodsImagePath);
		$tempPath = "";
		for ($i = 0; $i <= count($arrayNewGoodsFilePath) - 1; $i++){
			if ($arrayNewGoodsFilePath[$i]) {
				$tempPath .= $arrayNewGoodsFilePath[$i] . '/';
				if ($arrayNewGoodsFilePath[$i] != '.') {
					$setFile->makeDir($tempPath);
				}
			}
		}
	}
	
	//-----------------------------------------------------------
	//- Advice - Ȯ��, �� �̹���, ª�� ����
	//- Ȯ��, �� �̹���, ª�� ������ ���� �ʵ�, ���÷��̽� ó�� ���
	//-----------------------------------------------------------
	$arrayImgMagnify = array();
	if(trimPostRequest('imgMagnifyCnt') > 1){
		for ($i = 0; $i < count($imgMagnifyChange); $i++){
			$tempImgMagnify = dataCntReplace($goodsRow[$imgMagnifyChange[$i]], $repimgMagnifyBefore, $repimgMagnifyAfter, $repIMCnt);
			if ($tempImgMagnify) {
				$arrayImgMagnify[] = $tempImgMagnify;
			}
		}
	} else {
		$tempImgMagnify = dataCntReplace($goodsRow[$imgMagnifyChange[0]], $repimgMagnifyBefore, $repimgMagnifyAfter, $repIMCnt);
		if ($tempImgMagnify) {
			$arrayImgMagnify[] = $tempImgMagnify;
		}
	}

	$arrayImgDetail = array();
	if(trimPostRequest('imgDetailCnt') > 1){
		for ($i = 0; $i < count($imgDetailChange); $i++){
			$tempImgDetail = dataCntReplace($goodsRow[$imgDetailChange[$i]], $repImgDetailBefore, $repImgDetailAfter, $repIDCnt);
			if ($tempImgDetail) {
				$arrayImgDetail[] = $tempImgDetail;
			}
		}
	} else {
		$tempImgDetail = dataCntReplace($goodsRow[$imgDetailChange[0]], $repImgDetailBefore, $repImgDetailAfter, $repIDCnt);
		if ($tempImgDetail) {
			$arrayImgDetail[] = $tempImgDetail;
		}
	}

	if(trimPostRequest('shortdescCnt') > 1){
		for ($i = 0; $i < count($shortDescriptionChange); $i++){
			$shortDescription .= $goodsRow[$shortDescriptionChange[$i]];
		}
	} else {
		$shortDescription = $goodsRow[$shortDescriptionChange[0]];
	}
	$shortDescription	= dataCntReplace($shortDescription, $repShortDescriptionBefore, $repShortDescriptionAfter, $repShortCnt);
	//-----------------------------------------------------------
	
	//-----------------------------------------------------------
	//- Advice - �����, ����Ʈ, ����Ʈ�׷�, ���� �̹���, �˻���, ��ǰ����
	//- �����, ����Ʈ, ����Ʈ�׷�, ���� �̹���, �˻���� ���� �ʵ�, ���÷��̽� ó�� ���
	//-----------------------------------------------------------
	$tempImgList = dataCntReplace($goodsRow[$imgListChange], $repImgListBefore, $repImgListAfter, $repImgListCnt);
	if ($tempImgList) {
		$imgList		= $tempImgList;
	}
	$tempImgMain = dataCntReplace($goodsRow[$imgMainChange], $repImgMainBefore, $repImgMainAfter, $repImgMainCnt);
	if ($tempImgMain) {
		$imgMain		= $tempImgMain;
	}
	$tempImgAdd1 = dataCntReplace($goodsRow[$imgAdd1Change], $repImgAdd1Before, $repImgAdd1After, $repImgAdd1Cnt);
	if ($tempImgAdd1) {
		$imgAdd1		= $tempImgAdd1;
	}
	$tempImgAdd2 = dataCntReplace($goodsRow[$imgAdd2Change], $repImgAdd2Before, $repImgAdd2After, $repImgAdd2Cnt);
	if ($tempImgAdd2) {
		$imgAdd2		= $tempImgAdd2;
	}
	$goodsSearchWord	= dataCntReplace($goodsRow[$goodsSearchWordChange], $repGoodsSearchWordBefore, $repGoodsSearchWordAfter, $repGoodsSearchWordCnt);
	$goodsState			= dataCntReplace($goodsRow[$goodsStateChange], $repGoodsStateBefore, $repGoodsStateAfter, $repGoodsStateCnt);
	//-----------------------------------------------------------
	
	//-----------------------------------------------------------
	//- Advice - ���� ����
	//-----------------------------------------------------------
	if ($fileCopyFl) {
		$newFileData = array();

		//-----------------------------------------------------------
		//- Advice - ��� ����
		//-----------------------------------------------------------
		$imgMagnifyPath = trimPostRequest('imgMagnifyPath');
		if ($imgMagnifyPath) {
			if (!trimPostRequest('fieldDataFl')) {
				$imgMagnifyPath = $sFilePathOrg . $imgMagnifyPath . '/';
			}
			else {
				$imgMagnifyPath = $sFilePathOrg . $goodsRow[$imgMagnifyPath] . '/';
			}
		}
		else {
			$imgMagnifyPath = $sFilePathOrg;
		}
		
		$imgDetailPath = trimPostRequest('imgDetailPath');
		if ($imgDetailPath) {
			if (!trimPostRequest('fieldDataFl')) {
				$imgDetailPath = $sFilePathOrg . $imgDetailPath . '/';
			}
			else {
				$imgDetailPath = $sFilePathOrg . $goodsRow[$imgDetailPath] . '/';
			}
		}
		else {
			$imgDetailPath = $sFilePathOrg;
		}
		
		$imgListPath = trimPostRequest('imgListPath');
		if ($imgListPath) {
			if (!trimPostRequest('fieldDataFl')) {
				$imgListPath = $sFilePathOrg . $imgListPath . '/';
			}
			else {
				$imgListPath = $sFilePathOrg . $goodsRow[$imgListPath] . '/';
			}
		}
		else {
			$imgListPath = $sFilePathOrg;
		}
		
		$imgMainPath = trimPostRequest('imgMainPath');
		if ($imgMainPath) {
			if (!trimPostRequest('fieldDataFl')) {
				$imgMainPath = $sFilePathOrg . $imgMainPath . '/';
			}
			else {
				$imgMainPath = $sFilePathOrg . $goodsRow[$imgMainPath] . '/';
			}
		}
		else {
			$imgMainPath = $sFilePathOrg;
		}
		
		$imgAdd1Path = trimPostRequest('imgAdd1Path');
		if ($imgAdd1Path) {
			if (!trimPostRequest('fieldDataFl')) {
				$imgAdd1Path = $sFilePathOrg . $imgAdd1Path . '/';
			}
			else {
				$imgAdd1Path = $sFilePathOrg . $goodsRow[$imgAdd1Path] . '/';
			}
		}
		else {
			$imgAdd1Path = $sFilePathOrg;
		}
		
		$imgAdd2Path = trimPostRequest('imgAdd2Path');
		if ($imgAdd2Path) {
			if (!trimPostRequest('fieldDataFl')) {
				$imgAdd2Path = $sFilePathOrg . $imgAdd2Path . '/';
			}
			else {
				$imgAdd2Path = $sFilePathOrg . $goodsRow[$imgAdd2Path] . '/';
			}
		}
		else {
			$imgAdd2Path = $sFilePathOrg;
		}
		//-----------------------------------------------------------
		
		//-----------------------------------------------------------
		//- Advice - ���� ���� ����
		//-----------------------------------------------------------
		$imageNo = 0;
		$magnifyNumber = 0;
		$oldImgMagnify = $arrayImgMagnify;
		$newImgMagnify = array();
		$copyResult = 1;
		for ($i = 0; $i <= count($oldImgMagnify) - 1; $i++) {// ÷���̹���
			$newFileData = array();
			if ($oldImgMagnify[$i] && !ereg('http://', $oldImgMagnify[$i])) {

				$oldFileName = $oldImgMagnify[$i];
				$newImgMagnify[$magnifyNumber] = fileRename($oldImgMagnify[$i], $goodsNo . '_Magnify' . $magnifyNumber);
				
				if (!trimPostRequest('file_rename_yn')) {
					if (!trimPostRequest('localCopy')) {
						$oldFileName = str_replace('%2F', '/', str_replace('+', '%20', urlencode($oldFileName)));
					}
					$sTmpOldPathMagnify = $imgMagnifyPath . $oldFileName;
					$sTmpNewPathMagnify = $godo5GoodsImagePath . $newImgMagnify[$magnifyNumber];
				
					$copyResult = $setFile->fileCopy($sTmpOldPathMagnify, $sTmpNewPathMagnify, trimPostRequest('fileCopyCheck'));
					if ($copyResult) {
						$fileExt = fileExtSet($sTmpNewPathMagnify);
						if ($fileExt) {
							$newImgMagnify[$magnifyNumber] .= '.' . $fileExt;
						}

						if (!$imgHostingDomain) {
							$sTmpNewPathMagnify		= $godo5GoodsImagePath . $newImgMagnify[$magnifyNumber];
							$sTmpThumbNewPath		= $godo5GoodsImagePath . 't50_' . $newImgMagnify[$magnifyNumber];

							$attachImgInfo = getImageSize($sTmpNewPathMagnify);
							$changeWidthSize = $attachImgInfo[0] * 70 / $attachImgInfo[1];
							$tempFileExt = explode('/', $attachImgInfo['mime']);
							$oriFileExt = $tempFileExt[1];

							$setFile->createThumbnail($sTmpNewPathMagnify, $sTmpThumbNewPath, $changeWidthSize, 70, $oriFileExt, trimPostRequest('fileCopyCheck'));
						}
					}
				}
			}
			else {
				$newImgMagnify[$magnifyNumber] = $oldImgMagnify[$i];
			}

			if ($newImgMagnify[$magnifyNumber]) {
				if($imgHostingDomain) {
					$newImgMagnify[$magnifyNumber] = 'http://' . $imgHostingDomain . str_replace('./','/',$godo5GoodsImagePath) . $newImgMagnify[$magnifyNumber];
				}
				$newFileData['goodsNo'] = $goodsNo;
				$newFileData['imageNo'] = $imageNo;
				$newFileData['imageKind'] = 'magnify';
				$newFileData['imageName'] = $newImgMagnify[$magnifyNumber];
				$newFileData['regDt'] = 'now()';

				$goodsImageInsertSet->querySet($newFileData, $fileInsertCount);
				$fileInsertCount++;
				$imageNo++;
			}

			$magnifyNumber++;
		}
		
		$imageNo = 0;
		$detailNumber = 0;
		$oldImgDetail = $arrayImgDetail;
		$newImgDetail = array();
		$copyResult = 1;
		for ($i = 0; $i <= count($oldImgDetail) - 1; $i++) {// ÷���̹���
			$newFileData = array();
			if ($oldImgDetail[$i] && !ereg('http://', $oldImgDetail[$i])) {

				$oldFileName = $oldImgDetail[$i];
				$newImgDetail[$detailNumber] = fileRename($oldImgDetail[$i], $goodsNo . '_Detail' . $detailNumber);

				if (!trimPostRequest('file_rename_yn')) {
					if (!trimPostRequest('localCopy')) {
						$oldFileName = str_replace('%2F', '/', str_replace('+', '%20', urlencode($oldFileName)));
					}
					$sTmpOldPathDetail = $imgDetailPath . $oldFileName;
					$sTmpNewPathDetail = $godo5GoodsImagePath . $newImgDetail[$detailNumber];
			
			
					$copyResult = $setFile->fileCopy($sTmpOldPathDetail, $sTmpNewPathDetail, trimPostRequest('fileCopyCheck'));
					if ($copyResult) {
						$fileExt = fileExtSet($sTmpNewPathDetail);
						if ($fileExt) {
							$newImgDetail[$detailNumber] .= '.' . $fileExt;
						}

						if (!$imgHostingDomain) {
							$sTmpNewPathDetail		= $godo5GoodsImagePath . $newImgDetail[$detailNumber];
							$sTmpThumbNewPath		= $godo5GoodsImagePath . 't50_' . $newImgDetail[$detailNumber];

							$attachImgInfo = getImageSize($sTmpNewPathDetail);
							$changeWidthSize = $attachImgInfo[0] * 70 / $attachImgInfo[1];
							$tempFileExt = explode('/', $attachImgInfo['mime']);
							$oriFileExt = $tempFileExt[1];

							$setFile->createThumbnail($sTmpNewPathDetail, $sTmpThumbNewPath, $changeWidthSize, 70, $oriFileExt, trimPostRequest('fileCopyCheck'));
						}
					}
				}
				
				
			} else {
				$newImgDetail[$detailNumber] = $oldImgDetail[$i];
			}

			if ($newImgDetail[$detailNumber]) {
				if($imgHostingDomain) {
					$newImgDetail[$detailNumber] = 'http://' . $imgHostingDomain . str_replace('./','/',$godo5GoodsImagePath) . $newImgDetail[$detailNumber];
				}
				$newFileData['goodsNo'] = $goodsNo;
				$newFileData['imageNo'] = $imageNo;
				$newFileData['imageKind'] = 'detail';
				$newFileData['imageName'] = $newImgDetail[$detailNumber];
				$newFileData['regDt'] = 'now()';

				$goodsImageInsertSet->querySet($newFileData, $fileInsertCount);
				$fileInsertCount++;
				$imageNo++;
			}
			$detailNumber++;
		}

		$newFileData = array();
		$copyResult = 1;
		if ($imgList && !ereg('http://', $imgList)) {
			$oldFileName = $imgList;
			$imgList = fileRename($imgList, $goodsNo . '_List');
			if (!trimPostRequest('file_rename_yn')) {
				if (!trimPostRequest('localCopy')) {
					$oldFileName = str_replace('%2F', '/', str_replace('+', '%20', urlencode($oldFileName)));
				}
				$sTmpOldPathList = $imgListPath . $oldFileName;
				$sTmpNewPathList = $godo5GoodsImagePath . $imgList;
				
				$copyResult = $setFile->fileCopy($sTmpOldPathList, $sTmpNewPathList, trimPostRequest('fileCopyCheck'));

				
				if ($copyResult) {
					$fileExt = fileExtSet($sTmpNewPathList);
					if ($fileExt) {
						$imgList .= '.' . $fileExt;
					}
					if (!$imgHostingDomain) {
						$sTmpNewPathList		= $godo5GoodsImagePath . $imgList;
						$sTmpThumbNewPath		= $godo5GoodsImagePath . 't50_' . $imgList;

						$attachImgInfo = getImageSize($sTmpNewPathList);
						$changeWidthSize = $attachImgInfo[0] * 70 / $attachImgInfo[1];
						$tempFileExt = explode('/', $attachImgInfo['mime']);
						$oriFileExt = $tempFileExt[1];

						$setFile->createThumbnail($sTmpNewPathList, $sTmpThumbNewPath, $changeWidthSize, 70, $oriFileExt, trimPostRequest('fileCopyCheck'));
					}
				}
			}
		}
		
		if ($imgList) {
			if($imgHostingDomain) {
				$imgList = 'http://' . $imgHostingDomain . str_replace('./','/',$godo5GoodsImagePath) . $imgList;
			}
			$newFileData['goodsNo'] = $goodsNo;
			$newFileData['imageNo'] = '0';
			$newFileData['imageKind'] = 'list';
			$newFileData['imageName'] = $imgList;
			$newFileData['regDt'] = 'now()';
			$goodsImageInsertSet->querySet($newFileData, $fileInsertCount);
			$fileInsertCount++;
		}
		
		$newFileData = array();
		$copyResult = 1;
		if ($imgMain && !ereg('http://', $imgMain)) {
			$oldFileName = $imgMain;
			$imgMain = fileRename($imgMain, $goodsNo . '_Main');
			if (!trimPostRequest('file_rename_yn')) {
				if (!trimPostRequest('localCopy')) {
					$oldFileName = str_replace('%2F', '/', str_replace('+', '%20', urlencode($oldFileName)));
				}
				$sTmpOldPathMain = $imgMainPath . $oldFileName;
				$sTmpNewPathMain = $godo5GoodsImagePath . $imgMain;
				
				$copyResult = $setFile->fileCopy($sTmpOldPathMain, $sTmpNewPathMain, trimPostRequest('fileCopyCheck'));
				if ($copyResult) {
					$fileExt = fileExtSet($sTmpNewPathMain);
					if ($fileExt) {
						$imgMain .= '.' . $fileExt;
					}
					if (!$imgHostingDomain) {
						$sTmpNewPathMain		= $godo5GoodsImagePath . $imgMain;
						$sTmpThumbNewPath		= $godo5GoodsImagePath . 't50_' . $imgMain;

						$attachImgInfo = getImageSize($sTmpNewPathMain);
						$changeWidthSize = $attachImgInfo[0] * 70 / $attachImgInfo[1];
						$tempFileExt = explode('/', $attachImgInfo['mime']);
						$oriFileExt = $tempFileExt[1];

						$setFile->createThumbnail($sTmpNewPathMain, $sTmpThumbNewPath, $changeWidthSize, 70, $oriFileExt, trimPostRequest('fileCopyCheck'));
					}
				}
			}
		}
		
		if ($imgMain) {
			if($imgHostingDomain) {
				$imgMain = 'http://' . $imgHostingDomain . str_replace('./','/',$godo5GoodsImagePath) . $imgMain;
			}
			$newFileData['goodsNo'] = $goodsNo;
			$newFileData['imageNo'] = '0';
			$newFileData['imageKind'] = 'main';
			$newFileData['imageName'] = $imgMain;
			$newFileData['regDt'] = 'now()';
			$goodsImageInsertSet->querySet($newFileData, $fileInsertCount);
			$fileInsertCount++;
		}
		
		$newFileData = array();
		$copyResult = 1;
		if ($imgAdd1 && !ereg('http://', $imgAdd1)) {
			$oldFileName = $imgAdd1;
			$imgAdd1 = fileRename($imgAdd1, $goodsNo . '_Add1');
			if (!trimPostRequest('file_rename_yn')) {
				if (!trimPostRequest('localCopy')) {
					$oldFileName = str_replace('%2F', '/', str_replace('+', '%20', urlencode($oldFileName)));
				}
				$sTmpOldPathAdd1 = $imgAdd1Path . $oldFileName;
				$sTmpNewPathAdd1 = $godo5GoodsImagePath . $imgAdd1;
				
				$copyResult = $setFile->fileCopy($sTmpOldPathAdd1, $sTmpNewPathAdd1, trimPostRequest('fileCopyCheck'));
				if ($copyResult) {
					$fileExt = fileExtSet($sTmpOldPathAdd1);
					if ($fileExt) {
						$imgAdd1 .= '.' . $fileExt;
					}

					if (!$imgHostingDomain) {
						$sTmpNewPathAdd1		= $godo5GoodsImagePath . $imgAdd1;
						$sTmpThumbNewPath		= $godo5GoodsImagePath . 't50_' . $imgAdd1;

						$attachImgInfo = getImageSize($sTmpNewPathAdd1);
						$changeWidthSize = $attachImgInfo[0] * 70 / $attachImgInfo[1];
						$tempFileExt = explode('/', $attachImgInfo['mime']);
						$oriFileExt = $tempFileExt[1];

						$setFile->createThumbnail($sTmpNewPathAdd1, $sTmpThumbNewPath, $changeWidthSize, 70, $oriFileExt, trimPostRequest('fileCopyCheck'));
					}
				}
			}
		}
		
		if ($imgAdd1) {
			if($imgHostingDomain) {
				$imgAdd1 = 'http://' . $imgHostingDomain . str_replace('./','/',$godo5GoodsImagePath) . $imgAdd1;
			}
			$newFileData['goodsNo'] = $goodsNo;
			$newFileData['imageNo'] = '0';
			$newFileData['imageKind'] = 'add1';
			$newFileData['imageName'] = $imgAdd1;
			$newFileData['regDt'] = 'now()';
			$goodsImageInsertSet->querySet($newFileData, $fileInsertCount);
			$fileInsertCount++;
		}
		
		$newFileData = array();
		$copyResult = 1;
		if ($imgAdd2 && !ereg('http://', $imgAdd2)) {
			$oldFileName = $imgAdd2;
			$imgAdd2 = fileRename($imgAdd2, $goodsNo . '_Add2');
			if (!trimPostRequest('file_rename_yn')) {
				if (!trimPostRequest('localCopy')) {
					$oldFileName = str_replace('%2F', '/', str_replace('+', '%20', urlencode($oldFileName)));
				}
				$sTmpOldPathAdd2 = $imgAdd2Path . $oldFileName;
				$sTmpNewPathAdd2 = $godo5GoodsImagePath . $imgAdd2;
				
				$copyResult = $setFile->fileCopy($sTmpOldPathAdd2, $sTmpNewPathAdd2, trimPostRequest('fileCopyCheck'));
				if ($copyResult) {
					$fileExt = fileExtSet($sTmpNewPathMain);
					if ($fileExt) {
						$imgAdd2 .= '.' . $fileExt;
					}

					if (!$imgHostingDomain) {
						$sTmpOldPathAdd2		= $godo5GoodsImagePath . $imgAdd2;
						$sTmpThumbNewPath		= $godo5GoodsImagePath . 't50_' . $imgAdd2;

						$attachImgInfo = getImageSize($sTmpOldPathAdd2);
						$changeWidthSize = $attachImgInfo[0] * 70 / $attachImgInfo[1];
						$tempFileExt = explode('/', $attachImgInfo['mime']);
						$oriFileExt = $tempFileExt[1];

						$setFile->createThumbnail($sTmpOldPathAdd2, $sTmpThumbNewPath, $changeWidthSize, 70, $oriFileExt, trimPostRequest('fileCopyCheck'));
					}
				}
			}
		}
		
		if ($imgAdd2) {
			if(trimPostRequest('imgHostingDomain')) {
				$imgAdd2 = 'http://' . trimPostRequest('imgHostingDomain') . str_replace('./','/',$godo5GoodsImagePath) . $imgAdd2;
			}
			$newFileData['goodsNo'] = $goodsNo;
			$newFileData['imageNo'] = '0';
			$newFileData['imageKind'] = 'add2';
			$newFileData['imageName'] = $imgAdd2;
			$newFileData['regDt'] = 'now()';
			$goodsImageInsertSet->querySet($newFileData, $fileInsertCount);
			$fileInsertCount++;
		}
		//-----------------------------------------------------------
	}
	//-----------------------------------------------------------
	else {
		$newFileData = array();
		$imageNo = 0;
		$magnifyNumber = 0;
		$oldImgMagnify = $arrayImgMagnify;
		$newImgMagnify = array();
		for ($i = 0; $i <= count($oldImgMagnify) - 1; $i++) {// ÷���̹���
			$oldFileName = $oldImgMagnify[$i];
			$newImgMagnify[$magnifyNumber] = $oldImgMagnify[$i];
			
			if ($oldFileName) {
				$newFileData['goodsNo'] = $goodsNo;
				$newFileData['imageNo'] = $imageNo;
				$newFileData['imageKind'] = 'magnify';
				$newFileData['imageName'] = $oldFileName;
				$newFileData['regDt'] = 'now()';
				$goodsImageInsertSet->querySet($newFileData, $fileInsertCount);
				$fileInsertCount++;
				$imageNo++;
			}
			$magnifyNumber++;
		}
		
		$newFileData = array();
		$imageNo = 0;
		$detailNumber = 0;
		$oldImgDetail = $arrayImgDetail;
		$newImgDetail = array();
		for ($i = 0; $i <= count($oldImgDetail) - 1; $i++) {// ÷���̹���
			$oldFileName = $oldImgDetail[$i];

			if ($oldFileName) {
				$newFileData['goodsNo'] = $goodsNo;
				$newFileData['imageNo'] = $imageNo;
				$newFileData['imageKind'] = 'detail';
				$newFileData['imageName'] = $oldFileName;
				$newFileData['regDt'] = 'now()';
				$goodsImageInsertSet->querySet($newFileData, $fileInsertCount);
				$fileInsertCount++;
				$imageNo++;
			}
			$detailNumber++;
		}
		
		$newFileData = array();
		$oldFileName = $imgList;
		if ($oldFileName) {
			$newFileData['goodsNo'] = $goodsNo;
			$newFileData['imageNo'] = '0';
			$newFileData['imageKind'] = 'list';
			$newFileData['imageName'] = $oldFileName;
			$newFileData['regDt'] = 'now()';
			$goodsImageInsertSet->querySet($newFileData, $fileInsertCount);
			$fileInsertCount++;
		}
		
		$newFileData = array();
		$oldFileName = $imgMain;
		if ($oldFileName) {
			$newFileData['goodsNo'] = $goodsNo;
			$newFileData['imageNo'] = '0';
			$newFileData['imageKind'] = 'main';
			$newFileData['imageName'] = $oldFileName;
			$newFileData['regDt'] = 'now()';
			$goodsImageInsertSet->querySet($newFileData, $fileInsertCount);
			$fileInsertCount++;
		}
		
		$newFileData = array();
		$oldFileName = $imgAdd1;
		if ($oldFileName) {
			$newFileData['goodsNo'] = $goodsNo;
			$newFileData['imageNo'] = '0';
			$newFileData['imageKind'] = 'add1';
			$newFileData['imageName'] = $oldFileName;
			$newFileData['regDt'] = 'now()';
			$goodsImageInsertSet->querySet($newFileData, $fileInsertCount);
			$fileInsertCount++;
		}
		
		$newFileData = array();
		$oldFileName = $imgAdd2;
		if ($oldFileName) {
			$newFileData['goodsNo'] = $goodsNo;
			$newFileData['imageNo'] = '0';
			$newFileData['imageKind'] = 'add2';
			$newFileData['imageName'] = $oldFileName;
			$newFileData['regDt'] = 'now()';
			$goodsImageInsertSet->querySet($newFileData, $fileInsertCount);
			$fileInsertCount++;
		}
	}
	
	//-----------------------------------------------------------
	//- Advice - ����, ����� �󼼳��� �� �ۼ� �̹��� ���� ���
	//- ������ ���� �ʵ�, ���÷��̽� ó�� ���(*ī��24 ����)
	//-----------------------------------------------------------
	
	if(trimPostRequest('goodsDescriptionCnt') > 1){
		for ($i = 0; $i < count($goodsDescriptionChange); $i++){
			$goodsDescription .= '<div>' . $goodsRow[$goodsDescriptionChange[$i]] . '</div>';
		}
	} else {
		$goodsDescription = $goodsRow[$goodsDescriptionChange[0]];
	}
	
	if(trimPostRequest('goodsDescriptionMobileCnt') > 1){
		for ($i = 0; $i < count($goodsDescriptionMobileChange); $i++){
			$goodsDescriptionMobile .= '<div>' . $goodsRow[$goodsDescriptionMobileChange[$i]] . '</div>';
		}
	} else {
		$goodsDescriptionMobile = $goodsRow[$goodsDescriptionMobileChange[0]];
	}
	/*
	//(*ī��24 ����)
	if ($arrayGoodsDesc[$goodsRow[$goodsNoChange[0]]]) {
		foreach ($arrayGoodsDesc[$goodsRow[$goodsNoChange[0]]] as $description) {
			$goodsDescription .= '<div>' . $description[1] . '</div>';
			$shortDescription .= $description[12];
		}
	}
	*/
	
	//-----------------------------------------------------------

	$goodsDescription = stripslashes($goodsDescription);
	$goodsDescription = dataCntReplace($goodsDescription, $repGoodsDescriptionBefore, $repGoodsDescriptionAfter, $repGoodsDescriptionCnt);
	
	$goodsDescriptionMobile = dataCntReplace($goodsDescriptionMobile, $repGoodsDescriptionMobileBefore, $repGoodsDescriptionMobileAfter, $repGoodsDescriptionMobileCnt);
	$goodsDescriptionMobile = str_replace('\"', '', $goodsDescriptionMobile);

	$goodsDescriptionSameFl = ($goodsDescriptionMobile == '') ? 'y' : 'n';

	if ($editorFileCopyFl == 'Y') {
		$goodsDescription		= $setFile->editorCopy($goodsDescription, $goodsNo . '_goodsEditor_', trimPostRequest('editorFileCopyCheck'));
		$goodsDescriptionMobile	= $setFile->editorCopy($goodsDescriptionMobile, $goodsNo . '_goodsMobileEditor_', trimPostRequest('editorFileCopyCheck'));

		if ($imgHostingDomain) {
			$goodsDescription = preg_replace("/(src|SRC)(\"|'|=\"|='|=)(\/data\/)/i", '$1' . '$2' . 'http://' . $imgHostingDomain . '$3', $goodsDescription);
			$goodsDescriptionMobile = preg_replace("/(src|SRC)(\"|'|=\"|='|=)(\/data\/)/i", '$1' . '$2' . 'http://' . $imgHostingDomain . '$3', $goodsDescriptionMobile);
		}
	}

	$arrayGoodsLink = array();
	$brandCd = '';
	if ($goodsRow[$brandCdChange]) {
		list($arrayBrandQuery, $brandCd) = setCategoryBrand(array($goodsRow[$brandCdChange]), 'brand');
	}
	$goodsNm		= strip_tags($goodsRow[$goodsNmChange]);		// ��ǰ��
	$goodsPriceString	= $goodsRow[$goodsPriceStringChange];	// �ǸŰ� ��ü����
	$memo			= $goodsRow[$memoChange];		// �޸�
	$makerNm		= $goodsRow[$makerNmChange];		// ������
	$originNm		= $goodsRow[$originNmChange];		// ������
	$goodsModelNo	= $goodsRow[$goodsModelNoChange];	// �𵨸�
	$goodsWeight	= $goodsRow[$goodsWeightChange];		// ����
	$minOrderCnt	= $goodsRow[$minOrderCntChange];		// �ּұ��ż���
	$maxOrderCnt	= $goodsRow[$maxOrderCntChange];		// �ִ뱸�ż���
	$taxPercent		= $goodsRow[$taxPercentChange];		// ������
	
	$goodsSellFl			= ($goodsSellFlChange) ? flagChange('yn', $goodsRow[$goodsSellFlChange]) : 'y';	// PC �Ǹſ���
	$goodsSellMobileFl		= ($goodsSellMobileFlChange) ? flagChange('yn', $goodsRow[$goodsSellMobileFlChange]) : 'y';	// ����� �Ǹſ���
	$goodsDisplayFl			= ($goodsDisplayFlChange) ? flagChange('yn', $goodsRow[$goodsDisplayFlChange]) : 'y';	// ��������
	$goodsDisplayMobileFl	= ($goodsDisplayMobileFlChange) ? flagChange('yn', $goodsRow[$goodsDisplayMobileFlChange]) : 'y';	// ����� ���� ����
	$stockFl				= ($stockFlChange) ? flagChange('yn', $goodsRow[$stockFlChange]) : 'n';		// ��� ���� ����
	$soldOutFl				= ($soldOutFlChange) ? flagChange('yn', $goodsRow[$soldOutFlChange]) : 'n';	// ǰ�� ����
	$taxFreeFl				= ($taxFreeFlChange) ? flagChange('tf', $goodsRow[$taxFreeFlChange]) : 't';	// �ΰ��� ���� ����
	$mileageFl				= ($mileageFlChange) ? flagChange('cg', $goodsRow[$mileageFlChange]) : 'c';	// ��ǰ�� ������ ��� ����
	$onlyAdultFl			= ($onlyAdultFlChange) ? flagChange('yn', $goodsRow[$onlyAdultFlChange]) : 'n';

	$scmNo = 1;
	if ($scmNoChange) {
		$scmNo = ($arrayScmNo[$goodsRow[$scmNoChange]]) ? $arrayScmNo[$goodsRow[$scmNoChange]] : 1;
	}

	$deliverySno = 2;
	if ($scmNo !== 1) {
		$deliverySno = $arrayDeliveryNo[$scmNo];
	}
	
	//-----------------------------------------------------------
	// ���� ��� ��� ������ ��ġ �����Ŵٸ� './goodsRoopSource.php' �ҽ����� ���� �Ͽ� �۾� �Ͽ� �ֽñ� �ٶ��ϴ�.
	include './goodsRoopSource.php';	// �ɼ� �� �ǸŰ� �߰�����, ��ǰ ���� ��� �� ���� ���� ������
	
	if ($mode === "start_q") {
		if (trimPostRequest('queryRoopLimit') == $dataCnt) {
			$queryPrintCount = 1;
			$arrayQueryPostData = $insertSet->getQuery($arrayQueryPostData);
			$arrayQueryPostData = $goodsImageInsertSet->getQuery($arrayQueryPostData);
			$arrayQueryPostData = $optionInsertSet->getQuery($arrayQueryPostData);
			$arrayQueryPostData = $extraInfoInsertSet->getQuery($arrayQueryPostData);
			
			foreach ($arrayQueryPostData as $printQuery) {
				debug($queryPrintCount . " : " . $printQuery);
				$queryPrintCount++;
			}
			echo '<div>�۾� �Ϸ� �� : ' . number_format($dataCnt) . '��</div><script type="text/javascript">parent.configSubmitComplete("' . $dataCnt . '");</script>';
			exit;
		}
	}
	else if ($mode === "start") {
		if ((($dataCnt + 1) % 1000) == 0) {
			$arrayQueryPostData = $insertSet->getQuery($arrayQueryPostData);
			$arrayQueryPostData = $goodsImageInsertSet->getQuery($arrayQueryPostData);
			$arrayQueryPostData = $optionInsertSet->getQuery($arrayQueryPostData);
			$arrayQueryPostData = $extraInfoInsertSet->getQuery($arrayQueryPostData);
			dumpSqlFileSet ($dumpFileName, $arrayQueryPostData);
			unset($arrayQueryPostData);
			$arrayQueryPostData = array();
		}
	}

	$dataCnt++;
}

$arrayQueryPostData = $insertSet->getQuery($arrayQueryPostData);
$arrayQueryPostData = $goodsImageInsertSet->getQuery($arrayQueryPostData);
$arrayQueryPostData = $optionInsertSet->getQuery($arrayQueryPostData);
$arrayQueryPostData = $extraInfoInsertSet->getQuery($arrayQueryPostData);

if ($mode === "start") {
	$arrayQueryPostData[] = "truncate table es_categoryGoods";
	$insertCategoryResult = $db->query("Select * From es_categoryGoods Order By sno");
	while ($insertCategoryRow = $db->fetch($insertCategoryResult, 1)) {
		$arrayString = array();
	
		foreach ($insertCategoryRow as $fieldName => $value) {
			$arrayString[] = "$fieldName = '" . addslashes($value) . "'";
		}
		$arrayQueryPostData[] = "Insert Into es_categoryGoods set " . implode(" , ", $arrayString);
	}
	$arrayQueryPostData[] = "truncate table es_categoryBrand";
	$insertBrandResult = $db->query("Select * From es_categoryBrand Order By sno");
	while ($insertBrandRow = $db->fetch($insertBrandResult, 1)) {
		$arrayString = array();
	
		foreach ($insertBrandRow as $fieldName => $value) {
			$arrayString[] = "$fieldName = '" . addslashes($value) . "'";
		}
		$arrayQueryPostData[] = "Insert Into es_categoryBrand Set " . implode(" , ", $arrayString);
	}

	$arrayQueryPostData[] = "truncate table es_addGoods";
	$insertAddGoodsResult = $db->query("Select * From es_addgoods Order By addGoodsNo");
	while ($insertAddGoodsRow = $db->fetch($insertAddGoodsResult, 1)) {
		$arrayString = array();
	
		foreach ($insertAddGoodsRow as $fieldName => $value) {
			$arrayString[] = "$fieldName = '" . addslashes($value) . "'";
		}
		$arrayQueryPostData[] = "Insert Into es_addGoods Set " . implode(" , ", $arrayString);
	}
	
	/*
	$arrayQueryPostData[] = "truncate table es_scmManage";
	$insertBrandResult = $db->query("Select * From es_scmManage Order By scmNo");
	while ($insertBrandRow = $db->fetch($insertBrandResult, 1)) {
		$arrayString = array();
	
		foreach ($insertBrandRow as $fieldName => $value) {
			$arrayString[] = "$fieldName = '" . addslashes($value) . "'";
		}
		$arrayQueryPostData[] = "Insert Into es_scmManage Set " . implode(" , ", $arrayString);
	}

	$arrayQueryPostData[] = "truncate table es_scmDeliveryBasic";
	$insertBrandResult = $db->query("Select * From es_scmDeliveryBasic Order By sno");
	while ($insertBrandRow = $db->fetch($insertBrandResult, 1)) {
		$arrayString = array();
	
		foreach ($insertBrandRow as $fieldName => $value) {
			$arrayString[] = "$fieldName = '" . addslashes($value) . "'";
		}
		$arrayQueryPostData[] = "Insert Into es_scmDeliveryBasic Set " . implode(" , ", $arrayString);
	}
	*/
	
	$arrayQueryPostData[] = 'Insert Into es_goodsSearch (`goodsNo`, `goodsNm`, `goodsDisplayFl`, `goodsDisplayMobileFl`, `goodsSellFl`, `goodsSellMobileFl`, `scmNo`, `purchaseNo`, `applyFl`, `applyType`, `goodsCd`, `cateCd`, `goodsSearchWord`, `goodsOpenDt`, `goodsColor`, `brandCd`, `makerNm`, `originNm`, `goodsModelNo`, `totalStock`, `stockFl`, `soldOutFl`, `mileageFl`, `mileageGoods`, `goodsPrice`, `optionFl`, `optionTextFl`, `addGoodsFl`, `deliverySno`, `goodsIconCdPeriod`, `goodsIconCd`, `naverFl`, `orderCnt`, `hitCnt`, `reviewCnt`, `delFl`, `regDt`, `modDt`) Select `goodsNo`, `goodsNm`, `goodsDisplayFl`, `goodsDisplayMobileFl`, `goodsSellFl`, `goodsSellMobileFl`, `scmNo`, `purchaseNo`, `applyFl`, `applyType`, `goodsCd`, `cateCd`, `goodsSearchWord`, `goodsOpenDt`, `goodsColor`, `brandCd`, `makerNm`, `originNm`, `goodsModelNo`, `totalStock`, `stockFl`, `soldOutFl`, `mileageFl`, `mileageGoods`, `goodsPrice`, `optionFl`, `optionTextFl`, `addGoodsFl`, `deliverySno`, `goodsIconCdPeriod`, `goodsIconCd`, `naverFl`, `orderCnt`, `hitCnt`, `reviewCnt`, `delFl`, `regDt`, `modDt` From es_goods order by goodsNo;';
	$arrayQueryPostData[] = "OPTIMIZE TABLE es_goods";
	$arrayQueryPostData[] = "OPTIMIZE TABLE es_goodsAddInfo";
	$arrayQueryPostData[] = "OPTIMIZE TABLE es_goodsImage";
	$arrayQueryPostData[] = "OPTIMIZE TABLE es_goodsLinkBrand";
	$arrayQueryPostData[] = "OPTIMIZE TABLE es_goodsLinkCategory";
	$arrayQueryPostData[] = "OPTIMIZE TABLE es_categoryBrand";
	$arrayQueryPostData[] = "OPTIMIZE TABLE es_goodsMustInfo";
	$arrayQueryPostData[] = "OPTIMIZE TABLE es_categoryGoods";
	$arrayQueryPostData[] = "OPTIMIZE TABLE es_goodsOption";
	$arrayQueryPostData[] = "OPTIMIZE TABLE es_goodsOptionIcon";
	$arrayQueryPostData[] = "OPTIMIZE TABLE es_goodsOptionText";
	$arrayQueryPostData[] = "OPTIMIZE TABLE es_goodsSaleStatistics";
	$arrayQueryPostData[] = "OPTIMIZE TABLE es_goodsUpdateNaver";
	$arrayQueryPostData[] = "OPTIMIZE TABLE es_logAddGoods";
	$arrayQueryPostData[] = "OPTIMIZE TABLE es_logGoods";
	$arrayQueryPostData[] = "OPTIMIZE TABLE es_goodsSearch";
	/*
	$arrayQueryPostData[] = "OPTIMIZE TABLE es_scmManage";
	$arrayQueryPostData[] = "OPTIMIZE TABLE es_scmDeliveryBasic";
	*/


	$newGoodsNoTableCreateResult = $db->query("show create table tmp_goodsno");
	$newGoodsNoTableCreateRow = $db->fetch($newGoodsNoTableCreateResult);
	$arrayQueryPostData[] = 'DROP TABLE IF EXISTS tmp_goodsno;';
	$arrayQueryPostData[] = $newGoodsNoTableCreateRow['Create Table'] . ';';
	
	$selectGoodsNoResult = $db->query("Select * From tmp_goodsno Order By sno");
	while ($selectNewGoodsNoRow = $db->fetch($selectGoodsNoResult, 1)) {
		$arrayString = array();

		foreach ($selectNewGoodsNoRow as $fieldName => $value) {
			$arrayString[] = "$fieldName = '" . addslashes($value) . "'";
		}
		$arrayQueryPostData[] = "Insert Into tmp_goodsno Set " . implode(" , ", $arrayString) . ";";
	}
	$db->query( "Optimize Table tmp_goodsno;");
	$arrayQueryPostData[]	= 'Optimize Table tmp_goodsno;';

	dumpSqlFileSet ($dumpFileName, $arrayQueryPostData);
}
else if ($mode === "start_q") {
	foreach ($arrayQueryPostData as $printQuery) {
		debug($queryPrintCount . " : " . $printQuery);
		$queryPrintCount++;
	}
}
echo '<div>�۾� �Ϸ� �� : ' . number_format($dataCnt) . '��</div><script type="text/javascript">parent.configSubmitComplete("' . $dataCnt . '");</script>';
?>