<?
include '../../inc/header.php';
$arrayQueryPostData		= array();		// 생성 쿼리 저장 배열
$sFilePathOrg = '';						// 기존 복사 경로 설정
$sFilePathNew = '';
$sThumbFilePathNew = '';
$imgHostingDomain = trimPostRequest('imgHostingDomain'); // 복사 후 이미지 호스팅 경로

$arrayBeforeBoardCode		= trimPostRequest('before_board');		// 기존 게시판 코드
$arrayAfterBoardCode		= trimPostRequest('after_board');		// 이전 후 게시판 코드
$arrayBoardName				= trimPostRequest('boardname');			// 이전 전 게시판 코드
$arrayBoardKindQa			= trimPostRequest('boardKindQa');			// 게시판 형식 QA 형식 여부

//------------------------------------------------------
// - Advice - 상품 복사 기능 사용시 복사 준비
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
// - Advice - 고도몰5 사용 스킨 체크
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
// - Advice - 게시판 이전 필드 전역 변수
//------------------------------------------------------
$boardCodeField				= trimPostRequest('boardcd_field');
$noChange					= trimPostRequest('no_change');					//일련번호
$groupChange				= trimPostRequest('group_change');				//그룹번호
$groupDepthAutoCheckChange	= trimPostRequest('groupDepthAutoCheck');		//답글 자동 길이 계산
$groupDepthChange			= trimPostRequest('groupDepth_change');			//답글 뎁스 구분
$subChange					= trimPostRequest('sub_change');				//답글구분
$subTypeFlag				= trimPostRequest('sub_type');
$writerNmChange				= trimPostRequest('writerNm_change');			//작성자명
$writerNickChange				= trimPostRequest('writerNick_change');			//작성자명
$writerEmailChange			= trimPostRequest('writerEmail');				//이메일
$wrtierHpChange				= trimPostRequest('writerHp_change');			//홈페이지
$subjectChange				= trimPostRequest('subject_change');			//글 제목
$contentsChange				= trimPostRequest('contents');					//내용
$oldFileChange				= trimPostRequest('oldFile');				//이전 파일명
$newFileChange				= trimPostRequest('newFile');				//서버 저장 파일명
$urlLinkChange				= trimPostRequest('urlLink_change');			//링크
$writerPwChange				= trimPostRequest('writerPw_change');			//비밀번호
$memNoChange				= trimPostRequest('memNo_change');				//회원 고유번호
$ipChange					= trimPostRequest('ip');						//등록 ip
$isNoticeChange				= trimPostRequest('isNotice_change');			//공지여부
$isSecretChange				= trimPostRequest('isSecret_change');			//비밀글 여부
$hitChange					= trimPostRequest('hit_change');				//조회수
$goodsNoChange				= trimPostRequest('goodsNo_change');			//상품번호
$writerMobileChange			= trimPostRequest('writerMobile_change');		//작성자 휴대폰번호
$goodsPtChange				= trimPostRequest('goodsPt_change');			//상품평점
$mileageChange				= trimPostRequest('mileage_change');			//적립 마일리지

$categoryChange				= trimPostRequest('category_change');			//말머리
$regDtChange				= trimPostRequest('regDt');						//등록일
$modDtChange				= trimPostRequest('modDt');						//수정일

$delete_field				= trimPostRequest('delete_field');				//게시글 삭제 구분 필드

/*답글 Row 미분리 추가 input*/
$answerSeparate				= trimPostRequest('answer_separate');			// 답글 Row 분리여부(All,Y,N)
$AnswerSubjectChange		= trimPostRequest('separate_AnswerSubject');	// 답글 Row 분리 제목목
$AnswerContentsChange		= trimPostRequest('separate_AnswerContents');	// 답글 Row 분리 내용
$AnswerManagerNoChange		= trimPostRequest('separate_AnswerManagerNo');	// 답글 Row 분리 관리자 아이디
$AnswerModDtChange			= trimPostRequest('separate_AnswerModDt');		// 답글 Row 분리 작성일자
//------------------------------------------------------

//------------------------------------------------------
// - Advice - 데이터 리플레이스 관련 변수
//------------------------------------------------------
$repContBefore				= trimPostRequest('rep_contents_before');		//콘텐츠 리플레이스 변경 전 변수
$repContAfter				= trimPostRequest('rep_contents_after');		//콘텐츠 리플레이스 변경 후 변수
$repContCnt					= trimPostRequest('repcontentsCnt');			//콘텐츠 리플레이스 카운터 변수

$repOFileBefore				= trimPostRequest('rep_oldFile_before');		//이전 파일명 리플레이스 변경 전 변수
$repOFileAfter				= trimPostRequest('rep_oldFile_after');			//이전 파일명 리플레이스 변경 후 변수
$repOFileCnt				= trimPostRequest('repOldFileCnt');				//이전 파일명 리플레이스 카운터 변수

$repNFileBefore				= trimPostRequest('rep_newFile_before');		//서버 파일명 리플레이스 변경 전 변수
$repNFileAfter				= trimPostRequest('rep_newFile_after');			//서버 파일명 리플레이스 변경 후 변수
$repNFileCnt				= trimPostRequest('repNewFileCnt');				//서버 파일명 리플레이스 카운터 변수

$repCategoryBefore			= trimPostRequest('rep_category_before');		//말머리 변경 후 변수
$repCategoryAfter			= trimPostRequest('rep_category_after');		//말머리 변경 전 변수
$repCategoryCnt				= trimPostRequest('repcategoryCnt');				//말머리 카운터 변수

$repPointBefore				= trimPostRequest('rep_goodsPt_before');		//평가 변경 전 변수
$repPointAfter				= trimPostRequest('rep_goodsPt_after');			//평가 변경 후 변수
$repPointCnt				= trimPostRequest('repgoodsPtCnt');				//평가 리플레이스 카운터 변수

$groupDepthBefore			= trimPostRequest('rep_groupDepth_before');		//게시글 답글 뎁스 변경 전 변수
$groupDepthAfter			= trimPostRequest('rep_groupDepth_after');		//게시글 답글 뎁스 변경 후 변수
$groupDepthCnt				= trimPostRequest('groupDepthCnt');				//게시글 답글 뎁스 리플레이스 카운터 변수
//------------------------------------------------------

//------------------------------------------------------
// - Advice - 댓글 이전 필드 변수
//------------------------------------------------------
$replyIndexNo			= trimPostRequest('reply_index_no');			//리플 데이터 원 게시글 일련번호
$replyBoardCd			= trimPostRequest('reply_boardcd');				//리플 데이터 원 게시판 코드번호 있는 경우만 사용
$replywriterNmChange		= trimPostRequest('reply_writerNm_change');			//작성자명
$replymemNoChange			= trimPostRequest('reply_memNo_change');			//회원번호
$replyMemoChange		= trimPostRequest('reply_memo_change');			//내용
$replywriterPwChange	= trimPostRequest('reply_writerPw_change');		//비밀번호
$replyregDtChange		= trimPostRequest('replyRegdt');				//댓글 등록일
//------------------------------------------------------

//------------------------------------------------------
// - Advice - 공 데이터 전역변수 및 배열변수
//------------------------------------------------------
$boardSort				='';									//sql실행시 정렬 순서 변수
$arrayTempData			= array();								//게시판 데이터 초기 임시 배열
$newBoard				= array();								//게시글 데이터 키 배열 작성
$newMemoQuery			= array();								//게시글 댓글 등록 쿼리 임시 배열
$fileData				= array();								//파일 데이터 로드 후 임시저장 배열
$oldFileRow				= array();								//이전 파일명 임시 저장 배열
$newFileRow				= array();								//서버 파일명 임시 저장 배열
//------------------------------------------------------

//------------------------------------------------------
// - Advice - 등록 된 회원 정보 추출
//------------------------------------------------------
$arrayMemberCheckPostData			= array();			// 이전 후 쇼핑몰 회원 데이터 체크
$arrayMemberCheckPostData['mode']	= 'memberCheck';	//처리 프로세스 기본 모드 값 삽입

$object = @xmlUrlRequest($url, $arrayMemberCheckPostData);
$memberData = $object->memberData;

$arrayMember = array();
foreach($memberData as $value) {
	$newMno = (int)$value->attributes()->memNo;
	$arrayMember[urldecode((string)$value)] = $newMno;
}
//------------------------------------------------------

//------------------------------------------------------
// - Advice - 등록 된 상품 정보 추출
//------------------------------------------------------
$arrayGoodsNo = array();
$goodsNoResult = $db->query("Select originalGoodsKey, godo5GoodsNo From tmp_goodsno");
while ($goodsNoRow = $db->fetch($goodsNoResult)) {
	$arrayGoodsNo[$goodsNoRow['originalGoodsKey']] = $goodsNoRow['godo5GoodsNo'];
}
//------------------------------------------------------

//------------------------------------------------------
// - Advice - 첨부파일 테이블 따로 있을 때 파일명 배열 저장
//------------------------------------------------------
if(trimPostRequest('file_use') == 'Y') {
	//------------------------------------------------------
	// - Advice - 첨부파일 타테이블 전역 변수
	//------------------------------------------------------
	$tableOldFileChange			= trimPostRequest('fileTableOld');			//이전 파일명
	$tableNewFileChange			= trimPostRequest('fileTableNew');			//서버 저장 파일명

	$repTableOFileBefore		= trimPostRequest('rep_table_ofile_before');		//타 테이블 이전 파일명 리플레이스 변경 전 변수
	$repTableOFileAfter			= trimPostRequest('rep_table_ofile_after');			//타 테이블 이전 파일명 리플레이스 변경 후 변수
	$repTableOFileCnt			= trimPostRequest('rep_table_ofileCnt');				//타 테이블 이전 파일명 리플레이스 카운터 변수

	$repTableNFileBefore		= trimPostRequest('rep_table_nfile_before');		//타 테이블 서버 파일명 리플레이스 변경 전 변수
	$repTableNFileAfter			= trimPostRequest('rep_table_nfile_after');			//타 테이블 서버 파일명 리플레이스 변경 후 변수
	$repTableNFileCnt			= trimPostRequest('rep_table_nfileCnt');				//타 테이블 서버 파일명 리플레이스 카운터 변수

	//------------------------------------------------------

	//------------------------------------------------------
	// - Advice - 테이블 첨부파일 데이터를 첨부파일 배열에 삽입
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
// - Advice - 댓글 테이블 따로 있을 때 등록 쿼리 배열 저장
//------------------------------------------------------
if(trimPostRequest('reply_use') == 'Y') {
	if(trimPostRequest('comment_data_type') == 'csv'){// 댓글 테이블이 CSV로 되어 있는 데이터 로드
		if(trimPostRequest('reply_table_separate') == 'Y'){ // 게시판 CSV가 나눠져 있는 데이터 로드후 일괄 데이터 저장 후 never_board_code 데이터에 이전 코드명 입력
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
	} else if(trimPostRequest('comment_data_type') == 'sql') {// 댓글 테이블이 SQL로 되어 있는 데이터 로드
		if(trimPostRequest('comment_sort')){
			$commentSort = ' order by ' . trimPostRequest('comment_sort');
		}
		if(trimPostRequest('reply_table_separate') == 'Y'){// 댓글 테이블이 나눠져 있는 데이터 로드후 일괄 데이터 저장 후 never_board_code 데이터에 이전 코드명 입력
			foreach($arrayBeforeBoardCode as $beforeCode){
				$res = $db->query("select " . trimPostRequest('comment_select_field') . ", '" . $beforeCode . "' as never_board_code from " . trimPostRequest('comment_data_name') . $beforeCode . trimPostRequest('reply_separate_after_name') . $commentSort);

				while ($replyDataRow = $db->fetch($res)) $replyData[] = $replyDataRow;
			}
		} else {// 일반 데이터 로드
			$res = $db->query("select " . stripslashes(trimPostRequest('comment_select_field')) . " from " . trimPostRequest('comment_data_name') . $commentSort);
			while ($replyDataRow = $db->fetch($res)) $replyData[] = $replyDataRow;
		}
	}

	if($replyData){// 댓글 데이터 인서트 쿼리 생성 후 댓글 쿼리 배열에 삽입
		foreach($replyData as $replyRow){
			if(trimPostRequest('comment_delete_type')){
				if($replyRow[trimPostRequest('comment_delete_field')] == trimPostRequest('comment_delete_type')) continue;
			}
			//-----------------------------------------------------------
			//- Advice - 댓글 등록일
			//- 댓글 등록일은 다중 필드
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
// - Advice - 이전 전 이전 후 게시판 코드 매칭 및 이전 전 게시판 코드로 배열 변수 선언
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
// - Advice - 게시판 데이터 로드
//------------------------------------------------------
if(trimPostRequest('data_type') === 'csv') { // CSV 파일 데이터 로드
	if(trimPostRequest('table_separate') == 'Y') { // 게시판 CSV가 나눠져 있는 데이터 로드후 일괄 데이터 저장 후 never_board_code 데이터에 이전 코드명 입력
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
} else if(trimPostRequest('data_type') === 'sql') {// SQL 데이터 로드
	if(trimPostRequest('sort')){
		$boardSort = ' order by ' . trimPostRequest('sort');
	}
	if(trimPostRequest('table_separate') == 'Y'){// 게시판 테이블이 나눠져 있는 데이터 로드후 일괄 데이터 저장 후 never_board_code 데이터에 이전 코드명 입력
		foreach($arrayBeforeBoardCode as $beforeBoardCode){
			$res = $db->query("select " . stripslashes(trimPostRequest('select_field')) . ", '" . $beforeBoardCode . "' as never_board_code from " . trimPostRequest('data_name') . $beforeBoardCode . trimPostRequest('separate_after_name') . stripslashes($boardSort));
			while($tt = $db->fetch($res) ) $arrayTempData[] = $tt;
		}
	} else {// 일반 데이터 로드
		$res = $db->query("select " . stripslashes(trimPostRequest('select_field')) . " from " . trimPostRequest('data_name') . stripslashes($boardSort));
		while($tt = $db->fetch($res) ) $arrayTempData[] = $tt;
	}
}

foreach($arrayTempData as $row) {
	for($codeCnt = 0; $codeCnt <= count($arrayAfterBoardCode) - 1; $codeCnt++){
		// 게시판 테이블이 나눠져 있는 경우 never_board_code(이전 게시판 코드)필드값 사용 --
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
$totDataCnt		= 0; // 총 게시글 카운트
$boardTypeCount = 0; //게시판 루프 갯수 카운트
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
	//boardKind 임시 변수
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
	//- Advice - 게시글 es_board 쿼리 생성
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
		/*권한*/
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
	// - Advice - 게시판 테이블 이전 전 필드명
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
	$boardTypeCommentCount	= 0;		// 게시판 별 등록 댓글 수
	$queryPrintCount		= 0;
	$boardGroupCount		= 0;
	$boardGroupNoArray		= array();

	$arrayCategoryList = array();
	//-----------------------------------------------------------
	//- Advice - 게시글 실질적 Row foreach
	//-----------------------------------------------------------
	foreach (${$afterBoardId} as $boardRow) {
		if($deleteField){
			if($boardRow[$deleteField] == $_POST['delete_type']) continue;
		}
		$newBoard = array();

		$boardIndex = $dataCnt + 1; // 게시글 일련번호
		$commentCount = 0; // 댓글 수
		$parentMember = 0; // 부모글 작성자 일련번호
		
		//-----------------------------------------------------------
		//- Advice - 첨부파일 경로 생성
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
		//- Advice - 홈페이지, 제목, 링크
		//- Advice - 공지 여부, 비밀글 여부, html 여부, 조회수
		//- 상단 필드는 단일 컬럼 사용 및 특이 타입 없는 필드
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
		$AnswerSubject = '';			// 답글 Row 분리 제목
		$AnswerContents = '';			// 답글 Row 분리 내용
		$AnswerManagerNo = '';			// 답글 Row 분리 관리자 아이디
		$AnswerModDt = '';			// 답글 Row 분리 작성일자

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
		//- Advice - 전화번호
		//- 전화번호는 추가 필드 가능
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

		$AnswerSubject = (trimPostRequest('replycontentSubject') == 'y' && !empty($boardRow[$AnswerContentsChange])) ? '[답변] ' . $subject : $boardRow[$AnswerSubjectChange];

		$AnswerContents = $boardRow[$AnswerContentsChange];			// 답글 Row 분리 내용
		$AnswerContents = str_replace('\"', '', $AnswerContents);

		$AnswerManagerNo = $boardRow[$AnswerManagerNoChange];		// 답글 Row 분리 관리자 아이디
		$AnswerModDt = dateCreate($boardRow[$AnswerModDtChange]); // 답글 Row 분리 작성일자
		//-----------------------------------------------------------

		//-----------------------------------------------------------
		//- Advice - 게시판 그룹
		//- 게시판 그룹 데이터는 단일 필드
		//-----------------------------------------------------------
		//echo "체크 : " . trimPostRequest('groupDepthAutoCheck')."<br/>";
		//echo "기존 그룹 뎁스 필드명 : " . $boardRow[$groupDepthChange[0]] . "<br/>";
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
		//- Advice - 게시판 답글 그룹핑
		//-createGroupThread(그루핑 그룹 갯수 변수, 그루핑 깊이 수 변수, 그룹 답글 변수 1~4차답글, 현재 글 뎁스 값)
		//-----------------------------------------------------------

		$boardGroupNoArray['cnt'][] = $boardGroupCount; //그루핑 그룹 갯수 변수
		$boardGroupNoArray['group'][] = $groupSort;	//그루핑 깊이 수 변수
		$boardGroupNoArray['boardIdx'][] = $boardIndex; // 게시글 인덱스 변수
		$boardGroupThreadArray['sub'][]	= $sub; //답글여부 변수
		$boardGroupThreadArray['subTypeValue'][]	= $subType; // 답글 기준 Flag 변수
		$boardGroupThreadArray['subDepth'][]	= $groupDepth; // 그룹 답글 변수 1차, 2차, 3차, 4차답글 등
		if($groupSort == $boardGroupNoArray['group'][0] && $sub != $subTypeFlag) {
			$boardGroupCount = $boardGroupNoArray['cnt'][0];	//
			$cntGroupThread = count($boardGroupThreadArray['thread'])-1; //배열 갯수
			$boardGroupThreadParentArray = $boardGroupThreadArray['thread'][$cntGroupThread-1];//배열의 바로 위 값
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
		//- Advice - 작성, IP
		//- 작성일, IP은 다중 필드
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
		//- Advice - 작성자명, 닉네임
		//- 작성자명은 단일 필드
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
		//- Advice - 회원 아이디(회원번호)
		//- 아이디 단일 필드
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
		//- Advice - 평가
		//- 평가는 단일 필드
		//-----------------------------------------------------------
		if($repPointCnt > 0){
			$goodsPt = dataIfChange($boardRow[$goodsPtChange], $repPointBefore, $repPointAfter, $repPointCnt);
		} else {
			$goodsPt = $boardRow[$goodsPtChange];
		}
		//-----------------------------------------------------------

		//-----------------------------------------------------------
		//- Advice - 상품번호
		//- 상품번호는 단일 필드
		//-----------------------------------------------------------
		$goodsNo   = ($arrayGoodsNo[$boardRow[$goodsNoChange]]) ? $arrayGoodsNo[$boardRow[$goodsNoChange]] : ''; // 이전 된 상품 번호 삽입
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
		//- Advice - 이메일
		//- 이메일은 다중 필드
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
		//- Advice - 비밀번호
		//- 비밀번호는 단일 필드
		//-----------------------------------------------------------
		$writerPw = '';
		if(trimPostRequest('writerPw_type') === 'md5'){
			$writerPw = "md5('" . addslashes($boardRow[$writerPwChange]) . "')";
		} else {
			$writerPw = $boardRow[$writerPwChange];
		}

		//-----------------------------------------------------------
		//- Advice - 업로드 파일명,서버저장 파일명
		//- 파일은 다중필드 기능, 리플레이스 처리 기능, 타테이블 기능 추가
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
		//- Advice - 자체 테이블 댓글 셋팅
		//- 댓글 데이터가 있지만 자체 테이블에서 사용할 경우  댓글 쿼리 배열에 쿼리 생성 후 삽입
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
		//- Advice - 댓글 쿼리 배열에 있는 쿼리 실행 및 출력
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
		//- Advice - 파일 복사 시작
		//-----------------------------------------------------------
		if ($fileCopyFl) {
			$arrayNewFile = array();
			$oldFilePath = '';
			// 첨부파일 복사


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
			for ($i = 0; $i <= count($arrayNewFile) - 1; $i++) {// 첨부이미지
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
		//- Advice - 내용 및 작성 이미지 복사 기능
		//- 내용은 다중 필드, 리플레이스 처리 기능
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
		//- Advice - 게시글 데이터 new_board 배열변수에 각 필드명 키값에 삽입
		//-----------------------------------------------------------
		$newBoard['sno']				= $boardIndex;
		$newBoard['groupNo']			= "-" . $boardGroupCount;
		$newBoard['groupThread']		= $boardThreadChangeValue;
		$newBoard['parentSno']			= $boardParentSno;
		$newBoard['writerNm']			= $writerNm;
		$newBoard['writerNick']			= $writerNick;
		$newBoard['writerEmail']		= $writerEmail;
		$newBoard['writerHp']			= $writerHp;
		$newBoard['subject']			= ($subject) ? $subject : '<span style="color:#999999;">원문이 삭제되었습니다</span>';
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

		/*답글*/
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
			$arrayQueryPostData[] = "Update es_board Set bdCategoryFl = 'y', bdCategoryTitle = '구분', bdCategory = '" . $boardCategoryList . "' Where bdId = '" . $afterBoardId . "';";
		}
		$arrayQueryPostData[] = "OPTIMIZE TABLE es_bd_" . $afterBoardId.";";
	}
	else if ($mode === "start_q") {
		foreach ($arrayQueryPostData as $printQuery) {
			debug($queryPrintCount . " : " . $printQuery);
			$queryPrintCount++;
		}
	}

	echo '<div>"' . $afterBoardId . '" 게시판 이전 갯수 : ' . $dataCnt . ' 건 </div>';
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
	echo '총 데이터 이전 건수 : ' . $totDataCnt . '건 <script type="text/javascript">parent.configSubmitComplete("' . $totDataCnt . '");</script>';
}
else if ($mode === "start_q") {
	echo '<script type="text/javascript">parent.configSubmitComplete("' . $totDataCnt . '");</script>';
}

?>