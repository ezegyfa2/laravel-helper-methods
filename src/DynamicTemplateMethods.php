<?php

namespace Ezegyfa\LaravelHelperMethods;

class DynamicTemplateMethods
{
    public static function getTemplateDynamicPage($templateTypeName, $templateParams = []) {
        $template = static::getViewTemplate($templateTypeName, $templateParams);
        return view('dynamicPage', compact('template'));
    }

    public static function getViewTemplate($templateTypeName, $templateParams = []) {
        return (object)[
            'type' => $templateTypeName,
            'data' => (object)[
                'params' => $templateParams
            ]
        ];
    }

    public static function getTemplateParamTranslationsFromFile($templateFilePath, $paramSuffix = '') {
        $templateContent = str_replace('export default', '', file_get_contents($templateFilePath));
        $template = json_decode($templateContent, null, 512, JSON_THROW_ON_ERROR);
        return static::getTemplateParamTranslations($template, $paramSuffix);
    }

    public static function getTemplateParamTranslations($template, $paramSuffix = '') {
        $translations = new \stdClass();
        static::collectTemplateParamTranslations($template, $translations, $paramSuffix);
        return $translations;
    }

    public static function collectTemplateParamTranslations($template, $paramTranslations, $paramSuffix = '') {
        if (is_array($template)) {
            foreach ($template as $templateValue) {
                static::collectTemplateParamTranslations($templateValue, $paramTranslations, $paramSuffix);
            }
        }
        else if (gettype($template) == 'object') {
            foreach (array_keys(get_object_vars($template)) as $key) {
                static::collectTemplateParamTranslations($template->$key, $paramTranslations, $paramSuffix);
            }
            return $template;
        }
        else if (gettype($template) == 'string' && strpos($template, '++') === 0) {
            $paramName = substr($template, 2);
            static::collectTemplateParamTranslation($paramName, $paramTranslations, $paramSuffix);
        }
    }

    public static function collectTemplateParamTranslation($paramName, $paramTranslations, $paramSuffix = '') {
        $paramParts = explode('.', $paramName);
        $currentTranslationParent = $paramTranslations;
        $currentTranslation = $paramTranslations;
        foreach ($paramParts as $paramPart) {
            if (!isset($currentTranslation->$paramPart)) {
                $currentTranslation->$paramPart = new \stdClass();
            }
            $currentTranslationParent = $currentTranslation;
            $currentTranslation = $currentTranslation->$paramPart;
        }
        $partToTranslate = end($paramParts);
        $currentTranslationParent->$partToTranslate = static::getTranslatedValue($paramName, $paramSuffix);
    }

    public static function getTranslatedValue($paramName, $paramSuffix = '') {
        if ($paramSuffix == '') {
            return __($paramSuffix . '.' . $paramName);
        }
        else {
            return __($paramSuffix . '.' . $paramName);
        }
    }

    public static function replaceTemplateParams($template, $paramSuffix = '', $params = []) {
        if (is_array($template)) {
            return array_map(function($templateValue) use($params, $paramSuffix) {
                return static::replaceTemplateParams($templateValue, $paramSuffix, $params);
            }, $template);
        }
        else if (gettype($template) == 'object') {
            foreach (array_keys(get_object_vars($template)) as $key) {
                $value = static::replaceTemplateParams($template->$key, $paramSuffix, $params);
                $template->$key = $value;
            }
            return $template;
        }
        else if (gettype($template) == 'string' && strpos($template, '++') === 0) {
            $paramName = substr($template, 2);
            if (array_key_exists($paramName, $params)) {
                return $params[$paramName];
            }
            else {
                return __($paramSuffix . '.' . $paramName);
            }
        }
        else {
            return $template;
        }
    }
}
