<?php

use PHPUnit\Framework\TestCase;
use Rnr\DB\DB;
use Rnr\DB\SQLFunction;
use Rnr\DB\Expression;

class DBTest extends TestCase
{
    public $db;

    public function __construct()
    {
        $f = func_get_args();
        $this->db = new DB('localhost', 'root', 'root', 'uuii');
        parent::__construct(...$f);
    }

    public function testInsertPrepareData()
    {
        $data = [
            'name' => 'Jakub',
            'age' => 30,
            'tags' => ['php', 'js'],
            'created' => new SQLFunction('NOW()'),
            'deleted' => null
        ];

        $result = $this->db->prepareInsertQuery($data);
        $this->assertSame(
            '(name, age, tags, created, deleted) VALUES (:i_name, :i_age, :i_tags, NOW(), NULL)',
            $result->query
        );

        $this->assertSame(
            [
                'i_name' => 'Jakub',
                'i_age' => 30,
                'i_tags' => 'php,js'
            ],
            $result->data
        );
    }

    public function testUpdatePrepareData()
    {
        $data = [
            'name' => 'Jakub',
            'age' => 30,
            'tags' => ['php', 'js'],
            'created' => new SQLFunction('NOW()'),
            'deleted' => null
        ];

        $result = $this->db->prepareUpdateQuery($data);
        $this->assertSame(
            'name = :u_name, age = :u_age, tags = :u_tags, created = NOW(), deleted = NULL',
            $result->query
        );

        $this->assertSame(
            [
                'u_name' => 'Jakub',
                'u_age' => 30,
                'u_tags' => 'php,js'
            ],
            $result->data
        );
    }

    public function testWherePrepareData()
    {
        $data = [
            'name' => 'Jakub',
            'age' => 30,
            'created' => new SQLFunction('NOW()'),
            'tags' => ['man', 'woman'],
            'size' => new Expression('>=', new SQLFunction('NOW()')),
            'wide' => null,
            'xlat' => new Expression('<>', 30)
        ];
        $result = $this->db->prepareWhereQuery($data);
        $this->assertSame(
            'WHERE name = :w_name AND age = :w_age AND created = NOW() AND tags IN (:w_tags0, :w_tags1) AND size >= NOW() AND wide IS NULL AND xlat <> :w_xlat',
            $result->query
        );

        $this->assertSame(
            [
                'w_name' => 'Jakub',
                'w_age' => 30,
                'w_tags0' => 'man',
                'w_tags1' => 'woman',
                'w_xlat' => 30
            ],
            $result->data
        );
    }


    public function testFetchRowFrom()
    {
        $result = $this->db->fetchRowFrom('device', ['id_device' => 1, 'id_user' => 1]);
        $this->assertIsObject($result);
    }

    public function testFetchRow()
    {
        $result = $this->db->fetchRow('SELECT id_device FROM device ORDER BY id_device LIMIT 1;');
        $this->assertObjectHasProperty('id_device', $result);
    }


}