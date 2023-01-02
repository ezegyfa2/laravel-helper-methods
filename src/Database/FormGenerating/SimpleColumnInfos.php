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
        if ($this->name == 'email') {
            $formInfos['data']['type'] = 'email';
        }
        else if ($this->name == 'phone' || $this->name == 'telephone') {
            $formInfos['data']['type'] = 'tel';
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
                    'form_item_type' => 'input',
                    'maxlength' => $this->getLength(),
                    'minlength' => 0,
                ], $translationPrefix)),
            ];
        }
        else {
            switch ($this->getDataType()) {
                case 'int':
                case 'bigint':
                case 'decimal':
                case 'tinyint':
                    if ($this->getLength() == 1) {
                        unset($formInfos['required']);
                        return [
                            'type' => 'checkbox-input',
                            'data' => array_merge($formInfos, [
                                'type' => 'checkbox',
                            ]),
                        ];
                    }
                    else {
                        return [
                            'type' => 'number-input',
                            'data' => array_merge($formInfos, $this->getFormInfosWithPlaceholder([
                                'max' => $this->getMaxFromLength(),
                                'min' => $this->getMinFromLength(),
                            ], $translationPrefix)),
                        ];
                    }
                case 'varchar':
                    return [
                        'type' => 'text-input',
                        'data' => array_merge($formInfos, $this->getFormInfosWithPlaceholder([
                            'form_item_type' => 'input',
                            'maxlength' => $this->getLength(),
                            'minlength' => 0,
                        ], $translationPrefix)),
                    ];
                case 'text':
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
                    throw new \Exception("Invalid database column type");
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
            case 'int':
            case 'bigint':
            case 'decimal':
            case 'tinyint':
                if ($this->getLength() == 1) {
                    unset($validator['required']);
                    $validator = array_filter($validator, function($validatorValue) {
                        return $validatorValue != 'required';
                    });
                    return array_merge($validator, [
                        'boolean',
                    ]);
                }
                else {
                    return array_merge($validator, [
                        'int',
                        'max:' . $this->getMaxFromLength(),
                        'min:' . $this->getMinFromLength(),
                    ]);
                }
            case 'varchar':
                return array_merge($validator, [
                    'string',
                    'max:' . $this->getLength(),
                ]);
            case 'text':
                return $validator;
            case 'date':
                return array_merge($validator, ['date']);
            case 'timestamp':
                return array_merge($validator, ['date_format:Y-m-d H:i']);
            default:
                throw new \Exception("Invalid database column type");
        }
    }
}
