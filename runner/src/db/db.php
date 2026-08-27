<?php

namespace Rnr\DB;

use PDO;
use PDOStatement;
use Rnr\DB\Interfaces\RowProcessorInterface;

class DB
{

    private PDO $pdo;
    private PDOStatement $statement;

    public function __construct(string $host, string $userName, string $userPassword, string $database)
    {
        $this->pdo = new PDO("mysql:host={$host};dbname={$database};charset=utf8mb4", $userName, $userPassword);
    }


    public function query(string $query) : PDOStatement
    {
        return $this->pdo->query($query);
    }


    public function prepare(string $query) : PDOStatement
    {
        return $this->pdo->prepare($query);
    }


    public function fetchAll(string $query, ?array $values = null, ?string $objectType = null, ?RowProcessorInterface $processor = null) : array
    {
        $output = [];
        if($values === null) {
            $statement = $this->query($query);
        } else {
            $statement = $this->prepare($query);
            $statement->execute($values);
        }
        if($processor !== null) {
            while($row = $statement->fetchObject($objectType)) {
                $output[] = $processor->process($row);
            }
        } else {
            while($row = $statement->fetchObject($objectType)) {
                $output[] = $row;
            }
        }
        return $output;
    }
    

    public function fetchRow(string $query, ?array $values = null, ?string $objectType = null)
    {
        if($values === null) {
            $statement = $this->query($query);
        } else {
            $statement = $this->prepare($query);
            $statement->execute($values);
        }
        return $statement->fetchObject($objectType);
    }


    public function fetchRowFrom(string $table, array $where, ?string $objectType = null)
    {
        $preparedWhere = $this->prepareWhereQuery($where);
        $statement = $this->prepare("SELECT * FROM {$table} " . $preparedWhere->query . ' LIMIT 1;');
        $statement->execute($preparedWhere->data);
        return $statement->fetchObject($objectType);
    }


    public function insert(string $tableName, array $values, bool $ignore = false) : ?string
    {
        $preparedData = $this->prepareInsertQuery($values);
        $statement = $this->prepare('INSERT ' . ($ignore ? 'IGNORE ' : '') . 'INTO ' . $tableName . ' ' . $preparedData->query);
        $statement->execute($preparedData->data);
        return $this->pdo->lastInsertId();
    }


    public function update(string $tableName, array $values, array $where, ?int $limit = null) : ?int
    {
        $preparedData = $this->prepareUpdateQuery($values);
        $preparedWhereData = $this->prepareWhereQuery($where);
        $statement = $this->prepare('UPDATE ' . $tableName . ' SET ' . $preparedData->query . $preparedWhereData->query . ($limit ? ' LIMIT ' . $limit : '') . ';');
        $statement->execute(array_merge($preparedData->data, $preparedWhereData->data));
        return $statement->rowCount();
    }



    public function prepareInsertQuery(array $data, bool $dataFilter = false) : PreparedQueryData
    {
        if($dataFilter) {
            $data = array_filter($data);
        }
        $passedData = [];
        foreach($data as $key => $value) {
            switch(gettype($value)) {
                case('NULL'):
                    $value = 'NULL';
                    break;
                case('object'):
                    if($value instanceof SQLFunction) {
                        $value = $value->toSql();
                    }
                    break;
                case('array'):
                    $value = implode(',', $value);

                default:
                    $passedData['i_' . $key] = $value;
                    $value = ':i_' . $key;
            }

            $data[$key] = $value;
        }
        return new PreparedQueryData('(' . implode(', ', array_keys($data)) . ') VALUES (' . implode(', ', $data) . ')', $passedData);
    }


    public function prepareUpdateQuery(array $data, bool $dataFilter = false) : PreparedQueryData
    {
        if($dataFilter) {
            $data = array_filter($data);
        }
        $passedData = [];
        $updateData = [];
        foreach($data as $key => $value) {
            switch(gettype($value)) {
                case('NULL'):
                    $updateData[] = $key . ' = NULL';
                    break;
                case('object'):
                    if($value instanceof SQLFunction) {
                        $updateData[] = $key . ' = ' . $value->toSql();
                    }
                    break;
                case('array'):
                    $value = implode(',', $value);

                default:
                    $passedData['u_' . $key] = $value;
                    $updateData[] = $key . ' = :u_' . $key;
            }

        }
        return new PreparedQueryData(implode(', ', $updateData), $passedData);
    }


    public function prepareWhereQuery(array $data) : PreparedQueryData
    {
        $passedData = [];
        $whereCause = [];
        foreach($data as $key => $value) {
            switch(gettype($value)) {
                case('NULL'):
                    $whereCause[] = $key . ' IS NULL';
                    break;
                case('object'):
                    if($value instanceof SQLFunction) {
                        $whereCause[] = $key . ' = ' . $value->toSql();
                    }
                    if($value instanceof Expression) {
                        if($value->isFunction()) {
                            $whereCause[] = $key . ' ' . $value->toSql();
                        } else {
                            $whereCause[] = $key . ' ' . $value->toSql($key);
                            $passedData['w_' . $key] = $value->getValue();
                        }
                    }
                    break;
                case('array'):
                    $i = 0;
                    $indexNames = [];
                    foreach($value as $vv) {
                        $indexName = 'w_' . $key . $i;
                        $passedData[$indexName] = $vv;
                        $indexNames[] = ':' . $indexName;
                        $i++;
                    }
                    $whereCause[] = $key . ' IN (' . implode(', ', $indexNames) . ')';
                    break;
                default:
                    $passedData['w_' . $key] = $value;
                    $whereCause[] = $key . ' = :w_' . $key;
            }
        }
        return new PreparedQueryData('WHERE ' . implode(' AND ', $whereCause), $passedData);
    }

}