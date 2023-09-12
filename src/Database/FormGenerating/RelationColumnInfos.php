<?php

namespace Ezegyfa\LaravelHelperMethods\Database\FormGenerating;

use Ezegyfa\LaravelHelperMethods\StringMethods;
use Ezegyfa\LaravelHelperMethods\Database\HelperTableMethods;
use Ezegyfa\LaravelHelperMethods\Database\HelperMethods;

class RelationColumnInfos extends ColumnInfos {
    public $referencedTableInfos;
    public $tableInfos;
    public $referenceColumnName;
    public $optionCreator;

    public function __construct($referencedTableInfos, $tableInfos, $referenceColumnName, $optionCreator = null) {
        parent::__construct(
            $tableInfos->name,
            $referenceColumnName,
            $tableInfos->columnInfos[$referenceColumnName]->type,
            $tableInfos->columnInfos[$referenceColumnName]->isNull,
            $tableInfos->columnInfos[$referenceColumnName]->default,
            $tableInfos->columnInfos[$referenceColumnName]->validationErrors
        );
        $this->referencedTableInfos = $referencedTableInfos;
        $this->tableInfos = $tableInfos;
        $this->referenceColumnName = $referenceColumnName;
        $this->optionCreator = $optionCreator;
    }

    public function getFormInfos(string $labelPrefix = '', $withOldValues = null, $value = null) {
        $formInfoData = parent::getFormInfos($labelPrefix, $withOldValues, $value);
        $formInfoData = $this->setOptions($formInfoData);
        return (object) [
            'type' => 'select',
            'data' => $formInfoData
        ];
    }

    public function getFilterFormInfos(string $translationPrefix = '') {
        $formInfoData = parent::getFilterFormInfos($translationPrefix);
        $formInfoData = $this->setFilterFormInfoValue($formInfoData, 'value');
        $formInfoData = $this->setOptions($formInfoData);
        return (object) [
            'type' => 'select',
            'data' => $formInfoData
        ];
    }

    public function setOptions($formInfoData) {
        if (\DB::table($this->getReferenceTableName())->count() > 20) {
            $formInfoData->options = [];
            $formInfoData->data_url = '/admin/' . $this->tableInfos->name .'/get-select-options';
            $formInfoData->data_infos = [
                'column-name' => $this->referenceColumnName,
                '_token' => csrf_token()
            ];
        }
        else {
            $formInfoData->options = $this->getOptions();
            array_push($formInfoData->options, (object) [
                'text' => 'No filter',
                'value' => 'no_filter',
            ]);
        }
        return $formInfoData;
    }

    public function getOptions(?string $searchedText = null) {
        $query = \DB::table($this->getReferenceTableName());
        if ($searchedText) {
            $query = $query->where(\DB::raw($this->getRenderSelect()), 'LIKE', \DB::raw('\'%' . $searchedText . '%\''));
        }
        if ($query->count() > 20) {
            return __('admin.index.too_many_result');
        }
        else {
            if ($this->optionCreator) {
                $rows = $query->select()->get();
                return array_map($this->optionCreator, $rows);
            }
            else {
                $renderColumnNames = $this->getRenderColumnNamesWithTableName($this->getReferenceTableName());
                array_push($renderColumnNames, $this->getReferenceTableName() . '.id');
                $query = $query->select($renderColumnNames);
                $rows = $query->select($renderColumnNames)->get()->toArray();
                return array_map(function($row) {
                    return (object) [
                        'text' => $this->getRowLabel($row, $this->getRenderColumnNames()),
                        'value' => $row->id,
                    ];
                }, $rows);
            }
        }
    }

    public function getRenderValues(int $selectedPageNumber, int $rowToShowCount) {
        $columnNames = $this->getRenderColumnNamesWithTableName($this->getReferenceTableName());
        $rows = $this->getJoinedQuery()
            ->select($columnNames)
            ->orderBy($this->tableInfos->name . '.id')
            ->limit($rowToShowCount)
            ->offset(($selectedPageNumber - 1) * $rowToShowCount)
            ->get()->toArray();
        return array_map(function($row) {
            return $this->getRowLabel($row, $this->getRenderColumnNames());
        }, $rows);
    }

    public function getJoinedQuery() {
        return \DB::table($this->tableInfos->name)
            ->leftJoin($this->getReferenceTableName(), $this->tableInfos->name . '.' . $this->referenceColumnName, $this->getReferenceTableName() . '.id');
    }

    public function getSelect() {
        return HelperMethods::getShortStringQuery($this->getRawRenderSelect()) . ' AS ' . $this->referenceColumnName;
    }

    public function getRenderSelect() {
        return $this->getRawRenderSelect() . ' AS ' . $this->referenceColumnName;
    }

    public function getRawRenderSelect() {
        return 'CONCAT(' . StringMethods::concatenateStrings($this->getRenderColumnNamesWithTableName($this->getReferenceTableName()), ', " - ", ') . ')';
    }

    public function getRenderColumnNamesWithTableName(string $tableName) {
        return array_map(function ($renderColumnName) use($tableName) {
            if (str_contains($renderColumnName, '.')) {
                return $renderColumnName;
            }
            else {
                return $tableName . '.' . $renderColumnName;
            }
        }, $this->getRenderColumnNames());
    }

    public function getRenderColumnNames() {
        $tableConfigs = \Config::get('database.admin');
        if (
            $tableConfigs
            && array_key_exists($this->getReferenceTableName(), $tableConfigs)
            && array_key_exists('renderColumnNames', $tableConfigs[$this->getReferenceTableName()])
        ) {
            return $tableConfigs[$this->getReferenceTableName()]['renderColumnNames'];
        }
        else {
            return $this->getDefaultRenderColumnNames();
        }
    }

    public function getDefaultRenderColumnNames() {
        $defaultRenderColumnNames = [
            'name',
            'email',
            'title',
        ];
        foreach ($defaultRenderColumnNames as $renderColumnName) {
            if (array_key_exists($renderColumnName, $this->referencedTableInfos->columnInfos)) {
                return [ $renderColumnName ];
            }
        }
        throw new \Exception('Can\'t render column ' . $this->referenceColumnName . '. Must set render column names in config.');
    }

    public function getValidator() {
        $validator = parent::getValidator();
        $validator[] = 'integer';
        $validator[] = 'exists:' . $this->getReferenceTableName() . ',id';
        return $validator;
    }

    public function getRowLabel($row, array $renderColumnNames) {
        $renderValues = array_map(function($renderColumnName) use($row) {
            //dd($row);
            return $row->$renderColumnName;
        }, $renderColumnNames);
        return StringMethods::concatenateStrings($renderValues, ' - ');
    }

    public function getReferenceColumnInfos() {
        return $this->tableInfos->columnInfos[$this->referenceColumnName];
    }

    public function getReferenceTableName() {
        return $this->referencedTableInfos->name;
    }

    public function getFakeValue() {
        return HelperMethods::getRandomId($this->getReferenceTableName());
    }

    public function addFilterToQuery($query, $filters) {
        if (array_key_exists($this->name, $filters) && array_key_exists('value', $filters[$this->name])) {
            $filter = $filters[$this->name]['value'];
            if ($filter != 'no_filter') {
                $query->where($this->tableName . '.' . $filters[$this->name]['name'], $filters[$this->name]['value']);
            }
        }
    }

    public function addJoinToQuery($tableName, $query) {
        $query = HelperTableMethods::addJoin(
            $query,
            $this->getReferenceTableName(), 
            $tableName . '.' . $this->referenceColumnName, 
            $this->getReferenceTableName() . '.id', 
            'left'
        );
    }

    public function getColumnNameWithTableName($tableName) {
        $renderColumnNames = array_map(function($columnName) use($tableName) {
            return $tableName . '.' . $columnName;
        }, $this->getRenderColumnNames());
        return 'CONCAT(' . StringMethods::concatenateStrings($renderColumnNames, ", ' - '") . ') AS ' . $this->referenceColumnName;
    }
}
