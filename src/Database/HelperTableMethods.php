<?php

namespace Ezegyfa\LaravelHelperMethods\Database;

use Ezegyfa\LaravelHelperMethods\Database\FormGenerating\DatabaseInfos;
use Ezegyfa\LaravelHelperMethods\Database\FormGenerating\SimpleColumnInfos;
use Ezegyfa\LaravelHelperMethods\StringMethods;

class HelperTableMethods
{
    public static function createHelperTable(array $baseTableNames, string $resultTableName) {
        \DB::statement(static::getCreateTableQuery($baseTableNames, $resultTableName));
        //dd(static::getCreateInsertTriggerQuery($baseTableNames, $baseTableNames[0], $resultTableName));
        foreach ($baseTableNames as $baseTableName) {
            \DB::statement(static::getCreateInsertTriggerQuery($baseTableNames, $baseTableName, $resultTableName));
        }
    }

    protected static function getCreateTableQuery(array $baseTableNames, string $resultTableName) {
        $query = 'CREATE TABLE ' . $resultTableName . '(id INT NOT NULL AUTO_INCREMENT, ';
        $columnParts = array_map(function($columnInfo) {
            return $columnInfo->name . ' ' . $columnInfo->type;
        }, static::getColumnInfos($baseTableNames));
        $query .= StringMethods::concatenateStrings($columnParts, ', ');
        $query .= ', PRIMARY KEY (id))';
        return $query;
    }

    protected static function getCreateInsertTriggerQuery(array $baseTableNames, string $triggerTableName, string $resultTableName) {
        $triggerName = $resultTableName . '__' . $triggerTableName . '_insert';
        $columnNames = StringMethods::concatenateStrings(static::getColumnNames($baseTableNames), ', ');
        $columnValues = StringMethods::concatenateStrings(static::getColumnValues($baseTableNames, $triggerTableName), ', ');
        return 'CREATE TRIGGER ' . $triggerName
            . ' AFTER INSERT'
            . ' ON ' . $triggerTableName . ' FOR EACH ROW'
            . ' BEGIN'
            . ' INSERT INTO test(' . $columnNames . ') VALUES(' . $columnValues . ');'
            . ' END;';
    }

    protected static function getColumnValues(array $baseTableNames, string $insertTableName) {
        $columnNames = static::getColumnNames($baseTableNames);
        $insertColumnNames = static::getColumnNames([ $insertTableName ]);
        return array_map(function($columnName) use($insertTableName, $insertColumnNames) {
            if (in_array($columnName, $insertColumnNames)) {
                $insertedColumnName = str_replace($insertTableName . '__', '', $columnName);
                return 'NEW.' . $insertedColumnName;
            }
            else {
                return 'null';
            }
        }, $columnNames);
    }

    protected static function getColumnNames(array $baseTableNames) {
        return array_map(function($columnInfo) {
            return $columnInfo->name;
        }, static::getColumnInfos($baseTableNames));
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

    public static function select(string $query) {
        $rows = \DB::select($query);
        return array_map(function($row) {
            $convertedRow = new \stdClass();
            foreach ($row as $key => $value) {
                $convertedKey = strtolower($key);
                $convertedRow->$convertedKey = $value;
            }
            return $convertedRow;
        }, $rows);
    }

    public static function addJoin($query, $referenceTableName, $columnName, $referenceColumnName, $joinType = 'inner') {
        $currentReferenceTableName = static::getFreeJoinTableName($query, $referenceTableName);
        $currentReferenceColumnName = static::replaceTableName($referenceColumnName, $currentReferenceTableName);
        switch ($joinType) {
            case 'inner':
                return $query->join($currentReferenceTableName, $columnName, $currentReferenceColumnName);
            case 'left':
                return $query->leftJoin($currentReferenceTableName, $columnName, $currentReferenceColumnName);
            case 'right':
                return $query->rightJoin($currentReferenceTableName, $columnName, $currentReferenceColumnName);
            case 'cross':
                return $query->crossJoin($currentReferenceTableName, $columnName, $currentReferenceColumnName);
        }
    }

    public static function getFreeJoinTableName($query, $tableName) {
        $tableNames = static::getTableNames($query);
        $currentTableName = $tableName;
        $helperIndex = 1;
        while (in_array($currentTableName, $tableNames)) {
            $currentTableName = $tableName . ' AS ' . $tableName . $helperIndex;
            ++$helperIndex;
        }
        return $currentTableName;
    }

    public static function replaceTableName($columnName, $tableName) {
        $columnNameParts = explode('.', $columnName);
        $currentTableName = explode(' AS ', $tableName)[0];
        if (count($columnNameParts) > 1) {
            return str_replace($columnNameParts[count($columnNameParts) - 2], $currentTableName, $columnName);
        }
        else {
            return $columnName;
        }
    }

    public static function getTableNames($query) {
        if ($query->joins) {
            $tableNames = array_map(function($join) {
                return $join->table;
            }, $query->joins);
            array_push($tableNames, $query->from);
            return $tableNames;
        }
        else {
            return [ $query->from ];
        }
    }
}