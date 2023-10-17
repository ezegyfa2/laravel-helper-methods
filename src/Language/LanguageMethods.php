<?php

namespace Ezegyfa\LaravelHelperMethods\Language;

use Ezegyfa\LaravelHelperMethods\FolderMethods;
use Ezegyfa\LaravelHelperMethods\HttpMethods;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\App;

class LanguageMethods
{
    public static function getLanguageUrls() {
        $urls = [];
        foreach (static::getTranslatedLanguages() as $language) {
            $urls[$language] = static::getUrlWithLanguage(\URL::full(), $language);
        }
        return $urls;
    }

    public static function getTranslationUrlObjects() {
        $urlPath = HttpMethods::getCurrentUrlPath();
        if (strpos($urlPath, '/') === 0) {
            $urlPath = substr($urlPath, 1);
        }
        $urlPathTranslationKey = static::getTranslationKey($urlPath);
        //dd($urlPathTranslationKey);
        if ($urlPathTranslationKey) {
            $currentLanguage = App::currentLocale();
            $translationUrlObjects = array_map(function($language) use($urlPath, $urlPathTranslationKey) {
                App::setLocale($language);
                return (object) [
                    'name' => strtoupper($language),
                    'url' => str_replace($urlPath, __('routes.' . $urlPathTranslationKey), \URL::full()),
                ];
            }, static::getTranslatedLanguages());
            App::setLocale($currentLanguage);
            return $translationUrlObjects;
        }
        else {
            return static::getLanguageUrlObjects();
        }
    }

    public static function getLanguageUrlObjects() {
        $url = static::getCurrentUrlWithoutLanguageSegment();
        return array_map(function($language) use($url) {
            return (object) [
                'name' => strtoupper($language),
                'url' => static::getUrlWithLanguage($url, $language)
            ];
        }, static::getTranslatedLanguages());
    }

    // Doesn't get language from url
    public static function getCurrentUrlWithLanguage() {
        $currentLanguage = Session::get('language', Config::get('app.locale'));
        return static::getUrlWithLanguage(\URL::full(), $currentLanguage);
    }

    public static function getUrlWithLanguage(string $url, string $language) {
        $query = parse_url($url, PHP_URL_QUERY);
        $urlWithoutQuery = str_replace('?' . $query, '', $url);
        if ($urlWithoutQuery[strlen($urlWithoutQuery) - 1] == '/') {
            $urlWithoutQuery .= $language;
        }
        else {
            $urlWithoutQuery .= '/' . $language;
        }
        if ($query) {
            return $urlWithoutQuery . '?' . $query;
        }
        else {
            return $urlWithoutQuery;
        }
    }

    public static function getCurrentUrlWithoutLanguageSegment() {
        $lastSegment = static::getCurrentUrlLastSegment();
        if (in_array($lastSegment, LanguageMethods::getTranslatedLanguages())) {
            return str_replace('/' . $lastSegment, '', \URL::full());
        }
        else {
            return \URL::full();
        }
    }

    public static function getCurrentUrlLastSegment() {
        $segments = request()->segments();
        return end($segments);
    }

    public static function createTranslatedGetRoutes(string $url, $controllerAction) {
        static::createTranslatedRoutes($url, $controllerAction, function($urlParam, $controllerActionParam) {
            Route::get($urlParam, $controllerActionParam);
        });
    }

    public static function createTranslatedPostRoutes(string $url, $controllerAction) {
        static::createTranslatedRoutes($url, $controllerAction, function($urlParam, $controllerActionParam) {
            Route::post($urlParam, $controllerActionParam);
        });
    }

    public static function createTranslatedRoutes(string $url, $controllerAction, $routeCreator) {
        $urlToTranslate = str_replace('/', '.', $url);
        $routeCreator($url, $controllerAction);
        $currentLanguage = App::currentLocale();
        foreach(static::getTranslatedLanguages() as $language) {
            App::setLocale($language);
            if ($urlToTranslate == '.' || $urlToTranslate == '') {
                $routeCreator('/' . $language, $controllerAction);
            }
            else {
                $routeCreator('/' . __('routes' . $urlToTranslate), $controllerAction);
            }
        }
        App::setLocale($currentLanguage);
    }

    public static function createGetRouteWithLanguage(string $url, $controllerAction) {
        Route::get($url, $controllerAction);
        Route::get($url . '/{language}', $controllerAction)
            ->where(['language' => '[a-zA-Z]{2}']);
    }

    public static function getTranslations(string $translationFileName) {
        $currentLanguage = App::currentLocale();
        $translations = [];
        foreach (static::getTranslatedLanguages() as $language) {
            App::setLocale($language);
            $translations[$language] = \Lang::get($translationFileName);
        }
        App::setLocale($currentLanguage);
        return $translations;
    }

    public static function getTranslationKey(?string $translation) {
        if ($translation === null) {
            return null;
        }
        else {
            if (strpos('/', $translation) === 0) {
                $translation = substr($translation, 1);
            }
            $routeTranslations = static::getTranslations('routes');
            foreach(static::getTranslatedLanguages() as $language) {
                $translationKey = array_search($translation, $routeTranslations[$language]);
                if ($translationKey) {
                    return $translationKey;
                }
            }
            return null;
        }
    }

    public static function getTranslatedLanguages() {
        return FolderMethods::getFolderSubFolders(resource_path('lang'));
    }

    public static function getMetaDescription() {
        if (request()->path() == '/') {
            return __('welcome.meta_description');
        }
        else {
            return __(request()->path() . '.meta_description');
        }
    }
}
