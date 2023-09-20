<?php

namespace Ezegyfa\LaravelHelperMethods;

class DynamicTemplateMethods
{
    public static function getTranslatedTemplateDynamicPage(
        $templateTypeName, 
        $compiledTemplatePath, 
        $templateParams = new \stdObject, 
        array $scriptPaths = [], 
        array $stylePaths = []
    ) {
        $templatePath = base_path($compiledTemplatePath);
        foreach (static::getTranslatedTemplateParamsFromFile($templatePath) as $key => $value) {
            $templateParams->$key = $value;
        }
        //$templateParams = [];
        return static::getTemplateDynamicPage($templateTypeName, $templateParams, $scriptPaths, $stylePaths);
    }

    public static function getTemplateDynamicPage(
        $templateTypeName, 
        $templateParams = new \stdObject, 
        array $scriptPaths = [], 
        array $stylePaths = []
    ) {
        $scriptPaths = [ 'basicPackages', ...$scriptPaths ];
        $stylePaths = [ 'bootstrap.min', ...$stylePaths ];
        return view('ezegyfa::dynamicPage', compact('templateTypeName', 'templateParams', 'scriptPaths', 'stylePaths'));
    }

    public static function getTranslatedTemplateParamsFromFile($templateFilePath, $paramPrefix = '') {
        $templateContent = str_replace('export default', '', file_get_contents($templateFilePath));
        $template = json_decode($templateContent, null, 512, JSON_THROW_ON_ERROR);
        return static::getTemplateParamTranslations($template, $paramPrefix);
    }

    public static function getTemplateParamTranslations($template, $paramPrefix = '') {
        $translations = new \stdClass();
        static::collectTemplateParamTranslations($template, $translations, $paramPrefix);
        return $translations;
    }

    public static function collectTemplateParamTranslations($template, $paramTranslations, $paramPrefix = '') {
        if (is_array($template)) {
            foreach ($template as $templateValue) {
                static::collectTemplateParamTranslations($templateValue, $paramTranslations, $paramPrefix);
            }
        }
        else if (gettype($template) == 'object') {
            foreach (array_keys(get_object_vars($template)) as $key) {
                static::collectTemplateParamTranslations($template->$key, $paramTranslations, $paramPrefix);
            }
            return $template;
        }
        else if (gettype($template) == 'string' && strpos($template, '--') === 0) {
            $paramName = substr($template, 2);
            static::collectTemplateParamTranslation($paramName, $paramTranslations, $paramPrefix);
        }
    }

    public static function collectTemplateParamTranslation($paramName, $paramTranslations, $paramPrefix = '') {
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
        $currentTranslationParent->$partToTranslate = static::getTranslatedValue($paramName, $paramPrefix);
    }

    public static function getTranslatedValue($paramName, $paramPrefix = '') {
        if ($paramPrefix == '') {
            return __($paramName);
        }
        else {
            return __($paramPrefix . '.' . $paramName);
        }
    }

    public static function replaceTemplateParams($template, $paramPrefix = '', $params = []) {
        if (is_array($template)) {
            return array_map(function($templateValue) use($params, $paramPrefix) {
                return static::replaceTemplateParams($templateValue, $paramPrefix, $params);
            }, $template);
        }
        else if (gettype($template) == 'object') {
            foreach (array_keys(get_object_vars($template)) as $key) {
                $value = static::replaceTemplateParams($template->$key, $paramPrefix, $params);
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
                return __($paramPrefix . '.' . $paramName);
            }
        }
        else {
            return $template;
        }
    }
}
