<?php

namespace Ezegyfa\LaravelHelperMethods\Language;

use Ezegyfa\LaravelHelperMethods\FolderMethods;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Config;

class LanguageMethods
{
    public static function getLanguageUrls() {
        $urls = [];
        foreach (static::getTranslatedLanguages() as $language) {
            $urls[$language] = static::getUrlWithLanguage(\URL::full(), $language);
        }
        return $urls;
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

    public static function getTranslatedLanguages() {
        return FolderMethods::getFolderSubFolders(resource_path('lang'));
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

    public static function createGetRouteWithLanguage(string $url, $controllerAction) {
        Route::get($url, $controllerAction);
        Route::get($url . '/{language}', $controllerAction)
            ->where(['language' => '[a-zA-Z]{2}']);
    }
}
