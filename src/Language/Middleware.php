<?php

namespace Ezegyfa\LaravelHelperMethods\Language;

use Ezegyfa\LaravelHelperMethods\HttpMethods;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

class Middleware
{
    public function handle($request, \Closure $next)
    {
        $lastSegment = LanguageMethods::getCurrentUrlLastSegment();
        if (in_array($lastSegment, LanguageMethods::getTranslatedLanguages())) {
            $this->changeLanguage($lastSegment);
        }
        else {
            $routeTranslation = $this->getRouteTranslationLanguage(HttpMethods::getCurrentUrlPath());
            if ($routeTranslation) {
                $this->changeLanguage($routeTranslation);
            }
            else if (Session::has('language')) {
                App::setLocale(Session::get('language'));
            }
        }
        
        return $next($request);
    }

    protected function getRouteTranslationLanguage(?string $urlPath) {
        $routeTranslations = LanguageMethods::getTranslations('routes');
        foreach (LanguageMethods::getTranslatedLanguages() as $language) {
            if (is_array($routeTranslations[$language]) && in_array($urlPath, $routeTranslations[$language])) {
                return $language;
            }
        }
        return null;
    }

    protected function changeLanguage(?string $newLanguage) {
        App::setLocale($newLanguage);
        \URL::defaults(['language' => $newLanguage]);
        Session::start();
        Session::put('language', $newLanguage);
        Session::save();
    }
}
