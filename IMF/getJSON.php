<?php
$code = preg_replace("/[^a-zA-Z0-9;:_\-.]/", '', $_REQUEST['code']);

if(!$code) echo "no code";

$mongo = new Mongo();
$db = $mongo->selectDB("undata");
$col = $db->selectCollection("imf");

header('Access-Control-Allow-Origin:*');
header("Content-Type: application/json; charset=utf-8");

$count = $db->execute('return db.imf.count();');
//var_dump($count);
$cursor = $col->find(array("code" => $code));
foreach($cursor as $index => $doc) {
	//var_dump($index);
	echo json_encode($doc);
	//die;
}
?>
