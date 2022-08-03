<?php

namespace Ezegyfa\LaravelHelperMethods\Database\QueryBuilding;

use Ezegyfa\LaravelHelperMethods\StringMethods;

class Query
{
    public $select;
    public $from;
    public $where;
    public $groupBy;
    public $having;
    public $orderBy;
    public $limit;

    public function __construct(string $from, array $selectItems = [ '*' ], array $whereItems = [], array $groupByItems = [], 
        array $havingItems = [], array $orderByItems = [], int $limit = 0)
    {
        $this->from = $from;
        $this->selectItems = $selectItems;
        $this->whereItems = $whereItems;
        $this->groupByItems = $groupByItems;
        $this->havingItems = $havingItems;
        $this->orderByItems = $orderByItems;
        $this->limit = $limit;
    }

    public function getQuery()
    {
        return $query = $this->getSelect() . ' ' 
            . 'FROM ' . $this->from 
            . $this->getWhere()
            . $this->getGroupBy()
            . $this->getHaving()
            . $this->getOrderBy() 
            . $this->getLimit() . ';';
    }

    public function getSelect()
    {
        return 'SELECT ' . StringMethods::concatenateStrings($this->selectItems, ', ');
    }

    public function getWhere()
    {
        return $this->getConditionListQueryPart($this->whereItems, ' WHERE');
    }

    public function getGroupBy()
    {
        return $this->getParameterListQueryPart($this->groupByItems, ' GROUP BY');
    }

    public function getHaving()
    {
        return $this->getConditionListQueryPart($this->havingItems, ' HAVING');
    }

    public function getOrderBy()
    {
        return $this->getParameterListQueryPart($this->orderByItems, ' ORDER BY');
    }

    public function getLimit()
    {
        if ($this->limit > 0) {
            return ' LIMIT ' . $this->limit;
        }
        else {
            return '';
        }
    }

    public function getParameterListQueryPart(array $queryItems, string $querySufix) {
        if (count($queryItems) > 0) {
            return $querySufix . ' ' . StringMethods::concatenateStrings($queryItems, ', ');
        }
        else {
            return '';
        }
    }

    public function getConditionListQueryPart(array $queryItems, string $querySufix) {
        if (count($queryItems) > 0) {
            $conditionList = array_map(function($parameter) {
                if (gettype($parameter) == 'object' && method_exists($parameter, 'getQuery')) {
                    return $parameter->getQuery();
                }
                else {
                    return $parameter;
                }
            }, $queryItems);
            return $querySufix . ' ' . StringMethods::concatenateStrings($conditionList, ' AND ');
        }
        else {
            return '';
        }
    }
}
