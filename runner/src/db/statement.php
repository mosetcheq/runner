<?php
namespace Rnr\DB;
use PDO;
use PDOStatement;

class Statement {

	private PDOStatement $OS;

	public function __construct(PDOStatement $statement)
    {
		$this->OS = $statement;
	}


	public function __call(string $name, mixed $arguments) : mixed
    {
		return call_user_func_array([$this->OS, $name], $arguments);
	}


	public function execute(mixed $data = null) : self
    {
		$this->OS->execute($data);
		return $this;
		// if(DB_DEBUGMODE) $this->OS->debugDumpParams();
	}


	public function fetch(?string $className = null) : object | false
    {
		return $this->OS->fetchObject($className);
	}


	public function fetchGroup() : array
    {
		return $this->OS->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_OBJ);
	}


	public function fetchByColumn(int $col_index = 0) : array
    {
		return $this->OS->fetchAll(PDO::FETCH_COLUMN, $col_index);
	}

}