<?php
namespace Rnr\DB;

class PreparedQueryData
{

    /**
     * Query parametry ve string formátu
     *
     * @var string
     */
    public string $query;

    /**
     * Filtrované pole dat pro execute
     *
     * @var array
     */
    public array $data;


    public function __construct(string $query, array $data)
    {
        $this->query = $query;
        $this->data = $data;
    }

}