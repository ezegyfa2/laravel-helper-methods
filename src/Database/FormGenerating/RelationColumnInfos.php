<?php

namespace Ezegyfa\LaravelHelperMethods\Database\FormGenerating;

use Ezegyfa\LaravelHelperMethods\StringMethods;

class RelationColumnInfos extends ColumnInfos {
    public $referencedTableInfos;
    public $tableInfos;
    public $referenceColumnName;
    public $optionCreator;

    public function __construct($referencedTableInfos, $tableInfos, $referenceColumnName, $optionCreator = null) {
        parent::__construct(
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
        return (object)[
            'type' => 'select',
            'data' => (object)array_merge(parent::getFormInfos($labelPrefix, $withOldValues, $value), [
                'options' => $this->getOptions(),
            ])
        ];
    }

    public function getFilterFormInfos(string $translationPrefix = '') {
        $formInfoData = parent::getFormInfos($translationPrefix);
        $formInfoData['options'] = $this->getOptions();
        array_push($formInfoData['options'], (object) [
            'text' => 'No filter',
            'value' => 'no_filter',
        ]);
        $formInfoData = $this->setFilterFormInfoValue($formInfoData, 'value');
        return (object) [
            'type' => 'select',
            'data' => $formInfoData
        ];
    }

    public function getOptions() {
        if ($this->optionCreator) {
            $rows = \DB::table($this->referencedTableInfos->name)->select()->get();
            return array_map($this->optionCreator, $rows);
        }
        else {
            $renderColumnNames = $this->getRenderColumnNames();
            $rows = \DB::table($this->getReferenceTableName())->select(array_merge($renderColumnNames, ['id']))->get()->toArray();
            return array_map(function($row) use($renderColumnNames) {
                return (object) [
                    'text' => $this->getRowLabel($row, $renderColumnNames),
                    'value' => $row->id,
                ];
            }, $rows);
        }
    }

    public function getRenderValues(int $selectedPageNumber, int $rowToShowCount) {
        $columnNames = array_map(function($columnName) {
            return 'table2.' . $columnName;
        }, $this->getRenderColumnNames());
        $rows = \DB::table($this->tableInfos->name . ' as table1')
            ->leftJoin($this->referencedTableInfos->name . ' as table2', 'table1.' . $this->referenceColumnName, 'table2.id')
            ->select($columnNames)
            ->orderBy('table1.id')
            ->limit($rowToShowCount)
            ->offset(($selectedPageNumber - 1) * $rowToShowCount)
            ->get()->toArray();
        return array_map(function($row) {
            return $this->getRowLabel($row, $this->getRenderColumnNames());
        }, $rows);
    }

    public function getRenderSelect() {
        return 'CONCAT(' . StringMethods::concatenateStrings($this->getRenderColumnNamesWithTableName(), ', " - ", ') . ')';
    }

    public function getRenderColumnNamesWithTableName() {
        return array_map(function ($renderColumnName) {
            if (str_contains($renderColumnName, '.')) {
                return $renderColumnName;
            }
            else {
                return $this->getReferenceTableName() . '.' . $renderColumnName;
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
            return $row->$renderColumnName;
        }, $renderColumnNames);
        return StringMethods::shortString(StringMethods::concatenateStrings($renderValues, ' - '));
    }

    public function getReferenceColumnInfos() {
        return $this->tableInfos->columnInfos[$this->referenceColumnName];
    }

    public function getReferenceTableName() {
        return $this->referencedTableInfos->name;
    }

    public function addFilterToQuery($tableName, $query, $filters) {
        if (array_key_exists($this->name, $filters) && array_key_exists('value', $filters[$this->name])) {
            $filter = $filters[$this->name]['value'];
            if ($filter != 'no_filter') {
                $query->where($tableName . '.' . $filters[$this->name]['name'], $filters[$this->name]['value']);
            }
        }
    }

    public function addJoinToQuery($tableName, $query) {
        $query->leftJoin($this->getReferenceTableName(), $tableName . '.' . $this->referenceColumnName, $this->getReferenceTableName() . '.id');
    }

    public function getColumnNameWithTableName($tableName) {
        $renderColumnNames = array_map(function($columnName) use($tableName) {
            return $tableName . '.' . $columnName;
        }, $this->getRenderColumnNames());
        return 'CONCAT(' . StringMethods::concatenateStrings($renderColumnNames, ", ' - '") . ') AS ' . $this->referenceColumnName;
    }
}
