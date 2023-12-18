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
	
	$fileName = 'allNewRelocation.zip';
	
	$installPath = '../' . $fileName;
	$downloadPathFile = './2014/module/_version/relocation/' . $fileName;

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
		if (file_exists($downloadPathFile)) {
			unlink($downloadPathFile);
		}
		if (!$sftp->get($downloadPathFile, $installPath)) {
			$result = false;
			$errCode = 3;
		}
	}
	
	if ($result) {
		$zip = new ZipArchive;
		if ($zip->open($installPath) === TRUE) {
			$zip->extractTo('../');
			$zip->close();
		} else {
			$result = false;
			$errCode = 4;
		}
	}
	
	if ($result) {
		$versionInfo = explode(chr(13), file_get_contents('../version.info'));
		$versionInfo[0] = '버전정보 : v.' . $versionInfo[0];
	}

	unlink($installPath);

	$jsonData = array();
	$jsonData['result']		= $result;
	$jsonData['errCode']	= $errCode;
	if ($result) {
		$jsonData['versionInfo']	= implode('<br />', $versionInfo);
	}
	
	echo $callback . '(' . gd_json_encode($jsonData) . ')';
?>