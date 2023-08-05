<?php

namespace Ezegyfa\LaravelHelperMethods\Database;

use Ezegyfa\LaravelHelperMethods\Database\FormGenerating\DatabaseInfos;
use Ezegyfa\LaravelHelperMethods\StringMethods;

class DataStructureMethods
{
    public static function getManyToOneRelationReplacedRows(array $rows, string $tableName, array $replaceRelationQueries) {
        $data = [];
        $data[$tableName] = $rows;
        foreach ($replaceRelationQueries as $relationTableNames => $replaceRelationQuery) {
            if (is_array($replaceRelationQuery) && count($replaceRelationQuery) == 2 && is_string($replaceRelationQuery[1])) {
                $replceRows = static::getManyToOneReplaceRows($rows, $relationTableNames, $replaceRelationQuery[1], $replaceRelationQuery[0]);
            }
            else {
                dd(static::getDefaultColumnNames($relationTableNames));
                $replceRows = static::getManyToOneReplaceRows(
                    $rows, 
                    $relationTableNames, 
                    static::getDefaultColumnNames($relationTableNames), 
                    $replaceRelationQuery
                );
            }
            foreach ($replceRows as $relationTableName => $relationRows) {
                if (array_key_exists($relationTableName, $data)) {
                    $data[$relationTableName] = static::mergeRows($data[$relationTableName], $relationRows);
                }
                else {
                    $data[$relationTableName] = $relationRows;
                }
            }
        }
        static::structureData($data);
        return $data[$tableName];
    }
    
    public static function getRelationReplacedRows(array $rows, string $tableName, array $replaceRelationQueries) {
        $data = [];
        $data[$tableName] = $rows;
        foreach ($replaceRelationQueries as $replaceRelationName => $replaceRelationQuery) {
            $replceRows = static::getReplaceRows($rows, $tableName, $replaceRelationName, $replaceRelationQuery);
            foreach ($replceRows as $relationTableName => $relationRows) {
                if (array_key_exists($relationTableName, $data)) {
                    $data[$relationTableName] = static::mergeRows($data[$relationTableName], $relationRows);
                }
                else {
                    $data[$relationTableName] = $relationRows;
                }
            }
        }
        static::structureData($data);
        return $data[$tableName];
    }

    public static function mergeRows(array ...$rowArrays) {
        $resultRows = [];
        foreach ($rowArrays as $rows) {
            foreach ($rows as $row) {
                $currentResultRow = static::getRowById($resultRows, $row->id);
                if ($currentResultRow == null) {
                    array_push($resultRows, $row);
                }
                else {
                    $resultRows[array_search($currentResultRow, $resultRows)] = (object)array_merge(get_object_vars($currentResultRow), get_object_vars($row));
                }
            }
        }
        return $resultRows;
    }

    public static function getRowById(array $rows, int $idToCheck) {
        foreach ($rows as $row) {
            if ($row->id) {
                if ($row->id == $idToCheck) {
                    return $row;
                }
            }
            else {
                throw new \Exception('Invalid rows ' . json_encode($row));
            }
        }
        return false;
    }

    public static function getManyToOneReplaceRows(array $rows, string $replaceRelationTableNames, string $replaceRelationColumnNames, array $replaceRelationQueries) {
        if (str_contains($replaceRelationTableNames, '.')) {
            $replaceRelationTableNameParts = explode('.', $replaceRelationTableNames);
            $relationTableName = $replaceRelationTableNameParts[0];
            $replaceRelationColumnNameParts = explode('.', $replaceRelationColumnNames);
            $relationColumnName = $replaceRelationColumnNameParts[0];
            $relationRows = static::getManyToOneReplaceRows($rows, $relationTableName, $relationColumnName, [ $relationColumnName ]);
            $relationRows = $relationRows[array_key_first($relationRows)];
            $subRelationColumnNames = str_replace($relationColumnName . '.', '', $replaceRelationColumnNames);
            $subRelationTableNames = str_replace($relationTableName . '.', '', $replaceRelationTableNames);
            return array_merge(
                [ $relationTableName => $relationRows ], 
                static::getManyToOneReplaceRows($relationRows, $subRelationTableNames, $subRelationColumnNames, $replaceRelationQueries)
            );
        }
        else {
            $rowIds = array_unique(array_map(function($row) {
                return $row->id;
            }, $rows));
            $filter = [
                $replaceRelationColumnNames => [
                    'name' => $replaceRelationColumnNames,
                    'values' => $rowIds
                ]
            ];
            return [ $replaceRelationTableNames => static::getTableInfos($replaceRelationTableNames, $replaceRelationQueries)->getRawData(null, null, $filter) ];
        }
    }

    public static function getReplaceRows(array $rows, string $tableName, string $replaceRelationColumnName, array $replaceRelationQueries) {
        if (str_contains($replaceRelationColumnName, '.')) {
            $replaceRelationColumnNameParts = explode('.', $replaceRelationColumnName);
            $relationColumnName = $replaceRelationColumnNameParts[0];
            $relationRows = static::getReplaceRows($rows, $tableName, $relationColumnName, [ $replaceRelationColumnNameParts[1] ]);
            $relationRows = $relationRows[array_key_first($relationRows)];
            $tableInfos = DatabaseInfos::getTableInfosByColumns($tableName, [ $relationColumnName ]);
            $relationTableName = $tableInfos->getRelationReplacedColumnInfos()[$relationColumnName]->getReferenceTableName();
            $replaceColumnName = str_replace($relationColumnName . '.', '', $replaceRelationColumnName);
            return array_merge(
                [ $relationTableName => $relationRows ], 
                static::getReplaceRows($relationRows, $relationTableName, $replaceColumnName, $replaceRelationQueries)
            );
        }
        else {
            $tableInfos = DatabaseInfos::getTableInfos([])[$tableName];
            $relationTableName = $tableInfos->getRelationReplacedColumnInfos()[$replaceRelationColumnName]->getReferenceTableName();
            $rowIds = array_unique(array_map(function($row) use($replaceRelationColumnName) {
                return $row->$replaceRelationColumnName;
            }, $rows));
            $filter = [
                'id' => [
                    'name' => 'id',
                    'values' => $rowIds
                ]
            ];
            return [ $relationTableName => static::getTableInfos($relationTableName, $replaceRelationQueries)->getRawData(null, null, $filter) ];
        }
    }

    public static function getTableInfos(string $tableName, array $columnNames) {
        if ($columnNames[0] == '*') {
            return DatabaseInfos::getTableInfos([])[$tableName];
        }
        else {
            $relationColumns = array_unique(array_merge($columnNames, [ 'id' ]));
            return DatabaseInfos::getTableInfosByColumns($tableName, $relationColumns);
        }
    }
    
    public static function getRelationTableName(string $tableName, string $replaceRelationColumnName) {
        $tableInfos = DatabaseInfos::getTableInfos([])[$tableName];
        if (str_contains($replaceRelationColumnName, '.')) {
            $replaceRelationColumnNameParts = explode('.', $replaceRelationColumnName);
            $relationTableName = $tableInfos->getRelationReplacedColumnInfos()[$replaceRelationColumnNameParts[0]]->getReferenceTableName();
            $replaceColumnName = str_replace($replaceRelationColumnNameParts[0] . '.', '', $replaceRelationColumnName);
            return static::getRelationTableName($relationTableName, $replaceColumnName);
        }
        else {
            return $tableInfos->getRelationReplacedColumnInfos()[$replaceRelationColumnName]->getReferenceTableName();
        }
    }

    public static function structureData(array $arraysToStructure) {
        foreach ($arraysToStructure as $tableName => $arrayToStructure) {
            foreach ($arrayToStructure as $itemToStrucutre) {
                static::structureItem($itemToStrucutre, $tableName, $arraysToStructure);
            }
        }
    }

    public static function structureItem(object $itemToStrucutre, string $tableName, array $arraysToStructure) {
        $tableInfos = DatabaseInfos::getTableInfos([])[$tableName];
        $relationFieldNames = $tableInfos->getRelationColumnNames();
        foreach ($relationFieldNames as $relationFieldName) {
            $relatedTableName = $tableInfos->getRelationReplacedColumnInfos()[$relationFieldName]->getReferenceTableName();
            $relatedTypeName = str_replace('_id', '', $relationFieldName);
            if (array_key_exists($relatedTableName, $arraysToStructure)) {
                $relationItem = static::getRelationItem($itemToStrucutre->$relationFieldName, $arraysToStructure[$relatedTableName]);
                if ($relationItem) {
                    $itemToStrucutre->$relatedTypeName = $relationItem;
                    if (property_exists($relationItem, $tableName)) {
                        $currentItem = static::getRelationItem($itemToStrucutre->id, $relationItem->$tableName);
                        if ($currentItem == null) {
                            array_push($relationItem->$tableName, $itemToStrucutre);
                        }
                        else if (count(get_object_vars($currentItem)) < count(get_object_vars($relationItem))) {
                            $relationItem->$tableName[array_search($currentItem, $relationItem->$tableName)] = $relationItem;
                        }
                    }
                    else {
                        $relationItem->$tableName = [ $itemToStrucutre ];
                    }
                }
            }
        }
    }

    public static function getDefaultColumnNames(string $tableNames) {
        $defaultColumnNames = array_map(function($tableName) {
            return \Str::singular($tableName) . '_id';
        }, explode('.', $tableNames));
        return StringMethods::concatenateStrings($defaultColumnNames, '.');
    }

    public static function getRelationItem(int $itemIdToSearch, array $relationItems) {
        if ($itemIdToSearch) {
            foreach ($relationItems as $relationItem) {
                if ($relationItem->id == $itemIdToSearch) {
                    return $relationItem;
                }
            }
            // No match found
            return null;
        }
        else {
            return null;
        }
    }
}
