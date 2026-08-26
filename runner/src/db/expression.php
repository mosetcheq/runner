<?php
namespace Rnr\DB;

class Expression
{
    private string $operator;
    private mixed $value;

    public function __construct(string $operator, mixed $value)
    {
        $this->operator = $operator;
        $this->value = $value;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function isFunction(): bool
    {
        return $this->value instanceof SQLFunction;
    }

    public function toSql(?string $key = null): string
    {
        if ($this->isFunction()) {
            return $this->operator . ' ' . $this->value->toSql();
        }

        // Hodnota se bude escapovat přes parametr
        return $key == null ? $this->operator . ' ?' : $this->operator . ' :w_' . $key;
    }
}