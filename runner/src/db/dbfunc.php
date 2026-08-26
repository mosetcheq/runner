<?php
namespace Rnr\DB;

class sqlFunc {

	private string $value;
	private string $equal;

	public function __construct(string $val, string $equal = '=') {
		$this->value = $val;
		$this->equal = $equal;
	}


	public function GetInsert() : string
	{
		return $this->value;
	}


	public function GetUpdate() : string
	{
		return $this->equal.' '.$this->value;
	}

}