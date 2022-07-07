<?php

namespace Ezegyfa\LaravelHelperMethods\Database;

class MigrationMethods
{
    public static function maxConstraint($tableName, $columnName, $max) {
        static::constraint($tableName, $columnName, $tableName . ' <= ' . $max);
    }

    public static function minConstraint($tableName, $columnName, $min) {
        static::constraint($tableName, $columnName, $tableName . ' >= ' . $min);
    }

    public static function constraint($tableName, $columnName, $checkingExpression) {
        \Log::debug('ALTER TABLE ' . $tableName . ' ADD CONSTRAINT ' . $columnName . ' CHECK (' . $checkingExpression . ');');
        \DB::statement('ALTER TABLE ' . $tableName . ' ADD CONSTRAINT ' . $columnName . ' CHECK (' . $checkingExpression . ');');
    }
}
