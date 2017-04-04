<?php
namespace Rnr;

class StopWatch {

	// start time
	private $start = null;

	// constructor
	public function __construct() {
		$this->start = microtime(true);
	}

	// get delta time
	public function __toString() {
		return (string)round((microtime(true) - $this->start)*1000, 3);
	}

}

class Log {

	public static function Write($str) {
		if(is_array($str) || is_object($str)) $str = print_r($str, true);
		$fh = fopen('application.log', 'a');
		fputs($fh, date('Y-m-d H:i:s', time()).(AdvLog ? ' / T+'.(string)$GLOBALS['GlobalTime'] : '').'ms '.$str."\n");
		fclose($fh);
	}
}

class Conv {

	public static function IntValue($str) {
		preg_match('/\d+/', $str, $match);
		return $match[0];
	}

	public static function DecimalValue($str) {
		preg_match('/(\d|\.|,)+/', $str, $match);
		if($match[0]) {
			$out = str_replace(',', '.', $match[0]);
			return $out;
		} else return false;
	}


	public static function ArrayToObject($in) {
		if(is_array($in)) {
			$tmp = new \stdClass();
			foreach($in as $name => $value) $tmp->$name = $value;
			return $tmp;
		} else return false;
	}

	public static function ObjectToArray($in) {
		if(is_object($in)) {
			foreach($in as $name => $value) $tmp[$name] = $value;
			return $tmp;
		} else return false;
	}


	public static function Translate($obj, $table) {
		if(!is_object($obj) || !is_array($table)) return false;
		$props = get_object_vars($obj);
		foreach($props as $prop => $value) {
			if($table[$prop]) $obj->$table[$prop] = $value;
			unset($obj->$prop);
		}
	return $obj;
	}

}

class Validator {

	private $fields = [];
	public $data = [];
	public $errors = [];

	public function __construct($data) {
		$this->data = $data;
	}

	public function Add($fieldName, $regexp) {
		$this->fields[] = [$fieldName, $regexp];
		return $this;
	}

	public function Validate() {
		$this->errors = [];
		foreach($this->fields as $item) if(!preg_match("/{$item[1]}/", $this->data[$item[0]])) $this->errors[] = $item[0];
		return (count($this->errors) == 0);
	}

	public function GetInvalidFields() {
		return $this->errors;
	}
}
