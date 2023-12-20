<?php
	setlocale(LC_CTYPE, "ko_KR.eucKR");
	ini_set('memory_limit', -1);
	ini_set('max_execution_time', 0);
?>
<!DOCTYPE>
<html>
	<head>
	<title> csv 파일 정리기 </title>
		<meta name="generator" content="editplus">
		<meta name="author" content="">
		<meta name="keywords" content="">
		<meta name="description" content="">
		<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js" type="text/javascript"></script>
		<style type="text/css">
		body, div, input, select {
			font-family:'dotum', '돋움';
			font-size:11px;
			margin:0px;
			padding:0px;
		}
		</style>
	</head>

	<body>
		<?php
			if (!$_POST['fileName']) {
		?>
		<form name="csvForm" method="post" action="<?=$_SERVER['PHP_SELF']?>">
		
		<div style="width:300px;height:300px;border:solid 2px #3366ff;margin:0 auto;padding: 10px 10px 0 10px;line-height:30px;">
		<h3 style="text-align:center;border-bottom:solid 2px #3366ff;">깨진 CSV 정리 프로그램</h3>
			파일명 : <input type="text" name="fileName" value="" style="width:200px"/><br/>
			제일 앞 문자 형태 : 
			<select name="pregType">
				<option value="0">숫자,</option>
				<option value="1">"숫자",</option>
				<option value="2">숫자-숫자,</option>
				<option value="3">"숫자-숫자",</option>
				<option value="4">"문자",</option>
			</select>
			<br />
			<div style="text-align:right;"><input type="submit" name="formSubmit" value="csv 문서 치환 시작" style="padding:3px 3px;font-weight:bold;font-size:13px" /></div>
		</div>
		</form>
		<?php
			}
			else {
				$arrayPregText = array(
					'^[0-9]{1,}+[,]',
					'^["][0-9]{1,}+["][,]',
					'^[0-9]{1,}+[-]+[0-9]{1,}+[,]',	
					'^["][0-9]{1,}+[-]+[0-9]{1,}+["][,]',
					'^["][\xa1-\xfe[:alnum:][:space:]\!\@\#\$\%\^\&\*\(\)\-\=\_\+\:\'\,\.]{1,}["][,]',
				);

				$fileName = $_POST['fileName'];
				$pregType = $_POST['pregType'];

				$f = fopen($fileName, 'r');
				$fileText = '';
				while ($lineText = fgets($f, 500000)) {
					preg_match_all('/' . $arrayPregText[$pregType] . '/i', $lineText, $result);
					if (!empty($result)) {
						$fileText .= preg_replace('/' . $arrayPregText[$pregType] . '/i', '<chr>' . $result[0][0], $lineText);
					}
				}
				fclose($f);
				$fileText = str_replace('<br /><chr>', chr(13) . chr(10), str_replace(chr(13), '<br />', str_replace(chr(10), '<br />', str_replace(chr(13) . chr(10), '<br />', $fileText))));
				
				$arrayNewFileExt = explode('.', $fileName);

				$newFileName = '';
				for ($i = 0; $i < count($arrayNewFileExt) - 1; $i++) {
					$newFileName .= $arrayNewFileExt[$i] . '.';
				}
				$newFileName .= 'result.';
				$newFileName .= $arrayNewFileExt[count($arrayNewFileExt) - 1];
				$fpWriteCSV = fopen($newFileName, 'w');
				fwrite($fpWriteCSV, $fileText);
				fclose($fpWriteCSV);

				?>
				<ul>
					<li style="color:red"><h2>주의 사항 : 치환 된 문서 맨 뒤 "&lt;br&gt;" 태그가 삽입 되어 있을 수 있습니다. 해당 문서 확인 후 br 태그 삭제 부탁드립니다. </h2></li>
				</ul>
				<script type="text/javascript">alert("치환 완료 - 파일명 : <?=$newFileName?>")</script>
				<h1>치환 완료 - 파일명 : <?=$newFileName?>
				<input type="button" name="main" value="페이지 돌아가기" onclick="location.href='csvFileArrange.php'" style="cursor:pointer;padding:3px 3px;font-weight:bold;font-size:13px"/>
				<?php
			}
			/**
			* Date = 개발 작업일(2016.04.15)
			* ETC = CSV 문서 정리기
			* Developer = 한영민
			*/
			?>
	</body>
</html>
