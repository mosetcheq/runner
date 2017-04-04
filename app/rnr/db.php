<?php

namespace Rnr;
use \PDO;

class DBStatement {
	public $OS;

	public function __construct($statement) {
		$this->OS = $statement;
	}

	public function __call($name, $arguments) {
		return call_user_func_array([$this->OS, $name], $arguments);
	}

	public function Exec($data = null) {
		$this->OS->execute($data);
		return $this;
		// if(DB_DEBUGMODE) $this->OS->debugDumpParams();
	}

	public function Fetch($className = null) {
		return $this->OS->FetchObject($className);
	}

	public function FetchGroup() {
		return $this->OS->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_OBJ);
	}

	public function FetchByColumn($col_index = 0) {
		return $this->OS->fetchAll(PDO::FETCH_COLUMN, $col_index);
	}

}


class DB {

	private static $PDO;
	public	static $Prefix;
	private static $PDOStatement;
	public  static $debug = false;
	public	static $totaltime = 0;

	public static function Connect($server, $user, $password, $database, $prefix = null) {

		self::$Prefix = $prefix;
		try {
			self::$PDO = new PDO("mysql:host={$server};dbname={$database}", $user, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
		} catch(\PDOException $e) {
			Log::Write('Database error: '.$e->getMessage());
			ErrorHandling::SQL(null, $e->getMessage());
		}
	}

	public static function getAttr($atr) {
		return @self::$PDO->getAttribute(constant('PDO::ATTR_'.$atr));
	}


	private static function Q($query) {
		if(DB_DEBUGMODE) Log::Write($query); $start = microtime(true);
		if(!$statement = self::$PDO->query($query)) {
			$err = self::$PDO->errorInfo();
			Log::Write($err[1].':'.$err[2]);
			if(!DisableWarnings) ErrorHandling::SQL($query, $err);
			if(DB_DEBUGMODE) echo($query.'<br>'.$err[1].':'.$err[2]);
			exit();
		}
                self::$totaltime += (microtime(true) - $start);
		self::$PDOStatement = $statement;
		return new DBStatement($statement);
	}


	public static function Query($query) {
		return self::Q($query);
	}

	public static function Prepare($query) {
		if(DB_DEBUGMODE) Log::Write('Prepare '.$query);
		return new DBStatement(self::$PDO->prepare($query));
	}

	public static function PrepQuery($query, $data) {
        	if(DB_DEBUGMODE) Log::Write('Prepare '.$query);
		$statement = self::$PDO->prepare($query);
		if(!$statement->execute($data))	{
			if(!DisableWarnings) ErrorHandling::SQL($query, $statement->errorInfo());
		}
		self::$PDOStatement = $statement;
		return new DBStatement($statement);
	}

	public static function getObject($classname = 'stdClass') {
		return self::$PDOStatement->FetchObject($classname);
	}

	public static function Get($classname = 'stdClass') {
		return self::$PDOStatement->FetchObject($classname);
	}



	public static function Exec($query) {
		if(DB_DEBUGMODE) Log::Write($query);
		return self::$PDO->exec($query);
	}

	public static function PrepExec($query, $data) {
        	if(DB_DEBUGMODE) Log::Write('Prepare '.$query."\nValues:".print_r($data, true));
		$statement = self::$PDO->prepare($query);
		if(!$statement->execute($data))	{
			if(!DisableWarnings) ErrorHandling::SQL($query, $statement->errorInfo());
		}
		return $statement;
	}

	public static function FetchAllRaw($query) {
		if(DB_DEBUGMODE) Log::Write($query);
		return self::$PDO->query($query)->FetchAll(PDO::FetchClass);
	}



	public static function FetchRow($query, $values = false, $objtype = null) {
		if(DB_DEBUGMODE) Log::Write($query);
		if(!$values) return self::$PDO->query($query)->FetchObject($objtype);
		else return self::PrepExec($query, $values)->FetchObject($objtype);
	}


	public static function FetchAll($query, $values = false, $objtype = null, $key = null, $callback = null) {
        	if(DB_DEBUGMODE) Log::Write($query);
		$tmp = null;
                if(!$values) $q = self::$PDO->query($query);
		else $q = self::PrepExec($query, $values);
		if(!$q) {
			$err = self::$PDO->errorInfo();
			Log::Write($err[1].':'.$err[2]);
			if(!DisableWarnings) ErrorHandling::SQL($query, $err);
			if(DB_DEBUGMODE) echo($query.'<br>'.$err[1].':'.$err[2]);
			exit();
		}
		while($s = $q->FetchObject($objtype)) {
			if(is_callable($callback)) $s = call_user_func($callback, $s);
			if($key) $tmp[$s->$key] = $s; else $tmp[] = $s;
			}
		return $tmp;
	}



       	public static function FetchDataRow($tablename, $where = null) {
		if(self::$Prefix) $tablename = self::$Prefix.'_'.$tablename;
		return self::$PDO->query('SELECT * FROM '.$tablename.($where ? ' WHERE '.$where : '').' LIMIT 1;')->FetchObject();
	}



	public static function Insert($tablename, $data, $datafilter = false, $ignore = false) {
		if(self::$Prefix) $tablename = self::$Prefix.'_'.$tablename;
		if($datafilter) $data = array_filter($data);
		if($ignore) $ign = 'IGNORE ';
//		self::Exec("INSERT {$ign}INTO {$tablename} ".self::createInsertData($data, $datafilter).';');
		$data = self::createInsertData($data, $datafilter);
		self::PrepExec("INSERT {$ign}INTO {$tablename} {$data['query']};", $data['pass']);
		return self::$PDO->lastInsertId();
	}



	public static function Update($tablename, $data, $where = null, $limit = null) {
		if(self::$Prefix) $tablename = self::$Prefix.'_'.$tablename;
/*
		if(is_array($data)) $fields = self::createUpdateData($data);
			else $fields = $data;
*/
		if(is_array($where)) {
			$wh = [];
			foreach($where as $column => $value)
				if(gettype($value) == 'NULL') $wh[] = $column.' IS NULL';
					else $wh[] = $column.' = \''.$value.'\'';
			$where = implode(' AND ', $wh);
		}
		if(is_array($data)) {
			$data = self::createUpdateData($data);
//		return self::Exec("UPDATE {$tablename} SET {$fields}".($where ? ' WHERE '.$where : '').';');
			return self::PrepExec("UPDATE {$tablename} SET {$data['query']}".($where ? ' WHERE '.$where : '').($limit ? ' LIMIT '.$limit : '').';', $data['pass']);
		} else return self::Exec("UPDATE {$tablename} SET {$data}".($where ? ' WHERE '.$where : '').($limit ? ' LIMIT '.$limit : '').';');
	}



	public static function InsertUpdate($tablename, $insert, $update, $datafilter = false, $noID = false) {
		if(self::$Prefix) $tablename = self::$Prefix.'_'.$tablename;
		$data1 = self::createInsertData($insert, $datafilter);
		$data2 = self::createUpdateData($update, $datafilter);
		self::PrepExec("INSERT INTO {$tablename} {$data1['query']} ON DUPLICATE KEY UPDATE {$data2['query']}".(!$noID ? ', id = LAST_INSERT_ID(id)' : '').';', array_merge($data1['pass'], $data2['pass']));
		return self::$PDO->lastInsertId();
	}


	public static function InsertMultiple($tablename, $columns, $data, $ignore = false, $onduplicate = false) {
		foreach($data as $key => $values) {
			foreach($values as $k => $v) if(!is_null($v)) $values[$k] = '\''.addslashes($v).'\''; else $values[$k] = 'NULL';
			$data[$key] = '('.implode(', ', $values).')';
		}
		$query = 'INSERT'.($ignore ? ' IGNORE' : '')." INTO {$tablename} ({$columns}) VALUES ".implode(', ', $data).($onduplicate ? ' ON DUPLICATE KEY UPDATE '.$onduplicate : '').';';
		if(DB_DEBUGMODE) Log::Write($query);
		return self::$PDO->exec($query);
	}


	public static function Delete($tablename, $where, $limit = null) {
		if(self::$Prefix) $tablename = self::$Prefix.'_'.$tablename;

		return self::Exec('DELETE FROM '.$tablename.($where ? ' WHERE '.$where : '').($limit ? ' LIMIT '.$limit : '').';');
	}

	public static function createWhere($where) {
		if(is_array($where)) {
			$wh = [];
			foreach($where as $column => $value)
				if(gettype($value) == 'NULL') $wh[] = $column.' IS NULL';
					else $wh[] = $column.' = \''.$value.'\'';
			$where = implode(' AND ', $wh);
		}
		return $where;
	}


	public static function createInsertData($data, $datafilter = false) {
		if($datafilter) $data = array_filter($data);
		$data_pass = array();
		foreach($data as $key => $value) {
			switch(gettype($value)) {
				case('NULL'): $value = 'NULL'; break;
				case('array'): $value = implode(',', $value); break;
				default: if($value[0] == '$') $value = substr($value,1);
					else {
						$data_pass['i_'.$key] = $value;
						$value = ":i_{$key}";
						}
				break;
			}
			$data[$key] = $value;
		}
		return array('query' => '('.implode(', ', array_keys($data)).') VALUES ('.implode(', ', $data).')',
			'pass' => $data_pass);
	}



	public static function createUpdateData($data, $datafilter = false) {
        	$data_pass = array();
		foreach($data as $key => $value) {
			switch(gettype($value)) {
				case('NULL'): $value = 'NULL'; break;
				case('array'): $value = implode(',', $value); break;
				default: if($value[0] == '$') $value = substr($value,1);
					else {
                                                $data_pass['u_'.$key] = $value;
                                                $value = ":u_{$key}";
						}
				break;
			}
			$tmp[] = "{$key} = {$value}";
		}
		return array('query' => implode(', ', $tmp), 'pass' => $data_pass);
	}


}

