<?php

namespace Ezegyfa\LaravelHelperMethods\Database\FormGenerating;

use Ezegyfa\LaravelHelperMethods\Database\HelperMethods;
use Illuminate\Support\Facades\Lang;
use Illuminate\Validation\Rules\Password;
use Faker\Factory;

class SimpleColumnInfos extends ColumnInfos {

    public function __construct($tableName, $name, $type, $isNull, $default = null, $validationErrors = []) {
        parent::__construct($tableName, $name, $type, $isNull, $default, $validationErrors);
    }

    public function getFormInfos(string $translationPrefix = '', $withOldValues = null, $value = null) {
        $formInfos = $this->getSpecificFormInfos($translationPrefix, $withOldValues, $value);
        if ($this->isPhone()) {
            $formInfos->type = 'phone-input';
        }
        return (object)$formInfos;
    }

    public function getSpecificFormInfos(string $translationPrefix = '', bool $withOldValues = true, $value = null) {
        $formInfos = parent::getFormInfos($translationPrefix, $withOldValues, $value);
        $dataType = $this->getDataType();
        if ($this->isEmail()) {
            $formInfos->maxlength = $this->getLength();
            $formInfos->minlength = 0;
            return [
                'type' => 'email-input',
                'data' => $this->setFormInfosWithPlaceholder($formInfos, $translationPrefix),
            ];
        }
        else {
            switch ($this->getDataType()) {
                case 'bit':
                    unset($formInfos['required']);
                    return (object) [
                        'type' => 'checkbox-input',
                        'data' => $formInfos,
                    ];
                case 'tinyint':
                case 'smallint':
                case 'mediumint':
                case 'int':
                case 'bigint':
                    $formInfos->max = $this->getMax();
                    $formInfos->min = $this->getMin();
                    return (object) [
                        'type' => 'number-input',
                        'data' => $this->setFormInfosWithPlaceholder($formInfos, $translationPrefix),
                    ];
                case 'varchar':
                    $formInfos->maxlength = $this->getLength();
                    $formInfos->minlength = 0;
                    return (object) [
                        'type' => 'text-input',
                        'data' => $this->setFormInfosWithPlaceholder($formInfos, $translationPrefix),
                    ];
                case 'text':
                case 'mediumtext':
                case 'largetext':
                    return (object) [
                        'type' => 'textarea',
                        'data' => $this->setFormInfosWithPlaceholder($formInfos, $translationPrefix),
                    ];
                case 'date':
                    return (object) [
                        'type' => 'input',
                        'data' => $this->setFormInfosWithPlaceholder($formInfos, $translationPrefix),
                    ];
                case 'timestamp':
                    return (object) [
                        'type' => 'datetime-input',
                        'data' => $this->setFormInfosWithPlaceholder($formInfos, $translationPrefix),
                    ];
                default:
                    $this->invalidColumnType();
            }
        }
    }

    public function setFormInfosWithPlaceholder($formInfos, $translationPrefix) {
        $placeholderTranslationName = $translationPrefix .  '.' . $this->name . '.placeholder';
        if (Lang::has($placeholderTranslationName)) {
            $formInfos->placeholder = __($placeholderTranslationName);
        }
        return $formInfos;
    }

    public function getValidator() {
        $validator = $this->getValidatorWithTypeValues();
        if ($this->isEmail()) {
            $validator[] = 'email';
        }
        else if ($this->isPhone()) {
            $validator[] = 'regex:/([0-9]|\+){0,14}/';
        }
        else if ($this->isPassword()) {
            $validator[] = Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised();
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
            case 'tinyint':
            case 'smallint':
            case 'mediumint':
            case 'int':
            case 'bigint':
                return array_merge($validator, [
                    'int',
                    'max:' . $this->getMax(),
                    'min:' . $this->getMin(),
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
                return (object) [
                    'type' => 'checkbox-input',
                    'data' => $this->getFilterFormInfosWithValue($translationPrefix),
                ];
            case 'tinyint':
            case 'smallint':
            case 'mediumint':
            case 'int':
            case 'bigint':
                $filterFormInfos = $this->getFilterFormInfosWithFromToValue($translationPrefix);
                if (isset($filterFormInfos->from_value)) {
                    $filterFormInfos->from_value = (int)$filterFormInfos->from_value;
                }
                else {
                    $filterFormInfos->from_value = 0;
                }
                if (isset($filterFormInfos->to_value)) {
                    $filterFormInfos->to_value = (int)$filterFormInfos->to_value;
                }
                else {
                    $filterFormInfos->to_value = $this->getMaxValue();
                }
                return (object) [
                    'type' => 'number-input',
                    'data' => $filterFormInfos,
                ];
            case 'varchar':
            case 'text':
            case 'mediumtext':
            case 'largetext':
                return (object) [
                    'type' => 'text-input',
                    'data' => $this->getFilterFormInfosWithValue($translationPrefix),
                ];
            case 'date':
                return (object) [
                    'type' => 'date-input',
                    'data' => $this->getFilterFormInfosWithFromToValue($translationPrefix),
                ];
            case 'timestamp':
                return (object) [
                    'type' => 'datetime-input',
                    'data' => $this->getFilterFormInfosWithFromToValue($translationPrefix),
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
        $filterFormInfos->from_label = __('from');
        $filterFormInfos->to_label = __('to');
        $filterFormInfos = $this->setFilterFormInfoValue($filterFormInfos, 'from_value');
        $filterFormInfos = $this->setFilterFormInfoValue($filterFormInfos, 'to_value');
        return $filterFormInfos;
    }

    public function getFakeValue() {
        $faker = Factory::create();
        if ($this->isEmail()) {
            return $faker->email();
        }
        else if ($this->isPhone()) {
            return $faker->numerify('##########');
        }
        else if ($this->isUrl()) {
            return $faker->url();
        }
        else {
            switch ($this->getDataType()) {
                case 'bit':
                    return $faker->boolean();
                case 'tinyint':
                case 'smallint':
                case 'mediumint':
                case 'int':
                case 'bigint':
                case 'decimal':
                    return $faker->numberBetween($this->getMin(), $this->getMax());
                case 'date':
                    return $faker->date();
                case 'timestamp':
                    return $faker->dateTime();
                case 'varchar':
                case 'text':
                case 'mediumtext':
                case 'largetext':
                    if ($this->getLength() <= 5) {
                        return $faker->regexify('[A-Za-z]{' . $faker->numberBetween(0, $this->getLength()) . '}');
                    }
                    else if ($this->getLength() < 3000) {
                        return $faker->text($faker->numberBetween(5, $this->getLength()));
                    }
                    else {
                        return $faker->text($faker->numberBetween(5, 3000));
                    }
                default:
                    $this->invalidColumnType();
            }
        }
    }

    public function addFilterToQuery($query, $filter) {
        switch ($this->getDataType()) {
            case 'bit':
                if (array_key_exists('value', $filter)) {
                    if ($filter['value'] == 'on') {
                        $query->where($this->tableName . '.' . $this->name, 'TRUE');
                    }
                    else if ($filter['value'] == 'off') {
                        $query->where($this->tableName . '.' . $this->name, 'FALSE');
                    }
                    else {
                        throw new \Exception('Invalid checkbox value for column ' . $this->name);
                    }
                }
                break;
            case 'tinyint':
            case 'smallint':
            case 'mediumint':
            case 'int':
            case 'bigint':
            case 'decimal':
            case 'date':
            case 'timestamp':
                if (array_key_exists('from_value', $filter)) {
                    $query->where($this->tableName . '.' . $filter['name'], '>=', $filter['from_value']);
                }
                if (array_key_exists('to_value', $filter)) {
                    $query->where($this->tableName . '.' . $filter['name'], '<=', $filter['to_value']);
                }
                if (array_key_exists('values', $filter) && is_array($filter['values'])) {
                    $query->whereIn($this->tableName . '.' . $filter['name'], $filter['values']);
                }
                break;
            case 'varchar':
            case 'text':
            case 'mediumtext':
            case 'largetext':
                if (array_key_exists('value', $filter)) {
                    $query->where($this->tableName . '.' . $filter['name'], 'LIKE', '%' . $filter['value'] . '%');
                }
                break;
            default:
                $this->invalidColumnType();
        }
    }

    public function addOrderByToQuery($query, $order = 'ASC') {
        $query->orderBy($this->getColumnNameWithTableName(), $order);
    }

    public function isEmail() {
        return $this->name == 'email';
    }

    public function isPhone() {
        return $this->name == 'phone' || $this->name == 'telephone';
    }

    public function isPassword() {
        return $this->name == 'password';
    }

    public function isUrl() {
        return $this->name == 'url' || $this->name == 'link';
    }

    public function isCheckBox() {
        return ($this->getDataType() == 'tinyint' && $this->getLength() == 1) || $this->getDataType() == 'bit';
    }

    public function isDatetime() {
        return $this->getDataType() == 'timestamp';
    }

    public function getDataType() {
        $dataType = explode(' ', $this->type)[0];
        return explode('(', $dataType)[0];
    }

    public function getSelect() {
        switch ($this->getDataType()) {
            case 'varchar':
            case 'text':
            case 'mediumtext':
            case 'largetext':
                return HelperMethods::getShortStringQuery($this->tableName . '.' . $this->name) . ' as ' . $this->name;
            default:
                return $this->tableName . '.' . $this->name;
        }
    }

    public function getMax() {
        switch ($this->getDataType()) {
            case 'bigint': // 8 bite
                if ($this->isUnsigned()) {
                    return 18446744073709600000;
                }
                else {
                    return 9223372036854780000;
                }
            case 'int': // 4 bite
                if ($this->isUnsigned()) {
                    return 4294967295; 
                }
                else {
                    return 2147483647;
                }
            case 'mediumint': // 3 bite
                if ($this->isUnsigned()) {
                    return 16777216; 
                }
                else {
                    return 8388608;
                }
            case 'smallint': // 2 bite
                if ($this->isUnsigned()) {
                    return 65536; 
                }
                else {
                    return 32768;
                }
            case 'tinyint': // 1 bite
                if ($this->isUnsigned()) {
                    return 256; 
                }
                else {
                    return 128;
                }
            case 'decimal':
                return $this->getMaxFromLength();
        }
    }

    public function getMin() {
        if ($this->isUnsigned()) {
            return 0;
        }
        else {
            switch ($this->getDataType()) {
                case 'bigint': // 8 bite
                    return -9223372036854780000;
                case 'int': // 4 bite
                    return -2147483647;
                case 'mediumint': // 3 bite
                    return -8388608;
                case 'smallint': // 2 bite
                    return -32768;
                case 'tinyint': // 1 bite
                    return -128;
                case 'decimal':
                    return 0;
                default:
                    throw new \Exception('Can\'t get min for type ' . $this->getDataType());
            }
        }
    }

    public function getMinFromLength() {
        if ($this->isUnsigned()) {
            return 0;
        }
        else {
            return -$this->getMaxFromLength();
        }
    }

    public function getMaxValue() {
        $columnName = $this->name;
        $result = \DB::table($this->tableName)->select(\DB::raw('max(' . $columnName . ') AS ' . $columnName))->get();
        $result = $result[0]->$columnName;
        if ($result) {
            return $result;
        }
        else {
            return 0;
        }
    }

    public function getMaxFromLength() {
        $length = $this->getLength();
        if ($this->isUnsigned()) {
            --$length;
        }
        $max = 1;
        for ($i = 0; $i < $length; ++$i) {
            $max *= 10;
        }
        return $max;
    }

    public function getLength() {
        $length = explode('(', $this->type)[1];
        $length = explode(',', $length)[0];
        $length = explode(')', $length)[0];
        $length = intval($length);
        return $length;
    }

    public function isUnsigned() {
        return str_contains($this->type, 'unsigned');
    }

    public function getColumnNameWithTableName() {
        return $this->tableName . '.' . $this->name;
    }

    protected function invalidColumnType() {
        throw new \Exception("Invalid database column type " . $this->getDataType());
    }
}
