<?php

namespace Rnr\DB;

use Exception;
use PDO;
use PDOStatement;
use Rnr\DB\Interfaces\RowProcessorInterface;

class DB
{

    private PDO $pdo;

    /**
     * Konstruktor
     *
     * @param string $host
     * @param string $userName
     * @param string $userPassword
     * @param string $database
     */
    public function __construct(string $host, string $userName, string $userPassword, string $database)
    {
        $this->pdo = new PDO("mysql:host={$host};dbname={$database};charset=utf8mb4", $userName, $userPassword);
    }


    /**
     * Nádstavba PDO::query
     *
     * @param string $query SQL Query
     * @return PDOStatement
     */
    public function query(string $query) : PDOStatement
    {
        return $this->pdo->query($query);
    }


    /**
     * Nádstavba PDO::prepare
     *
     * @param string $query SQL Query
     * @return PDOStatement
     */
    public function prepare(string $query) : PDOStatement
    {
        return $this->pdo->prepare($query);
    }


    /**
     * Provede SQL Query s daty
     *
     * @param string $query SQL Query
     * @param array|null $data data bindovan8 do query
     * @return PDOStatement
     */
    public function exec(string $query, ?array $data = null) : PDOStatement
    {
        $statement = $this->prepare($query);
        $statement->execute($data);
        return $statement;
    }


    /**
     * Načte obsah celé query jako pole objektů
     *
     * @param string $query SQL Query
     * @param array|null $values Hodnoty pro data bind
     * @param string|null $objectType typ objektu
     * @param RowProcessorInterface|null $processor Data processing načtených dat
     * @return array
     */
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
    

    /**
     * Načte řádek tabulky podle query
     * Je potřeba si ohlídat aby příkaz vrátíl pouze jeden řádek - LIMIT 1
     *
     * @param string $query SQL query
     * @param array|null $values Hodnoty pro data bind
     * @param string|null $objectType typ objektu
     * @return object|null načtená data
     */
    public function fetchRow(string $query, ?array $values = null, ?string $objectType = null) : ?object
    {
        if($values === null) {
            $statement = $this->query($query);
        } else {
            $statement = $this->prepare($query);
            $statement->execute($values);
        }
        return $statement->fetchObject($objectType);
    }


    /**
     * Načte řádek z tabulky
     *
     * @param string $table Název tabulky
     * @param array $where Podmínka výběru
     * @param string|null $objectType typ objektu
     * @return object|null načtená data
     */
    public function fetchRowFrom(string $table, array $where, ?string $objectType = null) : ?object
    {
        $preparedWhere = $this->prepareWhereQuery($where);
        $statement = $this->prepare("SELECT * FROM {$table} " . $preparedWhere->query . ' LIMIT 1;');
        $statement->execute($preparedWhere->data);
        return $statement->fetchObject($objectType);
    }


    /**
     * Provede vložení dat do tabulky
     *
     * @param string $tableName Název tabulky
     * @param array $values Data k vložení
     * @param boolean $ignore Ignorovat existující index
     * @return string|null ID nového řádku
     */
    public function insert(string $tableName, array $values, bool $ignore = false) : ?string
    {
        $preparedData = $this->prepareInsertQuery($values);
        $statement = $this->prepare('INSERT ' . ($ignore ? 'IGNORE ' : '') . 'INTO ' . $tableName . ' ' . $preparedData->query);
        $statement->execute($preparedData->data);
        return $this->pdo->lastInsertId();
    }


    /**
     * Provede aktualizaci dat v tabulce
     *
     * @param string $tableName Název tabulky
     * @param array $values Data k UPDATE
     * @param array $where Pole podmínek pro update
     * @param integer|null $limit Limit řádků nebo null
     * @return integer|null Vrátí počet aktualizovaných řádků
     */
    public function update(string $tableName, array $values, array $where, ?int $limit = null) : ?int
    {
        $preparedData = $this->prepareUpdateQuery($values);
        $preparedWhereData = $this->prepareWhereQuery($where);
        $statement = $this->prepare('UPDATE ' . $tableName . ' SET ' . $preparedData->query . $preparedWhereData->query . ($limit ? ' LIMIT ' . $limit : '') . ';');
        $statement->execute(array_merge($preparedData->data, $preparedWhereData->data));
        return $statement->rowCount();
    }


    /**
     * Připraví PDOStatement pro opakovaný insert
     *
     * @param string $tableName Jméno tabulky
     * @param array $keys Klíče pole, které se bude vkládat
     * @param boolean $ignore Mají se ignorovat data s existujícím indexem
     * @return PDOStatement
     */
    public function prepareInsert(string $tableName, array $keys, bool $ignore = false) : PDOStatement
    {
        $valKeys = array_map(fn($key) => ':' . $key, $keys);
        return $this->prepare('INSERT ' . ($ignore ? 'IGNORE ' : '') . 'INTO ' . $tableName . ' (' . implode(', ', $keys) . ') VALUES (' . implode(', ', $valKeys) .');');
    }


    /**
     * Připraví PDOStatement pro opakovaný update
     *
     * @param string $tableName Jméno tabulky
     * @param array $keys Klíče pole dat, které se budou zpracovávat
     * @param array $whereKeys Pole s klíči, které budou použity ve WHERE podmínce - musí být součástí $keys
     * @return PDOStatement
     */
    public function prepareUpdate(string $tableName, array $keys, array $whereKeys) : PDOStatement
    {
        if(empty($whereKeys)) {
            throw new Exception('Array whereKeys is empty');
        }
        if(!empty(array_diff($whereKeys, $keys))) {
            throw new Exception('Some whereKeys are not part of keys');
        }
        $keys = array_diff($keys, $whereKeys);
        if(empty($keys)) {
            throw new Exception('Empty value keys');
        }
        $setPair = array_map(fn($key) => $key . ' = :' . $key, $keys);
        $wherePair = array_map(fn($key) => $key . ' = :' . $key, $whereKeys);
        return $this->prepare('UPDATE ' . $tableName . ' SET ' . implode(', ', $setPair) . ' WHERE ' . implode(' AND ', $wherePair) .';');
    }


    /**
     * Připraví parametry pro INSERT SQL dotaz
     *
     * @param array $data pole dat, které chceme vkládat
     * @param boolean $disablePrefix zakáže prefix v připravovaném dotazu :bind místo :i_bind
     * @return PreparedQueryData
     */
    private function prepareInsertQuery(array $data, bool $disablePrefix = false) : PreparedQueryData
    {
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
                    $passedData[($disablePrefix ? '' : 'i_') . $key] = $value;
                    $value = ':' . ($disablePrefix ? '' : 'i_') . $key;
            }

            $data[$key] = $value;
        }
        return new PreparedQueryData('(' . implode(', ', array_keys($data)) . ') VALUES (' . implode(', ', $data) . ')', $passedData);
    }


    /**
     * Připraví parametry pro UPDATE SQL dotaz
     *
     * @param array $data pole dat, které chceme vkládat
     * @param boolean $disablePrefix zakáže prefix v připravovaném dotazu :bind místo :u_bind
     * @return PreparedQueryData
     */
    private function prepareUpdateQuery(array $data, bool $disablePrefix = false) : PreparedQueryData
    {

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
                    $passedData[($disablePrefix ? '' : 'u_') . $key] = $value;
                    $updateData[] = $key . ' = :' . ($disablePrefix ? '' : 'u_') . $key;
            }

        }
        return new PreparedQueryData(implode(', ', $updateData), $passedData);
    }


    /**
     * Připraví parametry pro WHERE do SQL dotazu
     *
     * @param array $data pole dat které mají být použity jako podmínka
     * @return PreparedQueryData
     */
    private function prepareWhereQuery(array $data) : PreparedQueryData
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