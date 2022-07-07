<?php

namespace Ezegyfa\LaravelHelperMethods\Database\FormGenerating;

use Ezegyfa\LaravelHelperMethods\HttpMethods;
use Illuminate\Support\Facades\Session;

class ColumnInfos {
    public $name;
    public $type;
    public $isNull;
    public $default;
    public $validationErrors;

    public function __construct($name, $type, $isNull, $default = null, $validationErrors = []) {
        $this->name = $name;
        $this->type = $type;
        if ($isNull == 'YES') {
            $this->isNull = true;
        }
        else if ($isNull == 'NO') {
            $this->isNull = false;
        }
        else {
            throw new \Exception('Unexpected database value for isNull');
        }
        $this->default = $default;
        if (count($validationErrors) == 0) {
            $this->setValidationErrors(HttpMethods::getValidationErrors());
        }
        else {
            $this->validationErrors = $validationErrors;
        }
    }

    public function getFormInfos($withOldValue = true) {
        $formInfos = $this->getSpecificFormInfos();
        $formInfos['name'] = $this->name;
        $formInfos['label'] = __($this->name);
        if ($this->name == 'email') {
            $formInfos['value_type'] = 'email';
        }
        else if ($this->name == 'phone' || $this->name == 'telephone') {
            $formInfos['value_type'] = 'tel';
        }
        $formInfos['default'] = $this->getDefault($withOldValue);
        $formInfos['required'] = !$this->isNull;
        $formInfos['validation_errors'] = $this->validationErrors;
        return (object)$formInfos;
    }

    public function getDefault($withOldValue) {
        $oldValues = Session::get('_old_input');
        if ($withOldValue && isset($oldValues[$this->name])) {
            return $oldValues[$this->name];
        }
        else {
            return $this->default;
        }
    }

    public function getSpecificFormInfos() {
        switch ($this->getDataType()) {
            case 'int':
            case 'bigint':
            case 'decimal':
            case 'tinyint':
                return [
                    'type' => 'input',
                    'value_type' => 'number',
                    'max' => $this->getMaxFromLength(),
                    'min' => $this->getMinFromLength(),
                ];
            case 'varchar':
                return [
                    'type' => 'input',
                    'value_type' => 'text',
                    'max_length' => $this->getLength(),
                ];
            case 'text':
                return [
                    'type' => 'textarea',
                ];
            case 'date':
                return [
                    'type' => 'input',
                    'value_type' => 'date',
                ];
            case 'timestamp':
                return [
                    'type' => 'input',
                    'value_type' => 'datetime-local',
                ];
            default:
                throw new \Exception("Invalid database column type");
        }
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
        $validator = $this->getSpecificValidator();
        if ($this->name == 'email') {
            $validator[] = 'email';
        }
        else if ($this->name == 'phone' || $this->name == 'telephone') {
            $validator[] = 'regex:/([0-9]|\+){0,14}/';
        }
        if (!$this->isNull) {
            $validator[] = 'required';
        }
        return $validator;
    }

    public function getSpecificValidator() {
        switch ($this->getDataType()) {
            case 'int':
            case 'bigint':
            case 'decimal':
            case 'tinyint':
                return [
                    'int',
                    'max:' . $this->getMaxFromLength(),
                    'min:' . $this->getMinFromLength(),
                ];
            case 'varchar':
                return [
                    'string',
                    'max:' . $this->getLength(),
                ];
            case 'text':
                return ['string'];
            case 'date':
                return ['date'];
            case 'timestamp':
                return ['timestamp'];
            default:
                throw new \Exception("Invalid database column type");
        }
    }

    public function setValidationErrors($validationErrors) {
        foreach ($validationErrors as $inputName => $inputErrors) {
            if ($inputName == $this->name) {
                $this->validationErrors = $inputErrors;
                return;
            }
        }
    }
}
