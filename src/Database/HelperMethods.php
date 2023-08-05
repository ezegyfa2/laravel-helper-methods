<?php

namespace Ezegyfa\LaravelHelperMethods\Database;

use Ezegyfa\LaravelHelperMethods\Database\FormGenerating\DatabaseInfos;
use Ezegyfa\LaravelHelperMethods\Database\FormGenerating\SimpleColumnInfos;
use Ezegyfa\LaravelHelperMethods\StringMethods;

class HelperMethods
{
    public static function getRandomId(string $tableName) {
        $sql = 'SELECT id FROM ' . $tableName . ' ORDER BY RAND () LIMIT 1';
        return \DB::select($sql)[0]->id;
    }
}