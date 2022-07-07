<?php

namespace Ezegyfa\LaravelHelperMethods\Database\FormGenerating;

use Exception;
use Illuminate\Support\Facades\DB;

class DatabaseInfos {
    public const IGNORED_COLUMN_NAMES = [
        'id',
        'created_at',
        'updated_at'
    ];

    public static function getTableInfos() {
        $tableInfos = static::getTableInfosWithoutRelations();
        $rawRelationInfos = DB::select('SELECT for_name, ref_name, for_col_name, ref_col_name FROM INFORMATION_SCHEMA.INNODB_SYS_FOREIGN INNER JOIN INFORMATION_SCHEMA.INNODB_SYS_FOREIGN_COLS ON INNODB_SYS_FOREIGN.ID = INNODB_SYS_FOREIGN_COLS.ID');
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
            $referenceTableName = last(explode('/', $rawRelationInfo->for_name));
            $relation = new RelationInfos(
                $tableInfos[$referenceTableName],
                $tableInfos[$referenceTableName]->columnInfos[$rawRelationInfo->for_col_name],
                $tableInfos[$referenceTableName]->columnInfos[$rawRelationInfo->ref_col_name]
            );
            $tableInfos[$referenceTableName]->relations[] = $relation;
        }
        return $tableInfos;
    }

    public static function getTableInfosWithoutRelations() {
        $rawTableInfos = DB::select('SELECT table_name, column_name, column_type, is_nullable, column_default, character_maximum_length, numeric_precision FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = "' . \DB::connection()->getDatabaseName() . '"');
        $uniques = static::getUniques();
        $tableInfos = [];
        foreach ($rawTableInfos as $rawTableInfo) {
            if (!isset($tableInfos[$rawTableInfo->table_name])) {
                $tableInfos[$rawTableInfo->table_name] = new TableInfos($rawTableInfo->table_name, [], [], $uniques[$rawTableInfo->table_name] ?? []);
            }
            if (!in_array($rawTableInfo->column_name, static::IGNORED_COLUMN_NAMES)) {
                $tableInfos[$rawTableInfo->table_name]->columnInfos[$rawTableInfo->column_name] =
                    new ColumnInfos(
                        $rawTableInfo->column_name,
                        $rawTableInfo->column_type,
                        $rawTableInfo->is_nullable,
                        $rawTableInfo->column_default
                    );
            }
        }
        return $tableInfos;
    }

    public static function getUniques() {
        $uniqueRaws = DB::select('SELECT constraint_name, table_name, column_name FROM information_schema.KEY_COLUMN_USAGE WHERE table_schema = "' . \DB::connection()->getDatabaseName() . '";');
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
