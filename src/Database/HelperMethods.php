<?php

namespace Ezegyfa\LaravelHelperMethods\Database;

use Ezegyfa\LaravelHelperMethods\Database\FormGenerating\DatabaseInfos;
use Ezegyfa\LaravelHelperMethods\Database\FormGenerating\SimpleColumnInfos;
use Ezegyfa\LaravelHelperMethods\StringMethods;

class HelperMethods
{
    public static function getRandomId(string $tableName) {
        $sql = 'SELECT id FROM ' . $tableName . ' ORDER BY RAND () LIMIT 1';
        $result = \DB::select($sql);
        if (count($result) == 0) {
            return null;
        }
        else {
            return \DB::select($sql)[0]->id;
        }
    }

    public static function getSql($query) {
        return vsprintf(str_replace('?', '%s', $query->toSql()), collect($query->getBindings())->map(function ($binding) {
            return is_numeric($binding) ? $binding : "'{$binding}'";
        })->toArray());
    }

    public static function getShortStringQuery(string $queryExpression, int $maxLength = 30) {
        return 'IF(LENGTH(' . $queryExpression . ') > ' . $maxLength . ', CONCAT(SUBSTRING(' . $queryExpression . ', 1, 30), \'...\'), ' . $queryExpression . ')';
    }
}
