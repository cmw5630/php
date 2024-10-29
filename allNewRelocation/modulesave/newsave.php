<?
	include '../_inc/lib.func.php';

	$callback = $_REQUEST["callback"];
	$file_dir = $_REQUEST["file_dir"];
	$file_name = $_REQUEST["file_name"];

	switch ($_REQUEST['mode']){
		case "load" : 
			$filePath = './_save/' . $file_dir . "/" . $_REQUEST['save_mode'] . "/" . $file_name;

			$file_read = fopen($filePath, 'r+');
			$json_data = fread($file_read,filesize($filePath));
			$json_data = str_replace(chr(13),'\n',$json_data);
			$json_data = str_replace(chr(10),'\n',$json_data);
			if($json_data){
				$jsons = '"result":"true","json_data":'.$json_data;
			} else {
				$jsons = '"result":"false"';
			}
			echo $callback.'({'.$jsons.'})';
			break;
		case "read_list" : 
			$dir=opendir('./_save/' . $file_dir . "/" . $_REQUEST['save_mode']);
			while($row = readdir($dir)){
				if(in_array(substr($row,-4),array(".crs")))
				$file[]=$row;
			}
			sort($file);
			closedir($dir);
			$i=0;
			if(count($file)>0){
				foreach($file as $value){
					if($i==0){
						$jsons = '"result":"true","savefile0":"'.$value.'"';
					} else {
						$jsons .= ',"savefile'.$i.'":"'.$value.'"';
					}
					$i++;
				}
			}  else {
				$jsons = '"result":"false"';
			}
			echo $callback.'({'.$jsons.'})';
			break;
		default : 
			$defaultFilePath = './_save/' . $file_dir;
			if(!is_dir($defaultFilePath)){
				mkdir($defaultFilePath);
				chmod($defaultFilePath,0707);
			}
			$addFilePath = $defaultFilePath . "/" . $_REQUEST['save_mode'];
			if(!is_dir($addFilePath)){
				mkdir($addFilePath);
				chmod($addFilePath, 0707);
			}

			$json_data = $_REQUEST['json_data'];
			$result = true;

			$json_data = gd_json_decode(str_replace('\"','"',$json_data));

			if(!$file_name){
				$file_name = date('Ymd.H.i.s').".".$_REQUEST['save_mode'].".crs";
			} else {
				$file_name = $file_name.".crs";
			}
			$filePath = $addFilePath . "/" . $file_name;
			
			$json_data = gd_json_encode($json_data);
			
			$file_write = fopen($filePath, "w");

			if($file_write){
				$result = true;
				fwrite($file_write,$json_data);
				fclose($file_write);
				chmod($filePath, 0707);
			} else {
				$result = false;
			}

			$setData = '{"result":' . $result . ',"saveFileName":"' . $file_name . '","fileDir":"' . $file_dir . '"}';

			echo '<script type="text/javascript">window.parent.postMessage(' . $setData . ', "*");</script>';
			break;
		}
?>
