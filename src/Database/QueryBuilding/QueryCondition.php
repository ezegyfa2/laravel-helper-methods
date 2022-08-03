<?php

namespace Ezegyfa\LaravelHelperMethods\Database\QueryBuilding;

class QueryCondition
{
    public $expression1;
    public $expression2;
    public $operator;

    public function __construct(string $expression1, string $expression2, string $operator = '=')
    {
        $this->expression1 = $expression1;
        $this->expression2 = $expression2;
        $this->operator = $operator;
    }

    public function getQuery()
    {
        return $this->expression1 . ' ' . $this->operator . ' ' . $this->expression2;
    }
}
