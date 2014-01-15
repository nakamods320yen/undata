<?php
class M {
	var $link;
	function __construct() {
		$this->link = mysqli_connect("localhost","root","P@ssw0rd","undata") or die("Error " . mysqli_error($this->link));
		//var_dump($this->link);
	}
	function execSQL($sql) {
		$res = $this->link->query($sql);
		return $res;
	}
	function addDatasetJSON($arr){
		$this->_addData($arr, 'datasetJSON');
	}
	function addDataset($arr){
		//INSERT INTO `undata`.`datasets` (`filter`, `mart`, `dataset_title`, `source_agency_name`, `meta_info`, `update_info`, `source_agency_link`, `insert_time`, `update_time`) VALUES ('filter', 'mart', 'dataset_title', 'source_agency_name', 'meta_info', 'update_info', 'source_agency_link', NOW(), NOW());
		$this->_addData($arr, 'datasets');
	}
	function addHeader($arr){
		$this->_addData($arr, 'dataheaders');
	}
	function addValue($arr){
		$this->_addData($arr, 'datavalues');
	}
	function addFootnote($arr){
		$this->_addData($arr, 'footnotes');
	}
	function _addData($arr, $table_name){
		$column_arr = array();
		$value_arr = array();
		foreach($arr as $column => $value){
			array_push($column_arr, '`'.$column.'`');
			array_push($value_arr, "'".$this->link->real_escape_string($value)."'");
		}
		$sql_format = 'replace into %s (%s, `insert_time`, `update_time`) values (%s, NOW(), NOW());';
		$sql = sprintf($sql_format, $table_name, implode(',', $column_arr), implode(',', $value_arr));
		//var_dump($sql);
		$this->execSQL($sql);
	}
}
?>
