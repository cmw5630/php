<?php
include '../../inc/header.php';

$insertSet	= new insertSet('es_member', trimPostRequest('insertMode'));

$addressTableFl = (trimPostRequest('addressTableFl') == 'Y') ? true : false;

//------------------------------------------------------
// - Advice - ��Ƽ ���� ��� ���� �ʵ尪 ����
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

$arrayMemberCheckPostData	= array();		// ���� �� ���θ� ȸ�� ������ üũ
$arrayQueryPostData			= array();		// ���� ���� ���� �迭

$arrayMemberCheckPostData	= array(
	'mode'				=> 'memberCheck',
	'memberDeleteFlag'	=> 1,
	'memberDeleteNo'		=> $memberDeleteNo,
);

//------------------------------------------------------
// - Advice - ȸ�� ���̺� ���� �� �ʵ��
//------------------------------------------------------
$memNoChange				= trimPostRequest('memNo');				// ȸ�� �Ϸù�ȣ
$memIdChange				= trimPostRequest('memId');				// ���̵�
$groupSnoChange				= trimPostRequest('groupSno');			// ���
$memNmChange				= trimPostRequest('memNm');				// �̸�
$nickNmChange				= trimPostRequest('nickNm');			// �г���
$memPwChange				= trimPostRequest('memPw');				// ��й�ȣ
$appFlChange				= trimPostRequest('appFl');				// ���ο���
$sexFlChange				= trimPostRequest('sexFl');				// ����
$birthDtChange				= trimPostRequest('birthDt');			// �������
$calendarFlChange			= trimPostRequest('calendarFl');		// ��/���� ����
$emailChange				= trimPostRequest('email');				// �̸���
$zipcodeChange				= trimPostRequest('zipcode');			// �����ȣ
$zonecodeChange				= trimPostRequest('zonecode');			// �ű� �����ȣ
$addressChange				= trimPostRequest('address');			// �ּ�
$phoneChange				= trimPostRequest('phone');				// ����ó
$cellPhoneChange			= trimPostRequest('cellPhone');			// �ڵ���
$faxChange					= trimPostRequest('fax');				// �ѽ�
$memberFlChange				= trimPostRequest('memberFl');			// ȸ�� ����
$companyChange				= trimPostRequest('company');			// ȸ���
$serviceChange				= trimPostRequest('service');			// ����
$itemChange					= trimPostRequest('item');				// ����
$busiNoChange				= trimPostRequest('busiNo');			// ����� ��ȣ
$ceoChange					= trimPostRequest('ceo');				// ��ǥ�ڸ�
$comZipcodeChange			= trimPostRequest('comZipcode');		// ����� �����ȣ
$comZonecodeChange			= trimPostRequest('comZonecode');		// ����� �ű� �����ȣ
$comAddressChange			= trimPostRequest('comAddress');		// ����� �ּ�
$mileageChange				= trimPostRequest('mileage');			// ������
$depositChange				= trimPostRequest('deposit');			// ��ġ��
$maillingFlChange			= trimPostRequest('maillingFl');		// ���ϸ� ���ſ���
$smsFlChange				= trimPostRequest('smsFl');				// SMS ���ſ���
$marriFlChange				= trimPostRequest('marriFl');			// ��ȥ����
$marriDateChange			= trimPostRequest('marriDate');			// ��ȥ �����
$entryDtChange				= trimPostRequest('entryDt');			// ������
$approvalDtChange			= trimPostRequest('approvalDt');			// ���Խ�����
$lastLoginDtChange			= trimPostRequest('lastLoginDt');		// ���� �α��� ����
$lastLoginIpChange			= trimPostRequest('lastLoginIp');		// ���� �α��� IP
$loginCntChange				= trimPostRequest('loginCnt');			// �α��� Ƚ��
$memoChange					= trimPostRequest('memo');				// �޸�
$adminMemoChange			= trimPostRequest('adminMemo');			// ������ �޸�
$recommIdChange				= trimPostRequest('recommId');			// ��õ�� ID
$ex1Change					= trimPostRequest('ex1');				// �߰�1
$ex2Change					= trimPostRequest('ex2');				// �߰�2
$ex3Change					= trimPostRequest('ex3');				// �߰�3
$ex4Change					= trimPostRequest('ex4');				// �߰�4
$ex5Change					= trimPostRequest('ex5');				// �߰�5
$ex6Change					= trimPostRequest('ex6');				// �߰�6
$privateApprovalFlChange		= trimPostRequest('privateApprovalFl');			// �������������̿�(�ʼ�)
$privateApprovalOptionFlChange	= trimPostRequest('privateApprovalOptionFl');	// �������������̿�(����)
$privateOfferFlChange			= trimPostRequest('privateOfferFl');			// �������� 3�� ����
$privateConsignFlChange			= trimPostRequest('privateConsignFl');			// �������� ��Ź ����
$foreignerChange				= trimPostRequest('foreigner');					// ���ܱ��� ����
$dupeinfoChange					= trimPostRequest('dupeinfo');					// �ߺ�����Ȯ������
$adultFlChange					= trimPostRequest('adultFl');					// ���� ����
//------------------------------------------------------

//------------------------------------------------------
// - Advice - �ּ� ���̺� ���� �� �ʵ��
//------------------------------------------------------

//------------------------------------------------------

//------------------------------------------------------
// - Advice - �ΰ� ����
//------------------------------------------------------
$update_chk					= trimPostRequest('update_chk');		// �ʵ� ������Ʈ ����
$deleteField				= trimPostRequest('delete_field');		// ȸ�� ���� ���� �ʵ�

$rep_groupSno_before		= trimPostRequest('rep_groupSno_before');	//ȸ����� ���� �� ������ ����
$rep_groupSno_after			= trimPostRequest('rep_groupSno_after');	//ȸ����� ���� �� ������ ����
$repgroupSnoCnt				= trimPostRequest('repgroupSnoCnt');		//ȸ����� ���� ī���� ����
//------------------------------------------------------

//------------------------------------------------------
// - Advice - �⺻�� ����
//------------------------------------------------------
$dataCnt	= 0;
$memNo		= 0;

if($memberDeleteNo === '') $memberDeleteNo = 0;

$memNo = $memberDeleteNo + 1;
//------------------------------------------------------

//------------------------------------------------------
// - Advice - ���� �� ���θ� ������ �ʱ�ȭ
//------------------------------------------------------
if($mode == "start") {
	$arrayQueryPostData[] = "Delete From es_member Where memNo > $memberDeleteNo;";
	$arrayQueryPostData[] = "Truncate Table es_attendanceCheck";					//�⼮üũ ����
	$arrayQueryPostData[] = "Truncate Table es_attendanceReply";					//�⼮üũ ���
	$arrayQueryPostData[] = "Truncate Table es_boardRecommend";						//�Խñ� ��õ
	$arrayQueryPostData[] = "Truncate Table es_cart";								//��ٱ���
	$arrayQueryPostData[] = "Truncate Table es_cartStatistics";						//��ٱ��� ���
	$arrayQueryPostData[] = "Truncate Table es_cartWrite";							//�����ֹ��� ��ٱ���
	$arrayQueryPostData[] = "Truncate Table es_comebackCouponMember";				//�����ֹ��� ��ٱ��� (es_cart ���̺�� ����)
	$arrayQueryPostData[] = "Truncate Table es_couponOfflineCode";					//�������������ڵ�
	$arrayQueryPostData[] = "Truncate Table es_crmCounsel";							//crm ��㳻��
	$arrayQueryPostData[] = "Truncate Table es_goodsRestock";						//��ǰ ���԰� �˸� ��û ����
	$arrayQueryPostData[] = "Truncate Table es_mailSendList";						//MAIL �߼� ����Ʈ
	$arrayQueryPostData[] = "Truncate Table es_memberCoupon";						//����
	$arrayQueryPostData[] = "Truncate Table es_memberDeposit";						//ȸ�� ��ġ��
	$arrayQueryPostData[] = "Truncate Table es_memberHackout";						//ȸ�� Ż�� ����Ʈ
	$arrayQueryPostData[] = "Truncate Table es_memberHistory";						//ȸ������ ���� �̷� ���̺�
	$arrayQueryPostData[] = "Truncate Table es_memberInvoiceInfo";					//���ݰ�꼭/���ݿ����� �Է� ����
	$arrayQueryPostData[] = "Truncate Table es_memberLoginLog";						//ȸ���α��ηα�
	$arrayQueryPostData[] = "Truncate Table es_memberMileage";						//ȸ�� ���ϸ���
	$arrayQueryPostData[] = "Truncate Table es_memberModifyEventResult";			//ȸ�� ���� ���� �̺�Ʈ ���� ����
	$arrayQueryPostData[] = "Truncate Table es_memberNotificationLog";				//ȸ�� �˸� ����
	$arrayQueryPostData[] = "Truncate Table es_memberSleep";						//�޸�ȸ��
	$arrayQueryPostData[] = "Truncate Table es_memberSns";							//SNS ȸ������
	$arrayQueryPostData[] = "Truncate Table es_order";								//�ֹ��� �⺻����
	$arrayQueryPostData[] = "Truncate Table es_orderInfo";							//�ֹ�����
	$arrayQueryPostData[] = "Truncate Table es_orderGoods";							//�ֹ� ��ǰ����
	$arrayQueryPostData[] = "Truncate Table es_orderDelivery";						//�ֹ��� �������
	$arrayQueryPostData[] = "Truncate Table es_orderOriginal";						//�ֹ��� �⺻����-��������
	$arrayQueryPostData[] = "Truncate Table es_orderSalesStatistics";				//�������
	$arrayQueryPostData[] = "Truncate Table es_orderShippingAddress";				//�ֹ� ����(�ֹ���,������)
	$arrayQueryPostData[] = "Truncate Table es_plusMemoArticle";					//�÷������޸�Խ����� �Խñ�
	$arrayQueryPostData[] = "Truncate Table es_plusReviewArticle";					//�÷������� �Խñ�
	$arrayQueryPostData[] = "Truncate Table es_plusReviewMemo";						//�÷������� ���
	$arrayQueryPostData[] = "Truncate Table es_plusReviewPopupSkip";				//�÷������� ���� �˾� ��ŵ ��ǰ���
	$arrayQueryPostData[] = "Truncate Table es_plusReviewRecommend";				//�÷��� ���� ��õ
	$arrayQueryPostData[] = "Truncate Table es_pollResult";							//����������
	$arrayQueryPostData[] = "Truncate Table es_smsSendList";						//SMS �߼� ����Ʈ
	$arrayQueryPostData[] = "Truncate Table es_visitStatistics";					//�湮���
	$arrayQueryPostData[] = "Truncate Table es_visitStatisticsUser";				//�湮���-�湮��
	$arrayQueryPostData[] = "Truncate Table es_wish";								//�򸮽�Ʈ
	$arrayQueryPostData[] = "Truncate Table es_wishStatistics";						//���ɻ�ǰ ���
}

//------------------------------------------------------
// - Advice - �ּ� ���̺� �߰� ����� �迭 ���� ����
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
	// - Advice - �ּ� ���̺� ���� ����
	//------------------------------------------------------
	$defaultAddressNo			= trimPostRequest('defaultAddressNo');			//ȸ�� ���̺� �ּ� ��Ī �ʵ�
	$memberAddressID			= trimPostRequest('memberAddressID');			//ȸ�� �ּ� ���̺� ��Ī �ʵ�

	$ATaddressNo				= trimPostRequest('ATaddressNo');				//�ּ� ���̺� �Ϸù�ȣ
	$ATaddressID				= trimPostRequest('ATaddressID');			//�ּ� ���̺� ��Ī �ʵ�

	$ATnameChange				= trimPostRequest('ATmemNm');					//������ �̸�
	$ATzoneCodeChange			= trimPostRequest('ATzoneCode');				//�����ȣ(������ȣ)
	$ATaddressChange			= trimPostRequest('ATaddress');					//�ּ�
	$ATaddressSubChange			= trimPostRequest('ATaddressSub');				//�� �ּ�
	$ATphoneChange				= trimPostRequest('ATphone');					//������ ����ó
	$ATcellPhoneChange			= trimPostRequest('ATcellPhone');				//������ �ڵ���
	//------------------------------------------------------
	
	$addressData = subTableGetData(trimPostRequest('address_data_type'), trimPostRequest('address_data_name'), trimPostRequest('address_select_field'), trimPostRequest('address_sort'));

	//------------------------------------------------------
	// - Advice - �ּ� ���̺� �����͸� �߰� �ּ� �迭�� ����
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
		//- Advice - ��ȭ��ȣ
		//- ��ȭ��ȣ�� �߰� �ʵ� ����
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
		//- Advice - �ڵ���
		//- �ڵ����� �߰� �ʵ� ����
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
// - Advice - ���� ���� �ʴ� ȸ�� ���� ����
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
	//- Advice - ����,sms����,��ȥ ���� �� flag data
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
	//- Advice - ����
	//- ������ �����ʵ�
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
	//- Advice - ��ȭ��ȣ
	//- ��ȭ��ȣ�� �߰� �ʵ� ����
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
	//- Advice - �ڵ���
	//- �ڵ����� �߰� �ʵ� ����
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
	//- Advice - �ѽ�
	//- �ѽ��� �߰� �ʵ� ����
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
	//- Advice - �������
	//- ������ ���� �ʵ� ������ �߰� �ʵ� ����
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
	//- Advice - �����ȣ
	//- �����ȣ�� �߰� �ʵ� ����
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
	//- Advice - �ּ�
	//- �ּҴ� �߰� �ʵ� ����
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
	//- Advice - ȸ�� �����ȣ
	//- �����ȣ�� �߰� �ʵ� ����
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
	//- Advice - ȸ�� �ּ�
	//- ȸ�� �ּҴ� �߰� �ʵ� ����
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
	//- Advice - ��й�ȣ
	//- ��й�ȣ�� ���� �ʵ� ���� �ӽ� ��й�ȣ�� ���
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
	//- Advice - ȸ������
	//- ȸ������ ���� �ʵ�
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
	//- Advice - ������
	//- �������� ���� �ʵ�
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
	//- Advice - ���Խ����� ���� ��� �����Ϸ� ��ü
	//- �������� ���� �ʵ�$approvalDtChange
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
	//- Advice - �����α���
	//- �����α����� ���� �ʵ�
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
	//- Advice - ��ȥ�����
	//- ��ȥ������� ���� �ʵ�
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
	//- Advice - ������, ��ġ��
	//- ���� �ѱݾ��� ���� �ʵ�
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
	//- Advice - �̸���
	//- �̸����� ���� �ʵ�
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
	//- Advice - �޸�
	//- �޸�� ���� �ʵ�
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
	//- Advice - ������ �޸�
	//- ������ �޸�� ���� �ʵ�
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
	//- Advice - ȸ�� �߰�����
	//- ȸ�� �߰������� ���� �ʵ�
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
	//- Advice - ȸ�� �Ϸù�ȣ
	//- ȸ�� �Ϸù�ȣ�� ���� �ʵ�
	//-----------------------------------------------------------
	if($memNoChange != ''){
		$memNo = $row[$memNoChange];
	}
	//-----------------------------------------------------------
	
	//-----------------------------------------------------------
	//- Advice - ����� ��Ϲ�ȣ
	//- ȸ�� �Ϸù�ȣ�� ���� �ʵ�
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
	//- Advice - ���� �α��� IP
	//- ���� �α��� IP�� ���� �ʵ�
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
        echo "�ߺ����̵� : ".$memId ."<br>";
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
				$arrayShippingAddNewData['shippingTitle'] = '�߰������' . $shippingAddressCount;
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
	/* //ī��24 SNS ȸ�� ������ ����
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
			echo '<div>�۾� �Ϸ� �� : ' . number_format($dataCnt) . '��</div><script type="text/javascript">parent.configSubmitComplete("' . $dataCnt . '");</script>';
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
	$arrayQueryPostData[] = "Delete From es_memberMileage Where reasonCd = '01005011' and contents = '������ ���� : ��밡�� �����ݰ� ������ ���� ����ȭ ���� �߰� �α�' and mileage > 0;";
	$arrayQueryPostData[] = "Insert Into es_memberMileage (memNo, handleMode, reasonCd, deleteFl, afterMileage, mileage, contents, regDt) SELECT memNo, 'm' as handleMode, '01005011' as reasonCd, 'n' as deleteFl, mileage as afterMileage, mileage, '������ ���� : ��밡�� �����ݰ� ������ ���� ����ȭ ���� �߰� �α�' as memo, now() as regDt FROM `es_member` WHERE mileage > 0;";

	$arrayQueryPostData[] = "update `es_member` m left join es_member ms on m.recommId = ms.memId Set m.recommId = ms.memId Where m.recommId is not null";

	$arrayQueryPostData[] = "OPTIMIZE TABLE es_member";
	$arrayQueryPostData[] = "OPTIMIZE TABLE es_orderShippingAddress";
	dumpSqlFileSet ($dumpFileName, $arrayQueryPostData);
	//echo '<script>parent.progress();</script>';
	//echo '<script>parent.progress(100);</script>';
}
echo '<div>�۾� �Ϸ� �� : ' . number_format($dataCnt) . '��</div><script type="text/javascript">parent.configSubmitComplete("' . $dataCnt . '");</script>';

?>