<?php

class Response {
	public $type;
	public $template;
	public $data1;
	public $data2;

	public function __construct($type, $template, $data1 = null, $data2 = null)
    {
		$this->type = $type;
		$this->template = $template;
		$this->data1 = $data1;
		$this->data2 = $data2;
	}
}