<?php
require_once 'm.php';
$m = new M();
$sql = "SELECT * FROM  `datasets` ORDER BY  mart, filter";
$res = $m->execSQL($sql);
$arr = array();
while($row = mysqli_fetch_array($res)) {
	//var_dump($row['mart'], $row['filter'], $row['dataset_title']);
	$obj = array();
	$obj['name'] = $row['dataset_title'];
	$obj['name_ja'] = '日本語の名前が入ります';
	//$obj['file'] = sprintf('http://54.248.104.85/undata/getJSON.php?d=%s&f=%s', $row['mart'], $row['filter']);
	$obj['file'] = sprintf('http://s3-ap-northeast-1.amazonaws.com/undata/%s/%s', $row['mart'], $row['filter']);
	$obj['meta_info'] = $row['meta_info'];
	$obj['update_info'] = $row['update_info'];
	array_push($arr, $obj);
}


header('Access-Control-Allow-Origin:*');
header("Content-Type: application/json; charset=utf-8");
echo json_encode($arr);
?>
