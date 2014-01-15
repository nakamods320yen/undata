<?php
/**
  * make url for get data 
  */
ini_set( 'display_errors', 1 );
error_reporting(E_ALL);


getDatasets();





function getDatasets() {
	$url = 'http://data.un.org/Handlers/ExplorerHandler.ashx?t=topics';
	$data = getJSON($url);
	$topics = $data["Nodes"];
	foreach($topics as $topic_index => $topic) {
		$childNodes = $topic["childNodes"];
		for($i=-1,$l=count($childNodes);++$i<$l;) {
			$child = $childNodes[$i];
			$mart = $child["martId"];
			$url_mart = "http://data.un.org/Handlers/ExplorerHandler.ashx?m=".$mart;
			$data_mart = getJSON($url_mart);
			getFilter($data_mart["Nodes"]);
			//if($i==1) die;
		}
	}
}
function getFilter($arr) {
	for($i=-1,$l=count($arr);++$i<$l;) {
		$line = $arr[$i];
		//echo "<b>".$line["label"]."</b>:";
		if($line["dataFilter"]) {
			$url = sprintf('Data.aspx?d=%s&f=%s', $line["martId"], $line["dataFilter"]);
			echo $url;
			echo "\n";
		}
		//echo "<br>\n";

		$childNodes = $line["childNodes"];
		//var_dump($childNodes);
		if(count($childNodes)>0) {
			//echo "<b>drilldown:".$line["label"]."</b>";
			getFilter($childNodes);
			continue;
		}
	}
}
function getJSON($url) {
	//var_dump($url);
	$str = file_get_contents($url);
	$json = preg_replace("/(<[^>]+>)/", "", str_replace("{Nodes:", '{"Nodes":', $str));
	//$json = stripslashes($json);

	$data = json_decode($json, true);
	switch(json_last_error())
	{
		case JSON_ERROR_DEPTH:
			$error =  ' - Maximum stack depth exceeded';
			break;
		case JSON_ERROR_CTRL_CHAR:
			$error = ' - Unexpected control character found';
			break;
		case JSON_ERROR_SYNTAX:
			$error = ' - Syntax error, malformed JSON';
			break;
		case JSON_ERROR_NONE:
		default:
			$error = '';
	}
	if (!empty($error))
		throw new Exception('JSON Error: '.$error);

	return $data;
}



//getDataURL();
function getDataURL($url='') {
	$url = "http://data.un.org/Data.aspx?d=EDATA&f=cmID%3aAL";
	$url_format = "http://data.un.org/Handlers/DownloadHandler.ashx?DataFilter=%s&DataMartId=%s&Format=csv&c=%s";

	$html = file_get_html($url);

	//get dataset title
	$h2 = $html->find('div.SeriesMeta > h2', 0);
	$h2->find('span', 0)->outertext = '';
	var_dump($h2->outertext);
	var_dump(preg_replace('/<[^>]+>/', '', $h2->outertext));

	$DataMart = $html->find('.DataMart', 0);
	$dataset_source = $DataMart->find('h1', 0)->innertext;
	$source_agency = $DataMart->find('h2 > span > a', 0);
	$source_agency_name = $source_agency->innertext;
	$source_agency_link = $source_agency->href;
	$MetaInfo = $DataMart->find('.MetaInfo', 0)->innertext;
	$Update = $DataMart->find('.Update', 0)->innertext;
	var_dump($dataset_source);
	var_dump($source_agency_name);
	var_dump($source_agency_link);
	var_dump($MetaInfo);
	var_dump($Update);

	//$("boxDataFilter").value
	$boxDataFilter = $html->getElementById('boxDataFilter', 0);
	$DataFilter = $boxDataFilter->value;

	$boxDataMartId = $html->getElementById('boxDataMartId', 0);
	$DataMartId = $boxDataMartId->value;



	$m = new M();
	$m->addDataset(array(
		'filter' => $DataFilter,
		'mart' => $DataMartId,
		'dataset_title' => $dataset_source,
		'meta_info' => $MetaInfo,
		'update_info' => $Update,
		'source_agency_name' => $source_agency_name,
		'source_agency_link' => $source_agency_link
	));



	//$("divView") GetColumnList
	$divCountryorAreaInner = $html->getElementById('divView');
	$columnList = $divCountryorAreaInner->find('input[type=checkbox]');
	$columns = array();
	foreach($columnList as $index => $column) {
		array_push($columns, $index);
	}
	$columns_str = implode(',', $columns);

	var_dump(sprintf($url_format, $DataFilter, $DataMartId, $columns_str));
	return sprintf($url_format, $DataFilter, $DataMartId, $columns_str);


	//get countries #divCountryorAreaInner
	$divCountryorAreaInner = $html->getElementById('divCountryorAreaInner');
	$countries = $divCountryorAreaInner->find('input[type=checkbox]');
	foreach($countries as $country) {
		var_dump($country->name);
	}

	//get years #divYearInner
	$divYearInner = $html->getElementById('divYearInner');
	$years = $divYearInner->find('input[type=checkbox]');
	foreach($years as $year) {
		var_dump($year->name);
	}
}

class M {
	var $conn;
	function __construct() {
		$this->link = mysqli_connect("localhost","root","password","undata") or die("Error " . mysqli_error($link));
		var_dump($this->link);
	}
	function execSQL($sql) {
		$this->link->query($sql);
	}
	function addDataset($arr){
		//INSERT INTO `undata`.`datasets` (`filter`, `mart`, `dataset_title`, `source_agency_name`, `meta_info`, `update_info`, `source_agency_link`, `insert_time`, `update_time`) VALUES ('filter', 'mart', 'dataset_title', 'source_agency_name', 'meta_info', 'update_info', 'source_agency_link', NOW(), NOW());
		$column_arr = array();
		$value_arr = array();
		var_dump($arr);
		foreach($arr as $column => $value){
			array_push($column_arr, '`'.$column.'`');
			array_push($value_arr, "'".$value."'");
		}
		$sql_format = 'replace into datasets (%s, `insert_time`, `update_time`) values (%s, NOW(), NOW());';
		$sql = sprintf($sql_format, implode(',', $column_arr), implode(',', $value_arr));
		var_dump($sql);
		$this->execSQL($sql);
	}
}






/*$data = file_get_contents($url);
$doc = new DomDocument;
// ID を参照する前に、ドキュメントを検証する必要があります
$doc->validateOnParse = true;
$doc->loadHTML($data);
$xmlString = $doc->saveXML();
$xmlObject = simplexml_load_string($xmlString);
var_dump($xmlObject);*/
// $domDocument->loadHTML($html);
// $xmlString = $domDocument->saveXML();

?>
<html>
<script>
var json = '<?php //echo preg_replace("/(<[^>]+>)/", "", str_replace("{Nodes:", '{"Nodes":', str_replace(array("\r\n","\r"), '', str_replace("'", "\'", getDatasets()))));?>';
console.dir(JSON.parse(json));
</script>
</html>


