<?php

namespace Rnr\DB;

class PreparedQueryData
{

    public string $query;
    public array $data;


    public function __construct(string $query, array $data)
    {
        $this->query = $query;
        $this->data = $data;
    }

}