<?php
require ('../lib/lib.func.php');

$callback		= $_REQUEST["callback"];
$setDomain		= $_REQUEST["setDomain"];
$selectVersion	= iconv('UTF-8', 'EUC-KR', $_REQUEST["selectVersion"]);

$installPath = '../module/' . $setDomain;

if (!is_dir($installPath)) {
	mkdir($installPath);
}

if (!is_dir($installPath . '/data/')) {
	mkdir($installPath . '/data/');
}

if (!is_dir($installPath . '/dbfile/')) {
	mkdir($installPath . '/dbfile/');
}

$jsonData = array();

$zip = new ZipArchive;
if ($zip->open('../patch/source/' . $selectVersion) === TRUE) {
	$zip->extractTo($installPath);
	$zip->close();
	$jsonData['result'] = true;
} else {
	$jsonData['result'] = false;
}

echo $callback . '(' . gd_json_encode($jsonData) . ')';
?>