<?php
	require ('../lib/lib.func.php');
	set_include_path(get_include_path() . '\phpseclib');
	
	$encryptFtpInfo = explode('.', file_get_contents('../lib/ftp.conf.php'));

	$decryptStr = pack("H*", $encryptFtpInfo[0]);
	$arrayFtpInfo = unserialize(mcrypt_decrypt(MCRYPT_3DES, $encryptFtpInfo[1], $decryptStr, MCRYPT_MODE_ECB));
	
	foreach ($arrayFtpInfo as $key => $value) {
		${$key} = $value;
	}

	$result = true;
	$errCode = 0;

	$callback		= $_REQUEST["callback"];
	$setDomain		= $_REQUEST['setDomain'];
	
	if ($setDomain == 'defaultSource') {
		$installDir = $_REQUEST['beforeDomain'];
	}
	else {
		$installDir = $setDomain;
	}
	$insFileName	= iconv('UTF-8', 'EUC-KR', $_REQUEST['insFileName']);

	$installPath = '../module/' . $installDir . '/';
	$downloadPath = './2014/module/_source/' . $setDomain . '/';

	if (!is_dir($installPath)) {
		mkdir($installPath);
	}

	if (!is_dir($installPath . '/data/')) {
		mkdir($installPath . '/data/');
	}

	if (!is_dir($installPath . '/dbfile/')) {
		mkdir($installPath . '/dbfile/');
	}

	if(!@include('Net/SFTP.php')) {
		$result = false;
		$errCode = 1;
	}
	
	if ($result) {
		$sftp = new Net_SFTP($ftp_server, 22);
		if (!$sftp->login($ftp_user_name, $ftp_user_pass)) {
			$result = false;
			$errCode = 2;
		}
	}
	
	if ($result) {
		if (file_exists($installPath . $insFileName)) {
			unlink($installPath . $insFileName);
		}
		if (!$sftp->get($downloadPath . $insFileName, $installPath . $insFileName)) {
			$result = false;
			$errCode = 3;
		}
	}
	
	if ($result) {
		$zip = new ZipArchive;
		if ($zip->open($installPath . $insFileName) === TRUE) {
			$zip->extractTo($installPath);
			$zip->close();
		} else {
			$result = false;
			$errCode = 4;
		}
	}

	unlink($installPath . $insFileName);

	$jsonData = array();
	$jsonData['result']		= $result;
	$jsonData['errCode']	= $errCode;
	
	echo $callback . '(' . gd_json_encode($jsonData) . ')';
?>