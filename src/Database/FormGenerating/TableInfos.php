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
        $rowValues = $this->getRowValues($id);
        return array_map(function ($columnInfos) use($labelPrefix, $withOldValues, $rowValues) {
            return $columnInfos->getFormInfos($labelPrefix, $withOldValues, $rowValues[$columnInfos->name]);
        }, array_values($this->getRelationReplacedColumnInfos()));
    }

    public function getRowValues($id) {
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

    public function getDataResponse(int $rowToShowCount, int $selectedPageNumber, array $filters, $translationPrefix = '') {
        $columnNames = $this->getColumnNamesWithRelatedTableName();
        $rows = $this->getData($rowToShowCount, $selectedPageNumber, $filters);
        $totalRowCount = $this->getFilterDataQuery($filters)->count();
        return response()->json((object) [
            'total_row_count' => $totalRowCount,
            'column_names' => $columnNames,
            'rows' => $rows,
            'filter_sections' => $this->getFilterFormInfos($translationPrefix)
        ]);
    }

    public function getFullData(int $rowToShowCount, int $selectedPageNumber, array $filters) {
        $columnNames = $this->getColumnNamesWithRelatedTableName();
        return $this->getDataQuery($selectedPageNumber, $rowToShowCount, $columnNames, $filters)->get()->toArray();
    }

    public function getFullColumnNames() {
        $columnNames = [];
        foreach ($this->getRelationReplacedColumnInfos() as $columnInfos) {
            if ($columnInfo instanceof RelationColumnInfos) {
                //$relatedTableName 
            }
            else {
                array_push($columnNames, $this->name . '.' . $columnInfo->name);
            }
        }
        return array_values(array_map(function($columnInfo) {
            
        }, $this->getRelationReplacedColumnInfos()));
    }

    public function getRawData(?int $rowToShowCount = null, ?int $selectedPageNumber = null, array $filters = []) {
        $columnNames = $this->getColumnNamesWithTableName();
        return $this->getDataQuery($selectedPageNumber, $rowToShowCount, $columnNames, $filters)->get()->toArray();
    }

    public function getData(?int $rowToShowCount = null, ?int $selectedPageNumber = null, array $filters = []) {
        $columnNames = $this->getColumnNamesWithRelatedTableName();
        return $this->getDataQuery($selectedPageNumber, $rowToShowCount, $columnNames, $filters)->get()->toArray();
    }

    public function getColumnNamesWithRelatedTableName() {
        return array_values(array_map(function($columnInfo) {
            if ($columnInfo instanceof RelationColumnInfos) {
                return $columnInfo->getRenderSelect();
            }
            else {
                return $this->name . '.' . $columnInfo->name;
            }
        }, $this->getRelationReplacedColumnInfos()));
    }

    public function getColumnNamesWithTableName() {
        return array_values(array_map(function($columnInfo) {
            return $this->name . '.' . $columnInfo->name;
        }, $this->columnInfos));
    }

    public function getDataQuery(?int $selectedPageNumber, ?int $rowToShowCount, array $columnNames, array $filters) {
        $query = $this->getFilterDataQuery($filters)
            ->select(array_map(function($columnName) {
                return \DB::raw($columnName);
            }, $columnNames));
        if ($rowToShowCount) {
            $query = $query->limit($rowToShowCount);
            if ($selectedPageNumber) {
                $query = $query->offset(($selectedPageNumber - 1) * $rowToShowCount);
            }
        }
        return $query;
    }

    public function getFilterDataQuery(array $filters) {
        $query = \DB::table($this->name);
        foreach ($this->relationInfos as $relationInfo) {
            $relationInfo->addJoinToQuery($this->name, $query);
        }
        foreach ($this->getRelationReplacedColumnInfos() as $columnInfo) {
            $columnInfo->addFilterToQuery($this->name, $query, $filters);
        }
        return $query;
    }

    public function getFilterFormInfos($translationPrefix = '') {
        $filterFormInfos = array_map(function ($columnInfos) use($translationPrefix) {
            $columnRelation = $this->getColumnRelation($columnInfos);
            if ($columnRelation) {
                return $columnRelation->getFilterFormInfos($translationPrefix);
            }
            else {
                return $columnInfos->getFilterFormInfos($translationPrefix);
            }
        }, array_values($this->columnInfos));
        return $filterFormInfos;
    }

    public function getRelationReplacedColumnInfos() {
        return array_map(function ($columnInfos) {
            $columnRelation = $this->getColumnRelation($columnInfos);
            if ($columnRelation) {
                return $columnRelation;
            }
            else {
                return $columnInfos;
            }
        }, $this->columnInfos);
    }

    public function isCheckBox($columnName) {
        if (array_key_exists($columnName, $this->columnInfos)) {
            $columnInfo = $this->columnInfos[$columnName];
            return $columnInfo instanceof SimpleColumnInfos && $columnInfo->isCheckBox();
        }
        else {
            return false;
        }
    }

    public function isDatetime($columnName) {
        if (array_key_exists($columnName, $this->columnInfos)) {
            $columnInfo = $this->columnInfos[$columnName];
            return $columnInfo instanceof SimpleColumnInfos && $columnInfo->isDatetime();
        }
        else {
            return false;
        }
    }

    public function getNameInUrlFormat() {
        return str_replace('_', '-', $this->name);
    }

    public function getNameInNormalFormat() {
        return str_replace('_', ' ', $this->name);
    }

    public function createFakeData(int $dataCount) {
        for ($i = 0; $i < $dataCount; ++$i) {
            $fakeData = array_map(function($columnInfos) {
                return $columnInfos->getFakeValue();
            }, $this->getRelationReplacedColumnInfos());
            \DB::table($this->name)->insert($fakeData);
        }
    }

    public function setColumnInfos(array $columnNames) {
        $this->columnInfos = array_filter($this->columnInfos, function($columnInfo) use($columnNames) {
            return in_array($columnInfo->name, $columnNames);
        });
        $this->relationInfos = array_filter($this->relationInfos, function($relationInfo) use($columnNames) {
            return in_array($relationInfo->referenceColumnName, $columnNames);
        });
    }

    public function getRelationColumnNames() {
        return array_map(function ($relationInfo) {
            return $relationInfo->referenceColumnName;
        }, $this->relationInfos);
    }
}
