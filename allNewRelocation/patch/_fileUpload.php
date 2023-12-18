<?php
	require ('../lib/lib.func.php');
	set_include_path(get_include_path() . '\phpseclib');
	
	$encryptFtpInfo = explode('.', file_get_contents('../lib/ftp.conf.php'));

	$decryptStr = pack("H*", $encryptFtpInfo[0]);
	$arrayFtpInfo = unserialize(mcrypt_decrypt(MCRYPT_3DES, $encryptFtpInfo[1], $decryptStr, MCRYPT_MODE_ECB));
	
	foreach ($arrayFtpInfo as $key => $value) {
		${$key} = $value;
	}
	
	$callback		= $_REQUEST["callback"];
	$setDomain		= $_REQUEST['setDomain'];

	$fileName = 'source_default_' . date('Ymd') . '_godomall5_v.0.1.zip';

	$result = true;
	$errCode = 0;

	$file_names = array();
	
	$soursePath = '../module/' . $setDomain . '/';
	if (is_dir($soursePath)) {
		$dir = opendir($soursePath);
		while($row = readdir($dir)){
			if(in_array(substr($row,-4),array(".php")))
			$file_names[]=$row;
		}

		$archive_file_name = './' . $fileName;

		makeZipSet($file_names, $archive_file_name, $soursePath);
	}
	else {
		$result = false;
		$errCode = 5;
	}
	
	if ($result) {
		if(!@include('Net/SFTP.php')) {
			$result = false;
			$errCode = 1;
		}
	}
	
	if ($result) {
		$sftp = new Net_SFTP($ftp_server, 22);
		if (!$sftp->login($ftp_user_name, $ftp_user_pass)) {
			$result = false;
			$errCode = 2;
		}
	}
	
	if ($result) {
		$uploadPath = './2014/module/_source/' . $setDomain . '/';

		if (!$sftp->chdir($uploadPath)) {
			if (!$sftp->mkdir($uploadPath)) {
				$result = false;
				$errCode = 3;
			}
			else {
				$sftp->chdir($uploadPath);
			}
		}
	}
	
	if ($result) {
		if ($sftp->put($archive_file_name, $archive_file_name, NET_SFTP_LOCAL_FILE)) {
			unlink($archive_file_name);
		}
		else {
			$result = false;
			$errCode = 4;
		}
	}

	$jsonData = array();
	$jsonData['result']		= $result;
	$jsonData['errCode']	= $errCode;
	
	if ($result) {
		$jsonData['fileName'] = $fileName;
	}

	echo $callback . '(' . gd_json_encode($jsonData) . ')';

	function makeZipSet($file_name, $archive_name, $file_path) {
		$zip = new ZipArchive(); /// PHP 5.2 이상부터 제공
		if ($zip->open($archive_name, ZIPARCHIVE::CREATE )!==TRUE) { /// 에러처리
			exit;
		} /// 파일 리스트를 순차적으로 zip 압축파일에 추가     
	
		foreach($file_name as $file) {        
			$zip->addFile($file_path . $file, $file);
		}
		$zip->close();
	}
?>