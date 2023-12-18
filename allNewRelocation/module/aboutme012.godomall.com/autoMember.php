<?php
include '../../inc/header.php';

$insertSet	= new insertSet('es_member', trimPostRequest('insertMode'));

$addressTableFl = (trimPostRequest('addressTableFl') == 'Y') ? true : false;

//------------------------------------------------------
// - Advice - 멀티 쿼리 출력 전용 필드값 셋팅
//------------------------------------------------------
$insertSet->arrayFieldName = array(
	'memNo',
	'memId',
	'groupSno',
	'memNm',
	'nickNm',
	'memPw',
	'appFl',
	'sexFl',
	'birthDt',
	'calendarFl',
	'email',
	'zipcode',
	'zonecode',
	'address',
	'addressSub',
	'phone',
	'cellPhone',
	'fax',
	'memberFl',
	'company',
	'service',
	'item',
	'busiNo',
	'ceo',
	'comZipcode',
	'comZonecode',
	'comAddress',
	'comAddressSub',
	'mileage',
	'deposit',
	'maillingFl',
	'smsFl',
	'marriFl',
	'marriDate',
	'entryDt',
	'regDt',
	'approvalDt',
	'lastLoginDt',
	'lastLoginIp',
	'loginCnt',
	'memo',
	'adminMemo',
	'recommId',
	'ex1',
	'ex2',
	'ex3',
	'ex4',
	'ex5',
	'ex6',
	'privateApprovalFl',
	'privateApprovalOptionFl',
	'privateOfferFl',
	'privateConsignFl',
	'foreigner',
	'dupeinfo',
	'adultFl',
);
//------------------------------------------------------

$memberDeleteNo = trimPostRequest('memberDeleteNo');

$arrayMemberCheckPostData	= array();		// 이전 후 쇼핑몰 회원 데이터 체크
$arrayQueryPostData			= array();		// 생성 쿼리 저장 배열

$arrayMemberCheckPostData	= array(
	'mode'				=> 'memberCheck',
	'memberDeleteFlag'	=> 1,
	'memberDeleteNo'		=> $memberDeleteNo,
);

//------------------------------------------------------
// - Advice - 회원 테이블 이전 전 필드명
//------------------------------------------------------
$memNoChange				= trimPostRequest('memNo');				// 회원 일련번호
$memIdChange				= trimPostRequest('memId');				// 아이디
$groupSnoChange				= trimPostRequest('groupSno');			// 등급
$memNmChange				= trimPostRequest('memNm');				// 이름
$nickNmChange				= trimPostRequest('nickNm');			// 닉네임
$memPwChange				= trimPostRequest('memPw');				// 비밀번호
$appFlChange				= trimPostRequest('appFl');				// 승인여부
$sexFlChange				= trimPostRequest('sexFl');				// 성별
$birthDtChange				= trimPostRequest('birthDt');			// 생년월일
$calendarFlChange			= trimPostRequest('calendarFl');		// 양/음력 구분
$emailChange				= trimPostRequest('email');				// 이메일
$zipcodeChange				= trimPostRequest('zipcode');			// 우편번호
$zonecodeChange				= trimPostRequest('zonecode');			// 신규 우편번호
$addressChange				= trimPostRequest('address');			// 주소
$phoneChange				= trimPostRequest('phone');				// 연락처
$cellPhoneChange			= trimPostRequest('cellPhone');			// 핸드폰
$faxChange					= trimPostRequest('fax');				// 팩스
$memberFlChange				= trimPostRequest('memberFl');			// 회원 구분
$companyChange				= trimPostRequest('company');			// 회사명
$serviceChange				= trimPostRequest('service');			// 업태
$itemChange					= trimPostRequest('item');				// 종목
$busiNoChange				= trimPostRequest('busiNo');			// 사업자 번호
$ceoChange					= trimPostRequest('ceo');				// 대표자명
$comZipcodeChange			= trimPostRequest('comZipcode');		// 사업장 우편번호
$comZonecodeChange			= trimPostRequest('comZonecode');		// 사업장 신규 우편번호
$comAddressChange			= trimPostRequest('comAddress');		// 사업장 주소
$mileageChange				= trimPostRequest('mileage');			// 적립금
$depositChange				= trimPostRequest('deposit');			// 예치금
$maillingFlChange			= trimPostRequest('maillingFl');		// 메일링 수신여부
$smsFlChange				= trimPostRequest('smsFl');				// SMS 수신여부
$marriFlChange				= trimPostRequest('marriFl');			// 결혼여부
$marriDateChange			= trimPostRequest('marriDate');			// 결혼 기념일
$entryDtChange				= trimPostRequest('entryDt');			// 가입일
$approvalDtChange			= trimPostRequest('approvalDt');			// 가입승인일
$lastLoginDtChange			= trimPostRequest('lastLoginDt');		// 최종 로그인 일자
$lastLoginIpChange			= trimPostRequest('lastLoginIp');		// 최종 로그인 IP
$loginCntChange				= trimPostRequest('loginCnt');			// 로그인 횟수
$memoChange					= trimPostRequest('memo');				// 메모
$adminMemoChange			= trimPostRequest('adminMemo');			// 관리자 메모
$recommIdChange				= trimPostRequest('recommId');			// 추천인 ID
$ex1Change					= trimPostRequest('ex1');				// 추가1
$ex2Change					= trimPostRequest('ex2');				// 추가2
$ex3Change					= trimPostRequest('ex3');				// 추가3
$ex4Change					= trimPostRequest('ex4');				// 추가4
$ex5Change					= trimPostRequest('ex5');				// 추가5
$ex6Change					= trimPostRequest('ex6');				// 추가6
$privateApprovalFlChange		= trimPostRequest('privateApprovalFl');			// 개인정보수집이용(필수)
$privateApprovalOptionFlChange	= trimPostRequest('privateApprovalOptionFl');	// 개인정보수집이용(선택)
$privateOfferFlChange			= trimPostRequest('privateOfferFl');			// 개인정보 3자 제공
$privateConsignFlChange			= trimPostRequest('privateConsignFl');			// 개인정보 위탁 동의
$foreignerChange				= trimPostRequest('foreigner');					// 내외국인 여부
$dupeinfoChange					= trimPostRequest('dupeinfo');					// 중복가입확인정보
$adultFlChange					= trimPostRequest('adultFl');					// 성인 여부
//------------------------------------------------------

//------------------------------------------------------
// - Advice - 주소 테이블 이전 전 필드명
//------------------------------------------------------

//------------------------------------------------------

//------------------------------------------------------
// - Advice - 부가 설정
//------------------------------------------------------
$update_chk					= trimPostRequest('update_chk');		// 필드 업데이트 유무
$deleteField				= trimPostRequest('delete_field');		// 회원 삭제 구분 필드

$rep_groupSno_before		= trimPostRequest('rep_groupSno_before');	//회원등급 변경 전 데이터 변수
$rep_groupSno_after			= trimPostRequest('rep_groupSno_after');	//회원등급 변경 후 데이터 변수
$repgroupSnoCnt				= trimPostRequest('repgroupSnoCnt');		//회원등급 변경 카운터 변수
//------------------------------------------------------

//------------------------------------------------------
// - Advice - 기본값 설정
//------------------------------------------------------
$dataCnt	= 0;
$memNo		= 0;

if($memberDeleteNo === '') $memberDeleteNo = 0;

$memNo = $memberDeleteNo + 1;
//------------------------------------------------------

//------------------------------------------------------
// - Advice - 이전 후 쇼핑몰 데이터 초기화
//------------------------------------------------------
if($mode == "start") {
	$arrayQueryPostData[] = "Delete From es_member Where memNo > $memberDeleteNo;";
	$arrayQueryPostData[] = "Truncate Table es_attendanceCheck";					//출석체크 참석
	$arrayQueryPostData[] = "Truncate Table es_attendanceReply";					//출석체크 댓글
	$arrayQueryPostData[] = "Truncate Table es_boardRecommend";						//게시글 추천
	$arrayQueryPostData[] = "Truncate Table es_cart";								//장바구니
	$arrayQueryPostData[] = "Truncate Table es_cartStatistics";						//장바구니 통계
	$arrayQueryPostData[] = "Truncate Table es_cartWrite";							//수기주문용 장바구니
	$arrayQueryPostData[] = "Truncate Table es_comebackCouponMember";				//수기주문용 장바구니 (es_cart 테이블과 동일)
	$arrayQueryPostData[] = "Truncate Table es_couponOfflineCode";					//오프라인쿠폰코드
	$arrayQueryPostData[] = "Truncate Table es_crmCounsel";							//crm 상담내역
	$arrayQueryPostData[] = "Truncate Table es_goodsRestock";						//상품 재입고 알림 신청 내역
	$arrayQueryPostData[] = "Truncate Table es_mailSendList";						//MAIL 발송 리스트
	$arrayQueryPostData[] = "Truncate Table es_memberCoupon";						//쿠폰
	$arrayQueryPostData[] = "Truncate Table es_memberDeposit";						//회원 예치금
	$arrayQueryPostData[] = "Truncate Table es_memberHackout";						//회원 탈퇴 리스트
	$arrayQueryPostData[] = "Truncate Table es_memberHistory";						//회원정보 수정 이력 테이블
	$arrayQueryPostData[] = "Truncate Table es_memberInvoiceInfo";					//세금계산서/현금영수증 입력 정보
	$arrayQueryPostData[] = "Truncate Table es_memberLoginLog";						//회원로그인로그
	$arrayQueryPostData[] = "Truncate Table es_memberMileage";						//회원 마일리지
	$arrayQueryPostData[] = "Truncate Table es_memberModifyEventResult";			//회원 정보 수정 이벤트 참여 내역
	$arrayQueryPostData[] = "Truncate Table es_memberNotificationLog";				//회원 알림 내역
	$arrayQueryPostData[] = "Truncate Table es_memberSleep";						//휴면회원
	$arrayQueryPostData[] = "Truncate Table es_memberSns";							//SNS 회원관리
	$arrayQueryPostData[] = "Truncate Table es_order";								//주문서 기본정보
	$arrayQueryPostData[] = "Truncate Table es_orderInfo";							//주문정보
	$arrayQueryPostData[] = "Truncate Table es_orderGoods";							//주문 상품정보
	$arrayQueryPostData[] = "Truncate Table es_orderDelivery";						//주문서 배송정보
	$arrayQueryPostData[] = "Truncate Table es_orderOriginal";						//주문서 기본정보-최초정보
	$arrayQueryPostData[] = "Truncate Table es_orderSalesStatistics";				//매출통계
	$arrayQueryPostData[] = "Truncate Table es_orderShippingAddress";				//주문 정보(주문자,수취인)
	$arrayQueryPostData[] = "Truncate Table es_plusMemoArticle";					//플러스샵메모게시판의 게시글
	$arrayQueryPostData[] = "Truncate Table es_plusReviewArticle";					//플러스리뷰 게시글
	$arrayQueryPostData[] = "Truncate Table es_plusReviewMemo";						//플러스리뷰 댓글
	$arrayQueryPostData[] = "Truncate Table es_plusReviewPopupSkip";				//플러스리뷰 메인 팝업 스킵 상품목록
	$arrayQueryPostData[] = "Truncate Table es_plusReviewRecommend";				//플러스 리뷰 추천
	$arrayQueryPostData[] = "Truncate Table es_pollResult";							//설문조사결과
	$arrayQueryPostData[] = "Truncate Table es_smsSendList";						//SMS 발송 리스트
	$arrayQueryPostData[] = "Truncate Table es_visitStatistics";					//방문통계
	$arrayQueryPostData[] = "Truncate Table es_visitStatisticsUser";				//방문통계-방문자
	$arrayQueryPostData[] = "Truncate Table es_wish";								//찜리스트
	$arrayQueryPostData[] = "Truncate Table es_wishStatistics";						//관심상품 통계
}

//------------------------------------------------------
// - Advice - 주소 테이블 추가 존재시 배열 변수 셋팅
//------------------------------------------------------
if($addressTableFl) {
	$shippingAddressInsertSet	= new insertSet('es_orderShippingAddress', trimPostRequest('insertMode'));
	$shippingAddressInsertSet->arrayFieldName = array(
		'memNo',
		'shippingName',
		'shippingPhone',
		'shippingCellPhone',
		'shippingTitle',
		'shippingZipCode',
		'shippingZonecode',
		'shippingAddress',
		'shippingAddressSub',
		'regDt',
	);

	$addressTableData	= array();
	$addressData		= array();

	//------------------------------------------------------
	// - Advice - 주소 테이블 전역 변수
	//------------------------------------------------------
	$defaultAddressNo			= trimPostRequest('defaultAddressNo');			//회원 테이블 주소 매칭 필드
	$memberAddressID			= trimPostRequest('memberAddressID');			//회원 주소 테이블 매칭 필드

	$ATaddressNo				= trimPostRequest('ATaddressNo');				//주소 테이블 일련번호
	$ATaddressID				= trimPostRequest('ATaddressID');			//주소 테이블 매칭 필드

	$ATnameChange				= trimPostRequest('ATmemNm');					//수취인 이름
	$ATzoneCodeChange			= trimPostRequest('ATzoneCode');				//우편번호(구역번호)
	$ATaddressChange			= trimPostRequest('ATaddress');					//주소
	$ATaddressSubChange			= trimPostRequest('ATaddressSub');				//상세 주소
	$ATphoneChange				= trimPostRequest('ATphone');					//수취인 연락처
	$ATcellPhoneChange			= trimPostRequest('ATcellPhone');				//수취인 핸드폰
	//------------------------------------------------------
	
	$addressData = subTableGetData(trimPostRequest('address_data_type'), trimPostRequest('address_data_name'), trimPostRequest('address_select_field'), trimPostRequest('address_sort'));

	//------------------------------------------------------
	// - Advice - 주소 테이블 데이터를 추가 주소 배열에 삽입
	//------------------------------------------------------
	foreach ($addressData as $addressTableRow) {
		$ATZoneCode			= '';
		$ATZoneCode1		= '';
		$ATZoneCode2		= '';
		if(trimPostRequest('ATzoneCodeCnt') > 1){
			for ($i = 0; $i < count($ATzoneCodeChange); $i++){
				$ATZoneCode .= $addressTableRow[$ATzoneCodeChange[$i]];
			}
		} else {
			$addressTableRow[$ATzoneCodeChange[0]] = defaultReplace($addressTableRow[$ATzoneCodeChange[0]]);
			if (strlen($addressTableRow[$ATzoneCodeChange[0]]) === 6) {
				$ATZoneCode1 = Left($addressTableRow[$ATzoneCodeChange[0]], 3);
				$ATZoneCode2 = Right($addressTableRow[$ATzoneCodeChange[0]], 3);
				$ATZoneCode = $ATZoneCode1 . $ATZoneCode2;
			}
			else if (strlen($addressTableRow[$ATzoneCodeChange[0]]) === 5) {
				$ATZoneCode = $addressTableRow[$ATzoneCodeChange[0]];
			}
		}
		
		if (strlen($ATZoneCode) > 5) {
			$ATZoneCode = zipCodeCreate($ATZoneCode);
		}

		//-----------------------------------------------------------
		//- Advice - 전화번호
		//- 전화번호는 추가 필드 가능
		//-----------------------------------------------------------
		$ATphone = '';
		if(trimPostRequest('ATphoneCnt') > 1){
			for ($i = 0; $i < count($ATphoneChange); $i++){
				$ATphone .= $row[$ATphoneChange[$i]];
			}
		} else {
			$row[$ATphoneChange[0]] = defaultReplace($row[$ATphoneChange[0]]);
			$ATphone = $row[$ATphoneChange[0]];
		}
		if ($ATphone) {
			$ATphone = telCreate($ATphone);
		}
		//-----------------------------------------------------------

		//-----------------------------------------------------------
		//- Advice - 핸드폰
		//- 핸드폰은 추가 필드 가능
		//-----------------------------------------------------------
		$ATcellPhone = '';
		if(trimPostRequest('ATcellPhoneCnt') > 1){
			for ($i = 0; $i < count($ATcellPhoneChange); $i++){
				$ATcellPhone .= $row[$ATcellPhoneChange[$i]];
			}
		} else {
			$row[$ATcellPhoneChange[0]] = defaultReplace($row[$ATcellPhoneChange[0]]);
			$ATcellPhone = $row[$ATcellPhoneChange[0]];
		}
		if ($ATcellPhone) {
			$ATcellPhone = telCreate($ATcellPhone);
		}
		//-----------------------------------------------------------

		$ATname = $row[$ATnameChange[0]];

		if ($ATZoneCode) {
			$ATaddress			= '';
			$ATaddressSub		= '';
			$arrayOriATaddress	= array();
			$oriATaddress		= '';

			if($ATaddressChange[0]){
				for ($i = 0; $i < count($ATaddressChange); $i++){
					$arrayOriATaddress[] = $addressTableRow[$ATaddressChange[$i]];
				}
			}

			$oriATaddress = implode(' ', $arrayOriATaddress);
			unset($arrayOriATaddress);
			
			list($ATaddress, $ATaddressSub) = addressMake($oriATaddress);
			
			$addressTableData[$addressTableRow[$ATaddressID[0]]][] = array($ATZoneCode, $ATaddress, $ATaddressSub, $addressTableRow[$ATaddressNo[0]], $ATphone, $ATcellPhone, $ATname);
		}
		
	}
	//------------------------------------------------------
}
//------------------------------------------------------

//------------------------------------------------------
// - Advice - 삭제 되지 않는 회원 정보 추출
//------------------------------------------------------
$object = xmlUrlRequest($url, $arrayMemberCheckPostData);
$memberData = $object->memberData;

$arrayMember = array();
foreach($memberData as $value) {
	$newMemNo = (int)$value->attributes()->memNo;
	$arrayMember[urldecode((string)$value)] = $newMemNo;
}
//------------------------------------------------------

$arrayOldMember = array();
$row = '';
if(trimPostRequest('data_type') == 'csv'){/*** CSV ***/
	$fp = fopen($csvFilePath . trimPostRequest('data_name') . '.csv', 'r' );
	$tt = fgetcsv($fp, 1500000, ',');

} else if(trimPostRequest('data_type') == 'sql') {/*** SQL ***/
	$sort = (trimPostRequest('sort')) ? ' order by ' . trimPostRequest('sort') : '';
	
	$res = $db->query("select " . trimPostRequest('select_field') . " from " . trimPostRequest('data_name') . $sort);
}

$allShippingAddressCount = 0;
while($row = (trimPostRequest('data_type') === 'csv') ? fgetcsv($fp, 1500000, ',') : $db->fetch($res)) {
	$newMember = array();
	$updateYN = 'N';
	$updateMemNo = 0;
	

	if($arrayMember[$row[$memIdChange]]){
		$updateYN = 'Y';
		$updateMemNo = $arrayMember[$row[$memIdChange]];
		
		if (trimPostRequest('updateYN') != 'Y') {
			continue;
		}
	}

	if($deleteField){
		if (trimPostRequest('delete_type')) {
			if((string)$row[$deleteField] == trimPostRequest('delete_type')) continue;
		}
		else {
			if ($row[$deleteField] != '') continue;
		}
	}

	$newMember = array();

	//-----------------------------------------------------------
	//- Advice - 메일,sms수신,결혼 여부 등 flag data
	//-----------------------------------------------------------
	$maillingFl					= '';
	$smsFl						= '';
	$marriFl					= '';
	$calendarFl					= '';

	$maillingFl					= (trimPostRequest('maillingFl_type') == 'y') ? 'y' : flagChange('yn', $row[$maillingFlChange]);
	$smsFl						= (trimPostRequest('smsFl_type') == 'y') ? 'y' : flagChange('yn', $row[$smsFlChange]);
	$marriFl					= flagChange('yn', $row[$marriFlChange]);
	$calendarFl					= ($calendarFlChange && $row[$calendarFlChange]) ? flagChange('sl', $row[$calendarFlChange]) : 's';
	$memberFl					= ($memberFlChange && trimPostRequest('memberFl_type') === $row[$memberFlChange]) ? 'business' : 'personal';

	$appFl = 'y';
	if ($row[$appFlChange]) {
		$appFl		= ($row[$appFlChange] == trimPostRequest('appFl_type')) ? 'n' : 'y';
	}

	if ($privateApprovalFlChange != '') {
		$privateApprovalFl = (trimPostRequest('privateApprovalFl_type') != '' && trimPostRequest('privateApprovalFl_type') == $row[$privateApprovalFlChange]) ? 'n' : flagChange('yn', $row[$privateApprovalFlChange]);
	} else {
		$privateApprovalFl = 'y';
	}
	
	if ($privateApprovalOptionFlChange != '') {
		$privateApprovalOptionFl = (trimPostRequest('privateApprovalOptionFl_type') != '' && trimPostRequest('privateApprovalOptionFl_type') == $row[$privateApprovalOptionFlChange]) ? 'n' : flagChange('yn', $row[$privateApprovalOptionFlChange]);
	}
	else {
		$privateApprovalOptionFl = 'NULL';
	}

	if ($privateOfferFlChange != '') {
		$privateOfferFl = (trimPostRequest('privateOfferFl_type') != '' && trimPostRequest('privateOfferFl_type') == $row[$privateOfferFlChange]) ? 'n' : flagChange('yn', $row[$privateOfferFlChange]);
	}
	else {
		$privateOfferFl = 'NULL';
	}

	if ($privateConsignFlChange != '') {
		$privateConsignFl = (trimPostRequest('privateConsignFl_type') != '' && trimPostRequest('privateConsignFl_type') == $row[$privateConsignFlChange]) ? 'n' : flagChange('yn', $row[$privateConsignFlChange]);
	}
	else {
		$privateConsignFl = 'NULL';
	}
	
	if ($foreignerChange != '') {
		$foreigner = (trimPostRequest('foreigner_type') != '' && trimPostRequest('foreigner_type') == $row[$foreignerChange]) ? '0' : flagChange('10', $row[$foreignerChange]);
	}
	else {
		$foreigner = '1';
	}
	
	if ($adultFlChange != '') {
		$adultFl = (trimPostRequest('adultFl_type') != '' && trimPostRequest('adultFl_type') == $row[$adultFlChange]) ? 'y' : flagChange('yn', $row[$adultFlChange]);
	}
	else {
		$adultFl = 'n';
	}
	//-----------------------------------------------------------

	//-----------------------------------------------------------
	//- Advice - 성별
	//- 성별은 단일필드
	//-----------------------------------------------------------
	$sexFl = '';
	if(trimPostRequest('sexFl_type')){
		if(trimPostRequest('sexFl_type') == 'jumin'){
			$sexFl = Right($row[$sexFlChange], 7);
			$sexFl = Left($sexFl, 1);
			$sexFl = flagChange('sex', $sexFl);
		} else { 
			$sexFl = flagChange('sex', $row[$sexFlChange]);
		}
	} else {
		$sexFl = ($sexFlChange && $row[$sexFlChange]) ? flagChange('sex', $row[$sexFlChange]) : '';
	}
	//-----------------------------------------------------------

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
	if ($phone) {
		$phone = telCreate($phone);
	}
	//-----------------------------------------------------------

	//-----------------------------------------------------------
	//- Advice - 핸드폰
	//- 핸드폰은 추가 필드 가능
	//-----------------------------------------------------------
	$cellPhone = '';
	if(trimPostRequest('cellPhoneCnt') > 1){
		for ($i = 0; $i < count($cellPhoneChange); $i++){
			$cellPhone .= $row[$cellPhoneChange[$i]];
		}
	} else {
		$row[$cellPhoneChange[0]] = defaultReplace($row[$cellPhoneChange[0]]);
		$cellPhone = $row[$cellPhoneChange[0]];
	}
	if ($cellPhone) {
		$cellPhone = telCreate($cellPhone);
	}
	//-----------------------------------------------------------

	//-----------------------------------------------------------
	//- Advice - 팩스
	//- 팩스는 추가 필드 가능
	//-----------------------------------------------------------
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

	//-----------------------------------------------------------
	//- Advice - 생년월일
	//- 생년은 단일 필드 월일은 추가 필드 가능
	//-----------------------------------------------------------
	$birthYear='';
	$birth = '';
	$birthDt = '';
	if(trimPostRequest('birthDt_type') == 'jumin'){
		$birthYear = Left($row[$birthDtChange[0]], 2);
		$birthYear = str_replace('/', '', $birthYear);
		$birthYear = str_replace('-', '', $birthYear);
		$birth = Left($row[$birthDtChange[0]], 6);
		$birth = Right($birth, 4);
		if($birthYear){
			if($birthYear <= 10){
				$birthYear = "20" . $birthYear;
			} else {
				$birthYear = "19" . $birthYear;
			}
		}
		$birthDt = $birthYear . $birth;
		$birthDt = date('Y-m-d', strtotime(str_replace('-', '', str_replace('/', '', $birthDt))));
	} else {
		for ($i = 0; $i < trimPostRequest('birthDtCnt'); $i++) {
			$birthDt .= trim($row[$birthDtChange[$i]]);
		}
		$birthDt = ($birthDt == '') ? '' : date('Y-m-d', strtotime(str_replace('-', '', str_replace('/', '', $birthDt))));
	}
	//-----------------------------------------------------------

	//-----------------------------------------------------------
	//- Advice - 우편번호
	//- 우편번호는 추가 필드 가능
	//-----------------------------------------------------------
	$zipcode = '';
	$zipcode1 = '';
	$zipcode2 = '';
	$zonecode = '';

	if (trimPostRequest('zipcodeCnt') > 1) {
		for ($i = 0; $i < count($zipcodeChange); $i++) {
			$zipcode .= $row[$zipcodeChange[$i]];
		}
	} else {
		$row[$zipcodeChange[0]] = defaultReplace($row[$zipcodeChange[0]]);
		$zipcode1 = Left($row[$zipcodeChange[0]], 3);
		$zipcode2 = Right($row[$zipcodeChange[0]], 3);
		$zipcode = $zipcode1 . $zipcode2;
	}

	if ($zipcode) {
		$zipcode = zipCodeCreate($zipcode);
	}
	if (strlen($zipcode) === 5) {
		$zonecode = $zipcode;
		$zipcode = '';
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
			$arrayOriAddress[] = trim($row[$addressChange[$i]]);
		}
		$oriAddress = implode(' ', $arrayOriAddress);
	}
	list($address, $addressSub) = addressMake($oriAddress);
	//-----------------------------------------------------------

		//-----------------------------------------------------------
	//- Advice - 회사 우편번호
	//- 우편번호는 추가 필드 가능
	//-----------------------------------------------------------
	$comZipcode = '';
	$comZipcode1 = '';
	$comZipcode2 = '';
	$comZonecode = '';
	if (trimPostRequest('comZipcodeCnt') > 1) {
		for ($i = 0; $i < count($comZipcodeChange); $i++) {
			$comZipcode .= $row[$comZipcodeChange[$i]];
		}
	} else {
		$row[$comZipcodeChange[0]] = defaultReplace($row[$comZipcodeChange[0]]);
		$comZipcode1 = Left($row[$comZipcodeChange[0]], 3);
		$comZipcode2 = Right($row[$comZipcodeChange[0]], 3);
		$comZipcode = $comZipcode1 . $comZipcode2;
	}
	if ($comZipcode) {
		$comZipcode = zipCodeCreate($comZipcode);
	}

	if (strlen($comZipcode) === 5) {
		$comZonecode = $comZipcode;
		$comZipcode = '';
	}
	//-----------------------------------------------------------


	//-----------------------------------------------------------
	//- Advice - 회사 주소
	//- 회사 주소는 추가 필드 가능
	//-----------------------------------------------------------
	$comAddress		= '';
	$comAddressSub	= '';
	$arrayOriComAddressAddress = array();
	$oriComAddressAddress		= '';
	if($comAddressChange[0]){
		for ($i = 0; $i < count($comAddressChange); $i++){
			$arrayOriComAddressAddress[] = trim($row[$comAddressChange[$i]]);
		}
		$oriComAddressAddress = implode(' ', $arrayOriComAddressAddress);
	}

	list($comAddress, $comAddressSub) = addressMake($oriComAddressAddress);
	//-----------------------------------------------------------
	
	//-----------------------------------------------------------
	//- Advice - 비밀번호
	//- 비밀번호는 다중 필드 가능 임시 비밀번호인 경우
	//-----------------------------------------------------------
	$memPw = '';
	if (trimPostRequest('password_type') === 'password') {
		$memPw = "password('" . addslashes($row[$memPwChange[0]]) . "')";
	}
	else if (trimPostRequest('password_type') === 'temp') {
		for ($i = 0; $i <= trimPostRequest('memPwCnt') - 1; $i++) {
			if ($row[$memPwChange[$i]] && $memPw == '') {
				$memPw = Right($row[$memPwChange[$i]], 4);
			}
		}
		
		if ($memPw) {
			$memPw = "password('" . trimPostRequest('tempPassword') . $memPw . "')";
		}
		else {
			$memPw = "password('" . trimPostRequest('tempPassword') . '0000' . "')";
		}
	}
	else {
		$memPw = $row[$memPwChange[0]];
	}
	//-----------------------------------------------------------

	//-----------------------------------------------------------
	//- Advice - 회원레벨
	//- 회원레벨 단일 필드
	//-----------------------------------------------------------
	$groupSno = 1;
	if($groupSnoChange != ''){
		if ($repgroupSnoCnt > 1) {
			$groupSno = dataIfChange($row[$groupSnoChange],$rep_groupSno_before,$rep_groupSno_after,$repgroupSnoCnt);
		}
		else {
			$groupSno = $row[$groupSnoChange];
		}
		if(!$groupSno) $groupSno = 1;
	}
	//----------------------------------------------------------

	//-----------------------------------------------------------
	//- Advice - 가입일
	//- 가입일은 다중 필드
	//-----------------------------------------------------------
	$entryDt = '';
	if(trimPostRequest('entryDtCnt') > 1){
		for ($i = 0; $i < count($entryDtChange); $i++){
			$entryDt .= $row[$entryDtChange[$i]];
		}
	} else {
		if (!$row[$entryDtChange[0]]) {
			$entryDt = '0000-00-00 00:00:00';//date('Y-m-d h:i:s');
		}
		else {
			$entryDt = $row[$entryDtChange[0]];
		}
	}

	$entryDt = dateCreate($entryDt);
	$regDt = $entryDt;
	//-----------------------------------------------------------

	//-----------------------------------------------------------
	//- Advice - 가입승인일 없는 경우 가입일로 대체
	//- 가입일은 다중 필드$approvalDtChange
	//-----------------------------------------------------------
	$approvalDt = '';
	if(trimPostRequest('approvalDtCnt') > 1){
		for ($i = 0; $i < count($approvalDtChange); $i++){
			$approvalDt .= $row[$approvalDtChange[$i]];
		}
	} else {
		if (!$row[$approvalDtChange[0]]) {
			$approvalDt = '0000-00-00 00:00:00';//date('Y-m-d h:i:s');
		}
		else {
			$approvalDt = $row[$approvalDtChange[0]];
		}
	}

	$approvalDt = dateCreate($approvalDt);
	if ($approvalDt == '0000-00-00 00:00:00' || !$approvalDt) {
		$approvalDt = $entryDt;
	}
	//-----------------------------------------------------------

	//-----------------------------------------------------------
	//- Advice - 최종로그인
	//- 최종로그인은 다중 필드
	//-----------------------------------------------------------
	$lastLoginDt = '';
	if (trimPostRequest('lastLoginDt_type') === 'now') {
		$lastLoginDt = date('Y-m-d h:i:s');
	}
	else if (trimPostRequest('lastLoginDt_type') === 'valid' ){
		$lastLoginDt = $lastLoginDtChange[0];
	}
	else {
		if(trimPostRequest('lastLoginDtCnt') > 1){
			for ($i = 0; $i < count($lastLoginDtChange); $i++){
				$lastLoginDt .= $row[$lastLoginDtChange[$i]];
			}
		} else {
			$lastLoginDt = $row[$lastLoginDtChange[0]];
		}
	}
	$lastLoginDt = dateCreate($lastLoginDt);
	//-----------------------------------------------------------

	//-----------------------------------------------------------
	//- Advice - 결혼기념일
	//- 결혼기념일은 다중 필드
	//-----------------------------------------------------------
	$marriDate = '';
	if(trimPostRequest('marriDateCnt') > 1){
		for ($i = 0; $i < count($marriDateChange); $i++){
			$marriDate .= $row[$marriDateChange[$i]];
		}
	} else {
		$marriDate = $row[$marriDateChange[0]];
	}
	if ($marriDate) {
		$marriDate = dateCreate($marriDate);
		$marriDate = date('Ymd', strtotime($marriDate));
	}
	//-----------------------------------------------------------

	//-----------------------------------------------------------
	//- Advice - 적립금, 예치금
	//- 구매 총금액은 다중 필드
	//-----------------------------------------------------------
	$mileage = 0;
	if($_POST['mileageCnt'] > 1){
		for ($i = 0; $i < count($mileageChange); $i++){
			if($_POST['mileage_type'] == 'sum'){
				$mileage += str_replace(',','',$row[$mileageChange[$i]]);
			} else if($_POST['mileage_type'] == 'minus') {
				if($mileage){
					$mileage -= str_replace(',','',$row[$mileageChange[$i]]);
				} else {
					$mileage += str_replace(',','',$row[$mileageChange[$i]]);
				}
			}
		}
	} else {
		$mileage = str_replace(',','',$row[$mileageChange[0]]);
	}

	$deposit = 0;
	if($_POST['depositCnt'] > 1){
		for ($i = 0; $i < count($depositChange); $i++){
			if($_POST['deposit_type'] == 'sum'){
				$deposit += str_replace(',','',$row[$depositChange[$i]]);
			} else if($_POST['deposit_type'] == 'minus') {
				if($deposit){
					$deposit -= str_replace(',','',$row[$depositChange[$i]]);
				} else {
					$deposit += str_replace(',','',$row[$depositChange[$i]]);
				}
			}
		}
	} else {
		$deposit = str_replace(',','',$row[$mileageChange[0]]);
	}
	$deposit = str_replace(',','',$row[$depositChange[0]]);
	//-----------------------------------------------------------

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
	//- Advice - 관리자 메모
	//- 관리자 메모는 다중 필드
	//-----------------------------------------------------------
	$adminMemo = '';
	$arrayAdminMemo = array();
	if(trimPostRequest('adminMemoCnt') > 1){
		for ($i = 0; $i < count($adminMemoChange); $i++){
			$arrayAdminMemo[] = $row[$adminMemoChange[$i]];
		}
		$adminMemo = implode(' | ' . $arrayAdminMemo);
	} else {
		$adminMemo = $row[$adminMemoChange[0]];
	}
	//-----------------------------------------------------------

	//-----------------------------------------------------------
	//- Advice - 회원 추가정보
	//- 회원 추가정보는 다중 필드
	//-----------------------------------------------------------
	$ex1 ='';
	$ex2 ='';
	$ex3 ='';
	$ex4 ='';
	$ex5 ='';
	$ex6 ='';

	for($j=1;$j<=6;$j++){
		if(trimPostRequest('ex' . $j . 'Cnt') > 1){
			${'arrayex' . $j} = array();
			for ($i = 0; $i < count(${'ex' . $j . 'Change'}); $i++){
					${'arrayex' . $j}[] = $row[${'ex' . $j . 'Change'}[$i]];
			}
			${'ex' . $j} = implode(' ', ${'arrayex' . $j});
		} else {
			${'ex' . $j} = $row[${'ex' . $j . 'Change'}[0]];
		}
	}
	//-----------------------------------------------------------

	//-----------------------------------------------------------
	//- Advice - 회원 일련번호
	//- 회원 일련번호는 단일 필드
	//-----------------------------------------------------------
	if($memNoChange != ''){
		$memNo = $row[$memNoChange];
	}
	//-----------------------------------------------------------
	
	//-----------------------------------------------------------
	//- Advice - 사업자 등록번호
	//- 회원 일련번호는 다중 필드
	//-----------------------------------------------------------
	$busiNo = '';
	if(trimPostRequest('busiNoCnt') > 1){
		$arrayBusiNo = array();
		for ($i = 0; $i < count($busiNoChange); $i++){
			$arrayBusiNo[] = $row[$busiNoChange[$i]];
		}
		$busiNo = implode('-', $arrayBusiNo);
	}
	else {
		$busiNo			= $row[$busiNoChange[0]];
	}
	//-----------------------------------------------------------
	
	//-----------------------------------------------------------
	//- Advice - 최종 로그인 IP
	//- 최종 로그인 IP는 다중 필드
	//-----------------------------------------------------------
	$lastLoginIp = '';
	if (trimPostRequest('lastLoginIpCnt')) {
		$arrayLastLoginIp = array();
		for ($i = 0; $i < count($lastLoginIpChange); $i++){
			$arrayLastLoginIp[] = $row[$lastLoginIpChange[$i]];
		}
		$lastLoginIp = implode('.', $arrayLastLoginIp);
	}
	else {
		$lastLoginIp = $row[$lastLoginIpChange[0]];
	}
	//-----------------------------------------------------------

	$memId			= $row[$memIdChange];



    if($make_uid[$memId] == "") {
        $make_uid[$memId] = $memId;
    }
    else {
        echo "중복아이디 : ".$memId ."<br>";
        continue;
    }

	$memNm			= $row[$memNmChange];

	$nickNm			= $row[$nickNmChange];
	$company		= $row[$companyChange];
	$service		= $row[$serviceChange];
	$item			= $row[$itemChange];
	$loginCnt		= $row[$loginCntChange];
	$recommId		= $row[$recommIdChange];
	$recommFl		= ($recommId) ? 'y' : 'n';
	$ceo			= $row[$ceoChange];

	$dupeinfo		= $row[$dupeinfoChange];

	if (!$zonecode) {
		$zonecode = $row[$zonecodeChange];
	}
	if (!$comZonecode) {
		$comZonecode = $row[$comZonecodeChange];
	}

	if (!empty($addressTableData[$row[$memberAddressID[0]]])) {
		$arrayShippingAddNewData = array();
		$shippingAddressCount = 1;
		foreach ($addressTableData[$row[$memberAddressID[0]]] as $arrayAddressTableRow) {
			$ATzipCode		= '';
			$ATZoneCode		= '';
			if (strlen($arrayAddressTableRow[0]) > 5) {
				$ATzipCode = $arrayAddressTableRow[0];
			}
			else {
				$ATZoneCode = $arrayAddressTableRow[0];
			}
			if ($arrayAddressTableRow[4]) {
				$ATphone = $arrayAddressTableRow[4];
			}
			else {
				$ATphone = $phone;
			}
			if ($arrayAddressTableRow[5]) {
				$ATcellPhone = $arrayAddressTableRow[5];
			}
			else {
				$ATcellPhone = $cellPhone;
			}
			if ($arrayAddressTableRow[6]) {
				$ATmemNm = $arrayAddressTableRow[6];
			}
			else {
				$ATmemNm = $memNm;
			}
			
			if (!$zipcode && $row[$defaultAddressNo[0]] == $arrayAddressTableRow[3]) {
				$zipcode	= $ATzipCode;
				$zonecode	= $ATZoneCode;
				$address	= $arrayAddressTableRow[1];
				$addressSub	= $arrayAddressTableRow[2];
			}
			else {
				$arrayShippingAddNewData['memNo'] = $memNo;
				$arrayShippingAddNewData['shippingName'] = $ATmemNm;
				$arrayShippingAddNewData['shippingPhone'] = $ATphone;
				$arrayShippingAddNewData['shippingCellPhone'] = $ATcellPhone;
				$arrayShippingAddNewData['shippingTitle'] = '추가배송지' . $shippingAddressCount;
				$arrayShippingAddNewData['shippingZipCode'] = $ATzipCode;
				$arrayShippingAddNewData['shippingZonecode'] = $ATZoneCode;
				$arrayShippingAddNewData['shippingAddress'] = $arrayAddressTableRow[1];
				$arrayShippingAddNewData['shippingAddressSub'] = $arrayAddressTableRow[2];
				$arrayShippingAddNewData['regDt'] = 'now()';
				
				$shippingAddressInsertSet->querySet($arrayShippingAddNewData, $allShippingAddressCount);
				$shippingAddressCount++;
				$allShippingAddressCount++;
			}
		}
	}
	/* //카페24 SNS 회원 이전시 적용
	if (substr($memId, -2) == '@n') {
		$uuid = str_replace('@n', '', $memId);
		$arrayQueryPostData[] = "Insert Into es_memberSns Set 
									mallSno = 1,
									memNo = '" . $memNo . "',
									appId = 'godo',
									uuid = '" . $uuid . "',
									snsJoinFl = 'y',
									snsTypeFl = 'naver',
									connectFl = 'y',
									accessToken = '',
									refreshToken = '',
									regDt = now(),
									modDt = now();
								";
	}
	*/
	

	if($updateYN == 'N'){
		foreach ($insertSet->arrayFieldName as $fieldName) {
			$newMember[$fieldName]		=	${$fieldName};
		}

		$insertSet->querySet($newMember, $dataCnt + 1);
	}
	else {
		if (!empty($update_chk)) {
			foreach ($update_chk as $fieldName) {
				$newMember[$fieldName]		=	${$fieldName};
			}

			$arrayString=array();
			foreach($newMember as $key=>$value) 
			{
				if($key == 'memPw'){
					if(ereg('password', $value)){
						$arrayString[] = "$key = " . $value;
					} else{
						$arrayString[] = "$key = '" . addslashes($value) . "'";
					}
				} else {
					$arrayString[] = "$key = '" . addslashes($value) . "'";
				}
			}

			$arrayQueryPostData[] = "update es_member set " . implode(", ", $arrayString) . ", sleepFl = 'n' Where memNo ='" . $updateMemNo . "';";
		}

		//$newMember['dormant_regDate']		= '0000-00-00 00:00:00';
	}

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

	$memNo++;
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
	$arrayQueryPostData[] = "Delete From es_memberMileage Where reasonCd = '01005011' and contents = '데이터 이전 : 사용가능 적립금과 적립금 내역 동기화 목적 추가 로그' and mileage > 0;";
	$arrayQueryPostData[] = "Insert Into es_memberMileage (memNo, handleMode, reasonCd, deleteFl, afterMileage, mileage, contents, regDt) SELECT memNo, 'm' as handleMode, '01005011' as reasonCd, 'n' as deleteFl, mileage as afterMileage, mileage, '데이터 이전 : 사용가능 적립금과 적립금 내역 동기화 목적 추가 로그' as memo, now() as regDt FROM `es_member` WHERE mileage > 0;";

	$arrayQueryPostData[] = "update `es_member` m left join es_member ms on m.recommId = ms.memId Set m.recommId = ms.memId Where m.recommId is not null";

	$arrayQueryPostData[] = "OPTIMIZE TABLE es_member";
	$arrayQueryPostData[] = "OPTIMIZE TABLE es_orderShippingAddress";
	dumpSqlFileSet ($dumpFileName, $arrayQueryPostData);
	//echo '<script>parent.progress();</script>';
	//echo '<script>parent.progress(100);</script>';
}
echo '<div>작업 완료 총 : ' . number_format($dataCnt) . '건</div><script type="text/javascript">parent.configSubmitComplete("' . $dataCnt . '");</script>';

?>