<?php

namespace Ezegyfa\LaravelHelperMethods\Database\FormGenerating;

use Ezegyfa\LaravelHelperMethods\HttpMethods;

class TableInfos {
    public $name;
    public $columnInfos;
    public $relationInfos;
    // Attach to TableInfos becouse can be unique with multiple column
    public $uniques;

    public function __construct(String $name, Array $columnInfos = [], Array $relationInfos = [], Array $uniques = []) {
        $this->name = $name;
        $this->columnInfos = $columnInfos;
        $this->relationInfos = $relationInfos;
        $this->uniques = $uniques;
        $this->setValidationErrors(HttpMethods::getValidationErrors());
    }

    public function getFormInfos(string $labelPrefix = '', int $id = -1) {
        return $this->getFormInfosWithoutErrorChecking($labelPrefix, HttpMethods::hasValidationError(), $id);
    }

    public function getFormInfosWithoutErrorChecking(string $labelPrefix = '', bool $withOldValues = true, int $id = -1) {
        $columnValues = $this->getColumnValues($id);
        return array_map(function ($columnInfos) use($labelPrefix, $withOldValues, $columnValues) {
            $columnRelation = $this->getColumnRelation($columnInfos);
            if ($columnRelation) {
                return $columnRelation->getFormInfos($labelPrefix, $withOldValues, $columnValues[$columnInfos->name]);
            }
            else {
                return $columnInfos->getFormInfos($labelPrefix, $withOldValues, $columnValues[$columnInfos->name]);
            }
        }, array_values($this->columnInfos));
    }

    public function getColumnValues($id) {
        if ($id == -1) {
            $nullRow = [];
            foreach ($this->getColumnNames() as $columnName) {
                $nullRow[$columnName] = null;
            }
            return $nullRow;
        }
        else {
            return (array)\DB::table($this->name)->where('id', $id)->first();
        }
    }

    public function getColumnRelation($columnInfos) {
        foreach ($this->relationInfos as $relationInfo) {
            if ($relationInfo->referenceColumnName == $columnInfos->name) {
                return $relationInfo;
            }
        }
        return null;
    }

    public function getValidators($id = 'null') {
        $validators = array_map(function ($columnInfos) {
            $columnRelation = $this->getColumnRelation($columnInfos);
            if ($columnRelation) {
                return $columnRelation->getValidator();
            }
            else {
                return $columnInfos->getValidator();
            }
        }, $this->columnInfos);
        foreach ($this->uniques as $uniqueColumnNames) {
            $validators[$uniqueColumnNames[0]][] = $this->getUniqueValidatorRule($uniqueColumnNames, $id);
        }
        return $validators;
    }

    public function getUniqueValidatorRule($uniqueColumnNames, $id = 'null') {
        $rule = 'unique:' . $this->name;
        foreach ($uniqueColumnNames as $columnName) {
            if ($columnName == $uniqueColumnNames[0]) {
                $rule .= ',' . $columnName . ',' . $id . ',id';
            }
            else {
                $rule .= ',' . $columnName . ',' . request()->get($columnName);
            }
        }
        return $rule;
    }

    public function setValidationErrors($validationErrors) {
        foreach ($validationErrors as $inputName => $inputErrors) {
            if (array_key_exists($inputName, $this->columnInfos)) {
                $this->columnInfos[$inputName]->validationErrors = $inputErrors;
            }
        }
    }

    public function filterData(Array $data) {
        $columnNames = array_keys($this->columnInfos);
        return array_filter($data, function($key) use($columnNames) {
            return in_array($key, $columnNames);
        }, ARRAY_FILTER_USE_KEY);
    }

    public function getColumnNames() {
        return array_values(array_map(function($columnInfo) {
            return $columnInfo->name;
        }, $this->columnInfos));
    }

    public function getColumnNamesWithTableName() {
        return array_values(array_map(function($columnInfo) {
            return $this->name . '.' . $columnInfo->name;
        }, $this->columnInfos));
    }
}
