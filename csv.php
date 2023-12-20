<?php
setlocale(LC_CTYPE, "ko_KR.eucKR");
?>
<!DOCTYPE>
<html>
	<head>
	<title> :: 타사이전 CSV Viewer :: </title>
		<link rel="stylesheet" href="./common/css/bootstrap.min.css"></link>
	<style type="text/css">
		body, table, tr, td, textarea {
			margin:0;
			padding:0;
		}
		.table td, .table th {
			padding:4px;
		}
		.numberBar{font-weight:bold;height:25px;background-color:#f0f0f0;}
		.leftNum{font-weight:bold;padding:0 10px 0 10px;background-color:#f0f0f0;text-align:center;}
		textarea {border:0px;overflow:auto;}
	</style>
</head>
<?php

$setDomain = $_GET['setDomain'];
$csvFilePath = 'module/' . $setDomain . '/dbfile/';

if (!is_dir($csvFilePath)) {
	echo '<div class="alert alert-danger" role="alert">"' . $csvFilePath . '" 경로가 존재하지 않습니다.</div>';
	exit;
}
$arrayFileList = array();
$dir	= opendir($csvFilePath);
while($fileRow = readdir($dir)) {
	preg_match('/[\.](csv|CSV)$/i', $fileRow, $result);
	if($result[0]) {
		$arrayFileList[] = $fileRow;
	}
}
?>

<body>
	<form>
		<?php 
			if (!empty($arrayFileList)) {
		?>
		<input type="hidden" name="setDomain" value="<?=$setDomain?>" />
		<div style="margin:5px 0 0 5px;padding:0;">
			<div class="input-group float-left" style="width:auto;">
				<div class="input-group-prepend">
					<label class="input-group-text" for="csvFileName"><?=$csvFilePath?></label>
				</div>
				<select class="form-control data_type" id="csvFileName" name="csvFileName">
					<?php
						foreach ($arrayFileList as $csvFileName) {
							$selected = '';
							if ($_GET['csvFileName'] == $csvFilePath . $csvFileName) $selected = 'selected';
							echo '<option value="' . $csvFilePath . $csvFileName . '" ' . $selected . '>' . $csvFileName . '</option>';
						}
					?>
				</select>
			</div>
			<div class="float-left">&nbsp;</div>
			<div class="input-group" style="width:300px;">
				<div class="input-group-prepend">
					<label class="input-group-text" style="background-color:#fff;" for="displayline">출력개수</label>
				</div>
				<input type="text" class="form-control" id="displayline" name="displayline" value="<?=($_GET['displayline']) ? $_GET['displayline'] : '100' ?>" />
				<div class="input-group-append">
					<button class="btn input-group-text" name="shopSave" type="submit">확인</button>
				</div>
			</div>
		</div>
		<?php
			}
			else {
		?>
		<div class="alert alert-danger" role="alert">
			"<?=$csvFilePath?>" 경로에 CSV 파일이 존재 하지 않습니다.
		</div>
		<?php
			}
		?>
	</form>

	<?
		if($_GET['csvFileName']){

			$filename=$_GET['csvFileName'];
			
			$displayline=$_GET['displayline'];
		
			if(!file_exists($filename)){
				echo $filename." 파일이 존재하지 않습니다.<br ><a href='#' onclick=\"history.go(-1)\">뒤로</a>";
			}
			else{
				if($displayline<0)	$displayline=0;

				$fp = fopen($filename, 'r' );

				$fields = fgetcsv( $fp, 135000, ',');

				$cols = count($fields);

				$cnt=0;

				echo "<table class='table' style='margin-top:5px;' cellpadding='0' cellspacing='0'>";

				do{

					//칸번호 라인 출력
					if($cnt==0 || (($cnt-1)%20==0) && $cnt>1){

						echo "<tr class='numberBar'>";

							echo "<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>";

							for($i=0;$i<$cols;$i++)	echo "<td align=center>".$i."</td>";

						echo "</tr>";

					}
					
					//데이터 라인 출력
					echo "<tr>";
						if ($cnt === 0) {

							echo "<td class='leftNum'></td>";
							for($i=0;$i<$cols;$i++){

								echo "<td align='center' style='background-color:#8997a5;color:#fff;font-weight:bold;'>";

									if($fields[$i]=='') echo "&nbsp;";
									else {
										
										$width=strlen($fields[$i])*8;

										if($width>150)$width=150;

										if($width<10)$width=20;

										echo '<div style="width:130px;height:50px;padding-top:14px;">' . $fields[$i] . '</div>';

									}

								echo "</td>";

							}
						}
						else {
							echo "<td class='leftNum'>" . $cnt . "</td>";
							for($i=0;$i<$cols;$i++){
								
								echo "<td>";

									if($fields[$i]=='') echo "&nbsp;";
									else {
										echo "<textarea style=\"width:130px;\">" . $fields[$i] . "</textarea>";

									}

								echo "</td>";

							}
						}

					echo "</tr>";
					
					//출력갯수에 따라 끊어주기
					if($displayline && $cnt>=$displayline)break;

					$cnt++;

				}while($fields = fgetcsv( $fp, 1350000, ',' ));

				echo "</table>";
				echo "<br ><br >$cnt Lines";
			}
		}
	?>
</body>
</html>