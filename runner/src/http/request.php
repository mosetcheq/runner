<?php
namespace Rnr\Http;

class Request {

	public $method;
	public $isAjax;
	public $headers;
	public $body;
	public $url;

	public $getData;
	public $postData;
	public $cookieData;
	public $data;

	public $contentType;
	public $referer;

	public function __construct() {
		$this->method = $_SERVER['REQUEST_METHOD'];
		$this->url = $_SERVER['REQUEST_URI'];
		$this->isAjax = (((!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')) || isset($_GET[AjaxFlag]) || isset($_POST[AjaxFlag])));
		$this->postData = (object)$_POST;
		$this->getData = (object)$_GET;
		$this->cookieData = (object)$_COOKIE;
		$this->contentType = $_SERVER['CONTENT_TYPE'] ?? null;
		$this->body = file_get_contents('php://input');
		$this->referer = $_SERVER['HTTP_REFERER'] ?? null;
		$this->headers = new \stdClass;
		foreach(getallheaders() as $name => $value) {
			$name = strtoupper(str_replace('-', '_', $name));
			$this->headers->$name = $value;
		}
		switch($this->contentType) {
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

