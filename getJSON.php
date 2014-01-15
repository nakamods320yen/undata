<?php
$mart = preg_replace("/[^a-zA-Z0-9;:_\-.]/", '', $_REQUEST['d']);
$filter = preg_replace("/[^a-zA-Z0-9;:_\-.]/", '', $_REQUEST['f']);
$martfilter = $mart.$filter;

if(!$mart) echo "no mart";
if(!$filter) echo "no filter";

$mongo = new Mongo();
$db = $mongo->selectDB("undata");
$col = $db->selectCollection("test");

header('Access-Control-Allow-Origin:*');
/*header('Access-Control-Allow-Credentials: true');
header("Access-Control-Allow-Headers:Content-Type");
header("Access-Control-Allow-Methods:PUT,DELETE,POST,GET,OPTIONS");*/


//header('Content-Type:text/plain;charset=UTF-8');
header("Content-Type: application/json; charset=utf-8");

$count = $db->execute('return db.test.count();');
//var_dump($count);
$cursor = $col->find(array("martfilter" => $martfilter));
foreach($cursor as $index => $doc) {
	//var_dump($index);
	echo json_encode($doc);
	//die;
}
?>
