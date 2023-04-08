<?php

namespace Ezegyfa\LaravelHelperMethods\Database\FormGenerating;

use Exception;
use Illuminate\Support\Facades\DB;
use Ezegyfa\LaravelHelperMethods\Database\HelperTableMethods;

class DatabaseInfos {
    public const DEFAULT_IGNORED_COLUMN_NAMES = [
        'id',
        'created_at',
        'updated_at'
    ];

    // In the filterName we can set what columns will be render in the current page (eg: index, edit etc.)
    public static function getSpecificTableInfos(string $tableName, string $filterName = '-', array $ignoredColumnNames = [ 'id', 'created_at', 'updated_at' ]) {
        $tableInfos = static::getTableInfos($ignoredColumnNames)[$tableName];
        $renderColumnNames = static::getConfigGlobalFilterColumnNames($tableName);
        $renderColumnNames = array_merge($renderColumnNames, static::getConfigFilterColumnNames($tableName, $filterName));
        $renderColumnNames = array_merge($renderColumnNames, static::getConfigSpecificFilterColumnNames($tableName, $filterName));
        if (count($renderColumnNames) == 0) {
            $exceptionColumnNames = static::getConfigGlobalExceptionColumnNames();
            $exceptionColumnNames = array_merge($exceptionColumnNames, static::getConfigExceptionColumnNames($tableName, $filterName));
            $exceptionColumnNames = array_merge($exceptionColumnNames, static::getConfigSpecificExceptionColumnNames($tableName, $filterName));
            $tableInfos->columnInfos = array_filter($tableInfos->columnInfos, function($columnInfo) use($exceptionColumnNames) {
                return !in_array($columnInfo->name, $exceptionColumnNames);
            });
            $tableInfos->relationInfos = array_filter($tableInfos->relationInfos, function($relationInfo) use($exceptionColumnNames) {
                return !in_array($relationInfo->referenceColumnName, $exceptionColumnNames);
            });
        }
        else {
            $tableInfos->columnInfos = array_filter($tableInfos->columnInfos, function($columnInfo) use($renderColumnNames) {
                return in_array($columnInfo->name, $renderColumnNames);
            });
            $tableInfos->relationInfos = array_filter($tableInfos->relationInfos, function($relationInfo) use($renderColumnNames) {
                return in_array($relationInfo->referenceColumnName, $renderColumnNames);
            });
        }
        return $tableInfos;
    }

    public static function getConfigGlobalFilterColumnNames() {
        $tableConfigs = \Config::get('database.admin');
        if (
            $tableConfigs
            && array_key_exists('filterColumnNames', $tableConfigs)
        ) {
            return $tableConfigs['filterColumnNames'];
        }
        else {
            return [];
        }
    }

    public static function getConfigGlobalExceptionColumnNames() {
        $tableConfigs = \Config::get('database.admin');
        if (
            $tableConfigs
            && array_key_exists('filterExceptionColumnNames', $tableConfigs)
        ) {
            return $tableConfigs['filterExceptionColumnNames'];
        }
        else {
            return [];
        }
    }

    public static function getConfigFilterColumnNames(string $tableName) {
        $tableConfigs = \Config::get('database.admin');
        if (
            $tableConfigs
            && array_key_exists($tableName, $tableConfigs)
            && array_key_exists('filterColumnNames', $tableConfigs[$tableName])
        ) {
            return $tableConfigs[$tableName]['filterColumnNames'];
        }
        else {
            return [];
        }
    }

    public static function getConfigSpecificFilterColumnNames(string $tableName, string $filterName = '-') {
        $tableConfigs = \Config::get('database.admin');
        $fullFilterName = $filterName . 'FilterColumnNames';
        if (
            $tableConfigs
            && array_key_exists($tableName, $tableConfigs)
            && array_key_exists($fullFilterName, $tableConfigs[$tableName])
        ) {
            return $tableConfigs[$tableName][$fullFilterName];
        }
        else {
            return [];
        }
    }

    public static function getConfigExceptionColumnNames(string $tableName) {
        $tableConfigs = \Config::get('database.admin');
        if (
            $tableConfigs
            && array_key_exists($tableName, $tableConfigs)
            && array_key_exists('filterExceptionColumnNames', $tableConfigs[$tableName])
        ) {
            return $tableConfigs[$tableName]['filterExceptionColumnNames'];
        }
        else {
            return [];
        }
    }

    public static function getConfigSpecificExceptionColumnNames(string $tableName, string $filterName = '-') {
        $tableConfigs = \Config::get('database.admin');
        $fullFilterName = $filterName . 'FilterExceptionColumnNames';
        if (
            $tableConfigs
            && array_key_exists($tableName, $tableConfigs)
            && array_key_exists($fullFilterName, $tableConfigs[$tableName])
        ) {
            return $tableConfigs[$tableName][$fullFilterName];
        }
        else {
            return [];
        }
    }

    public static function getTableInfos(array $ignoredColumnNames = [ 'id', 'created_at', 'updated_at' ]) {
        $tableInfos = static::getTableInfosWithoutRelations($ignoredColumnNames);
        $query = 'SELECT for_name, ref_name, for_col_name, ref_col_name '
            . 'FROM INFORMATION_SCHEMA.INNODB_FOREIGN '
            . 'INNER JOIN INFORMATION_SCHEMA.INNODB_FOREIGN_COLS '
            . 'ON INNODB_FOREIGN.ID = INNODB_FOREIGN_COLS.ID';
        $rawRelationInfos = HelperTableMethods::select($query);
        $rawRelationInfos = array_filter($rawRelationInfos, function($rawRelationInfo) {
            $referenceTableNameParts = explode('/', $rawRelationInfo->ref_name);
            if (count($referenceTableNameParts) > 0) {
                $referenceTableSchemaName = explode('/', $rawRelationInfo->ref_name)[0];
                return $referenceTableSchemaName == DB::connection()->getDatabaseName();
            }
            else {
                throw new Exception('Invalid table name');
            }
        });
        foreach ($rawRelationInfos as $rawRelationInfo) {
            $tableName = last(explode('/', $rawRelationInfo->for_name));
            $referenceTableName = last(explode('/', $rawRelationInfo->ref_name));
            $relationInfo = new RelationColumnInfos(
                $tableInfos[$referenceTableName],
                $tableInfos[$tableName],
                $rawRelationInfo->for_col_name
            );

            $tableInfos[$tableName]->relationInfos[] = $relationInfo;
        }
        return $tableInfos;
    }

    public static function getCrmTableNames() {
        return array_filter(DatabaseInfos::getTableNames(), function($tableName) {
            return in_array($tableName, static::getConfigTableNames());
        });
    }

    public static function getConfigTableNames() {
        if (\Config::get('database.admin')) {
            return array_keys(\Config::get('database.admin'));
        }
        else {
            return [];
        }
    }

    public static function getTableNames() {
        return array_map(function($tableInfo) {
            return $tableInfo->name;
        }, static::getTableInfosWithoutRelations());
    }

    public static function getTableInfosWithoutRelations(array $ignoredColumnNames = [ 'id', 'created_at', 'updated_at' ]) {
        $query = 'SELECT table_name, column_name, column_type, is_nullable, column_default, character_maximum_length, numeric_precision '
            . 'FROM INFORMATION_SCHEMA.COLUMNS '
            . 'WHERE TABLE_SCHEMA = "' . \DB::connection()->getDatabaseName() . '"'
            . 'ORDER BY ordinal_position';
        $rawTableInfos = HelperTableMethods::select($query);
        $uniques = static::getUniques();
        $tableInfos = [];
        foreach ($rawTableInfos as $rawTableInfo) {
            if (!isset($tableInfos[$rawTableInfo->table_name])) {
                $tableInfos[$rawTableInfo->table_name] = new TableInfos($rawTableInfo->table_name, [], [], $uniques[$rawTableInfo->table_name] ?? []);
            }
            if (!in_array($rawTableInfo->column_name, $ignoredColumnNames)) {
                $tableInfos[$rawTableInfo->table_name]->columnInfos[$rawTableInfo->column_name] =
                    new SimpleColumnInfos(
                        $rawTableInfo->column_name,
                        static::getColumnType($rawTableInfo),
                        $rawTableInfo->is_nullable,
                        $rawTableInfo->column_default
                    );
            }
        }
        return $tableInfos;
    }

    public static function getColumnType($rawTableInfo) {
        if (str_contains($rawTableInfo->column_type, '(')) {
            return $rawTableInfo->column_type;
        }
        else if ($rawTableInfo->character_maximum_length) {
            return $rawTableInfo->column_type . '(' . $rawTableInfo->character_maximum_length . ')';
        }
        else if ($rawTableInfo->numeric_precision) {
            return $rawTableInfo->column_type . '(' . $rawTableInfo->numeric_precision . ')';
        }
        else {
            return $rawTableInfo->column_type;
        }
    }

    public static function getUniques() {
        $uniqueKeysQuery = 'SELECT KEY_COLUMN_USAGE.constraint_name, KEY_COLUMN_USAGE.table_name, KEY_COLUMN_USAGE.column_name '
            . 'FROM information_schema.KEY_COLUMN_USAGE '
            . 'INNER JOIN information_schema.TABLE_CONSTRAINTS '
            . 'ON KEY_COLUMN_USAGE.table_name = TABLE_CONSTRAINTS.table_name '
            . 'AND KEY_COLUMN_USAGE.constraint_name = TABLE_CONSTRAINTS.constraint_name '
            . 'AND KEY_COLUMN_USAGE.constraint_schema = TABLE_CONSTRAINTS.table_schema '
            . 'WHERE KEY_COLUMN_USAGE.table_schema = "' . \DB::connection()->getDatabaseName() . '" '
            . 'AND TABLE_CONSTRAINTS.constraint_type = "UNIQUE";';
        $uniqueRaws = HelperTableMethods::select($uniqueKeysQuery);
        $uniques = [];
        foreach ($uniqueRaws as $uniqueRaw) {
            if (!array_key_exists($uniqueRaw->table_name, $uniques)) {
                $uniques[$uniqueRaw->table_name] = [];
            }
            if (!array_key_exists($uniqueRaw->constraint_name, $uniques[$uniqueRaw->table_name])) {
                $uniques[$uniqueRaw->table_name][$uniqueRaw->constraint_name] = [];
            }
            $uniques[$uniqueRaw->table_name][$uniqueRaw->constraint_name][] = $uniqueRaw->column_name;
        }
        return $uniques;
    }
}
