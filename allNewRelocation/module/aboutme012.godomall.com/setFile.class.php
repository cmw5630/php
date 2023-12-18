<?php
	/*
		setFile 클래스
	*/
class setFile {
	
	private $bulkFileFl;					// 대용량 데이터 여부
	private $localFileExistsList;			// 로컬 파일 사전 체크 리스트 배열
	private $localDirExistsList;			// 로컬 디렉터리 사전 체크 리스트 배열

	private $localCopy;						// 로컬 다운 파일 복사 기능 사용 여부

	private $arrayOriEditorFilePath;			// 에디터 복사 경로 정보
	private $newEditorFilePath;					// 신규 복사 경로 정보
	private $editorFileCopyList;				// 중복 파일 복사 방지 배열

	
	public function __construct($bulkFileFl = false, $localCopy = false) {
		$this->bulkFileFl				= ($bulkFileFl == 'Y') ? true : false;

		$this->localCopy				= $localCopy;
		
		$this->localFileExistsList		= array();
		$this->localDirExistsList		= array();
		
		$this->arrayOriEditorFilePath	= array();
		$this->newEditorFilePath		= '';
		$this->editorFileCopyList		= array();
	}

	public function makeDir($localDirPath) {
		$dirExistsFl = false;
		if (!$this->bulkFileFl) {
			$dirExistsFl = ($this->localDirExistsList[$localDirPath]);
		}
		else {
			$dirExistsFl = (is_dir($localDirPath));
		}
		if (!$dirExistsFl) { 
			if (@mkdir($localDirPath)) {
				if (!$this->bulkFileFl) {
					$this->localDirExistsList[$localDirPath] = true;
				}
			}
		}
	}

	public function fileListCheck($localPath) {
		if (!$this->bulkFileFl) {
			$openDir = opendir($localPath);
	
			while ($openDirRow = readdir($openDir)) {
				if ($openDirRow != '.' && $openDirRow != '..') {
					$newPath = $localPath . '/' . $openDirRow;
					if (is_dir($newPath)) {
						$this->localDirExistsList[$newPath] = true;
						$this->fileListCheck($newPath);
					}
					if (is_file($newPath)) {
						$this->localFileExistsList[$newPath] = true;
					}
				}
			}
		}
	}

	private function fileCheck($localFilePath) {
		$fileExistsFl = false;
		if (!$this->bulkFileFl) {
			if ($this->localFileExistsList[$localFilePath]) $fileExistsFl = true;
		}
		else {
			if (file_exists($localFilePath)) $fileExistsFl = true;
		}

		return $fileExistsFl;
	}

	public function fileCopy($oldFile, $newFile, $msgPrintFl = false) {
		$msg = '';

		$sucessFl = false;
		if (!$this->fileCheck($newFile)) {
			if (@copy($oldFile, $newFile)) {
				if (!$this->bulkFileFl) {
					$this->localFileExistsList[$newFile] = true;
				}
				$msg = '<div style="color:blue;">복사 성공 : ' . $oldFile . ' => ' . $newFile . '</div>';
				$sucessFl = true;
			}
			else {
				$msg = '<div style="color:red;font-weight:bold;">복사 실패 : ' . $oldFile . ' => ' . $newFile . '</div>';
			}
		}
		else {
			$msg = '<div style="color:blue;">이전 후 파일 존재 : ' . $newFile . '</div>';
			$sucessFl = true;
		}

		if ($msgPrintFl) {
			echo $msg;
		}

		return $sucessFl;
	}

	public function editorFileInfoSet($arrayEditorFileDomain, $arrayEditorFileDefaultPath, $editorPath) {
		for ($i = 0; $i <= count($arrayEditorFileDomain) - 1; $i++) {
			$this->arrayOriEditorFilePath[$i][0] = 'http://' . $arrayEditorFileDomain[$i] . $arrayEditorFileDefaultPath[$i];
			$this->arrayOriEditorFilePath[$i][1] = 'http://www.' . $arrayEditorFileDomain[$i] . $arrayEditorFileDefaultPath[$i];
			$this->arrayOriEditorFilePath[$i][2] = 'src="' . $arrayEditorFileDefaultPath[$i];
			$this->arrayOriEditorFilePath[$i][3] = "src='" . $arrayEditorFileDefaultPath[$i];
			$this->arrayOriEditorFilePath[$i][4] = $arrayEditorFileDefaultPath[$i];

			$this->newEditorFilePath = './data/editor/' . $editorPath . '/';
			$arrayNewEditorFilePath = explode('/', $this->newEditorFilePath);
			$makePath = '';
			foreach ($arrayNewEditorFilePath as $makeEditorPath) {
				$makePath .= $makeEditorPath . '/';
				$this->makeDir($makePath);
			}
		}
	}

	public function editorCopy ($oriContents, $addFileName, $msgFl) {
		$msgFl = ($msgFl == 'Y') ? true : false;
		
		$changeFilePath = preg_replace('/^[\.][\/]/i', '/', $this->newEditorFilePath);

		$contentsCopy = $oriContents;
					
		preg_match_all("/(img|IMG)[^\>]+(src|SRC)=(\"|'|)[^\"'>]+/i", $contentsCopy, $media); // 이미지 태그 추출
				
		unset($contentsCopy);

		$contentsCopy = preg_replace("/(img|IMG)[^\>]+(src|SRC)(\"|'|=\"|='|=)(.*)/i","$4",$media[0]); // 이미지 파일 추출
		$editorImgCnt = 0;
		// 등록 이미지 갯수 만큼 루프
		foreach ($contentsCopy as $copyUrl) {
			$copyUrl = trim($copyUrl);
			foreach ($this->arrayOriEditorFilePath as $arrayOldEditorFilePath) {
				if (ereg($arrayOldEditorFilePath[0], $copyUrl) || ereg($arrayOldEditorFilePath[1], $copyUrl) || (substr($copyUrl, 0, 1) == '/' && substr($copyUrl, 0, 2) != '//')) {
					$oriCopyUrl = $copyUrl;
					$newCopyUrl = '';
					$copyUrl = str_replace($arrayOldEditorFilePath[0], '', $copyUrl);
					$copyUrl = str_replace($arrayOldEditorFilePath[1], '', $copyUrl);
					$copyUrl = preg_replace('/^' . str_replace('/', '\/', $arrayOldEditorFilePath[4]) . '/', '', $copyUrl);

					//echo $oldFilePath.'<br/>';
					$arrayFilePath = explode('/', $copyUrl);
					$filePath = $this->newEditorFilePath;
					
					$fileName = $arrayFilePath[count($arrayFilePath)-1];
					$oriFilePath = '';
					
					for ($i = 0; $i < count($arrayFilePath) - 1; $i++){
						$oriFilePath .= '/' . trim($arrayFilePath[$i]);
					}
					
					if (!$this->editorFileCopyList[$copyUrl]) {
						$newEditorFileName = fileRename($fileName, $addFileName . $editorImgCnt);
						$newCopyUrl = str_replace($arrayOldEditorFilePath[0], $changeFilePath, $oriCopyUrl);
						$newCopyUrl = str_replace($arrayOldEditorFilePath[1], $changeFilePath, $newCopyUrl);
						preg_match('/' . str_replace('/', '\/', $changeFilePath) . '/', $newCopyUrl, $testResult);
						if (empty($testResult)) {
							$newCopyUrl = preg_replace('/^\//', $changeFilePath, $newCopyUrl);
						}

						$newCopyUrl = str_replace($oriFilePath . '/', '/', $newCopyUrl);
						$newCopyUrl = str_replace($fileName, $newEditorFileName, $newCopyUrl);
						
						$this->editorFileCopyList[$copyUrl] = $newCopyUrl;
						if (!$this->fileCheck($newCopyUrl)) {
							if (!trimPostRequest('file_rename_yn')) {
								$changeUrl = $filePath . $newEditorFileName;
								if ($this->localCopy == 'Y') {
									$oldEditorPath = './oldSite' . $arrayOldEditorFilePath[4] . $copyUrl;
								}
								else {
									$oldEditorPath = $arrayOldEditorFilePath[0] . $copyUrl;
									$oldEditorPath = str_replace('+', '%20', str_replace('%3A', ':', str_replace('%2F', '/', urlencode($oldEditorPath))));
								}
								
								// 파일복사 실행
								$this->fileCopy($oldEditorPath, $changeUrl, $msgFl);
							}

							
							$editorImgCnt++;
						}
						
						$oriContents = str_replace($oriCopyUrl, $newCopyUrl, $oriContents);
					}
					else {
						$oriContents = str_replace($oriCopyUrl, $this->editorFileCopyList[$copyUrl], $oriContents);
					}
				}
			}
		}

		return $oriContents;
	}

	public function createThumbnail($oriImagePath, $thumbImagePath, $widthSize, $heightSize, $fileType, $msgPrintFl = false) {
		
		$resultFlag = false;
		if ($this->fileCheck($thumbImagePath)) {
			if ($msgPrintFl) {
				echo '<div style="color:blue;">썸네일 파일 존재 : ' . $oriImagePath . ' => ' . $thumbImagePath . '</div>';
			}
		}
		else {
			// 확장자에 따라 다른 내장함수를 통해 썸네일 생성
			if ($fileType == 'jpg' || $fileType == 'jpeg') {
				$oriImage		= imagecreatefromjpeg($oriImagePath);
				$thumbImage		= imagecreatetruecolor($widthSize, $heightSize);
				imagecopyresampled($thumbImage, $oriImage, 0, 0, 0, 0, $widthSize, $heightSize, imagesx($oriImage), imagesy($oriImage));
				if (imagejpeg($thumbImage, $thumbImagePath)) {
					$resultFlag = true;
				}
			}
			elseif ($fileType == 'gif') {
				$oriImage		= imagecreatefromgif($oriImagePath);
				$thumbImage		= imagecreatetruecolor($widthSize, $heightSize);
				imagecopyresampled($thumbImage, $oriImage, 0, 0, 0, 0, $widthSize, $heightSize, imagesx($oriImage), imagesy($oriImage));
				if (imagegif($thumbImage, $thumbImagePath)) {
					$resultFlag = true;
				}
			}
			elseif ($fileType == 'png') {
				$oriImage		= imagecreatefrompng($oriImagePath);
				$thumbImage		= imagecreatetruecolor($widthSize, $heightSize);
				imagecopyresampled($thumbImage, $oriImage, 0, 0, 0, 0, $widthSize, $heightSize, imagesx($oriImage), imagesy($oriImage));
				if (imagepng($thumbImage, $thumbImagePath)) {
					$resultFlag = true;
				}
			}
			elseif ($fileType == 'bmp') {
				$oriImage = imagecreatefromwbmp($oriImagePath);
				$thumbImage = imagecreatetruecolor($widthSize, $heightSize);
				imagecopyresampled($thumbImage, $oriImage, 0, 0, 0, 0, $widthSize, $heightSize, imagesx($oriImage), imagesy($oriImage));
				if (imagewbmp($thumbImage, $thumbImagePath)) {
					$resultFlag = true;
				}
			}
			
			if ($msgPrintFl) {
				if ($resultFlag) {
					if (!$this->bulkFileFl) {
						$this->localFileExistsList[$thumbImagePath] = true;
					}
					echo '<div style="color:blue;">썸네일 성공 : ' . $oriImagePath . ' => ' . $thumbImagePath . '</div>';
				}
				else {
					echo '<div style="color:red;">썸네일 실패 : ' . $oriImagePath . ' => ' . $thumbImagePath . '</div>';
				}
			}
		}
	}
}

?>