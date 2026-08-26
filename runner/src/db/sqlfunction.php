<?php
namespace Rnr\DB;

class SQLFunction
{
    private string $expression;

    public function __construct(string $expression)
    {
        $this->expression = $expression;
    }

    public function toSql(): string
    {
        return $this->expression;
    }
}