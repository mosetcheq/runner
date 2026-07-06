<?php


class DBX {

	public static function SelectFrom($table) {
		$q = new QueryBuilder(0);
		$q->Select('*')->From($table);
		return $q;
	}

	public static function Select($cols) {
		$q = new QueryBuilder(0);
		$q->Select($cols);
		return $q;
	}

	public static function Update($table) {
		$q = new QueryBuilder(1);
		$q->Update($table);
		return $q;
	}

	public static function Insert() {
		$q = new QueryBuilder(2);
		call_user_func(array($q, 'Insert'), func_get_args());
		return $q;
	}

	public static function Delete($table) {
		$q = new QueryBuilder(3);
		$q->Delete($table);
		return $q;
	}
}

class QueryBuilder {

	private $mode;
	private $query;

	public function __construct($mode) {
		$this->mode = $mode;
	}

	public function __call($property, $arguments) {
		$property = strtolower($property);
		if(!$this->$property) $this->$property = array();
		array_push($this->$property, $arguments);
		return $this;
	}

	public function FetchAll($key = null, $object = null) {
		if($this->mode != 0) throw new \Exception('Only Select and SelectFrom operations can be fetched');
		$this->GetQuery();
		echo($this->query);
	}

	public function Get($property) {
		$property = strtolower($property);
		$propname = strtoupper($property);
		switch($property) {
			case('select'):
			case('from'):
				$o = array();
				if($this->$property) {
					foreach($this->$property as $p) $o[] = implode(' ', $p);
					$o = $propname.' '.implode(', ', $o).' ';
				} else $o = '';
			break;
			case('where'):
				$o = '';
				if($this->$property) {
					$c = 0;
					foreach($this->$property as $p) {
						if($c > 0) {
							if($p[2]) $o.= ' '.$p[2].' ';
							else $o.='AND ';
						}
						$o.= $p[0].' ';
                                                if($p[1]) {
							$o.= '= ? ';
							$this->data[] = $p[1];
						}
						$c++;
					}
					$o = 'WHERE '.$o;
				} else $o = '';
			break;
			case('order'):
				$o = array();
				if($this->$property) {
					foreach($this->$property as $p) $o[] = implode(' ', $p);
					$o = 'ORDER BY '.implode(', ', $o).' ';
				} else $o = '';
			break;
			case('limit'):
				$l = $this->$property;
				if($l) $o = $propname.' '.implode(', ', $l[0]);
			break;
		}
		$this->query.= $o;
		return $this;
	}

	public function GetQuery() {
		switch($this->mode) {
			case(0):
				$this->query = '';
				$this->Get('Select')->Get('From')->Get('Join')->Get('Where')->Get('Order')->Get('Limit');
			break;
		}
	}
}
