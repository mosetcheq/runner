<?php
namespace Rnr;

define('OUTPUT_TEMPLATE', 1);
define('OUTPUT_ERRORDOCUMENT', 2);
define('OUTPUT_REDIRECT', 4);
define('OUTPUT_JSON', 8);
define('OUTPUT_PREVIOUS', 32);
define('OUTPUT_PLAIN', 64);
define('OUTPUT_FILE', 128);

class OutputType {
	public $type;
	public $template;
	public $data1;
	public $data2;

	public function __construct($type, $template, $data1 = null, $data2 = null) {
		$this->type = $type;
		$this->template = $template;
		$this->data1 = $data1;
		$this->data2 = $data2;
	}
}

class Output {

	public static $HttpCodes = array(
		100 => '1.0 100 Continue',
                101 => '1.0 101 Switching protocol',
                200 => '1.0 200 OK',
                201 => '1.0 201 Created',
                202 => '1.0 202 Accepted',
                203 => '1.1 203 Non-authoritative information',
                204 => '1.0 204 No content',
                205 => '1.0 205 Reset Content',
                206 => '1.0 206 Partial Content',
                300 => '1.0 300 Multiple Choices',
                301 => '1.0 301 Moved Permanently',
                302 => '1.0 302 Found',
                303 => '1.1 303 See Other',
                304 => '1.0 304 Not Modified',
                305 => '1.1 305 Use Proxy',
                307 => '1.1 307 Temporary Redicect',
		400 => '1.0 400 Bad Request',
                401 => '1.0 401 Unauthorised',
                403 => '1.0 403 Forbidden',
                404 => '1.0 404 Not Found',
                405 => '1.0 405 Method Not Allowed',
                406 => '1.0 406 Not Acceptable',
                407 => '1.0 407 Proxy Authentication Required',
                408 => '1.0 408 Request Timeout',
                409 => '1.0 409 Conflict',
                410 => '1.0 410 Gone',
                411 => '1.0 411 Length Required',
                412 => '1.0 412 Precondition Failed',
		413 => '1.0 413 Request Entity Too Large',
                414 => '1.0 414 Request-URI Too Long',
                415 => '1.0 415 Unsuported Media Type',
                416 => '1.0 416 Requested Range Not Satisfiable',
                417 => '1.0 417 Expectation Failed',
		);

	public $contentType;
	public $charset;
	public $headers = array();

	private $assigned = null;

	public function __construct($content = 'text/html', $charset = 'utf-8') {
		$this->contentType = $content;
		$this->charset = $charset;
		$this->data = new \StdClass();
	}


	public function Assign($variable, $value) {
		$this->assigned[$variable] = $value;
	}
	public function __set($variable, $value) {
		$this->assigned[$variable] = $value;
	}

	public function GetAssigned($as_array = false) {
		if($as_array) return $this->assigned;
		else return (object)$this->assigned;
	}


	public function AddHTTPHeader($name, $value = null) {
		if($value) $this->headers[] = $name.': '.$value;
			else $this->headers[] = $name;
	}

	public function File($filename, $exit = false) {
		readfile($filename);
		if($exit) exit();
	}


	public function PassToFile($source, $data = null) {
		ob_start();
		if($data) $v = $view = (object)$data; else $v = $view = (object)$this->assigned;
		if(file_exists(TemplateOutput.$source.'.php')) include(TemplateOutput.$source.'.php');
		elseif(file_exists(TemplateOutputCommon.$source.'.php')) include(TemplateOutputCommon.$source.'.php');
		elseif(file_exists("html/{$source}.html")) include("html/{$source}.html");
		return ob_get_clean();
	}


	public function SendHeaders() {
		header('Content-type: '.$this->contentType.($this->charset ? '; charset='.$this->charset : ''));
		if($this->headers) foreach($this->headers as $header) header($header);
	}


	public static function HTML($text) {
		return htmlspecialchars($text);
	}

}


class Input {

	public static function QueryString() {
		return $_SERVER['QUERY_STRING'];
	}


	public static function RequestMethod() {
		return $_SERVER['REQUEST_METHOD'];
	}


	public static function IsPost() {
		return ($_SERVER['REQUEST_METHOD'] == 'POST');
	}


	public static function IsGet() {
		return ($_SERVER['REQUEST_METHOD'] == 'GET');
	}


	public static function IsAJAX() {
		return (((!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')) || ($_GET[AjaxFlag]) || ($_POST[AjaxFlag])));
	}


	public static function Post($varname) {
		return $_POST[$varname];
	}

	public static function Files($varname) {
		return $_FILES[$varname];
	}

	public static function Get($varname) {
		return $_GET[$varname];
	}


	public static function Cookie($varname) {
		return $_COOKIE[$varname];
	}


	public static function IsSubmited($formID) {
		if(($_POST[formIdentificator] == $formID) || ($_GET[formIdentificator] == $formID)) return true;
			else return false;
	}

	public static function Self() {
		return $_SERVER['REQUEST_URI'];
	}


	public static function IsFiles() {
		return count($_FILES);
	}


	public static function FilterSelf($removes) {
		$tmp = explode('?', $_SERVER['REQUEST_URI']);
		parse_str($tmp[1], $params);
		$remove = explode(',', $removes);
		$new = array();
		foreach($params as $var => $param) if(!in_array($var, $remove)) $new[$var] = $param;
		$tmp[1] = http_build_query($new);
		if($tmp[1]) $tmp = implode('?', $tmp); else $tmp = $tmp[0];
		return $tmp;
	}


	public static function Referer() {
		return $_SERVER['HTTP_REFERER'];
	}


	public static function GetStream() {
		return file_get_contents('php://input');
	}

}

class Request {
	public function __construct() {
		$this->method = $_SERVER['REQUEST_METHOD'];
		$this->isAjax = (((!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')) || ($_GET[AjaxFlag]) || ($_POST[AjaxFlag])));
		$this->post = (object)$_POST;
		$this->get = (object)$_GET;
		$this->cookie = (object)$_COOKIE;
		$this->contentType = $_SERVER['CONTENT_TYPE'];
		$this->body = file_get_contents('php://input');
		$this->referer = $_SERVER['HTTP_REFERER'];
		$this->headers = (object)getallheaders();
		switch($this->content_type) {
			case('application/json'):
				$this->data = JSON_decode($this->body);
				break;
			case('application/xml'):
				$this->data = SimpleXML_load_string($this->body);
				break;
			case('application/x-www-form-urlencoded'):
				parse_str($this->body, $this->data);
				break;
		}
	}
}

class PostData {
	public function __construct() {
		$tmp = file_get_contents('php://input');
		if($tmp) {
			switch($_SERVER['CONTENT_TYPE']) {
				case('application/json'):
					$out = JSON_decode($tmp);
					break;
				case('application/xml'):
					$out = SimpleXML_load_string($tmp);
					break;
				case('application/x-www-form-urlencoded'):
					parse_str($tmp, $out);
					break;
			}
			foreach($out as $param => $value) $this->$param = $value;
		}
	}
}


class Session {

	public function __construct($ses_id = null) {
		if($ses_id) session_name($ses_id);
		session_start();
	}

	public function __set($name, $val) {
		$_SESSION[$name] = $val;
	}


	public function __get($name) {
		return $_SESSION[$name];
	}


	public function destroy() {
		return session_destroy();
	}
}


class UploadedFile {

	public function __construct($file = null) {
		if(is_array($file)) foreach($file as $key => $value) $this->$key = $value;
		$tmp = explode('.', $this->name);
		$this->extension = array_pop($tmp);
		$this->shortname = implode('.', $tmp);
	}

	public function IsImage() {
		return preg_match('/image\/(.+)/', $this->type);
	}

	public function Move($directory, $name = '') {
		return move_uploaded_file($this->tmp_name, $directory.'/'.($name ? $name : $this->name));
	}
}


class UploadedFileIterator implements \Iterator {

	private $files;
	private $pointer;

	public function __construct($prefix) {
		if(is_array($_FILES[$prefix]['name'])) {
			foreach($_FILES[$prefix]['name'] as $fkey => $name) {
				$this->files[$fkey] = new UploadedFile();
				foreach(array_keys($_FILES[$prefix]) as $akey) $this->files[$fkey]->$akey = $_FILES[$prefix][$akey][$fkey];
			}
		} else {
			$this->files[0] = new UploadedFile($_FILES[$prefix]);
		}

		$this->pointer = 0;
	}

	function rewind() {
		$this->pointer = 0;
	}

	function current() {
		return $this->files[$this->pointer];
	}

	function key() {
		return $this->pointer;
	}

	function next() {
		++$this->pointer;
	}

	function valid() {
		return isset($this->files[$this->pointer]);
	}
}


class_alias('Rnr\Output', '\Out');
class_alias('Rnr\Input', '\In');
