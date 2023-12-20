<?php
	ini_set('memory_limit', -1);
	ini_set('max_execution_time', 0);

	include '../../lib/lib.func.php';

	$arrangeCount = 0;
	
	$arrayBeforeFileName	= trimPostRequest('beforeFileName');
	$targetDomain			= trimPostRequest('targetDomain');
	$pregType = trimPostRequest('pregType');

	$arrayPregText = array(
		'^[0-9]{1,}+[,]',
		'^["][0-9]{1,}+["][,]',
		'^[0-9]{1,}+[-]+[0-9]{1,}+[,]',	
		'^["][0-9]{1,}+[-]+[0-9]{1,}+["][,]',
		'^["][\xa1-\xfe[:alnum:][:space:]\!\@\#\$\%\^\&\*\(\)\-\=\_\+\:\'\,\.]{1,}["][,]',
	);

	$oldDataBasePath	= '../../excelData/';
	$newDataBasePath	= '../../module/' . $targetDomain;
	makeDir($newDataBasePath);
	$newDataBasePath .= '/dbfile/';
	makeDir($newDataBasePath);
	$logPath			= $oldDataBasePath . 'log/csv/';
	makeDir($logPath);

	$arrayLogText = array();
	$logFileName = date('Ymd') . '.log';
	if ((int)$arrangeCount === 0) {
		$arrayLogText[] = "#############################################";
		$arrayLogText[] = "[log start time] : " . date('Y-m-d H:i:s');
		$arrayLogText[] = "[target domain] : " . $targetDomain;
	}

	$f = fopen($oldDataBasePath . $arrayBeforeFileName[$arrangeCount], 'r');
	$fileText = '';
	while ($lineText = fgets($f, 500000)) {
		preg_match_all('/' . $arrayPregText[$pregType] . '/i', $lineText, $result);
		if (!empty($result)) {
			$fileText .= preg_replace('/' . $arrayPregText[$pregType] . '/i', '<chr>' . $result[0][0], $lineText);
		}
	}
	fclose($f);
	$fileText = str_replace('<br /><chr>', chr(13) . chr(10), str_replace(chr(13), '<br />', str_replace(chr(10), '<br />', str_replace(chr(13) . chr(10), '<br />', $fileText))));

	$fpWriteCSV = fopen($newDataBasePath . $arrayBeforeFileName[$arrangeCount], 'w');
	fwrite($fpWriteCSV, $fileText);
	fclose($fpWriteCSV);
	
	$arrayLogText[] = "[log end time] : " . date('Y-m-d H:i:s');
	$arrayLogText[] = "#############################################" . chr(13);
	logFileSetting($logPath . $logFileName, $arrayLogText);
	
	?>
	<script type="text/javascript">
		parent.setProgress("<?=$arrangeCount?>", "complate");
	</script>
	<?php
	/**
	* Date = 개발 작업일(2016.04.15)
	* ETC = CSV 문서 정리기
	* Developer = 한영민
	*/
?>
