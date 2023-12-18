<?php
	$callback = $_GET['callback'];

	$mode = $_POST['mode'];
	if ($mode == 'load') {
		echo $callback . '(' . file_get_contents('./solution.json') . ')';
	}
	else {
		include '../../lib/lib.func.php';
		$code = $_POST['code'];
		$name = $_POST['name'];

		$arraySolutionInfo = gd_json_decode(file_get_contents('./solution.json'));
		$arraySolutionInfo[$code] = iconv('UTF-8', 'CP949', $name);
		$arraySolutionInfo = gd_json_encode($arraySolutionInfo);
		
		$file_write = fopen('./solution.json',"w");
		
		if($file_write){
			$result = true;
			fwrite($file_write,$arraySolutionInfo);
			fclose($file_write);
			//chmod('./solution.json', 0707);
		} else {
			$result = false;
		}
		echo $callback . '({"result":"' . $result . '"})';
	}
?>