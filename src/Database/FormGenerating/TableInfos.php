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

    public function getFormInfos($labelPrefix = '', $withOldValues = true) {
        return array_map(function ($columnInfos) use($labelPrefix, $withOldValues) {
            $columnRelation = $this->getColumnRelation($columnInfos);
            if ($columnRelation) {
                return $columnRelation->getFormInfos($labelPrefix, $withOldValues);
            }
            else {
                return $columnInfos->getFormInfos($labelPrefix, $withOldValues);
            }
        }, array_values($this->columnInfos));
    }

    public function getColumnRelation($columnInfos) {
        foreach ($this->relationInfos as $relationInfo) {
            if ($relationInfo->columnInfos == $columnInfos) {
                return $relationInfo;
            }
        }
        return null;
    }

    public function getValidators() {
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
            $validators[$uniqueColumnNames[0]][] = $this->getUniqueValidatorRule($uniqueColumnNames);
        }
        return $validators;
    }

    public function getUniqueValidatorRule($uniqueColumnNames) {
        $rule = 'unique:' . $this->name;
        foreach ($uniqueColumnNames as $columnName) {
            if ($columnName == $uniqueColumnNames[0]) {
                $rule .= ',' . $columnName . ',null,id';
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
}
