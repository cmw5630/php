<?php
include '../../inc/header.php';

$mode = $_POST['mode'];

$insertSet	= new insertSet('es_scmManage', 'n');

//------------------------------------------------------
// - Advice - 멀티 쿼리 출력 전용 필드값 셋팅
//------------------------------------------------------
$insertSet->arrayFieldName = array(
	'scmNo',					// 고유번호
	'companyNm',				// 공급사명
	'scmType',					// 공급사상태-운영y일시정지n탈퇴x
	'scmCommission',			// 판매수수료-%로 소수점 2자리
	'scmCommissionDelivery',	// 배송비수수료-%로 소수점 2자리
	'scmKind',					// 공급사종류 - 공급사('p'),본사('c')
	'scmCode',					// 공급사코드
	'scmPermissionInsert',		// 상품등록권한-자동승인('a'),관리자승인('c')
	'scmPermissionModify',		// 상품수정권한-자동승인('a'),관리자승인('c')
	'scmPermissionDelete',		// 상품삭제권한-자동승인('a'),관리자승인('c')
	'ceoNm',					// 대표자명
	'businessNo',				// 사업자 번호
	'mailOrderNo',				// 통신 판매 신고 번호
	'onlineOrderSerial',		// 통신 판매 신고 번호
	'service',					// 업태
	'item',						// 종목
	'email',					// 대표 이메일
	'zipcode',					// 우편번호
	'zonecode',					// 우편번호(5자리)
	'address',					// 주소
	'addressSub',				// 상세주소
	'unstoringZipcode',			// 기본 출고지 우편번호
	'unstoringZonecode',		// 기본 출고지 우편번호(5자리)
	'unstoringAddress',			// 기본 출고지 주소
	'unstoringAddressSub',		// 기본 출고지 상세주소
	'returnZipcode',			// 기본 반품/교환지 우편번호
	'returnZonecode',			// 기본 반품/교환지 우편번호(5자리)
	'returnAddress',			// 기본 반품/교환지 주소
	'returnAddressSub',			// 기본 반품/교환지 상세주소
	'phone',					// 대표전화
	'centerPhone',				// 고객센터 연락처
	'fax',						// 팩스번호
	'staff',					// 담당자정보-json
	'account',					// 계좌정보-json
	'functionAuth',				// 공급사 기능 권한
	'addInfo',					// 추가 정보 저장(엑셀 추가항목 등..)
	'scmInsertAdminId',			// SCM등록자아이디
	'managerNo',				// SCM등록자키
	'delFl',					// 삭제 여부
	'regDt',					// 등록일
	'modDt',					// 수정일
);
//------------------------------------------------------

$arrayQueryPostData			= array();		// 생성 쿼리 저장 배열


//------------------------------------------------------
// - Advice - 공급사 테이블 이전 전 필드명
//------------------------------------------------------
$scmNoChange				= trimPostRequest('scmNo');					// 공급사 일련번호
$scmCodeChange				= trimPostRequest('scmCode');				// 공급사 코드
$companyNmChange			= trimPostRequest('companyNm');				// 공급사명
$scmTypeChange				= trimPostRequest('scmType');				// 탈퇴여부
$sellCommissionChange		= trimPostRequest('sellCommission');		// 판매 수수료
$deliveryCommissionChange	= trimPostRequest('deliveryCommission');	// 배송비 수수료
$ceoNmChange				= trimPostRequest('ceoNm');					// 대표자명
$businessNoChange			= trimPostRequest('businessNo');			// 사업자 등록 번호
$serviceChange				= trimPostRequest('service');				// 업태
$itemChange					= trimPostRequest('item');					// 업종
$zipcodeChange				= trimPostRequest('zipcode');				// 우편번호
$zonecodeChange				= trimPostRequest('zonecode');				// 구역번호
$addressChange				= trimPostRequest('address');				// 주소
$unstoringZipcodeChange		= trimPostRequest('unstoringZipcode');		// 출고지 우편번호
$unstoringZonecodeChange	= trimPostRequest('unstoringZonecode');		// 출고지 구역번호
$unstoringAddressChange		= trimPostRequest('unstoringAddress');		// 출고지 주소
$returnZipcodeChange		= trimPostRequest('returnZipcode');			// 반품/교환지 우편번호
$returnZonecodeChange		= trimPostRequest('returnZonecode');		// 반품/교환지 구역번호
$returnAddressChange		= trimPostRequest('returnAddress');			// 반품/교환지 주소
$phoneChange				= trimPostRequest('phone');					// 대표 번호
$centerPhoneChange			= trimPostRequest('centerPhone');			// 고객센터 번호
$regDtChange				= trimPostRequest('regDt');					// 등록일
$modDtChange				= trimPostRequest('modDt');					// 수정일
//------------------------------------------------------

//------------------------------------------------------
// - Advice - 공급사 관리자 테이블 이전 필드명
//------------------------------------------------------
$managerIdChange			= trimPostRequest('managerId');				// 관리자 ID
$managerNmChange			= trimPostRequest('managerNm');				// 관리자명
$managerNickNmChange		= trimPostRequest('managerNickNm');			// 관리자 닉네임
$memPwChange				= trimPostRequest('memPw');					// 비밀번호
$managerPhoneChange			= trimPostRequest('managerPhone');			// 연락처
$managerCellPhoneChange		= trimPostRequest('managerCellPhone');		// 핸드폰
$managerEmailChange			= trimPostRequest('managerEmail');			// 이메일
$memoChange					= trimPostRequest('memo');					// 메모
$managerRegDtChange			= trimPostRequest('managerRegDt');			// 등록일
//------------------------------------------------------

//------------------------------------------------------
// - Advice - 직원 테이블 이전 필드명
//------------------------------------------------------
$staffNameChange		= trimPostRequest('staffName');				// 직원명
$staffPhoneChange		= trimPostRequest('staffPhone');			// 연락처
$staffCellPhoneChange	= trimPostRequest('staffCellPhone');		// 핸드폰
$staffEmailChange		= trimPostRequest('staffEmail');			// 이메일
//------------------------------------------------------

//------------------------------------------------------
// - Advice - 이전 후 쇼핑몰 데이터 초기화
//------------------------------------------------------
if($mode == "start") {
	$arrayQueryPostData[] = "Delete From es_scmManage Where scmNo > 1";				// 공급사 관리 테이블
	$arrayQueryPostData[] = "Delete From es_logScmCommission Where scmNo > 1";		// 공급사 수수료 정보 변경 이력
	$arrayQueryPostData[] = "Delete From es_scmDeliveryCharge Where scmNo > 1";		// scm별 배송비 설정
	$arrayQueryPostData[] = "Delete From es_scmAdjust Where scmNo > 1";				// 공급사 정산
	$arrayQueryPostData[] = "Delete From es_scmAdjustLog Where scmAdjustNo in (Select scmAdjustNo From es_scmAdjust Where scmNo > 1)";			// 공급사 정산 로그
	$arrayQueryPostData[] = "Delete From es_scmAdjustTaxBill Where scmNo > 1";		// 공급사 정산 세금계산서
	$arrayQueryPostData[] = "Delete From es_scmBoard Where scmNo > 1";				// 공급사게시판
	$arrayQueryPostData[] = "Delete From es_scmBoardGroup Where scmNo > 1";			// 공급사게시판그룹
	$arrayQueryPostData[] = "Delete From es_scmCommission Where scmNo > 1";			// 공급사 적용수수료 테이블
	$arrayQueryPostData[] = "Delete From es_scmCommissionSchedule Where scmNo > 1";	// 공급사 수수료 일정 테이블
	$arrayQueryPostData[] = "Delete From es_scmDeliveryArea Where scmNo > 1";		// scm별 배송비 설정
	$arrayQueryPostData[] = "Delete From es_scmDeliveryAreaGroup Where scmNo > 1";	// SCM별 지역별배송 정책 그룹
	$arrayQueryPostData[] = "Delete From es_scmDeliveryBasic Where scmNo > 1";		// SCM별 기본 배송 정책
	$arrayQueryPostData[] = "Delete From es_manager Where scmNo > 1";		// scm별 배송비 설정
}


if (trimPostRequest('thirdTableFl') == 'Y') {
	$arrayStaff = array();
	if(trimPostRequest('third_data_type') === 'csv'){// CSV 로 첨부파일 데이터 로드
		$thirdTableFp = fopen($csvFilePath . trimPostRequest('third_data_name') . '.csv', 'r' );
		$thirdTableRow = fgetcsv($thirdTableFp, 1500000, ',');
	} else if(trimPostRequest('third_data_type') === 'sql') {// SQL 로 첨부파일 데이터 로드
		if(trimPostRequest('third_sort')){
			$thirdTableSort = ' order by ' . trimPostRequest('third_sort');
		}
		$thirdTableResult = $db->query("select " . stripslashes(trimPostRequest('third_select_field')) . " from " . trimPostRequest('third_data_name') . $thirdTableSort);
	}

	while($thirdTableRow = (trimPostRequest('third_data_type') === 'csv') ? fgetcsv($thirdTableFp, 1500000, ',') : $db->fetch($thirdTableResult, 1)) {
		//-----------------------------------------------------------
		//- Advice - 전화번호
		//- 전화번호는 추가 필드 가능
		//-----------------------------------------------------------
		$staffPhone = '';
		if(trimPostRequest('staffPhoneCnt') > 1){
			for ($i = 0; $i < count($staffPhoneChange); $i++){
				$staffPhone .= $thirdTableRow[$staffPhoneChange[$i]];
			}
		} else {
			$thirdTableRow[$staffPhoneChange[0]] = defaultReplace($thirdTableRow[$staffPhoneChange[0]]);
			$staffPhone = $thirdTableRow[$staffPhoneChange[0]];
		}
		preg_match('/[[:digit:]]{9,11}/', $staffPhone, $phoneTypeResult);
		if (!empty($phoneTypeResult)) {
			$staffPhone = telCreate($staffPhone);
		}
		else {
			$staffPhone = '';
		}
		//-----------------------------------------------------------

		//-----------------------------------------------------------
		//- Advice - 핸드폰
		//- 핸드폰 추가 필드 가능
		//-----------------------------------------------------------
		$staffCellPhone = '';
		if(trimPostRequest('staffCellPhoneCnt') > 1){
			for ($i = 0; $i < count($staffCellPhoneChange); $i++){
				$staffCellPhone .= $thirdTableRow[$staffCellPhoneChange[$i]];
			}
		} else {
			$thirdTableRow[$staffCellPhoneChange[0]] = defaultReplace($thirdTableRow[$staffCellPhoneChange[0]]);
			$staffCellPhone = $thirdTableRow[$staffCellPhoneChange[0]];
		}
		preg_match('/[[:digit:]]{9,11}/', $staffCellPhone, $phoneTypeResult);
		if (!empty($phoneTypeResult)) {
			$staffCellPhone = telCreate($staffCellPhone);
		}
		else {
			$staffCellPhone = '';
		}
		//-----------------------------------------------------------

		//-----------------------------------------------------------
		//- Advice - 이메일
		//- 이메일은 다중 필드
		//-----------------------------------------------------------
		$staffEmail = '';
		$arrayEmail = array();
		if(trimPostRequest('staffEmailCnt') > 1){
			for ($i = 0; $i < count($staffEmailChange); $i++){
				$arrayEmail[] = $thirdTableRow[$staffEmailChange[$i]];
			}
			$staffEmail = implode('@', $arrayEmail);
		} else {
			$staffEmail = $thirdTableRow[$staffEmailChange[0]];
		}
		//----------------------------------------------------------


		$arrayStaff[$thirdTableRow[trimPostRequest('staffRelation')]][] = array(
			'staffType'		=>	'02001007',
			'staffName'		=>	$thirdTableRow[$staffNameChange],
			'staffTel'		=>	$staffPhone,
			'staffPhone'	=>	$staffCellPhone,
			'staffEmail'	=>	$staffEmail,
		);
	}
}

if (trimPostRequest('subTableFl') === 'Y') {
	$arrayManage = array();
	if(trimPostRequest('sub_data_type') === 'csv'){// CSV 로 첨부파일 데이터 로드
		$subTableFp = fopen($csvFilePath . trimPostRequest('sub_data_name') . '.csv', 'r' );
		$subTableRow = fgetcsv($subTableFp, 1500000, ',');
	} else if(trimPostRequest('sub_data_type') === 'sql') {// SQL 로 첨부파일 데이터 로드
		if(trimPostRequest('sub_sort')){
			$subTableSort = ' order by ' . trimPostRequest('sub_sort');
		}
		$subTableResult = $db->query("select " . stripslashes(trimPostRequest('sub_select_field')) . " from " . trimPostRequest('sub_data_name') . $subTableSort);
	}

	while($subTableRow = (trimPostRequest('sub_data_type') === 'csv') ? fgetcsv($subTableFp, 1500000, ',') : $db->fetch($subTableResult, 1)) {

		//-----------------------------------------------------------
		//- Advice - 전화번호
		//- 전화번호는 추가 필드 가능
		//-----------------------------------------------------------
		$managePhone = '';
		if(trimPostRequest('managePhoneCnt') > 1){
			for ($i = 0; $i < count($managerPhoneChange); $i++){
				$managePhone .= $subTableRow[$managerPhoneChange[$i]];
			}
		} else {
			$subTableRow[$managerPhoneChange[0]] = defaultReplace($subTableRow[$managerPhoneChange[0]]);
			$managePhone = $subTableRow[$managerPhoneChange[0]];
		}
		preg_match('/[[:digit:]]{9,11}/', $managePhone, $phoneTypeResult);
		if (!empty($phoneTypeResult)) {
			$managePhone = telCreate($managePhone);
		}
		else {
			$managePhone = '';
		}
		//-----------------------------------------------------------

		//-----------------------------------------------------------
		//- Advice - 핸드폰
		//- 핸드폰 추가 필드 가능
		//-----------------------------------------------------------
		$managerCellPhone = '';
		if(trimPostRequest('managerCellPhoneCnt') > 1){
			for ($i = 0; $i < count($managerCellPhoneChange); $i++){
				$managerCellPhone .= $subTableRow[$managerCellPhoneChange[$i]];
			}
		} else {
			$subTableRow[$managerCellPhoneChange[0]] = defaultReplace($subTableRow[$managerCellPhoneChange[0]]);
			$managerCellPhone = $subTableRow[$managerCellPhoneChange[0]];
		}
		preg_match('/[[:digit:]]{9,11}/', $managerCellPhone, $phoneTypeResult);
		if (!empty($phoneTypeResult)) {
			$managerCellPhone = telCreate($managerCellPhone);
		}
		else {
			$managerCellPhone = '';
		}
		//-----------------------------------------------------------

		//-----------------------------------------------------------
		//- Advice - 이메일
		//- 이메일은 다중 필드
		//-----------------------------------------------------------
		$manageEmail = '';
		$arrayEmail = array();
		if(trimPostRequest('managerEmailCnt') > 1){
			for ($i = 0; $i < count($managerEmailChange); $i++){
				$arrayEmail[] = $subTableRow[$managerEmailChange[$i]];
			}
			$manageEmail = implode('@', $arrayEmail);
		} else {
			$manageEmail = $subTableRow[$managerEmailChange[0]];
		}
		//----------------------------------------------------------
		
		//-----------------------------------------------------------
		//- Advice - 메모
		//- 메모는 다중 필드
		//-----------------------------------------------------------
		$memo = '';
		$arrayMemo = array();
		if(trimPostRequest('memoCnt') > 1){
			for ($i = 0; $i < count($memoChange); $i++){
				$arrayMemo[] = $subTableRow[$memoChange[$i]];
			}
			$memo = implode(' | ' . $arrayMemo);
		} else {
			$memo = $subTableRow[$memoChange[0]];
		}
		//-----------------------------------------------------------

		//-----------------------------------------------------------
		//- Advice - 등록일
		//- 가입일은 다중 필드
		//-----------------------------------------------------------
		$manageRegDt = '';
		if(trimPostRequest('entryDtCnt') > 1){
			for ($i = 0; $i < count($managerRegDtChange); $i++){
				$manageRegDt .= $subTableRow[$managerRegDtChange[$i]];
			}
		} else {
			if (!$subTableRow[$managerRegDtChange[0]]) {
				$manageRegDt = '0000-00-00 00:00:00';//date('Y-m-d h:i:s');
			}
			else {
				$manageRegDt = $subTableRow[$managerRegDtChange[0]];
			}
		}

		$manageRegDt = dateCreate($manageRegDt);
		//-----------------------------------------------------------

		//-----------------------------------------------------------
		//- Advice - 비밀번호
		//- 비밀번호는 다중 필드 가능 임시 비밀번호인 경우
		//-----------------------------------------------------------
		$managerPw = '';
		if (trimPostRequest('password_type') === 'password') {
			$managerPw = "password('" . addslashes($subTableRow[$memPwChange[0]]) . "')";
		}
		else if (trimPostRequest('password_type') === 'temp') {
			for ($i = 0; $i <= trimPostRequest('memPwCnt') - 1; $i++) {
				$subTableRow[$memPwChange[$i]] = defaultReplace($subTableRow[$memPwChange[$i]]);
				preg_match('/[[:digit:]]{9,11}/', $subTableRow[$memPwChange[$i]], $phoneTypeResult);
				if (!empty($phoneTypeResult) && $managerPw == '') {
					$managerPw = Right($subTableRow[$memPwChange[$i]], 4);
				}
			}
			
			if ($managerPw) {
				$managerPw = "password('" . trimPostRequest('tempPassword') . $managerPw . "')";
			}
			else {
				$managerPw = "password('" . trimPostRequest('tempPassword') . '0000' . "')";
			}
		}
		else {
			$managerPw = $subTableRow[$memPwChange[0]];
		}
		//-----------------------------------------------------------

		$manageId		= $subTableRow[$managerIdChange];
		$managerNm		= $subTableRow[$managerNmChange];
		$managerNickNm	= $subTableRow[$managerNickNmChange];

		$arrayManage[$subTableRow[trimPostRequest('managerRelation')]][] = array(
			'manageId'			=>	$manageId,
			'managerNm'			=>	$managerNm,
			'managerNickNm'		=>	$managerNickNm,
			'managePw'			=>	$managerPw,
			'manageRegDt'		=>	$manageRegDt,
			'memo'				=>	$memo,
			'manageEmail'		=>	$manageEmail,
			'managerCellPhone'	=>	$managerCellPhone,
			'managePhone'		=>	$managePhone,
		);
	}
}

$arrayAccountType = array(
	'하나'				=>	'04002005',
	'KEB'				=>	'04002005',
	'외환'				=>	'04002005',
	'중소기업'			=>	'04002002',
	'우리'				=>	'04002004',
	'씨티'				=>	'04002008',
	'한국씨티'			=>	'04002008',
	'한국시티'			=>	'04002008',
	'신한'				=>	'04002003',
	'부산'				=>	'04002018',
	'대구'				=>	'04002017',
	'농협'				=>	'04002009',
	'NH'				=>	'04002009',
	'KB'				=>	'04002001',
	'국민'				=>	'04002001',
	'SC제일은행'		=>	'04002007',
	'IBK'				=>	'04002002',
	'기업'				=>	'04002002',
);

$arrayScmCommission = array();
$scmCommissionResult = $db->query("Select provider_seq, charge From fm_provider_charge order by provider_seq, charge_seq");
while ($scmCommissionRow = $db->fetch($scmCommissionResult)) {
	$arrayScmCommission[$scmCommissionRow['provider_seq']][] = $scmCommissionRow['charge'];
}

//------------------------------------------------------
// - Advice - 기본값 설정
//------------------------------------------------------
$dataCnt	= 0;
$scmNo		= 2;
$deliveryBasicKey = 5;
//------------------------------------------------------


$row = '';
if(trimPostRequest('data_type') == 'csv'){/*** CSV ***/
	$fp = fopen($csvFilePath . trimPostRequest('data_name') . '.csv', 'r' );
	$tt = fgetcsv($fp, 1500000, ',');

} else if(trimPostRequest('data_type') == 'sql') {/*** SQL ***/
	$sort = (trimPostRequest('sort')) ? ' order by ' . trimPostRequest('sort') : '';
	$res = $db->query("select " . trimPostRequest('select_field') . " from " . trimPostRequest('data_name') . $sort);
}

//$res = $db->query("select * from fm_provider Where provider_gb != 'company' order by regdate");
$allShippingAddressCount = 0;
while($row = (trimPostRequest('data_type') === 'csv') ? fgetcsv($fp, 1500000, ',') : $db->fetch($res)) {
	
	if($deleteField){
		if (trimPostRequest('delete_type')) {
			if((string)$row[$deleteField] == trimPostRequest('delete_type')) continue;
		}
		else {
			if ($row[$deleteField] != '') continue;
		}
	}
	
	$newScm = array();
	
	$oldScmNo	= $row[$scmNoChnage];
	$companyNm	= $row[$companyNmChange];
	$scmCode	= $row[$scmCodeChange];
	$ceoNm		= $row[$ceoNmChange];
	$service	= $row[$serviceChange];
	$item		= $row[$itemChange];

	$permissionFl = 's';
	$addInfo = 'NULL';

	$functionAuth = '{"functionAuth": {"goodsNm": "y", "addGoodsNm": "y", "goodsPrice": "y", "orderState": "y", "boardDelete": "y", "goodsDelete": "y", "goodsExcelDown": "y", "goodsSalesDate": "y", "orderExcelDown": "y", "goodsCommission": "y", "goodsStockModify": "y", "addGoodsCommission": "y"}}';

	$scmPermissionInsert = 'c';		// 상품등록권한-자동승인('a'),관리자승인('c')
	$scmPermissionModify = 'c';		// 상품수정권한-자동승인('a'),관리자승인('c')
	$scmPermissionDelete = 'c';		// 상품삭제권한-자동승인('a'),관리자승인('c')


	$scmType = 'y';
	if ($row[$scmTypeChange]) {
		$scmType	= (trimPostRequest('scmType_type') && trimPostRequest('scmType_type') === (string)$row[$scmTypeChange]) ? 'x' : flagChange('yn', $row[$scmTypeChange]);
	}
	
	//-----------------------------------------------------------
	//- Advice - 판매수수료
	//- 판매수수료, 배송수수료 다중 필드(다중필드 기능 향후 수정)
	//-----------------------------------------------------------
	$scmCommission = $row[$sellCommissionChange[0]];

	if (trimPostRequest('deliveryCommission_type') == 'sync') {
		$scmCommissionDelivery = $scmCommission;
	} else {
		$scmCommissionDelivery = $row[$deliveryCommissionChange[0]];
	}
	//----------------------------------------------------------

	//-----------------------------------------------------------
	//- Advice - AccountInfo Set
	//-----------------------------------------------------------
	/*은행 정보 등록 필요시 사용
	$arrayAccountInfo = array();
	
	$arrayAccountMemo = array();
	if ($row['calcu_day']) {
		$arrayAccountMemo[] = $row['calcu_day'];
	}

	for ($i = 1; $i <= 4; $i++) {
		if ($row['calcu_day' . $i]) {
			$arrayAccountMemo[] = $row['calcu_day' . $i];
		}
	}
	if (!empty($arrayAccountMemo)) {
		$accountMemo = implode(chr(13), $arrayAccountMemo);
	}

	$arrayAccountInfo[0]['accountType'] = $arrayAccountType[$row['calcu_bank']];		// 은행 코드
	$arrayAccountInfo[0]['accountNum'] = $row['calcu_num'];							// 계좌번호
	$arrayAccountInfo[0]['accountName'] = $row['calcu_name'];							// 예금주
	$arrayAccountInfo[0]['accountMemo'] = $accountMemo;											// 메모

	$account = gd_json_encode($arrayAccountInfo);
	*/
	//-----------------------------------------------------------

	$zipcode				= '';
	$zonecode				= '';
	
	$unstoringZonecode		= '';
	$unstoringZipcode		= '';
	$unstoringAddress		= '';
	$unstoringAddressSub	= '';
	
	$returnZonecode			= '';
	$returnZipcode			= '';
	$returnAddress			= '';
	$returnAddressSub		= '';
	//-----------------------------------------------------------
	//- Advice - 우편번호
	//- 우편번호는 추가 필드 가능
	//-----------------------------------------------------------
	$zipcode = '';
	$zipcode1 = '';
	$zipcode2 = '';
	if(trimPostRequest('zipcode_cnt') > 1){
		for ($i = 0; $i < count($zipcodeChange); $i++){
			$zipcode .= $row[$zipcodeChange[$i]];
		}
	} else {
		$row[$zipcodeChange[0]] = defaultReplace($row[$zipcodeChange[0]]);
		$zipcode1 = Left($row[$zipcodeChange[0]], 3);
		$zipcode2 = Right($row[$zipcodeChange[0]], 3);
		$zipcode = $zipcode1 . $zipcode2;
	}

	if (strlen($zipcode) === 6) {
		$zipcode = zipCodeCreate($zipcode);
	}
	else if (strlen($zipcode) === 5 && !$zonecodeChange) {
		$zonecode = $zipcode;
		$zipcode = '';
	}
	
	if ($zonecodeChange) {
		$zonecode = defaultReplace($row[$zonecodeChange]);
		if (strlen($zonecode) > 5) {
			$zipcode = zipCodeCreate($zonecode);
			$zonecode = '';
		}
	}
	//-----------------------------------------------------------
	
	//-----------------------------------------------------------
	//- Advice - 주소
	//- 주소는 추가 필드 가능
	//-----------------------------------------------------------
	$address	= '';
	$addressSub	= '';
	$arrayOriAddress = array();
	$oriAddress = '';
	if($addressChange[0]){
		for ($i = 0; $i < count($addressChange); $i++){
			if ($i === 0) {
				if (trim($row[$addressChange[$i]])) {
					$arrayOriAddress[] = trim(str_replace('', '', $row[$addressChange[$i]]));
				}
				else {
					$arrayOriAddress[] = trim(str_replace('', '', $row['address']));
				}
			}
			else {
				$arrayOriAddress[] = trim($row[$addressChange[$i]]);
			}
			
		}
		$oriAddress = implode(' ', $arrayOriAddress);
	}
	list($address, $addressSub) = addressMake($oriAddress);
	//-----------------------------------------------------------
	
	if (trimPostRequest('unstoringAddress_type') == 'sync') {
		$unstoringZonecode	= $zonecode;
		$unstoringZipcode	= $zipcode;
		
		$unstoringAddress		= $address;
		$unstoringAddressSub	= $addressSub;
	}

	if (trimPostRequest('returnAddress_type') == 'sync') {
		$returnZonecode		= $zonecode;
		$returnZipcode		= $zipcode;
		
		$returnAddress			= $address;
		$returnAddressSub		= $addressSub;
	}

	//-----------------------------------------------------------
	//- Advice - 전화번호
	//- 전화번호는 추가 필드 가능
	//-----------------------------------------------------------
	$phone = '';
	if(trimPostRequest('phoneCnt') > 1){
		for ($i = 0; $i < count($phoneChange); $i++){
			$phone .= $row[$phoneChange[$i]];
		}
	} else {
		$row[$phoneChange[0]] = defaultReplace($row[$phoneChange[0]]);
		$phone = $row[$phoneChange[0]];
	}
	preg_match('/[[:digit:]]{9,11}/', $phone, $phoneTypeResult);
	if (!empty($phoneTypeResult)) {
		$phone = telCreate($phone);
	}

	$centerPhone = '';
	if(trimPostRequest('centerPhoneCnt') > 1){
		for ($i = 0; $i < count($centerPhoneChange); $i++){
			$centerPhone .= $row[$centerPhoneChange[$i]];
		}
	} else {
		$row[$centerPhoneChange[0]] = defaultReplace($row[$centerPhoneChange[0]]);
		$centerPhone = $row[$centerPhoneChange[0]];
	}
	preg_match('/[[:digit:]]{9,11}/', $centerPhone, $phoneTypeResult);
	if (!empty($phoneTypeResult)) {
		$centerPhone = telCreate($centerPhone);
	}

	$fax = '';
	if(trimPostRequest('faxCnt') > 1){
		for ($i = 0; $i < count($faxChange); $i++){
			$fax .= $row[$faxChange[$i]];
		}
	} else {
		$row[$faxChange[0]] = defaultReplace($row[$faxChange[0]]);
		$fax = $row[$faxChange[0]];
	}
	if ($fax) {
		$fax = telCreate($fax);
	}
	//-----------------------------------------------------------

	$phone = str_replace('-', '', $row['info_phone']);
	$phone = telCreate($phone);
	if ($phone == '--') {
		$phone = '';
	}

	//-----------------------------------------------------------
	//- Advice - 이메일
	//- 이메일은 다중 필드
	//-----------------------------------------------------------
	$email = '';
	$arrayEmail = array();
	if(trimPostRequest('emailCnt') > 1){
		for ($i = 0; $i < count($emailChange); $i++){
			$arrayEmail[] = $row[$emailChange[$i]];
		}
		$email = implode('@', $arrayEmail);
	} else {
		$email = $row[$emailChange[0]];
	}
	//-----------------------------------------------------------

	//-----------------------------------------------------------
	//- Advice - 등록일, 수정일
	//- 가입일은 다중 필드
	//-----------------------------------------------------------
	$regDt = '';
	if(trimPostRequest('regDtCnt') > 1){
		for ($i = 0; $i < count($regDtChange); $i++){
			$regDt .= $row[$regDtChange[$i]];
		}
	} else {
		if (!$row[$regDtChange[0]]) {
			$regDt = 'now()';//date('Y-m-d h:i:s');
		}
		else {
			$regDt = $row[$regDtChange[0]];
		}
	}
	
	if ($regDt != 'now()') {
		$regDt = dateCreate($regDt);
	}
	
	$modDt = '';
	if(trimPostRequest('modDtCnt') > 1){
		for ($i = 0; $i < count($modDtChange); $i++){
			$modDt .= $row[$modDtChange[$i]];
		}
	} else {
		if (!$row[$modDtChange[0]]) {
			$modDt = 'now()';//date('Y-m-d h:i:s');
		}
		else {
			$modDt = $row[$modDtChange[0]];
		}
	}
	
	if ($modDt != 'now()') {
		$modDt = dateCreate($modDt);
	}
	//-----------------------------------------------------------
	$isSuperFl = 'y';
	if (trimPostRequest('subTableFl') === 'Y') {
		$relationData = (trimPostRequest('managerRelation_type') === 'scmNo') ? $oldScmNo : $scmCode;
		if (!empty($arrayManage[$relationData])) {
			foreach ($arrayManage[$relationData] as $managerData) {
				$functionAuth = ($isSuperFl == 'y') ? '{"functionAuth": {"goodsStockModify": "y"}}' : '{"functionAuth": null}';
				$permissionMenu = ($isSuperFl == 'y') ? 'null' : "'{\"permission_1\": [\"godo00358\", \"godo00384\", \"godo00416\", \"godo00431\", \"godo00436\", \"godo00445\"], \"permission_2\": {\"godo00358\": [\"godo00359\", \"godo00361\", \"godo00366\", \"godo00369\"], \"godo00384\": [\"godo00385\", \"godo00396\", \"godo00402\", \"godo00413\"], \"godo00416\": [\"godo00417\", \"godo00426\"], \"godo00431\": [\"godo00432\"], \"godo00436\": [\"godo00437\", \"godo00442\", \"godo00597\"], \"godo00445\": [\"godo00446\", \"godo00451\"]}, \"permission_3\": {\"godo00359\": [\"godo00360\"], \"godo00361\": [\"godo00362\", \"godo00363\", \"godo00584\", \"godo00364\", \"godo00365\", \"godo00540\"], \"godo00366\": [\"godo00367\", \"godo00368\", \"godo00542\"], \"godo00369\": [\"godo00370\", \"godo00371\", \"godo00537\", \"godo00372\", \"godo00383\", \"godo00538\"], \"godo00385\": [\"godo00386\", \"godo00387\", \"godo00534\", \"godo00388\", \"godo00389\", \"godo00544\", \"godo00390\", \"godo00391\", \"godo00546\", \"godo00392\", \"godo00393\", \"godo00548\", \"godo00394\", \"godo00395\"], \"godo00396\": [\"godo00397\", \"godo00398\", \"godo00399\", \"godo00400\", \"godo00401\"], \"godo00402\": [\"godo00403\", \"godo00404\", \"godo00551\", \"godo00405\", \"godo00406\", \"godo00553\", \"godo00407\"], \"godo00413\": [\"godo00414\", \"godo00415\"], \"godo00417\": [\"godo00418\", \"godo00419\", \"godo00420\", \"godo00421\", \"godo00422\", \"godo00423\", \"godo00424\", \"godo00425\"], \"godo00426\": [\"godo00774\", \"godo00428\", \"godo00429\", \"godo00430\"], \"godo00432\": [\"godo00433\", \"godo00513\", \"godo00568\", \"godo00569\", \"godo00434\", \"godo00518\", \"godo00566\", \"godo00567\", \"godo00435\", \"godo00512\", \"godo00514\", \"godo00564\", \"godo00565\"], \"godo00437\": [\"godo00438\", \"godo00439\", \"godo00440\", \"godo00441\"], \"godo00442\": [\"godo00443\", \"godo00444\"], \"godo00446\": [\"godo00447\"], \"godo00451\": [\"godo00452\"], \"godo00597\": [\"godo00598\", \"godo00599\"]}}'";

				$arrayQueryPostData[] = "Insert Into es_manager Set 
											scmNo = '" . $scmNo . "',
											managerId = '" . addslashes($managerData['manageId']) . "',
											managerNm = '" . addslashes($managerData['managerNm']) . "',
											managerPw = password('" . addslashes($managerData['managePw']) . "'),
											managerNickNm = '" . addslashes($managerData['managerNickNm']) . "',
											phone = '" . addslashes($managerData['managePhone']) . "',
											cellPhone = '" . addslashes($managerData['managerCellPhone']) . "',
											email = '" . addslashes($managerData['manageEmail']) . "',
											workPermissionFl = 'n',
											debugPermissionFl = 'n',
											permissionMenu = " . $permissionMenu . ",
											functionAuth = '" . $functionAuth . "',
											isSuper = '" . $isSuperFl . "',
											regDt = now();'
				";

				$isSuperFl = 'n';
			}
		}
		else {
			$managerNm = ($ceoNm) ? $ceoNm : $companyNm . ' 관리자';
			$arrayQueryPostData[] = "Insert Into es_manager Set 
										scmNo = '" . $scmNo . "',
										managerId = 'scmManage" . $scmNo . "',
										managerNm = '" . addslashes($managerNm) . "',
										managerPw = password('" . trimPostRequest('tempPassword') . '0000' . "'),
										managerNickNm = '" . addslashes($managerNm) . "',
										phone = '" . addslashes($phone) . "',
										cellPhone = '" . addslashes($phone) . "',
										email = '" . addslashes($email) . "',
										workPermissionFl = 'n',
										debugPermissionFl = 'n',
										permissionMenu = null,
										functionAuth = '{\"functionAuth\": {\"goodsStockModify\": \"y\"}}',
										isSuper = 'y',
										regDt = now();
			";
		}
		
	}
	else if (trimPostRequest('subTableFl') == 'temp') {
		$managerNm = ($ceoNm) ? $ceoNm : $companyNm . ' 관리자';
		$arrayQueryPostData[] = "Insert Into es_manager Set 
								scmNo = '" . $scmNo . "',
								managerId = 'scmManage" . $scmNo . "',
								managerNm = '" . addslashes($managerNm) . "',
								managerPw = password('" . trimPostRequest('tempPassword') . '0000' . "'),
								managerNickNm = '" . addslashes($managerNm) . "',
								phone = '" . addslashes($phone) . "',
								cellPhone = '" . addslashes($phone) . "',
								email = '" . addslashes($email) . "',
								workPermissionFl = 'n',
								debugPermissionFl = 'n',
								permissionMenu = null,
								functionAuth = '{\"functionAuth\": {\"goodsStockModify\": \"y\"}}',
								isSuper = 'y',
								regDt = 'now();
		";
	}
	else {
		//-----------------------------------------------------------
		//- Advice - 전화번호
		//- 전화번호는 추가 필드 가능
		//-----------------------------------------------------------
		$managePhone = '';
		if(trimPostRequest('managePhoneCnt') > 1){
			for ($i = 0; $i < count($managerPhoneChange); $i++){
				$managePhone .= $row[$managerPhoneChange[$i]];
			}
		} else {
			$row[$managerPhoneChange[0]] = defaultReplace($row[$managerPhoneChange[0]]);
			$managePhone = $row[$managerPhoneChange[0]];
		}
		preg_match('/[[:digit:]]{9,11}/', $managePhone, $phoneTypeResult);
		if (!empty($phoneTypeResult)) {
			$managePhone = telCreate($managePhone);
		}
		else {
			$managePhone = '';
		}
		//-----------------------------------------------------------

		//-----------------------------------------------------------
		//- Advice - 핸드폰
		//- 핸드폰 추가 필드 가능
		//-----------------------------------------------------------
		$managerCellPhone = '';
		if(trimPostRequest('managerCellPhoneCnt') > 1){
			for ($i = 0; $i < count($managerCellPhoneChange); $i++){
				$managerCellPhone .= $row[$managerCellPhoneChange[$i]];
			}
		} else {
			$row[$managerCellPhoneChange[0]] = defaultReplace($row[$managerCellPhoneChange[0]]);
			$managerCellPhone = $row[$managerCellPhoneChange[0]];
		}
		preg_match('/[[:digit:]]{9,11}/', $managerCellPhone, $phoneTypeResult);
		if (!empty($phoneTypeResult)) {
			$managerCellPhone = telCreate($managerCellPhone);
		}
		else {
			$managerCellPhone = '';
		}
		//-----------------------------------------------------------

		//-----------------------------------------------------------
		//- Advice - 이메일
		//- 이메일은 다중 필드
		//-----------------------------------------------------------
		$manageEmail = '';
		$arrayEmail = array();
		if(trimPostRequest('managerEmailCnt') > 1){
			for ($i = 0; $i < count($managerEmailChange); $i++){
				$arrayEmail[] = $row[$managerEmailChange[$i]];
			}
			$manageEmail = implode('@', $arrayEmail);
		} else {
			$manageEmail = $row[$managerEmailChange[0]];
		}
		//----------------------------------------------------------
		
		//-----------------------------------------------------------
		//- Advice - 메모
		//- 메모는 다중 필드
		//-----------------------------------------------------------
		$memo = '';
		$arrayMemo = array();
		if(trimPostRequest('memoCnt') > 1){
			for ($i = 0; $i < count($memoChange); $i++){
				$arrayMemo[] = $row[$memoChange[$i]];
			}
			$memo = implode(' | ' . $arrayMemo);
		} else {
			$memo = $row[$memoChange[0]];
		}
		//-----------------------------------------------------------

		//-----------------------------------------------------------
		//- Advice - 등록일
		//- 가입일은 다중 필드
		//-----------------------------------------------------------
		$manageRegDt = '';
		if(trimPostRequest('entryDtCnt') > 1){
			for ($i = 0; $i < count($managerRegDtChange); $i++){
				$manageRegDt .= $row[$managerRegDtChange[$i]];
			}
		} else {
			if (!$row[$managerRegDtChange[0]]) {
				$manageRegDt = '0000-00-00 00:00:00';//date('Y-m-d h:i:s');
			}
			else {
				$manageRegDt = $row[$managerRegDtChange[0]];
			}
		}

		$manageRegDt = dateCreate($manageRegDt);
		//-----------------------------------------------------------

		//-----------------------------------------------------------
		//- Advice - 비밀번호
		//- 비밀번호는 다중 필드 가능 임시 비밀번호인 경우
		//-----------------------------------------------------------
		$managerPw = '';
		if (trimPostRequest('password_type') === 'password') {
			$managerPw = "password('" . addslashes($row[$memPwChange[0]]) . "')";
		}
		else if (trimPostRequest('password_type') === 'temp') {
			for ($i = 0; $i <= trimPostRequest('memPwCnt') - 1; $i++) {
				$row[$memPwChange[$i]] = defaultReplace($row[$memPwChange[$i]]);
				preg_match('/[[:digit:]]{9,11}/', $row[$memPwChange[$i]], $phoneTypeResult);
				if (!empty($phoneTypeResult) && $managerPw == '') {
					$managerPw = Right($row[$memPwChange[$i]], 4);
				}
			}
			
			if ($managerPw) {
				$managerPw = "password('" . trimPostRequest('tempPassword') . $managerPw . "')";
			}
			else {
				$managerPw = "password('" . trimPostRequest('tempPassword') . '0000' . "')";
			}
		}
		else {
			$managerPw = $row[$memPwChange[0]];
		}
		//-----------------------------------------------------------

		$manageId		= ($row[$managerIdChange]) ? $row[$managerIdChange] : 'scmManage' . $scmNo;
		$managerNm		= ($row[$managerNmChange]) ? $row[$managerNmChange] : $companyNm . ' 관리자';
		$managerNickNm	= $row[$managerNickNmChange];
		
		$arrayQueryPostData[] = "Insert Into es_manager Set 
								scmNo = '" . $scmNo . "',
								managerId = '" . $manageId . "',
								managerNm = '" . addslashes($managerNm) . "',
								managerPw = " . $managerPw . ",
								managerNickNm = '" . addslashes($managerNickNm) . "',
								phone = '" . addslashes($managePhone) . "',
								cellPhone = '" . addslashes($managerCellPhone) . "',
								email = '" . addslashes($manageEmail) . "',
								workPermissionFl = 'n',
								debugPermissionFl = 'n',
								permissionMenu = null,
								functionAuth = '{\"functionAuth\": {\"goodsStockModify\": \"y\"}}',
								isSuper = 'y',
								regDt = now();
		";
	}

	$staffRelationData = (trimPostRequest('staffRelation_type') === 'scmNo' || trimPostRequest('thirdTableFl') == 'N') ? $oldScmNo : $scmCode;

	if (trimPostRequest('thirdTableFl') == 'Y') {
		//-----------------------------------------------------------
		//- Advice - 전화번호
		//- 전화번호는 추가 필드 가능
		//-----------------------------------------------------------
		$staffPhone = '';
		if(trimPostRequest('staffPhoneCnt') > 1){
			for ($i = 0; $i < count($staffPhoneChange); $i++){
				$staffPhone .= $row[$staffPhoneChange[$i]];
			}
		} else {
			$row[$staffPhoneChange[0]] = defaultReplace($row[$staffPhoneChange[0]]);
			$staffPhone = $row[$staffPhoneChange[0]];
		}
		preg_match('/[[:digit:]]{9,11}/', $staffPhone, $phoneTypeResult);
		if (!empty($phoneTypeResult)) {
			$staffPhone = telCreate($staffPhone);
		}
		else {
			$staffPhone = '';
		}
		//-----------------------------------------------------------

		//-----------------------------------------------------------
		//- Advice - 핸드폰
		//- 핸드폰 추가 필드 가능
		//-----------------------------------------------------------
		$staffCellPhone = '';
		if(trimPostRequest('staffCellPhoneCnt') > 1){
			for ($i = 0; $i < count($staffCellPhoneChange); $i++){
				$staffCellPhone .= $row[$staffCellPhoneChange[$i]];
			}
		} else {
			$row[$staffCellPhoneChange[0]] = defaultReplace($row[$staffCellPhoneChange[0]]);
			$staffCellPhone = $row[$staffCellPhoneChange[0]];
		}
		preg_match('/[[:digit:]]{9,11}/', $staffCellPhone, $phoneTypeResult);
		if (!empty($phoneTypeResult)) {
			$staffCellPhone = telCreate($staffCellPhone);
		}
		else {
			$staffCellPhone = '';
		}
		//-----------------------------------------------------------

		//-----------------------------------------------------------
		//- Advice - 이메일
		//- 이메일은 다중 필드
		//-----------------------------------------------------------
		$staffEmail = '';
		$arrayEmail = array();
		if(trimPostRequest('staffEmailCnt') > 1){
			for ($i = 0; $i < count($staffEmailChange); $i++){
				$arrayEmail[] = $row[$staffEmailChange[$i]];
			}
			$staffEmail = implode('@', $arrayEmail);
		} else {
			$staffEmail = $row[$staffEmailChange[0]];
		}
		//----------------------------------------------------------


		$arrayStaff[$staffRelationData][] = array(
			'staffType'		=>	'02001007',
			'staffName'		=>	$row[$staffNameChange],
			'staffTel'		=>	$staffPhone,
			'staffPhone'	=>	$staffCellPhone,
			'staffEmail'	=>	$staffEmail,
		);
	}
	
	if (!empty($arrayStaff[$staffRelationData])) {
		$staff = gd_json_encode($arrayStaff[$staffRelationData]);
	}
	

	/*
	$scmCommission = 0;
	$scmCommissionDelivery = $row['shipping_charge'];
	if (!empty($arrayScmCommission[$row['provider_seq']])) {
		$scmCommission = $arrayScmCommission[$row['provider_seq']][0];
		if (count($arrayScmCommission[$row['provider_seq']]) > 1) {
			for ($i = 0; $i <= count($arrayScmCommission[$row['provider_seq']]) - 1; $i++) {
				$arrayQueryPostData[] = "Insert Into es_scmCommission Set
											scmNo = '" . $scmNo . "',
											commissionType = 'sell',
											commissionValue = '" . $arrayScmCommission[$row['provider_seq']][$i] . "',
											regDt = now();
										";
			}
		}
	}

	$tempPw = ($phone) ? 'change' . substr($phone, -4) : 'change0000';
	*/	

	
	$arrayQueryPostData[] = "INSERT INTO es_scmDeliveryBasic (`sno`, `managerNo`, `scmNo`, `method`, `description`, `deleteFl`, `defaultFl`, `collectFl`, `fixFl`, `freeFl`, `pricePlusStandard`, `priceMinusStandard`, `goodsDeliveryFl`, `areaFl`, `areaGroupNo`, `areaGroupBenefitFl`, `taxFreeFl`, `taxPercent`, `unstoringFl`, `unstoringZipcode`, `unstoringZonecode`, `unstoringAddress`, `unstoringAddressSub`, `returnFl`, `returnZipcode`, `returnZonecode`, `returnAddress`, `returnAddressSub`, `rangeLimitFl`, `rangeLimitWeight`, `deliveryMethodFl`, `dmVisitTypeFl`, `dmVisitTypeZonecode`, `dmVisitTypeZipcode`, `dmVisitTypeAddress`, `dmVisitTypeAddressSub`, `dmVisitTypeDisplayFl`, `rangeRepeat`, `addGoodsCountInclude`, `regDt`, `modDt`) VALUES(" . $deliveryBasicKey . ", 1, " . $scmNo . ", '" . addslashes($companyNm) . " : 기본 생성 배송비', '', 'y', 'n', 'pre', 'fixed', 'n', '', '', 'y', 'n', 0, 'n', 't', '10.0', 'same', '" . $zipcode . "', '" . $zonecode . "', '" . $address . "', '" . $addressSub . "', 'same', '" . $zipcode . "', '" . $zonecode . "', '" . $address . "', '" . $addressSub . "', 'n', '0.00', 'delivery^|^^|^^|^^|^^|^', 'same', '" . $zonecode . "', '" . $zipcode . "', '" . $address . "', '" . $addressSub . "', 'n', 'n', 'n', now(), NULL);";

	$arrayQueryPostData[] = "INSERT INTO `es_scmDeliveryCharge` (`scmNo`, `basicKey`, `unitStart`, `unitEnd`, `price`, `message`, `regDt`, `modDt`) VALUES (" . $scmNo . ", " . $deliveryBasicKey . ", '0.00', '0.00', '2500.00', '', '2019-04-02 11:12:49', NULL);";

	$managerNo = '1';
	$delFl = 'n';

	if (!empty($arrayStaff[$row['provider_seq']])) {
		$staff = gd_json_encode($arrayStaff[$row['provider_seq']]);
	}
	
	foreach ($insertSet->arrayFieldName as $fieldName) {
		$newScm[$fieldName]		=	${$fieldName};
	}

	$insertSet->querySet($newScm, $dataCnt + 1);

	if ($mode === "start_q") {
		if (trimPostRequest('queryRoopLimit') == $dataCnt) {
			$queryPrintCount = 1;
			$arrayQueryPostData = $insertSet->getQuery($arrayQueryPostData);
			if ($addressTableFl) {
				$arrayQueryPostData = $shippingAddressInsertSet->getQuery($arrayQueryPostData);
			}
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
				
			if ($addressTableFl) {
				$arrayQueryPostData = $shippingAddressInsertSet->getQuery($arrayQueryPostData);
			}
			dumpSqlFileSet ($dumpFileName, $arrayQueryPostData);
			unset($arrayQueryPostData);
			$arrayQueryPostData = array();
		}
	}

	$scmNo++;
	$deliveryBasicKey++;
	$dataCnt++;
}

$arrayQueryPostData = $insertSet->getQuery($arrayQueryPostData);

if ($addressTableFl) {
	$arrayQueryPostData = $shippingAddressInsertSet->getQuery($arrayQueryPostData);
}
if($mode === "start_q") {
	$queryPrintCount = 1;
	foreach ($arrayQueryPostData as $printQuery) {
		debug($queryPrintCount . " : " . $printQuery);
		$queryPrintCount++;
	}
}
else if($mode === "start") {
	$arrayQueryPostData[] = "Update es_scmManage Set scmInsertAdminId = (Select managerId From es_manager Where sno = 1)";
	$arrayQueryPostData[] = "OPTIMIZE TABLE es_scmManage";
	$arrayQueryPostData[] = "OPTIMIZE TABLE es_manager";
	$arrayQueryPostData[] = "OPTIMIZE TABLE es_scmDeliveryBasic";
	$arrayQueryPostData[] = "OPTIMIZE TABLE es_scmCommission";
	$arrayQueryPostData[] = "OPTIMIZE TABLE es_scmDeliveryCharge";
	dumpSqlFileSet ($dumpFileName, $arrayQueryPostData);
	//echo '<script>parent.progress();</script>';
	//echo '<script>parent.progress(100);</script>';
}
echo '<div>작업 완료 총 : ' . number_format($dataCnt) . '건</div><script type="text/javascript">parent.configSubmitComplete("' . $dataCnt . '");</script>';

?>