<?
include '../../inc/header.php';
$arrayQueryPostData		= array();		// ���� ���� ���� �迭
$sFilePathOrg = '';						// ���� ���� ��� ����
$sFilePathNew = '';
$sThumbFilePathNew = '';
$imgHostingDomain = trimPostRequest('imgHostingDomain'); // ���� �� �̹��� ȣ���� ���

$arrayBeforeBoardCode		= trimPostRequest('before_board');		// ���� �Խ��� �ڵ�
$arrayAfterBoardCode		= trimPostRequest('after_board');		// ���� �� �Խ��� �ڵ�
$arrayBoardName				= trimPostRequest('boardname');			// ���� �� �Խ��� �ڵ�
$arrayBoardKindQa			= trimPostRequest('boardKindQa');			// �Խ��� ���� QA ���� ����

//------------------------------------------------------
// - Advice - ��ǰ ���� ��� ���� ���� �غ�
//------------------------------------------------------
$fileCopyFl			= (trimPostRequest('file_copy_yn') == 'Y') ? true : false;
$editorFileCopyFl	= (trimPostRequest('editor_file_copy_yn') == 'Y') ? true : false;
$setFile	= new setFile(trimPostRequest('bulkFileFl'), trimPostRequest('localCopy'));
if (($fileCopyFl || $editorFileCopyFl) && !trimPostRequest('file_rename_yn')) {
	if (is_dir($sourcePath . '/data/board')) {
		$setFile->fileListCheck($sourcePath . '/data/board');
	}
	else {
		$setFile->makeDir($sourcePath . '/data/board');
	}
	if (is_dir($sourcePath . '/data/board/upload')) {
		$setFile->fileListCheck($sourcePath . '/data/board/upload');
	}
	else {
		$setFile->makeDir($sourcePath . '/data/board/upload');
		$setFile->makeDir($sourcePath . '/data/board/upload');
	}
	if (is_dir($sourcePath . '/data/editor/board')) {
		$setFile->fileListCheck($sourcePath . '/data/editor/board');
	}
	else {
		$setFile->makeDir($sourcePath . '/data/editor');
		$setFile->makeDir($sourcePath . '/data/editor/board');
	}

	if ($editorFileCopyFl) {
		$setFile->editorFileInfoSet(trimPostRequest('editorFileDomain'), trimPostRequest('editorFileDefaultPath'), 'board');
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
// - Advice - ����5 ��� ��Ų üũ
//------------------------------------------------------
$getConfigPost = array(
	'mode'	=> 'boardDefaultSkin',
);
$object = xmlUrlRequest($url, $getConfigPost);
$arrayFrontDefultSkin = array();
$arrayMobileDefultSkin = array();
foreach ((array)$object->frontDefaultSkin as $themeName => $themeSno) {
	$arrayFrontDefultSkin[$themeName] = $themeSno;
}
foreach ((array)$object->mobileDefaultSkin as $themeName => $themeSno) {
	$arrayMobileDefultSkin[$themeName] = $themeSno;
}

$getBoardListPost = array(
	'mode'	=> 'getBoardList',
);
$object = xmlUrlRequest($url, $getBoardListPost);
$arrayBoardList = array();

foreach ($object as $boardList) {
	$arrayBoardList[(string)$boardList] = true;
}
//------------------------------------------------------

//------------------------------------------------------
// - Advice - �Խ��� ���� �ʵ� ���� ����
//------------------------------------------------------
$boardCodeField				= trimPostRequest('boardcd_field');
$noChange					= trimPostRequest('no_change');					//�Ϸù�ȣ
$groupChange				= trimPostRequest('group_change');				//�׷��ȣ
$groupDepthAutoCheckChange	= trimPostRequest('groupDepthAutoCheck');		//��� �ڵ� ���� ���
$groupDepthChange			= trimPostRequest('groupDepth_change');			//��� ���� ����
$subChange					= trimPostRequest('sub_change');				//��۱���
$subTypeFlag				= trimPostRequest('sub_type');
$writerNmChange				= trimPostRequest('writerNm_change');			//�ۼ��ڸ�
$writerNickChange				= trimPostRequest('writerNick_change');			//�ۼ��ڸ�
$writerEmailChange			= trimPostRequest('writerEmail');				//�̸���
$wrtierHpChange				= trimPostRequest('writerHp_change');			//Ȩ������
$subjectChange				= trimPostRequest('subject_change');			//�� ����
$contentsChange				= trimPostRequest('contents');					//����
$oldFileChange				= trimPostRequest('oldFile');				//���� ���ϸ�
$newFileChange				= trimPostRequest('newFile');				//���� ���� ���ϸ�
$urlLinkChange				= trimPostRequest('urlLink_change');			//��ũ
$writerPwChange				= trimPostRequest('writerPw_change');			//��й�ȣ
$memNoChange				= trimPostRequest('memNo_change');				//ȸ�� ������ȣ
$ipChange					= trimPostRequest('ip');						//��� ip
$isNoticeChange				= trimPostRequest('isNotice_change');			//��������
$isSecretChange				= trimPostRequest('isSecret_change');			//��б� ����
$hitChange					= trimPostRequest('hit_change');				//��ȸ��
$goodsNoChange				= trimPostRequest('goodsNo_change');			//��ǰ��ȣ
$writerMobileChange			= trimPostRequest('writerMobile_change');		//�ۼ��� �޴�����ȣ
$goodsPtChange				= trimPostRequest('goodsPt_change');			//��ǰ����
$mileageChange				= trimPostRequest('mileage_change');			//���� ���ϸ���

$categoryChange				= trimPostRequest('category_change');			//���Ӹ�
$regDtChange				= trimPostRequest('regDt');						//�����
$modDtChange				= trimPostRequest('modDt');						//������

$delete_field				= trimPostRequest('delete_field');				//�Խñ� ���� ���� �ʵ�

/*��� Row �̺и� �߰� input*/
$answerSeparate				= trimPostRequest('answer_separate');			// ��� Row �и�����(All,Y,N)
$AnswerSubjectChange		= trimPostRequest('separate_AnswerSubject');	// ��� Row �и� �����
$AnswerContentsChange		= trimPostRequest('separate_AnswerContents');	// ��� Row �и� ����
$AnswerManagerNoChange		= trimPostRequest('separate_AnswerManagerNo');	// ��� Row �и� ������ ���̵�
$AnswerModDtChange			= trimPostRequest('separate_AnswerModDt');		// ��� Row �и� �ۼ�����
//------------------------------------------------------

//------------------------------------------------------
// - Advice - ������ ���÷��̽� ���� ����
//------------------------------------------------------
$repContBefore				= trimPostRequest('rep_contents_before');		//������ ���÷��̽� ���� �� ����
$repContAfter				= trimPostRequest('rep_contents_after');		//������ ���÷��̽� ���� �� ����
$repContCnt					= trimPostRequest('repcontentsCnt');			//������ ���÷��̽� ī���� ����

$repOFileBefore				= trimPostRequest('rep_oldFile_before');		//���� ���ϸ� ���÷��̽� ���� �� ����
$repOFileAfter				= trimPostRequest('rep_oldFile_after');			//���� ���ϸ� ���÷��̽� ���� �� ����
$repOFileCnt				= trimPostRequest('repOldFileCnt');				//���� ���ϸ� ���÷��̽� ī���� ����

$repNFileBefore				= trimPostRequest('rep_newFile_before');		//���� ���ϸ� ���÷��̽� ���� �� ����
$repNFileAfter				= trimPostRequest('rep_newFile_after');			//���� ���ϸ� ���÷��̽� ���� �� ����
$repNFileCnt				= trimPostRequest('repNewFileCnt');				//���� ���ϸ� ���÷��̽� ī���� ����

$repCategoryBefore			= trimPostRequest('rep_category_before');		//���Ӹ� ���� �� ����
$repCategoryAfter			= trimPostRequest('rep_category_after');		//���Ӹ� ���� �� ����
$repCategoryCnt				= trimPostRequest('repcategoryCnt');				//���Ӹ� ī���� ����

$repPointBefore				= trimPostRequest('rep_goodsPt_before');		//�� ���� �� ����
$repPointAfter				= trimPostRequest('rep_goodsPt_after');			//�� ���� �� ����
$repPointCnt				= trimPostRequest('repgoodsPtCnt');				//�� ���÷��̽� ī���� ����

$groupDepthBefore			= trimPostRequest('rep_groupDepth_before');		//�Խñ� ��� ���� ���� �� ����
$groupDepthAfter			= trimPostRequest('rep_groupDepth_after');		//�Խñ� ��� ���� ���� �� ����
$groupDepthCnt				= trimPostRequest('groupDepthCnt');				//�Խñ� ��� ���� ���÷��̽� ī���� ����
//------------------------------------------------------

//------------------------------------------------------
// - Advice - ��� ���� �ʵ� ����
//------------------------------------------------------
$replyIndexNo			= trimPostRequest('reply_index_no');			//���� ������ �� �Խñ� �Ϸù�ȣ
$replyBoardCd			= trimPostRequest('reply_boardcd');				//���� ������ �� �Խ��� �ڵ��ȣ �ִ� ��츸 ���
$replywriterNmChange		= trimPostRequest('reply_writerNm_change');			//�ۼ��ڸ�
$replymemNoChange			= trimPostRequest('reply_memNo_change');			//ȸ����ȣ
$replyMemoChange		= trimPostRequest('reply_memo_change');			//����
$replywriterPwChange	= trimPostRequest('reply_writerPw_change');		//��й�ȣ
$replyregDtChange		= trimPostRequest('replyRegdt');				//��� �����
//------------------------------------------------------

//------------------------------------------------------
// - Advice - �� ������ �������� �� �迭����
//------------------------------------------------------
$boardSort				='';									//sql����� ���� ���� ����
$arrayTempData			= array();								//�Խ��� ������ �ʱ� �ӽ� �迭
$newBoard				= array();								//�Խñ� ������ Ű �迭 �ۼ�
$newMemoQuery			= array();								//�Խñ� ��� ��� ���� �ӽ� �迭
$fileData				= array();								//���� ������ �ε� �� �ӽ����� �迭
$oldFileRow				= array();								//���� ���ϸ� �ӽ� ���� �迭
$newFileRow				= array();								//���� ���ϸ� �ӽ� ���� �迭
//------------------------------------------------------

//------------------------------------------------------
// - Advice - ��� �� ȸ�� ���� ����
//------------------------------------------------------
$arrayMemberCheckPostData			= array();			// ���� �� ���θ� ȸ�� ������ üũ
$arrayMemberCheckPostData['mode']	= 'memberCheck';	//ó�� ���μ��� �⺻ ��� �� ����

$object = @xmlUrlRequest($url, $arrayMemberCheckPostData);
$memberData = $object->memberData;

$arrayMember = array();
foreach($memberData as $value) {
	$newMno = (int)$value->attributes()->memNo;
	$arrayMember[urldecode((string)$value)] = $newMno;
}
//------------------------------------------------------

//------------------------------------------------------
// - Advice - ��� �� ��ǰ ���� ����
//------------------------------------------------------
$arrayGoodsNo = array();
$goodsNoResult = $db->query("Select originalGoodsKey, godo5GoodsNo From tmp_goodsno");
while ($goodsNoRow = $db->fetch($goodsNoResult)) {
	$arrayGoodsNo[$goodsNoRow['originalGoodsKey']] = $goodsNoRow['godo5GoodsNo'];
}
//------------------------------------------------------

//------------------------------------------------------
// - Advice - ÷������ ���̺� ���� ���� �� ���ϸ� �迭 ����
//------------------------------------------------------
if(trimPostRequest('file_use') == 'Y') {
	//------------------------------------------------------
	// - Advice - ÷������ Ÿ���̺� ���� ����
	//------------------------------------------------------
	$tableOldFileChange			= trimPostRequest('fileTableOld');			//���� ���ϸ�
	$tableNewFileChange			= trimPostRequest('fileTableNew');			//���� ���� ���ϸ�

	$repTableOFileBefore		= trimPostRequest('rep_table_ofile_before');		//Ÿ ���̺� ���� ���ϸ� ���÷��̽� ���� �� ����
	$repTableOFileAfter			= trimPostRequest('rep_table_ofile_after');			//Ÿ ���̺� ���� ���ϸ� ���÷��̽� ���� �� ����
	$repTableOFileCnt			= trimPostRequest('rep_table_ofileCnt');				//Ÿ ���̺� ���� ���ϸ� ���÷��̽� ī���� ����

	$repTableNFileBefore		= trimPostRequest('rep_table_nfile_before');		//Ÿ ���̺� ���� ���ϸ� ���÷��̽� ���� �� ����
	$repTableNFileAfter			= trimPostRequest('rep_table_nfile_after');			//Ÿ ���̺� ���� ���ϸ� ���÷��̽� ���� �� ����
	$repTableNFileCnt			= trimPostRequest('rep_table_nfileCnt');				//Ÿ ���̺� ���� ���ϸ� ���÷��̽� ī���� ����

	//------------------------------------------------------

	//------------------------------------------------------
	// - Advice - ���̺� ÷������ �����͸� ÷������ �迭�� ����
	//------------------------------------------------------
	$fileData = subTableGetData(trimPostRequest('file_data_type'), trimPostRequest('file_data_name'), trimPostRequest('file_select_field'), trimPostRequest('file_sort'));
	foreach ($fileData as $fileTableRow) {
		if (trimPostRequest('fileTableOldCnt') > 1) {
			foreach ($tableOldFileChange as $oldFileFieldRow) {
				$oldFileName = $fileTableRow[$oldFileFieldRow];
				if($repTableOFileCnt > 0){
					$oldFileName = dataCntReplace($oldFileName, $repTableOFileBefore, $repTableOFileAfter, $repTableOFileCnt);
				}
				if(trimPostRequest('file_board_cd')){
					$oldFileRow[$fileTableRow[trimPostRequest('file_board_cd')]][$fileTableRow[trimPostRequest('file_board_no')]][] = $oldFileName;
				} else {
					$oldFileRow[$fileTableRow[trimPostRequest('file_board_no')]][] = $oldFileName;
				}
			}
		} else {
			$oldFileName = $fileTableRow[$tableOldFileChange[0]];
			if($repTableOFileCnt > 0){
				$oldFileName = dataCntReplace($oldFileName, $repTableOFileBefore, $repTableOFileAfter, $repTableOFileCnt);
			}
			if(trimPostRequest('file_board_cd')){
				$oldFileRow[$fileTableRow[trimPostRequest('file_board_cd')]][$fileTableRow[trimPostRequest('file_board_no')]][] = $oldFileName;
			} else {
				$oldFileRow[$fileTableRow[trimPostRequest('file_board_no')]][] = $oldFileName;
			}
		}
		if (trimPostRequest('fileTableNewCnt') > 1) {
			foreach($tableNewFileChange as $newFileFieldRow){
				$newFileName = $fileTableRow[$newFileFieldRow];
				if($repTableNFileCnt > 0){
					$newFileName = dataCntReplace($newFileName, $repTableNFileBefore, $repTableNFileAfter, $repTableNFileCnt);
				}
				if (!trimPostRequest('localCopy')) {
					$newFileName = str_replace('%7C', '^|^', urlencode($newFileName));
				}
				if(trimPostRequest('file_board_cd')){
					$newFileRow[$fileTableRow[trimPostRequest('file_board_cd')]][$fileTableRow[trimPostRequest('file_board_no')]][] = $newFileName;
				} else {
					$newFileRow[$fileTableRow[trimPostRequest('file_board_no')]][] = $newFileName;
				}
			}
		} else {
			$newFileName = $fileTableRow[$tableNewFileChange[0]];
			if($repTableNFileCnt > 0){
				$newFileName = dataCntReplace($newFileName, $repTableNFileBefore, $repTableNFileAfter, $repTableNFileCnt);
			}
			if (!trimPostRequest('localCopy')) {
				$newFileName = str_replace('%7C', '^|^', urlencode($newFileName));
			}
			if(trimPostRequest('file_board_cd')){
				$newFileRow[$fileTableRow[trimPostRequest('file_board_cd')]][$fileTableRow[trimPostRequest('file_board_no')]][] = $newFileName;
			} else {
				$newFileRow[$fileTableRow[trimPostRequest('file_board_no')]][] = $newFileName;
			}
		}
	}
	//------------------------------------------------------
}
//------------------------------------------------------

//------------------------------------------------------
// - Advice - ��� ���̺� ���� ���� �� ��� ���� �迭 ����
//------------------------------------------------------
if(trimPostRequest('reply_use') == 'Y') {
	if(trimPostRequest('comment_data_type') == 'csv'){// ��� ���̺��� CSV�� �Ǿ� �ִ� ������ �ε�
		if(trimPostRequest('reply_table_separate') == 'Y'){ // �Խ��� CSV�� ������ �ִ� ������ �ε��� �ϰ� ������ ���� �� never_board_code �����Ϳ� ���� �ڵ�� �Է�
			foreach($arrayBeforeBoardCode as $beforeCode){
				$reply_fp = fopen($csvFilePath . trimPostRequest('comment_data_name') . $beforeCode . trimPostRequest('reply_separate_after_name') . '.csv', 'r' );
				$replyDataRow = fgetcsv($reply_fp, 1500000, ',');
				while ($replyDataRow = fgetcsv($reply_fp, 1500000, ',')) {
					$replyDataRow['never_board_code'] = $beforeCode;
					$replyData[] = $replyDataRow;
				}
			}
		} else {
			$reply_fp = fopen($csvFilePath . trimPostRequest('comment_data_name') . '.csv', 'r' );
			$replyDataRow = fgetcsv( $reply_fp, 1500000, ',' );
			while($replyDataRow = fgetcsv($reply_fp, 1500000, ',' )) $replyData[] = $replyDataRow;
		}
	} else if(trimPostRequest('comment_data_type') == 'sql') {// ��� ���̺��� SQL�� �Ǿ� �ִ� ������ �ε�
		if(trimPostRequest('comment_sort')){
			$commentSort = ' order by ' . trimPostRequest('comment_sort');
		}
		if(trimPostRequest('reply_table_separate') == 'Y'){// ��� ���̺��� ������ �ִ� ������ �ε��� �ϰ� ������ ���� �� never_board_code �����Ϳ� ���� �ڵ�� �Է�
			foreach($arrayBeforeBoardCode as $beforeCode){
				$res = $db->query("select " . trimPostRequest('comment_select_field') . ", '" . $beforeCode . "' as never_board_code from " . trimPostRequest('comment_data_name') . $beforeCode . trimPostRequest('reply_separate_after_name') . $commentSort);

				while ($replyDataRow = $db->fetch($res)) $replyData[] = $replyDataRow;
			}
		} else {// �Ϲ� ������ �ε�
			$res = $db->query("select " . stripslashes(trimPostRequest('comment_select_field')) . " from " . trimPostRequest('comment_data_name') . $commentSort);
			while ($replyDataRow = $db->fetch($res)) $replyData[] = $replyDataRow;
		}
	}

	if($replyData){// ��� ������ �μ�Ʈ ���� ���� �� ��� ���� �迭�� ����
		foreach($replyData as $replyRow){
			if(trimPostRequest('comment_delete_type')){
				if($replyRow[trimPostRequest('comment_delete_field')] == trimPostRequest('comment_delete_type')) continue;
			}
			//-----------------------------------------------------------
			//- Advice - ��� �����
			//- ��� ������� ���� �ʵ�
			//-----------------------------------------------------------
			$replyRegdt = '';
			if(trimPostRequest('replyRegdtCnt') > 1){
				for ($i = 0; $i < count($replyregDtChange); $i++){
					$replyRegdt .= $replyRow[$replyregDtChange[$i]];
				}
			} else {
				$replyRegdt = $replyRow[$replyregDtChange[0]];
			}

			$replyRegdt = dateCreate($replyRegdt);

			//-----------------------------------------------------------
			
			if($replyBoardCd != '') {
				$replayMemo = commentSetting($replyRow[$replyMemoChange]);
				if ($replayMemo) {
					$newMemoQuery[$replyRow[$replyBoardCd]][$replyRow[$replyIndexNo]][] = "INSERT INTO `es_boardMemo` (`bdId`, `bdSno`, `writerId`, `writerNm`, `memo`, `memNo`, `regDt`, `modDt`) VALUES ('board_pkid','board_pkno', '" . addslashes($replyRow[$replymemNoChange]) . "', '" . addslashes($replyRow[$replywriterNmChange]) . "','" . addslashes($replayMemo) . "','" . $arrayMember[$replyRow[$replymemNoChange]] . "','" . $replyRegdt . "','" . $replyRegdt . "');";
				}
			} else {
				$replayMemo = commentSetting($replyRow[$replyMemoChange]);
				if ($replayMemo) {
					$newMemoQuery[$replyRow[$replyIndexNo]][] = "INSERT INTO `es_boardMemo` (`bdId`, `bdSno`, `writerId`, `writerNm`, `memo`, `memNo`, `regDt`, `modDt`) VALUES ('board_pkid','board_pkno', '" . addslashes($replyRow[$replymemNoChange]) . "', '" . addslashes($replyRow[$replywriterNmChange]) . "','" . addslashes($replayMemo) . "','" . $arrayMember[$replyRow[$replymemNoChange]] . "','" . $replyRegdt . "','" . $replyRegdt . "');";
					
				}
			}
		}
	}
}
//------------------------------------------------------

//------------------------------------------------------
// - Advice - ���� �� ���� �� �Խ��� �ڵ� ��Ī �� ���� �� �Խ��� �ڵ�� �迭 ���� ����
//------------------------------------------------------
$arrayBoardId = array();
//$arrayBoardNameArray = array();
for ($codeCnt = 0; $codeCnt <= count($arrayAfterBoardCode) - 1; $codeCnt++) {
	if(!in_array($arrayAfterBoardCode[$codeCnt], $arrayBoardId)){
		$arrayBoardId[$arrayBeforeBoardCode[$codeCnt]] = $arrayAfterBoardCode[$codeCnt];
		${$arrayAfterBoardCode[$codeCnt]} = array();
	}
}
//------------------------------------------------------

//------------------------------------------------------
// - Advice - �Խ��� ������ �ε�
//------------------------------------------------------
if(trimPostRequest('data_type') === 'csv') { // CSV ���� ������ �ε�
	if(trimPostRequest('table_separate') == 'Y') { // �Խ��� CSV�� ������ �ִ� ������ �ε��� �ϰ� ������ ���� �� never_board_code �����Ϳ� ���� �ڵ�� �Է�
		foreach($arrayBeforeBoardCode as $beforeBoardCode) {
			$fp = fopen($csvFilePath . trimPostRequest('data_name') . $beforeBoardCode . trimPostRequest('separate_after_name') . '.csv', 'r' );
			$tt = fgetcsv($fp, 1500000, ',');
			while($tt = fgetcsv($fp, 1500000, ',')) {
				$tt['never_board_code'] = $beforeBoardCode;
				$arrayTempData[] = $tt;
			}
		}
	} else {
		$fp = fopen($csvFilePath . trimPostRequest('data_name') . '.csv', 'r' );
		$tt = fgetcsv($fp, 1500000, ',');
		while($tt = fgetcsv($fp, 1500000, ',')) $arrayTempData[] = $tt;
	}
} else if(trimPostRequest('data_type') === 'sql') {// SQL ������ �ε�
	if(trimPostRequest('sort')){
		$boardSort = ' order by ' . trimPostRequest('sort');
	}
	if(trimPostRequest('table_separate') == 'Y'){// �Խ��� ���̺��� ������ �ִ� ������ �ε��� �ϰ� ������ ���� �� never_board_code �����Ϳ� ���� �ڵ�� �Է�
		foreach($arrayBeforeBoardCode as $beforeBoardCode){
			$res = $db->query("select " . stripslashes(trimPostRequest('select_field')) . ", '" . $beforeBoardCode . "' as never_board_code from " . trimPostRequest('data_name') . $beforeBoardCode . trimPostRequest('separate_after_name') . stripslashes($boardSort));
			while($tt = $db->fetch($res) ) $arrayTempData[] = $tt;
		}
	} else {// �Ϲ� ������ �ε�
		$res = $db->query("select " . stripslashes(trimPostRequest('select_field')) . " from " . trimPostRequest('data_name') . stripslashes($boardSort));
		while($tt = $db->fetch($res) ) $arrayTempData[] = $tt;
	}
}

foreach($arrayTempData as $row) {
	for($codeCnt = 0; $codeCnt <= count($arrayAfterBoardCode) - 1; $codeCnt++){
		// �Խ��� ���̺��� ������ �ִ� ��� never_board_code(���� �Խ��� �ڵ�)�ʵ尪 ��� --
		if($_POST['table_separate'] == 'Y') $boardCodeField = 'never_board_code';
		//----------------------------------------------------------------------------------

		if ($boardCodeField !== '') {
			if($row[$boardCodeField] == $arrayBeforeBoardCode[$codeCnt]){
				${$arrayAfterBoardCode[$codeCnt]}[] = $row;
				continue;
			}
		} else {
			${$arrayAfterBoardCode[$codeCnt]}[] = $row;
		}
	}
}

//------------------------------------------------------
$totDataCnt		= 0; // �� �Խñ� ī��Ʈ
$boardTypeCount = 0; //�Խ��� ���� ���� ī��Ʈ
foreach($arrayBoardId as $beforeBoardId => $afterBoardId) {
	echo '<p>' . $beforeBoardId . ' => ' . $afterBoardId . '</p>';
	$arrayReplyQuery = array();

	if ($afterBoardId == 'goodsreview') {
		$arrayGoodsReviewCount = array();
	}
	if ($mode === "start") {
		if ($arrayBoardList[$afterBoardId] && trimPostRequest('boardInitFl') == 'Y') {
			$arrayQueryPostData[] = "Delete From es_board Where bdId='" . $afterBoardId . "' and sno >'6';";
		}
		$arrayQueryPostData[] = "DROP TABLE IF EXISTS `es_bd_" . $afterBoardId . "`;";
		$arrayQueryPostData[] = "Delete From es_boardMemo Where bdId='" . $afterBoardId . "';";
		$arrayQueryPostData[] = createBoardTableFunction($afterBoardId);
	}
	//boardKind �ӽ� ����
	if($arrayBoardKindQa[$boardTypeCount] =='Y') {
		$boardKindType = 'qa';
	} else {
		$arrayBoardKindQa[$boardTypeCount] = 'N';
		if ($afterBoardId == 'goodsreview') {
			$boardKindType = 'gallery';
		}
		else {
			$boardKindType = 'default';
		}
	}

	$themeSno			= $arrayFrontDefultSkin[$boardKindType];
	$mobileThemeSno		= $arrayMobileDefultSkin[$boardKindType];

	//-----------------------------------------------------------
	//- Advice - �Խñ� es_board ���� ����
	//-----------------------------------------------------------
	if($afterBoardId !='notice' && $afterBoardId !='goodsqa' && $afterBoardId !='goodsreview' && $afterBoardId !='qa' && $afterBoardId !='event' && $afterBoardId !='cooperation') {
		$godoMall5Board['bdId'] = $afterBoardId;
		$godoMall5Board['bdNm'] = $arrayBoardName[$boardTypeCount];
		$godoMall5Board['themeSno'] = $themeSno;
		$godoMall5Board['mobileThemeSno'] = $mobileThemeSno;
		$godoMall5Board['bdKind'] = $boardKindType;
		if ($boardKindType == 'qa') {
			$godoMall5Board['bdReplyStatusFl'] = 'y';
		}
		$godoMall5Board['bdNewFl'] ='24';
		$godoMall5Board['bdHotFl'] ='100';
		/*����*/
		$godoMall5Board['bdAuthList'] = 'all';
		$godoMall5Board['bdAuthRead'] = 'all';
		$godoMall5Board['bdAuthWrite'] = 'all';
		$godoMall5Board['bdReplyFl'] = 'y';
		$godoMall5Board['bdAuthReply'] = 'all';
		$godoMall5Board['bdAuthMemo'] = 'all';
		$godoMall5Board['bdAuthListGroup'] = 'all';
		$godoMall5Board['bdAuthWriteGroup'] = 'all';
		$godoMall5Board['bdAuthReadGroup'] ='all';
		$godoMall5Board['bdAuthReplyGroup'] = 'all';
		$godoMall5Board['bdAuthMemoGroup'] = 'all';
		$godoMall5Board['bdIpFl'] =changeFlag($bdIp, 'on');
		$godoMall5Board['bdIpFilterFl'] =changeFlag($bdIpAsterisk, 'on');
		$godoMall5Board['bdUploadStorage'] ='local';
		$godoMall5Board['bdUploadPath'] = 'upload/' . $afterBoardId.'/';
		$godoMall5Board['bdUploadThumbPath'] = 'upload/' . $afterBoardId.'/t/';
		$godoMall5Board['bdUploadMaxSize'] ='5';
		$godoMall5Board['bdCategoryFl'] = changeFlag($bdUseSubSpeech, 'on');
		$godoMall5Board['bdCategoryTitle'] = $bdSubSpeechTitle;
		$godoMall5Board['bdCategory'] = str_replace('|', '^|^', $bdSubSpeech);
		$godoMall5Board['bdMemoFl'] = changeFlag($bdUseComment, 'on');
		$godoMall5Board['bdUserDsp'] = 'name';
		$godoMall5Board['bdAdminDsp'] = 'nick';
		$godoMall5Board['bdSecretFl'] = $bdSecretChk;
		$godoMall5Board['bdSecretTitleFl'] = '0';
		$godoMall5Board['bdSecretTitleTxt'] = '';
		$godoMall5Board['bdEmailFl'] ='y';
		$godoMall5Board['bdCaptchaBgClr'] = 'FFFFFF';
		$godoMall5Board['bdCaptchaClr'] = '252525';
		$godoMall5Board['bdHitPerCnt']		= '1';
		$godoMall5Board['bdSubjectLength'] = $bdStrlen;
		$godoMall5Board['bdListCount'] = '10';
		$godoMall5Board['bdListColsCount'] = '5';
		$godoMall5Board['bdListRowsCount'] = '5';
		$godoMall5Board['bdListImageTarget']	= 'upload';
		$godoMall5Board['bdListImageSize'] = '100^|^100';
		$godoMall5Board['bdMileageFl']		=	'n';
		$godoMall5Board['bdMileageAmount']		=	'0';
		$godoMall5Board['bdMileageDeleteFl']	= 'n';
		$godoMall5Board['bdMileageLackAction']	= 'nodelete';
		$godoMall5Board['bdEditorFl']	= 'y';
		$godoMall5Board['bdUploadFl'] =changeFlag($bdUseFile, 'on');
		$godoMall5Board['regDt'] = date("Y-m-d h:i:s");
		$godoMall5Board['modDt'] = date("Y-m-d h:i:s");
		$arrayBoardQueryString = array();
		foreach ($godoMall5Board as $boardKey => $boardValue) $arrayBoardQueryString[] = "$boardKey = '" . addslashes($boardValue) . "'";
		if (!$arrayBoardList[$afterBoardId]) {
			$arrayQueryPostData[] = "Insert Into es_board Set " . implode(" , ", $arrayBoardQueryString).";";
		}
	}
	
	$insertSet				= new insertSet('es_bd_' . $afterBoardId, trimPostRequest('insertMode'));
	//------------------------------------------------------
	// - Advice - �Խ��� ���̺� ���� �� �ʵ��
	//------------------------------------------------------
	$insertSet->arrayFieldName = array(
		'sno',
		'groupNo',
		'groupThread',
		'parentSno',
		'answerSubject',
		'answerContents',
		'answerManagerNo',
		'replyStatus',
		'answerModDt',
		'writerNm',
		'writerNick',
		'writerEmail',
		'writerHp',
		'subject',
		'contents',
		'memoCnt',
		'urlLink',
		'uploadFileNm',
		'saveFileNm',
		'writerPw',
		'memNo',
		'writerId',
		'writerIp',
		'isNotice',
		'isSecret',
		'isDelete',
		'writerMobile',
		'goodsNo',
		'goodsPt',
		'hit',
		'category',
		'regdt',
		'moddt',
		'bdUploadStorage',
		'bdUploadPath',
		'bdUploadThumbPath',
	);

	$dataCnt				= 0;
	$boardTypeCommentCount	= 0;		// �Խ��� �� ��� ��� ��
	$queryPrintCount		= 0;
	$boardGroupCount		= 0;
	$boardGroupNoArray		= array();

	$arrayCategoryList = array();
	//-----------------------------------------------------------
	//- Advice - �Խñ� ������ Row foreach
	//-----------------------------------------------------------
	foreach (${$afterBoardId} as $boardRow) {
		if($deleteField){
			if($boardRow[$deleteField] == $_POST['delete_type']) continue;
		}
		$newBoard = array();

		$boardIndex = $dataCnt + 1; // �Խñ� �Ϸù�ȣ
		$commentCount = 0; // ��� ��
		$parentMember = 0; // �θ�� �ۼ��� �Ϸù�ȣ
		
		//-----------------------------------------------------------
		//- Advice - ÷������ ��� ����
		//-----------------------------------------------------------
		if ($fileCopyFl || $editorFileCopyFl) {
			$sFilePathNew = $sourcePath . '/data/board/upload/' . $afterBoardId;
			$sThumbFilePathNew = $sourcePath . '/data/board/upload/' . $afterBoardId . '/t/';
			if (is_dir($sFilePathNew)) {
				$setFile->fileListCheck($sFilePathNew);
			}
			else {
				$setFile->makeDir($sFilePathNew);
			}
			if (is_dir($sThumbFilePathNew)) {
				$setFile->fileListCheck($sThumbFilePathNew);
			}
			else {
				$setFile->makeDir($sThumbFilePathNew);
			}
		}
		//-----------------------------------------------------------

		//-----------------------------------------------------------
		//- Advice - Ȩ������, ����, ��ũ
		//- Advice - ���� ����, ��б� ����, html ����, ��ȸ��
		//- ��� �ʵ�� ���� �÷� ��� �� Ư�� Ÿ�� ���� �ʵ�
		//-----------------------------------------------------------
		$writerHp	= '';
		$subject	= '';
		$urlLink	= '';
		$ip			= '';
		$isNotice	= '';
		$isSecret		= '';
		$hit		= '';
		$category	= '';
		$writerMobile = '';
		$goodsPt ='';
		$mileage ='';
		$groupSort = '';
		$groupDepth = '';
		$groupDepthAutoCheck = '';
		$sub		= '';
		$subType ='';
		$AnswerSubject = '';			// ��� Row �и� ����
		$AnswerContents = '';			// ��� Row �и� ����
		$AnswerManagerNo = '';			// ��� Row �и� ������ ���̵�
		$AnswerModDt = '';			// ��� Row �и� �ۼ�����

		$writerHp		= $boardRow[$wrtierHpChange];
		$subject		= strip_tags(strCut($boardRow[$subjectChange], 50));
		$urlLink		= $boardRow[$urlLinkChange];
		$isNotice		= flagChange('yn', $boardRow[$isNoticeChange]);
		$isSecret		= flagChange('yn', $boardRow[$isSecretChange]);
		$hit			= $boardRow[$hitChange];
		$category		= dataCntReplace($boardRow[$categoryChange],$repCategoryBefore,$repCategoryAfter,$repCategoryCnt);
		$groupSort		= $boardRow[$groupChange];
		$sub			= $boardRow[$subChange];
		$subType		= $boardRow[$subTypeFlag];
		$mileage		= $boardRow[$mileageChange];

		//-----------------------------------------------------------
		//- Advice - ��ȭ��ȣ
		//- ��ȭ��ȣ�� �߰� �ʵ� ����
		//-----------------------------------------------------------
		$writerMobile = '';
		if(trimPostRequest('writerMobile_changeCnt') > 1){
			for ($i = 0; $i < count($writerMobileChange); $i++){
				$writerMobile .= $row[$writerMobileChange[$i]];
			}
		} else {
			$row[$writerMobileChange[0]] = defaultReplace($row[$writerMobileChange[0]]);
			$writerMobile = $row[$writerMobileChange[0]];
		}
		if ($writerMobile) {
			$writerMobile = telCreate($writerMobile);
		}
		//-----------------------------------------------------------

		if ($category) {
			if (empty($arrayCategoryList[$afterBoardId])) {
				$arrayCategoryList[$afterBoardId] = array();
			}

			if (!in_array($category, $arrayCategoryList[$afterBoardId])) {
				$arrayCategoryList[$afterBoardId][] = $category;
			}
		}

		$AnswerSubject = (trimPostRequest('replycontentSubject') == 'y' && !empty($boardRow[$AnswerContentsChange])) ? '[�亯] ' . $subject : $boardRow[$AnswerSubjectChange];

		$AnswerContents = $boardRow[$AnswerContentsChange];			// ��� Row �и� ����
		$AnswerContents = str_replace('\"', '', $AnswerContents);

		$AnswerManagerNo = $boardRow[$AnswerManagerNoChange];		// ��� Row �и� ������ ���̵�
		$AnswerModDt = dateCreate($boardRow[$AnswerModDtChange]); // ��� Row �и� �ۼ�����
		//-----------------------------------------------------------

		//-----------------------------------------------------------
		//- Advice - �Խ��� �׷�
		//- �Խ��� �׷� �����ʹ� ���� �ʵ�
		//-----------------------------------------------------------
		//echo "üũ : " . trimPostRequest('groupDepthAutoCheck')."<br/>";
		//echo "���� �׷� ���� �ʵ�� : " . $boardRow[$groupDepthChange[0]] . "<br/>";
		if(trimPostRequest('groupDepthAutoCheck') !='y') {
			if ($groupDepthCnt > 0) {
				$groupDepth = dataIfChange($boardRow[$groupDepthChange[0]], $groupDepthBefore, $groupDepthAfter, $groupDepthCnt);
			} else {
				$groupDepth = $boardRow[$groupDepthChange[0]];
			}
		} else {
			$groupDepth = strlen($boardRow[$groupDepthChange[0]]);
		}
		//-----------------------------------------------------------

		//-----------------------------------------------------------
		//- Advice - �Խ��� ��� �׷���
		//-createGroupThread(�׷��� �׷� ���� ����, �׷��� ���� �� ����, �׷� ��� ���� 1~4�����, ���� �� ���� ��)
		//-----------------------------------------------------------

		$boardGroupNoArray['cnt'][] = $boardGroupCount; //�׷��� �׷� ���� ����
		$boardGroupNoArray['group'][] = $groupSort;	//�׷��� ���� �� ����
		$boardGroupNoArray['boardIdx'][] = $boardIndex; // �Խñ� �ε��� ����
		$boardGroupThreadArray['sub'][]	= $sub; //��ۿ��� ����
		$boardGroupThreadArray['subTypeValue'][]	= $subType; // ��� ���� Flag ����
		$boardGroupThreadArray['subDepth'][]	= $groupDepth; // �׷� ��� ���� 1��, 2��, 3��, 4����� ��
		if($groupSort == $boardGroupNoArray['group'][0] && $sub != $subTypeFlag) {
			$boardGroupCount = $boardGroupNoArray['cnt'][0];	//
			$cntGroupThread = count($boardGroupThreadArray['thread'])-1; //�迭 ����
			$boardGroupThreadParentArray = $boardGroupThreadArray['thread'][$cntGroupThread-1];//�迭�� �ٷ� �� ��
			$boardThreadChangeValue = createGroupThread($boardGroupCount, $groupDepth, $boardGroupThreadArray['subDepth'][$cntGroupThread], $boardGroupThreadArray['thread'][$cntGroupThread]);
			$boardGroupThreadArray['subDepth'][]	= $groupDepth;
			$boardGroupThreadArray['thread'][] = $boardThreadChangeValue;
			$boardParentSno = $boardGroupNoArray['boardIdx'][0];
		} else {
			unset($boardGroupNoArray);
			$boardGroupNoArray['group'][] = $groupSort;
			$boardGroupNoArray['boardIdx'][] = $boardIndex;
			$boardGroupCount++;
			unset($boardGroupThreadArray);
			$boardGroupThreadParentArray= array();
			$boardParentSno=0;
			$boardThreadChangeValue = '';
		}
		//-----------------------------------------------------------

		//-----------------------------------------------------------
		//- Advice - �ۼ�, IP
		//- �ۼ���, IP�� ���� �ʵ�
		//-----------------------------------------------------------
		$regDt = '';
		if(trimPostRequest('regdtCnt') > 1){
			for ($i = 0; $i < count($regDtChange); $i++){
				$regDt .= $boardRow[$regDtChange[$i]];
			}
		} else {
			$regDt = $boardRow[$regDtChange[0]];
		}

		$regDt = dateCreate($regDt);
		$modDt = '';
		if(trimPostRequest('moddtCnt') > 1){
			for ($i = 0; $i < count($modDtChange); $i++){
				$modDt .= $boardRow[$modDtChange[$i]];
			}
		} else {
			$modDt = $boardRow[$modDtChange[0]];
		}

		$modDt = dateCreate($modDt);


		$ip = '';
		if (trimPostRequest('ipCnt')) {
			$arrayWriteIp = array();
			for ($i = 0; $i < count($ipChange); $i++){
				$arrayWriteIp[] = $boardRow[$ipChange[$i]];
			}
			$ip = implode('.', $arrayWriteIp);
		}
		else {
			$ip = $boardRow[$ipChange[0]];
		}
		//-----------------------------------------------------------

		//-----------------------------------------------------------
		//- Advice - �ۼ��ڸ�, �г���
		//- �ۼ��ڸ��� ���� �ʵ�
		//-----------------------------------------------------------
		$writerNm = '';
		if(trimPostRequest('name_type') != ''){
			$writerNm = trimPostRequest('name_type');
		} else {
			$writerNm = $boardRow[$writerNmChange];
		}

		$writerNick = '';
		if(trimPostRequest('nick_type') != ''){
			$writerNick = trimPostRequest('nick_type');
		} else {
			$writerNick = $boardRow[$writerNickChange];
		}
		//-----------------------------------------------------------

		//-----------------------------------------------------------
		//- Advice - ȸ�� ���̵�(ȸ����ȣ)
		//- ���̵� ���� �ʵ�
		//-----------------------------------------------------------
		$memNo = '';
		$writerId = '';
		if(trimPostRequest('memNo_type') != '') {
			$memNo = trimPostRequest('memNo_type');
		}

		if ($memNo != '') {
			if (trimPostRequest('admin_no') != '') {
				if (trimPostRequest('admin_no') == $boardRow[$memNoChange]) {
					$memNo = -1;
				}
			}
		}
		else {
			if (trimPostRequest('admin_no') != '') {
				if(trimPostRequest('admin_no') == $boardRow[$memNoChange]){
					$memNo = -1;
				} else {
					$memNo = $arrayMember[$boardRow[$memNoChange]];
					$writerId = $boardRow[$memNoChange];
				}
			}
			else {
				$memNo = $arrayMember[$boardRow[$memNoChange]];
				$writerId = $boardRow[$memNoChange];
			}
		}
		if (!$sub) {
			$parentMember = $memNo;
		}
		//-----------------------------------------------------------

		//-----------------------------------------------------------
		//- Advice - ��
		//- �򰡴� ���� �ʵ�
		//-----------------------------------------------------------
		if($repPointCnt > 0){
			$goodsPt = dataIfChange($boardRow[$goodsPtChange], $repPointBefore, $repPointAfter, $repPointCnt);
		} else {
			$goodsPt = $boardRow[$goodsPtChange];
		}
		//-----------------------------------------------------------

		//-----------------------------------------------------------
		//- Advice - ��ǰ��ȣ
		//- ��ǰ��ȣ�� ���� �ʵ�
		//-----------------------------------------------------------
		$goodsNo   = ($arrayGoodsNo[$boardRow[$goodsNoChange]]) ? $arrayGoodsNo[$boardRow[$goodsNoChange]] : ''; // ���� �� ��ǰ ��ȣ ����
		if ($afterBoardId == 'goodsreview' && $goodsNo) {
			if (!$arrayGoodsReviewCount[$goodsNo]) {
				$arrayGoodsReviewCount[$goodsNo] = 1;
			}
			else {
				$arrayGoodsReviewCount[$goodsNo]++;
			}
		}
		//-----------------------------------------------------------

		//-----------------------------------------------------------
		//- Advice - �̸���
		//- �̸����� ���� �ʵ�
		//-----------------------------------------------------------
		$writerEmail = '';
		$arrayEmail = array();
		if(trimPostRequest('writerEmailCnt') > 1) {
			for ($i = 0; $i < count($writerEmailChange); $i++){
				$arrayEmail[] = $boardRow[$writerEmailChange[$i]];
			}
			$writerEmail = implode('@', $arrayEmail);
		} else {
			$writerEmail = $boardRow[$writerEmailChange[0]];
		}
		//-----------------------------------------------------------

		//-----------------------------------------------------------
		//- Advice - ��й�ȣ
		//- ��й�ȣ�� ���� �ʵ�
		//-----------------------------------------------------------
		$writerPw = '';
		if(trimPostRequest('writerPw_type') === 'md5'){
			$writerPw = "md5('" . addslashes($boardRow[$writerPwChange]) . "')";
		} else {
			$writerPw = $boardRow[$writerPwChange];
		}

		//-----------------------------------------------------------
		//- Advice - ���ε� ���ϸ�,�������� ���ϸ�
		//- ������ �����ʵ� ���, ���÷��̽� ó�� ���, Ÿ���̺� ��� �߰�
		//-----------------------------------------------------------
		$oldFile='';
		$newFile='';
		if (!$boardRow[$noChange]) {
			$oldFileRow = array();
			$newFileRow = array();
		}
		
		foreach($oldFileChange as $oldFileChangeRow) {
			if (!$boardRow[$oldFileChangeRow]) continue;
			
			if(trimPostRequest('file_board_cd') && trimPostRequest('boardcd_field')) {
				if (trim($boardRow[$oldFileChangeRow])) {
					$oldFileRow[$boardRow[$boardCodeField]][$boardRow[$noChange]][]=dataCntReplace($boardRow[$oldFileChangeRow], $repOFileBefore, $repOFileAfter, $repOFileCnt);
				}
			} else {
				if (trim($boardRow[$oldFileChangeRow])) {
					$oldFileRow[$boardRow[$noChange]][]=dataCntReplace($boardRow[$oldFileChangeRow], $repOFileBefore, $repOFileAfter, $repOFileCnt);
				}
			}
		}

		foreach($newFileChange as $newFileChangeRow) {
			if (!$boardRow[$newFileChangeRow]) continue;
			if(trimPostRequest('file_board_cd') && trimPostRequest('boardcd_field')) {
				if (trim($boardRow[$newFileChangeRow])) {
					$boardRow[$newFileChangeRow] = dataCntReplace($boardRow[$newFileChangeRow], $repNFileBefore, $repNFileAfter, $repNFileCnt);
					$newFileRow[$boardRow[$boardCodeField]][$boardRow[$noChange]][] = $boardRow[$newFileChangeRow];

				}
			} else {
				if (trim($boardRow[$newFileChangeRow])) {
					$boardRow[$newFileChangeRow] = dataCntReplace($boardRow[$newFileChangeRow], $repNFileBefore, $repNFileAfter, $repNFileCnt);
					$newFileRow[$boardRow[$noChange]][] = $boardRow[$newFileChangeRow];
				}
			}
		}

		if($oldFileRow[$boardRow[$noChange]] || $oldFileRow[$boardRow[$boardCodeField]][$boardRow[$noChange]]){
			if(trimPostRequest('file_board_cd') && trimPostRequest('boardcd_field')) {
				if (!empty($oldFileRow[$boardRow[$boardCodeField]][$boardRow[$noChange]])) {
					$oldFile = implode($oldFileRow[$boardRow[$boardCodeField]][$boardRow[$noChange]], "^|^");
				}
			} else {
				if (!empty($oldFileRow[$boardRow[$noChange]])) {
					$oldFile = implode($oldFileRow[$boardRow[$noChange]], "^|^");
				}
			}
		}
	
		if($newFileRow[$boardRow[$noChange]] || $newFileRow[$boardRow[$boardCodeField]][$boardRow[$noChange]]){
			if(trimPostRequest('file_board_cd') && trimPostRequest('boardcd_field')) {
				if (!empty($newFileRow[$boardRow[$boardCodeField]][$boardRow[$noChange]])) {
					$newFile = implode($newFileRow[$boardRow[$boardCodeField]][$boardRow[$noChange]], "^|^");
				}
			} else {
				if (!empty($newFileRow[$boardRow[$noChange]])) {
					$newFile = implode($newFileRow[$boardRow[$noChange]], "^|^");
				}
			}
		}
		//-----------------------------------------------------------

		//-----------------------------------------------------------
		//- Advice - ��ü ���̺� ��� ����
		//- ��� �����Ͱ� ������ ��ü ���̺��� ����� ���  ��� ���� �迭�� ���� ���� �� ����
		//-----------------------------------------------------------
		if (trimPostRequest('reply_use') == 'S') {
			if (trimPostRequest('replyRegdtCnt') > 1) {
				for ($i = 0; $i < count($replyregDtChange); $i++) {
					$replyRegdt .= $boardRow[$replyregDtChange[$i]];
				}
			} else {
				$replyRegdt = $boardRow[$replyregDtChange[0]];
			}

			$replyRegdt = dateCreate($replyRegdt);

			if (trimPostRequest('reply_writeNm_type') != '') {
				$replyName = trimPostRequest('reply_writeNm_type');
			} else {
				$replyName = $boardRow[$replywriterNmChange];
			}

			if (trimPostRequest('reply_memNo_type') != '') {
				$replyMNo = trimPostRequest('reply_memNo_type');
			} else {
				$replyMNo = $arrayMember[$boardRow[$replymemNoChange]];
			}

			$newMemoQuery[$boardRow[$noChange]][] = "INSERT INTO `es_boardMemo` (`bdId`, `bdSno`, writerNm, `memo`, `memNo`, `regDt`) VALUES ('board_pkid','board_pkno','" . addslashes($replyName) . "','" . addslashes(commentSetting($boardRow[$replyMemoChange])) . "','" . $replyMNo . "','" . $replyRegdt . "');";
		}
		//-----------------------------------------------------------
		if ($memNo != '') {
			if (trimPostRequest('admin_no') != '') {
				if (trimPostRequest('admin_no') == $boardRow[$memNoChange]) {
					$memNo = -1;
				}
			}
		}
		else {
			if (trimPostRequest('admin_no') != '') {
				if(trimPostRequest('admin_no') == $boardRow[$memNoChange]){
					$memNo = -1;
				} else {
					$memNo = $arrayMember[$boardRow[$memNoChange]];
					$writerId = $boardRow[$memNoChange];
				}
			}
			else {
				$memNo = $arrayMember[$boardRow[$memNoChange]];
				$writerId = $boardRow[$memNoChange];
			}
		}

		//-----------------------------------------------------------
		//- Advice - ��� ���� �迭�� �ִ� ���� ���� �� ���
		//-----------------------------------------------------------
		if($replyBoardCd != '' && trimPostRequest('reply_use') =='Y') {
			if (!empty($newMemoQuery[$beforeBoardId][$boardRow[$noChange]])) {
				foreach($newMemoQuery[$beforeBoardId][$boardRow[$noChange]] as $replyInsertQueryRow){
					$memoInsertQuery = '';
					$replyInsertQueryRow = str_replace("board_pkid", $afterBoardId, $replyInsertQueryRow);
					$memoInsertQuery = str_replace("board_pkno",$boardIndex,$replyInsertQueryRow);
					
					$arrayQueryPostData[] = $memoInsertQuery;
					
					$commentCount++;
					$boardTypeCommentCount++;
				}
			}
		}
		else {
			if (!empty($newMemoQuery[$boardRow[$noChange]])) {
				foreach($newMemoQuery[$boardRow[$noChange]] as $replyInsertQueryRow){
					$memoInsertQuery = '';
					$replyInsertQueryRow = str_replace("board_pkid", $afterBoardId, $replyInsertQueryRow);
					$memoInsertQuery = str_replace("board_pkno", $boardIndex, $replyInsertQueryRow);

					$arrayQueryPostData[] = $memoInsertQuery;
					
					$commentCount++;
					$boardTypeCommentCount++;
				}
			}
		}
		// ----------------------------------------------------------

		//-----------------------------------------------------------
		//- Advice - ���� ���� ����
		//-----------------------------------------------------------
		if ($fileCopyFl) {
			$arrayNewFile = array();
			$oldFilePath = '';
			// ÷������ ����


			if (trimPostRequest('filePathOldBoardCodeYn') == 'Y') {
				$oldFilePath = $sFilePathOrg . $beforeBoardId . '/';
			}
			else {
				$oldFilePath = $sFilePathOrg;
			}

			if (trimPostRequest('filePath') && trimPostRequest('filePathFieldYn') == 'Y') {
				$oldFilePath .= $boardRow[trimPostRequest('filePath')] . '/';
			}
			else if (trimPostRequest('filePath') && !trimPostRequest('filePathFieldYn')) {
				$oldFilePath .= trimPostRequest('filePath') . '/';
			}

			$arrayNewFile = explode('^|^', $newFile);
			for ($i = 0; $i <= count($arrayNewFile) - 1; $i++) {// ÷���̹���
				$oldBoardId = ($boardRow[$noChange]) ? $boardRow[$noChange] : $boardIndex;
				if ($arrayNewFile[$i]) {
					$oldFileName = $arrayNewFile[$i];
					$arrayNewFile[$i] = fileRename($arrayNewFile[$i], $afterBoardId . '_oldboardSno' . $oldBoardId . '_' . $i);

					if (!trimPostRequest('file_rename_yn')) {
						if (trimPostRequest('localCopy') == 'Y') {
							$sTmpOldPath = $oldFilePath . $oldFileName;
						}
						else {
							$sTmpOldPath = $oldFilePath . str_replace('%2F', '/', str_replace('+', '%20', urlencode($oldFileName)));
						}
						$sTmpNewPath = $sFilePathNew . "/" . $arrayNewFile[$i];

						$copyFl = $setFile->fileCopy(str_replace('%252F', '/', $sTmpOldPath), $sTmpNewPath, trimPostRequest('fileCopyCheck'));
						
						if ($copyFl) {
							$attachImgInfo = getImageSize($sTmpNewPath);
							$tempFileExt = explode('/', $attachImgInfo['mime']);
							if ($tempFileExt[1] == 'jpg' || $tempFileExt[1] == 'jpeg' || $tempFileExt[1] == 'png' || $tempFileExt[1] == 'bmp' || $tempFileExt[1] == 'gif') {
								$changeWidthSize = $attachImgInfo[0] * 200 / $attachImgInfo[1];
								$oriFileExt = $tempFileExt[1];
								
								$sTmpThumbNewPath = $sThumbFilePathNew . $arrayNewFile[$i];

								$setFile->createThumbnail($sTmpNewPath, $sTmpThumbNewPath, $changeWidthSize, 200, $oriFileExt, trimPostRequest('fileCopyCheck'));
							}
						}
					}
				}
			}
			$newFile = implode('^|^', $arrayNewFile);
		}
		//-----------------------------------------------------------

		//-----------------------------------------------------------
		//- Advice - ���� �� �ۼ� �̹��� ���� ���
		//- ������ ���� �ʵ�, ���÷��̽� ó�� ���
		//-----------------------------------------------------------
		$contents = '';
		if(trimPostRequest('contentsCnt') > 1){
			for ($i = 0; $i < count($contentsChange); $i++){
				$contents .= '<div>' . $boardRow[$contentsChange[$i]] . '</div>';
			}
		} else {
			$contents = $boardRow[$contentsChange[0]];
		}

		$contents = stripslashes($contents);
		$contents = dataCntReplace($contents, $repContBefore, $repContAfter, $repContCnt);

		if ($editorFileCopyFl) {
			$setFile->editorFileInfoSet(trimPostRequest('editorFileDomain'), trimPostRequest('editorFileDefaultPath'), 'board/' . $afterBoardId);

			$contents = $setFile->editorCopy($contents, $afterBoardId . '_' . $boardIndex . '_boardEditor_', trimPostRequest('editorFileCopyCheck'));

			if ($imgHostingDomain) {
				$contents = preg_replace("/(src)(\"|'|=\"|='|=)(\/data\/)/i", '$1' . '$2' . 'http://' . $imgHostingDomain . '$3', $contents);
				$contents = preg_replace("/(src)(\"|'|=\"|='|=)(\/data\/)/i", '$1' . '$2' . 'http://' . $imgHostingDomain . '$3', $contents);
			}
		}
		
		$contents = nl2br($contents);
		//-----------------------------------------------------------

		//-----------------------------------------------------------
		//- Advice - �Խñ� ������ new_board �迭������ �� �ʵ�� Ű���� ����
		//-----------------------------------------------------------
		$newBoard['sno']				= $boardIndex;
		$newBoard['groupNo']			= "-" . $boardGroupCount;
		$newBoard['groupThread']		= $boardThreadChangeValue;
		$newBoard['parentSno']			= $boardParentSno;
		$newBoard['writerNm']			= $writerNm;
		$newBoard['writerNick']			= $writerNick;
		$newBoard['writerEmail']		= $writerEmail;
		$newBoard['writerHp']			= $writerHp;
		$newBoard['subject']			= ($subject) ? $subject : '<span style="color:#999999;">������ �����Ǿ����ϴ�</span>';
		$newBoard['contents']			= $contents;
		$newBoard['memoCnt']			= $commentCount;
		$newBoard['urlLink']			= $urlLink;
		$newBoard['uploadFileNm']		= $oldFile;
		$newBoard['saveFileNm']			= $newFile;
		$newBoard['writerPw']			= $writerPw;
		$newBoard['memNo']				= $memNo;
		$newBoard['writerId']			= $writerId;
		$newBoard['writerIp']			= $ip;
		$newBoard['isNotice']			= $isNotice;
		$newBoard['isSecret']			= $isSecret;
		$newBoard['isDelete']			= 'n';
		$newBoard['writerMobile']		= $writerMobile;
		$newBoard['goodsNo']			= $goodsNo;
		$newBoard['goodsPt']			= $goodsPt;
		$newBoard['hit']				= $hit;
		$newBoard['category']			= $category;
		$newBoard['regdt']				= $regDt;
		$newBoard['moddt']				= $modDt;
		$newBoard['bdUploadStorage']	= "local" ;
		$newBoard['bdUploadPath']		= "upload/" . $afterBoardId . "/" ;
		$newBoard['bdUploadThumbPath']	= "upload/" . $afterBoardId . "/t/" ;

		/*���*/
		$newBoard['answerSubject']		= addslashes($AnswerSubject);
		$newBoard['answerContents']		= addslashes($AnswerContents);
		$newBoard['answerManagerNo']	= '-1';
		$newBoard['replyStatus']		= ($AnswerContents) ?'3' : '2';
		$newBoard['answerModDt']		= $AnswerModDt;

		if ($arrayBoardKindQa[$boardTypeCount] == 'Y') {
			if (!$boardParentSno) {
				$insertSet->querySet($newBoard, $dataCnt + 1);
			}
			else {
				$arrayBoardReplyString = array();
				$arrayBoardReplyString[] = "answerSubject ='" . addslashes($subject) . "'";
				$arrayBoardReplyString[] = "answerContents ='" . addslashes($contents) . "'";
				$arrayBoardReplyString[] = "answerManagerNo = '-1'";
				$arrayBoardReplyString[] = "replyStatus = '3'";
				$arrayBoardReplyString[] = "answerModDt = '" . $regDt . "'";
				$arrayReplyQuery[] = "Update es_bd_" . $afterBoardId . " Set " . implode(', ', $arrayBoardReplyString) . " Where sno='" . $boardParentSno . "' and groupNo ='-" . $boardGroupCount . "';";
			}
		}
		else {
			$insertSet->querySet($newBoard, $dataCnt + 1);
		}
		
		if ($mode === "start_q") {
			if (trimPostRequest('queryRoopLimit') == $dataCnt) {
				$queryPrintCount = 1;
				$arrayQueryPostData = $insertSet->getQuery($arrayQueryPostData);
				$arrayQueryPostData = array_merge($arrayQueryPostData, $arrayReplyQuery);

				foreach ($arrayQueryPostData as $printQuery) {
					debug($queryPrintCount . " : " . $printQuery);
					$queryPrintCount++;
				}
				echo '<script type="text/javascript">parent.configSubmitComplete("' . $dataCnt . '");</script>';
				exit;
			}
		}
		else if ($mode === "start") {
			if ((($totDataCnt + 1) % 1000) == 0) {
				$arrayQueryPostData = $insertSet->getQuery($arrayQueryPostData);
				dumpSqlFileSet ($dumpFileName, $arrayQueryPostData);
				unset($arrayQueryPostData);
				$arrayQueryPostData = array();
			}
		}
	
		$dataCnt++;
		$totDataCnt++;
	}

	$arrayQueryPostData = $insertSet->getQuery($arrayQueryPostData);
	$arrayQueryPostData = array_merge($arrayQueryPostData, $arrayReplyQuery);
	if ($mode === "start") {
		if (!empty($arrayCategoryList[$afterBoardId])) {
			$boardCategoryList = implode('^|^', $arrayCategoryList[$afterBoardId]);
			$arrayQueryPostData[] = "Update es_board Set bdCategoryFl = 'y', bdCategoryTitle = '����', bdCategory = '" . $boardCategoryList . "' Where bdId = '" . $afterBoardId . "';";
		}
		$arrayQueryPostData[] = "OPTIMIZE TABLE es_bd_" . $afterBoardId.";";
	}
	else if ($mode === "start_q") {
		foreach ($arrayQueryPostData as $printQuery) {
			debug($queryPrintCount . " : " . $printQuery);
			$queryPrintCount++;
		}
	}

	echo '<div>"' . $afterBoardId . '" �Խ��� ���� ���� : ' . $dataCnt . ' �� </div>';
	$boardTypeCount++;
}
if ($mode === "start") {
	if (!empty($arrayGoodsReviewCount)) {
		foreach ($arrayGoodsReviewCount as $goodsNo => $reviewCount) {
			$arrayQueryPostData[] = "Update es_goods Set reviewCnt = " . $reviewCount . " Where goodsNo = " . $goodsNo . ";";
			$arrayQueryPostData[] = "Update es_goodsSearch Set reviewCnt = " . $reviewCount . " Where goodsNo = " . $goodsNo . ";";
		}
	}

	$arrayQueryPostData[] = "OPTIMIZE TABLE es_boardMemo;";

	dumpSqlFileSet ($dumpFileName, $arrayQueryPostData);
	echo '�� ������ ���� �Ǽ� : ' . $totDataCnt . '�� <script type="text/javascript">parent.configSubmitComplete("' . $totDataCnt . '");</script>';
}
else if ($mode === "start_q") {
	echo '<script type="text/javascript">parent.configSubmitComplete("' . $totDataCnt . '");</script>';
}

?>