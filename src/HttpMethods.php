<?php

namespace Ezegyfa\LaravelHelperMethods;

use Ezegyfa\LaravelHelperMethods\Database\FormGenerating\DatabaseInfos;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class HttpMethods
{
    public static function getFormResponseFromException(Exception $e)
    {
        return response([
            'message' => $e->getMessage(),
        ], 422);
    }

    public static function getStoreRequest(Request $request, String $tableName, $successMessage, $successRoute, $errorRoute = null) {
        $tableInfos = DatabaseInfos::getTableInfos()[$tableName];
        try {
            $request->validate($tableInfos->getValidators());
            \DB::table($tableName)->insert($tableInfos->filterData(request()->all()));
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
        return redirect($successRoute)->with('success_message', $successMessage);
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
        return __('validation.unique', [
            'attribute' => StringMethods::concatenateStrings($convertedUniqueRule, ', ') . ' combination'
        ]);
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
