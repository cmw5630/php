<?
include '../../inc/header.php';

$insertSet				= new insertSet('es_goods', trimPostRequest('insertMode'));
$optionInsertSet		= new insertSet('es_goodsOption', trimPostRequest('insertMode'));
$goodsImageInsertSet	= new insertSet('es_goodsImage', trimPostRequest('insertMode'));
$extraInfoInsertSet		= new insertSet('es_goodsAddInfo', trimPostRequest('insertMode'));
$arrayQueryPostData		= array();		// 생성 쿼리 저장 배열

$arrayGoodsLinkSort				= array(); // 카테고리 상품 연결 정렬 변수
$sFilePathNew = $sourcePath . '/data/goods/';
$sFilePathOrg = '';						// 기존 복사 경로 설정
$imgHostingDomain = trimPostRequest('imgHostingDomain'); // 복사 후 이미지 호스팅 경로

//------------------------------------------------------
// - Advice - 상품 복사 기능 사용시 복사 준비
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
// - Advice - 상품 테이블 이전 전 필드명
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
// - Advice - 상품 이전 필드 전역 변수
//------------------------------------------------------
$goodsNoChange				= trimPostRequest('goodsNo_change');		//일련번호
$goodsCdChange				= trimPostRequest('goodsCd_change');		//상품코드
$goodsNmChange				= trimPostRequest('goodsNm_change');		//상품명
$goodsPriceStringChange		= trimPostRequest('goodsPriceString_change');	//판매가 대체 문구
$memoChange					= trimPostRequest('memo_change');			//관리자 메모
$makerNmChange				= trimPostRequest('makerNm_change');		//제조사
$originNmChange				= trimPostRequest('originNm_change');		//원산지
$brandCdChange				= trimPostRequest('brandCd_change');		//브랜드
$goodsModelNoChange			= trimPostRequest('goodsModelNo_change');	//모델명
$goodsWeightChange			= trimPostRequest('goodsWeight_change');	//상품무게
$minOrderCntChange			= trimPostRequest('minOrderCnt_change');	//최소 구매 수량
$maxOrderCntChange			= trimPostRequest('maxOrderCnt_change');	//최대 구매 수량
$goodsSellFlChange			= trimPostRequest('goodsSellFl_change');	//PC 판매 여부
$goodsSellMobileFlChange	= trimPostRequest('goodsSellMobileFl_change');	//모바일 판매 여부
$goodsDisplayFlChange		= trimPostRequest('goodsDisplayFl_change');	//PC 노출 여부
$goodsDisplayMobileFlChange	= trimPostRequest('goodsDisplayMobileFl_change');	//모바일 노출 여부
$stockFlChange				= trimPostRequest('stockFl_change');		//재고량 연동 여부
$soldOutFlChange			= trimPostRequest('soldOutFl_change');		//품절 여부
$taxFreeFlChange			= trimPostRequest('taxFreeFl_change');		//과세 여부
$taxPercentChange			= trimPostRequest('taxPercent_change');		//과세율
$onlyAdultFlChange			= trimPostRequest('onlyAdultFl_change');	//성인인증 사용 여부
$mileageFlChange			= trimPostRequest('mileageFl_change');		//상품별 마일리지 여부
$imgMagnifyChange			= trimPostRequest('imgMagnify');			//확대 이미지
$imgDetailChange			= trimPostRequest('imgDetail');			//상세 이미지
$imgListChange				= trimPostRequest('imgList');				//썸네일 이미지
$imgMainChange				= trimPostRequest('imgMain');				//메인 이미지
$imgAdd1Change				= trimPostRequest('imgAdd1');				//리스트 그룹형 이미지
$imgAdd2Change				= trimPostRequest('imgAdd2');				//심플 이미지
$goodsSearchWordChange		= trimPostRequest('goodsSearchWord_change');	//검색어
$goodsStateChange			= trimPostRequest('goodsState_change');		//상품 상태
$goodsDescriptionChange		= trimPostRequest('goodsDescription');		//상세내용
$goodsDescriptionMobileChange	= trimPostRequest('goodsDescriptionMobile');	//모바일 상세 내용
$shortDescriptionChange		= trimPostRequest('shortDescription');		//짧은설명
$scmNoChange				= trimPostRequest('scmNo');					//공급사 매칭정보
$salesStartYmdChange		= trimPostRequest('salesStartYmd');			//상품 판매기간 시작일
$salesEndYmdChange			= trimPostRequest('salesEndYmd');			//상품 판매기간 종료일
$makeYmdChange				= trimPostRequest('makeYmd');				//제조일
$launchYmdChange			= trimPostRequest('launchYmd');				//출시일
$modDtChange				= trimPostRequest('modDt');					//수정일
$regDtChange				= trimPostRequest('regDt');					//등록일
//------------------------------------------------------

//------------------------------------------------------
// - Advice - 부가 설정
//------------------------------------------------------
$deleteField				= trimPostRequest('delete_field');			//상품 삭제 구분 필드
//------------------------------------------------------

//------------------------------------------------------
// - Advice - 대상 쇼핑몰 추출 테이블
//------------------------------------------------------
$db->query("DROP TABLE IF EXISTS es_categoryBrand;");
$db->query("DROP TABLE IF EXISTS es_categoryGoods;");
$db->query("DROP TABLE IF EXISTS es_scmManage;");
$db->query("DROP TABLE IF EXISTS es_scmDeliveryBasic;");
$db->query("DROP TABLE IF EXISTS es_addgoods;");
//------------------------------------------------------

//------------------------------------------------------
// - Advice - 대상 쇼핑몰 상품 연동 테이블 로컬 삽입
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
// - Advice - 이전 후 쇼핑몰 데이터 초기화
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
// - Advice - 데이터 리플레이스 관련 변수
//------------------------------------------------------
$repimgMagnifyBefore		= trimPostRequest('rep_imgMagnify_before');		//확대 이미지 리플레이스 변경 전 변수
$repimgMagnifyAfter			= trimPostRequest('rep_imgMagnify_after');		//확대 이미지 리플레이스 변경 후 변수
$repIMCnt					= trimPostRequest('repIMCnt');					//확대 이미지 리플레이스 카운터 변수

$repImgDetailBefore			= trimPostRequest('rep_imgDetail_before');		//상세 이미지 리플레이스 변경 전 변수
$repImgDetailAfter			= trimPostRequest('rep_imgDetail_after');		//상세 이미지 리플레이스 변경 후 변수
$repIDCnt					= trimPostRequest('repIDCnt');					//상세 이미지 리플레이스 카운터 변수

$repImgListBefore			= trimPostRequest('rep_imgList_before');		//썸네일 이미지 리플레이스 변경 전 변수
$repImgListAfter			= trimPostRequest('rep_imgList_after');			//썸네일 이미지 리플레이스 변경 후 변수
$repImgListCnt				= trimPostRequest('repimgListCnt');				//썸네일 이미지 리플레이스 카운터 변수

$repImgMainBefore			= trimPostRequest('rep_imgMain_before');		//리스트 이미지 리플레이스 변경 전 변수
$repImgMainAfter			= trimPostRequest('rep_imgMain_after');			//리스트 이미지 리플레이스 변경 후 변수
$repImgMainCnt				= trimPostRequest('repimgMainCnt');				//리스트 이미지 리플레이스 카운터 변수

$repImgAdd1Before			= trimPostRequest('rep_imgAdd1_before');		//리스트그룹 이미지 리플레이스 변경 전 변수
$repImgAdd1After			= trimPostRequest('rep_imgAdd1_after');			//리스트그룹 이미지 리플레이스 변경 후 변수
$repImgAdd1Cnt				= trimPostRequest('repimgAdd1Cnt');				//리스트그룹 이미지 리플레이스 카운터 변수

$repImgAdd2Before			= trimPostRequest('rep_imgAdd2_before');		//심플 이미지 리플레이스 변경 전 변수
$repImgAdd2After			= trimPostRequest('rep_imgAdd2_after');			//심플 이미지 리플레이스 변경 후 변수
$repImgAdd2Cnt				= trimPostRequest('repimgAdd2Cnt');				//심플 이미지 리플레이스 카운터 변수

$repGoodsSearchWordBefore	= trimPostRequest('rep_goodsSearchWord_before');	//검색어 리플레이스 변경 전 변수
$repGoodsSearchWordAfter	= trimPostRequest('rep_goodsSearchWord_after');		//검색어 리플레이스 변경 후 변수
$repGoodsSearchWordCnt		= trimPostRequest('repgoodsSearchWordCnt');			//검색어 리플레이스 카운터 변수

$repGoodsDescriptionBefore	= trimPostRequest('rep_goodsDescription_before');	//상세 내용 리플레이스 변경 전 변수
$repGoodsDescriptionAfter	= trimPostRequest('rep_goodsDescription_after');	//상세 내용 리플레이스 변경 후 변수
$repGoodsDescriptionCnt		= trimPostRequest('repgoodsDescriptionCnt');		//상세 내용 리플레이스 카운터 변수

$repGoodsDescriptionMobileBefore	= trimPostRequest('rep_goodsDescriptionMobile_before');	//모바일 상세 내용 리플레이스 변경 전 변수
$repGoodsDescriptionMobileAfter		= trimPostRequest('rep_goodsDescriptionMobile_after');	//모바일 상세 내용 리플레이스 변경 후 변수
$repGoodsDescriptionMobileCnt		= trimPostRequest('repgoodsDescriptionMobileCnt');		//모바일 상세 내용 리플레이스 카운터 변수

$repShortDescriptionBefore	= trimPostRequest('rep_shortDescription_before');	//짧은 설명 리플레이스 변경 전 변수
$repShortDescriptionAfter	= trimPostRequest('rep_shortDescription_after');	//짧은 설명 리플레이스 변경 후 변수
$repShortCnt				= trimPostRequest('repshortDescriptionCnt');		//짧은 설명 리플레이스 카운터 변수

$repGoodsStateBefore		= trimPostRequest('rep_goodsState_before');			//상품 상태 리플레이스 변경 전 변수
$repGoodsStateAfter			= trimPostRequest('rep_goodsState_after');			//상품 상태 리플레이스 변경 후 변수
$repGoodsStateCnt			= trimPostRequest('repgoodsStateCnt');				//상품 상태 리플레이스 카운터 변수
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

include $sourcePath . '/goodsRoopBeforeSource.php';		// 상품 관련 별도 테이블 운영시 수정 페이지

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
if(trimPostRequest('data_type') === 'csv'){// CSV 로 첨부파일 데이터 로드
	$goodsFp = fopen($csvFilePath . trimPostRequest('data_name') . '.csv', 'r' );
	$goodsRow = fgetcsv($goodsFp, 1500000, ',');
} else if(trimPostRequest('data_type') === 'sql') {// SQL 로 첨부파일 데이터 로드
	if(trimPostRequest('sort')){
		$goodsSort = ' order by ' . trimPostRequest('sort');
	}
	$goodsResult = $db->query("select " . stripslashes(trimPostRequest('select_field')) . " from " . trimPostRequest('data_name') . $goodsSort);
}

while($goodsRow = (trimPostRequest('data_type') === 'csv') ? fgetcsv($goodsFp, 1500000, ',') : $db->fetch($goodsResult, 1)) {
	if($deleteField){
		if($goodsRow[$deleteField] == trimPostRequest('delete_type')) continue;
	}
	$arrayNewGoods	= array(); // 쿼리 생성 임시 배열 '필드' => '값' 형태
	$arrayGoodsLink = array(); // 동일 카테고리 연결 방지 배열
	
	//------------------------------------------------------
	// - Advice - 변수 초기화
	//------------------------------------------------------
	$goodsCd				= '';	//상품코드
	$goodsNm				= '';	//상품명
	$goodsPriceString		= '';	//판매가 대체 문구
	$memo					= '';	//관리자 메모
	$makerNm				= '';	//제조사
	$originNm				= '';	//원산지
	$brandCd				= '';	//브랜드
	$goodsModelNo			= '';	//모델명
	$goodsWeight			= '';	//상품무게
	$minOrderCnt			= '';	//최소 구매 수량
	$maxOrderCnt			= '';	//최대 구매 수량
	$goodsSellFl			= '';	//PC 판매 여부
	$goodsSellMobileFl		= '';	//모바일 판매 여부
	$goodsDisplayFl			= '';	//PC 노출 여부
	$goodsDisplayMobileFl	= '';	//모바일 노출 여부
	$stockFl				= '';	//재고량 연동 여부
	$soldOutFl				= '';	//품절 여부
	$taxFreeFl				= '';	//과세 여부
	$taxPercent				= '';	//과세율
	$onlyAdultFl			= '';	//성인인증 사용 여부
	$goodsState				= '';	//상품상태
	$mileageFl				= '';	//상품별 마일리지 여부
	$imgMagnify				= '';	//확대 이미지
	$imgDetail				= '';	//상세 이미지
	$imgList				= '';	//리스트 이미지
	$imgMain				= '';	//메인 이미지
	$imgAdd1				= '';	//
	$imgAdd2				= '';	//
	$goodsSearchWord		= '';	//검색어
	$goodsDescription		= '';	//상세내용
	$goodsDescriptionMobile	= '';	//모바일 상세 내용
	$shortDescription		= '';	//짧은설명
	$salesStartYmd			= '';	//상품 판매기간 시작일
	$salesEndYmd			= '';	//상품 판매기간 종료일
	$makeYmd				= '';	//제조일
	$launchYmd				= '';	//출시일
	$modDt					= '';	//수정일
	$regDt					= '';	//등록일
	//------------------------------------------------------
	
	$goodsCd = $goodsRow[$goodsCdChange];		// 상품 코드

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
	//- Advice - 날짜 형태 데이터
	//- 다중 필드
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
	//- Advice - 확대, 상세 이미지, 짧은 설명
	//- 확대, 상세 이미지, 짧은 설명은 다중 필드, 리플레이스 처리 기능
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
	//- Advice - 썸네일, 리스트, 리스트그룹, 심플 이미지, 검색어, 상품상태
	//- 썸네일, 리스트, 리스트그룹, 심플 이미지, 검색어는 단일 필드, 리플레이스 처리 기능
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
	//- Advice - 파일 복사
	//-----------------------------------------------------------
	if ($fileCopyFl) {
		$newFileData = array();

		//-----------------------------------------------------------
		//- Advice - 경로 설정
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
		//- Advice - 파일 복사 실행
		//-----------------------------------------------------------
		$imageNo = 0;
		$magnifyNumber = 0;
		$oldImgMagnify = $arrayImgMagnify;
		$newImgMagnify = array();
		$copyResult = 1;
		for ($i = 0; $i <= count($oldImgMagnify) - 1; $i++) {// 첨부이미지
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
		for ($i = 0; $i <= count($oldImgDetail) - 1; $i++) {// 첨부이미지
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
		for ($i = 0; $i <= count($oldImgMagnify) - 1; $i++) {// 첨부이미지
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
		for ($i = 0; $i <= count($oldImgDetail) - 1; $i++) {// 첨부이미지
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
	//- Advice - 내용, 모바일 상세내용 및 작성 이미지 복사 기능
	//- 내용은 다중 필드, 리플레이스 처리 기능(*카페24 전용)
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
	//(*카페24 전용)
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
	$goodsNm		= strip_tags($goodsRow[$goodsNmChange]);		// 상품명
	$goodsPriceString	= $goodsRow[$goodsPriceStringChange];	// 판매가 대체문구
	$memo			= $goodsRow[$memoChange];		// 메모
	$makerNm		= $goodsRow[$makerNmChange];		// 제조사
	$originNm		= $goodsRow[$originNmChange];		// 원산지
	$goodsModelNo	= $goodsRow[$goodsModelNoChange];	// 모델명
	$goodsWeight	= $goodsRow[$goodsWeightChange];		// 무게
	$minOrderCnt	= $goodsRow[$minOrderCntChange];		// 최소구매수량
	$maxOrderCnt	= $goodsRow[$maxOrderCntChange];		// 최대구매수량
	$taxPercent		= $goodsRow[$taxPercentChange];		// 과세율
	
	$goodsSellFl			= ($goodsSellFlChange) ? flagChange('yn', $goodsRow[$goodsSellFlChange]) : 'y';	// PC 판매여부
	$goodsSellMobileFl		= ($goodsSellMobileFlChange) ? flagChange('yn', $goodsRow[$goodsSellMobileFlChange]) : 'y';	// 모바일 판매여부
	$goodsDisplayFl			= ($goodsDisplayFlChange) ? flagChange('yn', $goodsRow[$goodsDisplayFlChange]) : 'y';	// 진열여부
	$goodsDisplayMobileFl	= ($goodsDisplayMobileFlChange) ? flagChange('yn', $goodsRow[$goodsDisplayMobileFlChange]) : 'y';	// 모바일 진열 여부
	$stockFl				= ($stockFlChange) ? flagChange('yn', $goodsRow[$stockFlChange]) : 'n';		// 재고량 연동 여부
	$soldOutFl				= ($soldOutFlChange) ? flagChange('yn', $goodsRow[$soldOutFlChange]) : 'n';	// 품절 여부
	$taxFreeFl				= ($taxFreeFlChange) ? flagChange('tf', $goodsRow[$taxFreeFlChange]) : 't';	// 부가세 포함 여부
	$mileageFl				= ($mileageFlChange) ? flagChange('cg', $goodsRow[$mileageFlChange]) : 'c';	// 상품별 적립금 사용 여부
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
	// 이전 모듈 기능 동작을 원치 않으신다면 './goodsRoopSource.php' 소스에서 변경 하여 작업 하여 주시기 바랍니다.
	include './goodsRoopSource.php';	// 옵션 및 판매가 추가정보, 상품 정보 고시 등 정보 수정 페이지
	
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
			echo '<div>작업 완료 총 : ' . number_format($dataCnt) . '건</div><script type="text/javascript">parent.configSubmitComplete("' . $dataCnt . '");</script>';
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
echo '<div>작업 완료 총 : ' . number_format($dataCnt) . '건</div><script type="text/javascript">parent.configSubmitComplete("' . $dataCnt . '");</script>';
?>