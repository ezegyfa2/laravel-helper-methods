<?php

namespace Ezegyfa\LaravelHelperMethods\Database\FormGenerating;

use Illuminate\Support\Facades\Lang;

class SimpleColumnInfos extends ColumnInfos {
    public $name;
    public $type;
    public $isNull;
    public $default;
    public $validationErrors;

    public function __construct($name, $type, $isNull, $default = null, $validationErrors = []) {
        parent::__construct($name, $type, $isNull, $default, $validationErrors);
    }

    public function getFormInfos(string $labelPrefix = '', $withOldValues = null, $value = null) {
        $formInfos = $this->getSpecificFormInfos($labelPrefix, $withOldValues, $value);
        if ($this->name == 'phone' || $this->name == 'telephone') {
            $formInfos['type'] = 'phone-input';
        }
        $formInfos['data'] = (object)$formInfos['data'];
        return (object)$formInfos;
    }

    public function getSpecificFormInfos(string $translationPrefix = '', bool $withOldValues = true, $value = null) {
        $formInfos = parent::getFormInfos($translationPrefix, $withOldValues, $value);
        $dataType = $this->getDataType();
        if (($dataType == 'varchar' || $dataType == 'text') && $this->name == 'email') {
            return [
                'type' => 'email-input',
                'data' => array_merge($formInfos, $this->getFormInfosWithPlaceholder([
                    'maxlength' => $this->getLength(),
                    'minlength' => 0,
                ], $translationPrefix)),
            ];
        }
        else {
            switch ($this->getDataType()) {
                case 'bit':
                    unset($formInfos['required']);
                    return [
                        'type' => 'checkbox-input',
                        'data' => $formInfos,
                    ];
                case 'int':
                case 'bigint':
                case 'decimal':
                case 'tinyint':
                    return [
                        'type' => 'number-input',
                        'data' => array_merge($formInfos, $this->getFormInfosWithPlaceholder([
                            'max' => $this->getMaxFromLength(),
                            'min' => $this->getMinFromLength(),
                        ], $translationPrefix)),
                    ];
                case 'varchar':
                    return [
                        'type' => 'text-input',
                        'data' => array_merge($formInfos, $this->getFormInfosWithPlaceholder([
                            'maxlength' => $this->getLength(),
                            'minlength' => 0,
                        ], $translationPrefix)),
                    ];
                case 'text':
                case 'mediumtext':
                case 'largetext':
                    return [
                        'type' => 'textarea',
                        'data' => array_merge($formInfos, $this->getFormInfosWithPlaceholder([], $translationPrefix)),
                    ];
                case 'date':
                    return [
                        'type' => 'input',
                        'data' => array_merge($formInfos, $this->getFormInfosWithPlaceholder([], $translationPrefix)),
                    ];
                case 'timestamp':
                    return [
                        'type' => 'datetime-input',
                        'data' => array_merge($formInfos, $this->getFormInfosWithPlaceholder([], $translationPrefix)),
                    ];
                default:
                    $this->invalidColumnType();
            }
        }
    }

    public function getFormInfosWithPlaceholder($formInfos, $translationPrefix) {
        $placeholderTranslationName = $translationPrefix .  '.' . $this->name . '.placeholder';
        if (Lang::has($placeholderTranslationName)) {
            $formInfos['placeholder'] = __($placeholderTranslationName);
        }
        return $formInfos;
    }

    public function getMinFromLength() {
        if (str_contains('unsigned', $this->type)) {
            return 0;
        }
        else {
            return -$this->getMaxFromLength();
        }
    }

    public function getMaxFromLength() {
        $length = $this->getLength();
        $max = 1;
        for ($i = 0; $i < $length; ++$i) {
            $max *= 10;
        }
        return $max;
    }

    public function isCheckBox() {
        return $this->getDataType() == 'tinyint' && $this->getLength() == 1;
    }

    public function getDataType() {
        $dataType = explode(' ', $this->type)[0];
        return explode('(', $dataType)[0];
    }

    public function getLength() {
        $length = explode('(', $this->type)[1];
        $length = explode(',', $length)[0];
        $length = explode(')', $length)[0];
        $length = intval($length);
        return $length;
    }

    public function getValidator() {
        $validator = $this->getValidatorWithTypeValues();
        if ($this->name == 'email') {
            $validator[] = 'email';
        }
        else if ($this->name == 'phone' || $this->name == 'telephone') {
            $validator[] = 'regex:/([0-9]|\+){0,14}/';
        }
        return $validator;
    }

    public function getValidatorWithTypeValues() {
        $validator = parent::getValidator();
        switch ($this->getDataType()) {
            case 'bit':
                unset($validator['required']);
                $validator = array_filter($validator, function($validatorValue) {
                    return $validatorValue != 'required';
                });
                return array_merge($validator, [
                    'boolean',
                ]);
            case 'int':
            case 'bigint':
            case 'decimal':
            case 'tinyint':
                return array_merge($validator, [
                    'int',
                    'max:' . $this->getMaxFromLength(),
                    'min:' . $this->getMinFromLength(),
                ]);
            case 'varchar':
                return array_merge($validator, [
                    'string',
                    'max:' . $this->getLength(),
                ]);
            case 'text':
            case 'mediumtext':
            case 'largetext':
                return $validator;
            case 'date':
                return array_merge($validator, ['date']);
            case 'timestamp':
                return array_merge($validator, ['date_format:Y-m-d H:i']);
            default:
                $this->invalidColumnType();
        }
    }

    public function getFilterFormInfos(string $translationPrefix = '') {
        switch ($this->getDataType()) {
            case 'bit':
                return [
                    'type' => 'checkbox-input',
                    'data' => $this->getFilterFormInfosWithValue(),
                ];
            case 'int':
            case 'bigint':
            case 'decimal':
            case 'tinyint':
                $filterFormInfos = $this->getFilterFormInfosWithFromToValue();
                if (array_key_exists('from_value', $filterFormInfos)) {
                    $filterFormInfos['from_value'] = (int)$filterFormInfos['from_value'];
                }
                if (array_key_exists('to_value', $filterFormInfos)) {
                    $filterFormInfos['to_value'] = (int)$filterFormInfos['to_value'];
                }
                return [
                    'type' => 'number-input',
                    'data' => $filterFormInfos,
                ];
            case 'varchar':
            case 'text':
            case 'mediumtext':
            case 'largetext':
                return [
                    'type' => 'text-input',
                    'data' => $this->getFilterFormInfosWithValue(),
                ];
            case 'date':
                return [
                    'type' => 'date-input',
                    'data' => $this->getFilterFormInfosWithFromToValue(),
                ];
            case 'timestamp':
                return [
                    'type' => 'datetime-input',
                    'data' => $this->getFilterFormInfosWithFromToValue(),
                ];
            default:
                $this->invalidColumnType();
        }
    }

    public function getFilterFormInfosWithValue(string $translationPrefix = '') {
        $filterFormInfos = parent::getFilterFormInfos($translationPrefix);
        $filterFormInfos = $this->setFilterFormInfoValue($filterFormInfos, 'value');
        return $filterFormInfos;
    }

    public function getFilterFormInfosWithFromToValue(string $translationPrefix = '') {
        $filterFormInfos = parent::getFilterFormInfos($translationPrefix);
        $filterFormInfos['from_label'] = __('from');
        $filterFormInfos['to_label'] = __('to');
        $filterFormInfos = $this->setFilterFormInfoValue($filterFormInfos, 'from_value');
        $filterFormInfos = $this->setFilterFormInfoValue($filterFormInfos, 'to_value');
        return $filterFormInfos;
    }

    public function addFilterToQuery($tableName, $query, $filters) {
        if (array_key_exists($this->name, $filters)) {
            $filter = $filters[$this->name];
            switch ($this->getDataType()) {
                case 'bit':
                    if (array_key_exists('value', $filter)) {
                        if ($filter['value'] == 'on') {
                            $query->where($tableName . '.' . $this->name, 'TRUE');
                        }
                        else if ($filter['value'] == 'off') {
                            $query->where($tableName . '.' . $this->name, 'FALSE');
                        }
                        else {
                            throw new \Exception('Invalid checkbox value for column ' . $this->name);
                        }
                    }
                    break;
                case 'int':
                case 'bigint':
                case 'decimal':
                case 'tinyint':
                case 'date':
                case 'timestamp':
                    if (array_key_exists('from_value', $filter)) {
                        $query->where($tableName . '.' . $filter['name'], '>=', $filter['from_value']);
                    }
                    if (array_key_exists('to_value', $filter)) {
                        $query->where($tableName . '.' . $filter['name'], '<=', $filter['to_value']);
                    }
                    break;
                case 'varchar':
                case 'text':
                case 'mediumtext':
                case 'largetext':
                    if (array_key_exists('value', $filter)) {
                        $query->where($tableName . '.' . $filter['name'], 'LIKE', '%' . $filter['value'] . '%');
                    }
                    break;
                default:
                    $this->invalidColumnType();
            }
        }
    }

    public function addOrderByToQuery($tableName, $query, $order = 'ASC') {
        $query->orderBy();
    }

    public function getColumnNameWithTableName($tableName) {
        return $tableName . '.' . $this->name;
    }

    protected function invalidColumnType() {
        throw new \Exception("Invalid database column type " . $this->getDataType());
    }
}
