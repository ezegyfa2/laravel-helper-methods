<?php

namespace Ezegyfa\LaravelHelperMethods;

use Ezegyfa\LaravelHelperMethods\Database\FormGenerating\DatabaseInfos;
use Exception;
use Ezegyfa\LaravelHelperMethods\Database\FormGenerating\SimpleColumnInfos;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class HttpMethods
{
    public static function getFormResponseFromException(Exception $e) {
        return response([
            'message' => $e->getMessage(),
        ], 422);
    }

    public static function getStoreRequest(Request $request, String $tableName, $successMessage, $successRoute, $errorRoute = null) {
        try {
            $tableInfos = DatabaseInfos::getTableInfos()[$tableName];
            $request->merge(static::getCorrectedRequestData($request->all(), $tableInfos));
            $request->validate($tableInfos->getValidators());
            \DB::table($tableName)->insert($tableInfos->filterData(request()->all()));
            return redirect($successRoute)->with('success_message', $successMessage);
        }
        catch (ValidationException $e) {
            $errorMessages = static::updateErrors($e->errors(), $e->validator->failed());
            if ($errorRoute) {
                return redirect()->to($errorRoute)->withInput(request()->all())->withErrors($errorMessages);
            }
            else {
                return redirect()->back()->withInput(request()->all())->withErrors($errorMessages);
            }
        }
    }

    public static function getUpdateRequest(Request $request, int $id, String $tableName, $successMessage, $successRoute, $errorRoute = null) {
        try {
            $tableInfos = DatabaseInfos::getTableInfos()[$tableName];
            $request->merge(static::getCorrectedRequestData($request->all(), $tableInfos));
            $request->validate($tableInfos->getValidators($id));
            if (!\DB::table($tableName)->find($id)) {
                throw new \Exception('Item with id: ' . $id . 'doesn\'t exists in table ' . $tableName);
            }
            \DB::table($tableName)->where('id', $id)->update($tableInfos->filterData($request->all()));
            return redirect($successRoute)->with('success_message', $successMessage);
        }
        catch (ValidationException $e) {
            $errorMessages = static::updateErrors($e->errors(), $e->validator->failed());
            if ($errorRoute) {
                return redirect()->to($errorRoute)->withInput($request->all())->withErrors($errorMessages);
            }
            else {
                return redirect()->back()->withInput($request->all())->withErrors($errorMessages);
            }
        }
    }

    public static function getCorrectedRequestData($requestData, $tableInfos) {
        foreach ($tableInfos->getColumnNames() as $columnName) {
            if (array_key_exists($columnName, $requestData)) {
                if (static::isCheckBox($columnName, $tableInfos) && !array_key_exists($columnName, $requestData)) {
                    $requestData[$columnName] = false;
                }
                else {
                    $requestData[$columnName] = static::getCorrectedRequestValue($requestData[$columnName]);
                }
            }
        }
        return $requestData;
    }

    public static function isCheckBox($columnName, $tableInfos) {
        if (array_key_exists($columnName, $tableInfos->columnInfos)) {
            $columnInfo = $tableInfos->columnInfos[$columnName];
            return $columnInfo instanceof SimpleColumnInfos && $columnInfo->isCheckBox();
        }
        else {
            return false;
        }
    }

    public static function getCorrectedRequestValue($requestDataValue)
    {
        $datePattern = '/[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]T[0-9][0-9]:[0-9][0-9]/i';
        if (preg_replace($datePattern, '', $requestDataValue) == '') {
            return str_replace('T', ' ', $requestDataValue);
        }
        else if ($requestDataValue == 'on') {
            return 1;
        }
        else if ($requestDataValue == 'off') {
            return 0;
        }
        else {
            return $requestDataValue;
        }
    }

    public static function hasValidationError() {
        return count(static::getValidationErrors()) > 0;
    }

    public static function getValidationErrors() {
        $errors = session()->get('errors');
        if ($errors) {
            $errorBag = $errors->getBag('default');
            if ($errorBag) {
                return $errorBag->getMessages();
            }
            else {
                return [];
            }
        }
        else {
            return [];
        }
    }

    public static function updateErrors($errors, $errorRules) {
        foreach ($errors as $inputName => $error) {
            if (array_key_exists($inputName, $errorRules) && array_key_exists('Unique', $errorRules[$inputName])) {
                $uniqueRule = $errorRules[$inputName]['Unique'];
                $errors[$inputName] = static::getUpdatedUniqueMessage($uniqueRule);
            }
        }
        return $errors;
    }

    public static function getUpdatedUniqueMessage($uniqueRule) {
        $convertedUniqueRule = static::convertUniqueRule($uniqueRule);
        if (count($convertedUniqueRule) < 2) {
            return __('validation.unique', [
                'attribute' => $convertedUniqueRule[0]
            ]);
        } 
        else {
            return __('validation.unique', [
                'attribute' => StringMethods::concatenateStrings($convertedUniqueRule, ', ') . ' combination'
            ]);
        }
    }

    public static function convertUniqueRule($uniqueRule) {
        $correspondingUniqueRule = [
            $uniqueRule[1]
        ];
        for ($i = 4; $i < count($uniqueRule); $i += 2) {
            $correspondingUniqueRule[] = $uniqueRule[$i];
        }
        return $correspondingUniqueRule;
    }
}
