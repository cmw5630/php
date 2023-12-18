<?php
	include '../../lib/lib.func.php';

	$afterUrl = $_POST['afterUrl'];
	$callback = $_POST["callback"];

	$filePath = "../../module/{$afterUrl}/dbfile/";

	$arrayDataCount = array();
	$orderFileType = array(
		'order'			=> 'order.csv',
		'orderGoods'	=> 'orderGoods.csv',
		'Mileage'		=> 'Mileage.csv',
	);
	
	foreach ($orderFileType as $type => $fileName) {
		if (file_exists($filePath . $fileName)) {
			$orderFileType[$type] = number_format(getLines($filePath . $fileName));
		}
		else {
			$orderFileType[$type] = 'none';
		}
	}

	echo $callback . gd_json_encode($orderFileType);

	function getLines($file) {
		$f = fopen($file, 'rb');
		$lines = 0;

		while (!feof($f)) {
			$lines += substr_count(fread($f, 8192), "\n");
		}

		fclose($f);

		return $lines;
	}
	
?>