<?php

namespace Ezegyfa\LaravelHelperMethods\Database\QueryBuilding;

use Ezegyfa\LaravelHelperMethods\StringMethods;

class OrQueryCondition
{
    public $expressions;

    public function __construct(array $expressions)
    {
        $this->expressions = $expressions;
    }

    public function getQuery()
    {
        return StringMethods::concatenateStrings($this->expressions, ' OR ')
    }
}