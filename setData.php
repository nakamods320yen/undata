<?php
/**
 * bugs: cant use $dataset_arr
 * todo: get data for only 1 dataset
 */
ini_set( 'display_errors', 1 );
error_reporting(E_ALL);
require_once 'simple_html_dom.php';
require_once 'm.php';

//no override
$dataset_arr = getDatasetFromDB();
//$dataset_arr = array();
var_dump($dataset_arr);




getDatasets(); //loop all datasets

//identify mart & filter
//addDataByDataset('http://data.un.org/Data.aspx?q=mobile&d=EDATA&f=cmID:EC;trID:1331');
//addDataByDataset('http://data.un.org/Data.aspx?d=SNA&f=group_code:203;sub_item_code:6;item_code:1');

//get dataset index json
function getDatasets() {
	$url = 'http://data.un.org/Handlers/ExplorerHandler.ashx?t=topics';
	$data = getJSON($url);
	$topics = $data["Nodes"];
	$m = new M();
	$m->addDatasetJSON(array(
		'topic' => 'topics',
		'mart' => '',
		'url' => $url,
		'json' => json_encode($data),
	));
	foreach($topics as $topic_index => $topic) {
		$childNodes = $topic["childNodes"];
		for($i=-1,$l=count($childNodes);++$i<$l;) {
			$child = $childNodes[$i];
			$mart = $child["martId"];
			$url_mart = "http://data.un.org/Handlers/ExplorerHandler.ashx?m=".$mart;
			$data_mart = getJSON($url_mart);
			$m->addDatasetJSON(array(
				'topic' => '',
				'mart' => $mart,
				'url' => $url_mart,
				'json' => json_encode($data_mart),
			));
			getFilter($data_mart["Nodes"]);
			//if($i==3) die;
		}
	}
}
//not completed yet
//series:XNATURP_FSGOV_FNCUR_FFD	UNESCO	UIS Data Centre
function getFilter($arr) {
	global $dataset_arr;
	for($i=-1,$l=count($arr);++$i<$l;) {
		$line = $arr[$i];
		//echo "<b>".$line["label"]."</b>:";
		if($line["dataFilter"]) {
			$url = sprintf('http://data.un.org/Data.aspx?d=%s&f=%s', $line["martId"], $line["dataFilter"]);
			echo $url;
			echo "\n";

			//no override
			if(!array_search(urldecode($line["martId"].$line["dataFilter"]), $dataset_arr)) {
				addDataByDataset($url);
			}
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





//unzip and insert from csv
function addDataByDataset($page_url) {

	$d = new Data();
	$url = $d->getDataURL($page_url);
	return;
	if(!$url) return;
	var_dump($url);
	//$url = "http://data.un.org/Handlers/DownloadHandler.ashx?DataFilter=series:C_N_DNTPI;ref_area:ABW,AFG,AGO,AIA,ALB,AND,ANT,ARE,ARG,ARM,ATG,AUS,AUT,AZE,BDI,BEL,BEN,BFA,BGD,BGR,BHR,BHS,BIH,BLR,BLZ,BMU,BOL,BRA,BRB,BRN,BTN,BWA,CAF,CAN,CHE,CHL,CHN,CIV,CMR,COD,COG,COK,COL,COM,CPV,CRI,CUB,CYM,CYP,CZE,DEU,DNK,DOM,DZA,ECU,EGY,ESP,EST,ETH,FIN,FJI,FRA,GAB,GBR,GEO,GHA,GIN,GLP,GMB,GNB,GNQ,GRC,GRD,GTM,GUF,GUM,GUY,HKG,HND,HRV,HTI,HUN,IDN,IND,IRL,IRN,IRQ,ISL,ISR,ITA,JAM,JOR,JPN,KAZ,KEN,KGZ,KHM,KNA,KOR,KWT,LAO,LBN,LBR,LBY,LCA,LIE,LKA,LSO,LTU,LUX,LVA,MAC,MAR,MDA,MDG,MDV,MEX,MHL,MKD,MLI,MLT,MMR,MNG,MOZ,MRT,MTQ,MUS,MWI,MYS,NAM,NCL,NER,NGA,NIC,NIU,NLD,NOR,NPL,NZL,OMN,PAK,PAN,PER,PHL,PNG,POL,PRI,PRK,PRT,PRY,PSE,PYF,QAT,REU,ROU,RUS,RWA,SAU,SEN,SGP,SLB,SLE,SLV,SOM,STP,SUR,SVK,SVN,SWE,SWZ,SYC,SYR,TCD,TGO,THA,TJK,TKM,TLS,TON,TTO,TUN,TUR,TZA,UGA,UKR,URY,USA,UZB,VCT,VEN,VGB,VNM,VUT,WSM,YEM,ZAF,ZMB,ZWE;time_period:1996,1997,1998,1999,2000,2001,2002,2003,2004,2005&DataMartId=UNESCO&Format=csv&c=0,1,2,3,4,5,6,7,8,9,10&s=ref_area_name:asc,time_period:desc";

	//save zip file
	$data = file_get_contents($url);
	file_put_contents('tmp.zip',$data);

	//open zip
	$zip = new ZipArchive();
	$res = $zip->open('tmp.zip');
	//var_dump($zip);
	$csvname = $zip->getNameIndex(0);
	if ($res === true) {
	    // 圧縮ファイル内の全てのファイルを指定した解凍先に展開する
	    if($zip->extractTo('./')===true) {
	    	// ZIPファイルをクローズ
	    	$zip->close();
	    } else {
	    	echo("extract err");
	    }
	    
	} else {
		echo('open err');
	}

	//open csv
	/*$csv = file_get_contents($csvname);
	var_dump($csv);*/
	$d->readCSV($csvname);
}



//?. _l1Code:98 ComTrade
//2. cmID:RF;trID:122
//1. UNESCOseries:XGOVEXP_FNCUR_FFD
function getDatasetFromDB() {
	$m = new M();
	$sql = "SELECT filter, mart FROM  `datasets` WHERE 1 "
		." and update_time > '2013-10-29 05:20:00' "
		." ORDER BY  `update_time` DESC ";
	$res = $m->execSQL($sql);
	$dataset_arr = array();
	$num = 0;
	while($row = mysqli_fetch_array($res)){
		if($num!==0)
			array_push($dataset_arr, $row["mart"].$row["filter"]);
		$num++;
	}
	return $dataset_arr;
}





$mongo = new Mongo();
var_dump($mongo);
$db = $mongo->selectDB("undata");
//$col = $db->createCollection("test");
//$col->insert(array("test"=>"test"));

class Data {
	var $DataFilter;
	var $DataMartId;
	var $headers;
	function readCSV($csvname){
		$start_time = microtime(1);
		if ($fp = fopen($csvname, 'r')){
			if (flock($fp, LOCK_SH)){
			//if ($data = fgetcsv($fp, 0, ",")) {
				$num = 0;
				$this->footnote_index = 0;
				$this->year_index = 999;
				while (!feof($fp)) {
					$buffer = fgets($fp);
					//print($buffer);
					$this->addDataRow($buffer, $num);
					$num++;
				}

				flock($fp, LOCK_UN);
			}else{
				print('ファイルロックに失敗しました');
			}
		}
		fclose($fp);
		echo 'csv読み込み完了：'.(microtime(1)-$start_time).'(s)';
	}
	function addHeaders($headers_str) {

	}
	function addDataRow($row_str, $num) {
		//remove 2 `"` left and right
		$str = substr($row_str, 1, -3);
		//var_dump($row_str);
		//var_dump($str);
		if(!$str) {
			$this->footnote_index = $num;
			var_dump($this->footnote_index);
			return;
		}
		$arr = explode('","', $str);
		//	var_dump($arr);
		if($num===0) {
			$this->headers = $arr;
			$this->year_index = array_search('Year', $arr);
			if($this->year_index===false) $this->year_index = array_search('Time Period', $arr);
			//elseif($this->year_index===false) $this->year_index = array_search('Period', $arr); //http://data.un.org/Data.aspx?d=CLINO&f=ElementCode:BT
		}

		$m = new M();

		//footnotes
		//var_dump("num:".$num, "footnote_index:".$this->footnote_index);
		if($this->footnote_index > 0 && $num > $this->footnote_index) {
			if($num > $this->footnote_index+1) $m->addFootnote(array(
				'filter' => $this->DataFilter,
				'mart' => $this->DataMartId,
				'fnSeqID' => $arr[0],
				'Footnote' => $arr[1],
			));
			return;
		}

		for($i=-1,$l=count($arr);++$i<$l;) { //yoko
			if($num===0) {
				$m->addHeader(array(
					'filter' => $this->DataFilter,
					'mart' => $this->DataMartId,
					'field_name' => $arr[$i],
					'header_order' => $i
				));
			} else {
				if($i==$this->year_index) continue;
				$m->addValue(array(
					'filter' => $this->DataFilter,
					'mart' => $this->DataMartId,
					'year' => $arr[$this->year_index],
					'field_name' => $this->headers[$i],
					'row_order' => $num,
					'column_order' => $i,
					'value' => $arr[$i]
				));

			}
		}
	}


	//todo: http://data.un.org/Data.aspx?d=EDATA&f=cmID:ST;trID:0927
	function getDataURL($url='http://data.un.org/Data.aspx?d=EDATA&f=cmID%3aAL') {
		$start_time = microtime(1);
		$url_format = "http://data.un.org/Handlers/DownloadHandler.ashx?DataFilter=%s&DataMartId=%s&Format=csv&c=%s";

		$html = @file_get_html($url);
		if(strlen($html) < 1) return false;

		//get dataset title
		//filter exists
		$dataset_title_arr = array();
		$divCurrentArr = $html->find('#divCurrent > a');
		foreach($divCurrentArr as $line) {
			array_push($dataset_title_arr, str_replace('&nbsp;', '', $line->plaintext));
		}
		$dataset_title = implode(' - ', $dataset_title_arr);

		//no filter
		if(!$dataset_title) {
			$h2 = $html->find('div.SeriesMeta > h2', 0);
			$h2->find('span', 0)->outertext = '';
			//var_dump($h2->outertext);
			//var_dump(preg_replace('/<[^>]+>/', '', $h2->outertext));

			$dataset_title = preg_replace('/<[^>]+>/', '', preg_replace('/<span>.*$/', '', $h2->outertext));
		}
		

		$DataMart = $html->find('.DataMart', 0);
		$dataset_source = $DataMart->find('h1', 0)->innertext;
		$source_agency = $DataMart->find('h2 > span > a', 0);
		$source_agency_name = $source_agency->innertext;
		$source_agency_link = $source_agency->href;
		$MetaInfo = $DataMart->find('.MetaInfo', 0)->innertext;
		$Update = $DataMart->find('.Update', 0)->innertext;
		/*var_dump($dataset_source);
		var_dump($source_agency_name);
		var_dump($source_agency_link);
		var_dump($MetaInfo);
		var_dump($Update);*/

		//$("boxDataFilter").value
		$boxDataFilter = $html->getElementById('boxDataFilter', 0);
		$DataFilter = $boxDataFilter->value;

		$boxDataMartId = $html->getElementById('boxDataMartId', 0);
		$DataMartId = $boxDataMartId->value;

		$this->DataFilter = $DataFilter;
		$this->DataMartId = $DataMartId;


		$m = new M();
		$m->addDataset(array(
			'filter' => $DataFilter,
			'mart' => $DataMartId,
			//'dataset_title' => $dataset_source,
			'dataset_title' => $dataset_title,
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

		echo 'csv URL作成完了：'.(microtime(1)-$start_time).'(s)';
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
}










?>
