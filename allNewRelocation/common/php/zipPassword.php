<?php
	include '../../lib/lib.func.php';

	$afterUrl			= $_POST['afterUrl'];
	$setPassword		= $_POST['setPassword'];
	$zipFlag			= $_POST['zipFlag'];
	//$callback			= $_REQUEST['callback'];
	$selectedPath = dirname(__FILE__);

	$secretKey = "relocation487!**!@#782%1";
	$targetPath = $selectedPath . "/../../module/{$afterUrl}/dbfile/";

	$arrayReturn = array();
	$arrayReturn['result'] = false;
	
	if ($zipFlag == 'y') {
		if (file_exists($targetPath . 'dbfile.zip')) {
			//unlink($targetPath . 'dbfile.zip');
		}

		$encryptText = '';
		### ��?? ####
		$en_str = mcrypt_encrypt(MCRYPT_3DES, $secretKey, $setPassword, MCRYPT_MODE_ECB);
		$encryptText = bin2hex($en_str);
		$encryptText = base64_encode($encryptText);

		if (is_dir($targetPath)) {
			$dir = opendir($targetPath);
			while($row = readdir($dir)){
				if(in_array(substr($row,-4),array(".csv")))
				$file_names[]= $targetPath . $row;

			}

			if (count($file_names)) {
				//echo $setPassword . '<br/>';
				if (makeZipSet($file_names, $targetPath . 'dbfile.zip', $setPassword, $targetPath)) {
					$fp = fopen($targetPath . $encryptText, 'w');
					fclose($fp);
					$arrayReturn['result'] = true;
				}
			}
		}
	}
	else {
		//echo $setPassword . '<br/>';
		if (is_file($targetPath . 'dbfile.zip')) {
			$de_str			= base64_decode($setPassword);
			$de_str			= pack("H*", trim($de_str));
			$setPassword	= mcrypt_decrypt(MCRYPT_3DES, $secretKey, $de_str, MCRYPT_MODE_ECB);
			
			$zip = new ZipArchive();
			$zip_status = $zip->open($targetPath . 'dbfile.zip');

			if ($zip_status === true)
			{
				if ($zip->setPassword($setPassword))
				{
					if ($zip->extractTo($targetPath)) {
						$arrayReturn['result'] = true;
						//echo "Extraction failed (wrong password?)";
					}
				}

				$zip->close();
			}
		}
	}

	echo gd_json_encode($arrayReturn);

	function makeZipSet ($file_names, $archive_name, $password, $file_path) {
		//echo '"C:\Program Files\Bandizip/7z/7z" a -p' . $password . ' ' . $archive_name . ' ' . implode(' ', $file_names);
		if (exec('"7z" a -p' . $password . ' ' . $archive_name . ' ' . implode(' ', $file_names))) {
			foreach ($file_names as $file_name) {
				unlink($file_name);
			}

			return true;
		}
		else return false;
	}
?> 
