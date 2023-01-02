<?php

namespace Ezegyfa\LaravelHelperMethods\Database;

use Ezegyfa\LaravelHelperMethods\Database\FormGenerating\DatabaseInfos;
use Ezegyfa\LaravelHelperMethods\Database\FormGenerating\SimpleColumnInfos;
use Ezegyfa\LaravelHelperMethods\StringMethods;

class HelperTableMethods
{
    public static function createHelperTable(array $baseTableNames, string $resultTableName) {
        \DB::statement(static::getCreateTableQuery($baseTableNames, $resultTableName));
        dd('asd');
        foreach ($baseTableNames as $baseTableName) {
            \DB::statement(static::getCreateTriggerQuery($baseTableName, $resultTableName));
        }
    }

    protected static function getCreateTableQuery(array $baseTableNames, string $resultTableName) {
        $query = 'CREATE TABLE ' . $resultTableName . '(id INT NOT NULL AUTO_INCREMENT, ';
        $columnParts = array_map(function($columnInfo) {
            return $columnInfo->name . ' ' . $columnInfo->type;
        }, static::getColumnInfos($baseTableNames));
        $query .= StringMethods::concatenateStrings($columnParts, ', ');
        $query .= ', PRIMARY KEY (`id`))';
        return $query;
    }

    protected static function getColumnInfos(array $baseTableNames) {
        $tableInfos = DatabaseInfos::getTableInfos();
        $helperColumnInfos = [];
        foreach ($baseTableNames as $baseTableName) {
            foreach ($tableInfos[$baseTableName]->columnInfos as $columnInfo) {
                $columnInfo->name = $baseTableName . '__' . $columnInfo->name;
                array_push($helperColumnInfos, $columnInfo);
            }
            $idColumnInfo = new SimpleColumnInfos(
                $baseTableName . '__id',
                'INT',
                'NO'
            );
            array_push($helperColumnInfos, $idColumnInfo);
        }
        return $helperColumnInfos;
    }

    protected static function getCreateTriggerQuery(string $baseTableName, string $resultTableName) {
        $triggerName = $resultTableName . '__' . $baseTableName . '_insert';
        return 'DELIMITER $$'
            . ' CREATE TRIGGER ' . $triggerName
            . ' AFTER INSERT'
            . ' ON ' . $baseTableName . ' FOR EACH ROW'
            . ' BEGIN'
            . ' INSERT INTO test(product_details__stock) VALUES(3);'
            . ' END$$'
            . ' DELIMITER ;';
    }
}
