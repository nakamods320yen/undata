<?php
/**
 * bugs: cant use $dataset_arr
 * todo: get data for only 1 dataset
 */
ini_set( 'display_errors', 1 );
error_reporting(E_ALL);
//require_once 'm.php';

mb_internal_encoding("UTF-8");
$d = new Data();
$d->readCSV('WEOOct2013all.csv');

class Data {
	var $DataFilter;
	var $DataMartId;
	var $headers;
	var $code_year_index, $code_year_end, $last_code, $crnt_code, $first_newcode;
	var $data_index = 0;
	function readCSV($csvname){
		$start_time = microtime(1);
		if ($fp = fopen($csvname, 'r')){
			if (flock($fp, LOCK_SH)){
			//if ($data = fgetcsv($fp, 0, ",")) {
				$num = 0;
				$this->footnote_index = 0;
				while (!feof($fp)) {
					//$buffer = fgets($fp);
					$buffer = fgetcsv($fp);
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
		$this->addMongo();
	}
	function addDataRow($row_str, $num) {
		//remove 2 `"` left and right
		/*$str = substr($row_str, 1, -3);
		if(!$str) {
			$this->footnote_index = $num;
			var_dump($this->footnote_index);
			return;
		}*/
		
		//$arr = explode(',', $str);

		$this->first_newcode = 0;

		$arr = $row_str;
		if(count($arr) < 1) {
			$this->footnote_index = $num;
			var_dump($this->footnote_index);

			//insert last dataset

			return;
		}
		
		//	var_dump($arr);
		if($num===0) {
			$this->headers = $arr;
			$this->code_index = array_search('WEO Subject Code', $arr);
			$this->name_index = array_search('Subject Descriptor', $arr);
			$this->note_index = array_search('Subject Notes', $arr);
			$this->unit_index = array_search('Units', $arr);
			$this->data = array();

		} else {
			$this->crnt_code = $arr[$this->code_index];
			if($this->crnt_code != $this->last_code) { //first line of new code
				$this->first_newcode = 1;
				$this->data[] = array(
					'code' => $this->crnt_code,
					'name' => $arr[$this->name_index]." ".$arr[$this->unit_index],
					'note' => $arr[$this->note_index],
					"data" => array()
				);
				$this->data_index = count($this->data) - 1;

				/*if($this->last_code && $this->last_code != 'BCA') {
					$this->dumpJSON($this->data[0]);
					//var_dump($this->data);
					die(); //tmp
				}*/
			}
		}


		//footnotes
		//var_dump("num:".$num, "footnote_index:".$this->footnote_index);
		if($this->footnote_index > 0 && $num > $this->footnote_index) {
			if($num > $this->footnote_index+1) {}
			return;
		}

		for($i=-1,$l=count($arr);++$i<$l;) { //yoko
			if($num===0) {
				if(!$this->code_year_index && is_numeric($arr[$i])) {
					$this->code_year_index = $i;
				} else {
					$this->code_year_end = $i;
				}

				/*if($this->code_year_index && $i >= $this->code_year_index) {
					$this->data[$this->data_index]["data"][] = array(
						'year' => $arr[$i],
						'country_value' => array()
					);
				}*/
			} else {
				if($this->first_newcode) {
					/*$this->data[$this->data_index]["data"][] = array(
						'year' => $arr[$i],
						'country_value' => array()
					);*/
					if($i >= $this->code_year_index && $i < $this->code_year_end) {
						$this->data[$this->data_index]["data"][$i-$this->code_year_index]['year'] = $this->headers[$i];
					}
				}
				if($i==0) {
					$tmp = array();
				}
				if($i >= $this->code_year_index && $i < $this->code_year_end) {
					$tmp['value'] = $arr[$i] + 0;
					//var_dump($tmp);
					/*var_dump($this->data["data"]);
					var_dump($i-$this->code_year_index);
					var_dump($this->data->data[$i-$this->code_year_index]);*/
					$this->data[$this->data_index]["data"][$i-$this->code_year_index]['country_value'][] = $tmp;
					//var_dump($this->data);
				} elseif($i != $this->name_index && $i != $this->code_index && $i != $this->note_index) {
					$tmp[$this->headers[$i]] = mb_convert_encoding($arr[$i], "UTF-8");
				}



			}
		}
		$this->last_code = $this->crnt_code; //last of 1 tate
	}
	function addMongo() {
		$mongo = new Mongo();
		
		$db = $mongo->selectDB("undata");
		$col = $db->selectCollection("imf");


		for($i=0,$l=count($this->data);$i<$l;$i++) {
			$data = $this->data[$i];
			$col->remove(array("martfilter" => $data["code"]));

			try {
				$col->insert($data);
			} catch(Exception $e) {
				$col->insert(array(
				"martfilter" => $data["code"],
				"name" => $data["name"],
				"message" => $e->getMessage(),
				"data" => array()
				));
			}

		}
	}
	function dumpJSON($data) {
		header('Access-Control-Allow-Origin:*');
		header("Content-Type: application/json; charset=utf-8");
		echo json_encode($data);
	}
}













?>