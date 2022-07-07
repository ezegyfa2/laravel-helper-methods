<?php

namespace Ezegyfa\LaravelHelperMethods\Database\FormGenerating;

class TableViewValues {
    protected static $viewColumnNames = [
        'contact_us' => [ 'email', 'name' ],
    ];

    public static function getTableHyphenViewValues($tableName) {
        $viewData = static::getTableViewData($tableName);
        return array_map(function($rowData) {
            return [
                'id' => $rowData->id,
                'view_value' => static::getRowViewValue($rowData),
            ];
        }, $viewData);
    }

    public static function getTableViewData($tableName) {
        $columnsToSelect = static::$viewColumnNames[$tableName];
        $columnsToSelect[] = 'id';
        return \DB::table($tableName)->select($columnsToSelect)->get()->toArray();
    }

    public static function getRowViewValue($rowData) {
        $rowViewValue = '';
        $rowData = (array)$rowData;
        foreach (array_keys($rowData) as $columnName) {
            if ($columnName != 'id') {
                $rowViewValue .= $rowData[$columnName] . ' - ';
            }
        }
        return substr($rowViewValue, 0, strlen($rowViewValue) - 3);
    }
}
