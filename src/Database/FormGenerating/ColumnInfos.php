<?php

namespace Ezegyfa\LaravelHelperMethods\Database\FormGenerating;

use Ezegyfa\LaravelHelperMethods\HttpMethods;
use Illuminate\Support\Facades\Session;

class ColumnInfos {
    public $tableName;
    public $name;
    public $type;
    public $isNull;
    public $default;
    public $validationErrors;

    public function __construct($tableName, $name, $type, $isNull, $default = null, $validationErrors = []) {
        $this->tableName = $tableName;
        $this->name = $name;
        $this->type = $type;
        $this->isNull = static::convertIsNull($isNull);
        $this->default = $default;
        if (count($validationErrors) == 0) {
            $this->setValidationErrors(HttpMethods::getValidationErrors());
        }
        else {
            $this->validationErrors = $validationErrors;
        }
    }

    public function getFormInfos(string $labelPrefix = '', $withOldValues = null, $value = null) {
        if ($withOldValues == null) {
            return $this->getFormInfosWithoutErrorChecking($labelPrefix, HttpMethods::hasValidationError(), $value);
        }
        else {
            return $this->getFormInfosWithoutErrorChecking($labelPrefix, $withOldValues, $value);
        }
    }

    public function getFormInfosWithoutErrorChecking(string $translationPrefix = '', bool $withOldValues = true, $value = null) {
        return (object) [
            'name' => $this->name,
            'label' => __($translationPrefix .  '.label.' . $this->name),
            'value' => $this->getDefault($withOldValues, $value),
            'required' => !$this->isNull,
            'validation_errors' => $this->validationErrors,
        ];
    }

    public function getValidator() {
        if ($this->isNull) {
            return [];
        }
        else {
            return [ 'required' ];
        }
    }

    public function setValidationErrors($validationErrors) {
        foreach ($validationErrors as $inputName => $inputErrors) {
            if ($inputName == $this->name) {
                $this->validationErrors = $inputErrors;
                return;
            }
        }
        $this->validationErrors = [];
    }

    public function getFilterFormInfos(string $translationPrefix = '') {
        return (object) [
            'name' => $this->name,
            'label' => __($translationPrefix .  '.label.' . $this->name),
            'value' => $this->getDefault(true),
            'validation_errors' => $this->validationErrors,
        ];
    }

    protected function setFilterFormInfoValue($filterFormInfos, $valueName) {
        if (request()->get('filter-data') && array_key_exists($this->name, request()->get('filter-data')) && array_key_exists($valueName, request()->get('filter-data')[$this->name])) {
            $filterFormInfos->$valueName = request()->get('filter-data')[$this->name][$valueName];
        }
        return $filterFormInfos;
    }

    public function getDefault($withOldValues, $value = null) {
        //dd($value);
        if ($value == null) {
            $oldValues = Session::get('_old_input');
            if ($withOldValues && isset($oldValues[$this->name])) {
                return $oldValues[$this->name];
            }
            else {
                return $this->default;
            }
        }
        else {
            return $value;
        }
    }

    public static function convertIsNull($isNull) {
        if (gettype($isNull) == 'boolean') {
            return $isNull;
        }
        else if ($isNull == 'YES') {
            return true;
        }
        else if ($isNull == 'NO') {
            return false;
        }
        else {
            throw new \Exception('Unexpected database value for isNull: ' . $isNull);
        }
    }
}
