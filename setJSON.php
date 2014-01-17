<?php
/*SELECT * FROM `datavalues`
WHERE 1
and mart = "CLINO"
and filter = "ElementCode:95"
order by year, row_order, column_order*/

require '../test/s3.php';
require_once 'm.php';

$c = new setJSON();

//$c->setJSONtoMongo('ITU', 'ind1Code:I911', 'Mobile-cellular telephone subscriptions per 100 inhabitants');
$c->getDatasetList();

class setJSON {
	var $m;
	function __construct() {
		$this->m = new M();
	}
	function getDatasetList() {
		$sql = "SELECT * FROM  `datasets` WHERE 1 "
			." and json_time <  '2014-01-15 10:00:00' " // not include ComTrade/_l1Code:20
			." ORDER BY  `datasets`.`update_time` ASC ";
		$res = $this->m->execSQL($sql);
		while($row = mysqli_fetch_array($res)) {
			var_dump($row['mart'], $row['filter'], $row['dataset_title']);
			$this->setJSONtoMongo($row['mart'], $row['filter'], $row['dataset_title']);
		}
	}
	function setJSONtoMongo($mart, $filter, $dataset_title) {
		$sql = 'SELECT * FROM `datavalues`'
			.' WHERE 1'
			.' and mart = "'.$mart.'"'
			.' and filter = "'.$filter.'"'
			.' order by year, row_order, column_order';
		//var_dump($sql);
		$res = $this->m->execSQL($sql);
		
		$arr = array();
		$last_row_order = -1;
		$last_year = -1;
		$num = -1;

		/** !!!!!check this later!!!!!! */
		if(!$res) return;

		while($row = mysqli_fetch_array($res)){
			if($last_year != $row["year"]) {
				$num = 0;
				$last_row_order = $row["row_order"];
			}
			$value = $row["value"];
			if(is_numeric($value)) $value = $value+0;
			$arr[$row["year"]][$num][$row["field_name"]] = $value;

			if($last_row_order != $row["row_order"]) {
				$num++;
			}
			$last_row_order = $row["row_order"];
			$last_year = $row["year"];
		}

		$arr_json = array();
		foreach($arr as $year => $obj) {
			array_push($arr_json, array(
				"year" => $year,
				"country_value" => $obj
			));
		}

		//$martfilter = "UNESCOseries:XGDP_FSGOV";
		$martfilter = $mart.$filter;
		$json_object = array(
			"martfilter" => $martfilter,
			"name" => $dataset_title,
			"data" => $arr_json
		);

		upload2s3gz($mart."/".$filter, json_encode($json_object));
		//for mongo
		/*$mongo = new Mongo();
		
		$db = $mongo->selectDB("undata");
		$col = $db->selectCollection("test");

		$col->remove(array("martfilter" => $martfilter));

		try {
			$col->insert($json_object);
		} catch(Exception $e) {
			$col->insert(array(
			"martfilter" => $martfilter,
			"name" => $dataset_title,
			"message" => $e->getMessage(),
			"data" => array()
			));
		}*/

		$this->updateJSONTime($mart, $filter);


	}
	function updateJSONTime($mart, $filter) {
		$sql = sprintf("update datasets set json_time = now() where mart = '%s' and filter = '%s'"
				, $mart, $filter);
		$this->m->execSQL($sql);
	}
}


























?>
